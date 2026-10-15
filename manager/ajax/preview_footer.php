<?php
/**
 * Anteprima footer pubblico (manager). ?live=1 utilise la session brouillon.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (empty($_SESSION['user_name']) || $_SESSION['user_name'] !== 'pm_admin') {
    header('HTTP/1.1 403 Forbidden');
    exit('Accesso non autorizzato.');
}

$BASE_HREF = '../../public/';
require_once __DIR__ . '/../config/config.php';

$isLive = !empty($_GET['live']);
if ($isLive && !empty($_SESSION['footer_preview_draft']) && is_array($_SESSION['footer_preview_draft'])) {
    $draft = $_SESSION['footer_preview_draft'];
    if (isset($draft['footer_style']) && is_array($draft['footer_style'])) {
        $contest_config['footer_style'] = array_replace(
            is_array($contest_config['footer_style'] ?? null) ? $contest_config['footer_style'] : [],
            $draft['footer_style']
        );
    }
    if (isset($draft['footer_layout']) && is_array($draft['footer_layout'])) {
        $contest_config['footer_layout'] = normalize_footer_layout(
            $draft['footer_layout'],
            is_array($content_defaults['footer_layout'] ?? null) ? $content_defaults['footer_layout'] : []
        );
    }
    if (array_key_exists('cookie_policy_link', $draft)) {
        $cookiePolicyDraft = trim((string) $draft['cookie_policy_link']);
        // Anteprima: se Link Cookie Policy è attivo, mostra il link anche senza PDF caricato
        if ($cookiePolicyDraft === '' && !empty($contest_config['footer_layout']['blocks']['links_cookie_policy'])) {
            $cookiePolicyDraft = '#';
        }
        $contest_config['cookie_policy_link'] = $cookiePolicyDraft;
    } elseif (!empty($contest_config['footer_layout']['blocks']['links_cookie_policy']) && trim((string) ($contest_config['cookie_policy_link'] ?? '')) === '') {
        $contest_config['cookie_policy_link'] = '#';
    }
    if (array_key_exists('cookie_consent_enabled', $draft)) {
        if (!isset($contest_config['cookie_consent']) || !is_array($contest_config['cookie_consent'])) {
            $contest_config['cookie_consent'] = [];
        }
        $contest_config['cookie_consent']['enabled'] = !empty($draft['cookie_consent_enabled']);
    }
    if (isset($draft['style_customization']) && is_array($draft['style_customization'])) {
        if (!isset($contest_config['style_customization']) || !is_array($contest_config['style_customization'])) {
            $contest_config['style_customization'] = [];
        }
        if (array_key_exists('footer_text_colors', $draft['style_customization'])) {
            $contest_config['style_customization']['footer_text_colors'] = !empty($draft['style_customization']['footer_text_colors']);
        }
    }
    if (isset($draft['colors']) && is_array($draft['colors'])) {
        if (!isset($contest_config['colors']) || !is_array($contest_config['colors'])) {
            $contest_config['colors'] = [];
        }
        foreach (['primary', 'secondary', 'tertiary', 'text'] as $colorKey) {
            $hex = trim((string) ($draft['colors'][$colorKey] ?? ''));
            if (preg_match('/^#[0-9A-Fa-f]{6}$/', $hex)) {
                $contest_config['colors'][$colorKey] = $hex;
            }
        }
    }
}

$is_published = $content_config['published'] ?? ($content_defaults['published'] ?? false);
if ($is_published && !$isLive) {
    $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $manager_path = dirname(dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    $manager_path = rtrim(str_replace('\\', '/', $manager_path), '/') . '/';
    header('Location: ' . $scheme . '://' . $host . $manager_path);
    exit;
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <base href="<?php echo htmlspecialchars(rtrim($BASE_HREF, '/') . '/', ENT_QUOTES, 'UTF-8'); ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
    <link rel="stylesheet" href="assets/css/minimal.css">
    <link rel="stylesheet" href="assets/css/custom.css">
    <?php echo get_footer_style_block(true); ?>
    <title>Anteprima footer</title>
    <style>
        body { margin: 0; padding: 0; background: #e9ecef; min-height: 100%; }
        .preview-shell { min-height: 120px; }
    </style>
</head>
<body>
<div class="preview-shell">
<?php render_public_footer($contest_config); ?>
</div>
</body>
</html>
