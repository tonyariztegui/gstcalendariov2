<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../../public/assets/mail/email_system.php';

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

$participationId = isset($_POST['participation_id']) ? (int)$_POST['participation_id'] : 0;
$emailType = isset($_POST['email_type']) ? trim((string)$_POST['email_type']) : '';

if ($participationId <= 0) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Dati richiesti mancanti.'
    ]);
    exit;
}

// Reinvio manuale dal manager: ignora flag "Invia questa email" disattivati in configurazione.
email_send_force(true);

try {
    $stmt = $connection->prepare("
        SELECT 
            p.has_won,
            p.confermato,
            p.annullato,
            p.prize_amount,
            p.prize_id,
            p.email_inviato,
            p.id_codice_premio,
            p.id_pdv_concorso,
            p.insegna,
            u.nome,
            u.cognome,
            u.email
        FROM partecipazioni p
        LEFT JOIN utenti u ON u.id_cliente = p.id_cliente
        WHERE p.id = ?
    ");
    $stmt->execute([$participationId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (is_array($row)) {
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

    $hasWon = (int)($row['has_won'] ?? 0) === 1;
    $isConfirmed = (int)($row['confermato'] ?? 0) === 1;
    $isCancelled = (int)($row['annullato'] ?? 0) === 1;
    $email = trim((string)($row['email'] ?? ''));

    if ($email === '') {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Email del partecipante non disponibile.'
        ]);
        exit;
    }

    $name = trim((string)($row['nome'] ?? ''));
    if ($name === '') {
        $name = trim((string)($row['cognome'] ?? ''));
    }
    if ($name === '') {
        $name = strtok($email, '@') ?: 'Partecipante';
    }

    $prizeAmount = $row['prize_amount'];
    if ($prizeAmount === null || $prizeAmount === '' || (float)$prizeAmount <= 0) {
        if (!empty($row['prize_id'])) {
            $stmtPrize = $connection->prepare('SELECT valore FROM premi WHERE id = ?');
            $stmtPrize->execute([(int)$row['prize_id']]);
            $fallbackPrize = $stmtPrize->fetchColumn();
            if ($fallbackPrize !== false) {
                $prizeAmount = $fallbackPrize;
            }
        }
    }

    if ($prizeAmount === null || $prizeAmount === '') {
        $prizeAmount = 0;
    }

    $contestMode = isset($contest_mode) ? strtolower((string)$contest_mode) : '';
    $fields_enabled = $contest_config['fields_enabled'] ?? [];
    $require_receipt = ($fields_enabled['scontrino'] ?? true) === true;
    $autoPrizeWithReceipt = (bool)($contest_config['instantwin_auto_prize_with_receipt'] ?? false);
    $autoPrizeFlow = $autoPrizeWithReceipt && $require_receipt;

    $computedType = 'participation';
    $successMessage = 'Email di partecipazione reinviata con successo.';

    if ($contestMode === 'instantwin') {
        $computedType = resolve_instantwin_outcome_email_type($hasWon, $isConfirmed, $isCancelled, $autoPrizeFlow);
        switch ($computedType) {
            case 'instantwin_cancel':
                $successMessage = 'Email di annullamento reinviata con successo.';
                break;
            case 'instantwin_prize':
                $successMessage = 'Email premio reinviata con successo.';
                break;
            case 'instantwin_confirm':
                $successMessage = 'Email di vincita reinviata con successo.';
                break;
            case 'instantwin_loss':
                $successMessage = 'Email con l\'esito reinviata con successo.';
                break;
            default:
                $successMessage = 'Email reinviata con successo.';
                break;
        }
    }

    // Tipo esplicito dal pulsante manager (data-email-type)
    if ($emailType === 'confirm') {
        $computedType = 'instantwin_prize';
        $successMessage = 'Email di conferma vittoria reinviata con successo.';
    } elseif ($emailType === 'instantwin_cancel') {
        $computedType = 'instantwin_cancel';
        $successMessage = 'Email di annullamento reinviata con successo.';
    } elseif ($emailType === 'instantwin_win' || $emailType === 'instantwin_confirm') {
        $computedType = 'instantwin_confirm';
        $successMessage = 'Email di conferma vittoria reinviata con successo.';
    } elseif ($emailType === 'instantwin_loss') {
        $computedType = 'instantwin_loss';
        $successMessage = 'Email con l\'esito reinviata con successo.';
    } elseif ($emailType === 'instantwin_prize') {
        $computedType = 'instantwin_prize';
        $successMessage = 'Email premio reinviata con successo.';
    } elseif ($emailType === 'partecipazione' || $emailType === 'participation') {
        $computedType = 'participation';
        $successMessage = 'Email di partecipazione reinviata con successo.';
    }
    // email_type "result" = calcolo automatico ($computedType già impostato)

    switch ($computedType) {
        case 'instantwin_win':
        case 'instantwin_confirm':
            $emailSent = sendInstantWinConfirmEmail($email, $name, $prizeAmount);
            break;
        case 'instantwin_loss':
            $emailSent = sendInstantWinLossEmail($email, $name);
            break;
        case 'instantwin_prize':
            $codice_premio = null;
            $pdf_path = null;
            $id_codice_premio = !empty($row['id_codice_premio']) ? (int)$row['id_codice_premio'] : null;
            if ($id_codice_premio) {
                $stmtCodice = $connection->prepare('SELECT codice, pdf_path FROM codici_premio WHERE id = ? LIMIT 1');
                $stmtCodice->execute([$id_codice_premio]);
                $codiceRow = $stmtCodice->fetch(PDO::FETCH_ASSOC);
                if ($codiceRow) {
                    $codice_premio = !empty($codiceRow['codice']) ? trim((string)$codiceRow['codice']) : null;
                    $pdf_path = !empty($codiceRow['pdf_path']) ? trim((string)$codiceRow['pdf_path']) : null;
                }
            }
            $emailSent = sendConfirmedWinEmail($email, $name, $prizeAmount, $codice_premio, $pdf_path, [
                'id_pdv_concorso' => (string) ($row['id_pdv_concorso'] ?? ''),
                'insegna' => (string) ($row['insegna'] ?? ''),
                'prize_id' => (int) ($row['prize_id'] ?? 0),
                'id_codice_premio' => (int) ($id_codice_premio ?? 0),
            ]);
            break;
        case 'instantwin_cancel':
            $emailSent = sendCancelledWinEmail($email, $name, $prizeAmount);
            break;
        case 'participation':
        default:
            $emailSent = sendPartecipazioneEmail($email, $name);
            break;
    }

    if ($emailSent) {
        $stmtUpdate = $connection->prepare("
            UPDATE partecipazioni 
            SET email_inviato = 1, email_error = NULL 
            WHERE id = ?
        ");
        $stmtUpdate->execute([$participationId]);
    }

    if (!$emailSent) {
        $smtpHint = '';
        global $email_config;
        $host = trim((string)($email_config['smtp_host'] ?? ''));
        $user = trim((string)($email_config['smtp_username'] ?? ''));
        $pass = trim((string)($email_config['smtp_password'] ?? ''));
        if ($host === '' || $user === '' || $pass === '') {
            $smtpHint = ' Configurazione SMTP incompleta nel concorso.';
        }
        throw new RuntimeException('Invio email non riuscito.' . $smtpHint);
    }

    echo json_encode([
        'success' => true,
        'message' => $successMessage
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Errore durante il reinvio: ' . $e->getMessage()
    ]);
}
