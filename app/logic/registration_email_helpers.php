<?php
/**
 * Helper condivisi per invio email post-registrazione / post-quiz.
 */
if (!function_exists('registration_send_instantwin_loss_email')) {
    /** @return bool|null true/false = invio tentato; null = template perdita disabilitato */
    function registration_send_instantwin_loss_email(string $email, string $nome): ?bool
    {
        if (!function_exists('should_send_email_type') || !should_send_email_type('instantwin_loss')) {
            return null;
        }
        return sendInstantWinLossEmail($email, $nome);
    }
}

if (!function_exists('registration_instantwin_is_win')) {
    function registration_instantwin_is_win($result): bool
    {
        return is_array($result) && !empty($result['has_won']);
    }
}

if (!function_exists('registration_record_email_attempt')) {
    function registration_record_email_attempt($result, bool &$emailSent, ?string &$emailError, string $errorMessage): void
    {
        if ($result === null) {
            return;
        }
        if ($result) {
            $emailSent = true;
            return;
        }
        $emailError = $errorMessage;
    }
}

if (!function_exists('registration_load_participation_win_state')) {
    /** @return array{has_won:bool, prize_id:int, prize_amount:float, id_codice_premio:?int}|null */
    function registration_load_participation_win_state(PDO $pdo, int $participationId): ?array
    {
        $stmt = $pdo->prepare('
            SELECT has_won, prize_id, prize_amount, id_codice_premio
            FROM partecipazioni
            WHERE id = :id
            LIMIT 1
        ');
        $stmt->execute([':id' => $participationId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!is_array($row)) {
            return null;
        }
        return [
            'has_won' => (int) ($row['has_won'] ?? 0) === 1,
            'prize_id' => (int) ($row['prize_id'] ?? 0),
            'prize_amount' => (float) ($row['prize_amount'] ?? 0),
            'id_codice_premio' => !empty($row['id_codice_premio']) ? (int) $row['id_codice_premio'] : null,
        ];
    }
}

if (!function_exists('registration_apply_instantwin_win_email')) {
    /**
     * @return array{result:?bool,type:?string}
     */
    function registration_apply_instantwin_win_email(
        string $email,
        string $nome,
        float $prizeValue,
        ?string $codicePremio,
        ?string $pdfPath,
        bool $autoPrizeFlow,
        string $codicePdv,
        string $insegnaCoop,
        array $emailContext = []
    ): array {
        $prizeContext = array_merge([
            'id_pdv_concorso' => $codicePdv,
            'insegna' => $insegnaCoop,
        ], $emailContext);

        if ($autoPrizeFlow && function_exists('should_send_email_type') && should_send_email_type('instantwin_prize')) {
            return [
                'result' => sendInstantWinPrizeEmail($email, $nome, $prizeValue, $codicePremio, $pdfPath, $prizeContext),
                'type' => 'instantwin_prize',
            ];
        }

        if (function_exists('should_send_email_type') && should_send_email_type('instantwin_confirm')) {
            return [
                'result' => sendInstantWinConfirmEmail($email, $nome, $prizeValue),
                'type' => 'instantwin_confirm',
            ];
        }

        $codicePremioTrimmed = is_string($codicePremio) ? trim($codicePremio) : '';
        $deferVoucher = function_exists('instantwin_should_defer_voucher_code_until_manager_confirm')
            && instantwin_should_defer_voucher_code_until_manager_confirm();
        if ($deferVoucher && $codicePremioTrimmed === '') {
            error_log('registration_apply_instantwin_win_email: codice premio differito ma email premio richiesta senza codice — skip fallback instantwin_prize');
            return ['result' => null, 'type' => null];
        }

        if (function_exists('should_send_email_type') && should_send_email_type('instantwin_prize')) {
            return [
                'result' => sendInstantWinPrizeEmail($email, $nome, $prizeValue, $codicePremio, $pdfPath, $prizeContext),
                'type' => 'instantwin_prize',
            ];
        }

        return ['result' => null, 'type' => null];
    }
}
