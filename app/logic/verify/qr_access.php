<?php

require_once __DIR__ . '/../../inc/qr_config.php';

if (!function_exists('qr_access_is_preview_bypass')) {
    function qr_access_is_preview_bypass(): bool
    {
        return !empty($_SESSION['contest_preview']);
    }
}

if (!function_exists('qr_access_is_active')) {
    function qr_access_is_active(): bool
    {
        if (qr_access_is_preview_bypass()) {
            return false;
        }
        return qr_config_is_enabled();
    }
}

if (!function_exists('qr_access_success_route')) {
    function qr_access_success_route(): string
    {
        global $contest_config;
        if (!empty($contest_config['participation_page_enabled'])) {
            return 'partecipazione';
        }
        return 'registrazione';
    }
}

if (!function_exists('qr_access_grant')) {
    /**
     * @param array<string, mixed> $payload
     */
    function qr_access_grant(string $code, array $payload = []): void
    {
        $_SESSION['qr_access'] = [
            'code' => $code,
            'payload' => $payload,
            'granted_at' => time(),
        ];
    }
}

if (!function_exists('qr_access_get_code')) {
    function qr_access_get_code(): ?string
    {
        $session = $_SESSION['qr_access'] ?? null;
        if (!is_array($session)) {
            return null;
        }
        $code = trim((string) ($session['code'] ?? ''));
        return $code !== '' ? $code : null;
    }
}

if (!function_exists('qr_access_clear')) {
    function qr_access_clear(): void
    {
        unset($_SESSION['qr_access']);
    }
}

if (!function_exists('qr_access_has_valid_session')) {
    function qr_access_has_valid_session(): bool
    {
        return qr_access_get_code() !== null;
    }
}

if (!function_exists('qr_access_require')) {
    function qr_access_require(): void
    {
        if (!qr_access_is_active()) {
            return;
        }
        if (qr_access_has_valid_session()) {
            return;
        }
        require_once __DIR__ . '/qr_verify_view.php';
        qr_verify_render_error('qr_missing');
        exit;
    }
}

if (!function_exists('qr_access_count_usages')) {
    function qr_access_count_usages(PDO $pdo, string $code): int
    {
        $code = trim($code);
        if ($code === '') {
            return 0;
        }
        try {
            $col = $pdo->query("SHOW COLUMNS FROM partecipazioni LIKE 'qr_code'")->fetch(PDO::FETCH_ASSOC);
            if (!$col) {
                return 0;
            }
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM partecipazioni WHERE qr_code = :code');
            $stmt->bindValue(':code', $code, PDO::PARAM_STR);
            $stmt->execute();
            return (int) $stmt->fetchColumn();
        } catch (Throwable $e) {
            error_log('qr_access_count_usages: ' . $e->getMessage());
            return 0;
        }
    }
}

if (!function_exists('qr_access_get_max_uses')) {
    /**
     * @param array<string, mixed> $payload
     */
    function qr_access_get_max_uses(array $payload): int
    {
        $cfg = qr_config_get();
        $fromPayload = (int) ($payload['giocate'] ?? 0);
        if (!empty($cfg['use_payload_giocate']) && $fromPayload > 0) {
            return $fromPayload;
        }
        return max(1, (int) ($cfg['max_uses_per_code'] ?? 1));
    }
}

if (!function_exists('qr_access_is_usage_available')) {
    /**
     * @param array<string, mixed> $payload
     */
    function qr_access_is_usage_available(PDO $pdo, string $code, array $payload): bool
    {
        $maxUses = qr_access_get_max_uses($payload);
        return qr_access_count_usages($pdo, $code) < $maxUses;
    }
}
