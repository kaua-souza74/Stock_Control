# StockControl

Aplicação de controle de estoque em PHP 8.2 e MySQL/MariaDB, desenvolvida para XAMPP.

## Instalação local

1. Copie a pasta para `C:\xampp\htdocs\Stock Control`.
2. Inicie Apache e MySQL no painel do XAMPP.
3. No phpMyAdmin, importe `database/stockcontrol.sql` (ou o dump fornecido pelo professor).
4. Confira host, banco, usuário e senha em `config.php`.
5. Abra `http://localhost/Stock%20Control/`. Administradores entram no painel; funcionários entram diretamente em `estoque.php` para consultar produtos, preços, quantidades, comentários e histórico.
6. Acesso administrativo do dump: `admin@stockcontrol.com` / `admin123`. Troque a senha após o primeiro acesso. O sistema migra essa senha legada para hash na primeira autenticação.
7. Garanta que o Apache possa gravar na pasta `imagens/` para permitir envio de fotos.

Os cinco doces de exemplo já vêm com fotos WebP em `imagens/`. Produtos cadastrados usam a imagem enviada no formulário.
O dump SQL já aponta os cinco produtos para esses arquivos. Se o banco já estava importado, execute `database/atualizar_imagens.sql` no phpMyAdmin para atualizar só os caminhos das fotos; não é preciso reimportar nem apagar os dados.

## Entregáveis

- `SPEC.md`: especificação visual baseada na segunda imagem e regras funcionais.
- `assets/stockcontrol-logo.svg`: logo horizontal vetorial; `assets/stockcontrol.svg`: símbolo para espaços compactos.
- `database/stockcontrol.sql`: estrutura e dados do banco fornecido.
- `docs/der.png`: diagrama entidade-relacionamento.
- `docs/atividade-inclusao-produto.png`: fluxo de login e inclusão de produto.

O esquema recebido não possui categorias nem fotos de usuário; a aplicação segue as tabelas e colunas reais desse banco.
