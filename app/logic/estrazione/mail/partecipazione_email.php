<?php
/**
 * Template email di conferma partecipazione - generico
 */

function getPartecipazioneEmailTemplate($name, $custom_body_html = '') {
    $safeName = htmlspecialchars($name ?? '', ENT_QUOTES, 'UTF-8');

    global $app_config, $contest_config;
    $baseUrl = resolve_email_base_url();
    $helpUrl = $baseUrl !== '' ? htmlspecialchars($baseUrl . '/assistenza', ENT_QUOTES, 'UTF-8') : '/assistenza';
    $contestName = htmlspecialchars($contest_config['name'] ?? 'Concorso', ENT_QUOTES, 'UTF-8');

    $default_body_html = '
                                <p>Ciao <strong>' . $safeName . '</strong>,</p>
                                <h1>GRAZIE PER AVER PARTECIPATO!</h1>
                                <p>La tua partecipazione al concorso <strong>' . $contestName . '</strong> &egrave; stata registrata con successo.</p>
                                <h2>INFORMAZIONI IMPORTANTI</h2>
                                <ul>
                                    <li>Conserva lo scontrino originale fino al termine delle verifiche previste dal regolamento.</li>
                                    <li>Le comunicazioni ufficiali saranno inviate all\'indirizzo email che hai indicato.</li>
                                    <li>Partecipazioni non conformi al regolamento potranno essere escluse.</li>
                                </ul>
                                <p>Se hai domande, consulta la <a href="' . $helpUrl . '">pagina di assistenza</a>.</p>
                                <p>Ti ringraziamo per aver preso parte al concorso <strong>' . $contestName . '</strong>.</p>
                                <p>Ti auguriamo buona fortuna!</p>';
    $body_html = trim((string) $custom_body_html);
    if ($body_html === '') {
        $body_html = $default_body_html;
    }

    return wrap_email_html(
        'Conferma partecipazione - ' . ($contest_config['name'] ?? 'Concorso'),
        $body_html,
        'Banner ' . ($contest_config['name'] ?? 'Concorso')
    );
}
?>
