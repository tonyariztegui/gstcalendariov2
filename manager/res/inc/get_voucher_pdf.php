<?php
/**
 * Script pour servir les PDFs vouchers avec les bons headers HTTP
 */

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../../config/config.php';

// Vérifier que l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    die('Access denied');
}

// Récupérer le chemin du PDF depuis le paramètre
$pdfPath = isset($_GET['path']) ? trim((string)$_GET['path']) : '';

if ($pdfPath === '') {
    http_response_code(400);
    die('PDF path not provided');
}

// Normaliser le chemin (supprimer les backslashes Windows, les préfixes)
$normalizedPath = str_replace('\\', '/', $pdfPath);
$normalizedPath = preg_replace('#^(public/|assets/)#', '', $normalizedPath);

// Construire le chemin complet du fichier
$baseDir = dirname(__DIR__, 3); // Remonter de manager/res/inc vers la racine
$filePath = '';

if (strpos($normalizedPath, 'uploads/') === 0) {
    // Chemin depuis uploads/
    $filePath = $baseDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $normalizedPath);
} else {
    // Chemin depuis assets/ ou autre
    $filePath = $baseDir . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $normalizedPath);
}

// Sécurité : vérifier que le chemin est dans uploads/vouchers/
$realPath = realpath($filePath);
$allowedDir = realpath($baseDir . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'vouchers');

if ($realPath === false || $allowedDir === false || strpos($realPath, $allowedDir) !== 0) {
    http_response_code(403);
    die('Access denied: Invalid path');
}

// Vérifier que le fichier existe et est un PDF
if (!is_file($realPath)) {
    http_response_code(404);
    die('File not found');
}

$extension = strtolower(pathinfo($realPath, PATHINFO_EXTENSION));
if ($extension !== 'pdf') {
    http_response_code(400);
    die('Invalid file type');
}

// Déterminer si c'est un téléchargement ou une visualisation
$download = isset($_GET['download']) && $_GET['download'] === '1';

// Headers HTTP pour PDF
header('Content-Type: application/pdf');
header('Content-Length: ' . filesize($realPath));
header('Cache-Control: private, max-age=3600');
header('Pragma: cache');

if ($download) {
    // Headers pour téléchargement
    $filename = basename($realPath);
    header('Content-Disposition: attachment; filename="' . addslashes($filename) . '"');
} else {
    // Headers pour visualisation inline
    header('Content-Disposition: inline; filename="' . addslashes(basename($realPath)) . '"');
}

// Lire et envoyer le fichier
readfile($realPath);
exit;
