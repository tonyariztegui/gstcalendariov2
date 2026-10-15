<?php
/**
 * Template email di annullamento vincita Instant Win
 */

function getInstantWinCancelledEmailTemplate($name, $custom_body_html = '') {
    global $contest_config, $app_config;

    $safeName = htmlspecialchars($name ?? '', ENT_QUOTES, 'UTF-8');

    $baseUrl = resolve_email_base_url();
    $helpUrl = $baseUrl !== '' ? htmlspecialchars($baseUrl . '/assistenza', ENT_QUOTES, 'UTF-8') : '/assistenza';
    $contestName = htmlspecialchars($contest_config['name'] ?? 'Concorso', ENT_QUOTES, 'UTF-8');

    $greeting = 'Gentile';
    $intro = 'A seguito delle verifiche relative al concorso {{CONTEST_NAME}}, la tua precedente vincita &egrave; stata annullata.';
    $helpText = 'Se ritieni ci sia un errore puoi contattare l\'<a href="{{HELP_URL}}">assistenza</a>.';
    $closing = 'Ti ringraziamo per la partecipazione e restiamo a disposizione.';

    $intro = str_replace('{{CONTEST_NAME}}', $contestName, $intro);
    $helpText = str_replace('{{HELP_URL}}', $helpUrl, $helpText);

    $default_body_html = '
                                <p>' . htmlspecialchars($greeting, ENT_QUOTES, 'UTF-8') . ' ' . $safeName . ',</p>
                                <p>' . $intro . '</p>
                                <p>' . $helpText . '</p>
                                <p>' . $closing . '</p>';
    $body_html = trim((string) $custom_body_html);
    if ($body_html === '') {
        $body_html = $default_body_html;
    }

    return wrap_email_html(
        'Vincita annullata - ' . ($contest_config['name'] ?? 'Concorso'),
        $body_html,
        (string) ($contest_config['name'] ?? 'Concorso')
    );
}
?>
