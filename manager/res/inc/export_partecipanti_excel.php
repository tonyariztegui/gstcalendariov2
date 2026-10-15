<?php

require_once __DIR__ . '/../../config/config.php';

@set_time_limit(0);
@ini_set('max_execution_time', '0');
@ini_set('max_input_time', '0');
@ini_set('memory_limit', '512M');
@ini_set('zlib.output_compression', '0');
ignore_user_abort(true);
if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}

$sanitizeString = static function ($value) {
    $string = (string) $value;
    $string = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', $string);

    return trim($string);
};

$csvText = static function ($value) use ($sanitizeString) {
    $string = $sanitizeString($value);
    if ($string !== '' && preg_match('/^[=+\-@]/', $string)) {
        return "'" . $string;
    }

    return $string;
};

if (ob_get_level() > 0) {
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
}

if (!isset($connection) || !$connection) {
    header('Content-Type: text/plain; charset=utf-8');
    exit('Errore: connessione al database non disponibile.');
}

try {
    $connection->exec("SET NAMES utf8mb4");
    $connection->exec("SET CHARACTER SET utf8mb4");
    $connection->exec("SET collation_connection = 'utf8mb4_unicode_ci'");
} catch (Throwable $e) {
    header('Content-Type: text/plain; charset=utf-8');
    exit('Errore impostazione encoding: ' . $e->getMessage());
}

$filterStatus = isset($_GET['filterStatus']) ? trim((string)$_GET['filterStatus']) : '';
$filterScontrino = isset($_GET['filterScontrino']) ? trim((string)$_GET['filterScontrino']) : '';
$searchQuery = isset($_GET['search']) ? trim((string)$_GET['search']) : '';
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
$fieldsEnabled = $contest_config['fields_enabled'] ?? [];
$showEmailColumn = $fieldsEnabled['email'] ?? true;
$showFidelityColumn = $fieldsEnabled['fidelity'] ?? true;
$showAddressColumn = $fieldsEnabled['indirizzo'] ?? true;
$showCityColumn = $fieldsEnabled['citta'] ?? true;
$showProvinceColumn = $fieldsEnabled['provincia'] ?? true;
$showCapColumn = $fieldsEnabled['cap'] ?? false;
$showPrivacyColumn = $fieldsEnabled['privacy'] ?? true;
$showMarketingColumn = $fieldsEnabled['marketing'] ?? true;
$showProfilazioneColumn = $fieldsEnabled['profilazione'] ?? true;
$showImportoColumn = $fieldsEnabled['importo'] ?? true;
$showDataScontrinoColumn = $fieldsEnabled['data_scontrino'] ?? true;
$showPdvColumn = ($fieldsEnabled['prov_pdv'] ?? true) || ($fieldsEnabled['citta_pdv'] ?? true) || ($fieldsEnabled['indirizzo_pdv'] ?? true);
$showReceiptAction = $fieldsEnabled['scontrino'] ?? true;
$showReceiptColumn = !empty($fieldsEnabled['scontrino']);
$showWinnerColumn = isset($contest_mode) && strtolower((string)$contest_mode) === 'instantwin';
$autoPrizeWithReceipt = (bool)($contest_config['instantwin_auto_prize_with_receipt'] ?? false);
$autoPrizeFlow = $autoPrizeWithReceipt && $showWinnerColumn && $showReceiptAction;
require_once __DIR__ . '/../../../app/logic/instantwin.php';
require_once __DIR__ . '/ensure_scontrino_validato_column.inc.php';
$showConfirmedColumn = $showWinnerColumn && !$autoPrizeFlow && instantwin_is_manager_confirm_flow_enabled();
$showPdvProvColumn = $fieldsEnabled['prov_pdv'] ?? true;
$showPdvCittaColumn = $fieldsEnabled['citta_pdv'] ?? true;
$showPdvIndirizzoColumn = $fieldsEnabled['indirizzo_pdv'] ?? true;
$showSmsColumn = !empty($contest_config['sms']['enabled']);
$smsExportSelect = '';
if ($showSmsColumn) {
    try {
        $smsCol = $connection->query("SHOW COLUMNS FROM partecipazioni LIKE 'sms_inviato'")->fetch(PDO::FETCH_ASSOC);
        if ($smsCol) {
            $smsExportSelect = ",\n        p.sms_inviato,\n        p.sms_error";
        } else {
            $showSmsColumn = false;
        }
    } catch (Throwable $e) {
        $showSmsColumn = false;
    }
}

$receiptValidationEnabled = $showReceiptColumn && manager_ensure_scontrino_validato_column($connection);
if (!$receiptValidationEnabled || !in_array($filterScontrino, ['valid', 'invalid', 'pending'], true)) {
    $filterScontrino = '';
}
$scontrinoValidatoExportSelect = $receiptValidationEnabled
    ? ",\n        p.scontrino_validato"
    : '';

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
    $customColumn = preg_replace('/[^a-z0-9_]/i', '_', strtolower($customId));
    $customColumn = trim($customColumn, '_');
    if ($customColumn === '') {
        continue;
    }
    $customAlias = strtoupper($customColumn);
    $customFieldsList[] = [
        'id' => $customId,
        'label' => $customLabel,
        'type' => strtolower(trim((string)($customField['type'] ?? 'varchar'))),
        'column' => $customColumn,
        'alias' => $customAlias,
    ];
}

$whereClauses = [];
$bindings = [];

$whereClauses[] = 'p.data_partecipazione >= :date_from';
$whereClauses[] = 'p.data_partecipazione < :date_to_excl';
$bindings[':date_from'] = $dateFrom . ' 00:00:00';
$bindings[':date_to_excl'] = date('Y-m-d', strtotime($dateTo . ' +1 day')) . ' 00:00:00';

switch ($filterStatus) {
    case '1':
    case '0':
        $whereClauses[] = 'p.has_won = :filter_has_won';
        $bindings[':filter_has_won'] = (int)$filterStatus;
        break;
    case 'confirmed':
        $whereClauses[] = 'p.has_won = 1';
        $whereClauses[] = 'p.confermato = 1';
        $whereClauses[] = 'p.annullato = 0';
        break;
    case 'not_confirmed':
        $whereClauses[] = 'p.has_won = 1';
        $whereClauses[] = 'p.confermato = 0';
        $whereClauses[] = 'p.annullato = 0';
        break;
    case 'cancelled':
        $whereClauses[] = 'p.annullato = 1';
        break;
}

if ($receiptValidationEnabled && $filterScontrino !== '') {
    switch ($filterScontrino) {
        case 'valid':
            $whereClauses[] = 'p.scontrino_validato = 1';
            break;
        case 'invalid':
            $whereClauses[] = 'p.scontrino_validato = 0';
            break;
        case 'pending':
            $whereClauses[] = 'p.scontrino_validato IS NULL';
            break;
    }
}

if ($searchQuery !== '') {
    $searchColumns = [
        'CAST(p.id AS CHAR)',
        'CAST(p.id_cliente AS CHAR)',
        "DATE_FORMAT(p.data_partecipazione, '%d/%m/%Y %H:%i')",
        "DATE_FORMAT(p.data_partecipazione, '%d-%m-%Y %H:%i')",
        "DATE_FORMAT(p.data_partecipazione, '%Y-%m-%d')",
        'CAST(p.importo_scontrino AS CHAR)',
        "REPLACE(CAST(p.importo_scontrino AS CHAR), '.', ',')",
        "DATE_FORMAT(p.data_scontrino, '%d/%m/%Y')",
        "DATE_FORMAT(p.data_scontrino, '%d-%m-%Y')",
        "DATE_FORMAT(p.data_scontrino, '%Y-%m-%d')",
        'p.insegna',
        'mpdv.ragsoc',
        'mpdv.indirizzo',
        'mpdv.citta',
        'mpdv.prov',
        'mpdv.cap',
        'mpdv.codice',
        'u.nome',
        'u.cognome',
        'TRIM(CONCAT(COALESCE(u.nome, \'\'), \' \', COALESCE(u.cognome, \'\')))',
        'TRIM(CONCAT(COALESCE(u.cognome, \'\'), \' \', COALESCE(u.nome, \'\')))',
        'u.email',
        'u.fidelity',
        'CONCAT(u.prefisso, u.cellulare)',
        'u.cellulare',
        'u.indirizzo',
        'u.citta',
        'u.provincia',
        'u.cap'
    ];
    $searchConditions = [];
    foreach ($searchColumns as $index => $col) {
        $paramName = 'search_' . $index;
        $searchConditions[] = "({$col} LIKE :{$paramName})";
        $bindings[$paramName] = '%' . $searchQuery . '%';
    }
    if (!empty($searchConditions)) {
        $whereClauses[] = '(' . implode(' OR ', $searchConditions) . ')';
    }
}

$customFieldsSelect = [];
foreach ($customFieldsList as $customField) {
    $customColumn = $customField['column'];
    $customAlias = $customField['alias'];
    $customFieldsSelect[] = "p.`{$customColumn}` AS CUSTOM_{$customAlias}";
}
$customFieldsSelectStr = !empty($customFieldsSelect) ? ', ' . implode(', ', $customFieldsSelect) : '';

$sql = "
    SELECT 
        p.id,
        p.data_partecipazione,
        p.importo_scontrino,
        p.data_scontrino,
        p.insegna,
        p.id_pdv_concorso,
        mpdv.ragsoc AS PDV_RAGSOC,
        mpdv.insegna AS PDV_INSEGNA,
        mpdv.indirizzo AS PDV_INDIRIZZO,
        mpdv.citta AS PDV_CITTA,
        mpdv.prov AS PDV_PROV,
        mpdv.cap AS PDV_CAP,
        mpdv.codice AS PDV_CODICE,
        u.nome,
        u.cognome,
        u.email,
        u.fidelity,
        u.prefisso,
        u.cellulare,
        u.indirizzo,
        u.citta,
        u.provincia,
        u.cap,
        u.privacy,
        u.marketing,
        u.profilazione,
        p.has_won,
        p.confermato,
        p.annullato,
        p.win_date,
        p.email_inviato{$smsExportSelect}{$scontrinoValidatoExportSelect}{$customFieldsSelectStr}
    FROM partecipazioni p
    LEFT JOIN utenti u ON u.id_cliente = p.id_cliente
    LEFT JOIN punti_vendita mpdv ON mpdv.codice = p.id_pdv_concorso
";

if (!empty($whereClauses)) {
    $sql .= ' WHERE ' . implode(' AND ', $whereClauses);
}

$sql .= ' ORDER BY p.data_partecipazione DESC, p.id DESC';

try {
    $stmt = $connection->prepare($sql);
    foreach ($bindings as $key => $value) {
        $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
    }
    $stmt->execute();
} catch (Throwable $e) {
    header('Content-Type: text/plain; charset=utf-8');
    exit('Errore export: ' . $e->getMessage());
}

$headers = [
    'Nome',
    'Cognome',
    'Cellulare',
];
if ($showEmailColumn) {
    $headers[] = 'Email';
}
if ($showFidelityColumn) {
    $headers[] = 'Fidelity';
}
if ($showAddressColumn) {
    $headers[] = 'Indirizzo';
}
if ($showCityColumn) {
    $headers[] = 'Città';
}
if ($showProvinceColumn) {
    $headers[] = 'Provincia';
}
if ($showCapColumn) {
    $headers[] = 'CAP';
}
if ($showPrivacyColumn) {
    $headers[] = 'Privacy';
}
if ($showMarketingColumn) {
    $headers[] = 'Marketing';
}
if ($showProfilazioneColumn) {
    $headers[] = 'Profilazione';
}
$headers[] = 'Data partecipazione';
if ($showImportoColumn) {
    $headers[] = 'Importo scontrino';
}
if ($showDataScontrinoColumn) {
    $headers[] = 'Data scontrino';
}
if ($receiptValidationEnabled) {
    $headers[] = 'Scontrino validato';
}
$headers[] = 'Insegna';
if ($showPdvColumn) {
    $headers[] = 'Codice PDV';
    if ($showPdvIndirizzoColumn) {
        $headers[] = 'Indirizzo PDV';
    }
    if ($showPdvCittaColumn) {
        $headers[] = 'Città PDV';
    }
    if ($showPdvProvColumn) {
        $headers[] = 'Provincia PDV';
    }
}
if ($showWinnerColumn) {
    $headers[] = 'Vincitore';
}
if ($showConfirmedColumn) {
    $headers[] = 'Confermato';
}
$headers[] = 'Data vincita';
$headers[] = 'Email inviato';
if ($showSmsColumn) {
    $headers[] = 'SMS inviato';
}
foreach ($customFieldsList as $customField) {
    $headers[] = $customField['label'];
}

$fileName = 'partecipazioni';
if ($dateFrom !== '' || $dateTo !== '') {
    $fileName .= '_' . ($dateFrom !== '' ? str_replace('-', '', $dateFrom) : 'inizio');
    $fileName .= '_' . ($dateTo !== '' ? str_replace('-', '', $dateTo) : 'fine');
}
$fileName .= '_' . date('Ymd_His') . '.csv';

while (ob_get_level() > 0) {
    ob_end_clean();
}

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $fileName . '"');
header('Cache-Control: max-age=0, no-store');
header('Pragma: public');
header('Expires: 0');
header('X-Accel-Buffering: no');

$out = fopen('php://output', 'wb');
fwrite($out, "\xEF\xBB\xBF");
fputcsv($out, $headers, ';', '"', '\\');
fflush($out);

$exportedRows = 0;
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    if (($exportedRows % 200) === 0) {
        @set_time_limit(0);
        fflush($out);
    }
    $insegna = trim((string)($row['insegna'] ?? ''));
    $pdvCodice = trim((string)($row['PDV_CODICE'] ?? ''));
    if ($pdvCodice === '' && !empty($row['id_pdv_concorso'])) {
        $pdvCodice = (string)$row['id_pdv_concorso'];
    }
    $pdvIndirizzo = trim((string)($row['PDV_INDIRIZZO'] ?? ''));
    $pdvCitta = trim((string)($row['PDV_CITTA'] ?? ''));
    $pdvProv = trim((string)($row['PDV_PROV'] ?? ''));

    $dataPartecipazione = !empty($row['data_partecipazione']) ? date('d/m/Y H:i', strtotime($row['data_partecipazione'])) : '';
    $importoScontrino = null;
    if ($row['importo_scontrino'] !== null) {
        $rawImporto = str_replace(["\u{00A0}", ' '], '', (string)$row['importo_scontrino']);
        if (strpos($rawImporto, ',') !== false && strpos($rawImporto, '.') !== false) {
            $rawImporto = str_replace('.', '', $rawImporto);
        }
        $normalizedImporto = str_replace(',', '.', $rawImporto);
        $importoScontrino = (float)$normalizedImporto;
    }
    $dataScontrino = !empty($row['data_scontrino']) ? date('d/m/Y', strtotime($row['data_scontrino'])) : '';
    $dataVincita = !empty($row['win_date']) ? date('d/m/Y H:i', strtotime($row['win_date'])) : '';

    $cellulareValue = $csvText(formatPhoneWithPrefix($row['prefisso'] ?? '', $row['cellulare'] ?? ''));
    $fidelityValue = $csvText($row['fidelity'] ?? '');
    $importoCsv = '';
    if ($importoScontrino !== null) {
        $importoCsv = number_format((float) $importoScontrino, 2, ',', '.');
    }

    $rowData = [];
    $rowData[] = $sanitizeString($row['nome'] ?? '');
    $rowData[] = $sanitizeString($row['cognome'] ?? '');
    $rowData[] = $cellulareValue;
    if ($showEmailColumn) {
        $rowData[] = $sanitizeString($row['email'] ?? '');
    }
    if ($showFidelityColumn) {
        $rowData[] = $fidelityValue;
    }
    if ($showAddressColumn) {
        $rowData[] = $sanitizeString($row['indirizzo'] ?? '');
    }
    if ($showCityColumn) {
        $rowData[] = $sanitizeString($row['citta'] ?? '');
    }
    if ($showProvinceColumn) {
        $rowData[] = $sanitizeString($row['provincia'] ?? '');
    }
    if ($showCapColumn) {
        $rowData[] = $csvText($row['cap'] ?? '');
    }
    if ($showPrivacyColumn) {
        $rowData[] = ((int)($row['privacy'] ?? 0)) === 1 ? 'SI' : 'NO';
    }
    if ($showMarketingColumn) {
        $rowData[] = ((int)($row['marketing'] ?? 0)) === 1 ? 'SI' : 'NO';
    }
    if ($showProfilazioneColumn) {
        $rowData[] = ((int)($row['profilazione'] ?? 0)) === 1 ? 'SI' : 'NO';
    }
    $rowData[] = $sanitizeString($dataPartecipazione);
    if ($showImportoColumn) {
        $rowData[] = $importoCsv;
    }
    if ($showDataScontrinoColumn) {
        $rowData[] = $sanitizeString($dataScontrino);
    }
    if ($receiptValidationEnabled) {
        $validatoRaw = $row['scontrino_validato'] ?? null;
        if ($validatoRaw === null || $validatoRaw === '') {
            $rowData[] = 'Da validare';
        } else {
            $rowData[] = ((int)$validatoRaw === 1) ? 'SI' : 'NO';
        }
    }
    $rowData[] = $sanitizeString($insegna);
    if ($showPdvColumn) {
        $rowData[] = $csvText($pdvCodice);
        if ($showPdvIndirizzoColumn) {
            $rowData[] = $sanitizeString($pdvIndirizzo);
        }
        if ($showPdvCittaColumn) {
            $rowData[] = $sanitizeString($pdvCitta);
        }
        if ($showPdvProvColumn) {
            $rowData[] = $sanitizeString($pdvProv);
        }
    }
    if ($showWinnerColumn) {
        $rowData[] = ((int)($row['has_won'] ?? 0)) === 1 ? 'SI' : 'NO';
    }
    if ($showConfirmedColumn) {
        if ((int)($row['annullato'] ?? 0) === 1) {
            $rowData[] = 'Annullato';
        } elseif ((int)($row['has_won'] ?? 0) === 1) {
            $rowData[] = ((int)($row['confermato'] ?? 0)) === 1 ? 'SI' : 'NO';
        } else {
            $rowData[] = '-';
        }
    }
    $rowData[] = $sanitizeString($dataVincita);
    $rowData[] = ((int)($row['email_inviato'] ?? 0)) === 1 ? 'SI' : 'NO';
    if ($showSmsColumn) {
        $rowData[] = ((int)($row['sms_inviato'] ?? 0)) === 1 ? 'SI' : 'NO';
    }

    foreach ($customFieldsList as $customField) {
        $customId = $customField['id'];
        $customType = $customField['type'];
        $customAlias = $customField['alias'] ?? strtoupper(preg_replace('/[^a-z0-9_]/i', '_', (string)$customId));
        $columnKey = 'CUSTOM_' . $customAlias;
        $rawValue = $row[$columnKey] ?? null;

        $formattedValue = '';
        if ($rawValue !== null && $rawValue !== '') {
            switch ($customType) {
                case 'boolean':
                    $boolValue = ($rawValue === '1' || $rawValue === 1 || $rawValue === true || strtolower((string)$rawValue) === 'true');
                    $formattedValue = $boolValue ? 'SI' : 'NO';
                    break;
                case 'date':
                    $timestamp = strtotime($rawValue);
                    if ($timestamp !== false) {
                        $formattedValue = date('d/m/Y', $timestamp);
                    } else {
                        $formattedValue = $sanitizeString((string)$rawValue);
                    }
                    break;
                case 'int':
                    $formattedValue = (string)(int)$rawValue;
                    break;
                case 'varchar':
                case 'text':
                default:
                    $formattedValue = $sanitizeString((string)$rawValue);
                    break;
            }
        }

        $rowData[] = $formattedValue;
    }

    fputcsv($out, $rowData, ';', '"', '\\');
    $exportedRows++;
}

fclose($out);
exit;
