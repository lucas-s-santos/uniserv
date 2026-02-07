<?php
    session_start();

    $current = isset($_SESSION['theme']) ? $_SESSION['theme'] : 'dark';
    $_SESSION['theme'] = $current === 'light' ? 'dark' : 'light';

    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(['theme' => $_SESSION['theme']]);
?>