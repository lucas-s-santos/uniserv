<?php
    if (!isset($conn)) {
        include_once __DIR__ . '/../conexao.php';
    }
    if (!function_exists('servico_status_label')) {
        include_once __DIR__ . '/../status.php';
    }
?>

<div class="services-page">
    <div class="services-header">
        <div>
            <h1 class="page-title">Meus servicos</h1>
            <p class="page-subtitle">Gerencie todos os seus chamados em um so lugar</p>
        </div>
    </div>

    <?php
        $comando_testar2 = "SELECT A.nome 'nome_trabalhador', A.foto 'foto_trabalhador',
            D.nome 'nome_cliente', D.foto 'foto_cliente', B.nome_func 'funcao', C.endereco 'endereco',
            C.valor_atual 'valor', C.tempo_servico 'tempo_servico', C.valor_final 'valor_final', C.ativo 'ativo', C.status_etapa 'status_etapa',
            C.registro_id_registro 'id_cliente', C.id_trabalhador 'id_trabalhador', C.id_servico, C.pagamento_status, C.pagamento_comprovante,
            A.pix_tipo 'pix_tipo', A.pix_chave 'pix_chave', A.aceita_pix, A.aceita_dinheiro, A.aceita_cartao_presencial,
            C.data_2 'data_servico'
            FROM servico C INNER JOIN registro A ON A.id_registro = C.id_trabalhador
            INNER JOIN registro D ON D.id_registro = C.registro_id_registro
            INNER JOIN funcoes B ON B.id_funcoes = C.funcoes_id_funcoes WHERE ativo>0";
        $joga_no_banco = mysqli_query($conn, $comando_testar2);
        
        $servicos_ativos = [];
        $servicos_aguardando = [];
        $servicos_pendentes = [];
        
        while ($linha54 = mysqli_fetch_array($joga_no_banco)) {
            if ($linha54['id_cliente'] == $_SESSION['id_acesso'] || $linha54['id_trabalhador'] == $_SESSION['id_acesso']) {
                $status_num = (int)$linha54['ativo'];
                if ($status_num === SERVICO_STATUS_ATIVO) {
                    $servicos_ativos[] = $linha54;
                } elseif ($status_num === SERVICO_STATUS_AGUARDANDO_PAGAMENTO) {
                    $servicos_aguardando[] = $linha54;
                } elseif ($status_num === SERVICO_STATUS_PENDENTE) {
                    $servicos_pendentes[] = $linha54;
                }
            }
        }
        
        $total_ativos = count($servicos_ativos);
        $total_aguardando = count($servicos_aguardando);
        $total_pendentes = count($servicos_pendentes);
        $total_geral = $total_ativos + $total_aguardando + $total_pendentes;
    ?>

    <div class="services-tabs">
        <button class="services-tab is-active" data-tab="ativos">
            <span class="services-tab__icon">⚡</span>
            <span class="services-tab__label">Em andamento</span>
            <span class="services-tab__count"><?php echo $total_ativos; ?></span>
        </button>
        <button class="services-tab" data-tab="aguardando">
            <span class="services-tab__icon">💳</span>
            <span class="services-tab__label">Aguardando pagamento</span>
            <span class="services-tab__count"><?php echo $total_aguardando; ?></span>
        </button>
        <button class="services-tab" data-tab="pendentes">
            <span class="services-tab__icon">⏰</span>
            <span class="services-tab__label">Pendentes</span>
            <span class="services-tab__count"><?php echo $total_pendentes; ?></span>
        </button>
    </div>

    <?php if ($total_geral === 0): ?>
        <div class="collab-empty">
            <div class="collab-empty__icon">📋</div>
            <div class="collab-empty__text">Nenhum servico encontrado</div>
            <p class="collab-empty__hint">Seus chamados aparecerao aqui quando voce receber solicitacoes</p>
        </div>
    <?php endif; ?>

    <!-- Servicos Ativos -->
    <div class="services-content is-active" data-content="ativos">
        <?php if (empty($servicos_ativos)): ?>
            <div class="collab-empty collab-empty--compact">
                <div class="collab-empty__text">Nenhum servico em andamento</div>
            </div>
        <?php else: ?>
            <div class="services-grid">
            <?php foreach ($servicos_ativos as $servico): 
                $is_trabalhador = $servico['id_trabalhador'] == $_SESSION['id_acesso'];
                $foto = $is_trabalhador
                    ? (!empty($servico['foto_cliente']) ? $servico['foto_cliente'] : 'image/logoservicore.jpg')
                    : (!empty($servico['foto_trabalhador']) ? $servico['foto_trabalhador'] : 'image/logoservicore.jpg');
                $nome_pessoa = $is_trabalhador ? $servico['nome_cliente'] : $servico['nome_trabalhador'];
                $papel = $is_trabalhador ? 'Cliente' : 'Colaborador';
                $etapa_atual = $servico['status_etapa'] !== null ? (int)$servico['status_etapa'] : servico_etapa_from_status($servico['ativo']);
                $steps = servico_etapa_steps();
                $progress = 0;
                foreach ($steps as $idx => $step) {
                    if ($step <= $etapa_atual) $progress = (($idx + 1) / count($steps)) * 100;
                }
                $data_fmt = $servico['data_servico'] ? date('d/m/Y', strtotime($servico['data_servico'])) : '-';
            ?>
                <div class="service-card service-card--active">
                    <div class="service-card__header">
                        <div class="service-card__person">
                            <img class="service-card__avatar" src="<?php echo htmlspecialchars($foto, ENT_QUOTES, 'UTF-8'); ?>" alt="Foto">
                            <div>
                                <div class="service-card__name"><?php echo htmlspecialchars($nome_pessoa, ENT_QUOTES, 'UTF-8'); ?></div>
                                <div class="service-card__role"><?php echo $papel; ?></div>
                            </div>
                        </div>
                        <span class="status-badge status-badge--active">Em andamento</span>
                    </div>

                    <div class="service-card__service">
                        <span class="service-card__icon">🛠️</span>
                        <div class="service-card__service-info">
                            <div class="service-card__service-name"><?php echo htmlspecialchars($servico['funcao'], ENT_QUOTES, 'UTF-8'); ?></div>
                            <div class="service-card__service-meta">R$ <?php echo number_format((float)$servico['valor'], 2, ',', '.'); ?>/hora · <?php echo $data_fmt; ?></div>
                        </div>
                    </div>

                    <div class="service-card__location">
                        <span>📍</span>
                        <span><?php echo htmlspecialchars($servico['endereco'], ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>

                    <div class="service-card__progress">
                        <div class="service-progress">
                            <div class="service-progress__bar">
                                <div class="service-progress__fill" style="width: <?php echo $progress; ?>%"></div>
                            </div>
                            <div class="service-progress__steps">
                                <?php foreach ($steps as $step): 
                                    $done = $step <= $etapa_atual ? ' is-done' : '';
                                    $label = servico_etapa_label($step);
                                ?>
                                    <span class="service-progress__step<?php echo $done; ?>" title="<?php echo $label; ?>"></span>
                                <?php endforeach; ?>
                            </div>
                            <div class="service-progress__label"><?php echo servico_etapa_label($etapa_atual); ?></div>
                        </div>
                    </div>

                    <?php if ($is_trabalhador): ?>
                        <div class="service-card__actions">
                            <a href="servicos.php?servic=<?php echo $servico['id_servico']; ?>" class="btn btn-primary btn-block">
                                Finalizar servico →
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="service-card__info">
                            <span class="info-badge">Aguarde a finalizacao do colaborador</span>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Servicos Aguardando Pagamento -->
    <div class="services-content" data-content="aguardando">
        <?php if (empty($servicos_aguardando)): ?>
            <div class="collab-empty collab-empty--compact">
                <div class="collab-empty__text">Nenhum servico aguardando pagamento</div>
            </div>
        <?php else: ?>
            <div class="services-grid">
            <?php foreach ($servicos_aguardando as $servico): 
                $is_trabalhador = $servico['id_trabalhador'] == $_SESSION['id_acesso'];
                $foto = $is_trabalhador
                    ? (!empty($servico['foto_cliente']) ? $servico['foto_cliente'] : 'image/logoservicore.jpg')
                    : (!empty($servico['foto_trabalhador']) ? $servico['foto_trabalhador'] : 'image/logoservicore.jpg');
                $nome_pessoa = $is_trabalhador ? $servico['nome_cliente'] : $servico['nome_trabalhador'];
                $papel = $is_trabalhador ? 'Cliente' : 'Colaborador';
                $pagamento_status = isset($servico['pagamento_status']) ? (int)$servico['pagamento_status'] : 0;
                $valor_final = $servico['valor_final'] !== null ? (float)$servico['valor_final'] : 0.0;
                if ($valor_final <= 0 && $servico['tempo_servico']) {
                    $valor_final = ((float)$servico['valor'] * ((float)$servico['tempo_servico'] / 60));
                }
                $data_fmt = $servico['data_servico'] ? date('d/m/Y', strtotime($servico['data_servico'])) : '-';
            ?>
                <div class="service-card service-card--payment">
                    <div class="service-card__header">
                        <div class="service-card__person">
                            <img class="service-card__avatar" src="<?php echo htmlspecialchars($foto, ENT_QUOTES, 'UTF-8'); ?>" alt="Foto">
                            <div>
                                <div class="service-card__name"><?php echo htmlspecialchars($nome_pessoa, ENT_QUOTES, 'UTF-8'); ?></div>
                                <div class="service-card__role"><?php echo $papel; ?></div>
                            </div>
                        </div>
                        <span class="status-badge status-badge--warning">Aguardando pagamento</span>
                    </div>

                    <div class="service-card__service">
                        <span class="service-card__icon">🛠️</span>
                        <div class="service-card__service-info">
                            <div class="service-card__service-name"><?php echo htmlspecialchars($servico['funcao'], ENT_QUOTES, 'UTF-8'); ?></div>
                            <div class="service-card__service-meta">Total: R$ <?php echo number_format($valor_final, 2, ',', '.'); ?> · <?php echo $data_fmt; ?></div>
                        </div>
                    </div>

                    <div class="service-card__location">
                        <span>📍</span>
                        <span><?php echo htmlspecialchars($servico['endereco'], ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>

                    <?php if ($is_trabalhador): ?>
                        <?php if ($pagamento_status === 1 && !empty($servico['pagamento_comprovante'])): ?>
                            <div class="payment-notice payment-notice--success">
                                <span class="payment-notice__icon">✓</span>
                                <div>
                                    <div class="payment-notice__title">Pagamento informado</div>
                                    <div class="payment-notice__text">Cliente enviou comprovante. Verifique e confirme.</div>
                                </div>
                            </div>
                            <div class="service-card__actions">
                                <?php if ($servico['pagamento_comprovante'] !== 'presencial'): ?>
                                    <a href="<?php echo htmlspecialchars($servico['pagamento_comprovante'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" class="btn btn-ghost btn-small">
                                        Ver comprovante
                                    </a>
                                <?php else: ?>
                                    <span class="info-badge">Pagamento presencial</span>
                                <?php endif; ?>
                                <a href="confirmar_pagamento.php?servico=<?php echo $servico['id_servico']; ?>" class="btn btn-success btn-block">
                                    Confirmar pagamento →
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="payment-notice">
                                <span class="payment-notice__icon">⏳</span>
                                <div class="payment-notice__text">Aguardando cliente enviar pagamento</div>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <?php 
                            $pix_chave = htmlspecialchars((string)$servico['pix_chave'], ENT_QUOTES, 'UTF-8');
                            $aceita_pix = isset($servico['aceita_pix']) ? (int)$servico['aceita_pix'] === 1 : true;
                            $aceita_dinheiro = isset($servico['aceita_dinheiro']) ? (int)$servico['aceita_dinheiro'] === 1 : false;
                            $aceita_cartao = isset($servico['aceita_cartao_presencial']) ? (int)$servico['aceita_cartao_presencial'] === 1 : false;
                        ?>
                        <?php if ($pagamento_status === 1): ?>
                            <div class="payment-notice payment-notice--success">
                                <span class="payment-notice__icon">✓</span>
                                <div>
                                    <div class="payment-notice__title">Pagamento enviado</div>
                                    <div class="payment-notice__text">Aguarde confirmacao do colaborador</div>
                                </div>
                            </div>
                        <?php elseif ($aceita_pix || $aceita_dinheiro || $aceita_cartao): ?>
                            <div class="payment-info">
                                <div class="payment-info__title">Informacoes de pagamento</div>
                                <div class="payment-info__methods">
                                    <?php if ($aceita_pix && $pix_chave): ?>
                                        <span class="payment-badge">PIX disponivel</span>
                                    <?php endif; ?>
                                    <?php if ($aceita_dinheiro): ?>
                                        <span class="payment-badge">Dinheiro</span>
                                    <?php endif; ?>
                                    <?php if ($aceita_cartao): ?>
                                        <span class="payment-badge">Cartao</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="service-card__actions">
                                <a href="pagamento.php?servico=<?php echo $servico['id_servico']; ?>" class="btn btn-primary btn-block">
                                    Realizar pagamento →
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="payment-notice payment-notice--warn">
                                <span class="payment-notice__icon">⚠</span>
                                <div class="payment-notice__text">Colaborador nao configurou formas de pagamento</div>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Servicos Pendentes -->
    <div class="services-content" data-content="pendentes">
        <?php if (empty($servicos_pendentes)): ?>
            <div class="collab-empty collab-empty--compact">
                <div class="collab-empty__text">Nenhum servico pendente</div>
            </div>
        <?php else: ?>
            <div class="services-grid">
            <?php foreach ($servicos_pendentes as $servico): 
                $is_trabalhador = $servico['id_trabalhador'] == $_SESSION['id_acesso'];
                $foto = $is_trabalhador
                    ? (!empty($servico['foto_cliente']) ? $servico['foto_cliente'] : 'image/logoservicore.jpg')
                    : (!empty($servico['foto_trabalhador']) ? $servico['foto_trabalhador'] : 'image/logoservicore.jpg');
                $nome_pessoa = $is_trabalhador ? $servico['nome_cliente'] : $servico['nome_trabalhador'];
                $papel = $is_trabalhador ? 'Cliente' : 'Colaborador';
                $data_fmt = $servico['data_servico'] ? date('d/m/Y', strtotime($servico['data_servico'])) : '-';
            ?>
                <div class="service-card service-card--pending">
                    <div class="service-card__header">
                        <div class="service-card__person">
                            <img class="service-card__avatar" src="<?php echo htmlspecialchars($foto, ENT_QUOTES, 'UTF-8'); ?>" alt="Foto">
                            <div>
                                <div class="service-card__name"><?php echo htmlspecialchars($nome_pessoa, ENT_QUOTES, 'UTF-8'); ?></div>
                                <div class="service-card__role"><?php echo $papel; ?></div>
                            </div>
                        </div>
                        <span class="status-badge status-badge--pending">Pendente</span>
                    </div>

                    <div class="service-card__service">
                        <span class="service-card__icon">🛠️</span>
                        <div class="service-card__service-info">
                            <div class="service-card__service-name"><?php echo htmlspecialchars($servico['funcao'], ENT_QUOTES, 'UTF-8'); ?></div>
                            <div class="service-card__service-meta">R$ <?php echo number_format((float)$servico['valor'], 2, ',', '.'); ?>/hora · <?php echo $data_fmt; ?></div>
                        </div>
                    </div>

                    <div class="service-card__location">
                        <span>📍</span>
                        <span><?php echo htmlspecialchars($servico['endereco'], ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>

                    <div class="service-card__info">
                        <span class="info-badge info-badge--pending">Aguardando resposta do colaborador</span>
                    </div>
                </div>
            <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const tabs = document.querySelectorAll('.services-tab');
    const contents = document.querySelectorAll('.services-content');
    
    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            const targetTab = this.dataset.tab;
            
            tabs.forEach(t => t.classList.remove('is-active'));
            contents.forEach(c => c.classList.remove('is-active'));
            
            this.classList.add('is-active');
            document.querySelector(`[data-content="${targetTab}"]`).classList.add('is-active');
        });
    });
});
</script>
