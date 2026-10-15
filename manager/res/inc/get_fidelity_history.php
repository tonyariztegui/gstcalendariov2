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

$tokenParam = isset($_GET['email']) ? trim((string)$_GET['email']) : '';
$mainId = isset($_GET['main_id']) ? (int)$_GET['main_id'] : 0;

if ($tokenParam === '' && $mainId <= 0) {
    echo json_encode([
        'success' => false,
        'error' => 'Parametro non valido.'
    ]);
    exit;
}

$lookupToken = $tokenParam;
if ($lookupToken === '' && $mainId > 0) {
    $lookupStmt = $connection->prepare("
        SELECT 
            LOWER(TRIM(COALESCE(FIDELITY, ''))) AS fidelity_key,
            LOWER(TRIM(COALESCE(EMAIL, ''))) AS email_key
        FROM utenti
        WHERE ID_CLIENTE = ?
        LIMIT 1
    ");
    $lookupStmt->execute([$mainId]);
    $row = $lookupStmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $lookupToken = $row['fidelity_key'] !== '' ? $row['fidelity_key'] : $row['email_key'];
    }
}

if ($lookupToken === '__EMPTY__') {
    $lookupToken = '';
}

$lookupToken = strtolower(trim((string)$lookupToken));

try {
    // Verificare se è in modalità estrazione e se codice_partecipazione è attivato
    $contestMode = isset($contest_mode) ? strtolower((string)$contest_mode) : '';
    $isEstrazioneMode = $contestMode === 'estrazione';
    $fieldsEnabled = $contest_config['fields_enabled'] ?? [];
    $codicePartecipazioneEnabled = !empty($fieldsEnabled['codice_partecipazione']);
    $fidelityEnabled = array_key_exists('fidelity', $fieldsEnabled) ? (bool)$fieldsEnabled['fidelity'] : true;

    $tokenCondition = $fidelityEnabled
        ? "WHERE LOWER(TRIM(COALESCE(FIDELITY, ''))) = :token OR LOWER(TRIM(COALESCE(EMAIL, ''))) = :token"
        : "WHERE LOWER(TRIM(COALESCE(EMAIL, ''))) = :token";

    $userStmt = $connection->prepare("
        SELECT 
            ID_CLIENTE,
            NOME,
            COGNOME,
            EMAIL,
            FIDELITY,
            PREFISSO,
            CELLULARE,
            COALESCE(ATTIVO, 0) AS ATTIVO,
            COALESCE(PRIVACY, 0) AS PRIVACY,
            COALESCE(MARKETING, 0) AS MARKETING,
            COALESCE(PROFILAZIONE, 0) AS PROFILAZIONE
        FROM utenti
        {$tokenCondition}
        ORDER BY ID_CLIENTE ASC
    ");
    $userStmt->bindValue(':token', $lookupToken, PDO::PARAM_STR);
    $userStmt->execute();
    $profileRows = $userStmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$profileRows) {
        echo json_encode([
            'success' => false,
            'error' => 'Nessun profilo associato a questo indirizzo.'
        ]);
        exit;
    }

    $qrAccessRaw = is_array($contest_config['qr_access'] ?? null) ? $contest_config['qr_access'] : [];
    $qrAccessEnabled = !empty($qrAccessRaw['enabled']);

    $hasQrCodeColumn = false;
    if ($qrAccessEnabled) {
        try {
            $qrColCheck = $connection->query("SHOW COLUMNS FROM partecipazioni LIKE 'qr_code'");
            $hasQrCodeColumn = $qrColCheck && $qrColCheck->fetch() !== false;
        } catch (PDOException $e) {
            $hasQrCodeColumn = false;
        }
    }
    $qrSelect = $hasQrCodeColumn ? ', p.QR_CODE' : '';

    $historyWhere = $fidelityEnabled
        ? "WHERE LOWER(TRIM(COALESCE(u.FIDELITY, ''))) = :token OR LOWER(TRIM(COALESCE(u.EMAIL, ''))) = :token"
        : "WHERE LOWER(TRIM(COALESCE(u.EMAIL, ''))) = :token";

    $historyStmt = $connection->prepare("
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
            p.CODICE_PARTECIPAZIONE,
            u.NOME,
            u.COGNOME,
            u.EMAIL,
            u.FIDELITY
            {$qrSelect}
        FROM partecipazioni p
        INNER JOIN utenti u ON u.ID_CLIENTE = p.ID_CLIENTE
        {$historyWhere}
        ORDER BY p.DATA_PARTECIPAZIONE DESC, p.ID DESC
    ");
    $historyStmt->bindValue(':token', $lookupToken, PDO::PARAM_STR);
    $historyStmt->execute();
    $historyRows = $historyStmt->fetchAll(PDO::FETCH_ASSOC);

    $baseUploadDir = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'scontrini' . DIRECTORY_SEPARATOR;
    $baseUploadUrl = '../public/uploads/scontrini/';

    $profiles = [];
    $profileIndex = 1;
    $profileIndexMap = [];
    foreach ($profileRows as $row) {
        $profiles[] = [
            'index' => $profileIndex,
            'id_cliente' => (int)$row['ID_CLIENTE'],
            'nome' => $row['NOME'] ?? '',
            'cognome' => $row['COGNOME'] ?? '',
            'email' => $row['EMAIL'] ?? '',
            'fidelity' => $row['FIDELITY'] ?? '',
            'telefono' => formatPhoneWithPrefix($row['PREFISSO'] ?? '', $row['CELLULARE'] ?? ''),
            'attivo' => (int)$row['ATTIVO'],
            'privacy' => (int)$row['PRIVACY'],
            'marketing' => (int)$row['MARKETING'],
            'profilazione' => (int)$row['PROFILAZIONE']
        ];
        $profileIndexMap[(int)$row['ID_CLIENTE']] = $profileIndex;
        $profileIndex++;
    }

    $history = [];
    foreach ($historyRows as $row) {
        $fileName = $row['FILE_SCONTRINO'] ?? '';
        $fileUrl = null;
        $fileExt = null;

        if (!empty($fileName)) {
            $fullPath = $baseUploadDir . $fileName;
            if (is_file($fullPath)) {
                $fileUrl = $baseUploadUrl . rawurlencode($fileName);
                $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            }
        }

        $importoLabel = null;
        if ($row['IMPORTO_SCONTRINO'] !== null && $row['IMPORTO_SCONTRINO'] !== '') {
            $rawImporto = str_replace(["\u{00A0}", ' '], '', (string)$row['IMPORTO_SCONTRINO']);
            if (strpos($rawImporto, ',') !== false && strpos($rawImporto, '.') !== false) {
                $rawImporto = str_replace('.', '', $rawImporto);
            }
            $normalizedImporto = str_replace(',', '.', $rawImporto);
            $importoValue = (float)$normalizedImporto;
            $importoLabel = '€ ' . number_format($importoValue, 2, ',', '.');
        }

        $dataScontrino = null;
        if (!empty($row['DATA_SCONTRINO'])) {
            $timestamp = strtotime($row['DATA_SCONTRINO']);
            if ($timestamp !== false) {
                $dataScontrino = date('d/m/Y', $timestamp);
            }
        }


        $nome = trim((string)($row['NOME'] ?? ''));
        $cognome = trim((string)($row['COGNOME'] ?? ''));
        $fullName = trim($nome . ' ' . $cognome);
        if ($fullName === '' && !empty($row['EMAIL'])) {
            $fullName = $row['EMAIL'];
        } elseif ($fullName === '') {
            $fullName = 'ID ' . (int)$row['ID_CLIENTE'];
        }

        $profileIndexRef = $profileIndexMap[(int)$row['ID_CLIENTE']] ?? null;
        $dateSort = 0;
        if (!empty($row['DATA_PARTECIPAZIONE'])) {
            $timestamp = strtotime($row['DATA_PARTECIPAZIONE']);
            if ($timestamp !== false) {
                $dateSort = $timestamp;
            }
        } elseif (!empty($row['DATA_SCONTRINO'])) {
            $timestamp = strtotime($row['DATA_SCONTRINO']);
            if ($timestamp !== false) {
                $dateSort = $timestamp;
            }
        }

        $codicePartecipazione = !empty($row['CODICE_PARTECIPAZIONE']) ? trim((string)$row['CODICE_PARTECIPAZIONE']) : null;
        
        $history[] = [
            'profile_index' => $profileIndexRef,
            'id' => (int)$row['ID'],
            'id_cliente' => (int)$row['ID_CLIENTE'],
            'partecipante' => $fullName,
            'nome' => $nome,
            'cognome' => $cognome,
            'importo_label' => $importoLabel,
            'data_partecipazione' => !empty($row['DATA_PARTECIPAZIONE']) ? date('d/m/Y H:i', strtotime($row['DATA_PARTECIPAZIONE'])) : null,
            'data_scontrino' => $dataScontrino,
            'codice_partecipazione' => $codicePartecipazione,
            'qr_code' => $hasQrCodeColumn ? trim((string) ($row['QR_CODE'] ?? '')) : '',
            'file_url' => $fileUrl,
            'file_name' => $fileName,
            'file_ext' => $fileExt,
            'date_sort' => $dateSort
        ];
    }

    usort($history, function ($a, $b) {
        $pa = $a['profile_index'] ?? PHP_INT_MAX;
        $pb = $b['profile_index'] ?? PHP_INT_MAX;
        if ($pa === $pb) {
            return ($b['date_sort'] <=> $a['date_sort']);
        }
        return $pa <=> $pb;
    });

    foreach ($history as &$entry) {
        unset($entry['date_sort']);
    }
    unset($entry);

    $rawFlags = $contest_config['fields_enabled'] ?? [];
    $fieldsFlags = [
        'nome' => (bool)($rawFlags['nome'] ?? true),
        'cognome' => (bool)($rawFlags['cognome'] ?? true),
        'email' => (bool)($rawFlags['email'] ?? true),
        'prefisso' => (bool)($rawFlags['prefisso'] ?? true),
        'cellulare' => (bool)($rawFlags['cellulare'] ?? true),
        'privacy' => (bool)($rawFlags['privacy'] ?? true),
        'marketing' => (bool)($rawFlags['marketing'] ?? true),
        'profilazione' => (bool)($rawFlags['profilazione'] ?? true),
        'importo' => (bool)($rawFlags['importo'] ?? true),
        'data_scontrino' => (bool)($rawFlags['data_scontrino'] ?? true),
        'scontrino' => (bool)($rawFlags['scontrino'] ?? true),
        'codice_partecipazione' => !empty($fieldsEnabled['codice_partecipazione']),
    ];

    $fieldsEnabled = $contest_config['fields_enabled'] ?? [];
    $fidelityEnabled = array_key_exists('fidelity', $fieldsEnabled) ? (bool)$fieldsEnabled['fidelity'] : true;
    $primaryEmail = $profiles[0]['email'] ?? '';
    $primaryFidelity = $profiles[0]['fidelity'] ?? '';
    $useFidelity = $fidelityEnabled && $primaryFidelity !== '';
    $badgeLabel = $useFidelity ? 'Fidelity' : 'Email';
    $displayToken = $useFidelity ? $primaryFidelity : ($primaryEmail !== '' ? $primaryEmail : $lookupToken);

    echo json_encode([
        'success' => true,
        'email' => $lookupToken,
        'display_token' => $displayToken,
        'profiles_count' => count($profiles),
        'profiles' => $profiles,
        'history_count' => count($history),
        'history' => $history,
        'label' => $badgeLabel,
        'fields_flags' => $fieldsFlags,
        'is_estrazione_mode' => $isEstrazioneMode,
        'codice_partecipazione_enabled' => $codicePartecipazioneEnabled,
        'qr_access_enabled' => $qrAccessEnabled && $hasQrCodeColumn
    ]);
} catch (Throwable $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Errore nel recupero dello storico: ' . $e->getMessage()
    ]);
}


