<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../config/config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'error' => 'Sessione scaduta. Effettua nuovamente il login.'
    ]);
    exit;
}

$emailParam = isset($_GET['email']) ? trim((string) $_GET['email']) : '';
if ($emailParam === '') {
    $emailParam = isset($_GET['card']) ? trim((string) $_GET['card']) : '';
}
if ($emailParam === '') {
    echo json_encode([
        'success' => false,
        'error' => 'Email non valida.'
    ]);
    exit;
}

$emailKey = $emailParam === '__EMPTY__' ? '' : strtolower(trim($emailParam));

try {
    $hasQrCodeColumn = false;
    try {
        $qrColCheck = $connection->query("SHOW COLUMNS FROM partecipazioni LIKE 'qr_code'");
        $hasQrCodeColumn = $qrColCheck && $qrColCheck->fetch() !== false;
    } catch (PDOException $e) {
        $hasQrCodeColumn = false;
    }

    $qrSelect = $hasQrCodeColumn ? ', p.QR_CODE' : '';

    $stmt = $connection->prepare("
        SELECT
            p.ID,
            p.ID_CLIENTE,
            p.DATA_PARTECIPAZIONE,
            p.DATA_SCONTRINO,
            p.IMPORTO_SCONTRINO,
            p.FILE_SCONTRINO,
            p.HAS_WON,
            p.PRIZE_AMOUNT,
            p.PRIZE_ID,
            p.WIN_DATE,
            u.NOME,
            u.COGNOME,
            u.EMAIL
            {$qrSelect}
        FROM partecipazioni p
        INNER JOIN utenti u ON u.ID_CLIENTE = p.ID_CLIENTE
        WHERE LOWER(TRIM(COALESCE(u.EMAIL, ''))) = :email
        ORDER BY p.DATA_PARTECIPAZIONE DESC, p.ID DESC
    ");
    $stmt->bindValue(':email', $emailKey, PDO::PARAM_STR);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $baseUploadDir = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'scontrini' . DIRECTORY_SEPARATOR;
    $baseUploadUrl = '../uploads/scontrini/';

    $partecipazioni = [];
    foreach ($rows as $row) {
        $fileName = $row['FILE_SCONTRINO'] ?? '';
        $fileUrl = null;

        if (!empty($fileName)) {
            $fullPath = $baseUploadDir . $fileName;
            if (is_file($fullPath)) {
                $fileUrl = $baseUploadUrl . rawurlencode($fileName);
            }
        }

        $importoFormatted = '0,00';
        if ($row['IMPORTO_SCONTRINO'] !== null) {
            $rawImporto = str_replace(["\u{00A0}", ' '], '', (string)$row['IMPORTO_SCONTRINO']);
            if (strpos($rawImporto, ',') !== false && strpos($rawImporto, '.') !== false) {
                $rawImporto = str_replace('.', '', $rawImporto);
            }
            $normalizedImporto = str_replace(',', '.', $rawImporto);
            $importoValue = (float)$normalizedImporto;
            $importoFormatted = number_format($importoValue, 2, ',', '.');
        }

        $partecipante = trim(($row['NOME'] ?? '') . ' ' . ($row['COGNOME'] ?? ''));
        if ($partecipante === '' && !empty($row['EMAIL'])) {
            $partecipante = $row['EMAIL'];
        } elseif ($partecipante === '') {
            $partecipante = 'ID ' . (int)$row['ID_CLIENTE'];
        }


        $partecipazioni[] = [
            'id' => (int)$row['ID'],
            'id_cliente' => (int)$row['ID_CLIENTE'],
            'partecipante' => $partecipante,
            'data_partecipazione' => !empty($row['DATA_PARTECIPAZIONE']) ? date('d/m/Y H:i', strtotime($row['DATA_PARTECIPAZIONE'])) : '--',
            'data_scontrino' => !empty($row['DATA_SCONTRINO']) ? date('d/m/Y', strtotime($row['DATA_SCONTRINO'])) : null,
            'importo_scontrino' => $importoFormatted,
            'file_url' => $fileUrl,
            'file_name' => $fileName,
            'qr_code' => $hasQrCodeColumn ? trim((string) ($row['QR_CODE'] ?? '')) : '',
            'has_won' => (int)($row['HAS_WON'] ?? 0),
            'prize_amount' => $row['PRIZE_AMOUNT'] !== null ? number_format((float)$row['PRIZE_AMOUNT'], 2, ',', '.') : null,
            'prize_id' => $row['PRIZE_ID'] ?? null,
            'win_date' => !empty($row['WIN_DATE']) ? date('d/m/Y H:i', strtotime($row['WIN_DATE'])) : null
        ];
    }

    $emailLabel = $emailKey !== '' ? $emailKey : 'Senza email';

    echo json_encode([
        'success' => true,
        'email_label' => $emailLabel,
        'count' => count($partecipazioni),
        'partecipazioni' => $partecipazioni
    ]);
} catch (Throwable $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Errore nel recupero delle partecipazioni: ' . $e->getMessage()
    ]);
}
