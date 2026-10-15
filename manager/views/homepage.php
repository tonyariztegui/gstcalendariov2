<?php 

	include("_header.php"); 
	include("res/inc/utenze.php");
	include("res/inc/header.php");
    include("res/inc/sanitize.inc.php");
    require_once __DIR__ . '/../../app/logic/instantwin.php';

    $tipo = (isset($_GET['tp'])) ? $_GET['tp'] : '';

    $totale_partecipanti = $connection->query("SELECT COUNT(*) FROM utenti")->fetchColumn();
    $totale_partecipazioni = $connection->query("SELECT COUNT(*) FROM partecipazioni")->fetchColumn();
    $partecipazioni_oggi = $connection->query("SELECT COUNT(*) FROM partecipazioni WHERE DATE(DATA_PARTECIPAZIONE) = CURDATE()")->fetchColumn();
    
    $totale_premi_disponibili = $connection->query("SELECT SUM(QUANTITA_TOTALE) FROM premi WHERE IS_ACTIVE = 1")->fetchColumn();
    $premi_assegnati = $connection->query("SELECT SUM(QUANTITA_ASSEGNATI) FROM premi")->fetchColumn();
    $premi_rimanenti = $totale_premi_disponibili - $premi_assegnati;
    
    $totale_vincitori = $connection->query("SELECT COUNT(*) FROM partecipazioni WHERE HAS_WON = 1")->fetchColumn();
    $vincitori_oggi = $connection->query("SELECT COUNT(*) FROM partecipazioni WHERE HAS_WON = 1 AND DATE(WIN_DATE) = CURDATE()")->fetchColumn();
    $totale_vincitori_attivi = $connection->query("SELECT COUNT(*) FROM partecipazioni WHERE HAS_WON = 1 AND COALESCE(ANNULLATO, 0) = 0")->fetchColumn();
    $vincitori_confermati = $connection->query("SELECT COUNT(*) FROM partecipazioni WHERE HAS_WON = 1 AND COALESCE(CONFERMATO, 0) = 1 AND COALESCE(ANNULLATO, 0) = 0")->fetchColumn();
    $vincitori_non_confermati = $connection->query("SELECT COUNT(*) FROM partecipazioni WHERE HAS_WON = 1 AND COALESCE(CONFERMATO, 0) = 0 AND COALESCE(ANNULLATO, 0) = 0")->fetchColumn();
    $vincitori_annullati = $connection->query("SELECT COUNT(*) FROM partecipazioni WHERE COALESCE(ANNULLATO, 0) = 1")->fetchColumn();
    $fields_enabled = $contest_config['fields_enabled'] ?? [];
    $show_importo_scontrino = ($fields_enabled['importo'] ?? true) === true;
    $show_receipt_enabled = ($fields_enabled['scontrino'] ?? true) === true;
    $showPdvColumn = ($fields_enabled['prov_pdv'] ?? true) || ($fields_enabled['citta_pdv'] ?? true) || ($fields_enabled['indirizzo_pdv'] ?? true);
    $is_instantwin = isset($contest_mode) && strtolower((string)$contest_mode) === 'instantwin';
    $showWinnerCounter = $is_instantwin;
    $autoPrizeWithReceipt = (bool)($contest_config['instantwin_auto_prize_with_receipt'] ?? false);
    $autoPrizeFlow = $autoPrizeWithReceipt && $is_instantwin && $show_receipt_enabled;
    $managerConfirmFlow = $is_instantwin && !$autoPrizeFlow && instantwin_is_manager_confirm_flow_enabled();
    $managerCancelFlow = $is_instantwin && !$autoPrizeFlow && instantwin_is_manager_cancel_flow_enabled();
    
    $carte_assegnate = $connection->query("SELECT COUNT(*) FROM partecipazioni WHERE HAS_WON = 1 AND PRIZE_ID IS NOT NULL")->fetchColumn();
    $carte_disponibili = $connection->query("SELECT SUM(QUANTITA_TOTALE - QUANTITA_ASSEGNATI) FROM premi WHERE IS_ACTIVE = 1")->fetchColumn();

    // Statistiche per tipo di premio (raggruppate per Ce.Di.)
    require_once __DIR__ . '/../res/inc/premi_stats_helpers.inc.php';
    $premiCediColsHome = manager_premi_detect_cedi_columns($connection);
    $premiStatsBundleHome = manager_premi_fetch_stats_per_tipo($connection, $premiCediColsHome);
    $stats_per_tipo = $premiStatsBundleHome['rows'];
    $stats_per_tipo_by_cedi = $premiStatsBundleHome['by_cedi'];

    // Statistiques par PDV
    $pdv_stats = [];
    $pdv_query = $connection->query("
        SELECT 
            p.ID_PDV_CONCORSO,
            COALESCE(mpdv.codice, p.ID_PDV_CONCORSO) AS ID_PDV_TODIS,
            COALESCE(mpdv.insegna, p.INSEGNA) AS INSEGNA,
            COUNT(p.ID) AS partecipazioni,
            COUNT(CASE WHEN p.HAS_WON = 1 THEN 1 END) AS vincitori,
            SUM(p.IMPORTO_SCONTRINO) AS totale_importi
        FROM partecipazioni p
        LEFT JOIN punti_vendita mpdv ON mpdv.codice = p.ID_PDV_CONCORSO
        WHERE p.ID_PDV_CONCORSO IS NOT NULL AND p.ID_PDV_CONCORSO != ''
        GROUP BY p.ID_PDV_CONCORSO, mpdv.codice, COALESCE(mpdv.insegna, p.INSEGNA)
        ORDER BY partecipazioni DESC
        LIMIT 20
    ");
    if ($pdv_query !== false) {
        $pdv_stats = $pdv_query->fetchAll(PDO::FETCH_ASSOC);
    }

    // ============================
    // Andamento premi (settimane / giorni)
    // ============================
    $contestStartObj = new DateTimeImmutable($contest_config['start_date'] . ' 00:00:00');
    $contestEndObj = new DateTimeImmutable($contest_config['end_date'] . ' 23:59:59');

    $wins_stmt = $connection->prepare("
        SELECT WIN_DATE, PRIZE_AMOUNT, PRIZE_ID
        FROM partecipazioni
        WHERE HAS_WON = 1
          AND COALESCE(ANNULLATO, 0) = 0
          AND WIN_DATE IS NOT NULL
          AND WIN_DATE BETWEEN :start AND :end
        ORDER BY WIN_DATE ASC
    ");
    $wins_stmt->execute([
        ':start' => $contestStartObj->format('Y-m-d H:i:s'),
        ':end' => $contestEndObj->format('Y-m-d H:i:s')
    ]);
    $wins_data = $wins_stmt->fetchAll(PDO::FETCH_ASSOC);
    $distinct_prizes = [];

    // Partecipazioni aggregate (per colonne "Partecipazioni")
    $part_stmt = $connection->prepare("
        SELECT DATA_PARTECIPAZIONE
        FROM partecipazioni
        WHERE DATA_PARTECIPAZIONE BETWEEN :start AND :end
    ");
    $part_stmt->execute([
        ':start' => $contestStartObj->format('Y-m-d 00:00:00'),
        ':end' => $contestEndObj->format('Y-m-d 23:59:59')
    ]);
    $partecipazioni_data = $part_stmt->fetchAll(PDO::FETCH_ASSOC);

    $weeklyTotals = [];
    $weeklyPartecipazioni = [];
    $weekdayLabels = [
        1 => 'Lunedi',
        2 => 'Martedi',
        3 => 'Mercoledi',
        4 => 'Giovedi',
        5 => 'Venerdi',
        6 => 'Sabato',
        7 => 'Domenica'
    ];
    $weekdayAggregates = [];
    foreach ($weekdayLabels as $dow => $label) {
        $weekdayAggregates[$dow] = [
            'label' => $label,
            'wins' => 0,
            'amount' => 0.0,
            'partecipazioni' => 0
        ];
    }

    foreach ($wins_data as $row) {
        if (empty($row['WIN_DATE'])) {
            continue;
        }

        try {
            $winDate = new DateTimeImmutable($row['WIN_DATE']);
        } catch (Exception $e) {
            continue;
        }

        if ($winDate < $contestStartObj || $winDate > $contestEndObj) {
            continue;
        }

        $amount = isset($row['PRIZE_AMOUNT']) ? (float) $row['PRIZE_AMOUNT'] : 0.0;

        $weekStart = $winDate->modify('monday this week');
        $weekKey = $weekStart->format('Y-m-d');

        if (!isset($weeklyTotals[$weekKey])) {
            $weekEnd = $weekStart->modify('sunday this week');
            $weeklyTotals[$weekKey] = [
                'week_start' => $weekStart,
                'week_end' => $weekEnd,
                'wins' => 0,
                'amount' => 0.0
            ];
        }

        $weeklyTotals[$weekKey]['wins'] += 1;
        $weeklyTotals[$weekKey]['amount'] += $amount;

        // Traccia premi distinti per decidere se mostrare importo medio
        $prizeKey = null;
        if (!empty($row['PRIZE_ID'])) {
            $prizeKey = 'id_' . $row['PRIZE_ID'];
        } elseif ($row['PRIZE_AMOUNT'] !== null && $row['PRIZE_AMOUNT'] !== '') {
            $prizeKey = 'amt_' . (string)$row['PRIZE_AMOUNT'];
        }
        if ($prizeKey !== null) {
            $distinct_prizes[$prizeKey] = true;
        }

        $dow = (int) $winDate->format('N');
        if (isset($weekdayAggregates[$dow])) {
            $weekdayAggregates[$dow]['wins'] += 1;
            $weekdayAggregates[$dow]['amount'] += $amount;
        }
    }

    // Conta partecipazioni per settimana/giorno
    foreach ($partecipazioni_data as $prow) {
        if (empty($prow['DATA_PARTECIPAZIONE'])) {
            continue;
        }
        try {
            $pDate = new DateTimeImmutable($prow['DATA_PARTECIPAZIONE']);
        } catch (Exception $e) {
            continue;
        }
        if ($pDate < $contestStartObj || $pDate > $contestEndObj) {
            continue;
        }
        $pWeekStart = $pDate->modify('monday this week');
        $pWeekKey = $pWeekStart->format('Y-m-d');
        if (!isset($weeklyPartecipazioni[$pWeekKey])) {
            $weeklyPartecipazioni[$pWeekKey] = 0;
        }
        $weeklyPartecipazioni[$pWeekKey] += 1;

        $pdow = (int)$pDate->format('N');
        if (isset($weekdayAggregates[$pdow])) {
            $weekdayAggregates[$pdow]['partecipazioni'] += 1;
        }
    }

    $weekly_series = [];
    $firstWeekStart = $contestStartObj->modify('monday this week');
    $weekCursor = $firstWeekStart;

    while ($weekCursor <= $contestEndObj) {
        $weekKey = $weekCursor->format('Y-m-d');
        $weekEnd = $weekCursor->modify('sunday this week');

        $displayStart = $weekCursor < $contestStartObj ? $contestStartObj : $weekCursor;
        $displayEnd = $weekEnd > $contestEndObj ? $contestEndObj : $weekEnd;

        $wins = isset($weeklyTotals[$weekKey]) ? (int) $weeklyTotals[$weekKey]['wins'] : 0;
        $amount = isset($weeklyTotals[$weekKey]) ? (float) $weeklyTotals[$weekKey]['amount'] : 0.0;
        $average = $wins > 0 ? $amount / $wins : 0.0;
        $partecipazioniSettimana = isset($weeklyPartecipazioni[$weekKey]) ? (int)$weeklyPartecipazioni[$weekKey] : 0;

        $weekly_series[] = [
            'label' => 'W' . $weekCursor->format('W') . ' ' . $weekCursor->format('o'),
            'period' => $displayStart->format('d/m/Y') . ' - ' . $displayEnd->format('d/m/Y'),
            'week_key' => $weekKey, // Aggiungere week_key per facilitare la corrispondenza
            'wins' => $wins,
            'partecipazioni' => $partecipazioniSettimana,
            'amount' => $amount,
            'average' => $average
        ];

        $weekCursor = $weekCursor->modify('+1 week');
    }

    $show_average_column = $is_instantwin && count($distinct_prizes) > 1;

    // Generare dati per giorno della settimana con filtro per settimana
    // Raggruppare per settimana e giorno della settimana
    $weekdayByWeek = [];
    foreach ($wins_data as $row) {
        if (empty($row['WIN_DATE'])) {
            continue;
        }
        try {
            $winDate = new DateTimeImmutable($row['WIN_DATE']);
        } catch (Exception $e) {
            continue;
        }
        if ($winDate < $contestStartObj || $winDate > $contestEndObj) {
            continue;
        }
        $weekStart = $winDate->modify('monday this week');
        $weekKey = $weekStart->format('Y-m-d');
        $dow = (int) $winDate->format('N');
        $amount = isset($row['PRIZE_AMOUNT']) ? (float) $row['PRIZE_AMOUNT'] : 0.0;
        
        if (!isset($weekdayByWeek[$weekKey])) {
            $weekdayByWeek[$weekKey] = [];
            foreach ($weekdayLabels as $dayNum => $dayLabel) {
                $weekdayByWeek[$weekKey][$dayNum] = [
                    'label' => $dayLabel,
                    'wins' => 0,
                    'amount' => 0.0,
                    'partecipazioni' => 0
                ];
            }
        }
        $weekdayByWeek[$weekKey][$dow]['wins'] += 1;
        $weekdayByWeek[$weekKey][$dow]['amount'] += $amount;
    }
    
    // Aggiungere partecipazioni per settimana e giorno
    foreach ($partecipazioni_data as $prow) {
        if (empty($prow['DATA_PARTECIPAZIONE'])) {
            continue;
        }
        try {
            $pDate = new DateTimeImmutable($prow['DATA_PARTECIPAZIONE']);
        } catch (Exception $e) {
            continue;
        }
        if ($pDate < $contestStartObj || $pDate > $contestEndObj) {
            continue;
        }
        $pWeekStart = $pDate->modify('monday this week');
        $pWeekKey = $pWeekStart->format('Y-m-d');
        $pdow = (int)$pDate->format('N');
        
        if (!isset($weekdayByWeek[$pWeekKey])) {
            $weekdayByWeek[$pWeekKey] = [];
            foreach ($weekdayLabels as $dayNum => $dayLabel) {
                $weekdayByWeek[$pWeekKey][$dayNum] = [
                    'label' => $dayLabel,
                    'wins' => 0,
                    'amount' => 0.0,
                    'partecipazioni' => 0
                ];
            }
        }
        $weekdayByWeek[$pWeekKey][$pdow]['partecipazioni'] += 1;
    }
    
    // Preparare le settimane disponibili per il filtro (tutte le settimane)
    $available_weeks = [];
    foreach ($weekly_series as $week) {
        $available_weeks[] = [
            'key' => $week['period'],
            'label' => $week['label'] . ' (' . $week['period'] . ')',
            'week_key' => $week['week_key']
        ];
    }
    
    // Settimana selezionata (default: ultima settimana)
    $selected_week = $_GET['week_filter'] ?? '';
    if ($selected_week === '' && !empty($available_weeks)) {
        $selected_week = $available_weeks[count($available_weeks) - 1]['key'];
    }
    
    // Generare weekday_series per la settimana selezionata
    $weekday_series = [];
    if ($selected_week !== '' && !empty($weekdayByWeek)) {
        // Trovare la chiave della settimana corrispondente
        $selected_week_key = null;
        foreach ($available_weeks as $week_option) {
            if ($week_option['key'] === $selected_week) {
                $selected_week_key = $week_option['week_key'];
                break;
            }
        }
        
        // Se non trovato, calcolare dalla data di inizio periodo
        if (!$selected_week_key) {
            $periodStartStr = explode(' - ', $selected_week)[0] ?? '';
            if ($periodStartStr !== '') {
                $periodStart = DateTimeImmutable::createFromFormat('d/m/Y', $periodStartStr);
                if ($periodStart) {
                    $selected_week_key = $periodStart->modify('monday this week')->format('Y-m-d');
                }
            }
        }
        
        if ($selected_week_key && isset($weekdayByWeek[$selected_week_key])) {
            ksort($weekdayByWeek[$selected_week_key]);
            foreach ($weekdayByWeek[$selected_week_key] as $dow => $data) {
                $average = $data['wins'] > 0 ? $data['amount'] / $data['wins'] : 0.0;
                $weekday_series[] = [
                    'label' => $data['label'],
                    'wins' => $data['wins'],
                    'partecipazioni' => $data['partecipazioni'],
                    'amount' => $data['amount'],
                    'average' => $average
                ];
            }
        } else {
            // Se non ci sono dati, mostrare tutti zeri
            foreach ($weekdayLabels as $dow => $label) {
                $weekday_series[] = [
                    'label' => $label,
                    'wins' => 0,
                    'partecipazioni' => 0,
                    'amount' => 0.0,
                    'average' => 0.0
                ];
            }
        }
    } else {
        // Fallback: usare dati aggregati
        foreach ($weekdayAggregates as $dow => $data) {
            $average = $data['wins'] > 0 ? $data['amount'] / $data['wins'] : 0.0;
            $weekday_series[] = [
                'label' => $data['label'],
                'wins' => $data['wins'],
                'partecipazioni' => $data['partecipazioni'],
                'amount' => $data['amount'],
                'average' => $average
            ];
        }
    }

    $_usr_id = $_SESSION['user_id'] ?? '';
    $leads = $totale_partecipazioni;
    $data_inizio_concorso = $contest_config['start_date'];
    $data_fine_concorso = $contest_config['end_date'];
    $data_oggi = date('Y-m-d');

    $inizioObj = DateTimeImmutable::createFromFormat('Y-m-d', $data_inizio_concorso) ?: new DateTimeImmutable($data_inizio_concorso);
    $fineObj = DateTimeImmutable::createFromFormat('Y-m-d', $data_fine_concorso) ?: new DateTimeImmutable($data_fine_concorso);
    $oggiObj = DateTimeImmutable::createFromFormat('Y-m-d', $data_oggi) ?: new DateTimeImmutable($data_oggi);

    if ($inizioObj > $fineObj) {
        $giorni_totali = 0;
    } else {
        $giorni_totali = $inizioObj->diff($fineObj)->days + 1;
    }

    if ($giorni_totali > 0 && $oggiObj >= $inizioObj) {
        $calcEnd = $oggiObj > $fineObj ? $fineObj : $oggiObj;
        $giorni_trascorsi = $inizioObj->diff($calcEnd)->days + 1;
        $giorni_trascorsi = min($giorni_trascorsi, $giorni_totali);
    } else {
        $giorni_trascorsi = 0;
    }

    $giorni_rimanenti = max(0, $giorni_totali - $giorni_trascorsi);
    $percentuale_progresso = $giorni_totali > 0 ? min(100, max(0, ($giorni_trascorsi / $giorni_totali) * 100)) : 0;
    
    // Statut du concours
    $concorso_status = '';
    if ($data_oggi < $data_inizio_concorso) {
        $concorso_status = 'non_iniziato';
    } elseif ($data_oggi > $data_fine_concorso) {
        $concorso_status = 'terminato';
    } else {
        $concorso_status = 'attivo';
    }

    $_title = 'Dashboard Concorso ' . $contest_config['name'];

?>

	<style>
		.rct {color:#ccc}
        .tbl td {vertical-align:top !important}
        .dataTables_info {display:none}
        .toolbar-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
            margin-bottom: 20px;
            margin-top: 10px;
        }
        .toolbar-left, .toolbar-center, .toolbar-right {
            display: flex;
            align-items: center;
        }
        .toolbar-center {
            justify-content: center;
        }
        .toolbar-left {
            justify-content: flex-start;
        }
        .toolbar-right {
            justify-content: flex-end;
        }
        .dataTables_filter {
            display: none !important;
        }
        /* Animazioni fluide per il cambio settimana */
        #weekday_table_body {
            transition: opacity 0.4s ease-in-out, transform 0.4s ease-in-out;
        }
        #weekday_table_body tr {
            transition: opacity 0.3s ease-in-out, transform 0.3s ease-in-out;
        }
        #weekday_table_body tr.fade-in-row {
            animation: fadeInRow 0.5s ease-out forwards;
        }
        @keyframes fadeInRow {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .contest-info-card {
            background: #f7f9fc;
            color: #0f172a;
            border-radius: 14px;
            padding: 18px;
            margin-bottom: 20px;
            border: 1px solid #e5e8ef;
            box-shadow: 0 8px 20px rgba(15,23,42,0.08);
        }
        .progress-container {
            background: #fff;
            border-radius: 10px;
            padding: 15px;
            margin-top: 15px;
            border: 1px solid #e6e9f0;
        }
        .progress-bar-custom {
            height: 16px;
            background: #edf1f7;
            border-radius: 8px;
            overflow: hidden;
            position: relative;
        }
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #4c6ef5, #60a5fa);
            border-radius: 8px;
            transition: width 0.5s ease;
            position: relative;
        }
        .progress-text {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: #0f172a;
            font-weight: 700;
            font-size: 12px;
        }
        .contest-dates {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 8px;
            font-size: 14px;
        }
        .status-badge {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            background: #e8f0ff;
            color: #1f55ff;
            border: 1px solid #d5e1ff;
        }
        .status-attivo { background: #e8f7ef; color:#0f9d58; border-color:#c7e9d4; }
        .status-terminato { background: #ffe9e9; color:#c0392b; border-color:#ffcdcd; }
        .status-non_iniziato { background: #fff4e5; color:#b35400; border-color:#ffe3c4; }
        .dashboard-grid>.row+.row{margin-top:1.5rem}
        /* Stats cards (riuso layout partecipazioni) */
        .stats-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 48px;
            justify-content: space-evenly;
            align-items: center;
        }
        .stats-card {
            background: #f7f9fc;
            border: 1px solid #e5e8ef;
            border-radius: 12px;
            padding: 18px;
            display: flex;
            align-items: center;
            justify-content: flex-start;
            gap: 14px;
            box-shadow: 0 4px 12px rgba(15,23,42,0.06);
            text-align: left;
            width: 360px;
            max-width: 420px;
            min-width: 300px;
        }
        .stats-icon {
            width: 50px;
            height: 50px;
            border-radius: 14px;
            display: grid;
            place-items: center;
            background: #0d6efd;
            color: #fff;
            font-size: 20px;
            flex-shrink: 0;
        }
        .stats-meta { line-height: 1.3; }
        .stats-label { color: #6b7280; font-size: 13px; letter-spacing: 0.01em; }
        .stats-value { font-weight: 700; font-size: 24px; color: #111827; margin-top: 6px; }
	</style>

    <div class="app-main__outer" id="custom_filter" data-val="<?php echo $_usr_id; ?>" data-tp="<?php echo $tipo; ?>">
        <div class="app-main__inner">
            <div class="tabs-animation">

                <!-- Informazioni Concorso -->
                <div class="contest-info-card">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h4 class="mb-2">
                                <i class="fa fa-calendar-alt mr-2"></i>
                                Concorso <?php echo $contest_config['name']; ?> <?php echo date('Y', strtotime($contest_config['start_date'])); ?>
                            </h4>
                            <div class="contest-dates">
                                <div>
                                    <i class="fa fa-play-circle mr-1"></i>
                                    <strong>Inizio:</strong> <?php echo date('d/m/Y', strtotime($data_inizio_concorso)); ?>
                                </div>
                                <div>
                                    <i class="fa fa-flag-checkered mr-1"></i>
                                    <strong>Fine:</strong> <?php echo date('d/m/Y', strtotime($data_fine_concorso)); ?>
                                </div>
                                <div>
                                    <span class="status-badge status-<?php echo $concorso_status; ?>">
                                        <?php 
                                        switch($concorso_status) {
                                            case 'attivo': echo 'Attivo'; break;
                                            case 'terminato': echo 'Terminato'; break;
                                            case 'non_iniziato': echo 'Non Iniziato'; break;
                                        }
                                        ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 text-right">
                            <div class="h3 mb-0"><?php echo round($percentuale_progresso, 1); ?>%</div>
                            <small>Completato</small>
                        </div>
                    </div>
                    
                    <div class="progress-container">
                        <div class="d-flex justify-content-between mb-2">
                            <small><strong>Giorni trascorsi:</strong> <?php echo $giorni_trascorsi; ?></small>
                            <small><strong>Giorni rimanenti:</strong> <?php echo $giorni_rimanenti; ?></small>
                        </div>
                        <div class="progress-bar-custom">
                            <div class="progress-fill" style="width: <?php echo $percentuale_progresso; ?>%">
                                <div class="progress-text"><?php echo round($percentuale_progresso, 1); ?>%</div>
                            </div>
                        </div>
                        <div class="text-center mt-2">
                            <small>
                                <i class="fa fa-info-circle mr-1"></i>
                                Durata totale: <?php echo $giorni_totali; ?> giorni
                            </small>
                        </div>
                    </div>
                </div>

                <!-- Dashboard Partecipazioni -->
                <div class="mb-3 card">
                    <div class="card-header-tab card-header d-flex align-items-center justify-content-between">
                        <div class="card-header-title font-size-lg text-capitalize font-weight-normal">
                            <i class="header-icon lnr-chart-bars icon-gradient bg-amy-crisp"> </i>
                            Dashboard Partecipazioni
                        </div>
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

                <!-- Statistiche per Tipo di Premio -->
                <?php
                $premiStatsCardInnerOnly = false;
                include __DIR__ . '/inc/premi_stats_tipo_cedi_card.php';
                ?>

                <!-- Top Punti Vendita -->
                <?php if ($showPdvColumn): ?>
                <div class="card mb-3">
                    <div class="card-header-tab card-header">
                        <div class="card-header-title font-size-lg text-capitalize font-weight-normal">
                            <i class="header-icon lnr-store icon-gradient bg-happy-green"> </i>
                            Top 20 Punti Vendita per Partecipazioni
                        </div>
                        <div class="btn-actions-pane-right text-capitalize">
                            <?php if ($_usr_id == '') { ?>
                                <a href="res/inc/export_pdv.php" class="btn btn-info" style="margin:0 10px">Esporta PDV (CSV)</a>
                            <?php } ?>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <?php
                                $pdv_columns = 4;
                                if ($is_instantwin) {
                                    $pdv_columns++;
                                }
                                if ($show_importo_scontrino) {
                                    $pdv_columns += 2;
                                }
                            ?>
                            <table class="table table-hover table-striped">
                                <thead>
                                    <tr>
                                        <th>Posizione</th>
                                        <th>ID PDV</th>
                                        <th>Insegna</th>
                                        <th>Partecipazioni</th>
                                        <?php if ($is_instantwin): ?>
                                            <th>Vincitori</th>
                                        <?php endif; ?>
                                        <?php if ($show_importo_scontrino): ?>
                                            <th>Totale Importi</th>
                                            <th>Importo Medio</th>
                                        <?php endif; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($pdv_stats)): ?>
                                        <tr><td colspan="<?php echo $pdv_columns; ?>" class="text-center text-muted">Nessun dato disponibile.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($pdv_stats as $index => $pdv): ?>
                                            <tr>
                                                <td><strong><?php echo $index + 1; ?></strong></td>
                                                <td><?php echo htmlspecialchars($pdv['ID_PDV_TODIS'] ?? 'N/A'); ?></td>
                                                <td><?php echo htmlspecialchars($pdv['INSEGNA'] ?? 'N/A'); ?></td>
                                                <td><span><?php echo $pdv['partecipazioni']; ?></span></td>
                                                <?php if ($is_instantwin): ?>
                                                    <td><span><?php echo $pdv['vincitori']; ?></span></td>
                                                <?php endif; ?>
                                                <?php if ($show_importo_scontrino): ?>
                                                    <td><?php echo number_format(isset($pdv['totale_importi']) ? (float) $pdv['totale_importi'] : 0.0, 2); ?>&euro;</td>
                                                    <td><?php echo number_format(($pdv['partecipazioni'] > 0 && isset($pdv['totale_importi'])) ? ((float) $pdv['totale_importi'] / max($pdv['partecipazioni'], 1)) : 0, 2); ?>&euro;</td>
                                                <?php endif; ?>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php endif; ?>


                <!-- Andamento Premi per Settimana -->
                <div class="card mb-3">
                    <div class="card-header-tab card-header">
                        <div class="card-header-title font-size-lg text-capitalize font-weight-normal">
                            <i class="header-icon lnr-clock icon-gradient bg-mean-fruit"> </i>
                            <?php echo $is_instantwin ? 'Andamento Premi per Settimana' : 'Andamento Partecipazioni per Settimana'; ?>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <?php
                                if ($is_instantwin) {
                                    $weekly_columns = $show_average_column ? 5 : 4;
                                } else {
                                    $weekly_columns = 3;
                                }
                            ?>
                            <table class="table table-hover table-striped">
                                <thead>
                                    <tr>
                                        <th>Settimana</th>
                                        <th>Periodo</th>
                                        <th>Partecipazioni</th>
                                        <?php if ($is_instantwin): ?>
                                            <th>Totale Vincitori</th>
                                            <?php if ($show_average_column): ?>
                                                <th>Importo Medio</th>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($weekly_series)): ?>
                                        <tr><td colspan="<?php echo $weekly_columns; ?>" class="text-center text-muted">Nessun dato disponibile.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($weekly_series as $week): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($week['label']); ?></td>
                                                <td><?php echo htmlspecialchars($week['period']); ?></td>
                                                <td><span><?php echo $week['partecipazioni']; ?></span></td>
                                                <?php if ($is_instantwin): ?>
                                                    <td><span><?php echo $week['wins']; ?></span></td>
                                                    <?php if ($show_average_column): ?>
                                                        <td><?php echo number_format($week['average'], 2); ?>&euro;</td>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Distribuzione Premi per Giorno della Settimana -->
                <div class="card mb-3">
                    <div class="card-header-tab card-header">
                        <div class="card-header-title font-size-lg text-capitalize font-weight-normal d-flex align-items-center justify-content-between w-100">
                            <div class="d-flex align-items-center">
                                <i class="header-icon lnr-calendar-full icon-gradient bg-happy-green"> </i>
                                <span><?php echo $is_instantwin ? 'Premi per Giorno della Settimana' : 'Partecipazioni per Giorno della Settimana'; ?></span>
                            </div>
                            <?php if (!empty($available_weeks)): ?>
                                <select id="week_filter_select" class="form-control form-control-sm ml-3" style="width: auto; min-width: 250px;">
                                    <?php foreach ($available_weeks as $week_option): ?>
                                        <option value="<?php echo htmlspecialchars($week_option['key'], ENT_QUOTES, 'UTF-8'); ?>" <?php echo $selected_week === $week_option['key'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($week_option['label'], ENT_QUOTES, 'UTF-8'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <?php
                                if ($is_instantwin) {
                                    $weekday_columns = $show_average_column ? 4 : 3;
                                } else {
                                    $weekday_columns = 2;
                                }
                            ?>
                            <table class="table table-hover table-striped">
                                <thead>
                                    <tr>
                                        <th>Giorno</th>
                                        <th>Partecipazioni</th>
                                        <?php if ($is_instantwin): ?>
                                            <th>Totale Vincitori</th>
                                            <?php if ($show_average_column): ?>
                                                <th>Importo Medio</th>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </tr>
                                </thead>
                                <tbody id="weekday_table_body">
                                    <?php if (empty($weekday_series)): ?>
                                        <tr><td colspan="<?php echo $weekday_columns; ?>" class="text-center text-muted">Nessun dato disponibile.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($weekday_series as $weekday): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($weekday['label']); ?></td>
                                                <td><span><?php echo $weekday['partecipazioni']; ?></span></td>
                                                <?php if ($is_instantwin): ?>
                                                    <td><span><?php echo $weekday['wins']; ?></span></td>
                                                    <?php if ($show_average_column): ?>
                                                        <td><?php echo number_format($weekday['average'], 2); ?>&euro;</td>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

<!-- Modals -->
<div class="modal fade" id="record" tabindex="-1" role="dialog" aria-labelledby="newUser" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Dati Registrazione</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Chiudi" id="close_modal">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <form name="new_content" method="post">
            <div class="row">
                <div class="col-12">
                    <label for="nome">Nome</label>
                    <input type="text" id="nome" name="nome" class="form-control form-req" value="" placeholder="">
                    <br>
                </div>
                <div class="col-12">
                    <label for="cognome">Cognome</label>
                    <input type="text" id="cognome" name="cognome" class="form-control form-req" value="" placeholder="">
                    <br>
                </div>
                <div class="col-12">
                    <label for="email">Email</label>
                    <input type="text" id="email" name="email" class="form-control form-req" value="" placeholder="">
                    <br>
                </div>
                <div class="col-12">
                    <label for="tel">Telefono</label>
                    <input type="text" id="tel" name="tel" class="form-control form-req" value="" placeholder="">
                    <br>
                </div>
                <div class="col-12">
                    <label for="indirizzo">Indirizzo</label>
                    <input type="text" id="indirizzo" name="indirizzo" class="form-control form-req" value="" placeholder="">
                    <br>
                </div>
                <div class="col-12">
                    <label for="citta">Città</label>
                    <input type="text" id="citta" name="citta" class="form-control form-req" value="" placeholder="">
                    <br>
                </div>
                <div class="col-12">
                    <label for="cap">CAP</label>
                    <input type="text" id="cap" name="cap" class="form-control form-req" value="" placeholder="">
                    <br>
                </div>
            </div>
            <div class="row">
                <div class="col-12 text-right">
                    <button type="button" class="btn btn-warning" style="font-size:16px" id="saveContent" data-id="">Salva</button>
                    <br>
                </div>
            </div>
            <input type="hidden" name="save" value="1">
            <input type="hidden" name="user_id" id="_user_id" value="0">
        </form>
      </div>
    </div>
  </div>
</div>

<?php include("_footer.php"); ?>
<script>
$(document).ready(function() {
    $('#refreshTable').on('click', function() {
        if (typeof table !== 'undefined') {
            table.ajax.reload();
        }
    });
    $('#customSearch').on('keyup', function() {
        if (typeof table !== 'undefined') {
            table.search(this.value).draw();
        }
    });
    
    // Gestione filtro settimana per "Premi per Giorno della Settimana" (aggiornamento dinamico con animazione fluida)
    $('#week_filter_select').on('change', function() {
        var selectedWeek = $(this).val();
        var $select = $(this);
        var $tbody = $('#weekday_table_body');
        
        if (!selectedWeek) {
            return;
        }
        
        // Disabilitare il select durante il caricamento
        $select.prop('disabled', true);
        
        // Animazione fade out più fluida
        $tbody.css('opacity', '0.5');
        $tbody.css('transform', 'translateY(-5px)');
        
        setTimeout(function() {
            // Mostrare un indicatore di caricamento
            $tbody.html('<tr><td colspan="100%" class="text-center" style="padding: 20px;"><i class="fa fa-spinner fa-spin" style="font-size: 18px;"></i> <span class="ml-2">Caricamento...</span></td></tr>');
            $tbody.css('opacity', '1');
            $tbody.css('transform', 'translateY(0)');
            
            // Richiesta AJAX per ottenere i dati filtrati
            $.ajax({
                url: 'res/inc/get_weekday_data.php',
                method: 'GET',
                data: {
                    week_filter: selectedWeek
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success && response.data) {
                        var html = '';
                        var weekday_columns = response.is_instantwin ? (response.show_average_column ? 4 : 3) : 2;
                        
                        if (response.data.length === 0) {
                            html = '<tr><td colspan="' + weekday_columns + '" class="text-center text-muted">Nessun dato disponibile.</td></tr>';
                        } else {
                            response.data.forEach(function(weekday, index) {
                                html += '<tr class="fade-in-row" style="opacity: 0;">';
                                html += '<td>' + $('<div>').text(weekday.label).html() + '</td>';
                                html += '<td><span>' + weekday.partecipazioni + '</span></td>';
                                if (response.is_instantwin) {
                                    html += '<td><span>' + weekday.wins + '</span></td>';
                                    if (response.show_average_column) {
                                        var avgFormatted = parseFloat(weekday.average).toFixed(2).replace('.', ',');
                                        html += '<td>' + avgFormatted + '&euro;</td>';
                                    }
                                }
                                html += '</tr>';
                            });
                        }
                        
                        // Aggiornare il contenuto
                        $tbody.html(html);
                        
                        // Animazione sequenziale delle righe per un effetto più fluido
                        $tbody.find('tr').each(function(index) {
                            var $row = $(this);
                            setTimeout(function() {
                                $row.css('opacity', '1');
                            }, index * 50); // Delay progressivo de 50ms per ogni riga
                        });
                    } else {
                        $tbody.html('<tr><td colspan="100%" class="text-center text-danger">Errore nel caricamento dei dati.</td></tr>');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Errore AJAX:', error);
                    $tbody.html('<tr><td colspan="100%" class="text-center text-danger">Errore di connessione.</td></tr>');
                },
                complete: function() {
                    // Riabilitare il select
                    $select.prop('disabled', false);
                }
            });
        }, 150); // Piccolo delay per l'animazione fade out
    });
    
});
</script>
