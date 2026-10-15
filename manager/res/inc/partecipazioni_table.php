<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/partecipazione_email_status.inc.php';

try {
    $connection->exec("SET NAMES utf8mb4");
    $connection->exec("SET CHARACTER SET utf8mb4");
    $connection->exec("SET collation_connection = 'utf8mb4_unicode_ci'");

    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        $draw = isset($_POST['draw']) ? (int)$_POST['draw'] : 0;
        echo json_encode([
            'data' => [],
            'recordsTotal' => 0,
            'recordsFiltered' => 0,
            'draw' => $draw,
            'error' => 'Sessione scaduta. Effettua nuovamente il login.'
        ]);
        exit;
    }

    $start = isset($_POST['start']) ? max((int)$_POST['start'], 0) : 0;
    $length = isset($_POST['length']) ? (int)$_POST['length'] : 10;
    $length = $length > 0 ? $length : 10;
    $search = isset($_POST['search']['value']) ? trim($_POST['search']['value']) : '';
    $filterStatus = isset($_POST['filterStatus']) ? trim((string)$_POST['filterStatus']) : '';
    $filterScontrino = isset($_POST['filterScontrino']) ? trim((string)$_POST['filterScontrino']) : '';

    $orderColumn = isset($_POST['order'][0]['column']) ? (int)$_POST['order'][0]['column'] : 1;
    $orderDir = isset($_POST['order'][0]['dir']) && strtolower($_POST['order'][0]['dir']) === 'asc' ? 'ASC' : 'DESC';

    $columnsMap = [
        'p.id',
        "COALESCE(u.cognome, '')",
        'p.data_partecipazione',
        'p.importo_scontrino',
        'p.data_scontrino',
        'p.insegna',
        'p.has_won',
        'p.confermato',
        'p.annullato',
        'p.win_date',
        'p.email_inviato',
        'p.id'
    ];

    if (!isset($columnsMap[$orderColumn])) {
        $orderColumn = 1;
    }
    $orderBy = $columnsMap[$orderColumn];

    $whereConditions = [];
    $bindings = [];

    if ($search !== '') {
        $like = "%{$search}%";
        $searchConditions = [
            "CAST(p.id AS CHAR) LIKE ?",
            "CAST(p.id_cliente AS CHAR) LIKE ?",
            "DATE_FORMAT(p.data_partecipazione, '%d/%m/%Y %H:%i') LIKE ?",
            "DATE_FORMAT(p.data_scontrino, '%d/%m/%Y') LIKE ?",
            "p.insegna LIKE ?",
            "CAST(p.confermato AS CHAR) LIKE ?",
            "CAST(p.annullato AS CHAR) LIKE ?",
            "mpdv.ragsoc LIKE ?",
            "mpdv.indirizzo LIKE ?",
            "mpdv.citta LIKE ?",
            "mpdv.prov LIKE ?",
            "mpdv.cap LIKE ?",
            "CAST(p.id_pdv_concorso AS CHAR) LIKE ?",
            "CAST(p.prize_id AS CHAR) LIKE ?",
            "DATE_FORMAT(p.win_date, '%d/%m/%Y %H:%i') LIKE ?",
            "CAST(p.email_inviato AS CHAR) LIKE ?",
            "u.nome LIKE ?",
            "u.cognome LIKE ?",
            "u.email LIKE ?"
        ];

        $whereConditions[] = '(' . implode(' OR ', $searchConditions) . ')';

        foreach ($searchConditions as $_condition) {
            $bindings[] = [
                'value' => $like,
                'type' => PDO::PARAM_STR
            ];
        }
    }

    if ($filterStatus !== '') {
        switch ($filterStatus) {
            case '1':
            case '0':
                $whereConditions[] = 'p.has_won = ?';
                $bindings[] = [
                    'value' => (int)$filterStatus,
                    'type' => PDO::PARAM_INT
                ];
                break;
            case 'confirmed':
                $whereConditions[] = 'p.has_won = 1';
                $whereConditions[] = 'p.confermato = 1';
                $whereConditions[] = 'p.annullato = 0';
                break;
            case 'not_confirmed':
                $whereConditions[] = 'p.has_won = 1';
                $whereConditions[] = 'p.confermato = 0';
                $whereConditions[] = 'p.annullato = 0';
                break;
            case 'cancelled':
                $whereConditions[] = 'p.annullato = 1';
                break;
        }
    }

    // Récupérer les champs personnalisés depuis la configuration
    $customFields = $contest_config['custom_fields'] ?? [];
    if (!is_array($customFields)) {
        $customFields = [];
    }
    
    // Construire la liste des colonnes personnalisées pour le SELECT
    $customFieldsSelect = [];
    $customFieldsData = [];
    foreach ($customFields as $customField) {
        if (!is_array($customField)) {
            continue;
        }
        $customId = trim((string)($customField['id'] ?? ''));
        if ($customId === '') {
            continue;
        }
        $customColumn = preg_replace('/[^a-z0-9_]/i', '_', $customId);
        if ($customColumn === '') {
            continue;
        }
        $customAlias = strtoupper(preg_replace('/[^A-Z0-9_]/', '_', strtoupper($customColumn)));
        // Le nom de la colonne dans la DB est le même que l'ID (sanitizzato)
        $columnName = 'p.`' . $customColumn . '`';
        $customFieldsSelect[] = $columnName . ' AS CUSTOM_' . $customAlias;
        $customFieldsData[] = [
            'id' => $customColumn,
            'label' => trim((string)($customField['label'] ?? '')),
            'type' => strtolower(trim((string)($customField['type'] ?? 'varchar')))
        ];
    }
    $customFieldsSelectStr = !empty($customFieldsSelect) ? ', ' . implode(', ', $customFieldsSelect) : '';

    $contestMode = isset($contest_mode) ? strtolower((string)$contest_mode) : '';
    $fields_enabled_cfg = $contest_config['fields_enabled'] ?? [];
    $require_receipt = ($fields_enabled_cfg['scontrino'] ?? true) === true;
    $showReceiptColumn = !empty($fields_enabled_cfg['scontrino']);
    $autoPrizeWithReceipt = (bool)($contest_config['instantwin_auto_prize_with_receipt'] ?? false);
    $autoPrizeFlow = $autoPrizeWithReceipt && $contestMode === 'instantwin' && $require_receipt;
    require_once __DIR__ . '/../../../app/logic/instantwin.php';
    require_once __DIR__ . '/ensure_scontrino_validato_column.inc.php';
    $isInstantWin = $contestMode === 'instantwin';
    $managerConfirmFlow = $isInstantWin && !$autoPrizeFlow && instantwin_is_manager_confirm_flow_enabled();
    $managerCancelFlow = $isInstantWin && !$autoPrizeFlow && instantwin_is_manager_cancel_flow_enabled();
    $receiptValidationEnabled = $showReceiptColumn && manager_ensure_scontrino_validato_column($connection);
    // email_sending vive in content.json via get_email_sending_config() — non su $contest_config.
    require_once __DIR__ . '/../../../public/assets/mail/email_system.php';
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
    $iwManagerReceiptFlow = $isInstantWin && $receiptValidationEnabled && ($managerConfirmFlow || $managerCancelFlow);
    $estrazioneReceiptFlow = !$isInstantWin && $receiptValidationEnabled;
    // Filtro scontrino solo in estrazione (non in Instant Win).
    if (!$estrazioneReceiptFlow || !in_array($filterScontrino, ['valid', 'invalid', 'pending'], true)) {
        $filterScontrino = '';
    }
    if ($estrazioneReceiptFlow && $filterScontrino !== '') {
        switch ($filterScontrino) {
            case 'valid':
                $whereConditions[] = 'p.scontrino_validato = 1';
                break;
            case 'invalid':
                $whereConditions[] = 'p.scontrino_validato = 0';
                break;
            case 'pending':
                $whereConditions[] = 'p.scontrino_validato IS NULL';
                break;
        }
    }
    $showSmsColumn = !empty($contest_config['sms']['enabled']);
    $smsSelectSql = '';
    if ($showSmsColumn) {
        try {
            $smsCol = $connection->query("SHOW COLUMNS FROM partecipazioni LIKE 'sms_inviato'")->fetch(PDO::FETCH_ASSOC);
            if ($smsCol) {
                $smsSelectSql = ",\n            p.sms_inviato,\n            p.sms_error";
            } else {
                $showSmsColumn = false;
            }
        } catch (Throwable $e) {
            $showSmsColumn = false;
        }
    }
    $scontrinoValidatoSelectSql = $receiptValidationEnabled
        ? ",\n            p.scontrino_validato"
        : '';

    $baseQuery = "FROM partecipazioni p
        LEFT JOIN utenti u ON u.id_cliente = p.id_cliente
        LEFT JOIN punti_vendita mpdv ON mpdv.codice = p.id_pdv_concorso";
    $whereClause = $whereConditions ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

    $totalRecords = (int)$connection->query("SELECT COUNT(*) FROM partecipazioni")->fetchColumn();

    if ($whereClause !== '') {
        $countStmt = $connection->prepare("SELECT COUNT(*) {$baseQuery} {$whereClause}");
        bindValues($countStmt, $bindings);
        $countStmt->execute();
        $filteredRecords = (int)$countStmt->fetchColumn();
    } else {
        $filteredRecords = $totalRecords;
    }

    $dataSql = "
        SELECT 
            p.id,
            p.id_cliente,
            p.data_partecipazione,
            p.importo_scontrino,
            p.data_scontrino,
            p.file_scontrino{$scontrinoValidatoSelectSql},
            p.id_pdv_concorso,
            p.insegna,
            mpdv.ragsoc AS PDV_RAGSOC,
            mpdv.insegna AS PDV_INSEGNA,
            mpdv.indirizzo AS PDV_INDIRIZZO,
            mpdv.citta AS PDV_CITTA,
            mpdv.prov AS PDV_PROV,
            mpdv.cap AS PDV_CAP,
            p.has_won,
            p.confermato,
            p.annullato,
            p.win_date,
            p.email_inviato,
            p.email_error{$smsSelectSql},
            u.nome AS NOME_CLIENTE,
            u.cognome AS COGNOME_CLIENTE,
            u.email AS EMAIL_CLIENTE,
            u.prefisso AS PREFISSO_CLIENTE,
            u.cellulare AS CELLULARE_CLIENTE,
            u.indirizzo AS INDIRIZZO_CLIENTE,
            u.citta AS CITTA_CLIENTE,
            u.provincia AS PROVINCIA_CLIENTE,
            u.cap AS CAP_CLIENTE,
            mpdv.codice AS PDV_CODICE{$customFieldsSelectStr}
        {$baseQuery}
        {$whereClause}
        ORDER BY {$orderBy} {$orderDir}
        LIMIT ? OFFSET ?
    ";

    $dataStmt = $connection->prepare($dataSql);
    $dataBindings = $bindings;
    $dataBindings[] = [
        'value' => (int)$length,
        'type' => PDO::PARAM_INT
    ];
    $dataBindings[] = [
        'value' => (int)$start,
        'type' => PDO::PARAM_INT
    ];
    bindValues($dataStmt, $dataBindings);
    $dataStmt->execute();
    $rows = $dataStmt->fetchAll(PDO::FETCH_ASSOC);

    $uploadBaseDir = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'scontrini' . DIRECTORY_SEPARATOR;
    $uploadBaseUrl = '../uploads/scontrini/';

    $data = [];
    foreach ($rows as $row) {
        $id = (int)$row['id'];
        $idCliente = (int)($row['id_cliente'] ?? 0);
        $nomeCliente = (string)($row['NOME_CLIENTE'] ?? '');
        $cognomeCliente = (string)($row['COGNOME_CLIENTE'] ?? '');
        $emailCliente = (string)($row['EMAIL_CLIENTE'] ?? '');
        $prefissoCliente = (string)($row['PREFISSO_CLIENTE'] ?? '');
        $cellulareClienteRaw = (string)($row['CELLULARE_CLIENTE'] ?? '');
        $cellulareClienteFormatted = formatPhoneWithPrefix($prefissoCliente, $cellulareClienteRaw);
        $indirizzoCliente = trim((string)($row['INDIRIZZO_CLIENTE'] ?? ''));
        $cittaCliente = trim((string)($row['CITTA_CLIENTE'] ?? ''));
        $provinciaCliente = trim((string)($row['PROVINCIA_CLIENTE'] ?? ''));
        $capCliente = trim((string)($row['CAP_CLIENTE'] ?? ''));

        $dataPartecipazione = formatDateValue($row['data_partecipazione'] ?? null, 'd/m/Y H:i');
        $importoScontrino = formatCurrencyValue($row['importo_scontrino']);
        $dataScontrino = formatDateValue($row['data_scontrino'] ?? null, 'd/m/Y');

        $insegna = trim((string)($row['insegna'] ?? ''));
        $codicePdv = trim((string)($row['PDV_CODICE'] ?? ''));
        $ragsocPdv = trim((string)($row['PDV_RAGSOC'] ?? ''));
        $insegnaPdv = trim((string)($row['PDV_INSEGNA'] ?? ''));
        // Utiliser insegna de punti_vendita si ragsoc est vide, sinon utiliser INSEGNA de partecipazioni
        if ($ragsocPdv === '') {
            $ragsocPdv = $insegnaPdv !== '' ? $insegnaPdv : $insegna;
        }
        $indirizzoPdv = trim((string)($row['PDV_INDIRIZZO'] ?? ''));
        $cittaPdv = trim((string)($row['PDV_CITTA'] ?? ''));
        $provPdv = trim((string)($row['PDV_PROV'] ?? ''));
        $capPdv = trim((string)($row['PDV_CAP'] ?? ''));
        $puntoVendita = formatPuntoVendita($ragsocPdv, $codicePdv, $indirizzoPdv, $cittaPdv, $provPdv, $capPdv);

        $hasWon = (int)($row['has_won'] ?? 0) === 1;
        $confermatoRaw = (int)($row['confermato'] ?? 0);
        $annullatoRaw = (int)($row['annullato'] ?? 0);
        $winDate = formatDateValue($row['win_date'] ?? null, 'd/m/Y H:i');
        $emailSentRaw = (int)($row['email_inviato'] ?? 0);
        $emailInviato = manager_participation_email_status_badge(
            $emailSentRaw,
            (string)($row['email_error'] ?? ''),
            static fn(string $s): string => safeHtml($s)
        );
        $smsInviato = '';
        if ($showSmsColumn) {
            $smsInviato = manager_participation_delivery_status_badge(
                (int)($row['sms_inviato'] ?? 0),
                (string)($row['sms_error'] ?? ''),
                static fn(string $s): string => safeHtml($s)
            );
        }
        $vincitore = formatBooleanBadge($hasWon, 'Si', 'No', $hasWon ? 1 : 0);
        $confermato = $hasWon
            ? formatBooleanBadge($confermatoRaw === 1, 'Si', 'No', $confermatoRaw)
            : '<span class="text-muted">-</span>';
        if ($annullatoRaw === 1) {
            $annullato = formatBooleanBadge(true, 'Si', 'No', 1);
        } elseif ($hasWon) {
            $annullato = formatBooleanBadge(false, 'Si', 'No', 0);
        } else {
            $annullato = '<span class="text-muted">-</span>';
        }
        $partecipante = formatParticipant($nomeCliente, $cognomeCliente, $emailCliente, $cellulareClienteFormatted, $indirizzoCliente, $cittaCliente, $provinciaCliente, $capCliente);

        $fileName = $row['file_scontrino'] ?? '';
        $fileUrl = '';
        if ($fileName !== '') {
            $fullPath = $uploadBaseDir . $fileName;
            if (is_file($fullPath)) {
                $fileUrl = $uploadBaseUrl . rawurlencode($fileName);
            }
        }

        $scontrinoValidatoRaw = null;
        if ($receiptValidationEnabled) {
            $rawValidato = $row['scontrino_validato'] ?? null;
            if ($rawValidato !== null && $rawValidato !== '') {
                $scontrinoValidatoRaw = (int)$rawValidato === 1 ? 1 : 0;
            }
        }

        $azioniButtons = [];
        $rowShowEmailActions = false;
        if ($isInstantWin) {
            $rowShowEmailActions = $hasWon ? $emailIwWinEnabled : $emailIwLossEnabled;
        } else {
            $rowShowEmailActions = $emailPartecipazioneEnabled;
        }

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
            if ($fileUrl !== '') {
                $fileExt = strtolower((string)pathinfo($fileName, PATHINFO_EXTENSION));
                $azioniButtons[] = '<button type="button" class="btn btn-sm btn-outline-info btnViewReceipt" data-file-url="' . safeHtml($fileUrl) . '" data-file-name="' . safeHtml($fileName !== '' ? $fileName : 'Scontrino') . '" data-file-ext="' . safeHtml($fileExt) . '"><i class="fa fa-file"></i></button>';
            }

            if ($managerConfirmFlow && $hasWon && $annullatoRaw !== 1 && $confermatoRaw !== 1) {
                $azioniButtons[] = '<button type="button" class="btn btn-sm btn-outline-success btnConfirmWin" data-participation-id="' . $id . '"><i class="fa fa-check"></i></button>';
            }
            if ($managerCancelFlow && $hasWon && $annullatoRaw !== 1 && $confermatoRaw !== 1) {
                $azioniButtons[] = '<button type="button" class="btn btn-sm btn-outline-danger btnCancelWin" data-participation-id="' . $id . '"><i class="fa fa-times"></i></button>';
            }
        }

        if ($rowShowEmailActions) {
            if (!$receiptValidationEnabled && $hasWon && $confermatoRaw === 1 && $annullatoRaw !== 1) {
                $azioniButtons[] = '<button type="button" class="btn btn-sm btn-outline-primary btnResendEmail" data-participation-id="' . $id . '" data-email-type="confirm" data-email-label="di conferma vittoria"><i class="fa fa-paper-plane"></i></button>';
            }
            if (!$receiptValidationEnabled && $hasWon && $annullatoRaw === 1) {
                $azioniButtons[] = '<button type="button" class="btn btn-sm btn-outline-warning btnResendEmail" data-participation-id="' . $id . '" data-email-type="instantwin_cancel" data-email-label="di annullamento"><i class="fa fa-envelope"></i></button>';
            }
            $azioniButtons[] = '<button type="button" class="btn btn-sm btn-outline-secondary btnLastEmailPreview" data-participation-id="' . $id . '"><i class="fa fa-eye"></i></button>';
            $azioniButtons[] = '<button type="button" class="btn btn-sm btn-outline-primary btnResendEmail" data-participation-id="' . $id . '" data-email-type="result" data-email-label="Reinvia ultima email"><i class="fa fa-envelope"></i></button>';
        }

        $azioni = !empty($azioniButtons)
            ? '<div class="actions-group">' . implode('', $azioniButtons) . '</div>'
            : '<span class="text-muted">-</span>';

        $rowClass = '';
        if ($scontrinoValidatoRaw === 1) {
            $rowClass = 'row-scontrino-valid';
        } elseif ($scontrinoValidatoRaw === 0) {
            $rowClass = 'row-scontrino-invalid';
        }

        // Formater les champs personnalisés
        $customFieldsFormatted = [];
        foreach ($customFieldsData as $customField) {
            $customId = $customField['id'];
            $customType = $customField['type'];
            $columnKey = 'CUSTOM_' . strtoupper(preg_replace('/[^A-Z0-9_]/', '_', strtoupper($customId)));
            $rawValue = $row[$columnKey] ?? null;
            
            $formattedValue = formatCustomFieldValue($rawValue, $customType);
            $customFieldsFormatted[$customId] = $formattedValue;
        }

        $dataRow = [
            'DT_RowClass' => $rowClass,
            'DT_RowAttr' => [
                'data-participation-id' => (string)$id,
                'data-scontrino-status' => $scontrinoValidatoRaw === null ? '' : (string)$scontrinoValidatoRaw,
            ],
            'partecipante' => $partecipante,
            'data_partecipazione' => $dataPartecipazione,
            'importo_scontrino' => $importoScontrino,
            'data_scontrino' => $dataScontrino,
            'punto_vendita' => $puntoVendita,
            'vincitore' => $vincitore,
            'confermato' => $confermato,
            'annullato' => $annullato,
            'win_date' => $winDate,
            'email_inviato' => $emailInviato,
            'sms_inviato' => $smsInviato,
            'azioni' => $azioni
        ];
        
        // Ajouter les champs personnalisés après vincitore
        foreach ($customFieldsFormatted as $customId => $formattedValue) {
            $dataRow['custom_' . $customId] = $formattedValue;
        }
        
        $data[] = $dataRow;
    }

    echo json_encode([
        'data' => $data,
        'recordsTotal' => $totalRecords,
        'recordsFiltered' => $filteredRecords,
        'draw' => isset($_POST['draw']) ? (int)$_POST['draw'] : 1
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'data' => [],
        'recordsTotal' => 0,
        'recordsFiltered' => 0,
        'draw' => isset($_POST['draw']) ? (int)$_POST['draw'] : 0,
        'error' => 'Errore nel recupero delle partecipazioni: ' . $e->getMessage()
    ]);
}

function bindValues(PDOStatement $stmt, array $bindings): void
{
    $index = 1;
    foreach ($bindings as $binding) {
        $value = $binding['value'];
        $type = $binding['type'] ?? PDO::PARAM_STR;
        $stmt->bindValue($index, $value, $type);
        $index++;
    }
}

function safeHtml(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function formatDateValue(?string $value, string $format): string
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

function formatCurrencyValue($value, bool $allowNull = false): string
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

function formatPlainValue(string $value): string
{
    $trimmed = trim($value);
    if ($trimmed === '') {
        return '<span class="text-muted">-</span>';
    }

    return safeHtml($trimmed);
}

function formatParticipant(string $nome, string $cognome, string $email, string $cellulare, string $indirizzo = '', string $citta = '', string $provincia = '', string $cap = ''): string
{
    $displayName = trim($nome . ' ' . $cognome);
    if ($displayName === '') {
        $displayName = $email !== '' ? $email : 'Cliente senza profilo';
    }

    $html = '<div><strong>' . safeHtml($displayName) . '</strong></div>';

    if ($email !== '') {
        $html .= '<div><small class="text-muted">' . safeHtml($email) . '</small></div>';
    }

    if ($cellulare !== '') {
        $html .= '<div><small class="text-muted">' . safeHtml($cellulare) . '</small></div>';
    }

    $addressLine = [];
    if ($indirizzo !== '') {
        $addressLine[] = safeHtml($indirizzo);
    }
    if ($citta !== '') {
        $cityProv = safeHtml($citta);
        if ($provincia !== '') {
            $cityProv .= ' (' . safeHtml($provincia) . ')';
        }
        $addressLine[] = $cityProv;
    } elseif ($provincia !== '') {
        $addressLine[] = safeHtml($provincia);
    }
    if ($cap !== '') {
        $addressLine[] = safeHtml($cap);
    }
    if (!empty($addressLine)) {
        $html .= '<div><small class="text-muted">' . implode(' - ', $addressLine) . '</small></div>';
    }

    return $html;
}

function formatBooleanBadge(bool $value, string $trueLabel, string $falseLabel, ?int $order = null): string
{
    $orderAttr = $order !== null ? ' data-order="' . $order . '"' : '';
    if ($value) {
        return '<span class="badge bg-success"' . $orderAttr . '>' . safeHtml($trueLabel) . '</span>';
    }

    return '<span class="badge bg-secondary"' . $orderAttr . '>' . safeHtml($falseLabel) . '</span>';
}

function formatPuntoVendita(string $ragsoc, string $codice, string $indirizzo, string $citta, string $prov, string $cap): string
{
    if ($ragsoc === '' && $codice === '' && $indirizzo === '' && $citta === '' && $prov === '' && $cap === '') {
        return '<span class="text-muted">N/A</span>';
    }

    $parts = [];
    
    // Ragsoc (premier)
    if ($ragsoc !== '') {
        $parts[] = '<strong>' . safeHtml($ragsoc) . '</strong>';
    }

    // Indirizzo + citta + provincia
    $addressLine = [];
    if ($indirizzo !== '') {
        $addressLine[] = safeHtml($indirizzo);
    }
    $cityProv = '';
    if ($citta !== '') {
        $cityProv = safeHtml($citta);
        if ($prov !== '') {
            $cityProv .= ' (' . safeHtml($prov) . ')';
        }
    } elseif ($prov !== '') {
        $cityProv = safeHtml($prov);
    }
    if ($cityProv !== '') {
        $addressLine[] = $cityProv;
    }
    if ($cap !== '') {
        $addressLine[] = safeHtml($cap);
    }
    if (!empty($addressLine)) {
        $parts[] = implode(' - ', $addressLine);
    }

    // Codice (dernier)
    if ($codice !== '') {
        $parts[] = '<small class="text-muted">Codice: ' . safeHtml($codice) . '</small>';
    }

    return implode('<br>', $parts);
}

function formatCustomFieldValue($value, string $type): string
{
    if ($value === null || $value === '') {
        return '<span class="text-muted">-</span>';
    }
    
    $type = strtolower(trim($type));
    
    switch ($type) {
        case 'boolean':
            $boolValue = ($value === '1' || $value === 1 || $value === true || strtolower((string)$value) === 'true');
            return formatBooleanBadge($boolValue, 'Si', 'No', $boolValue ? 1 : 0);
            
        case 'date':
            return formatDateValue($value, 'd/m/Y');
            
        case 'int':
            return formatPlainValue((string)$value);
            
        case 'text':
        case 'varchar':
        default:
            $trimmed = trim((string)$value);
            if ($trimmed === '') {
                return '<span class="text-muted">-</span>';
            }
            // Limiter l'affichage pour les textes longs
            if (strlen($trimmed) > 100) {
                return safeHtml(substr($trimmed, 0, 100)) . '...';
            }
            return safeHtml($trimmed);
    }
}
