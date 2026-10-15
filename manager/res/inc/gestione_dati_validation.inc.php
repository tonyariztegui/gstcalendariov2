<?php
/**
 * Validazioni condivise import CSV e CRUD Gestione Dati.
 */

require_once __DIR__ . '/gestione_dati_context.inc.php';

if (!function_exists('gestione_dati_expected_codice_length')) {
    function gestione_dati_expected_codice_length(PDO $pdo, array $content, array $defaults): int
    {
        $useAuto = !empty($content['instantwin_codice_length_auto'] ?? ($defaults['instantwin_codice_length_auto'] ?? false));
        if ($useAuto) {
            try {
                $stmt = $pdo->query("SELECT CHAR_LENGTH(codice) AS len FROM codici_partecipazione WHERE isActive = 1 AND codice IS NOT NULL AND codice <> '' LIMIT 1");
                $row = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : false;
                if ($row && isset($row['len']) && (int) $row['len'] > 0) {
                    return (int) $row['len'];
                }
            } catch (Throwable $e) {
                // ignore
            }
        }
        return (int) ($content['instantwin_codice_length'] ?? ($defaults['instantwin_codice_length'] ?? 12));
    }
}

if (!function_exists('gestione_dati_validate_codice_partecipazione')) {
    /**
     * @return list<string>
     */
    function gestione_dati_validate_codice_partecipazione(string $codice, PDO $pdo, array $content, array $defaults, ?int $excludeId = null): array
    {
        $errors = [];
        $codice = trim($codice);
        if ($codice === '') {
            $errors[] = 'Il codice partecipazione è obbligatorio.';
            return $errors;
        }

        $expectedLen = gestione_dati_expected_codice_length($pdo, $content, $defaults);
        if (strlen($codice) !== $expectedLen) {
            $errors[] = "Il codice deve essere di esattamente {$expectedLen} caratteri.";
        }

        $sql = 'SELECT id FROM codici_partecipazione WHERE codice = ?';
        $params = [$codice];
        if ($excludeId !== null && $excludeId > 0) {
            $sql .= ' AND id <> ?';
            $params[] = $excludeId;
        }
        $sql .= ' LIMIT 1';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        if ($stmt->fetchColumn()) {
            $errors[] = 'Codice partecipazione già presente.';
        }

        return $errors;
    }
}

if (!function_exists('gestione_dati_validate_codice_premio_create')) {
    /**
     * @return list<string>
     */
    function gestione_dati_validate_codice_premio_create(PDO $pdo, string $codice, int $prizeId, ?int $excludeId = null): array
    {
        $errors = [];
        $codice = trim($codice);
        if ($codice === '') {
            $errors[] = 'Il codice premio è obbligatorio.';
        }
        if ($prizeId <= 0) {
            $errors[] = 'Seleziona un premio valido (prize_id).';
            return $errors;
        }

        $stmtPrize = $pdo->prepare('SELECT id, quantita_totale FROM premi WHERE id = ? AND is_active = 1 LIMIT 1');
        $stmtPrize->execute([$prizeId]);
        $prize = $stmtPrize->fetch(PDO::FETCH_ASSOC);
        if (!$prize) {
            $errors[] = 'Premio non trovato o non attivo.';
            return $errors;
        }

        $qt = (int) ($prize['quantita_totale'] ?? 0);
        $sql = 'SELECT COUNT(*) FROM codici_premio WHERE prize_id = ?';
        $params = [$prizeId];
        if ($excludeId !== null && $excludeId > 0) {
            $sql .= ' AND id <> ?';
            $params[] = $excludeId;
        }
        $stmtCount = $pdo->prepare($sql);
        $stmtCount->execute($params);
        $existing = (int) $stmtCount->fetchColumn();
        if ($existing >= $qt) {
            $errors[] = "Quota raggiunta per il premio ID {$prizeId}: massimo {$qt} codici.";
        }

        if ($codice !== '') {
            $sqlDup = 'SELECT id FROM codici_premio WHERE codice = ?';
            $dupParams = [$codice];
            if ($excludeId !== null && $excludeId > 0) {
                $sqlDup .= ' AND id <> ?';
                $dupParams[] = $excludeId;
            }
            $sqlDup .= ' LIMIT 1';
            $stmtDup = $pdo->prepare($sqlDup);
            $stmtDup->execute($dupParams);
            if ($stmtDup->fetchColumn()) {
                $errors[] = 'Codice premio già presente.';
            }
        }

        return $errors;
    }
}

if (!function_exists('gestione_dati_validate_codice_premio')) {
    /**
     * @param array<string, mixed> $ctx
     * @return list<string>
     */
    function gestione_dati_validate_codice_premio(
        PDO $pdo,
        string $codice,
        int $prizeId,
        ?int $idCodicePartecipazione,
        array $ctx,
        ?int $excludeId = null
    ): array {
        $errors = gestione_dati_validate_codice_premio_create($pdo, $codice, $prizeId, $excludeId);

        if (($ctx['codice_mode'] ?? '') === 'direct_win') {
            if ($idCodicePartecipazione === null || $idCodicePartecipazione <= 0) {
                $errors[] = 'In modalità Instant Win + codice è obbligatorio collegare un codice partecipazione.';
            } else {
                $stmt = $pdo->prepare('SELECT id, used FROM codici_partecipazione WHERE id = ? LIMIT 1');
                $stmt->execute([$idCodicePartecipazione]);
                $cp = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$cp) {
                    $errors[] = 'Codice partecipazione collegato non trovato.';
                } else {
                    $stmtDup = $pdo->prepare('SELECT id FROM codici_premio WHERE id_codice_partecipazione = ? AND id <> ? LIMIT 1');
                    $dupId = $excludeId ?? 0;
                    $stmtDup->execute([$idCodicePartecipazione, $dupId]);
                    if ($stmtDup->fetchColumn()) {
                        $errors[] = 'Questo codice partecipazione è già collegato a un altro codice premio.';
                    }
                }
            }
        }

        return $errors;
    }
}

if (!function_exists('gestione_dati_validate_pdv')) {
    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $ctx contesto da gestione_dati_resolve_context (opzionale)
     * @return list<string>
     */
    function gestione_dati_validate_pdv(PDO $pdo, array $data, ?int $excludeId = null, array $ctx = [], array $content = [], array $defaults = []): array
    {
        $errors = [];
        $codice = trim((string) ($data['codice'] ?? ''));
        if ($codice === '') {
            $errors[] = 'Il codice PDV è obbligatorio.';
        }

        $sql = 'SELECT id FROM punti_vendita WHERE codice = ?';
        $params = [$codice];
        if ($excludeId !== null && $excludeId > 0) {
            $sql .= ' AND id <> ?';
            $params[] = $excludeId;
        }
        $sql .= ' LIMIT 1';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        if ($stmt->fetchColumn()) {
            $errors[] = 'Codice PDV già presente.';
        }

        if (!function_exists('gestione_dati_pdv_registration_fields')) {
            require_once __DIR__ . '/gestione_dati_columns.inc.php';
        }
        $requiredPdvFields = ['codice'];
        foreach (gestione_dati_pdv_registration_fields($content, $defaults)['fields'] ?? [] as $field) {
            $field = strtolower(trim((string) $field));
            if ($field !== '' && !in_array($field, $requiredPdvFields, true)) {
                $requiredPdvFields[] = $field;
            }
        }
        if (!empty($ctx['iw_per_cedi'])) {
            $requiredPdvFields[] = 'codice_cedi';
        }
        $pdvLabels = function_exists('gestione_dati_pdv_field_labels') ? gestione_dati_pdv_field_labels() : [];
        foreach ($requiredPdvFields as $field) {
            if ($field === 'codice') {
                continue;
            }
            $value = trim((string) ($data[$field] ?? ''));
            if ($value === '') {
                $label = $pdvLabels[$field] ?? ucfirst(str_replace('_', ' ', $field));
                $errors[] = "Il campo {$label} è obbligatorio.";
            }
        }

        if (!empty($ctx['iw_per_cedi'])) {
            $cedi = trim((string) ($data['codice_cedi'] ?? ''));
            if ($cedi === '') {
                $errors[] = 'Con ripartizione premi per Ce.Di. il codice Ce.Di. è obbligatorio su ogni PDV.';
            }
        }

        if (array_key_exists('iw_traffic_weight', $data) && $data['iw_traffic_weight'] !== '' && $data['iw_traffic_weight'] !== null) {
            $w = (float) $data['iw_traffic_weight'];
            if ($w < 0) {
                $errors[] = 'Il peso traffico IW non può essere negativo.';
            }
        }

        return $errors;
    }
}

if (!function_exists('gestione_dati_validate_quiz_domanda')) {
    /**
     * @param array<string, mixed> $data
     * @return list<string>
     */
    function gestione_dati_validate_quiz_domanda(array $data): array
    {
        $errors = [];
        $testo = trim((string) ($data['testo'] ?? ''));
        if ($testo === '') {
            $errors[] = 'Il testo della domanda è obbligatorio.';
        }
        $tipo = (string) ($data['tipo'] ?? 'text');
        if (!in_array($tipo, ['text', 'audio', 'video'], true)) {
            $errors[] = 'Tipo domanda non valido.';
        }
        $corretta = strtoupper(trim((string) ($data['risposta_corretta'] ?? 'A')));
        if (!in_array($corretta, ['A', 'B', 'C', 'D'], true)) {
            $errors[] = 'Risposta corretta non valida.';
        }
        foreach (['a', 'b', 'c', 'd'] as $letter) {
            $answer = trim((string) ($data['risposta_' . $letter] ?? ''));
            if ($answer === '') {
                $errors[] = 'Risposta ' . strtoupper($letter) . ' obbligatoria.';
            }
        }
        if (in_array($corretta, ['A', 'B', 'C', 'D'], true)) {
            $selected = trim((string) ($data['risposta_' . strtolower($corretta)] ?? ''));
            if ($selected === '') {
                $errors[] = 'La risposta selezionata come corretta non può essere vuota.';
            }
        }
        return $errors;
    }
}
