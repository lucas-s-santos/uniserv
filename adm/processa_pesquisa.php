<?php
    session_start();
    if (!isset($_SESSION['cpf']) || (int)$_SESSION['funcao'] !== 1) {
        $_SESSION['avisar'] = "Acesso restrito para administradores.";
        header('location: ../login.php');
        exit;
    }
?>
<?php
    $_SESSION['nome_adm'] = $_POST['nome_adm'];
    $_SESSION['cpf_adm'] = $_POST['cpf_adm'];
    $_SESSION['exibir_tabela'] = "sim";
    header("Location: ../administrador.php");
?>