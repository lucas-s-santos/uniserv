<?php
    include_once __DIR__ . '/includes/bootstrap.php';

    header('Content-Type: application/json; charset=UTF-8');

    if (!isset($_SESSION['id_acesso'])) {
        http_response_code(401);
        echo json_encode(['ok' => false, 'error' => 'unauthorized']);
        exit;
    }

    $query = '';
    if (isset($_POST['endereco'])) {
        $query = trim($_POST['endereco']);
    }
    if ($query === '' && isset($_POST['cidade'])) {
        $cidade = trim($_POST['cidade']);
        $estado = isset($_POST['estado']) ? trim($_POST['estado']) : '';
        $query = $cidade !== '' ? ($cidade . ($estado !== '' ? ', ' . $estado : '') . ', Brasil') : '';
    }

    if ($query === '') {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'missing_query']);
        exit;
    }

    $geo = geocode_nominatim($query);
    if (!$geo) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'error' => 'not_found']);
        exit;
    }

    echo json_encode([
        'ok' => true,
        'lat' => $geo['lat'],
        'lng' => $geo['lng']
    ]);
?>