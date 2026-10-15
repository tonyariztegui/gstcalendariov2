<?php
/**
 * Invio email dopo completamento quiz (estrazione o Instant Win).
 */
require_once __DIR__ . '/../registration_email_helpers.php';

if (!function_exists('quiz_flow_send_completion_email')) {
    /**
     * @param array<string, mixed>|null $instantwinResult
     */
    function quiz_flow_send_completion_email(PDO $pdo, string $token, ?array $instantwinResult, bool $quizPassed): void
    {
        global $contest_config, $contest_mode;

        $stmt = $pdo->prepare('
            SELECT u.nome, u.email, p.id AS participation_id, p.has_won, p.prize_id, p.prize_amount,
                   p.id_codice_premio, p.id_pdv_concorso, p.insegna
            FROM utenti u
            INNER JOIN partecipazioni p ON p.id_cliente = u.id_cliente
            WHERE p.token = :token
            LIMIT 1
        ');
        $stmt->bindValue(':token', $token, PDO::PARAM_STR);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!is_array($row)) {
            return;
        }

        $participationId = (int) ($row['participation_id'] ?? 0);
        if ($participationId <= 0) {
            return;
        }

        $email = trim((string) ($row['email'] ?? ''));
        $nome = trim((string) ($row['nome'] ?? ''));
        if ($email === '') {
            return;
        }

        include_once __DIR__ . '/../../../public/assets/mail/email_system.php';

        $emailSent = false;
        $emailError = null;
        $autoConfirmed = false;
        $smsType = null;
        $smsCodice = '';

        if (($contest_mode ?? '') === 'instantwin') {
            $requireReceipt = !empty($contest_config['require_receipt']);
            $autoPrizeFlow = $requireReceipt && !empty($contest_config['instantwin_auto_prize_with_receipt']);
            $codicePdv = (string) ($row['id_pdv_concorso'] ?? '');
            $insegnaCoop = (string) ($row['insegna'] ?? '');

            if (!$quizPassed || !registration_instantwin_is_win($instantwinResult)) {
                $lossResult = registration_send_instantwin_loss_email($email, $nome);
                $smsType = 'instantwin_loss';
                registration_record_email_attempt(
                    $lossResult,
                    $emailSent,
                    $emailError,
                    'Errore invio email di non vincita Instant Win'
                );
            } else {
                $partWinState = registration_load_participation_win_state($pdo, $participationId);
                $hasWonInDb = is_array($partWinState) && !empty($partWinState['has_won']);

                if (registration_instantwin_is_win($instantwinResult) && !$hasWonInDb) {
                    try {
                        $stmtUpdateWin = $pdo->prepare('
                            UPDATE partecipazioni
                            SET has_won = 1,
                                prize_id = :prize_id,
                                prize_amount = :prize_value,
                                win_date = NOW()
                            WHERE id = :participation_id
                        ');
                        $stmtUpdateWin->execute([
                            ':prize_id' => $instantwinResult['prize_id'] ?? null,
                            ':prize_value' => $instantwinResult['prize_value'] ?? 0,
                            ':participation_id' => $participationId,
                        ]);
                        $partWinState = registration_load_participation_win_state($pdo, $participationId);
                        $hasWonInDb = is_array($partWinState) && !empty($partWinState['has_won']);
                    } catch (Throwable $e) {
                        error_log('quiz_flow_send_completion_email update vincita #' . $participationId . ': ' . $e->getMessage());
                    }
                }

                if ($hasWonInDb) {
                    if (!registration_instantwin_is_win($instantwinResult) && is_array($partWinState)) {
                        $instantwinResult = [
                            'has_won' => true,
                            'prize_id' => (int) ($partWinState['prize_id'] ?? 0),
                            'prize_value' => (float) ($partWinState['prize_amount'] ?? 0),
                            'prize_type' => 'Carta Prepagata Fisica',
                        ];
                    }

                    $prizeValue = (float) ($instantwinResult['prize_value'] ?? ($partWinState['prize_amount'] ?? 0));
                    $codicePremio = $instantwinResult['codice_premio'] ?? null;
                    $pdfPath = $instantwinResult['pdf_path'] ?? null;
                    $idCodicePremio = !empty($partWinState['id_codice_premio']) ? (int) $partWinState['id_codice_premio'] : null;
                    if ($idCodicePremio) {
                        $stmtCp = $pdo->prepare('SELECT codice, pdf_path FROM codici_premio WHERE id = ? LIMIT 1');
                        $stmtCp->execute([$idCodicePremio]);
                        $cpRow = $stmtCp->fetch(PDO::FETCH_ASSOC);
                        if (is_array($cpRow)) {
                            if ($codicePremio === null && !empty($cpRow['codice'])) {
                                $codicePremio = trim((string) $cpRow['codice']);
                            }
                            if ($pdfPath === null && !empty($cpRow['pdf_path'])) {
                                $pdfPath = trim((string) $cpRow['pdf_path']);
                            }
                        }
                    }

                    try {
                        $winEmail = registration_apply_instantwin_win_email(
                            $email,
                            $nome,
                            $prizeValue,
                            $codicePremio,
                            $pdfPath,
                            $autoPrizeFlow,
                            $codicePdv,
                            $insegnaCoop,
                            [
                                'prize_id' => (int) ($instantwinResult['prize_id'] ?? ($partWinState['prize_id'] ?? 0)),
                                'id_codice_premio' => (int) ($idCodicePremio ?: ($instantwinResult['codice_premio_id'] ?? 0)),
                            ]
                        );
                        registration_record_email_attempt(
                            $winEmail['result'],
                            $emailSent,
                            $emailError,
                            'Errore invio email vincita Instant Win'
                        );
                        $smsType = registration_resolve_instantwin_win_sms_type($autoPrizeFlow);
                        $smsCodice = (string) ($codicePremio ?? '');
                        if ($emailSent && ($winEmail['type'] ?? null) === 'instantwin_prize') {
                            $autoConfirmed = true;
                        }
                    } catch (Throwable $e) {
                        error_log('quiz_flow_send_completion_email vincita #' . $participationId . ': ' . $e->getMessage());
                        $emailError = 'Errore invio email vincita Instant Win';
                    }
                } else {
                    $lossResult = registration_send_instantwin_loss_email($email, $nome);
                    $smsType = 'instantwin_loss';
                    registration_record_email_attempt(
                        $lossResult,
                        $emailSent,
                        $emailError,
                        'Errore invio email di non vincita Instant Win'
                    );
                }
            }
        } else {
            if (function_exists('should_send_email_type') && should_send_email_type('partecipazione') && function_exists('sendPartecipazioneEmail')) {
                $emailSent = (bool) sendPartecipazioneEmail($email, $nome);
                $smsType = 'partecipazione';
                if (!$emailSent) {
                    $emailError = 'Errore invio email di conferma partecipazione';
                }
            } else {
                $smsType = 'partecipazione';
            }
        }

        if ($autoConfirmed) {
            $stmtEmail = $pdo->prepare('
                UPDATE partecipazioni
                SET confermato = 1,
                    annullato = 0,
                    email_inviato = :sent,
                    email_error = :error
                WHERE id = :participation_id
            ');
        } else {
            $stmtEmail = $pdo->prepare('
                UPDATE partecipazioni
                SET email_inviato = :sent, email_error = :error
                WHERE id = :participation_id
            ');
        }
        $stmtEmail->bindValue(':sent', $emailSent ? 1 : 0, PDO::PARAM_INT);
        $stmtEmail->bindValue(':error', $emailError ?? '', PDO::PARAM_STR);
        $stmtEmail->bindValue(':participation_id', $participationId, PDO::PARAM_INT);
        $stmtEmail->execute();

        if (is_string($smsType) && $smsType !== '' && function_exists('gst_sms_try_send_for_participation')) {
            try {
                gst_sms_try_send_for_participation($pdo, $participationId, $smsType, [
                    'nome' => $nome,
                    'codice' => $smsCodice,
                ]);
            } catch (Throwable $smsEx) {
                error_log('quiz_flow SMS #' . $participationId . ': ' . $smsEx->getMessage());
            }
        }
    }
}
