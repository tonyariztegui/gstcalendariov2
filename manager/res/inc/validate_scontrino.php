<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/ensure_scontrino_validato_column.inc.php';
require_once __DIR__ . '/../../../app/logic/instantwin.php';
require_once __DIR__ . '/../../../public/assets/mail/email_system.php';
require_once __DIR__ . '/manager_win_actions.inc.php';

try {
    $connection->exec("SET NAMES utf8mb4");
    $connection->exec("SET CHARACTER SET utf8mb4");
    $connection->exec("SET collation_connection = 'utf8mb4_unicode_ci'");
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Errore di connessione al database.'
    ]);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Sessione scaduta. Effettua nuovamente il login.'
    ]);
    exit;
}

$fieldsEnabled = $contest_config['fields_enabled'] ?? [];
$showReceipt = !empty($fieldsEnabled['scontrino']);
if (!$showReceipt) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Validazione scontrino non disponibile per questo concorso.'
    ]);
    exit;
}

if (!manager_ensure_scontrino_validato_column($connection)) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Impossibile preparare la colonna di validazione scontrino.'
    ]);
    exit;
}

$participationId = isset($_POST['participation_id']) ? (int) $_POST['participation_id'] : 0;
$statusRaw = isset($_POST['status']) ? trim((string) $_POST['status']) : '';

if ($participationId <= 0) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'ID partecipazione non valido.'
    ]);
    exit;
}

// status: 1 = valido, 0 = non valido, pending = reset
$statusValue = null;
if ($statusRaw === '1' || $statusRaw === 'valid') {
    $statusValue = 1;
} elseif ($statusRaw === '0' || $statusRaw === 'invalid') {
    $statusValue = 0;
} elseif ($statusRaw === 'pending' || $statusRaw === '') {
    $statusValue = null;
} else {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Stato di validazione non valido.'
    ]);
    exit;
}

$autoPrizeWithReceipt = (bool) ($contest_config['instantwin_auto_prize_with_receipt'] ?? false);
$requireReceiptUpload = !empty($fieldsEnabled['scontrino']);
$contestModeLocal = isset($contest_mode) ? strtolower((string) $contest_mode) : '';
$isInstantWin = $contestModeLocal === 'instantwin';
$isEstrazione = !$isInstantWin;
$autoPrizeFlow = $autoPrizeWithReceipt && $isInstantWin && $requireReceiptUpload;
$managerConfirmFlow = $isInstantWin && !$autoPrizeFlow && instantwin_is_manager_confirm_flow_enabled();
$managerCancelFlow = $isInstantWin && !$autoPrizeFlow && instantwin_is_manager_cancel_flow_enabled();
// Lock + mail solo Instant Win + Conferma manager (mai in estrazione).
$emailLockEnabled = !$isEstrazione && ($managerConfirmFlow || $managerCancelFlow);

try {
    $stmt = $connection->prepare('
        SELECT
            ID,
            FILE_SCONTRINO,
            HAS_WON,
            CONFERMATO,
            ANNULLATO,
            SCONTRINO_VALIDATO
        FROM partecipazioni
        WHERE ID = ?
        LIMIT 1
    ');
    $stmt->execute([$participationId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $row = array_change_key_case($row, CASE_LOWER);
    }
    if (!$row) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Partecipazione non trovata.'
        ]);
        exit;
    }

    $fileName = trim((string) ($row['file_scontrino'] ?? ''));
    if ($fileName === '') {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Nessuna foto scontrino da validare per questa partecipazione.'
        ]);
        exit;
    }

    $currentValidato = null;
    $rawValidato = $row['scontrino_validato'] ?? null;
    if ($rawValidato !== null && $rawValidato !== '') {
        $currentValidato = (int) $rawValidato === 1 ? 1 : 0;
    }
    $hasWon = (int) ($row['has_won'] ?? 0) === 1;
    $confermato = (int) ($row['confermato'] ?? 0) === 1;
    $annullato = (int) ($row['annullato'] ?? 0) === 1;

    // Instant Win: validazione scontrino solo per le vincite.
    if ($isInstantWin && !$hasWon) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Validazione scontrino non disponibile per le non vincite Instant Win.',
        ]);
        exit;
    }

    // Instant Win senza Conferma manager: niente validazione via questo endpoint.
    if ($isInstantWin && !$emailLockEnabled) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Validazione scontrino disponibile solo con Conferma manager attiva.',
        ]);
        exit;
    }

    if ($emailLockEnabled) {
        $alreadyDecided = $currentValidato !== null || $confermato || $annullato;
        if ($alreadyDecided) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Scelta già registrata, non modificabile.',
                'status' => $currentValidato,
                'locked' => true,
            ]);
            exit;
        }
        if ($statusValue === null) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Con le email Conferma manager attive non è possibile annullare la scelta.',
                'locked' => true,
            ]);
            exit;
        }
    }

    $winActionMessage = null;
    $winnerEligible = $hasWon && !$confermato && !$annullato;

    // Conferma / annullamento vincita PRIMA del salvataggio scontrino (solo IW + manager).
    if ($emailLockEnabled && $winnerEligible && $statusValue === 1 && $managerConfirmFlow) {
        $confirmResult = manager_confirm_participation_win($connection, $participationId);
        if (empty($confirmResult['success'])) {
            http_response_code((int) ($confirmResult['http_code'] ?? 500));
            echo json_encode([
                'success' => false,
                'message' => (string) ($confirmResult['message'] ?? 'Errore durante la conferma della vittoria.'),
            ]);
            exit;
        }
        $winActionMessage = (string) ($confirmResult['message'] ?? '');
    } elseif ($emailLockEnabled && $winnerEligible && $statusValue === 0 && $managerCancelFlow) {
        $cancelResult = manager_cancel_participation_win($connection, $participationId);
        if (empty($cancelResult['success'])) {
            http_response_code((int) ($cancelResult['http_code'] ?? 500));
            echo json_encode([
                'success' => false,
                'message' => (string) ($cancelResult['message'] ?? 'Errore durante l\'annullamento della vittoria.'),
            ]);
            exit;
        }
        $winActionMessage = (string) ($cancelResult['message'] ?? '');
    }

    $update = $connection->prepare('UPDATE partecipazioni SET scontrino_validato = :status WHERE ID = :id');
    if ($statusValue === null) {
        $update->bindValue(':status', null, PDO::PARAM_NULL);
    } else {
        $update->bindValue(':status', $statusValue, PDO::PARAM_INT);
    }
    $update->bindValue(':id', $participationId, PDO::PARAM_INT);
    $update->execute();

    $label = $statusValue === 1 ? 'valido' : ($statusValue === 0 ? 'non valido' : 'da validare');
    $message = 'Scontrino segnato come ' . $label . '.';
    if ($winActionMessage !== null && $winActionMessage !== '') {
        $message .= ' ' . $winActionMessage;
    }

    echo json_encode([
        'success' => true,
        'message' => $message,
        'status' => $statusValue,
        'participation_id' => $participationId,
        'locked' => $emailLockEnabled && $statusValue !== null,
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Errore durante il salvataggio della validazione.'
    ]);
}
