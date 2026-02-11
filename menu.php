<?php
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    $_SESSION['atualizar'] = "nao";
    include_once __DIR__ . '/conexao.php';
    include_once __DIR__ . '/status.php';
    if (isset($_SESSION['id_acesso'])) {

        $comando_testar = "SELECT * FROM registro WHERE id_registro = '$_SESSION[id_acesso]' LIMIT 1";
        $procure_o_teste = mysqli_query($conn, $comando_testar);
        $resultado5 = mysqli_fetch_assoc($procure_o_teste);
        
        if(isset($resultado5)) {
            if ($resultado5['atualizar'] <> 0) {
                $_SESSION['atualizar'] = "sim";
            }   
        } else {
            $_SESSION['atualizar'] = "sim";
        }
    }

    if (isset($_SESSION['atualizar'])) {
        if ($_SESSION['atualizar'] <> 'nao') {
            $atualizar = "sim";
        } else {
            $atualizar = "nao";
        }
    }

    $isLogged = isset($_SESSION['cpf']);
    $role = $isLogged ? (int)$_SESSION['funcao'] : 0;
    $roleLabels = [
        1 => 'Admin',
        2 => 'Colaborador',
        3 => 'Cliente'
    ];

    $baseUrl = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
    $segments = array_values(array_filter(explode('/', $baseUrl), 'strlen'));
    $lastSegment = end($segments);
    if ($lastSegment === 'colabo' || $lastSegment === 'adm') {
        array_pop($segments);
        $baseUrl = '/' . implode('/', $segments);
    }
    if ($baseUrl === '') {
        $baseUrl = '/';
    }

    $toastScript = $baseUrl === '/' ? '/js/toast.js' : $baseUrl . '/js/toast.js';

    $toastMessage = null;
    $toastType = null;
    if (isset($_SESSION['avisar'])) {
        $toastMessage = $_SESSION['avisar'];
    }
    if (isset($_SESSION['avisar_tipo'])) {
        $toastType = $_SESSION['avisar_tipo'];
    }
    unset($_SESSION['avisar'], $_SESSION['avisar_tipo']);

    if ($toastMessage !== null && $toastType === null) {
        $toastMessageText = strtolower(strip_tags($toastMessage));
        if (preg_match('/sucesso|bem sucedido|bem-sucedido|criada com sucesso|atribuido/', $toastMessageText)) {
            $toastType = 'success';
        } elseif (preg_match('/erro|falha|falhou|invalido|incorreta|restrito|precisa|ocupado/', $toastMessageText)) {
            $toastType = 'error';
        } else {
            $toastType = 'info';
        }
    }

    function render_menu_item($href, $label, $className = '') {
        global $baseUrl;
        $path = $baseUrl === '/' ? '/' . ltrim($href, '/') : $baseUrl . '/' . ltrim($href, '/');
        $safeHref = htmlspecialchars($path, ENT_QUOTES, 'UTF-8');
        $safeLabel = htmlspecialchars($label, ENT_QUOTES, 'UTF-8');
        $safeClass = $className ? " class='".htmlspecialchars($className, ENT_QUOTES, 'UTF-8')."'" : '';
        echo "<li><a href='{$safeHref}' target='_parent'{$safeClass}>{$safeLabel}</a></li>";
    }

    $menuLinks = [
        ['href' => 'index.php', 'label' => 'Home'],
        ['href' => 'sobre.php', 'label' => 'Sobre']
    ];

    $notif_count = 0;
    $pending_count = 0;
    if ($isLogged) {
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM notificacoes WHERE registro_id_registro = ? AND lida = 0");
        $stmt->bind_param("i", $_SESSION['id_acesso']);
        $stmt->execute();
        $res_notif = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        $notif_count = $res_notif ? (int)$res_notif['total'] : 0;

        if ($role === 2) {
            $status_pendente = SERVICO_STATUS_PENDENTE;
            $stmt = $conn->prepare("SELECT COUNT(*) as total FROM servico WHERE id_trabalhador = ? AND ativo = ?");
            $stmt->bind_param("ii", $_SESSION['id_acesso'], $status_pendente);
            $stmt->execute();
            $res_pending = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            $pending_count = $res_pending ? (int)$res_pending['total'] : 0;
        }
    }
    $alert_count = $notif_count + $pending_count;

    if ($isLogged) {
        if ($role === 1) {
            $menuLinks[] = ['href' => 'administrador.php', 'label' => 'Administracao'];
            $menuLinks[] = ['href' => 'chamar.php', 'label' => 'Chamar'];
            $menuLinks[] = ['href' => 'historico.php?qm=1', 'label' => 'Historico'];
        }
        if ($role === 2) {
            $menuLinks[] = ['href' => 'colabo/colaborador.php', 'label' => 'Painel Colaborador'];
            $menuLinks[] = ['href' => 'historico.php?qm=2', 'label' => 'Historico'];
            $menuLinks[] = ['href' => 'pagamento_config.php', 'label' => 'Pagamento'];
        }
        if ($role === 3) {
            $menuLinks[] = ['href' => 'chamar.php', 'label' => 'Chamar'];
            $menuLinks[] = ['href' => 'historico.php?qm=1', 'label' => 'Historico'];
        }
    }
?>
<script src="<?php echo htmlspecialchars($toastScript, ENT_QUOTES, 'UTF-8'); ?>"></script>
<script>
    var basePath = "<?php echo $baseUrl; ?>";
    document.addEventListener('DOMContentLoaded', function () {
        let toggle = document.getElementById('theme-toggle');
        if (!toggle) {
            return;
        }
        toggle.addEventListener('click', function (event) {
            event.preventDefault();
            let target = basePath === '/' ? '/theme_toggle.php' : basePath + '/theme_toggle.php';
            fetch(target, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function () {
                    if (parent && parent.location) {
                        parent.location.reload();
                    } else {
                        location.reload();
                    }
                });
        });
    });
</script>

<?php
    if ($toastMessage !== null) {
        $toastPlain = trim(preg_replace('/\s+/', ' ', strip_tags($toastMessage)));
        $toastPayload = json_encode($toastPlain, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $toastTypePayload = json_encode($toastType, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        echo "<script>document.addEventListener('DOMContentLoaded', function () { if (window.showToast) { showToast({$toastPayload}, {$toastTypePayload}); } });</script>";
    }
?>

<?php
    if (isset($atualizar) && $atualizar == "sim") {
        $sairLink = $baseUrl === '/' ? '/sair.php' : $baseUrl . '/sair.php';
        $sairLinkSafe = htmlspecialchars($sairLink, ENT_QUOTES, 'UTF-8');
        echo "<div class='notice notice--warn'>
            <div><strong>Atualizacao:</strong> precisamos recarregar sua sessao para manter os dados corretos.</div>
            <button type='button' onclick=\"parent.window.location.href='{$sairLinkSafe}';\">Recarregar</button>
        </div>
        <script>
            setTimeout(function () {
                parent.window.location.href = '{$sairLinkSafe}';
            }, 3500);
        </script>";
    }
?>
<nav class='menu'>
    <ul>
        <li class='menu-left'>
            <?php
                $homeLink = $baseUrl === '/' ? '/index.php' : $baseUrl . '/index.php';
                $logoSrc = $baseUrl === '/' ? '/image/logoservicore.jpg' : $baseUrl . '/image/logoservicore.jpg';
            ?>
            <a class='brand' href='<?php echo htmlspecialchars($homeLink, ENT_QUOTES, 'UTF-8'); ?>' target='_parent' id='theme-toggle'>
                <img src='<?php echo htmlspecialchars($logoSrc, ENT_QUOTES, 'UTF-8'); ?>' alt='Uniserv'>
                <span>Uniserv</span>
                <?php
                    if ($isLogged && isset($roleLabels[$role])) {
                        echo "<span class='role-tag'>".htmlspecialchars($roleLabels[$role], ENT_QUOTES, 'UTF-8')."</span>";
                    }
                ?>
            </a>
        </li>
        <li class='menu-center'>
            <div class='menu-links'>
                <?php
                    foreach ($menuLinks as $item) {
                        render_menu_item($item['href'], $item['label']);
                    }

                    if ($isLogged) {
                        $comando_testar2 = "SELECT * FROM servico WHERE ativo>0";
                        $joga_no_banco = mysqli_query($conn, $comando_testar2);
                        while ($linha54 = mysqli_fetch_array($joga_no_banco)) {
                            if ($linha54['registro_id_registro'] == $_SESSION['id_acesso'] || $linha54['id_trabalhador'] == $_SESSION['id_acesso'] && $linha54['ativo'] == 1 ) {
                                render_menu_item('servicos.php', 'Servicos Ativos', 'menu-alert');
                                break;
                            }
                        }
                    }
                ?>
            </div>
        </li>
        <li class='menu-right'>
            <div class='menu-actions'>
                <?php
                    if ($isLogged) {
                        $apelido = htmlspecialchars($_SESSION['apelido'], ENT_QUOTES, 'UTF-8');
                        $notifLink = $baseUrl === '/' ? '/notificacoes.php' : $baseUrl . '/notificacoes.php';
                        $notifBadge = $alert_count > 0 ? "<span class='notif-badge'>{$alert_count}</span>" : "";
                        $notifClass = $alert_count > 0 ? 'notif-link notif-link--pulse' : 'notif-link';
                        $notifLabel = $alert_count > 0 ? "Notificacoes ({$alert_count})" : 'Notificacoes';
                        echo "<a href='".htmlspecialchars($notifLink, ENT_QUOTES, 'UTF-8')."' target='_parent' class='{$notifClass}' aria-label='".htmlspecialchars($notifLabel, ENT_QUOTES, 'UTF-8')."' title='".htmlspecialchars($notifLabel, ENT_QUOTES, 'UTF-8')."'><span class='notif-icon'>&#128276;</span>{$notifBadge}</a>";
                        $perfilLink = $baseUrl === '/' ? '/perfil.php' : $baseUrl . '/perfil.php';
                        echo "<a href='".htmlspecialchars($perfilLink, ENT_QUOTES, 'UTF-8')."' target='_parent' class='menu-profile'>{$apelido}</a>";
                    } else {
                        render_menu_item('cadastro.php', 'Cadastrar');
                        render_menu_item('login.php', 'Entrar');
                    }
                ?>
            </div>
        </li>
    </ul>
</nav>
    </body>