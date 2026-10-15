<?php

require_once __DIR__ . '/../../config/config.php';

if (ob_get_level() > 0) {
    ob_end_clean();
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

$fieldsEnabled = $contest_config['fields_enabled'] ?? [];
$instantwinPerPdv = !empty($contest_config['instantwin_per_pdv']);
$showPdvColumn = ($fieldsEnabled['prov_pdv'] ?? true) || ($fieldsEnabled['citta_pdv'] ?? true) || ($fieldsEnabled['indirizzo_pdv'] ?? true) || $instantwinPerPdv;
$codicePartecipazioneEnabled = !empty($fieldsEnabled['codice_partecipazione']);

$searchQuery = isset($_GET['search']) ? trim((string)$_GET['search']) : '';
$searchPdv = isset($_GET['search_pdv']) ? trim((string)$_GET['search_pdv']) : '';
$dateFrom = isset($_GET['date_from']) ? trim((string)$_GET['date_from']) : '';
$dateTo = isset($_GET['date_to']) ? trim((string)$_GET['date_to']) : '';

$allowedSortKeys = ['win_date', 'premio', 'valore', 'vincitore', 'email', 'telefono'];
if ($showPdvColumn) {
    $allowedSortKeys[] = 'pdv';
}
$sortKey = strtolower(trim((string)($_GET['sort'] ?? 'win_date')));
if (!in_array($sortKey, $allowedSortKeys, true)) {
    $sortKey = 'win_date';
}
$sortDirParam = strtolower(trim((string)($_GET['dir'] ?? 'desc')));
if (!in_array($sortDirParam, ['asc', 'desc'], true)) {
    $sortDirParam = 'desc';
}
$sortDirSql = strtoupper($sortDirParam);

switch ($sortKey) {
    case 'premio':
        $orderSql = "pr.TIPO_PREMIO {$sortDirSql}, pr.VALORE {$sortDirSql}, p.ID DESC";
        break;
    case 'valore':
        $orderSql = "pr.VALORE {$sortDirSql}, p.ID DESC";
        break;
    case 'vincitore':
        $orderSql = "MAX(u.COGNOME) {$sortDirSql}, MAX(u.NOME) {$sortDirSql}, p.ID DESC";
        break;
    case 'email':
        $orderSql = "MAX(u.EMAIL) {$sortDirSql}, p.ID DESC";
        break;
    case 'telefono':
        $orderSql = "MAX(u.CELLULARE) {$sortDirSql}, p.ID DESC";
        break;
    case 'pdv':
        $orderSql = "MAX(COALESCE(mpdv.codice, '')) {$sortDirSql}, MAX(COALESCE(mpdv.ragsoc, '')) {$sortDirSql}, MAX(COALESCE(mpdv.insegna, '')) {$sortDirSql}, MAX(COALESCE(mpdv.citta, '')) {$sortDirSql}, p.ID DESC";
        break;
    case 'win_date':
    default:
        $orderSql = "p.WIN_DATE {$sortDirSql}, p.ID DESC";
        break;
}

$conditions = [
    'p.HAS_WON = 1',
    'COALESCE(p.ANNULLATO, 0) = 0',
    'p.PRIZE_ID IS NOT NULL'
];
$bindings = [];

if ($dateFrom !== '') {
    $conditions[] = 'p.WIN_DATE >= :date_from';
    $bindings['date_from'] = $dateFrom . ' 00:00:00';
}
if ($dateTo !== '') {
    $conditions[] = 'p.WIN_DATE <= :date_to';
    $bindings['date_to'] = $dateTo . ' 23:59:59';
}

if ($searchQuery !== '') {
    $conditions[] = '('
        . 'LOWER(COALESCE(u.NOME, \'\')) LIKE :search_lower'
        . ' OR LOWER(COALESCE(u.COGNOME, \'\')) LIKE :search_lower'
        . ' OR LOWER(TRIM(CONCAT(COALESCE(u.NOME, \'\'), \' \', COALESCE(u.COGNOME, \'\')))) LIKE :search_lower'
        . ' OR LOWER(TRIM(CONCAT(COALESCE(u.COGNOME, \'\'), \' \', COALESCE(u.NOME, \'\')))) LIKE :search_lower'
        . ' OR LOWER(COALESCE(u.EMAIL, \'\')) LIKE :search_lower'
        . ' OR LOWER(COALESCE(u.CELLULARE, \'\')) LIKE :search_lower'
        . ' OR LOWER(CONCAT(COALESCE(u.PREFISSO, \'\'), \' \', COALESCE(u.CELLULARE, \'\'))) LIKE :search_lower'
        . ' OR LOWER(COALESCE(pr.TIPO_PREMIO, \'\')) LIKE :search_lower'
        . ($showPdvColumn ? (
            ' OR LOWER(COALESCE(mpdv.insegna, \'\')) LIKE :search_lower'
            . ' OR LOWER(COALESCE(mpdv.ragsoc, \'\')) LIKE :search_lower'
            . ' OR LOWER(COALESCE(mpdv.citta, \'\')) LIKE :search_lower'
            . ' OR LOWER(COALESCE(mpdv.indirizzo, \'\')) LIKE :search_lower'
            . ' OR LOWER(COALESCE(mpdv.cap, \'\')) LIKE :search_lower'
            . ' OR LOWER(COALESCE(mpdv.prov, \'\')) LIKE :search_lower'
            . ' OR LOWER(COALESCE(mpdv.codice, \'\')) LIKE :search_lower'
        ) : '')
        . ' OR LOWER(COALESCE(p.codice_partecipazione, \'\')) LIKE :search_lower'
        . ' OR LOWER(COALESCE(cpart.codice, \'\')) LIKE :search_lower'
        . ' OR LOWER(COALESCE(cp.codice, \'\')) LIKE :search_lower'
        . ' OR DATE_FORMAT(p.WIN_DATE, \'%d/%m/%Y %H:%i\') LIKE :search_plain'
        . ' OR CAST(pr.VALORE AS CHAR) LIKE :search_plain'
    . ')';
    $bindings['search_lower'] = '%' . strtolower($searchQuery) . '%';
    $bindings['search_plain'] = '%' . $searchQuery . '%';
}

if ($searchPdv !== '' && $showPdvColumn) {
    $conditions[] = '('
        . 'LOWER(COALESCE(mpdv.insegna, \'\')) LIKE :pdv_search_lower'
        . ' OR LOWER(COALESCE(mpdv.ragsoc, \'\')) LIKE :pdv_search_lower'
        . ' OR LOWER(COALESCE(mpdv.citta, \'\')) LIKE :pdv_search_lower'
        . ' OR LOWER(COALESCE(mpdv.indirizzo, \'\')) LIKE :pdv_search_lower'
        . ' OR LOWER(COALESCE(mpdv.cap, \'\')) LIKE :pdv_search_lower'
        . ' OR LOWER(COALESCE(mpdv.prov, \'\')) LIKE :pdv_search_lower'
        . ' OR LOWER(COALESCE(mpdv.codice, \'\')) LIKE :pdv_search_lower'
        . ' OR LOWER(COALESCE(p.ID_PDV_CONCORSO, \'\')) LIKE :pdv_search_lower'
    . ')';
    $bindings['pdv_search_lower'] = '%' . strtolower($searchPdv) . '%';
}

$baseQuery = "
    FROM partecipazioni p
    LEFT JOIN premi pr ON p.PRIZE_ID = pr.ID
    LEFT JOIN utenti u ON p.ID_CLIENTE = u.ID_CLIENTE
    LEFT JOIN punti_vendita mpdv ON mpdv.codice = p.ID_PDV_CONCORSO
    LEFT JOIN codici_premio cp ON cp.id = p.id_codice_premio
    LEFT JOIN codici_partecipazione cpart ON cpart.id_partecipazione = p.ID
";
$whereSql = 'WHERE ' . implode(' AND ', $conditions);

$dataSql = "
    SELECT
        p.ID AS ID_PARTECIPAZIONE,
        p.WIN_DATE,
        p.codice_partecipazione AS CODICE_PARTECIPAZIONE,
        pr.VALORE AS VALORE_PREMIO,
        pr.TIPO_PREMIO,
        MAX(u.NOME) AS NOME,
        MAX(u.COGNOME) AS COGNOME,
        MAX(u.EMAIL) AS EMAIL,
        MAX(u.PREFISSO) AS PREFISSO,
        MAX(u.CELLULARE) AS CELLULARE,
        MAX(mpdv.insegna) AS INSEGNA,
        MAX(mpdv.ragsoc) AS RAGSOC,
        MAX(mpdv.citta) AS CITTA,
        MAX(mpdv.indirizzo) AS INDIRIZZO,
        MAX(mpdv.cap) AS CAP_PDV,
        MAX(mpdv.codice) AS CODICE_PDV,
        MAX(cp.codice) AS CODICE_PREMIO
    {$baseQuery}
    {$whereSql}
    GROUP BY p.ID
    ORDER BY {$orderSql}
";

$stmt = $connection->prepare($dataSql);
foreach ($bindings as $name => $value) {
    $stmt->bindValue(':' . $name, $value, PDO::PARAM_STR);
}
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$headers = ['Data Vittoria', 'Premio', 'Valore', 'Vincitore', 'Email', 'Telefono'];
if ($codicePartecipazioneEnabled) {
    $headers[] = 'Codice Partecipazione';
}
$headers[] = 'Codice Premio';
if ($showPdvColumn) {
    $headers[] = 'Punto Vendita';
}

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="premi_assegnati_' . date('Ymd_His') . '.csv"');
header('Cache-Control: max-age=0');
header('Pragma: public');

$out = fopen('php://output', 'wb');
fwrite($out, "\xEF\xBB\xBF");
// Semicolon separator for European Excel (colonnes reconnues à l'ouverture)
fputcsv($out, $headers, ';', '"', '\\');
foreach ($rows as $row) {
    $winDate = '-';
    if (!empty($row['WIN_DATE'])) {
        $ts = strtotime($row['WIN_DATE']);
        if ($ts !== false) {
            $winDate = date('d/m/Y H:i', $ts);
        }
    }
    $valore = isset($row['VALORE_PREMIO']) ? number_format((float)$row['VALORE_PREMIO'], 2, ',', '.') : '0,00';
    $fullName = trim(($row['NOME'] ?? '') . ' ' . ($row['COGNOME'] ?? ''));
    if ($fullName === '') {
        $fullName = $row['EMAIL'] ?? '-';
    }
    $telefono = formatPhoneWithPrefix($row['PREFISSO'] ?? '', $row['CELLULARE'] ?? '');
    $pdv = '';
    if ($showPdvColumn) {
        $nome = trim((string)($row['RAGSOC'] ?? ''));
        if ($nome === '') {
            $nome = trim((string)($row['INSEGNA'] ?? ''));
        }
        $ind = trim((string)($row['INDIRIZZO'] ?? ''));
        $cit = trim((string)($row['CITTA'] ?? ''));
        $cap = trim((string)($row['CAP_PDV'] ?? ''));
        $addr = implode(' - ', array_filter([$ind, $cit, $cap], static function ($v) {
            return $v !== '';
        }));
        $cod = trim((string)($row['CODICE_PDV'] ?? ''));
        $lines = [];
        if ($nome !== '') {
            $lines[] = $nome;
        }
        if ($addr !== '') {
            $lines[] = $addr;
        }
        if ($cod !== '') {
            $lines[] = 'Codice: ' . $cod;
        }
        $pdv = $lines !== [] ? implode("\n", $lines) : '—';
    }
    $csvRow = [
        $winDate,
        $row['TIPO_PREMIO'] ?? '-',
        $valore,
        $fullName,
        $row['EMAIL'] ?? '',
        $telefono
    ];
    if ($codicePartecipazioneEnabled) {
        $csvRow[] = trim((string)($row['CODICE_PARTECIPAZIONE'] ?? ''));
    }
    $csvRow[] = trim((string)($row['CODICE_PREMIO'] ?? ''));
    if ($showPdvColumn) {
        $csvRow[] = $pdv;
    }
    fputcsv($out, $csvRow, ';', '"', '\\');
}
fclose($out);
exit;
