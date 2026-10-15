<?php
/**
 * Template email di non vincita Instant Win
 */

function getInstantWinLossEmailTemplate($name, $custom_body_html = '') {
    global $contest_config, $app_config;

    $safeName = htmlspecialchars($name ?? '', ENT_QUOTES, 'UTF-8');

    $baseUrl = resolve_email_base_url();
    $helpUrl = $baseUrl !== '' ? htmlspecialchars($baseUrl . '/assistenza', ENT_QUOTES, 'UTF-8') : '/assistenza';
    $contestName = htmlspecialchars($contest_config['name'] ?? 'Concorso', ENT_QUOTES, 'UTF-8');

    $greeting = 'Gentile';
    $intro = 'Ti ringraziamo per aver preso parte al concorso {{CONTEST_NAME}}. L\'estrazione odierna non ha rilevato una vincita associata alla tua partecipazione.';
    $contestInfoTitle = 'Il concorso prosegue:';
    $contestInfoText = 'L\'iniziativa &egrave; attiva dal {{START_DATE}} al {{END_DATE}}. Puoi partecipare ogni giorno: ogni ingresso rappresenta una nuova opportunit&agrave; di vincita Instant Win.';
    $tipsTitle = 'Suggerimenti utili:';
    $tips = [
        'Partecipa quotidianamente per massimizzare le possibilit&agrave; di vincita.',
        'Ricorda che ogni partecipazione richiede una spesa minima di &euro;{{MIN_SPEND}}.'
    ];
    $helpText = 'Per qualsiasi ulteriore chiarimento, puoi contattare l\'<a href="{{HELP_URL}}">assistenza</a>.';
    $closing = 'Ti auguriamo buona fortuna per le prossime estrazioni e ti ringraziamo ancora per la fiducia accordata.';

    $intro = str_replace('{{CONTEST_NAME}}', $contestName, $intro);
    $contestInfoText = str_replace(
        ['{{START_DATE}}', '{{END_DATE}}'],
        [
            (string) ($contest_config['start_date_formatted'] ?? ($contest_config['display_start_date'] ?? $contest_config['start_date'] ?? '')),
            (string) ($contest_config['end_date_formatted'] ?? ($contest_config['display_end_date'] ?? $contest_config['end_date'] ?? ''))
        ],
        $contestInfoText
    );
    $minSpend = isset($contest_config['min_spend']) ? number_format($contest_config['min_spend'], 2, ',', '.') : '20,00';
    $tipsHtml = '';
    if (is_array($tips)) {
        foreach ($tips as $tip) {
            $tip = str_replace('{{MIN_SPEND}}', $minSpend, $tip);
            $tipsHtml .= '<li>' . $tip . '</li>';
        }
    }
    $helpText = str_replace('{{HELP_URL}}', $helpUrl, $helpText);

    $default_body_html = '
                                <p>' . htmlspecialchars($greeting, ENT_QUOTES, 'UTF-8') . ' ' . $safeName . ',</p>
                                <p>' . $intro . '</p>
                                <p>' . htmlspecialchars($contestInfoTitle, ENT_QUOTES, 'UTF-8') . '</p>
                                <p>' . $contestInfoText . '</p>
                                <p>' . htmlspecialchars($tipsTitle, ENT_QUOTES, 'UTF-8') . '</p>
                                <ul>
                                    ' . $tipsHtml . '
                                </ul>
                                <p>' . $helpText . '</p>
                                <p>' . $closing . '</p>';
    $body_html = trim((string) $custom_body_html);
    if ($body_html === '') {
        $body_html = $default_body_html;
    }

    return wrap_email_html(
        'Grazie per la partecipazione - ' . ($contest_config['name'] ?? 'Concorso'),
        $body_html,
        (string) ($contest_config['name'] ?? 'Concorso')
    );
}
?>
