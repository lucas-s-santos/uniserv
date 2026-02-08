<?php
    include_once "../includes/bootstrap.php";
    include_once "../includes/auth.php";
    require_login('../login.php', 'Voce precisa estar logado para acessar esta area.');
    require_role([3], '../login.php', 'Acesso apenas para clientes interessados em colaborar.');

    $id = isset($_POST['id_pessoal']) ? (int)$_POST['id_pessoal'] : 0;
    if ($id <= 0 || $id !== (int)$_SESSION['id_acesso']) {
        $_SESSION['avisar'] = "ID invalido.";
        header("Location: ../index.php");
        exit;
    }

    $telefone = isset($_POST['telefone']) ? trim($_POST['telefone']) : '';
    $cidade = isset($_POST['cidade']) ? trim($_POST['cidade']) : '';
    $cnpj = isset($_POST['cnpj']) ? trim($_POST['cnpj']) : '';
    $descricao = isset($_POST['descricao']) ? trim($_POST['descricao']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';

    $stmt = $conn->prepare("UPDATE registro SET email=?, telefone=?, cidade=?, cnpj=?, descricao=?, funcao='2', atualizar='1' WHERE id_registro=?");
    $stmt->bind_param("sssssi", $email, $telefone, $cidade, $cnpj, $descricao, $id);
    $stmt->execute();
    $stmt->close();

    header("Location: ../index.php");
?>