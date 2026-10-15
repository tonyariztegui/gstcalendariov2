<?php
/**
 * Logica condivisa conferma / annullamento vincita manager (Conferma manager).
 * Usata da confirm_win.php, cancel_win.php e validate_scontrino.php.
 */

if (!function_exists('cancel_win_decrement_pdv_quota')) {
    /**
     * Decremento quota PDV senza caricare functions_instantwin_pdv (evita DDL implicit commit in transazione).
     */
    function cancel_win_decrement_pdv_quota(PDO $pdo, string $codicePdv, string $date): void
    {
        $pdv = trim($codicePdv);
        if ($pdv === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return;
        }
        $raw = getenv('INSTANTWIN_PDV_DAILY_PACING_ENABLED');
        if ($raw !== false && trim((string) $raw) !== '') {
            $enabled = filter_var($raw, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($enabled === false) {
                return;
            }
        }
        try {
            $pdo->prepare('
                UPDATE `pdv_quota_giornaliera`
                SET `assigned` = GREATEST(`assigned` - 1, 0)
                WHERE `codice_pdv` = :p AND `data` = :d AND `assigned` > 0
            ')->execute([':p' => $pdv, ':d' => $date]);
        } catch (Throwable $e) {
            error_log('cancel_win pdv_quota_giornaliera: ' . $e->getMessage());
        }
    }
}

if (!function_exists('manager_confirm_participation_win')) {
    /**
     * @return array{success:bool,message:string,http_code:int}
     */
    function manager_confirm_participation_win(PDO $connection, int $participationId): array
    {
        global $contest_mode;

        if ($participationId <= 0) {
            return ['success' => false, 'message' => 'ID partecipazione non valido.', 'http_code' => 400];
        }

        try {
            $connection->beginTransaction();

            $stmt = $connection->prepare("
                SELECT
                    p.HAS_WON,
                    p.CONFERMATO,
                    p.ANNULLATO,
                    p.PRIZE_AMOUNT,
                    p.PRIZE_ID,
                    p.ID_CODICE_PREMIO,
                    p.ID_PDV_CONCORSO,
                    p.INSEGNA,
                    u.NOME,
                    u.COGNOME,
                    u.EMAIL,
                    u.PREFISSO,
                    u.CELLULARE
                FROM partecipazioni p
                LEFT JOIN utenti u ON u.ID_CLIENTE = p.ID_CLIENTE
                WHERE p.ID = ?
                FOR UPDATE
            ");
            $stmt->execute([$participationId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $row = array_change_key_case($row, CASE_LOWER);
            }

            if (!$row) {
                $connection->rollBack();
                return ['success' => false, 'message' => 'Partecipazione non trovata.', 'http_code' => 404];
            }

            if ((int) ($row['has_won'] ?? 0) !== 1) {
                $connection->rollBack();
                return ['success' => false, 'message' => 'Solo le partecipazioni vincenti possono essere confermate.', 'http_code' => 400];
            }

            if ((int) ($row['annullato'] ?? 0) === 1) {
                $connection->rollBack();
                return ['success' => false, 'message' => 'La vittoria risulta annullata e non puo\' essere confermata.', 'http_code' => 400];
            }

            if ((int) ($row['confermato'] ?? 0) === 1) {
                $connection->rollBack();
                return ['success' => true, 'message' => 'La vittoria e\' gia\' stata confermata.', 'http_code' => 200];
            }

            $email = trim((string) ($row['email'] ?? ''));
            if ($email === '') {
                $connection->rollBack();
                return ['success' => false, 'message' => 'Email del partecipante non disponibile. Impossibile inviare la conferma.', 'http_code' => 400];
            }

            $name = trim((string) ($row['nome'] ?? ''));
            if ($name === '') {
                $name = trim((string) ($row['cognome'] ?? ''));
            }
            if ($name === '') {
                $name = strtok($email, '@') ?: 'Partecipante';
            }

            $prizeAmount = $row['prize_amount'] ?? null;
            if ($prizeAmount === null || $prizeAmount === '' || (float) $prizeAmount <= 0) {
                if (!empty($row['prize_id'])) {
                    $stmtPrize = $connection->prepare('SELECT VALORE FROM premi WHERE ID = ?');
                    $stmtPrize->execute([(int) $row['prize_id']]);
                    $fallbackPrize = $stmtPrize->fetchColumn();
                    if ($fallbackPrize !== false) {
                        $prizeAmount = $fallbackPrize;
                    }
                }
            }

            if ($prizeAmount === null || $prizeAmount === '') {
                $prizeAmount = 0;
            }

            $codice_premio = null;
            $pdf_path = null;
            $id_codice_premio = !empty($row['id_codice_premio']) ? (int) $row['id_codice_premio'] : null;

            email_send_force(true);

            if (($contest_mode ?? '') === 'instantwin' && !empty($row['prize_id'])) {
                $assigned = instantwin_assign_voucher_code_on_manager_confirm($connection, $participationId);
                $codice_premio = isset($assigned['codice']) && $assigned['codice'] !== '' ? trim((string) $assigned['codice']) : null;
                $pdf_path = !empty($assigned['pdf_path']) ? trim((string) $assigned['pdf_path']) : null;
                if (!empty($assigned['codice_premio_id'])) {
                    $id_codice_premio = (int) $assigned['codice_premio_id'];
                }
            } elseif ($id_codice_premio) {
                $stmtCodice = $connection->prepare('SELECT codice, pdf_path FROM codici_premio WHERE id = ? LIMIT 1');
                $stmtCodice->execute([$id_codice_premio]);
                $codiceRow = $stmtCodice->fetch(PDO::FETCH_ASSOC);
                if ($codiceRow) {
                    $codice_premio = !empty($codiceRow['codice']) ? trim((string) $codiceRow['codice']) : null;
                    $pdf_path = !empty($codiceRow['pdf_path']) ? trim((string) $codiceRow['pdf_path']) : null;
                }
            }

            $emailSent = sendConfirmedWinEmail($email, $name, $prizeAmount, $codice_premio, $pdf_path, [
                'id_pdv_concorso' => (string) ($row['id_pdv_concorso'] ?? ''),
                'insegna' => (string) ($row['insegna'] ?? ''),
                'prize_id' => (int) ($row['prize_id'] ?? 0),
                'id_codice_premio' => (int) ($id_codice_premio ?? 0),
            ]);

            if (!$emailSent) {
                throw new RuntimeException('Invio email premio non riuscito. Controllare la configurazione SMTP e riprovare.');
            }

            $update = $connection->prepare("
                UPDATE partecipazioni
                SET CONFERMATO = 1, ANNULLATO = 0, email_inviato = 1, email_error = NULL
                WHERE ID = ?
            ");
            $update->execute([$participationId]);

            $connection->commit();

            try {
                require_once __DIR__ . '/../../../app/inc/sms_mailup.inc.php';
                gst_sms_try_send_for_participation($connection, $participationId, 'instantwin_prize', [
                    'nome' => $name,
                    'prefisso' => (string) ($row['prefisso'] ?? ''),
                    'cellulare' => (string) ($row['cellulare'] ?? ''),
                    'codice' => (string) ($codice_premio ?? ''),
                ]);
            } catch (Throwable $smsEx) {
                error_log('confirm_win SMS: ' . $smsEx->getMessage());
            }

            return ['success' => true, 'message' => 'Vittoria confermata e email inviata con successo.', 'http_code' => 200];
        } catch (Throwable $e) {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }
            if ($participationId > 0) {
                try {
                    $errMsg = mb_substr((string) $e->getMessage(), 0, 500);
                    $connection->prepare('UPDATE partecipazioni SET email_inviato = 0, email_error = ? WHERE id = ?')
                        ->execute([$errMsg, $participationId]);
                } catch (Throwable $ignored) {
                }
            }
            return ['success' => false, 'message' => 'Errore durante la conferma: ' . $e->getMessage(), 'http_code' => 500];
        }
    }
}

if (!function_exists('manager_cancel_participation_win')) {
    /**
     * @return array{success:bool,message:string,http_code:int}
     */
    function manager_cancel_participation_win(PDO $connection, int $participationId): array
    {
        global $contest_mode, $contest_config;

        if ($participationId <= 0) {
            return ['success' => false, 'message' => 'ID partecipazione non valido.', 'http_code' => 400];
        }

        try {
            $connection->beginTransaction();

            $stmt = $connection->prepare('
                SELECT
                    p.has_won,
                    p.annullato,
                    p.confermato,
                    p.prize_id,
                    p.prize_amount,
                    p.win_date,
                    p.data_partecipazione,
                    p.id_pdv_concorso,
                    p.id_codice_premio,
                    p.codice_partecipazione,
                    u.nome,
                    u.cognome,
                    u.email,
                    u.prefisso,
                    u.cellulare
                FROM partecipazioni p
                LEFT JOIN utenti u ON u.id_cliente = p.id_cliente
                WHERE p.id = ?
                FOR UPDATE
            ');
            $stmt->execute([$participationId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $row = array_change_key_case($row, CASE_LOWER);
            }

            if (!$row) {
                $connection->rollBack();
                return ['success' => false, 'message' => 'Partecipazione non trovata.', 'http_code' => 404];
            }

            if ((int) ($row['annullato'] ?? 0) === 1) {
                $connection->rollBack();
                return ['success' => true, 'message' => 'La vittoria risulta gia\' annullata.', 'http_code' => 200];
            }

            if ((int) ($row['has_won'] ?? 0) !== 1) {
                $connection->rollBack();
                return ['success' => false, 'message' => 'Solo le partecipazioni vincenti possono essere annullate.', 'http_code' => 400];
            }

            $email = trim((string) ($row['email'] ?? ''));
            $name = trim((string) ($row['nome'] ?? ''));
            if ($name === '') {
                $name = trim((string) ($row['cognome'] ?? ''));
            }
            if ($name === '') {
                $name = $email !== '' ? strtok($email, '@') : 'Partecipante';
            }
            $prizeId = !empty($row['prize_id']) ? (int) $row['prize_id'] : null;
            $isConfirmed = (int) ($row['confermato'] ?? 0) === 1;
            $prizeAmount = $row['prize_amount'] ?? null;
            if ($prizeAmount === null || $prizeAmount === '' || (float) $prizeAmount <= 0) {
                if ($prizeId !== null && $prizeId > 0) {
                    $stmtPrize = $connection->prepare('SELECT valore FROM premi WHERE id = ?');
                    $stmtPrize->execute([$prizeId]);
                    $fallbackPrize = $stmtPrize->fetchColumn();
                    if ($fallbackPrize !== false) {
                        $prizeAmount = $fallbackPrize;
                    }
                }
            }
            if ($prizeAmount === null || $prizeAmount === '') {
                $prizeAmount = 0;
            }

            $idCodicePremio = !empty($row['id_codice_premio']) ? (int) $row['id_codice_premio'] : 0;
            if ($idCodicePremio > 0) {
                $connection->prepare('
                    UPDATE codici_premio
                    SET used = 0,
                        isActive = 1,
                        id_partecipazione = NULL,
                        id_codice_partecipazione = NULL,
                        data_assegnazione = NULL
                    WHERE id = ?
                ')->execute([$idCodicePremio]);
            } else {
                $connection->prepare('
                    UPDATE codici_premio
                    SET used = 0,
                        isActive = 1,
                        id_partecipazione = NULL,
                        id_codice_partecipazione = NULL,
                        data_assegnazione = NULL
                    WHERE id_partecipazione = ?
                ')->execute([$participationId]);
            }

            $connection->prepare('
                UPDATE partecipazioni
                SET
                    has_won = 0,
                    confermato = 0,
                    annullato = 1,
                    prize_id = NULL,
                    prize_amount = NULL,
                    id_codice_premio = NULL
                WHERE id = ?
            ')->execute([$participationId]);

            $isInstantWin = isset($contest_mode) && strtolower((string) $contest_mode) === 'instantwin';
            $iwPerPdv = $isInstantWin && !empty($contest_config['instantwin_per_pdv'] ?? false);
            $iwPerCedi = $isInstantWin && !empty($contest_config['instantwin_per_cedi'] ?? false);
            $pdvCod = trim((string) ($row['id_pdv_concorso'] ?? ''));
            $quotaDate = null;
            if ($isInstantWin) {
                $winRaw = $row['win_date'] ?? null;
                $partRaw = $row['data_partecipazione'] ?? null;
                if ($winRaw !== null && $winRaw !== '') {
                    $quotaDate = substr((string) $winRaw, 0, 10);
                } elseif ($partRaw !== null && $partRaw !== '') {
                    $quotaDate = substr((string) $partRaw, 0, 10);
                }
                if ($quotaDate !== null && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $quotaDate)) {
                    $quotaDate = null;
                }
            }

            if ($prizeId !== null && $prizeId > 0 && !$isConfirmed) {
                $connection->prepare('
                    UPDATE premi
                    SET
                        quantita_assegnati = GREATEST(quantita_assegnati - 1, 0),
                        is_active = CASE
                            WHEN GREATEST(quantita_assegnati - 1, 0) < quantita_totale THEN 1
                            ELSE 0
                        END
                    WHERE id = ?
                ')->execute([$prizeId]);

                if ($iwPerPdv && $pdvCod !== '') {
                    $connection->prepare('
                        UPDATE `pdv_premio_budget`
                        SET `budget_assigned` = GREATEST(`budget_assigned` - 1, 0),
                            `is_active` = CASE
                                WHEN GREATEST(`budget_assigned` - 1, 0) < `budget_total` THEN 1
                                ELSE 0
                            END
                        WHERE `codice_pdv` = :cod
                          AND `prize_id` = :pid
                          AND `budget_assigned` > 0
                    ')->execute([':cod' => $pdvCod, ':pid' => $prizeId]);
                }

                if ($isInstantWin && $quotaDate !== null) {
                    $useCediDaily = false;
                    if ($iwPerCedi && file_exists(__DIR__ . '/../../../app/logic/instantwin_cedi/cedi_core.php')) {
                        require_once __DIR__ . '/../../../app/logic/instantwin_cedi/cedi_core.php';
                        require_once __DIR__ . '/../../../app/logic/instantwin_cedi/instantwin_cedi.php';
                        $useCediDaily = function_exists('instantwin_cedi_daily_quota_enabled')
                            && instantwin_cedi_daily_quota_enabled($connection);
                    }
                    if ($useCediDaily) {
                        $cediCode = null;
                        $stCedi = $connection->prepare('
                            SELECT p.`codice_partecipazione`, pv.`codice_cedi`
                            FROM `partecipazioni` p
                            LEFT JOIN `punti_vendita` pv ON TRIM(pv.`codice`) = TRIM(p.`id_pdv_concorso`)
                            WHERE p.`id` = ?
                            LIMIT 1
                        ');
                        $stCedi->execute([$participationId]);
                        $cediRow = $stCedi->fetch(PDO::FETCH_ASSOC);
                        if ($cediRow) {
                            $cp = trim((string) ($cediRow['codice_partecipazione'] ?? ''));
                            if (strlen($cp) >= 1 && ctype_digit($cp[0])) {
                                $cediCode = function_exists('instantwin_cedi_normalize_codice')
                                    ? instantwin_cedi_normalize_codice($cp[0])
                                    : $cp[0];
                            }
                            if ($cediCode === null) {
                                $cediCode = function_exists('instantwin_cedi_normalize_codice')
                                    ? instantwin_cedi_normalize_codice((string) ($cediRow['codice_cedi'] ?? ''))
                                    : null;
                            }
                        }
                        if ($cediCode !== null && $cediCode !== '') {
                            $connection->prepare('
                                UPDATE `cedi_quota_giornaliera`
                                SET `assigned` = GREATEST(`assigned` - 1, 0)
                                WHERE `data` = :d AND `codice_cedi` = :c AND `assigned` > 0
                            ')->execute([':d' => $quotaDate, ':c' => $cediCode]);
                        }
                    } else {
                        $connection->prepare('
                            UPDATE `quota_giornaliera`
                            SET `assigned` = GREATEST(`assigned` - 1, 0)
                            WHERE `data` = :d
                              AND `assigned` > 0
                        ')->execute([':d' => $quotaDate]);
                    }

                    if ($iwPerPdv && $pdvCod !== '') {
                        cancel_win_decrement_pdv_quota($connection, $pdvCod, $quotaDate);
                    }

                    if ($iwPerCedi && $quotaDate !== null) {
                        $winToken = trim((string) ($row['token'] ?? ''));
                        $cediForSlot = null;
                        $cp = trim((string) ($row['codice_partecipazione'] ?? ''));
                        if (strlen($cp) >= 1 && ctype_digit($cp[0]) && function_exists('instantwin_cedi_normalize_codice')) {
                            $cediForSlot = instantwin_cedi_normalize_codice($cp[0]);
                        }
                        if ($cediForSlot === null && $pdvCod !== '') {
                            $stPv = $connection->prepare('SELECT codice_cedi FROM punti_vendita WHERE TRIM(codice) = TRIM(?) LIMIT 1');
                            $stPv->execute([$pdvCod]);
                            $pvRow = $stPv->fetch(PDO::FETCH_ASSOC);
                            if ($pvRow && function_exists('instantwin_cedi_normalize_codice')) {
                                $cediForSlot = instantwin_cedi_normalize_codice((string) ($pvRow['codice_cedi'] ?? ''));
                            }
                        }
                        if ($winToken !== '' && $cediForSlot !== null && function_exists('instantwin_cedi_release_win_slot')) {
                            instantwin_cedi_release_win_slot($connection, $winToken, $quotaDate, $cediForSlot);
                        }
                    }
                }
            }

            $connection->commit();

            $emailSent = false;
            if ($email !== '' && function_exists('instantwin_email_sending_enabled') && instantwin_email_sending_enabled('instantwin_cancel')) {
                try {
                    email_send_force(true);
                    $emailSent = sendCancelledWinEmail($email, $name, $prizeAmount);
                } catch (Throwable $mailEx) {
                    error_log('Invio email annullamento fallito per partecipazione ' . $participationId . ': ' . $mailEx->getMessage());
                }
            }

            if ($emailSent) {
                $connection->prepare('UPDATE partecipazioni SET email_inviato = 1, email_error = NULL WHERE id = ?')
                    ->execute([$participationId]);
            } elseif ($email !== '') {
                $connection->prepare('UPDATE partecipazioni SET email_error = ? WHERE id = ?')
                    ->execute([
                        'Invio email di annullamento non riuscito (verificare SMTP).',
                        $participationId,
                    ]);
            }

            try {
                require_once __DIR__ . '/../../../app/inc/sms_mailup.inc.php';
                gst_sms_try_send_for_participation($connection, $participationId, 'instantwin_cancel', [
                    'nome' => $name,
                    'prefisso' => (string) ($row['prefisso'] ?? ''),
                    'cellulare' => (string) ($row['cellulare'] ?? ''),
                ]);
            } catch (Throwable $smsEx) {
                error_log('cancel_win SMS: ' . $smsEx->getMessage());
            }

            return ['success' => true, 'message' => 'Vittoria annullata con successo.', 'http_code' => 200];
        } catch (Throwable $e) {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }
            error_log('cancel_win: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Errore durante l\'annullamento: ' . $e->getMessage(), 'http_code' => 500];
        }
    }
}
