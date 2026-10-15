<?php

/**
 * Fenêtres slot Ce.Di. partagées (décision = persistance audit).
 */

if (!function_exists('instantwin_cedi_calendar_first_slot_window_seconds')) {
    function instantwin_cedi_calendar_first_slot_window_seconds(): int
    {
        if (defined('INSTANTWIN_CEDI_FIRST_SLOT_WINDOW_SECONDS')) {
            return max(60, (int) INSTANTWIN_CEDI_FIRST_SLOT_WINDOW_SECONDS);
        }
        return 300;
    }
}

if (!function_exists('instantwin_cedi_calendar_slots_batch_per_request')) {
    function instantwin_cedi_calendar_slots_batch_per_request(): int
    {
        $sim = function_exists('instantwin_cedi_simulation_context_active')
            && instantwin_cedi_simulation_context_active();
        if ($sim) {
            if (defined('INSTANTWIN_CEDI_SLOTS_BATCH_SIM')) {
                return max(1, (int) INSTANTWIN_CEDI_SLOTS_BATCH_SIM);
            }
            return 12;
        }
        if (defined('INSTANTWIN_CEDI_SLOTS_BATCH_PER_REQUEST')) {
            return max(1, (int) INSTANTWIN_CEDI_SLOTS_BATCH_PER_REQUEST);
        }
        return 5;
    }
}

if (!function_exists('instantwin_cedi_get_daily_target_for_calendar')) {
    function instantwin_cedi_get_daily_target_for_calendar(PDO $pdo, string $dateYmd, string $cedi): int
    {
        if (!function_exists('instantwin_cedi_get_daily_quota_row')) {
            return 0;
        }
        $row = instantwin_cedi_get_daily_quota_row($pdo, $dateYmd, $cedi);
        if ($row === null) {
            return 0;
        }
        return max(0, (int) ($row['target'] ?? 0));
    }
}

if (!function_exists('instantwin_cedi_compute_window_for_slot')) {
    /**
     * @return array{window_start: int, window_end: int}|null
     */
    function instantwin_cedi_compute_window_for_slot(
        int $slot_ts,
        int $prev_ts,
        int $next_ts,
        int $now,
        int $day_start_ts,
        int $effective_end_ts,
        int $slot_recovery,
        bool $is_first_in_chain,
        int $t0
    ): ?array {
        if ($now < $slot_ts - $slot_recovery) {
            return null;
        }

        if ($is_first_in_chain) {
            if (function_exists('instantwin_is_default_full_day_schedule') && instantwin_is_default_full_day_schedule()) {
                $span = max(60, $slot_ts - $t0);
                $back = min((int) floor($span * 0.9), 10 * 3600);
                $window_start = max($t0, $slot_ts - $back);
            } else {
                $window_start = max($day_start_ts, $slot_ts - $slot_recovery);
            }
        } else {
            $window_start = (int) floor(($prev_ts + $slot_ts) / 2);
        }

        $window_end = (int) floor(($slot_ts + $next_ts) / 2);
        $window_start = max($window_start, $day_start_ts, $slot_ts - $slot_recovery);
        $window_end = min($window_end, $effective_end_ts);

        if ($window_start >= $window_end || $now < $window_start || $now >= $window_end) {
            return null;
        }

        return [
            'window_start' => $window_start,
            'window_end' => $window_end,
        ];
    }
}

if (!function_exists('instantwin_cedi_compute_window_bounds_for_slot')) {
    /**
     * Même géométrie que find_slot_window, sans filtre « now in window » (persistance audit).
     *
     * @return array{window_start: int, window_end: int}|null
     */
    function instantwin_cedi_compute_window_bounds_for_slot(
        int $slot_ts,
        int $prev_ts,
        int $next_ts,
        int $day_start_ts,
        int $effective_end_ts,
        int $slot_recovery,
        bool $is_first_in_chain,
        int $t0
    ): ?array {
        if ($is_first_in_chain) {
            if (function_exists('instantwin_is_default_full_day_schedule') && instantwin_is_default_full_day_schedule()) {
                $span = max(60, $slot_ts - $t0);
                $back = min((int) floor($span * 0.9), 10 * 3600);
                $window_start = max($t0, $slot_ts - $back);
            } else {
                $window_start = max($day_start_ts, $slot_ts - $slot_recovery);
            }
        } else {
            $window_start = (int) floor(($prev_ts + $slot_ts) / 2);
        }

        $window_end = (int) floor(($slot_ts + $next_ts) / 2);
        $window_start = max($window_start, $day_start_ts, $slot_ts - $slot_recovery);
        $window_end = min($window_end, $effective_end_ts);

        if ($window_start >= $window_end) {
            return null;
        }

        return [
            'window_start' => $window_start,
            'window_end' => $window_end,
        ];
    }
}

if (!function_exists('instantwin_cedi_upsert_slot_windows')) {
    /**
     * @param list<array{slot_timestamp: int, window_start: int, window_end: int}> $slots
     */
    function instantwin_cedi_upsert_slot_windows(string $date, string $codice_cedi, array $slots): void
    {
        global $c_pdo;
        if (!($c_pdo instanceof PDO) || $slots === []) {
            return;
        }

        $cedi = instantwin_cedi_normalize_codice($codice_cedi);
        if ($cedi === null) {
            return;
        }

        instantwin_cedi_ensure_calendar_schema($c_pdo);

        $ins = $c_pdo->prepare('
            INSERT INTO cedi_calendario_time_slot
            (data, codice_cedi, slot_timestamp, finestra_inizio, finestra_fine, token_vincitore, partecipanti_totali, data_tiraggio)
            VALUES (:d, :cedi, :s, :ws, :we, NULL, 0, NULL)
            ON DUPLICATE KEY UPDATE
                finestra_inizio = VALUES(finestra_inizio),
                finestra_fine = VALUES(finestra_fine)
        ');

        foreach ($slots as $row) {
            $slot_ts = (int) ($row['slot_timestamp'] ?? 0);
            $ws = (int) ($row['window_start'] ?? 0);
            $we = (int) ($row['window_end'] ?? 0);
            if ($slot_ts <= 0 || $we <= $ws) {
                continue;
            }
            try {
                $ins->execute([
                    ':d' => $date,
                    ':cedi' => $cedi,
                    ':s' => $slot_ts,
                    ':ws' => $ws,
                    ':we' => $we,
                ]);
            } catch (Exception $e) {
                error_log('instantwin_cedi_upsert_slot_windows: ' . $e->getMessage());
            }
        }
    }
}

if (!function_exists('instantwin_cedi_find_active_window_from_persisted')) {
    /**
     * @param list<array{slot_timestamp: int|string, finestra_inizio: int|string, finestra_fine: int|string}> $rows
     * @return array{slot_timestamp: int, window_start: int, window_end: int}|null
     */
    function instantwin_cedi_find_active_window_from_persisted(array $rows, int $now): ?array
    {
        foreach ($rows as $row) {
            $ws = (int) ($row['finestra_inizio'] ?? 0);
            $we = (int) ($row['finestra_fine'] ?? 0);
            $slot_ts = (int) ($row['slot_timestamp'] ?? 0);
            if ($slot_ts <= 0 || $we <= $ws) {
                continue;
            }
            if ($now >= $ws && $now < $we) {
                return [
                    'slot_timestamp' => $slot_ts,
                    'window_start' => $ws,
                    'window_end' => $we,
                ];
            }
        }

        return null;
    }
}

if (!function_exists('instantwin_cedi_release_expired_unoccupied_slots')) {
    /** Slots passés non gagnés : ne bloquent plus la génération (rattrapage via nouveaux slots). */
    function instantwin_cedi_release_expired_unoccupied_slots(
        PDO $pdo,
        string $dateYmd,
        string $cedi,
        int $now,
        int $recovery_seconds
    ): void {
        $cedi = instantwin_cedi_normalize_codice($cedi);
        if ($cedi === null) {
            return;
        }
        try {
            $st = $pdo->prepare('
                DELETE FROM cedi_calendario_time_slot
                WHERE data = :d AND codice_cedi = :c
                  AND token_vincitore IS NULL
                  AND finestra_fine < :now_cut
            ');
            $st->execute([':d' => $dateYmd, ':c' => $cedi, ':now_cut' => $now]);
        } catch (Exception $e) {
            error_log('instantwin_cedi_release_expired_unoccupied_slots: ' . $e->getMessage());
        }
    }
}
