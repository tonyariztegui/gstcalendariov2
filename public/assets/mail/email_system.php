<?php
/**
 * Sistema centralizzato email - usa la configurazione SMTP da config.php
 */

// Includere la configurazione da config.php
// $email_config è definito in config.php (linea 127-134)
include_once __DIR__ . '/../../../app/inc/config.php';
include_once __DIR__ . '/../../../app/inc/functions.php';

// Recuperare la configurazione email e contest da config.php
global $email_config, $contest_config;

/**
 * Funzione principale per l'invio di email
 * @param string $to Email destinatario
 * @param string $subject Oggetto dell'email
 * @param string $html_content Contenuto HTML
 * @param array $config Configurazione opzionale
 * @return bool Successo o errore
 */
if (!function_exists('sendEmail')) {
    function sendEmail($to, $subject, $html_content, $config = null, $attachments = [], $bccRecipients = []) {
    global $email_config;
    
    // Verificare che $email_config sia valido
    if (!isset($email_config) || !is_array($email_config)) {
        error_log("ERRORE: Configurazione email non disponibile in email_system.php");
        return false;
    }
    
    if ($config && is_array($config)) {
        $email_config = array_merge($email_config, $config);
    }
    
    // Verificare che SMTP sia configurato prima di tentare l'invio
    $smtp_host = isset($email_config['smtp_host']) ? trim((string)$email_config['smtp_host']) : '';
    $smtp_username = isset($email_config['smtp_username']) ? trim((string)$email_config['smtp_username']) : '';
    $smtp_password = isset($email_config['smtp_password']) ? trim((string)$email_config['smtp_password']) : '';
    
    if ($smtp_host === '' || $smtp_username === '' || $smtp_password === '') {
        error_log("ERRORE: Configurazione SMTP incompleta. Host: " . ($smtp_host !== '' ? 'OK' : 'VUOTO') . ", Username: " . ($smtp_username !== '' ? 'OK' : 'VUOTO') . ", Password: " . ($smtp_password !== '' ? 'OK' : 'VUOTO'));
        return false;
    }

    // Validazione rigorosa dell'email prima dell'invio
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        error_log("Email non valida rilevata: " . $to);
        return false;
    }

    // Verificare che l'email non sia un dominio fittizio
    $email_parts = explode('@', $to);
    if (count($email_parts) !== 2) {
        error_log("Formato email non valido: " . $to);
        return false;
    }

    $domain = $email_parts[1];
    
    // In modalità test, non si applicano i blocchi di dominio per permettere Mailtrap
    $skipDomainChecks = function_exists('isTestMode') && isTestMode();
    if (!$skipDomainChecks) {
        // Lista dei domini fittizi da bloccare
        $fake_domains = [
            'test.com', 'example.com', 'fake.com', 'invalid.com', 'nonexistent.com',
            'test.test', 'fake.fake', 'invalid.invalid', 'nonexistent.nonexistent',
            'localhost', '127.0.0.1', '0.0.0.0'
        ];
        
        if (in_array(strtolower($domain), $fake_domains)) {
            error_log("Dominio fittizio rilevato: " . $domain . " per l'email: " . $to);
            return false;
        }

        // Verificare che il dominio abbia almeno un punto (es: gmail.com)
        if (strpos($domain, '.') === false) {
            error_log("Dominio senza punto rilevato: " . $domain . " per l'email: " . $to);
            return false;
        }
    }

    require_once __DIR__ . '/../phpmailer/PHPMailerAutoload.php';

    $mail = new PHPMailer;
    $mail->isSMTP();
    $mail->Host = isset($email_config['smtp_host']) ? $email_config['smtp_host'] : '';
    $mail->SMTPAuth = true;
    $mail->Username = isset($email_config['smtp_username']) ? $email_config['smtp_username'] : '';
    $mail->Password = isset($email_config['smtp_password']) ? $email_config['smtp_password'] : '';
    // Configurazione sicurezza SMTP in base alla porta
    // Porta 465 = SSL, Porta 587 = TLS, Porta 2525 = Nessuna sicurezza (Mailtrap sandbox)
    $smtp_port = isset($email_config['smtp_port']) ? (int)$email_config['smtp_port'] : 587;
    if ($smtp_port == 465) {
        $mail->SMTPSecure = 'ssl'; // SSL per porta 465
    } elseif ($smtp_port == 2525) {
        $mail->SMTPSecure = ''; // Nessuna sicurezza per Mailtrap sandbox (porta 2525)
    } else {
        $mail->SMTPSecure = 'tls'; // TLS per porta 587 e altre porte standard
    }
    $mail->Port = $smtp_port;
    $mail->CharSet = 'UTF-8';
    $mail->SMTPDebug = 0;
    $mail->Debugoutput = 'html';
	$mail->addBCC('itpromomedia@gmail.com');

    
    // Configurazione ottimizzata per SendGrid
    $mail->SMTPKeepAlive = false;
    $mail->Timeout = 30;
    $isProduction = (getenv('APP_ENV') === 'production');
    $mail->SMTPOptions = array(
        'ssl' => array(
            'verify_peer' => $isProduction,
            'verify_peer_name' => $isProduction,
            'allow_self_signed' => !$isProduction,
        )
    );
    
    // Parametri ottimizzati
    $mail->WordWrap = 0;
    $mail->Priority = 3;
    //$mail->Encoding = 'base64';

    $from_email = isset($email_config['from_email']) ? $email_config['from_email'] : 'no-reply@example.com';
    $from_name = isset($email_config['from_name']) ? $email_config['from_name'] : ($contest_config['name'] ?? 'Contest');
    $mail->setFrom($from_email, $from_name);
    if (!empty($email_config['reply_to'])) {
        $mail->addReplyTo($email_config['reply_to'], $from_name);
    }
    $mail->addAddress($to);
    if (!empty($bccRecipients) && is_array($bccRecipients)) {
        foreach ($bccRecipients as $bccEmail) {
            $bccEmail = trim((string) $bccEmail);
            if ($bccEmail !== '' && filter_var($bccEmail, FILTER_VALIDATE_EMAIL)) {
                $mail->addBCC($bccEmail);
            }
        }
    }
    $mail->isHTML(true);
    $mail->Subject = $subject;
    $mail->Body = $html_content;
    
    // Versione testo semplificata
    $text_content = strip_tags($html_content);
    $mail->AltBody = $text_content;
    
    // Aggiungere pièces jointes se presenti
    if (!empty($attachments) && is_array($attachments)) {
        foreach ($attachments as $attachment) {
            if (is_string($attachment)) {
                // Se è una stringa, assumiamo che sia un percorso file
                if (file_exists($attachment) && is_readable($attachment)) {
                    $mail->addAttachment($attachment);
                }
            } elseif (is_array($attachment)) {
                // Se è un array, assumiamo formato ['path' => ..., 'name' => ...]
                $path = $attachment['path'] ?? $attachment['file'] ?? '';
                $name = $attachment['name'] ?? basename($path);
                if (!empty($path) && file_exists($path) && is_readable($path)) {
                    $mail->addAttachment($path, $name);
                }
            }
        }
    }

    if (function_exists('email_embedded_images_attach_to_mailer')) {
        email_embedded_images_attach_to_mailer($mail);
    }

    // Log dell'invio
    error_log("Tentativo invio email a: " . $to . " - Dominio: " . $domain . " - Dimensione: " . strlen($html_content) . " caratteri");
    
    $result = $mail->send();
    if (function_exists('email_embedded_images_clear')) {
        email_embedded_images_clear();
    }
    
    if ($result) {
        error_log("Email inviata con successo a: " . $to . " - Dimensione: " . strlen($html_content) . " caratteri");
    } else {
        error_log("Errore invio email a: " . $to . " - Errore: " . $mail->ErrorInfo);
    }
    
    return $result;
    }
}

if (!function_exists('normalize_email_html')) {
    function normalize_email_html(string $html): string {
        if ($html === '') {
            return '';
        }
        if (function_exists('contest_normalize_summernote_font_html')) {
            $html = contest_normalize_summernote_font_html($html);
        } else {
            $html = (string) preg_replace('/font-family\s*:\s*"([^\"]+)"/i', "font-family: '$1'", $html);
        }
        if (strpos($html, '&lt;') !== false || strpos($html, '&gt;') !== false) {
            $html = htmlspecialchars_decode($html, ENT_QUOTES);
        }
        return $html;
    }
}

if (!function_exists('normalize_email_type_key')) {
    /** @deprecated instantwin_win unificato in instantwin_confirm */
    function normalize_email_type_key(string $type): string {
        $type = strtolower(trim($type));
        return $type === 'instantwin_win' ? 'instantwin_confirm' : $type;
    }
}

if (!function_exists('get_email_templates_config')) {
    function get_email_templates_config(): array {
        global $content_config, $content_defaults;
        $defaults = $content_defaults['email_templates'] ?? [];
        $current = $content_config['email_templates'] ?? [];
        if (!is_array($defaults)) {
            $defaults = [];
        }
        if (!is_array($current)) {
            $current = [];
        }
        $merged = array_replace_recursive($defaults, $current);
        $legacyWin = is_array($merged['instantwin_win'] ?? null) ? $merged['instantwin_win'] : [];
        $confirm = is_array($merged['instantwin_confirm'] ?? null) ? $merged['instantwin_confirm'] : [];
        if (trim((string) ($confirm['subject'] ?? '')) === '' && trim((string) ($legacyWin['subject'] ?? '')) !== '') {
            $merged['instantwin_confirm']['subject'] = $legacyWin['subject'];
        }
        if (trim((string) ($confirm['body'] ?? '')) === '' && trim((string) ($legacyWin['body'] ?? '')) !== '') {
            $merged['instantwin_confirm']['body'] = $legacyWin['body'];
        }
        unset($merged['instantwin_win']);
        return $merged;
    }
}

if (!function_exists('get_email_sending_config')) {
    function get_email_sending_config(): array {
        global $content_config, $content_defaults;
        $defaults = $content_defaults['email_sending'] ?? [];
        $current = $content_config['email_sending'] ?? [];
        if (!is_array($defaults)) {
            $defaults = [];
        }
        if (!is_array($current)) {
            $current = [];
        }
        $merged = array_replace_recursive($defaults, $current);
        if (!empty($merged['instantwin_win'])) {
            $merged['instantwin_confirm'] = !empty($merged['instantwin_confirm']) || !empty($merged['instantwin_win']);
        }
        unset($merged['instantwin_win']);
        return $merged;
    }
}

if (!function_exists('get_email_bcc_config')) {
    function get_email_bcc_config(): array {
        global $content_config, $content_defaults;
        $defaults = $content_defaults['email_bcc'] ?? [];
        $current = $content_config['email_bcc'] ?? [];
        if (!is_array($defaults)) {
            $defaults = [];
        }
        if (!is_array($current)) {
            $current = [];
        }
        return array_replace_recursive($defaults, $current);
    }
}

if (!function_exists('parse_email_csv_values')) {
    function parse_email_csv_values(string $value): array {
        $parts = preg_split('/[\s,;]+/', trim($value)) ?: [];
        $emails = [];
        foreach ($parts as $part) {
            $candidate = trim((string) $part);
            if ($candidate === '') {
                continue;
            }
            if (filter_var($candidate, FILTER_VALIDATE_EMAIL)) {
                $emails[] = strtolower($candidate);
            }
        }
        return array_values(array_unique($emails));
    }
}

if (!function_exists('normalize_insegna_key')) {
    function normalize_insegna_key(string $value): string {
        return mb_strtolower(trim($value), 'UTF-8');
    }
}

if (!function_exists('find_pdv_emails_by_context')) {
    function find_pdv_emails_by_context(array $context): array {
        $idPdv = trim((string) ($context['id_pdv_concorso'] ?? ''));
        $insegna = trim((string) ($context['insegna'] ?? ''));
        if ($idPdv === '' && $insegna === '') {
            return [];
        }
        if (!function_exists('conn_pdo')) {
            return [];
        }
        try {
            $pdo = conn_pdo();
            if (!$pdo instanceof PDO) {
                return [];
            }
            if ($idPdv !== '') {
                $stmt = $pdo->prepare("SELECT email_pdv FROM punti_vendita WHERE codice = :codice AND active = 1 LIMIT 1");
                $stmt->bindValue(':codice', $idPdv, PDO::PARAM_STR);
                $stmt->execute();
                $emails = parse_email_csv_values(trim((string) ($stmt->fetchColumn() ?: '')));
                if ($emails !== []) {
                    return $emails;
                }
            }
            if ($insegna !== '') {
                $stmt = $pdo->prepare("SELECT email_pdv FROM punti_vendita WHERE insegna = :insegna AND active = 1 AND email_pdv IS NOT NULL AND TRIM(email_pdv) <> '' ORDER BY id ASC LIMIT 1");
                $stmt->bindValue(':insegna', $insegna, PDO::PARAM_STR);
                $stmt->execute();
                $emails = parse_email_csv_values(trim((string) ($stmt->fetchColumn() ?: '')));
                if ($emails !== []) {
                    return $emails;
                }
            }
        } catch (Throwable $e) {
            error_log("BCC InstantWin: errore lookup email_pdv - " . $e->getMessage());
        }
        return [];
    }
}

if (!function_exists('resolve_instantwin_prize_bcc')) {
    function resolve_instantwin_prize_bcc(array $context = []): array {
        $bccConfig = get_email_bcc_config();
        $prizeConfig = $bccConfig['instantwin_prize'] ?? [];
        if (!is_array($prizeConfig) || empty($prizeConfig['enabled'])) {
            return [];
        }

        $resolved = [];
        $usePdvEmail = !array_key_exists('use_pdv_email', $prizeConfig) || (bool) $prizeConfig['use_pdv_email'];
        if ($usePdvEmail) {
            $resolved = array_merge($resolved, find_pdv_emails_by_context($context));
        }

        $byInsegna = $prizeConfig['by_insegna'] ?? [];
        if (is_array($byInsegna)) {
            $normalizedMap = [];
            foreach ($byInsegna as $insegnaKey => $emailsCsv) {
                $normalizedKey = normalize_insegna_key((string) $insegnaKey);
                if ($normalizedKey === '') {
                    continue;
                }
                $normalizedMap[$normalizedKey] = (string) $emailsCsv;
            }
            $contextInsegnaKey = normalize_insegna_key((string) ($context['insegna'] ?? ''));
            if ($contextInsegnaKey !== '' && isset($normalizedMap[$contextInsegnaKey])) {
                $resolved = array_merge($resolved, parse_email_csv_values($normalizedMap[$contextInsegnaKey]));
            }
        }

        $resolved = array_merge($resolved, parse_email_csv_values((string) ($prizeConfig['global'] ?? '')));
        return array_values(array_unique($resolved));
    }
}

if (!function_exists('email_send_force')) {
    function email_send_force(bool $enabled = true): void {
        $GLOBALS['email_send_force'] = $enabled;
    }
}

if (!function_exists('email_send_is_forced')) {
    function email_send_is_forced(): bool {
        return !empty($GLOBALS['email_send_force']);
    }
}

if (!function_exists('should_send_email_type')) {
    function should_send_email_type(string $type): bool {
        if (email_send_is_forced()) {
            return true;
        }
        $type = normalize_email_type_key($type);
        $config = get_email_sending_config();
        return (bool) ($config[$type] ?? false);
    }
}

if (!function_exists('resolve_instantwin_outcome_email_type')) {
    /**
     * Template email coerente con stato partecipazione e checkbox del manager.
     */
    function resolve_instantwin_outcome_email_type(
        bool $hasWon,
        bool $isConfirmed,
        bool $isCancelled,
        bool $autoPrizeFlow = false
    ): string {
        if ($isCancelled) {
            return 'instantwin_cancel';
        }
        if (!$hasWon) {
            return 'instantwin_loss';
        }
        if ($isConfirmed || $autoPrizeFlow) {
            return 'instantwin_prize';
        }
        $sending = get_email_sending_config();
        if (!empty($sending['instantwin_confirm'])) {
            return 'instantwin_confirm';
        }
        if (!empty($sending['instantwin_prize'])) {
            return 'instantwin_prize';
        }
        return 'instantwin_confirm';
    }
}

if (!function_exists('resolve_instantwin_outcome_email_label')) {
    function resolve_instantwin_outcome_email_label(string $emailType): string {
        switch (normalize_email_type_key($emailType)) {
            case 'instantwin_cancel':
                return 'Vittoria annullata';
            case 'instantwin_prize':
                return 'Premio (conferma vittoria)';
            case 'instantwin_confirm':
                return 'Vincita (in attesa di conferma)';
            case 'instantwin_loss':
                return 'Esito non vincente';
            case 'partecipazione':
            default:
                return 'Conferma partecipazione';
        }
    }
}

if (!function_exists('resolve_email_base_url')) {
    function resolve_email_base_url(): string {
        global $contest_config, $app_config;
        $baseUrl = isset($contest_config['base_url']) ? rtrim((string) $contest_config['base_url'], '/') : '';
        if ($baseUrl === '') {
            $baseUrl = isset($app_config['base_url']) ? rtrim((string) $app_config['base_url'], '/') : '';
        }
        if ($baseUrl === '') {
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $path = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');
            if (strpos($path, '/manager') !== false) {
                $path = preg_replace('#/manager.*$#', '/public', $path) ?: $path;
            }
            $baseUrl = $protocol . '://' . $host . $path;
        }
        return $baseUrl;
    }
}

if (!function_exists('resolve_email_asset_url')) {
    function resolve_email_asset_url(string $path): string {
        $clean = trim($path);
        if ($clean === '') {
            return '';
        }
        if (preg_match('#^https?://#i', $clean) || str_starts_with($clean, '//')) {
            return htmlspecialchars($clean, ENT_QUOTES, 'UTF-8');
        }
        $baseUrl = resolve_email_base_url();
        if ($clean[0] === '/') {
            return $baseUrl !== ''
                ? htmlspecialchars(rtrim($baseUrl, '/') . $clean, ENT_QUOTES, 'UTF-8')
                : htmlspecialchars($clean, ENT_QUOTES, 'UTF-8');
        }
        return $baseUrl !== ''
            ? htmlspecialchars($baseUrl . '/' . ltrim($clean, '/'), ENT_QUOTES, 'UTF-8')
            : '/' . ltrim($clean, '/');
    }
}

if (!function_exists('resolve_email_banner_url')) {
    function resolve_email_banner_url(): string {
        global $contest_config;
        $bannerPath = $contest_config['mail_banner'] ?? ($contest_config['logo'] ?? 'assets/mail/img/banner.png');
        if (is_array($bannerPath)) {
            $bannerPath = '';
        }
        $bannerPath = trim((string) $bannerPath);
        if ($bannerPath === '' && function_exists('normalize_mail_banner_path')) {
            $bannerPath = normalize_mail_banner_path('');
        } elseif ($bannerPath === '') {
            $bannerPath = 'assets/mail/img/banner.png';
        } elseif (function_exists('normalize_mail_banner_path')) {
            $bannerPath = normalize_mail_banner_path($bannerPath);
        }
        return resolve_email_asset_url($bannerPath);
    }
}

if (!function_exists('wrap_email_html')) {
    /**
     * Layout email compatible Outlook + Gmail (bandeau pleine largeur de la colonne 600px).
     */
    function wrap_email_html(string $pageTitle, string $bodyHtml, string $bannerAlt = ''): string {
        global $contest_config;
        $bannerUrl = resolve_email_banner_url();
        $safeTitle = htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8');
        $safeAlt = htmlspecialchars(
            $bannerAlt !== '' ? $bannerAlt : (string) ($contest_config['name'] ?? 'Concorso'),
            ENT_QUOTES,
            'UTF-8'
        );

        return '<!DOCTYPE html>
<html lang="it" xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="x-apple-disable-message-reformatting">
    <title>' . $safeTitle . '</title>
    <!--[if mso]>
    <noscript>
        <xml>
            <o:OfficeDocumentSettings>
                <o:PixelsPerInch>96</o:PixelsPerInch>
            </o:OfficeDocumentSettings>
        </xml>
    </noscript>
    <![endif]-->
    <style type="text/css">
        body, table, td, p, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; border-collapse: collapse; }
        img { -ms-interpolation-mode: bicubic; border: 0; outline: none; text-decoration: none; display: block; }
        body { margin: 0 !important; padding: 0 !important; width: 100% !important; height: 100% !important; }
        .email-main { width: 600px !important; max-width: 600px !important; margin: 0 auto !important; }
        .email-banner-cell { width: 600px !important; max-width: 600px !important; padding: 0 !important; line-height: 0 !important; font-size: 0 !important; }
        .email-banner-img { display: block !important; width: 600px !important; max-width: 600px !important; height: auto !important; border: 0 !important; margin: 0 !important; padding: 0 !important; }
        @media only screen and (max-width: 620px) {
            .email-main { width: 100% !important; max-width: 100% !important; }
            .email-banner-cell { width: 100% !important; max-width: 100% !important; }
            .email-banner-img { width: 100% !important; max-width: 100% !important; min-width: 100% !important; height: auto !important; }
        }
    </style>
</head>
<body class="body" style="margin:0;padding:0;width:100% !important;background-color:#f4f4f4;font-family:Arial,sans-serif;font-size:16px;line-height:1.6;color:#212529;">
    <div style="display:none;max-height:0;overflow:hidden;mso-hide:all;">&nbsp;</div>
    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="width:100%;background-color:#f4f4f4;">
        <tr>
            <td align="center" style="padding:0;margin:0;">
                <!--[if mso | IE]><table role="presentation" border="0" cellpadding="0" cellspacing="0" width="600" align="center"><tr><td><![endif]-->
                <table role="presentation" class="email-main" border="0" cellpadding="0" cellspacing="0" width="600" align="center" style="width:600px;max-width:600px;margin:0 auto;background-color:#ffffff;">
                    <tr>
                        <td class="email-banner-cell" align="center" valign="top" width="600" bgcolor="#ffffff" background="' . $bannerUrl . '" style="width:600px;max-width:600px;padding:0;line-height:0;font-size:0;mso-line-height-rule:exactly;background-color:#ffffff;background-image:url(' . $bannerUrl . ');background-repeat:no-repeat;background-position:center center;background-size:cover;">
                            <span style="display:block;width:600px;max-width:600px;line-height:0;font-size:0;">
                                <img src="' . $bannerUrl . '" width="600" alt="' . $safeAlt . '" class="email-banner-img" border="0" style="display:block;width:600px;max-width:600px;height:auto;margin:0;padding:0;border:0;color:#ffffff;">
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td align="left" valign="top" width="600" style="width:600px;max-width:600px;padding:40px 30px;font-family:Arial,sans-serif;font-size:16px;line-height:1.6;color:#212529;">
                            ' . $bodyHtml . '
                        </td>
                    </tr>
                </table>
                <!--[if mso | IE]></td></tr></table><![endif]-->
            </td>
        </tr>
    </table>
</body>
</html>';
    }
}

if (!function_exists('build_email_placeholders')) {
    function build_email_placeholders(
        string $name = '',
        $prizeAmount = null,
        $voucherCode = null,
        bool $escape_html = true,
        string $pdvLabel = ''
    ): array {
        global $contest_config, $app_config;
        $contest_name = (string) ($contest_config['name'] ?? 'Concorso');
        $start_date = (string) ($contest_config['start_date_formatted'] ?? ($contest_config['start_date'] ?? ''));
        $end_date = (string) ($contest_config['end_date_formatted'] ?? ($contest_config['end_date'] ?? ''));
        $min_spend = number_format((float) ($contest_config['min_spend'] ?? 0), 2, ',', '.');
        $regolamento_link = (string) ($contest_config['regolamento_link'] ?? '#');
        $baseUrl = '';
        if (isset($contest_config['base_url'])) {
            $baseUrl = rtrim((string) $contest_config['base_url'], '/');
        }
        if ($baseUrl === '') {
            $baseUrl = isset($app_config['base_url']) ? rtrim((string) $app_config['base_url'], '/') : '';
        }
        if ($baseUrl === '') {
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $path = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');
            if (strpos($path, '/manager') !== false) {
                $path = preg_replace('#/manager.*$#', '/public', $path) ?: $path;
            }
            $baseUrl = $protocol . '://' . $host . $path;
        }
        $help_url = $baseUrl . '/assistenza';
        $prize_amount = '';
        if ($prizeAmount !== null && $prizeAmount !== '') {
            $prize_amount = is_numeric($prizeAmount)
                ? number_format((float) $prizeAmount, 2, ',', '.')
                : (string) $prizeAmount;
        }
        $voucher_code = $voucherCode !== null ? (string) $voucherCode : '';

        $values = [
            '{{CONTEST_NAME}}' => $contest_name,
            '{{USER_NAME}}' => $name,
            '{{START_DATE}}' => $start_date,
            '{{END_DATE}}' => $end_date,
            '{{MIN_SPEND}}' => $min_spend,
            '{{PRIZE_AMOUNT}}' => $prize_amount,
            '{{PRIZE_WIN_AMOUNT}}' => $prize_amount,
            '{{HELP_URL}}' => $help_url,
            '{{REGOLAMENTO_LINK}}' => $regolamento_link,
            '{{VOUCHER_CODE}}' => $voucher_code,
            '{{PDV}}' => $pdvLabel,
        ];

        if ($escape_html) {
            foreach ($values as $key => $value) {
                $values[$key] = htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
            }
        }

        return $values;
    }
}

if (!function_exists('get_email_voucher_code_display_mode')) {
    function get_email_voucher_code_display_mode(?string $override = null): string
    {
        global $content_config, $content_defaults;
        $mode = $override !== null && $override !== ''
            ? $override
            : (string) ($content_config['email_voucher_code_display'] ?? ($content_defaults['email_voucher_code_display'] ?? 'text'));
        $mode = strtolower(trim($mode));

        return in_array($mode, ['text', 'barcode', 'both'], true) ? $mode : 'text';
    }
}

require_once __DIR__ . '/../../../app/logic/ean13_helpers.php';
require_once __DIR__ . '/../../../app/logic/pdv_display.inc.php';

if (!function_exists('email_embedded_images_reset')) {
    function email_embedded_images_reset(): void
    {
        $GLOBALS['__email_embedded_images'] = [];
    }
}

if (!function_exists('email_embedded_images_register')) {
    function email_embedded_images_register(string $cid, string $binary, string $mimeType = 'image/png', string $filename = 'barcode.png'): void
    {
        if (!isset($GLOBALS['__email_embedded_images']) || !is_array($GLOBALS['__email_embedded_images'])) {
            $GLOBALS['__email_embedded_images'] = [];
        }
        $GLOBALS['__email_embedded_images'][$cid] = [
            'cid' => $cid,
            'binary' => $binary,
            'mimeType' => $mimeType,
            'filename' => $filename,
        ];
    }
}

if (!function_exists('email_embedded_images_attach_to_mailer')) {
    function email_embedded_images_attach_to_mailer($mail): void
    {
        $images = $GLOBALS['__email_embedded_images'] ?? [];
        if (!is_array($images) || $images === []) {
            return;
        }
        foreach ($images as $img) {
            if (!method_exists($mail, 'addStringEmbeddedImage')) {
                continue;
            }
            $mail->addStringEmbeddedImage(
                $img['binary'],
                $img['cid'],
                $img['filename'],
                'base64',
                $img['mimeType']
            );
        }
    }
}

if (!function_exists('email_embedded_images_clear')) {
    function email_embedded_images_clear(): void
    {
        email_embedded_images_reset();
    }
}

if (!function_exists('email_voucher_load_composer_autoload')) {
    function email_voucher_load_composer_autoload(): bool
    {
        static $loaded = null;
        if ($loaded === true) {
            return true;
        }
        if ($loaded === false) {
            return false;
        }
        $paths = [
            __DIR__ . '/../../../vendor/autoload.php',
            __DIR__ . '/../../../../vendor/autoload.php',
        ];
        foreach ($paths as $path) {
            if (is_file($path)) {
                require_once $path;
                $loaded = true;

                return true;
            }
        }
        $loaded = false;

        return false;
    }
}

if (!function_exists('email_voucher_resolve_block_style')) {
    /**
     * @return array{border_color: string, bg_color: string, text_color: string}
     */
    function email_voucher_resolve_block_style(?array $styleOverrides = null, bool $barcodeInner = false): array
    {
        $defaults = $barcodeInner
            ? ['border_color' => '#198754', 'bg_color' => '#ffffff', 'text_color' => '#111111']
            : ['border_color' => '#198754', 'bg_color' => '#f0f0f0', 'text_color' => '#198754'];
        global $content_config, $content_defaults;
        $style = $content_config['email_voucher_block_style'] ?? $content_defaults['email_voucher_block_style'] ?? [];
        if (!is_array($style)) {
            $style = [];
        }
        if ($styleOverrides !== null && is_array($styleOverrides)) {
            $filtered = array_filter($styleOverrides, static fn($v) => $v !== null && $v !== '');
            $style = array_merge($style, $filtered);
        }
        $border = preg_match('/^#[0-9A-Fa-f]{3,8}$/', (string) ($style['border_color'] ?? ''))
            ? $style['border_color']
            : $defaults['border_color'];
        $bg = preg_match('/^#[0-9A-Fa-f]{3,8}$/', (string) ($style['bg_color'] ?? ''))
            ? $style['bg_color']
            : $defaults['bg_color'];
        $text = preg_match('/^#[0-9A-Fa-f]{3,8}$/', (string) ($style['text_color'] ?? ''))
            ? $style['text_color']
            : $defaults['text_color'];

        return ['border_color' => $border, 'bg_color' => $bg, 'text_color' => $text];
    }
}

if (!function_exists('build_voucher_code_text_block_html')) {
    function build_voucher_code_text_block_html(string $voucherCode, ?array $styleOverrides = null): string
    {
        $voucherCode = trim($voucherCode);
        if ($voucherCode === '') {
            return '';
        }
        $resolved = email_voucher_resolve_block_style($styleOverrides, false);
        $safeVoucher = htmlspecialchars($voucherCode, ENT_QUOTES, 'UTF-8');

        return '<p style="margin:0 0 24px 0; padding:12px; background-color:' . $resolved['bg_color'] . '; border:2px solid ' . $resolved['border_color'] . '; border-radius:4px; font-size:18px; font-weight:bold; text-align:center; letter-spacing:2px; color:' . $resolved['text_color'] . ';">' . $safeVoucher . '</p>';
    }
}

if (!function_exists('build_ean13_barcode_png_html')) {
    function build_ean13_barcode_png_html(string $voucherCode, ?array $styleOverrides = null, bool $embedForEmail = false): string
    {
        $voucherCode = trim($voucherCode);
        if (!function_exists('ean13_is_valid') || !ean13_is_valid($voucherCode)) {
            return '';
        }
        if (!email_voucher_load_composer_autoload() || !class_exists(\Picqer\Barcode\BarcodeGeneratorPNG::class)) {
            error_log('email_voucher barcode PNG: Picqer non disponibile (composer install?)');

            return '';
        }

        try {
            $generator = new \Picqer\Barcode\BarcodeGeneratorPNG();
            $barcodeData = $generator->getBarcode($voucherCode, $generator::TYPE_EAN_13);
        } catch (Throwable $e) {
            error_log('email_voucher barcode PNG: ' . $e->getMessage());

            return '';
        }

        if ($barcodeData === '') {
            return '';
        }

        $resolved = email_voucher_resolve_block_style($styleOverrides, true);
        $border = $resolved['border_color'];
        $bg = $resolved['bg_color'];
        $text = $resolved['text_color'];
        $safeCode = htmlspecialchars($voucherCode, ENT_QUOTES, 'UTF-8');
        if ($embedForEmail) {
            $cid = 'voucher_ean13_' . substr(hash('sha256', $voucherCode), 0, 16);
            email_embedded_images_register($cid, $barcodeData, 'image/png', 'ean13-barcode.png');
            $imgSrc = 'cid:' . $cid;
        } else {
            $imgSrc = 'data:image/png;base64,' . base64_encode($barcodeData);
        }

        return '<div style="margin:0 0 24px 0; padding:14px 12px 10px 12px; background-color:' . $bg . '; border:2px solid ' . $border . '; border-radius:4px; text-align:center;">'
            . '<img src="' . $imgSrc . '" alt="Barcode EAN-13" width="280" height="130" style="display:block; margin:0 auto; max-width:280px; width:100%; height:auto; background-color:' . $bg . ';">'
            . '<div style="font-family:Arial, sans-serif; font-size:14px; line-height:18px; letter-spacing:3px; color:' . $text . '; margin-top:6px;">' . $safeCode . '</div>'
            . '</div>';
    }
}

if (!function_exists('build_voucher_code_block_html')) {
    /**
     * Costruisce l'HTML del blocco {{VOUCHER_CODE_BLOCK}}.
     * @param string|null $displayModeOverride text | barcode | both
     */
    function build_voucher_code_block_html(string $voucherCode, ?array $styleOverrides = null, ?string $displayModeOverride = null, bool $embedImagesForEmail = false): string
    {
        $voucherCode = trim($voucherCode);
        if ($voucherCode === '') {
            return '';
        }
        $mode = get_email_voucher_code_display_mode($displayModeOverride);
        $barcodeHtml = build_ean13_barcode_png_html($voucherCode, $styleOverrides, $embedImagesForEmail);
        if ($mode === 'barcode' && $barcodeHtml !== '') {
            return $barcodeHtml;
        }
        if ($mode === 'both' && $barcodeHtml !== '') {
            return $barcodeHtml . build_voucher_code_text_block_html($voucherCode, $styleOverrides);
        }
        if ($mode === 'barcode' || $mode === 'both') {
            return build_voucher_code_text_block_html($voucherCode, $styleOverrides);
        }

        return build_voucher_code_text_block_html($voucherCode, $styleOverrides);
    }
}

if (!function_exists('replace_email_placeholders')) {
    function replace_email_placeholders(string $value, array $placeholders): string {
        if ($value === '' || $placeholders === []) {
            return $value;
        }
        return str_replace(array_keys($placeholders), array_values($placeholders), $value);
    }
}
// getBaseUrl() è già definita in assets/inc/functions.php. Evitare ridichiarazioni.

/**
 * Funzione per inviare un'email di conferma partecipazione
 * @param string $email Email dell'utente
 * @param string $name Nome dell'utente
 * @return bool Successo o errore
 */
function sendPartecipazioneEmail($email, $name) {
    if (!should_send_email_type('partecipazione')) {
        error_log("Invio email 'partecipazione' disattivato: {$email}");
        return false;
    }
    // Includere il template
    include_once __DIR__ . '/../../../app/logic/estrazione/mail/partecipazione_email.php';
    $templates = get_email_templates_config();
    $template = $templates['partecipazione'] ?? [];

    $placeholders_text = build_email_placeholders($name, null, null, false);
    $placeholders_html = build_email_placeholders($name, null, null, true);

    global $contest_config;
    $subject_default = 'Conferma partecipazione - ' . ($contest_config['name'] ?? 'Concorso');
    $subject_template = (string) ($template['subject'] ?? '');
    $subject = trim(replace_email_placeholders($subject_template !== '' ? $subject_template : $subject_default, $placeholders_text));
    if ($subject === '') {
        $subject = $subject_default;
    }

    $body_template = normalize_email_html((string) ($template['body'] ?? ''));
    $body_html = replace_email_placeholders($body_template, $placeholders_html);
    $email_content = getPartecipazioneEmailTemplate($name, $body_html);
    return sendEmail($email, $subject, $email_content);
}


/**
 * Funzione per inviare l'email di assistenza
 * @param string $nome Nome
 * @param string $cognome Cognome
 * @param string $prefisso Prefisso telefonico
 * @param string $tel Telefono
 * @param string $email Email
 * @param string $messaggio Messaggio
 * @return bool Successo o errore
 */
if (!function_exists('sendAssistanceEmail')) {
    function sendAssistanceEmail($nome, $cognome, $prefisso, $tel, $email, $messaggio) {
        global $contest_config, $email_config;
        $receiver = $email_config['helpdesk_email'] ?? 'concorsi@example.com';
        $subject = 'Richiesta di Assistenza - ' . ($contest_config['name'] ?? 'Concorso');
        
        // Costruisci il numero di telefono completo con prefisso
        $telefono_completo = '';
        if (!empty($prefisso)) {
            $telefono_completo = htmlspecialchars($prefisso) . ' ' . htmlspecialchars($tel);
        } else {
            $telefono_completo = htmlspecialchars($tel);
        }
        
        $body = '
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Richiesta di Assistenza</title>
        </head>
        <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
            <h2 style="color: #198754;">Nuova Richiesta di Assistenza</h2>
            <p>Ciao, abbiamo ricevuto una richiesta di assistenza dal sito ' . htmlspecialchars($contest_config['name'] ?? '') . '.</p>
            
            <h3 style="color: #198754;">Dettagli della richiesta:</h3>
            <table style="border-collapse: collapse; width: 100%; margin: 20px 0;">
                <tr>
                    <td style="padding: 8px; border: 1px solid #ddd; font-weight: bold; background-color: #f9f9f9;">Nome:</td>
                    <td style="padding: 8px; border: 1px solid #ddd;">' . htmlspecialchars($nome) . '</td>
                </tr>
                <tr>
                    <td style="padding: 8px; border: 1px solid #ddd; font-weight: bold; background-color: #f9f9f9;">Cognome:</td>
                    <td style="padding: 8px; border: 1px solid #ddd;">' . htmlspecialchars($cognome) . '</td>
                </tr>
                <tr>
                    <td style="padding: 8px; border: 1px solid #ddd; font-weight: bold; background-color: #f9f9f9;">Telefono:</td>
                    <td style="padding: 8px; border: 1px solid #ddd;">' . $telefono_completo . '</td>
                </tr>
                <tr>
                    <td style="padding: 8px; border: 1px solid #ddd; font-weight: bold; background-color: #f9f9f9;">Email:</td>
                    <td style="padding: 8px; border: 1px solid #ddd;">' . htmlspecialchars($email) . '</td>
                </tr>
            </table>
            
            <h3 style="color: #198754;">Messaggio:</h3>
            <div style="background-color: #f9f9f9; padding: 15px; border-left: 4px solid #198754; margin: 10px 0;">
                ' . nl2br(htmlspecialchars($messaggio)) . '
            </div>
            
        </body>
        </html>';
        
        return sendEmail($receiver, $subject, $body);
    }
}

/**
 * Funzione per inviare l'email di vincita Instant Win
 * @param string $email Email dell'utente
 * @param string $name Nome dell'utente
 * @param float $prizeAmount Importo del premio
 * @return bool Successo o errore
 */
if (!function_exists('sendInstantWinWinEmail')) {
    /** @deprecated Usare sendInstantWinConfirmEmail (template unificato). */
    function sendInstantWinWinEmail($email, $name, $prizeAmount) {
        return sendInstantWinConfirmEmail($email, $name, $prizeAmount);
    }
}

/**
 * Funzione per inviare l'email di conferma vincita in attesa di verifica (Instant Win)
 * @param string $email Email dell'utente
 * @param string $name Nome dell'utente
 * @param float $prizeAmount Importo del premio
 * @return bool Successo o errore
 */
if (!function_exists('sendInstantWinConfirmEmail')) {
    function sendInstantWinConfirmEmail($email, $name, $prizeAmount) {
        if (!should_send_email_type('instantwin_confirm')) {
            error_log("Invio email 'instantwin_confirm' disattivato: {$email}");
            return false;
        }
        include_once __DIR__ . '/../../../app/logic/instantwin/mail/instantwin_confirm_email.php';
        $templates = get_email_templates_config();
        $template = $templates['instantwin_confirm'] ?? [];

        $placeholders_text = build_email_placeholders($name, $prizeAmount, null, false);
        $placeholders_html = build_email_placeholders($name, $prizeAmount, null, true);

        global $contest_config;
        $subject_default = 'Vincita da confermare - ' . ($contest_config['name'] ?? 'Concorso');
        $subject_template = (string) ($template['subject'] ?? '');
        $subject = trim(replace_email_placeholders($subject_template !== '' ? $subject_template : $subject_default, $placeholders_text));
        if ($subject === '') {
            $subject = $subject_default;
        }

        $body_template = normalize_email_html((string) ($template['body'] ?? ''));
        $body_html = replace_email_placeholders($body_template, $placeholders_html);
        $email_content = getInstantWinConfirmEmailTemplate($name, $prizeAmount, $body_html);
        return sendEmail($email, $subject, $email_content);
    }
}

/**
 * Funzione per inviare l'email di non vincita Instant Win
 * @param string $email Email dell'utente
 * @param string $name Nome dell'utente
 * @return bool Successo o errore
 */
if (!function_exists('sendInstantWinLossEmail')) {
    function sendInstantWinLossEmail($email, $name) {
        if (!should_send_email_type('instantwin_loss')) {
            error_log("Invio email 'instantwin_loss' disattivato: {$email}");
            return false;
        }
        include_once __DIR__ . '/../../../app/logic/instantwin/mail/instantwin_loss_email.php';
        $templates = get_email_templates_config();
        $template = $templates['instantwin_loss'] ?? [];

        $placeholders_text = build_email_placeholders($name, null, null, false);
        $placeholders_html = build_email_placeholders($name, null, null, true);

        global $contest_config;
        $subject_default = 'Grazie per la partecipazione - ' . ($contest_config['name'] ?? 'Concorso');
        $subject_template = (string) ($template['subject'] ?? '');
        $subject = trim(replace_email_placeholders($subject_template !== '' ? $subject_template : $subject_default, $placeholders_text));
        if ($subject === '') {
            $subject = $subject_default;
        }

        $body_template = normalize_email_html((string) ($template['body'] ?? ''));
        $body_html = replace_email_placeholders($body_template, $placeholders_html);
        $email_content = getInstantWinLossEmailTemplate($name, $body_html);
        return sendEmail($email, $subject, $email_content);
    }
}

/**
 * Funzione per inviare l'email di annullamento vincita Instant Win
 * @param string $email Email dell'utente
 * @param string $name Nome dell'utente
 * @param float|null $prizeAmount Importo del premio (opzionale)
 * @return bool Successo o errore
 */
if (!function_exists('sendCancelledWinEmail')) {
    function sendCancelledWinEmail($email, $name, $prizeAmount = null) {
        if (!should_send_email_type('instantwin_cancel')) {
            error_log("Invio email 'instantwin_cancel' disattivato: {$email}");
            return false;
        }
        include_once __DIR__ . '/../../../app/logic/instantwin/mail/instantwin_cancelled_email.php';
        $templates = get_email_templates_config();
        $template = $templates['instantwin_cancel'] ?? [];

        $placeholders_text = build_email_placeholders($name, $prizeAmount, null, false);
        $placeholders_html = build_email_placeholders($name, $prizeAmount, null, true);

        global $contest_config;
        $subject_default = 'Vittoria annullata - ' . ($contest_config['name'] ?? 'Concorso');
        $subject_template = (string) ($template['subject'] ?? '');
        $subject = trim(replace_email_placeholders($subject_template !== '' ? $subject_template : $subject_default, $placeholders_text));
        if ($subject === '') {
            $subject = $subject_default;
        }

        $body_template = normalize_email_html((string) ($template['body'] ?? ''));
        $body_html = replace_email_placeholders($body_template, $placeholders_html);
        $email_content = getInstantWinCancelledEmailTemplate($name, $body_html);
        return sendEmail($email, $subject, $email_content);
    }
}

/**
 * Alias per inviare la conferma vittoria (usa il template premio Instant Win)
 * @param string $email Email dell'utente
 * @param string $name Nome dell'utente
 * @param float $prizeAmount Importo del premio
 * @param string|null $voucherCode Codice voucher (opzionale) - deve essere codice_premio
 * @param string|null $pdfPath Percorso del PDF voucher da allegare (opzionale)
 * @return bool Successo o errore
 */
if (!function_exists('sendConfirmedWinEmail')) {
    function sendConfirmedWinEmail($email, $name, $prizeAmount, $voucherCode = null, $pdfPath = null, array $context = []) {
        return sendInstantWinPrizeEmail($email, $name, $prizeAmount, $voucherCode, $pdfPath, $context);
    }
}

/**
 * Funzione per inviare l'email con il codice premio Instant Win
 * @param string $email Email dell'utente
 * @param string $name Nome dell'utente
 * @param float $prizeAmount Importo del premio
 * @param string|null $voucherCode Codice voucher (opzionale) - deve essere codice_premio
 * @param string|null $pdfPath Percorso del PDF voucher da allegare (opzionale)
 * @return bool Successo o errore
 */
if (!function_exists('sendInstantWinPrizeEmail')) {
    function sendInstantWinPrizeEmail($email, $name, $prizeAmount, $voucherCode = null, $pdfPath = null, array $context = []) {
        if (!should_send_email_type('instantwin_prize')) {
            error_log("Invio email 'instantwin_prize' disattivato: {$email}");
            return false;
        }
        include_once __DIR__ . '/../../../app/logic/instantwin/mail/instantwin_prize_email.php';
        require_once __DIR__ . '/../../../app/logic/prize_image_generator.php';
        $templates = get_email_templates_config();
        $template = $templates['instantwin_prize'] ?? [];

        $amountFormatted = is_numeric($prizeAmount) ? number_format((float) $prizeAmount, 2, ',', '.') : $prizeAmount;
        $pdvLabel = '';
        if (function_exists('pdv_resolve_email_label')) {
            $pdvLabel = pdv_resolve_email_label(
                isset($context['id_pdv_concorso']) ? (string) $context['id_pdv_concorso'] : null,
                isset($context['insegna']) ? (string) $context['insegna'] : null
            );
        }
        $placeholders_text = build_email_placeholders($name, $amountFormatted, $voucherCode, false, $pdvLabel);
        $placeholders_html = build_email_placeholders($name, $amountFormatted, $voucherCode, true, $pdvLabel);

        $prizeId = (int) ($context['prize_id'] ?? 0);
        $idCodicePremio = (int) ($context['id_codice_premio'] ?? 0);
        $pdoForPrizeImage = null;
        if ($prizeId > 0 || $idCodicePremio > 0) {
            if (!function_exists('conn_pdo')) {
                require_once __DIR__ . '/../../../app/inc/functions.php';
            }
            try {
                $pdoForPrizeImage = conn_pdo();
            } catch (Throwable $e) {
                $pdoForPrizeImage = null;
            }
        }

        $voucherDisplayMode = function_exists('prize_email_resolve_voucher_display_mode')
            ? prize_email_resolve_voucher_display_mode($pdoForPrizeImage, $prizeId, null)
            : get_email_voucher_code_display_mode();
        email_embedded_images_reset();
        $voucher_block_html = $voucherCode !== null && $voucherCode !== ''
            ? build_voucher_code_block_html((string) $voucherCode, null, $voucherDisplayMode, true)
            : '';
        $placeholders_html['{{VOUCHER_CODE_BLOCK}}'] = $voucher_block_html;
        $placeholders_text['{{VOUCHER_CODE_BLOCK}}'] = $voucherCode !== null ? (string) $voucherCode : '';

        $prizeImageResolved = prize_image_resolve_for_email(
            $idCodicePremio > 0 ? $idCodicePremio : null,
            $prizeId > 0 ? $prizeId : null,
            $pdoForPrizeImage
        );
        $prizeImagePath = is_array($prizeImageResolved) ? trim((string) ($prizeImageResolved['path'] ?? '')) : '';
        $prizeTypeLabel = is_array($prizeImageResolved) ? trim((string) ($prizeImageResolved['tipo_premio'] ?? '')) : '';
        $prizeImageBlock = build_prize_image_block_html($prizeImagePath, $prizeTypeLabel);
        $placeholders_html['{{PRIZE_IMAGE_BLOCK}}'] = $prizeImageBlock;
        $placeholders_text['{{PRIZE_IMAGE_BLOCK}}'] = $prizeImagePath;
        $placeholders_html['{{PRIZE_TYPE}}'] = htmlspecialchars($prizeTypeLabel, ENT_QUOTES, 'UTF-8');
        $placeholders_text['{{PRIZE_TYPE}}'] = $prizeTypeLabel;

        global $contest_config;
        $subject_default = 'Conferma vittoria - ' . ($contest_config['name'] ?? 'Concorso');
        $subject_template = (string) ($template['subject'] ?? '');
        $subject = trim(replace_email_placeholders($subject_template !== '' ? $subject_template : $subject_default, $placeholders_text));
        if ($subject === '') {
            $subject = $subject_default;
        }

        $body_template = normalize_email_html((string) ($template['body'] ?? ''));
        if (function_exists('prize_email_resolve_body_for_prize')) {
            global $content_config;
            $resolvedBody = prize_email_resolve_body_for_prize(
                $pdoForPrizeImage,
                $prizeId,
                is_array($content_config ?? null) ? $content_config : null,
                $body_template
            );
            if (trim($resolvedBody) !== '') {
                $body_template = normalize_email_html($resolvedBody);
            }
        }
        $body_html = replace_email_placeholders($body_template, $placeholders_html);
        $email_content = getInstantWinPrizeEmailTemplate($name, $amountFormatted, $voucherCode, $body_html, true);
        
        // Gestione allegato PDF se attivo e disponibile
        $attachments = [];
        $attachPdfEnabled = (bool)($contest_config['email_instantwin_prize_attach_pdf'] ?? false);

        if ($attachPdfEnabled && !empty($pdfPath)) {
            $baseDir = dirname(__DIR__, 3);
            $pathToResolve = trim($pdfPath);

            // Se pdf_path è un URL (es. http://localhost/gst-con/uploads/vouchers/voucher_1.pdf), estrarre il path relativo
            $baseUrl = rtrim((string)($contest_config['base_url'] ?? ''), '/');
            if ($baseUrl !== '' && (strpos($pathToResolve, 'http://') === 0 || strpos($pathToResolve, 'https://') === 0)) {
                $parsed = parse_url($pathToResolve);
                $urlPath = isset($parsed['path']) ? $parsed['path'] : '';
                $basePath = parse_url($baseUrl, PHP_URL_PATH);
                if ($basePath !== null && $basePath !== false && $basePath !== '') {
                    $basePath = rtrim($basePath, '/');
                    if (strpos($urlPath, $basePath) === 0) {
                        $pathToResolve = ltrim(substr($urlPath, strlen($basePath)), '/');
                    } else {
                        $pathToResolve = ltrim($urlPath, '/');
                    }
                } else {
                    $pathToResolve = ltrim($urlPath, '/');
                }
            }

            // Normalizzare i separatori: convertire tutti i backslash e slash in DIRECTORY_SEPARATOR
            $normalizedPath = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $pathToResolve);

            // Se inizia con / o è un percorso assoluto Windows, usarlo direttamente
            if (strpos($normalizedPath, DIRECTORY_SEPARATOR) === 0 || preg_match('/^[A-Za-z]:' . preg_quote(DIRECTORY_SEPARATOR, '/') . '/', $normalizedPath)) {
                $fullPdfPath = $normalizedPath;
            } else {
                // Percorso relativo rispetto alla root del progetto
                $fullPdfPath = $baseDir . DIRECTORY_SEPARATOR . ltrim($normalizedPath, DIRECTORY_SEPARATOR);
            }

            if (file_exists($fullPdfPath) && is_readable($fullPdfPath)) {
                $attachments[] = $fullPdfPath;
                error_log("PDF voucher allegato all'email Instant Win Premio: {$fullPdfPath}");
            } else {
                error_log("PDF voucher non trovato o non leggibile: {$fullPdfPath}");
            }
        }
        
        $bccRecipients = resolve_instantwin_prize_bcc($context);
        return sendEmail($email, $subject, $email_content, null, $attachments, $bccRecipients);
    }
}

