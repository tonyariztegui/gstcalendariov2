<?php
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
if (($_SESSION['user_name'] ?? '') !== 'pm_admin') { http_response_code(403); exit; }
header('Cache-Control: no-store, private');
require_once __DIR__ . '/../../app/logic/advent_calendar.php';
require_once __DIR__ . '/../../app/logic/advent_footer.php';
require_once __DIR__ . '/../../app/inc/config.php';
require_once __DIR__ . '/../views/calendari_avvento/editor.php';
$calendar = advent_load();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!is_string($_POST['csrf'] ?? null) || !hash_equals($_SESSION['advent_csrf'] ?? '', $_POST['csrf'])) { http_response_code(403); exit; }
    $input = advent_footer_from_post($_POST);
    [$calendar, $errors] = advent_validate($input);
    if ($errors) { http_response_code(422); echo advent_escape(implode(' ', $errors)); exit; }
    // Local preview images stay in this response; no uploaded file or draft is saved.
    foreach (['upload_footer_image'=>'footer_background_image','upload_footer_logo_image'=>'footer_logo'] as $key=>$target) {
        $file = $_FILES[$key] ?? null;
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) continue;
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        if ($file['size'] <= 6 * 1024 * 1024 && in_array($mime, ['image/png','image/jpeg','image/webp','image/gif'], true)) {
            $calendar[$target] = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($file['tmp_name']));
        }
    }
}
$base = advent_editor_base();
$contest_config = advent_footer_config($calendar, $base);
?>
<!doctype html><html lang="it"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="<?= advent_escape($base) ?>/public/assets/css/custom.css">
<?= get_footer_style_block() ?><style>body{margin:0;background:#e9ecef}</style></head><body>
<?php render_public_footer($contest_config); ?>
</body></html>
