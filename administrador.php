<?php
    session_start();
    include_once("conexao.php");
    $theme = isset($_SESSION['theme']) ? $_SESSION['theme'] : 'dark';
    $themeClass = $theme === 'light' ? 'theme-light' : 'theme-dark';
    if (isset($_SESSION['funcao'])) {
        if ($_SESSION['funcao'] != '1') {
            $_SESSION['avisar'] = "Ei espera ta faltando algo aqui...<br> ah claro.. <br>SUA PERMISSAO PRA ENTRAR AQUI NÉ";
            header('location: login.php');
            exit;
        }
    } else {
        $_SESSION['avisar'] = "Faça login no site!";
        header('location: login.php');
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
            <!-- <meta http-equiv="refresh" content="15"> -->
            <meta name="keywords" content="HTML, CSS">
            <meta name="description" content="Pagina inicial do Serviços Relâmpagos">
            <link rel="stylesheet" href="css/estrutura_geral.css">
            <title>Página principal</title>
            <script>

                

                function adicionarF() {
                    if (window.showToast) {
                        showToast('Adicionar forcado', 'info');
                    }
                }

                function issoENumero(Y) {
                    let formCHECK = document.getElementById(Y);
                    let campoID = formCHECK.id_adm.value;
                    let teste = isNumber(campoID);
                    if (teste == false) {
                        if (window.showToast) {
                            showToast('Isso nao e um numero!', 'error');
                        }
                        return false;
                    }
                    return teste;
                }

                function confirmacaoID(ID) {
                    let formCHECK = document.getElementById(ID);
                    let campoID = formCHECK.id_adm.value;
                    return confirm('Tem certeza que deseja apagar a pessoa de ID "'+campoID+'"?');
                }

                function testarOEditar() {
                    let formEDITAR = document.getElementById("hidden3");
                    let campoNome = formEDITAR.nome.value,
                        campoEstado = formEDITAR.estado,
                        campoCpf = formEDITAR.cpf.value,
                        campoEmail = formEDITAR.email.value,
                        campoTel = formEDITAR.telefone.value,
                        campoData = formEDITAR.data_ani,
                        campoGen = formEDITAR.genero,
                        campoSenha = formEDITAR.senha.value;
                    if (!testeoCpf(campoCpf)) {
                        if (window.showToast) {
                            showToast('Esse cpf e falso!', 'error');
                        }
                        return false;
                    }
                    if (campoCpf.length != 14) {
                        if (window.showToast) {
                            showToast('Esse cpf esta incompleto', 'error');
                        }
                        return false;
                    }
                    if (campoTel.length != 15 && campoTel != "") {
                        if (window.showToast) {
                            showToast('Esse numero esta incompleto', 'error');
                        }
                        return false;
                    }
                    <?php
                        if (isset($_SESSION['id_adm'])) {
                            $comando_mysql2 = "SELECT * FROM registro WHERE id_registro = '$_SESSION[id_adm]'";
                            $procure2 = mysqli_query($conn, $comando_mysql2);
                            $resultado2 = mysqli_fetch_assoc($procure2);
                        }
                    ?>
                    <?php
                        $pesquise_usuarios= "SELECT * FROM registro";
                        $resultado = mysqli_query($conn, $pesquise_usuarios);
                        if (isset($_SESSION['id_adm'])) {
                            while ($linha = mysqli_fetch_array($resultado)) {
                                echo "if (campoCpf != '$resultado2[cpf]') {if (campoCpf == '$linha[cpf]') {if (window.showToast) {showToast('Esse cpf ja foi cadastrado!', 'error');} return false;}}";

                                echo "if (campoEmail != '$resultado2[email]' && campoEmail != '') {if (campoEmail == '$linha[email]') {if (window.showToast) {showToast('Esse email ja foi cadastrado', 'error');} return false;}}";

                                echo "if (campoTel != '$resultado2[telefone]' && campoTel != '') {if (campoTel == '$linha[telefone]') {if (window.showToast) {showToast('Esse telefone ja foi cadastrado!', 'error');} return false;}}";
                            }
                        }
                    ?>

                    return true;
                }
            </script>


    </head>
    
    <body class="centralizar <?php echo $themeClass; ?>">
        <?php include 'menu.php'; ?>
        <div class="menu-spacer"></div>
        <section class="page-header">
            <div>
                <div class="page-kicker">Administracao</div>
                <h1 class="page-title">Painel do administrador</h1>
                <p class="page-subtitle">Gerencie usuarios, servicos e acompanhe a atividade recente do sistema.</p>
            </div>
            <div class="page-actions">
                <button type="button" class="btn btn-accent" onclick="invisibleON('hidden5')">Adicionar servico</button>
                <a class="btn btn-ghost" href="#admin-filter-panel">Filtrar usuarios</a>
                <a class="btn btn-primary" href="#tabela_cadastros">Ver cadastros</a>
            </div>
        </section>

        <section class="info-panel" id="admin-filter-panel">
            <div class="section-title">Filtro rapido</div>
            <p class="section-subtitle">Use nome ou CPF para reduzir a lista sem abrir o modal.</p>
            <form action="adm/processa_pesquisa.php" method="POST" class="admin-filter">
                <label for="admin-nome">Nome</label>
                <input type="text" name="nome_adm" id="admin-nome" placeholder="Digite o nome">
                <label for="admin-cpf">CPF</label>
                <input type="text" name="cpf_adm" id="admin-cpf" placeholder="Digite o CPF">
                <button type="submit" class="btn btn-primary">Filtrar</button>
            </form>
        </section>

        <?php
            $audit_page = isset($_GET['audit_page']) ? max(1, (int)$_GET['audit_page']) : 1;
            $audit_limit = 5;
            $audit_offset = ($audit_page - 1) * $audit_limit;
            $audit_has_next = false;

            $audit_items = [];
            $stmt = $conn->prepare("SELECT a.acao, a.entidade, a.entidade_id, a.detalhes, a.data_acao, r.nome, r.apelido
                FROM audit_log a
                LEFT JOIN registro r ON r.id_registro = a.registro_id_registro
                ORDER BY a.data_acao DESC
                LIMIT ? OFFSET ?");
            $audit_fetch = $audit_limit + 1;
            $stmt->bind_param("ii", $audit_fetch, $audit_offset);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $audit_items[] = $row;
            }
            $stmt->close();

            if (count($audit_items) > $audit_limit) {
                $audit_has_next = true;
                $audit_items = array_slice($audit_items, 0, $audit_limit);
            }

            $audit_total = 0;
            $total_stmt = $conn->prepare("SELECT COUNT(*) as total FROM audit_log");
            $total_stmt->execute();
            $total_res = $total_stmt->get_result()->fetch_assoc();
            $total_stmt->close();
            $audit_total = $total_res ? (int)$total_res['total'] : 0;
            $audit_pages = $audit_total > 0 ? (int)ceil($audit_total / $audit_limit) : 1;

            function audit_tag_class($acao) {
                $acao = strtolower(trim($acao));
                if (in_array($acao, ['criar', 'login', 'aceitar'], true)) {
                    return 'audit-tag--create';
                }
                if (in_array($acao, ['editar', 'finalizar'], true)) {
                    return 'audit-tag--update';
                }
                if (in_array($acao, ['excluir', 'recusar'], true)) {
                    return 'audit-tag--delete';
                }
                return '';
            }

            function audit_relative_time($datetime) {
                if (!$datetime) {
                    return '';
                }
                $timestamp = strtotime($datetime);
                if (!$timestamp) {
                    return '';
                }
                $diff = time() - $timestamp;
                if ($diff < 60) {
                    return 'agora';
                }
                if ($diff < 3600) {
                    $mins = (int)floor($diff / 60);
                    return "ha {$mins} min";
                }
                if ($diff < 86400) {
                    $hours = (int)floor($diff / 3600);
                    return "ha {$hours} h";
                }
                $days = (int)floor($diff / 86400);
                return "ha {$days} d";
            }
        ?>

        <section class="info-panel">
            <div class="section-title">Atividade recente</div>
            <p class="section-subtitle">Ultimas acoes registradas no sistema.</p>
            <?php if (empty($audit_items)) { ?>
                <div class="collab-empty">Nenhuma atividade registrada ainda.</div>
            <?php } else { ?>
                <table class="audit-table">
                    <thead>
                        <tr>
                            <th>Acao</th>
                            <th>Entidade</th>
                            <th>Usuario</th>
                            <th>Data</th>
                            <th>Detalhes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($audit_items as $item) {
                            $acao = htmlspecialchars($item['acao'], ENT_QUOTES, 'UTF-8');
                            $entidade = htmlspecialchars($item['entidade'], ENT_QUOTES, 'UTF-8');
                            $entidade_id = $item['entidade_id'] !== null ? (int)$item['entidade_id'] : null;
                            $detalhes = $item['detalhes'] ? htmlspecialchars($item['detalhes'], ENT_QUOTES, 'UTF-8') : '-';
                            $nome = $item['apelido'] ?: $item['nome'];
                            $nome = $nome ? htmlspecialchars($nome, ENT_QUOTES, 'UTF-8') : 'Sistema';
                            $data_fmt = $item['data_acao'] ? date('d/m/Y H:i', strtotime($item['data_acao'])) : '';
                            $data_rel = audit_relative_time($item['data_acao']);
                            $tag_class = audit_tag_class($item['acao']);
                            $titulo = $entidade_id !== null ? "{$entidade} #{$entidade_id}" : $entidade;
                            echo "<tr>
                                <td><span class='audit-tag {$tag_class}'>{$acao}</span></td>
                                <td>{$titulo}</td>
                                <td>{$nome}</td>
                                <td>
                                    <div class='audit-date'>{$data_rel}</div>
                                    <div class='audit-date-sub'>{$data_fmt}</div>
                                </td>
                                <td><div class='audit-details' title='{$detalhes}'>{$detalhes}</div></td>
                            </tr>";
                        } ?>
                    </tbody>
                </table>
                <div class="audit-pagination">
                    <span class="audit-page-indicator">Pagina <?php echo $audit_page; ?> de <?php echo $audit_pages; ?></span>
                    <?php if ($audit_page > 1) { ?>
                        <a class="btn btn-ghost btn-small" href="?audit_page=<?php echo $audit_page - 1; ?>">Anterior</a>
                    <?php } ?>
                    <?php if ($audit_has_next) { ?>
                        <a class="btn btn-primary btn-small" href="?audit_page=<?php echo $audit_page + 1; ?>">Proxima</a>
                    <?php } ?>
                </div>
            <?php } ?>
        </section>

        <form action="adm/processa_editar.php" method="POST" class="hidden" id="hidden2">
            <div class="title">Editar</div>
            <input type="hidden" name="acao" value="pesquisar">
            <label>Digite o id da pessoa:</label> <br>
            <input type="text" name="id_adm" id="id_p" placeholder="Necessario para a busca"> <br>
            <div class="hidden_sub" style="text-align: left"><input type="submit" value="Pesquisar" onclick="return issoENumero('hidden2')"></div>
        </form>

        <form action="adm/processa_deletar.php" method="POST" class="hidden" id="hidden4">
            <div class="title">Excluir</div>
            <input type="hidden" name="acao" value="pesquisar">
            <label>Digite o id da pessoa:</label> <br>
            <input type="text" name="id_adm" id="id_p2" placeholder="Necessario para a busca"> <br>
            <div class="hidden_sub" style="text-align: left"><input type="submit" value="Apagar" onclick="return confirmacaoID('hidden4')"></div>
        </form>

        <form action="adm/processa_criar.php" method="POST" class="hidden" id="hidden5">
            <div class="title">Criando novo</div>
            <label>Nome:</label>
            <input type="text" name="nome_func" placeholder="Filtro por nome"> <br>
            <label>Categoria:</label>
            <input type="text" name="categoria" placeholder="Ex: Limpeza, Eletrica"> <br>
            <label>Valor base:</label>
            <input type="text" name="valor_base" placeholder="Valor base em R$"> <br>
            <label>Duração estimada (min):</label>
            <input type="text" name="duracao_estimada" placeholder="Ex: 60"> <br>
            <label>Descrição:</label>
            <input type="text" name="descricao" placeholder="Detalhes do serviço"> <br>
            <label>Imagem:</label>
            <input type="file" name="avatar" accept="image/png, image/jpeg"> <br>
            <div class="hidden_sub"><input type="submit"></div>
        </form>

        <?php
            if (isset($_SESSION['id_adm'])) {
                echo "<form action='adm/processa_editar.php' method='POST' class='hidden is-open' id='hidden3' style='text-align: left;'>
                <input type='hidden' name='acao' value='$_SESSION[id_adm]'>
                <div class='title'>Editar</div>
                <label>Nome:</label>
                <input type='text' name='nome' placeholder='Nome' value='$resultado2[nome]' required> <br>
                <label>Apelido:</label>
                <input type='text' name='apelido' placeholder='Nome' value='$resultado2[apelido]' required> <br>
                <label>Cpf:</label>
                <input type='text' id='cpf2' name='cpf' placeholder='Cpf' value='$resultado2[cpf]' required> <br>
                <label>Estado:</label>
                <select name='estado' id='estado'>
                <option value='$resultado2[estado]' selected>Atual: $resultado2[estado]
                    <option value='Acre'>Acre-AC
                    <option value='Alagoas'>Alagoas-AL
                    <option value='Amapa'>Amapa-AP
                    <option value='Amazonas'>Amazonas-AM
                    <option value='Bahia'>Bahia-BA
                    <option value='Ceara'>Ceara-CE
                    <option value='Distrito federal'>Distrito_federal-DF
                    <option value='Espirito Santo'>Espirito_Santo-ES
                    <option value='Goias'>Goiás - GO
                    <option value='Maranhão'>Maranhão - MA
                    <option value='Mato Grosso'>Mato Grosso – MT
                    <option value='Mato Grosso do Sul'>Mato Grosso do Sul - MS
                    <option value='Minas Gerais'>Minas Gerais - MG
                    <option value='Pará'>Pará - PA
                    <option value='Paraíba'>Paraíba – PB
                    <option value='Paraná'>Paraná - PR
                    <option value='Pernambuco'>Pernambuco - PE
                    <option value='Piauí'>Piauí - PI
                    <option value='Rio de Janeiro'>Rio de Janeiro – RJ
                    <option value='Rio Grande do Norte'>Rio Grande do Norte - RN
                    <option value='Rio Grande do Sul'>Rio Grande do Sul - RS
                    <option value='Rondônia'>Rondônia - RO
                    <option value='Roraima'>Roraima - RR
                    <option value='Santa Catarina'>Santa Catarina - SC
                    <option value='São Paulo'>São Paulo - SP
                    <option value='Sergipe'>Sergipe - SE
                    <option value='Tocantins'>Tocantins - TO
                </select> <br>
                <label>Cidade:</label>
                <input type='text' name='cidade' placeholder='Cidade' value='$resultado2[cidade]' required> <br>
                <label>E-mail:</label>
                <input type='text' name='email' placeholder='E-mail' value='$resultado2[email]'> <br>
                <label>Telefone:</label>
                <input type='text' id='telefone' name='telefone' placeholder='Telefone' value='$resultado2[telefone]'> <br>
                <label>Data de nascimento:</label>
                <input type='text' name='data_ani' placeholder='data_ani' value='$resultado2[data_ani]' required> <br>
                <label>Gênero:</label>
                <select name='genero'> 
                    <option value='$resultado2[sexo]' selected>Atual: $resultado2[sexo]
                    <option value='M'>Masculino
                    <option value='F'>Feminino
                    <option value='O'>Outro
                    <option value='P'>Prefiro não falar
                </select> <br>
                <label>Nova senha (opcional):</label>
                <input type='password' name='senha' placeholder='Defina uma nova senha'> <br>
                <div class='hidden_sub' style='text-align: center'><input type='submit' value='Editar' onclick='return testarOEditar()'></div>    
            </form>";
            unset ($_SESSION['id_adm']);
            }
        ?>
        

        <br><br>
        <div class="section-title">Usuarios cadastrados</div>
        <div class="tabela_adm table-cards" id="tabela_cadastros">
            <table>
                <thead>
                    <tr><td>ID</td><td>NOME COMPLETO</td><td>CPF</td><td>ESTADO</td><td>CIDADE</td>
                        <td>GÊNERO</td><td>CNPJ</td><td>EMAIL</td><td>TELEFONE</td><td>Serviços prestados</td><td>Função</td><td>Acoes</td></tr>
                </thead>
                <tbody id="admin-table-body">
                <?php
                    if (isset($_SESSION['nome_adm'])) {$nome_filtro = $_SESSION['nome_adm'];} else {$nome_filtro = "";}
                    if (isset($_SESSION['cpf_adm'])) {$cpf_filtro = $_SESSION['cpf_adm'];} else {$cpf_filtro = "";}
                    $nome_like = "%".$nome_filtro."%";
                    $cpf_like = "%".$cpf_filtro."%";
                    $stmt = $conn->prepare("SELECT * FROM registro WHERE nome LIKE ? AND cpf LIKE ?");
                    $stmt->bind_param("ss", $nome_like, $cpf_like);
                    $stmt->execute();
                    $resultado = $stmt->get_result();
                    $cont = 0;
                    while ($linha = mysqli_fetch_array($resultado)) {
                        $cont++;
                        echo "<tr><td data-label='ID'>$linha[id_registro]</td><td data-label='Nome'>$linha[nome]</td><td data-label='CPF'>$linha[cpf]</td><td data-label='Estado'>$linha[estado]</td><td data-label='Cidade'>$linha[cidade]</td><td data-label='Genero'>";
                        switch ($linha['sexo']) {case 'M': echo "Masculino"; break;    case 'F': echo "Feminino"; break;
                            case 'P': echo "Não falar"; break;    default: echo "Outro"; break; 
                        }   
                        echo "</td><td data-label='CNPJ'>$linha[cnpj]</td><td data-label='Email'>$linha[email]</td><td data-label='Telefone'>$linha[telefone]</td><td data-label='Servicos prestados'>$linha[servicos_ok]</td><td data-label='Funcao'>";
                        switch ($linha['funcao']) {case '1': echo "Administrador"; break;    case '2': echo "Colaborador"; break;
                            default: echo "Cliente"; break; 
                        }
                                                echo "</td><td data-label='Acoes'>
                                                                <div class='admin-table-actions'>
                                                                        <button type='button' class='btn btn-small btn-ghost admin-edit' data-id='$linha[id_registro]'>Editar</button>
                                                                        <button type='button' class='btn btn-small btn-ghost admin-delete' data-id='$linha[id_registro]'>Excluir</button>
                                                                </div>
                                                            </td></tr>";
                    }
                    $stmt->close();
                    unset ($_SESSION['nome_adm'], $_SESSION['cpf_adm']);
                    echo "<tr><td data-label='ID'>(X)</td><td data-label='Nome'>($cont) Resultados</td></tr>";
                ?>
                </tbody>
            </table>
            <br><br><br><br>
        </div>
            <script>
                let edit2 = document.getElementById('tabela_cadastros');
                <?php 
                    if (isset($_SESSION['exibir_tabela'])) {
                        echo "invisibleON('tabela_cadastros')";
                        unset($_SESSION['exibir_tabela']);
                    }
                ?>

                (function () {
                    var nomeInput = document.getElementById('admin-nome');
                    var cpfInput = document.getElementById('admin-cpf');
                    var tableBody = document.getElementById('admin-table-body');
                    var timer;

                    function abrirEdicao(id) {
                        var form = document.getElementById('hidden2');
                        var input = document.getElementById('id_p');
                        if (!form || !input) {
                            return;
                        }
                        input.value = id;
                        invisibleON('hidden2');
                    }

                    function bindRowActions() {
                        var buttons = document.querySelectorAll('.admin-edit');
                        buttons.forEach(function (btn) {
                            btn.addEventListener('click', function () {
                                abrirEdicao(btn.getAttribute('data-id'));
                            });
                        });

                        var deleteButtons = document.querySelectorAll('.admin-delete');
                        deleteButtons.forEach(function (btn) {
                            btn.addEventListener('click', function () {
                                var form = document.getElementById('hidden4');
                                var input = document.getElementById('id_p2');
                                if (!form || !input) {
                                    return;
                                }
                                input.value = btn.getAttribute('data-id');
                                invisibleON('hidden4');
                            });
                        });
                    }

                    function fetchResults() {
                        if (!tableBody) {
                            return;
                        }
                        var nome = nomeInput ? nomeInput.value.trim() : '';
                        var cpf = cpfInput ? cpfInput.value.trim() : '';
                        var url = 'adm/processa_pesquisa_ajax.php?nome=' + encodeURIComponent(nome) + '&cpf=' + encodeURIComponent(cpf);
                        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                            .then(function (response) { return response.text(); })
                            .then(function (html) {
                                tableBody.innerHTML = html;
                                bindRowActions();
                            });
                    }

                    function scheduleFetch() {
                        clearTimeout(timer);
                        timer = setTimeout(fetchResults, 300);
                    }

                    if (nomeInput) {
                        nomeInput.addEventListener('input', scheduleFetch);
                    }
                    if (cpfInput) {
                        cpfInput.addEventListener('input', scheduleFetch);
                    }
                    bindRowActions();
                })();
            </script>
    </body>

    <footer class="footer">
        <?php include 'pe.html'; ?>
    </footer>

</html>