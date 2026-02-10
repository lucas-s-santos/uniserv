<?php
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    if (!isset($_SESSION['cpf'])) {
        $_SESSION['avisar'] = "Voce precisa estar logado para acessar esta area.";
        header('location: ../login.php');
        exit;
    }
    if ((int)$_SESSION['funcao'] !== 2) {
        $_SESSION['avisar'] = "Acesso restrito para colaboradores.";
        header('location: ../login.php');
        exit;
    }
    include_once "../conexao.php";
    include_once "../status.php";
    include_once "../audit.php";
    $theme = isset($_SESSION['theme']) ? $_SESSION['theme'] : 'dark';
    $themeClass = $theme === 'light' ? 'theme-light' : 'theme-dark';

    if (isset($_POST['cidade_mudar'])) {
        $comando_editar = "UPDATE registro SET cidade='$_POST[cidade_mudar]' WHERE id_registro='$_SESSION[id_acesso]'";
        unset($_POST['cidade_mudar']);
        mysqli_query($conn, $comando_editar);
        $_SESSION['avisar'] = "Cidade atualizada com sucesso.";
        $_SESSION['avisar_tipo'] = "success";
    }
    if (isset($_POST['id_servico'])) {
        $id_servico = (int)$_POST['id_servico'];
        $buscar_status = "SELECT ativo, registro_id_registro FROM servico WHERE id_servico='$id_servico' LIMIT 1";
        $resultado_status = mysqli_query($conn, $buscar_status);
        $status_atual = mysqli_fetch_assoc($resultado_status);

        if (!$status_atual) {
            $_SESSION['avisar'] = "Servico nao encontrado.";
            $_SESSION['avisar_tipo'] = "error";
        } else {
            $status_atual = (int)$status_atual['ativo'];
            $status_pendente = SERVICO_STATUS_PENDENTE;
            if ($status_atual !== $status_pendente) {
                $_SESSION['avisar'] = "Este chamado ja foi respondido.";
                $_SESSION['avisar_tipo'] = "warn";
            } else {
                if ($_POST['escolha'] == 'sim') {
                    $status_aceito = SERVICO_STATUS_ATIVO;
                    $etapa_orcamento = SERVICO_ETAPA_ORCAMENTO;
                    $comando_editar = "UPDATE servico SET ativo='$status_aceito', status_etapa='$etapa_orcamento' WHERE id_servico='$id_servico'";
                } else {
                    $status_recusado = SERVICO_STATUS_RECUSADO;
                    $comando_editar = "UPDATE servico SET ativo='$status_recusado', status_etapa=NULL, comentario='recusedservice#43242' WHERE id_servico='$id_servico'";
                }
                mysqli_query($conn, $comando_editar);
                $acao = $_POST['escolha'] == 'sim' ? 'aceitar' : 'recusar';
                audit_log($conn, $acao, 'servico', $id_servico, 'Resposta do colaborador');
                $cliente_id = (int)$status_atual['registro_id_registro'];
                $mensagem = $_POST['escolha'] == 'sim' ? 'Seu chamado foi aceito.' : 'Seu chamado foi recusado.';
                $link = 'servicos.php';
                $stmt = $conn->prepare("INSERT INTO notificacoes (registro_id_registro, mensagem, link) VALUES (?, ?, ?)");
                $stmt->bind_param("iss", $cliente_id, $mensagem, $link);
                $stmt->execute();
                $stmt->close();
                if ($_POST['escolha'] == 'sim') {
                    $_SESSION['avisar'] = "Chamado aceito com sucesso.";
                    $_SESSION['avisar_tipo'] = "success";
                } else {
                    $_SESSION['avisar'] = "Chamado recusado.";
                    $_SESSION['avisar_tipo'] = "warn";
                }
                if ($_POST['escolha'] == 'sim') {
                    echo "<script> parent.window.location.href = '../servicos.php';</script>";
                }
            }
        }
        unset($_POST['id_servico']);
    }
?>

<div class="collab-calls">
    <div class="collab-calls__header">
        <div>
            <div class="subtitle">Chamados pendentes</div>
            <div class="texto">Aqui aparecem os pedidos da sua cidade. Atualize a cidade se precisar.</div>
        </div>
        <form action="#" method="POST" class="collab-city">
            <?php 
                $analise_cidade = "SELECT * FROM registro WHERE id_registro = '$_SESSION[id_acesso]' LIMIT 1";
                $procure_sua_cidade = mysqli_query($conn, $analise_cidade);
                $resultado5 = mysqli_fetch_assoc($procure_sua_cidade);

                echo "<label>Minha cidade</label>
                <div class='collab-city__controls'>
                    <input type='text' name='cidade_mudar' placeholder='Digite sua cidade' value='$resultado5[cidade]' required>
                    <input type='submit' value='Atualizar'>
                </div>";

                if ($resultado5['cidade'] == " " || $resultado5['cidade'] == "") {
                    echo "<div class='texto' style='color: var(--c-notice-warn-text);'>Você precisa digitar sua cidade ou nunca será chamado.</div>";
                }
            ?>
        </form>
    </div>

    <?php
        function show_call_notice($message) {
            $safe = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
            echo "<div class='collab-empty'>{$safe}</div>";
        }

        $status_pendente = SERVICO_STATUS_PENDENTE;
        $verifique = "SELECT A.endereco 'endereco', B.nome_func 'funcao', A.id_servico 'id_servico',
            A.valor_atual 'valor', A.data_2 'data_2', C.nome 'nome', C.foto 'foto_cliente'
            FROM servico A INNER JOIN registro C ON C.id_registro = A.registro_id_registro 
            INNER JOIN funcoes B ON B.id_funcoes = A.funcoes_id_funcoes
            WHERE id_trabalhador = '$_SESSION[id_acesso]' AND ativo='$status_pendente'";
        $jogue_no_banco = mysqli_query($conn, $verifique);
        $total_chamados = mysqli_num_rows($jogue_no_banco);
        $tem_chamado = false;
        if ($total_chamados > 0) {
            echo "<div class='collab-calls__summary'>
                    <div class='collab-calls__count'>Voce tem <strong>{$total_chamados}</strong> chamado(s) aguardando resposta.</div>
                    <div class='collab-calls__chips'>
                        <span class='call-chip'>Responda rapido para aumentar sua avaliacao.</span>
                        <span class='call-chip call-chip--warn'>Pendentes agora</span>
                    </div>
                </div>";
        }
        while ($linha75 = mysqli_fetch_array($jogue_no_banco)) {
            $tem_chamado = true;
            $data_formatada = $linha75['data_2'] ? date('d/m/Y', strtotime($linha75['data_2'])) : 'Hoje';
            $nome = htmlspecialchars($linha75['nome'], ENT_QUOTES, 'UTF-8');
            $funcao = htmlspecialchars($linha75['funcao'], ENT_QUOTES, 'UTF-8');
            $endereco = htmlspecialchars($linha75['endereco'], ENT_QUOTES, 'UTF-8');
            $valor = number_format((float)$linha75['valor'], 2, ',', '.');
            $foto_cliente = !empty($linha75['foto_cliente']) ? $linha75['foto_cliente'] : 'image/logoservicore.jpg';
            $foto_cliente_safe = htmlspecialchars($foto_cliente, ENT_QUOTES, 'UTF-8');
            echo "<div class='call-card'>
                <div class='call-card__header'>
                    <div class='call-card__identity'>
                        <img class='call-card__avatar' src='{$foto_cliente_safe}' alt='Foto do cliente'>
                        <div>
                            <div class='call-card__title'>Pedido de {$nome}</div>
                            <div class='call-card__subtitle'>{$funcao}</div>
                        </div>
                    </div>
                    <span class='status-badge status-badge--pending'>Pendente</span>
                </div>
                <div class='call-card__meta'>
                    <span>Endereco: {$endereco}</span>
                    <span>Valor/hora: R$ {$valor}</span>
                    <span>Data: {$data_formatada}</span>
                </div>
                <form action='#' method='POST' class='call-card__actions'>
                    <input type='hidden' name='id_servico' value='{$linha75['id_servico']}'>
                    <button type='submit' name='escolha' value='sim' class='btn btn-primary btn-small'>Aceitar</button>
                    <button type='submit' name='escolha' value='nao' class='btn btn-ghost btn-small'>Recusar</button>
                </form>
            </div>";
        }

        if (!$tem_chamado) {
            show_call_notice('Nenhum chamado pendente no momento.');
        }
    ?>
</div>