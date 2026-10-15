<?php

include("_header.php");
include("res/inc/utenze.php");
include("res/inc/header.php");
include("res/inc/sanitize.inc.php");

$_usr_id = $_SESSION['user_id'] ?? '';

// Script pour le modal Dettagli Premio (sera intégré directement dans le footer)
$scripts = '';

// Vérifier si les champs PDV sont activés (contest_config est déjà chargé via utenze.php)
if (!isset($contest_config)) {
    require_once __DIR__ . '/../../app/inc/config.php';
}
$fieldsEnabled = $contest_config['fields_enabled'] ?? [];
$instantwinPerPdv = !empty($contest_config['instantwin_per_pdv']);
$showPdvColumn = ($fieldsEnabled['prov_pdv'] ?? true) || ($fieldsEnabled['citta_pdv'] ?? true) || ($fieldsEnabled['indirizzo_pdv'] ?? true) || $instantwinPerPdv;
$codicePartecipazioneEnabled = !empty($fieldsEnabled['codice_partecipazione']);

// Mostrare il dettaglio "PDF Premio" nel modal Dettagli solo se l'opzione Allega PDF voucher è attiva
$showPdfPremioInDetails = !empty($content_config['email_instantwin_prize_attach_pdf'] ?? false);

require_once __DIR__ . '/../res/inc/premi_stats_helpers.inc.php';
$premiCediCols = manager_premi_detect_cedi_columns($connection);
$hasPremiCodiceCedi = $premiCediCols['has_codice'];
$hasPremiCediNome = $premiCediCols['has_nome'];
$premiCodiceCediSelect = $premiCediCols['codice_sql'];
$premiCediNomeSelect = $premiCediCols['nome_sql'];
$premiCediNomeOrder = $premiCediCols['nome_order_sql'];
$cediLabelMapPremi = [];
if (function_exists('instantwin_cedi_label_map')) {
    global $content_config;
    $cediLabelMapPremi = instantwin_cedi_label_map($connection, is_array($content_config ?? null) ? $content_config : null);
} else {
    require_once __DIR__ . '/../../app/logic/instantwin_cedi/cedi_core.php';
    global $content_config;
    $cediLabelMapPremi = instantwin_cedi_label_map($connection, is_array($content_config ?? null) ? $content_config : null);
}

// Statistiche sintetiche
$totale_premi_disponibili = $connection->query("SELECT SUM(QUANTITA_TOTALE - QUANTITA_ASSEGNATI) FROM premi WHERE IS_ACTIVE = 1")->fetchColumn();
// Compter toutes les victoires non annulées (pas seulement confirmées)
$premi_assegnati = $connection->query("SELECT COUNT(*) FROM partecipazioni WHERE HAS_WON = 1 AND COALESCE(ANNULLATO, 0) = 0 AND PRIZE_ID IS NOT NULL")->fetchColumn();
$premi_oggi = $connection->query("SELECT COUNT(*) FROM partecipazioni WHERE HAS_WON = 1 AND COALESCE(ANNULLATO, 0) = 0 AND PRIZE_ID IS NOT NULL AND DATE(WIN_DATE) = CURDATE()")->fetchColumn();

// Impostazioni tabella premi
$perPageOptions = [15, 25, 50];
$perPage = (int)($_GET['per_page'] ?? 15);
if (!in_array($perPage, $perPageOptions, true)) {
    $perPage = 15;
}
$page = max((int)($_GET['page'] ?? 1), 1);
$searchQuery = trim((string)($_GET['search'] ?? ''));
$searchPdv = trim((string)($_GET['search_pdv'] ?? ''));
$dateFrom = trim((string)($_GET['date_from'] ?? ''));
$dateTo = trim((string)($_GET['date_to'] ?? ''));

$allowedSortKeys = ['win_date', 'premio', 'valore', 'vincitore', 'email', 'telefono'];
if ($hasPremiCodiceCedi) {
    $allowedSortKeys[] = 'cedi';
}
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
    case 'cedi':
        $orderSql = "pr.codice_cedi {$sortDirSql}, {$premiCediNomeOrder} {$sortDirSql}, p.ID DESC";
        break;
    case 'pdv':
        $orderSql = "MAX(COALESCE(mpdv.codice, '')) {$sortDirSql}, MAX(COALESCE(mpdv.ragsoc, '')) {$sortDirSql}, MAX(COALESCE(mpdv.insegna, '')) {$sortDirSql}, MAX(COALESCE(mpdv.citta, '')) {$sortDirSql}, p.ID DESC";
        break;
    case 'win_date':
    default:
        $orderSql = "p.WIN_DATE {$sortDirSql}, p.ID DESC";
        break;
}

// Inclure toutes les victoires non annulées (confirmées ou non)
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
        . ($hasPremiCodiceCedi ? ' OR LOWER(COALESCE(pr.codice_cedi, \'\')) LIKE :search_lower' : '')
        . ($hasPremiCediNome ? ' OR LOWER(COALESCE(pr.cedi_nome, \'\')) LIKE :search_lower' : '')
        // Recherche PDV seulement si les champs sont activés
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

// Utiliser COUNT(DISTINCT p.ID) pour éviter les duplications dues aux JOINs
$countSql = "SELECT COUNT(DISTINCT p.ID) {$baseQuery} {$whereSql}";
$countStmt = $connection->prepare($countSql);
foreach ($bindings as $name => $value) {
    $countStmt->bindValue(':' . $name, $value, PDO::PARAM_STR);
}
$countStmt->execute();
$filteredRecords = (int)$countStmt->fetchColumn();

$totalPages = max((int)ceil($filteredRecords / $perPage), 1);
if ($page > $totalPages) {
    $page = $totalPages;
}
$offset = ($page - 1) * $perPage;

// Utiliser GROUP BY p.ID pour éviter les duplications dues aux JOINs
$dataSql = "
    SELECT
        p.ID AS ID_PARTECIPAZIONE,
        p.WIN_DATE,
        p.PRIZE_ID,
        p.codice_partecipazione AS CODICE_PARTECIPAZIONE,
        pr.VALORE AS VALORE_PREMIO,
        pr.TIPO_PREMIO,
        {$premiCodiceCediSelect} AS CODICE_CEDI_PREMIO,
        {$premiCediNomeSelect} AS CEDI_NOME_PREMIO,
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
        MAX(cp.codice) AS CODICE_PREMIO,
        MAX(cp.pdf_path) AS PDF_PATH
    {$baseQuery}
    {$whereSql}
    GROUP BY p.ID
    ORDER BY {$orderSql}
    LIMIT :limit OFFSET :offset
";
$dataStmt = $connection->prepare($dataSql);
foreach ($bindings as $name => $value) {
    $dataStmt->bindValue(':' . $name, $value, PDO::PARAM_STR);
}
$dataStmt->bindValue(':limit', (int)$perPage, PDO::PARAM_INT);
$dataStmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
$dataStmt->execute();
$premiRows = $dataStmt->fetchAll(PDO::FETCH_ASSOC);

$showingFrom = $filteredRecords > 0 ? $offset + 1 : 0;
$showingTo = $filteredRecords > 0 ? min($offset + count($premiRows), $filteredRecords) : 0;

if (!function_exists('manager_safe_html')) {
    function manager_safe_html(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

$buildPageUrl = function (int $targetPage) {
    $params = $_GET;
    $params['page'] = $targetPage;
    return '?' . http_build_query($params) . '#premi-table';
};

$buildSortUrl = function (string $key) use ($searchQuery, $searchPdv, $dateFrom, $dateTo, $perPage, $sortKey, $sortDirParam) {
    $params = [
        'pg' => 'premi',
        'page' => 1,
        'per_page' => (string) $perPage,
        'sort' => $key,
    ];
    if ($searchQuery !== '') {
        $params['search'] = $searchQuery;
    }
    if ($searchPdv !== '') {
        $params['search_pdv'] = $searchPdv;
    }
    if ($dateFrom !== '') {
        $params['date_from'] = $dateFrom;
    }
    if ($dateTo !== '') {
        $params['date_to'] = $dateTo;
    }
    if ($sortKey === $key) {
        $params['dir'] = ($sortDirParam === 'asc') ? 'desc' : 'asc';
    } else {
        if ($key === 'win_date' || $key === 'valore') {
            $params['dir'] = 'desc';
        } else {
            $params['dir'] = 'asc';
        }
    }
    return '?' . http_build_query($params) . '#premi-table';
};

$sortThLink = function (string $key, string $label) use ($buildSortUrl, $sortKey, $sortDirSql) {
    $href = $buildSortUrl($key);
    $icon = '';
    if ($sortKey === $key) {
        $icon = $sortDirSql === 'ASC'
            ? ' <i class="fa fa-sort-up text-primary" aria-hidden="true"></i>'
            : ' <i class="fa fa-sort-down text-primary" aria-hidden="true"></i>';
    }
    return '<a class="text-dark" href="' . manager_safe_html($href) . '">' . manager_safe_html($label) . $icon . '</a>';
};

// Statistiche per tipologia di premio (raggruppate per Ce.Di.)
$premiStatsBundle = manager_premi_fetch_stats_per_tipo($connection, $premiCediCols);
$stats_per_tipo = $premiStatsBundle['rows'];
$stats_per_tipo_by_cedi = $premiStatsBundle['by_cedi'];

$_title = 'Gestione Premi';

?>

<style>
    .premio-card {
        border-left: 4px solid #28a745;
        margin-bottom: 15px;
    }
    .premio-card.premio-alto {
        border-left-color: #dc3545;
    }
    .premio-card.premio-medio {
        border-left-color: #ffc107;
    }
    .stats-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }
    .toolbar-row {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 12px;
    }
    .toolbar-row .toolbar-right {
        margin-left: auto;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .table-responsive {
        margin-top: 15px;
    }
    .premi-subnav .nav-link { border-radius: 6px; }
    #premi-table thead th a.text-dark {
        white-space: nowrap;
    }
    .premi-pdv-wrap {
        min-width: 200px;
        max-width: 300px;
    }
    .premi-pdv-box {
        background: #f1f3f5;
        border: 1px solid #dee2e6;
        border-radius: 6px;
        padding: 0.65rem 0.75rem;
        text-align: center;
    }
    .premi-pdv-box .premi-pdv-name {
        font-weight: 700;
        color: #212529;
        font-size: 0.9rem;
        line-height: 1.25;
        margin-bottom: 0.4rem;
        display: block;
    }
    .premi-pdv-box .premi-pdv-address {
        font-size: 0.82rem;
        color: #495057;
        line-height: 1.35;
        margin-bottom: 0.35rem;
        display: block;
    }
    .premi-pdv-box .premi-pdv-codice {
        font-size: 0.8rem;
        color: #495057;
        margin-bottom: 0;
    }
</style>

<div class="app-main__outer">
<div class="app-main__inner">
    <?php
    $premiSubnavActive = 'premi';
    include __DIR__ . '/inc/premi_iw_subnav.php';
    ?>
    <?php include __DIR__ . '/inc/premi_stats_tipo_cedi_card.php'; ?>

    <!-- Lista premi assegnati -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header-tab card-header d-flex align-items-center justify-content-between">
                    <h5 class="card-header-title card-title mb-0 font-size-lg text-capitalize font-weight-normal flex-grow-1">
                        <i class="fa fa-list mr-2"></i>Premi Assegnati
                    </h5>
                    <div class="btn-actions-pane-right text-capitalize ms-auto d-flex justify-content-end">
                        <?php
                            $exportParams = [];
                            if ($searchQuery !== '') { $exportParams['search'] = $searchQuery; }
                            if ($searchPdv !== '') { $exportParams['search_pdv'] = $searchPdv; }
                            if ($dateFrom !== '') { $exportParams['date_from'] = $dateFrom; }
                            if ($dateTo !== '') { $exportParams['date_to'] = $dateTo; }
                            $exportParams['sort'] = $sortKey;
                            $exportParams['dir'] = $sortDirParam;
                            $exportPremiUrl = 'res/inc/export_premi_assegnati_csv.php' . (empty($exportParams) ? '' : '?' . http_build_query($exportParams));
                        ?>
                        <a href="<?php echo manager_safe_html($exportPremiUrl); ?>" class="btn btn-success" style="margin:0 10px">Esporta (CSV)</a>
                    </div>
                </div>
                <div class="card-body">
                    <form method="get" action="<?php echo manager_safe_html($_SERVER['PHP_SELF']); ?>" class="toolbar-row mb-3">
                        <input type="hidden" name="pg" value="premi">
                        <input type="hidden" name="page" value="1">
                        <input type="hidden" name="per_page" value="<?php echo (int)$perPage; ?>">
                        <input type="hidden" name="sort" value="<?php echo manager_safe_html($sortKey); ?>">
                        <input type="hidden" name="dir" value="<?php echo manager_safe_html($sortDirParam); ?>">
                        <div class="toolbar-right d-flex align-items-center flex-wrap gap-2">
                            <div class="d-flex align-items-center gap-1">
                                <label class="form-label mb-0 small text-muted me-1">Da</label>
                                <input type="date" name="date_from" class="form-control form-control-sm" style="width: auto;" value="<?php echo manager_safe_html($dateFrom); ?>" onchange="this.form.submit()">
                            </div>
                            <div class="d-flex align-items-center gap-1">
                                <label class="form-label mb-0 small text-muted me-1">A</label>
                                <input type="date" name="date_to" class="form-control form-control-sm" style="width: auto;" value="<?php echo manager_safe_html($dateTo); ?>" onchange="this.form.submit()">
                            </div>
                            <input type="search"
                                   id="premi-search"
                                   name="search"
                                   class="form-control form-control-sm"
                                   placeholder="Cerca premio, vincitore, codice partecipazione o codice premio..."
                                   style="width: 220px;"
                                   value="<?php echo manager_safe_html($searchQuery); ?>"
                                   onsearch="this.form.submit()"
                                   onkeydown="if(event.key === 'Enter'){ this.form.submit(); }">
                            <?php if ($showPdvColumn): ?>
                            <input type="search"
                                   id="premi-search-pdv"
                                   name="search_pdv"
                                   class="form-control form-control-sm"
                                   placeholder="Punto vendita: codice, nome, città..."
                                   style="width: 200px;"
                                   value="<?php echo manager_safe_html($searchPdv); ?>"
                                   onsearch="this.form.submit()"
                                   onkeydown="if(event.key === 'Enter'){ this.form.submit(); }"
                                   title="Filtra per dati del punto vendita (codice, ragione sociale, insegna, indirizzo, CAP, provincia)">
                            <?php endif; ?>
                            <button type="submit" class="btn btn-sm btn-primary">Cerca</button>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table id="premi-table" class="table table-hover table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th><?php echo $sortThLink('win_date', 'Data Vittoria'); ?></th>
                                    <th><?php echo $sortThLink('premio', 'Premio'); ?></th>
                                    <?php if ($hasPremiCodiceCedi): ?>
                                        <th><?php echo $sortThLink('cedi', 'CEDI'); ?></th>
                                    <?php endif; ?>
                                    <th><?php echo $sortThLink('valore', 'Valore'); ?></th>
                                    <th><?php echo $sortThLink('vincitore', 'Vincitore'); ?></th>
                                    <th><?php echo $sortThLink('email', 'Email'); ?></th>
                                    <th><?php echo $sortThLink('telefono', 'Telefono'); ?></th>
                                    <?php if ($showPdvColumn): ?>
                                        <th><?php echo $sortThLink('pdv', 'Punto Vendita'); ?></th>
                                    <?php endif; ?>
                                    <th style="width: 120px;">Dettagli</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($premiRows)): ?>
                                    <?php
                                    $premiTableColspan = 7 + ($hasPremiCodiceCedi ? 1 : 0) + ($showPdvColumn ? 1 : 0);
                                    ?>
                                    <tr>
                                        <td colspan="<?php echo (int) $premiTableColspan; ?>" class="text-center text-muted">Nessun premio assegnato.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($premiRows as $premio): ?>
                                    <?php
                                        $winDate = '-';
                                        if (!empty($premio['WIN_DATE'])) {
                                            $timestamp = strtotime($premio['WIN_DATE']);
                                            if ($timestamp !== false) {
                                                $winDate = date('d/m/Y H:i', $timestamp);
                                            }
                                        }
                                        $valorePremio = isset($premio['VALORE_PREMIO'])
                                            ? number_format((float)$premio['VALORE_PREMIO'], 2)
                                            : '0.00';
                                        $fullName = trim(($premio['NOME'] ?? '') . ' ' . ($premio['COGNOME'] ?? ''));
                                        if ($fullName === '') {
                                            $fullName = $premio['EMAIL'] ?? '-';
                                        }
                                        $tipologia = strtolower(trim((string)($premio['TIPO_PREMIO'] ?? '')));
                                        $premioCardClass = $tipologia !== '' ? 'premio-' . $tipologia : '';
                                        $prefissoCliente = (string)($premio['PREFISSO'] ?? '');
                                        $cellulareRaw = (string)($premio['CELLULARE'] ?? '');
                                        $cellulareDisplay = formatPhoneWithPrefix($prefissoCliente, $cellulareRaw);
                                        $cellulareTel = preg_replace('/[\\s()-]+/', '', $cellulareDisplay);
                                    ?>
                                    <tr>
                                        <td>
                                            <i class="fa fa-calendar text-primary mr-1"></i>
                                            <?php echo manager_safe_html($winDate); ?>
                                        </td>
                                        <td>
                                            <div class="premio-card card p-2<?php echo $premioCardClass !== '' ? ' ' . manager_safe_html($premioCardClass) : ''; ?>">
                                                <strong><?php echo manager_safe_html($premio['TIPO_PREMIO'] ?? '-'); ?></strong>
                                                <br>
                                                <small class="text-muted">&euro;<?php echo manager_safe_html($valorePremio); ?></small>
                                            </div>
                                        </td>
                                        <?php if ($hasPremiCodiceCedi): ?>
                                            <?php
                                                $premioCediCodice = trim((string)($premio['CODICE_CEDI_PREMIO'] ?? ''));
                                                $premioCediNome = trim((string)($premio['CEDI_NOME_PREMIO'] ?? ''));
                                            ?>
                                            <td>
                                                <?php
                                                $cediDisplay = instantwin_cedi_display_label(
                                                    $premioCediCodice,
                                                    $premioCediNome,
                                                    $cediLabelMapPremi
                                                );
                                                if ($cediDisplay !== 'Senza CEDI'): ?>
                                                    <span class="badge badge-secondary"><?php echo manager_safe_html($cediDisplay); ?></span>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                        <?php endif; ?>
                                        <td>
                                            <span class="badge badge-success badge-lg">
                                                &euro;<?php echo manager_safe_html($valorePremio); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <strong><?php echo manager_safe_html($fullName); ?></strong>
                                        </td>
                                        <td>
                                            <?php if (!empty($premio['EMAIL'])): ?>
                                                <a href="mailto:<?php echo manager_safe_html($premio['EMAIL']); ?>" class="text-primary">
                                                    <i class="fa fa-envelope mr-1"></i>
                                                    <?php echo manager_safe_html($premio['EMAIL']); ?>
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($cellulareDisplay !== ''): ?>
                                                <a href="tel:<?php echo manager_safe_html($cellulareTel); ?>" class="text-success">
                                                    <i class="fa fa-phone mr-1"></i>
                                                    <?php echo manager_safe_html($cellulareDisplay); ?>
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <?php if ($showPdvColumn): ?>
                                            <td class="align-middle">
                                                <?php
                                                    $pdvNome = trim((string)($premio['RAGSOC'] ?? ''));
                                                    if ($pdvNome === '') {
                                                        $pdvNome = trim((string)($premio['INSEGNA'] ?? ''));
                                                    }
                                                    $pdvInd = trim((string)($premio['INDIRIZZO'] ?? ''));
                                                    $pdvCitta = trim((string)($premio['CITTA'] ?? ''));
                                                    $pdvCap = trim((string)($premio['CAP_PDV'] ?? ''));
                                                    $pdvCod = trim((string)($premio['CODICE_PDV'] ?? ''));
                                                    $addrBits = array_filter([$pdvInd, $pdvCitta, $pdvCap], static function ($v) {
                                                        return $v !== '';
                                                    });
                                                    $pdvIndirizzoLine = implode(' - ', $addrBits);
                                                    $pdvHasAny = $pdvNome !== '' || $pdvIndirizzoLine !== '' || $pdvCod !== '';
                                                ?>
                                                <div class="premi-pdv-wrap">
                                                    <?php if ($pdvHasAny): ?>
                                                        <div class="premi-pdv-box">
                                                            <?php if ($pdvNome !== ''): ?>
                                                                <span class="premi-pdv-name"><?php echo manager_safe_html($pdvNome); ?></span>
                                                            <?php endif; ?>
                                                            <?php if ($pdvIndirizzoLine !== ''): ?>
                                                                <span class="premi-pdv-address"><?php echo manager_safe_html($pdvIndirizzoLine); ?></span>
                                                            <?php endif; ?>
                                                            <?php if ($pdvCod !== ''): ?>
                                                                <span class="premi-pdv-codice">Codice: <?php echo manager_safe_html($pdvCod); ?></span>
                                                            <?php endif; ?>
                                                        </div>
                                                    <?php else: ?>
                                                        <div class="premi-pdv-box">
                                                            <span class="text-muted small">—</span>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        <?php endif; ?>
                                        <td>
                                            <?php
                                                $codicePartecipazione = trim((string)($premio['CODICE_PARTECIPAZIONE'] ?? ''));
                                                $codicePremio = trim((string)($premio['CODICE_PREMIO'] ?? ''));
                                                $pdfPath = trim((string)($premio['PDF_PATH'] ?? ''));
                                                $hasDetails = ($codicePartecipazioneEnabled && $codicePartecipazione !== '') || $codicePremio !== '' || $pdfPath !== '';
                                            ?>
                                            <?php if ($hasDetails): ?>
                                                <button 
                                                    class="btn btn-sm btn-outline-info btnPremioDetails" 
                                                    data-participation-id="<?php echo (int)$premio['ID_PARTECIPAZIONE']; ?>"
                                                    data-codice-partecipazione="<?php echo manager_safe_html($codicePartecipazione); ?>"
                                                    data-codice-premio="<?php echo manager_safe_html($codicePremio); ?>"
                                                    data-pdf-path="<?php echo manager_safe_html($pdfPath); ?>">
                                                    <i class="fa fa-info-circle"></i> Dettagli
                                                </button>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex align-items-center flex-wrap gap-3 mt-3">
                        <div class="text-muted small flex-grow-1">
                            <?php if ($filteredRecords > 0): ?>
                                Mostrando <?php echo $showingFrom; ?>-<?php echo $showingTo; ?> di <?php echo $filteredRecords; ?> premi assegnati
                            <?php else: ?>
                                Nessun premio da mostrare
                            <?php endif; ?>
                        </div>
                        <div class="d-flex align-items-center justify-content-end flex-wrap ms-auto" style="gap: 1.5rem;">
                            <nav class="mb-2 mb-sm-0">
                                <ul class="pagination pagination-sm mb-0">
                                    <?php
                                        $prevDisabled = ($page <= 1);
                                        $nextDisabled = ($page >= $totalPages);
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
                                <input type="hidden" name="pg" value="premi">
                                <input type="hidden" name="page" value="1">
                                <?php if ($searchQuery !== ''): ?>
                                    <input type="hidden" name="search" value="<?php echo manager_safe_html($searchQuery); ?>">
                                <?php endif; ?>
                                <?php if ($searchPdv !== ''): ?>
                                    <input type="hidden" name="search_pdv" value="<?php echo manager_safe_html($searchPdv); ?>">
                                <?php endif; ?>
                                <?php if ($dateFrom !== ''): ?>
                                    <input type="hidden" name="date_from" value="<?php echo manager_safe_html($dateFrom); ?>">
                                <?php endif; ?>
                                <?php if ($dateTo !== ''): ?>
                                    <input type="hidden" name="date_to" value="<?php echo manager_safe_html($dateTo); ?>">
                                <?php endif; ?>
                                <input type="hidden" name="sort" value="<?php echo manager_safe_html($sortKey); ?>">
                                <input type="hidden" name="dir" value="<?php echo manager_safe_html($sortDirParam); ?>">
                                <select id="premi-per-page" name="per_page" class="form-select form-select-sm" onchange="this.form.submit()">
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
</div>

<!-- Modal Dettagli Premio -->
<div class="modal fade" id="modalPremioDetails" tabindex="-1" role="dialog" aria-labelledby="modalPremioDetailsLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalPremioDetailsLabel">
                    <i class="fa fa-info-circle mr-2"></i>Dettagli Premio
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <?php if ($codicePartecipazioneEnabled): ?>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="font-weight-bold">Codice Partecipazione:</label>
                            <div id="modal-codice-partecipazione" class="p-2 bg-light border rounded">
                                <span class="text-muted">-</span>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    <div class="<?php echo $codicePartecipazioneEnabled ? 'col-md-6' : 'col-12'; ?>">
                        <div class="form-group">
                            <label class="font-weight-bold">Codice Premio:</label>
                            <div id="modal-codice-premio" class="p-2 bg-light border rounded">
                                <span class="text-muted">-</span>
                            </div>
                        </div>
                    </div>
                </div>
                <?php if ($showPdfPremioInDetails): ?>
                <div class="row">
                    <div class="col-12">
                        <div class="form-group">
                            <label class="font-weight-bold">PDF Premio:</label>
                            <div id="modal-pdf-container" class="p-2 bg-light border rounded text-center">
                                <span class="text-muted">Nessun PDF disponibile</span>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Chiudi</button>
            </div>
        </div>
    </div>
</div>


<?php include("_footer.php"); ?>
