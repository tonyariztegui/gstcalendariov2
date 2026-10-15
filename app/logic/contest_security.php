<?php

/**
 * Sicurezza concorso: orchestratore centrale (blacklist, domini, IP, rate limits, registro).
 */

require_once __DIR__ . '/email_blacklist.php';

if (!function_exists('contest_security_ensure_tables')) {
    function contest_security_ensure_tables(PDO $pdo): void
    {
        static $done = [];
        $cid = spl_object_id($pdo);
        if (isset($done[$cid])) {
            return;
        }

        email_blacklist_ensure_table($pdo);

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `email_domain_blocklist` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `domain` VARCHAR(253) NOT NULL,
                `note` VARCHAR(500) NULL DEFAULT NULL,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `created_by` VARCHAR(100) NULL DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uk_domain_blocklist` (`domain`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `ip_blocklist` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `ip_address` VARCHAR(45) NOT NULL,
                `note` VARCHAR(500) NULL DEFAULT NULL,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `created_by` VARCHAR(100) NULL DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uk_ip_blocklist` (`ip_address`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `security_settings` (
                `setting_key` VARCHAR(64) NOT NULL,
                `setting_value` VARCHAR(255) NOT NULL DEFAULT '',
                `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`setting_key`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `security_event_log` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `event_type` VARCHAR(64) NOT NULL,
                `email_masked` VARCHAR(254) NULL DEFAULT NULL,
                `ip_address` VARCHAR(45) NULL DEFAULT NULL,
                `detail` VARCHAR(500) NULL DEFAULT NULL,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `idx_security_log_created` (`created_at`),
                KEY `idx_security_log_type` (`event_type`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `codice_lockout` (
                `entity_type` ENUM('ip','email') NOT NULL,
                `entity_value` VARCHAR(254) NOT NULL,
                `failure_count` INT UNSIGNED NOT NULL DEFAULT 0,
                `window_started_at` DATETIME NULL DEFAULT NULL,
                `strike_level` TINYINT UNSIGNED NOT NULL DEFAULT 0,
                `locked_until` DATETIME NULL DEFAULT NULL,
                `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`entity_type`, `entity_value`),
                KEY `idx_codice_lockout_locked` (`locked_until`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $defaults = contest_security_default_settings();
        $ins = $pdo->prepare('INSERT IGNORE INTO security_settings (setting_key, setting_value) VALUES (:k, :v)');
        foreach ($defaults as $k => $v) {
            $ins->execute([':k' => $k, ':v' => $v]);
        }

        $done[$cid] = true;
    }
}

if (!function_exists('contest_security_default_settings')) {
    /**
     * @return array<string, string>
     */
    function contest_security_default_settings(): array
    {
        return [
            'registration_ip_max_per_day' => '0',
            'registration_email_max_per_day' => '0',
            'registration_cooldown_seconds' => '0',
            'log_retention_days' => '90',
            'codice_fail_max_per_window' => '5',
            'codice_fail_window_seconds' => '900',
            'codice_lockout_level1_seconds' => '300',
            'codice_lockout_level2_seconds' => '1800',
            'codice_lockout_level3_seconds' => '86400',
            'codice_blacklist_at_strike' => '4',
        ];
    }
}

if (!function_exists('contest_security_reset_to_defaults')) {
    /**
     * Ripristina Sicurezza: blacklist, registro, lockout codici, impostazioni predefinite.
     * Non modifica content.json (gestito separatamente dal reset concorso).
     *
     * @return array{ok: bool, message: string}
     */
    function contest_security_reset_to_defaults(PDO $pdo): array
    {
        try {
            email_blacklist_ensure_table($pdo);
            contest_security_ensure_tables($pdo);

            $tables = [
                'email_blacklist',
                'email_domain_blocklist',
                'ip_blocklist',
                'security_event_log',
                'codice_lockout',
            ];

            $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
            foreach ($tables as $table) {
                $pdo->exec("TRUNCATE TABLE `{$table}`");
            }
            $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');

            $defaults = contest_security_default_settings();
            $intDefaults = [];
            foreach ($defaults as $key => $value) {
                $intDefaults[$key] = (int) $value;
            }
            contest_security_save_settings($pdo, $intDefaults);

            return [
                'ok' => true,
                'message' => 'Sicurezza ripristinata: blacklist, registro e impostazioni ai valori predefiniti.',
            ];
        } catch (Throwable $e) {
            error_log('contest_security_reset_to_defaults: ' . $e->getMessage());

            return [
                'ok' => false,
                'message' => 'Errore ripristino Sicurezza: ' . $e->getMessage(),
            ];
        }
    }
}

if (!function_exists('contest_security_normalize_domain')) {
    function contest_security_normalize_domain(string $domain): string
    {
        $domain = strtolower(trim($domain));
        $domain = ltrim($domain, '@');
        $domain = preg_replace('/\s+/u', '', $domain) ?? '';

        return $domain;
    }
}

if (!function_exists('contest_security_get_settings')) {
    /**
     * @return array<string, string>
     */
    function contest_security_get_settings(PDO $pdo): array
    {
        contest_security_ensure_tables($pdo);
        $out = [];
        $st = $pdo->query('SELECT setting_key, setting_value FROM security_settings');
        while ($row = $st->fetch(PDO::FETCH_ASSOC)) {
            $out[(string) $row['setting_key']] = (string) $row['setting_value'];
        }

        return $out;
    }
}

if (!function_exists('contest_security_save_settings')) {
    /**
     * @param array<string, int|string> $settings
     */
    function contest_security_save_settings(PDO $pdo, array $settings): void
    {
        contest_security_ensure_tables($pdo);
        $allowed = [
            'registration_ip_max_per_day',
            'registration_email_max_per_day',
            'registration_cooldown_seconds',
            'log_retention_days',
            'codice_fail_max_per_window',
            'codice_fail_window_seconds',
            'codice_lockout_level1_seconds',
            'codice_lockout_level2_seconds',
            'codice_lockout_level3_seconds',
            'codice_blacklist_at_strike',
        ];
        $upd = $pdo->prepare('
            INSERT INTO security_settings (setting_key, setting_value)
            VALUES (:k, :v)
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
        ');
        foreach ($allowed as $key) {
            if (!array_key_exists($key, $settings)) {
                continue;
            }
            $val = max(0, (int) $settings[$key]);
            if ($key === 'log_retention_days') {
                $val = max(7, min(365, $val));
            } elseif ($key === 'codice_fail_max_per_window') {
                $val = min(99, $val);
            } elseif ($key === 'codice_fail_window_seconds') {
                $val = min(86400, $val);
            } elseif (str_starts_with($key, 'codice_lockout_level')) {
                $val = min(604800, $val);
            } elseif ($key === 'codice_blacklist_at_strike') {
                $val = max(1, min(10, $val));
            }
            $upd->execute([':k' => $key, ':v' => (string) $val]);
        }
    }
}

if (!function_exists('contest_security_mask_email')) {
    function contest_security_mask_email(string $email): string
    {
        $email = strtolower(trim($email));
        if ($email === '' || !str_contains($email, '@')) {
            return '***';
        }
        [$local, $domain] = explode('@', $email, 2);
        $localLen = strlen($local);
        if ($localLen <= 2) {
            $maskedLocal = str_repeat('*', $localLen);
        } else {
            $maskedLocal = substr($local, 0, 1) . str_repeat('*', max(1, $localLen - 2)) . substr($local, -1);
        }

        return $maskedLocal . '@' . $domain;
    }
}

if (!function_exists('contest_security_log_event')) {
    function contest_security_log_event(
        PDO $pdo,
        string $eventType,
        ?string $email = null,
        ?string $ip = null,
        ?string $detail = null
    ): void {
        try {
            contest_security_ensure_tables($pdo);
            $masked = ($email !== null && $email !== '') ? contest_security_mask_email($email) : null;
            $ip = $ip !== null ? trim($ip) : null;
            if ($ip !== null && strlen($ip) > 45) {
                $ip = substr($ip, 0, 45);
            }
            $detail = $detail !== null ? trim($detail) : null;
            if ($detail !== null && strlen($detail) > 500) {
                $detail = mb_substr($detail, 0, 500, 'UTF-8');
            }
            $pdo->prepare('
                INSERT INTO security_event_log (event_type, email_masked, ip_address, detail)
                VALUES (:t, :e, :ip, :d)
            ')->execute([
                ':t' => $eventType,
                ':e' => $masked,
                ':ip' => $ip !== '' ? $ip : null,
                ':d' => $detail !== '' ? $detail : null,
            ]);

            $settings = contest_security_get_settings($pdo);
            $retention = max(7, (int) ($settings['log_retention_days'] ?? 90));
            $threshold = date('Y-m-d H:i:s', time() - ($retention * 86400));
            $pdo->prepare('DELETE FROM security_event_log WHERE created_at < :threshold')
                ->execute([':threshold' => $threshold]);
        } catch (Throwable $e) {
            error_log('contest_security_log_event: ' . $e->getMessage());
        }
    }
}

if (!function_exists('contest_security_domain_is_blocked')) {
    function contest_security_domain_is_blocked(PDO $pdo, string $email): bool
    {
        $email = email_blacklist_normalize($email);
        if ($email === '' || !str_contains($email, '@')) {
            return false;
        }
        $domain = contest_security_normalize_domain((string) substr($email, strpos($email, '@') + 1));
        if ($domain === '') {
            return false;
        }

        contest_security_ensure_tables($pdo);
        $stmt = $pdo->prepare('SELECT 1 FROM email_domain_blocklist WHERE domain = :d LIMIT 1');
        $stmt->execute([':d' => $domain]);

        return (bool) $stmt->fetchColumn();
    }
}

if (!function_exists('contest_security_domain_add')) {
    /** @return array{ok: bool, message: string} */
    function contest_security_domain_add(PDO $pdo, string $domain, ?string $note = null, ?string $createdBy = null): array
    {
        $domain = contest_security_normalize_domain($domain);
        if ($domain === '' || !preg_match('/^[a-z0-9]([a-z0-9.-]*[a-z0-9])?$/i', $domain)) {
            return ['ok' => false, 'message' => 'Dominio non valido.'];
        }
        if (strlen($domain) > 253) {
            return ['ok' => false, 'message' => 'Dominio troppo lungo.'];
        }

        $note = $note !== null ? trim($note) : '';
        if (strlen($note) > 500) {
            $note = mb_substr($note, 0, 500, 'UTF-8');
        }
        $createdBy = $createdBy !== null ? trim($createdBy) : '';
        if (strlen($createdBy) > 100) {
            $createdBy = mb_substr($createdBy, 0, 100, 'UTF-8');
        }

        contest_security_ensure_tables($pdo);
        try {
            $pdo->prepare('
                INSERT INTO email_domain_blocklist (domain, note, created_by)
                VALUES (:d, :n, :c)
            ')->execute([
                ':d' => $domain,
                ':n' => $note !== '' ? $note : null,
                ':c' => $createdBy !== '' ? $createdBy : null,
            ]);

            return ['ok' => true, 'message' => 'Dominio aggiunto alla blocklist.'];
        } catch (PDOException $e) {
            if ((int) ($e->errorInfo[1] ?? 0) === 1062) {
                return ['ok' => false, 'message' => 'Questo dominio è già in blocklist.'];
            }
            error_log('contest_security_domain_add: ' . $e->getMessage());

            return ['ok' => false, 'message' => 'Errore durante il salvataggio.'];
        }
    }
}

if (!function_exists('contest_security_domain_remove')) {
    function contest_security_domain_remove(PDO $pdo, int $id): bool
    {
        if ($id <= 0) {
            return false;
        }
        contest_security_ensure_tables($pdo);
        $st = $pdo->prepare('DELETE FROM email_domain_blocklist WHERE id = :id LIMIT 1');
        $st->execute([':id' => $id]);

        return $st->rowCount() > 0;
    }
}

if (!function_exists('contest_security_domain_count')) {
    function contest_security_domain_count(PDO $pdo, string $search = ''): int
    {
        contest_security_ensure_tables($pdo);
        $search = trim($search);
        if ($search === '') {
            return (int) $pdo->query('SELECT COUNT(*) FROM email_domain_blocklist')->fetchColumn();
        }
        $st = $pdo->prepare('SELECT COUNT(*) FROM email_domain_blocklist WHERE domain LIKE :s OR note LIKE :s');
        $st->execute([':s' => '%' . $search . '%']);

        return (int) $st->fetchColumn();
    }
}

if (!function_exists('contest_security_domain_list')) {
    /** @return list<array{id: int, domain: string, note: ?string, created_at: string, created_by: ?string}> */
    function contest_security_domain_list(PDO $pdo, string $search = '', int $limit = 50, int $offset = 0): array
    {
        contest_security_ensure_tables($pdo);
        $limit = max(1, min(200, $limit));
        $offset = max(0, $offset);
        $search = trim($search);

        if ($search === '') {
            $stmt = $pdo->prepare('
                SELECT id, domain, note, created_at, created_by
                FROM email_domain_blocklist
                ORDER BY created_at DESC, id DESC
                LIMIT :limit OFFSET :offset
            ');
        } else {
            $stmt = $pdo->prepare('
                SELECT id, domain, note, created_at, created_by
                FROM email_domain_blocklist
                WHERE domain LIKE :search OR note LIKE :search
                ORDER BY created_at DESC, id DESC
                LIMIT :limit OFFSET :offset
            ');
            $stmt->bindValue(':search', '%' . $search . '%', PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $rows = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $rows[] = [
                'id' => (int) ($row['id'] ?? 0),
                'domain' => (string) ($row['domain'] ?? ''),
                'note' => isset($row['note']) ? (string) $row['note'] : null,
                'created_at' => (string) ($row['created_at'] ?? ''),
                'created_by' => isset($row['created_by']) ? (string) $row['created_by'] : null,
            ];
        }

        return $rows;
    }
}

if (!function_exists('contest_security_ip_is_blocked')) {
    function contest_security_ip_is_blocked(PDO $pdo, string $ip): bool
    {
        $ip = trim($ip);
        if ($ip === '') {
            return false;
        }
        contest_security_ensure_tables($pdo);
        $st = $pdo->prepare('SELECT 1 FROM ip_blocklist WHERE ip_address = :ip LIMIT 1');
        $st->execute([':ip' => $ip]);

        return (bool) $st->fetchColumn();
    }
}

if (!function_exists('contest_security_ip_add')) {
    /** @return array{ok: bool, message: string} */
    function contest_security_ip_add(PDO $pdo, string $ip, ?string $note = null, ?string $createdBy = null): array
    {
        $ip = trim($ip);
        if ($ip === '' || !filter_var($ip, FILTER_VALIDATE_IP)) {
            return ['ok' => false, 'message' => 'Indirizzo IP non valido.'];
        }

        $note = $note !== null ? trim($note) : '';
        if (strlen($note) > 500) {
            $note = mb_substr($note, 0, 500, 'UTF-8');
        }
        $createdBy = $createdBy !== null ? trim($createdBy) : '';

        contest_security_ensure_tables($pdo);
        try {
            $pdo->prepare('
                INSERT INTO ip_blocklist (ip_address, note, created_by)
                VALUES (:ip, :n, :c)
            ')->execute([
                ':ip' => $ip,
                ':n' => $note !== '' ? $note : null,
                ':c' => $createdBy !== '' ? $createdBy : null,
            ]);

            return ['ok' => true, 'message' => 'IP aggiunto alla blocklist.'];
        } catch (PDOException $e) {
            if ((int) ($e->errorInfo[1] ?? 0) === 1062) {
                return ['ok' => false, 'message' => 'Questo IP è già in blocklist.'];
            }
            error_log('contest_security_ip_add: ' . $e->getMessage());

            return ['ok' => false, 'message' => 'Errore durante il salvataggio.'];
        }
    }
}

if (!function_exists('contest_security_ip_remove')) {
    function contest_security_ip_remove(PDO $pdo, int $id): bool
    {
        if ($id <= 0) {
            return false;
        }
        contest_security_ensure_tables($pdo);
        $st = $pdo->prepare('DELETE FROM ip_blocklist WHERE id = :id LIMIT 1');
        $st->execute([':id' => $id]);

        return $st->rowCount() > 0;
    }
}

if (!function_exists('contest_security_ip_count')) {
    function contest_security_ip_count(PDO $pdo, string $search = ''): int
    {
        contest_security_ensure_tables($pdo);
        $search = trim($search);
        if ($search === '') {
            return (int) $pdo->query('SELECT COUNT(*) FROM ip_blocklist')->fetchColumn();
        }
        $st = $pdo->prepare('SELECT COUNT(*) FROM ip_blocklist WHERE ip_address LIKE :s OR note LIKE :s');
        $st->execute([':s' => '%' . $search . '%']);

        return (int) $st->fetchColumn();
    }
}

if (!function_exists('contest_security_ip_list')) {
    /** @return list<array{id: int, ip_address: string, note: ?string, created_at: string, created_by: ?string}> */
    function contest_security_ip_list(PDO $pdo, string $search = '', int $limit = 50, int $offset = 0): array
    {
        contest_security_ensure_tables($pdo);
        $limit = max(1, min(200, $limit));
        $offset = max(0, $offset);
        $search = trim($search);

        if ($search === '') {
            $stmt = $pdo->prepare('
                SELECT id, ip_address, note, created_at, created_by
                FROM ip_blocklist
                ORDER BY created_at DESC, id DESC
                LIMIT :limit OFFSET :offset
            ');
        } else {
            $stmt = $pdo->prepare('
                SELECT id, ip_address, note, created_at, created_by
                FROM ip_blocklist
                WHERE ip_address LIKE :search OR note LIKE :search
                ORDER BY created_at DESC, id DESC
                LIMIT :limit OFFSET :offset
            ');
            $stmt->bindValue(':search', '%' . $search . '%', PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $rows = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $rows[] = [
                'id' => (int) ($row['id'] ?? 0),
                'ip_address' => (string) ($row['ip_address'] ?? ''),
                'note' => isset($row['note']) ? (string) $row['note'] : null,
                'created_at' => (string) ($row['created_at'] ?? ''),
                'created_by' => isset($row['created_by']) ? (string) $row['created_by'] : null,
            ];
        }

        return $rows;
    }
}

if (!function_exists('contest_security_participations_has_ip_column')) {
    function contest_security_participations_has_ip_column(PDO $pdo): bool
    {
        static $cache = null;
        if ($cache !== null) {
            return $cache;
        }
        try {
            $col = $pdo->query("SHOW COLUMNS FROM partecipazioni LIKE 'ip_address'")->fetch(PDO::FETCH_ASSOC);
            $cache = $col !== false;
        } catch (Throwable $e) {
            $cache = false;
        }

        return $cache;
    }
}

if (!function_exists('contest_security_check_rate_limits')) {
    function contest_security_check_rate_limits(PDO $pdo, string $email, string $ip): ?string
    {
        $settings = contest_security_get_settings($pdo);
        $ipMax = (int) ($settings['registration_ip_max_per_day'] ?? 0);
        $emailMax = (int) ($settings['registration_email_max_per_day'] ?? 0);
        $cooldown = (int) ($settings['registration_cooldown_seconds'] ?? 0);

        if ($ipMax <= 0 && $emailMax <= 0 && $cooldown <= 0) {
            return null;
        }

        if (!contest_security_participations_has_ip_column($pdo)) {
            return null;
        }

        $sinceDay = time() - 86400;

        try {
            if ($ipMax > 0 && trim($ip) !== '') {
                $st = $pdo->prepare('
                    SELECT COUNT(*) FROM partecipazioni
                    WHERE ip_address = :ip
                      AND UNIX_TIMESTAMP(data_partecipazione) > :since
                ');
                $st->execute([':ip' => $ip, ':since' => $sinceDay]);
                if ((int) $st->fetchColumn() >= $ipMax) {
                    return 'rate_limit_ip';
                }
            }

            if ($emailMax > 0 && trim($email) !== '') {
                $st = $pdo->prepare('
                    SELECT COUNT(*) FROM partecipazioni p
                    JOIN utenti u ON u.id_cliente = p.id_cliente
                    WHERE LOWER(u.email) = LOWER(:email)
                      AND UNIX_TIMESTAMP(p.data_partecipazione) > :since
                ');
                $st->execute([':email' => email_blacklist_normalize($email), ':since' => $sinceDay]);
                if ((int) $st->fetchColumn() >= $emailMax) {
                    return 'rate_limit_email';
                }
            }

            if ($cooldown > 0 && trim($ip) !== '') {
                $st = $pdo->prepare('
                    SELECT UNIX_TIMESTAMP(MAX(data_partecipazione)) FROM partecipazioni
                    WHERE ip_address = :ip
                ');
                $st->execute([':ip' => $ip]);
                $last = $st->fetchColumn();
                if ($last !== false && $last !== null && (time() - (int) $last) < $cooldown) {
                    return 'rate_limit_cooldown';
                }
            }
        } catch (Throwable $e) {
            error_log('contest_security_check_rate_limits: ' . $e->getMessage());
        }

        return null;
    }
}

if (!function_exists('contest_security_evaluate_registration')) {
    /**
     * @param array{email?: string, ip?: string, fidelity?: string, pdo?: PDO} $ctx
     * @return string|null code blocage ou null si OK
     */
    function contest_security_evaluate_registration(array $ctx): ?string
    {
        $pdo = $ctx['pdo'] ?? null;
        if (!($pdo instanceof PDO)) {
            global $c_pdo;
            $pdo = $c_pdo instanceof PDO ? $c_pdo : null;
        }
        if (!($pdo instanceof PDO)) {
            return null;
        }

        $email = isset($ctx['email']) ? email_blacklist_normalize((string) $ctx['email']) : '';
        $ip = isset($ctx['ip']) ? trim((string) $ctx['ip']) : '';

        if ($email !== '' && email_blacklist_is_blocked($pdo, $email)) {
            contest_security_log_event($pdo, 'email_blacklist', $email, $ip);

            return 'email_blacklist';
        }

        if ($email !== '' && contest_security_domain_is_blocked($pdo, $email)) {
            contest_security_log_event($pdo, 'domain_blocked', $email, $ip);

            return 'domain_blocked';
        }

        if ($ip !== '' && contest_security_ip_is_blocked($pdo, $ip)) {
            contest_security_log_event($pdo, 'ip_blocked', $email, $ip);

            return 'ip_blocked';
        }

        $rateReason = contest_security_check_rate_limits($pdo, $email, $ip);
        if ($rateReason !== null) {
            contest_security_log_event($pdo, $rateReason, $email, $ip);

            return $rateReason;
        }

        return null;
    }
}

if (!function_exists('contest_security_get_active_rules')) {
    /**
     * @return list<array{key: string, label: string, active: bool, detail: string}>
     */
    function contest_security_get_active_rules(PDO $pdo): array
    {
        global $contest_config, $contest_mode;

        contest_security_ensure_tables($pdo);
        $settings = contest_security_get_settings($pdo);

        $rules = [
            [
                'key' => 'email_blacklist',
                'label' => 'Blacklist email',
                'active' => true,
                'detail' => (string) email_blacklist_count($pdo) . ' indirizzi bloccati',
            ],
            [
                'key' => 'domain_blocklist',
                'label' => 'Dominii email bloccati',
                'active' => contest_security_domain_count($pdo) > 0,
                'detail' => (string) contest_security_domain_count($pdo) . ' domini',
            ],
            [
                'key' => 'ip_blocklist',
                'label' => 'IP bloccati',
                'active' => contest_security_ip_count($pdo) > 0,
                'detail' => (string) contest_security_ip_count($pdo) . ' IP',
            ],
        ];

        $ipMax = (int) ($settings['registration_ip_max_per_day'] ?? 0);
        $rules[] = [
            'key' => 'rate_limit_ip',
            'label' => 'Limite iscrizioni per IP / giorno',
            'active' => $ipMax > 0,
            'detail' => $ipMax > 0 ? ('Max ' . $ipMax . ' / 24h') : 'Disattivato',
        ];

        $emailMax = (int) ($settings['registration_email_max_per_day'] ?? 0);
        $rules[] = [
            'key' => 'rate_limit_email',
            'label' => 'Limite iscrizioni per email / giorno',
            'active' => $emailMax > 0,
            'detail' => $emailMax > 0 ? ('Max ' . $emailMax . ' / 24h') : 'Disattivato',
        ];

        $cooldown = (int) ($settings['registration_cooldown_seconds'] ?? 0);
        $rules[] = [
            'key' => 'rate_limit_cooldown',
            'label' => 'Cooldown tra tentativi (stesso IP)',
            'active' => $cooldown > 0,
            'detail' => $cooldown > 0 ? ($cooldown . ' secondi') : 'Disattivato',
        ];

        $codiceCtx = contest_security_codice_protection_get_context($pdo, is_array($contest_config) ? $contest_config : []);
        $codiceMax = (int) ($settings['codice_fail_max_per_window'] ?? 0);
        $rules[] = [
            'key' => 'codice_protection',
            'label' => 'Protezione codici partecipazione',
            'active' => !empty($codiceCtx['protection_active']),
            'detail' => !empty($codiceCtx['protection_active'])
                ? ('Max ' . $codiceMax . ' tentativi / ' . (int) ($settings['codice_fail_window_seconds'] ?? 900) . 's')
                : (!empty($codiceCtx['toggle_enabled']) ? 'Attiva ma non applicabile' : 'Disattivata per questo concorso'),
        ];

        $fidelityRules = is_array($contest_config['fidelity'] ?? null) ? $contest_config['fidelity'] : [];
        $fidelityUnique = !empty($fidelityRules['unique_participation']);
        $rules[] = [
            'key' => 'fidelity_unique',
            'label' => 'Carta fidelity unica per concorso',
            'active' => $fidelityUnique,
            'detail' => $fidelityUnique ? 'Configurato in Concorso' : 'Disattivato',
        ];

        $mode = strtolower((string) ($contest_mode ?? ($contest_config['mode'] ?? '')));
        $iwOneWinActive = false;
        $iwOneWinDetail = 'Non applicabile';
        if ($mode === 'instantwin') {
            $allowMultiple = function_exists('instantwin_allow_multiple_wins')
                ? instantwin_allow_multiple_wins()
                : filter_var(getenv('INSTANTWIN_ALLOW_MULTIPLE_WINS') ?: 'false', FILTER_VALIDATE_BOOLEAN);
            // Regola "una vincita" attiva solo se le vittorie multiple sono disattivate
            $iwOneWinActive = !$allowMultiple;
            $iwOneWinDetail = $iwOneWinActive
                ? 'Attivo (Instant Win — personalizzazione)'
                : 'Disattivato (vittorie multiple consentite)';
        }
        $rules[] = [
            'key' => 'iw_one_win_per_email',
            'label' => 'Una vincita per email (Instant Win)',
            'active' => $iwOneWinActive,
            'detail' => $iwOneWinDetail,
        ];

        return $rules;
    }
}

if (!function_exists('contest_security_content_json_path')) {
    function contest_security_content_json_path(): string
    {
        return dirname(__DIR__) . '/config/content.json';
    }
}

if (!function_exists('contest_security_codice_field_enabled')) {
    function contest_security_codice_field_enabled(array $contest_config): bool
    {
        $fields = $contest_config['fields_enabled'] ?? [];
        if (!is_array($fields) || !array_key_exists('codice_partecipazione', $fields)) {
            return true;
        }
        $parsed = filter_var($fields['codice_partecipazione'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

        return $parsed === null ? true : $parsed;
    }
}

if (!function_exists('contest_security_codice_protection_get_context')) {
    /**
     * @return array{
     *   contest_name: string,
     *   mode: string,
     *   codice_field_enabled: bool,
     *   toggle_enabled: bool,
     *   protection_active: bool,
     *   contest_in_progress: bool
     * }
     */
    function contest_security_codice_protection_get_context(PDO $pdo, array $contest_config): array
    {
        $mode = strtolower(trim((string) ($contest_config['mode'] ?? '')));
        $toggle = !empty($contest_config['codice_partecipazione_security_enabled']);
        $codiceField = contest_security_codice_field_enabled($contest_config);
        $modeOk = in_array($mode, ['instantwin', 'estrazione'], true);
        $settings = contest_security_get_settings($pdo);
        $max = (int) ($settings['codice_fail_max_per_window'] ?? 0);

        $start = strtotime((string) ($contest_config['start_date'] ?? '') . ' 00:00:00');
        $end = strtotime((string) ($contest_config['end_date'] ?? '') . ' 23:59:59');
        $now = time();
        $inProgress = ($start !== false && $end !== false && $now >= $start && $now <= $end);

        return [
            'contest_name' => trim((string) ($contest_config['name'] ?? 'Concorso')),
            'mode' => $mode,
            'codice_field_enabled' => $codiceField,
            'toggle_enabled' => $toggle,
            'protection_active' => $toggle && $codiceField && $modeOk && $max > 0,
            'contest_in_progress' => $inProgress,
        ];
    }
}

if (!function_exists('contest_security_codice_protection_active')) {
    function contest_security_codice_protection_active(PDO $pdo, array $contest_config): bool
    {
        $ctx = contest_security_codice_protection_get_context($pdo, $contest_config);

        return !empty($ctx['protection_active']);
    }
}

if (!function_exists('contest_security_codice_protection_set_enabled')) {
    /** @return array{ok: bool, message: string} */
    function contest_security_codice_protection_set_enabled(bool $enabled): array
    {
        $path = contest_security_content_json_path();
        $defaultsPath = dirname(__DIR__) . '/config/content.defaults.json';
        $data = [];
        if (is_file($path) && is_readable($path)) {
            $decoded = json_decode((string) file_get_contents($path), true);
            if (is_array($decoded)) {
                $data = $decoded;
            }
        }
        if ($data === [] && is_file($defaultsPath) && is_readable($defaultsPath)) {
            $decoded = json_decode((string) file_get_contents($defaultsPath), true);
            if (is_array($decoded)) {
                $data = $decoded;
            }
        }
        $data['codice_partecipazione_security_enabled'] = $enabled;
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            return ['ok' => false, 'message' => 'Errore codifica JSON.'];
        }
        if (@file_put_contents($path, $json . "\n") === false) {
            return ['ok' => false, 'message' => 'Impossibile scrivere content.json.'];
        }

        return ['ok' => true, 'message' => ''];
    }
}

if (!function_exists('contest_security_assistance_footer_plain')) {
    function contest_security_assistance_footer_plain(): string
    {
        global $contest_config;
        $cfg = is_array($contest_config ?? null) ? $contest_config : [];
        $helpdesk = trim((string) ($cfg['helpdesk_email'] ?? ''));
        if ($helpdesk !== '') {
            return ' Se ritieni si tratti di un errore, contatta l\'assistenza (' . $helpdesk . ').';
        }
        $base = rtrim(trim((string) ($cfg['base_url'] ?? '')), '/');
        $helpUrl = ($base !== '' ? $base : '') . '/assistenza';

        return ' Se ritieni si tratti di un errore, contatta l\'assistenza (' . $helpUrl . ').';
    }
}

if (!function_exists('contest_security_codice_format_wait')) {
    function contest_security_codice_format_wait(int $secondsRemaining): string
    {
        if ($secondsRemaining < 60) {
            return 'meno di 1 minuto';
        }
        $mins = (int) ceil($secondsRemaining / 60);
        if ($mins >= 1440) {
            return '1 giorno';
        }

        return $mins . ' minut' . ($mins === 1 ? 'o' : 'i');
    }
}

if (!function_exists('contest_security_codice_get_lockout_duration')) {
    function contest_security_codice_get_lockout_duration(int $strikeLevel, array $settings): int
    {
        $strikeLevel = max(1, $strikeLevel);
        if ($strikeLevel >= 3) {
            return max(60, (int) ($settings['codice_lockout_level3_seconds'] ?? 86400));
        }
        if ($strikeLevel === 2) {
            return max(60, (int) ($settings['codice_lockout_level2_seconds'] ?? 1800));
        }

        return max(60, (int) ($settings['codice_lockout_level1_seconds'] ?? 300));
    }
}

if (!function_exists('contest_security_codice_lockout_fetch')) {
    /**
     * @return array{failure_count: int, window_started_at: ?string, strike_level: int, locked_until: ?string}|null
     */
    function contest_security_codice_lockout_fetch(PDO $pdo, string $entityType, string $entityValue): ?array
    {
        contest_security_ensure_tables($pdo);
        $st = $pdo->prepare('
            SELECT failure_count, window_started_at, strike_level, locked_until
            FROM codice_lockout
            WHERE entity_type = :t AND entity_value = :v
            LIMIT 1
        ');
        $st->execute([':t' => $entityType, ':v' => $entityValue]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }

        return [
            'failure_count' => (int) ($row['failure_count'] ?? 0),
            'window_started_at' => isset($row['window_started_at']) ? (string) $row['window_started_at'] : null,
            'strike_level' => (int) ($row['strike_level'] ?? 0),
            'locked_until' => isset($row['locked_until']) ? (string) $row['locked_until'] : null,
        ];
    }
}

if (!function_exists('contest_security_codice_lockout_increment')) {
    function contest_security_codice_lockout_increment(PDO $pdo, string $entityType, string $entityValue, int $windowSeconds): int
    {
        contest_security_ensure_tables($pdo);
        $now = time();
        $row = contest_security_codice_lockout_fetch($pdo, $entityType, $entityValue);
        $count = 0;
        $windowStart = $now;
        $strike = 0;
        $lockedUntil = null;

        if ($row !== null) {
            $count = (int) $row['failure_count'];
            $strike = (int) $row['strike_level'];
            $lockedUntil = $row['locked_until'];
            $ws = $row['window_started_at'];
            if ($ws !== null && $ws !== '') {
                $wsTs = strtotime($ws);
                if ($wsTs !== false && ($now - $wsTs) <= $windowSeconds) {
                    $windowStart = $wsTs;
                } else {
                    $count = 0;
                }
            }
        }

        $count++;

        $pdo->prepare('
            INSERT INTO codice_lockout (entity_type, entity_value, failure_count, window_started_at, strike_level, locked_until)
            VALUES (:t, :v, :c, FROM_UNIXTIME(:ws), :s, :lu)
            ON DUPLICATE KEY UPDATE
                failure_count = VALUES(failure_count),
                window_started_at = VALUES(window_started_at),
                strike_level = VALUES(strike_level),
                locked_until = VALUES(locked_until)
        ')->execute([
            ':t' => $entityType,
            ':v' => $entityValue,
            ':c' => $count,
            ':ws' => $windowStart,
            ':s' => $strike,
            ':lu' => ($lockedUntil !== null && $lockedUntil !== '') ? $lockedUntil : null,
        ]);

        return $count;
    }
}

if (!function_exists('contest_security_codice_lockout_apply_escalation')) {
    /**
     * @return array{locked: bool, blacklisted: bool, lock_seconds: int, strike_level: int}
     */
    function contest_security_codice_lockout_apply_escalation(
        PDO $pdo,
        string $ip,
        string $email,
        array $settings,
        ?string $createdBy = null
    ): array {
        $blacklistAt = max(1, (int) ($settings['codice_blacklist_at_strike'] ?? 4));
        $ipRow = contest_security_codice_lockout_fetch($pdo, 'ip', $ip);
        $newStrike = max(1, (int) (($ipRow['strike_level'] ?? 0) + 1));

        if ($newStrike >= $blacklistAt) {
            if ($ip !== '') {
                contest_security_ip_add($pdo, $ip, 'Auto: troppi tentativi codice', $createdBy);
            }
            if ($email !== '') {
                email_blacklist_add($pdo, $email, 'Auto: troppi tentativi codice', $createdBy);
            }
            contest_security_log_event($pdo, 'codice_auto_blacklist', $email, $ip, 'strike=' . $newStrike);
            foreach (['ip' => $ip, 'email' => $email] as $type => $value) {
                if ($value === '') {
                    continue;
                }
                $pdo->prepare('
                    INSERT INTO codice_lockout (entity_type, entity_value, failure_count, window_started_at, strike_level, locked_until)
                    VALUES (:t, :v, 0, NULL, :s, NULL)
                    ON DUPLICATE KEY UPDATE failure_count = 0, window_started_at = NULL, strike_level = VALUES(strike_level), locked_until = NULL
                ')->execute([':t' => $type, ':v' => $value, ':s' => $newStrike]);
            }

            return ['locked' => false, 'blacklisted' => true, 'lock_seconds' => 0, 'strike_level' => $newStrike];
        }

        $duration = contest_security_codice_get_lockout_duration($newStrike, $settings);
        $lockedUntil = date('Y-m-d H:i:s', time() + $duration);
        foreach (['ip' => $ip, 'email' => $email] as $type => $value) {
            if ($value === '') {
                continue;
            }
            $pdo->prepare('
                INSERT INTO codice_lockout (entity_type, entity_value, failure_count, window_started_at, strike_level, locked_until)
                VALUES (:t, :v, 0, NULL, :s, :lu)
                ON DUPLICATE KEY UPDATE failure_count = 0, window_started_at = NULL, strike_level = VALUES(strike_level), locked_until = VALUES(locked_until)
            ')->execute([':t' => $type, ':v' => $value, ':s' => $newStrike, ':lu' => $lockedUntil]);
        }
        contest_security_log_event($pdo, 'codice_lockout', $email, $ip, 'strike=' . $newStrike . ',seconds=' . $duration);

        return ['locked' => true, 'blacklisted' => false, 'lock_seconds' => $duration, 'strike_level' => $newStrike];
    }
}

if (!function_exists('contest_security_codice_is_locked')) {
    /**
     * @return array{locked_until: int, seconds_remaining: int}|null
     */
    function contest_security_codice_is_locked(PDO $pdo, string $ip, string $email): ?array
    {
        $ip = trim($ip);
        $email = email_blacklist_normalize($email);
        $now = time();
        $latestUntil = 0;

        foreach (['ip' => $ip, 'email' => $email] as $type => $value) {
            if ($value === '') {
                continue;
            }
            $row = contest_security_codice_lockout_fetch($pdo, $type, $value);
            if ($row === null || empty($row['locked_until'])) {
                continue;
            }
            $until = strtotime((string) $row['locked_until']);
            if ($until !== false && $until > $now) {
                $latestUntil = max($latestUntil, $until);
            }
        }

        if ($latestUntil <= $now) {
            return null;
        }

        return [
            'locked_until' => $latestUntil,
            'seconds_remaining' => $latestUntil - $now,
        ];
    }
}

if (!function_exists('contest_security_codice_record_failure')) {
    /**
     * @return array{remaining: int, locked: bool, blacklisted: bool, lock_seconds: int}
     */
    function contest_security_codice_record_failure(PDO $pdo, string $ip, string $email): array
    {
        $settings = contest_security_get_settings($pdo);
        $max = max(1, (int) ($settings['codice_fail_max_per_window'] ?? 5));
        $window = max(60, (int) ($settings['codice_fail_window_seconds'] ?? 900));
        $ip = trim($ip);
        $email = email_blacklist_normalize($email);

        try {
            $pdo->beginTransaction();
            $ipCount = $ip !== '' ? contest_security_codice_lockout_increment($pdo, 'ip', $ip, $window) : 0;
            $emailCount = $email !== '' ? contest_security_codice_lockout_increment($pdo, 'email', $email, $window) : 0;
            $count = max($ipCount, $emailCount);
            contest_security_log_event($pdo, 'invalid_codice', $email, $ip, 'failures=' . $count);

            if ($count >= $max) {
                $escalation = contest_security_codice_lockout_apply_escalation($pdo, $ip, $email, $settings);
                $pdo->commit();

                return [
                    'remaining' => 0,
                    'locked' => !empty($escalation['locked']),
                    'blacklisted' => !empty($escalation['blacklisted']),
                    'lock_seconds' => (int) ($escalation['lock_seconds'] ?? 0),
                ];
            }

            $pdo->commit();

            return [
                'remaining' => max(0, $max - $count),
                'locked' => false,
                'blacklisted' => false,
                'lock_seconds' => 0,
            ];
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('contest_security_codice_record_failure: ' . $e->getMessage());

            return ['remaining' => 0, 'locked' => false, 'blacklisted' => false, 'lock_seconds' => 0];
        }
    }
}

if (!function_exists('contest_security_codice_reset_on_success')) {
    function contest_security_codice_reset_on_success(PDO $pdo, string $ip, string $email): void
    {
        contest_security_ensure_tables($pdo);
        $ip = trim($ip);
        $email = email_blacklist_normalize($email);
        $st = $pdo->prepare('
            UPDATE codice_lockout
            SET failure_count = 0, window_started_at = NULL, strike_level = 0, locked_until = NULL
            WHERE entity_type = :t AND entity_value = :v
        ');
        try {
            if ($ip !== '') {
                $st->execute([':t' => 'ip', ':v' => $ip]);
            }
            if ($email !== '') {
                $st->execute([':t' => 'email', ':v' => $email]);
            }
        } catch (Throwable $e) {
            error_log('contest_security_codice_reset_on_success: ' . $e->getMessage());
        }
    }
}

if (!function_exists('contest_security_codice_failure_is_countable')) {
    function contest_security_codice_failure_is_countable(string $fieldError): bool
    {
        if (str_contains($fieldError, 'è obbligatorio')) {
            return false;
        }
        if (str_contains($fieldError, 'Errore durante la validazione del codice')) {
            return false;
        }
        if (str_contains($fieldError, 'temporaneamente bloccato')) {
            return false;
        }
        if (str_contains($fieldError, 'Non è possibile completare la registrazione')) {
            return false;
        }

        return true;
    }
}

if (!function_exists('contest_security_codice_user_message')) {
  /**
     * @param array{label?: string, remaining?: int, seconds_remaining?: int, lock_seconds?: int} $ctx
     */
    function contest_security_codice_user_message(string $type, array $ctx = []): string
    {
        $label = trim((string) ($ctx['label'] ?? 'Codice partecipazione'));
        $assist = contest_security_assistance_footer_plain();

        return match ($type) {
            'remaining' => (function () use ($ctx, $label): string {
                $n = max(0, (int) ($ctx['remaining'] ?? 0));
                if ($n === 1) {
                    return '  Attenzione: ti resta 1 tentativo prima del blocco temporaneo.';
                }

                return '  Attenzione: ti restano ' . $n . ' tentativi prima del blocco temporaneo.';
            })(),
            'locked' => '- Accesso temporaneamente bloccato. Riprova tra '
                . contest_security_codice_format_wait((int) ($ctx['seconds_remaining'] ?? $ctx['lock_seconds'] ?? 0))
                . '.' . $assist,
            'blacklist', 'permanent_block' => '- Non è possibile completare la registrazione.' . $assist,
            'rate_limit' => '- Troppe richieste di registrazione. Riprova più tardi.' . $assist,
            'generic_codice' => '- Il campo "' . $label . '" è errato.',
            default => '- Il campo "' . $label . '" è errato.',
        };
    }
}

if (!function_exists('contest_security_registration_block_user_message')) {
    function contest_security_registration_block_user_message(
        string $blockCode,
        PDO $pdo,
        array $contest_config
    ): string {
        $label = trim((string) ($contest_config['instantwin_codice_label'] ?? ''));
        if ($label === '') {
            $label = 'Codice partecipazione';
        }
        $codiceActive = contest_security_codice_protection_active($pdo, $contest_config);
        if (!$codiceActive) {
            return contest_security_codice_user_message('generic_codice', ['label' => $label]);
        }
        if (in_array($blockCode, ['email_blacklist', 'domain_blocked', 'ip_blocked'], true)) {
            return contest_security_codice_user_message('permanent_block');
        }
        if (in_array($blockCode, ['rate_limit_ip', 'rate_limit_email', 'rate_limit_cooldown'], true)) {
            return contest_security_codice_user_message('rate_limit');
        }

        return contest_security_codice_user_message('generic_codice', ['label' => $label]);
    }
}

if (!function_exists('contest_security_log_count')) {
    function contest_security_log_count(PDO $pdo, string $search = ''): int
    {
        contest_security_ensure_tables($pdo);
        $search = trim($search);
        if ($search === '') {
            return (int) $pdo->query('SELECT COUNT(*) FROM security_event_log')->fetchColumn();
        }
        $st = $pdo->prepare('
            SELECT COUNT(*) FROM security_event_log
            WHERE event_type LIKE :s OR email_masked LIKE :s OR ip_address LIKE :s OR detail LIKE :s
        ');
        $st->execute([':s' => '%' . $search . '%']);

        return (int) $st->fetchColumn();
    }
}

if (!function_exists('contest_security_log_list')) {
    /**
     * @return list<array{id: int, event_type: string, email_masked: ?string, ip_address: ?string, detail: ?string, created_at: string}>
     */
    function contest_security_log_list(PDO $pdo, string $search = '', int $limit = 50, int $offset = 0): array
    {
        contest_security_ensure_tables($pdo);
        $limit = max(1, min(200, $limit));
        $offset = max(0, $offset);
        $search = trim($search);

        if ($search === '') {
            $stmt = $pdo->prepare('
                SELECT id, event_type, email_masked, ip_address, detail, created_at
                FROM security_event_log
                ORDER BY created_at DESC, id DESC
                LIMIT :limit OFFSET :offset
            ');
        } else {
            $stmt = $pdo->prepare('
                SELECT id, event_type, email_masked, ip_address, detail, created_at
                FROM security_event_log
                WHERE event_type LIKE :search OR email_masked LIKE :search
                   OR ip_address LIKE :search OR detail LIKE :search
                ORDER BY created_at DESC, id DESC
                LIMIT :limit OFFSET :offset
            ');
            $stmt->bindValue(':search', '%' . $search . '%', PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $rows = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $rows[] = [
                'id' => (int) ($row['id'] ?? 0),
                'event_type' => (string) ($row['event_type'] ?? ''),
                'email_masked' => isset($row['email_masked']) ? (string) $row['email_masked'] : null,
                'ip_address' => isset($row['ip_address']) ? (string) $row['ip_address'] : null,
                'detail' => isset($row['detail']) ? (string) $row['detail'] : null,
                'created_at' => (string) ($row['created_at'] ?? ''),
            ];
        }

        return $rows;
    }
}

if (!function_exists('contest_security_event_type_label')) {
    function contest_security_event_type_label(string $type): string
    {
        return match ($type) {
            'email_blacklist' => 'Email in blacklist',
            'domain_blocked' => 'Dominio bloccato',
            'ip_blocked' => 'IP bloccato',
            'rate_limit_ip' => 'Limite IP / giorno',
            'rate_limit_email' => 'Limite email / giorno',
            'rate_limit_cooldown' => 'Cooldown IP',
            'invalid_codice' => 'Codice non valido',
            'codice_lockout' => 'Blocco temporaneo codici',
            'codice_auto_blacklist' => 'Blacklist auto (codici)',
            default => $type,
        };
    }
}
