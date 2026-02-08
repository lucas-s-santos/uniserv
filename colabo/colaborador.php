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

            <script>
                function showFormNotice(message) {
                    let notice = document.getElementById("formNotice");
                    let text = document.getElementById("formNoticeText");
                    if (!notice || !text) {
                        return;
                    }
                    text.textContent = message;
                    notice.style.display = "flex";
                    notice.scrollIntoView({ behavior: "smooth", block: "center" });
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
        <div class="notice notice--warn" id="formNotice" style="display: none;">
            <div id="formNoticeText">Aviso</div>
            <button type="button" onclick="this.parentElement.style.display='none';">Fechar</button>
        </div>

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

            $stmt = $conn->prepare("SELECT IFNULL(SUM(valor_atual * (tempo_servico / 60)), 0) as total_bruto,
                COUNT(id_servico) as total_servicos, IFNULL(AVG(avaliacao), 0) as media_avaliacao
                FROM servico WHERE id_trabalhador = ? AND ativo = ?
                AND MONTH(data_2) = ? AND YEAR(data_2) = ?");
            $stmt->bind_param("iiii", $_SESSION['id_acesso'], $status_finalizado, $mes_selecionado, $ano_selecionado);
            $stmt->execute();
            $dashboard = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            $stmt = $conn->prepare("SELECT COUNT(*) as pendentes FROM servico WHERE id_trabalhador = ? AND ativo = ?");
            $stmt->bind_param("ii", $_SESSION['id_acesso'], $status_pendente);
            $stmt->execute();
            $pendentes = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            $total_bruto = (float)$dashboard['total_bruto'];
            $total_liquido = $total_bruto * 0.9;
            $total_servicos = (int)$dashboard['total_servicos'];
            $media_avaliacao = (float)$dashboard['media_avaliacao'];
            $total_pendentes = (int)$pendentes['pendentes'];

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
                <div class="dashboard-chart__bars">
                    <?php
                        foreach ($grafico_meses as $index => $label) {
                            $valor = $grafico_valores[$index];
                            $altura = $max_valor > 0 ? (int)round(($valor / $max_valor) * 100) : 0;
                            $valor_fmt = number_format($valor, 2, ',', '.');
                            echo "<div class='chart-bar'>
                                <div class='chart-bar__fill' style='height: {$altura}%;'></div>
                                <div class='chart-bar__label'>{$label}</div>
                                <div class='chart-bar__value'>R$ {$valor_fmt}</div>
                            </div>";
                        }
                    ?>
                </div>
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

        <div class="title">Informação</div>
        <div class='botaolist'><a onclick="invisibleON('newclass')">Inscrever se em uma função</a> <a href='../historico.php?qm=2'>Historico de serviços</a> </div>
        <div class="texto">Sua cidade cadastrada define onde voce aparece para clientes. Atualize no perfil se necessario.</div>
        <div class="subtitle">Seus serviços</div>

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
                echo "<div class='caixa'> <form action='#' method='POST'><input  type='hidden' value='".$linha13['id_trafun']."' name='atualizar_disponivel'>
                <input class='off' type='submit' style='width:100px; height: 30px; background: ";
                if ($linha13['disponivel'] == 0) {echo "red";} else {echo "green";}
                echo ";' value='STATUS'>";
                echo "<b>$linha13[funcao]</b> / Valor por hora: $linha13[valor_hora] / Seu certificado: $certificado_html</form></div>";
            }
        ?>
        <div class="lightbox" id="lightbox">
            <div class="lightbox__content">
                <img id="lightboxImage" src="" alt="Certificado">
                <button type="button" class="lightbox__close" id="lightboxClose">Fechar</button>
            </div>
        </div>
        <?php include 'convites_update.php'; ?>

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
        <object data="../pe.html" height="45px" width="100%"></object>
    </footer>
</html>