<?php
/**
 * Contesto Gestione Dati: tabelle visibili in base alla configurazione concorso.
 */

if (!function_exists('gestione_dati_reset_auto_increment_if_empty')) {
  /**
   * Reset AUTO_INCREMENT a 1 solo se la tabella è completamente vuota.
   */
  function gestione_dati_reset_auto_increment_if_empty(PDO $pdo, string $table): void
  {
    $allowed = ['punti_vendita', 'codici_partecipazione', 'codici_premio', 'quiz_domande'];
    if (!in_array($table, $allowed, true)) {
      return;
    }
    try {
      $stmt = $pdo->query('SELECT COUNT(*) FROM `' . $table . '`');
      if ($stmt && (int) $stmt->fetchColumn() === 0) {
        $pdo->exec('ALTER TABLE `' . $table . '` AUTO_INCREMENT = 1');
      }
    } catch (Throwable $e) {
      error_log('gestione_dati_reset_auto_increment_if_empty: ' . $e->getMessage());
    }
  }
}

if (!function_exists('gestione_dati_table_labels')) {
    function gestione_dati_table_labels(): array
    {
        return [
            'punti_vendita' => 'Punti Vendita (PDV)',
            'codici_partecipazione' => 'Codici Partecipazione',
            'codici_premio' => 'Codici Premio',
            'quiz_domande' => 'Quiz – Domande',
        ];
    }
}

if (!function_exists('gestione_dati_allowed_tables')) {
    /**
     * @param array<string, mixed> $content
     * @param array<string, mixed> $defaults
     * @return list<string>
     */
    function gestione_dati_allowed_tables(array $content, array $defaults = []): array
    {
        $fields = is_array($content['fields_enabled'] ?? null)
            ? $content['fields_enabled']
            : (is_array($defaults['fields_enabled'] ?? null) ? $defaults['fields_enabled'] : []);

        $pdvEnabled = !empty($fields['prov_pdv']) || !empty($fields['citta_pdv']) || !empty($fields['indirizzo_pdv']);
        $codiceEnabled = !empty($fields['codice_partecipazione']);
        $contestMode = strtolower((string) ($content['contest_mode'] ?? ($defaults['contest_mode'] ?? 'estrazione')));
        $isInstantwin = ($contestMode === 'instantwin');
        $quizEnabled = !empty($content['quiz_enabled'] ?? false);

        $tables = [];
        if ($pdvEnabled) {
            $tables[] = 'punti_vendita';
        }
        if ($codiceEnabled) {
            if (!function_exists('codice_partecipazione_resolve_mode')) {
                require_once dirname(__DIR__, 3) . '/app/logic/codice_partecipazione_validation.php';
            }
            $merged = array_merge($defaults, $content);
            $codiceMode = codice_partecipazione_resolve_mode($merged);
            if (codice_partecipazione_mode_requires_pool($codiceMode)) {
                $tables[] = 'codici_partecipazione';
            }
        }
        if ($isInstantwin) {
            $tables[] = 'codici_premio';
        }
        if ($quizEnabled) {
            $tables[] = 'quiz_domande';
        }

        return $tables;
    }
}

if (!function_exists('gestione_dati_table_visible')) {
    function gestione_dati_table_visible(string $table, array $content, array $defaults = []): bool
    {
        return in_array($table, gestione_dati_allowed_tables($content, $defaults), true);
    }
}

if (!function_exists('gestione_dati_safe_table_name')) {
    function gestione_dati_safe_table_name(string $table, array $content, array $defaults = []): ?string
    {
        $table = trim($table);
        if ($table === '' || !preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
            return null;
        }
        if (!gestione_dati_table_visible($table, $content, $defaults)) {
            return null;
        }
        return $table;
    }
}

if (!function_exists('gestione_dati_load_content')) {
    /**
     * @return array{content: array<string, mixed>, defaults: array<string, mixed>}
     */
    function gestione_dati_load_content(): array
    {
        global $contest_config;

        $defaults = [];
        $content = is_array($contest_config ?? null) ? $contest_config : [];

        if (!function_exists('load_json_config')) {
            require_once dirname(__DIR__, 3) . '/app/inc/config.php';
        }

        $baseDir = dirname(__DIR__, 2);
        $defaultsPath = dirname(__DIR__, 3) . '/app/config/content.defaults.json';
        $contentPath = dirname(__DIR__, 3) . '/app/config/content.json';

        if (function_exists('load_json_config') && is_file($defaultsPath)) {
            $defaults = load_json_config($defaultsPath, []);
            if (is_file($contentPath)) {
                $content = load_json_config($contentPath, $defaults);
            }
        }

        return ['content' => $content, 'defaults' => $defaults];
    }
}

if (!function_exists('gestione_dati_require_auth_json')) {
    function gestione_dati_require_auth_json(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        if (empty($_SESSION['user_id'])) {
            http_response_code(401);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'error' => 'Sessione scaduta. Effettua nuovamente il login.']);
            exit;
        }
    }
}

if (!function_exists('gestione_dati_format_flag_badge')) {
    function gestione_dati_format_flag_badge($value, string $trueLabel, string $falseLabel, string $trueClass = 'badge-success', string $falseClass = 'badge-secondary'): string
    {
        if ((int) $value === 1) {
            return '<span class="badge ' . $trueClass . '">' . htmlspecialchars($trueLabel, ENT_QUOTES, 'UTF-8') . '</span>';
        }
        return '<span class="badge ' . $falseClass . '">' . htmlspecialchars($falseLabel, ENT_QUOTES, 'UTF-8') . '</span>';
    }
}

if (!function_exists('gestione_dati_raise_import_limits')) {
    /** Rimuove i limiti di tempo/memoria per import CSV grandi. */
    function gestione_dati_raise_import_limits(): void
    {
        @set_time_limit(0);
        @ini_set('max_execution_time', '0');
        @ini_set('memory_limit', '512M');
    }
}

if (!function_exists('gestione_dati_flush_insert_batch')) {
    /**
     * Insert multi-riga (fallback riga-per-riga se il batch fallisce).
     *
     * @param list<string> $columns
     * @param list<array<string, mixed>> $rows assoc rows keyed by column name
     * @param list<int> $lineNumbers CSV line number per row (same order as $rows)
     * @param list<string> $failRows
     */
    function gestione_dati_flush_insert_batch(
        PDO $pdo,
        string $table,
        array $columns,
        array $rows,
        array $lineNumbers,
        int &$inserted,
        int &$failed,
        array &$failRows,
        int $maxFailRows = 20
    ): void {
        if ($rows === [] || $columns === []) {
            return;
        }
        $colSql = '`' . implode('`,`', $columns) . '`';
        $onePlaceholders = '(' . implode(',', array_fill(0, count($columns), '?')) . ')';
        $sql = 'INSERT INTO `' . $table . '` (' . $colSql . ') VALUES '
            . implode(',', array_fill(0, count($rows), $onePlaceholders));
        $params = [];
        foreach ($rows as $row) {
            foreach ($columns as $col) {
                $params[] = $row[$col] ?? null;
            }
        }
        try {
            $pdo->prepare($sql)->execute($params);
            $inserted += count($rows);
            return;
        } catch (Throwable $batchEx) {
            // Fallback: individua le righe in errore
            $stmt = $pdo->prepare('INSERT INTO `' . $table . '` (' . $colSql . ') VALUES ' . $onePlaceholders);
            foreach ($rows as $i => $row) {
                $vals = [];
                foreach ($columns as $col) {
                    $vals[] = $row[$col] ?? null;
                }
                try {
                    $stmt->execute($vals);
                    $inserted++;
                } catch (Throwable $rowEx) {
                    $failed++;
                    if (count($failRows) < $maxFailRows) {
                        $lineLabel = isset($lineNumbers[$i]) ? (string) $lineNumbers[$i] : '?';
                        $failRows[] = 'Riga ' . $lineLabel . ': ' . $rowEx->getMessage();
                    }
                }
            }
        }
    }
}
