<?php
    include_once "includes/bootstrap.php";

    if (isset($_POST['cpf_login'], $_POST['senha_login'])) {
        $cpf_login = trim($_POST['cpf_login']);
        $senha_login = $_POST['senha_login'];

        $stmt = $conn->prepare("SELECT id_registro, apelido, cpf, funcao, senha FROM registro WHERE cpf = ? LIMIT 1");
        $stmt->bind_param("s", $cpf_login);
        $stmt->execute();
        $resultado = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$resultado) {
            $_SESSION['avisar'] = "CPF nao encontrado.";
            header("Location: login.php");
            exit;
        }

        $senha_hash = $resultado['senha'];
        $senha_valida = password_verify($senha_login, $senha_hash);

        if (!$senha_valida && $senha_hash === $senha_login) {
            $senha_valida = true;
            $novo_hash = password_hash($senha_login, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE registro SET senha = ? WHERE id_registro = ? LIMIT 1");
            $stmt->bind_param("si", $novo_hash, $resultado['id_registro']);
            $stmt->execute();
            $stmt->close();
        }

        if (!$senha_valida) {
            $_SESSION['avisar'] = "Senha incorreta.";
            header("Location: login.php");
            exit;
        }

        $stmt = $conn->prepare("UPDATE registro SET atualizar = '0' WHERE id_registro = ? LIMIT 1");
        $stmt->bind_param("i", $resultado['id_registro']);
        $stmt->execute();
        $stmt->close();
        $_SESSION['atualizar'] = "nao";

        $_SESSION['apelido'] = $resultado['apelido'];
        $_SESSION['cpf'] = $resultado['cpf'];
        $_SESSION['funcao'] = $resultado['funcao'];
        $_SESSION['id_acesso'] = $resultado['id_registro'];
        audit_log($conn, 'login', 'registro', (int)$resultado['id_registro'], 'Login realizado');

        mysqli_close($conn);
        header("Location: index.php");
    } else {
        $_SESSION['avisar'] = "Login falhou POST_NOT_FOUND_X";
        header("Location: login.php");
    }
?>