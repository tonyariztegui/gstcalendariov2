<?php

/**
 * Instant Win ripartito per Ce.Di. — entrée publique du module.
 */
require_once __DIR__ . '/../instantwin/core.php';
require_once __DIR__ . '/cedi_core.php';
require_once __DIR__ . '/cedi_calendar_schema.php';
require_once __DIR__ . '/cedi_calendar_flags.php';
require_once __DIR__ . '/cedi_calendar_window.php';
require_once __DIR__ . '/cedi_calendar_dynamic.php';
require_once __DIR__ . '/cedi_calendar_slots.php';
require_once __DIR__ . '/cedi_assign_prize.php';
require_once __DIR__ . '/cedi_calendar_play.php';

/**
 * Instant Win con `instantwin_per_cedi`: calendario slot per Ce.Di. + budget settimanale.
 */
function checkInstantWinCedi($token, $simulated_timestamp = null)
{
    global $c_pdo, $contest_mode, $contest_config;

    try {
        if (!isset($contest_mode)) {
            require_once __DIR__ . '/../../inc/config.php';
        }
        if (!isset($contest_mode) || $contest_mode !== 'instantwin') {
            error_log("checkInstantWinCedi: contest_mode non è 'instantwin' (valore: " . ($contest_mode ?? 'non definito') . ") - rifiuto");
            return INSTANTWIN_NO_WIN;
        }
        if (!instantwin_cedi_enabled($contest_config)) {
            error_log('checkInstantWinCedi: instantwin_per_cedi non attivo');
            return INSTANTWIN_NO_WIN;
        }
        if (!($c_pdo instanceof PDO)) {
            return INSTANTWIN_NO_WIN;
        }

        instantwin_cedi_ensure_schema($c_pdo);

        if (function_exists('instantwin_test_direct_loss_enabled') && instantwin_test_direct_loss_enabled()) {
            error_log('checkInstantWinCedi: INSTANTWIN_TEST_OUTCOME_MODE=direct_loss — perdita forzata (solo test)');
            return INSTANTWIN_NO_WIN;
        }

        $check_time = $simulated_timestamp !== null ? $simulated_timestamp : time();
        $current_date = date('Y-m-d', $check_time);
        $day_of_week = (int) date('N', $check_time);

        if (!isDateInContestRange($current_date)) {
            error_log("checkInstantWinCedi: data $current_date non nel range di distribuzione");
            return INSTANTWIN_NO_WIN;
        }

        $distribution_days = instantwin_get_distribution_days();
        if (!in_array($day_of_week, $distribution_days, true)) {
            return INSTANTWIN_NO_WIN;
        }

        $prizes_info = checkPrizesAvailable();
        if (!$prizes_info['available'] || $prizes_info['quantity'] <= 0) {
            return INSTANTWIN_NO_WIN;
        }
        if (!instantwin_allow_multiple_wins() && hasEmailAlreadyWon($token)) {
            return INSTANTWIN_NO_WIN;
        }

        if (!instantwin_cedi_can_assign_for_token($c_pdo, $token, $current_date)) {
            return INSTANTWIN_NO_WIN;
        }

        if (function_exists('instantwin_cedi_daily_quota_enabled') && instantwin_cedi_daily_quota_enabled($c_pdo)) {
            instantwin_cedi_sync_daily_quota_targets($c_pdo, $current_date);
            $cediCode = instantwin_cedi_resolve_for_token($c_pdo, $token);
            if ($cediCode === null || instantwin_cedi_get_daily_remaining($c_pdo, $current_date, $cediCode, true) <= 0) {
                return INSTANTWIN_NO_WIN;
            }
        }

        $time_range = instantwin_get_time_range_for_day($day_of_week);
        if (!($time_range['start'] == 0 && $time_range['end'] == 24)) {
            if (!isPrizeDistributionTime($check_time)) {
                return INSTANTWIN_NO_WIN;
            }
        }

        if (function_exists('instantwin_cedi_calendar_enabled') && instantwin_cedi_calendar_enabled($c_pdo)) {
            return instantwin_cedi_try_win($token, $simulated_timestamp);
        }

        if (instantwin_legacy_direct_win_enabled()) {
            if (!instantwin_legacy_direct_win_allowed($c_pdo, $current_date, null)) {
                return INSTANTWIN_NO_WIN;
            }
            error_log('checkInstantWinCedi: INSTANTWIN_LEGACY_DIRECT_WIN — assegnazione senza calendario slot Ce.Di.');
            return instantwin_cedi_assign_prize($token, $simulated_timestamp);
        }

        error_log('checkInstantWinCedi: calendario Ce.Di. disattivato e nessuna modalità legacy — perdita');
        return INSTANTWIN_NO_WIN;
    } catch (Exception $e) {
        error_log('Errore checkInstantWinCedi: ' . $e->getMessage());
        return INSTANTWIN_NO_WIN;
    }
}
