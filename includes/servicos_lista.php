<?php
    if (!isset($conn)) {
        include_once __DIR__ . '/../conexao.php';
    }
    if (!function_exists('servico_status_label')) {
        include_once __DIR__ . '/../status.php';
    }
?>

<div class="tabela_adm">
    <table style="visibility: visible">
        <tr>
            <td>Situacao</td>
            <td>Nome</td>
            <td>Tipo de servico</td>
            <td>Valor por Hora</td>
            <td>Endereco</td>
            <td>Progresso</td>
        </tr>
        <?php
            $comando_testar2 = "SELECT A.nome 'nome_trabalhador', B.nome_func 'funcao', C.endereco 'endereco',
                C.valor_atual 'valor', C.tempo_servico 'tempo_servico', C.valor_final 'valor_final', C.ativo 'ativo', C.status_etapa 'status_etapa',
                C.registro_id_registro 'id_cliente', C.id_trabalhador 'id_trabalhador', C.id_servico, C.pagamento_status, C.pagamento_comprovante,
                A.pix_tipo 'pix_tipo', A.pix_chave 'pix_chave', A.aceita_pix, A.aceita_dinheiro, A.aceita_cartao_presencial
                FROM servico C INNER JOIN registro A ON A.id_registro = C.id_trabalhador
                INNER JOIN funcoes B ON B.id_funcoes = C.funcoes_id_funcoes WHERE ativo>0";
            $joga_no_banco = mysqli_query($conn, $comando_testar2);
            while ($linha54 = mysqli_fetch_array($joga_no_banco)) {
                if ($linha54['id_cliente'] == $_SESSION['id_acesso'] || $linha54['id_trabalhador'] == $_SESSION['id_acesso']) {
                    $status_label = servico_status_label($linha54['ativo']);
                    $badge_class = servico_status_badge_class($linha54['ativo']);
                    $etapa_atual = $linha54['status_etapa'] !== null ? (int)$linha54['status_etapa'] : servico_etapa_from_status($linha54['ativo']);
                    $steps = servico_etapa_steps();
                    $timeline_html = "<div class='service-timeline'>";
                    foreach ($steps as $step) {
                        $done = $step <= $etapa_atual ? ' is-done' : '';
                        $label = servico_etapa_label($step);
                        $timeline_html .= "<span class='service-step{$done}'>{$label}</span>";
                    }
                    $timeline_html .= "</div>";
                    echo "<tr>
                        <td><span class='status-badge {$badge_class}'>{$status_label}</span></td>
                        <td>{$linha54['nome_trabalhador']}</td>
                        <td>{$linha54['funcao']}</td>
                        <td>{$linha54['valor']}</td>
                        <td>{$linha54['endereco']}</td>
                        <td data-label='Progresso'>{$timeline_html}</td>
                    </tr>";
                    $status_atual = (int)$linha54['ativo'];
                    $pagamento_status = isset($linha54['pagamento_status']) ? (int)$linha54['pagamento_status'] : 0;
                    if ($linha54['id_trabalhador'] == $_SESSION['id_acesso']) {
                        if ($status_atual === SERVICO_STATUS_ATIVO) {
                            echo "<tr><td colspan='6'><a href='servicos.php?servic={$linha54['id_servico']}' target='_parent'>Finalizar servico</a></td></tr>";
                        }
                        if ($status_atual === SERVICO_STATUS_AGUARDANDO_PAGAMENTO) {
                            if ($pagamento_status === 1 && !empty($linha54['pagamento_comprovante'])) {
                                $comprovante = htmlspecialchars($linha54['pagamento_comprovante'], ENT_QUOTES, 'UTF-8');
                                $comprovante_html = $comprovante === 'presencial'
                                    ? "<div class='texto'>Pagamento informado como presencial.</div>"
                                    : "<a class='btn btn-ghost btn-small' href='{$comprovante}' target='_blank'>Ver comprovante</a>";
                                echo "<tr><td colspan='6'>
                                    <div class='texto'>Pagamento informado pelo cliente.</div>
                                    {$comprovante_html}
                                    <a class='btn btn-primary btn-small' href='confirmar_pagamento.php?servico={$linha54['id_servico']}'>Ir para confirmacao</a>
                                </td></tr>";
                            } else {
                                echo "<tr><td colspan='6'><div class='texto'>Aguardando pagamento do cliente.</div></td></tr>";
                            }
                        }
                    }
                    if ($linha54['id_cliente'] == $_SESSION['id_acesso'] && $status_atual === SERVICO_STATUS_AGUARDANDO_PAGAMENTO) {
                        $valor_final = $linha54['valor_final'] !== null ? (float)$linha54['valor_final'] : 0.0;
                        if ($valor_final <= 0 && $linha54['tempo_servico']) {
                            $valor_final = ((float)$linha54['valor'] * ((float)$linha54['tempo_servico'] / 60));
                        }
                        $pix_tipo = htmlspecialchars((string)$linha54['pix_tipo'], ENT_QUOTES, 'UTF-8');
                        $pix_chave = htmlspecialchars((string)$linha54['pix_chave'], ENT_QUOTES, 'UTF-8');
                        $aceita_pix = isset($linha54['aceita_pix']) ? (int)$linha54['aceita_pix'] === 1 : true;
                        $aceita_dinheiro = isset($linha54['aceita_dinheiro']) ? (int)$linha54['aceita_dinheiro'] === 1 : false;
                        $aceita_cartao_presencial = isset($linha54['aceita_cartao_presencial']) ? (int)$linha54['aceita_cartao_presencial'] === 1 : false;
                        $valor_fmt = number_format($valor_final, 2, ',', '.');
                        if ($aceita_pix) {
                            $pix_info = $pix_chave ? ($pix_tipo ? "{$pix_tipo}: {$pix_chave}" : $pix_chave) : 'Chave PIX nao informada';
                        } else {
                            $pix_info = 'PIX nao aceito';
                        }
                        $pagamento_msg = $pagamento_status === 1 ? "Pagamento informado. Aguarde confirmacao." : "Pagamento pendente.";
                        $upload_html = "";
                        if ($pagamento_status === 0) {
                            if ($aceita_pix || $aceita_dinheiro || $aceita_cartao_presencial) {
                                if ($aceita_pix && !$pix_chave) {
                                    $upload_html = "<div class='texto'>O colaborador ainda nao informou uma chave PIX.</div>";
                                } else {
                                    $upload_html = "<a class='btn btn-primary btn-small' href='pagamento.php?servico={$linha54['id_servico']}'>Ir para pagamento</a>";
                                }
                            } else {
                                $upload_html = "<div class='texto'>O colaborador nao configurou formas de pagamento.</div>";
                            }
                        }
                        echo "<tr><td colspan='6'>
                            <div class='texto'>{$pagamento_msg} Valor: R$ {$valor_fmt}</div>
                            <div class='texto'>PIX do colaborador: {$pix_info}</div>
                            {$upload_html}
                        </td></tr>";
                    }
                }
            }
        ?>
    </table>
</div>
