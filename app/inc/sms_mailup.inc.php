<?php

declare(strict_types=1);

/**
 * MailUp transactional SMS — credentials only from .env (MAILUP_SMS_*).
 */

if (!defined('GST_SMS_TEMPLATE_MAX_SEPTETS')) {
    define('GST_SMS_TEMPLATE_MAX_SEPTETS', 150);
}
if (!defined('GST_SMS_TEMPLATE_MAX_LENGTH')) {
    define('GST_SMS_TEMPLATE_MAX_LENGTH', GST_SMS_TEMPLATE_MAX_SEPTETS);
}

if (!function_exists('gst_sms_config_from_env')) {
    /**
     * @return array{
     *   username:string,password:string,account_id:string,list_id:string,
     *   list_guid:string,list_secret:string,test_recipient:string,sender:string
     * }
     */
    function gst_sms_config_from_env(): array
    {
        $get = static function (string $key): string {
            $v = getenv($key);
            return is_string($v) ? trim($v) : '';
        };

        return [
            'username' => $get('MAILUP_SMS_USERNAME'),
            'password' => $get('MAILUP_SMS_PASSWORD'),
            'account_id' => $get('MAILUP_SMS_ACCOUNT_ID'),
            'list_id' => $get('MAILUP_SMS_LIST_ID'),
            'list_guid' => $get('MAILUP_SMS_LIST_GUID'),
            'list_secret' => $get('MAILUP_SMS_LIST_SECRET'),
            'test_recipient' => $get('MAILUP_SMS_TEST_RECIPIENT'),
            'sender' => $get('MAILUP_SMS_SENDER'),
        ];
    }
}

if (!function_exists('get_sms_config')) {
    function get_sms_config(): array
    {
        global $content_config, $content_defaults, $contest_config;
        if (isset($contest_config['sms']) && is_array($contest_config['sms'])) {
            $current = $contest_config['sms'];
        } else {
            $current = $content_config['sms'] ?? [];
        }
        $defaults = $content_defaults['sms'] ?? [];
        if (!is_array($defaults)) {
            $defaults = [];
        }
        if (!is_array($current)) {
            $current = [];
        }
        return array_replace_recursive($defaults, $current);
    }
}

if (!function_exists('gst_sms_resolve_campaign_code')) {
    function gst_sms_resolve_campaign_code(string $type): string
    {
        $custom = trim((string) (get_sms_config()['campaign_code'] ?? ''));
        if ($custom !== '') {
            return $custom;
        }
        if ($type === 'test') {
            return 'gst-con-test';
        }
        return 'gst-con-' . preg_replace('/[^a-z0-9_-]/i', '', $type);
    }
}

if (!function_exists('gst_sms_master_enabled')) {
    function gst_sms_master_enabled(): bool
    {
        global $contest_config, $content_config, $content_defaults;
        if (isset($contest_config['sms']) && is_array($contest_config['sms'])) {
            return !empty($contest_config['sms']['enabled']);
        }
        $current = $content_config['sms'] ?? ($content_defaults['sms'] ?? []);
        return is_array($current) && !empty($current['enabled']);
    }
}

if (!function_exists('get_sms_sending_config')) {
    function get_sms_sending_config(): array
    {
        global $content_config, $content_defaults, $contest_config;
        if (isset($contest_config['sms_sending']) && is_array($contest_config['sms_sending'])) {
            return $contest_config['sms_sending'];
        }
        $defaults = $content_defaults['sms_sending'] ?? [];
        $current = $content_config['sms_sending'] ?? [];
        if (!is_array($defaults)) {
            $defaults = [];
        }
        if (!is_array($current)) {
            $current = [];
        }
        return array_replace_recursive($defaults, $current);
    }
}

if (!function_exists('get_sms_templates_config')) {
    function get_sms_templates_config(): array
    {
        global $content_config, $content_defaults, $contest_config;
        if (isset($contest_config['sms_templates']) && is_array($contest_config['sms_templates'])) {
            return $contest_config['sms_templates'];
        }
        $defaults = $content_defaults['sms_templates'] ?? [];
        $current = $content_config['sms_templates'] ?? [];
        if (!is_array($defaults)) {
            $defaults = [];
        }
        if (!is_array($current)) {
            $current = [];
        }
        return array_replace_recursive($defaults, $current);
    }
}

if (!function_exists('should_send_sms_type')) {
    function should_send_sms_type(string $type): bool
    {
        if (!gst_sms_master_enabled()) {
            return false;
        }
        $type = strtolower(trim($type));
        if ($type === 'instantwin_win') {
            $type = 'instantwin_prize';
        }
        $config = get_sms_sending_config();
        return !empty($config[$type]);
    }
}

if (!function_exists('registration_resolve_instantwin_win_sms_type')) {
    /**
     * Specchio della logica email vincita: auto premio → prize; altrimenti confirm se ON, altrimenti prize.
     */
    function registration_resolve_instantwin_win_sms_type(bool $autoPrizeFlow): ?string
    {
        if ($autoPrizeFlow && should_send_sms_type('instantwin_prize')) {
            return 'instantwin_prize';
        }
        if (should_send_sms_type('instantwin_confirm')) {
            return 'instantwin_confirm';
        }
        if (should_send_sms_type('instantwin_prize')) {
            return 'instantwin_prize';
        }
        return null;
    }
}

if (!function_exists('gst_sms_format_recipient')) {
    function gst_sms_format_recipient(string $prefisso, string $cellulare): string
    {
        $digits = preg_replace('/\D+/', '', $cellulare) ?? '';
        $pref = trim($prefisso);
        if ($pref === '') {
            $pref = '+39';
        }
        if ($pref[0] !== '+') {
            $pref = '+' . ltrim($pref, '0');
        }
        $prefDigits = preg_replace('/\D+/', '', $pref) ?? '';
        if ($digits === '') {
            return '';
        }
        // Evita doppio prefisso se cellulare già internazionale
        if (str_starts_with($digits, $prefDigits) && strlen($digits) > strlen($prefDigits) + 5) {
            return '+' . $digits;
        }
        return $pref . $digits;
    }
}

if (!function_exists('gst_sms_normalize_recipient_e164')) {
    /**
     * Normalizza un numero per MailUp: aggiunge +39 se manca il prefisso internazionale.
     * Accetta: +3933…, 003933…, 39…, 333… (solo cifre locali IT).
     */
    function gst_sms_normalize_recipient_e164(string $raw): string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return '';
        }
        if (str_starts_with($raw, '00')) {
            $digits = preg_replace('/\D+/', '', substr($raw, 2)) ?? '';
            return $digits !== '' ? '+' . $digits : '';
        }
        if (str_starts_with($raw, '+')) {
            $digits = preg_replace('/\D+/', '', $raw) ?? '';
            return $digits !== '' ? '+' . $digits : '';
        }
        $digits = preg_replace('/\D+/', '', $raw) ?? '';
        if ($digits === '') {
            return '';
        }
        // Già con country code IT
        if (str_starts_with($digits, '39') && strlen($digits) >= 11) {
            return '+' . $digits;
        }
        return '+39' . $digits;
    }
}

if (!function_exists('gst_sms_gsm7_charset')) {
  function gst_sms_gsm7_charset(bool $includeLineBreaks = false): string
  {
    static $withBreaks = null;
    static $withoutBreaks = null;
    if ($withBreaks === null) {
      $withBreaks = '@£$¥èéùìòÇ' . "\n" . 'Øø' . "\r" . 'ÅåΔ_ΦΓΛΩΠΨΣΘΞ ÆæßÉ !"#¤%&\'()*+,-./0123456789:;<=>?¡ABCDEFGHIJKLMNOPQRSTUVWXYZÄÖÑÜ§¿abcdefghijklmnopqrstuvwxyzäöñüà^{}\\[~]|€';
      $withoutBreaks = str_replace(["\n", "\r"], '', $withBreaks);
    }
    return $includeLineBreaks ? $withBreaks : $withoutBreaks;
  }
}

if (!function_exists('gst_sms_gsm7_extended_chars')) {
  function gst_sms_gsm7_extended_chars(): string
  {
    return '^{}\\[~]|€';
  }
}

if (!function_exists('gst_sms_gsm7_septet_count')) {
  function gst_sms_gsm7_septet_count(string $text): int
  {
    if ($text === '') {
      return 0;
    }
    $extended = gst_sms_gsm7_extended_chars();
    $count = 0;
    $chars = preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY);
    if ($chars === false) {
      return 0;
    }
    foreach ($chars as $ch) {
      $count += mb_strpos($extended, $ch, 0, 'UTF-8') !== false ? 2 : 1;
    }
    return $count;
  }
}

if (!function_exists('gst_sms_gsm7_truncate_to_septets')) {
  function gst_sms_gsm7_truncate_to_septets(string $text, int $maxSeptets): string
  {
    if ($text === '' || $maxSeptets <= 0) {
      return '';
    }
    $extended = gst_sms_gsm7_extended_chars();
    $out = '';
    $septets = 0;
    $chars = preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY);
    if ($chars === false) {
      return '';
    }
    foreach ($chars as $ch) {
      $cost = mb_strpos($extended, $ch, 0, 'UTF-8') !== false ? 2 : 1;
      if ($septets + $cost > $maxSeptets) {
        break;
      }
      $out .= $ch;
      $septets += $cost;
    }
    return $out;
  }
}

if (!function_exists('gst_sms_sanitize_template_text')) {
  /**
   * Plain SMS template: single line, GSM-7 only (no bullets, smart quotes, HTML).
   */
  function gst_sms_sanitize_template_text(string $text, ?int $maxSeptets = GST_SMS_TEMPLATE_MAX_SEPTETS): string
  {
    $text = strip_tags($text);
    $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = str_replace(
      ["\u{2018}", "\u{2019}", "\u{201A}", "\u{201B}", "\u{2032}", "\u{2035}"],
      "'",
      $text
    );
    $text = str_replace(
      ["\u{201C}", "\u{201D}", "\u{201E}", "\u{201F}", "\u{2033}", "\u{2036}"],
      '"',
      $text
    );
    $text = preg_replace('/[\x{2022}\x{2023}\x{25E6}\x{2043}\x{2219}\x{00B7}\x{25AA}\x{25CF}\x{25CB}\x{2024}\x{204C}\x{204D}\x{221A}\x{2713}\x{2714}\x{2717}\x{2718}]/u', '-', $text) ?? $text;
    $text = preg_replace('/\s+/u', ' ', $text) ?? $text;
    $text = trim($text);

    $allowed = gst_sms_gsm7_charset(false);
    $out = '';
    $chars = preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY);
    if ($chars !== false) {
      foreach ($chars as $ch) {
        if (mb_strpos($allowed, $ch, 0, 'UTF-8') !== false) {
          $out .= $ch;
        }
      }
    }
    if ($maxSeptets !== null && $maxSeptets > 0) {
      $out = gst_sms_gsm7_truncate_to_septets($out, $maxSeptets);
    }
    return $out;
  }
}

if (!function_exists('gst_sms_is_gsm7_string')) {
  /**
   * True if every character is in the GSM 7-bit default alphabet (incl. extension table).
   */
  function gst_sms_is_gsm7_string(string $text): bool
  {
    if ($text === '') {
      return true;
    }
    static $gsm7 = null;
    if ($gsm7 === null) {
      $gsm7 = gst_sms_gsm7_charset(true);
    }
    $chars = preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY);
    if ($chars === false) {
      return false;
    }
    foreach ($chars as $ch) {
      if (mb_strpos($gsm7, $ch, 0, 'UTF-8') === false) {
        return false;
      }
    }
    return true;
  }
}

if (!function_exists('gst_sms_requires_unicode')) {
  function gst_sms_requires_unicode(string $content): bool
  {
    return !gst_sms_is_gsm7_string($content);
  }
}

if (!function_exists('gst_sms_render_template')) {
    /**
     * @param array<string, string|int|float|null> $vars
     */
    function gst_sms_render_template(string $type, array $vars = []): string
    {
        $type = strtolower(trim($type));
        if ($type === 'instantwin_win') {
            $type = 'instantwin_prize';
        }
        $templates = get_sms_templates_config();
        $tpl = trim((string) ($templates[$type] ?? ''));
        if ($tpl === '') {
            return '';
        }
        $map = [
            '{{nome}}' => (string) ($vars['nome'] ?? $vars['USER_NAME'] ?? ''),
            '{{USER_NAME}}' => (string) ($vars['nome'] ?? $vars['USER_NAME'] ?? ''),
            '{{codice}}' => (string) ($vars['codice'] ?? $vars['VOUCHER_CODE'] ?? ''),
            '{{VOUCHER_CODE}}' => (string) ($vars['codice'] ?? $vars['VOUCHER_CODE'] ?? ''),
            '{{contest}}' => (string) ($vars['contest'] ?? $vars['CONTEST_NAME'] ?? ''),
            '{{CONTEST_NAME}}' => (string) ($vars['contest'] ?? $vars['CONTEST_NAME'] ?? ''),
        ];
        $out = strtr($tpl, $map);
        return gst_sms_sanitize_template_text($out);
    }
}

if (!function_exists('gst_sms_resolve_ca_bundle')) {
    /**
     * CA bundle for cURL (WAMP often leaves curl.cainfo empty → SSL errors).
     */
    function gst_sms_resolve_ca_bundle(): string
    {
        foreach ([
            (string) ini_get('curl.cainfo'),
            (string) ini_get('openssl.cafile'),
            dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'certs' . DIRECTORY_SEPARATOR . 'cacert.pem',
            'C:\\wamp64\\apps\\phpmyadmin5.2.3\\vendor\\composer\\ca-bundle\\res\\cacert.pem',
        ] as $candidate) {
            $candidate = trim($candidate);
            if ($candidate !== '' && is_file($candidate) && is_readable($candidate)) {
                return $candidate;
            }
        }

        return '';
    }
}

if (!function_exists('gst_sms_http_json')) {
    /**
     * @param array<string, string> $headers
     * @return array{ok:bool,http:int,body:string,json:?array}
     */
    function gst_sms_http_json(string $method, string $url, ?array $payload = null, array $headers = []): array
    {
        $ch = curl_init($url);
        if ($ch === false) {
            return ['ok' => false, 'http' => 0, 'body' => 'curl_init failed', 'json' => null];
        }
        $hdrs = array_merge(['Accept: application/json'], $headers);
        $opts = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => strtoupper($method),
            CURLOPT_HTTPHEADER => $hdrs,
            CURLOPT_TIMEOUT => 30,
        ];
        $ca = gst_sms_resolve_ca_bundle();
        if ($ca !== '') {
            $opts[CURLOPT_CAINFO] = $ca;
        }
        if ($payload !== null) {
            $json = json_encode($payload, JSON_UNESCAPED_UNICODE);
            $opts[CURLOPT_POSTFIELDS] = $json !== false ? $json : '{}';
            $hdrs[] = 'Content-Type: application/json;charset=utf-8';
            $opts[CURLOPT_HTTPHEADER] = $hdrs;
        }
        curl_setopt_array($ch, $opts);
        $body = curl_exec($ch);
        $http = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        if ($body === false) {
            return ['ok' => false, 'http' => $http, 'body' => $err !== '' ? $err : 'curl error', 'json' => null];
        }
        $decoded = json_decode($body, true);
        return [
            'ok' => $http >= 200 && $http < 300,
            'http' => $http,
            'body' => (string) $body,
            'json' => is_array($decoded) ? $decoded : null,
        ];
    }
}

if (!function_exists('gst_sms_ensure_list_secret')) {
    /**
     * GET ListSecret; POST create if missing (needs username/password + list_guid).
     *
     * @return array{ok:bool,list_secret:string,error:string}
     */
    function gst_sms_ensure_list_secret(array $cfg): array
    {
        $listId = trim((string) ($cfg['list_id'] ?? ''));
        $secret = trim((string) ($cfg['list_secret'] ?? ''));
        if ($secret !== '') {
            return ['ok' => true, 'list_secret' => $secret, 'error' => ''];
        }
        if ($listId === '') {
            return ['ok' => false, 'list_secret' => '', 'error' => 'MAILUP_SMS_LIST_ID mancante'];
        }
        $user = (string) ($cfg['username'] ?? '');
        $pass = (string) ($cfg['password'] ?? '');
        if ($user === '' || $pass === '') {
            return ['ok' => false, 'list_secret' => '', 'error' => 'MAILUP_SMS_LIST_SECRET vuoto e USERNAME/PASSWORD mancanti'];
        }
        $auth = 'Authorization: Basic ' . base64_encode($user . ':' . $pass);
        $url = 'https://sendsms.mailup.com/api/v2.0/lists/' . rawurlencode($listId) . '/listsecret';
        $get = gst_sms_http_json('GET', $url, null, [$auth]);
        $existing = is_array($get['json']['Data']['ListSecret'] ?? null)
            ? ''
            : trim((string) ($get['json']['Data']['ListSecret'] ?? ''));
        // API may return quoted GUID string
        if ($existing === '' && isset($get['json']['Data']['ListSecret'])) {
            $existing = trim((string) $get['json']['Data']['ListSecret'], " \t\n\r\0\x0B\"");
        }
        if ($existing !== '' && strtolower($existing) !== 'null') {
            return ['ok' => true, 'list_secret' => $existing, 'error' => ''];
        }
        $guid = trim((string) ($cfg['list_guid'] ?? ''));
        if ($guid === '') {
            return ['ok' => false, 'list_secret' => '', 'error' => 'MAILUP_SMS_LIST_GUID richiesto per creare ListSecret'];
        }
        $post = gst_sms_http_json('POST', $url, ['ListGUID' => $guid], [$auth]);
        $created = trim((string) ($post['json']['Data']['ListSecret'] ?? ''), " \t\n\r\0\x0B\"");
        if ($created === '' || strtolower($created) === 'null') {
            return [
                'ok' => false,
                'list_secret' => '',
                'error' => 'Creazione ListSecret fallita HTTP ' . $post['http'] . ': ' . substr($post['body'], 0, 200),
            ];
        }
        return ['ok' => true, 'list_secret' => $created, 'error' => ''];
    }
}

if (!function_exists('send_sms_mailup')) {
    /**
     * @return array{ok:bool,delivery_id:int,cost:float,error:string,http:int,app_code:string,raw:string}
     */
    function send_sms_mailup(string $recipient, string $content, string $campaignCode = 'gst-con'): array
    {
        $empty = [
            'ok' => false,
            'delivery_id' => 0,
            'cost' => 0.0,
            'error' => '',
            'http' => 0,
            'app_code' => '',
            'raw' => '',
        ];
        $recipient = trim($recipient);
        $content = trim($content);
        if ($recipient === '' || $content === '') {
            $empty['error'] = 'Recipient o Content vuoto';
            return $empty;
        }
        $cfg = gst_sms_config_from_env();
        $accountId = $cfg['account_id'];
        $listId = $cfg['list_id'];
        $listGuid = $cfg['list_guid'];
        if ($accountId === '' || $listId === '' || $listGuid === '') {
            $empty['error'] = 'MAILUP_SMS_ACCOUNT_ID / LIST_ID / LIST_GUID mancanti nel .env';
            return $empty;
        }
        $secretRes = gst_sms_ensure_list_secret($cfg);
        if (!$secretRes['ok']) {
            $empty['error'] = $secretRes['error'];
            return $empty;
        }
        $payload = [
            'Content' => $content,
            'ListGuid' => $listGuid,
            'ListSecret' => $secretRes['list_secret'],
            'Recipient' => $recipient,
            'CampaignCode' => $campaignCode !== '' ? $campaignCode : 'gst-con',
            'isUnicode' => gst_sms_requires_unicode($content) ? 1 : 0,
        ];
        $sender = preg_replace('/[^a-zA-Z0-9]/', '', $cfg['sender'] ?? '') ?? '';
        if ($sender !== '') {
            $payload['Sender'] = substr($sender, 0, 11);
        }
        $url = 'https://sendsms.mailup.com/api/v2.0/sms/'
            . rawurlencode($accountId) . '/'
            . rawurlencode($listId);
        $res = gst_sms_http_json('POST', $url, $payload);
        $appCode = (string) ($res['json']['Code'] ?? '');
        $ok = $res['ok'] && ($appCode === '0' || $appCode === '301');
        $deliveryId = (int) ($res['json']['Data']['DeliveryId'] ?? 0);
        $cost = (float) ($res['json']['Data']['Cost'] ?? 0);
        $error = '';
        if (!$ok) {
            $desc = trim((string) ($res['json']['Description'] ?? ''));
            $error = 'HTTP ' . $res['http'] . ' Code ' . $appCode
                . ($desc !== '' ? (': ' . $desc) : '')
                . ' ' . substr($res['body'], 0, 180);
            $error = trim($error);
        }
        return [
            'ok' => $ok,
            'delivery_id' => $deliveryId,
            'cost' => $cost,
            'error' => $error,
            'http' => $res['http'],
            'app_code' => $appCode,
            'raw' => $res['body'],
        ];
    }
}

if (!function_exists('gst_sms_columns_exist')) {
    function gst_sms_columns_exist(PDO $pdo): bool
    {
        static $cache = null;
        if ($cache !== null) {
            return $cache;
        }
        try {
            $col = $pdo->query("SHOW COLUMNS FROM partecipazioni LIKE 'sms_inviato'")->fetch(PDO::FETCH_ASSOC);
            $cache = $col !== false;
        } catch (Throwable $e) {
            $cache = false;
        }
        return $cache;
    }
}

if (!function_exists('gst_sms_update_participation_status')) {
    function gst_sms_update_participation_status(PDO $pdo, int $participationId, bool $sent, ?string $error): void
    {
        if ($participationId < 1 || !gst_sms_columns_exist($pdo)) {
            return;
        }
        $err = $error !== null && $error !== '' ? substr($error, 0, 255) : null;
        $stmt = $pdo->prepare('
            UPDATE partecipazioni
            SET sms_inviato = :sent, sms_error = :error
            WHERE id = :id
        ');
        $stmt->bindValue(':sent', $sent ? 1 : 0, PDO::PARAM_INT);
        $stmt->bindValue(':error', $err, $err === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':id', $participationId, PDO::PARAM_INT);
        $stmt->execute();
    }
}

if (!function_exists('gst_sms_try_send_for_participation')) {
    /**
     * @param array{nome?:string,prefisso?:string,cellulare?:string,codice?:string,contest?:string} $context
     * @return array{attempted:bool,ok:bool,error:string}
     */
    function gst_sms_try_send_for_participation(PDO $pdo, int $participationId, string $type, array $context = []): array
    {
        $result = ['attempted' => false, 'ok' => false, 'error' => ''];
        if (!should_send_sms_type($type)) {
            return $result;
        }
        global $contest_config;
        $nome = (string) ($context['nome'] ?? '');
        $prefisso = (string) ($context['prefisso'] ?? '');
        $cellulare = (string) ($context['cellulare'] ?? '');
        $codice = (string) ($context['codice'] ?? '');
        $contest = (string) ($context['contest'] ?? ($contest_config['name'] ?? ''));

        if (($prefisso === '' || $cellulare === '') && $participationId > 0) {
            try {
                $stmt = $pdo->prepare('
                    SELECT u.nome, u.prefisso, u.cellulare
                    FROM partecipazioni p
                    JOIN utenti u ON u.id_cliente = p.id_cliente
                    WHERE p.id = :id
                    LIMIT 1
                ');
                $stmt->execute([':id' => $participationId]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if (is_array($row)) {
                    if ($nome === '') {
                        $nome = (string) ($row['nome'] ?? '');
                    }
                    if ($prefisso === '') {
                        $prefisso = (string) ($row['prefisso'] ?? '');
                    }
                    if ($cellulare === '') {
                        $cellulare = (string) ($row['cellulare'] ?? '');
                    }
                }
            } catch (Throwable $e) {
                // ignore
            }
        }

        $recipient = gst_sms_format_recipient($prefisso, $cellulare);
        if ($recipient === '') {
            $result['attempted'] = true;
            $result['error'] = 'Numero cellulare assente o non valido';
            gst_sms_update_participation_status($pdo, $participationId, false, $result['error']);
            return $result;
        }

        $content = gst_sms_render_template($type, [
            'nome' => $nome,
            'codice' => $codice,
            'contest' => $contest,
        ]);
        if ($content === '') {
            $result['attempted'] = true;
            $result['error'] = 'Template SMS vuoto per tipo ' . $type;
            gst_sms_update_participation_status($pdo, $participationId, false, $result['error']);
            return $result;
        }

        $result['attempted'] = true;
        $send = send_sms_mailup($recipient, $content, gst_sms_resolve_campaign_code($type));
        if ($send['ok']) {
            $result['ok'] = true;
            gst_sms_update_participation_status($pdo, $participationId, true, null);
            return $result;
        }
        $result['error'] = $send['error'] !== '' ? $send['error'] : 'Invio SMS fallito';
        gst_sms_update_participation_status($pdo, $participationId, false, $result['error']);
        error_log('gst_sms_try_send_for_participation: ' . $result['error']);
        return $result;
    }
}
