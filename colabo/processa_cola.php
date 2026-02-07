<?php
    session_start();
    if (!isset($_SESSION['cpf'])) {
        $_SESSION['avisar'] = "Voce precisa estar logado para acessar esta area.";
        header('location: ../login.php');
        exit;
    }
    if ((int)$_SESSION['funcao'] !== 3) {
        $_SESSION['avisar'] = "Acesso apenas para clientes interessados em colaborar.";
        header('location: ../login.php');
        exit;
    }
    include_once("../conexao.php");
?>
<?php
    $acao = "nada";
    $acao = $_POST['id_pessoal'];

    if ($acao <> "nada") {
        $telefone = " "; $descricao =" ";
        $id = $acao;
        $telefone = $_POST['telefone'];
        $cnpj = $_POST['cnpj'];
        $descricao = $_POST['descricao'];
        $email = $_POST['email'];

        $comando_editar = "UPDATE registro SET email='$email', telefone='$telefone', cnpj='$cnpj', descricao='$descricao', funcao='2', atualizar='1' WHERE id_registro='$id'";

        mysqli_query($conn, $comando_editar);
    }

    header("Location: ../index.php");
?>