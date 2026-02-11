<?php
    include_once "includes/bootstrap.php";
    include_once "includes/auth.php";
    $theme = isset($_SESSION['theme']) ? $_SESSION['theme'] : 'dark';
    $themeClass = $theme === 'light' ? 'theme-light' : 'theme-dark';
    require_login('login.php', 'Voce precisa estar logado para acessar esta area.');
    require_role([1, 3], 'login.php', 'Acesso restrito para clientes.');
    if (isset($_GET['funcao'], $_GET['cidade'], $_GET['estado'], $_GET['endereco'])) {
        $_SESSION['funcao_preferida'] = (int)$_GET['funcao'];
        $_SESSION['cidade_preferida'] = trim(strip_tags($_GET['cidade']));
        $_SESSION['estado'] = trim(strip_tags($_GET['estado']));
        $_SESSION['endereco'] = trim(strip_tags($_GET['endereco']));
    }
    if (isset($_GET['ta'])) {
        $id_trafun = (int)$_GET['ta'];
    } else {
        $_SESSION['avisar'] = "Por favor, selecione novamente<br>quem deseja chamar";
        header("Location: index.php");
        exit;
    }
    if (isset($_POST['formnum'])) {
        $data_hj = date('Y/m/d');

        $erro = 'nao';
        $teste_se_ocupado = "SELECT * FROM servico WHERE id_trabalhador = '$_POST[id_chamado]' AND ativo = '1'";
        $jogue_no_banco = mysqli_query($conn, $teste_se_ocupado); $ocupado = 'nao';
        while ($linha12 = mysqli_fetch_array($jogue_no_banco)) {
            $_SESSION['avisar'] = 'Esse colaborador está<br>ocupado em outro serviço';
            $erro = 'sim';
        }
        $stmt = $conn->prepare("SELECT disponivel FROM trabalhador_funcoes WHERE id_trafun = ? LIMIT 1");
        $stmt->bind_param("i", $id_trafun);
        $stmt->execute();
        $ta_on = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($ta_on['disponivel'] == 0) {
            $_SESSION['avisar'] = 'Esse colaborador não<br>está disponível';
            $erro = 'sim';
        }
        if ($_POST['id_chamado'] == $_SESSION['id_acesso']) {
            $_SESSION['avisar'] = 'Kkkk muito engraçado você<br>tentando chamar você mesmo';
            $erro = 'sim';
        }

        if ($erro == 'nao') {
            $status_pendente = SERVICO_STATUS_PENDENTE;
            $etapa_pendente = SERVICO_ETAPA_PENDENTE;
            $comando_chamar = "INSERT INTO servico(registro_id_registro, id_trabalhador, funcoes_id_funcoes, endereco, valor_atual, tempo_servico, avaliacao, ativo, status_etapa, comentario, data_2) 
            VALUES ('$_POST[id_qmchamou]', '$_POST[id_chamado]', '$_POST[funcao]', '$_POST[endereco]', '$_POST[valor_atual]', '0', '0', '$status_pendente', '$etapa_pendente', '', '$data_hj')";
            $joga_no_banco = mysqli_query($conn, $comando_chamar);
                $novo_servico_id = mysqli_insert_id($conn);
                if ($novo_servico_id) {
                    audit_log($conn, 'criar', 'servico', $novo_servico_id, 'Chamado criado');
                    $mensagem = 'Novo chamado de ' . $_SESSION['apelido'];
                    $link = 'colabo/colaborador.php';
                    $stmt = $conn->prepare("INSERT INTO notificacoes (registro_id_registro, mensagem, link) VALUES (?, ?, ?)");
                    $stmt->bind_param("iss", $_POST['id_chamado'], $mensagem, $link);
                    $stmt->execute();
                    $stmt->close();
                }
            unset ($_POST['formnum']);
            header("Location: servicos.php");
        } else {
            header("Location: index.php");
        }
    }
    $analise_servico = "SELECT C.nome 'nome_trabalhador', C.descricao 'descricao', C.data_ani 'data_aniversario', C.sexo 'genero',
    C.pix_tipo 'pix_tipo', C.pix_chave 'pix_chave', C.aceita_pix 'aceita_pix', C.aceita_dinheiro 'aceita_dinheiro',
    C.aceita_cartao_presencial 'aceita_cartao_presencial', C.mensagem_pagamento 'mensagem_pagamento', C.foto 'foto',
    C.latitude 'latitude', C.longitude 'longitude',
    B.nome_func 'funcao', A.valor_hora 'valor_hora', A.registro_id_registro 'id_trabalhador',
    A.certificado 'certificado', A.funcoes_id_funcoes 'id_funcao', A.id_trafun 'id_trafun' FROM trabalhador_funcoes A INNER JOIN
    registro C ON C.id_registro = A.registro_id_registro INNER JOIN funcoes B ON B.id_funcoes = A.funcoes_id_funcoes WHERE id_trafun = ? LIMIT 1";
    $stmt = $conn->prepare($analise_servico);
    $stmt->bind_param("i", $id_trafun);
    $stmt->execute();
    $resultado_trafun = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $comando_pra_contar = "SELECT COUNT(*) as 'conta' FROM servico WHERE id_trabalhador = '$resultado_trafun[id_trabalhador]' AND ativo='0' AND avaliacao>0";
    $joga_no_banco = mysqli_query($conn, $comando_pra_contar);
    $salvar = mysqli_fetch_assoc($joga_no_banco);
    $total_avaliacoes = $salvar['conta'];

    $conta = 0;
    $comando_avaliacoes = "SELECT avaliacao as 'soma' FROM servico 
    WHERE id_trabalhador = '$resultado_trafun[id_trabalhador]' AND ativo='0' AND avaliacao>0 ORDER BY id_servico DESC";
    $joga_no_banco_2 = mysqli_query($conn, $comando_avaliacoes);
    while ($linha23 = mysqli_fetch_assoc($joga_no_banco_2)) {
        $conta = $conta + $linha23['soma'];
    }
    if (!$total_avaliacoes == 0) {
        $media_avaliacoes = $conta / $total_avaliacoes;
        $media_avaliacoes = number_format($media_avaliacoes, 2, '.', '');
    } else {
        $media_avaliacoes = 'Nova pessoa';
    }

    $analise_registro_cliente = "SELECT * FROM registro WHERE id_registro = '$_SESSION[id_acesso]' LIMIT 1";
    $procure = mysqli_query($conn, $analise_registro_cliente);
    $resultado_registro2 = mysqli_fetch_assoc($procure);

    $data_hj = date('Y/m/d');
    $data_new = date($resultado_trafun['data_aniversario']);
    $diferenca = strtotime($data_hj) - strtotime($data_new);
    $total = $diferenca / 60 / 60 / 24 / 365;
    if ($total < 1) {
        $idade = "Menos de 1 ano";
    } else {
        $idade = floor($total)." Anos";
    }
    switch ($resultado_trafun['genero']) {case 'M': $genero = "Masculino"; break; case 'F': $genero = "Feminino"; break;
        case 'P': $genero = "Se optou por não falar"; break; default: $genero = "Outro"; break; 
    }

    $colab_nome = htmlspecialchars($resultado_trafun['nome_trabalhador'], ENT_QUOTES, 'UTF-8');
    $colab_desc = htmlspecialchars($resultado_trafun['descricao'], ENT_QUOTES, 'UTF-8');
    $colab_funcao = htmlspecialchars($resultado_trafun['funcao'], ENT_QUOTES, 'UTF-8');
    $colab_valor = number_format((float)$resultado_trafun['valor_hora'], 2, ',', '.');
    $colab_genero = htmlspecialchars($genero, ENT_QUOTES, 'UTF-8');
    $colab_idade = htmlspecialchars($idade, ENT_QUOTES, 'UTF-8');
    $cliente_nome = htmlspecialchars($resultado_registro2['apelido'], ENT_QUOTES, 'UTF-8');
    $cliente_endereco = htmlspecialchars($_SESSION['endereco'], ENT_QUOTES, 'UTF-8');
    $cliente_cidade = htmlspecialchars($_SESSION['cidade_preferida'], ENT_QUOTES, 'UTF-8');
    $cliente_estado = htmlspecialchars($resultado_registro2['estado'], ENT_QUOTES, 'UTF-8');
    $cliente_foto = !empty($resultado_registro2['foto']) ? $resultado_registro2['foto'] : 'image/logoservicore.jpg';
    $cliente_foto_safe = htmlspecialchars($cliente_foto, ENT_QUOTES, 'UTF-8');
    $pix_tipo = htmlspecialchars((string)$resultado_trafun['pix_tipo'], ENT_QUOTES, 'UTF-8');
    $pix_chave = htmlspecialchars((string)$resultado_trafun['pix_chave'], ENT_QUOTES, 'UTF-8');
    $aceita_pix = (int)$resultado_trafun['aceita_pix'] === 1;
    $aceita_dinheiro = (int)$resultado_trafun['aceita_dinheiro'] === 1;
    $aceita_cartao = (int)$resultado_trafun['aceita_cartao_presencial'] === 1;
    $mensagem_pagamento = trim((string)$resultado_trafun['mensagem_pagamento']);
    $metodos = [];
    if ($aceita_pix) { $metodos[] = 'PIX'; }
    if ($aceita_dinheiro) { $metodos[] = 'Dinheiro'; }
    if ($aceita_cartao) { $metodos[] = 'Cartao presencial'; }
    $metodos_label = $metodos ? implode(' · ', $metodos) : 'Pagamento nao configurado';
    $pix_info = $pix_chave !== '' ? ($pix_tipo ? "$pix_tipo: $pix_chave" : $pix_chave) : 'Chave PIX nao informada';
    $colab_foto = !empty($resultado_trafun['foto']) ? $resultado_trafun['foto'] : 'image/logoservicore.jpg';
    $colab_foto_safe = htmlspecialchars($colab_foto, ENT_QUOTES, 'UTF-8');
    $colab_lat = $resultado_trafun['latitude'] !== null ? (float)$resultado_trafun['latitude'] : null;
    $colab_lng = $resultado_trafun['longitude'] !== null ? (float)$resultado_trafun['longitude'] : null;
    $certificado_path = !empty($resultado_trafun['certificado']) ? $resultado_trafun['certificado'] : '';
    $certificado_safe = htmlspecialchars($certificado_path, ENT_QUOTES, 'UTF-8');
    $is_top = is_numeric($media_avaliacoes) && (float)$media_avaliacoes >= 4.7 && (int)$total_avaliacoes >= 5;
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
            <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
            <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

            <script>
                function alterarPagina (K) {
                    window.location.href = "chamar.php?teste="+K;
                }
            </script>
            <title>Página principal</title>
    </head>
    
    <body class="centralizar <?php echo $themeClass; ?>">
        <?php include 'menu.php'; ?>
        <div class="menu-spacer"></div>
        <main class="page profile-call">
            <section class="profile-hero">
                <div class="profile-card profile-card--accent">
                    <div class="profile-card__header">
                        <div class="profile-identity">
                            <div class="profile-avatar">
                                <img src="<?php echo $colab_foto_safe; ?>" alt="Foto do colaborador">
                                <?php if ($is_top) { ?>
                                    <span class="profile-badge">Top avaliacao</span>
                                <?php } ?>
                            </div>
                            <div>
                                <div class="profile-kicker">Colaborador</div>
                                <div class="profile-title"><?php echo $colab_nome; ?></div>
                                <div class="profile-subtitle"><?php echo $colab_funcao; ?></div>
                            </div>
                        </div>
                        <div class="profile-price">R$ <?php echo $colab_valor; ?>/hora</div>
                    </div>
                    <div class="profile-stats">
                        <div class="profile-stat">
                            <span class="profile-stat__label">Idade</span>
                            <span class="profile-stat__value"><?php echo $colab_idade; ?></span>
                        </div>
                        <div class="profile-stat">
                            <span class="profile-stat__label">Genero</span>
                            <span class="profile-stat__value"><?php echo $colab_genero; ?></span>
                        </div>
                        <div class="profile-stat">
                            <span class="profile-stat__label">Avaliacao</span>
                            <span class="profile-stat__value"><?php echo htmlspecialchars($media_avaliacoes, ENT_QUOTES, 'UTF-8'); ?>/5</span>
                        </div>
                    </div>
                    <div class="profile-description"><?php echo $colab_desc ?: 'Sem descricao cadastrada.'; ?></div>
                    <div class="profile-badges">
                        <span class="chip"><?php echo htmlspecialchars($metodos_label, ENT_QUOTES, 'UTF-8'); ?></span>
                        <?php if ($aceita_pix && $pix_chave !== '') { ?>
                            <span class="chip chip--muted">PIX: <?php echo $pix_info; ?></span>
                        <?php } ?>
                    </div>
                    <?php if ($mensagem_pagamento !== '') { ?>
                        <div class="notice" style="margin-top: 12px;">
                            <?php echo htmlspecialchars($mensagem_pagamento, ENT_QUOTES, 'UTF-8'); ?>
                        </div>
                    <?php } ?>
                    <div class="profile-certificate">
                        <div class="profile-certificate__label">Certificado</div>
                        <?php if ($certificado_path !== '') { ?>
                            <div class="profile-certificate__content">
                                <img class="profile-certificate__thumb" src="<?php echo $certificado_safe; ?>" alt="Certificado do colaborador">
                                <div class="profile-certificate__actions">
                                    <span>Documento enviado pelo colaborador</span>
                                    <a class="btn btn-ghost btn-small" href="<?php echo $certificado_safe; ?>" target="_blank">Abrir</a>
                                </div>
                            </div>
                        <?php } else { ?>
                            <div class="profile-certificate__empty">Sem certificado informado</div>
                        <?php } ?>
                    </div>
                    <div class="profile-comments">
                        <div class="profile-comments__label">Comentarios recentes</div>
                        <select name="enfeite" class="profile-comments__select">
                            <?php
                                $tem_coment = false;
                                $comando_avaliacoes = "SELECT comentario as 'coment' FROM servico WHERE id_trabalhador = '$resultado_trafun[id_trabalhador]'
                                 AND ativo='0' AND avaliacao>0 ORDER BY id_servico DESC LIMIT 20";
                                $joga_no_banco_2 = mysqli_query($conn, $comando_avaliacoes);
                                while ($linha24 = mysqli_fetch_assoc($joga_no_banco_2)) {
                                    $tem_coment = true;
                                    $coment_safe = htmlspecialchars($linha24['coment'], ENT_QUOTES, 'UTF-8');
                                    echo "<option>{$coment_safe}</option>";
                                }
                                if (!$tem_coment) {
                                    echo "<option>Sem comentarios ainda</option>";
                                }
                            ?>
                        </select>
                    </div>
                    <div class="profile-map" id="colabMap" data-lat="<?php echo $colab_lat !== null ? $colab_lat : ''; ?>" data-lng="<?php echo $colab_lng !== null ? $colab_lng : ''; ?>">
                        <div class="profile-map__placeholder">Localizacao aproximada do colaborador.</div>
                    </div>
                </div>

                <div class="profile-card">
                    <div class="profile-card__header">
                        <div class="profile-identity">
                            <div class="profile-avatar profile-avatar--sm">
                                <img src="<?php echo $cliente_foto_safe; ?>" alt="Sua foto">
                            </div>
                            <div>
                                <div class="profile-kicker">Voce</div>
                                <div class="profile-title"><?php echo $cliente_nome; ?></div>
                                <div class="profile-subtitle">Confirme os dados do chamado</div>
                            </div>
                        </div>
                    </div>
                    <div class="profile-list">
                        <div><span>Endereco</span><strong><?php echo $cliente_endereco; ?></strong></div>
                        <div><span>Cidade</span><strong><?php echo $cliente_cidade; ?></strong></div>
                        <div><span>Estado</span><strong><?php echo $cliente_estado; ?></strong></div>
                    </div>
                    <form action="#" method="POST" class="profile-actions">
                        <input type="hidden" name="id_qmchamou" value="<?php echo (int)$_SESSION['id_acesso']; ?>">
                        <input type="hidden" name="id_chamado" value="<?php echo (int)$resultado_trafun['id_trabalhador']; ?>">
                        <input type="hidden" name="endereco" value="<?php echo $cliente_endereco; ?>">
                        <input type="hidden" name="valor_atual" value="<?php echo (float)$resultado_trafun['valor_hora']; ?>">
                        <input type="hidden" name="funcao" value="<?php echo (int)$resultado_trafun['id_funcao']; ?>">
                        <input type="hidden" name="formnum" value="4">
                        <button type="reset" class="btn btn-ghost" onclick="alterarPagina(2)">Mudar endereco</button>
                        <button type="submit" class="btn btn-primary">Chamar agora</button>
                    </form>
                </div>
            </section>
            <div class="mobile-cta">
                <div>
                    <div class="mobile-cta__label">R$ <?php echo $colab_valor; ?>/hora</div>
                    <div class="mobile-cta__title"><?php echo $colab_funcao; ?></div>
                </div>
                <form action="#" method="POST" class="mobile-cta__form">
                    <input type="hidden" name="id_qmchamou" value="<?php echo (int)$_SESSION['id_acesso']; ?>">
                    <input type="hidden" name="id_chamado" value="<?php echo (int)$resultado_trafun['id_trabalhador']; ?>">
                    <input type="hidden" name="endereco" value="<?php echo $cliente_endereco; ?>">
                    <input type="hidden" name="valor_atual" value="<?php echo (float)$resultado_trafun['valor_hora']; ?>">
                    <input type="hidden" name="funcao" value="<?php echo (int)$resultado_trafun['id_funcao']; ?>">
                    <input type="hidden" name="formnum" value="4">
                    <button type="submit" class="btn btn-primary">Chamar</button>
                </form>
            </div>
        </main>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var mapEl = document.getElementById('colabMap');
                if (mapEl && window.L) {
                    var lat = parseFloat(mapEl.dataset.lat);
                    var lng = parseFloat(mapEl.dataset.lng);
                    if (!Number.isNaN(lat) && !Number.isNaN(lng)) {
                        mapEl.innerHTML = '';
                        var map = L.map(mapEl, { scrollWheelZoom: false, zoomControl: false, attributionControl: false });
                        var isLight = document.body.classList.contains('theme-light');
                        var tileUrl = isLight
                            ? 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png'
                            : 'https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png';
                        L.tileLayer(tileUrl, {
                            maxZoom: 18
                        }).addTo(map);
                        map.setView([lat, lng], 13);
                        L.circleMarker([lat, lng], {
                            radius: 7,
                            color: '#1f6feb',
                            fillColor: '#1f6feb',
                            fillOpacity: 0.9
                        }).addTo(map);
                    }
                }

                var cta = document.querySelector('.mobile-cta');
                if (!cta) {
                    return;
                }
                var pulseTimer;
                function triggerCtaPulse() {
                    if (window.innerWidth > 720) {
                        return;
                    }
                    cta.classList.remove('mobile-cta--pulse');
                    void cta.offsetWidth;
                    cta.classList.add('mobile-cta--pulse');
                    clearTimeout(pulseTimer);
                    pulseTimer = setTimeout(function () {
                        cta.classList.remove('mobile-cta--pulse');
                    }, 1200);
                }
                window.addEventListener('scroll', function () {
                    triggerCtaPulse();
                }, { passive: true });
            });
        </script>
    </body>
</html>