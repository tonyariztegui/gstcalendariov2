<?php

	include("_header.php");
	include("res/inc/utenze.php");
	include("res/inc/header.php");
	include("res/inc/sanitize.inc.php");
    require_once __DIR__ . '/../../app/logic/instantwin.php';

    $tipo = (isset($_GET['tp'])) ? $_GET['tp'] : '';

    $_usr_id = $_SESSION['user_id'] ?? '';

    $defaultFrom = (string)($contest_config['start_date'] ?? date('Y-m-d', strtotime('-30 days')));
    $defaultTo = (string)($contest_config['end_date'] ?? date('Y-m-d'));
    $dateFrom = trim((string)($_GET['date_from'] ?? $_GET['from'] ?? $defaultFrom));
    $dateTo = trim((string)($_GET['date_to'] ?? $_GET['to'] ?? $defaultTo));
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) {
        $dateFrom = $defaultFrom;
    }
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)) {
        $dateTo = $defaultTo;
    }
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) {
        $dateFrom = date('Y-m-d', strtotime('-30 days'));
    }
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)) {
        $dateTo = date('Y-m-d');
    }
    if ($dateFrom > $dateTo) {
        [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
    }
    $dateFromBound = $dateFrom . ' 00:00:00';
    $dateToExclBound = date('Y-m-d', strtotime($dateTo . ' +1 day')) . ' 00:00:00';

    // Utiliser COUNT(DISTINCT p.ID) pour éviter les duplications dues aux JOINs
    // Définir baseQueryCount avant de l'utiliser dans les comptages
    $baseQueryCount = "
        FROM partecipazioni p
        LEFT JOIN utenti u ON u.ID_CLIENTE = p.ID_CLIENTE
        LEFT JOIN punti_vendita mpdv ON mpdv.codice = p.ID_PDV_CONCORSO
    ";
    $kpiCount = static function (string $extraWhere = '') use ($connection, $baseQueryCount, $dateFromBound, $dateToExclBound): int {
        $sql = "SELECT COUNT(DISTINCT p.ID) {$baseQueryCount} WHERE p.DATA_PARTECIPAZIONE >= :date_from AND p.DATA_PARTECIPAZIONE < :date_to_excl";
        if ($extraWhere !== '') {
            $sql .= ' AND ' . $extraWhere;
        }
        $stmt = $connection->prepare($sql);
        $stmt->bindValue(':date_from', $dateFromBound);
        $stmt->bindValue(':date_to_excl', $dateToExclBound);
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    };
    $totale_partecipazioni = $kpiCount();
    $totale_vincitori = $kpiCount('p.HAS_WON = 1 AND COALESCE(p.ANNULLATO, 0) = 0');
    $vincitori_confermati = $kpiCount('p.HAS_WON = 1 AND COALESCE(p.CONFERMATO, 0) = 1 AND COALESCE(p.ANNULLATO, 0) = 0');
    $vincitori_non_confermati = $kpiCount('p.HAS_WON = 1 AND COALESCE(p.CONFERMATO, 0) = 0 AND COALESCE(p.ANNULLATO, 0) = 0');
    $vincitori_annullati = $kpiCount('COALESCE(p.ANNULLATO, 0) = 1');
    $_title = 'Partecipazioni';
    $fieldsEnabled = $contest_config['fields_enabled'] ?? [];
    $showEmailColumn = $fieldsEnabled['email'] ?? true;
    $showFidelityColumn = $fieldsEnabled['fidelity'] ?? true;
    $showImportoColumn = $fieldsEnabled['importo'] ?? true;
    $showCodicePartecipazioneColumn = !empty($fieldsEnabled['codice_partecipazione']);
    $showReceiptColumn = !empty($fieldsEnabled['scontrino']);
    $showDataScontrinoColumn = $showReceiptColumn && ($fieldsEnabled['data_scontrino'] ?? true);
    $showPdvColumn = ($fieldsEnabled['prov_pdv'] ?? true) || ($fieldsEnabled['citta_pdv'] ?? true) || ($fieldsEnabled['indirizzo_pdv'] ?? true);
    $showReceiptAction = $fieldsEnabled['scontrino'] ?? true;
    $requireReceiptUpload = $showReceiptAction === true;
    $showWinnerColumn = isset($contest_mode) && strtolower((string)$contest_mode) === 'instantwin';
    $showWinnerCounter = $showWinnerColumn;
    $showSmsColumn = !empty($contest_config['sms']['enabled']);
    $smsColumnsExist = false;
    if ($showSmsColumn) {
        try {
            $smsCol = $connection->query("SHOW COLUMNS FROM partecipazioni LIKE 'sms_inviato'")->fetch(PDO::FETCH_ASSOC);
            $smsColumnsExist = $smsCol !== false;
        } catch (Throwable $e) {
            $smsColumnsExist = false;
        }
    }
    $showSmsColumn = $showSmsColumn && $smsColumnsExist;

    $receiptValidationEnabled = false;
    if ($showReceiptColumn) {
        require_once __DIR__ . '/../res/inc/ensure_scontrino_validato_column.inc.php';
        $receiptValidationEnabled = manager_ensure_scontrino_validato_column($connection);
    }

    $autoPrizeWithReceipt = (bool)($contest_config['instantwin_auto_prize_with_receipt'] ?? false);
    // Conferma/annulla vincita: disponibile in IW salvo flusso auto-premio con upload scontrino obbligatorio.
    // Non legare alle azioni il solo campo scontrino: concorsi QR (senza upload) devono poter confermare la vincita.
    $autoPrizeFlow = $autoPrizeWithReceipt && $showWinnerColumn && $requireReceiptUpload;
    $managerConfirmFlow = $showWinnerColumn && !$autoPrizeFlow && instantwin_is_manager_confirm_flow_enabled();
    $managerCancelFlow = $showWinnerColumn && !$autoPrizeFlow && instantwin_is_manager_cancel_flow_enabled();
    $showConfirmedColumn = $managerConfirmFlow;
    $enableConfirmWinAction = $managerConfirmFlow;
    $enableCancelWinAction = $managerCancelFlow;

    // Colonne Email inviato / Azioni: gate per modalità (IW vs estrazione).
    // email_sending vive in content.json via get_email_sending_config() — non su $contest_config.
    require_once __DIR__ . '/../../public/assets/mail/email_system.php';
    $isInstantWin = $showWinnerColumn;
    $emailIwWinEnabled = should_send_email_type('instantwin_confirm')
        || should_send_email_type('instantwin_prize')
        || should_send_email_type('instantwin_cancel');
    $emailIwLossEnabled = should_send_email_type('instantwin_loss');
    $emailPartecipazioneEnabled = should_send_email_type('partecipazione');
    if ($isInstantWin) {
        $anyUserEmailEnabled = $emailIwWinEnabled || $emailIwLossEnabled;
    } else {
        $anyUserEmailEnabled = $emailPartecipazioneEnabled;
    }
    $showEmailSentColumn = $anyUserEmailEnabled;
    // IW + scontrino + Conferma manager: ✓/✗ solo su vincite, definitivo + mail.
    $iwManagerReceiptFlow = $isInstantWin && $receiptValidationEnabled && ($managerConfirmFlow || $managerCancelFlow);
    // Estrazione + scontrino: ✓/✗ su tutte le foto, sempre reversibile.
    $estrazioneReceiptFlow = !$isInstantWin && $receiptValidationEnabled;
    $showAzioniColumn = $receiptValidationEnabled
        || $anyUserEmailEnabled
        || $enableConfirmWinAction
        || $enableCancelWinAction
        || (!$showReceiptColumn && $showReceiptAction);
    
    $customFields = $contest_config['custom_fields'] ?? [];
    if (!is_array($customFields)) {
        $customFields = [];
    }
    $customFieldsList = [];
    foreach ($customFields as $customField) {
        if (!is_array($customField)) {
            continue;
        }
        $customId = trim((string)($customField['id'] ?? ''));
        $customLabel = trim((string)($customField['label'] ?? ''));
        if ($customId === '' || $customLabel === '') {
            continue;
        }
        $customFieldsList[] = [
            'id' => $customId,
            'label' => $customLabel,
            'type' => strtolower(trim((string)($customField['type'] ?? 'varchar')))
        ];
    }

    $perPageOptions = [15, 25, 50];
    $perPage = (int)($_GET['per_page'] ?? 50);
    if (!in_array($perPage, $perPageOptions, true)) {
        $perPage = 50;
    }
    $page = max((int)($_GET['page'] ?? 1), 1);
    $searchQuery = trim((string)($_GET['search'] ?? ''));
    $filterStatus = trim((string)($_GET['filterStatus'] ?? ''));
    // Filtro scontrino solo in estrazione (in Instant Win basta il filtro state Tutti/…).
    $filterScontrino = $estrazioneReceiptFlow
        ? trim((string)($_GET['filterScontrino'] ?? ''))
        : '';
    if (!in_array($filterScontrino, ['valid', 'invalid', 'pending'], true)) {
        $filterScontrino = '';
    }

    $conditions = [];
    $bindings = [];

    $conditions[] = 'p.DATA_PARTECIPAZIONE >= :date_from';
    $conditions[] = 'p.DATA_PARTECIPAZIONE < :date_to_excl';
    $bindings['date_from'] = $dateFromBound;
    $bindings['date_to_excl'] = $dateToExclBound;

    if ($filterStatus !== '') {
        switch ($filterStatus) {
            case '1':
            case '0':
                $conditions[] = 'p.HAS_WON = :filter_has_won';
                $bindings['filter_has_won'] = (int)$filterStatus;
                break;
            case 'confirmed':
                $conditions[] = 'p.HAS_WON = 1';
                $conditions[] = 'COALESCE(p.CONFERMATO, 0) = 1';
                $conditions[] = 'COALESCE(p.ANNULLATO, 0) = 0';
                break;
            case 'not_confirmed':
                $conditions[] = 'p.HAS_WON = 1';
                $conditions[] = 'COALESCE(p.CONFERMATO, 0) = 0';
                $conditions[] = 'COALESCE(p.ANNULLATO, 0) = 0';
                break;
            case 'cancelled':
                $conditions[] = 'COALESCE(p.ANNULLATO, 0) = 1';
                break;
        }
    }

    if ($receiptValidationEnabled && $filterScontrino !== '') {
        switch ($filterScontrino) {
            case 'valid':
                $conditions[] = 'p.scontrino_validato = 1';
                break;
            case 'invalid':
                $conditions[] = 'p.scontrino_validato = 0';
                break;
            case 'pending':
                $conditions[] = 'p.scontrino_validato IS NULL';
                break;
        }
    }

    if ($searchQuery !== '') {
        $searchColumns = [
            'CAST(p.ID AS CHAR)',
            'CAST(p.ID_CLIENTE AS CHAR)',
            "DATE_FORMAT(p.DATA_PARTECIPAZIONE, '%d/%m/%Y %H:%i')",
            "DATE_FORMAT(p.DATA_PARTECIPAZIONE, '%d-%m-%Y %H:%i')",
            "DATE_FORMAT(p.DATA_PARTECIPAZIONE, '%Y-%m-%d')",
            'CAST(p.IMPORTO_SCONTRINO AS CHAR)',
            "REPLACE(CAST(p.IMPORTO_SCONTRINO AS CHAR), '.', ',')",
            "DATE_FORMAT(p.DATA_SCONTRINO, '%d/%m/%Y')",
            "DATE_FORMAT(p.DATA_SCONTRINO, '%d-%m-%Y')",
            "DATE_FORMAT(p.DATA_SCONTRINO, '%Y-%m-%d')",
            'p.INSEGNA',
            'mpdv.ragsoc',
            'mpdv.indirizzo',
            'mpdv.citta',
            'mpdv.prov',
            'mpdv.cap',
            'mpdv.codice',
            'u.NOME',
            'u.COGNOME',
            'TRIM(CONCAT(COALESCE(u.NOME,\'\'), \' \', COALESCE(u.COGNOME,\'\')))',
            'TRIM(CONCAT(COALESCE(u.COGNOME,\'\'), \' \', COALESCE(u.NOME,\'\')))',
            'u.EMAIL',
            'u.FIDELITY',
            'CONCAT(u.PREFISSO, u.CELLULARE)',
            'u.CELLULARE',
            'u.INDIRIZZO',
            'u.CITTA',
            'u.PROVINCIA',
            'u.cap',
            'p.CODICE_PARTECIPAZIONE'
        ];

        $searchConditions = [];
        foreach ($searchColumns as $index => $column) {
            $paramName = 'search_' . $index;
            $searchConditions[] = "{$column} LIKE :{$paramName}";
            $bindings[$paramName] = '%' . $searchQuery . '%';
        }

        if (!empty($searchConditions)) {
            $conditions[] = '(' . implode(' OR ', $searchConditions) . ')';
        }
    }

    $baseQuery = "
        FROM partecipazioni p
        LEFT JOIN utenti u ON u.ID_CLIENTE = p.ID_CLIENTE
        LEFT JOIN punti_vendita mpdv ON mpdv.codice = p.ID_PDV_CONCORSO
    ";

    $whereSql = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

    // Utiliser COUNT(DISTINCT p.ID) pour éviter les duplications dues aux JOINs
    $countSql = "SELECT COUNT(DISTINCT p.ID) {$baseQuery} {$whereSql}";
    $countStmt = $connection->prepare($countSql);
    foreach ($bindings as $name => $value) {
        $countStmt->bindValue(':' . $name, $value);
    }
    $countStmt->execute();
    $filteredRecords = (int)$countStmt->fetchColumn();

    $totalPages = max((int)ceil($filteredRecords / $perPage), 1);
    if ($page > $totalPages) {
        $page = $totalPages;
    }
    if ($page < 1) {
        $page = 1;
    }
    $offset = ($page - 1) * $perPage;

    $customFieldsSelect = [];
    foreach ($customFieldsList as $customField) {
        $customId = $customField['id'];
        $customFieldsSelect[] = 'p.' . $customId . ' AS ' . strtoupper($customId);
    }
    $customFieldsSelectStr = !empty($customFieldsSelect) ? ', ' . implode(', ', $customFieldsSelect) : '';
    $smsSelectSql = $showSmsColumn
        ? "p.sms_inviato AS SMS_INVIATO,\n            p.sms_error AS SMS_ERROR,"
        : '';
    $scontrinoValidatoSelectSql = $receiptValidationEnabled
        ? "p.scontrino_validato AS SCONTRINO_VALIDATO,\n            "
        : '';

    // Utiliser GROUP BY p.ID pour éviter les duplications dues aux JOINs
    $dataSql = "
        SELECT 
            p.ID,
            p.ID_CLIENTE,
            p.DATA_PARTECIPAZIONE,
            p.CODICE_PARTECIPAZIONE,
            p.IMPORTO_SCONTRINO,
            p.DATA_SCONTRINO,
            p.FILE_SCONTRINO,
            {$scontrinoValidatoSelectSql}p.ID_PDV_CONCORSO,
            p.INSEGNA,
            MAX(mpdv.ragsoc) AS PDV_RAGSOC,
            MAX(mpdv.insegna) AS PDV_INSEGNA,
            MAX(mpdv.indirizzo) AS PDV_INDIRIZZO,
            MAX(mpdv.citta) AS PDV_CITTA,
            MAX(mpdv.prov) AS PDV_PROV,
            MAX(mpdv.cap) AS PDV_CAP,
            p.HAS_WON,
            p.CONFERMATO,
            p.ANNULLATO,
            p.WIN_DATE,
            p.EMAIL_INVIATO,
            p.EMAIL_ERROR,
            {$smsSelectSql}
            MAX(u.NOME) AS NOME_CLIENTE,
            MAX(u.COGNOME) AS COGNOME_CLIENTE,
            MAX(u.EMAIL) AS EMAIL_CLIENTE,
            MAX(u.FIDELITY) AS FIDELITY_CLIENTE,
            MAX(u.PREFISSO) AS PREFISSO_CLIENTE,
            MAX(u.CELLULARE) AS CELLULARE_CLIENTE,
            MAX(u.INDIRIZZO) AS INDIRIZZO_CLIENTE,
            MAX(u.CITTA) AS CITTA_CLIENTE,
            MAX(u.PROVINCIA) AS PROVINCIA_CLIENTE,
            MAX(u.cap) AS CAP_CLIENTE,
            MAX(mpdv.codice) AS PDV_CODICE{$customFieldsSelectStr}
        {$baseQuery}
        {$whereSql}
        GROUP BY p.ID
        ORDER BY p.DATA_PARTECIPAZIONE DESC, p.ID DESC
        LIMIT :limit OFFSET :offset
    ";

    $dataStmt = $connection->prepare($dataSql);
    foreach ($bindings as $name => $value) {
        $dataStmt->bindValue(':' . $name, $value);
    }
    $dataStmt->bindValue(':limit', (int)$perPage, PDO::PARAM_INT);
    $dataStmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
    $dataStmt->execute();
    $partecipazioniRows = $dataStmt->fetchAll(PDO::FETCH_ASSOC);

    $showingFrom = $filteredRecords > 0 ? $offset + 1 : 0;
    $showingTo = $filteredRecords > 0 ? min($offset + count($partecipazioniRows), $filteredRecords) : 0;

    if (!function_exists('manager_safe_html')) {
        function manager_safe_html(string $value): string
        {
            return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }
    }

    if (!function_exists('manager_format_date_value')) {
        function manager_format_date_value(?string $value, string $format): string
        {
            if (empty($value)) {
                return '<span class="text-muted">-</span>';
            }

            $timestamp = strtotime($value);
            if ($timestamp === false) {
                return '<span class="text-muted">-</span>';
            }

            return '<span data-order="' . $timestamp . '">' . date($format, $timestamp) . '</span>';
        }
    }

    if (!function_exists('manager_format_currency_value')) {
        function manager_format_currency_value($value, bool $allowNull = false): string
        {
            if ($value === null) {
                return $allowNull ? '<span class="text-muted">-</span>' : '<span data-order="0.00">&euro; 0,00</span>';
            }

            $valueString = str_replace(["\u{00A0}", ' '], '', (string)$value);
            if (strpos($valueString, ',') !== false && strpos($valueString, '.') !== false) {
                $valueString = str_replace('.', '', $valueString);
            }
            $valueString = str_replace(',', '.', $valueString);
            $floatValue = (float)$valueString;
            $order = number_format($floatValue, 2, '.', '');
            $display = number_format($floatValue, 2, ',', '.');

            return '<span data-order="' . $order . '">&euro; ' . $display . '</span>';
        }
    }

    if (!function_exists('manager_format_plain_value')) {
        function manager_format_plain_value(?string $value): string
        {
            $trimmed = trim((string)$value);
            if ($trimmed === '') {
                return '<span class="text-muted">-</span>';
            }

            return manager_safe_html($trimmed);
        }
    }

    if (!function_exists('manager_format_boolean_badge')) {
        function manager_format_boolean_badge(bool $value, string $trueLabel, string $falseLabel, ?int $order = null): string
        {
            $orderAttr = $order !== null ? ' data-order="' . $order . '"' : '';
            if ($value) {
                return '<span class="badge bg-success"' . $orderAttr . '>' . manager_safe_html($trueLabel) . '</span>';
            }

            return '<span class="badge bg-secondary"' . $orderAttr . '>' . manager_safe_html($falseLabel) . '</span>';
        }
    }

    require_once __DIR__ . '/../res/inc/partecipazione_email_status.inc.php';

    if (!function_exists('manager_format_participant')) {
        function manager_format_participant(string $nome, string $cognome, string $email, string $cellulare, string $indirizzo = '', string $citta = '', string $provincia = '', string $cap = ''): string
        {
            $displayName = trim($nome . ' ' . $cognome);
            if ($displayName === '') {
                $displayName = $email !== '' ? $email : 'Cliente senza profilo';
            }

            $html = '<div><strong>' . manager_safe_html($displayName) . '</strong></div>';

            if ($email !== '') {
                $html .= '<div><small class="text-muted">' . manager_safe_html($email) . '</small></div>';
            }

            if ($cellulare !== '') {
                $html .= '<div><small class="text-muted">' . manager_safe_html($cellulare) . '</small></div>';
            }

            $addressLine = [];
            if ($indirizzo !== '') {
                $addressLine[] = manager_safe_html($indirizzo);
            }
            if ($citta !== '') {
                $cityProv = manager_safe_html($citta);
                if ($provincia !== '') {
                    $cityProv .= ' (' . manager_safe_html($provincia) . ')';
                }
                $addressLine[] = $cityProv;
            } elseif ($provincia !== '') {
                $addressLine[] = manager_safe_html($provincia);
            }
            if ($cap !== '') {
                $addressLine[] = manager_safe_html($cap);
            }
            if (!empty($addressLine)) {
                $html .= '<div><small class="text-muted">' . implode(' - ', $addressLine) . '</small></div>';
            }

            return $html;
        }
    }

    if (!function_exists('manager_format_custom_field_value')) {
        function manager_format_custom_field_value($value, string $type): string
        {
            if ($value === null || $value === '') {
                return '<span class="text-muted">-</span>';
            }
            
            $type = strtolower(trim($type));
            
            switch ($type) {
                case 'boolean':
                    $boolValue = ($value === '1' || $value === 1 || $value === true || strtolower((string)$value) === 'true');
                    return manager_format_boolean_badge($boolValue, 'Si', 'No', $boolValue ? 1 : 0);
                    
                case 'date':
                    return manager_format_date_value($value, 'd/m/Y');
                    
                case 'int':
                    return manager_format_plain_value((string)$value);
                    
                case 'text':
                case 'varchar':
                default:
                    $trimmed = trim((string)$value);
                    if ($trimmed === '') {
                        return '<span class="text-muted">-</span>';
                    }
                    if (strlen($trimmed) > 100) {
                        return manager_safe_html(substr($trimmed, 0, 100)) . '...';
                    }
                    return manager_format_plain_value($trimmed);
            }
        }
    }

    if (!function_exists('manager_format_punto_vendita')) {
        function manager_format_punto_vendita(string $ragsoc, string $codice, string $indirizzo, string $citta, string $prov, string $cap): string
        {
            if ($ragsoc === '' && $codice === '' && $indirizzo === '' && $citta === '' && $prov === '' && $cap === '') {
                return '<span class="text-muted">N/A</span>';
            }

            $parts = [];
            
            if ($ragsoc !== '') {
                $parts[] = '<strong>' . manager_safe_html($ragsoc) . '</strong>';
            }

            $addressLine = [];
            if ($indirizzo !== '') {
                $addressLine[] = manager_safe_html($indirizzo);
            }
            $cityProv = '';
            if ($citta !== '') {
                $cityProv = manager_safe_html($citta);
                if ($prov !== '') {
                    $cityProv .= ' (' . manager_safe_html($prov) . ')';
                }
            } elseif ($prov !== '') {
                $cityProv = manager_safe_html($prov);
            }
            if ($cityProv !== '') {
                $addressLine[] = $cityProv;
            }
            if ($cap !== '') {
                $addressLine[] = manager_safe_html($cap);
            }
            if (!empty($addressLine)) {
                $parts[] = implode(' - ', $addressLine);
            }

            if ($codice !== '') {
                $parts[] = '<small class="text-muted">Codice: ' . manager_safe_html($codice) . '</small>';
            }

            return implode('<br>', $parts);
        }
    }

    $uploadBaseDir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'scontrini' . DIRECTORY_SEPARATOR;
    $uploadBaseUrl = '../public/uploads/scontrini/';

    $buildPageUrl = function (int $targetPage) {
        $params = $_GET;
        $params['page'] = $targetPage;
        return '?' . http_build_query($params) . '#partecipazioni-list';
    };

    $columnsCount = 3; // #, Partecipante, Data Partecipazione
    if ($showEmailColumn) {
        $columnsCount++;
    }
    if ($showFidelityColumn) {
        $columnsCount++;
    }
    if ($showImportoColumn) {
        $columnsCount++;
    }
    if ($showDataScontrinoColumn) {
        $columnsCount++;
    }
    if ($showCodicePartecipazioneColumn) {
        $columnsCount++;
    }
    if ($showReceiptColumn) {
        $columnsCount++;
    }
    if ($showPdvColumn) {
        $columnsCount++;
    }
    if ($showWinnerColumn) {
        $columnsCount++;
    }
    if ($showConfirmedColumn) {
        $columnsCount++;
    }
    $columnsCount += count($customFieldsList);
    if ($showEmailSentColumn) {
        $columnsCount++;
    }
    if ($showSmsColumn) {
        $columnsCount++;
    }
    if ($showAzioniColumn) {
        $columnsCount++;
    }

?>

	<style>
		.rct {color:#ccc}
        .partecipazioni-table td {vertical-align:top !important}
        .dataTables_info {display:none}
        .toolbar-row {
            display: flex;
            align-items: flex-end;
            width: 100%;
            gap: 10px;
            margin-bottom: 20px;
            margin-top: 10px;
        }
        .partecipazioni-date-filter {
            display: flex;
            align-items: flex-end;
            flex-wrap: wrap;
            gap: 0.75rem;
        }
        .partecipazioni-date-filter .form-label {
            display: block;
        }
        .partecipazioni-date-filter input[type="date"] {
            min-width: 150px;
        }
        .toolbar-refresh,
        .toolbar-center,
        .toolbar-left,
        .toolbar-right {
            display: flex;
            align-items: center;
        }
        .toolbar-refresh {
            justify-content: flex-start;
        }
        .toolbar-center {
            flex: 1 1 auto;
            justify-content: center;
        }
        .toolbar-right {
            margin-left: auto;
            justify-content: flex-end;
            gap: 0.75rem;
        }
        .dataTables_filter {
            display: none !important;
        }
        .table-responsive {
            margin-top: 15px;
        }
        .actions-group {
            display: inline-flex;
            flex-wrap: nowrap;
            align-items: center;
            gap: 6px;
            white-space: nowrap;
            max-width: 100%;
            overflow-x: auto;
        }
        /* Dashboard minimal */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 18px;
            justify-items: center;
        }
        .stats-card {
            background: #f7f9fc;
            border: 1px solid #e5e8ef;
            border-radius: 12px;
            padding: 18px 22px;
            display: flex;
            align-items: center;
            gap: 16px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.06);
            max-width: 360px;
            width: 100%;
        }
        .stats-icon {
            width: 52px;
            height: 52px;
            border-radius: 14px;
            display: grid;
            place-items: center;
            background: #0d6efd;
            color: #fff;
            font-size: 20px;
            flex-shrink: 0;
        }
        .stats-meta {
            line-height: 1.3;
            text-align: left;
        }
        .stats-label {
            color: #6b7280;
            font-size: 13px;
            letter-spacing: 0.01em;
        }
        .stats-value {
            font-weight: 700;
            font-size: 24px;
            color: #111827;
            margin-top: 6px;
        }
        .partecipazioni-table th,
        .partecipazioni-table td {
            text-align: center;
            vertical-align: middle !important;
        }
        .partecipazioni-table td:first-child,
        .partecipazioni-table th:first-child {
            width: 60px;
        }
        .actions-group .btn {
            margin: 0;
            flex: 0 0 auto;
        }
        .partecipazioni-table tr.row-scontrino-valid > td {
            background-color: #d1e7dd !important;
        }
        .partecipazioni-table tr.row-scontrino-invalid > td {
            background-color: #f8d7da !important;
        }
        .partecipazioni-table tr.row-scontrino-valid:hover > td,
        .partecipazioni-table.table-hover tr.row-scontrino-valid:hover > td {
            background-color: #badbcc !important;
        }
        .partecipazioni-table tr.row-scontrino-invalid:hover > td,
        .partecipazioni-table.table-hover tr.row-scontrino-invalid:hover > td {
            background-color: #f1aeb5 !important;
        }
        @media (max-width: 992px) {
            .toolbar-row {
                flex-direction: column;
                align-items: stretch;
                gap: 10px;
            }
        .toolbar-refresh,
        .toolbar-center,
        .toolbar-left,
        .toolbar-right {
                width: 100%;
                justify-content: flex-start;
            }
            .toolbar-center {
                justify-content: flex-start;
            }
            .toolbar-right {
                margin-bottom: 0;
                margin-left: 0;
            }
            #search {
                width: 100% !important;
            }
            .partecipazioni-date-filter input[type="date"] {
                width: 100%;
            }
        }
	</style>

    <div class="app-main__outer" id="custom_filter" data-val="<?php echo $_usr_id; ?>" data-tp="<?php echo $tipo; ?>">
                <div class="app-main__inner">
                    <div class="tabs-animation">

                        <!-- Dashboard Partecipazioni -->
                        <div class="mb-3 card">
                            <div class="card-header d-flex align-items-center justify-content-between" style="border-bottom:1px solid #eef1f4;">
                                <div class="fw-semibold text-muted text-uppercase" style="letter-spacing:0.04em;">Dashboard Partecipazioni</div>
                            </div>
                            <div class="card-body">
                                <div class="stats-grid">
                                    <div class="stats-card">
                                        <div class="stats-icon"><i class="fa fa-clipboard-list"></i></div>
                                        <div class="stats-meta">
                                            <div class="stats-label">Totale Partecipazioni</div>
                                            <div class="stats-value"><?php echo $totale_partecipazioni; ?></div>
                                        </div>
                                    </div>
                                    <?php if ($showWinnerCounter): ?>
                                        <div class="stats-card">
                                            <div class="stats-icon" style="background:#198754;"><i class="fa fa-trophy"></i></div>
                                            <div class="stats-meta">
                                                <div class="stats-label">Totale Vincitori</div>
                                                <div class="stats-value"><?php echo $totale_vincitori; ?></div>
                                            </div>
                                        </div>
                                        <?php if ($managerConfirmFlow): ?>
                                            <div class="stats-card">
                                                <div class="stats-icon" style="background:#0d6efd;"><i class="fa fa-check-circle"></i></div>
                                                <div class="stats-meta">
                                                    <div class="stats-label">Vittorie Confermate</div>
                                                    <div class="stats-value"><?php echo $vincitori_confermati; ?></div>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                        <?php if ($managerCancelFlow): ?>
                                            <div class="stats-card">
                                                <div class="stats-icon" style="background:#dc3545;"><i class="fa fa-ban"></i></div>
                                                <div class="stats-meta">
                                                    <div class="stats-label">Vittorie Annullate</div>
                                                    <div class="stats-value"><?php echo $vincitori_annullati; ?></div>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="card mb-3">
                            <div class="card-header-tab card-header d-flex align-items-center justify-content-between">
                                <div class="card-header-title font-size-lg text-capitalize font-weight-normal flex-grow-1">
                                    <i class="header-icon lnr-users icon-gradient bg-happy-green"> </i>
                                    <?php echo $_title; ?>
                                </div>
                                <div class="btn-actions-pane-right text-capitalize ms-auto d-flex justify-content-end">
                                    <?php
                                        $exportParams = [
                                            'date_from' => $dateFrom,
                                            'date_to' => $dateTo,
                                        ];
                                        if ($searchQuery !== '') { $exportParams['search'] = $searchQuery; }
                                        if ($filterStatus !== '') { $exportParams['filterStatus'] = $filterStatus; }
                                        if ($filterScontrino !== '') { $exportParams['filterScontrino'] = $filterScontrino; }
                                        if ($tipo !== '') { $exportParams['tp'] = $tipo; }
                                        $exportUrl = 'res/inc/export_partecipanti_excel.php' . (empty($exportParams) ? '' : '?' . http_build_query($exportParams));
                                    ?>
                                    <a href="<?php echo manager_safe_html($exportUrl); ?>" class="btn btn-success" style="margin:0 10px">Esporta (CSV)</a>

                                </div>
                            </div>
                            <div class="card-body">
                                <form method="get" action="<?php echo manager_safe_html($_SERVER['PHP_SELF']); ?>" class="toolbar-row mb-3">
                                    <input type="hidden" name="pg" value="partecipazioni">
                                    <input type="hidden" name="page" value="1">
                                    <input type="hidden" name="per_page" value="<?php echo (int)$perPage; ?>">
                                    <?php if ($tipo !== ''): ?>
                                        <input type="hidden" name="tp" value="<?php echo manager_safe_html($tipo); ?>">
                                    <?php endif; ?>
                                    <div class="toolbar-refresh me-3">
                                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.location.reload();">Aggiorna</button>
                                    </div>
                                    <div class="toolbar-left partecipazioni-date-filter me-3">
                                        <div>
                                            <label for="partecipazioni-date-from" class="form-label mb-1 small text-muted">Data inizio</label>
                                            <input type="date" class="form-control form-control-sm" id="partecipazioni-date-from" name="date_from" value="<?php echo manager_safe_html($dateFrom); ?>" onchange="this.form.submit()">
                                        </div>
                                        <div>
                                            <label for="partecipazioni-date-to" class="form-label mb-1 small text-muted">Data fine</label>
                                            <input type="date" class="form-control form-control-sm" id="partecipazioni-date-to" name="date_to" value="<?php echo manager_safe_html($dateTo); ?>" onchange="this.form.submit()">
                                        </div>
                                    </div>
                                    <div class="toolbar-center">
                                    </div>
                                    <div class="toolbar-right d-flex align-items-end flex-wrap gap-2">
                                        <?php if ($showWinnerColumn): ?>
                                        <div class="me-2 d-flex align-items-center">
                                            <select name="filterStatus" class="form-control form-control-sm" style="width: auto; min-width: 180px;" onchange="this.form.submit()" title="Filtra partecipazioni">
                                                <option value="" <?php echo ($filterStatus === '') ? 'selected' : ''; ?>>Tutti</option>
                                                <option value="1" <?php echo ($filterStatus === '1') ? 'selected' : ''; ?>>Vincitore Sì</option>
                                                <option value="0" <?php echo ($filterStatus === '0') ? 'selected' : ''; ?>>Vincitore No</option>
                                                <?php if ($managerConfirmFlow): ?>
                                                <option value="confirmed" <?php echo ($filterStatus === 'confirmed') ? 'selected' : ''; ?>>Confermato Sì</option>
                                                <option value="not_confirmed" <?php echo ($filterStatus === 'not_confirmed') ? 'selected' : ''; ?>>Confermato No</option>
                                                <?php endif; ?>
                                                <?php if ($managerCancelFlow): ?>
                                                <option value="cancelled" <?php echo ($filterStatus === 'cancelled') ? 'selected' : ''; ?>>Vittorie Annullate</option>
                                                <?php endif; ?>
                                            </select>
                                        </div>
                                        <?php endif; ?>
                                        <?php if ($estrazioneReceiptFlow): ?>
                                        <div class="me-2 d-flex align-items-center">
                                            <select name="filterScontrino" class="form-control form-control-sm" style="width: auto; min-width: 180px;" onchange="this.form.submit()" title="Filtra validazione scontrino">
                                                <option value="" <?php echo ($filterScontrino === '') ? 'selected' : ''; ?>>Scontrino: tutti</option>
                                                <option value="valid" <?php echo ($filterScontrino === 'valid') ? 'selected' : ''; ?>>Scontrino valido</option>
                                                <option value="invalid" <?php echo ($filterScontrino === 'invalid') ? 'selected' : ''; ?>>Scontrino non valido</option>
                                                <option value="pending" <?php echo ($filterScontrino === 'pending') ? 'selected' : ''; ?>>Da validare</option>
                                            </select>
                                        </div>
                                        <?php endif; ?>
                                        <div class="me-3">
                                            <input type="search" id="search" name="search" class="form-control form-control-sm" placeholder="Cerca..." style="width:320px;" value="<?php echo manager_safe_html($searchQuery); ?>" onsearch="this.form.submit()" onkeydown="if(event.key === 'Enter'){ this.form.submit(); }" title="Cerca in: Nome, Cognome, Email, Telefono, Indirizzo, CittÃ , Provincia, Punto Vendita, Insegna, Codice PDV, Importo, Date">
                                        </div>
                                        <button type="submit" class="btn btn-sm btn-primary">Cerca</button>
                                    </div>
                                </form>
                                <div class="table-responsive">
                                    <table id="partecipazioni-list" style="width: 100%; min-width: 900px;" class="table table-hover table-striped table-bordered partecipazioni-table">
                                        <thead>
                                        <tr>
                                            <th class="text-center" style="width: 60px;">#</th>
                                            <th>Partecipante</th>
                                            <?php if ($showEmailColumn): ?>
                                                <th>Email</th>
                                            <?php endif; ?>
                                            <?php if ($showFidelityColumn): ?>
                                                <th>Fidelity</th>
                                            <?php endif; ?>
                                            <th>Data Partecipazione</th>
                                            <?php if ($showCodicePartecipazioneColumn): ?>
                                                <th>Codice partecipazione</th>
                                            <?php endif; ?>
                                            <?php if ($showImportoColumn): ?>
                                                <th>Importo Scontrino</th>
                                            <?php endif; ?>
                                            <?php if ($showDataScontrinoColumn): ?>
                                                <th>Data Scontrino</th>
                                            <?php endif; ?>
                                            <?php if ($showReceiptColumn): ?>
                                                <th class="text-center" style="width: 90px;">Foto scontrino</th>
                                            <?php endif; ?>
                                            <?php if ($showPdvColumn): ?>
                                                <th>Punto Vendita</th>
                                            <?php endif; ?>
                                            <?php if ($showWinnerColumn): ?>
                                                <th>Vincitore</th>
                                            <?php endif; ?>
                                            <?php if ($showConfirmedColumn): ?>
                                                <th>Confermato</th>
                                            <?php endif; ?>
                                            <?php foreach ($customFieldsList as $customField): ?>
                                                <th><?php echo manager_safe_html($customField['label']); ?></th>
                                            <?php endforeach; ?>
                                            <?php if ($showEmailSentColumn): ?>
                                                <th>Email Inviato</th>
                                            <?php endif; ?>
                                            <?php if ($showSmsColumn): ?>
                                                <th>SMS inviato</th>
                                            <?php endif; ?>
                                            <?php if ($showAzioniColumn): ?>
                                                <th>Azioni</th>
                                            <?php endif; ?>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <?php if (empty($partecipazioniRows)): ?>
                                            <tr>
                                                <td colspan="<?php echo (int)$columnsCount; ?>" class="text-center text-muted">Nessuna partecipazione trovata.</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php $rowNumber = $offset; ?>
                                            <?php foreach ($partecipazioniRows as $row): ?>
                                                <?php
                                                    $rowNumber++;
                                                    $id = (int)($row['ID'] ?? 0);
                                                    $idCliente = (int)($row['ID_CLIENTE'] ?? 0);
                                                    $hasWon = (int)($row['HAS_WON'] ?? 0) === 1;
                                                    $confermatoRaw = (int)($row['CONFERMATO'] ?? 0);
                                                    $annullatoRaw = (int)($row['ANNULLATO'] ?? 0);
                                                    $emailSentRaw = (int)($row['EMAIL_INVIATO'] ?? $row['email_inviato'] ?? 0);
                                                    $emailErrorRaw = (string)($row['EMAIL_ERROR'] ?? $row['email_error'] ?? '');
                                                    $nomeCliente = ($fieldsEnabled['nome'] ?? true) ? (string)($row['NOME_CLIENTE'] ?? '') : '';
                                                    $cognomeCliente = ($fieldsEnabled['cognome'] ?? true) ? (string)($row['COGNOME_CLIENTE'] ?? '') : '';
                                                    $emailClienteRaw = $showEmailColumn ? (string)($row['EMAIL_CLIENTE'] ?? '') : '';
                                                    $fidelityCliente = $showFidelityColumn ? trim((string)($row['FIDELITY_CLIENTE'] ?? '')) : '';
                                                    $prefissoCliente = ($fieldsEnabled['prefisso'] ?? true) ? (string)($row['PREFISSO_CLIENTE'] ?? '') : '';
                                                    $cellulareClienteRaw = ($fieldsEnabled['cellulare'] ?? true) ? (string)($row['CELLULARE_CLIENTE'] ?? '') : '';
                                                    $cellulareClienteFormatted = $cellulareClienteRaw !== '' ? formatPhoneWithPrefix($prefissoCliente, $cellulareClienteRaw) : '';
                                                    $indirizzoCliente = ($fieldsEnabled['indirizzo'] ?? true) ? trim((string)($row['INDIRIZZO_CLIENTE'] ?? '')) : '';
                                                    $cittaCliente = ($fieldsEnabled['citta'] ?? true) ? trim((string)($row['CITTA_CLIENTE'] ?? '')) : '';
                                                    $provinciaCliente = ($fieldsEnabled['provincia'] ?? true) ? trim((string)($row['PROVINCIA_CLIENTE'] ?? '')) : '';
                                                    $capCliente = ($fieldsEnabled['cap'] ?? false) ? trim((string)($row['CAP_CLIENTE'] ?? '')) : '';
                                                    $partecipante = manager_format_participant(
                                                        $nomeCliente,
                                                        $cognomeCliente,
                                                        $emailClienteRaw,
                                                        $cellulareClienteFormatted,
                                                        $indirizzoCliente,
                                                        $cittaCliente,
                                                        $provinciaCliente,
                                                        $capCliente
                                                    );
                                                    $emailCliente = $showEmailColumn ? manager_format_plain_value($emailClienteRaw) : '';
                                                    $dataPartecipazione = manager_format_date_value($row['DATA_PARTECIPAZIONE'] ?? null, 'd/m/Y H:i');
                                                    $codicePartecipazioneRaw = $showCodicePartecipazioneColumn
                                                        ? trim((string)($row['CODICE_PARTECIPAZIONE'] ?? ''))
                                                        : '';
                                                    $codicePartecipazioneCell = $codicePartecipazioneRaw !== ''
                                                        ? '<code class="small">' . manager_safe_html($codicePartecipazioneRaw) . '</code>'
                                                        : '<span class="text-muted">-</span>';
                                                    $importoScontrino = $showImportoColumn ? manager_format_currency_value($row['IMPORTO_SCONTRINO']) : '';
                                                    $dataScontrino = $showDataScontrinoColumn ? manager_format_date_value($row['DATA_SCONTRINO'] ?? null, 'd/m/Y') : '';
                                                    $codicePdv = trim((string)($row['PDV_CODICE'] ?? ''));
                                                    $ragsocPdv = trim((string)($row['PDV_RAGSOC'] ?? ''));
                                                    $insegnaPdv = trim((string)($row['PDV_INSEGNA'] ?? ''));
                                                    $insegna = trim((string)($row['INSEGNA'] ?? ''));
                                                    if ($ragsocPdv === '') {
                                                        $ragsocPdv = $insegnaPdv !== '' ? $insegnaPdv : $insegna;
                                                    }
                                                    $puntoVendita = '';
                                                    if ($showPdvColumn) {
                                                        $indirizzoPdv = ($fieldsEnabled['indirizzo_pdv'] ?? true) ? (string)($row['PDV_INDIRIZZO'] ?? '') : '';
                                                        $cittaPdv = ($fieldsEnabled['citta_pdv'] ?? true) ? (string)($row['PDV_CITTA'] ?? '') : '';
                                                        $provPdv = ($fieldsEnabled['prov_pdv'] ?? true) ? (string)($row['PDV_PROV'] ?? '') : '';
                                                        $puntoVendita = manager_format_punto_vendita(
                                                            $ragsocPdv,
                                                            $codicePdv,
                                                            $indirizzoPdv,
                                                            $cittaPdv,
                                                            $provPdv,
                                                            (string)($row['PDV_CAP'] ?? '')
                                                        );
                                                    }
                                                    $emailInviato = manager_participation_email_status_badge(
                                                        $emailSentRaw,
                                                        $emailErrorRaw,
                                                        static fn(string $s): string => manager_safe_html($s)
                                                    );
                                                    $smsInviato = '';
                                                    if ($showSmsColumn) {
                                                        $smsSentRaw = (int)($row['SMS_INVIATO'] ?? $row['sms_inviato'] ?? 0);
                                                        $smsErrorRaw = (string)($row['SMS_ERROR'] ?? $row['sms_error'] ?? '');
                                                        $smsInviato = manager_participation_delivery_status_badge(
                                                            $smsSentRaw,
                                                            $smsErrorRaw,
                                                            static fn(string $s): string => manager_safe_html($s)
                                                        );
                                                    }

                                                    $fileName = (string)($row['FILE_SCONTRINO'] ?? '');
                                                    $fileUrl = '';
                                                    if ($fileName !== '') {
                                                        $fullPath = $uploadBaseDir . $fileName;
                                                        if (is_file($fullPath)) {
                                                            $fileUrl = $uploadBaseUrl . rawurlencode($fileName);
                                                        }
                                                    }

                                                    $scontrinoValidatoRaw = null;
                                                    if ($receiptValidationEnabled) {
                                                        $rawValidato = $row['SCONTRINO_VALIDATO'] ?? $row['scontrino_validato'] ?? null;
                                                        if ($rawValidato !== null && $rawValidato !== '') {
                                                            $scontrinoValidatoRaw = (int)$rawValidato === 1 ? 1 : 0;
                                                        }
                                                    }

                                                    $azioniButtons = [];
                                                    if ($showAzioniColumn) {
                                                        $rowShowEmailActions = false;
                                                        if ($isInstantWin) {
                                                            $rowShowEmailActions = $hasWon ? $emailIwWinEnabled : $emailIwLossEnabled;
                                                        } else {
                                                            $rowShowEmailActions = $emailPartecipazioneEnabled;
                                                        }

                                                        // IW + scontrino + Conferma manager: ✓/✗ solo vincite (definitivo).
                                                        // Estrazione + scontrino: ✓/✗ su tutte le foto (reversibile).
                                                        // IW perdite / IW senza Conferma manager: nessun ✓/✗.
                                                        $showValidateButtons = false;
                                                        $lockValidateButtons = false;
                                                        if ($fileUrl !== '') {
                                                            if ($iwManagerReceiptFlow && $hasWon) {
                                                                $showValidateButtons = true;
                                                                $lockValidateButtons = true;
                                                            } elseif ($estrazioneReceiptFlow) {
                                                                $showValidateButtons = true;
                                                                $lockValidateButtons = false;
                                                            }
                                                        }

                                                        if ($showValidateButtons) {
                                                            $validBtnClass = $scontrinoValidatoRaw === 1 ? 'btn-success' : 'btn-outline-success';
                                                            $invalidBtnClass = $scontrinoValidatoRaw === 0 ? 'btn-danger' : 'btn-outline-danger';
                                                            $lockAttr = $lockValidateButtons ? ' data-lock-emails="1"' : '';
                                                            $lockDisabled = $lockValidateButtons && (
                                                                $scontrinoValidatoRaw !== null
                                                                || $confermatoRaw === 1
                                                                || $annullatoRaw === 1
                                                            );
                                                            $disabledAttr = $lockDisabled ? ' disabled' : '';
                                                            $azioniButtons[] = '<button type="button" class="btn btn-sm ' . $validBtnClass . ' btnValidateScontrino" data-participation-id="' . $id . '" data-status="1" title="Scontrino valido"' . $lockAttr . $disabledAttr . '><i class="fa fa-check"></i></button>';
                                                            $azioniButtons[] = '<button type="button" class="btn btn-sm ' . $invalidBtnClass . ' btnValidateScontrino" data-participation-id="' . $id . '" data-status="0" title="Scontrino non valido"' . $lockAttr . $disabledAttr . '><i class="fa fa-times"></i></button>';
                                                        }

                                                        if (!$receiptValidationEnabled) {
                                                            if (!$showReceiptColumn && $showReceiptAction && $fileUrl !== '') {
                                                                $fileExt = strtolower((string)pathinfo($fileName, PATHINFO_EXTENSION));
                                                                $azioniButtons[] = '<button type="button" class="btn btn-sm btn-outline-info btnViewReceipt" data-file-url="' . manager_safe_html($fileUrl) . '" data-file-name="' . manager_safe_html($fileName !== '' ? $fileName : 'Scontrino') . '" data-file-ext="' . manager_safe_html($fileExt) . '"><i class="fa fa-file"></i></button>';
                                                            }
                                                            if ($enableConfirmWinAction && $hasWon && $annullatoRaw !== 1 && $confermatoRaw !== 1) {
                                                                $azioniButtons[] = '<button type="button" class="btn btn-sm btn-outline-success btnConfirmWin" data-participation-id="' . $id . '"><i class="fa fa-check"></i></button>';
                                                            }
                                                            if ($enableCancelWinAction && $hasWon && $annullatoRaw !== 1 && $confermatoRaw !== 1) {
                                                                $azioniButtons[] = '<button type="button" class="btn btn-sm btn-outline-danger btnCancelWin" data-participation-id="' . $id . '"><i class="fa fa-times"></i></button>';
                                                            }
                                                        }

                                                        if ($rowShowEmailActions) {
                                                            $azioniButtons[] = '<button type="button" class="btn btn-sm btn-outline-secondary btnLastEmailPreview" data-participation-id="' . $id . '"><i class="fa fa-eye"></i></button>';
                                                            $azioniButtons[] = '<button type="button" class="btn btn-sm btn-outline-primary btnResendEmail" data-participation-id="' . $id . '" data-email-type="result" data-email-label="Reinvia ultima email"><i class="fa fa-envelope"></i></button>';
                                                        }
                                                    }

                                                    $azioni = !empty($azioniButtons)
                                                        ? '<div class="actions-group">' . implode('', $azioniButtons) . '</div>'
                                                        : '<span class="text-muted">-</span>';

                                                    $fotoScontrinoCell = '<span class="text-muted">-</span>';
                                                    if ($showReceiptColumn && $fileUrl !== '') {
                                                        $fileExt = strtolower((string)pathinfo($fileName, PATHINFO_EXTENSION));
                                                        $fotoScontrinoCell = '<button type="button" class="btn btn-sm btn-outline-primary btnViewReceipt" data-file-url="' . manager_safe_html($fileUrl) . '" data-file-name="' . manager_safe_html($fileName !== '' ? $fileName : 'Scontrino') . '" data-file-ext="' . manager_safe_html($fileExt) . '"><i class="fa fa-image me-1"></i></button>';
                                                    }

                                                    $rowClass = '';
                                                    if ($scontrinoValidatoRaw === 1) {
                                                        $rowClass = 'row-scontrino-valid';
                                                    } elseif ($scontrinoValidatoRaw === 0) {
                                                        $rowClass = 'row-scontrino-invalid';
                                                    }
                                                ?>
                                                <tr class="<?php echo manager_safe_html($rowClass); ?>" data-participation-id="<?php echo (int)$id; ?>" data-scontrino-status="<?php echo $scontrinoValidatoRaw === null ? '' : (int)$scontrinoValidatoRaw; ?>">
                                                    <td class="text-muted text-end"><?php echo $rowNumber; ?></td>
                                                    <td><?php echo $partecipante; ?></td>
                                                    <?php if ($showEmailColumn): ?>
                                                        <td><?php echo $emailCliente; ?></td>
                                                    <?php endif; ?>
                                                    <?php if ($showFidelityColumn): ?>
                                                        <td><?php echo manager_format_plain_value($fidelityCliente); ?></td>
                                                    <?php endif; ?>
                                                    <td><?php echo $dataPartecipazione; ?></td>
                                                    <?php if ($showCodicePartecipazioneColumn): ?>
                                                        <td><?php echo $codicePartecipazioneCell; ?></td>
                                                    <?php endif; ?>
                                                    <?php if ($showImportoColumn): ?>
                                                        <td><?php echo $importoScontrino; ?></td>
                                                    <?php endif; ?>
                                                    <?php if ($showDataScontrinoColumn): ?>
                                                        <td><?php echo $dataScontrino; ?></td>
                                                    <?php endif; ?>
                                                    <?php if ($showReceiptColumn): ?>
                                                        <td class="text-center"><?php echo $fotoScontrinoCell; ?></td>
                                                    <?php endif; ?>
                                                    <?php if ($showPdvColumn): ?>
                                                        <td><?php echo $puntoVendita; ?></td>
                                                    <?php endif; ?>
                                                    <?php if ($showWinnerColumn): ?>
                                                        <td><?php echo manager_format_boolean_badge($hasWon, 'Si', 'No', $hasWon ? 1 : 0); ?></td>
                                                    <?php endif; ?>
                                                    <?php if ($showConfirmedColumn): ?>
                                                        <td>
                                                            <?php
                                                                if ($annullatoRaw === 1) {
                                                                    echo '<span class="badge bg-danger" data-order="2">Annullato</span>';
                                                                } elseif ($hasWon) {
                                                                    echo manager_format_boolean_badge($confermatoRaw === 1, 'Si', 'No', $confermatoRaw);
                                                                } else {
                                                                    echo '<span class="text-muted">-</span>';
                                                                }
                                                            ?>
                                                        </td>
                                                    <?php endif; ?>
                                                    <?php foreach ($customFieldsList as $customField): ?>
                                                        <?php
                                                            $customId = $customField['id'];
                                                            $customType = $customField['type'];
                                                            $columnKey = strtoupper($customId);
                                                            $rawValue = $row[$columnKey] ?? null;
                                                            $formattedValue = manager_format_custom_field_value($rawValue, $customType);
                                                        ?>
                                                        <td><?php echo $formattedValue; ?></td>
                                                    <?php endforeach; ?>
                                                    <?php if ($showEmailSentColumn): ?>
                                                        <td><?php echo $emailInviato; ?></td>
                                                    <?php endif; ?>
                                                    <?php if ($showSmsColumn): ?>
                                                        <td><?php echo $smsInviato; ?></td>
                                                    <?php endif; ?>
                                                    <?php if ($showAzioniColumn): ?>
                                                        <td><?php echo $azioni; ?></td>
                                                    <?php endif; ?>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="d-flex align-items-center flex-wrap gap-3 mt-3">
                                    <div class="text-muted small flex-grow-1">
                                        <?php if ($filteredRecords > 0): ?>
                                            Mostrando <?php echo $showingFrom; ?>-<?php echo $showingTo; ?> di <?php echo $filteredRecords; ?> partecipazioni
                                        <?php else: ?>
                                            Nessuna partecipazione da mostrare
                                        <?php endif; ?>
                                    </div>
                                    <div class="d-flex align-items-center justify-content-end flex-wrap ms-auto" style="gap: 1.5rem;">
                                        <nav class="mb-2 mb-sm-0">
                                            <ul class="pagination pagination-sm mb-0">
                                                <?php
                                                    $prevDisabled = $page <= 1;
                                                    $nextDisabled = $page >= $totalPages;
                                                ?>
                                                <li class="page-item<?php echo $prevDisabled ? ' disabled' : ''; ?>">
                                                    <?php if ($prevDisabled): ?>
                                                        <span class="page-link">Prec</span>
                                                    <?php else: ?>
                                                        <a class="page-link" href="<?php echo manager_safe_html($buildPageUrl($page - 1)); ?>">Prec</a>
                                                    <?php endif; ?>
                                                </li>
                                                <?php
                                                    $window = 2;
                                                    $startPage = max(1, $page - $window);
                                                    $endPage = min($totalPages, $page + $window);
                                                    if ($endPage - $startPage < ($window * 2)) {
                                                        $endPage = min($totalPages, $startPage + $window * 2);
                                                        $startPage = max(1, $endPage - $window * 2);
                                                    }
                                                ?>
                                                <?php if ($startPage > 1): ?>
                                                    <li class="page-item">
                                                        <a class="page-link" href="<?php echo manager_safe_html($buildPageUrl(1)); ?>">1</a>
                                                    </li>
                                                    <?php if ($startPage > 2): ?>
                                                        <li class="page-item disabled"><span class="page-link">&hellip;</span></li>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                                <?php for ($p = $startPage; $p <= $endPage; $p++): ?>
                                                    <li class="page-item<?php echo $p === $page ? ' active' : ''; ?>">
                                                        <?php if ($p === $page): ?>
                                                            <span class="page-link"><?php echo $p; ?></span>
                                                        <?php else: ?>
                                                            <a class="page-link" href="<?php echo manager_safe_html($buildPageUrl($p)); ?>"><?php echo $p; ?></a>
                                                        <?php endif; ?>
                                                    </li>
                                                <?php endfor; ?>
                                                <?php if ($endPage < $totalPages): ?>
                                                    <?php if ($endPage < $totalPages - 1): ?>
                                                        <li class="page-item disabled"><span class="page-link">&hellip;</span></li>
                                                    <?php endif; ?>
                                                    <li class="page-item">
                                                        <a class="page-link" href="<?php echo manager_safe_html($buildPageUrl($totalPages)); ?>"><?php echo $totalPages; ?></a>
                                                    </li>
                                                <?php endif; ?>
                                                <li class="page-item<?php echo $nextDisabled ? ' disabled' : ''; ?>">
                                                    <?php if ($nextDisabled): ?>
                                                        <span class="page-link">Succ</span>
                                                    <?php else: ?>
                                                        <a class="page-link" href="<?php echo manager_safe_html($buildPageUrl($page + 1)); ?>">Succ</a>
                                                    <?php endif; ?>
                                                </li>
                                            </ul>
                                        </nav>
                                        <form method="get" action="<?php echo manager_safe_html($_SERVER['PHP_SELF']); ?>" class="mt-2 mt-sm-0">
                                            <input type="hidden" name="pg" value="partecipazioni">
                                            <input type="hidden" name="page" value="1">
                                            <input type="hidden" name="date_from" value="<?php echo manager_safe_html($dateFrom); ?>">
                                            <input type="hidden" name="date_to" value="<?php echo manager_safe_html($dateTo); ?>">
                                            <?php if ($searchQuery !== ''): ?>
                                                <input type="hidden" name="search" value="<?php echo manager_safe_html($searchQuery); ?>">
                                            <?php endif; ?>
                                            <?php if ($filterStatus !== ''): ?>
                                                <input type="hidden" name="filterStatus" value="<?php echo manager_safe_html($filterStatus); ?>">
                                            <?php endif; ?>
                                            <?php if ($filterScontrino !== ''): ?>
                                                <input type="hidden" name="filterScontrino" value="<?php echo manager_safe_html($filterScontrino); ?>">
                                            <?php endif; ?>
                                            <?php if ($tipo !== ''): ?>
                                                <input type="hidden" name="tp" value="<?php echo manager_safe_html($tipo); ?>">
                                            <?php endif; ?>
                                            <select id="per_page" name="per_page" class="form-select form-select-sm" onchange="this.form.submit()">
                                                <?php foreach ($perPageOptions as $option): ?>
                                                    <option value="<?php echo $option; ?>" <?php echo $option === $perPage ? 'selected' : ''; ?>><?php echo $option; ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

<?php include("_footer.php"); ?>





