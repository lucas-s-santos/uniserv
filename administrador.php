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

            <?php if (isset($_SESSION['sucesso_edicao']) && $_SESSION['sucesso_edicao']) { ?>
                <script>
                    window.addEventListener('load', function() {
                        if (window.showToast) {
                            showToast('<?php echo $_SESSION['msg_edicao'] ?? "Usuário editado com sucesso!"; ?>', 'success');
                        }
                    });
                </script>
                <?php unset($_SESSION['sucesso_edicao']); unset($_SESSION['msg_edicao']); ?>
            <?php } ?>

    </head>
    
    <body class="centralizar <?php echo $themeClass; ?>">
        <?php include 'menu.php'; ?>
        <div class="menu-spacer"></div>
        <main class="page">
        
        <!-- ADMIN HEADER -->
        <section class="admin-header">
            <div>
                <div class="page-kicker">Administração</div>
                <h1 class="page-title">Painel de Controle</h1>
                <p class="page-subtitle">Gerencie usuários, serviços e acompanhe a atividade do sistema em tempo real.</p>
            </div>
        </section>

        <!-- ADMIN STATS -->
        <section class="admin-stats">
            <?php
                $stats_users = $conn->query("SELECT COUNT(*) as total FROM registro")->fetch_assoc()['total'] ?? 0;
                $stats_services = $conn->query("SELECT COUNT(*) as total FROM servico")->fetch_assoc()['total'] ?? 0;
                $stats_collab = $conn->query("SELECT COUNT(*) as total FROM registro WHERE funcao = 2")->fetch_assoc()['total'] ?? 0;
                $stats_active = $conn->query("SELECT COUNT(*) as total FROM servico WHERE ativo > 0")->fetch_assoc()['total'] ?? 0;
            ?>
            <div class="admin-stat-card">
                <div class="admin-stat-icon">👥</div>
                <div class="admin-stat-content">
                    <div class="admin-stat-label">Usuários</div>
                    <div class="admin-stat-value"><?php echo $stats_users; ?></div>
                </div>
            </div>
            <div class="admin-stat-card">
                <div class="admin-stat-icon">⚙️</div>
                <div class="admin-stat-content">
                    <div class="admin-stat-label">Serviços total</div>
                    <div class="admin-stat-value"><?php echo $stats_services; ?></div>
                </div>
            </div>
            <div class="admin-stat-card">
                <div class="admin-stat-icon">🤝</div>
                <div class="admin-stat-content">
                    <div class="admin-stat-label">Colaboradores</div>
                    <div class="admin-stat-value"><?php echo $stats_collab; ?></div>
                </div>
            </div>
            <div class="admin-stat-card">
                <div class="admin-stat-icon">✅</div>
                <div class="admin-stat-content">
                    <div class="admin-stat-label">Serviços ativos</div>
                    <div class="admin-stat-value"><?php echo $stats_active; ?></div>
                </div>
            </div>
        </section>

        <!-- ADMIN ACTION BUTTONS -->
        <section class="admin-actions">
            <button type="button" class="admin-action-btn admin-action-btn--create" onclick="invisibleON('hidden5')">
                <div class="admin-action-icon">➕</div>
                <div class="admin-action-content">
                    <div class="admin-action-title">Novo Serviço</div>
                    <div class="admin-action-desc">Adicione um novo        serviço ao sistema</div>
                </div>
            </button>
            <button type="button" class="admin-action-btn admin-action-btn--edit" onclick="document.getElementById('admin-nome').focus()">
                <div class="admin-action-icon">🔍</div>
                <div class="admin-action-content">
                    <div class="admin-action-title">Filtrar Usuários</div>
                    <div class="admin-action-desc">Encontre usuários rapidamente</div>
                </div>
            </button>
            <button type="button" class="admin-action-btn admin-action-btn--delete" onclick="document.getElementById('tabela_cadastros').scrollIntoView({behavior: 'smooth'})">
                <div class="admin-action-icon">📋</div>
                <div class="admin-action-content">
                    <div class="admin-action-title">Ver Cadastros</div>
                    <div class="admin-action-desc">Lista completa de usuários</div>
                </div>
            </button>
        </section>

        <!-- ACTIVITY SECTION -->
        <section class="admin-section">
            <div class="admin-section-header">
                <div>
                    <div class="admin-section-title">📊 Atividade Recente</div>
                    <div class="admin-section-desc">Acompanhe as ações do sistema em tempo real</div>
                </div>
            </div>
            <?php if (empty($audit_items)) { ?>
                <div class="admin-empty">
                    <div class="admin-empty-icon">📭</div>
                    <div>Nenhuma atividade registrada ainda.</div>
                </div>
            <?php } else { ?>
                <div class="admin-activity-list">
                    <?php foreach (array_slice($audit_items, 0, 10) as $item) {
                        $acao = htmlspecialchars($item['acao'], ENT_QUOTES, 'UTF-8');
                        $entidade = htmlspecialchars($item['entidade'], ENT_QUOTES, 'UTF-8');
                        $nome = $item['apelido'] ?: $item['nome'];
                        $nome = $nome ? htmlspecialchars($nome, ENT_QUOTES, 'UTF-8') : 'Sistema';
                        $data_rel = audit_relative_time($item['data_acao']);
                        $tag_class = audit_tag_class($item['acao']);
                        echo "<div class='admin-activity-item'>
                            <div class='admin-activity-badge {$tag_class}'>{$acao}</div>
                            <div class='admin-activity-main'>
                                <div class='admin-activity-title'>{$entidade}</div>
                                <div class='admin-activity-meta'>Por <strong>{$nome}</strong> • {$data_rel}</div>
                            </div>
                        </div>";
                    } ?>
                </div>
                <?php if ($audit_pages > 1) { ?>
                    <div class="admin-pagination">
                        <?php if ($audit_page > 1) { ?>
                            <a class="btn btn-ghost btn-small" href="?audit_page=<?php echo $audit_page - 1; ?>">← Anterior</a>
                        <?php } ?>
                        <span class="admin-page-info">Página <?php echo $audit_page; ?> de <?php echo $audit_pages; ?></span>
                        <?php if ($audit_has_next) { ?>
                            <a class="btn btn-primary btn-small" href="?audit_page=<?php echo $audit_page + 1; ?>">Próxima →</a>
                        <?php } ?>
                    </div>
                <?php } ?>
            <?php } ?>
        </section>

        <!-- FILTER SECTION -->
        <section class="admin-section">
            <div class="admin-section-header">
                <div>
                    <div class="admin-section-title">🔎 Pesquisa Rápida</div>
                    <div class="admin-section-desc">Encontre usuários por nome ou CPF</div>
                </div>
            </div>
            <form action="adm/processa_pesquisa.php" method="POST" class="admin-search-form">
                <div class="admin-search-inputs">
                    <div class="campo-texto">
                        <label for="admin-nome">Nome</label>
                        <input type="text" name="nome_adm" id="admin-nome" placeholder="Digite o nome">
                    </div>
                    <div class="campo-texto">
                        <label for="admin-cpf">CPF</label>
                        <input type="text" name="cpf_adm" id="admin-cpf" placeholder="Ex: 000.000.000-00">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Pesquisar</button>
            </form>
        </section>

        <!-- USERS TABLE SECTION -->
        <section class="admin-section">
            <div class="admin-section-header">
                <div>
                    <div class="admin-section-title">👥 Usuários Cadastrados</div>
                    <div class="admin-section-desc">Gerencie todos os usuários do sistema</div>
                </div>
            </div>
            <div class="admin-table-wrapper" id="tabela_cadastros">
                <table class="admin-users-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nome</th>
                            <th>CPF</th>
                            <th>Cidade</th>
                            <th>E-mail</th>
                            <th>Função</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody id="admin-table-body">
                    <?php
                        if (isset($_SESSION['nome_adm'])) {$nome_filtro = $_SESSION['nome_adm'];} else {$nome_filtro = "";}
                        if (isset($_SESSION['cpf_adm'])) {$cpf_filtro = $_SESSION['cpf_adm'];} else {$cpf_filtro = "";}
                        $nome_like = "%".$nome_filtro."%";
                        $cpf_like = "%".$cpf_filtro."%";
                        $stmt = $conn->prepare("SELECT id_registro, nome, cpf, cidade, email, funcao FROM registro WHERE nome LIKE ? AND cpf LIKE ?");
                        $stmt->bind_param("ss", $nome_like, $cpf_like);
                        $stmt->execute();
                        $resultado = $stmt->get_result();
                        $cont = 0;
                        while ($linha = mysqli_fetch_array($resultado)) {
                            $cont++;
                            $funcao_label = '';
                            switch ($linha['funcao']) {
                                case '1': $funcao_label = "👑 Admin"; break;
                                case '2': $funcao_label = "🤝 Colaborador"; break;
                                default: $funcao_label = "👤 Cliente";
                            }
                            $email_display = strlen($linha['email']) > 25 ? substr($linha['email'], 0, 22) . '...' : $linha['email'];
                            echo "<tr>
                                <td data-label='ID'><span class='admin-user-id'>{$linha['id_registro']}</span></td>
                                <td data-label='Nome'><strong>{$linha['nome']}</strong></td>
                                <td data-label='CPF'><code>{$linha['cpf']}</code></td>
                                <td data-label='Cidade'>{$linha['cidade']}</td>
                                <td data-label='E-mail'><small>{$email_display}</small></td>
                                <td data-label='Função'><span class='admin-function-badge'>{$funcao_label}</span></td>
                                <td data-label='Ações'>
                                    <div class='admin-row-actions'>
                                        <button type='button' class='btn btn-small btn-primary admin-edit' data-id='{$linha['id_registro']}' title='Editar usuário'>✏️</button>
                                        <button type='button' class='btn btn-small btn-ghost admin-delete' data-id='{$linha['id_registro']}' title='Excluir usuário'>🗑️</button>
                                    </div>
                                </td>
                            </tr>";
                        }
                        $stmt->close();
                        unset ($_SESSION['nome_adm'], $_SESSION['cpf_adm']);
                    ?>
                    </tbody>
                </table>
                <div class="admin-table-footer">
                    <span class="admin-result-count">Total: <strong><?php echo $cont; ?></strong> resultado(s)</span>
                </div>
            </div>
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

        <!-- EDIT FORMS SECTION (HIDDEN) -->
        <form action="adm/processa_editar.php" method="POST" class="hidden form-card" id="hidden2">
            <div class="title">Pesquisar Usuário</div>
            <input type="hidden" name="acao" value="pesquisar">
            <div class="campo-texto">
                <label for="id_p">ID do Usuário</label>
                <input type="text" name="id_adm" id="id_p" placeholder="Digite o ID para pesquisar">
            </div>
            <button type="submit" class="btn btn-primary" onclick="return issoENumero('hidden2')">Pesquisar</button>
        </form>

        <form action="adm/processa_deletar.php" method="POST" class="hidden form-card" id="hidden4">
            <div class="title">Excluir Usuário</div>
            <input type="hidden" name="acao" value="pesquisar">
            <div class="campo-texto">
                <label for="id_p2">ID do Usuário</label>
                <input type="text" name="id_adm" id="id_p2" placeholder="Digite o ID para excluir">
            </div>
            <button type="submit" class="btn btn-ghost" onclick="return confirmacaoID('hidden4')">Excluir</button>
        </form>

        <form action="adm/processa_criar.php" method="POST" class="hidden form-card" id="hidden5" enctype="multipart/form-data">
            <div class="title">Criar Novo Serviço</div>
            <div class="form-grid">
                <div class="campo-texto full">
                    <label for="nome_func">Nome do Serviço</label>
                    <input type="text" id="nome_func" name="nome_func" placeholder="Ex: Limpeza Residencial" required>
                </div>
                <div class="campo-texto">
                    <label for="categoria">Categoria</label>
                    <input type="text" id="categoria" name="categoria" placeholder="Ex: Limpeza" required>
                </div>
                <div class="campo-texto">
                    <label for="valor_base">Valor Base (R$)</label>
                    <input type="number" id="valor_base" name="valor_base" placeholder="0.00" step="0.01" required>
                </div>
                <div class="campo-texto">
                    <label for="duracao_estimada">Duração (minutos)</label>
                    <input type="number" id="duracao_estimada" name="duracao_estimada" placeholder="60" required>
                </div>
                <div class="campo-texto full">
                    <label for="descricao">Descrição</label>
                    <input type="text" id="descricao" name="descricao" placeholder="Detalhes do serviço" required>
                </div>
                <div class="campo-texto full">
                    <label for="avatar">Imagem do Serviço</label>
                    <input type="file" id="avatar" name="avatar" accept="image/png, image/jpeg">
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Criar Serviço</button>
        </form>

        <?php
            if (isset($_SESSION['id_adm'])) {
                echo "<form action='adm/processa_editar.php' method='POST' class='hidden is-open' id='hidden3' style='text-align: left;'>
                <input type='hidden' name='acao' value='$_SESSION[id_adm]'>
                <div class='title'>Editar <button type='button' class='btn-close-form' onclick='fecharEdicao()' style='float: right; cursor: pointer; background: none; border: none; font-size: 24px; padding: 0;'>×</button></div>
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
                <label>Função:</label>
                <select name='funcao' required> 
                    <option value='$resultado2[funcao]' selected>Atual: " . ($resultado2['funcao'] == '1' ? '👑 Administrador' : ($resultado2['funcao'] == '2' ? '🤝 Colaborador' : '👤 Cliente')) . "
                    <option value='1'>👑 Administrador
                    <option value='2'>🤝 Colaborador
                    <option value='3'>👤 Cliente
                </select> <br>
                <label>Nova senha (opcional):</label>
                <input type='password' name='senha' placeholder='Defina uma nova senha'> <br>
                <div class='hidden_sub' style='text-align: center'><input type='submit' value='Editar' onclick='return testarOEditar()'></div>    
            </form>";
            unset ($_SESSION['id_adm']);
            }
        ?>

        </main>
        <script>
            // Fechar o formulário de edição
            function fecharEdicao() {
                const editForm = document.getElementById('hidden3');
                if (editForm) {
                    editForm.classList.remove('is-open');
                }
            }

            // Binding dos botões de editar
            document.addEventListener('click', function(event) {
                if (event.target.classList.contains('admin-edit')) {
                    const userId = event.target.getAttribute('data-id');
                    // Preencher e submeter o formulário hidden2 automaticamente
                    const searchForm = document.getElementById('hidden2');
                    if (searchForm) {
                        searchForm.id_adm.value = userId;
                        // Submeter o formulário automaticamente
                        searchForm.submit();
                    }
                }
            });

            // Binding dos botões de deletar
            document.addEventListener('click', function(event) {
                if (event.target.classList.contains('admin-delete')) {
                    const userId = event.target.getAttribute('data-id');
                    // Preencher o formulário hidden4 com o ID
                    const deleteForm = document.getElementById('hidden4');
                    if (deleteForm) {
                        deleteForm.id_adm.value = userId;
                        // Mostrar o formulário de confirmação
                        deleteForm.classList.add('is-open');
                    }
                }
            });

            // Binding do botão de novo serviço
            document.addEventListener('click', function(event) {
                if (event.target.classList.contains('admin-action-btn--create')) {
                    const createForm = document.getElementById('hidden5');
                    if (createForm) {
                        createForm.classList.add('is-open');
                    }
                }
            });

            // Fechar modais ao clicar fora deles (apenas hidden2, hidden4, hidden5)
            document.addEventListener('click', function(event) {
                const modals = ['hidden2', 'hidden4', 'hidden5'];
                modals.forEach(modalId => {
                    const modal = document.getElementById(modalId);
                    if (modal && event.target === modal) {
                        modal.classList.remove('is-open');
                    }
                });
            });
        </script>
    </body>
</html>