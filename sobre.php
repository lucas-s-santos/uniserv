<?php
    session_start();
    $theme = isset($_SESSION['theme']) ? $_SESSION['theme'] : 'dark';
    $themeClass = $theme === 'light' ? 'theme-light' : 'theme-dark';
?>
<!DOCTYPE html>
<html lang="pt-br">
    <head>
        <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0"> 
            <meta name="author" content="Gabriel Nepomuceno de Almeida dos Santos">
            <meta name="keywords" content="HTML, CSS">
            <meta name="description" content="Pagina inicial do Serviços Relâmpagos">
            <link rel="stylesheet" href="css/estrutura_geral.css">
            <title>Página principal</title>

    </head>
    
    <body class="centralizar <?php echo $themeClass; ?>">
        <div style="width:100%; position: fixed"><object data="menu.php" height="80px" width="100%"></object></div>
        <div style="width:100%; height: 80px;"></div>
        <div class="title">Sobre</div>
        <div class="texto">Esse site foi desenvolvido por Gabriel Nepomuceno de Almeida dos Santos em um projeto de programação</div>
        <div class="texto">Senac - Curso de Tecnico em Informatica</div>
        <?php if (isset($_SESSION['funcao'])) {
                    if ($_SESSION['funcao'] == '3') {
                         echo "<div class='botaolist'><a href='colabo/cadastro_colaborador.php'>Tenho interesse em trabalhar nesse site</a></div>";
                    }
                }
        ?>
    </body>

    <footer class="footer">
        <object data="pe.html" height="45px" width="100%"></object>
    </footer>

</html>