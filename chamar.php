<?php
    session_start();
    include_once "conexao.php";
    include_once "status.php";
    include_once "audit.php";
        $theme = isset($_SESSION['theme']) ? $_SESSION['theme'] : 'dark';
        $themeClass = $theme === 'light' ? 'theme-light' : 'theme-dark';
    if (!isset($_SESSION['cpf'])) {
        $_SESSION['avisar'] = "Você precisa estar logado no site!";
        header('location: login.php');
        exit;
    }
    $role = (int)$_SESSION['funcao'];
    if ($role !== 1 && $role !== 3) {
        $_SESSION['avisar'] = "Acesso restrito para clientes.";
        header('location: login.php');
        exit;
    }
    if (isset($_GET['teste'])) {
        $_SESSION['ordem_id'] = $_GET['teste'];
    }

    if (isset($_POST['formnum'])) {
        $_SESSION['ordem_id'] = $_POST['formnum'];
        if ($_SESSION['ordem_id'] == 4) {
            $status_pendente = SERVICO_STATUS_PENDENTE;
            $comando_chamar = "INSERT INTO servico(registro_id_registro, id_trabalhador, endereco, valor_atual, tempo_servico, avaliacao, ativo, comentario, data_2) 
            VALUES ('$_POST[id_qmchamou]', '$_POST[id_chamado]', '$_POST[endereco]', '$_POST[valor_atual]', '0', '5', '$status_pendente', 'analise', '2022-12-12')";
            $joga_no_banco = mysqli_query($conn, $comando_chamar);
            $novo_servico_id = mysqli_insert_id($conn);
            if ($novo_servico_id) {
                audit_log($conn, 'criar', 'servico', $novo_servico_id, 'Chamado criado');
            }
            unset ($_POST['formnum']);
        }
    }
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
        <div class="menu-spacer"></div>
        <br>
        <object data="chamarsub.php" height="600px" width="100%"></object>
    </body>

    <footer class="footer">
        <object data="pe.html" height="45px" width="100%"></object>
    </footer>
</html>