<?php
    include_once "includes/bootstrap.php";
    include_once "includes/auth.php";
    $theme = isset($_SESSION['theme']) ? $_SESSION['theme'] : 'dark';
    $themeClass = $theme === 'light' ? 'theme-light' : 'theme-dark';
    require_login('login.php', 'Voce precisa estar logado no site!');
?>

<!DOCTYPE html>
<html lang="pt-br">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0"> 
        <meta name="author" content="Gabriel Nepomuceno de Almeida dos Santos">
        <meta name="keywords" content="HTML, CSS">
        <meta name="description" content="Pagina inicial do Serviços Relâmpagos">
        <meta http-equiv="refresh" content="15">
        <link rel="stylesheet" href="css/estrutura_geral.css">
        <title>Página principal</title>
    </head>
    
    <body class="centralizar <?php echo $themeClass; ?>">
        <?php include 'menu.php'; ?>
        <div class="menu-spacer"></div>
        <main class="page">
            <?php include 'includes/servicos_lista.php'; ?>
        </main>
    </body>

    <footer class="footer">
        <?php include 'pe.html'; ?>
    </footer>
</html>