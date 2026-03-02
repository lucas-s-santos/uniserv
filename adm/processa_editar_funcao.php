<?php
    include_once "../includes/bootstrap.php";
    include_once "../includes/auth.php";

    require_login('../login.php', 'Acesso restrito para administradores.');
    require_role([1], '../login.php', 'Acesso restrito para administradores.');

    $id_funcao = isset($_POST['id_funcao']) ? (int)$_POST['id_funcao'] : 0;
    $nome = isset($_POST['nome_func']) ? trim((string)$_POST['nome_func']) : '';
    $categoria = isset($_POST['categoria']) ? trim((string)$_POST['categoria']) : '';
    $valor_base = isset($_POST['valor_base']) ? str_replace(',', '.', trim((string)$_POST['valor_base'])) : '';
    $duracao_estimada = isset($_POST['duracao_estimada']) ? (int)$_POST['duracao_estimada'] : 0;
    $descricao = isset($_POST['descricao']) ? trim((string)$_POST['descricao']) : '';
    $motivo_acao = isset($_POST['motivo_acao']) ? trim((string)$_POST['motivo_acao']) : '';

    if ($id_funcao <= 0) {
        $_SESSION['avisar'] = 'Funcao invalida.';
        $_SESSION['avisar_tipo'] = 'error';
        header('Location: ../administrador.php');
        exit;
    }

    if ($nome === '' || $categoria === '' || $descricao === '') {
        $_SESSION['avisar'] = 'Preencha nome, categoria e descricao da funcao.';
        $_SESSION['avisar_tipo'] = 'warn';
        header('Location: ../administrador.php');
        exit;
    }

    if ($valor_base !== '' && !is_numeric($valor_base)) {
        $_SESSION['avisar'] = 'Valor base invalido.';
        $_SESSION['avisar_tipo'] = 'warn';
        header('Location: ../administrador.php');
        exit;
    }

    if ($duracao_estimada < 0) {
        $_SESSION['avisar'] = 'Duracao estimada invalida.';
        $_SESSION['avisar_tipo'] = 'warn';
        header('Location: ../administrador.php');
        exit;
    }

    $stmt = $conn->prepare("SELECT id_funcoes FROM funcoes WHERE id_funcoes = ? LIMIT 1");
    $stmt->bind_param("i", $id_funcao);
    $stmt->execute();
    $funcao = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$funcao) {
        $_SESSION['avisar'] = 'Funcao nao encontrada.';
        $_SESSION['avisar_tipo'] = 'warn';
        header('Location: ../administrador.php');
        exit;
    }

    $stmt = $conn->prepare("SELECT id_funcoes FROM funcoes WHERE nome_func = ? AND categoria = ? AND id_funcoes <> ? LIMIT 1");
    $stmt->bind_param("ssi", $nome, $categoria, $id_funcao);
    $stmt->execute();
    $duplicada = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($duplicada) {
        $_SESSION['avisar'] = 'Ja existe outra funcao com esse nome e categoria.';
        $_SESSION['avisar_tipo'] = 'warn';
        header('Location: ../administrador.php');
        exit;
    }

    $valor_db = $valor_base === '' ? null : (float)$valor_base;
    $duracao_db = $duracao_estimada > 0 ? $duracao_estimada : null;

    $stmt = $conn->prepare("UPDATE funcoes
        SET nome_func = ?, categoria = ?, valor_base = ?, duracao_estimada = ?, descricao = ?
        WHERE id_funcoes = ?");
    $stmt->bind_param("ssdisi", $nome, $categoria, $valor_db, $duracao_db, $descricao, $id_funcao);
    $stmt->execute();
    $updated = $stmt->affected_rows >= 0;
    $stmt->close();

    if ($updated) {
        $detalhes = "Admin editou funcao {$id_funcao} ({$nome})";
        if ($motivo_acao !== '') {
            $detalhes .= ". Motivo: {$motivo_acao}";
        }
        audit_log($conn, 'editar', 'funcoes', $id_funcao, $detalhes);
        $_SESSION['avisar'] = 'Funcao atualizada com sucesso.';
        $_SESSION['avisar_tipo'] = 'success';
    } else {
        $_SESSION['avisar'] = 'Nao foi possivel atualizar a funcao.';
        $_SESSION['avisar_tipo'] = 'error';
    }

    header('Location: ../administrador.php');
?>
