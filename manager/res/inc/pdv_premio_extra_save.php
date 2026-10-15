<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../../app/logic/instantwin_pdv/instantwin_pdv.php';

try {
    $connection->exec('SET NAMES utf8mb4');
    $connection->exec('SET CHARACTER SET utf8mb4');
    $connection->exec("SET collation_connection = 'utf8mb4_unicode_ci'");
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Errore di connessione al database.',
    ]);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Sessione scaduta. Effettua nuovamente il login.',
    ]);
    exit;
}

if (!instantwin_pdv_enabled()) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Instant Win per PDV non attivo.',
    ]);
    exit;
}

$prizeId = isset($_POST['prize_id']) ? (int) $_POST['prize_id'] : 0;
$extrasIn = $_POST['extras'] ?? null;
if ($prizeId <= 0 || !is_array($extrasIn)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Parametri prize_id o extras non validi.',
    ]);
    exit;
}

instantwin_pdv_ensure_schema($connection);

$pdvs = instantwin_pdv_active_pdvs_for_split($connection);
$nPdv = count($pdvs);
if ($nPdv === 0) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Nessun PDV attivo con codice magazzino.',
    ]);
    exit;
}

$allowed = [];
foreach ($pdvs as $row) {
    $allowed[trim((string) ($row['codice'] ?? ''))] = true;
}

try {
    $st = $connection->prepare('SELECT `quantita_totale` FROM `premi` WHERE `id` = :id LIMIT 1');
    $st->execute([':id' => $prizeId]);
    $qty = (int) $st->fetchColumn();
    if ($qty <= 0) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Premio non trovato o quantità non valida.',
        ]);
        exit;
    }

    $extraTotal = instantwin_pdv_equal_base_and_extra_total($qty, $nPdv)['extra_total'];
    if ($extraTotal <= 0) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Questo premio non ha slot aggiuntivi da ripartire (divisione perfetta tra i PDV).',
        ]);
        exit;
    }

    $byCode = [];
    $sum = 0;
    foreach ($extrasIn as $codRaw => $nRaw) {
        $cod = trim((string) $codRaw);
        if ($cod === '' || !isset($allowed[$cod])) {
            continue;
        }
        $n = max(0, (int) $nRaw);
        if ($n <= 0) {
            continue;
        }
        $byCode[$cod] = ($byCode[$cod] ?? 0) + $n;
        $sum += $n;
    }

    if ($sum !== $extraTotal) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => "La somma degli slot aggiuntivi deve essere {$extraTotal} (attuale: {$sum}).",
        ]);
        exit;
    }

    $connection->beginTransaction();
    instantwin_pdv_save_extra_allocations_for_prize($connection, $prizeId, $byCode);
    // Schema gia' garantito sopra; evitare DDL dentro la transazione (commit implicito MySQL → errore su commit()).
    instantwin_pdv_rebuild_budget_tables_only($connection, false);
    $connection->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Premi aggiuntivi salvati e pdv_premio_budget rigenerato.',
    ]);
} catch (Throwable $e) {
    if ($connection->inTransaction()) {
        $connection->rollBack();
    }
    error_log('pdv_premio_extra_save: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Errore durante il salvataggio: ' . $e->getMessage(),
    ]);
}
