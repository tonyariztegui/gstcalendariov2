<?php

/**
 * Lettura / scrittura variabili INSTANTWIN_* nel file .env (manager).
 */

if (!function_exists('instantwin_manager_env_file_path')) {
    function instantwin_manager_env_file_path(): string
    {
        return dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . '.env';
    }
}

if (!function_exists('instantwin_manager_env_schema')) {
    /**
     * @return array<string, array<int, array<string, mixed>>>
     */
    function instantwin_manager_env_schema(): array
    {
        $dayHourFields = [];
        $dayLabels = [
            1 => 'Lunedì',
            2 => 'Martedì',
            3 => 'Mercoledì',
            4 => 'Giovedì',
            5 => 'Venerdì',
            6 => 'Sabato',
            7 => 'Domenica',
        ];
        foreach ($dayLabels as $d => $label) {
            $dayHourFields[] = [
                'key' => 'INSTANTWIN_DAY_' . $d . '_HOURS',
                'type' => 'day_hours',
                'label' => $label,
                'hint' => 'Formato: ora_inizio,ora_fine (es. 9,19)',
                'group' => 'day_hours',
            ];
        }

        return [
            'date' => [
                [
                    'key' => 'INSTANTWIN_ENABLE_CUSTOM_END_DATE',
                    'type' => 'bool',
                    'label' => 'Data fine personalizzata (diversa da content.json)',
                    'default' => 'false',
                ],
                [
                    'key' => 'INSTANTWIN_END_DATE',
                    'type' => 'date',
                    'label' => 'Data fine IW',
                    'default' => '',
                    'show_if' => 'INSTANTWIN_ENABLE_CUSTOM_END_DATE',
                ],
            ],
            'hours' => [
                [
                    'key' => 'INSTANTWIN_ENABLE_TIME_RESTRICTIONS',
                    'type' => 'bool',
                    'label' => 'Limita distribuzione a fascia oraria',
                    'default' => 'false',
                    'hint' => 'Disattivo = 24/7',
                ],
                [
                    'key' => 'INSTANTWIN_START_HOUR',
                    'type' => 'int',
                    'label' => 'Ora inizio (0–23)',
                    'default' => '9',
                    'min' => 0,
                    'max' => 23,
                    'show_if' => 'INSTANTWIN_ENABLE_TIME_RESTRICTIONS',
                ],
                [
                    'key' => 'INSTANTWIN_END_HOUR',
                    'type' => 'int',
                    'label' => 'Ora fine esclusiva (1–24)',
                    'default' => '24',
                    'min' => 1,
                    'max' => 24,
                    'show_if' => 'INSTANTWIN_ENABLE_TIME_RESTRICTIONS',
                ],
            ],
            'days' => [
                [
                    'key' => 'INSTANTWIN_ENABLE_DAY_CUSTOMIZATION',
                    'type' => 'bool',
                    'label' => 'Giorni e orari personalizzati per giorno della settimana',
                    'default' => 'false',
                ],
                [
                    'key' => 'INSTANTWIN_DISTRIBUTION_DAYS',
                    'type' => 'days_list',
                    'label' => 'Giorni attivi (1=lun … 7=dom)',
                    'default' => '1,2,3,4,5,6,7',
                    'show_if' => 'INSTANTWIN_ENABLE_DAY_CUSTOMIZATION',
                ],
            ],
            'day_hours' => $dayHourFields,
            'prize' => [
                [
                    'key' => 'INSTANTWIN_PRIZE_SELECTION_MODE',
                    'type' => 'select',
                    'label' => 'Selezione premio in vincita',
                    'default' => 'highest_value',
                    'options' => [
                        'highest_value' => 'Valore più alto',
                        'lowest_id' => 'ID premio più basso',
                        'random' => 'Casuale',
                    ],
                ],
            ],
            'quota' => [
                [
                    'key' => 'INSTANTWIN_QUOTA_DISTRIBUTION',
                    'type' => 'select',
                    'label' => 'Distribuzione quota giornaliera globale',
                    'default' => 'progressive',
                    'options' => [
                        'balanced' => 'Bilanciata (uguali per giorno)',
                        'progressive' => 'Progressiva (più all\'inizio)',
                    ],
                ],
                [
                    'key' => 'INSTANTWIN_QUOTA_COEF_START',
                    'type' => 'float',
                    'label' => 'Coefficiente primo giorno (progressive)',
                    'default' => '1.5',
                    'min' => 0.01,
                    'max' => 10,
                    'show_if_quota' => 'progressive',
                ],
                [
                    'key' => 'INSTANTWIN_QUOTA_COEF_END',
                    'type' => 'float',
                    'label' => 'Coefficiente ultimo giorno (progressive)',
                    'default' => '0.5',
                    'min' => 0.01,
                    'max' => 10,
                    'show_if_quota' => 'progressive',
                ],
            ],
            'engine' => [
                [
                    'key' => 'INSTANTWIN_ENGINE',
                    'type' => 'select',
                    'label' => 'Motore Instant Win',
                    'default' => 'v2',
                    'options' => [
                        'v2' => 'v2 (griglia slot + finestre)',
                        'legacy' => 'Legacy',
                    ],
                ],
                [
                    'key' => 'INSTANTWIN_MIN_SLOT_INTERVAL_SECONDS',
                    'type' => 'int',
                    'label' => 'Intervallo minimo tra vincite (secondi)',
                    'default' => '180',
                    'min' => 0,
                    'max' => 86400,
                ],
                [
                    'key' => 'INSTANTWIN_CATCHUP_HOURS_BEFORE_END',
                    'type' => 'int',
                    'label' => 'Catch-up: ore prima della fine fascia',
                    'default' => '2',
                    'min' => 1,
                    'max' => 24,
                ],
                [
                    'key' => 'INSTANTWIN_FIRST_FASCIA_RANDOM_ENABLED',
                    'type' => 'bool',
                    'label' => 'Sorteggio ad ogni tentativo di vincita sul slot',
                    'default' => 'true',
                    'hint' => 'Ogni partecipazione su uno slot aperto (1ª, 2ª, 3ª…) ha un esito casuale win/lose. Se disattivo: claim FCFS (il primo che trova lo slot vince).',
                ],
                [
                    'key' => 'INSTANTWIN_FIRST_FASCIA_WIN_PROBABILITY',
                    'type' => 'float',
                    'label' => 'Probabilità di vincita per tentativo (vuoto = 50%)',
                    'default' => '',
                    'min' => 0,
                    'max' => 1,
                    'hint' => 'Vuoto: 50% win / 50% lose a ogni tentativo. Es. 0.3 = 30% di vincita per giocata sullo slot.',
                ],
                [
                    'key' => 'INSTANTWIN_TEST_OUTCOME_MODE',
                    'type' => 'select',
                    'label' => 'Esito test senza calendario (solo test)',
                    'default' => 'off',
                    'hint' => 'Ignora slot e calendario: forza vincita o perdita a ogni partecipazione.',
                    'options' => [
                        'off' => 'Disattivato (calendario normale)',
                        'direct_win' => 'Vincita diretta',
                        'direct_loss' => 'Perdita diretta',
                    ],
                ],
            ],
            'pdv' => [
                [
                    'key' => 'INSTANTWIN_PDV_DAILY_PACING_ENABLED',
                    'type' => 'bool',
                    'label' => 'Pacing giornaliero soft per PDV',
                    'default' => 'true',
                ],
                [
                    'key' => 'INSTANTWIN_PDV_PACING_RELAX_DAYS',
                    'type' => 'int',
                    'label' => 'Giorni di rilassamento pacing (fine concorso)',
                    'default' => '3',
                    'min' => 0,
                    'max' => 365,
                    'show_if' => 'INSTANTWIN_PDV_DAILY_PACING_ENABLED',
                ],
            ],
            'winner_dedup' => [
                [
                    'key' => 'INSTANTWIN_ALLOW_MULTIPLE_WINS',
                    'type' => 'bool',
                    'label' => 'Consenti vittorie multiple per lo stesso utente',
                    'default' => 'false',
                    'hint' => 'Attivo: uno stesso email o fidelity può vincere più volte. Disattivo (default): ogni identità vince al massimo una volta.',
                ],
            ],
            'cedi' => [
                [
                    'key' => 'INSTANTWIN_CEDI_WEEK_BUDGET_CARRYOVER',
                    'type' => 'bool',
                    'label' => 'Riporto budget non assegnato alla settimana successiva',
                    'default' => 'true',
                    'hint' => 'All\'inizio di ogni settimana, i premi non distribuiti passano alla settimana seguente.',
                ],
            ],
        ];
    }
}

if (!function_exists('instantwin_manager_env_flat_keys')) {
    /** @return string[] */
    function instantwin_manager_env_flat_keys(): array
    {
        $keys = [];
        foreach (instantwin_manager_env_schema() as $fields) {
            foreach ($fields as $field) {
                $keys[] = (string) $field['key'];
            }
        }
        return $keys;
    }
}

if (!function_exists('instantwin_manager_env_parse_file')) {
    /**
     * @return array<string, string>
     */
    function instantwin_manager_env_parse_file(?string $path = null): array
    {
        $path = $path ?? instantwin_manager_env_file_path();
        $out = [];
        if (!is_readable($path)) {
            return $out;
        }
        $lines = file($path, FILE_IGNORE_NEW_LINES);
        if ($lines === false) {
            return $out;
        }
        foreach ($lines as $line) {
            $trim = trim($line);
            if ($trim === '' || $trim[0] === '#') {
                continue;
            }
            if (strpos($trim, '=') === false) {
                continue;
            }
            [$k, $v] = explode('=', $trim, 2);
            $k = trim($k);
            $v = trim($v);
            if (($p = strpos($v, ' #')) !== false) {
                $v = trim(substr($v, 0, $p));
            } elseif (($p = strpos($v, "\t#")) !== false) {
                $v = trim(substr($v, 0, $p));
            }
            $out[$k] = $v;
        }
        return $out;
    }
}

if (!function_exists('instantwin_manager_env_values_for_form')) {
    /**
     * Valori per il form (file .env, poi getenv).
     *
     * @return array<string, string>
     */
    function instantwin_manager_env_values_for_form(): array
    {
        $parsed = instantwin_manager_env_parse_file();
        $values = [];
        foreach (instantwin_manager_env_flat_keys() as $key) {
            if (array_key_exists($key, $parsed)) {
                $values[$key] = (string) $parsed[$key];
                continue;
            }
            $g = getenv($key);
            if ($g !== false && $g !== '') {
                $values[$key] = (string) $g;
            }
        }
        $mode = strtolower(trim((string) ($values['INSTANTWIN_TEST_OUTCOME_MODE'] ?? '')));
        if (!in_array($mode, ['direct_win', 'direct_loss'], true)) {
            $legacyDirect = instantwin_manager_env_normalize_bool($values['INSTANTWIN_LEGACY_DIRECT_WIN'] ?? 'false');
            $values['INSTANTWIN_TEST_OUTCOME_MODE'] = $legacyDirect === 'true' ? 'direct_win' : 'off';
        }
        return $values;
    }
}

if (!function_exists('instantwin_manager_env_normalize_bool')) {
    function instantwin_manager_env_normalize_bool($raw): string
    {
        if (is_bool($raw)) {
            return $raw ? 'true' : 'false';
        }
        $s = strtolower(trim((string) $raw));
        if (in_array($s, ['1', 'true', 'yes', 'on'], true)) {
            return 'true';
        }
        return 'false';
    }
}

if (!function_exists('instantwin_manager_env_validate_submission')) {
    /**
     * @param array<string, mixed> $in
     * @return array{ok: bool, message?: string, values?: array<string, string>}
     */
    function instantwin_manager_env_validate_submission(array $in, ?array $keysFilter = null): array
    {
        $out = [];
        $schemaByKey = [];
        foreach (instantwin_manager_env_schema() as $fields) {
            foreach ($fields as $field) {
                $schemaByKey[$field['key']] = $field;
            }
        }

        $keys = $keysFilter ?? instantwin_manager_env_flat_keys();
        foreach ($keys as $key) {
            $field = $schemaByKey[$key] ?? null;
            if ($field === null) {
                continue;
            }
            $type = (string) ($field['type'] ?? 'string');
            $raw = $in[$key] ?? null;

            if ($type === 'bool') {
                $out[$key] = instantwin_manager_env_normalize_bool($raw);
                continue;
            }

            if ($type === 'int') {
                if ($raw === null || (is_string($raw) && trim($raw) === '')) {
                    $out[$key] = (string) ($field['default'] ?? '0');
                    continue;
                }
                $v = filter_var(is_string($raw) ? str_replace(',', '.', trim($raw)) : $raw, FILTER_VALIDATE_INT);
                if ($v === false) {
                    return ['ok' => false, 'message' => 'Valore non valido per ' . $key];
                }
                $min = isset($field['min']) ? (int) $field['min'] : null;
                $max = isset($field['max']) ? (int) $field['max'] : null;
                if ($min !== null && $v < $min) {
                    return ['ok' => false, 'message' => $key . ' deve essere ≥ ' . $min];
                }
                if ($max !== null && $v > $max) {
                    return ['ok' => false, 'message' => $key . ' deve essere ≤ ' . $max];
                }
                $out[$key] = (string) $v;
                continue;
            }

            if ($type === 'float') {
                $trimmed = is_string($raw) ? str_replace(',', '.', trim($raw)) : $raw;
                if ($trimmed === null || $trimmed === '') {
                    if (($field['default'] ?? null) === '') {
                        $out[$key] = '';
                        continue;
                    }
                    $trimmed = (string) ($field['default'] ?? '0');
                }
                $v = filter_var($trimmed, FILTER_VALIDATE_FLOAT);
                if ($v === false) {
                    return ['ok' => false, 'message' => 'Valore non valido per ' . $key];
                }
                $min = isset($field['min']) ? (float) $field['min'] : null;
                $max = isset($field['max']) ? (float) $field['max'] : null;
                if ($min !== null && $v < $min) {
                    return ['ok' => false, 'message' => $key . ' deve essere ≥ ' . $min];
                }
                if ($max !== null && $v > $max) {
                    return ['ok' => false, 'message' => $key . ' deve essere ≤ ' . $max];
                }
                $out[$key] = rtrim(rtrim(sprintf('%.4F', $v), '0'), '.');
                continue;
            }

            if ($type === 'date') {
                $s = trim((string) ($raw ?? ''));
                if ($s === '') {
                    $out[$key] = '';
                    continue;
                }
                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $s)) {
                    return ['ok' => false, 'message' => 'Data non valida per ' . $key . ' (YYYY-MM-DD)'];
                }
                $out[$key] = $s;
                continue;
            }

            if ($type === 'days_list') {
                if (is_array($raw)) {
                    $days = array_map('intval', $raw);
                } else {
                    $days = array_map('intval', array_map('trim', explode(',', (string) $raw)));
                }
                $days = array_values(array_unique(array_filter($days, static function ($d) {
                    return $d >= 1 && $d <= 7;
                })));
                sort($days);
                if ($days === []) {
                    return ['ok' => false, 'message' => 'Seleziona almeno un giorno per ' . $key];
                }
                $out[$key] = implode(',', $days);
                continue;
            }

            if ($type === 'day_hours') {
                $s = trim((string) ($raw ?? ''));
                if ($s === '') {
                    $out[$key] = (string) ($field['default'] ?? '9,19');
                    continue;
                }
                $parts = array_map('intval', array_map('trim', explode(',', $s)));
                if (count($parts) !== 2 || $parts[0] < 0 || $parts[0] > 23 || $parts[1] < 1 || $parts[1] > 24) {
                    return ['ok' => false, 'message' => 'Orari non validi per ' . $key . ' (es. 9,19)'];
                }
                $out[$key] = $parts[0] . ',' . $parts[1];
                continue;
            }

            if ($type === 'select') {
                $s = strtolower(trim((string) ($raw ?? '')));
                $opts = array_keys((array) ($field['options'] ?? []));
                if (!in_array($s, $opts, true)) {
                    return ['ok' => false, 'message' => 'Opzione non valida per ' . $key];
                }
                $out[$key] = $s;
                continue;
            }

            $out[$key] = trim((string) ($raw ?? ''));
        }

        if (($out['INSTANTWIN_ENABLE_CUSTOM_END_DATE'] ?? 'false') === 'true') {
            if (trim($out['INSTANTWIN_END_DATE'] ?? '') === '') {
                return ['ok' => false, 'message' => 'Indica INSTANTWIN_END_DATE se la data fine personalizzata è attiva.'];
            }
        }

        $testMode = strtolower(trim((string) ($out['INSTANTWIN_TEST_OUTCOME_MODE'] ?? 'off')));
        if (!in_array($testMode, ['off', 'direct_win', 'direct_loss'], true)) {
            $testMode = 'off';
        }
        $out['INSTANTWIN_TEST_OUTCOME_MODE'] = $testMode;
        $out['INSTANTWIN_LEGACY_DIRECT_WIN'] = $testMode === 'direct_win' ? 'true' : 'false';

        return ['ok' => true, 'values' => $out];
    }
}

if (!function_exists('instantwin_manager_env_write')) {
    /**
     * @param array<string, string> $values
     */
    function instantwin_manager_env_write(array $values): bool
    {
        $path = instantwin_manager_env_file_path();
        if (!is_readable($path) || !is_writable($path)) {
            return false;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES);
        if ($lines === false) {
            return false;
        }

        $keysLeft = array_fill_keys(array_keys($values), true);
        $newLines = [];

        foreach ($lines as $line) {
            $trim = ltrim($line);
            if ($trim === '' || $trim[0] === '#') {
                $newLines[] = $line;
                continue;
            }
            if (strpos($trim, '=') === false) {
                $newLines[] = $line;
                continue;
            }
            $eq = strpos($trim, '=');
            $k = trim(substr($trim, 0, $eq));
            if (isset($values[$k])) {
                $suffix = '';
                if (($hash = strpos($trim, '#')) !== false) {
                    $suffix = ' ' . ltrim(substr($trim, $hash));
                }
                $newLines[] = $k . '=' . $values[$k] . $suffix;
                unset($keysLeft[$k]);
            } else {
                $newLines[] = $line;
            }
        }

        foreach (array_keys($keysLeft) as $k) {
            $newLines[] = $k . '=' . $values[$k];
        }

        $content = implode("\n", $newLines);
        if ($content !== '' && substr($content, -1) !== "\n") {
            $content .= "\n";
        }

        return file_put_contents($path, $content, LOCK_EX) !== false;
    }
}

if (!function_exists('instantwin_manager_env_apply_runtime')) {
    function instantwin_manager_env_apply_runtime(string $name, string $value): void
    {
        if (function_exists('config_apply_env_pair')) {
            config_apply_env_pair($name, $value, true);
            return;
        }
        putenv($name . '=' . $value);
        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;
    }
}

if (!function_exists('instantwin_manager_env_sync_app_env_from_published')) {
    /**
     * Sync APP_ENV from Concorso Pubblicato: Sì → production, No → local.
     * Never overwrites testing.
     *
     * @return array{ok:bool,skipped:bool,value:?string,message:string}
     */
    function instantwin_manager_env_sync_app_env_from_published(bool $published): array
    {
        $target = $published ? 'production' : 'local';
        $path = instantwin_manager_env_file_path();
        $parsed = instantwin_manager_env_parse_file($path);
        $fromFile = isset($parsed['APP_ENV']) ? (string) $parsed['APP_ENV'] : null;
        $fromEnv = getenv('APP_ENV');
        $current = is_string($fromEnv) && $fromEnv !== '' ? $fromEnv : ($fromFile ?? '');

        if ($current === 'testing' || $fromFile === 'testing') {
            return [
                'ok' => true,
                'skipped' => true,
                'value' => 'testing',
                'message' => '',
            ];
        }

        if (!is_file($path)) {
            return [
                'ok' => false,
                'skipped' => false,
                'value' => null,
                'message' => 'Attenzione: file .env non trovato — APP_ENV non aggiornato.',
            ];
        }

        if (!is_readable($path)) {
            return [
                'ok' => false,
                'skipped' => false,
                'value' => null,
                'message' => 'Attenzione: file .env non leggibile — APP_ENV non aggiornato.',
            ];
        }

        if ($fromFile === $target) {
            instantwin_manager_env_apply_runtime('APP_ENV', $target);
            return [
                'ok' => true,
                'skipped' => true,
                'value' => $target,
                'message' => '',
            ];
        }

        if (!is_writable($path)) {
            return [
                'ok' => false,
                'skipped' => false,
                'value' => null,
                'message' => 'Attenzione: .env non scrivibile — APP_ENV non aggiornato (Pubblicato salvato comunque).',
            ];
        }

        if (!instantwin_manager_env_write(['APP_ENV' => $target])) {
            return [
                'ok' => false,
                'skipped' => false,
                'value' => null,
                'message' => 'Attenzione: errore scrittura .env — APP_ENV non aggiornato (Pubblicato salvato comunque).',
            ];
        }

        instantwin_manager_env_apply_runtime('APP_ENV', $target);

        return [
            'ok' => true,
            'skipped' => false,
            'value' => $target,
            'message' => 'APP_ENV aggiornato a ' . $target . ' (sincronizzato con Pubblicato).',
        ];
    }
}
