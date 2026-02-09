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
                C.valor_atual 'valor', C.ativo 'ativo', C.status_etapa 'status_etapa', C.registro_id_registro 'id_cliente', C.id_trabalhador 'id_trabalhador', C.id_servico
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
                    if ($linha54['id_trabalhador'] == $_SESSION['id_acesso']) {
                        echo "<tr><td colspan='6'><a href='servicos.php?servic={$linha54['id_servico']}' target='_parent'>Finalizar servico</a></td></tr>";
                    }
                }
            }
        ?>
    </table>
</div>
