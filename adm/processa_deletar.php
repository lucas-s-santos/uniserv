<?php
    include_once "../includes/bootstrap.php";
    include_once "../includes/auth.php";
    require_login('../login.php', 'Acesso restrito para administradores.');
    require_role([1], '../login.php', 'Acesso restrito para administradores.');

    function remover_arquivo_local($relative_path) {
        $relative_path = trim((string)$relative_path);
        if ($relative_path === '' || $relative_path === 'presencial') {
            return;
        }
        if (preg_match('/^https?:\/\//i', $relative_path)) {
            return;
        }

        $base_dir = realpath(__DIR__ . '/..');
        if ($base_dir === false) {
            return;
        }

        $normalized_relative = str_replace('\\', '/', $relative_path);
        $target = realpath($base_dir . '/' . ltrim($normalized_relative, '/'));
        if ($target === false) {
            return;
        }

        $base_dir = rtrim(str_replace('\\', '/', $base_dir), '/') . '/';
        $target = str_replace('\\', '/', $target);
        if (strpos($target, $base_dir) !== 0) {
            return;
        }

        if (is_file($target)) {
            @unlink($target);
        }
    }

    $tipo = isset($_POST['tipo']) ? trim((string)$_POST['tipo']) : 'usuario';
    $id = isset($_POST['id_adm']) ? (int)$_POST['id_adm'] : 0;
    $motivo_acao = isset($_POST['motivo_acao']) ? trim((string)$_POST['motivo_acao']) : '';

    if ($id <= 0) {
        $_SESSION['avisar'] = 'ID invalido.';
        $_SESSION['avisar_tipo'] = 'error';
        header("Location: ../administrador.php");
        exit;
    }

    if ($tipo === 'servico') {
        $stmt = $conn->prepare("SELECT id_servico, pagamento_comprovante, foto_antes, foto_depois FROM servico WHERE id_servico = ? LIMIT 1");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $servico = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$servico) {
            $_SESSION['avisar'] = 'Servico nao encontrado.';
            $_SESSION['avisar_tipo'] = 'warn';
            header("Location: ../administrador.php");
            exit;
        }

        $stmt = $conn->prepare("DELETE FROM servico_checklist WHERE servico_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare("DELETE FROM servico WHERE id_servico = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $deletou = $stmt->affected_rows > 0;
        $stmt->close();

        if ($deletou) {
            remover_arquivo_local($servico['pagamento_comprovante']);
            remover_arquivo_local($servico['foto_antes']);
            remover_arquivo_local($servico['foto_depois']);
            $detalhes = 'Admin excluiu servico pelo painel';
            if ($motivo_acao !== '') {
                $detalhes .= '. Motivo: ' . $motivo_acao;
            }
            audit_log($conn, 'excluir', 'servico', $id, $detalhes);
            $_SESSION['avisar'] = 'Servico excluido com sucesso.';
            $_SESSION['avisar_tipo'] = 'success';
        } else {
            $_SESSION['avisar'] = 'Nao foi possivel excluir o servico.';
            $_SESSION['avisar_tipo'] = 'error';
        }

        header("Location: ../administrador.php");
        exit;
    }

    if ($tipo === 'funcao') {
        $stmt = $conn->prepare("SELECT id_funcoes, nome_func FROM funcoes WHERE id_funcoes = ? LIMIT 1");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $funcao = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$funcao) {
            $_SESSION['avisar'] = 'Funcao nao encontrada.';
            $_SESSION['avisar_tipo'] = 'warn';
            header("Location: ../administrador.php");
            exit;
        }

        $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM trabalhador_funcoes WHERE funcoes_id_funcoes = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $colab_row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        $total_colaboradores = $colab_row ? (int)$colab_row['total'] : 0;

        $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM servico WHERE funcoes_id_funcoes = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $serv_row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        $total_servicos_funcao = $serv_row ? (int)$serv_row['total'] : 0;

        if ($total_colaboradores > 0 || $total_servicos_funcao > 0) {
            $_SESSION['avisar'] = 'Nao foi possivel excluir: funcao vinculada a colaboradores ou servicos.';
            $_SESSION['avisar_tipo'] = 'warn';
            header("Location: ../administrador.php");
            exit;
        }

        $stmt = $conn->prepare("DELETE FROM checklist_itens WHERE funcoes_id_funcoes = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare("DELETE FROM funcoes WHERE id_funcoes = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $deletou_funcao = $stmt->affected_rows > 0;
        $stmt->close();

        if ($deletou_funcao) {
            $detalhes = 'Admin excluiu funcao ' . $funcao['nome_func'];
            if ($motivo_acao !== '') {
                $detalhes .= '. Motivo: ' . $motivo_acao;
            }
            audit_log($conn, 'excluir', 'funcoes', $id, $detalhes);
            $_SESSION['avisar'] = 'Funcao excluida com sucesso.';
            $_SESSION['avisar_tipo'] = 'success';
        } else {
            $_SESSION['avisar'] = 'Nao foi possivel excluir a funcao.';
            $_SESSION['avisar_tipo'] = 'error';
        }

        header("Location: ../administrador.php");
        exit;
    }

    if ($id === (int)$_SESSION['id_acesso']) {
        $_SESSION['avisar'] = 'Voce nao pode excluir seu proprio usuario.';
        $_SESSION['avisar_tipo'] = 'warn';
        header("Location: ../administrador.php");
        exit;
    }

    $stmt = $conn->prepare("SELECT id_registro FROM registro WHERE id_registro = ? LIMIT 1");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $usuario = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$usuario) {
        $_SESSION['avisar'] = 'Usuario nao encontrado.';
        $_SESSION['avisar_tipo'] = 'warn';
        header("Location: ../administrador.php");
        exit;
    }

    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM servico WHERE registro_id_registro = ? OR id_trabalhador = ?");
    $stmt->bind_param("ii", $id, $id);
    $stmt->execute();
    $count_row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $total_servicos = $count_row ? (int)$count_row['total'] : 0;

    if ($total_servicos > 0) {
        $_SESSION['avisar'] = 'Este usuario possui servicos vinculados. Exclua os servicos primeiro.';
        $_SESSION['avisar_tipo'] = 'warn';
        header("Location: ../administrador.php");
        exit;
    }

    $stmt = $conn->prepare("DELETE FROM trabalhador_funcoes WHERE registro_id_registro = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare("DELETE FROM registro WHERE id_registro = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $deletou = $stmt->affected_rows > 0;
    $stmt->close();

    if ($deletou) {
        $detalhes = 'Admin excluiu usuario';
        if ($motivo_acao !== '') {
            $detalhes .= '. Motivo: ' . $motivo_acao;
        }
        audit_log($conn, 'excluir', 'registro', $id, $detalhes);
        $_SESSION['avisar'] = 'Usuario excluido com sucesso.';
        $_SESSION['avisar_tipo'] = 'success';
    } else {
        $_SESSION['avisar'] = 'Nao foi possivel excluir o usuario.';
        $_SESSION['avisar_tipo'] = 'error';
    }

    header("Location: ../administrador.php");
?>
