<?php
declare(strict_types=1);
require_once __DIR__ . '/../app/logic/advent_calendar.php';
header('Cache-Control: no-store, private');
$adventPreview = false;
$adventOpenAll = false;
$adventToday = null;
$adventFailure = null;
try { $calendar = advent_load(); }
catch (Throwable $e) {
    $calendar = advent_defaults();
    $adventFailure = 'Il calendario non è momentaneamente disponibile. Riprova più tardi.';
    http_response_code(503);
}
$scriptPath = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/public/index.php');
$adventBase = (string) preg_replace('~/public(?:/.*)?$~', '', $scriptPath);
$adventPageUrl = $adventBase . '/public/calendario-avvento';
require __DIR__ . '/../app/views/advent_calendar.php';
