<?php
    if (!isset($conn)) {
        include_once __DIR__ . '/../conexao.php';
    }

    $funcao_q = isset($_GET['funcao_q']) ? trim((string)$_GET['funcao_q']) : '';
    $funcao_categoria = isset($_GET['funcao_categoria']) ? trim((string)$_GET['funcao_categoria']) : '';
    $funcao_page = isset($_GET['funcao_page']) ? max(1, (int)$_GET['funcao_page']) : 1;
    $funcao_limit = 10;
    $funcao_offset = ($funcao_page - 1) * $funcao_limit;

    $nome_like = '%' . $funcao_q . '%';
    $categoria_like = '%' . $funcao_categoria . '%';

    $funcao_total = 0;
    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM funcoes WHERE nome_func LIKE ? AND categoria LIKE ?");
    $stmt->bind_param("ss", $nome_like, $categoria_like);
    $stmt->execute();
    $count_row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $funcao_total = $count_row ? (int)$count_row['total'] : 0;
    $funcao_pages = $funcao_total > 0 ? (int)ceil($funcao_total / $funcao_limit) : 1;
    if ($funcao_page > $funcao_pages) {
        $funcao_page = $funcao_pages;
        $funcao_offset = ($funcao_page - 1) * $funcao_limit;
    }

    $funcoes_admin = [];
    $stmt = $conn->prepare("SELECT f.id_funcoes, f.nome_func, f.categoria, f.valor_base, f.duracao_estimada, f.descricao,
            (SELECT COUNT(*) FROM trabalhador_funcoes tf WHERE tf.funcoes_id_funcoes = f.id_funcoes) AS total_colaboradores,
            (SELECT COUNT(*) FROM servico s WHERE s.funcoes_id_funcoes = f.id_funcoes) AS total_servicos
        FROM funcoes f
        WHERE f.nome_func LIKE ? AND f.categoria LIKE ?
        ORDER BY f.id_funcoes DESC
        LIMIT ? OFFSET ?");
    $stmt->bind_param("ssii", $nome_like, $categoria_like, $funcao_limit, $funcao_offset);
    $stmt->execute();
    $res_funcoes = $stmt->get_result();
    while ($row = $res_funcoes->fetch_assoc()) {
        $funcoes_admin[] = $row;
    }
    $stmt->close();

    $qs_base = $_GET;
?>

<section class="admin-section">
    <div class="admin-section-header">
        <div>
            <div class="admin-section-title">Servicos do Sistema</div>
            <div class="admin-section-desc">Edite ou exclua funcoes cadastradas, como "Lava Rapido".</div>
        </div>
    </div>

    <form method="GET" action="administrador.php" class="admin-search-form" style="margin-bottom: 16px;">
        <?php if (isset($_GET['audit_page'])) { ?>
            <input type="hidden" name="audit_page" value="<?php echo (int)$_GET['audit_page']; ?>">
        <?php } ?>
        <?php if (isset($_GET['servico_cliente'])) { ?>
            <input type="hidden" name="servico_cliente" value="<?php echo htmlspecialchars((string)$_GET['servico_cliente'], ENT_QUOTES, 'UTF-8'); ?>">
        <?php } ?>
        <?php if (isset($_GET['servico_funcao'])) { ?>
            <input type="hidden" name="servico_funcao" value="<?php echo htmlspecialchars((string)$_GET['servico_funcao'], ENT_QUOTES, 'UTF-8'); ?>">
        <?php } ?>
        <?php if (isset($_GET['servico_status'])) { ?>
            <input type="hidden" name="servico_status" value="<?php echo htmlspecialchars((string)$_GET['servico_status'], ENT_QUOTES, 'UTF-8'); ?>">
        <?php } ?>
        <?php if (isset($_GET['servico_page'])) { ?>
            <input type="hidden" name="servico_page" value="<?php echo (int)$_GET['servico_page']; ?>">
        <?php } ?>
        <div class="admin-search-inputs">
            <div class="campo-texto">
                <label for="funcao_q">Nome do servico</label>
                <input type="text" id="funcao_q" name="funcao_q" value="<?php echo htmlspecialchars($funcao_q, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Ex: Lava Rapido">
            </div>
            <div class="campo-texto">
                <label for="funcao_categoria">Categoria</label>
                <input type="text" id="funcao_categoria" name="funcao_categoria" value="<?php echo htmlspecialchars($funcao_categoria, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Ex: Limpeza automotiva">
            </div>
        </div>
        <button type="submit" class="btn btn-primary">Filtrar servicos</button>
    </form>

    <div class="admin-table-wrapper">
        <table class="admin-users-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nome</th>
                    <th>Categoria</th>
                    <th>Valor base</th>
                    <th>Duracao</th>
                    <th>Vinculos</th>
                    <th>Acoes</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($funcoes_admin)) { ?>
                    <tr>
                        <td colspan="7" style="text-align: center;">Nenhum servico encontrado para os filtros informados.</td>
                    </tr>
                <?php } else { ?>
                    <?php foreach ($funcoes_admin as $funcao_item) {
                        $desc = trim((string)$funcao_item['descricao']);
                        $valor_base = $funcao_item['valor_base'] !== null ? number_format((float)$funcao_item['valor_base'], 2, ',', '.') : '-';
                        $duracao = $funcao_item['duracao_estimada'] ? (int)$funcao_item['duracao_estimada'] . ' min' : '-';
                        $vinculos = ((int)$funcao_item['total_colaboradores']) . ' colab. / ' . ((int)$funcao_item['total_servicos']) . ' serv.';
                    ?>
                        <tr>
                            <td data-label="ID"><span class="admin-user-id"><?php echo (int)$funcao_item['id_funcoes']; ?></span></td>
                            <td data-label="Nome">
                                <strong><?php echo htmlspecialchars($funcao_item['nome_func'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                <?php if ($desc !== '') { ?>
                                    <div><small><?php echo htmlspecialchars(strlen($desc) > 90 ? substr($desc, 0, 87) . '...' : $desc, ENT_QUOTES, 'UTF-8'); ?></small></div>
                                <?php } ?>
                            </td>
                            <td data-label="Categoria"><?php echo htmlspecialchars((string)$funcao_item['categoria'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td data-label="Valor base">R$ <?php echo $valor_base; ?></td>
                            <td data-label="Duracao"><?php echo htmlspecialchars($duracao, ENT_QUOTES, 'UTF-8'); ?></td>
                            <td data-label="Vinculos"><?php echo htmlspecialchars($vinculos, ENT_QUOTES, 'UTF-8'); ?></td>
                            <td data-label="Acoes">
                                <div class="admin-row-actions">
                                    <button
                                        type="button"
                                        class="btn btn-small btn-primary admin-edit-funcao"
                                        data-id="<?php echo (int)$funcao_item['id_funcoes']; ?>"
                                        data-nome="<?php echo htmlspecialchars((string)$funcao_item['nome_func'], ENT_QUOTES, 'UTF-8'); ?>"
                                        data-categoria="<?php echo htmlspecialchars((string)$funcao_item['categoria'], ENT_QUOTES, 'UTF-8'); ?>"
                                        data-valor="<?php echo htmlspecialchars((string)$funcao_item['valor_base'], ENT_QUOTES, 'UTF-8'); ?>"
                                        data-duracao="<?php echo htmlspecialchars((string)$funcao_item['duracao_estimada'], ENT_QUOTES, 'UTF-8'); ?>"
                                        data-descricao="<?php echo htmlspecialchars((string)$funcao_item['descricao'], ENT_QUOTES, 'UTF-8'); ?>"
                                    >Editar</button>
                                    <button
                                        type="button"
                                        class="btn btn-small btn-ghost admin-delete-funcao"
                                        data-id="<?php echo (int)$funcao_item['id_funcoes']; ?>"
                                        data-nome="<?php echo htmlspecialchars((string)$funcao_item['nome_func'], ENT_QUOTES, 'UTF-8'); ?>"
                                    >Excluir</button>
                                </div>
                            </td>
                        </tr>
                    <?php } ?>
                <?php } ?>
            </tbody>
        </table>
        <div class="admin-table-footer">
            <span class="admin-result-count">Total filtrado: <strong><?php echo $funcao_total; ?></strong> servico(s)</span>
            <?php if ($funcao_pages > 1) { ?>
                <div class="admin-pagination">
                    <?php if ($funcao_page > 1) {
                        $prev = $qs_base;
                        $prev['funcao_page'] = $funcao_page - 1;
                    ?>
                        <a class="btn btn-ghost btn-small" href="?<?php echo htmlspecialchars(http_build_query($prev), ENT_QUOTES, 'UTF-8'); ?>">Anterior</a>
                    <?php } ?>
                    <span class="admin-page-info">Pagina <?php echo $funcao_page; ?> de <?php echo $funcao_pages; ?></span>
                    <?php if ($funcao_page < $funcao_pages) {
                        $next = $qs_base;
                        $next['funcao_page'] = $funcao_page + 1;
                    ?>
                        <a class="btn btn-primary btn-small" href="?<?php echo htmlspecialchars(http_build_query($next), ENT_QUOTES, 'UTF-8'); ?>">Proxima</a>
                    <?php } ?>
                </div>
            <?php } ?>
        </div>
    </div>
</section>

<form action="adm/processa_deletar.php" method="POST" class="hidden form-card" id="hidden_funcao_delete">
    <div class="title">Excluir Servico do Sistema</div>
    <input type="hidden" name="tipo" value="funcao">
    <div class="campo-texto">
        <label for="id_funcao_delete">ID da funcao</label>
        <input type="text" id="id_funcao_delete" name="id_adm" readonly>
    </div>
    <div class="campo-texto">
        <label for="motivo_exclusao_funcao">Motivo da exclusao</label>
        <input type="text" id="motivo_exclusao_funcao" name="motivo_acao" maxlength="180" placeholder="Ex: servico duplicado">
    </div>
    <button type="submit" class="btn btn-ghost">Confirmar exclusao</button>
</form>

<form action="adm/processa_editar_funcao.php" method="POST" class="hidden form-card" id="hidden_funcao_edit">
    <div class="title">Editar Servico do Sistema</div>
    <input type="hidden" id="edit_funcao_id" name="id_funcao" value="">
    <div class="form-grid">
        <div class="campo-texto full">
            <label for="edit_nome_func">Nome do servico</label>
            <input type="text" id="edit_nome_func" name="nome_func" required>
        </div>
        <div class="campo-texto">
            <label for="edit_categoria_func">Categoria</label>
            <input type="text" id="edit_categoria_func" name="categoria" required>
        </div>
        <div class="campo-texto">
            <label for="edit_valor_base_func">Valor base (R$)</label>
            <input type="number" id="edit_valor_base_func" name="valor_base" step="0.01" min="0">
        </div>
        <div class="campo-texto">
            <label for="edit_duracao_func">Duracao estimada (min)</label>
            <input type="number" id="edit_duracao_func" name="duracao_estimada" min="1">
        </div>
        <div class="campo-texto full">
            <label for="edit_descricao_func">Descricao</label>
            <input type="text" id="edit_descricao_func" name="descricao" required>
        </div>
        <div class="campo-texto full">
            <label for="edit_motivo_func">Motivo da alteracao</label>
            <input type="text" id="edit_motivo_func" name="motivo_acao" maxlength="180" placeholder="Ex: ajuste de preco">
        </div>
    </div>
    <button type="submit" class="btn btn-primary">Salvar alteracoes</button>
</form>

<script>
    document.addEventListener('click', function (event) {
        var editBtn = event.target.closest('.admin-edit-funcao');
        if (editBtn) {
            var formEdit = document.getElementById('hidden_funcao_edit');
            if (!formEdit) {
                return;
            }
            document.getElementById('edit_funcao_id').value = editBtn.getAttribute('data-id') || '';
            document.getElementById('edit_nome_func').value = editBtn.getAttribute('data-nome') || '';
            document.getElementById('edit_categoria_func').value = editBtn.getAttribute('data-categoria') || '';
            document.getElementById('edit_valor_base_func').value = editBtn.getAttribute('data-valor') || '';
            document.getElementById('edit_duracao_func').value = editBtn.getAttribute('data-duracao') || '';
            document.getElementById('edit_descricao_func').value = editBtn.getAttribute('data-descricao') || '';
            document.getElementById('edit_motivo_func').value = '';
            formEdit.classList.add('is-open');
            return;
        }

        var deleteBtn = event.target.closest('.admin-delete-funcao');
        if (deleteBtn) {
            var formDelete = document.getElementById('hidden_funcao_delete');
            if (!formDelete) {
                return;
            }
            document.getElementById('id_funcao_delete').value = deleteBtn.getAttribute('data-id') || '';
            document.getElementById('motivo_exclusao_funcao').value = '';
            formDelete.classList.add('is-open');
            return;
        }

        var modalEdit = document.getElementById('hidden_funcao_edit');
        var modalDelete = document.getElementById('hidden_funcao_delete');
        if (modalEdit && event.target === modalEdit) {
            modalEdit.classList.remove('is-open');
        }
        if (modalDelete && event.target === modalDelete) {
            modalDelete.classList.remove('is-open');
        }
    });
</script>
