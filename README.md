<div align="center">
  <img src="assets/stockcontrol-logo.svg" alt="StockControl — gestão de estoque" width="360">
  <h1>StockControl</h1>
  <p><strong>Controle de estoque simples, visual e direto.</strong></p>
  <p>Produtos, quantidades, movimentações e comentários em uma aplicação web local.</p>
  <p>
    <img src="https://img.shields.io/badge/PHP-8.2%2B-777BB4?logo=php&logoColor=white" alt="PHP 8.2 ou superior">
    <img src="https://img.shields.io/badge/MySQL%20%2F%20MariaDB-database-4479A1?logo=mysql&logoColor=white" alt="MySQL ou MariaDB">
    <img src="https://img.shields.io/badge/Interface-responsiva-A60321" alt="Interface responsiva">
  </p>
</div>

---

## Visão geral

O **StockControl** é um sistema de gestão de estoque desenvolvido em PHP 8.2 e MySQL/MariaDB para execução local com XAMPP. A aplicação oferece experiências específicas para administradores e funcionários, mantendo o catálogo, o histórico de alterações e as observações dos produtos em um só lugar.

## Recursos

- Painel administrativo com indicadores, catálogo e movimentações recentes.
- Cadastro, edição e exclusão de produtos com imagem.
- Registro de entradas e saídas, com atualização da quantidade disponível.
- Área do funcionário para consultar produtos ativos, preços e quantidades.
- Comentários e histórico de movimentação associados a cada produto.
- Busca no catálogo, filtros do histórico e avisos de estoque baixo.
- Interface responsiva para computador e dispositivos móveis.

## Prévia do catálogo

Produtos de demonstração incluídos no projeto:

<table>
  <tr>
    <td align="center"><img src="imagens/brigadeiro.webp" alt="Brigadeiro" width="220"><br><strong>Brigadeiro</strong></td>
    <td align="center"><img src="imagens/cocada.webp" alt="Cocada" width="220"><br><strong>Cocada</strong></td>
    <td align="center"><img src="imagens/goiabada.webp" alt="Goiabada" width="220"><br><strong>Goiabada</strong></td>
  </tr>
  <tr>
    <td align="center"><img src="imagens/pacoca.webp" alt="Paçoca" width="220"><br><strong>Paçoca</strong></td>
    <td align="center"><img src="imagens/pudim.webp" alt="Pudim" width="220"><br><strong>Pudim</strong></td>
    <td align="center"><img src="assets/stockcontrol.svg" alt="Símbolo StockControl" width="90"><br><strong>Marca</strong></td>
  </tr>
</table>

## Tecnologias

| Camada | Tecnologia |
| --- | --- |
| Servidor | PHP 8.2 ou superior |
| Banco de dados | MySQL ou MariaDB |
| Conexão | PDO com driver `pdo_mysql` |
| Interface | HTML, CSS e JavaScript |
| Servidor local | Apache (XAMPP) |

## Instalação local

1. Coloque a pasta do projeto dentro de `htdocs` do XAMPP. Exemplo:

   ```text
   C:\xampp\htdocs\Stock\Stock_Control
   ```

2. Inicie **Apache** e **MySQL** no painel do XAMPP.
3. Abra o phpMyAdmin em [http://localhost/phpmyadmin](http://localhost/phpmyadmin).
4. Crie um banco chamado `stockcontrol` e importe [`database/stockcontrol.sql`](database/stockcontrol.sql).
5. Confira em [`config.php`](config.php) se a conexão está configurada para o banco `stockcontrol`, usuário `root` e senha vazia (padrão do XAMPP local).
6. Abra o projeto no navegador:

   ```text
   http://localhost/Stock/Stock_Control/
   ```

O Apache precisa ter permissão de escrita na pasta `imagens/` para salvar fotos enviadas pelo formulário.

### Contas locais de demonstração

O banco inclui contas para testes locais:

| Perfil | E-mail | Senha inicial |
| --- | --- | --- |
| Administrador | `admin@stockcontrol.com` | `admin123` |
| Funcionário | `carlos@stockcontrol.com` | `user123` |

Essas credenciais são destinadas ao ambiente local de demonstração. Altere as senhas antes de qualquer uso fora dele. A senha administrativa legada é convertida para hash no primeiro login.

### Atualizar os caminhos das imagens

Se o banco já tiver sido importado antes da inclusão das imagens de exemplo, execute [`database/atualizar_imagens.sql`](database/atualizar_imagens.sql) no phpMyAdmin. O script atualiza os caminhos das fotos sem apagar os registros existentes.

## Perfis de acesso

| Ação | Administrador | Funcionário |
| --- | :---: | :---: |
| Consultar produtos ativos | Sim | Sim |
| Ver comentários e histórico | Sim | Sim |
| Adicionar comentários | Sim | Sim |
| Cadastrar e editar produtos | Sim | — |
| Excluir produtos | Sim | — |
| Registrar entradas e saídas | Sim | — |

## Estrutura do projeto

```text
Stock_Control/
├── assets/
│   ├── js/                 # Scripts da interface
│   ├── stockcontrol-logo.svg
│   ├── stockcontrol.svg    # Símbolo compacto
│   └── style.css
├── database/               # Banco e atualização dos caminhos das imagens
├── imagens/                # Fotos de demonstração e imagens enviadas
├── config.php              # Conexão PDO com MySQL/MariaDB
├── index.php               # Login e painel administrativo
├── estoque.php             # Consulta de estoque para funcionários
└── movimentacoes.php       # Histórico de entradas e saídas
```

## Banco de dados

O esquema usa as tabelas `usuarios`, `produtos`, `movimentacoes` e `comentarios`. A aplicação acompanha as tabelas e colunas disponíveis no banco recebido para a atividade; categorias e fotos de perfil não fazem parte desse esquema.

## Identidade visual

| Aplicação | Cor |
| --- | --- |
| Cor principal | `#A60321` |
| Fundo claro | `#F2E9D8` |
| Cor de apoio | `#8C5C32` |

As versões vetoriais da marca são [`assets/stockcontrol-logo.svg`](assets/stockcontrol-logo.svg) e [`assets/stockcontrol.svg`](assets/stockcontrol.svg).
