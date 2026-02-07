<?php
    session_start();
    if (!isset($_SESSION['cpf']) || (int)$_SESSION['funcao'] !== 1) {
        $_SESSION['avisar'] = "Acesso restrito para administradores.";
        header('location: ../login.php');
        exit;
    }
    include_once("../conexao.php");
    include_once("../audit.php");
?>
<?php
    $_SESSION['id_adm'] = $_POST['id_adm'];

    $comando_mysql3 = "DELETE FROM registro WHERE id_registro = '$_SESSION[id_adm]'";
	mysqli_query($conn, $comando_mysql3);
    audit_log($conn, 'excluir', 'registro', (int)$_SESSION['id_adm'], 'Admin excluiu usuario');
    unset($_SESSION['id_adm']);

    header("Location: ../administrador.php");
?>