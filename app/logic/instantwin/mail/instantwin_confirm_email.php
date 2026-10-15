<?php
/**
 * Template email di conferma vincita in attesa di verifica (Instant Win)
 */

function getInstantWinConfirmEmailTemplate($name, $prizeAmount, $custom_body_html = '')
{
    global $contest_config, $app_config;

    $safeName = htmlspecialchars($name ?? '', ENT_QUOTES, 'UTF-8');
    $amount = is_numeric($prizeAmount)
        ? number_format((float) $prizeAmount, 2, ',', '.')
        : preg_replace('/[^\d,\.]/', '', (string) $prizeAmount);
    $safeAmount = htmlspecialchars($amount, ENT_QUOTES, 'UTF-8');

    $baseUrl = resolve_email_base_url();
    $helpUrl = $baseUrl !== '' ? htmlspecialchars($baseUrl . '/assistenza', ENT_QUOTES, 'UTF-8') : '/assistenza';
    $contestName = htmlspecialchars($contest_config['name'] ?? 'Concorso', ENT_QUOTES, 'UTF-8');

    $greeting = 'Ciao';
    $intro = 'La tua partecipazione al concorso {{CONTEST_NAME}} &egrave; risultata vincente. Premio previsto: &euro;{{PRIZE_AMOUNT}}.';
    $info = 'Stiamo completando le verifiche previste dal regolamento. Riceverai una seconda email di conferma con le istruzioni per il ritiro del premio.';
    $stepsTitle = 'Cosa fare ora:';
    $steps = [
        'Conserva lo scontrino originale.',
        'Controlla la casella di posta (e la cartella spam) nelle prossime ore.',
        'Per dubbi o assistenza scrivi all\' <a href="{{HELP_URL}}">assistenza</a>.'
    ];
    $closing = 'Grazie per aver partecipato!';

    $intro = str_replace(['{{CONTEST_NAME}}', '{{PRIZE_AMOUNT}}'], [$contestName, $safeAmount], $intro);
    $info = str_replace('{{HELP_URL}}', $helpUrl, $info);
    $closing = htmlspecialchars($closing, ENT_QUOTES, 'UTF-8');

    $stepsHtml = '';
    foreach ($steps as $step) {
        $stepSafe = str_replace('{{HELP_URL}}', $helpUrl, $step);
        $stepsHtml .= '<li>' . $stepSafe . '</li>';
    }

    $default_body_html = '
                                <p>' . htmlspecialchars($greeting, ENT_QUOTES, 'UTF-8') . ' ' . $safeName . ',</p>
                                <p>' . $intro . '</p>
                                <p>' . $info . '</p>
                                <p>' . htmlspecialchars($stepsTitle, ENT_QUOTES, 'UTF-8') . '</p>
                                <ul>
                                    ' . $stepsHtml . '
                                </ul>
                                <p>' . $closing . '</p>';

    $body_html = trim((string) $custom_body_html);
    if ($body_html === '') {
        $body_html = $default_body_html;
    }

    return wrap_email_html(
        'Vincita da confermare - ' . ($contest_config['name'] ?? 'Concorso'),
        $body_html,
        'Banner ' . ($contest_config['name'] ?? 'Concorso')
    );
}
?>
