<?php
require_once(__DIR__ . '/../../config/config.php');
if (!isset($connection) || !$connection) {
    header('Content-Type: text/plain; charset=utf-8');
    die('Erreur : la connexion à la base de données a échoué.');
}

// Headers pour forcer Excel à reconnaître l'UTF-8
header("Content-Type: text/csv; charset=utf-8");
header("Content-Disposition: attachment; filename*=UTF-8''export_pdv_completo.csv");
header("Cache-Control: no-cache, must-revalidate");
header("Pragma: no-cache");

// BOM UTF-8 obligatoire pour Excel
echo "\xEF\xBB\xBF";

$output = fopen('php://output', 'w');

// En-têtes avec les champs de punti_vendita
$headers = [
    'ID',
    'CODICE',
    'RAGIONE_SOCIALE',
    'INDIRIZZO',
    'CITTA',
    'PROVINCIA',
    'CAP',
    'REGIONE',
    'INSEGNA',
    'ACTIVE'
];

fputcsv($output, $headers, ';');

// Requête complète avec les champs de punti_vendita
$sql = "SELECT 
    p.id,
    p.codice,
    p.ragsoc,
    p.indirizzo,
    p.citta,
    p.prov,
    p.cap,
    p.regione,
    p.insegna,
    p.active
FROM punti_vendita p
WHERE p.active = 1
ORDER BY p.codice ASC";

foreach ($connection->query($sql) as $row) {
    // Forcer les champs numériques comme texte pour Excel
    $id = '="' . $row['id'] . '"';
    $codice = '="' . $row['codice'] . '"';
    $active = '="' . $row['active'] . '"';
    
    fputcsv($output, [
        $id,
        $codice,
        $row['ragsoc'],
        $row['indirizzo'],
        $row['citta'],
        $row['prov'],
        $row['cap'],
        $row['regione'],
        $row['insegna'],
        $active
    ], ';');
}

fclose($output);
exit;
?> 
