<?php
    include_once "../includes/bootstrap.php";
    include_once "../includes/auth.php";
    require_login('../login.php', 'Acesso restrito para administradores.');
    require_role([1], '../login.php', 'Acesso restrito para administradores.');

    $nome = trim($_POST['nome_func']);
    $categoria = trim($_POST['categoria']);
    $valor_base = str_replace(',', '.', $_POST['valor_base']);
    $valor_base = $valor_base !== '' ? $valor_base : null;
    $duracao_estimada = $_POST['duracao_estimada'] !== '' ? (string)(int)$_POST['duracao_estimada'] : null;
    $descricao = trim($_POST['descricao']);
    $imagem = null;

    $stmt = $conn->prepare("INSERT INTO funcoes(nome_func, categoria, valor_base, duracao_estimada, descricao, imagem)
        VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $nome, $categoria, $valor_base, $duracao_estimada, $descricao, $imagem);
    $stmt->execute();
    $novo_id = $stmt->insert_id;
    $stmt->close();

    if ($novo_id) {
        $_SESSION['avisar'] = "Funcao criada com sucesso.";
        audit_log($conn, 'criar', 'funcoes', $novo_id, "Nome: $nome");
    } else {
        $_SESSION['avisar'] = "Erro ao criar funcao.";
    }
    mysqli_close($conn);
    header("Location: ../administrador.php");
?>