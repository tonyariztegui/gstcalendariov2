<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../../app/logic/prize_image_generator.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit('Sessione scaduta.');
}

$template = trim(str_replace('\\', '/', (string) ($_GET['template'] ?? '')));
if ($template === '' || !preg_match('#^uploads/premi/templates/[a-zA-Z0-9_\.\-]+$#', $template)) {
    http_response_code(400);
    exit('Template non valido.');
}

$templateAbs = prize_image_resolve_absolute_path($template);
if (!is_file($templateAbs) || !is_readable($templateAbs)) {
    http_response_code(404);
    exit('Immagine non trovata.');
}

$overlay = prize_email_normalize_overlay_config([
    'enabled' => filter_var($_GET['enabled'] ?? false, FILTER_VALIDATE_BOOLEAN),
    'x' => $_GET['x'] ?? 200,
    'y' => $_GET['y'] ?? 350,
    'font_size' => $_GET['font_size'] ?? 24,
    'color' => $_GET['color'] ?? '#000000',
]);
$sampleCode = trim((string) ($_GET['code'] ?? 'ABC123'));
if ($sampleCode === '') {
    $sampleCode = 'ABC123';
}

$previewRel = prize_image_render_overlay_preview($template, $overlay, $sampleCode);
if ($previewRel === null) {
    http_response_code(500);
    exit('Anteprima non disponibile (verificare estensione GD).');
}

$previewAbs = prize_image_resolve_absolute_path($previewRel);
if (!is_file($previewAbs)) {
    http_response_code(500);
    exit('File anteprima mancante.');
}

header('Content-Type: image/png');
header('Cache-Control: no-store, no-cache, must-revalidate');
readfile($previewAbs);
exit;
