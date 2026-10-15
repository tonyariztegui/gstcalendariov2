<?php

if (!function_exists('instantwin_cedi_calendar_enabled')) {
    function instantwin_cedi_calendar_enabled(PDO $pdo): bool
    {
        if (!function_exists('instantwin_cedi_daily_quota_enabled')) {
            require_once __DIR__ . '/cedi_quota.php';
        }
        return instantwin_cedi_daily_quota_enabled($pdo);
    }
}
