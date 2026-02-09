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
    include_once ("../all.php");
    include_once ("../audit.php");
    include_once ("../status.php");
    $theme = isset($_SESSION['theme']) ? $_SESSION['theme'] : 'dark';
    $themeClass = $theme === 'light' ? 'theme-light' : 'theme-dark';

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

        <section class="page-header">
            <div>
                <div class="page-kicker">Painel colaborador</div>
                <h1 class="page-title">Acompanhe seus ganhos e chamados</h1>
                <p class="page-subtitle">Gerencie disponibilidade, acompanhe o historico e mantenha seus dados atualizados.</p>
            </div>
            <div class="page-actions">
                <button type="button" class="btn btn-accent" onclick="invisibleON('newclass')">Inscrever em uma funcao</button>
                <a class="btn btn-ghost" href="../historico.php?qm=2">Historico</a>
            </div>
        </section>

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
                } elseif ($status === $status_ativo) {
                    $count_ativo = $total;
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

        <section class="dashboard">
            <div class="dashboard-header">
                <div class="title" style="text-align: left">Resumo do mes</div>
                <form class="dashboard-filter" method="GET">
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
                    <button type="submit">Filtrar</button>
                </form>
            </div>
            <div class="dashboard-cards">
                <div class="dashboard-card">
                    <div class="dashboard-label">Chamados abertos</div>
                    <div class="dashboard-value"><?php echo $count_pendente; ?></div>
                    <div class="dashboard-sub">Aguardando resposta</div>
                </div>
                <div class="dashboard-card">
                    <div class="dashboard-label">Em andamento</div>
                    <div class="dashboard-value"><?php echo $count_ativo; ?></div>
                    <div class="dashboard-sub">Servicos ativos</div>
                </div>
                <div class="dashboard-card">
                    <div class="dashboard-label">Concluidos</div>
                    <div class="dashboard-value"><?php echo $count_finalizado; ?></div>
                    <div class="dashboard-sub">Historico total</div>
                </div>
                <div class="dashboard-card">
                    <div class="dashboard-label">Ganhos do mes (liquido)</div>
                    <div class="dashboard-value">R$ <?php echo number_format($total_liquido, 2, ',', '.'); ?></div>
                    <div class="dashboard-sub">Bruto: R$ <?php echo number_format($total_bruto, 2, ',', '.'); ?></div>
                </div>
                <div class="dashboard-card">
                    <div class="dashboard-label">Servicos realizados</div>
                    <div class="dashboard-value"><?php echo $total_servicos; ?></div>
                    <div class="dashboard-sub">No mes selecionado</div>
                </div>
                <div class="dashboard-card">
                    <div class="dashboard-label">Sua nota</div>
                    <div class="dashboard-value">
                        <?php echo $media_avaliacao > 0 ? number_format($media_avaliacao, 2, ',', '.') : 'Sem avaliacao'; ?>
                    </div>
                    <div class="dashboard-sub">Media do mes</div>
                </div>
                <div class="dashboard-card">
                    <div class="dashboard-label">Chamados pendentes</div>
                    <div class="dashboard-value"><?php echo $total_pendentes; ?></div>
                    <div class="dashboard-sub">Aguardando resposta</div>
                </div>
            </div>
            <div class="dashboard-chart">
                <div class="dashboard-chart__title">Ultimos 3 meses</div>
                <canvas id="servicesChart" height="240"></canvas>
            </div>
        </section>
        
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

        <section class="info-panel">
            <div class="section-title">Informacoes importantes</div>
            <p class="section-subtitle">Sua cidade cadastrada define onde voce aparece para clientes. Atualize no perfil se necessario.</p>
        </section>
        <div class="section-title">Seus servicos</div>

        <section class="service-list">

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
                $status_class = $linha13['disponivel'] == 1 ? 'btn-accent' : 'btn-ghost';
                echo "<div class='service-card'>
                    <div class='service-card__header'>
                        <div>
                            <div class='service-card__title'>".$linha13['funcao']."</div>
                            <div class='service-card__meta'>
                                <span>Valor por hora: ".$linha13['valor_hora']."</span>
                                <span>Certificado: {$certificado_html}</span>
                            </div>
                        </div>
                        <form action='#' method='POST'>
                            <input type='hidden' value='".$linha13['id_trafun']."' name='atualizar_disponivel'>
                            <button type='submit' class='btn btn-small {$status_class}'>".$status_label."</button>
                        </form>
                    </div>
                </div>";
            }
        ?>
        </section>
        <div class="lightbox" id="lightbox">
            <div class="lightbox__content">
                <img id="lightboxImage" src="" alt="Certificado">
                <button type="button" class="lightbox__close" id="lightboxClose">Fechar</button>
            </div>
        </div>
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
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Ganhos (R$)',
                            data: valores,
                            backgroundColor: 'rgba(79, 124, 255, 0.65)',
                            borderColor: 'rgba(79, 124, 255, 0.9)',
                            borderWidth: 1,
                            borderRadius: 8
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: function (value) {
                                        return 'R$ ' + value;
                                    }
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                callbacks: {
                                    label: function (context) {
                                        return 'R$ ' + context.formattedValue;
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

    <footer class="footer">
        <?php include '../pe.html'; ?>
    </footer>
</html>