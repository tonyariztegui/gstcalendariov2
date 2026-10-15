<?php 

	include("_header.php"); 
	include("res/inc/utenze.php");
	include("res/inc/header.php");
    include("res/inc/sanitize.inc.php");

    $tipo = (isset($_GET['tp'])) ? $_GET['tp'] : '';

    // Variables pour l'affichage
    $_usr_id = $_SESSION['user_id'] ?? '';
    $fieldsEnabled = $contest_config['fields_enabled'] ?? [];
    $emailEnabled = $fieldsEnabled['email'] ?? true;
    $fidelityEnabled = $fieldsEnabled['fidelity'] ?? true;

    // Parametri filtro backend
    $searchTerm = isset($_GET['search']) ? trim((string)$_GET['search']) : '';
    $filterStatus = '';

    $perPageOptions = [15, 25, 50];
    $perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 50;
    if (!in_array($perPage, $perPageOptions, true)) {
        $perPage = 50;
    }
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $page = $page > 0 ? $page : 1;

    $sortKey = strtolower(trim((string)($_GET['sort'] ?? 'default')));
    if (!in_array($sortKey, ['partecipazioni', 'default'], true)) {
        $sortKey = 'default';
    }
    $sortDirParam = strtolower(trim((string)($_GET['dir'] ?? 'desc')));
    if (!in_array($sortDirParam, ['asc', 'desc'], true)) {
        $sortDirParam = 'desc';
    }
    $sortDirSql = strtoupper($sortDirParam);

    $utenti_query_error = null;
    $whereClauses = [];
    $queryParams = [];

    if ($searchTerm !== '') {
        $whereClauses[] = "(u.nome LIKE :search OR u.cognome LIKE :search"
            . " OR TRIM(CONCAT(COALESCE(u.nome, ''), ' ', COALESCE(u.cognome, ''))) LIKE :search"
            . " OR TRIM(CONCAT(COALESCE(u.cognome, ''), ' ', COALESCE(u.nome, ''))) LIKE :search"
            . " OR u.email LIKE :search OR u.cellulare LIKE :search)";
        $queryParams[':search'] = '%' . $searchTerm . '%';
    }

    $whereSql = '';
    if (!empty($whereClauses)) {
        $whereSql = 'WHERE ' . implode(' AND ', $whereClauses);
    }

    $hasFidelityColumn = false;
    if ($fidelityEnabled) {
        try {
            $fidelityColCheck = $connection->query("SHOW COLUMNS FROM utenti LIKE 'fidelity'");
            $hasFidelityColumn = $fidelityColCheck && $fidelityColCheck->fetch() !== false;
        } catch (PDOException $e) {
            $hasFidelityColumn = false;
        }
    }
    $useFidelityGrouping = $fidelityEnabled && $hasFidelityColumn;

    $groupingKeySql = $useFidelityGrouping
        ? "CASE WHEN LOWER(TRIM(COALESCE(u.fidelity, ''))) <> '' THEN LOWER(TRIM(COALESCE(u.fidelity, ''))) ELSE LOWER(TRIM(COALESCE(u.email, ''))) END"
        : "LOWER(TRIM(COALESCE(u.email, '')))";

    // GROUP BY expression (not alias) + MAX() for ONLY_FULL_GROUP_BY (MySQL 8 / Aruba)
    $groupingSql = "
        SELECT 
            MAX(LOWER(TRIM(COALESCE(u.email, '')))) AS email_key,
            MAX(LOWER(TRIM(COALESCE(u.fidelity, '')))) AS fidelity_key,
            {$groupingKeySql} AS grouping_key,
            MIN(u.id_cliente) AS main_id,
            GROUP_CONCAT(DISTINCT u.id_cliente ORDER BY u.id_cliente) AS user_ids,
            COUNT(DISTINCT u.id_cliente) AS utenti_count,
            SUM(COALESCE(part.totale_partecipazioni, 0)) AS totale_partecipazioni
        FROM utenti u
        LEFT JOIN (
            SELECT id_cliente, COUNT(*) AS totale_partecipazioni
            FROM partecipazioni
            GROUP BY id_cliente
        ) part ON part.id_cliente = u.id_cliente
        $whereSql
        GROUP BY {$groupingKeySql}
    ";

    try {
        $countSql = "SELECT COUNT(*) FROM ({$groupingSql}) grouped";
        $countStmt = $connection->prepare($countSql);
        foreach ($queryParams as $key => $value) {
            $countStmt->bindValue($key, $value, PDO::PARAM_STR);
        }
        $countStmt->execute();
        $totalRecords = (int)$countStmt->fetchColumn();

        $totalPages = max((int)ceil($totalRecords / $perPage), 1);
        if ($page > $totalPages) {
            $page = $totalPages;
        }
        $offset = ($page - 1) * $perPage;

        $orderBySql = $sortKey === 'partecipazioni'
            ? "totale_partecipazioni {$sortDirSql}, main_id DESC"
            : 'main_id DESC';
        $dataSql = $groupingSql . " ORDER BY {$orderBySql} LIMIT :limit OFFSET :offset";
        $dataStmt = $connection->prepare($dataSql);
        foreach ($queryParams as $key => $value) {
            $dataStmt->bindValue($key, $value, PDO::PARAM_STR);
        }
        $dataStmt->bindValue(':limit', (int)$perPage, PDO::PARAM_INT);
        $dataStmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        $dataStmt->execute();
        $rows = $dataStmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('manager/utenti.php query error: ' . $e->getMessage());
        $totalRecords = 0;
        $totalPages = 1;
        $page = 1;
        $offset = 0;
        $rows = [];
        $utenti_query_error = 'Impossibile caricare gli utenti. Verificare la struttura del database (tabelle utenti/partecipazioni).';
    }

    $utenti_data = [];
    foreach ($rows as $row) {
        $emailKey = trim((string)($row['email_key'] ?? ''));
        $fidelityKey = trim((string)($row['fidelity_key'] ?? ''));
        $groupKey = trim((string)($row['grouping_key'] ?? ''));
        $token = $groupKey !== '' ? $groupKey : ($emailKey !== '' ? $emailKey : '__EMPTY__');
        $display = ($useFidelityGrouping && $fidelityKey !== '') ? $fidelityKey : ($emailKey !== '' ? $emailKey : 'Senza email');
        $userIds = [];
        if (!empty($row['user_ids'])) {
            $userIds = array_filter(array_map('trim', explode(',', (string)$row['user_ids'])));
        }
        $utenti_data[] = [
            'EMAIL_KEY' => $emailKey,
            'FIDELITY_KEY' => $fidelityKey,
            'GROUP_TOKEN' => $token,
            'DISPLAY_VALUE' => $display,
            'MAIN_ID' => (int)($row['main_id'] ?? 0),
            'USER_IDS' => $userIds,
            'UTENTI_COUNT' => (int)($row['utenti_count'] ?? 0),
            'TOTALE_PARTECIPAZIONI' => (int)($row['totale_partecipazioni'] ?? 0)
        ];
    }

    $startRecord = $totalRecords > 0 ? $offset + 1 : 0;
    $endRecord = $totalRecords > 0 ? min($offset + $perPage, $totalRecords) : 0;

    $queryBase = [
        'pg' => 'utenti',
    ];
    $queryBase['per_page'] = $perPage;
    if ($tipo !== '') {
        $queryBase['tp'] = $tipo;
    }
    if ($searchTerm !== '') {
        $queryBase['search'] = $searchTerm;
    }
    if ($sortKey === 'partecipazioni') {
        $queryBase['sort'] = 'partecipazioni';
        $queryBase['dir'] = $sortDirParam;
    }
    $buildUserPageUrl = function (int $targetPage) use ($queryBase): string {
        $params = $queryBase;
        $params['page'] = $targetPage;
        return 'index.php?' . http_build_query($params) . '#utenti-list';
    };
    $buildSortUrl = function (string $key) use ($queryBase, $sortKey, $sortDirParam): string {
        $params = $queryBase;
        $params['page'] = 1;
        if ($key === 'partecipazioni') {
            $params['sort'] = 'partecipazioni';
            if ($sortKey === 'partecipazioni') {
                $params['dir'] = ($sortDirParam === 'asc') ? 'desc' : 'asc';
            } else {
                $params['dir'] = 'desc';
            }
        } else {
            unset($params['sort'], $params['dir']);
        }
        return 'index.php?' . http_build_query($params) . '#utenti-list';
    };
    $sortThLink = function (string $key, string $label) use ($buildSortUrl, $sortKey, $sortDirSql): string {
        $href = $buildSortUrl($key);
        $icon = '';
        if ($sortKey === $key) {
            $icon = $sortDirSql === 'ASC'
                ? ' <i class="fa fa-sort-up text-primary" aria-hidden="true"></i>'
                : ' <i class="fa fa-sort-down text-primary" aria-hidden="true"></i>';
        }
        return '<a class="text-dark" href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . $icon . '</a>';
    };

    $_title = 'Gestione Utenti - ' . $contest_config['name'];

?>

	<style>
		.rct {color:#ccc}
        .table-users td {vertical-align: middle !important;}
        .toolbar-row {
            display: flex;
            align-items: center;
            width: 100%;
            gap: 10px;
            margin-bottom: 20px;
            margin-top: 10px;
        }
        .toolbar-refresh,
        .toolbar-center,
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
        .toolbar-right .form-control {
            width: 220px;
        }
        @media (max-width: 992px) {
            .toolbar-row {
                flex-direction: column;
                align-items: stretch;
                gap: 10px;
            }
            .toolbar-refresh,
            .toolbar-center,
            .toolbar-right {
                width: 100%;
                justify-content: flex-start;
            }
            .toolbar-center {
                justify-content: flex-start;
            }
            .toolbar-right {
                margin-left: 0;
            }
            .toolbar-right .form-control {
                width: 100%;
            }
        }
        .pagination-wrapper {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
        }
	</style>

    <div class="app-main__outer" id="custom_filter" data-val="<?php echo $_usr_id; ?>" data-tp="<?php echo $tipo; ?>">
                <div class="app-main__inner">
                    <div class="tabs-animation">
                        
                        <div class="card mb-3">
                            <div class="card-header-tab card-header">
                                <div class="card-header-title font-size-lg text-capitalize font-weight-normal">
                                    <i class="header-icon lnr-list icon-gradient bg-happy-green"> </i>
                                    <?php echo $_title; ?>
                                </div>
                                <div class="btn-actions-pane-right text-capitalize">
                                    <?php if ($_usr_id == '') { ?>
                                        <a href="res/inc/export_utenti.php" class="btn btn-success" style="margin:0 10px">Esporta Utenti (CSV)</a>
                                    <?php } ?>
                                </div>
                            </div>
                            <?php $utentiCardBodyTag = 'd' . 'iv'; ?>
                            <<?php echo $utentiCardBodyTag; ?> class="card-body">
                                <?php if (!empty($utenti_query_error)): ?>
                                    <div class="alert alert-danger"><?php echo htmlspecialchars($utenti_query_error, ENT_QUOTES, 'UTF-8'); ?></div>
                                <?php endif; ?>
                                <form method="get" action="index.php" class="toolbar-row mb-3">
                                    <input type="hidden" name="pg" value="utenti">
                                    <input type="hidden" name="page" value="1">
                                    <input type="hidden" name="per_page" value="<?php echo (int)$perPage; ?>">
                                    <?php if ($tipo !== ''): ?>
                                        <input type="hidden" name="tp" value="<?php echo htmlspecialchars($tipo, ENT_QUOTES, 'UTF-8'); ?>">
                                    <?php endif; ?>
                                    <?php if ($sortKey === 'partecipazioni'): ?>
                                        <input type="hidden" name="sort" value="partecipazioni">
                                        <input type="hidden" name="dir" value="<?php echo htmlspecialchars($sortDirParam, ENT_QUOTES, 'UTF-8'); ?>">
                                    <?php endif; ?>
                                    <div class="toolbar-refresh">
                                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.location.reload();">Aggiorna</button>
                                    </div>
                                    <div class="toolbar-right">
                                        <input
                                            type="search"
                                            id="search"
                                            name="search"
                                            class="form-control form-control-sm"
                                            placeholder="Cerca..."
                                            value="<?php echo htmlspecialchars($searchTerm, ENT_QUOTES, 'UTF-8'); ?>"
                                            onsearch="this.form.submit()"
                                            onkeydown="if (event.key === 'Enter') { this.form.submit(); }">
                                        <button type="submit" class="btn btn-sm btn-primary">Cerca</button>
                                    </div>
                                </form>

                                <div class="table-responsive">
                                    <table id="utenti-list" class="table table-hover table-striped table-bordered table-users" style="width: 100%; min-width: 900px;">
                                        <thead>
                                        <tr>
                                            <?php
                                                $columnLabel = ($fidelityEnabled && $emailEnabled) ? 'Fidelity' : ($emailEnabled ? 'Email' : 'Fidelity');
                                            ?>
                                            <th><?php echo htmlspecialchars($columnLabel, ENT_QUOTES, 'UTF-8'); ?></th>
                                            <th class="text-center" style="width: 130px;"><?php echo $sortThLink('partecipazioni', 'N. partecipazioni'); ?></th>
                                            <th style="width: 150px;">Storico</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <?php if (empty($utenti_data)): ?>
                                            <tr><td colspan="3" class="text-center text-muted">Nessun utente registrato.</td></tr>
                                        <?php else: ?>
                                            <?php foreach ($utenti_data as $utente): ?>
                                                <?php
                                                    $mainId = (int)($utente['MAIN_ID'] ?? 0);
                                                    $emailKey = trim((string)($utente['EMAIL_KEY'] ?? ''));
                                                    $fidelityKey = trim((string)($utente['FIDELITY_KEY'] ?? ''));
                                                    $groupToken = (string)($utente['GROUP_TOKEN'] ?? '__EMPTY__');
                                                    $displayValue = trim((string)($utente['DISPLAY_VALUE'] ?? ''));
                                                    $userIds = !empty($utente['USER_IDS']) ? implode(',', $utente['USER_IDS']) : '';
                                                    $partecipazioni = (int)($utente['TOTALE_PARTECIPAZIONI'] ?? 0);

                                                    $displayHtml = $displayValue !== ''
                                                        ? htmlspecialchars($displayValue, ENT_QUOTES, 'UTF-8')
                                                        : '<span class="text-muted">Senza email</span>';
                                                ?>
                                                <tr>
                                                    <td><?php echo $displayHtml; ?></td>
                                                    <td class="text-center"><?php echo $partecipazioni > 0 ? (int)$partecipazioni : '<span class="text-muted">0</span>'; ?></td>
                                                    <td>
                                                        <button 
                                                            class="btn btn-sm btn-outline-primary btnFidelityHistory" 
                                                            data-email="<?php echo htmlspecialchars($groupToken, ENT_QUOTES, 'UTF-8'); ?>"
                                                            data-main-id="<?php echo $mainId; ?>">
                                                            Storico
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>

                                <div class="pagination-wrapper mt-3">
                                    <div class="text-muted small">
                                        <?php if ($totalRecords > 0): ?>
                                            Mostrate <?php echo $startRecord; ?>-<?php echo $endRecord; ?> di <?php echo $totalRecords; ?>
                                        <?php else: ?>
                                            Nessuna email da mostrare.
                                        <?php endif; ?>
                                    </div>
                                    <div class="d-flex align-items-center flex-wrap" style="gap: 0.75rem 1.5rem;">
                                        <?php if ($totalRecords > 0): ?>
                                            <nav aria-label="Paginazione utenti">
                                                <ul class="pagination pagination-sm mb-0">
                                                    <?php
                                                        $prevDisabled = $page <= 1;
                                                        $nextDisabled = $page >= $totalPages;
                                                    ?>
                                                    <li class="page-item<?php echo $prevDisabled ? ' disabled' : ''; ?>">
                                                        <?php if ($prevDisabled): ?>
                                                            <span class="page-link">Prec</span>
                                                        <?php else: ?>
                                                            <a class="page-link" href="<?php echo htmlspecialchars($buildUserPageUrl($page - 1), ENT_QUOTES, 'UTF-8'); ?>">Prec</a>
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
                                                            <a class="page-link" href="<?php echo htmlspecialchars($buildUserPageUrl(1), ENT_QUOTES, 'UTF-8'); ?>">1</a>
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
                                                                <a class="page-link" href="<?php echo htmlspecialchars($buildUserPageUrl($p), ENT_QUOTES, 'UTF-8'); ?>"><?php echo $p; ?></a>
                                                            <?php endif; ?>
                                                        </li>
                                                    <?php endfor; ?>
                                                    <?php if ($endPage < $totalPages): ?>
                                                        <?php if ($endPage < $totalPages - 1): ?>
                                                            <li class="page-item disabled"><span class="page-link">&hellip;</span></li>
                                                        <?php endif; ?>
                                                        <li class="page-item">
                                                            <a class="page-link" href="<?php echo htmlspecialchars($buildUserPageUrl($totalPages), ENT_QUOTES, 'UTF-8'); ?>"><?php echo $totalPages; ?></a>
                                                        </li>
                                                    <?php endif; ?>
                                                    <li class="page-item<?php echo $nextDisabled ? ' disabled' : ''; ?>">
                                                        <?php if ($nextDisabled): ?>
                                                            <span class="page-link">Succ</span>
                                                        <?php else: ?>
                                                            <a class="page-link" href="<?php echo htmlspecialchars($buildUserPageUrl($page + 1), ENT_QUOTES, 'UTF-8'); ?>">Succ</a>
                                                        <?php endif; ?>
                                                    </li>
                                                </ul>
                                            </nav>
                                        <?php endif; ?>
                                        <form method="get" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>" class="mt-2 mt-sm-0">
                                            <input type="hidden" name="pg" value="utenti">
                                            <input type="hidden" name="page" value="1">
                                            <?php if ($searchTerm !== ''): ?>
                                                <input type="hidden" name="search" value="<?php echo htmlspecialchars($searchTerm, ENT_QUOTES, 'UTF-8'); ?>">
                                            <?php endif; ?>
                                            <?php if ($filterStatus !== ''): ?>
                                                <input type="hidden" name="status" value="<?php echo htmlspecialchars($filterStatus, ENT_QUOTES, 'UTF-8'); ?>">
                                            <?php endif; ?>
                                            <?php if ($tipo !== ''): ?>
                                                <input type="hidden" name="tp" value="<?php echo htmlspecialchars($tipo, ENT_QUOTES, 'UTF-8'); ?>">
                                            <?php endif; ?>
                                            <?php if ($sortKey === 'partecipazioni'): ?>
                                                <input type="hidden" name="sort" value="partecipazioni">
                                                <input type="hidden" name="dir" value="<?php echo htmlspecialchars($sortDirParam, ENT_QUOTES, 'UTF-8'); ?>">
                                            <?php endif; ?>
                                            <select id="per_page" name="per_page" class="form-select form-select-sm" onchange="this.form.submit()">
                                                <?php foreach ($perPageOptions as $option): ?>
                                                    <option value="<?php echo (int)$option; ?>" <?php echo $option === $perPage ? 'selected' : ''; ?>><?php echo (int)$option; ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </form>
                                        <form method="get" action="index.php" class="mt-2 mt-sm-0">
                                            <input type="hidden" name="pg" value="utenti">
                                            <?php if ($searchTerm !== ''): ?>
                                                <input type="hidden" name="search" value="<?php echo htmlspecialchars($searchTerm, ENT_QUOTES, 'UTF-8'); ?>">
                                            <?php endif; ?>
                                            <?php if ($filterStatus !== ''): ?>
                                                <input type="hidden" name="status" value="<?php echo htmlspecialchars($filterStatus, ENT_QUOTES, 'UTF-8'); ?>">
                                            <?php endif; ?>
                                            <?php if ($tipo !== ''): ?>
                                                <input type="hidden" name="tp" value="<?php echo htmlspecialchars($tipo, ENT_QUOTES, 'UTF-8'); ?>">
                                            <?php endif; ?>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                    </div>
                </div>
            </div>

<?php include("_footer.php"); ?>




