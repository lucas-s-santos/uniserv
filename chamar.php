<?php
    include_once "includes/bootstrap.php";
    include_once "includes/auth.php";
    $theme = isset($_SESSION['theme']) ? $_SESSION['theme'] : 'dark';
    $themeClass = $theme === 'light' ? 'theme-light' : 'theme-dark';
    require_login();
    require_role([1, 3], 'login.php', 'Acesso restrito para clientes.');
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
                <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
                <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
            <title>Solicitar Servico - Servicos Relampagos</title>
    </head>
    
    <body class="centralizar <?php echo $themeClass; ?>">
        <?php include 'menu.php'; ?>
        <div class="menu-spacer"></div>
        <section class="page-hero">
            <div class="page-hero__content">
                <div class="page-hero__badge">🚀 Rapido e Facil</div>
                <h1 class="page-hero__title">Solicite seu servico</h1>
                <p class="page-hero__subtitle">Preencha 3 passos simples e conecte-se com os melhores profissionais da sua regiao</p>
            </div>
            <div class="page-hero__actions">
                <a class="btn btn-ghost" href="servicos.php">📋 Meus servicos</a>
                <a class="btn btn-primary" href="historico.php">⏱️ Historico</a>
            </div>
        </section>
        <?php include 'includes/chamar_wizard.php'; ?>
    </body>
</html>