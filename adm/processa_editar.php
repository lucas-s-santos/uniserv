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
    $acao = "nada";
    $acao = $_POST['acao'];

    if ($acao == 'pesquisar') {
        $_SESSION['id_adm'] = $_POST['id_adm'];

        $comando_mysql2 = "SELECT * FROM registro WHERE id_registro = '$_SESSION[id_adm]'";
	    $procure2 = mysqli_query($conn, $comando_mysql2);
	    $resultado2 = mysqli_fetch_assoc($procure2);

        if (isset($resultado2['id_registro'])) { } else 
            {unset($_SESSION['id_adm']);
        }
    } else if ($acao <> "nada") {
        $telefone = " "; $email =" ";
        $id = $acao;
        $nome = $_POST['nome'];
        $apelido = $_POST['apelido'];
        $estado = $_POST['estado'];
        $cpf = $_POST['cpf'];
        $telefone = $_POST['telefone'];
        $email = $_POST['email'];
        $senha = $_POST['senha'];
        $genero = $_POST['genero'];
        $data = $_POST['data_ani'];

        $comando_editar = "UPDATE registro SET nome='$nome', apelido='$apelido', estado='$estado', cpf='$cpf', email='$email', telefone='$telefone', senha='$senha', sexo='$genero',
         atualizar='1', data_ani='$data' WHERE id_registro='$id'";

        mysqli_query($conn, $comando_editar);
        audit_log($conn, 'editar', 'registro', $id, "Admin editou usuario $id");

        unset($_SESSION['id_adm']);
    }

    header("Location: ../administrador.php");
?>