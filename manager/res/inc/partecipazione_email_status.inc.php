<?php

/**
 * Badge colonne « Email inviato » / « SMS inviato » : solo inviata se flag = 1.
 */
if (!function_exists('manager_participation_delivery_status_badge')) {
    function manager_participation_delivery_status_badge(
        int $sentFlag,
        string $errorMessage,
        callable $escapeHtml
    ): string {
        $errorMessage = trim($errorMessage);
        if ($sentFlag === 1) {
            return '<span class="badge bg-success" data-order="1">'
                . $escapeHtml('Inviata')
                . '</span>';
        }

        $title = $errorMessage !== '' ? ' title="' . $escapeHtml($errorMessage) . '"' : '';

        return '<span class="badge bg-secondary" data-order="0"'
            . $title
            . '>'
            . $escapeHtml('Non inviata')
            . '</span>';
    }
}

if (!function_exists('manager_participation_email_status_badge')) {
    function manager_participation_email_status_badge(
        int $emailInviato,
        string $emailError,
        callable $escapeHtml
    ): string {
        return manager_participation_delivery_status_badge($emailInviato, $emailError, $escapeHtml);
    }
}
