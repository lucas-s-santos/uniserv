# UniServ

Projeto PHP + MySQL para gestao de servicos locais.

## Requisitos
- Windows com XAMPP (Apache + MySQL)
- PHP 7.4+ (XAMPP ja inclui)

## Como rodar localmente
1) Copie a pasta do projeto para: `C:\xampp\htdocs\servicos-gerais`
2) Inicie o Apache e o MySQL no painel do XAMPP
3) Crie o banco e as tabelas:
   - Abra o phpMyAdmin: http://localhost/phpmyadmin
   - Importe o arquivo `banco.sql`
4) Ajuste a conexao (se necessario) em `conexao.php`:
   - host: `localhost`
   - usuario: `root`
   - senha: `` (vazia)
   - banco: `relampagoservice`
5) Abra no navegador:
   - http://localhost/servicos-gerais

## Estrutura basica
- `index.php`: pagina inicial
- `login.php` / `cadastro.php`: acesso e cadastro
- `menu.php`: menu principal
- `banco.sql`: schema do banco

## Observacoes
- Se mudar o nome da pasta, atualize o caminho de acesso no navegador.
- Para reiniciar o banco, reimporte o `banco.sql`.
