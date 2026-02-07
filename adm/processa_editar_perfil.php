<?php
    session_start();
    include_once("../conexao.php");
    include_once("../audit.php");
?>
<?php
    $acao = "nada";
    $acao = $_POST['acao'];

    if ($acao <> "nada") {
        $telefone = " "; $email =" "; $senha = "";
        $id = $acao;
        $nome = $_POST['nome'];
        $apelido = $_POST['apelido'];
        $email = $_POST['email'];
        $estado = $_POST['estado'];
        $telefone = $_POST['telefone'];
        $genero = $_POST['genero'];
        $senha = $_POST['senhanova'];

        if ($senha <> "") {
            $comando_editar = "UPDATE registro SET nome='$nome', apelido='$apelido', estado='$estado', telefone='$telefone',
            email='$email', senha='$senha', sexo='$genero',
            atualizar='1' WHERE id_registro='$id'";
        } else {
            $comando_editar = "UPDATE registro SET nome='$nome', apelido='$apelido', estado='$estado', telefone='$telefone',
            email='$email', sexo='$genero',
             atualizar='1' WHERE id_registro='$id'";
        }

        mysqli_query($conn, $comando_editar);
        audit_log($conn, 'editar', 'registro', $id, 'Usuario atualizou perfil');

        unset($_SESSION['id_adm']);
    }

    header("Location: ../index.php");
?>