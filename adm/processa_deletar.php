<?php
    include_once "../includes/bootstrap.php";
    include_once "../includes/auth.php";
    require_login('../login.php', 'Acesso restrito para administradores.');
    require_role([1], '../login.php', 'Acesso restrito para administradores.');

    $id = isset($_POST['id_adm']) ? (int)$_POST['id_adm'] : 0;
    if ($id <= 0) {
        $_SESSION['avisar'] = 'ID invalido.';
        header("Location: ../administrador.php");
        exit;
    }

    $stmt = $conn->prepare("DELETE FROM registro WHERE id_registro = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    audit_log($conn, 'excluir', 'registro', $id, 'Admin excluiu usuario');

    header("Location: ../administrador.php");
?>