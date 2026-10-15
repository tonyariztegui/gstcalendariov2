<?php

require_once __DIR__ . '/../../inc/qr_config.php';
require_once __DIR__ . '/../../inc/functions.php';
require_once __DIR__ . '/qr_access.php';

if (!function_exists('qr_verify_normalize_pem')) {
    function qr_verify_normalize_pem(string $key): string
    {
        $key = trim(str_replace(["\r\n", "\r"], "\n", $key));
        if ($key === '') {
            return '';
        }
        if (str_contains($key, 'BEGIN PUBLIC KEY')) {
            return $key;
        }
        $clean = preg_replace('/\s+/', '', $key);
        if ($clean === '') {
            return '';
        }
        return "-----BEGIN PUBLIC KEY-----\n" . wordwrap($clean, 64, "\n", true) . "\n-----END PUBLIC KEY-----";
    }
}

if (!function_exists('qr_verify_normalize_url_code')) {
    /**
     * In query strings PHP decodes "+" as space (x-www-form-urlencoded).
     * Base64 signatures (segment 9) may contain "+" — restore before verify.
     */
    function qr_verify_normalize_url_code(string $rawCode): string
    {
        $rawCode = trim($rawCode);
        if ($rawCode === '') {
            return '';
        }
        $parts = explode(';', $rawCode);
        if (count($parts) >= 9) {
            $parts[8] = str_replace(' ', '+', trim((string) $parts[8]));
        }
        return implode(';', $parts);
    }
}

if (!function_exists('qr_verify_parse_payload')) {
    /**
     * @return array{ok: bool, reason?: string, code?: string, payload?: array<string, mixed>}
     */
    function qr_verify_parse_payload(string $rawCode): array
    {
        $code = qr_verify_normalize_url_code($rawCode);
        if ($code === '') {
            return ['ok' => false, 'reason' => 'qr_missing'];
        }

        $parts = explode(';', $code);
        if (count($parts) < 11) {
            return ['ok' => false, 'reason' => 'qr_invalid'];
        }

        $dataParts = array_slice($parts, 0, 8);
        $signature = trim((string) ($parts[8] ?? ''));
        $contestCode = trim((string) ($parts[9] ?? ''));
        $emitter = strtoupper(trim((string) ($parts[10] ?? '')));

        if ($signature === '' || !in_array($emitter, ['SP', 'MC'], true)) {
            return ['ok' => false, 'reason' => 'qr_invalid'];
        }

        $payload = [
            'data_scontrino' => trim((string) ($parts[0] ?? '')),
            'giocate' => (int) preg_replace('/\D/', '', (string) ($parts[7] ?? '1')),
            'signature' => $signature,
            'contest_code' => $contestCode,
            'emitter' => $emitter,
            'signed_data' => implode(';', $dataParts),
            'parts' => $parts,
        ];

        if ($payload['giocate'] < 1) {
            $payload['giocate'] = 1;
        }

        return [
            'ok' => true,
            'code' => $code,
            'payload' => $payload,
        ];
    }
}

if (!function_exists('qr_verify_check_signature')) {
    /**
     * @param array<string, mixed> $payload
     */
    function qr_verify_check_signature(array $payload): bool
    {
        $cfg = qr_config_get();
        if (empty($cfg['verify_signature'])) {
            return true;
        }

        $emitter = strtoupper(trim((string) ($payload['emitter'] ?? '')));
        $publicKey = qr_verify_normalize_pem(qr_config_get_public_key($emitter));
        if ($publicKey === '') {
            return false;
        }

        $signedData = trim((string) ($payload['signed_data'] ?? ''));
        $signature = base64_decode(trim((string) ($payload['signature'] ?? '')), true);
        if ($signedData === '' || $signature === false || $signature === '') {
            return false;
        }

        foreach (qr_config_get_signature_algorithms() as $algorithm) {
            $algoConstant = qr_config_signature_algorithm_constant($algorithm);
            if ($algoConstant === null) {
                continue;
            }
            $result = openssl_verify($signedData, $signature, $publicKey, $algoConstant);
            if ($result === 1) {
                return true;
            }
            if ($result === -1) {
                error_log('qr_verify_check_signature openssl error (' . $algorithm . '): ' . (openssl_error_string() ?: 'unknown'));
            }
        }

        return false;
    }
}

if (!function_exists('qr_verify_check_contest_code')) {
    /**
     * @param array<string, mixed> $payload
     */
    function qr_verify_check_contest_code(array $payload): bool
    {
        $cfg = qr_config_get();
        $expected = trim((string) ($cfg['contest_code'] ?? ''));
        if ($expected === '') {
            return true;
        }

        $emitter = strtoupper(trim((string) ($payload['emitter'] ?? '')));
        $verifyEnabled = $emitter === 'MC'
            ? !empty($cfg['verify_contest_code_mc'])
            : !empty($cfg['verify_contest_code_sp']);
        if (!$verifyEnabled) {
            return true;
        }

        $actual = trim((string) ($payload['contest_code'] ?? ''));
        if ($actual === '') {
            return false;
        }

        return strcasecmp($actual, $expected) === 0;
    }
}

if (!function_exists('qr_verify_check_receipt_date')) {
    /**
     * @param array<string, mixed> $payload
     */
    function qr_verify_check_receipt_date(array $payload): bool
    {
        global $contest_config;

        $cfg = qr_config_get();
        $mode = (string) ($cfg['receipt_date_mode'] ?? 'none');
        if ($mode === 'none') {
            return true;
        }

        $receiptDigits = preg_replace('/\D/', '', (string) ($payload['data_scontrino'] ?? ''));
        if ($receiptDigits === '') {
            return false;
        }
        $receiptInt = (int) $receiptDigits;

        if ($mode === 'min') {
            $min = preg_replace('/\D/', '', (string) ($cfg['receipt_date_min'] ?? ''));
            if ($min === '') {
                return true;
            }
            return $receiptInt >= (int) $min;
        }

        if ($mode === 'contest_dates') {
            // Date inizio/fine concorso (content.json) vs 1° segmento QR in YYMMDD
            $start = preg_replace('/\D/', '', (string) ($contest_config['start_date'] ?? ''));
            $end = preg_replace('/\D/', '', (string) ($contest_config['end_date'] ?? ''));
            if (strlen($start) === 8) {
                $start = substr($start, 2);
            }
            if (strlen($end) === 8) {
                $end = substr($end, 2);
            }
            if ($start !== '' && $receiptInt < (int) $start) {
                return false;
            }
            if ($end !== '' && $receiptInt > (int) $end) {
                return false;
            }
        }

        return true;
    }
}

if (!function_exists('qr_verify_check_rate_limit')) {
    function qr_verify_check_rate_limit(PDO $pdo, string $ipAddress): bool
    {
        $seconds = (int) (qr_config_get()['rate_limit_seconds'] ?? 0);
        if ($seconds <= 0 || trim($ipAddress) === '') {
            return true;
        }

        try {
            $col = $pdo->query("SHOW COLUMNS FROM partecipazioni LIKE 'ip_address'")->fetch(PDO::FETCH_ASSOC);
            if (!$col) {
                return true;
            }
            $threshold = time() - $seconds;
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM partecipazioni WHERE ip_address = :ip AND UNIX_TIMESTAMP(data_partecipazione) > :threshold');
            $stmt->bindValue(':ip', $ipAddress, PDO::PARAM_STR);
            $stmt->bindValue(':threshold', $threshold, PDO::PARAM_INT);
            $stmt->execute();
            return ((int) $stmt->fetchColumn()) === 0;
        } catch (Throwable $e) {
            error_log('qr_verify_check_rate_limit: ' . $e->getMessage());
            return true;
        }
    }
}

if (!function_exists('qr_verify_contest_timing')) {
    /**
     * @return array{action: string, url?: string}
     */
    function qr_verify_contest_timing(bool $isPreview): array
    {
        global $date_debut, $date_fin;

        if ($isPreview) {
            return ['action' => 'continue'];
        }

        $now = time();
        if ($now < $date_debut) {
            return ['action' => 'waiting', 'view' => 'waiting_page.php'];
        }
        if ($now > $date_fin) {
            return ['action' => 'waiting', 'view' => 'waiting_page_ended.php'];
        }

        return ['action' => 'continue'];
    }
}

if (!function_exists('qr_verify_handle_request')) {
    /**
     * @param array<string, mixed> $server
     * @return array<string, mixed>
     */
    function qr_verify_handle_request(array $get, array $server, bool $isPreview): array
    {
        if (!qr_config_is_enabled()) {
            return [
                'action' => 'redirect',
                'url' => qr_access_success_route(),
            ];
        }

        $timing = qr_verify_contest_timing($isPreview);
        if (($timing['action'] ?? '') === 'waiting') {
            return $timing;
        }

        $rawCode = qr_verify_normalize_url_code((string) ($get['code'] ?? ''));
        if ($rawCode === '') {
            return ['action' => 'error', 'reason' => 'qr_missing'];
        }

        $parsed = qr_verify_parse_payload($rawCode);
        if (empty($parsed['ok'])) {
            return ['action' => 'error', 'reason' => (string) ($parsed['reason'] ?? 'qr_invalid')];
        }

        $code = (string) $parsed['code'];
        $payload = is_array($parsed['payload'] ?? null) ? $parsed['payload'] : [];

        if (!qr_verify_check_signature($payload)) {
            return ['action' => 'error', 'reason' => 'qr_invalid'];
        }

        if (!qr_verify_check_contest_code($payload)) {
            return ['action' => 'error', 'reason' => 'qr_contest'];
        }

        if (!qr_verify_check_receipt_date($payload)) {
            return ['action' => 'error', 'reason' => 'qr_receipt'];
        }

        $pdo = null;
        try {
            $pdo = conn_pdo();
        } catch (Throwable $e) {
            error_log('qr_verify_handle_request PDO: ' . $e->getMessage());
        }

        $ipAddress = trim((string) ($server['REMOTE_ADDR'] ?? ''));
        if ($pdo instanceof PDO && !qr_verify_check_rate_limit($pdo, $ipAddress)) {
            return ['action' => 'error', 'reason' => 'qr_rate'];
        }

        if ($pdo instanceof PDO && !qr_access_is_usage_available($pdo, $code, $payload)) {
            return ['action' => 'error', 'reason' => 'qr_used'];
        }

        qr_access_grant($code, $payload);

        return [
            'action' => 'redirect',
            'url' => qr_access_success_route(),
        ];
    }
}
