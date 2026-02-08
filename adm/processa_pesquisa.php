<?php
    include_once "../includes/bootstrap.php";
    include_once "../includes/auth.php";
    require_login('../login.php', 'Acesso restrito para administradores.');
    require_role([1], '../login.php', 'Acesso restrito para administradores.');

    $_SESSION['nome_adm'] = isset($_POST['nome_adm']) ? trim($_POST['nome_adm']) : '';
    $_SESSION['cpf_adm'] = isset($_POST['cpf_adm']) ? trim($_POST['cpf_adm']) : '';
    $_SESSION['exibir_tabela'] = "sim";
    header("Location: ../administrador.php");
?>