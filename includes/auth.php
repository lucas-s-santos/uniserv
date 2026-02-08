<?php
function require_login($redirect = 'login.php', $message = 'Voce precisa estar logado para acessar esta area.') {
    if (!isset($_SESSION['cpf'])) {
        $_SESSION['avisar'] = $message;
        header('location: ' . $redirect);
        exit;
    }
}

function require_role(array $roles, $redirect = 'login.php', $message = 'Acesso restrito.') {
    $role = isset($_SESSION['funcao']) ? (int)$_SESSION['funcao'] : 0;
    if (!in_array($role, $roles, true)) {
        $_SESSION['avisar'] = $message;
        header('location: ' . $redirect);
        exit;
    }
}

function proteger_pagina($nivelRequerido = null, $redirect = 'login.php') {
    if (!isset($_SESSION['id_acesso'])) {
        header("Location: $redirect");
        exit;
    }
    if ($nivelRequerido !== null && (int)$_SESSION['funcao'] !== (int)$nivelRequerido) {
        $_SESSION['avisar'] = "Acesso restrito.";
        header("Location: index.php");
        exit;
    }
}
?>