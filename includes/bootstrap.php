<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

include_once __DIR__ . '/../conexao.php';
include_once __DIR__ . '/../audit.php';
include_once __DIR__ . '/../status.php';
?>