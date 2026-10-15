<?php
/**
 * Statistiche PDV per il manager (partecipazioni / vincite per periodo e magazzino).
 * Indipendente dalla cartella scripts/ e dagli strumenti di simulazione.
 */

if (!function_exists('stats_pdv_export_column_defs')) {
    /**
     * Colonne PDV esportabili (ordine fisso) — filtrate su colonne realmente presenti in DB.
     *
     * @return array<string, string> chiave colonna => intestazione Excel
     */
    function stats_pdv_export_column_defs(): array
    {
        return [
            'codice' => 'Codice',
            'codice_pdv' => 'Codice PDV',
            'ragsoc' => 'Ragione sociale',
            'insegna' => 'Insegna',
            'indirizzo' => 'Indirizzo',
            'citta' => 'Città',
            'cap' => 'CAP',
            'prov' => 'Provincia',
            'regione' => 'Regione',
        ];
    }
}

if (!function_exists('stats_pdv_punti_vendita_columns')) {
    /**
     * @return list<string>
     */
    function stats_pdv_punti_vendita_columns(PDO $pdo): array
    {
        static $cache = [];
        $cacheKey = spl_object_id($pdo);
        if (isset($cache[$cacheKey])) {
            return $cache[$cacheKey];
        }

        $wanted = array_keys(stats_pdv_export_column_defs());
        $existing = [];
        try {
            $st = $pdo->query('SHOW COLUMNS FROM `punti_vendita`');
            while ($st && ($row = $st->fetch(PDO::FETCH_ASSOC))) {
                $existing[] = strtolower((string) ($row['Field'] ?? ''));
            }
        } catch (Throwable $e) {
            error_log('stats_pdv_punti_vendita_columns: ' . $e->getMessage());
            $cache[$cacheKey] = ['codice', 'ragsoc', 'insegna', 'citta'];

            return $cache[$cacheKey];
        }

        $cache[$cacheKey] = array_values(array_filter(
            $wanted,
            static fn(string $col): bool => in_array($col, $existing, true)
        ));

        return $cache[$cacheKey];
    }
}

if (!function_exists('stats_pdv_has_pdv_cedi_column')) {
    function stats_pdv_has_pdv_cedi_column(PDO $pdo): bool
    {
        static $cache = [];
        $cacheKey = spl_object_id($pdo);
        if (array_key_exists($cacheKey, $cache)) {
            return $cache[$cacheKey];
        }
        try {
            $cache[$cacheKey] = $pdo->query("SHOW COLUMNS FROM `punti_vendita` LIKE 'codice_cedi'")->fetch(PDO::FETCH_ASSOC) !== false;
        } catch (Throwable $e) {
            $cache[$cacheKey] = false;
        }

        return $cache[$cacheKey];
    }
}

if (!function_exists('stats_pdv_punti_vendita_has_column')) {
    function stats_pdv_punti_vendita_has_column(PDO $pdo, string $column): bool
    {
        static $cache = [];
        $cacheKey = spl_object_id($pdo);
        if (!isset($cache[$cacheKey])) {
            $cache[$cacheKey] = [];
            try {
                $st = $pdo->query('SHOW COLUMNS FROM `punti_vendita`');
                while ($st && ($row = $st->fetch(PDO::FETCH_ASSOC))) {
                    $cache[$cacheKey][strtolower((string) ($row['Field'] ?? ''))] = true;
                }
            } catch (Throwable $e) {
                error_log('stats_pdv_punti_vendita_has_column: ' . $e->getMessage());
            }
        }

        return !empty($cache[$cacheKey][strtolower($column)]);
    }
}

if (!function_exists('stats_pdv_format_cedi_select_label')) {
    function stats_pdv_format_cedi_select_label(string $code, string $ragsoc = ''): string
    {
        $code = trim($code);
        $ragsoc = trim($ragsoc);
        if ($code === '') {
            return '';
        }
        if ($ragsoc !== '') {
            return $code . ' — ' . $ragsoc;
        }

        return $code;
    }
}

if (!function_exists('stats_pdv_join_ragsoc_labels')) {
    /**
     * @param list<string> $names
     */
    function stats_pdv_join_ragsoc_labels(array $names, int $maxShown = 4): string
    {
        $clean = [];
        foreach ($names as $name) {
            $name = trim((string) $name);
            if ($name !== '' && !in_array($name, $clean, true)) {
                $clean[] = $name;
            }
        }
        if ($clean === []) {
            return '';
        }
        usort($clean, static fn(string $a, string $b): int => strcasecmp($a, $b));
        if (count($clean) > $maxShown) {
            $head = array_slice($clean, 0, $maxShown);
            $extra = count($clean) - $maxShown;

            return implode(' · ', $head) . ' (+' . $extra . ')';
        }

        return implode(' · ', $clean);
    }
}

if (!function_exists('stats_pdv_load_cedi_ragsoc_map')) {
    /**
     * Ragioni sociali distinte per codice_cedi (un magazzino può avere più PDV).
     *
     * @return array<string, string> codice_cedi => etichetta ragioni sociali unite
     */
    function stats_pdv_load_cedi_ragsoc_map(PDO $pdo): array
    {
        $out = [];
        if (!stats_pdv_has_pdv_cedi_column($pdo) || !stats_pdv_punti_vendita_has_column($pdo, 'ragsoc')) {
            return $out;
        }

        $grouped = [];
        try {
            $st = $pdo->query("
                SELECT TRIM(`codice_cedi`) AS `c`,
                       NULLIF(TRIM(`ragsoc`), '') AS `ragsoc`
                FROM `punti_vendita`
                WHERE `codice_cedi` IS NOT NULL AND TRIM(`codice_cedi`) <> ''
                ORDER BY TRIM(`codice_cedi`) ASC, TRIM(`ragsoc`) ASC
            ");
            while ($st && ($r = $st->fetch(PDO::FETCH_ASSOC))) {
                $c = trim((string) ($r['c'] ?? ''));
                if ($c === '') {
                    continue;
                }
                if (!isset($grouped[$c])) {
                    $grouped[$c] = [];
                }
                $name = trim((string) ($r['ragsoc'] ?? ''));
                if ($name !== '' && !in_array($name, $grouped[$c], true)) {
                    $grouped[$c][] = $name;
                }
            }
        } catch (Throwable $e) {
            error_log('stats_pdv_load_cedi_ragsoc_map: ' . $e->getMessage());
        }

        foreach ($grouped as $code => $names) {
            $out[$code] = stats_pdv_join_ragsoc_labels($names);
        }
        ksort($out, SORT_NATURAL | SORT_FLAG_CASE);

        return $out;
    }
}

if (!function_exists('stats_pdv_load_cedi_options')) {
    /**
     * Filtro CEDI: codice_cedi => « codice — ragione sociale ».
     *
     * @return array<string, string> codice_cedi => etichetta select
     */
    function stats_pdv_load_cedi_options(PDO $pdo, ?array $contestConfig = null): array
    {
        unset($contestConfig);

        $ragsocMap = stats_pdv_load_cedi_ragsoc_map($pdo);
        $out = [];
        foreach ($ragsocMap as $code => $ragsoc) {
            $out[$code] = stats_pdv_format_cedi_select_label($code, $ragsoc);
        }

        if ($out === [] && stats_pdv_has_pdv_cedi_column($pdo)) {
            try {
                $st = $pdo->query("
                    SELECT DISTINCT TRIM(`codice_cedi`) AS `c`
                    FROM `punti_vendita`
                    WHERE `codice_cedi` IS NOT NULL AND TRIM(`codice_cedi`) <> ''
                    ORDER BY `c` ASC
                ");
                while ($st && ($r = $st->fetch(PDO::FETCH_ASSOC))) {
                    $c = trim((string) ($r['c'] ?? ''));
                    if ($c !== '') {
                        $out[$c] = stats_pdv_format_cedi_select_label($c);
                    }
                }
            } catch (Throwable $e) {
                error_log('stats_pdv_load_cedi_options: ' . $e->getMessage());
            }
        }

        if ($out === []) {
            try {
                $hasCodice = $pdo->query("SHOW COLUMNS FROM `premi` LIKE 'codice_cedi'")->fetch(PDO::FETCH_ASSOC) !== false;
                if ($hasCodice) {
                    $st = $pdo->query("
                        SELECT DISTINCT TRIM(`codice_cedi`) AS `c`
                        FROM `premi`
                        WHERE `codice_cedi` IS NOT NULL AND TRIM(`codice_cedi`) <> ''
                        ORDER BY `c` ASC
                    ");
                    while ($st && ($r = $st->fetch(PDO::FETCH_ASSOC))) {
                        $c = trim((string) ($r['c'] ?? ''));
                        if ($c !== '') {
                            $out[$c] = stats_pdv_format_cedi_select_label($c);
                        }
                    }
                }
            } catch (Throwable $e) {
                error_log('stats_pdv_load_cedi_options premi: ' . $e->getMessage());
            }
        }

        return $out;
    }
}

if (!function_exists('stats_pdv_trim_field')) {
    function stats_pdv_trim_field($value): string
    {
        return trim((string) ($value ?? ''));
    }
}

if (!function_exists('stats_pdv_load_meta')) {
    /**
     * @return array<string, array<string, string>>
     */
    function stats_pdv_load_meta(PDO $pdo): array
    {
        $out = [];
        $columns = stats_pdv_punti_vendita_columns($pdo);
        $hasCediCol = stats_pdv_has_pdv_cedi_column($pdo);
        $selectParts = ['TRIM(`codice`) AS `c`'];
        if ($hasCediCol) {
            $selectParts[] = "NULLIF(TRIM(`codice_cedi`), '') AS `codice_cedi`";
        }
        foreach ($columns as $col) {
            if ($col === 'codice') {
                continue;
            }
            $selectParts[] = "NULLIF(TRIM(`{$col}`), '') AS `{$col}`";
        }

        try {
            $sql = "
                SELECT " . implode(",\n                       ", $selectParts) . "
                FROM `punti_vendita`
                WHERE `active` = 1 AND `codice` IS NOT NULL AND TRIM(`codice`) <> ''
                ORDER BY COALESCE(NULLIF(TRIM(`ragsoc`), ''), NULLIF(TRIM(`insegna`), ''), '') ASC,
                         TRIM(`codice`) ASC
            ";
            $st = $pdo->query($sql);
            if (!$st) {
                return [];
            }
            while ($r = $st->fetch(PDO::FETCH_ASSOC)) {
                $r = array_change_key_case($r, CASE_LOWER);
                $c = stats_pdv_trim_field($r['c'] ?? '');
                if ($c === '') {
                    continue;
                }
                $nome = stats_pdv_trim_field($r['ragsoc'] ?? '');
                if ($nome === '') {
                    $nome = stats_pdv_trim_field($r['insegna'] ?? '');
                }
                $entry = [
                    'cod' => $c,
                    'codice' => $c,
                    'label' => $nome !== '' ? $nome : $c,
                ];
                foreach ($columns as $col) {
                    if ($col === 'codice') {
                        continue;
                    }
                    $entry[$col] = stats_pdv_trim_field($r[$col] ?? '');
                }
                if ($hasCediCol) {
                    $entry['codice_cedi'] = stats_pdv_trim_field($r['codice_cedi'] ?? '');
                }
                $out[$c] = $entry;
            }
        } catch (Throwable $e) {
            error_log('stats_pdv_load_meta: ' . $e->getMessage());
        }

        return $out;
    }
}

if (!function_exists('stats_pdv_build_report')) {
    /**
     * @param array{pdv_codice?: string, cedi_codice?: string} $opts
     * @return array<string, mixed>
     */
    function stats_pdv_build_report(PDO $pdo, string $dateFrom, string $dateTo, array $opts = []): array
    {
        if ($dateFrom > $dateTo) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }

        $pdvCodice = isset($opts['pdv_codice']) ? trim((string) $opts['pdv_codice']) : '';
        $cediCodice = isset($opts['cedi_codice']) ? trim((string) $opts['cedi_codice']) : '';
        $dtEndExcl = date('Y-m-d', strtotime($dateTo . ' +1 day'));
        $params = [
            ':df_start' => $dateFrom . ' 00:00:00',
            ':dt_end_excl' => $dtEndExcl . ' 00:00:00',
        ];
        $pdvSql = '';
        if ($pdvCodice !== '') {
            $pdvSql = ' AND p.`id_pdv_concorso` = :pdv_cod ';
            $params[':pdv_cod'] = $pdvCodice;
        }
        $cediSql = '';
        if ($cediCodice !== '' && stats_pdv_has_pdv_cedi_column($pdo)) {
            $cediSql = " AND EXISTS (
                SELECT 1 FROM `punti_vendita` pv
                WHERE TRIM(pv.`codice`) = TRIM(p.`id_pdv_concorso`)
                  AND TRIM(pv.`codice_cedi`) = :cedi_cod
            )";
            $params[':cedi_cod'] = $cediCodice;
        }

        $baseFrom = "
            FROM `partecipazioni` p
            WHERE p.`data_partecipazione` >= :df_start AND p.`data_partecipazione` < :dt_end_excl
            {$pdvSql}
            {$cediSql}
        ";
        $pdvMeta = stats_pdv_load_meta($pdo);

        $summary = [
            'participations' => 0,
            'wins' => 0,
            'win_rate' => 0.0,
            'pdv_with_activity' => 0,
            'days_with_activity' => 0,
        ];

        try {
            $q = $pdo->prepare("
                SELECT COUNT(*) AS `part`,
                       SUM(CASE WHEN p.`has_won` = 1 AND COALESCE(p.`annullato`, 0) = 0 THEN 1 ELSE 0 END) AS `wins`
                {$baseFrom}
            ");
            $q->execute($params);
            $r = $q->fetch(PDO::FETCH_ASSOC);
            if ($r) {
                $r = array_change_key_case($r, CASE_LOWER);
                $summary['participations'] = (int) ($r['part'] ?? 0);
                $summary['wins'] = (int) ($r['wins'] ?? 0);
            }
        } catch (Throwable $e) {
            return [
                'error' => 'Query riepilogo: ' . $e->getMessage(),
                'meta' => ['from' => $dateFrom, 'to' => $dateTo, 'pdv_codice' => $pdvCodice, 'cedi_codice' => $cediCodice],
            ];
        }

        if ($summary['participations'] > 0) {
            $summary['win_rate'] = round(100.0 * $summary['wins'] / $summary['participations'], 3);
        }

        $byDay = [];
        try {
            $q = $pdo->prepare("
                SELECT DATE(p.`data_partecipazione`) AS `g`,
                       COUNT(*) AS `part`,
                       SUM(CASE WHEN p.`has_won` = 1 AND COALESCE(p.`annullato`, 0) = 0 THEN 1 ELSE 0 END) AS `wins`
                {$baseFrom}
                GROUP BY DATE(p.`data_partecipazione`)
                ORDER BY `g` ASC
            ");
            $q->execute($params);
            while ($r = $q->fetch(PDO::FETCH_ASSOC)) {
                $r = array_change_key_case($r, CASE_LOWER);
                $part = (int) ($r['part'] ?? 0);
                $wins = (int) ($r['wins'] ?? 0);
                $byDay[] = [
                    'date' => (string) ($r['g'] ?? ''),
                    'part' => $part,
                    'wins' => $wins,
                    'win_rate' => $part > 0 ? round(100.0 * $wins / $part, 2) : 0.0,
                ];
            }
        } catch (Throwable $e) {
            $byDay = [];
        }
        $summary['days_with_activity'] = count($byDay);

        $byHour = [];
        for ($h = 0; $h < 24; $h++) {
            $byHour[$h] = ['hour' => $h, 'part' => 0, 'wins' => 0];
        }
        try {
            $q = $pdo->prepare("
                SELECT HOUR(p.`data_partecipazione`) AS `h`,
                       COUNT(*) AS `part`,
                       SUM(CASE WHEN p.`has_won` = 1 AND COALESCE(p.`annullato`, 0) = 0 THEN 1 ELSE 0 END) AS `wins`
                {$baseFrom}
                GROUP BY HOUR(p.`data_partecipazione`)
                ORDER BY `h` ASC
            ");
            $q->execute($params);
            while ($r = $q->fetch(PDO::FETCH_ASSOC)) {
                $r = array_change_key_case($r, CASE_LOWER);
                $h = (int) ($r['h'] ?? 0);
                if ($h < 0 || $h > 23) {
                    continue;
                }
                $byHour[$h] = [
                    'hour' => $h,
                    'part' => (int) ($r['part'] ?? 0),
                    'wins' => (int) ($r['wins'] ?? 0),
                ];
            }
        } catch (Throwable $e) {
            // ignore
        }

        $partByPdv = [];
        $winByPdv = [];
        try {
            $q = $pdo->prepare("
                SELECT p.`id_pdv_concorso` AS `cod`,
                       COUNT(*) AS `part`,
                       SUM(CASE WHEN p.`has_won` = 1 AND COALESCE(p.`annullato`, 0) = 0 THEN 1 ELSE 0 END) AS `wins`
                {$baseFrom}
                  AND p.`id_pdv_concorso` IS NOT NULL AND p.`id_pdv_concorso` <> ''
                GROUP BY p.`id_pdv_concorso`
            ");
            $q->execute($params);
            while ($r = $q->fetch(PDO::FETCH_ASSOC)) {
                $r = array_change_key_case($r, CASE_LOWER);
                $c = trim((string) ($r['cod'] ?? ''));
                if ($c === '') {
                    continue;
                }
                $partByPdv[$c] = (int) ($r['part'] ?? 0);
                $winByPdv[$c] = (int) ($r['wins'] ?? 0);
            }
        } catch (Throwable $e) {
            $partByPdv = [];
            $winByPdv = [];
        }

        $summary['pdv_with_activity'] = count($partByPdv);

        $byPdv = [];
        $allCodes = array_keys($pdvMeta);
        foreach ($partByPdv as $cod => $_part) {
            if (!in_array($cod, $allCodes, true)) {
                $allCodes[] = $cod;
            }
        }
        if ($pdvCodice !== '' && !in_array($pdvCodice, $allCodes, true)) {
            $allCodes[] = $pdvCodice;
        }
        foreach ($allCodes as $cod) {
            if ($pdvCodice !== '' && $cod !== $pdvCodice) {
                continue;
            }
            $part = (int) ($partByPdv[$cod] ?? 0);
            $wins = (int) ($winByPdv[$cod] ?? 0);
            $metaRow = $pdvMeta[$cod] ?? ['cod' => $cod, 'codice' => $cod, 'label' => $cod];
            $byPdv[] = array_merge($metaRow, [
                'cod' => $cod,
                'codice' => $cod,
                'label' => (string) ($metaRow['label'] ?? $cod),
                'codice_cedi' => (string) ($metaRow['codice_cedi'] ?? ''),
                'part' => $part,
                'wins' => $wins,
                'win_rate' => $part > 0 ? round(100.0 * $wins / $part, 2) : 0.0,
            ]);
        }
        usort($byPdv, static function (array $a, array $b): int {
            $cmp = $b['part'] <=> $a['part'];
            if ($cmp !== 0) {
                return $cmp;
            }
            return strcmp((string) $a['cod'], (string) $b['cod']);
        });

        $exportColumns = stats_pdv_punti_vendita_columns($pdo);
        $exportHeaders = [];
        $columnDefs = stats_pdv_export_column_defs();
        foreach ($exportColumns as $col) {
            $exportHeaders[$col] = $columnDefs[$col] ?? $col;
        }

        return [
            'meta' => [
                'from' => $dateFrom,
                'to' => $dateTo,
                'generated_at' => date('Y-m-d H:i:s'),
                'pdv_codice' => $pdvCodice,
                'cedi_codice' => $cediCodice,
                'pdv_export_columns' => $exportColumns,
                'pdv_export_headers' => $exportHeaders,
            ],
            'summary' => $summary,
            'by_day' => $byDay,
            'by_hour' => array_values($byHour),
            'by_pdv' => $byPdv,
        ];
    }
}
