<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/gestione_dati_context.inc.php';
require_once __DIR__ . '/gestione_dati_columns.inc.php';

gestione_dati_require_auth_json();

$draw = isset($_POST['draw']) ? (int) $_POST['draw'] : 0;
$ctx = gestione_dati_load_content();
$content = $ctx['content'];
$defaults = $ctx['defaults'];

$table = gestione_dati_safe_table_name((string) ($_POST['table'] ?? ''), $content, $defaults);
if ($table === null) {
    http_response_code(403);
    echo json_encode([
        'data' => [],
        'recordsTotal' => 0,
        'recordsFiltered' => 0,
        'draw' => $draw,
        'error' => 'Tabella non consentita per questo concorso.',
    ]);
    exit;
}

if (!isset($connection) || !$connection) {
    http_response_code(500);
    echo json_encode(['data' => [], 'recordsTotal' => 0, 'recordsFiltered' => 0, 'draw' => $draw, 'error' => 'DB non disponibile.']);
    exit;
}

$connection->exec("SET NAMES utf8mb4");

$start = isset($_POST['start']) ? max((int) $_POST['start'], 0) : 0;
$length = isset($_POST['length']) ? (int) $_POST['length'] : 25;
$length = $length > 0 ? min($length, 200) : 25;
$search = isset($_POST['search']['value']) ? trim((string) $_POST['search']['value']) : '';
$filterActive = trim((string) ($_POST['filterActive'] ?? 'all'));
$filterUsed = trim((string) ($_POST['filterUsed'] ?? 'all'));
$orderField = trim((string) ($_POST['orderField'] ?? ''));
$ctxResolved = gestione_dati_resolve_context($connection, $content, $defaults);

function gd_h(?string $v): string
{
    return htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function gd_actions(int $id): string
{
    return '<div class="btn-group btn-group-sm gestione-dati-row-actions" role="group">'
        . '<button type="button" class="btn btn-outline-primary gd-btn-edit" data-id="' . $id . '" title="Modifica"><i class="fa fa-pencil"></i></button>'
        . '<button type="button" class="btn btn-outline-danger gd-btn-delete" data-id="' . $id . '" title="Elimina"><i class="fa fa-trash"></i></button>'
        . '</div>';
}

function gd_apply_flag_filter(string $column, string $filter, array &$where, array &$params): void
{
    if ($filter === '1' || $filter === '0') {
        $where[] = "{$column} = ?";
        $params[] = (int) $filter;
    }
}

$where = [];
$params = [];
$from = '';
$columnsMap = [];
$searchColumns = [];

switch ($table) {
    case 'punti_vendita':
        $from = 'punti_vendita p';
        gd_apply_flag_filter('p.active', $filterActive, $where, $params);
        $columnsMap = ['p.codice', 'p.ragsoc', 'p.citta', 'p.prov', 'p.insegna', 'p.active', 'p.id'];
        $searchColumns = ['p.codice', 'p.ragsoc', 'p.citta', 'p.prov', 'p.cap', 'p.indirizzo', 'p.insegna', 'p.regione', 'CAST(p.id AS CHAR)'];
        if (gestione_dati_table_has_column($connection, 'punti_vendita', 'codice_cedi')) {
            $searchColumns[] = 'p.codice_cedi';
        }
        if (gestione_dati_table_has_column($connection, 'punti_vendita', 'email_pdv')) {
            $searchColumns[] = 'p.email_pdv';
        }
        break;

    case 'codici_partecipazione':
        $from = 'codici_partecipazione cp LEFT JOIN (
            SELECT id_codice_partecipazione, MAX(prize_id) AS prize_id
            FROM codici_premio
            WHERE id_codice_partecipazione IS NOT NULL
            GROUP BY id_codice_partecipazione
        ) cpr_link ON cpr_link.id_codice_partecipazione = cp.id';
        gd_apply_flag_filter('cp.used', $filterUsed, $where, $params);
        gd_apply_flag_filter('cp.isActive', $filterActive, $where, $params);
        $where[] = "cp.codice IS NOT NULL AND cp.codice <> ''";
        $columnsMap = ['cp.codice', 'cp.used', 'cp.isActive', 'cpr_link.prize_id', 'cp.id_partecipazione', 'cp.data_creazione', 'cp.id'];
        $searchColumns = ['cp.codice', 'CAST(cp.id AS CHAR)', 'CAST(cp.id_partecipazione AS CHAR)', 'CAST(cpr_link.prize_id AS CHAR)'];
        break;

    case 'codici_premio':
        $from = 'codici_premio cpr LEFT JOIN premi pr ON pr.id = cpr.prize_id';
        gd_apply_flag_filter('cpr.used', $filterUsed, $where, $params);
        gd_apply_flag_filter('cpr.isActive', $filterActive, $where, $params);
        $where[] = "cpr.codice IS NOT NULL AND cpr.codice <> ''";
        $columnsMap = ['cpr.codice', 'pr.valore', 'cpr.prize_id', 'cpr.used', 'cpr.isActive', 'cpr.data_assegnazione', 'cpr.id'];
        $searchColumns = ['cpr.codice', 'pr.tipo_premio', 'CAST(cpr.prize_id AS CHAR)', 'CAST(pr.valore AS CHAR)', 'CAST(cpr.id_partecipazione AS CHAR)', 'CAST(cpr.id AS CHAR)'];
        break;

    case 'quiz_domande':
        $from = 'quiz_domande q';
        gd_apply_flag_filter('q.attiva', $filterActive, $where, $params);
        $columnsMap = ['q.ordine', 'q.testo', 'q.tipo', 'q.attiva', 'q.id'];
        $searchColumns = ['q.testo', 'q.tipo', 'q.risposta_corretta', 'CAST(q.id AS CHAR)', 'CAST(q.ordine AS CHAR)'];
        break;
}

if ($search !== '' && !empty($searchColumns)) {
    $likeParts = [];
    foreach ($searchColumns as $col) {
        $likeParts[] = "{$col} LIKE ?";
        $params[] = '%' . $search . '%';
    }
    $where[] = '(' . implode(' OR ', $likeParts) . ')';
}

$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

$countTotalSql = match ($table) {
    'punti_vendita' => 'SELECT COUNT(*) FROM punti_vendita',
    'codici_partecipazione' => "SELECT COUNT(*) FROM codici_partecipazione WHERE codice IS NOT NULL AND codice <> ''",
    'codici_premio' => "SELECT COUNT(*) FROM codici_premio WHERE codice IS NOT NULL AND codice <> ''",
    'quiz_domande' => 'SELECT COUNT(*) FROM quiz_domande',
    default => 'SELECT 0',
};
$recordsTotal = (int) $connection->query($countTotalSql)->fetchColumn();

$countFilteredSql = "SELECT COUNT(*) FROM {$from} {$whereClause}";
$stmtCount = $connection->prepare($countFilteredSql);
$stmtCount->execute($params);
$recordsFiltered = (int) $stmtCount->fetchColumn();

$orderDir = isset($_POST['order'][0]['dir']) && strtolower($_POST['order'][0]['dir']) === 'asc' ? 'ASC' : 'DESC';
if ($orderField === '' && isset($_POST['order'][0]['column'])) {
    $catalogForOrder = gestione_dati_table_columns_catalog($table, $connection, $ctxResolved, $content, $defaults);
    $visibleForOrder = gestione_dati_resolve_visible_columns($table, $catalogForOrder, $content, $defaults);
    $orderIdx = (int) $_POST['order'][0]['column'];
    if (isset($visibleForOrder[$orderIdx])) {
        $orderField = (string) $visibleForOrder[$orderIdx];
    }
}
$orderBy = gestione_dati_sort_sql_for_field($table, $orderField !== '' ? $orderField : 'codice', $connection, $ctxResolved, $content, $defaults) . ' ' . $orderDir;

$selectSql = match ($table) {
    'punti_vendita' => "SELECT p.id, p.codice, p.ragsoc, p.indirizzo, p.citta, p.prov, p.cap, p.regione, p.insegna, p.active"
        . (gestione_dati_table_has_column($connection, 'punti_vendita', 'codice_cedi') ? ', p.codice_cedi' : '')
        . (gestione_dati_table_has_column($connection, 'punti_vendita', 'email_pdv') ? ', p.email_pdv' : '')
        . " FROM {$from} {$whereClause} ORDER BY {$orderBy} LIMIT ? OFFSET ?",
    'codici_partecipazione' => "SELECT cp.id, cp.codice, cp.used, cp.isActive, cp.id_partecipazione, cp.data_creazione, cp.data_utilizzo, cpr_link.prize_id FROM {$from} {$whereClause} ORDER BY {$orderBy} LIMIT ? OFFSET ?",
    'codici_premio' => "SELECT cpr.id, cpr.codice, cpr.prize_id, pr.valore AS premio_valore, pr.tipo_premio AS premio_tipo, cpr.used, cpr.isActive, cpr.id_partecipazione, cpr.data_assegnazione, cpr.id_codice_partecipazione FROM {$from} {$whereClause} ORDER BY {$orderBy} LIMIT ? OFFSET ?",
    'quiz_domande' => "SELECT q.id, q.ordine, q.testo, q.tipo, q.risposta_corretta, q.risposta_a, q.risposta_b, q.risposta_c, q.risposta_d, q.attiva, q.audio_file FROM {$from} {$whereClause} ORDER BY {$orderBy} LIMIT ? OFFSET ?",
    default => '',
};

$stmt = $connection->prepare($selectSql);
$bindIndex = 1;
foreach ($params as $p) {
    $stmt->bindValue($bindIndex++, $p);
}
$stmt->bindValue($bindIndex++, $length, PDO::PARAM_INT);
$stmt->bindValue($bindIndex, $start, PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$data = [];
foreach ($rows as $row) {
    $id = (int) ($row['id'] ?? 0);
    switch ($table) {
        case 'punti_vendita':
            $rowData = [
                'codice' => gd_h($row['codice'] ?? ''),
                'ragsoc' => gd_h($row['ragsoc'] ?? ''),
                'indirizzo' => gd_h($row['indirizzo'] ?? ''),
                'citta' => gd_h($row['citta'] ?? ''),
                'prov' => gd_h($row['prov'] ?? ''),
                'cap' => gd_h($row['cap'] ?? ''),
                'regione' => gd_h($row['regione'] ?? ''),
                'insegna' => gd_h($row['insegna'] ?? ''),
                'active' => gestione_dati_format_flag_badge($row['active'] ?? 0, 'Attivo', 'Inattivo', 'badge-success', 'badge-secondary'),
                'azioni' => gd_actions($id),
                'DT_RowId' => 'gd_row_' . $id,
            ];
            if (gestione_dati_table_has_column($connection, 'punti_vendita', 'codice_cedi')) {
                $rowData['codice_cedi'] = gd_h($row['codice_cedi'] ?? '');
            }
            if (gestione_dati_table_has_column($connection, 'punti_vendita', 'email_pdv')) {
                $rowData['email_pdv'] = gd_h($row['email_pdv'] ?? '');
            }
            $data[] = $rowData;
            break;

        case 'codici_partecipazione':
            $prizeId = (int) ($row['prize_id'] ?? 0);
            $data[] = [
                'codice' => '<code>' . gd_h($row['codice'] ?? '') . '</code>',
                'used' => gestione_dati_format_flag_badge($row['used'] ?? 0, 'Usato', 'Libero', 'badge-warning', 'badge-success'),
                'isActive' => gestione_dati_format_flag_badge($row['isActive'] ?? 1, 'Attivo', 'Inattivo'),
                'prize_id' => $prizeId > 0 ? gd_h((string) $prizeId) : '—',
                'id_partecipazione' => gd_h((string) ($row['id_partecipazione'] ?? '—')),
                'data_creazione' => gd_h((string) ($row['data_creazione'] ?? '')),
                'data_utilizzo' => gd_h((string) ($row['data_utilizzo'] ?? '—')),
                'azioni' => gd_actions($id),
                'DT_RowId' => 'gd_row_' . $id,
            ];
            break;

        case 'codici_premio':
            $premioLabel = trim((string) ($row['premio_tipo'] ?? ''));
            if ($premioLabel === '') {
                $premioLabel = 'Premio';
            }
            $valore = $row['premio_valore'] ?? '';
            if ($valore !== '' && $valore !== null) {
                $premioLabel .= ' (€' . $valore . ')';
            }
            $premioLabel .= ' #' . (int) ($row['prize_id'] ?? 0);
            $data[] = [
                'codice' => '<code>' . gd_h($row['codice'] ?? '') . '</code>',
                'premio' => gd_h($premioLabel),
                'prize_id' => gd_h((string) (int) ($row['prize_id'] ?? 0)),
                'used' => gestione_dati_format_flag_badge($row['used'] ?? 0, 'Usato', 'Libero', 'badge-warning', 'badge-success'),
                'isActive' => gestione_dati_format_flag_badge($row['isActive'] ?? 1, 'Attivo', 'Inattivo'),
                'data_assegnazione' => gd_h((string) ($row['data_assegnazione'] ?? '—')),
                'id_codice_partecipazione' => gd_h((string) ($row['id_codice_partecipazione'] ?? '—')),
                'azioni' => gd_actions($id),
                'DT_RowId' => 'gd_row_' . $id,
            ];
            break;

        case 'quiz_domande':
            $testo = (string) ($row['testo'] ?? '');
            if (mb_strlen($testo) > 80) {
                $testo = mb_substr($testo, 0, 77) . '…';
            }
            $data[] = [
                'ordine' => (int) ($row['ordine'] ?? 0),
                'testo' => gd_h($testo),
                'tipo' => gd_h(strtoupper((string) ($row['tipo'] ?? 'text'))),
                'attiva' => gestione_dati_format_flag_badge($row['attiva'] ?? 1, 'Attiva', 'Disattiva'),
                'azioni' => gd_actions($id),
                'DT_RowId' => 'gd_row_' . $id,
            ];
            break;
    }
}

echo json_encode([
    'data' => $data,
    'recordsTotal' => $recordsTotal,
    'recordsFiltered' => $recordsFiltered,
    'draw' => $draw,
]);
