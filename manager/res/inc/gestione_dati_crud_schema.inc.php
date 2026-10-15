<?php
/**
 * Schema CRUD Gestione Dati: contesto concorso, hint, campi core/opzionali.
 */

require_once __DIR__ . '/gestione_dati_context.inc.php';
require_once __DIR__ . '/gestione_dati_validation.inc.php';
require_once dirname(__DIR__, 3) . '/app/logic/codice_partecipazione_validation.php';

if (!function_exists('gestione_dati_table_has_column')) {
    function gestione_dati_table_has_column(PDO $pdo, string $table, string $column): bool
    {
        static $cache = [];
        $key = $table . '.' . $column;
        if (array_key_exists($key, $cache)) {
            return $cache[$key];
        }
        try {
            $stmt = $pdo->prepare('SHOW COLUMNS FROM `' . str_replace('`', '', $table) . '` LIKE ?');
            $stmt->execute([$column]);
            $cache[$key] = (bool) $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            $cache[$key] = false;
        }
        return $cache[$key];
    }
}

if (!function_exists('gestione_dati_resolve_context')) {
    /**
     * @param array<string, mixed> $content
     * @param array<string, mixed> $defaults
     * @return array<string, mixed>
     */
    function gestione_dati_resolve_context(PDO $pdo, array $content, array $defaults): array
    {
        $merged = array_merge($defaults, $content);
        $fields = is_array($merged['fields_enabled'] ?? null) ? $merged['fields_enabled'] : [];
        $contestMode = strtolower((string) ($merged['contest_mode'] ?? 'estrazione'));
        $isInstantwin = ($contestMode === 'instantwin');
        $codiceMode = codice_partecipazione_resolve_mode($merged);
        $iwDist = strtolower((string) ($merged['instantwin_prize_distribution'] ?? 'default'));
        if (!in_array($iwDist, ['default', 'pdv', 'cedi'], true)) {
            $iwDist = !empty($merged['instantwin_per_cedi']) ? 'cedi' : (!empty($merged['instantwin_per_pdv']) ? 'pdv' : 'default');
        }
        if (!$isInstantwin) {
            $iwDist = 'default';
        }
        if (!function_exists('gestione_dati_pdv_registration_fields')) {
            require_once __DIR__ . '/gestione_dati_columns.inc.php';
        }
        $pdvRegistration = gestione_dati_pdv_registration_fields($content, $defaults);
        $pdvRequiredFields = ['codice'];
        $pdvRequiredLabels = ['Codice'];
        $labelMap = gestione_dati_pdv_field_labels();
        foreach ($pdvRegistration['fields'] ?? [] as $f) {
            $f = strtolower(trim((string) $f));
            if ($f !== '' && !in_array($f, $pdvRequiredFields, true)) {
                $pdvRequiredFields[] = $f;
                $pdvRequiredLabels[] = $labelMap[$f] ?? ucfirst(str_replace('_', ' ', $f));
            }
        }
        if ($isInstantwin && $iwDist === 'cedi' && gestione_dati_table_has_column($pdo, 'punti_vendita', 'codice_cedi')) {
            $pdvRequiredFields[] = 'codice_cedi';
            $pdvRequiredLabels[] = 'Codice Ce.Di.';
        }

        $modeLabels = [
            'pool' => $isInstantwin ? 'Instant Win (pool codici)' : 'Pool codici',
            'direct_win' => 'Instant Win + codice',
            'combinazione' => 'Controllo combinazione (senza pool)',
        ];
        $distLabels = [
            'default' => 'Globale',
            'pdv' => 'Per punto vendita',
            'cedi' => 'Per Ce.Di.',
        ];

        return [
            'contest_mode' => $contestMode,
            'is_instantwin' => $isInstantwin,
            'codice_mode' => $codiceMode,
            'codice_mode_label' => $modeLabels[$codiceMode] ?? $codiceMode,
            'codice_requires_pool' => codice_partecipazione_mode_requires_pool($codiceMode),
            'codice_is_combinazione' => codice_partecipazione_mode_is_combinazione($codiceMode),
            'iw_distribution' => $iwDist,
            'iw_distribution_label' => $distLabels[$iwDist] ?? $iwDist,
            'iw_per_pdv' => $isInstantwin && $iwDist === 'pdv',
            'iw_per_cedi' => $isInstantwin && $iwDist === 'cedi',
            'pdv_enabled' => !empty($fields['prov_pdv']) || !empty($fields['citta_pdv']) || !empty($fields['indirizzo_pdv']),
            'codice_field_enabled' => !empty($fields['codice_partecipazione']),
            'quiz_enabled' => !empty($merged['quiz_enabled']),
            'codice_length' => gestione_dati_expected_codice_length($pdo, $content, $defaults),
            'email_bcc_pdv' => !empty($merged['email_bcc_instantwin_prize_use_pdv_email']),
            'pdv_required_fields' => array_values(array_unique($pdvRequiredFields)),
            'pdv_required_labels' => array_values(array_unique($pdvRequiredLabels)),
        ];
    }
}

if (!function_exists('gestione_dati_table_hint')) {
    function gestione_dati_table_hint(string $table, array $ctx): string
    {
        switch ($table) {
            case 'codici_partecipazione':
                $n = (int) ($ctx['codice_length'] ?? 12);
                if (!empty($ctx['codice_is_combinazione'])) {
                    return 'Modalità combinazione: pool non necessario. I codici vengono validati e salvati in partecipazioni all\'iscrizione.';
                }
                if (($ctx['codice_mode'] ?? '') === 'direct_win') {
                    return "Pool obbligatorio (modalità {$ctx['codice_mode_label']}). Campo richiesto: codice (esattamente {$n} caratteri). Collega ogni vincita in Codici Premio con id_codice_partecipazione; l'ID premio collegato è visibile in archivio.";
                }
                $iw = !empty($ctx['is_instantwin'])
                    ? ' La vincita è decisa dal motore Instant Win; i premi si caricano in Codici Premio (codice + prize_id).'
                    : ' Partecipazione valida per l\'estrazione finale.';
                return "Pool obbligatorio. Campo richiesto: codice (esattamente {$n} caratteri). L'ID premio compare se collegato da Codici Premio.{$iw}";

            case 'codici_premio':
                if (($ctx['codice_mode'] ?? '') === 'direct_win') {
                    return 'Modalità Instant Win + codice: per ogni vincita pre-assegnata servono codice, prize_id e id_codice_partecipazione (ID del codice partecipazione). Senza link = perdita all\'iscrizione.';
                }
                return 'Campi obbligatori: codice e prize_id (quota ≤ quantità premio). Non collegare id_codice_partecipazione in modalità pool: il motore IW assegna i codici alla vincita.';

            case 'punti_vendita':
                $labels = $ctx['pdv_required_labels'] ?? ['Codice'];
                if (!is_array($labels) || $labels === []) {
                    $labels = ['Codice'];
                }
                return 'Campi obbligatori: ' . implode(', ', $labels) . '.';

            case 'quiz_domande':
                return 'Campi obbligatori: testo, tipo, risposte A-B-C-D, risposta corretta.';

            default:
                return '';
        }
    }
}

if (!function_exists('gestione_dati_csv_expected_columns')) {
    /**
     * @return list<string>
     */
    function gestione_dati_csv_expected_columns(string $table, array $ctx, PDO $pdo): array
    {
        switch ($table) {
            case 'punti_vendita':
                $cols = ['codice'];
                if (!empty($ctx['iw_per_cedi']) && gestione_dati_table_has_column($pdo, 'punti_vendita', 'codice_cedi')) {
                    $cols[] = 'codice_cedi';
                }
                if (!empty($ctx['iw_per_pdv']) && gestione_dati_table_has_column($pdo, 'punti_vendita', 'iw_traffic_weight')) {
                    $cols[] = 'iw_traffic_weight';
                }
                if (!empty($ctx['email_bcc_pdv']) && gestione_dati_table_has_column($pdo, 'punti_vendita', 'email_pdv')) {
                    $cols[] = 'email_pdv';
                }
                return array_merge($cols, ['ragsoc', 'indirizzo', 'citta', 'prov', 'cap', 'regione', 'insegna', 'active']);

            case 'codici_partecipazione':
                return ['codice', 'isActive'];

            case 'codici_premio':
                $cols = ['codice', 'prize_id', 'isActive'];
                if (($ctx['codice_mode'] ?? '') === 'direct_win') {
                    $cols[] = 'id_codice_partecipazione';
                }
                return $cols;

            case 'quiz_domande':
                return ['testo', 'tipo', 'risposta_a', 'risposta_b', 'risposta_c', 'risposta_d', 'risposta_corretta', 'ordine', 'attiva'];

            default:
                return [];
        }
    }
}

if (!function_exists('gestione_dati_crud_field_schema')) {
    /**
     * @return array{core: list<array<string, mixed>>, optional: list<array<string, mixed>>}
     */
    function gestione_dati_crud_field_schema(PDO $pdo, string $table, array $ctx): array
    {
        $core = [];
        $optional = [];

        switch ($table) {
            case 'punti_vendita':
                $pdvDefs = [
                    'ragsoc' => ['name' => 'ragsoc', 'label' => 'Ragione sociale', 'type' => 'text', 'required' => false],
                    'indirizzo' => ['name' => 'indirizzo', 'label' => 'Indirizzo', 'type' => 'text', 'required' => false],
                    'citta' => ['name' => 'citta', 'label' => 'Città', 'type' => 'text', 'required' => false, 'col' => 'col-md-6'],
                    'prov' => ['name' => 'prov', 'label' => 'Provincia', 'type' => 'text', 'required' => false, 'col' => 'col-md-3'],
                    'cap' => ['name' => 'cap', 'label' => 'CAP', 'type' => 'text', 'required' => false, 'col' => 'col-md-3'],
                    'regione' => ['name' => 'regione', 'label' => 'Regione', 'type' => 'text', 'required' => false],
                    'insegna' => ['name' => 'insegna', 'label' => 'Insegna', 'type' => 'text', 'required' => false],
                ];
                $core[] = [
                    'name' => 'codice',
                    'label' => 'Codice PDV',
                    'type' => 'text',
                    'required' => true,
                    'help' => 'Identificativo univoco del punto vendita (non usare codice_pdv).',
                ];
                $requiredPdvFields = $ctx['pdv_required_fields'] ?? ['codice'];
                if (!is_array($requiredPdvFields)) {
                    $requiredPdvFields = ['codice'];
                }
                foreach (['ragsoc', 'insegna', 'indirizzo', 'citta', 'prov', 'regione', 'cap'] as $fieldName) {
                    if (!isset($pdvDefs[$fieldName])) {
                        continue;
                    }
                    $def = $pdvDefs[$fieldName];
                    if (in_array($fieldName, $requiredPdvFields, true)) {
                        $def['required'] = true;
                        $core[] = $def;
                    } else {
                        $optional[] = $def;
                    }
                }
                if (gestione_dati_table_has_column($pdo, 'punti_vendita', 'codice_cedi')) {
                    $cediField = [
                        'name' => 'codice_cedi',
                        'label' => 'Codice Ce.Di.',
                        'type' => 'text',
                        'required' => !empty($ctx['iw_per_cedi']),
                        'help' => !empty($ctx['iw_per_cedi'])
                            ? 'Obbligatorio con ripartizione premi per Ce.Di.'
                            : 'Codice centro distribuzione (opzionale).',
                    ];
                    if (in_array('codice_cedi', $requiredPdvFields, true)) {
                        $core[] = $cediField;
                    } else {
                        $optional[] = $cediField;
                    }
                }
                if (gestione_dati_table_has_column($pdo, 'punti_vendita', 'email_pdv')) {
                    $optional[] = [
                        'name' => 'email_pdv',
                        'label' => 'Email PDV',
                        'type' => 'text',
                        'required' => false,
                        'help' => !empty($ctx['email_bcc_pdv'])
                            ? 'Consigliato per CCN email premio Instant Win.'
                            : 'Indirizzo/i email del punto vendita.',
                    ];
                }
                $core[] = [
                    'name' => 'active',
                    'label' => 'PDV attivo',
                    'type' => 'checkbox',
                    'required' => false,
                    'default' => '1',
                ];
                break;

            case 'codici_partecipazione':
                $n = (int) ($ctx['codice_length'] ?? 12);
                $core[] = [
                    'name' => 'codice',
                    'label' => 'Codice',
                    'type' => 'text',
                    'required' => true,
                    'help' => "Esattamente {$n} caratteri (lunghezza configurata nel concorso).",
                    'lock_when' => 'used',
                ];
                $core[] = [
                    'name' => 'isActive',
                    'label' => 'Attivo nel pool',
                    'type' => 'checkbox',
                    'required' => false,
                    'default' => '1',
                ];
                break;

            case 'codici_premio':
                $core[] = [
                    'name' => 'codice',
                    'label' => 'Codice premio',
                    'type' => 'text',
                    'required' => true,
                    'lock_when' => 'assigned',
                ];
                $core[] = [
                    'name' => 'prize_id',
                    'label' => 'Premio',
                    'type' => 'prize_select',
                    'required' => true,
                    'lock_when' => 'assigned',
                ];
                if (($ctx['codice_mode'] ?? '') === 'direct_win') {
                    $core[] = [
                        'name' => 'id_codice_partecipazione',
                        'label' => 'Codice partecipazione collegato',
                        'type' => 'cp_select',
                        'required' => true,
                        'help' => 'Seleziona il codice partecipazione a cui è pre-assegnata questa vincita.',
                        'lock_when' => 'assigned',
                    ];
                }
                $core[] = [
                    'name' => 'isActive',
                    'label' => 'Attivo nel pool',
                    'type' => 'checkbox',
                    'required' => false,
                    'default' => '1',
                ];
                break;

            case 'quiz_domande':
                $core[] = ['name' => 'testo', 'label' => 'Testo domanda', 'type' => 'textarea', 'required' => true];
                $core[] = [
                    'name' => 'tipo',
                    'label' => 'Tipo',
                    'type' => 'select',
                    'required' => true,
                    'options' => [
                        ['value' => 'text', 'label' => 'TEXT'],
                        ['value' => 'audio', 'label' => 'AUDIO'],
                        ['value' => 'video', 'label' => 'VIDEO'],
                    ],
                    'default' => 'text',
                ];
                foreach (['a', 'b', 'c', 'd'] as $letter) {
                    $core[] = [
                        'name' => 'risposta_' . $letter,
                        'label' => 'Risposta ' . strtoupper($letter),
                        'type' => 'text',
                        'required' => true,
                    ];
                }
                $core[] = [
                    'name' => 'risposta_corretta',
                    'label' => 'Risposta corretta',
                    'type' => 'select',
                    'required' => true,
                    'options' => [
                        ['value' => 'A', 'label' => 'A'],
                        ['value' => 'B', 'label' => 'B'],
                        ['value' => 'C', 'label' => 'C'],
                        ['value' => 'D', 'label' => 'D'],
                    ],
                    'default' => 'A',
                ];
                $optional[] = ['name' => 'ordine', 'label' => 'Ordine', 'type' => 'number', 'required' => false, 'default' => '0'];
                $core[] = ['name' => 'attiva', 'label' => 'Domanda attiva', 'type' => 'checkbox', 'required' => false, 'default' => '1'];
                break;
        }

        return ['core' => $core, 'optional' => $optional];
    }
}

if (!function_exists('gestione_dati_crud_build_meta')) {
    /**
     * @param array<string, mixed> $content
     */
    function gestione_dati_crud_build_meta(PDO $pdo, string $table, array $content, array $defaults): array
    {
        $ctx = gestione_dati_resolve_context($pdo, $content, $defaults);
        $schema = gestione_dati_crud_field_schema($pdo, $table, $ctx);
        $meta = [
            'context' => $ctx,
            'hint' => gestione_dati_table_hint($table, $ctx),
            'csv_columns' => gestione_dati_csv_expected_columns($table, $ctx, $pdo),
            'fields' => $schema,
        ];

        if ($table === 'codici_premio') {
            $stmt = $pdo->query('SELECT id, valore, tipo_premio FROM premi WHERE is_active = 1 ORDER BY valore ASC, id ASC');
            $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
            $meta['premi'] = array_map(static function ($p) {
                $label = trim((string) ($p['tipo_premio'] ?? 'Premio'));
                if (isset($p['valore']) && $p['valore'] !== '') {
                    $label .= ' (€' . $p['valore'] . ')';
                }
                return [
                    'id' => $p['id'],
                    'nome' => $label . ' #' . $p['id'],
                ];
            }, $rows);

            if (($ctx['codice_mode'] ?? '') === 'direct_win') {
                $stmtCp = $pdo->query('SELECT id, codice FROM codici_partecipazione WHERE used = 0 ORDER BY codice ASC LIMIT 500');
                $meta['codici_partecipazione_options'] = $stmtCp ? $stmtCp->fetchAll(PDO::FETCH_ASSOC) : [];
            }
        }

        if ($table === 'quiz_domande') {
            $meta['quiz_editor_url'] = 'index.php?pg=concorso&concorso_step=10';
            if (empty($content['quiz_enabled'])) {
                $meta['quiz_editor_url'] = 'index.php?pg=quiz_domande';
            }
        }

        return $meta;
    }
}

if (!function_exists('gestione_dati_csv_required_columns')) {
    /**
     * @return list<string>
     */
    function gestione_dati_csv_required_columns(string $table, array $ctx, PDO $pdo): array
    {
        switch ($table) {
            case 'punti_vendita':
                $req = ['codice'];
                foreach ($ctx['pdv_required_fields'] ?? [] as $field) {
                    $field = (string) $field;
                    if ($field !== '' && !in_array($field, $req, true)) {
                        $req[] = $field;
                    }
                }
                return $req;
            case 'codici_partecipazione':
                return ['codice'];
            case 'codici_premio':
                $req = ['codice', 'prize_id'];
                if (($ctx['codice_mode'] ?? '') === 'direct_win') {
                    $req[] = 'id_codice_partecipazione';
                }
                return $req;
            case 'quiz_domande':
                return ['testo', 'tipo', 'risposta_a', 'risposta_b', 'risposta_c', 'risposta_d', 'risposta_corretta'];
            default:
                return [];
        }
    }
}

if (!function_exists('gestione_dati_csv_columns_bundle')) {
    /**
     * @return array{required: list<string>, all: list<string>}
     */
    function gestione_dati_csv_columns_bundle(string $table, array $ctx, PDO $pdo): array
    {
        return [
            'required' => gestione_dati_csv_required_columns($table, $ctx, $pdo),
            'all' => gestione_dati_csv_expected_columns($table, $ctx, $pdo),
        ];
    }
}

if (!function_exists('gestione_dati_table_hints_map')) {
    /**
     * @param list<string> $tables
     * @return array<string, string>
     */
    function gestione_dati_table_hints_map(PDO $pdo, array $tables, array $content, array $defaults): array
    {
        if (!function_exists('gestione_dati_pdv_registration_fields')) {
            require_once __DIR__ . '/gestione_dati_columns.inc.php';
        }

        $ctx = gestione_dati_resolve_context($pdo, $content, $defaults);
        $map = [];
        foreach ($tables as $table) {
            $hint = gestione_dati_table_hint($table, $ctx);
            if ($table === 'punti_vendita') {
                $extra = [];
                if (!empty($ctx['iw_per_pdv'])) {
                    $extra[] = 'Ripartizione per PDV: servono almeno 2 punti vendita attivi con codice.';
                }
                if (!empty($ctx['iw_per_cedi'])) {
                    $extra[] = 'Ripartizione per Ce.Di.: codice_cedi obbligatorio su ogni PDV per il calendario Instant Win.';
                }
                if (!empty($ctx['email_bcc_pdv'])) {
                    $extra[] = 'Email premio IW con CCN PDV: consigliato email_pdv su ogni punto vendita.';
                }
                if ($extra !== []) {
                    $hint .= ' ' . implode(' ', $extra);
                }
            }
            $map[$table] = $hint;
        }
        if (!empty($ctx['codice_is_combinazione']) && !empty($ctx['codice_field_enabled'])) {
            $map['_global'] = 'Modalità codice: ' . $ctx['codice_mode_label']
                . ' — nessun pool codici_partecipazione da importare; i codici finiscono in partecipazioni.';
        }
        return $map;
    }
}
