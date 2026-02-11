<?php
    include_once "../includes/bootstrap.php";
    if (!isset($_SESSION['cpf']) || (int)$_SESSION['funcao'] !== 1) {
        $_SESSION['avisar'] = "Acesso restrito para administradores.";
        header('location: ../login.php');
        exit;
    }
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
        $cidade = $_POST['cidade'];
        $cpf = $_POST['cpf'];
        $telefone = $_POST['telefone'];
        $email = $_POST['email'];
        $senha = trim($_POST['senha']);
        $genero = $_POST['genero'];
        $funcao = $_POST['funcao'];
        $data = $_POST['data_ani'];

        if ($senha !== '') {
            $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE registro SET nome=?, apelido=?, estado=?, cidade=?, cpf=?, email=?, telefone=?, senha=?, sexo=?, funcao=?, atualizar='1', data_ani=? WHERE id_registro=?");
            $stmt->bind_param("sssssssssssi", $nome, $apelido, $estado, $cidade, $cpf, $email, $telefone, $senha_hash, $genero, $funcao, $data, $id);
        } else {
            $stmt = $conn->prepare("UPDATE registro SET nome=?, apelido=?, estado=?, cidade=?, cpf=?, email=?, telefone=?, sexo=?, funcao=?, atualizar='1', data_ani=? WHERE id_registro=?");
            $stmt->bind_param("ssssssssssi", $nome, $apelido, $estado, $cidade, $cpf, $email, $telefone, $genero, $funcao, $data, $id);
        }

        $stmt->execute();
        $stmt->close();
        audit_log($conn, 'editar', 'registro', $id, "Admin editou usuario $id");

        $_SESSION['sucesso_edicao'] = true;
        $_SESSION['msg_edicao'] = "Usuário editado com sucesso!";
        unset($_SESSION['id_adm']);
    }

    header("Location: ../administrador.php");
?>