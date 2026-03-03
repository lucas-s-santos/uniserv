<?php

if (!function_exists('pix_env')) {
    function pix_env($key, $default = '')
    {
        $value = getenv($key);
        if ($value === false || $value === null || $value === '') {
            return $default;
        }
        return $value;
    }
}

if (!function_exists('pix_get_webhook_token')) {
    function pix_get_webhook_token()
    {
        return trim((string) pix_env('PIX_WEBHOOK_TOKEN', ''));
    }
}

if (!function_exists('pix_get_ttl_minutes')) {
    function pix_get_ttl_minutes()
    {
        $ttl = (int) pix_env('PIX_TTL_MINUTES', 30);
        if ($ttl <= 0) {
            $ttl = 30;
        }
        return $ttl;
    }
}

if (!function_exists('pix_dynamic_columns')) {
    function pix_dynamic_columns()
    {
        return [
            'pix_txid',
            'pix_payload',
            'pix_qr_url',
            'pix_gateway',
            'pix_expira_em',
            'pix_status',
            'pix_valor',
            'pix_pago_em',
            'pix_webhook_payload'
        ];
    }
}

if (!function_exists('pix_db_column_exists')) {
    function pix_db_column_exists($conn, $table, $column)
    {
        static $cache = [];

        $table = preg_replace('/[^a-zA-Z0-9_]/', '', (string) $table);
        $column = preg_replace('/[^a-zA-Z0-9_]/', '', (string) $column);
        if ($table === '' || $column === '' || !($conn instanceof mysqli)) {
            return false;
        }

        $cacheKey = strtolower($table . '.' . $column);
        if (array_key_exists($cacheKey, $cache)) {
            return $cache[$cacheKey];
        }

        $sql = "SHOW COLUMNS FROM `{$table}` LIKE ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            $cache[$cacheKey] = false;
            return false;
        }

        $stmt->bind_param('s', $column);
        $exists = false;
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            $exists = $result && $result->num_rows > 0;
            if ($result) {
                $result->free();
            }
        }

        $stmt->close();
        $cache[$cacheKey] = $exists;
        return $exists;
    }
}

if (!function_exists('pix_has_dynamic_schema')) {
    function pix_has_dynamic_schema($conn)
    {
        foreach (pix_dynamic_columns() as $column) {
            if (!pix_db_column_exists($conn, 'servico', $column)) {
                return false;
            }
        }
        return true;
    }
}

if (!function_exists('pix_emv_field')) {
    function pix_emv_field($id, $value)
    {
        $len = str_pad((string) strlen((string) $value), 2, '0', STR_PAD_LEFT);
        return $id . $len . $value;
    }
}

if (!function_exists('pix_sanitize')) {
    function pix_sanitize($text, $maxLen)
    {
        $text = trim((string) $text);
        if ($text === '') {
            return '';
        }

        if (function_exists('iconv')) {
            $text = @iconv('UTF-8', 'ASCII//TRANSLIT', $text);
        }

        $text = strtoupper($text);
        $text = preg_replace('/[^A-Z0-9 ]/', '', $text);
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);

        if (strlen($text) > $maxLen) {
            $text = substr($text, 0, $maxLen);
        }
        return $text;
    }
}

if (!function_exists('pix_crc16')) {
    function pix_crc16($payload)
    {
        $crc = 0xFFFF;
        $polynomial = 0x1021;

        for ($i = 0; $i < strlen($payload); $i++) {
            $crc ^= (ord($payload[$i]) << 8);
            for ($j = 0; $j < 8; $j++) {
                if ($crc & 0x8000) {
                    $crc = (($crc << 1) ^ $polynomial) & 0xFFFF;
                } else {
                    $crc = ($crc << 1) & 0xFFFF;
                }
            }
        }

        return strtoupper(str_pad(dechex($crc), 4, '0', STR_PAD_LEFT));
    }
}

if (!function_exists('pix_generate_txid')) {
    function pix_generate_txid($idServico)
    {
        $idServico = (int) $idServico;
        $base = 'USV' . date('ymd') . str_pad((string) $idServico, 6, '0', STR_PAD_LEFT);
        try {
            $rand = strtoupper(substr(bin2hex(random_bytes(6)), 0, 12));
        } catch (Throwable $e) {
            $rand = strtoupper(substr(md5(uniqid((string) mt_rand(), true)), 0, 12));
        }
        $txid = preg_replace('/[^A-Z0-9]/', '', $base . $rand);
        return substr($txid, 0, 35);
    }
}

if (!function_exists('pix_build_payload')) {
    function pix_build_payload($pixKeyRaw, $receiverName, $receiverCity, $amount, $txid)
    {
        $pixKeyRaw = trim((string) $pixKeyRaw);
        if ($pixKeyRaw === '') {
            return '';
        }

        $nomeEmv = pix_sanitize($receiverName, 25);
        if ($nomeEmv === '') {
            $nomeEmv = 'UNISERV';
        }
        $cidadeEmv = pix_sanitize($receiverCity, 15);
        if ($cidadeEmv === '') {
            $cidadeEmv = 'BRASIL';
        }

        $amount = (float) $amount;
        if ($amount <= 0) {
            $amount = 0.01;
        }
        $valorEmv = number_format($amount, 2, '.', '');
        $merchantInfo = pix_emv_field('00', 'BR.GOV.BCB.PIX') . pix_emv_field('01', $pixKeyRaw);

        $payload = '';
        $payload .= pix_emv_field('00', '01');
        $payload .= pix_emv_field('26', $merchantInfo);
        $payload .= pix_emv_field('52', '0000');
        $payload .= pix_emv_field('53', '986');
        $payload .= pix_emv_field('54', $valorEmv);
        $payload .= pix_emv_field('58', 'BR');
        $payload .= pix_emv_field('59', $nomeEmv);
        $payload .= pix_emv_field('60', $cidadeEmv);
        $payload .= pix_emv_field('62', pix_emv_field('05', $txid));
        $payload .= '6304';
        $payload .= pix_crc16($payload);

        return $payload;
    }
}

if (!function_exists('pix_build_dynamic_charge')) {
    function pix_build_dynamic_charge($idServico, $pixKeyRaw, $receiverName, $receiverCity, $amount)
    {
        $txid = pix_generate_txid($idServico);
        $payload = pix_build_payload($pixKeyRaw, $receiverName, $receiverCity, $amount, $txid);
        $amount = (float) $amount;
        if ($amount <= 0) {
            $amount = 0.01;
        }

        return [
            'txid' => $txid,
            'payload' => $payload,
            'qr_url' => 'https://api.qrserver.com/v1/create-qr-code/?size=240x240&data=' . urlencode($payload),
            'expires_at' => date('Y-m-d H:i:s', time() + (pix_get_ttl_minutes() * 60)),
            'gateway' => 'pix_emv_local',
            'status' => 'pending',
            'amount' => round($amount, 2)
        ];
    }
}

if (!function_exists('pix_is_charge_expired')) {
    function pix_is_charge_expired($expiresAt)
    {
        $expiresAt = trim((string) $expiresAt);
        if ($expiresAt === '') {
            return true;
        }
        $timestamp = strtotime($expiresAt);
        if ($timestamp === false) {
            return true;
        }
        return $timestamp <= time();
    }
}

if (!function_exists('pix_webhook_signature_is_valid')) {
    function pix_webhook_signature_is_valid($rawBody, $receivedSignature)
    {
        $token = pix_get_webhook_token();
        if ($token === '') {
            return true;
        }

        $receivedSignature = trim((string) $receivedSignature);
        if ($receivedSignature === '') {
            return false;
        }

        $expected = hash_hmac('sha256', (string) $rawBody, $token);
        return hash_equals($expected, $receivedSignature);
    }
}

if (!function_exists('pix_webhook_status_is_paid')) {
    function pix_webhook_status_is_paid($status)
    {
        $status = strtoupper(trim((string) $status));
        return in_array($status, ['PAID', 'CONFIRMED', 'COMPLETED', 'DONE', 'RECEIVED', 'RECEBIDO', 'APROVADO'], true);
    }
}

if (!function_exists('pix_webhook_status_is_pending')) {
    function pix_webhook_status_is_pending($status)
    {
        $status = strtoupper(trim((string) $status));
        return in_array($status, ['PENDING', 'WAITING', 'OPEN', 'CREATED', 'AWAITING'], true);
    }
}

if (!function_exists('pix_webhook_status_is_failed')) {
    function pix_webhook_status_is_failed($status)
    {
        $status = strtoupper(trim((string) $status));
        return in_array($status, ['FAILED', 'REJECTED', 'CANCELED', 'CANCELLED', 'EXPIRED', 'REFUNDED'], true);
    }
}

if (!function_exists('pix_array_get_path')) {
    function pix_array_get_path(array $payload, $path)
    {
        $cursor = $payload;
        $parts = explode('.', (string) $path);
        foreach ($parts as $part) {
            if (!is_array($cursor) || !array_key_exists($part, $cursor)) {
                return null;
            }
            $cursor = $cursor[$part];
        }
        return $cursor;
    }
}

if (!function_exists('pix_webhook_extract_string')) {
    function pix_webhook_extract_string(array $payload, array $paths)
    {
        foreach ($paths as $path) {
            $value = pix_array_get_path($payload, $path);
            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
            if (is_numeric($value)) {
                return (string) $value;
            }
        }
        return '';
    }
}
