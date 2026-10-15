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

$weightsIn = $_POST['weights'] ?? null;
if (!is_array($weightsIn)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Parametro weights mancante o non valido.',
    ]);
    exit;
}

instantwin_pdv_ensure_schema($connection);
$allowed = [];
foreach (instantwin_pdv_active_pdvs_for_split($connection) as $row) {
    $allowed[trim((string) ($row['codice'] ?? ''))] = true;
}

try {
    $connection->beginTransaction();

    $upd = $connection->prepare('
        UPDATE `punti_vendita`
        SET `iw_traffic_weight` = :w
        WHERE TRIM(`codice`) = :c
          AND `active` = 1
          AND `codice` IS NOT NULL
          AND TRIM(`codice`) <> \'\'
    ');

    foreach ($weightsIn as $codRaw => $wRaw) {
        $cod = trim((string) $codRaw);
        if ($cod === '' || !isset($allowed[$cod])) {
            continue;
        }
        $w = is_string($wRaw) ? str_replace(',', '.', trim($wRaw)) : $wRaw;
        $w = (float) $w;
        if (!is_finite($w) || $w < 0.0001) {
            $w = 1.0;
        }
        if ($w > 1000.0) {
            $w = 1000.0;
        }
        $upd->execute([':w' => $w, ':c' => $cod]);
    }

    // Schema gia' garantito sopra; evitare DDL dentro la transazione (MySQL).
    instantwin_pdv_rebuild_budget_tables_only($connection, false);

    $connection->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Pesi salvati e tabella pdv_premio_budget rigenerata (quota giornaliera non modificata).',
    ]);
} catch (Throwable $e) {
    if ($connection->inTransaction()) {
        $connection->rollBack();
    }
    error_log('pdv_iw_traffic_weights_save: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Errore durante il salvataggio: ' . $e->getMessage(),
    ]);
}
