<?php

if (!function_exists('qr_config_system_error_keys')) {
  /**
   * @return list<string>
   */
    function qr_config_system_error_keys(): array
    {
        return ['qr_missing', 'qr_invalid', 'qr_rate'];
    }
}

if (!function_exists('qr_config_map_error_reason')) {
    function qr_config_map_error_reason(string $reason): string
    {
        $reason = preg_replace('/[^a-z0-9_]/', '', strtolower(trim($reason)));
        if ($reason === 'qr_missing' || $reason === 'qr_rate') {
            return $reason;
        }
        return 'qr_invalid';
    }
}

if (!function_exists('qr_config_system_error_labels')) {
    /**
     * @return array<string, string>
     */
    function qr_config_system_error_labels(): array
    {
        return [
            'qr_missing' => 'Codice non rilevato',
            'qr_invalid' => 'Codice non valido o gia utilizzato',
            'qr_rate' => 'Rate limit (10 secondi)',
        ];
    }
}

if (!function_exists('qr_config_default_errors')) {
    function qr_config_default_errors(): array
    {
        return [
            'qr_missing' => [
                'title' => 'Attenzione! Codice non rilevato',
                'text' => '',
            ],
            'qr_invalid' => [
                'title' => 'Attenzione! Il codice non è valido o è già stato utilizzato.',
                'text' => '',
            ],
            'qr_rate' => [
                'title' => 'Ops! Puoi giocare una volta ogni 10 secondi.',
                'text' => '',
            ],
        ];
    }
}

if (!function_exists('qr_config_normalize')) {
    /**
     * @param array<string, mixed> $raw
     * @param array<string, mixed> $defaults
     * @return array<string, mixed>
     */
    function qr_config_normalize(array $raw, array $defaults = []): array
    {
        $base = array_merge([
            'enabled' => false,
            'verify_signature' => true,
            'signature_algorithms' => ['sha256', 'sha1'],
            'public_key_sp' => '',
            'public_key_mc' => '',
            'contest_code' => '',
            'verify_contest_code_sp' => false,
            'verify_contest_code_mc' => true,
            'max_uses_per_code' => 1,
            'use_payload_giocate' => true,
            'rate_limit_seconds' => 10,
            'receipt_date_mode' => 'contest_dates',
            'receipt_date_min' => '',
            'errors' => qr_config_default_errors(),
        ], $defaults, $raw);

        $base['enabled'] = !empty($base['enabled']);
        $base['verify_signature'] = !empty($base['verify_signature']);
        $base['use_payload_giocate'] = !empty($base['use_payload_giocate']);
        $base['max_uses_per_code'] = max(1, (int) ($base['max_uses_per_code'] ?? 1));
        $base['rate_limit_seconds'] = max(0, (int) ($base['rate_limit_seconds'] ?? 10));
        $base['public_key_sp'] = trim((string) ($base['public_key_sp'] ?? ''));
        $base['public_key_mc'] = trim((string) ($base['public_key_mc'] ?? ''));
        if ($base['public_key_sp'] === '' && trim((string) ($defaults['public_key_sp'] ?? '')) !== '') {
            $base['public_key_sp'] = trim((string) $defaults['public_key_sp']);
        }
        if ($base['public_key_mc'] === '' && trim((string) ($defaults['public_key_mc'] ?? '')) !== '') {
            $base['public_key_mc'] = trim((string) $defaults['public_key_mc']);
        }
        $base['contest_code'] = trim((string) ($base['contest_code'] ?? ''));
        $base['verify_contest_code_sp'] = !empty($base['verify_contest_code_sp']);
        $base['verify_contest_code_mc'] = !empty($base['verify_contest_code_mc']);
        $base['signature_algorithms'] = qr_config_normalize_signature_algorithms($base['signature_algorithms'] ?? null);

        $mode = strtolower(trim((string) ($base['receipt_date_mode'] ?? 'contest_dates')));
        if ($mode === 'whitelist') {
            $mode = 'contest_dates';
        }
        if (!in_array($mode, ['none', 'min', 'contest_dates'], true)) {
            $mode = 'contest_dates';
        }
        $base['receipt_date_mode'] = $mode;
        $base['receipt_date_min'] = preg_replace('/\D/', '', (string) ($base['receipt_date_min'] ?? ''));

        $errors = qr_config_default_errors();
        $customErrors = is_array($base['errors'] ?? null) ? $base['errors'] : [];
        foreach ($customErrors as $key => $entry) {
            if (!is_array($entry)) {
                continue;
            }
            $errorKey = qr_config_map_error_reason((string) $key);
            $title = trim((string) ($entry['title'] ?? ''));
            $text = trim((string) ($entry['text'] ?? ''));
            if ($title === '' && $text === '') {
                continue;
            }
            if ($title !== '') {
                $errors[$errorKey]['title'] = $title;
            }
            if ($text !== '') {
                $errors[$errorKey]['text'] = $text;
            }
        }
        $base['errors'] = $errors;

        return $base;
    }
}

if (!function_exists('qr_config_get')) {
    function qr_config_get(): array
    {
        global $contest_config;
        $cfg = is_array($contest_config['qr_access'] ?? null) ? $contest_config['qr_access'] : [];
        return qr_config_normalize($cfg);
    }
}

if (!function_exists('qr_config_is_enabled')) {
    function qr_config_is_enabled(): bool
    {
        return !empty(qr_config_get()['enabled']);
    }
}

if (!function_exists('qr_config_get_error')) {
    function qr_config_get_error(string $reason): array
    {
        $cfg = qr_config_get();
        $errors = is_array($cfg['errors'] ?? null) ? $cfg['errors'] : qr_config_default_errors();
        $fallback = [
            'title' => 'Attenzione',
            'text' => 'Si e verificato un errore durante la verifica del codice QR.',
        ];
        $reason = qr_config_map_error_reason($reason);
        $entry = $errors[$reason] ?? $fallback;
        return [
            'title' => (string) ($entry['title'] ?? $fallback['title']),
            'text' => (string) ($entry['text'] ?? $fallback['text']),
        ];
    }
}

if (!function_exists('qr_config_normalize_signature_algorithms')) {
    /**
     * @param mixed $raw
     * @return list<string>
     */
    function qr_config_normalize_signature_algorithms(mixed $raw): array
    {
        $allowed = ['sha256', 'sha1'];
        $items = is_array($raw) ? $raw : explode(',', (string) $raw);
        $normalized = [];
        foreach ($items as $item) {
            $algo = strtolower(trim((string) $item));
            if ($algo === '' || !in_array($algo, $allowed, true)) {
                continue;
            }
            if (!in_array($algo, $normalized, true)) {
                $normalized[] = $algo;
            }
        }
        if ($normalized === []) {
            return ['sha256', 'sha1'];
        }
        usort($normalized, static function (string $a, string $b): int {
            $order = ['sha256' => 0, 'sha1' => 1];
            return ($order[$a] ?? 99) <=> ($order[$b] ?? 99);
        });
        return $normalized;
    }
}

if (!function_exists('qr_config_signature_algorithm_constant')) {
    function qr_config_signature_algorithm_constant(string $algorithm): ?int
    {
        return match (strtolower(trim($algorithm))) {
            'sha256' => OPENSSL_ALGO_SHA256,
            'sha1' => OPENSSL_ALGO_SHA1,
            default => null,
        };
    }
}

if (!function_exists('qr_config_get_signature_algorithms')) {
    /**
     * @return list<string>
     */
    function qr_config_get_signature_algorithms(): array
    {
        return qr_config_normalize_signature_algorithms(qr_config_get()['signature_algorithms'] ?? null);
    }
}

if (!function_exists('qr_config_get_public_key')) {
    function qr_config_get_public_key(string $emitter): string
    {
        $cfg = qr_config_get();
        $emitter = strtoupper(trim($emitter));
        if ($emitter === 'MC') {
            return (string) ($cfg['public_key_mc'] ?? '');
        }
        return (string) ($cfg['public_key_sp'] ?? '');
    }
}
