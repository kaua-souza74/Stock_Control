<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/config.php';

function e(mixed $value): string { return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function go(string $url = 'index.php'): never { header('Location: ' . $url); exit; }
function flash(string $message, string $type = 'success'): void { $_SESSION['flash'] = [$message, $type]; }
function csrf(): string { return $_SESSION['csrf'] ??= bin2hex(random_bytes(32)); }
function verifyCsrf(): void {
    if (!hash_equals(csrf(), (string)($_POST['csrf'] ?? ''))) { http_response_code(419); exit('Sessão expirada. Atualize a página e tente novamente.'); }
}
function isAdmin(): bool { return !empty($_SESSION['user']['is_admin']); }
function productImage(string $path): string {
    $path = trim($path);
    if ($path !== '' && is_file(__DIR__ . '/' . $path)) return $path;
    $fallback = basename(pathinfo($path, PATHINFO_FILENAME)) . '.webp';
    return $fallback !== '.webp' && is_file(__DIR__ . '/imagens/' . $fallback) ? 'imagens/' . $fallback : '';
}
function uploadImage(?array $file, ?string $current = null): string {
    if (!$file || $file['error'] === UPLOAD_ERR_NO_FILE) return $current ?? '';
    if ($file['error'] !== UPLOAD_ERR_OK || $file['size'] > 5 * 1024 * 1024) throw new RuntimeException('A imagem deve ter até 5 MB.');
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
    if (!isset($extensions[$mime])) throw new RuntimeException('Envie uma imagem JPG, PNG, WEBP ou GIF.');
    $dir = __DIR__ . '/imagens';
    if (!is_dir($dir)) mkdir($dir, 0775, true);
    $name = bin2hex(random_bytes(12)) . '.' . $extensions[$mime];
    if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $name)) throw new RuntimeException('Não foi possível salvar a imagem. Verifique a permissão da pasta imagens/.');
    return 'imagens/' . $name;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'login') {
            $stmt = db()->prepare('SELECT id,nome,email,senha,is_admin FROM usuarios WHERE email = ? LIMIT 1');
            $stmt->execute([trim((string)($_POST['email'] ?? ''))]);
            $user = $stmt->fetch();
            $password = (string)($_POST['senha'] ?? '');
            if (!$user || !(password_verify($password, $user['senha']) || hash_equals((string)$user['senha'], $password))) {
                throw new RuntimeException('E-mail ou senha inválidos. Confira seus dados e tente novamente.');
            }
            if (!password_get_info($user['senha'])['algo']) {
                db()->prepare('UPDATE usuarios SET senha = ? WHERE id = ?')->execute([password_hash($password, PASSWORD_DEFAULT), $user['id']]);
            }
            session_regenerate_id(true);
            $_SESSION['user'] = ['id' => (int)$user['id'], 'nome' => $user['nome'], 'email' => $user['email'], 'is_admin' => (bool)$user['is_admin']];
            go($user['is_admin'] ? 'index.php' : 'estoque.php');
        }
        if (empty($_SESSION['user'])) throw new RuntimeException('Entre na sua conta para continuar.');
        $pdo = db();
        if ($action === 'logout') { $_SESSION = []; session_destroy(); go(); }
        if ($action === 'save_product') {
            if (!isAdmin()) throw new RuntimeException('Apenas administradores podem cadastrar ou editar produtos.');
            $id = (int)($_POST['id'] ?? 0);
            $name = trim((string)($_POST['nome'] ?? ''));
            $description = trim((string)($_POST['descricao'] ?? ''));
            $qty = filter_var($_POST['quantidade'] ?? null, FILTER_VALIDATE_INT);
            $price = filter_var($_POST['preco'] ?? null, FILTER_VALIDATE_FLOAT);
            if ($name === '' || $description === '' || $qty === false || $qty < 0 || $price === false || $price < 0 || !isset($_POST['status'])) throw new RuntimeException('Preencha todos os campos corretamente.');
            $initialComment = trim((string)($_POST['comentario_inicial'] ?? ''));
            $pdo->beginTransaction();
            $current = ''; $previousQty = 0;
            if ($id) { $q = $pdo->prepare('SELECT imagem,quantidade FROM produtos WHERE id = ? FOR UPDATE'); $q->execute([$id]); $existing = $q->fetch(); if (!$existing) throw new RuntimeException('Produto não encontrado.'); $current = (string)$existing['imagem']; $previousQty = (int)$existing['quantidade']; }
            $image = uploadImage($_FILES['imagem'] ?? null, $current);
            if ($image === '') throw new RuntimeException('Selecione uma imagem para o produto.');
            if ($id) $pdo->prepare('UPDATE produtos SET nome=?,descricao=?,quantidade=?,preco=?,status=?,imagem=? WHERE id=?')->execute([$name,$description,$qty,$price,(int)$_POST['status'],$image,$id]);
            else { $pdo->prepare('INSERT INTO produtos (nome,descricao,quantidade,preco,status,imagem) VALUES (?,?,?,?,?,?)')->execute([$name,$description,$qty,$price,(int)$_POST['status'],$image]); $id = (int)$pdo->lastInsertId(); }
            $difference = (int)$qty - $previousQty;
            if ($difference !== 0) $pdo->prepare('INSERT INTO movimentacoes (produto_id,usuario_id,tipo,quantidade,observacoes) VALUES (?,?,?,?,?)')->execute([$id,$_SESSION['user']['id'],$difference > 0 ? 'entrada' : 'saida',abs($difference),$id && $previousQty ? 'Ajuste de quantidade no cadastro do produto.' : 'Quantidade inicial do produto.']);
            if ($initialComment !== '') $pdo->prepare('INSERT INTO comentarios (produto_id,usuario_id,texto) VALUES (?,?,?)')->execute([$id,$_SESSION['user']['id'],$initialComment]);
            $pdo->commit();
            flash($id ? 'Produto atualizado com sucesso.' : 'Produto cadastrado com sucesso.'); go('#produtos');
        }
        if ($action === 'delete_product') {
            if (!isAdmin()) throw new RuntimeException('Apenas administradores podem excluir produtos.');
            $pdo->prepare('DELETE FROM produtos WHERE id = ?')->execute([(int)$_POST['produto_id']]);
            flash('Produto excluído.'); go('#produtos');
        }
        if ($action === 'movement') {
            if (!isAdmin()) throw new RuntimeException('Somente o administrador pode registrar movimentações de estoque.');
            $productId = (int)($_POST['produto_id'] ?? 0); $type = $_POST['tipo'] ?? ''; $qty = filter_var($_POST['quantidade'] ?? null, FILTER_VALIDATE_INT);
            if (!in_array($type, ['entrada','saida'], true) || $qty === false || $qty < 1) throw new RuntimeException('Informe um tipo e uma quantidade válida.');
            $pdo->beginTransaction();
            $q = $pdo->prepare('SELECT quantidade FROM produtos WHERE id=? AND status=1 FOR UPDATE'); $q->execute([$productId]); $current = $q->fetchColumn();
            if ($current === false || ($type === 'saida' && $qty > (int)$current)) throw new RuntimeException('Produto indisponível ou quantidade insuficiente para a saída.');
            $newQty = (int)$current + ($type === 'entrada' ? $qty : -$qty);
            $pdo->prepare('UPDATE produtos SET quantidade=? WHERE id=?')->execute([$newQty,$productId]);
            $obs = trim((string)($_POST['observacoes'] ?? ''));
            $pdo->prepare('INSERT INTO movimentacoes (produto_id,usuario_id,tipo,quantidade,observacoes) VALUES (?,?,?,?,?)')->execute([$productId,$_SESSION['user']['id'],$type,$qty,$obs ?: null]);
            $pdo->commit(); flash('Movimentação registrada e estoque atualizado.'); go('index.php#produtos');
        }
        if ($action === 'comment') {
            $text = trim((string)($_POST['texto'] ?? ''));
            if ($text === '') throw new RuntimeException('O comentário não pode ficar vazio.');
            $pdo->prepare('INSERT INTO comentarios (produto_id,usuario_id,texto) SELECT p.id,?,? FROM produtos p WHERE p.id=? AND p.status=1')->execute([$_SESSION['user']['id'],$text,(int)$_POST['produto_id']]);
            flash('Sucesso! Comentário cadastrado para o produto.'); go(isAdmin() ? 'index.php#produtos' : 'estoque.php#produtos');
        }
    } catch (Throwable $ex) {
        if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
        flash($ex instanceof PDOException ? 'Não foi possível concluir a operação no banco de dados.' : $ex->getMessage(), 'error'); go();
    }
}

$message = $_SESSION['flash'] ?? null; unset($_SESSION['flash']);
$user = $_SESSION['user'] ?? null;
if ($user && !isAdmin()) go('estoque.php');
$today = (new DateTimeImmutable('now'))->format('d/m/Y');
if (!$user) {
?><!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="theme-color" content="#F2E9D8"><title>Entrar · StockControl</title><link rel="stylesheet" href="assets/style.css"></head><body class="login-page"><main class="login-card"><a class="brand login-brand" href="index.php"><img src="assets/stockcontrol-logo.svg" alt="StockControl"></a><p class="eyebrow">Gestão de estoque</p><h1>Bem-vindo de volta</h1><p class="muted">Entre com sua conta para acompanhar os produtos.</p><?php if ($message): ?><div class="notice <?=e($message[1])?>"><?=e($message[0])?></div><?php endif; ?><form method="post" class="login-form"><input type="hidden" name="csrf" value="<?=e(csrf())?>"><input type="hidden" name="action" value="login"><label class="field">E-mail<input type="email" name="email" autocomplete="username" required placeholder="voce@empresa.com"></label><label class="field">Senha<input type="password" name="senha" autocomplete="current-password" required placeholder="Sua senha"></label><button class="button primary full-width">Entrar</button></form><p class="login-hint">Acesso inicial: admin@stockcontrol.com · admin123</p></main></body></html><?php exit; }

try {
    $pdo = db();
    $products = $pdo->query('SELECT p.*, (SELECT COUNT(*) FROM comentarios c WHERE c.produto_id=p.id) AS total_comentarios FROM produtos p WHERE p.status=1 ORDER BY p.nome')->fetchAll();
    $stats = $pdo->query('SELECT COUNT(*) total, COALESCE(SUM(quantidade),0) unidades, COALESCE(SUM(quantidade*preco),0) valor, COALESCE(SUM(quantidade<=5),0) baixo FROM produtos WHERE status=1')->fetch();
    $movements = $pdo->query('SELECT m.*,p.nome produto,u.nome usuario FROM movimentacoes m JOIN produtos p ON p.id=m.produto_id JOIN usuarios u ON u.id=m.usuario_id ORDER BY m.data_movimentacao DESC,m.id DESC LIMIT 6')->fetchAll();
} catch (Throwable $ex) { http_response_code(500); exit('Não foi possível conectar ao banco. Importe o SQL e confira as credenciais em config.php.'); }
$movementData = [];
foreach ($products as $product) {
    $history = $pdo->prepare('SELECT m.tipo,m.quantidade,m.data_movimentacao,m.observacoes,u.nome usuario FROM movimentacoes m JOIN usuarios u ON u.id=m.usuario_id WHERE m.produto_id=? ORDER BY m.data_movimentacao DESC,m.id DESC');
    $history->execute([$product['id']]);
    $movementData[$product['id']] = $history->fetchAll();
}
$initials = implode('', array_map(fn($part) => mb_substr($part, 0, 1), array_slice(preg_split('/\s+/', trim($user['nome'])), 0, 2)));
?><!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="theme-color" content="#F2E9D8"><title>StockControl · Painel</title><link rel="stylesheet" href="assets/style.css"></head><body><div class="app-shell">
<aside class="sidebar"><a class="brand" href="index.php"><img src="assets/stockcontrol.svg" alt=""><span>Stock<span>Control</span></span></a><div class="side-label">MENU PRINCIPAL</div><nav class="side-nav"><a class="selected" href="#painel" title="Painel" aria-label="Painel"><span>▦</span>Painel</a><a href="#produtos" title="Produtos" aria-label="Produtos"><span>▤</span>Produtos</a><a href="movimentacoes.php" title="Movimentações" aria-label="Movimentações"><span>↕</span>Movimentações</a></nav><div class="sidebar-bottom"><div class="profile"><span class="avatar"><?=e($initials)?></span><span class="profile-copy"><b><?=e($user['nome'])?></b><small><?=isAdmin()?'Administrador':'Usuário'?></small></span></div><form method="post"><input type="hidden" name="csrf" value="<?=e(csrf())?>"><button class="logout" name="action" value="logout">↪ <span>Sair da conta</span></button></form></div></aside>
<main class="main-content" id="painel"><div class="insight-banner"><span class="banner-mark">✳</span><span>Movimentações e níveis de estoque atualizados em tempo real.</span><strong><?= (int)$stats['baixo'] ?> itens precisam de atenção</strong></div><header class="page-head"><div><div class="eyebrow"><?=e($today)?> <span>·</span> StockControl</div><h1>Olá, <?=e(explode(' ',trim($user['nome']))[0])?> <span class="wave">✳</span></h1><p class="muted">Aqui está o resumo do seu estoque hoje.</p></div><?php if(isAdmin()): ?><button class="button primary" onclick="openDialog('productDialog')"><span>＋</span> Novo produto</button><?php endif; ?></header>
<?php if ($message): ?><div class="notice <?=e($message[1])?>"><?=e($message[0])?></div><?php endif; ?>
<section class="stats-grid"><article class="stat-card"><div class="stat-heading">Produtos ativos <span class="stat-icon">▤</span></div><strong><?=number_format((int)$stats['total'],0,',','.')?></strong><small>Itens disponíveis no catálogo</small></article><article class="stat-card"><div class="stat-heading">Unidades em estoque <span class="stat-icon">▣</span></div><strong><?=number_format((int)$stats['unidades'],0,',','.')?></strong><small>Soma das quantidades atuais</small></article><article class="stat-card"><div class="stat-heading">Estoque baixo <span class="stat-icon">⌁</span></div><strong><?=number_format((int)$stats['baixo'],0,',','.')?></strong><small class="wine-text">5 unidades ou menos</small></article><article class="stat-card"><div class="stat-heading">Valor em estoque <span class="stat-icon">◇</span></div><strong class="money">R$ <?=number_format((float)$stats['valor'],2,',','.')?></strong><small>Estimativa pelo preço cadastrado</small></article></section>
<div class="dashboard-grid"><section class="products-section" id="produtos"><div class="section-heading"><div><span class="eyebrow">CATÁLOGO</span><h2>Produtos em estoque</h2><p class="muted">Itens ativos e suas quantidades atuais.</p></div><span class="item-count"><?=count($products)?> itens</span></div><div class="search-wrap"><span>⌕</span><input id="productSearch" type="search" placeholder="Buscar produto..." oninput="filterProducts()"></div><div class="product-list" id="productList"><?php foreach($products as $p): $low=(int)$p['quantidade']<=5; $photo=productImage((string)$p['imagem']); ?><article class="product-card" data-search="<?=e(mb_strtolower($p['nome'].' '.$p['descricao']))?>"><div class="product-image"><?php if($photo !== ''): ?><img src="<?=e($photo)?>" alt="<?=e($p['nome'])?>"><?php else: ?><span>□</span><?php endif; ?><span class="photo-tag"><?=$low?'Estoque baixo':'Em estoque'?></span><div class="product-actions"><button class="text-button" onclick="showComments(<?= (int)$p['id'] ?>, <?=e(json_encode($p['nome'],JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_TAG|JSON_HEX_AMP))?>)" title="Comentários" aria-label="Ver comentários">☷</button><button class="text-button" onclick="showHistory(<?= (int)$p['id'] ?>, <?=e(json_encode($p['nome'],JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_TAG|JSON_HEX_AMP))?>)" title="Histórico" aria-label="Ver histórico">↶</button><button class="text-button" onclick="openMovement(<?= (int)$p['id'] ?>,<?=e(json_encode($p['nome'],JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_TAG|JSON_HEX_AMP))?>,<?= (int)$p['quantidade'] ?>)" title="Movimentar estoque" aria-label="Movimentar estoque">±</button><?php if(isAdmin()): ?><button class="text-button" onclick='editProduct(<?=json_encode(['id'=>(int)$p['id'],'nome'=>$p['nome'],'descricao'=>$p['descricao'],'quantidade'=>(int)$p['quantidade'],'preco'=>(float)$p['preco'],'status'=>(int)$p['status']],JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_TAG|JSON_HEX_AMP)?>)' title="Editar produto" aria-label="Editar produto">✎</button><form method="post" class="inline-form" onsubmit="return confirm('Atenção! Tem certeza que deseja excluir o produto? Essa ação não poderá ser desfeita.')"><input type="hidden" name="csrf" value="<?=e(csrf())?>"><input type="hidden" name="action" value="delete_product"><input type="hidden" name="produto_id" value="<?=(int)$p['id']?>"><button class="text-button danger-text" title="Excluir produto" aria-label="Excluir produto">×</button></form><?php endif; ?></div></div><div class="product-main"><div class="product-title-row"><h3><?=e($p['nome'])?></h3></div><div class="product-meta"><span>R$ <?=number_format((float)$p['preco'],2,',','.')?></span><b><?=number_format((int)$p['quantidade'],0,',','.')?> un.</b></div></div></article><?php endforeach; ?><?php if(!$products): ?><div class="empty-state">Nenhum produto ativo cadastrado ainda.</div><?php endif; ?><div id="noResults" class="empty-state hidden">Nenhum produto corresponde à busca.</div></div></section>
<aside class="right-rail"><section class="rail-card" id="movimentacoes"><div class="section-heading compact"><div><span class="eyebrow">HISTÓRICO</span><h2>Movimentações recentes</h2></div></div><?php if(!$movements): ?><p class="muted small">Nenhuma movimentação registrada.</p><?php endif; ?><?php foreach($movements as $m): ?><div class="movement-row"><span class="movement-icon <?=$m['tipo']==='saida'?'out':''?>"><?=$m['tipo']==='entrada'?'↙':'↗'?></span><div class="movement-copy"><b><?=e(ucfirst($m['tipo']))?> de estoque</b><small><?=e($m['produto'])?> · <?=e($m['usuario'])?></small><small><?=e((new DateTime($m['data_movimentacao']))->format('d/m · H:i'))?></small></div><strong class="<?=$m['tipo']==='saida'?'negative':''?>"><?=$m['tipo']==='entrada'?'+':'−'?><?= (int)$m['quantidade'] ?></strong></div><?php endforeach; ?></section><section class="rail-card low-card"><div class="section-heading compact"><div><span class="eyebrow">ATENÇÃO</span><h2>Estoque baixo</h2></div><span class="alert-dot">!</span></div><?php $lowProducts=array_filter($products,fn($p)=>(int)$p['quantidade']<=5); if(!$lowProducts): ?><p class="muted small">Tudo certo: nenhum item precisa de reposição.</p><?php endif; foreach(array_slice($lowProducts,0,5) as $p): ?><div class="low-row"><span class="low-dot"></span><span><b><?=e($p['nome'])?></b><small><?=e(mb_strimwidth($p['descricao'],0,42,'…'))?></small></span><strong><?= (int)$p['quantidade'] ?> un.</strong></div><?php endforeach; ?></section></aside></div></main></div>
<?php if(isAdmin()): ?><dialog id="productDialog" class="dialog"><form method="post" enctype="multipart/form-data" id="productForm"><input type="hidden" name="csrf" value="<?=e(csrf())?>"><input type="hidden" name="action" value="save_product"><input type="hidden" name="id" id="productId"><div class="dialog-head"><div><h2 id="productDialogTitle">Novo produto</h2><p>Preencha os dados do item.</p></div><button type="button" class="close-button" onclick="closeDialog('productDialog')">×</button></div><div class="form-grid"><label class="field wide">Nome<input name="nome" id="productName" required maxlength="150"></label><label class="field wide">Descrição<textarea name="descricao" id="productDescription" required></textarea></label><label class="field">Quantidade<input name="quantidade" id="productQty" type="number" min="0" required></label><label class="field">Preço (R$)<input name="preco" id="productPrice" type="number" step="0.01" min="0" required></label><label class="field">Status<select name="status" id="productStatus" required><option value="1">Ativo</option><option value="0">Inativo</option></select></label><label class="field">Imagem<input name="imagem" id="productImage" type="file" accept="image/jpeg,image/png,image/webp,image/gif"><small>JPG, PNG, WEBP ou GIF (máx. 5 MB). Obrigatória no cadastro.</small></label><label class="field wide">Observação inicial (opcional)<textarea name="comentario_inicial" placeholder="Registre uma informação importante sobre este produto."></textarea></label></div><div class="dialog-actions"><button type="button" class="button secondary" onclick="closeDialog('productDialog')">Cancelar</button><button class="button primary">Cadastrar</button></div></form></dialog><?php endif; ?>
<dialog id="movementDialog" class="dialog"><form method="post"><input type="hidden" name="csrf" value="<?=e(csrf())?>"><input type="hidden" name="action" value="movement"><input type="hidden" name="produto_id" id="movementProductId"><div class="dialog-head"><div><h2>Movimentar estoque</h2><p id="movementProductName"></p></div><button type="button" class="close-button" onclick="closeDialog('movementDialog')">×</button></div><div class="form-grid"><label class="field">Tipo<select name="tipo" required><option value="entrada">Entrada</option><option value="saida">Saída</option></select></label><label class="field">Quantidade<input name="quantidade" type="number" min="1" required></label><label class="field wide">Observação<textarea name="observacoes"></textarea></label></div><div class="dialog-actions"><button type="button" class="button secondary" onclick="closeDialog('movementDialog')">Cancelar</button><button class="button primary">Registrar movimentação</button></div></form></dialog>
<dialog id="historyDialog" class="dialog"><div class="dialog-head"><div><h2 id="historyTitle">Histórico</h2><p>Movimentações do produto com responsável e observações.</p></div><button type="button" class="close-button" onclick="closeDialog('historyDialog')">×</button></div><div id="historyList" class="comments-list"></div><div class="dialog-actions"><button type="button" class="button secondary" onclick="closeDialog('historyDialog')">Fechar</button></div></dialog><dialog id="commentsDialog" class="dialog"><div class="dialog-head"><div><h2 id="commentsTitle">Comentários</h2><p>Observações mais recentes primeiro.</p></div><button type="button" class="close-button" onclick="closeDialog('commentsDialog')">×</button></div><div id="commentsList" class="comments-list"></div><form method="post" class="comment-form"><input type="hidden" name="csrf" value="<?=e(csrf())?>"><input type="hidden" name="action" value="comment"><input type="hidden" name="produto_id" id="commentProductId"><label class="field">Adicionar comentário<textarea name="texto" required placeholder="Escreva uma observação..."></textarea></label><div class="dialog-actions"><button type="button" class="button secondary" onclick="closeDialog('commentsDialog')">Fechar</button><button class="button primary">Cadastrar</button></div></form></dialog>
<script>const movementData=<?=json_encode($movementData,JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP)?>;const commentData=<?=json_encode(array_reduce($products,function($out,$p){$q=db()->prepare('SELECT c.texto,c.data_comentario,u.nome FROM comentarios c JOIN usuarios u ON u.id=c.usuario_id WHERE c.produto_id=? ORDER BY c.data_comentario DESC,c.id DESC');$q->execute([$p['id']]);$out[$p['id']]=$q->fetchAll();return $out;},[]),JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP)?>;function openDialog(id){document.getElementById(id).showModal()}function closeDialog(id){document.getElementById(id).close()}function filterProducts(){let q=document.getElementById('productSearch').value.toLocaleLowerCase('pt-BR'),shown=0;document.querySelectorAll('.product-card').forEach(x=>{let match=x.dataset.search.includes(q);x.classList.toggle('hidden',!match);if(match)shown++});document.getElementById('noResults').classList.toggle('hidden',shown!==0)}function openMovement(id,name,qty){document.getElementById('movementProductId').value=id;document.getElementById('movementProductName').textContent=name+' · estoque atual: '+qty+' unidades';openDialog('movementDialog')}function safeText(text){let p=document.createElement('p');p.textContent=text;return p.innerHTML}function showComments(id,name){document.getElementById('commentsTitle').textContent='Comentários · '+name;document.getElementById('commentProductId').value=id;let data=commentData[id]||[],list=document.getElementById('commentsList');list.innerHTML=data.length?data.map(c=>'<article class="comment-item"><b>'+safeText(c.nome)+'</b><time>'+new Date(c.data_comentario.replace(' ','T')+'Z').toLocaleString('pt-BR')+'</time><p>'+safeText(c.texto)+'</p></article>').join(''):'<p class="empty-state">Ainda não há comentários para este produto.</p>';openDialog('commentsDialog')}function showHistory(id,name){document.getElementById('historyTitle').textContent='Histórico · '+name;let data=movementData[id]||[],list=document.getElementById('historyList');list.innerHTML=data.length?data.map(m=>'<article class="comment-item"><b>'+safeText(m.tipo==='entrada'?'Entrada':'Saída')+' · '+Number(m.quantidade)+' unidade(s)</b><time>'+new Date(m.data_movimentacao.replace(' ','T')+'Z').toLocaleString('pt-BR')+'</time><p>Responsável: '+safeText(m.usuario)+(m.observacoes?'<br>'+safeText(m.observacoes):'')+'</p></article>').join(''):'<p class="empty-state">Nenhuma movimentação registrada para este produto.</p>';openDialog('historyDialog')}function syncProductSubmit(){let form=document.getElementById('productForm');if(form)form.querySelector('button.primary').disabled=!form.checkValidity()}function editProduct(p){document.getElementById('productDialogTitle').textContent='Editar produto';document.getElementById('productId').value=p.id;document.getElementById('productName').value=p.nome;document.getElementById('productDescription').value=p.descricao;document.getElementById('productQty').value=p.quantidade;document.getElementById('productPrice').value=p.preco;document.getElementById('productStatus').value=p.status;document.getElementById('productImage').required=false;document.querySelector('#productForm .primary').textContent='Salvar alterações';syncProductSubmit();openDialog('productDialog')}<?php if(isAdmin()): ?>document.getElementById('productDialog').addEventListener('close',()=>{document.getElementById('productForm').reset();document.getElementById('productId').value='';document.getElementById('productDialogTitle').textContent='Novo produto';document.getElementById('productImage').required=true;document.querySelector('#productForm .primary').textContent='Cadastrar';syncProductSubmit()});document.getElementById('productImage').required=true;document.getElementById('productForm').addEventListener('input',syncProductSubmit);document.getElementById('productForm').addEventListener('change',syncProductSubmit);syncProductSubmit();<?php endif; ?></script></body></html>
