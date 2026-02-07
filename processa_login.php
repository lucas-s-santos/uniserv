<?php
    session_start();

    include_once("conexao.php");
    include_once("audit.php");
?>
<?php
    if (isset($_POST['cpf_login'])) {
        $cpf_login = $_POST['cpf_login'];
    
        $comando_mysql = "SELECT * FROM registro WHERE cpf = '$cpf_login' LIMIT 1";
        $procure = mysqli_query($conn, $comando_mysql);
        $resultado = mysqli_fetch_assoc($procure);
    
        $comando_editar2 = "UPDATE registro SET atualizar='0' WHERE cpf = '$cpf_login' LIMIT 1";
        mysqli_query($conn, $comando_editar2);
        $_SESSION['atualizar'] = "nao"; 
        
        if(isset($resultado)) {
            $_SESSION['apelido'] = $resultado['apelido'];
            $_SESSION['cpf'] = $resultado['cpf'];
            $_SESSION['funcao'] = $resultado['funcao'];
            $_SESSION['id_acesso'] = $resultado['id_registro'];
            audit_log($conn, 'login', 'registro', (int)$resultado['id_registro'], 'Login realizado');
        }
        mysqli_close($conn);
    
        header("Location: index.php");
    } else {
        $_SESSION['avisar'] = "Login falhou POST_NOT_FOUND_X";
        header("Location: index.php");
    }
?>