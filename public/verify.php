<?php

header('Content-Type: text/html; charset=UTF-8');
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../app/inc/config.php';
require_once __DIR__ . '/../app/inc/functions.php';
require_once __DIR__ . '/../app/logic/verify/qr_verify.php';
require_once __DIR__ . '/../app/logic/verify/qr_verify_view.php';

if (!empty($_GET['preview'])) {
    if (!empty($contest_config['published'])) {
        header('Location: verifica');
        exit;
    }
    $_SESSION['contest_preview'] = true;
}

$is_preview = !empty($_SESSION['contest_preview'] ?? null);
if (!empty($contest_config['published']) && $is_preview) {
    unset($_SESSION['contest_preview']);
    $is_preview = false;
}

if (empty($contest_config['published']) && !$is_preview) {
    header('Location: benvenuto');
    exit;
}

if ($is_preview && isset($_GET['preview_error'])) {
    require_once __DIR__ . '/../app/inc/qr_config.php';
    $previewError = qr_config_map_error_reason((string) $_GET['preview_error']);
    if (in_array($previewError, qr_config_system_error_keys(), true)) {
        qr_verify_render_error($previewError);
        exit;
    }
}

$result = qr_verify_handle_request($_GET, $_SERVER, $is_preview);

if (($result['action'] ?? '') === 'redirect') {
    $url = trim((string) ($result['url'] ?? qr_access_success_route()));
    header('Location: ' . $url);
    exit;
}

if (($result['action'] ?? '') === 'waiting') {
    $view = trim((string) ($result['view'] ?? 'waiting_page.php'));
    if (!preg_match('/^waiting_page(_ended)?\.php$/', $view)) {
        $view = 'waiting_page.php';
    }
    include __DIR__ . '/' . $view;
    exit;
}

if (($result['action'] ?? '') === 'error') {
    qr_verify_render_error((string) ($result['reason'] ?? 'qr_invalid'));
    exit;
}

header('Location: ' . qr_access_success_route());
exit;
