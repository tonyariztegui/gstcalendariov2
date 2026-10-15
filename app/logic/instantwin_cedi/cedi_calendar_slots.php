<?php

/**
 * Fenêtres slot actives par Ce.Di. (port checkTimeSlotWindow_legacy + table cedi_calendario_time_slot).
 */

if (!function_exists('instantwin_cedi_find_slot_window')) {
    /**
     * @return array{slot_timestamp: int, window_start: int, window_end: int, slots_vides_precedents: int}|false
     */
    function instantwin_cedi_find_slot_window(?int $timestamp, string $codice_cedi)
    {
        global $c_pdo;
        if (!($c_pdo instanceof PDO)) {
            return false;
        }

        $cedi = instantwin_cedi_normalize_codice($codice_cedi);
        if ($cedi === null) {
            return false;
        }

        instantwin_cedi_ensure_calendar_schema($c_pdo);

        $now = $timestamp !== null ? (int) $timestamp : time();
        $current_date = date('Y-m-d', $now);
        $calBindBase = [':d' => $current_date, ':cedi' => $cedi];

        if (!isDateInContestRange($current_date)) {
            return false;
        }

        $day_of_week = (int) date('N', $now);
        if (!in_array($day_of_week, instantwin_get_distribution_days(), true)) {
            return false;
        }

        $time_range = instantwin_get_time_range_for_day($day_of_week);
        $start_hour = (int) $time_range['start'];
        $end_hour = (int) $time_range['end'];

        if (!($start_hour === 0 && $end_hour === 24)) {
            $hour = (int) date('H', $now);
            if (!instantwin_is_in_time_range($day_of_week, $hour)) {
                return false;
            }
        }

        [$day_start_ts, $day_end_ts] = instantwin_get_day_bounds_ts($current_date, $start_hour, $end_hour);

        $reduction_seconds = defined('INSTANTWIN_NORMAL_END_REDUCTION_SECONDS')
            ? (int) INSTANTWIN_NORMAL_END_REDUCTION_SECONDS
            : 0;
        $normal_end_ts = $day_end_ts - $reduction_seconds;
        if ($normal_end_ts <= $day_start_ts) {
            $normal_end_ts = $day_start_ts + max(60, $reduction_seconds);
        }

        $daily_remaining = instantwin_cedi_get_daily_remaining_for_calendar($c_pdo, $current_date, $cedi);
        $catchup = instantwin_resolve_catchup_mode(
            $now,
            $day_start_ts,
            $day_end_ts,
            $normal_end_ts,
            $end_hour,
            $daily_remaining
        );
        $is_catchup_mode = $catchup['is_catchup'];
        $catchup_start_ts = $catchup['catchup_start_ts'];

        $effective_end_ts = $is_catchup_mode ? $day_end_ts : $normal_end_ts;

        if ($now < $day_start_ts || $now >= $effective_end_ts) {
            return false;
        }

        if (defined('MAX_DAILY_TARGET') && MAX_DAILY_TARGET > 0 && $daily_remaining <= 0) {
            return false;
        }

        $stmt_last_win = $c_pdo->prepare('
            SELECT MAX(
                GREATEST(
                    COALESCE(UNIX_TIMESTAMP(data_tiraggio), 0),
                    slot_timestamp
                )
            ) AS last_win_ts
            FROM cedi_calendario_time_slot
            WHERE data = :d AND codice_cedi = :cedi
              AND token_vincitore IS NOT NULL
              AND GREATEST(
                    COALESCE(UNIX_TIMESTAMP(data_tiraggio), 0),
                    slot_timestamp
              ) <= :now
        ');
        $stmt_last_win->execute($calBindBase + [':now' => $now]);
        $row_last = $stmt_last_win->fetch(PDO::FETCH_ASSOC);
        $last_win_ts = ($row_last && $row_last['last_win_ts'] !== null)
            ? (int) $row_last['last_win_ts']
            : null;
        $min_interval = instantwin_effective_min_win_interval_seconds($last_win_ts, $current_date);
        if ($last_win_ts !== null && ($now - $last_win_ts) < $min_interval) {
            return false;
        }

        $slot_recovery = max(0, (int) INSTANTWIN_SLOT_RECOVERY_WINDOW_SECONDS);
        $simFast = function_exists('instantwin_cedi_simulation_context_active')
            && instantwin_cedi_simulation_context_active();
        if (!$simFast) {
            instantwin_cedi_release_expired_unoccupied_slots(
                $c_pdo,
                $current_date,
                $cedi,
                $now,
                $slot_recovery
            );
        }

        $fetch_unoccupied = static function () use ($c_pdo, $calBindBase, $day_start_ts, $effective_end_ts): array {
            $stmt = $c_pdo->prepare('
                SELECT slot_timestamp, finestra_inizio, finestra_fine
                FROM cedi_calendario_time_slot
                WHERE data = :d AND codice_cedi = :cedi AND token_vincitore IS NULL
                ORDER BY slot_timestamp ASC
            ');
            $stmt->execute($calBindBase);
            $out = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $out[] = $row;
            }
            return $out;
        };

        $try_return_window = static function (array $slot_rows) use ($now): ?array {
            if (!function_exists('instantwin_cedi_find_active_window_from_persisted')) {
                return null;
            }
            $active = instantwin_cedi_find_active_window_from_persisted($slot_rows, $now);
            if ($active === null) {
                return null;
            }
            return [
                'slot_timestamp' => $active['slot_timestamp'],
                'window_start' => $active['window_start'],
                'window_end' => $active['window_end'],
                'slots_vides_precedents' => 0,
            ];
        };

        $unoccupied = $fetch_unoccupied();

        $activeWin = $try_return_window($unoccupied);
        if ($activeWin !== null) {
            return $activeWin;
        }

        // Recalcule N fenêtres contiguës (N = prix restants) dès qu'aucune fenêtre n'est active.
        if ($daily_remaining > 0 && function_exists('instantwin_cedi_sync_slots_for_remaining')) {
            if (instantwin_cedi_sync_slots_for_remaining($c_pdo, $current_date, $cedi, $now)) {
                $unoccupied = $fetch_unoccupied();
                $activeWin = $try_return_window($unoccupied);
                if ($activeWin !== null) {
                    return $activeWin;
                }
            }
        }

        return false;
    }
}

if (!function_exists('instantwin_cedi_warmup_day')) {
    /** Prépare les slots dynamiques pour chaque Ce.Di. (simulateur). */
    function instantwin_cedi_warmup_day(string $dateYmd, int $sampleTs = 0): void
    {
        global $c_pdo;
        if (!($c_pdo instanceof PDO) || !function_exists('instantwin_cedi_calendar_enabled')
            || !instantwin_cedi_calendar_enabled($c_pdo)) {
            return;
        }

        $ts = $sampleTs > 0 ? $sampleTs : strtotime($dateYmd . ' 12:00:00');
        foreach (instantwin_cedi_definitions() as $code => $_meta) {
            instantwin_cedi_find_slot_window($ts, (string) $code);
        }
    }
}
