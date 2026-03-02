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

    $servico_cliente = isset($_GET['servico_cliente']) ? trim((string)$_GET['servico_cliente']) : '';
    $servico_funcao = isset($_GET['servico_funcao']) ? trim((string)$_GET['servico_funcao']) : '';
    $servico_status = isset($_GET['servico_status']) ? trim((string)$_GET['servico_status']) : '';
    $servico_page = isset($_GET['servico_page']) ? max(1, (int)$_GET['servico_page']) : 1;
    $servico_limit = 10;
    $servico_offset = ($servico_page - 1) * $servico_limit;
    $servico_status_num = null;
    if ($servico_status === 'pendente') {
        $servico_status_num = SERVICO_STATUS_PENDENTE;
    } elseif ($servico_status === 'andamento') {
        $servico_status_num = SERVICO_STATUS_ATIVO;
    } elseif ($servico_status === 'aguardando_pagamento') {
        $servico_status_num = SERVICO_STATUS_AGUARDANDO_PAGAMENTO;
    } elseif ($servico_status === 'finalizado') {
        $servico_status_num = SERVICO_STATUS_FINALIZADO;
    }

    $servico_cliente_like = '%' . $servico_cliente . '%';
    $servico_funcao_like = '%' . $servico_funcao . '%';

    $servico_total = 0;
    if ($servico_status_num === null) {
        $stmt = $conn->prepare("SELECT COUNT(*) AS total
            FROM servico s
            INNER JOIN registro c ON c.id_registro = s.registro_id_registro
            INNER JOIN funcoes f ON f.id_funcoes = s.funcoes_id_funcoes
            WHERE c.nome LIKE ? AND f.nome_func LIKE ?");
        $stmt->bind_param("ss", $servico_cliente_like, $servico_funcao_like);
    } else {
        $stmt = $conn->prepare("SELECT COUNT(*) AS total
            FROM servico s
            INNER JOIN registro c ON c.id_registro = s.registro_id_registro
            INNER JOIN funcoes f ON f.id_funcoes = s.funcoes_id_funcoes
            WHERE c.nome LIKE ? AND f.nome_func LIKE ? AND s.ativo = ?");
        $stmt->bind_param("ssi", $servico_cliente_like, $servico_funcao_like, $servico_status_num);
    }
    $stmt->execute();
    $servico_count_row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $servico_total = $servico_count_row ? (int)$servico_count_row['total'] : 0;
    $servico_pages = $servico_total > 0 ? (int)ceil($servico_total / $servico_limit) : 1;
    if ($servico_page > $servico_pages) {
        $servico_page = $servico_pages;
        $servico_offset = ($servico_page - 1) * $servico_limit;
    }

    $admin_servicos_recentes = [];
    if ($servico_status_num === null) {
        $stmt = $conn->prepare("SELECT s.id_servico, s.ativo, s.pagamento_status, s.valor_final, s.tempo_servico, s.valor_atual, s.data_2,
                c.nome AS nome_cliente, t.nome AS nome_trabalhador, f.nome_func
            FROM servico s
            INNER JOIN registro c ON c.id_registro = s.registro_id_registro
            INNER JOIN registro t ON t.id_registro = s.id_trabalhador
            INNER JOIN funcoes f ON f.id_funcoes = s.funcoes_id_funcoes
            WHERE c.nome LIKE ? AND f.nome_func LIKE ?
            ORDER BY s.id_servico DESC
            LIMIT ? OFFSET ?");
        $stmt->bind_param("ssii", $servico_cliente_like, $servico_funcao_like, $servico_limit, $servico_offset);
    } else {
        $stmt = $conn->prepare("SELECT s.id_servico, s.ativo, s.pagamento_status, s.valor_final, s.tempo_servico, s.valor_atual, s.data_2,
                c.nome AS nome_cliente, t.nome AS nome_trabalhador, f.nome_func
            FROM servico s
            INNER JOIN registro c ON c.id_registro = s.registro_id_registro
            INNER JOIN registro t ON t.id_registro = s.id_trabalhador
            INNER JOIN funcoes f ON f.id_funcoes = s.funcoes_id_funcoes
            WHERE c.nome LIKE ? AND f.nome_func LIKE ? AND s.ativo = ?
            ORDER BY s.id_servico DESC
            LIMIT ? OFFSET ?");
        $stmt->bind_param("ssiii", $servico_cliente_like, $servico_funcao_like, $servico_status_num, $servico_limit, $servico_offset);
    }
    $stmt->execute();
    $res_servicos = $stmt->get_result();
    while ($row = $res_servicos->fetch_assoc()) {
        $admin_servicos_recentes[] = $row;
    }
    $stmt->close();

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

                function confirmacaoServicoID(ID) {
                    let formCHECK = document.getElementById(ID);
                    let campoID = formCHECK.id_adm.value;
                    return confirm('Tem certeza que deseja apagar o servico de ID "' + campoID + '"? Essa acao nao pode ser desfeita.');
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

        <!-- SERVICES TABLE SECTION -->
        <section class="admin-section">
            <div class="admin-section-header">
                <div>
                    <div class="admin-section-title">Servicos Prestados</div>
                    <div class="admin-section-desc">Acompanhe os ultimos servicos e exclua registros incorretos.</div>
                </div>
            </div>
            <form method="GET" action="administrador.php" class="admin-search-form" style="margin-bottom: 16px;">
                <?php if (isset($_GET['audit_page'])) { ?>
                    <input type="hidden" name="audit_page" value="<?php echo (int)$_GET['audit_page']; ?>">
                <?php } ?>
                <?php if (isset($_GET['funcao_q'])) { ?>
                    <input type="hidden" name="funcao_q" value="<?php echo htmlspecialchars((string)$_GET['funcao_q'], ENT_QUOTES, 'UTF-8'); ?>">
                <?php } ?>
                <?php if (isset($_GET['funcao_categoria'])) { ?>
                    <input type="hidden" name="funcao_categoria" value="<?php echo htmlspecialchars((string)$_GET['funcao_categoria'], ENT_QUOTES, 'UTF-8'); ?>">
                <?php } ?>
                <?php if (isset($_GET['funcao_page'])) { ?>
                    <input type="hidden" name="funcao_page" value="<?php echo (int)$_GET['funcao_page']; ?>">
                <?php } ?>
                <div class="admin-search-inputs">
                    <div class="campo-texto">
                        <label for="servico_cliente">Cliente</label>
                        <input type="text" id="servico_cliente" name="servico_cliente" value="<?php echo htmlspecialchars($servico_cliente, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Nome do cliente">
                    </div>
                    <div class="campo-texto">
                        <label for="servico_funcao">Funcao</label>
                        <input type="text" id="servico_funcao" name="servico_funcao" value="<?php echo htmlspecialchars($servico_funcao, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Ex: Lava Rapido">
                    </div>
                    <div class="campo-texto">
                        <label for="servico_status">Status</label>
                        <select id="servico_status" name="servico_status">
                            <option value="" <?php echo $servico_status === '' ? 'selected' : ''; ?>>Todos</option>
                            <option value="pendente" <?php echo $servico_status === 'pendente' ? 'selected' : ''; ?>>Pendente</option>
                            <option value="andamento" <?php echo $servico_status === 'andamento' ? 'selected' : ''; ?>>Em andamento</option>
                            <option value="aguardando_pagamento" <?php echo $servico_status === 'aguardando_pagamento' ? 'selected' : ''; ?>>Aguardando pagamento</option>
                            <option value="finalizado" <?php echo $servico_status === 'finalizado' ? 'selected' : ''; ?>>Finalizado</option>
                        </select>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Filtrar servicos prestados</button>
            </form>
            <div class="admin-table-wrapper" id="tabela_servicos">
                <table class="admin-users-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Cliente</th>
                            <th>Colaborador</th>
                            <th>Funcao</th>
                            <th>Status</th>
                            <th>Pagamento</th>
                            <th>Valor</th>
                            <th>Data</th>
                            <th>Acoes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($admin_servicos_recentes)) { ?>
                            <tr>
                                <td colspan="9" style="text-align: center;">Nenhum servico encontrado.</td>
                            </tr>
                        <?php } else { ?>
                            <?php foreach ($admin_servicos_recentes as $servico_item) {
                                $status_num = (int)$servico_item['ativo'];
                                $status_label = servico_status_label($status_num);
                                $status_class = 'status-badge ' . servico_status_badge_class($status_num);

                                $pagamento_label = '-';
                                $pagamento_status = isset($servico_item['pagamento_status']) ? (int)$servico_item['pagamento_status'] : 0;
                                if ($pagamento_status === 2) {
                                    $pagamento_label = 'Confirmado';
                                } elseif ($pagamento_status === 1) {
                                    $pagamento_label = 'Comprovante enviado';
                                } elseif ($status_num === SERVICO_STATUS_AGUARDANDO_PAGAMENTO) {
                                    $pagamento_label = 'Pendente';
                                }

                                $valor_final = $servico_item['valor_final'] !== null ? (float)$servico_item['valor_final'] : 0.0;
                                if ($valor_final <= 0 && !empty($servico_item['tempo_servico'])) {
                                    $valor_final = ((float)$servico_item['valor_atual'] * ((float)$servico_item['tempo_servico'] / 60));
                                }
                                $valor_fmt = number_format($valor_final, 2, ',', '.');
                                $data_fmt = !empty($servico_item['data_2']) ? date('d/m/Y', strtotime($servico_item['data_2'])) : '-';
                            ?>
                                <tr>
                                    <td data-label="ID"><span class="admin-user-id"><?php echo (int)$servico_item['id_servico']; ?></span></td>
                                    <td data-label="Cliente"><?php echo htmlspecialchars($servico_item['nome_cliente'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td data-label="Colaborador"><?php echo htmlspecialchars($servico_item['nome_trabalhador'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td data-label="Funcao"><?php echo htmlspecialchars($servico_item['nome_func'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td data-label="Status"><span class="<?php echo $status_class; ?>"><?php echo $status_label; ?></span></td>
                                    <td data-label="Pagamento"><?php echo htmlspecialchars($pagamento_label, ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td data-label="Valor">R$ <?php echo $valor_fmt; ?></td>
                                    <td data-label="Data"><?php echo $data_fmt; ?></td>
                                    <td data-label="Acoes">
                                        <div class="admin-row-actions">
                                            <button type="button" class="btn btn-small btn-ghost admin-delete-service" data-id="<?php echo (int)$servico_item['id_servico']; ?>" title="Excluir servico">Excluir</button>
                                        </div>
                                    </td>
                                </tr>
                            <?php } ?>
                        <?php } ?>
                    </tbody>
                </table>
                <div class="admin-table-footer">
                    <span class="admin-result-count">Total filtrado: <strong><?php echo $servico_total; ?></strong> servico(s)</span>
                    <?php if ($servico_pages > 1) { ?>
                        <div class="admin-pagination">
                            <?php if ($servico_page > 1) {
                                $params_prev = $_GET;
                                $params_prev['servico_page'] = $servico_page - 1;
                            ?>
                                <a class="btn btn-ghost btn-small" href="?<?php echo htmlspecialchars(http_build_query($params_prev), ENT_QUOTES, 'UTF-8'); ?>">Anterior</a>
                            <?php } ?>
                            <span class="admin-page-info">Pagina <?php echo $servico_page; ?> de <?php echo $servico_pages; ?></span>
                            <?php if ($servico_page < $servico_pages) {
                                $params_next = $_GET;
                                $params_next['servico_page'] = $servico_page + 1;
                            ?>
                                <a class="btn btn-primary btn-small" href="?<?php echo htmlspecialchars(http_build_query($params_next), ENT_QUOTES, 'UTF-8'); ?>">Proxima</a>
                            <?php } ?>
                        </div>
                    <?php } ?>
                </div>
            </div>
        </section>

        <?php include_once "includes/admin_funcoes.php"; ?>

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
            <div class="campo-texto">
                <label for="motivo_excluir_usuario">Motivo (opcional)</label>
                <input type="text" name="motivo_acao" id="motivo_excluir_usuario" maxlength="180" placeholder="Ex: cadastro duplicado">
            </div>
            <button type="submit" class="btn btn-ghost" onclick="return confirmacaoID('hidden4')">Excluir</button>
        </form>

        <form action="adm/processa_deletar.php" method="POST" class="hidden form-card" id="hidden6">
            <div class="title">Excluir Servico</div>
            <input type="hidden" name="tipo" value="servico">
            <div class="campo-texto">
                <label for="id_p3">ID do Servico</label>
                <input type="text" name="id_adm" id="id_p3" placeholder="Digite o ID do servico">
            </div>
            <div class="campo-texto">
                <label for="motivo_excluir_servico">Motivo (opcional)</label>
                <input type="text" name="motivo_acao" id="motivo_excluir_servico" maxlength="180" placeholder="Ex: servico invalido">
            </div>
            <button type="submit" class="btn btn-ghost" onclick="return confirmacaoServicoID('hidden6')">Excluir servico</button>
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
                    <label for="motivo_criar_funcao">Motivo da criacao (opcional)</label>
                    <input type="text" id="motivo_criar_funcao" name="motivo_acao" maxlength="180" placeholder="Ex: novo servico solicitado pelos clientes">
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
        <script>
            document.addEventListener('click', function(event) {
                const btn = event.target.closest('.admin-delete-service');
                if (!btn) {
                    return;
                }
                const form = document.getElementById('hidden6');
                if (!form) {
                    return;
                }
                form.id_adm.value = btn.getAttribute('data-id');
                form.classList.add('is-open');
            });

            document.addEventListener('click', function(event) {
                const modal = document.getElementById('hidden6');
                if (modal && event.target === modal) {
                    modal.classList.remove('is-open');
                }
            });
        </script>
    </body>
</html>
