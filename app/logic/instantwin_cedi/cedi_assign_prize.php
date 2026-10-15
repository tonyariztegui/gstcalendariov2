<?php

/**
 * Assegnazione premio Instant Win in modalità Ce.Di. (filtri codice_cedi + budget settimanale).
 * Richiede instantwin/core.php e cedi_core.php già caricati.
 */

if (!function_exists('instantwin_cedi_assign_prize_rollback_if_own')) {
    /**
     * Rollback only when this call owns the transaction.
     */
    function instantwin_cedi_assign_prize_rollback_if_own(PDO $c_pdo, bool $already_in_transaction): void
    {
        if (!$already_in_transaction && $c_pdo->inTransaction()) {
            $c_pdo->rollBack();
        }
    }
}

if (!function_exists('instantwin_cedi_assign_prize_begin_transaction')) {
    /**
     * Begin a transaction unless one is already active.
     *
     * @return bool true if a transaction was already open
     */
    function instantwin_cedi_assign_prize_begin_transaction(PDO $c_pdo): bool
    {
        if ($c_pdo->inTransaction()) {
            return true;
        }
        $c_pdo->beginTransaction();
        return false;
    }
}

if (!function_exists('instantwin_cedi_assign_prize_resolve_participation_dates')) {
    /**
     * Resolve win_date and participation_date from simulation or participation row.
     *
     * @param array|false|null $part
     * @return array{win_date: string, participation_date: string}
     */
    function instantwin_cedi_assign_prize_resolve_participation_dates($part, $simulated_timestamp = null): array
    {
        if ($simulated_timestamp !== null) {
            return [
                'win_date' => date('Y-m-d H:i:s', $simulated_timestamp),
                'participation_date' => date('Y-m-d', $simulated_timestamp),
            ];
        }
        if ($part && !empty($part['data_partecipazione'])) {
            $participation_date_obj = new DateTime($part['data_partecipazione']);
            return [
                'win_date' => $part['data_partecipazione'],
                'participation_date' => $participation_date_obj->format('Y-m-d'),
            ];
        }
        return [
            'win_date' => date('Y-m-d H:i:s'),
            'participation_date' => date('Y-m-d'),
        ];
    }
}

if (!function_exists('instantwin_cedi_assign_prize_early_validation')) {
    /**
     * Contest range, Ce.Di. assignability, and optional daily-quota gate.
     * Rolls back on failure when this call owns the transaction.
     *
     * @return bool true if validation passed
     */
    function instantwin_cedi_assign_prize_early_validation(
        PDO $c_pdo,
        string $token,
        string $participation_date,
        bool $already_in_transaction
    ): bool {
        if (!isDateInContestRange($participation_date)) {
            instantwin_cedi_assign_prize_rollback_if_own($c_pdo, $already_in_transaction);
            error_log("assignPrize: tentativo di assegnare premio per data fuori range: $participation_date");
            return false;
        }

        if (!instantwin_cedi_can_assign_for_token($c_pdo, $token, $participation_date)) {
            instantwin_cedi_assign_prize_rollback_if_own($c_pdo, $already_in_transaction);
            return false;
        }

        if (function_exists('instantwin_cedi_daily_quota_enabled') && instantwin_cedi_daily_quota_enabled($c_pdo)) {
            $cediCode = instantwin_cedi_resolve_for_token($c_pdo, $token);
            if ($cediCode === null || instantwin_cedi_get_daily_remaining($c_pdo, $participation_date, $cediCode, false) <= 0) {
                instantwin_cedi_assign_prize_rollback_if_own($c_pdo, $already_in_transaction);
                return false;
            }
        }

        return true;
    }
}

if (!function_exists('instantwin_cedi_assign_prize_select_prize')) {
    /**
     * Select one available prize under Ce.Di. filters, locking the row.
     *
     * @return array|false|null
     */
    function instantwin_cedi_assign_prize_select_prize(PDO $c_pdo, string $token, string $participation_date)
    {
        $prize_mode = strtolower(trim((string) (getenv('INSTANTWIN_PRIZE_SELECTION_MODE') ?: 'highest_value')));
        $prize_mode = in_array($prize_mode, ['highest_value', 'lowest_id', 'random'], true) ? $prize_mode : 'highest_value';
        $order_by = match ($prize_mode) {
            'lowest_id' => 'id ASC',
            'random' => 'RAND()',
            default => 'valore DESC, id ASC',
        };

        $cediFilter = instantwin_cedi_assign_prize_filters($c_pdo, $token, $participation_date) ?? ['sql' => '', 'params' => []];
        $cediSql = $cediFilter['sql'] ?? '';
        $cediParams = $cediFilter['params'] ?? [];

        $stmt_prize = $c_pdo->prepare("
            SELECT p.id, p.valore
            FROM premi p
            WHERE p.quantita_assegnati < p.quantita_totale
            {$cediSql}
            ORDER BY {$order_by}
            LIMIT 1
            FOR UPDATE
        ");
        foreach ($cediParams as $k => $v) {
            $stmt_prize->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt_prize->execute();
        return $stmt_prize->fetch();
    }
}

if (!function_exists('instantwin_cedi_assign_prize_consume_legacy_daily_quota')) {
    /**
     * Consume one slot from quota_giornaliera (non–Ce.Di. daily quota path).
     */
    function instantwin_cedi_assign_prize_consume_legacy_daily_quota(
        PDO $c_pdo,
        string $participation_date,
        bool $is_last_day
    ): bool {
        createDailyQuotaTable();
        $prizes_remaining = getTotalPrizesAvailable();
        $allow_catchup = $is_last_day && $prizes_remaining > 0;
        $apply_max_daily_limit = (defined('MAX_DAILY_TARGET') && MAX_DAILY_TARGET > 0);

        if ($allow_catchup) {
            if ($apply_max_daily_limit) {
                $stmt_quota = $c_pdo->prepare('
                    UPDATE quota_giornaliera
                    SET assigned = assigned + 1
                    WHERE data = :date AND assigned < :max_daily
                ');
                $stmt_quota->execute([':date' => $participation_date, ':max_daily' => MAX_DAILY_TARGET]);
            } else {
                $stmt_quota = $c_pdo->prepare('
                    UPDATE quota_giornaliera
                    SET assigned = assigned + 1
                    WHERE data = :date
                ');
                $stmt_quota->execute([':date' => $participation_date]);
            }
        } elseif ($apply_max_daily_limit) {
            $stmt_quota = $c_pdo->prepare('
                UPDATE quota_giornaliera
                SET assigned = assigned + 1
                WHERE data = :date
                  AND assigned < target
                  AND assigned < :max_daily
            ');
            $stmt_quota->execute([':date' => $participation_date, ':max_daily' => MAX_DAILY_TARGET]);
        } else {
            $stmt_quota = $c_pdo->prepare('
                UPDATE quota_giornaliera
                SET assigned = assigned + 1
                WHERE data = :date AND assigned < target
            ');
            $stmt_quota->execute([':date' => $participation_date]);
        }

        return $stmt_quota->rowCount() === 1;
    }
}

if (!function_exists('instantwin_cedi_assign_prize_consume_daily_quota')) {
    /**
     * Consume daily quota (Ce.Di. or legacy). Rolls back on failure when owning the transaction.
     *
     * @return bool true if quota was consumed
     */
    function instantwin_cedi_assign_prize_consume_daily_quota(
        PDO $c_pdo,
        string $token,
        string $participation_date,
        bool $already_in_transaction
    ): bool {
        $contest_dates = getContestDates();
        $is_last_day = ($contest_dates && $participation_date === $contest_dates['end_date']);
        $force_recalculate = ($participation_date === date('Y-m-d')) || $is_last_day;
        initDailyTargetIfNeeded($participation_date, null, $force_recalculate);

        if (function_exists('instantwin_cedi_daily_quota_enabled') && instantwin_cedi_daily_quota_enabled($c_pdo)) {
            $cediCode = instantwin_cedi_resolve_for_token($c_pdo, $token);
            if ($cediCode === null || !instantwin_cedi_try_consume_daily_quota($c_pdo, $participation_date, $cediCode)) {
                instantwin_cedi_assign_prize_rollback_if_own($c_pdo, $already_in_transaction);
                return false;
            }
            return true;
        }

        if (!instantwin_cedi_assign_prize_consume_legacy_daily_quota($c_pdo, $participation_date, (bool) $is_last_day)) {
            instantwin_cedi_assign_prize_rollback_if_own($c_pdo, $already_in_transaction);
            return false;
        }
        return true;
    }
}

if (!function_exists('instantwin_cedi_assign_prize_increment_week_budget_or_rollback')) {
    /**
     * @return bool true if budget OK (or disabled)
     */
    function instantwin_cedi_assign_prize_increment_week_budget_or_rollback(
        PDO $c_pdo,
        int $prize_id_for_week,
        $iw_week_no,
        bool $already_in_transaction
    ): bool {
        if (instantwin_cedi_weekly_budget_enabled($c_pdo) && !instantwin_cedi_increment_week_budget($c_pdo, $prize_id_for_week, $iw_week_no)) {
            instantwin_cedi_assign_prize_rollback_if_own($c_pdo, $already_in_transaction);
            return false;
        }
        return true;
    }
}

if (!function_exists('instantwin_cedi_assign_prize_update_prize_quantity')) {
    function instantwin_cedi_assign_prize_update_prize_quantity(PDO $c_pdo, $prize_id): void
    {
        $stmt_update_prize = $c_pdo->prepare('
            UPDATE premi
            SET quantita_assegnati = quantita_assegnati + 1,
                is_active = CASE WHEN quantita_assegnati >= quantita_totale THEN 0 ELSE 1 END
            WHERE id = :prize_id
        ');
        $stmt_update_prize->execute([':prize_id' => $prize_id]);
    }
}

if (!function_exists('instantwin_cedi_assign_prize_commit_if_own')) {
    function instantwin_cedi_assign_prize_commit_if_own(PDO $c_pdo, bool $already_in_transaction): void
    {
        if (!$already_in_transaction && $c_pdo->inTransaction()) {
            $c_pdo->commit();
        }
    }
}

if (!function_exists('instantwin_cedi_assign_prize_deferred_voucher_win')) {
    /**
     * Win path when voucher code assignment is deferred until manager confirm.
     *
     * @param array $prize
     * @return array|int win payload or INSTANTWIN_NO_WIN
     */
    function instantwin_cedi_assign_prize_deferred_voucher_win(
        PDO $c_pdo,
        string $token,
        array $prize,
        string $win_date,
        int $prize_id_for_week,
        $iw_week_no,
        bool $already_in_transaction
    ) {
        $stmt_stock = $c_pdo->prepare('
            SELECT COUNT(*) FROM codici_premio
            WHERE prize_id = :prize_id AND used = 0 AND (isActive = 1 OR isActive IS NULL) AND id_codice_partecipazione IS NULL
        ');
        $stmt_stock->bindValue(':prize_id', $prize['id'], PDO::PARAM_INT);
        $stmt_stock->execute();
        if ((int) $stmt_stock->fetchColumn() < 1) {
            instantwin_cedi_assign_prize_rollback_if_own($c_pdo, $already_in_transaction);
            error_log('assignPrize: defer voucher â€” nessun codice_premio disponibile per prize_id ' . $prize['id']);
            return INSTANTWIN_NO_WIN;
        }

        $stmt_participation = $c_pdo->prepare('
            UPDATE partecipazioni
            SET has_won = 1, prize_id = :prize_id, prize_amount = :prize_value,
                win_date = :win_date, annullato = 0, confermato = 0,
                id_codice_premio = NULL
            WHERE token = :token
        ');
        $stmt_participation->execute([
            ':prize_id' => $prize['id'],
            ':prize_value' => $prize['valore'],
            ':win_date' => $win_date,
            ':token' => $token,
        ]);

        if (!instantwin_cedi_assign_prize_increment_week_budget_or_rollback($c_pdo, $prize_id_for_week, $iw_week_no, $already_in_transaction)) {
            return INSTANTWIN_NO_WIN;
        }

        instantwin_cedi_assign_prize_update_prize_quantity($c_pdo, $prize['id']);
        instantwin_cedi_assign_prize_commit_if_own($c_pdo, $already_in_transaction);

        return [
            'has_won' => true,
            'prize_id' => $prize['id'],
            'prize_value' => $prize['valore'],
            'prize_type' => 'Carta Prepagata Fisica',
            'codice_premio_id' => null,
            'codice_premio' => null,
            'pdf_path' => null,
        ];
    }
}

if (!function_exists('instantwin_cedi_assign_prize_resolve_id_codice_partecipazione')) {
    /**
     * @return int|null
     */
    function instantwin_cedi_assign_prize_resolve_id_codice_partecipazione(PDO $c_pdo, $codice_partecipazione_value)
    {
        if (!$codice_partecipazione_value) {
            return null;
        }
        $stmt_cp = $c_pdo->prepare('SELECT id FROM codici_partecipazione WHERE codice = :codice LIMIT 1');
        $stmt_cp->bindValue(':codice', $codice_partecipazione_value, PDO::PARAM_STR);
        $stmt_cp->execute();
        $cp_row = $stmt_cp->fetch();
        return $cp_row ? (int) $cp_row['id'] : null;
    }
}

if (!function_exists('instantwin_cedi_assign_prize_immediate_codice_premio_win')) {
    /**
     * Win path that immediately assigns a codice_premio.
     *
     * @param array $prize
     * @return array|int win payload or INSTANTWIN_NO_WIN
     */
    function instantwin_cedi_assign_prize_immediate_codice_premio_win(
        PDO $c_pdo,
        string $token,
        array $prize,
        string $win_date,
        $participation_id,
        $codice_partecipazione_value,
        int $prize_id_for_week,
        $iw_week_no,
        bool $already_in_transaction
    ) {
        $id_codice_partecipazione = instantwin_cedi_assign_prize_resolve_id_codice_partecipazione($c_pdo, $codice_partecipazione_value);

        $stmt_codice_premio = $c_pdo->prepare('
            SELECT id, codice, pdf_path
            FROM codici_premio
            WHERE prize_id = :prize_id
              AND used = 0
              AND (isActive = 1 OR isActive IS NULL)
              AND id_codice_partecipazione IS NULL
            LIMIT 1
            FOR UPDATE
        ');
        $stmt_codice_premio->bindValue(':prize_id', $prize['id'], PDO::PARAM_INT);
        $stmt_codice_premio->execute();
        $codice_premio_row = $stmt_codice_premio->fetch();

        if (!$codice_premio_row) {
            instantwin_cedi_assign_prize_rollback_if_own($c_pdo, $already_in_transaction);
            error_log("assignPrize: Nessun codice_premio disponibile per prize_id " . $prize['id']);
            return INSTANTWIN_NO_WIN;
        }

        $codice_premio_id = (int) $codice_premio_row['id'];
        $codice_premio_code = $codice_premio_row['codice'];
        $codice_premio_pdf_path = !empty($codice_premio_row['pdf_path']) ? trim((string) $codice_premio_row['pdf_path']) : null;

        $stmt_update_codice_premio = $c_pdo->prepare('
            UPDATE codici_premio
            SET id_codice_partecipazione = :id_codice_partecipazione,
                id_partecipazione = :id_partecipazione,
                used = 1,
                isActive = 0,
                data_assegnazione = NOW()
            WHERE id = :codice_premio_id
        ');
        $stmt_update_codice_premio->bindValue(':id_codice_partecipazione', $id_codice_partecipazione, PDO::PARAM_INT);
        $stmt_update_codice_premio->bindValue(':id_partecipazione', $participation_id, PDO::PARAM_INT);
        $stmt_update_codice_premio->bindValue(':codice_premio_id', $codice_premio_id, PDO::PARAM_INT);
        $stmt_update_codice_premio->execute();

        require_once __DIR__ . '/../prize_image_generator.php';
        prize_image_try_generate_after_assign(
            $c_pdo,
            $codice_premio_id,
            (int) $prize['id'],
            trim((string) $codice_premio_code)
        );

        $stmt_participation = $c_pdo->prepare('
            UPDATE partecipazioni
            SET has_won = 1, prize_id = :prize_id, prize_amount = :prize_value,
                win_date = :win_date, annullato = 0, confermato = 0,
                id_codice_premio = :id_codice_premio
            WHERE token = :token
        ');
        $stmt_participation->execute([
            ':prize_id' => $prize['id'],
            ':prize_value' => $prize['valore'],
            ':win_date' => $win_date,
            ':token' => $token,
            ':id_codice_premio' => $codice_premio_id,
        ]);

        if (!instantwin_cedi_assign_prize_increment_week_budget_or_rollback($c_pdo, $prize_id_for_week, $iw_week_no, $already_in_transaction)) {
            return INSTANTWIN_NO_WIN;
        }

        instantwin_cedi_assign_prize_update_prize_quantity($c_pdo, $prize['id']);
        instantwin_cedi_assign_prize_commit_if_own($c_pdo, $already_in_transaction);

        return [
            'has_won' => true,
            'prize_id' => $prize['id'],
            'prize_value' => $prize['valore'],
            'prize_type' => 'Carta Prepagata Fisica',
            'codice_premio_id' => $codice_premio_id,
            'codice_premio' => $codice_premio_code,
            'pdf_path' => $codice_premio_pdf_path,
        ];
    }
}

if (!function_exists('instantwin_cedi_assign_prize_load_participation')) {
    /**
     * @return array|false
     */
    function instantwin_cedi_assign_prize_load_participation(PDO $c_pdo, string $token)
    {
        $stmt_part = $c_pdo->prepare('SELECT id, data_partecipazione, codice_partecipazione FROM partecipazioni WHERE token = :token');
        $stmt_part->bindValue(':token', $token, PDO::PARAM_STR);
        $stmt_part->execute();
        return $stmt_part->fetch();
    }
}

if (!function_exists('instantwin_cedi_assign_prize')) {
    function instantwin_cedi_assign_prize($token, $simulated_timestamp = null)
    {
        global $c_pdo;
        /** @var PDO $c_pdo */
        $already_in_transaction = false;

        try {
            $already_in_transaction = instantwin_cedi_assign_prize_begin_transaction($c_pdo);

            $c_pdo->exec('UPDATE premi SET is_active = 1 WHERE quantita_assegnati < quantita_totale');

            $defer_voucher = instantwin_should_defer_voucher_code_until_manager_confirm();

            $part = instantwin_cedi_assign_prize_load_participation($c_pdo, $token);
            $participation_id = $part ? (int) $part['id'] : null;
            $codice_partecipazione_value = $part ? ($part['codice_partecipazione'] ?? null) : null;

            $dates = instantwin_cedi_assign_prize_resolve_participation_dates($part, $simulated_timestamp);
            $win_date = $dates['win_date'];
            $participation_date = $dates['participation_date'];

            if (!instantwin_cedi_assign_prize_early_validation($c_pdo, $token, $participation_date, $already_in_transaction)) {
                return INSTANTWIN_NO_WIN;
            }

            $prize = instantwin_cedi_assign_prize_select_prize($c_pdo, $token, $participation_date);
            if (!$prize) {
                instantwin_cedi_assign_prize_rollback_if_own($c_pdo, $already_in_transaction);
                return INSTANTWIN_NO_WIN;
            }

            $prize_id_for_week = (int) ($prize['id'] ?? 0);
            $iw_week_no = instantwin_cedi_week_number_for_date($participation_date);

            if (!instantwin_cedi_assign_prize_consume_daily_quota($c_pdo, $token, $participation_date, $already_in_transaction)) {
                return INSTANTWIN_NO_WIN;
            }

            if ($defer_voucher) {
                return instantwin_cedi_assign_prize_deferred_voucher_win(
                    $c_pdo,
                    $token,
                    $prize,
                    $win_date,
                    $prize_id_for_week,
                    $iw_week_no,
                    $already_in_transaction
                );
            }

            return instantwin_cedi_assign_prize_immediate_codice_premio_win(
                $c_pdo,
                $token,
                $prize,
                $win_date,
                $participation_id,
                $codice_partecipazione_value,
                $prize_id_for_week,
                $iw_week_no,
                $already_in_transaction
            );
        } catch (Exception $e) {
            instantwin_cedi_assign_prize_rollback_if_own($c_pdo, $already_in_transaction);
            error_log("Errore assignPrize: " . $e->getMessage());
            return INSTANTWIN_NO_WIN;
        }
    }
}
