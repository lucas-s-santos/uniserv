<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

include_once __DIR__ . '/../conexao.php';
include_once __DIR__ . '/../audit.php';
include_once __DIR__ . '/../status.php';

if (!function_exists('geocode_nominatim')) {
    function geocode_nominatim($query) {
        $query = trim((string)$query);
        if ($query === '') {
            return null;
        }
        $url = 'https://nominatim.openstreetmap.org/search?format=json&limit=1&q=' . urlencode($query);
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 6,
                'header' => "User-Agent: UniServ/1.0 (geocode)\r\n"
            ]
        ]);
        $raw = @file_get_contents($url, false, $context);
        if ($raw === false) {
            return null;
        }
        $data = json_decode($raw, true);
        if (!is_array($data) || empty($data[0]['lat']) || empty($data[0]['lon'])) {
            return null;
        }
        return [
            'lat' => (float)$data[0]['lat'],
            'lng' => (float)$data[0]['lon']
        ];
    }
}

if (!function_exists('reverse_geocode_nominatim')) {
    function reverse_geocode_nominatim($lat, $lng) {
        if (!is_numeric($lat) || !is_numeric($lng)) {
            return null;
        }
        $url = 'https://nominatim.openstreetmap.org/reverse?format=json&zoom=18&addressdetails=1&lat=' . urlencode($lat) . '&lon=' . urlencode($lng);
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 6,
                'header' => "User-Agent: UniServ/1.0 (reverse-geocode)\r\n"
            ]
        ]);
        $raw = @file_get_contents($url, false, $context);
        if ($raw === false) {
            return null;
        }
        $data = json_decode($raw, true);
        if (!is_array($data) || empty($data['address'])) {
            return null;
        }
        $addr = $data['address'];
        $rua = $addr['road'] ?? ($addr['pedestrian'] ?? '');
        $numero = $addr['house_number'] ?? '';
        $bairro = $addr['suburb'] ?? ($addr['neighbourhood'] ?? '');
        return [
            'rua' => $rua,
            'numero' => $numero,
            'bairro' => $bairro
        ];
    }
}
?>