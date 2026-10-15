<?php

/**
 * Pacing Ce.Di. : calendrier fixe journalier (1 fenêtre = 1 prix) + rattrapage contigu si fenêtre manquée.
 */

if (!function_exists('instantwin_cedi_compute_ideal_pacing_step')) {
    function instantwin_cedi_compute_ideal_pacing_step(
        int $day_start_ts,
        int $effective_end_ts,
        int $prizes_total_target
    ): int {
        $min = max(180, (int) INSTANTWIN_MIN_SLOT_INTERVAL_SECONDS);
        $full_span = $effective_end_ts - $day_start_ts;
        if ($full_span <= 0 || $prizes_total_target <= 0) {
            return $min;
        }

        return max($min, (int) floor($full_span / $prizes_total_target));
    }
}

if (!function_exists('instantwin_cedi_fixed_window_end')) {
    function instantwin_cedi_fixed_window_end(
        int $day_start_ts,
        int $effective_end_ts,
        int $prizes_total_target,
        int $idx,
        int $ideal_step
    ): int {
        if ($idx >= $prizes_total_target - 1) {
            return $effective_end_ts;
        }

        return min($effective_end_ts, $day_start_ts + (($idx + 1) * $ideal_step));
    }
}

if (!function_exists('instantwin_cedi_build_fixed_daily_windows')) {
    /**
     * Grille fixe du jour : fenêtre k = [day_start + k·step , fin_k), contiguës, 1 prix par fenêtre.
     *
     * @return list<array{slot_timestamp: int, window_start: int, window_end: int}>
     */
    function instantwin_cedi_build_fixed_daily_windows(
        int $day_start_ts,
        int $effective_end_ts,
        int $prizes_total_target,
        int $start_index,
        int $now,
        int $min
    ): array {
        if ($prizes_total_target <= 0 || $start_index >= $prizes_total_target) {
            return [];
        }

        $full_span = $effective_end_ts - $day_start_ts;
        if ($full_span <= 0) {
            return [];
        }

        $step = max($min, (int) floor($full_span / $prizes_total_target));
        $plan = [];
        for ($k = $start_index; $k < $prizes_total_target; $k++) {
            $ws = $day_start_ts + ($k * $step);
            if ($ws >= $effective_end_ts) {
                break;
            }
            $we = ($k === $prizes_total_target - 1)
                ? $effective_end_ts
                : min($effective_end_ts, $day_start_ts + (($k + 1) * $step));
            if ($we <= $ws || $we <= $now) {
                continue;
            }
            $plan[] = [
                'slot_timestamp' => $ws,
                'window_start' => $ws,
                'window_end' => $we,
            ];
        }

        return $plan;
    }
}

if (!function_exists('instantwin_cedi_build_contiguous_pacing_plan')) {
    /**
     * Rattrapage : découpe [coverage_start, fin] en fenêtres contiguës sans creux (fin N = début N+1).
     *
     * @return list<array{slot_timestamp: int, window_start: int, window_end: int}>
     */
    function instantwin_cedi_build_contiguous_pacing_plan(
        int $day_start_ts,
        int $effective_end_ts,
        int $coverage_start,
        int $prizes_remaining,
        int $now,
        int $min
    ): array {
        $coverage_start = max($day_start_ts, $coverage_start);
        $plan_span = $effective_end_ts - $coverage_start;
        if ($plan_span <= 0 || $prizes_remaining <= 0) {
            return [];
        }

        $step = max($min, (int) floor($plan_span / $prizes_remaining));
        $plan = [];
        for ($i = 0; $i < $prizes_remaining; $i++) {
            $ws = $coverage_start + ($i * $step);
            if ($ws >= $effective_end_ts) {
                break;
            }
            $we = ($i === $prizes_remaining - 1)
                ? $effective_end_ts
                : min($effective_end_ts, $coverage_start + (($i + 1) * $step));
            if ($we <= $ws || $we <= $now) {
                continue;
            }
            $plan[] = [
                'slot_timestamp' => $ws,
                'window_start' => $ws,
                'window_end' => $we,
            ];
        }

        return $plan;
    }
}

if (!function_exists('instantwin_cedi_is_pacing_behind_schedule')) {
    /** Fenêtre fixe du prochain prix expirée sans gain. */
    function instantwin_cedi_is_pacing_behind_schedule(
        int $day_start_ts,
        int $effective_end_ts,
        int $prizes_total_target,
        int $prizes_already_won,
        int $now,
        int $ideal_step
    ): bool {
        if ($prizes_already_won >= $prizes_total_target || $now >= $effective_end_ts) {
            return false;
        }

        $next_we = instantwin_cedi_fixed_window_end(
            $day_start_ts,
            $effective_end_ts,
            $prizes_total_target,
            $prizes_already_won,
            $ideal_step
        );

        return $next_we <= $now;
    }
}

if (!function_exists('instantwin_cedi_build_pacing_plan')) {
    /**
     * Mode normal : fenêtres fixes à partir de l'index courant.
     * Mode rattrapage : prix restants répartis contiguës sur le temps restant.
     *
     * @return list<array{slot_timestamp: int, window_start: int, window_end: int}>
     */
    function instantwin_cedi_build_pacing_plan(
        int $day_start_ts,
        int $effective_end_ts,
        int $prizes_total_target,
        int $prizes_already_won,
        int $now
    ): array {
        $min = max(180, (int) INSTANTWIN_MIN_SLOT_INTERVAL_SECONDS);
        $prizes_remaining = max(0, $prizes_total_target - $prizes_already_won);
        if ($prizes_total_target <= 0 || $prizes_remaining <= 0 || $now >= $effective_end_ts) {
            return [];
        }

        $ideal_step = instantwin_cedi_compute_ideal_pacing_step(
            $day_start_ts,
            $effective_end_ts,
            $prizes_total_target
        );

        if (instantwin_cedi_is_pacing_behind_schedule(
            $day_start_ts,
            $effective_end_ts,
            $prizes_total_target,
            $prizes_already_won,
            $now,
            $ideal_step
        )) {
            return instantwin_cedi_build_contiguous_pacing_plan(
                $day_start_ts,
                $effective_end_ts,
                max($day_start_ts, $now),
                $prizes_remaining,
                $now,
                $min
            );
        }

        return instantwin_cedi_build_fixed_daily_windows(
            $day_start_ts,
            $effective_end_ts,
            $prizes_total_target,
            $prizes_already_won,
            $now,
            $min
        );
    }
}

/** @deprecated Utiliser instantwin_cedi_build_pacing_plan */
if (!function_exists('instantwin_cedi_build_hybrid_pacing_plan')) {
    function instantwin_cedi_build_hybrid_pacing_plan(
        int $day_start_ts,
        int $effective_end_ts,
        int $prizes_total_target,
        int $prizes_already_won,
        int $now,
        ?int $last_win_ts,
        string $dateYmd
    ): array {
        return instantwin_cedi_build_pacing_plan(
            $day_start_ts,
            $effective_end_ts,
            $prizes_total_target,
            $prizes_already_won,
            $now
        );
    }
}

if (!function_exists('instantwin_cedi_resolve_calendar_day_bounds')) {
    /**
     * @return array{day_start_ts: int, effective_end_ts: int}|null
     */
    function instantwin_cedi_resolve_calendar_day_bounds(string $dateYmd, int $now, int $daily_remaining): ?array
    {
        $dow = (int) date('N', $now);
        if (!in_array($dow, instantwin_get_distribution_days(), true)) {
            return null;
        }

        $time_range = instantwin_get_time_range_for_day($dow);
        $start_hour = (int) $time_range['start'];
        $end_hour = (int) $time_range['end'];
        [$day_start_ts, $day_end_ts] = instantwin_get_day_bounds_ts($dateYmd, $start_hour, $end_hour);

        $reduction_seconds = defined('INSTANTWIN_NORMAL_END_REDUCTION_SECONDS')
            ? (int) INSTANTWIN_NORMAL_END_REDUCTION_SECONDS
            : 0;
        $normal_end_ts = $day_end_ts - $reduction_seconds;
        if ($normal_end_ts <= $day_start_ts) {
            $normal_end_ts = $day_start_ts + max(60, $reduction_seconds);
        }

        $catchup = instantwin_resolve_catchup_mode(
            $now,
            $day_start_ts,
            $day_end_ts,
            $normal_end_ts,
            $end_hour,
            $daily_remaining
        );
        $effective_end_ts = $catchup['is_catchup'] ? $day_end_ts : $normal_end_ts;

        return [
            'day_start_ts' => $day_start_ts,
            'effective_end_ts' => $effective_end_ts,
        ];
    }
}

if (!function_exists('instantwin_cedi_resolve_pacing_context')) {
    /**
     * @return array{
     *   day_start_ts: int,
     *   effective_end_ts: int,
     *   prizes_total_target: int,
     *   prizes_already_won: int,
     *   prizes_remaining: int
     * }|null
     */
    function instantwin_cedi_resolve_pacing_context(
        PDO $pdo,
        string $dateYmd,
        string $cedi,
        int $now
    ): ?array {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateYmd)) {
            return null;
        }

        $remaining = instantwin_cedi_get_daily_remaining($pdo, $dateYmd, $cedi, false);
        if ($remaining <= 0) {
            return null;
        }

        $bounds = instantwin_cedi_resolve_calendar_day_bounds($dateYmd, $now, $remaining);
        if ($bounds === null || $now >= $bounds['effective_end_ts']) {
            return null;
        }

        $stWins = $pdo->prepare('
            SELECT COUNT(*) FROM cedi_calendario_time_slot
            WHERE data = :d AND codice_cedi = :cedi AND token_vincitore IS NOT NULL
        ');
        $stWins->execute([':d' => $dateYmd, ':cedi' => $cedi]);
        $prizes_already_won = (int) $stWins->fetchColumn();

        $prizes_total_target = $remaining + $prizes_already_won;
        if (function_exists('instantwin_cedi_get_daily_quota_row')) {
            $quotaRow = instantwin_cedi_get_daily_quota_row($pdo, $dateYmd, $cedi);
            if ($quotaRow !== null) {
                $prizes_total_target = max((int) ($quotaRow['target'] ?? 0), $prizes_total_target);
            }
        }
        if ($prizes_total_target <= 0) {
            return null;
        }

        return [
            'day_start_ts' => $bounds['day_start_ts'],
            'effective_end_ts' => $bounds['effective_end_ts'],
            'prizes_total_target' => $prizes_total_target,
            'prizes_already_won' => $prizes_already_won,
            'prizes_remaining' => max(0, $prizes_total_target - $prizes_already_won),
        ];
    }
}

if (!function_exists('instantwin_cedi_persist_free_slot_plan')) {
    /**
     * @param list<array{slot_timestamp: int, window_start: int, window_end: int}> $plan
     */
    function instantwin_cedi_persist_free_slot_plan(
        PDO $pdo,
        string $dateYmd,
        string $cedi,
        array $plan
    ): bool {
        if ($plan === []) {
            return false;
        }

        try {
            $pdo->prepare('
                DELETE FROM cedi_calendario_time_slot
                WHERE data = :d AND codice_cedi = :cedi AND token_vincitore IS NULL
            ')->execute([':d' => $dateYmd, ':cedi' => $cedi]);
        } catch (Exception $e) {
            error_log('instantwin_cedi_persist_free_slot_plan delete: ' . $e->getMessage());
            return false;
        }

        global $c_pdo;
        if (!($c_pdo instanceof PDO)) {
            $c_pdo = $pdo;
        }

        instantwin_cedi_upsert_slot_windows($dateYmd, $cedi, $plan);

        return true;
    }
}

if (!function_exists('instantwin_cedi_sync_slots_for_remaining')) {
    /**
     * Recalcule les slots libres : grille fixe ou rattrapage si fenêtre manquée.
     * Appelé quand aucune fenêtre active n'est trouvée (pas après chaque vincita).
     */
    function instantwin_cedi_sync_slots_for_remaining(
        PDO $pdo,
        string $dateYmd,
        string $codice_cedi,
        int $now
    ): bool {
        $cedi = instantwin_cedi_normalize_codice($codice_cedi);
        if ($cedi === null) {
            return false;
        }

        instantwin_cedi_ensure_calendar_schema($pdo);

        $ctx = instantwin_cedi_resolve_pacing_context($pdo, $dateYmd, $cedi, $now);
        if ($ctx === null) {
            return false;
        }

        $plan = instantwin_cedi_build_pacing_plan(
            $ctx['day_start_ts'],
            $ctx['effective_end_ts'],
            $ctx['prizes_total_target'],
            $ctx['prizes_already_won'],
            $now
        );

        return instantwin_cedi_persist_free_slot_plan($pdo, $dateYmd, $cedi, $plan);
    }
}

if (!function_exists('instantwin_cedi_advance_after_win')) {
    /**
     * Après une vincita : persiste les fenêtres futures.
     * - À l'heure : grille fixe (index k+1), sans replan depuis now.
     * - En retard : rattrapage contigu à partir de la fin de la fenêtre gagnée.
     */
    function instantwin_cedi_advance_after_win(
        PDO $pdo,
        string $dateYmd,
        string $codice_cedi,
        int $now
    ): void {
        $cedi = instantwin_cedi_normalize_codice($codice_cedi);
        if ($cedi === null) {
            return;
        }

        instantwin_cedi_ensure_calendar_schema($pdo);

        $ctx = instantwin_cedi_resolve_pacing_context($pdo, $dateYmd, $cedi, $now);
        if ($ctx === null) {
            return;
        }

        $min = max(180, (int) INSTANTWIN_MIN_SLOT_INTERVAL_SECONDS);
        $ideal_step = instantwin_cedi_compute_ideal_pacing_step(
            $ctx['day_start_ts'],
            $ctx['effective_end_ts'],
            $ctx['prizes_total_target']
        );

        $behind = instantwin_cedi_is_pacing_behind_schedule(
            $ctx['day_start_ts'],
            $ctx['effective_end_ts'],
            $ctx['prizes_total_target'],
            $ctx['prizes_already_won'],
            $now,
            $ideal_step
        );

        if (!$behind) {
            $plan = instantwin_cedi_build_fixed_daily_windows(
                $ctx['day_start_ts'],
                $ctx['effective_end_ts'],
                $ctx['prizes_total_target'],
                $ctx['prizes_already_won'],
                $now,
                $min
            );
        } else {
            $catchup_start = $ctx['day_start_ts'];
            $stLast = $pdo->prepare('
                SELECT finestra_fine
                FROM cedi_calendario_time_slot
                WHERE data = :d AND codice_cedi = :cedi AND token_vincitore IS NOT NULL
                ORDER BY GREATEST(
                    COALESCE(UNIX_TIMESTAMP(data_tiraggio), 0),
                    slot_timestamp
                ) DESC
                LIMIT 1
            ');
            $stLast->execute([':d' => $dateYmd, ':cedi' => $cedi]);
            $lastWe = $stLast->fetchColumn();
            if ($lastWe !== false && $lastWe !== null) {
                $catchup_start = max($catchup_start, (int) $lastWe);
            } else {
                $catchup_start = max($catchup_start, $now);
            }

            $plan = instantwin_cedi_build_contiguous_pacing_plan(
                $ctx['day_start_ts'],
                $ctx['effective_end_ts'],
                $catchup_start,
                $ctx['prizes_remaining'],
                $now,
                $min
            );
        }

        instantwin_cedi_persist_free_slot_plan($pdo, $dateYmd, $cedi, $plan);
    }
}

/** @deprecated Utiliser instantwin_cedi_advance_after_win */
if (!function_exists('instantwin_cedi_replan_future_slots_after_win')) {
    function instantwin_cedi_replan_future_slots_after_win(
        PDO $pdo,
        string $dateYmd,
        string $codice_cedi,
        int $now
    ): void {
        instantwin_cedi_advance_after_win($pdo, $dateYmd, $codice_cedi, $now);
    }
}

if (!function_exists('instantwin_cedi_get_daily_remaining_for_calendar')) {
    function instantwin_cedi_get_daily_remaining_for_calendar(PDO $pdo, string $dateYmd, string $cedi): int
    {
        $skipSync = function_exists('instantwin_cedi_simulation_context_active')
            && instantwin_cedi_simulation_context_active();

        return instantwin_cedi_get_daily_remaining($pdo, $dateYmd, $cedi, $skipSync);
    }
}
