<?php
    session_start();
    include_once 'conexao.php';
    include_once 'status.php';
    $theme = isset($_SESSION['theme']) ? $_SESSION['theme'] : 'dark';
    $themeClass = $theme === 'light' ? 'theme-light' : 'theme-dark';
    if (!isset($_SESSION['cpf'])) {
        $_SESSION['avisar'] = "Voce precisa estar logado para acessar esta area.";
        header('location: login.php');
        exit;
    }
    $qm = isset($_GET['qm']) ? (int)$_GET['qm'] : 0;
    $role = (int)$_SESSION['funcao'];
    if ($qm !== 1 && $qm !== 2) {
        $_SESSION['avisar'] = "Erro, por favor entre no historico novamente!";
        header('location: login.php');
        exit;
    }
    if ($qm === 1 && $role !== 1 && $role !== 3) {
        $_SESSION['avisar'] = "Acesso restrito ao historico de clientes.";
        header('location: login.php');
        exit;
    }
    if ($qm === 2 && $role !== 2) {
        $_SESSION['avisar'] = "Acesso restrito ao historico de colaboradores.";
        header('location: login.php');
        exit;
    }
    if (isset($_POST['id_editar'])) {
        $atualizar_avaliacao = "UPDATE servico SET avaliacao='$_POST[avaliacao]', comentario='$_POST[comentario]' WHERE id_servico='$_POST[id_editar]'";
        mysqli_query($conn, $atualizar_avaliacao);
    }
    include_once 'all.php';
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
                function mudeTexto() {
                        
                        document.getElementById("conta3").textContent = document.getElementById("conta7").value;
                }
            </script>
    </head>
    
    <body class="centralizar <?php echo $themeClass; ?>">
        <?php include 'menu.php'; ?>
        <div class="menu-spacer"></div>
        <main class="page">
        <?php 
            if ($_GET['qm'] == 1) {
                echo "<div class='title'>Serviços recebidos</div>";

                if (isset($_POST['id_servico'])) {
                    $avaliar = "SELECT A.nome 'nome_colaborador', B.nome_func 'funcao', C.valor_atual 'valor_hora', C.tempo_servico 'tempo_min', C.avaliacao 'avaliacao', 
                        C.comentario 'comentario', C.data_2 'data_2', C.id_servico FROM servico C INNER JOIN registro A ON A.id_registro = C.id_trabalhador
                        INNER JOIN funcoes B ON B.id_funcoes = C.funcoes_id_funcoes WHERE id_servico = '$_POST[id_servico]' LIMIT 1";
                    $jogue_no_banco = mysqli_query($conn, $avaliar);
                    $salvar2 = mysqli_fetch_array($jogue_no_banco);
                    $resultado_conta = round(($salvar2['valor_hora']/60) * $salvar2['tempo_min']);
                    if ($salvar2['avaliacao'] == 0) {$salvar2['avaliacao'] = 5;}
                    echo "<form action='#' method='POST' class='hidden' id='hidden99'>
                        <div class='title'>Avaliação</div>
                        <label>Colaborador: $salvar2[nome_colaborador].</label>  <label>Valor por Hora: $salvar2[valor_hora].</label> <br>
                        <label>Duração: $salvar2[tempo_min] Minutos. Total: R$ $resultado_conta</label> <br>
                        <label>Função: $salvar2[funcao]</label><br>
                        <input type='hidden' value='$salvar2[id_servico]' name='id_editar'>
                        <input type='range' value='$salvar2[avaliacao]' min='1' max='5' id='conta7' name='avaliacao' onchange='mudeTexto()'><br>
                        <label>Avaliação: <b id='conta3'>$salvar2[avaliacao]</b>/5 Estrelas</label><br><br>
                        <label>Se quiser digite um comentário</label><br>
                        <input type='text' name='comentario' style='width: 300px;' value='$salvar2[comentario]' placeholder='Comentário é publico mas quem postou não'> <br>
                        <div class='hidden_sub'><input type='submit' value='Confirmar'><input type='reset' value='Cancelar' onclick='invisibleON("; echo '"hidden99"'; echo ")'></div>
                    </form>";
                    echo "<script>invisibleON('hidden99');</script>";
                } 
                $status_finalizado = SERVICO_STATUS_FINALIZADO;
                $quantidade = "SELECT COUNT(*) as 'conta' FROM servico WHERE registro_id_registro = '$_SESSION[id_acesso]' AND ativo='$status_finalizado'";
                $jogue_no_banco = mysqli_query($conn, $quantidade);
                $salvar = mysqli_fetch_array($jogue_no_banco);
                $cont = $salvar['conta'];
                $historico = "SELECT A.nome 'nome_colaborador', A.foto 'foto_colaborador', B.nome_func 'funcao', C.valor_atual 'valor_hora', C.tempo_servico 'tempo_min',
                C.avaliacao 'avaliacao', C.ativo 'ativo', C.comentario 'comentario', C.data_2 'data_2', C.id_servico, C.foto_antes, C.foto_depois
                FROM servico C INNER JOIN registro A ON A.id_registro = C.id_trabalhador
                INNER JOIN funcoes B ON B.id_funcoes = C.funcoes_id_funcoes WHERE registro_id_registro = '$_SESSION[id_acesso]' AND ativo='$status_finalizado' ORDER BY id_servico DESC";
                $jogue_no_banco = mysqli_query($conn, $historico);
                echo "<div class='service-list'>";
                while($linha = mysqli_fetch_array($jogue_no_banco)) {
                    $data_a = date('d/m/Y',  strtotime($linha['data_2']));
                    $resultado_conta = round(($linha['valor_hora']/60) * $linha['tempo_min']);
                    $foto_colab = !empty($linha['foto_colaborador']) ? $linha['foto_colaborador'] : 'image/logoservicore.jpg';
                    $foto_colab_safe = htmlspecialchars($foto_colab, ENT_QUOTES, 'UTF-8');
                    $foto_antes = !empty($linha['foto_antes']) ? $linha['foto_antes'] : '';
                    $foto_depois = !empty($linha['foto_depois']) ? $linha['foto_depois'] : '';
                    $media_items = '';
                    if ($foto_antes !== '') {
                        $foto_antes_safe = htmlspecialchars($foto_antes, ENT_QUOTES, 'UTF-8');
                        $media_items .= "<div class='media-tile'><span class='media-label'>Antes</span><a href='{$foto_antes_safe}' target='_blank'><img src='{$foto_antes_safe}' alt='Foto antes'></a></div>";
                    }
                    if ($foto_depois !== '') {
                        $foto_depois_safe = htmlspecialchars($foto_depois, ENT_QUOTES, 'UTF-8');
                        $media_items .= "<div class='media-tile'><span class='media-label'>Depois</span><a href='{$foto_depois_safe}' target='_blank'><img src='{$foto_depois_safe}' alt='Foto depois'></a></div>";
                    }
                    $media_html = $media_items !== '' ? "<div class='service-card__media'><div class='media-grid'>{$media_items}</div></div>" : '';
                    echo "<div class='service-card'>
                            <div class='service-card__header'>
                                <div class='service-card__title'>Registro Nº$cont</div>
                                <span class='status-badge status-badge--done'>Finalizado</span>
                            </div>
                            <div class='service-card__person-line'>
                                <img class='service-card__avatar' src='{$foto_colab_safe}' alt='Foto do colaborador'>
                                <div class='service-card__person-info'>
                                    <span>Colaborador</span>
                                    <strong>$linha[nome_colaborador]</strong>
                                </div>
                            </div>
                            <div class='service-card__meta'>
                                <span>Data: $data_a</span>
                                <span>Colaborador: $linha[nome_colaborador]</span>
                                <span>Serviço: $linha[funcao]</span>
                                <span>Total: R$ $resultado_conta</span>
                            </div>";
                    echo $media_html;
                    if ($linha['avaliacao'] == 0) {
                        echo "<form action='#' method='POST'>
                                <input type='hidden' name='id_servico' value='$linha[id_servico]'>
                                <div class='button-group'><input type='submit' value='Avaliar'></div>
                              </form>
                          </div>";
                    } else {
                        echo "<div class='service-card__meta'>
                                <span>Avaliação: $linha[avaliacao]</span>
                                <span>Comentário: $linha[comentario]</span>
                              </div>
                              <form action='#' method='POST'>
                                <input type='hidden' name='id_servico' value='$linha[id_servico]'>
                                <div class='button-group'><input type='submit' value='Editar avaliação'></div>
                              </form>
                          </div>";
                    }
                        $cont = $cont-1;
                }
                echo "</div>";

            } else {
                echo "<div class='title'>Serviços prestados</div>";
                $status_finalizado = SERVICO_STATUS_FINALIZADO;
                $quantidade = "SELECT COUNT(*) as 'conta' FROM servico WHERE id_trabalhador = '$_SESSION[id_acesso]' AND ativo='$status_finalizado'";
                $jogue_no_banco = mysqli_query($conn, $quantidade);
                $salvar = mysqli_fetch_array($jogue_no_banco);
                $cont = $salvar['conta'];
                $historico = "SELECT A.nome 'nome_colaborador', B.nome_func 'funcao', C.valor_atual 'valor_hora', C.tempo_servico 'tempo_min', C.avaliacao 'avaliacao', C.ativo 'ativo',
                C.comentario 'comentario', C.data_2 'data_2', C.id_servico, C.foto_antes, C.foto_depois, D.nome 'nome_cliente', D.foto 'foto_cliente'
                FROM servico C INNER JOIN registro A ON A.id_registro = C.id_trabalhador
                INNER JOIN registro D ON D.id_registro = C.registro_id_registro
                INNER JOIN funcoes B ON B.id_funcoes = C.funcoes_id_funcoes WHERE id_trabalhador = '$_SESSION[id_acesso]' AND ativo='$status_finalizado' ORDER BY id_servico DESC";
                $jogue_no_banco = mysqli_query($conn, $historico);
                echo "<div class='service-list'>";
                while($linha = mysqli_fetch_array($jogue_no_banco)) {
                    $data_a = date('d/m/Y',  strtotime($linha['data_2']));
                    $resultado_conta = round(($linha['valor_hora']/60) * $linha['tempo_min']);
                    $foto_cliente = !empty($linha['foto_cliente']) ? $linha['foto_cliente'] : 'image/logoservicore.jpg';
                    $foto_cliente_safe = htmlspecialchars($foto_cliente, ENT_QUOTES, 'UTF-8');
                    $foto_antes = !empty($linha['foto_antes']) ? $linha['foto_antes'] : '';
                    $foto_depois = !empty($linha['foto_depois']) ? $linha['foto_depois'] : '';
                    $media_items = '';
                    if ($foto_antes !== '') {
                        $foto_antes_safe = htmlspecialchars($foto_antes, ENT_QUOTES, 'UTF-8');
                        $media_items .= "<div class='media-tile'><span class='media-label'>Antes</span><a href='{$foto_antes_safe}' target='_blank'><img src='{$foto_antes_safe}' alt='Foto antes'></a></div>";
                    }
                    if ($foto_depois !== '') {
                        $foto_depois_safe = htmlspecialchars($foto_depois, ENT_QUOTES, 'UTF-8');
                        $media_items .= "<div class='media-tile'><span class='media-label'>Depois</span><a href='{$foto_depois_safe}' target='_blank'><img src='{$foto_depois_safe}' alt='Foto depois'></a></div>";
                    }
                    $media_html = $media_items !== '' ? "<div class='service-card__media'><div class='media-grid'>{$media_items}</div></div>" : '';
                    echo "<div class='service-card'>
                            <div class='service-card__header'>
                                <div class='service-card__title'>Registro Nº$cont</div>
                                <span class='status-badge status-badge--done'>Finalizado</span>
                            </div>
                            <div class='service-card__person-line'>
                                <img class='service-card__avatar' src='{$foto_cliente_safe}' alt='Foto do cliente'>
                                <div class='service-card__person-info'>
                                    <span>Cliente</span>
                                    <strong>$linha[nome_cliente]</strong>
                                </div>
                            </div>
                            <div class='service-card__meta'>
                                <span>Data: $data_a</span>
                                <span>Colaborador: $linha[nome_colaborador]</span>
                                <span>Serviço: $linha[funcao]</span>
                                <span>Total: R$ $resultado_conta</span>
                            </div>";
                    echo $media_html;
                    if ($linha['avaliacao'] == 0) {
                        echo "<div class='service-card__meta'><span>Avaliação: Não foi avaliado</span></div></div>";
                    } else {
                        echo "<div class='service-card__meta'><span>Avaliação: $linha[avaliacao] Estrelas</span><span>Comentário: $linha[comentario]</span></div></div>";
                    }
                        $cont = $cont-1;
                }
                echo "</div>";

            }
        ?>
        <div style="width: 100%; height: 45px"></div>
        </main>
    </body>
</html>