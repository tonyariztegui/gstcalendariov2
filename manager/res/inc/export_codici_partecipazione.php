<?php
/**
 * Export Codici Partecipazione
 */
require_once(__DIR__ . '/../../config/config.php');
if (!isset($connection) || !$connection) {
    header('Content-Type: text/plain; charset=utf-8');
    die('Erreur : la connexion à la base de données a échoué.');
}

// Headers pour forcer Excel à reconnaître l'UTF-8
header("Content-Type: text/csv; charset=utf-8");
header("Content-Disposition: attachment; filename*=UTF-8''export_codici_partecipazione.csv");
header("Cache-Control: no-cache, must-revalidate");
header("Pragma: no-cache");

// BOM UTF-8 obligatoire pour Excel
echo "\xEF\xBB\xBF";

$output = fopen('php://output', 'w');

// En-têtes
$headers = [
    'ID',
    'CODICE',
    'IS_ACTIVE',
    'USED',
    'ID_PARTECIPAZIONE',
    'DATA_CREAZIONE',
    'DATA_UTILIZZO'
];

fputcsv($output, $headers, ';');

// Requête
$sql = "SELECT 
    id,
    codice,
    isActive,
    used,
    id_partecipazione,
    data_creazione,
    data_utilizzo
FROM codici_partecipazione
ORDER BY data_creazione DESC";

foreach ($connection->query($sql) as $row) {
    fputcsv($output, [
        $row['id'],
        $row['codice'] ?? '',
        $row['isActive'] ?? 1,
        $row['used'] ?? 0,
        $row['id_partecipazione'] ?? '',
        $row['data_creazione'] ?? '',
        $row['data_utilizzo'] ?? ''
    ], ';');
}

fclose($output);
exit;
