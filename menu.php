<?php
    session_start();
    $_SESSION['atualizar'] = "nao";
    include_once 'conexao.php';
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
    $theme = isset($_SESSION['theme']) ? $_SESSION['theme'] : 'dark';
    $themeClass = $theme === 'light' ? 'theme-light' : 'theme-dark';
    $roleLabels = [
        1 => 'Admin',
        2 => 'Colaborador',
        3 => 'Cliente'
    ];

    function render_menu_item($href, $label, $className = '') {
        $safeHref = htmlspecialchars($href, ENT_QUOTES, 'UTF-8');
        $safeLabel = htmlspecialchars($label, ENT_QUOTES, 'UTF-8');
        $safeClass = $className ? " class='".htmlspecialchars($className, ENT_QUOTES, 'UTF-8')."'" : '';
        echo "<li><a href='{$safeHref}' target='_parent'{$safeClass}>{$safeLabel}</a></li>";
    }

    $menuLinks = [
        ['href' => 'index.php', 'label' => 'Home'],
        ['href' => 'sobre.php', 'label' => 'Sobre']
    ];

    if ($isLogged) {
        if ($role === 1) {
            $menuLinks[] = ['href' => 'administrador.php', 'label' => 'Administracao'];
            $menuLinks[] = ['href' => 'chamar.php', 'label' => 'Chamar'];
            $menuLinks[] = ['href' => 'historico.php?qm=1', 'label' => 'Historico'];
        }
        if ($role === 2) {
            $menuLinks[] = ['href' => 'colabo/colaborador.php', 'label' => 'Painel Colaborador'];
            $menuLinks[] = ['href' => 'historico.php?qm=2', 'label' => 'Historico'];
        }
        if ($role === 3) {
            $menuLinks[] = ['href' => 'chamar.php', 'label' => 'Chamar'];
            $menuLinks[] = ['href' => 'historico.php?qm=1', 'label' => 'Historico'];
        }
    }
?>
<!DOCTYPE html>
<html lang="pt-br">
    <head>
        <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0"> 
            <meta name="author" content="Gabriel Nepomuceno de Almeida dos Santos">
            <meta name="keywords" content="HTML, CSS">
            <meta name="description" content="Pagina inicial do fast services">
            <link rel="stylesheet" href="css/estrutura_geral.css">
            <title>Menu</title>
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    let toggle = document.getElementById('theme-toggle');
                    if (!toggle) {
                        return;
                    }
                    toggle.addEventListener('click', function (event) {
                        event.preventDefault();
                        fetch('theme_toggle.php', { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
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
    </head>

    <body class="menu-frame <?php echo $themeClass; ?>">
        <?php
            if (isset($atualizar) && $atualizar == "sim") {
                echo "<div class='notice notice--warn'>
                    <div><strong>Atualizacao:</strong> precisamos recarregar sua sessao para manter os dados corretos.</div>
                    <button type='button' onclick=\"parent.window.location.href='sair.php';\">Recarregar</button>
                </div>
                <script>
                    setTimeout(function () {
                        parent.window.location.href = 'sair.php';
                    }, 3500);
                </script>";
            }
        ?>
        <nav class='menu'>
            <ul>
                <li>
                    <a class='brand' href='index.php' target='_parent' id='theme-toggle'>
                        <img src='image/logoservicore.jpg' alt='Uniserv'>
                        <span>Uniserv</span>
                        <?php
                            if ($isLogged && isset($roleLabels[$role])) {
                                echo "<span class='role-tag'>".htmlspecialchars($roleLabels[$role], ENT_QUOTES, 'UTF-8')."</span>";
                            }
                        ?>
                    </a>
                </li>
                <?php
                    foreach ($menuLinks as $item) {
                        render_menu_item($item['href'], $item['label']);
                    }

                    if ($isLogged) {
                        $apelido = htmlspecialchars($_SESSION['apelido'], ENT_QUOTES, 'UTF-8');
                        render_menu_item('perfil.php', $apelido);

                        $comando_testar2 = "SELECT * FROM servico WHERE ativo>0";
                        $joga_no_banco = mysqli_query($conn, $comando_testar2);
                        while ($linha54 = mysqli_fetch_array($joga_no_banco)) {
                            if ($linha54['registro_id_registro'] == $_SESSION['id_acesso'] || $linha54['id_trabalhador'] == $_SESSION['id_acesso'] && $linha54['ativo'] == 1 ) {
                                render_menu_item('servicos.php', 'Servicos Ativos', 'menu-alert');
                                break;
                            }
                        }
                    } else {
                        render_menu_item('cadastro.php', 'Cadastrar');
                        render_menu_item('login.php', 'Entrar');
                    }
                ?>
            </ul>
        </nav>
        
        
        

    </body>