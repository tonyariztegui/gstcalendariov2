<?php

/**
 * Cookie banner + tracking (GTM / Meta) — config resolve e gate per pagina.
 */

if (!function_exists('cookie_consent_default_page_keys')) {
    /** @return array<string, string> key => label manager */
    function cookie_consent_default_page_keys(): array
    {
        return [
            'registration' => 'Registrazione',
            'waiting' => 'Pagina attesa',
            'waiting_ended' => 'Pagina attesa — Fine',
            'participation' => 'Pagina partecipazione',
            'thankyou' => 'Thank-you',
            'help' => 'Assistenza / FAQ',
            'quiz' => 'Quiz',
            'error' => 'Errore',
        ];
    }
}

if (!function_exists('cookie_consent_normalize_cookie_row')) {
    /**
     * @param mixed $row
     * @return array{name: string, purpose: string, duration: string, provider: string}|null
     */
    function cookie_consent_normalize_cookie_row($row): ?array
    {
        if (!is_array($row)) {
            return null;
        }
        $name = trim((string) ($row['name'] ?? ''));
        if ($name === '') {
            return null;
        }

        return [
            'name' => $name,
            'purpose' => trim((string) ($row['purpose'] ?? '')),
            'duration' => trim((string) ($row['duration'] ?? '')),
            'provider' => trim((string) ($row['provider'] ?? '')),
        ];
    }
}

if (!function_exists('cookie_consent_normalize_cookie_list')) {
    /**
     * @param mixed $list
     * @param list<array<string, string>> $fallback
     * @return list<array{name: string, purpose: string, duration: string, provider: string}>
     */
    function cookie_consent_normalize_cookie_list($list, array $fallback = []): array
    {
        $source = is_array($list) ? $list : $fallback;
        $out = [];
        foreach ($source as $row) {
            $norm = cookie_consent_normalize_cookie_row($row);
            if ($norm !== null) {
                $out[] = $norm;
            }
        }

        return $out;
    }
}

if (!function_exists('cookie_consent_sanitize_storage_name')) {
    /**
     * Nome cookie preferenze: solo caratteri sicuri per Set-Cookie.
     */
    function cookie_consent_sanitize_storage_name(string $name, string $fallback = 'gst_cookie_consent'): string
    {
        $name = trim($name);
        $name = (string) preg_replace('/[^a-zA-Z0-9_-]+/', '_', $name);
        $name = trim($name, '_-');
        if ($name === '' || strcasecmp($name, 'PHPSESSID') === 0) {
            $fallback = (string) preg_replace('/[^a-zA-Z0-9_-]+/', '_', $fallback);
            $fallback = trim($fallback, '_-');

            return $fallback !== '' ? $fallback : 'gst_cookie_consent';
        }

        return $name;
    }
}

if (!function_exists('cookie_consent_resolve_storage_name')) {
    /**
     * @param array<string, mixed> $cc normalized cookie_consent
     */
    function cookie_consent_resolve_storage_name(array $cc): string
    {
        $configured = trim((string) ($cc['consent_cookie_name'] ?? ''));
        if ($configured !== '') {
            return cookie_consent_sanitize_storage_name($configured);
        }
        $technical = is_array($cc['cookies']['technical'] ?? null) ? $cc['cookies']['technical'] : [];
        foreach ($technical as $row) {
            if (!is_array($row)) {
                continue;
            }
            $name = trim((string) ($row['name'] ?? ''));
            $purpose = strtolower((string) ($row['purpose'] ?? ''));
            if ($name === '' || strcasecmp($name, 'PHPSESSID') === 0) {
                continue;
            }
            if (
                str_contains($purpose, 'preferenze')
                || str_contains($purpose, 'consent')
                || preg_match('/cookie_consent|cookie_pixel/i', $name)
            ) {
                return cookie_consent_sanitize_storage_name($name);
            }
        }

        return 'gst_cookie_consent';
    }
}

if (!function_exists('cookie_consent_core_technical_kind')) {
    /**
     * Identifica le righe tecniche obbligatorie del sito pubblico.
     *
     * @param array<string, mixed> $row
     * @return 'session'|'consent'|null
     */
    function cookie_consent_core_technical_kind(array $row, string $consentName = ''): ?string
    {
        $name = trim((string) ($row['name'] ?? ''));
        $purpose = strtolower((string) ($row['purpose'] ?? ''));
        if ($name !== '' && strcasecmp($name, 'PHPSESSID') === 0) {
            return 'session';
        }
        if ($name === '' || strcasecmp($name, 'PHPSESSID') === 0) {
            return null;
        }
        $consentName = trim($consentName);
        if ($consentName !== '' && strcasecmp($name, $consentName) === 0) {
            return 'consent';
        }
        if (
            str_contains($purpose, 'preferenze')
            || str_contains($purpose, 'consent')
            || preg_match('/cookie_consent|cookie_pixel/i', $name)
            || strcasecmp($name, 'gst_cookie_consent') === 0
        ) {
            return 'consent';
        }

        return null;
    }
}

if (!function_exists('cookie_consent_ensure_core_technical')) {
    /**
     * Garantisce PHPSESSID + cookie preferenze (non eliminabili).
     *
     * @param array<int, mixed> $technical
     * @return list<array{name: string, purpose: string, duration: string, provider: string}>
     */
    function cookie_consent_ensure_core_technical(array $technical, string $consentName): array
    {
        $consentName = cookie_consent_sanitize_storage_name($consentName);
        $sessionRow = [
            'name' => 'PHPSESSID',
            'purpose' => 'Sessione utente',
            'duration' => 'Sessione',
            'provider' => 'Sito',
        ];
        $consentRow = [
            'name' => $consentName,
            'purpose' => 'Memorizzazione preferenze cookie',
            'duration' => '90 giorni',
            'provider' => 'Sito',
        ];
        $extras = [];
        $hasSession = false;
        $hasConsent = false;

        foreach ($technical as $row) {
            if (!is_array($row)) {
                continue;
            }
            $norm = cookie_consent_normalize_cookie_row($row);
            if ($norm === null) {
                continue;
            }
            $kind = cookie_consent_core_technical_kind($norm, $consentName);
            if ($kind === 'session') {
                if ($hasSession) {
                    continue;
                }
                $sessionRow['purpose'] = $norm['purpose'] !== '' ? $norm['purpose'] : $sessionRow['purpose'];
                $sessionRow['duration'] = $norm['duration'] !== '' ? $norm['duration'] : $sessionRow['duration'];
                $sessionRow['provider'] = $norm['provider'] !== '' ? $norm['provider'] : $sessionRow['provider'];
                $hasSession = true;
                continue;
            }
            if ($kind === 'consent') {
                if ($hasConsent) {
                    continue;
                }
                $consentRow['purpose'] = $norm['purpose'] !== '' ? $norm['purpose'] : $consentRow['purpose'];
                $consentRow['duration'] = $norm['duration'] !== '' ? $norm['duration'] : $consentRow['duration'];
                $consentRow['provider'] = $norm['provider'] !== '' ? $norm['provider'] : $consentRow['provider'];
                $hasConsent = true;
                continue;
            }
            $extras[] = $norm;
        }

        return array_merge([$sessionRow, $consentRow], $extras);
    }
}

if (!function_exists('cookie_consent_normalize')) {
    /**
     * @param mixed $raw
     * @param array<string, mixed> $defaults
     * @return array<string, mixed>
     */
    function cookie_consent_normalize($raw, array $defaults = []): array
    {
        $raw = is_array($raw) ? $raw : [];
        $defBanner = is_array($defaults['banner'] ?? null) ? $defaults['banner'] : [];
        $defStyle = is_array($defaults['style'] ?? null) ? $defaults['style'] : [];
        $defTracking = is_array($defaults['tracking'] ?? null) ? $defaults['tracking'] : [];
        $defCookies = is_array($defaults['cookies'] ?? null) ? $defaults['cookies'] : [];
        $defPages = is_array($defaults['pages'] ?? null) ? $defaults['pages'] : [];

        $bannerIn = is_array($raw['banner'] ?? null) ? $raw['banner'] : [];
        $styleIn = is_array($raw['style'] ?? null) ? $raw['style'] : [];
        $trackingIn = is_array($raw['tracking'] ?? null) ? $raw['tracking'] : [];
        $cookiesIn = is_array($raw['cookies'] ?? null) ? $raw['cookies'] : [];
        $pagesIn = is_array($raw['pages'] ?? null) ? $raw['pages'] : [];

        $hexOr = static function ($value, string $fallback): string {
            $v = trim((string) $value);
            if (preg_match('/^#[0-9A-Fa-f]{6}$/', $v)) {
                return $v;
            }

            return preg_match('/^#[0-9A-Fa-f]{6}$/', $fallback) ? $fallback : '#333333';
        };

        $bannerKeys = [
            'text', 'accept_label', 'preferences_label', 'save_label',
            'necessary_title', 'necessary_desc', 'marketing_title', 'marketing_desc', 'policy_link_label',
        ];
        $banner = [];
        foreach ($bannerKeys as $k) {
            $banner[$k] = trim((string) ($bannerIn[$k] ?? ($defBanner[$k] ?? '')));
        }

        $styleKeys = [
            'bg_color', 'text_color', 'link_color', 'border_color',
            'button_bg', 'button_text', 'button_secondary_bg', 'button_secondary_text',
        ];
        $style = [];
        foreach ($styleKeys as $k) {
            $style[$k] = $hexOr($styleIn[$k] ?? null, (string) ($defStyle[$k] ?? '#333333'));
        }

        $pages = [];
        foreach (cookie_consent_default_page_keys() as $pageKey => $_label) {
            $pageDef = is_array($defPages[$pageKey] ?? null) ? $defPages[$pageKey] : ['banner' => true, 'tracking' => true];
            $pageCur = is_array($pagesIn[$pageKey] ?? null) ? $pagesIn[$pageKey] : [];
            $pages[$pageKey] = [
                'banner' => array_key_exists('banner', $pageCur)
                    ? (bool) $pageCur['banner']
                    : (bool) ($pageDef['banner'] ?? true),
                'tracking' => array_key_exists('tracking', $pageCur)
                    ? (bool) $pageCur['tracking']
                    : (bool) ($pageDef['tracking'] ?? true),
            ];
        }

        $consentCookieName = cookie_consent_sanitize_storage_name(
            trim((string) ($raw['consent_cookie_name'] ?? ($defaults['consent_cookie_name'] ?? 'gst_cookie_consent'))),
            'gst_cookie_consent'
        );

        return [
            'enabled' => !empty($raw['enabled']),
            'sync_registration_marketing' => array_key_exists('sync_registration_marketing', $raw)
                ? (bool) $raw['sync_registration_marketing']
                : (bool) ($defaults['sync_registration_marketing'] ?? true),
            'consent_cookie_name' => $consentCookieName,
            'banner' => $banner,
            'style' => $style,
            'tracking' => [
                'gtm_container_id' => trim((string) ($trackingIn['gtm_container_id'] ?? ($defTracking['gtm_container_id'] ?? ''))),
                'meta_pixel_id' => trim((string) ($trackingIn['meta_pixel_id'] ?? ($defTracking['meta_pixel_id'] ?? ''))),
                'tiktok_pixel_id' => trim((string) ($trackingIn['tiktok_pixel_id'] ?? ($defTracking['tiktok_pixel_id'] ?? ''))),
            ],
            'cookies' => [
                'technical' => cookie_consent_ensure_core_technical(
                    cookie_consent_normalize_cookie_list(
                        $cookiesIn['technical'] ?? null,
                        is_array($defCookies['technical'] ?? null) ? $defCookies['technical'] : []
                    ),
                    $consentCookieName
                ),
                'marketing' => cookie_consent_normalize_cookie_list(
                    $cookiesIn['marketing'] ?? null,
                    is_array($defCookies['marketing'] ?? null) ? $defCookies['marketing'] : []
                ),
            ],
            'pages' => $pages,
        ];
    }
}

if (!function_exists('cookie_consent_resolve')) {
    /**
     * @param array<string, mixed> $contestConfig
     * @param array<string, mixed> $defaults
     * @return array<string, mixed>
     */
    function cookie_consent_resolve(array $contestConfig, array $defaults = []): array
    {
        $raw = $contestConfig['cookie_consent'] ?? ($defaults['cookie_consent'] ?? []);
        $def = is_array($defaults['cookie_consent'] ?? null) ? $defaults['cookie_consent'] : [];

        return cookie_consent_normalize($raw, $def);
    }
}

if (!function_exists('cookie_consent_detect_page_key')) {
    /**
     * Risolve la chiave pagina cookie (registration, waiting, …).
     * Supporta sia gli script .php diretti sia le pretty URL via index.php?route=.
     */
    function cookie_consent_detect_page_key(?string $override = null): string
    {
        if (is_string($override) && $override !== '') {
            return $override;
        }
        if (isset($GLOBALS['cookie_consent_page_key']) && is_string($GLOBALS['cookie_consent_page_key']) && $GLOBALS['cookie_consent_page_key'] !== '') {
            return $GLOBALS['cookie_consent_page_key'];
        }

        $scriptMap = [
            'registration.php' => 'registration',
            'waiting_page.php' => 'waiting',
            'waiting_page_ended.php' => 'waiting_ended',
            'participation_page.php' => 'participation',
            'thank-you.php' => 'thankyou',
            'help.php' => 'help',
            'quiz_flow.php' => 'quiz',
            'error.php' => 'error',
        ];

        $routeMap = [
            'registrazione' => 'registration',
            'attesa' => 'waiting',
            'concorso-terminato' => 'waiting_ended',
            'partecipazione' => 'participation',
            'grazie' => 'thankyou',
            'assistenza' => 'help',
            'quiz' => 'quiz',
            'errore' => 'error',
            'non-consentito' => 'error',
        ];

        $script = strtolower(basename((string) ($_SERVER['SCRIPT_NAME'] ?? '')));
        if (isset($scriptMap[$script])) {
            return $scriptMap[$script];
        }

        $route = '';
        if (isset($_GET['route'])) {
            $route = strtolower(trim((string) $_GET['route']));
            $route = preg_replace('/\.php$/i', '', $route);
            $route = preg_replace('/[^a-z0-9_-]/', '', $route);
        }
        if ($route === '' && function_exists('app_public_route_from_request')) {
            $fromRouter = app_public_route_from_request();
            if (is_string($fromRouter) && $fromRouter !== '') {
                $route = strtolower($fromRouter);
            }
        }
        if ($route !== '' && isset($routeMap[$route])) {
            return $routeMap[$route];
        }

        // Fallback: ultimo segmento del path (es. /public/registrazione)
        $uriPath = (string) (parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH) ?? '');
        $segment = strtolower(basename(rtrim($uriPath, '/')));
        if ($segment !== '' && isset($routeMap[$segment])) {
            return $routeMap[$segment];
        }
        if ($segment !== '' && isset($scriptMap[$segment])) {
            return $scriptMap[$segment];
        }

        // Home pubblica via index.php (senza ?route= / slug) → registrazione di default
        if ($script === 'index.php') {
            return 'registration';
        }

        return '';
    }
}

if (!function_exists('cookie_consent_is_enabled')) {
    function cookie_consent_is_enabled(array $cc): bool
    {
        return !empty($cc['enabled']);
    }
}

if (!function_exists('cookie_consent_is_banner_active_on_page')) {
    function cookie_consent_is_banner_active_on_page(array $cc, ?string $pageKey = null): bool
    {
        if (!cookie_consent_is_enabled($cc)) {
            return false;
        }
        $key = cookie_consent_detect_page_key($pageKey);
        if ($key === '') {
            return false;
        }
        $pages = is_array($cc['pages'] ?? null) ? $cc['pages'] : [];

        return !empty($pages[$key]['banner']);
    }
}

if (!function_exists('cookie_consent_is_tracking_active_on_page')) {
    function cookie_consent_is_tracking_active_on_page(array $cc, ?string $pageKey = null): bool
    {
        if (!cookie_consent_is_enabled($cc)) {
            return false;
        }
        $key = cookie_consent_detect_page_key($pageKey);
        if ($key === '') {
            return false;
        }
        $pages = is_array($cc['pages'] ?? null) ? $cc['pages'] : [];

        return !empty($pages[$key]['tracking']);
    }
}

if (!function_exists('cookie_consent_has_marketing_from_request')) {
    function cookie_consent_has_marketing_from_request(?array $cc = null): bool
    {
        $names = [];
        if (is_array($cc)) {
            $names[] = cookie_consent_resolve_storage_name($cc);
        } else {
            global $contest_config;
            if (is_array($contest_config['cookie_consent'] ?? null)) {
                $names[] = cookie_consent_resolve_storage_name($contest_config['cookie_consent']);
            }
        }
        $names[] = 'gst_cookie_consent'; // legacy fallback
        $names = array_values(array_unique(array_filter($names)));

        foreach ($names as $cookieName) {
            $raw = isset($_COOKIE[$cookieName]) ? (string) $_COOKIE[$cookieName] : '';
            if ($raw === '') {
                continue;
            }
            $decoded = json_decode($raw, true);
            if (!is_array($decoded)) {
                continue;
            }
            if (!empty($decoded['marketing']) && (int) ($decoded['v'] ?? 0) === 1) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('cookie_consent_set_cookie')) {
    function cookie_consent_set_cookie(bool $marketing, ?array $cc = null): void
    {
        $payload = json_encode([
            'v' => 1,
            'necessary' => true,
            'marketing' => $marketing,
            'ts' => date('c'),
        ], JSON_UNESCAPED_SLASHES);
        if ($payload === false) {
            return;
        }
        if ($cc === null) {
            global $contest_config;
            $cc = is_array($contest_config['cookie_consent'] ?? null) ? $contest_config['cookie_consent'] : [];
        }
        $cookieName = cookie_consent_resolve_storage_name(is_array($cc) ? $cc : []);
        $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
        setcookie($cookieName, $payload, [
            'expires' => time() + (60 * 60 * 24 * 90),
            'path' => '/',
            'secure' => $secure,
            'httponly' => false,
            'samesite' => 'Lax',
        ]);
        $_COOKIE[$cookieName] = $payload;
    }
}

if (!function_exists('cookie_consent_render_cookie_table_html')) {
    /**
     * @param list<array{name: string, purpose: string, duration: string, provider: string}> $rows
     */
    function cookie_consent_render_cookie_table_html(array $rows): string
    {
        if ($rows === []) {
            return '';
        }
        $html = '<div class="cc-cookie-list"><table class="cc-cookie-table"><thead><tr>';
        $html .= '<th>Nome</th><th>Finalità</th><th>Durata</th><th>Fornitore</th>';
        $html .= '</tr></thead><tbody>';
        foreach ($rows as $row) {
            $html .= '<tr>';
            $html .= '<td>' . htmlspecialchars((string) ($row['name'] ?? ''), ENT_QUOTES, 'UTF-8') . '</td>';
            $html .= '<td>' . htmlspecialchars((string) ($row['purpose'] ?? ''), ENT_QUOTES, 'UTF-8') . '</td>';
            $html .= '<td>' . htmlspecialchars((string) ($row['duration'] ?? ''), ENT_QUOTES, 'UTF-8') . '</td>';
            $html .= '<td>' . htmlspecialchars((string) ($row['provider'] ?? ''), ENT_QUOTES, 'UTF-8') . '</td>';
            $html .= '</tr>';
        }
        $html .= '</tbody></table></div>';

        return $html;
    }
}

if (!function_exists('cookie_consent_resolve_runtime_style')) {
    /**
     * Couleurs banner: palette si style_customization.cookie_banner_colors=false, sinon cookie_consent.style.
     * Alignement palette (comme le reste du site) :
     * - Primario → bottoni principali + bordo accent
     * - Secondario → link + bottone secondario (riposo)
     * - Terziario → hover link e bottone secondario
     * - Testo → testo
     *
     * @param array<string, mixed> $contestConfig
     * @return array{
     *   bg_color: string,
     *   text_color: string,
     *   link_color: string,
     *   link_hover: string,
     *   border_color: string,
     *   button_bg: string,
     *   button_text: string,
     *   button_secondary_bg: string,
     *   button_secondary_text: string,
     *   button_secondary_hover: string
     * }
     */
    function cookie_consent_resolve_runtime_style(array $contestConfig): array
    {
        $cc = is_array($contestConfig['cookie_consent'] ?? null) ? $contestConfig['cookie_consent'] : [];
        $stored = is_array($cc['style'] ?? null) ? $cc['style'] : [];
        $custom = !empty($contestConfig['style_customization']['cookie_banner_colors']);

        $hex = static function ($value, string $fallback): string {
            $v = trim((string) $value);
            return preg_match('/^#[0-9A-Fa-f]{6}$/', $v) ? $v : $fallback;
        };

        $colors = is_array($contestConfig['colors'] ?? null) ? $contestConfig['colors'] : [];
        $primary = $hex($colors['primary'] ?? null, '#0d6efd');
        $secondary = $hex($colors['secondary'] ?? null, '#6c757d');
        $tertiary = $hex($colors['tertiary'] ?? null, '#0a58ca');
        $text = $hex($colors['text'] ?? null, '#212529');

        if ($custom) {
            $link = $hex($stored['link_color'] ?? null, $secondary);
            $btn2Text = $hex($stored['button_secondary_text'] ?? null, $secondary);

            return [
                'bg_color' => $hex($stored['bg_color'] ?? null, '#ffffff'),
                'text_color' => $hex($stored['text_color'] ?? null, $text),
                'link_color' => $link,
                'link_hover' => $hex($stored['link_hover'] ?? null, $tertiary),
                'border_color' => $hex($stored['border_color'] ?? null, $primary),
                'button_bg' => $hex($stored['button_bg'] ?? null, $primary),
                'button_text' => $hex($stored['button_text'] ?? null, '#ffffff'),
                'button_secondary_bg' => $hex($stored['button_secondary_bg'] ?? null, '#ffffff'),
                'button_secondary_text' => $btn2Text,
                'button_secondary_hover' => $hex($stored['button_secondary_hover'] ?? null, $tertiary),
            ];
        }

        $btnPrimaryBg = $primary;
        $btnPrimaryText = '#ffffff';
        $btnSecondaryBg = '#ffffff';
        $btnSecondaryText = $secondary;
        if (!empty($contestConfig['style_customization']['buttons_colors'])) {
            $buttons = is_array($contestConfig['buttons'] ?? null) ? $contestConfig['buttons'] : [];
            $primaryBtn = is_array($buttons['primary'] ?? null) ? $buttons['primary'] : [];
            $secondaryBtn = is_array($buttons['secondary'] ?? null) ? $buttons['secondary'] : [];
            $btnPrimaryBg = $hex($primaryBtn['bg'] ?? null, $primary);
            $btnPrimaryText = $hex($primaryBtn['text_color'] ?? null, '#ffffff');
            $btnSecondaryBg = $hex($secondaryBtn['bg'] ?? null, '#ffffff');
            $btnSecondaryText = $hex($secondaryBtn['text_color'] ?? null, $secondary);
        }

        return [
            'bg_color' => '#ffffff',
            'text_color' => $text,
            'link_color' => $secondary,
            'link_hover' => $tertiary,
            'border_color' => $primary,
            'button_bg' => $btnPrimaryBg,
            'button_text' => $btnPrimaryText,
            'button_secondary_bg' => $btnSecondaryBg,
            'button_secondary_text' => $btnSecondaryText,
            'button_secondary_hover' => $tertiary,
        ];
    }
}

if (!function_exists('cookie_consent_policy_href')) {
    function cookie_consent_policy_href(array $contestConfig): string
    {
        $cookiePolicy = trim((string) ($contestConfig['cookie_policy_link'] ?? ''));
        if ($cookiePolicy !== '') {
            return $cookiePolicy;
        }
        $privacy = trim((string) ($contestConfig['privacy_link'] ?? ''));

        return $privacy !== '' ? $privacy : '#';
    }
}
