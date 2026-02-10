<?php
    session_start();
    include_once "conexao.php";
    include_once "status.php";
    include_once "audit.php";
    $theme = isset($_SESSION['theme']) ? $_SESSION['theme'] : 'dark';
    $themeClass = $theme === 'light' ? 'theme-light' : 'theme-dark';
    if (!isset($_SESSION['cpf'])) {
        $_SESSION['avisar'] = "Você precisa estar logado no site!";
        header('location: login.php');
        exit;
        
    }
    if (isset($_POST['acao'], $_POST['id_servico'])) {
        $id_servico = (int)$_POST['id_servico'];
        $acao = trim($_POST['acao']);
        $buscar_status = "SELECT s.ativo, s.status_etapa, s.funcoes_id_funcoes, s.id_trabalhador, s.registro_id_registro, s.valor_atual,
            s.pagamento_status, s.pagamento_comprovante, r.pix_chave, r.pix_tipo, r.aceita_pix, r.aceita_dinheiro, r.aceita_cartao_presencial
            FROM servico s INNER JOIN registro r ON r.id_registro = s.id_trabalhador
            WHERE s.id_servico='$id_servico' LIMIT 1";
        $resultado_status = mysqli_query($conn, $buscar_status);
        $status_atual = mysqli_fetch_assoc($resultado_status);

        if (!$status_atual) {
            $_SESSION['avisar'] = "Servico nao encontrado.";
            header('location: servicos.php');
            exit;
        }

        $is_trabalhador = (int)$status_atual['id_trabalhador'] === (int)$_SESSION['id_acesso'];
        $is_cliente = (int)$status_atual['registro_id_registro'] === (int)$_SESSION['id_acesso'];
        $acoes_trabalhador = ['etapa', 'finalizar', 'confirmar_pagamento'];
        $acoes_cliente = ['pagar', 'pagar_presencial'];

        if (in_array($acao, $acoes_trabalhador, true) && !$is_trabalhador) {
            $_SESSION['avisar'] = "Acesso restrito para este servico.";
            header('location: servicos.php');
            exit;
        }
        if (in_array($acao, $acoes_cliente, true) && !$is_cliente) {
            $_SESSION['avisar'] = "Acesso restrito para este servico.";
            header('location: servicos.php');
            exit;
        }

        $status_atual_num = (int)$status_atual['ativo'];
        $etapa_atual = $status_atual['status_etapa'] !== null
            ? (int)$status_atual['status_etapa']
            : servico_etapa_from_status($status_atual_num);

        if ($acao === 'pagar') {
            if ($status_atual_num !== SERVICO_STATUS_AGUARDANDO_PAGAMENTO) {
                $_SESSION['avisar'] = "Este servico nao aceita pagamento no status atual.";
                $_SESSION['avisar_tipo'] = "warn";
                header('location: servicos.php');
                exit;
            }
            if ((int)$status_atual['pagamento_status'] !== 0) {
                $_SESSION['avisar'] = "Pagamento ja enviado ou confirmado.";
                $_SESSION['avisar_tipo'] = "warn";
                header('location: servicos.php');
                exit;
            }
            $comprovante = isset($_FILES['comprovante']) ? $_FILES['comprovante'] : null;
            if (!$comprovante || $comprovante['error'] !== UPLOAD_ERR_OK) {
                $_SESSION['avisar'] = "Envie o comprovante do pagamento.";
                $_SESSION['avisar_tipo'] = "warn";
                header('location: servicos.php');
                exit;
            }
            $permitidos = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'application/pdf' => 'pdf'];
            if (!isset($permitidos[$comprovante['type']])) {
                $_SESSION['avisar'] = "Formato invalido. Use JPG, PNG ou PDF.";
                $_SESSION['avisar_tipo'] = "error";
                header('location: servicos.php');
                exit;
            }
            $upload_dir = __DIR__ . '/image/comprovantes';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0775, true);
            }
            $base_path = 'image/comprovantes';
            $nome_comprovante = $base_path . '/comprovante_' . $id_servico . '_' . time() . '.' . $permitidos[$comprovante['type']];
            if (!move_uploaded_file($comprovante['tmp_name'], $upload_dir . '/' . basename($nome_comprovante))) {
                $_SESSION['avisar'] = "Falha ao salvar o comprovante.";
                $_SESSION['avisar_tipo'] = "error";
                header('location: servicos.php');
                exit;
            }
            $stmt = $conn->prepare("UPDATE servico SET pagamento_status=1, pagamento_comprovante=?, pagamento_data=NOW() WHERE id_servico=?");
            $stmt->bind_param("si", $nome_comprovante, $id_servico);
            $stmt->execute();
            $stmt->close();
            audit_log($conn, 'pagamento_enviado', 'servico', $id_servico, 'Cliente enviou comprovante');
            $mensagem = 'Cliente enviou comprovante de pagamento.';
            $link = 'servicos.php';
            $stmt = $conn->prepare("INSERT INTO notificacoes (registro_id_registro, mensagem, link) VALUES (?, ?, ?)");
            $stmt->bind_param("iss", $status_atual['id_trabalhador'], $mensagem, $link);
            $stmt->execute();
            $stmt->close();
            $_SESSION['avisar'] = "Comprovante enviado. Aguarde confirmacao.";
            $_SESSION['avisar_tipo'] = "success";
            header('location: servicos.php');
            exit;
        }

        if ($acao === 'pagar_presencial') {
            if ($status_atual_num !== SERVICO_STATUS_AGUARDANDO_PAGAMENTO) {
                $_SESSION['avisar'] = "Este servico nao aceita pagamento no status atual.";
                $_SESSION['avisar_tipo'] = "warn";
                header('location: servicos.php');
                exit;
            }
            if ((int)$status_atual['pagamento_status'] !== 0) {
                $_SESSION['avisar'] = "Pagamento ja enviado ou confirmado.";
                $_SESSION['avisar_tipo'] = "warn";
                header('location: servicos.php');
                exit;
            }
            $aceita_dinheiro = isset($status_atual['aceita_dinheiro']) ? (int)$status_atual['aceita_dinheiro'] === 1 : false;
            $aceita_cartao_presencial = isset($status_atual['aceita_cartao_presencial']) ? (int)$status_atual['aceita_cartao_presencial'] === 1 : false;
            if (!$aceita_dinheiro && !$aceita_cartao_presencial) {
                $_SESSION['avisar'] = "Pagamento presencial nao permitido para este colaborador.";
                $_SESSION['avisar_tipo'] = "warn";
                header('location: servicos.php');
                exit;
            }
            $comprovante = 'presencial';
            $stmt = $conn->prepare("UPDATE servico SET pagamento_status=1, pagamento_comprovante=?, pagamento_data=NOW() WHERE id_servico=?");
            $stmt->bind_param("si", $comprovante, $id_servico);
            $stmt->execute();
            $stmt->close();
            audit_log($conn, 'pagamento_presencial', 'servico', $id_servico, 'Cliente confirmou pagamento presencial');
            $mensagem = 'Cliente confirmou pagamento presencial.';
            $link = 'servicos.php';
            $stmt = $conn->prepare("INSERT INTO notificacoes (registro_id_registro, mensagem, link) VALUES (?, ?, ?)");
            $stmt->bind_param("iss", $status_atual['id_trabalhador'], $mensagem, $link);
            $stmt->execute();
            $stmt->close();
            $_SESSION['avisar'] = "Pagamento presencial informado. Aguarde confirmacao.";
            $_SESSION['avisar_tipo'] = "success";
            header('location: servicos.php');
            exit;
        }

        if ($acao === 'confirmar_pagamento') {
            if ($status_atual_num !== SERVICO_STATUS_AGUARDANDO_PAGAMENTO) {
                $_SESSION['avisar'] = "Este servico nao esta aguardando pagamento.";
                $_SESSION['avisar_tipo'] = "warn";
                header('location: servicos.php');
                exit;
            }
            if ((int)$status_atual['pagamento_status'] !== 1) {
                $_SESSION['avisar'] = "Nenhum comprovante pendente.";
                $_SESSION['avisar_tipo'] = "warn";
                header('location: servicos.php');
                exit;
            }
            $status_finalizado = SERVICO_STATUS_FINALIZADO;
            $stmt = $conn->prepare("UPDATE servico SET ativo=?, pagamento_status=2, pagamento_data=NOW() WHERE id_servico=?");
            $stmt->bind_param("ii", $status_finalizado, $id_servico);
            $stmt->execute();
            $stmt->close();
            audit_log($conn, 'pagamento_confirmado', 'servico', $id_servico, 'Colaborador confirmou pagamento');
            $mensagem = 'Pagamento confirmado pelo colaborador.';
            $link = 'historico.php';
            $stmt = $conn->prepare("INSERT INTO notificacoes (registro_id_registro, mensagem, link) VALUES (?, ?, ?)");
            $stmt->bind_param("iss", $status_atual['registro_id_registro'], $mensagem, $link);
            $stmt->execute();
            $stmt->close();
            $_SESSION['avisar'] = "Pagamento confirmado. Servico concluido.";
            $_SESSION['avisar_tipo'] = "success";
            header('location: servicos.php');
            exit;
        }

        if ($acao === 'etapa') {
            if ($status_atual_num !== SERVICO_STATUS_ATIVO) {
                $_SESSION['avisar'] = "Somente servicos ativos podem alterar etapa.";
                $_SESSION['avisar_tipo'] = "warn";
                header('location: servicos.php');
                exit;
            }
            $nova_etapa = isset($_POST['status_etapa']) ? (int)$_POST['status_etapa'] : $etapa_atual;
            if (!servico_etapa_can_transition($etapa_atual, $nova_etapa)) {
                $_SESSION['avisar'] = "Transicao de etapa invalida.";
                header('location: servicos.php');
                exit;
            }
            $stmt = $conn->prepare("UPDATE servico SET status_etapa=? WHERE id_servico=?");
            $stmt->bind_param("ii", $nova_etapa, $id_servico);
            $stmt->execute();
            $stmt->close();
            $_SESSION['avisar'] = "Etapa atualizada com sucesso.";
            $_SESSION['avisar_tipo'] = "success";
            header('location: servicos.php');
            exit;
        }

        if ($status_atual_num !== SERVICO_STATUS_ATIVO) {
            $_SESSION['avisar'] = "Nao e possivel finalizar este servico no status atual.";
            header('location: servicos.php');
            exit;
        }

        $pix_chave = trim((string)$status_atual['pix_chave']);
        $aceita_pix = isset($status_atual['aceita_pix']) ? (int)$status_atual['aceita_pix'] === 1 : true;
        $aceita_dinheiro = isset($status_atual['aceita_dinheiro']) ? (int)$status_atual['aceita_dinheiro'] === 1 : false;
        $aceita_cartao_presencial = isset($status_atual['aceita_cartao_presencial']) ? (int)$status_atual['aceita_cartao_presencial'] === 1 : false;
        if (!$aceita_pix && !$aceita_dinheiro && !$aceita_cartao_presencial) {
            $_SESSION['avisar'] = "Configure ao menos um metodo de pagamento antes de finalizar o servico.";
            $_SESSION['avisar_tipo'] = "warn";
            header('location: servicos.php');
            exit;
        }
        if ($aceita_pix && $pix_chave === '') {
            $_SESSION['avisar'] = "Informe sua chave PIX antes de finalizar o servico.";
            $_SESSION['avisar_tipo'] = "warn";
            header('location: servicos.php');
            exit;
        }

        $foto_antes = isset($_FILES['foto_antes']) ? $_FILES['foto_antes'] : null;
        $foto_depois = isset($_FILES['foto_depois']) ? $_FILES['foto_depois'] : null;
        if (!$foto_antes || !$foto_depois || $foto_antes['error'] !== UPLOAD_ERR_OK || $foto_depois['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['avisar'] = "Envie as fotos de antes e depois para finalizar.";
            $_SESSION['avisar_tipo'] = "warn";
            header('location: servicos.php');
            exit;
        }

        $permitidos = ['image/jpeg' => 'jpg', 'image/png' => 'png'];
        if (!isset($permitidos[$foto_antes['type']]) || !isset($permitidos[$foto_depois['type']])) {
            $_SESSION['avisar'] = "Formato de imagem invalido. Use JPG ou PNG.";
            $_SESSION['avisar_tipo'] = "error";
            header('location: servicos.php');
            exit;
        }

        $upload_dir = __DIR__ . '/image/servicos';
        $base_path = 'image/servicos';
        $nome_antes = $base_path . '/antes_' . $id_servico . '_' . time() . '.' . $permitidos[$foto_antes['type']];
        $nome_depois = $base_path . '/depois_' . $id_servico . '_' . time() . '.' . $permitidos[$foto_depois['type']];

        if (!move_uploaded_file($foto_antes['tmp_name'], $upload_dir . '/' . basename($nome_antes))) {
            $_SESSION['avisar'] = "Falha ao salvar a foto de antes.";
            $_SESSION['avisar_tipo'] = "error";
            header('location: servicos.php');
            exit;
        }
        if (!move_uploaded_file($foto_depois['tmp_name'], $upload_dir . '/' . basename($nome_depois))) {
            $_SESSION['avisar'] = "Falha ao salvar a foto de depois.";
            $_SESSION['avisar_tipo'] = "error";
            header('location: servicos.php');
            exit;
        }

        $funcoes_id = (int)$status_atual['funcoes_id_funcoes'];
        $stmt = $conn->prepare("SELECT id_item FROM checklist_itens WHERE funcoes_id_funcoes = ? AND ativo = 1");
        $stmt->bind_param("i", $funcoes_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $checklist_ids = [];
        while ($row = $result->fetch_assoc()) {
            $checklist_ids[] = (int)$row['id_item'];
        }
        $stmt->close();

        $selecionados = isset($_POST['checklist']) && is_array($_POST['checklist']) ? array_map('intval', $_POST['checklist']) : [];
        if (!empty($checklist_ids) && count(array_diff($checklist_ids, $selecionados)) > 0) {
            $_SESSION['avisar'] = "Conclua todo o checklist antes de finalizar.";
            $_SESSION['avisar_tipo'] = "warn";
            header('location: servicos.php');
            exit;
        }

        $stmt = $conn->prepare("DELETE FROM servico_checklist WHERE servico_id = ?");
        $stmt->bind_param("i", $id_servico);
        $stmt->execute();
        $stmt->close();

        if (!empty($checklist_ids)) {
            $stmt = $conn->prepare("INSERT INTO servico_checklist (servico_id, item_id, concluido) VALUES (?, ?, 1)");
            foreach ($checklist_ids as $item_id) {
                $stmt->bind_param("ii", $id_servico, $item_id);
                $stmt->execute();
            }
            $stmt->close();
        }

        $status_finalizado = SERVICO_STATUS_AGUARDANDO_PAGAMENTO;
        $etapa_finalizado = SERVICO_ETAPA_FINALIZADO;
        $tempo = isset($_POST['tempo']) ? (int)$_POST['tempo'] : 0;
        if ($tempo <= 0) {
            $_SESSION['avisar'] = "Informe um tempo valido em minutos.";
            $_SESSION['avisar_tipo'] = "warn";
            header('location: servicos.php');
            exit;
        }
        $valor_hora = (float)$status_atual['valor_atual'];
        $valor_final = round(($tempo / 60) * $valor_hora, 2);
        $stmt = $conn->prepare("UPDATE servico SET ativo=?, status_etapa=?, tempo_servico=?, valor_final=?, endereco='Finalizado', foto_antes=?, foto_depois=?, pagamento_status=0 WHERE id_servico=?");
        $stmt->bind_param("iiidssi", $status_finalizado, $etapa_finalizado, $tempo, $valor_final, $nome_antes, $nome_depois, $id_servico);
        $stmt->execute();
        $stmt->close();
        audit_log($conn, 'finalizar', 'servico', $id_servico, "Tempo: $tempo");
        $mensagem = 'Servico finalizado. Pagamento pendente.';
        $link = 'servicos.php';
        $stmt = $conn->prepare("INSERT INTO notificacoes (registro_id_registro, mensagem, link) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $status_atual['registro_id_registro'], $mensagem, $link);
        $stmt->execute();
        $stmt->close();
        $_SESSION['avisar'] = "Servico finalizado. Aguardando pagamento do cliente.";
        $_SESSION['avisar_tipo'] = "success";
        header('location: index.php');
        exit;
    }
    include_once ("all.php");
?>

<!DOCTYPE html>
<html lang="pt-br">
    <head>
        <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0"> 
            <meta name="author" content="Gabriel Nepomuceno de Almeida dos Santos">
            <meta name="keywords" content="HTML, CSS">
            <meta name="description" content="Pagina inicial do Serviços Relâmpagos">
            <link rel="stylesheet" href="css/estrutura_geral.css">
            <title>Página principal</title>
            <script>
                function seiLa() {
                    let valor_hora = document.getElementById("conta1").textContent;
                    let minutos = document.getElementById("conta2").value;
                    let hora = minutos/60;
                    let total = Math.round(hora * valor_hora);
                    document.getElementById("conta3").textContent = "R$ "+total;
                }
                function confirmeIsso() {
                    let check = document.getElementById("confirm_total");
                    if (check && !check.checked) {
                        if (window.showToast) {
                            showToast("Confirme que informou o valor total ao cliente.", "warn");
                        }
                        return false;
                    }
                    return true;
                }
            </script>
    </head>

    
    <body class="centralizar <?php echo $themeClass; ?>">
        <?php include 'menu.php'; ?>
        <div class="menu-spacer"></div>
        <main class="page">
        <br>
        <?php 
            if (isset($_GET['servic'])) {
                $comando_nome = "SELECT B.nome_func as 'funcao', A.valor_atual, A.funcoes_id_funcoes, A.status_etapa, A.ativo FROM servico A INNER JOIN funcoes B ON A.funcoes_id_funcoes = B.id_funcoes WHERE id_servico = '$_GET[servic]'";
                $joga_no_banco = mysqli_query($conn, $comando_nome);
                $nome_funcao = mysqli_fetch_array($joga_no_banco);
                if (!$nome_funcao) {
                    $_SESSION['avisar'] = 'Servico nao encontrado.';
                    header('location: servicos.php');
                    exit;
                }

                $etapa_atual = $nome_funcao['status_etapa'] !== null ? (int)$nome_funcao['status_etapa'] : servico_etapa_from_status($nome_funcao['ativo']);
                $steps = servico_etapa_steps();
                $next_step = null;
                foreach ($steps as $idx => $step) {
                    if ($step === $etapa_atual && isset($steps[$idx + 1])) {
                        $next_step = $steps[$idx + 1];
                        break;
                    }
                }

                $checklist_itens = [];
                $stmt = $conn->prepare("SELECT id_item, descricao FROM checklist_itens WHERE funcoes_id_funcoes = ? AND ativo = 1");
                $stmt->bind_param("i", $nome_funcao['funcoes_id_funcoes']);
                $stmt->execute();
                $result = $stmt->get_result();
                while ($row = $result->fetch_assoc()) {
                    $checklist_itens[] = $row;
                }
                $stmt->close();

                $checklist_html = '';
                if (!empty($checklist_itens)) {
                    $checklist_html .= "<div class='section-title'>Checklist de execucao</div>";
                    foreach ($checklist_itens as $item) {
                        $item_id = (int)$item['id_item'];
                        $desc = htmlspecialchars($item['descricao'], ENT_QUOTES, 'UTF-8');
                        $checklist_html .= "<label style='display: block; margin: 6px 0;'><input type='checkbox' name='checklist[]' value='{$item_id}'> {$desc}</label>";
                    }
                } else {
                    $checklist_html .= "<div class='texto' style='font-size: 14px;'>Nenhum checklist cadastrado para esta funcao.</div>";
                }

                $etapa_label = servico_etapa_label($etapa_atual);
                $etapa_next_label = $next_step ? servico_etapa_label($next_step) : '';

                echo "<div class='hidden' id='hidden65'>
                    <div class='title'>Finalizar serviço</div>
                    <label>Função: $nome_funcao[funcao].</label>  <label>Valor por Hora: <b id='conta1'>$nome_funcao[valor_atual]</b>.</label> <br>
                    <label>Etapa atual: <b>{$etapa_label}</b></label> <br>";
                if ($next_step) {
                    echo "<form action='servicos.php' method='POST' style='margin: 10px 0;'>
                            <input type='hidden' name='acao' value='etapa'>
                            <input type='hidden' name='id_servico' value='$_GET[servic]'>
                            <input type='hidden' name='status_etapa' value='{$next_step}'>
                            <button type='submit' class='btn btn-ghost btn-small'>Avancar para: {$etapa_next_label}</button>
                        </form>";
                }
                echo "<hr style='margin: 16px 0; border-color: var(--c-border);'>
                    <form action='servicos.php' method='POST' enctype='multipart/form-data'>
                        <label>Quanto tempo durou?(responda em minutos)</label> <br>
                        <input type='hidden' name='acao' value='finalizar'>
                        <input type='hidden' value='$_GET[servic]' name='id_servico'>
                        <input type='text' name='tempo' id='conta2' placeholder='TEMPO' onchange='seiLa()'> <br>
                        <label>Valor total calculado: <b id='conta3'>Digite o tempo</b></label>
                        <div style='margin: 12px 0;'>
                            {$checklist_html}
                        </div>
                        <div style='margin: 12px 0;'>
                            <label>Foto antes:</label><br>
                            <input type='file' name='foto_antes' accept='image/png, image/jpeg' required> <br>
                            <label>Foto depois:</label><br>
                            <input type='file' name='foto_depois' accept='image/png, image/jpeg' required> <br>
                        </div>
                        <div class='hidden_sub'>
                            <label style='display: block; margin-bottom: 8px;'>
                                <input type='checkbox' id='confirm_total'> Confirmo que informei o valor total
                            </label>
                            <input type='submit' value='Confirmar' onclick='return confirmeIsso()'>
                        </div>
                    </form>
                </div>";
                echo "<script>invisibleON('hidden65');</script>";
            }
        ?>
        <?php include 'includes/servicos_lista.php'; ?>
        </main>
    </body>

    <footer class="footer">
        <?php include 'pe.html'; ?>
    </footer>
</html>