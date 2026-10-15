<?php
// Page d'accueil : Non Publicato = bienvenue ; Publicato = registrazione
require_once __DIR__ . '/../app/inc/router.php';

$config_dir = __DIR__ . '/../app/config';
$content = [];
if (is_readable($config_dir . '/content.defaults.json')) {
    $raw = @file_get_contents($config_dir . '/content.defaults.json');
    if ($raw !== false && ($d = @json_decode($raw, true)) && is_array($d)) {
        $content = $d;
    }
}
if (is_readable($config_dir . '/content.json')) {
    $raw = @file_get_contents($config_dir . '/content.json');
    if ($raw !== false && ($d = @json_decode($raw, true)) && is_array($d)) {
        $content = array_merge($content, $d);
    }
}
$requestedRoute = app_public_route_from_request();
if ($requestedRoute !== null) {
    $routePath = app_route_view_path('public', $requestedRoute, __DIR__);
    if ($routePath !== null) {
        if ($requestedRoute === 'non-consentito') {
            $public_error_code = 403;
        } elseif ($requestedRoute === 'errore') {
            $public_error_code = 500;
        }
        require_once $routePath;
    } else {
        $public_error_code = 404;
        require_once __DIR__ . '/error.php';
    }
    exit;
}
if (empty($content['published'])) {
    $routePath = app_route_view_path('public', 'benvenuto', __DIR__);
    if ($routePath !== null) {
        require_once $routePath;
    }
    exit;
}
if (!empty($content['participation_page_enabled'])) {
    $routePath = app_route_view_path('public', 'partecipazione', __DIR__);
    if ($routePath !== null) {
        require_once $routePath;
    }
    exit;
}
$routePath = app_route_view_path('public', 'registrazione', __DIR__);
if ($routePath !== null) {
    require_once $routePath;
}
