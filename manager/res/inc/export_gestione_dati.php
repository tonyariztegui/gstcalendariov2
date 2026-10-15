<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/gestione_dati_context.inc.php';

if (empty($_SESSION['user_id'])) {
    header('Content-Type: text/plain; charset=utf-8');
    http_response_code(401);
    die('Sessione scaduta.');
}

if (!isset($connection) || !$connection) {
    header('Content-Type: text/plain; charset=utf-8');
    http_response_code(500);
    die('DB non disponibile.');
}

$ctx = gestione_dati_load_content();
$content = $ctx['content'];
$defaults = $ctx['defaults'];

$table = gestione_dati_safe_table_name((string) ($_GET['table'] ?? ''), $content, $defaults);
if ($table === null) {
    http_response_code(403);
    die('Tabella non consentita.');
}

$filterActive = trim((string) ($_GET['filterActive'] ?? 'all'));
$filterUsed = trim((string) ($_GET['filterUsed'] ?? 'all'));

$filename = 'export_' . $table . '_' . date('Y-m-d_His') . '.csv';

header('Content-Type: text/csv; charset=utf-8');
header("Content-Disposition: attachment; filename*=UTF-8''" . rawurlencode($filename));
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');

echo "\xEF\xBB\xBF";
$output = fopen('php://output', 'w');

$where = [];
$params = [];

if ($filterActive === '1' || $filterActive === '0') {
    $col = $table === 'quiz_domande' ? 'attiva' : ($table === 'punti_vendita' ? 'active' : 'isActive');
    $where[] = "`{$col}` = ?";
    $params[] = (int) $filterActive;
}
if (($table === 'codici_partecipazione' || $table === 'codici_premio') && ($filterUsed === '1' || $filterUsed === '0')) {
    $where[] = '`used` = ?';
    $params[] = (int) $filterUsed;
}

$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

switch ($table) {
    case 'punti_vendita':
        fputcsv($output, ['ID', 'CODICE', 'RAGIONE_SOCIALE', 'INDIRIZZO', 'CITTA', 'PROVINCIA', 'CAP', 'REGIONE', 'INSEGNA', 'ACTIVE'], ';');
        $sql = "SELECT id, codice, ragsoc, indirizzo, citta, prov, cap, regione, insegna, active FROM punti_vendita {$whereClause} ORDER BY codice ASC";
        break;

    case 'codici_partecipazione':
        fputcsv($output, ['ID', 'CODICE', 'IS_ACTIVE', 'USED', 'ID_PARTECIPAZIONE', 'DATA_CREAZIONE', 'DATA_UTILIZZO'], ';');
        $extra = $whereClause === '' ? 'WHERE' : $whereClause . ' AND';
        $sql = "SELECT id, codice, isActive, used, id_partecipazione, data_creazione, data_utilizzo FROM codici_partecipazione {$extra} codice IS NOT NULL AND codice <> '' ORDER BY data_creazione DESC";
        break;

    case 'codici_premio':
        fputcsv($output, ['ID', 'CODICE', 'PRIZE_ID', 'ID_CODICE_PARTECIPAZIONE', 'ID_PARTECIPAZIONE', 'USED', 'IS_ACTIVE', 'PDF_PATH', 'DATA_CREAZIONE', 'DATA_ASSEGNAZIONE', 'DATA_INVIO_EMAIL'], ';');
        $extra = $whereClause === '' ? 'WHERE' : $whereClause . ' AND';
        $sql = "SELECT id, codice, prize_id, id_codice_partecipazione, id_partecipazione, used, isActive, pdf_path, data_creazione, data_assegnazione, data_invio_email FROM codici_premio {$extra} codice IS NOT NULL AND codice <> '' ORDER BY data_creazione DESC";
        break;

    case 'quiz_domande':
        fputcsv($output, ['ID', 'TESTO', 'TIPO', 'RISPOSTA_A', 'RISPOSTA_B', 'RISPOSTA_C', 'RISPOSTA_D', 'RISPOSTA_CORRETTA', 'ORDINE', 'ATTIVA'], ';');
        $sql = "SELECT id, testo, tipo, risposta_a, risposta_b, risposta_c, risposta_d, risposta_corretta, ordine, attiva FROM quiz_domande {$whereClause} ORDER BY ordine ASC, id ASC";
        break;

    default:
        fclose($output);
        exit;
}

$stmt = $connection->prepare($sql);
$stmt->execute($params);
while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
    fputcsv($output, $row, ';');
}

fclose($output);
exit;
