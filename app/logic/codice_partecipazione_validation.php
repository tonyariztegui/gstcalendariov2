<?php

/**
 * Validazione codice partecipazione (combinazione 13 cifre ABBCCCDEEEFGG + modalità pool).
 * Usato da do_registration.php e scripts/simulate_players.
 */

if (!function_exists('codice_partecipazione_normalize')) {
    function codice_partecipazione_normalize(string $raw): string
    {
        $raw = trim($raw);
        return (string) preg_replace('/\s+/u', '', $raw);
    }
}

if (!function_exists('codice_partecipazione_validate_base')) {
    /**
     * Plages A…GG sans PDV formulaire ni somma GG (mode registration false / legacy).
     *
     * @return null si OK, sinon code interne (format, cedi, promo_day, …)
     */
    function codice_partecipazione_validate_base(string $codice): ?string
    {
        if (!preg_match('/^\d{13}$/', $codice)) {
            return 'format';
        }

        $cedi = (int) substr($codice, 0, 1);
        $promo_day = (int) substr($codice, 1, 2);
        $pdv = (int) substr($codice, 3, 3);
        $cassa = (int) substr($codice, 6, 1);
        $scontrino = (int) substr($codice, 7, 3);
        $random = (int) substr($codice, 10, 1);
        $check = (int) substr($codice, 11, 2);

        if ($cedi < 1 || $cedi > 4) {
            return 'cedi';
        }
        if ($promo_day < 1 || $promo_day > 42) {
            return 'promo_day';
        }
        if ($pdv < 1 || $pdv > 999) {
            return 'pdv';
        }
        if ($cassa < 1 || $cassa > 9) {
            return 'cassa';
        }
        if ($scontrino < 1 || $scontrino > 999) {
            return 'scontrino';
        }
        if ($random < 0 || $random > 9) {
            return 'random';
        }
        if ($check < 0 || $check > 99) {
            return 'check';
        }

        return null;
    }
}

if (!function_exists('validate_codice_partecipazione_structure_ranges')) {
    /** @deprecated Alias — utiliser codice_partecipazione_validate_base(). */
    function validate_codice_partecipazione_structure_ranges(string $codice): ?string
    {
        return codice_partecipazione_validate_base($codice);
    }
}

if (!function_exists('codice_partecipazione_pdv_to_int')) {
    function codice_partecipazione_pdv_to_int(string $pdvCodice): ?int
    {
        $digits = preg_replace('/\D/', '', trim($pdvCodice));
        if ($digits === '') {
            return null;
        }
        $n = (int) $digits;
        if ($n < 1 || $n > 999) {
            return null;
        }
        return $n;
    }
}

if (!function_exists('codice_partecipazione_ccc_matches_pdv')) {
    function codice_partecipazione_ccc_matches_pdv(string $codice, string $pdvCodice): bool
    {
        if (!preg_match('/^\d{13}$/', $codice)) {
            return false;
        }
        $pdvInt = codice_partecipazione_pdv_to_int($pdvCodice);
        if ($pdvInt === null) {
            return false;
        }
        return (int) substr($codice, 3, 3) === $pdvInt;
    }
}

if (!function_exists('codice_partecipazione_gg_expected_sum')) {
    /** Somme des 11 premiers chiffres (un par un). */
    function codice_partecipazione_gg_expected_sum(string $codice): ?int
    {
        if (!preg_match('/^\d{13}$/', $codice)) {
            return null;
        }
        $sum = 0;
        for ($i = 0; $i < 11; $i++) {
            $sum += (int) $codice[$i];
        }
        return $sum;
    }
}

if (!function_exists('codice_partecipazione_gg_checksum_valid')) {
    function codice_partecipazione_gg_checksum_valid(string $codice): bool
    {
        $expected = codice_partecipazione_gg_expected_sum($codice);
        if ($expected === null || $expected > 99) {
            return false;
        }
        return (int) substr($codice, 11, 2) === $expected;
    }
}

if (!function_exists('codice_partecipazione_validate')) {
    /**
     * @param bool $strict true = base + checksum GG
     * @return null si OK, sinon code (format, cedi, gg_checksum, …)
     */
    function codice_partecipazione_validate(string $codice, bool $strict, string $pdvCodice = ''): ?string
    {
        $codice = codice_partecipazione_normalize($codice);
        if (!preg_match('/^\d{13}$/', $codice)) {
            return 'format';
        }

        $baseErr = codice_partecipazione_validate_base($codice);
        if ($baseErr !== null) {
            return $baseErr;
        }

        if (!$strict) {
            return null;
        }

        if (!codice_partecipazione_gg_checksum_valid($codice)) {
            return 'gg_checksum';
        }

        return null;
    }
}

if (!function_exists('codice_partecipazione_resolve_mode')) {
    /**
     * Modalità codice partecipazione: pool | direct_win | combinazione.
     *
     * @param array<string, mixed> $contest_config
     */
    function codice_partecipazione_resolve_mode(array $contest_config): string
    {
        $mode = strtolower(trim((string) ($contest_config['codice_partecipazione_mode'] ?? '')));
        if (in_array($mode, ['pool', 'direct_win', 'combinazione'], true)) {
            return $mode;
        }

        $legacy = strtolower(trim((string) ($contest_config['instantwin_codice_mode'] ?? 'instantwin')));
        if ($legacy === 'direct_win') {
            return 'direct_win';
        }

        return 'pool';
    }
}

if (!function_exists('codice_partecipazione_mode_requires_pool')) {
    function codice_partecipazione_mode_requires_pool(string $mode): bool
    {
        return in_array($mode, ['pool', 'direct_win'], true);
    }
}

if (!function_exists('codice_partecipazione_mode_is_combinazione')) {
    function codice_partecipazione_mode_is_combinazione(string $mode): bool
    {
        return $mode === 'combinazione';
    }
}

if (!function_exists('codice_partecipazione_strict_validation_enabled')) {
    function codice_partecipazione_strict_validation_enabled(): bool
    {
        global $contest_config;
        if (is_array($contest_config) && array_key_exists('codice_partecipazione_strict_validation', $contest_config)) {
            return !empty($contest_config['codice_partecipazione_strict_validation']);
        }

        return false;
    }
}

if (!function_exists('codice_partecipazione_uses_ticket_format')) {
    /** @deprecated Utiliser codice_partecipazione_mode_is_combinazione(). */
    function codice_partecipazione_uses_ticket_format(int $codiceLen): bool
    {
        return $codiceLen === 13;
    }
}

if (!function_exists('registration_resolve_codice_partecipazione_length')) {
    function registration_resolve_codice_partecipazione_length(?PDO $c_pdo, array $contest_config): int
    {
        $mode = codice_partecipazione_resolve_mode($contest_config);
        if (codice_partecipazione_mode_is_combinazione($mode)) {
            return 13;
        }

        $codice_len = 0;
        $use_auto_len = !empty($contest_config['instantwin_codice_length_auto']);
        if ($use_auto_len && $c_pdo instanceof PDO) {
            try {
                $stmtLen = $c_pdo->query("SELECT CHAR_LENGTH(codice) AS len FROM codici_partecipazione WHERE isActive = 1 AND codice IS NOT NULL AND codice <> '' LIMIT 1");
                $rowLen = $stmtLen ? ($stmtLen->fetch(PDO::FETCH_ASSOC) ?: null) : null;
                $codice_len = (int) ($rowLen['len'] ?? 0);
            } catch (Throwable $e) {
                $codice_len = 0;
            }
        }
        if ($codice_len < 1) {
            $codice_len = (int) ($contest_config['instantwin_codice_length'] ?? 0);
        }
        if ($codice_len < 1) {
            $codice_len = 12;
        }

        return min(255, max(1, $codice_len));
    }
}

if (!function_exists('codice_partecipazione_is_already_used')) {
    function codice_partecipazione_is_already_used(PDO $pdo, string $codice): bool
    {
        $stmt = $pdo->prepare('
            SELECT id
            FROM partecipazioni
            WHERE codice_partecipazione = :codice
              AND COALESCE(annullato, 0) = 0
            LIMIT 1
        ');
        $stmt->bindValue(':codice', $codice, PDO::PARAM_STR);
        $stmt->execute();

        return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

if (!function_exists('registration_validate_codice_partecipazione_submit')) {
    function registration_validate_codice_partecipazione_submit(
        string $codice_partecipazione,
        string $labelCodice,
        bool $codice_partecipazione_required,
        array $contest_config,
        string $pdvCodice,
        ?PDO &$c_pdo,
        array &$errors,
        array &$field_errors
    ): void {
        $mode = codice_partecipazione_resolve_mode($contest_config);
        $codice_len = registration_resolve_codice_partecipazione_length($c_pdo, $contest_config);

        if ($codice_partecipazione_required && $codice_partecipazione === '') {
            $errors[] = '- Il campo "' . $labelCodice . '" è obbligatorio.';
            $field_errors['codice_partecipazione'] = '- Il campo "' . $labelCodice . '" è obbligatorio.';
            return;
        }
        if ($codice_partecipazione === '') {
            return;
        }

        if (codice_partecipazione_mode_is_combinazione($mode)) {
            if (mb_strlen($codice_partecipazione, 'UTF-8') !== 13) {
                $errors[] = '- Il campo "' . $labelCodice . '" è errato.';
                $field_errors['codice_partecipazione'] = '- Il campo "' . $labelCodice . '" è errato.';
                return;
            }
            $structureError = codice_partecipazione_validate(
                $codice_partecipazione,
                codice_partecipazione_strict_validation_enabled(),
                $pdvCodice
            );
            if ($structureError !== null) {
                $errors[] = '- Il campo "' . $labelCodice . '" è errato.';
                $field_errors['codice_partecipazione'] = '- Il campo "' . $labelCodice . '" è errato.';
                return;
            }
        } elseif (mb_strlen($codice_partecipazione, 'UTF-8') !== $codice_len) {
            $errors[] = '- Il campo "' . $labelCodice . '" è errato.';
            $field_errors['codice_partecipazione'] = '- Il campo "' . $labelCodice . '" è errato.';
            return;
        }

        try {
            if ($c_pdo === null) {
                $c_pdo = conn_pdo();
            }
            if (!$c_pdo instanceof PDO) {
                return;
            }

            if (codice_partecipazione_mode_requires_pool($mode)) {
                $stmt_check_code = $c_pdo->prepare('
                    SELECT id, codice, isActive, used
                    FROM codici_partecipazione
                    WHERE codice = :codice
                    LIMIT 1
                ');
                $stmt_check_code->bindValue(':codice', $codice_partecipazione, PDO::PARAM_STR);
                $stmt_check_code->execute();
                $code_row = $stmt_check_code->fetch(PDO::FETCH_ASSOC);

                if (!$code_row) {
                    $errors[] = '- Il campo "' . $labelCodice . '" non è valido.';
                    $field_errors['codice_partecipazione'] = '- Il campo "' . $labelCodice . '" non è valido.';
                } elseif ((int) $code_row['isActive'] !== 1) {
                    $errors[] = '- Il campo "' . $labelCodice . '" non è valido.';
                    $field_errors['codice_partecipazione'] = '- Il campo "' . $labelCodice . '" non è valido.';
                } elseif ((int) $code_row['used'] === 1) {
                    $errors[] = '- Il campo "' . $labelCodice . '" è già stato utilizzato.';
                    $field_errors['codice_partecipazione'] = '- Il campo "' . $labelCodice . '" è già stato utilizzato.';
                }
            } elseif (codice_partecipazione_is_already_used($c_pdo, $codice_partecipazione)) {
                $errors[] = '- Il campo "' . $labelCodice . '" è già stato utilizzato.';
                $field_errors['codice_partecipazione'] = '- Il campo "' . $labelCodice . '" è già stato utilizzato.';
            }
        } catch (Throwable $e) {
            error_log('Errore validazione codice_partecipazione: ' . $e->getMessage());
            $errors[] = '- Errore durante la validazione del codice. Riprova.';
            $field_errors['codice_partecipazione'] = '- Errore durante la validazione del codice. Riprova.';
        }
    }
}
