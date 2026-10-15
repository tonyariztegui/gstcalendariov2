<?php
/**
 * Export Codici Premio
 */
require_once(__DIR__ . '/../../config/config.php');
if (!isset($connection) || !$connection) {
    header('Content-Type: text/plain; charset=utf-8');
    die('Erreur : la connexion à la base de données a échoué.');
}

// Headers pour forcer Excel à reconnaître l'UTF-8
header("Content-Type: text/csv; charset=utf-8");
header("Content-Disposition: attachment; filename*=UTF-8''export_codici_premio.csv");
header("Cache-Control: no-cache, must-revalidate");
header("Pragma: no-cache");

// BOM UTF-8 obligatoire pour Excel
echo "\xEF\xBB\xBF";

$output = fopen('php://output', 'w');

// En-têtes
$headers = [
    'ID',
    'CODICE',
    'PRIZE_ID',
    'ID_CODICE_PARTECIPAZIONE',
    'ID_PARTECIPAZIONE',
    'USED',
    'IS_ACTIVE',
    'PDF_PATH',
    'DATA_CREAZIONE',
    'DATA_ASSEGNAZIONE',
    'DATA_INVIO_EMAIL'
];

fputcsv($output, $headers, ';');

// Requête
$sql = "SELECT 
    id,
    codice,
    prize_id,
    id_codice_partecipazione,
    id_partecipazione,
    used,
    isActive,
    pdf_path,
    data_creazione,
    data_assegnazione,
    data_invio_email
FROM codici_premio
ORDER BY data_creazione DESC";

foreach ($connection->query($sql) as $row) {
    fputcsv($output, [
        $row['id'],
        $row['codice'] ?? '',
        $row['prize_id'] ?? '',
        $row['id_codice_partecipazione'] ?? '',
        $row['id_partecipazione'] ?? '',
        $row['used'] ?? 0,
        $row['isActive'] ?? 1,
        $row['pdf_path'] ?? '',
        $row['data_creazione'] ?? '',
        $row['data_assegnazione'] ?? '',
        $row['data_invio_email'] ?? ''
    ], ';');
}

fclose($output);
exit;
