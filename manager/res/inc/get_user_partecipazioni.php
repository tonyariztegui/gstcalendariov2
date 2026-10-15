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

$id_cliente = isset($_GET['id_cliente']) ? (int)$_GET['id_cliente'] : 0;
if ($id_cliente <= 0) {
    echo json_encode([
        'success' => false,
        'error' => 'ID cliente non valido.'
    ]);
    exit;
}

try {
    $hasQrCodeColumn = false;
    try {
        $qrColCheck = $connection->query("SHOW COLUMNS FROM partecipazioni LIKE 'qr_code'");
        $hasQrCodeColumn = $qrColCheck && $qrColCheck->fetch() !== false;
    } catch (PDOException $e) {
        $hasQrCodeColumn = false;
    }

    $qrSelect = $hasQrCodeColumn ? ', QR_CODE' : '';

    $stmt = $connection->prepare("
        SELECT 
            ID,
            DATA_PARTECIPAZIONE,
            DATA_SCONTRINO,
            IMPORTO_SCONTRINO,
            FILE_SCONTRINO,
            HAS_WON,
            PRIZE_AMOUNT,
            PRIZE_ID,
            WIN_DATE
            {$qrSelect}
        FROM partecipazioni
        WHERE ID_CLIENTE = ?
        ORDER BY DATA_PARTECIPAZIONE DESC, ID DESC
    ");
    $stmt->execute([$id_cliente]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $base_upload_dir = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'scontrini' . DIRECTORY_SEPARATOR;
    $base_upload_url = '../uploads/scontrini/';

    $partecipazioni = [];
    foreach ($rows as $row) {
        $file_name = $row['FILE_SCONTRINO'] ?? '';
        $file_url = null;

        if (!empty($file_name)) {
            $full_path = $base_upload_dir . $file_name;
            if (is_file($full_path)) {
                $file_url = $base_upload_url . rawurlencode($file_name);
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

        $partecipazioni[] = [
            'id' => (int)$row['ID'],
            'data_partecipazione' => !empty($row['DATA_PARTECIPAZIONE']) ? date('d/m/Y H:i', strtotime($row['DATA_PARTECIPAZIONE'])) : '--',
            'data_scontrino' => !empty($row['DATA_SCONTRINO']) ? date('d/m/Y', strtotime($row['DATA_SCONTRINO'])) : null,
            'importo_scontrino' => $importoFormatted,
            'file_url' => $file_url,
            'file_name' => $file_name,
            'qr_code' => $hasQrCodeColumn ? trim((string) ($row['QR_CODE'] ?? '')) : '',
            'has_won' => (int)($row['HAS_WON'] ?? 0),
            'prize_amount' => $row['PRIZE_AMOUNT'] !== null ? number_format((float)$row['PRIZE_AMOUNT'], 2, ',', '.') : null,
            'prize_id' => $row['PRIZE_ID'] ?? null,
            'win_date' => !empty($row['WIN_DATE']) ? date('d/m/Y H:i', strtotime($row['WIN_DATE'])) : null
        ];
    }

    echo json_encode([
        'success' => true,
        'count' => count($partecipazioni),
        'partecipazioni' => $partecipazioni
    ]);
} catch (Throwable $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Errore nel recupero delle partecipazioni: ' . $e->getMessage()
    ]);
}
