<?php
/**
 * Template email premio con codice voucher Instant Win
 */

function getInstantWinPrizeEmailTemplate($name, $prizeAmountFormatted, $voucherCode = null, $custom_body_html = '', bool $embedVoucherImagesForEmail = false) {
    global $contest_config, $app_config;

    $safeName = htmlspecialchars($name ?? '', ENT_QUOTES, 'UTF-8');
    $safePrize = htmlspecialchars($prizeAmountFormatted ?? '', ENT_QUOTES, 'UTF-8');

    $contestName = htmlspecialchars($contest_config['name'] ?? 'Concorso', ENT_QUOTES, 'UTF-8');

    // Contenuto email (hardcoded, non più da content.json)
    $greeting = 'Gentilissimo/a';
    $intro = 'Che bella notizia: sei tra i vincitori del concorso {{CONTEST_NAME}}! La tua partecipazione &egrave; stata verificata con successo e ti sei aggiudicato/a una prepagata dal valore di &euro;{{PRIZE_AMOUNT}}.';
    $instructionsTitle = 'Ecco cosa devi fare per riceverla:';
    $instructions = [
        'Ritira il premio presso: {{PDV}}. Riceverai subito la tua prepagata come riconoscimento della tua vincita!',
        'Mostra al personale questa email di conferma (puoi farlo dallo smartphone o con una stampa) e il tuo documento d\'identit&agrave;.'
    ];
    $reminderTitle = 'Ti ricordiamo che:';
    $reminder = '&Egrave; preferibile conservare lo scontrino originale fino ad almeno due mesi dalla fine del concorso, in caso di eventuali verifiche.';
    $closing = 'Grazie per aver partecipato e per aver scelto di partecipare ogni giorno. Continua a fare la tua spesa con noi: le sorprese non finiscono mai!';
    $signature = 'A presto,<br>Il Team';

    $intro = str_replace(['{{CONTEST_NAME}}', '{{PRIZE_AMOUNT}}'], [$contestName, $safePrize], $intro);

    $voucherCodeHtml = '';
    if (($voucherCode !== null && $voucherCode !== '') && function_exists('build_voucher_code_block_html') && trim((string) $custom_body_html) === '') {
        $voucherCodeHtml = '<p style="margin:24px 0 12px 0; font-weight:bold;">Il tuo codice voucher per ritirare la prepagata:</p>' . build_voucher_code_block_html((string) $voucherCode, null, null, $embedVoucherImagesForEmail);
    }

    $instructionsHtml = '';
    if (is_array($instructions)) {
        foreach ($instructions as $instruction) {
            $instructionsHtml .= '<li>' . $instruction . '</li>';
        }
    }

    $default_body_html = '
                                <p>' . htmlspecialchars($greeting, ENT_QUOTES, 'UTF-8') . ' ' . $safeName . ',</p>
                                <p>' . $intro . '</p>
                                ' . $voucherCodeHtml . '
                                <p>' . htmlspecialchars($instructionsTitle, ENT_QUOTES, 'UTF-8') . '</p>
                                <ul>
                                    ' . $instructionsHtml . '
                                </ul>
                                <p>' . htmlspecialchars($reminderTitle, ENT_QUOTES, 'UTF-8') . '</p>
                                <p>' . $reminder . '</p>
                                <p>' . $closing . '</p>
                                <p>' . $signature . '</p>';
    $body_html = trim((string) $custom_body_html);
    if ($body_html === '') {
        $body_html = $default_body_html;
    }

    return wrap_email_html(
        'Conferma vittoria - ' . ($contest_config['name'] ?? 'Concorso'),
        $body_html,
        (string) ($contest_config['name'] ?? 'Concorso')
    );
}
?>
