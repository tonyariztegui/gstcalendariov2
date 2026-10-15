<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', '0');
ob_start();

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../../app/inc/functions.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Sessione scaduta. Effettua nuovamente il login.'
    ]);
    exit;
}

$selected_week = isset($_GET['week_filter']) ? trim((string)$_GET['week_filter']) : '';

if ($selected_week === '') {
    echo json_encode([
        'success' => false,
        'message' => 'Settimana non specificata.'
    ]);
    exit;
}

try {
    $connection = conn_pdo();
    
    // Recuperare la configurazione del concorso
    if (!isset($contest_config)) {
        require_once __DIR__ . '/../../../app/inc/config.php';
    }
    
    // Verificare se è Instant Win
    $is_instantwin = isset($contest_mode) && strtolower((string)$contest_mode) === 'instantwin';
    
    $contestStartObj = new DateTimeImmutable($contest_config['start_date'] . ' 00:00:00');
    $contestEndObj = new DateTimeImmutable($contest_config['end_date'] . ' 23:59:59');
    
    $weekdayLabels = [
        1 => 'Lunedi',
        2 => 'Martedi',
        3 => 'Mercoledi',
        4 => 'Giovedi',
        5 => 'Venerdi',
        6 => 'Sabato',
        7 => 'Domenica'
    ];
    
    // Recuperare i dati delle vincite
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
    
    // Recuperare i dati delle partecipazioni
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
    
    // Generare le settimane disponibili con conteggi
    $weeklyTotals = [];
    $weeklyPartecipazioni = [];
    
    // Contare vincite per settimana
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
        
        if (!isset($weeklyTotals[$weekKey])) {
            $weeklyTotals[$weekKey] = ['wins' => 0];
        }
        $weeklyTotals[$weekKey]['wins'] += 1;
    }
    
    // Contare partecipazioni per settimana
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
    }
    
    // Generare le settimane disponibili (stessa logica di homepage.php)
    $weekly_series = [];
    $firstWeekStart = $contestStartObj->modify('monday this week');
    $weekCursor = $firstWeekStart;
    
    while ($weekCursor <= $contestEndObj) {
        $weekKey = $weekCursor->format('Y-m-d');
        $weekEnd = $weekCursor->modify('sunday this week');
        
        $displayStart = $weekCursor < $contestStartObj ? $contestStartObj : $weekCursor;
        $displayEnd = $weekEnd > $contestEndObj ? $contestEndObj : $weekEnd;
        
        $wins = isset($weeklyTotals[$weekKey]) ? (int) $weeklyTotals[$weekKey]['wins'] : 0;
        $partecipazioniSettimana = isset($weeklyPartecipazioni[$weekKey]) ? (int)$weeklyPartecipazioni[$weekKey] : 0;
        
        // Generare il periodo (stessa logica di homepage.php)
        $period = $displayStart->format('d/m/Y') . ' - ' . $displayEnd->format('d/m/Y');
        
        // IMPORTANTE: Generare tutte le settimane per garantire la corrispondenza del periodo
        // Anche se non hanno dati, devono essere presenti per la ricerca del periodo
        $weekly_series[] = [
            'label' => 'W' . $weekCursor->format('W') . ' ' . $weekCursor->format('o'),
            'period' => $period,
            'week_key' => $weekKey, // Chiave per la corrispondenza con weekdayByWeek
            'wins' => $wins,
            'partecipazioni' => $partecipazioniSettimana
        ];
        
        $weekCursor = $weekCursor->modify('+1 week');
    }
    
    // Generare weekday_series per la settimana selezionata
    // Stessa logica di homepage.php
    $weekday_series = [];
    
    if ($selected_week !== '' && !empty($weekdayByWeek)) {
        // Trovare la chiave della settimana corrispondente
        $selected_week_key = null;
        
        // Cercare nella lista delle settimane disponibili (con dati)
        foreach ($weekly_series as $week) {
            if ($week['period'] === $selected_week && isset($week['week_key'])) {
                $selected_week_key = $week['week_key'];
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
            // Ordinare per giorno della settimana (1=Lunedì, 7=Domenica)
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
            // Se non ci sono dati per la settimana selezionata, mostrare tutti zeri
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
    
    // Verificare se mostrare la colonna importo medio (is_instantwin già definito sopra)
    $distinct_prizes = [];
    foreach ($wins_data as $row) {
        $prizeKey = null;
        if (!empty($row['PRIZE_ID'])) {
            $prizeKey = 'id_' . $row['PRIZE_ID'];
        } elseif ($row['PRIZE_AMOUNT'] !== null && $row['PRIZE_AMOUNT'] !== '') {
            $prizeKey = 'amt_' . (string)$row['PRIZE_AMOUNT'];
        }
        if ($prizeKey !== null) {
            $distinct_prizes[$prizeKey] = true;
        }
    }
    $show_average_column = $is_instantwin && count($distinct_prizes) > 1;
    
    ob_end_clean();
    echo json_encode([
        'success' => true,
        'data' => $weekday_series,
        'show_average_column' => $show_average_column,
        'is_instantwin' => $is_instantwin
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    
} catch (Throwable $e) {
    error_log("Errore get_weekday_data.php: " . $e->getMessage());
    ob_end_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Errore nel recupero dei dati: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8')
    ], JSON_UNESCAPED_UNICODE);
}
