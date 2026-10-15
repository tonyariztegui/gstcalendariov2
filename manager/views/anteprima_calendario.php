<?php
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
if (empty($_SESSION['user_name']) || $_SESSION['user_name'] !== 'pm_admin') {
    http_response_code(403);
    echo 'Accesso riservato.';
    return;
}
require_once __DIR__ . '/../../app/logic/advent_calendar.php';
require_once __DIR__ . '/calendari_avvento/editor.php';
header('Cache-Control: no-store, private');
header('X-Robots-Tag: noindex, nofollow');
$calendar = $_SESSION['advent_preview'] ?? advent_load();
$adventPreview = true;
$adventOpenAll = !isset($_GET['mode']) || $_GET['mode'] !== 'date';
$adventToday = isset($_GET['date']) && is_string($_GET['date']) ? advent_date($_GET['date']) : null;
$adventFailure = null;
$adventBase = advent_editor_base();
$adventPageUrl = $adventBase . '/manager/anteprima-calendario';
require __DIR__ . '/../../app/views/advent_calendar.php';
