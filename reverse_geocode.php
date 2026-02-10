<?php
    include_once __DIR__ . '/includes/bootstrap.php';

    header('Content-Type: application/json; charset=UTF-8');

    if (!isset($_SESSION['id_acesso'])) {
        http_response_code(401);
        echo json_encode(['ok' => false, 'error' => 'unauthorized']);
        exit;
    }

    $lat = isset($_POST['lat']) ? trim($_POST['lat']) : '';
    $lng = isset($_POST['lng']) ? trim($_POST['lng']) : '';
    if ($lat === '' || $lng === '' || !is_numeric($lat) || !is_numeric($lng)) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'invalid_coords']);
        exit;
    }

    $addr = reverse_geocode_nominatim($lat, $lng);
    if (!$addr) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'error' => 'not_found']);
        exit;
    }

    echo json_encode([
        'ok' => true,
        'rua' => $addr['rua'],
        'numero' => $addr['numero'],
        'bairro' => $addr['bairro']
    ]);
?>