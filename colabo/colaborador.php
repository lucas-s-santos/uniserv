<?php
    session_start();
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
    include_once("../conexao.php");
    include_once ("../audit.php");
    include_once ("../status.php");
    $theme = isset($_SESSION['theme']) ? $_SESSION['theme'] : 'dark';
    $themeClass = $theme === 'light' ? 'theme-light' : 'theme-dark';
    $stmt = $conn->prepare("SELECT pix_chave, aceita_pix, aceita_dinheiro, aceita_cartao_presencial FROM registro WHERE id_registro = ? LIMIT 1");
    $stmt->bind_param("i", $_SESSION['id_acesso']);
    $stmt->execute();
    $pix_row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $pix_chave_atual = isset($pix_row['pix_chave']) ? trim($pix_row['pix_chave']) : '';
    $aceita_pix = isset($pix_row['aceita_pix']) ? (int)$pix_row['aceita_pix'] === 1 : true;
    $aceita_dinheiro = isset($pix_row['aceita_dinheiro']) ? (int)$pix_row['aceita_dinheiro'] === 1 : false;
    $aceita_cartao_presencial = isset($pix_row['aceita_cartao_presencial']) ? (int)$pix_row['aceita_cartao_presencial'] === 1 : false;

    if (isset($_POST['atualizar_disponivel'])) {
        $numero_servico = $_POST['atualizar_disponivel'];
        $jogue_no_banco = "SELECT * FROM trabalhador_funcoes WHERE id_trafun = '$_POST[atualizar_disponivel]'";
        $resultado_e = mysqli_query($conn, $jogue_no_banco);
        $linha45 = mysqli_fetch_array($resultado_e);
        if ($linha45['disponivel'] == "1") {
            $comando_final = "UPDATE trabalhador_funcoes SET disponivel='0' WHERE id_trafun='$linha45[id_trafun]'";
        } else {
            $comando_final = "UPDATE trabalhador_funcoes SET disponivel='1' WHERE id_trafun='$linha45[id_trafun]'";
        }
        mysqli_query($conn, $comando_final);
        $_SESSION['avisar'] = "Disponibilidade atualizada com sucesso.";
        $_SESSION['avisar_tipo'] = "success";
        $redirectTo = strtok($_SERVER['REQUEST_URI'], '?');
        $query = $_SERVER['QUERY_STRING'];
        if (!empty($query)) {
            $redirectTo .= '?' . $query;
        }
        header("Location: {$redirectTo}");
        exit;
    }

    include_once ("../all.php");

?>

<!DOCTYPE html>
<html lang="pt-br">
    <head>
        <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0"> 
            <meta name="author" content="Gabriel Nepomuceno de Almeida dos Santos">
            <meta name="keywords" content="HTML, CSS">
            <meta name="description" content="Pagina inicial do Serviços Relâmpagos">
            <link rel="stylesheet" href="../css/estrutura_geral.css">

            <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

            <script>
                function showFormNotice(message) {
                    if (window.showToast) {
                        showToast(message, "warn");
                    }
                }

                $("#id_p3").mask("0000000000");

                function calma() {
                    showFormNotice('Funcao temporariamente indisponivel.');
                }

                function testeCandidatar() {
                    let formCADS = document.getElementById("newclass");
                    let campoValor = formCADS.valor_hora.value,
                    campoFuncao = formCADS.funcao_servico;
                    for (var i=0; i < campoFuncao.length; i++) {
                        if (campoFuncao[i].selected && campoFuncao[i].value == "") {
                            showFormNotice("Selecione um servico.");
                            return false;
                        }
                        if (campoFuncao[i].selected) {
                            var salvarFuncao = campoFuncao[i].value;
                        }    
                    }
                    <?php
                        $analise_funcoes= "SELECT * FROM trabalhador_funcoes";
                        $analise_geral = mysqli_query($conn, $analise_funcoes);
                        while ($linha12 = mysqli_fetch_array($analise_geral)) {
                            echo "if ('$_SESSION[id_acesso]' == '$linha12[registro_id_registro]') {
                                        if (salvarFuncao == '$linha12[funcoes_id_funcoes]') { showFormNotice('Voce ja se candidatou a esse servico.'); return false; }
                                  }";
                        }
                    ?>
                    let arquivo = formCADS.certificado && formCADS.certificado.files[0] ? formCADS.certificado.files[0] : null;
                    if (arquivo) {
                        let tamanhoMax = 2 * 1024 * 1024;
                        if (arquivo.size > tamanhoMax) {
                            showFormNotice('Certificado acima de 2MB.');
                            return false;
                        }
                    }
                }

                function onOffAtualizar(Y) {
                    let editar = document.getElementById(Y);
                    
                    if (editar.style.background == "red") {
                        editar.style.background = "green";
                    } else {
                        editar.style.background = "red";
                    }
                }

            </script>
            <title>Página principal</title>
    </head>
    
    <body class="centralizar <?php echo $themeClass; ?>">
        <?php include '../menu.php'; ?>
        <div class="menu-spacer"></div>

        <?php if ((!$aceita_pix || $pix_chave_atual === '') && !$aceita_dinheiro && !$aceita_cartao_presencial) { ?>
            <div class="notice notice--warn" style="margin: 0 auto 16px; max-width: 1200px;">
                Voce ainda nao configurou uma forma de pagamento. Atualize em Pagamento.
                <a class="btn btn-ghost btn-small" href="../pagamento_config.php">Atualizar PIX</a>
            </div>
        <?php } ?>

        <?php
            $mes_selecionado = isset($_GET['mes']) ? (int)$_GET['mes'] : (int)date('m');
            $ano_selecionado = isset($_GET['ano']) ? (int)$_GET['ano'] : (int)date('Y');
            if ($mes_selecionado < 1 || $mes_selecionado > 12) {
                $mes_selecionado = (int)date('m');
            }
            if ($ano_selecionado < 2020 || $ano_selecionado > (int)date('Y')) {
                $ano_selecionado = (int)date('Y');
            }

            $status_finalizado = SERVICO_STATUS_FINALIZADO;
            $status_pendente = SERVICO_STATUS_PENDENTE;
            $status_ativo = SERVICO_STATUS_ATIVO;
            $status_aguardando_pagamento = SERVICO_STATUS_AGUARDANDO_PAGAMENTO;

            $stmt = $conn->prepare("SELECT IFNULL(SUM(valor_atual * (tempo_servico / 60)), 0) as total_bruto,
                COUNT(id_servico) as total_servicos, IFNULL(AVG(avaliacao), 0) as media_avaliacao
                FROM servico WHERE id_trabalhador = ? AND ativo = ?
                AND MONTH(data_2) = ? AND YEAR(data_2) = ?");
            $stmt->bind_param("iiii", $_SESSION['id_acesso'], $status_finalizado, $mes_selecionado, $ano_selecionado);
            $stmt->execute();
            $dashboard = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            $count_pendente = 0;
            $count_ativo = 0;
            $count_finalizado = 0;
            $stmt = $conn->prepare("SELECT ativo, COUNT(*) as total FROM servico WHERE id_trabalhador = ? GROUP BY ativo");
            $stmt->bind_param("i", $_SESSION['id_acesso']);
            $stmt->execute();
            $result_counts = $stmt->get_result();
            while ($row = $result_counts->fetch_assoc()) {
                $status = (int)$row['ativo'];
                $total = (int)$row['total'];
                if ($status === $status_pendente) {
                    $count_pendente = $total;
                } elseif ($status === $status_ativo || $status === $status_aguardando_pagamento) {
                    $count_ativo += $total;
                } elseif ($status === $status_finalizado) {
                    $count_finalizado = $total;
                }
            }
            $stmt->close();

            $total_bruto = (float)$dashboard['total_bruto'];
            $total_liquido = $total_bruto * 0.9;
            $total_servicos = (int)$dashboard['total_servicos'];
            $media_avaliacao = (float)$dashboard['media_avaliacao'];
            $total_pendentes = $count_pendente;
            $metodos = [];
            if ($aceita_pix) { $metodos[] = 'PIX'; }
            if ($aceita_dinheiro) { $metodos[] = 'Dinheiro'; }
            if ($aceita_cartao_presencial) { $metodos[] = 'Cartao presencial'; }
            $metodos_label = $metodos ? implode(' · ', $metodos) : 'Pagamento nao configurado';

            $grafico_meses = [];
            $grafico_valores = [];
            $max_valor = 0.0;
            $base = DateTime::createFromFormat('Y-m-01', sprintf('%04d-%02d-01', $ano_selecionado, $mes_selecionado));
            $stmt = $conn->prepare("SELECT IFNULL(SUM(valor_atual * (tempo_servico / 60)), 0) as total_mes
                FROM servico WHERE id_trabalhador = ? AND ativo = ? AND MONTH(data_2) = ? AND YEAR(data_2) = ?");
            for ($i = 2; $i >= 0; $i--) {
                $data_mes = clone $base;
                $data_mes->modify("-$i month");
                $mes = (int)$data_mes->format('m');
                $ano = (int)$data_mes->format('Y');
                $stmt->bind_param("iiii", $_SESSION['id_acesso'], $status_finalizado, $mes, $ano);
                $stmt->execute();
                $res = $stmt->get_result()->fetch_assoc();
                $total_mes = (float)$res['total_mes'];
                $label = $data_mes->format('m/Y');
                $grafico_meses[] = $label;
                $grafico_valores[] = $total_mes;
                if ($total_mes > $max_valor) {
                    $max_valor = $total_mes;
                }
            }
            $stmt->close();
        ?>

        <section class="collab-hero">
            <div class="collab-hero__main">
                <div class="collab-hero__greeting">
                    <div class="collab-hero__kicker">Painel Colaborador</div>
                    <h1 class="collab-hero__title">Ola, <?php echo htmlspecialchars($_SESSION['apelido'], ENT_QUOTES, 'UTF-8'); ?>!</h1>
                    <p class="collab-hero__text">Acompanhe seu desempenho e gerencie seus chamados em tempo real.</p>
                </div>
                <div class="collab-hero__actions">
                    <button type="button" class="btn btn-primary" onclick="invisibleON('newclass')">Nova funcao</button>
                    <a class="btn btn-ghost" href="../historico.php?qm=2">Historico completo</a>
                </div>
            </div>
            <div class="collab-hero__stats">
                <div class="collab-stat collab-stat--primary">
                    <div class="collab-stat__icon">💰</div>
                    <div class="collab-stat__content">
                        <div class="collab-stat__label">Ganho liquido (mes)</div>
                        <div class="collab-stat__value">R$ <?php echo number_format($total_liquido, 2, ',', '.'); ?></div>
                        <div class="collab-stat__sub">Bruto: R$ <?php echo number_format($total_bruto, 2, ',', '.'); ?></div>
                    </div>
                </div>
                <div class="collab-stat collab-stat--accent">
                    <div class="collab-stat__icon">📋</div>
                    <div class="collab-stat__content">
                        <div class="collab-stat__label">Chamados pendentes</div>
                        <div class="collab-stat__value"><?php echo $count_pendente; ?></div>
                        <div class="collab-stat__sub"><a href="#convites" style="color: inherit;">Ver todos →</a></div>
                    </div>
                </div>
                <div class="collab-stat">
                    <div class="collab-stat__icon">⚡</div>
                    <div class="collab-stat__content">
                        <div class="collab-stat__label">Em andamento</div>
                        <div class="collab-stat__value"><?php echo $count_ativo; ?></div>
                        <div class="collab-stat__sub"><a href="../servicos.php" style="color: inherit;">Gerenciar →</a></div>
                    </div>
                </div>
                <div class="collab-stat">
                    <div class="collab-stat__icon">⭐</div>
                    <div class="collab-stat__content">
                        <div class="collab-stat__label">Avaliacao media</div>
                        <div class="collab-stat__value"><?php echo $media_avaliacao > 0 ? number_format($media_avaliacao, 1, ',', '') : '-'; ?></div>
                        <div class="collab-stat__sub"><?php echo $total_servicos; ?> servicos realizados</div>
                    </div>
                </div>
            </div>
        </section>

        <div class="collab-container">
            <section class="collab-metrics">
                <div class="collab-metrics__header">
                    <div>
                        <div class="section-title">Performance do mes</div>
                        <div class="section-subtitle">Acompanhe seus resultados e metas</div>
                    </div>
                    <form class="dashboard-filter" method="GET">
                        <div class="campo-texto">
                            <label>Mes</label>
                            <select name="mes">
                                <?php
                                    $meses = [
                                        1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Marco', 4 => 'Abril',
                                        5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
                                        9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'
                                    ];
                                    foreach ($meses as $numero => $label) {
                                        $selected = $numero === $mes_selecionado ? ' selected' : '';
                                        echo "<option value='{$numero}'{$selected}>{$label}</option>";
                                    }
                                ?>
                            </select>
                        </div>
                        <div class="campo-texto">
                            <label>Ano</label>
                            <select name="ano">
                                <?php
                                    $ano_atual = (int)date('Y');
                                    for ($ano = $ano_atual; $ano >= $ano_atual - 4; $ano--) {
                                        $selected = $ano === $ano_selecionado ? ' selected' : '';
                                        echo "<option value='{$ano}'{$selected}>{$ano}</option>";
                                    }
                                ?>
                            </select>
                        </div>
                        <button type="submit">Filtrar</button>
                    </form>
                </div>
                <div class="collab-metrics__chart">
                    <div class="dashboard-chart">
                        <div class="dashboard-chart__title">Evolucao dos ganhos - Ultimos 3 meses</div>
                        <canvas id="servicesChart" height="220"></canvas>
                    </div>
                </div>
            </section>

            <aside class="collab-aside">
                <div class="collab-widget">
                    <div class="collab-widget__header">
                        <div class="collab-widget__icon">💳</div>
                        <div class="collab-widget__title">Pagamento</div>
                    </div>
                    <div class="collab-widget__body">
                        <div class="collab-widget__label"><?php echo htmlspecialchars($metodos_label, ENT_QUOTES, 'UTF-8'); ?></div>
                        <p class="collab-widget__text">Configure suas formas de recebimento.</p>
                    </div>
                    <a class="btn btn-small btn-ghost" href="../pagamento_config.php">Configurar</a>
                </div>
                <div class="collab-widget">
                    <div class="collab-widget__header">
                        <div class="collab-widget__icon">📊</div>
                        <div class="collab-widget__title">Resumo rapido</div>
                    </div>
                    <div class="collab-widget__body">
                        <div class="collab-widget__stat">
                            <span>Concluidos</span>
                            <strong><?php echo $count_finalizado; ?></strong>
                        </div>
                        <div class="collab-widget__stat">
                            <span>Total do mes</span>
                            <strong><?php echo $total_servicos; ?></strong>
                        </div>
                    </div>
                </div>
            </aside>
        </div>


        
        <form action='processa_candidatar.php' method='POST' enctype='multipart/form-data' class='hidden_two' id='newclass' style='text-align: left;'>
        <?php
            echo "<input type='hidden' name='acao_cand' value='$_SESSION[id_acesso]'>
            <div class='title'>Candidatar-se</div>
            <label>Qual função você se interessa?</label><br>
            <select name='funcao_servico'>
                <option value='' selected>Disponíveis:";
            $pesquise_funcoes= "SELECT * FROM funcoes";
            $resultado_funcoes = mysqli_query($conn, $pesquise_funcoes);
            while ($linha9 = mysqli_fetch_array($resultado_funcoes)) {
                
                echo "<option value='".$linha9['id_funcoes']."'>".$linha9['nome_func'];
            }     
        ?>    
            </select> <br>
            <label>Certificado(Opcional, coloque foto do original se tiver)</label><br>
            <input type="file" name="certificado" id="certificado" accept="image/*"> <br>
            <div class="texto" style="font-size: 13px;">JPG ou PNG ate 2MB.</div>
            <label>Quanto você cobra por hora(taxa 10%)?</label><br>
            <input type='text' name='valor_hora' id="preco" placeholder='Valor em R$(XXXX)' required> <br>
            <div class='hidden_sub' style='text-align: center'><input type='submit' value='Confirmar' onclick='return testeCandidatar()'> <input type='reset' value='Cancelar' onclick="invisibleON('newclass')"></div>
        </form>

        <section class="collab-section">
            <div class="collab-section__header">
                <div>
                    <div class="section-title">Seus servicos cadastrados</div>
                    <p class="section-subtitle">Gerencie funcoes, valores e disponibilidade</p>
                </div>
                <button type="button" class="btn btn-accent btn-small" onclick="invisibleON('newclass')">Adicionar funcao</button>
            </div>
            <div class="collab-services">

        <?php
            $analise_funcoes2= "SELECT B.nome_func as 'funcao', A.certificado, A.valor_hora, A.disponivel, A.id_trafun
             FROM trabalhador_funcoes A INNER JOIN funcoes B ON A.funcoes_id_funcoes = B.id_funcoes WHERE registro_id_registro = '$_SESSION[id_acesso]'";
            $analise_geral2 = mysqli_query($conn, $analise_funcoes2);
            while ($linha13 = mysqli_fetch_array($analise_geral2)) {
                $certificado_html = 'Sem certificado';
                if (!empty($linha13['certificado'])) {
                    $certificado_safe = htmlspecialchars($linha13['certificado'], ENT_QUOTES, 'UTF-8');
                    $certificado_html = "<img class='cert-thumb' src='{$certificado_safe}' data-full='{$certificado_safe}' alt='Certificado'>";
                }
                $status_label = $linha13['disponivel'] == 1 ? 'Disponivel' : 'Indisponivel';
                $status_class = $linha13['disponivel'] == 1 ? 'collab-service--active' : 'collab-service--inactive';
                $status_badge = $linha13['disponivel'] == 1 ? 'status-badge--active' : 'status-badge';
                echo "<div class='collab-service {$status_class}'>
                    <div class='collab-service__header'>
                        <div class='collab-service__icon'>🛠️</div>
                        <div class='collab-service__main'>
                            <div class='collab-service__title'>".$linha13['funcao']."</div>
                            <div class='collab-service__meta'>
                                <span class='collab-service__price'>R$ ".$linha13['valor_hora']." /hora</span>
                            </div>
                        </div>
                        <span class='status-badge {$status_badge}'>".$status_label."</span>
                    </div>
                    <div class='collab-service__body'>
                        <div class='collab-service__item'>
                            <span>Certificado:</span>
                            {$certificado_html}
                        </div>
                    </div>
                    <form action='#' method='POST' class='collab-service__actions'>
                        <input type='hidden' value='".$linha13['id_trafun']."' name='atualizar_disponivel'>
                        <button type='submit' class='btn btn-small btn-ghost'>Alterar status</button>
                    </form>
                </div>";
            }
        ?>
            </div>
        </section>
        <div class="lightbox" id="lightbox">
            <div class="lightbox__content">
                <img id="lightboxImage" src="" alt="Certificado">
                <button type="button" class="lightbox__close" id="lightboxClose">Fechar</button>
            </div>
        </div>
        <div id="convites"></div>
        <?php include 'convites_update.php'; ?>

        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var ctx = document.getElementById('servicesChart');
                if (!ctx || !window.Chart) {
                    return;
                }
                var labels = <?php echo json_encode($grafico_meses, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;
                var valores = <?php echo json_encode($grafico_valores, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Ganhos (R$)',
                            data: valores,
                            backgroundColor: 'rgba(79, 124, 255, 0.12)',
                            borderColor: 'rgba(79, 124, 255, 0.9)',
                            borderWidth: 3,
                            fill: true,
                            tension: 0.4,
                            pointRadius: 5,
                            pointHoverRadius: 7,
                            pointBackgroundColor: 'rgba(79, 124, 255, 0.9)',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: {
                                    color: 'rgba(255, 255, 255, 0.05)'
                                },
                                ticks: {
                                    callback: function (value) {
                                        return 'R$ ' + value.toFixed(2);
                                    },
                                    color: 'rgba(255, 255, 255, 0.6)'
                                }
                            },
                            x: {
                                grid: {
                                    display: false
                                },
                                ticks: {
                                    color: 'rgba(255, 255, 255, 0.6)'
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                backgroundColor: 'rgba(12, 16, 26, 0.95)',
                                padding: 12,
                                titleColor: '#fff',
                                bodyColor: '#fff',
                                borderColor: 'rgba(79, 124, 255, 0.5)',
                                borderWidth: 1,
                                callbacks: {
                                    label: function (context) {
                                        return 'R$ ' + context.parsed.y.toFixed(2);
                                    }
                                }
                            }
                        }
                    }
                });
            });
        </script>

        <script>
            document.addEventListener('click', function (event) {
                let alvo = event.target;
                if (alvo && alvo.classList.contains('cert-thumb')) {
                    let full = alvo.getAttribute('data-full');
                    let lightbox = document.getElementById('lightbox');
                    let img = document.getElementById('lightboxImage');
                    if (full && lightbox && img) {
                        img.src = full;
                        lightbox.classList.add('is-open');
                    }
                }
            });
            document.getElementById('lightboxClose')?.addEventListener('click', function () {
                let lightbox = document.getElementById('lightbox');
                let img = document.getElementById('lightboxImage');
                if (lightbox && img) {
                    img.src = '';
                    lightbox.classList.remove('is-open');
                }
            });
            document.getElementById('lightbox')?.addEventListener('click', function (event) {
                if (event.target && event.target.id === 'lightbox') {
                    let img = document.getElementById('lightboxImage');
                    event.currentTarget.classList.remove('is-open');
                    if (img) {
                        img.src = '';
                    }
                }
            });
        </script>


        
    </body>
</html>