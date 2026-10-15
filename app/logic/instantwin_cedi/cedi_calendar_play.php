<?php

/**
 * Point d'entrée vincita Instant Win par Ce.Di. (calendrier dédié).
 */

if (!function_exists('instantwin_cedi_release_win_slot')) {
    function instantwin_cedi_release_win_slot(PDO $pdo, string $token, string $dateYmd, string $codice_cedi): void
    {
        $cedi = instantwin_cedi_normalize_codice($codice_cedi);
        if ($cedi === null || $token === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateYmd)) {
            return;
        }
        instantwin_cedi_ensure_calendar_schema($pdo);
        try {
            $pdo->prepare('
                UPDATE cedi_calendario_time_slot
                SET token_vincitore = NULL, data_tiraggio = NULL
                WHERE data = :d AND codice_cedi = :cedi AND token_vincitore = :tk
            ')->execute([':d' => $dateYmd, ':cedi' => $cedi, ':tk' => $token]);
        } catch (Exception $e) {
            error_log('instantwin_cedi_release_win_slot: ' . $e->getMessage());
        }
    }
}

if (!function_exists('instantwin_cedi_try_win')) {
    /**
     * @return array<string, mixed>
     */
    function instantwin_cedi_try_win(string $token, $simulated_timestamp = null)
    {
        global $c_pdo;
        if (!($c_pdo instanceof PDO)) {
            return INSTANTWIN_NO_WIN;
        }

        $check_time = $simulated_timestamp !== null ? (int) $simulated_timestamp : time();
        $current_date = date('Y-m-d', $check_time);

        $cedi = instantwin_cedi_resolve_for_token($c_pdo, $token);
        if ($cedi === null) {
            return INSTANTWIN_NO_WIN;
        }

        if (instantwin_cedi_get_daily_remaining_for_calendar($c_pdo, $current_date, $cedi) <= 0) {
            return INSTANTWIN_NO_WIN;
        }

        $slot_window = instantwin_cedi_find_slot_window($check_time, $cedi);
        if (!$slot_window) {
            return INSTANTWIN_NO_WIN;
        }

        $remaining = instantwin_cedi_get_daily_remaining_for_calendar($c_pdo, $current_date, $cedi);
        if (defined('MAX_DAILY_TARGET') && MAX_DAILY_TARGET > 0 && $remaining <= 0) {
            return INSTANTWIN_NO_WIN;
        }

        try {
            $ins_participants = $c_pdo->prepare('
                INSERT INTO cedi_calendario_time_slot
                (data, codice_cedi, slot_timestamp, finestra_inizio, finestra_fine, token_vincitore, partecipanti_totali, data_tiraggio)
                VALUES (:d, :cedi, :s, :ws, :we, NULL, 1, NULL)
                ON DUPLICATE KEY UPDATE partecipanti_totali = partecipanti_totali + 1
            ');
            $ins_participants->execute([
                ':d' => $current_date,
                ':cedi' => $cedi,
                ':s' => $slot_window['slot_timestamp'],
                ':ws' => $slot_window['window_start'],
                ':we' => $slot_window['window_end'],
            ]);
        } catch (Exception $e) {
            error_log('instantwin_cedi_try_win incremento partecipanti: ' . $e->getMessage());
        }

        if (function_exists('instantwin_should_block_first_fascia_claim')
            && instantwin_should_block_first_fascia_claim(
                $c_pdo,
                $token,
                $current_date,
                $slot_window,
                0,
                'cedi:' . $cedi
            )) {
            return INSTANTWIN_NO_WIN;
        }

        $data_tiraggio = date('Y-m-d H:i:s', $check_time);

        $ownsTx = false;
        if (!$c_pdo->inTransaction()) {
            $c_pdo->beginTransaction();
            $ownsTx = true;
        }

        try {
            $claim = $c_pdo->prepare('
                UPDATE cedi_calendario_time_slot
                SET token_vincitore = :tk, data_tiraggio = :tir
                WHERE data = :d AND codice_cedi = :cedi AND slot_timestamp = :s AND token_vincitore IS NULL
            ');
            $claim->execute([
                ':tk' => $token,
                ':tir' => $data_tiraggio,
                ':d' => $current_date,
                ':cedi' => $cedi,
                ':s' => $slot_window['slot_timestamp'],
            ]);
            if ($claim->rowCount() !== 1) {
                if ($ownsTx && $c_pdo->inTransaction()) {
                    $c_pdo->rollBack();
                }
                return INSTANTWIN_NO_WIN;
            }

            $prize = instantwin_cedi_assign_prize($token, $simulated_timestamp);
            if (empty($prize['has_won'])) {
                if ($ownsTx && $c_pdo->inTransaction()) {
                    $c_pdo->rollBack();
                }
                return INSTANTWIN_NO_WIN;
            }

            if ($ownsTx && $c_pdo->inTransaction()) {
                $c_pdo->commit();
            }

            if (function_exists('instantwin_cedi_advance_after_win')) {
                instantwin_cedi_advance_after_win($c_pdo, $current_date, $cedi, $check_time);
            }

            return $prize;
        } catch (Exception $e) {
            if ($ownsTx && $c_pdo->inTransaction()) {
                $c_pdo->rollBack();
            }
            error_log('instantwin_cedi_try_win: ' . $e->getMessage());
            return INSTANTWIN_NO_WIN;
        }
    }
}
