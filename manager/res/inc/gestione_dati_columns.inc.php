<?php
/**
 * Catalogo colonne archivio Gestione Dati + visibilità da content.json.
 */

require_once __DIR__ . '/gestione_dati_crud_schema.inc.php';

if (!function_exists('gestione_dati_pdv_field_labels')) {
    /** @return array<string, string> */
    function gestione_dati_pdv_field_labels(): array
    {
        return [
            'codice' => 'Codice',
            'ragsoc' => 'Ragione sociale',
            'indirizzo' => 'Indirizzo',
            'citta' => 'Città',
            'prov' => 'Provincia',
            'cap' => 'CAP',
            'regione' => 'Regione',
            'insegna' => 'Insegna',
            'codice_cedi' => 'Codice Ce.Di.',
            'email_pdv' => 'Email PDV',
            'active' => 'Stato',
        ];
    }
}

if (!function_exists('gestione_dati_import_mappable_columns')) {
    /**
     * Colonnes importables pour le mapping CSV → table.
     * Pour punti_vendita: masque id, iw_traffic_weight, codice_pdv et applique des labels amicaux.
     *
     * @param list<string> $columns
     * @return list<array{name:string,label:string}>
     */
    function gestione_dati_import_mappable_columns(string $table, array $columns): array
    {
        $table = strtolower(trim($table));
        $hidden = [];
        $labels = [];
        if ($table === 'punti_vendita') {
            $hidden = ['id', 'iw_traffic_weight', 'codice_pdv'];
            $labels = gestione_dati_pdv_field_labels();
        }
        $out = [];
        foreach ($columns as $col) {
            $name = (string) $col;
            if ($name === '' || in_array($name, $hidden, true)) {
                continue;
            }
            $out[] = [
                'name' => $name,
                'label' => $labels[$name] ?? $name,
            ];
        }
        return $out;
    }
}

if (!function_exists('gestione_dati_pdv_registration_fields')) {
    /**
     * @param array<string, mixed> $content
     * @param array<string, mixed> $defaults
     * @return array<string, mixed>
     */
    function gestione_dati_pdv_registration_fields(array $content, array $defaults): array
    {
        $merged = array_merge($defaults, $content);
        $fieldsEnabled = is_array($merged['fields_enabled'] ?? null) ? $merged['fields_enabled'] : [];
        $pdvEnabled = !empty($fieldsEnabled['prov_pdv']) || !empty($fieldsEnabled['citta_pdv']) || !empty($fieldsEnabled['indirizzo_pdv']);

        $labels = gestione_dati_pdv_field_labels();
        $empty = [
            'pdv_enabled' => $pdvEnabled,
            'mode' => '',
            'mode_label' => '',
            'fields' => [],
            'field_labels' => [],
            'registration_line' => '',
        ];

        if (!$pdvEnabled) {
            return $empty;
        }

        $pdvConfig = is_array($merged['pdv_config'] ?? null) ? $merged['pdv_config'] : [];
        $mode = strtolower(trim((string) ($pdvConfig['mode'] ?? 'combinato')));
        if (!in_array($mode, ['combinato', 'cascata'], true)) {
            $mode = 'combinato';
        }

        $dbFields = [];

        if ($mode === 'combinato') {
            $labelFields = $pdvConfig['combinato']['label_fields'] ?? ['ragsoc', 'indirizzo', 'citta', 'prov'];
            if (!is_array($labelFields)) {
                $labelFields = ['ragsoc', 'indirizzo', 'citta', 'prov'];
            }
            $allowed = ['ragsoc', 'indirizzo', 'citta', 'prov', 'cap', 'regione', 'insegna', 'codice'];
            foreach ($labelFields as $f) {
                $f = strtolower(trim((string) $f));
                if ($f !== '' && in_array($f, $allowed, true) && !in_array($f, $dbFields, true)) {
                    $dbFields[] = $f;
                }
            }
            $modeLabel = 'Combinato (campo unico con ricerca)';
            $regLine = 'In registrazione (Combinato) il label del punto vendita mostra: ';
        } else {
            $geo = strtolower(trim((string) ($pdvConfig['cascata']['geographic_mode'] ?? 'provincia')));
            if (!in_array($geo, ['provincia', 'regione'], true)) {
                $geo = 'provincia';
            }
            if ($geo === 'regione') {
                $dbFields[] = 'regione';
            } else {
                $dbFields[] = 'prov';
            }
            $dbFields[] = 'citta';

            $display = strtolower(trim((string) ($pdvConfig['cascata']['punto_vendita_display'] ?? 'insegna_indirizzo')));
            if (!in_array($display, ['insegna_indirizzo', 'indirizzo_insegna', 'indirizzo_only'], true)) {
                $display = 'insegna_indirizzo';
            }
            if ($display === 'indirizzo_only') {
                $dbFields[] = 'indirizzo';
            } else {
                $dbFields[] = 'insegna';
                $dbFields[] = 'indirizzo';
            }

            $modeLabel = $geo === 'regione'
                ? 'Cascata: Regione → Città → Punto vendita'
                : 'Cascata: Provincia → Città → Punto vendita';
            $displayLabels = [
                'insegna_indirizzo' => 'Insegna/Ragione sociale + Indirizzo',
                'indirizzo_insegna' => 'Indirizzo + Insegna/Ragione sociale',
                'indirizzo_only' => 'Solo indirizzo',
            ];
            $regLine = 'In registrazione (' . $modeLabel . ', campo Punto vendita: '
                . ($displayLabels[$display] ?? $display) . ') valorizza: ';
        }

        $fieldLabels = [];
        foreach ($dbFields as $f) {
            $fieldLabels[] = $labels[$f] ?? $f;
        }

        return [
            'pdv_enabled' => true,
            'mode' => $mode,
            'mode_label' => $modeLabel,
            'fields' => $dbFields,
            'field_labels' => $fieldLabels,
            'registration_line' => $regLine . implode(', ', $fieldLabels) . '.',
        ];
    }
}

if (!function_exists('gestione_dati_table_columns_catalog')) {
    /**
     * @return list<array<string, mixed>>
     */
    function gestione_dati_table_columns_catalog(string $table, PDO $pdo, array $ctx, array $content, array $defaults): array
    {
        $pdvReg = gestione_dati_pdv_registration_fields($content, $defaults);
        $regSet = array_flip($pdvReg['fields'] ?? []);

        $col = static function (
            string $data,
            string $title,
            string $sortSql,
            bool $defaultVisible,
            bool $locked = false,
            bool $searchable = true,
            bool $orderable = true,
            bool $registrationRelevant = false
        ): array {
            return [
                'data' => $data,
                'title' => $title,
                'sortSql' => $sortSql,
                'default' => $defaultVisible,
                'locked' => $locked,
                'searchable' => $searchable,
                'orderable' => $orderable,
                'registrationRelevant' => $registrationRelevant,
            ];
        };

        switch ($table) {
            case 'punti_vendita':
                $cols = [
                    $col('codice', 'Codice', 'p.codice', true, true, true, true, false),
                    $col('ragsoc', 'Ragione sociale', 'p.ragsoc', isset($regSet['ragsoc']), false, true, true, isset($regSet['ragsoc'])),
                    $col('indirizzo', 'Indirizzo', 'p.indirizzo', isset($regSet['indirizzo']), false, true, true, isset($regSet['indirizzo'])),
                    $col('citta', 'Città', 'p.citta', isset($regSet['citta']), false, true, true, isset($regSet['citta'])),
                    $col('prov', 'Prov.', 'p.prov', isset($regSet['prov']), false, true, true, isset($regSet['prov'])),
                    $col('cap', 'CAP', 'p.cap', isset($regSet['cap']), false, true, true, isset($regSet['cap'])),
                    $col('regione', 'Regione', 'p.regione', isset($regSet['regione']), false, true, true, isset($regSet['regione'])),
                    $col('insegna', 'Insegna', 'p.insegna', isset($regSet['insegna']), false, true, true, isset($regSet['insegna'])),
                ];
                if (gestione_dati_table_has_column($pdo, 'punti_vendita', 'codice_cedi')) {
                    $cols[] = $col('codice_cedi', 'Codice Ce.Di.', 'p.codice_cedi', !empty($ctx['iw_per_cedi']), false, true, true, false);
                }
                if (gestione_dati_table_has_column($pdo, 'punti_vendita', 'email_pdv')) {
                    $cols[] = $col('email_pdv', 'Email PDV', 'p.email_pdv', false, false, true, true, false);
                }
                $cols[] = $col('active', 'Stato', 'p.active', true, false, false, false, false);
                $cols[] = $col('azioni', 'Azioni', 'p.id', true, true, false, false, false);
                return $cols;

            case 'codici_partecipazione':
                return [
                    $col('codice', 'Codice', 'cp.codice', true, true),
                    $col('used', 'Utilizzo', 'cp.used', true, false, false, false),
                    $col('isActive', 'Attivo', 'cp.isActive', true, false, false, false),
                    $col('prize_id', 'ID premio', 'cpr_link.prize_id', false),
                    $col('id_partecipazione', 'ID partecip.', 'cp.id_partecipazione', false),
                    $col('data_creazione', 'Creato il', 'cp.data_creazione', true),
                    $col('data_utilizzo', 'Utilizzato il', 'cp.data_utilizzo', false),
                    $col('azioni', 'Azioni', 'cp.id', true, true, false, false),
                ];

            case 'codici_premio':
                $cols = [
                    $col('codice', 'Codice', 'cpr.codice', true, true),
                    $col('premio', 'Premio', 'pr.valore', true),
                    $col('prize_id', 'ID premio', 'cpr.prize_id', false),
                    $col('used', 'Utilizzo', 'cpr.used', true, false, false, false),
                    $col('isActive', 'Attivo', 'cpr.isActive', true, false, false, false),
                    $col('data_assegnazione', 'Assegnato il', 'cpr.data_assegnazione', true),
                ];
                if (($ctx['codice_mode'] ?? '') === 'direct_win') {
                    $cols[] = $col('id_codice_partecipazione', 'ID cod. partec.', 'cpr.id_codice_partecipazione', true);
                }
                $cols[] = $col('azioni', 'Azioni', 'cpr.id', true, true, false, false);
                return $cols;

            case 'quiz_domande':
                return [
                    $col('ordine', 'Ordine', 'q.ordine', true),
                    $col('testo', 'Testo', 'q.testo', true, true),
                    $col('tipo', 'Tipo', 'q.tipo', true),
                    $col('attiva', 'Stato', 'q.attiva', true, false, false, false),
                    $col('azioni', 'Azioni', 'q.id', true, true, false, false),
                ];

            default:
                return [];
        }
    }
}

if (!function_exists('gestione_dati_recommended_pdv_columns')) {
    /** @return list<string> */
    function gestione_dati_recommended_pdv_columns(array $content, array $defaults): array
    {
        $pdvReg = gestione_dati_pdv_registration_fields($content, $defaults);
        $merged = array_merge($defaults, $content);
        $cols = ['codice'];
        foreach ($pdvReg['fields'] ?? [] as $f) {
            if (!in_array($f, $cols, true)) {
                $cols[] = $f;
            }
        }
        $iwCedi = strtolower((string) ($merged['instantwin_prize_distribution'] ?? '')) === 'cedi'
            || !empty($merged['instantwin_per_cedi']);
        if ($iwCedi && !in_array('codice_cedi', $cols, true)) {
            $cols[] = 'codice_cedi';
        }
        $cols[] = 'active';
        $cols[] = 'azioni';
        return $cols;
    }
}

if (!function_exists('gestione_dati_default_visible_columns')) {
    /**
     * @param list<array<string, mixed>> $catalog
     * @return list<string>
     */
    function gestione_dati_default_visible_columns(string $table, array $catalog, array $content, array $defaults): array
    {
        if ($table === 'punti_vendita') {
            return gestione_dati_recommended_pdv_columns($content, $defaults);
        }

        $visible = [];
        foreach ($catalog as $c) {
            if (!empty($c['default']) || !empty($c['locked'])) {
                $visible[] = $c['data'];
            }
        }
        return $visible;
    }
}

if (!function_exists('gestione_dati_resolve_visible_columns')) {
    /**
     * @param list<array<string, mixed>> $catalog
     * @return list<string>
     */
    function gestione_dati_resolve_visible_columns(
        string $table,
        array $catalog,
        array $content,
        array $defaults
    ): array {
        $saved = $content['gestione_dati_visible_columns'][$table] ?? null;
        $catalogFlip = array_flip(array_map(static fn ($c) => $c['data'], $catalog));

        if (is_array($saved) && $saved !== []) {
            $visible = [];
            foreach ($saved as $key) {
                $key = (string) $key;
                if (isset($catalogFlip[$key])) {
                    $visible[] = $key;
                }
            }
            foreach ($catalog as $c) {
                if (!empty($c['locked']) && !in_array($c['data'], $visible, true)) {
                    $visible[] = $c['data'];
                }
            }
            if ($visible !== []) {
                return array_values(array_unique($visible));
            }
        }

        return gestione_dati_default_visible_columns($table, $catalog, $content, $defaults);
    }
}

if (!function_exists('gestione_dati_build_columns_payload')) {
    /** @return array<string, mixed> */
    function gestione_dati_build_columns_payload(PDO $pdo, string $table, array $content, array $defaults): array
    {
        $ctx = gestione_dati_resolve_context($pdo, $content, $defaults);
        $catalog = gestione_dati_table_columns_catalog($table, $pdo, $ctx, $content, $defaults);
        $visible = gestione_dati_resolve_visible_columns($table, $catalog, $content, $defaults);
        $recommended = $table === 'punti_vendita'
            ? gestione_dati_recommended_pdv_columns($content, $defaults)
            : gestione_dati_default_visible_columns($table, $catalog, $content, $defaults);

        return [
            'catalog' => $catalog,
            'visible' => $visible,
            'recommended' => $recommended,
        ];
    }
}

if (!function_exists('gestione_dati_build_all_columns_payload')) {
    /**
     * @param list<string> $tables
     * @return array<string, array<string, mixed>>
     */
    function gestione_dati_build_all_columns_payload(PDO $pdo, array $tables, array $content, array $defaults): array
    {
        $out = [];
        foreach ($tables as $table) {
            $out[$table] = gestione_dati_build_columns_payload($pdo, $table, $content, $defaults);
        }
        return $out;
    }
}

if (!function_exists('gestione_dati_sort_sql_for_field')) {
    function gestione_dati_sort_sql_for_field(string $table, string $field, PDO $pdo, array $ctx, array $content, array $defaults): string
    {
        $catalog = gestione_dati_table_columns_catalog($table, $pdo, $ctx, $content, $defaults);
        foreach ($catalog as $c) {
            if (($c['data'] ?? '') === $field && !empty($c['sortSql'])) {
                return (string) $c['sortSql'];
            }
        }
        return match ($table) {
            'punti_vendita' => 'p.codice',
            'codici_partecipazione' => 'cp.codice',
            'codici_premio' => 'cpr.codice',
            'quiz_domande' => 'q.ordine',
            default => '1',
        };
    }
}

if (!function_exists('gestione_dati_save_visible_columns')) {
    /**
     * @param list<string> $columns
     * @return array{success: bool, error?: string, visible?: list<string>}
     */
    function gestione_dati_save_visible_columns(
        string $table,
        array $columns,
        PDO $pdo,
        array &$content,
        array $defaults
    ): array {
        $ctx = gestione_dati_resolve_context($pdo, $content, $defaults);
        $catalog = gestione_dati_table_columns_catalog($table, $pdo, $ctx, $content, $defaults);
        $catalogFlip = array_flip(array_map(static fn ($c) => $c['data'], $catalog));

        $visible = [];
        foreach ($columns as $key) {
            $key = (string) $key;
            if (isset($catalogFlip[$key])) {
                $visible[] = $key;
            }
        }
        foreach ($catalog as $c) {
            if (!empty($c['locked']) && !in_array($c['data'], $visible, true)) {
                $visible[] = $c['data'];
            }
        }
        if ($visible === []) {
            return ['success' => false, 'error' => 'Seleziona almeno una colonna.'];
        }

        if (!isset($content['gestione_dati_visible_columns']) || !is_array($content['gestione_dati_visible_columns'])) {
            $content['gestione_dati_visible_columns'] = [];
        }
        $content['gestione_dati_visible_columns'][$table] = array_values(array_unique($visible));

        return ['success' => true, 'visible' => $content['gestione_dati_visible_columns'][$table]];
    }
}

if (!function_exists('gestione_dati_validate_visible_columns_map')) {
    /**
     * @param array<string, list<string>> $byTable
     * @return array<string, list<string>>
     */
    function gestione_dati_validate_visible_columns_map(
        array $byTable,
        PDO $pdo,
        array $content,
        array $defaults
    ): array {
        $allowedTables = gestione_dati_allowed_tables($content, $defaults);
        $out = [];

        foreach ($byTable as $table => $columns) {
            $table = (string) $table;
            if (!in_array($table, $allowedTables, true) || !is_array($columns)) {
                continue;
            }
            $scratch = $content;
            $result = gestione_dati_save_visible_columns($table, $columns, $pdo, $scratch, $defaults);
            if (!empty($result['success']) && !empty($result['visible'])) {
                $out[$table] = $result['visible'];
            }
        }

        return $out;
    }
}

if (!function_exists('gestione_dati_persist_content_json')) {
    /** @param array<string, mixed> $content */
    function gestione_dati_persist_content_json(array $content): bool
    {
        if (!isset($content['gestione_dati_visible_columns']) || !is_array($content['gestione_dati_visible_columns'])) {
            return false;
        }

        if (!function_exists('load_json_config')) {
            require_once dirname(__DIR__, 3) . '/app/inc/config.php';
        }

        $contentPath = dirname(__DIR__, 3) . '/app/config/content.json';
        $existing = [];
        if (is_file($contentPath)) {
            $decoded = json_decode((string) file_get_contents($contentPath), true);
            if (is_array($decoded)) {
                $existing = $decoded;
            }
        }

        $existing['gestione_dati_visible_columns'] = $content['gestione_dati_visible_columns'];
        $json = json_encode($existing, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            return false;
        }

        return file_put_contents($contentPath, $json . "\n") !== false;
    }
}
