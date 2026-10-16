<?php
date_default_timezone_set('Europe/Rome');


// Sonar S1192 — shared literals (config.php)
if (!defined('CONFIG_REGEX_HTTP_URL')) {
    define('CONFIG_REGEX_HTTP_URL', '#^https?://#i');
}
if (!defined('CONFIG_REGEX_HEX6')) {
    define('CONFIG_REGEX_HEX6', '/^#[0-9A-Fa-f]{6}$/');
}
if (!defined('CONFIG_REGEX_HEX6_LOWER')) {
    define('CONFIG_REGEX_HEX6_LOWER', '/^#[0-9a-f]{6}$/');
}
if (!defined('CONFIG_COLOR_WHITE')) {
    define('CONFIG_COLOR_WHITE', '#ffffff');
}
if (!defined('CONFIG_COLOR_BLACK')) {
    define('CONFIG_COLOR_BLACK', '#000000');
}
if (!defined('CONFIG_COLOR_TEXT')) {
    define('CONFIG_COLOR_TEXT', '#212529');
}
if (!defined('CONFIG_COLOR_PRIMARY')) {
    define('CONFIG_COLOR_PRIMARY', '#0d6efd');
}
if (!defined('CONFIG_COLOR_PRIMARY_DARK')) {
    define('CONFIG_COLOR_PRIMARY_DARK', '#0a58ca');
}
if (!defined('CONFIG_COLOR_PRIMARY_HOVER')) {
    define('CONFIG_COLOR_PRIMARY_HOVER', '#0b5ed7');
}
if (!defined('CONFIG_COLOR_SECONDARY')) {
    define('CONFIG_COLOR_SECONDARY', '#6c757d');
}
if (!defined('CONFIG_COLOR_SECONDARY_DARK')) {
    define('CONFIG_COLOR_SECONDARY_DARK', '#5c636a');
}
if (!defined('CONFIG_COLOR_BORDER')) {
    define('CONFIG_COLOR_BORDER', '#dee2e6');
}
if (!defined('CONFIG_COLOR_SUCCESS')) {
    define('CONFIG_COLOR_SUCCESS', '#198754');
}
if (!defined('CONFIG_COLOR_SUCCESS_DARK')) {
    define('CONFIG_COLOR_SUCCESS_DARK', '#157347');
}
if (!defined('CONFIG_RGBA_OVERLAY_45')) {
    define('CONFIG_RGBA_OVERLAY_45', 'rgba(0, 0, 0, 0.45)');
}
if (!defined('CONFIG_SCHEME_HTTPS')) {
    define('CONFIG_SCHEME_HTTPS', 'https://');
}
if (!defined('CONFIG_SCHEME_HTTP')) {
    define('CONFIG_SCHEME_HTTP', 'http://');
}
if (!defined('CONFIG_CSS_BG_IMAGE_URL')) {
    define('CONFIG_CSS_BG_IMAGE_URL', "background-image: url('");
}
if (!defined('CONFIG_CSS_IMPORTANT_SPACE')) {
    define('CONFIG_CSS_IMPORTANT_SPACE', ' !important; ');
}
if (!defined('CONFIG_CSS_IMPORTANT_NL')) {
    define('CONFIG_CSS_IMPORTANT_NL', " !important;\n");
}
if (!defined('CONFIG_CSS_IMPORTANT_CLOSE')) {
    define('CONFIG_CSS_IMPORTANT_CLOSE', " !important; }\n");
}
if (!defined('CONFIG_CSS_RULE_CLOSE')) {
    define('CONFIG_CSS_RULE_CLOSE', " }\n");
}
if (!defined('CONFIG_CSS_URL_QUOTE')) {
    define('CONFIG_CSS_URL_QUOTE', "url('");
}
if (!defined('CONFIG_CSS_BG_REPEAT_NONE')) {
    define('CONFIG_CSS_BG_REPEAT_NONE', " background-repeat: no-repeat !important;\n");
}
if (!defined('CONFIG_CSS_COLOR_TRANSPARENT')) {
    define('CONFIG_CSS_COLOR_TRANSPARENT', " color: transparent !important;\n");
}
if (!defined('CONFIG_CSS_TEXT_SHADOW_NONE')) {
    define('CONFIG_CSS_TEXT_SHADOW_NONE', " text-shadow: none !important;\n");
}
if (!defined('CONFIG_CSS_BG_IMAGE_NONE')) {
    define('CONFIG_CSS_BG_IMAGE_NONE', " background-image: none !important;\n");
}
if (!defined('CONFIG_CSS_BG_COLOR_TRANSPARENT')) {
    define('CONFIG_CSS_BG_COLOR_TRANSPARENT', " background-color: transparent !important;\n");
}
if (!defined('CONFIG_CSS_BOX_SHADOW_NONE')) {
    define('CONFIG_CSS_BOX_SHADOW_NONE', " box-shadow: none !important;\n");
}
if (!defined('CONFIG_CSS_COLOR_PREFIX')) {
    define('CONFIG_CSS_COLOR_PREFIX', ' color: ');
}
if (!defined('CONFIG_CSS_BG_COLOR_PREFIX')) {
    define('CONFIG_CSS_BG_COLOR_PREFIX', ' background-color: ');
}
if (!defined('CONFIG_CSS_BG_COLOR_PREFIX_NOSPACE')) {
    define('CONFIG_CSS_BG_COLOR_PREFIX_NOSPACE', 'background-color: ');
}
if (!defined('CONFIG_CSS_FORM_FIELD_TEXT')) {
    define('CONFIG_CSS_FORM_FIELD_TEXT', " color: var(--form-field-text) !important;\n");
}
if (!defined('CONFIG_CSS_FORM_FIELD_FILL')) {
    define('CONFIG_CSS_FORM_FIELD_FILL', " -webkit-text-fill-color: var(--form-field-text) !important;\n");
}
if (!defined('CONFIG_CSS_MARGIN_BOX')) {
    define('CONFIG_CSS_MARGIN_BOX', 'margin: %dpx %dpx %dpx %dpx; ');
}
if (!defined('CONFIG_CSS_RADIUS_BOX')) {
    define('CONFIG_CSS_RADIUS_BOX', 'border-radius: %dpx %dpx %dpx %dpx; ');
}
if (!defined('CONFIG_CLASS_CARD_AUTO')) {
    define('CONFIG_CLASS_CARD_AUTO', 'card mx-auto w-100');
}
if (!defined('CONFIG_CLASS_CARD_BODY_CENTER')) {
    define('CONFIG_CLASS_CARD_BODY_CENTER', 'card-body text-center py-4 py-md-5');
}
if (!defined('CONFIG_PH_CONTEST_NAME')) {
    define('CONFIG_PH_CONTEST_NAME', '{{CONTEST_NAME}}');
}
if (!defined('CONFIG_PH_START_DATE')) {
    define('CONFIG_PH_START_DATE', '{{START_DATE}}');
}
if (!defined('CONFIG_PH_END_DATE')) {
    define('CONFIG_PH_END_DATE', '{{END_DATE}}');
}
if (!defined('CONFIG_PH_MIN_SPEND')) {
    define('CONFIG_PH_MIN_SPEND', '{{MIN_SPEND}}');
}
if (!defined('CONFIG_PH_REGOLAMENTO')) {
    define('CONFIG_PH_REGOLAMENTO', '{{REGOLAMENTO_LINK}}');
}
if (!defined('CONFIG_PH_PRIZE_TYPE')) {
    define('CONFIG_PH_PRIZE_TYPE', '{{PRIZE_TYPE}}');
}

/**
 * Carica le variabili da un file .env in getenv/$_ENV senza sovrascrivere quelle già presenti.
 */
if (!function_exists('config_parse_env_line')) {
function config_parse_env_line(string $line): ?array {
    $line = trim($line);
    if ($line === '' || str_starts_with($line, '#')) {
        return null;
    }

    if (!preg_match('/^([A-Za-z_][A-Za-z0-9_]*)\s*=\s*(.*)$/', $line, $matches)) {
        return null;
    }

    [, $name, $value] = $matches;

    // Rimuovi eventuale commento inline (#) solo se non siamo tra virgolette
    if (!(str_starts_with($value, '"') || str_starts_with($value, "'"))) {
        $value = preg_split('/\s+#/', $value, 2)[0];
    }

    $value = trim($value);
    if ((str_starts_with($value, '"') && str_ends_with($value, '"')) ||
        (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
        $value = substr($value, 1, -1);
    }

    return ['name' => $name, 'value' => $value];
}
}

if (!function_exists('config_apply_env_pair')) {
function config_apply_env_pair(string $name, string $value, bool $overwrite = false): void {
    if (!$overwrite && getenv($name) !== false) {
        return;
    }
    putenv("$name=$value");
    $_ENV[$name] = $value;
    $_SERVER[$name] = $value;
}
}

if (!function_exists('load_env_file')) {
function load_env_file(string $path, bool $overwrite = false): void {
    if (!is_file($path) || !is_readable($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $parsed = config_parse_env_line($line);
        if ($parsed === null) {
            continue;
        }
        config_apply_env_pair($parsed['name'], $parsed['value'], $overwrite);
    }
}
}

// Carica automaticamente il file .env della root, se presente
$projectRoot = dirname(__DIR__, 2);
load_env_file($projectRoot . DIRECTORY_SEPARATOR . '.env');
// Ambiente test: .env.testing sovrascrive DB_* / APP_* (Playwright e2e + PHPUnit)
if (getenv('APP_ENV') === 'testing') {
    load_env_file($projectRoot . DIRECTORY_SEPARATOR . '.env.testing', true);
}

/**
 * Read a boolean flag from environment variables with a fallback.
 */
if (!function_exists('env_flag')) {
function env_flag(string $key, bool $default = true): bool {
    $value = getenv($key);
    if ($value === false) {
        return $default;
    }

    $parsed = filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
    return $parsed === null ? $default : $parsed;
}
}

/**
 * Carica un file JSON di configurazione e fonde con i default.
 */
if (!function_exists('config_is_list')) {
function config_is_list(array $value): bool {
    if ($value === []) {
        return true;
    }
    return array_keys($value) === range(0, count($value) - 1);
}
}

/**
 * Merge ricorsivo che sostituisce completamente gli array numerici (liste)
 * invece di fonderli, cosÇÿ da permettere la rimozione di elementi di default
 * come le FAQ.
 */
if (!function_exists('config_deep_merge')) {
function config_deep_merge(array $defaults, array $overrides): array {
    foreach ($overrides as $key => $value) {
        if (is_array($value) && isset($defaults[$key]) && is_array($defaults[$key])) {
            $isList = config_is_list($value) || config_is_list($defaults[$key]);
            if ($isList) {
                // Liste: il contenuto dell'override rimpiazza quello di default
                $defaults[$key] = $value;
            } else {
                // Array associativi: merge ricorsivo
                $defaults[$key] = config_deep_merge($defaults[$key], $value);
            }
        } else {
            $defaults[$key] = $value;
        }
    }

    return $defaults;
}
}

if (!function_exists('format_contest_date')) {
/**
 * Formattare una date del concorso secondo il formato configurato
 * @param string $dateStr Date au format Y-m-d (es: "2025-12-24")
 * @param string $format Format PHP date() (di default: 'd/m/Y')
 * @return string Date formattata
 */
function format_contest_date(string $dateStr, string $format = 'd/m/Y'): string {
    
    // Se la date è vuota, restituire vuoto
    if (empty($dateStr)) {
        return '';
    }
    
    // Parsare la date e formattare secondo il formato richiesto
    try {
        $date = DateTime::createFromFormat('Y-m-d', $dateStr);
        if ($date === false) {
            // Se il formato non è Y-m-d, provare a parsare direttamente
            $date = new DateTime($dateStr);
        }
        
        // Utilizzare setlocale per i mesi in italiano
        setlocale(LC_TIME, 'it_IT.UTF-8', 'it_IT', 'italian');
        
        // Formattare la date
        $formatted = $date->format($format);
        
        // Se il format contiene i mesi in lettere, convertire in italiano
        if (preg_match('/[Fjl]/', $format)) {
            $months = [
                'January' => 'gennaio', 'February' => 'febbraio', 'March' => 'marzo',
                'April' => 'aprile', 'May' => 'maggio', 'June' => 'giugno',
                'July' => 'luglio', 'August' => 'agosto', 'September' => 'settembre',
                'October' => 'ottobre', 'November' => 'novembre', 'December' => 'dicembre'
            ];
            $monthsShort = [
                'Jan' => 'gen', 'Feb' => 'feb', 'Mar' => 'mar', 'Apr' => 'apr',
                'May' => 'mag', 'Jun' => 'giu', 'Jul' => 'lug', 'Aug' => 'ago',
                'Sep' => 'set', 'Oct' => 'ott', 'Nov' => 'nov', 'Dec' => 'dic'
            ];
            $days = [
                'Monday' => 'lunedì', 'Tuesday' => 'martedì', 'Wednesday' => 'mercoledì',
                'Thursday' => 'giovedì', 'Friday' => 'venerdì', 'Saturday' => 'sabato', 'Sunday' => 'domenica'
            ];
            $daysShort = [
                'Mon' => 'lun', 'Tue' => 'mar', 'Wed' => 'mer', 'Thu' => 'gio',
                'Fri' => 'ven', 'Sat' => 'sab', 'Sun' => 'dom'
            ];
            
            $formatted = str_replace(array_keys($months), array_values($months), $formatted);
            $formatted = str_replace(array_keys($monthsShort), array_values($monthsShort), $formatted);
            $formatted = str_replace(array_keys($days), array_values($days), $formatted);
            $formatted = str_replace(array_keys($daysShort), array_values($daysShort), $formatted);
        }
        
        return $formatted;
    } catch (Exception $e) {
        // En cas d'erreur, retourner la date originale
        return $dateStr;
    }
}
}

if (!function_exists('load_json_config')) {
function load_json_config(string $path, array $defaults = []): array {
    if (!is_readable($path)) {
        return $defaults;
    }

    $raw = file_get_contents($path);
    if ($raw === false) {
        return $defaults;
    }

    // Rimuovi eventuale BOM UTF-8
    if (str_starts_with($raw, "\xEF\xBB\xBF")) {
        $raw = substr($raw, 3);
    }

    $decoded = json_decode($raw, true);
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
        return $defaults;
    }

    return config_deep_merge($defaults, $decoded);
}
}

// Database connection settings
$db_config = [
    'host' => getenv('DB_HOST') ?: 'localhost',
    'username' => getenv('DB_USER') ?: 'root',
    'password' => getenv('DB_PASSWORD') ?: '',
    'database' => getenv('DB_NAME') ?: 'contest_app',
    'charset' => 'utf8mb4',
];

// Application level settings
$app_config = [
    'base_url' => getenv('APP_BASE_URL'),
];

// Contenuti statici: letti da JSON e sovrascrivibili via .env dove serve.
// I default completi sono in app/config/content.defaults.json (struttura identica a content.json).
$default_content_file = __DIR__ . '/../config/content.defaults.json';
$content_defaults = load_json_config($default_content_file, []);

$content_file = __DIR__ . '/../config/content.json';
$content_config = load_json_config($content_file, $content_defaults);

// Base URL: solo da .env, non dalla configurazione (branding/sfondo usano solo percorsi relativi)
$app_config['base_url'] = $app_config['base_url'] ?: '';

if (!function_exists('asset_path_to_relative')) {
    function asset_path_to_relative(string $path): string {
        $path = trim($path);
        if ($path === '') {
            return '';
        }
        if (preg_match(CONFIG_REGEX_HTTP_URL, $path) || strpos($path, '//') === 0) {
            $pathPart = parse_url($path, PHP_URL_PATH);
            $pathPart = $pathPart !== null ? ltrim($pathPart, '/') : '';
            if (preg_match('#assets/#', $pathPart)) {
                return (string) preg_replace('#^.*?assets/#', 'assets/', $pathPart);
            }
            return $pathPart;
        }
        return ltrim($path, '/');
    }
}

if (!function_exists('normalize_public_container_style_config')) {
function normalize_public_container_style_config($style, array $fallback = []): array {
    $defaults = array_replace_recursive([
        'mode' => 'color',
        'color' => CONFIG_COLOR_WHITE,
        'opacity' => 100,
        'image' => '',
        'radius' => [
            'top' => 0,
            'right' => 0,
            'bottom' => 12,
            'left' => 12,
        ],
    ], $fallback);
    $style = is_array($style) ? $style : [];

    $mode = strtolower(trim((string) ($style['mode'] ?? $defaults['mode'] ?? 'color')));
    if (!in_array($mode, ['color', 'image'], true)) {
        $mode = 'color';
    }

    $color = trim((string) ($style['color'] ?? $defaults['color'] ?? CONFIG_COLOR_WHITE));
    if (strtolower($color) !== 'transparent' && !preg_match(CONFIG_REGEX_HEX6, $color)) {
        $color = CONFIG_COLOR_WHITE;
    }

    $radiusInput = is_array($style['radius'] ?? null) ? $style['radius'] : [];
    $radiusFallback = is_array($defaults['radius'] ?? null) ? $defaults['radius'] : [];
    $radius = [];
    foreach (['top', 'right', 'bottom', 'left'] as $side) {
        $radius[$side] = max(0, min(80, (int) ($radiusInput[$side] ?? $radiusFallback[$side] ?? 0)));
    }

    return [
        'mode' => $mode,
        'color' => $color,
        'opacity' => max(0, min(100, (int) ($style['opacity'] ?? $defaults['opacity'] ?? 100))),
        'image' => asset_path_to_relative(trim((string) ($style['image'] ?? $defaults['image'] ?? ''))),
        'radius' => $radius,
    ];
}
}

if (!function_exists('public_container_content_page_key')) {
function public_container_content_page_key(string $pageKey): string {
    if ($pageKey === 'registration') {
        return 'form_page';
    }
    if ($pageKey === 'help') {
        return 'help_page';
    }
    return $pageKey;
}
}

if (!function_exists('public_container_style_fallback_from_page')) {
function public_container_style_fallback_from_page(string $pageKey, array $config, array $fallback): array {
    $contentPageKey = public_container_content_page_key($pageKey);
    if ($contentPageKey === '') {
        return $fallback;
    }

    $page = is_array($config['pages'][$contentPageKey] ?? null) ? $config['pages'][$contentPageKey] : [];
    $containers = is_array($page['containers'] ?? null) ? $page['containers'] : [];
    $first = is_array($containers[0] ?? null) ? $containers[0] : [];
    $background = is_array($first['background'] ?? null) ? $first['background'] : [];

    if (!empty($background)) {
        $bgMode = strtolower(trim((string) ($background['mode'] ?? '')));
        if (in_array($bgMode, ['color', 'image'], true)) {
            $fallback['mode'] = $bgMode;
        }
        $bgColor = trim((string) ($background['color'] ?? ''));
        if (preg_match(CONFIG_REGEX_HEX6, $bgColor)) {
            $fallback['color'] = $bgColor;
        }
        $fallback['opacity'] = max(0, min(100, (int) ($background['opacity'] ?? $fallback['opacity'] ?? 100)));
        $fallback['image'] = asset_path_to_relative(trim((string) ($background['image'] ?? $fallback['image'] ?? '')));
    }

    $style = is_array($first['style'] ?? null) ? $first['style'] : [];
    if (is_array($style['radius'] ?? null)) {
        $fallback['radius'] = normalize_public_container_style_config(['radius' => $style['radius']], $fallback)['radius'];
    }

    return $fallback;
}
}

if (!function_exists('contest_normalize_summernote_font_html')) {
    /**
     * Summernote / execCommand scrive font-family: "Nome Con Spazi" dentro style="...",
     * rompendo l'attributo HTML (stack system→attributi spuri tipo segoe="").
     * Ripara virgolette e rimuove attributi non ammessi già esplosi.
     * Su .btn / .contest-block-cta rimuove font-family e font-size (tipografia = Bottoni).
     */
    function contest_normalize_summernote_font_html(string $html): string
    {
        if ($html === '') {
            return '';
        }
        $html = (string) preg_replace(
            '/font-family\s*:\s*\x22([^\x22]+)\x22/i',
            'font-family: \'$1\'',
            $html
        );
        $html = (string) preg_replace(
            '/font-family\s*:\s*&quot;([^&]+)&quot;/i',
            'font-family: \'$1\'',
            $html
        );

        // Already-broken markup: drop attributes that are not in the allowlist.
        if (
            preg_match('/\s(?:segoe|roboto|helvetica|liberation|emoji|noto|apple|ui"|symbol|monospace|clamp)\s*=/i', $html)
            || preg_match('/<[a-z0-9]+\s+[^>]*\s[a-z0-9_-]*=\"\"/i', $html)
        ) {
            $html = contest_strip_unallowed_html_attributes($html);
        }

        $html = contest_strip_button_typography_from_html($html);
        $html = contest_normalize_urlencoded_placeholders($html);

        return $html;
    }
}

if (!function_exists('contest_strip_button_typography_from_html')) {
    /**
     * Rimuove font-family / font-size inline da bottoni (tipografia gestita in Bottoni).
     * Srotola anche wrapper tipografici (span/font) intorno o dentro i bottoni.
     */
    function contest_strip_button_typography_from_html(string $html): string
    {
        if ($html === '' || !class_exists('DOMDocument')) {
            return $html;
        }

        $prev = libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $wrapped = '<div id="__contest_btn_typo_root">' . $html . '</div>';
        $loaded = @$dom->loadHTML(
            '<?xml encoding="UTF-8">' . $wrapped,
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        if (!$loaded) {
            libxml_clear_errors();
            libxml_use_internal_errors($prev);

            return $html;
        }

        $isTypographyOnlyWrapper = static function (DOMElement $el): bool {
            $tag = strtolower($el->tagName);
            if ($tag === 'font') {
                return true;
            }
            if (!in_array($tag, ['span', 'b', 'strong', 'i', 'em', 'font'], true)) {
                return false;
            }
            $style = (string) $el->getAttribute('style');
            if ($style === '') {
                // b/strong senza style ma che wrappano solo un bottone → unwrap
                return in_array($tag, ['b', 'strong', 'i', 'em'], true);
            }
            $withoutTypo = (string) preg_replace('/(?:^|;)\s*font-(?:family|size)\s*:[^;]*/i', '', $style);
            $withoutTypo = trim($withoutTypo, " \t\n\r\0\x0B;");
            return $withoutTypo === '' && (bool) preg_match('/font-(?:family|size)\s*:/i', $style);
        };

        $elementChildCount = static function (DOMNode $node): int {
            $n = 0;
            foreach ($node->childNodes as $child) {
                if ($child instanceof DOMElement) {
                    $n++;
                } elseif ($child instanceof DOMText && trim($child->textContent) !== '') {
                    $n += 10; // testo non-whitespace: non è un wrapper puro
                }
            }
            return $n;
        };

        $unwrapNode = static function (DOMElement $child): void {
            if (!$child->parentNode) {
                return;
            }
            while ($child->firstChild) {
                $child->parentNode->insertBefore($child->firstChild, $child);
            }
            $child->parentNode->removeChild($child);
        };

        $xpath = new DOMXPath($dom);
        $nodes = $xpath->query(
            '//*[contains(concat(" ", normalize-space(@class), " "), " btn ")'
            . ' or contains(concat(" ", normalize-space(@class), " "), " contest-block-cta ")'
            . ' or @id="submitBtn"]'
        ) ?: [];

        $buttonNodes = [];
        foreach ($nodes as $node) {
            if ($node instanceof DOMElement) {
                $buttonNodes[] = $node;
            }
        }

        foreach ($buttonNodes as $node) {
            if ($node->hasAttribute('style')) {
                $style = (string) $node->getAttribute('style');
                $parts = array_filter(array_map('trim', explode(';', $style)), static function ($part) {
                    if ($part === '') {
                        return false;
                    }
                    if (preg_match('/^(font-family|font-size)\s*:/i', $part)) {
                        return false;
                    }
                    return true;
                });
                $cleaned = implode('; ', $parts);
                if ($cleaned === '') {
                    $node->removeAttribute('style');
                } else {
                    $node->setAttribute('style', $cleaned);
                }
            }

            // Unwrap nested spans/fonts that only set typography inside the button.
            $nested = [];
            foreach ($node->getElementsByTagName('*') as $child) {
                $nested[] = $child;
            }
            foreach ($nested as $child) {
                if (!$child instanceof DOMElement || !$child->parentNode) {
                    continue;
                }
                if (!$isTypographyOnlyWrapper($child)) {
                    continue;
                }
                $unwrapNode($child);
            }

            // Unwrap parent typography wrappers: <span style="font-family:Gotham"><a class="btn">…
            $parent = $node->parentNode;
            $guard = 0;
            while ($parent instanceof DOMElement && $guard < 6) {
                $guard++;
                if ($parent->getAttribute('id') === '__contest_btn_typo_root') {
                    break;
                }
                if (!$isTypographyOnlyWrapper($parent)) {
                    break;
                }
                if ($elementChildCount($parent) !== 1) {
                    break;
                }
                $grand = $parent->parentNode;
                $unwrapNode($parent);
                $parent = $grand;
            }
        }

        $root = $dom->getElementById('__contest_btn_typo_root');
        if (!$root) {
            libxml_clear_errors();
            libxml_use_internal_errors($prev);

            return $html;
        }

        $out = '';
        foreach ($root->childNodes as $child) {
            $out .= $dom->saveHTML($child);
        }
        libxml_clear_errors();
        libxml_use_internal_errors($prev);

        return $out;
    }
}

if (!function_exists('contest_strip_unallowed_html_attributes')) {
    /**
     * Keep only safe attributes on HTML fragments (waiting / Summernote content).
     */
    function contest_strip_unallowed_html_attributes(string $html): string
    {
        if ($html === '' || !class_exists('DOMDocument')) {
            return $html;
        }

        $allowed = [
            'style' => true,
            'class' => true,
            'href' => true,
            'src' => true,
            'alt' => true,
            'color' => true,
            'target' => true,
            'rel' => true,
            'width' => true,
            'height' => true,
            'title' => true,
            'id' => true,
        ];

        $prev = libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $wrapped = '<div id="__contest_html_root">' . $html . '</div>';
        $loaded = $dom->loadHTML(
            '<?xml encoding="UTF-8">' . $wrapped,
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        if (!$loaded) {
            libxml_clear_errors();
            libxml_use_internal_errors($prev);

            return $html;
        }

        $xpath = new DOMXPath($dom);
        foreach ($xpath->query('//*[@*]') ?: [] as $node) {
            if (!$node instanceof DOMElement) {
                continue;
            }
            $toRemove = [];
            foreach ($node->attributes ?? [] as $attr) {
                $name = strtolower((string) $attr->name);
                if (!isset($allowed[$name])) {
                    $toRemove[] = $attr->name;
                    continue;
                }
                if ($name === 'style') {
                    $style = (string) $attr->value;
                    $style = (string) preg_replace(
                        '/font-family\s*:\s*\x22([^\x22]+)\x22/i',
                        "font-family: '$1'",
                        $style
                    );
                    // Drop only truncated junk left after quote explosions (e.g. `ui", Roboto`).
                    $parts = array_filter(array_map('trim', explode(';', $style)), static function ($part) {
                        if ($part === '') {
                            return false;
                        }
                        // Keep normal "property: value"
                        if (preg_match('/^[a-z-]+\s*:\s*.+$/i', $part)) {
                            return true;
                        }

                        return false;
                    });
                    $node->setAttribute('style', implode('; ', $parts));
                }
            }
            foreach ($toRemove as $attrName) {
                $node->removeAttribute($attrName);
            }
        }

        $root = $dom->getElementById('__contest_html_root');
        $out = '';
        if ($root) {
            foreach ($root->childNodes as $child) {
                $out .= $dom->saveHTML($child);
            }
        }
        libxml_clear_errors();
        libxml_use_internal_errors($prev);

        return $out !== '' ? $out : $html;
    }
}

if (!function_exists('contest_font_family_from_filename')) {
    function contest_font_family_from_filename(string $filename): string
    {
        $base = pathinfo($filename, PATHINFO_FILENAME);
        $base = (string) preg_replace('/[_\-\.]+/', ' ', $base);
        $base = trim((string) preg_replace('/\s+/', ' ', $base));
        if ($base === '') {
            $base = 'Font';
        }
        if (function_exists('mb_substr')) {
            return mb_substr($base, 0, 80, 'UTF-8');
        }

        return substr($base, 0, 80);
    }
}

if (!function_exists('normalize_fonts_config')) {
    function normalize_fonts_config($raw): array
    {
        $raw = is_array($raw) ? $raw : [];
        $itemsIn = is_array($raw['items'] ?? null) ? $raw['items'] : [];
        $items = [];
        $seenFamilies = [];
        $formatByExt = [
            'woff2' => 'woff2',
            'woff' => 'woff',
            'otf' => 'opentype',
            'ttf' => 'truetype',
        ];
        foreach ($itemsIn as $item) {
            if (!is_array($item)) {
                continue;
            }
            $family = trim((string) ($item['family'] ?? ''));
            $file = asset_path_to_relative(trim((string) ($item['file'] ?? '')));
            if ($family === '' || $file === '') {
                continue;
            }
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if (!isset($formatByExt[$ext])) {
                continue;
            }
            $familyKey = mb_strtolower($family, 'UTF-8');
            if (isset($seenFamilies[$familyKey])) {
                continue;
            }
            $seenFamilies[$familyKey] = true;
            $idRaw = preg_replace('/[^a-zA-Z0-9_-]+/', '-', (string) ($item['id'] ?? ''));
            $id = is_string($idRaw) ? $idRaw : '';
            if ($id === '') {
                $id = 'font_' . substr(sha1($family . '|' . $file), 0, 12);
            }
            $items[] = [
                'id' => $id,
                'family' => $family,
                'file' => $file,
                'format' => $formatByExt[$ext],
            ];
        }

        return ['items' => $items];
    }
}

if (!function_exists('contest_font_family_names')) {
/**
 * @return list<string>
 */
function contest_font_family_names(?array $config = null): array {
    if ($config === null) {
        global $contest_config;
        $config = is_array($contest_config ?? null) ? $contest_config : [];
    }
    $fonts = normalize_fonts_config($config['fonts'] ?? []);
    $names = [];
    foreach ($fonts['items'] as $item) {
        $names[] = (string) $item['family'];
    }
    return $names;
}
}

if (!function_exists('contest_resolve_font_family')) {
function contest_resolve_font_family(?string $requested, ?array $config = null): string {
    $requested = trim((string) $requested);
    if ($requested === '') {
        return '';
    }
    $allowed = contest_font_family_names($config);
    foreach ($allowed as $name) {
        if (strcasecmp($name, $requested) === 0) {
            return $name;
        }
    }
    return '';
}
}

if (!function_exists('get_custom_fonts_style_block')) {
function get_custom_fonts_style_block(bool $relative_from_manager = false): string {
    global $contest_config;
    $fonts = normalize_fonts_config($contest_config['fonts'] ?? []);
    if ($fonts['items'] === []) {
        return '';
    }
    $css = "<style>\n";
    foreach ($fonts['items'] as $item) {
        $family = htmlspecialchars((string) $item['family'], ENT_QUOTES, 'UTF-8');
        $file = (string) $item['file'];
        $path = $relative_from_manager ? '../' . ltrim($file, '/') : $file;
        $pathEsc = htmlspecialchars($path, ENT_QUOTES, 'UTF-8');
        $format = htmlspecialchars((string) ($item['format'] ?? 'woff2'), ENT_QUOTES, 'UTF-8');
        $css .= '@font-face {' . "\n";
        $css .= "  font-family: '{$family}';\n";
        $css .= "  src: url('{$pathEsc}') format('{$format}');\n";
        $css .= "  font-display: swap;\n";
        $css .= '}' . "\n";
    }
    $css .= "</style>\n";
    return $css;
}
}

if (!function_exists('contest_font_select_options_html')) {
function contest_font_select_options_html(string $selected = '', ?array $config = null, string $emptyLabel = 'Predefinito'): string {
    $selected = trim($selected);
    $html = '<option value="">' . htmlspecialchars($emptyLabel, ENT_QUOTES, 'UTF-8') . '</option>';
    foreach (contest_font_family_names($config) as $family) {
        $isSelected = strcasecmp($family, $selected) === 0 ? ' selected' : '';
        $html .= '<option value="' . htmlspecialchars($family, ENT_QUOTES, 'UTF-8') . '"' . $isSelected . '>'
            . htmlspecialchars($family, ENT_QUOTES, 'UTF-8') . '</option>';
    }
    return $html;
}
}

if (!function_exists('contest_font_size_allowed_values')) {
/**
 * @return list<string>
 */
function contest_font_size_allowed_values(): array {
    return ['12px', '14px', '16px', '18px', '20px', '22px', '24px', '28px'];
}
}

if (!function_exists('contest_resolve_font_size')) {
function contest_resolve_font_size(?string $requested): string {
    $requested = strtolower(trim((string) $requested));
    if ($requested === '') {
        return '';
    }
    return in_array($requested, contest_font_size_allowed_values(), true) ? $requested : '';
}
}

if (!function_exists('contest_font_size_select_options_html')) {
function contest_font_size_select_options_html(string $selected = '', string $emptyLabel = 'Predefinito'): string {
    $selected = contest_resolve_font_size($selected);
    $html = '<option value="">' . htmlspecialchars($emptyLabel, ENT_QUOTES, 'UTF-8') . '</option>';
    foreach (contest_font_size_allowed_values() as $size) {
        $isSelected = $selected === $size ? ' selected' : '';
        $html .= '<option value="' . htmlspecialchars($size, ENT_QUOTES, 'UTF-8') . '"' . $isSelected . '>'
            . htmlspecialchars($size, ENT_QUOTES, 'UTF-8') . '</option>';
    }
    return $html;
}
}

require_once __DIR__ . '/contest_date_helpers.php';

// Contest window configuration: env override, otherwise JSON (operational dates for logic/tests)
$contest_dates = [
    'start_date' => getenv('CONTEST_START_DATE') ?: ($content_config['start_date'] ?? $content_defaults['start_date']),
    'end_date' => getenv('CONTEST_END_DATE') ?: ($content_config['end_date'] ?? $content_defaults['end_date']),
];
$start_raw = is_string($contest_dates['start_date'] ?? null) ? trim((string) $contest_dates['start_date']) : '';
$end_raw = is_string($contest_dates['end_date'] ?? null) ? trim((string) $contest_dates['end_date']) : '';
$contest_dates['start_date'] = resolve_contest_config_date($start_raw);
$contest_dates['end_date'] = resolve_contest_config_date($end_raw, $contest_dates['start_date']);

// Display dates for public texts (footer, emails, placeholders); fallback to operational if empty
$contest_display_dates = resolve_contest_display_dates($content_config, $contest_dates);

// Contest mode configuration (estrazione or instantwin) - env overrides config
$contest_mode_env = getenv('CONTEST_MODE');
$contest_mode_cfg = $content_config['contest_mode'] ?? ($content_defaults['contest_mode'] ?? 'estrazione');
$contest_mode_raw = ($contest_mode_env !== false && trim((string) $contest_mode_env) !== '') ? $contest_mode_env : $contest_mode_cfg;
$contest_mode = strtolower(trim((string) $contest_mode_raw));
if (!in_array($contest_mode, ['estrazione', 'instantwin'], true)) {
    $contest_mode = 'estrazione'; // Default to estrazione if invalid
}

// Epoch boundaries per contest window
$date_debut = strtotime($contest_dates['start_date'] . ' 00:00:00');
$date_fin = strtotime($contest_dates['end_date'] . ' 23:59:59');

// Background mode e colore: env > content > default
$bg_mode_env = getenv('CONTEST_BG_MODE');
$bg_mode_env = $bg_mode_env === false ? null : strtolower(trim($bg_mode_env));
$bg_mode = in_array($bg_mode_env, ['color', 'image'], true)
    ? $bg_mode_env
    : ($content_config['background_mode'] ?? $content_defaults['background_mode']);

$bg_color_env = getenv('CONTEST_BG_COLOR');
$bg_color = ($bg_color_env === false || trim((string) $bg_color_env) === '')
    ? ($content_config['background_color'] ?? $content_defaults['background_color'])
    : trim($bg_color_env);

$backgrounds_content = $content_config['backgrounds'] ?? [];
$background_style_content = $content_config['background_style'] ?? [];
$is_custom_background = function(string $path): bool {
    $p = trim($path);
    if ($p === '') {
        return false;
    }
    $p = ltrim($p, '/');
    return strpos($p, 'assets/img/backgrounds/') === 0;
};

$bg_default_fallback = (string) ($content_defaults['backgrounds']['default'] ?? '');
$bg_mobile_fallback = (string) ($content_defaults['backgrounds']['mobile'] ?? $bg_default_fallback);
$bg_tablet_fallback = (string) ($content_defaults['backgrounds']['tablet'] ?? $bg_default_fallback);
$bg_laptop_fallback = (string) ($content_defaults['backgrounds']['laptop'] ?? $bg_default_fallback);
$bg_desktop_fallback = (string) ($content_defaults['backgrounds']['desktop'] ?? $bg_default_fallback);
$bg_ultrawide_fallback = (string) ($content_defaults['backgrounds']['ultrawide'] ?? $bg_default_fallback);

$bg_default_env = getenv('CONTEST_BG_DEFAULT');
$bg_default_env = ($bg_default_env !== false && trim((string) $bg_default_env) !== '') ? trim((string) $bg_default_env) : '';
$bg_background_image_env = getenv('CONTEST_BACKGROUND_IMAGE');
$bg_background_image_env = ($bg_background_image_env !== false && trim((string) $bg_background_image_env) !== '') ? trim((string) $bg_background_image_env) : '';
$bg_default_cfg = $backgrounds_content['default'] ?? null;
$bg_default_cfg = is_string($bg_default_cfg) ? trim($bg_default_cfg) : '';

$bg_default_candidate = $bg_default_env !== ''
    ? $bg_default_env
    : ($bg_background_image_env !== ''
        ? $bg_background_image_env
        : $bg_default_cfg);

$default_background = asset_path_to_relative($bg_default_candidate !== '' ? $bg_default_candidate : $bg_default_fallback);

// Detect custom backgrounds for specific sizes (env/cfg), excluding default.
$bg_mobile_env = getenv('CONTEST_BG_MOBILE');
$bg_mobile_env = ($bg_mobile_env !== false && trim((string) $bg_mobile_env) !== '') ? trim((string) $bg_mobile_env) : '';
$bg_mobile_cfg = $backgrounds_content['mobile'] ?? null;
$bg_mobile_cfg = is_string($bg_mobile_cfg) ? trim($bg_mobile_cfg) : '';
$bg_mobile_candidate = $bg_mobile_env !== '' ? $bg_mobile_env : $bg_mobile_cfg;

$bg_tablet_env = getenv('CONTEST_BG_TABLET');
$bg_tablet_env = ($bg_tablet_env !== false && trim((string) $bg_tablet_env) !== '') ? trim((string) $bg_tablet_env) : '';
$bg_tablet_cfg = $backgrounds_content['tablet'] ?? null;
$bg_tablet_cfg = is_string($bg_tablet_cfg) ? trim($bg_tablet_cfg) : '';
$bg_tablet_candidate = $bg_tablet_env !== '' ? $bg_tablet_env : $bg_tablet_cfg;

$bg_laptop_env = getenv('CONTEST_BG_LAPTOP');
$bg_laptop_env = ($bg_laptop_env !== false && trim((string) $bg_laptop_env) !== '') ? trim((string) $bg_laptop_env) : '';
$bg_laptop_cfg = $backgrounds_content['laptop'] ?? null;
$bg_laptop_cfg = is_string($bg_laptop_cfg) ? trim($bg_laptop_cfg) : '';
$bg_laptop_candidate = $bg_laptop_env !== '' ? $bg_laptop_env : $bg_laptop_cfg;

$bg_desktop_env = getenv('CONTEST_BG_DESKTOP');
$bg_desktop_env = ($bg_desktop_env !== false && trim((string) $bg_desktop_env) !== '') ? trim((string) $bg_desktop_env) : '';
$bg_desktop_cfg = $backgrounds_content['desktop'] ?? null;
$bg_desktop_cfg = is_string($bg_desktop_cfg) ? trim($bg_desktop_cfg) : '';
$bg_desktop_candidate = $bg_desktop_env !== '' ? $bg_desktop_env : $bg_desktop_cfg;

$bg_ultrawide_env = getenv('CONTEST_BG_ULTRAWIDE');
$bg_ultrawide_env = ($bg_ultrawide_env !== false && trim((string) $bg_ultrawide_env) !== '') ? trim((string) $bg_ultrawide_env) : '';
$bg_ultrawide_cfg = $backgrounds_content['ultrawide'] ?? null;
$bg_ultrawide_cfg = is_string($bg_ultrawide_cfg) ? trim($bg_ultrawide_cfg) : '';
$bg_ultrawide_candidate = $bg_ultrawide_env !== '' ? $bg_ultrawide_env : $bg_ultrawide_cfg;

// Eredita Default SOLO quando Default è un asset custom (assets/img/backgrounds/)
$bg_use_default_for_missing = $is_custom_background($default_background);

// Risoluzione finale: solo percorsi relativi per sfondo.
$bg_mobile = asset_path_to_relative($bg_mobile_candidate !== ''
    ? $bg_mobile_candidate
    : ($bg_use_default_for_missing ? $default_background : $bg_mobile_fallback));
$bg_tablet = asset_path_to_relative($bg_tablet_candidate !== ''
    ? $bg_tablet_candidate
    : ($bg_use_default_for_missing ? $default_background : $bg_tablet_fallback));
$bg_laptop = asset_path_to_relative($bg_laptop_candidate !== ''
    ? $bg_laptop_candidate
    : ($bg_use_default_for_missing ? $default_background : $bg_laptop_fallback));
$bg_desktop = asset_path_to_relative($bg_desktop_candidate !== ''
    ? $bg_desktop_candidate
    : ($bg_use_default_for_missing ? $default_background : $bg_desktop_fallback));
$bg_ultrawide = asset_path_to_relative($bg_ultrawide_candidate !== ''
    ? $bg_ultrawide_candidate
    : ($bg_use_default_for_missing ? $default_background : $bg_ultrawide_fallback));

$colors_content = $content_config['colors'] ?? [];
$footer_content = $content_config['footer_style'] ?? [];
$footer_layout_content = is_array($content_config['footer_layout'] ?? null) ? $content_config['footer_layout'] : [];
$buttons_content = $content_config['buttons'] ?? [];
$fidelity_content = $content_config['fidelity'] ?? [];
$form_container_content = $content_config['form_container'] ?? [];
$content_fields = $content_config['fields_enabled'] ?? [];
$content_required_fields = $content_config['fields_required'] ?? ($content_defaults['fields_required'] ?? []);
$custom_fields_content = $content_config['custom_fields'] ?? ($content_defaults['custom_fields'] ?? []);
if (!is_array($custom_fields_content)) {
    $custom_fields_content = [];
}
$palette_primary = getenv('CONTEST_PRIMARY_COLOR') ?: ($colors_content['primary'] ?? $content_defaults['colors']['primary']);
$palette_secondary = getenv('CONTEST_SECONDARY_COLOR') ?: ($colors_content['secondary'] ?? $content_defaults['colors']['secondary']);
$palette_tertiary = getenv('CONTEST_TERTIARY_COLOR') ?: ($colors_content['tertiary'] ?? ($content_defaults['colors']['tertiary'] ?? CONFIG_COLOR_WHITE));
$palette_text = getenv('CONTEST_TEXT_COLOR') ?: ($colors_content['text'] ?? $content_defaults['colors']['text']);
// Branding: solo percorsi relativi (es. assets/img/default_img/logo-generic.png), mai URL assoluti.
$logo_env = getenv('CONTEST_LOGO');
$logo_cfg = $content_config['logo'] ?? null;
$logo_cfg = is_string($logo_cfg) ? trim($logo_cfg) : '';
$logo_raw = ($logo_env !== false && trim((string) $logo_env) !== '')
    ? trim((string) $logo_env)
    : ($logo_cfg !== ''
        ? $logo_cfg
        : ($content_defaults['logo'] ?? 'assets/img/default_img/logo-generic.png'));
$logo = asset_path_to_relative($logo_raw);

$favicon_env = getenv('CONTEST_FAVICON');
$favicon_cfg = $content_config['favicon'] ?? null;
$favicon_cfg = is_string($favicon_cfg) ? trim($favicon_cfg) : '';
$favicon_raw = ($favicon_env !== false && trim((string) $favicon_env) !== '')
    ? trim((string) $favicon_env)
    : ($favicon_cfg !== ''
        ? $favicon_cfg
        : ($content_defaults['favicon'] ?? 'assets/img/default_img/favicon.png'));
$favicon = asset_path_to_relative($favicon_raw);

$favicon_mobile_env = getenv('CONTEST_FAVICON_MOBILE');
$favicon_mobile_cfg = $content_config['favicon_mobile'] ?? null;
$favicon_mobile_cfg = is_string($favicon_mobile_cfg) ? trim($favicon_mobile_cfg) : '';
$favicon_mobile_raw = ($favicon_mobile_env !== false && trim((string) $favicon_mobile_env) !== '')
    ? trim((string) $favicon_mobile_env)
    : ($favicon_mobile_cfg !== ''
        ? $favicon_mobile_cfg
        : ($content_defaults['favicon_mobile'] ?? $favicon));
$favicon_mobile = asset_path_to_relative($favicon_mobile_raw);

$logo_mobile_env = getenv('CONTEST_LOGO_MOBILE');
$logo_mobile_cfg = $content_config['logo_mobile'] ?? null;
$logo_mobile_cfg = is_string($logo_mobile_cfg) ? trim($logo_mobile_cfg) : '';
$logo_mobile_raw = ($logo_mobile_env !== false && trim((string) $logo_mobile_env) !== '')
    ? trim((string) $logo_mobile_env)
    : ($logo_mobile_cfg !== ''
        ? $logo_mobile_cfg
        : ($content_defaults['logo_mobile'] ?? $logo));
$logo_mobile = asset_path_to_relative($logo_mobile_raw);

$header_image_cfg = $content_config['header_image'] ?? null;
$header_image_cfg = is_string($header_image_cfg) ? trim($header_image_cfg) : '';
$header_image_raw = $header_image_cfg !== ''
    ? $header_image_cfg
    : ($content_defaults['header_image'] ?? '');
$header_image = asset_path_to_relative((string) $header_image_raw);

$header_image_mobile_cfg = $content_config['header_image_mobile'] ?? null;
$header_image_mobile_cfg = is_string($header_image_mobile_cfg) ? trim($header_image_mobile_cfg) : '';
$header_image_mobile_raw = $header_image_mobile_cfg !== ''
    ? $header_image_mobile_cfg
    : ($content_defaults['header_image_mobile'] ?? '');
$header_image_mobile = asset_path_to_relative((string) $header_image_mobile_raw);

$logo_display_mode = strtolower(trim((string) ($content_config['logo_display_mode'] ?? ($content_defaults['logo_display_mode'] ?? 'logo'))));
if (!in_array($logo_display_mode, ['none', 'logo', 'header', 'both'], true)) {
    $legacy_logo_enabled = array_key_exists('logo_enabled', $content_config)
        ? filter_var($content_config['logo_enabled'], FILTER_VALIDATE_BOOLEAN)
        : (bool) ($content_defaults['logo_enabled'] ?? true);
    $logo_display_mode = $legacy_logo_enabled ? 'logo' : 'none';
}

if (!function_exists('brand_layout_page_definitions')) {
function brand_layout_page_definitions(): array {
    return [
        'registration' => 'Registration',
        'help' => 'Help',
        'waiting_page' => 'In attesa',
        'participation_page' => 'Participation',
        'waiting_page_ended' => 'In attesa terminata',
        'thankyou_instantwin_win_page' => 'Thank You Win',
        'thankyou_instantwin_loss_page' => 'Thank You Loss',
        'thankyou_instantwin_quiz_failed_page' => 'Thank You Quiz KO',
        'thankyou_estrazione_page' => 'Thank You Estrazione',
        'thankyou_estrazione_quiz_failed_page' => 'Thank You Estrazione Quiz KO',
        'quiz_page' => 'Quiz',
        'quiz_choice_page' => 'Quiz scelta',
    ];
}
}

if (!function_exists('brand_layout_presets')) {
function brand_layout_presets(): array {
    return [
        'attached' => ['mobile' => 0, 'tablet' => 0, 'desktop' => 0],
        'overlap_light' => ['mobile' => -24, 'tablet' => -40, 'desktop' => -56],
        'overlap_strong' => ['mobile' => -120, 'tablet' => -80, 'desktop' => -120],
        'separated' => ['mobile' => 16, 'tablet' => 24, 'desktop' => 32],
    ];
}
}

if (!function_exists('brand_layout_clamp_overlap')) {
function brand_layout_clamp_overlap(int $value, string $breakpoint = 'desktop'): int {
    $min = match ($breakpoint) {
        'mobile' => -120,
        'tablet' => -80,
        default => -120,
    };
    return max($min, min(120, $value));
}
}

if (!function_exists('brand_layout_clamp_logo_top_spacing')) {
function brand_layout_clamp_logo_top_spacing(int $value): int {
    return max(0, min(120, $value));
}
}

if (!function_exists('brand_layout_default_item')) {
function brand_layout_default_item(): array {
    return [
        'preset' => 'attached',
        'customize_per_size' => true,
        'mobile' => ['overlap_px' => 0],
        'tablet' => ['overlap_px' => 0],
        'desktop' => ['overlap_px' => 0],
    ];
}
}

if (!function_exists('normalize_brand_layout_item')) {
function normalize_brand_layout_item($raw): array {
    $raw = is_array($raw) ? $raw : [];
    $defaults = brand_layout_default_item();
    $preset = strtolower(trim((string) ($raw['preset'] ?? $defaults['preset'])));
    $presets = brand_layout_presets();

    if ($preset !== 'custom' && isset($presets[$preset])) {
        $resolved = $presets[$preset];
    } else {
        $resolved = [
            'mobile' => (int) ($raw['mobile']['overlap_px'] ?? $defaults['mobile']['overlap_px']),
            'tablet' => (int) ($raw['tablet']['overlap_px'] ?? $defaults['tablet']['overlap_px']),
            'desktop' => (int) ($raw['desktop']['overlap_px'] ?? $defaults['desktop']['overlap_px']),
        ];
    }

    return [
        'preset' => isset($presets[$preset]) || $preset === 'custom' ? $preset : 'attached',
        'customize_per_size' => array_key_exists('customize_per_size', $raw)
            ? !empty($raw['customize_per_size'])
            : !empty($defaults['customize_per_size']),
        'mobile' => ['overlap_px' => brand_layout_clamp_overlap((int) $resolved['mobile'], 'mobile')],
        'tablet' => ['overlap_px' => brand_layout_clamp_overlap((int) $resolved['tablet'], 'tablet')],
        'desktop' => ['overlap_px' => brand_layout_clamp_overlap((int) $resolved['desktop'], 'desktop')],
    ];
}
}

if (!function_exists('normalize_brand_layout_collection')) {
function normalize_brand_layout_collection($raw, ?array $defaults = null): array {
    $raw = is_array($raw) ? $raw : [];
    $defaults = is_array($defaults) ? $defaults : [];
    $out = [];
    foreach (brand_layout_page_definitions() as $pageKey => $label) {
        $base = normalize_brand_layout_item($defaults[$pageKey] ?? []);
        $item = is_array($raw[$pageKey] ?? null) ? array_replace_recursive($base, $raw[$pageKey]) : $base;
        $out[$pageKey] = normalize_brand_layout_item($item);
    }
    return $out;
}
}

if (!function_exists('brand_layout_clamp_header_logo_gap')) {
function brand_layout_clamp_header_logo_gap(int $value): int {
    return max(0, min(48, $value));
}
}

if (!function_exists('brand_layout_clamp_header_top_spacing')) {
function brand_layout_clamp_header_top_spacing(int $value): int {
    return max(0, min(120, $value));
}
}

if (!function_exists('normalize_logo_top_spacing_config')) {
function normalize_logo_top_spacing_config($raw, ?array $defaults = null): array {
    $raw = is_array($raw) ? $raw : [];
    $defaults = is_array($defaults) ? $defaults : [];
    return [
        'mobile_px' => brand_layout_clamp_logo_top_spacing((int) ($raw['mobile_px'] ?? $defaults['mobile_px'] ?? 0)),
        'tablet_px' => brand_layout_clamp_logo_top_spacing((int) ($raw['tablet_px'] ?? $defaults['tablet_px'] ?? 0)),
        'desktop_px' => brand_layout_clamp_logo_top_spacing((int) ($raw['desktop_px'] ?? $defaults['desktop_px'] ?? 0)),
    ];
}
}

if (!function_exists('normalize_brand_header_logo_gap_config')) {
function normalize_brand_header_logo_gap_config($raw, ?array $defaults = null): array {
    $raw = is_array($raw) ? $raw : [];
    $defaults = is_array($defaults) ? $defaults : [];
    return [
        'mobile_px' => brand_layout_clamp_header_logo_gap((int) ($raw['mobile_px'] ?? $defaults['mobile_px'] ?? 0)),
        'tablet_px' => brand_layout_clamp_header_logo_gap((int) ($raw['tablet_px'] ?? $defaults['tablet_px'] ?? 0)),
        'desktop_px' => brand_layout_clamp_header_logo_gap((int) ($raw['desktop_px'] ?? $defaults['desktop_px'] ?? 0)),
    ];
}
}

if (!function_exists('normalize_brand_header_top_spacing_config')) {
function normalize_brand_header_top_spacing_config($raw, ?array $defaults = null): array {
    $raw = is_array($raw) ? $raw : [];
    $defaults = is_array($defaults) ? $defaults : [];
    return [
        'mobile_px' => brand_layout_clamp_header_top_spacing((int) ($raw['mobile_px'] ?? $defaults['mobile_px'] ?? 0)),
        'tablet_px' => brand_layout_clamp_header_top_spacing((int) ($raw['tablet_px'] ?? $defaults['tablet_px'] ?? 0)),
        'desktop_px' => brand_layout_clamp_header_top_spacing((int) ($raw['desktop_px'] ?? $defaults['desktop_px'] ?? 0)),
    ];
}
}

if (!function_exists('brand_layout_container_rgba')) {
function brand_layout_container_rgba(string $hex, int $opacity): string {
    $hex = ltrim(trim($hex), '#');
    if (strlen($hex) === 3) {
        $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
    }
    if (strlen($hex) !== 6) {
        return 'rgba(255, 255, 255, ' . max(0, min(1, $opacity / 100)) . ')';
    }
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    $alpha = max(0, min(1, $opacity / 100));
    return "rgba({$r}, {$g}, {$b}, {$alpha})";
}
}

if (!function_exists('resolve_brand_display_context')) {
function resolve_brand_display_context(?array $config = null): array {
    if ($config === null) {
        global $contest_config;
        $config = is_array($contest_config ?? null) ? $contest_config : [];
    }

    $logoDisplayMode = strtolower(trim((string) ($config['logo_display_mode'] ?? '')));
    if (!in_array($logoDisplayMode, ['none', 'logo', 'header', 'both'], true)) {
        $logoDisplayMode = (array_key_exists('logo_enabled', $config) && !filter_var($config['logo_enabled'], FILTER_VALIDATE_BOOLEAN))
            ? 'none'
            : 'logo';
    }

    $showLogo = in_array($logoDisplayMode, ['logo', 'both'], true);
    $showHeader = in_array($logoDisplayMode, ['header', 'both'], true)
        && trim((string) ($config['header_image'] ?? '')) !== '';
    $hasBrandVisual = $showLogo || $showHeader;

    $wrapperModifiers = '';
    if ($showHeader) {
        $wrapperModifiers .= ' brand-header-active';
    }
    if ($showHeader && !$showLogo) {
        $wrapperModifiers .= ' brand-header-only';
    }

    return [
        'logo_display_mode' => $logoDisplayMode,
        'show_logo' => $showLogo,
        'show_header' => $showHeader,
        'has_brand_visual' => $hasBrandVisual,
        'wrapper_modifier_classes' => $wrapperModifiers,
    ];
}
}

if (!function_exists('normalize_mail_banner_path')) {
    function normalize_mail_banner_path(string $path): string {
        $path = trim(str_replace('\\', '/', $path));
        if ($path === '') {
            return 'assets/img/default_img/banner.png';
        }
        if (preg_match(CONFIG_REGEX_HTTP_URL, $path)) {
            return $path;
        }
        $path = ltrim($path, '/');
        if (preg_match('#^assets/#i', $path) !== 1) {
            $path = 'assets/' . ltrim($path, '/');
        }
        $projectRoot = dirname(__DIR__, 2);
        $publicRoot = $projectRoot . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR;
        $configured = $publicRoot . str_replace('/', DIRECTORY_SEPARATOR, $path);
        if (is_file($configured)) {
            return $path;
        }
        $emailDir = $publicRoot . 'assets' . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR . 'email' . DIRECTORY_SEPARATOR;
        if (is_dir($emailDir)) {
            $matches = glob($emailDir . 'banner.*');
            if ($matches !== false) {
                foreach ($matches as $file) {
                    if (is_file($file)) {
                        return 'assets/img/email/' . basename($file);
                    }
                }
            }
        }
        return $path;
    }
}

$mail_banner_env = getenv('CONTEST_MAIL_BANNER');
$mail_banner_cfg = $content_config['mail_banner'] ?? null;
$mail_banner_cfg = is_string($mail_banner_cfg) ? trim($mail_banner_cfg) : '';
$mail_banner_raw = ($mail_banner_env !== false && trim((string) $mail_banner_env) !== '')
    ? trim((string) $mail_banner_env)
    : ($mail_banner_cfg !== ''
        ? $mail_banner_cfg
        : ($content_defaults['mail_banner'] ?? 'assets/img/default_img/banner.png'));
$mail_banner = normalize_mail_banner_path(asset_path_to_relative($mail_banner_raw));
$helpdesk_email = getenv('HELPDESK_EMAIL') ?: ($content_config['helpdesk_email'] ?? ($content_defaults['helpdesk_email'] ?? ''));
// SMTP: .env first (SMTP_*), JSON only as temporary fallback during migration
$smtp_host_cfg = $content_config['smtp_host'] ?? ($content_defaults['smtp_host'] ?? '');
$smtp_port_cfg = (int) ($content_config['smtp_port'] ?? ($content_defaults['smtp_port'] ?? 587));
$smtp_user_cfg = $content_config['smtp_username'] ?? ($content_defaults['smtp_username'] ?? '');
$smtp_pass_cfg = $content_config['smtp_password'] ?? ($content_defaults['smtp_password'] ?? '');
$smtp_from_email_cfg = $content_config['smtp_from_email'] ?? ($content_defaults['smtp_from_email'] ?? 'no-reply@example.com');
$smtp_from_name_cfg = $content_config['smtp_from_name'] ?? ($content_defaults['smtp_from_name'] ?? ($contest_config['name'] ?? 'Contest'));
$smtp_reply_to_cfg = ''; // reply-to disabilitato da configurazione

// Formato data per la visualizzazione
$date_format = $content_config['date_format'] ?? ($content_defaults['date_format'] ?? 'd/m/Y');

// Tipi di file scontrino consentiti (configuratore concorso)
$receipt_allowed_types_default = ['jpg', 'png', 'pdf'];
$receipt_allowed_types = $content_config['receipt_allowed_extensions'] ?? ($content_defaults['receipt_allowed_extensions'] ?? $receipt_allowed_types_default);
$receipt_allowed_types = array_values(array_intersect($receipt_allowed_types_default, array_map('strtolower', (array) $receipt_allowed_types)));
if (empty($receipt_allowed_types)) {
    $receipt_allowed_types = $receipt_allowed_types_default;
}

$form_container_normalized = normalize_public_container_style_config(
    $form_container_content,
    is_array($content_defaults['form_container'] ?? null) ? $content_defaults['form_container'] : []
);
$public_page_container_defs = [
    'registration',
    'help',
    'waiting_page',
    'participation_page',
    'waiting_page_ended',
    'thankyou_instantwin_win_page',
    'thankyou_instantwin_loss_page',
    'thankyou_instantwin_quiz_failed_page',
    'thankyou_estrazione_page',
    'thankyou_estrazione_quiz_failed_page',
    'quiz_page',
    'quiz_choice_page',
];
$public_page_container_content = is_array($content_config['public_page_containers'] ?? null) ? $content_config['public_page_containers'] : [];
$public_page_container_defaults = is_array($content_defaults['public_page_containers'] ?? null) ? $content_defaults['public_page_containers'] : [];
$public_page_containers_normalized = [];
foreach ($public_page_container_defs as $publicPageContainerKey) {
    $pageContainerFallbackBase = normalize_public_container_style_config($public_page_container_defaults[$publicPageContainerKey] ?? []);
    $pageContainerFallback = public_container_style_fallback_from_page($publicPageContainerKey, $content_config, $pageContainerFallbackBase);
    $public_page_containers_normalized[$publicPageContainerKey] = normalize_public_container_style_config(
        $public_page_container_content[$publicPageContainerKey] ?? ($public_page_container_defaults[$publicPageContainerKey] ?? []),
        $pageContainerFallback
    );
}

$brand_page_layout_defaults = is_array($content_defaults['brand_page_layout'] ?? null) ? $content_defaults['brand_page_layout'] : [];
$brand_page_layout_normalized = normalize_brand_layout_collection($content_config['brand_page_layout'] ?? [], $brand_page_layout_defaults);
$logo_top_spacing_normalized = normalize_logo_top_spacing_config(
    $content_config['logo_top_spacing'] ?? [],
    is_array($content_defaults['logo_top_spacing'] ?? null) ? $content_defaults['logo_top_spacing'] : []
);
$brand_header_logo_gap_normalized = normalize_brand_header_logo_gap_config(
    $content_config['brand_header_logo_gap'] ?? [],
    is_array($content_defaults['brand_header_logo_gap'] ?? null) ? $content_defaults['brand_header_logo_gap'] : []
);
$brand_header_top_spacing_normalized = normalize_brand_header_top_spacing_config(
    $content_config['brand_header_top_spacing'] ?? [],
    is_array($content_defaults['brand_header_top_spacing'] ?? null) ? $content_defaults['brand_header_top_spacing'] : []
);

if (!function_exists('contest_normalize_instantwin_prize_distribution')) {
    require_once __DIR__ . '/functions.php';
}
$instantwin_prize_distribution_runtime = contest_normalize_instantwin_prize_distribution(
    array_merge(
        is_array($content_defaults) ? $content_defaults : [],
        is_array($content_config) ? $content_config : []
    )
);

$contest_config = [
    'name' => getenv('CONTEST_NAME') ?: ($content_config['name'] ?? $content_defaults['name']),
    'published' => (bool)($content_config['published'] ?? ($content_defaults['published'] ?? false)),
    'start_date' => $contest_dates['start_date'],
    'end_date' => $contest_dates['end_date'],
    'display_start_date' => $contest_display_dates['start_date'],
    'display_end_date' => $contest_display_dates['end_date'],
    'start_date_formatted' => format_contest_date($contest_display_dates['start_date'], $date_format),
    'end_date_formatted' => format_contest_date($contest_display_dates['end_date'], $date_format),
    'date_format' => $date_format,
    'mode' => $contest_mode,
    'instantwin_auto_prize_with_receipt' => env_flag('INSTANTWIN_AUTO_PRIZE_WITH_RECEIPT', (bool)($content_config['instantwin_auto_prize_with_receipt'] ?? ($content_defaults['instantwin_auto_prize_with_receipt'] ?? false))),
    'instantwin_prize_distribution' => $instantwin_prize_distribution_runtime,
    'instantwin_per_pdv' => ($instantwin_prize_distribution_runtime === 'pdv'),
    'instantwin_per_cedi' => ($instantwin_prize_distribution_runtime === 'cedi'),
    'instantwin_codice_label' => (string) (getenv('INSTANTWIN_CODICE_LABEL') ?: ($content_config['instantwin_codice_label'] ?? ($content_defaults['instantwin_codice_label'] ?? 'Codice partecipazione'))),
    'instantwin_codice_length' => (int) (getenv('INSTANTWIN_CODICE_LENGTH') ?: ($content_config['instantwin_codice_length'] ?? ($content_defaults['instantwin_codice_length'] ?? 12))),
    'instantwin_codice_length_auto' => env_flag('INSTANTWIN_CODICE_LENGTH_AUTO', (bool)($content_config['instantwin_codice_length_auto'] ?? ($content_defaults['instantwin_codice_length_auto'] ?? false))),
    'instantwin_codice_mode' => (string) (getenv('INSTANTWIN_CODICE_MODE') ?: ($content_config['instantwin_codice_mode'] ?? ($content_defaults['instantwin_codice_mode'] ?? 'instantwin'))),
    'codice_partecipazione_mode' => (function () use ($content_config, $content_defaults) {
        require_once __DIR__ . '/../logic/codice_partecipazione_validation.php';
        $merged = array_merge(
            is_array($content_defaults) ? $content_defaults : [],
            is_array($content_config) ? $content_config : []
        );
        return codice_partecipazione_resolve_mode($merged);
    })(),
    'codice_partecipazione_strict_validation' => (bool) ($content_config['codice_partecipazione_strict_validation'] ?? ($content_defaults['codice_partecipazione_strict_validation'] ?? false)),
    'codice_partecipazione_security_enabled' => (bool) ($content_config['codice_partecipazione_security_enabled'] ?? ($content_defaults['codice_partecipazione_security_enabled'] ?? false)),
    'min_spend' => (float) ((getenv('CONTEST_MIN_SPEND') !== false && trim((string) getenv('CONTEST_MIN_SPEND')) !== '') ? getenv('CONTEST_MIN_SPEND') : ($content_config['min_spend'] ?? $content_defaults['min_spend'])),
    'prize_total' => (float) ((getenv('CONTEST_PRIZE_TOTAL') !== false && trim((string) getenv('CONTEST_PRIZE_TOTAL')) !== '') ? getenv('CONTEST_PRIZE_TOTAL') : ($content_config['prize_total'] ?? $content_defaults['prize_total'])),
    'premi' => (function () use ($content_config, $content_defaults) {
        $raw = $content_config['premi'] ?? ($content_defaults['premi'] ?? []);
        if (!is_array($raw) || empty($raw)) {
            return [];
        }
        $out = [];
        foreach ($raw as $p) {
            $v = isset($p['valore']) ? (float)$p['valore'] : 0;
            $q = isset($p['quantita_totale']) ? (int)$p['quantita_totale'] : 0;
            $t = isset($p['tipo_premio']) ? trim((string)$p['tipo_premio']) : 'Premio';
            // Valore 0 consentito (premi omaggio / senza importo monetario)
            if ($v >= 0 && $q > 0) {
                $out[] = ['value' => $v, 'quantity' => $q, 'type' => $t !== '' ? $t : 'Premio'];
            }
        }
        return $out;
    })(),
    'max_file_size' => (int) ((getenv('CONTEST_MAX_FILE_SIZE') !== false && trim((string) getenv('CONTEST_MAX_FILE_SIZE')) !== '') ? getenv('CONTEST_MAX_FILE_SIZE') : ($content_config['max_file_size'] ?? $content_defaults['max_file_size'])),
    'receipt_allowed_extensions' => $receipt_allowed_types,
    'privacy_link' => getenv('CONTEST_PRIVACY_LINK') ?: ($content_config['privacy_link'] ?? $content_defaults['privacy_link']),
    'cookie_policy_link' => trim((string) ($content_config['cookie_policy_link'] ?? ($content_defaults['cookie_policy_link'] ?? ''))),
    'regolamento_link' => getenv('CONTEST_RULES_LINK') ?: ($content_config['rules_link'] ?? $content_defaults['rules_link']),
    'cookie_consent' => (function () use ($content_config, $content_defaults) {
        require_once __DIR__ . '/cookie_consent.php';
        return cookie_consent_normalize(
            $content_config['cookie_consent'] ?? [],
            is_array($content_defaults['cookie_consent'] ?? null) ? $content_defaults['cookie_consent'] : []
        );
    })(),
    'logo_enabled' => array_key_exists('logo_enabled', $content_config)
        ? filter_var($content_config['logo_enabled'], FILTER_VALIDATE_BOOLEAN)
        : (bool)($content_defaults['logo_enabled'] ?? true),
    'logo_display_mode' => $logo_display_mode,
    'logo' => $logo,
    'logo_mobile' => $logo_mobile,
    'header_image' => $header_image,
    'header_image_mobile' => $header_image_mobile,
    'logo_desktop_size' => (function () use ($content_config, $content_defaults) {
        $size = strtolower(trim((string) ($content_config['logo_desktop_size'] ?? ($content_defaults['logo_desktop_size'] ?? 'medium'))));
        return in_array($size, ['compact', 'medium', 'large', 'custom'], true) ? $size : 'medium';
    })(),
    'logo_desktop_width_percent' => (function () use ($content_config, $content_defaults) {
        $pct = (int) ($content_config['logo_desktop_width_percent'] ?? ($content_defaults['logo_desktop_width_percent'] ?? 85));
        return max(40, min(100, $pct));
    })(),
    'logo_desktop_max_height_px' => (function () use ($content_config, $content_defaults) {
        $px = (int) ($content_config['logo_desktop_max_height_px'] ?? ($content_defaults['logo_desktop_max_height_px'] ?? 0));
        return max(0, min(500, $px));
    })(),
    'brand_page_layout' => $brand_page_layout_normalized,
    'logo_top_spacing' => $logo_top_spacing_normalized,
    'brand_header_logo_gap' => $brand_header_logo_gap_normalized,
    'brand_header_top_spacing' => $brand_header_top_spacing_normalized,
    'favicon' => $favicon,
    'favicon_mobile' => $favicon_mobile,
    'mail_banner' => $mail_banner,
    'promoter' => getenv('CONTEST_PROMOTER') ?: ($content_config['promoter'] ?? $content_defaults['promoter']),
    'promoter_link' => getenv('CONTEST_PROMOTER_LINK') ?: ($content_config['promoter_link'] ?? $content_defaults['promoter_link'] ?? ''),
    'delegate' => getenv('CONTEST_DELEGATE') ?: ($content_config['delegate'] ?? $content_defaults['delegate']),
    'delegate_link' => getenv('CONTEST_DELEGATE_LINK') ?: ($content_config['delegate_link'] ?? $content_defaults['delegate_link'] ?? ''),
    'helpdesk_email' => $helpdesk_email,
    'base_url' => $app_config['base_url'] ?? '',
    'email_instantwin_prize_attach_pdf' => (bool)($content_config['email_instantwin_prize_attach_pdf'] ?? ($content_defaults['email_instantwin_prize_attach_pdf'] ?? false)),
    'pdv_config' => $content_config['pdv_config'] ?? ($content_defaults['pdv_config'] ?? ['mode' => 'combinato', 'combinato' => ['label_fields' => ['ragsoc', 'indirizzo', 'citta', 'prov'], 'separator' => ' - ', 'search_limit' => 50], 'cascata' => ['punto_vendita_display' => 'insegna_indirizzo']]),
    'consensi_texts' => $content_config['consensi_texts'] ?? ($content_defaults['consensi_texts'] ?? [
        'privacy' => 'Dichiaro di aver preso visione e di accettare {{PRIVACY_LINK}} *',
        'marketing' => 'Autorizzo il trattamento dei dati per finalità di marketing (i.e. attività promozionale, commerciale e pubblicitaria)',
        'profilazione' => 'Autorizzo il trattamento dei dati per finalità di profilazione (per ricevere comunicazioni personalizzate in base ai miei gusti e interessi)',
    ]),
    'help_form_enabled' => array_key_exists('help_form_enabled', $content_config)
        ? (bool) $content_config['help_form_enabled']
        : (bool) ($content_defaults['help_form_enabled'] ?? true),
    'participation_page_enabled' => array_key_exists('participation_page_enabled', $content_config)
        ? (bool) $content_config['participation_page_enabled']
        : (bool) ($content_defaults['participation_page_enabled'] ?? false),
    'qr_access' => (function () use ($content_config, $content_defaults) {
        require_once __DIR__ . '/qr_config.php';
        $raw = is_array($content_config['qr_access'] ?? null)
            ? $content_config['qr_access']
            : (is_array($content_defaults['qr_access'] ?? null) ? $content_defaults['qr_access'] : []);
        $defaults = is_array($content_defaults['qr_access'] ?? null) ? $content_defaults['qr_access'] : [];
        return qr_config_normalize($raw, $defaults);
    })(),
    'pages' => is_array($content_config['pages'] ?? null)
        ? $content_config['pages']
        : ($content_defaults['pages'] ?? []),
    'faq' => [],
    'background_mode' => $bg_mode,
    'background_color' => $bg_color,
    'background_scope' => $content_config['background_scope'] ?? ($content_defaults['background_scope'] ?? 'full_body'),
    'backgrounds' => [
        'default' => $default_background,
        'mobile' => $bg_mobile,
        'tablet' => $bg_tablet,
        'laptop' => $bg_laptop,
        'desktop' => $bg_desktop,
        'ultrawide' => $bg_ultrawide,
    ],
    'background_style' => [
        'position_vertical' => $background_style_content['position_vertical'] ?? ($content_defaults['background_style']['position_vertical'] ?? 'center'),
        'position_horizontal' => $background_style_content['position_horizontal'] ?? ($content_defaults['background_style']['position_horizontal'] ?? 'center'),
        'size' => $background_style_content['size'] ?? ($content_defaults['background_style']['size'] ?? 'cover'),
        'size_custom' => $background_style_content['size_custom'] ?? ($content_defaults['background_style']['size_custom'] ?? ''),
        'repeat' => $background_style_content['repeat'] ?? ($content_defaults['background_style']['repeat'] ?? 'no-repeat'),
        'attachment' => $background_style_content['attachment'] ?? ($content_defaults['background_style']['attachment'] ?? 'fixed'),
        'customize_per_size' => (bool)($background_style_content['customize_per_size'] ?? ($content_defaults['background_style']['customize_per_size'] ?? false)),
        'mobile' => $background_style_content['mobile'] ?? ($content_defaults['background_style']['mobile'] ?? []),
        'tablet' => $background_style_content['tablet'] ?? ($content_defaults['background_style']['tablet'] ?? []),
        'laptop' => $background_style_content['laptop'] ?? ($content_defaults['background_style']['laptop'] ?? []),
        'desktop' => $background_style_content['desktop'] ?? ($content_defaults['background_style']['desktop'] ?? []),
        'ultrawide' => $background_style_content['ultrawide'] ?? ($content_defaults['background_style']['ultrawide'] ?? []),
    ],
    'colors' => [
        'primary' => $palette_primary,
        'secondary' => $palette_secondary,
        'tertiary' => $palette_tertiary,
        'text' => $palette_text,
    ],
    'fonts' => normalize_fonts_config($content_config['fonts'] ?? ($content_defaults['fonts'] ?? [])),
    'style_customization' => (function () use ($content_config, $content_defaults): array {
        $defaults = is_array($content_defaults['style_customization'] ?? null)
            ? $content_defaults['style_customization']
            : [
                'buttons_colors' => false,
                'footer_text_colors' => false,
                'help_page_colors' => false,
                'form_field_colors' => false,
            ];

        if (is_array($content_config['style_customization'] ?? null)) {
            return array_merge($defaults, $content_config['style_customization']);
        }

        return $defaults;
    })(),
    'footer_style' => [
        'mode' => $footer_content['mode'] ?? ($content_defaults['footer_style']['mode'] ?? 'color'),
        'background' => getenv('FOOTER_BG_COLOR') ?: ($footer_content['background'] ?? $content_defaults['footer_style']['background']),
        'image' => $footer_content['image'] ?? ($content_defaults['footer_style']['image'] ?? ''),
        'opacity' => (int)($footer_content['opacity'] ?? $content_defaults['footer_style']['opacity'] ?? 100),
        'image_position_vertical' => $footer_content['image_position_vertical'] ?? ($content_defaults['footer_style']['image_position_vertical'] ?? 'center'),
        'image_position_horizontal' => $footer_content['image_position_horizontal'] ?? ($content_defaults['footer_style']['image_position_horizontal'] ?? 'center'),
        'image_size' => $footer_content['image_size'] ?? ($content_defaults['footer_style']['image_size'] ?? 'cover'),
        'image_size_custom' => $footer_content['image_size_custom'] ?? ($content_defaults['footer_style']['image_size_custom'] ?? ''),
        'image_repeat' => $footer_content['image_repeat'] ?? ($content_defaults['footer_style']['image_repeat'] ?? 'no-repeat'),
        'text_primary' => getenv('FOOTER_TEXT_PRIMARY_COLOR') ?: (getenv('FOOTER_TEXT_COLOR') ?: ($footer_content['text_primary'] ?? $content_defaults['footer_style']['text_primary'])),
        'text_secondary' => getenv('FOOTER_TEXT_SECONDARY_COLOR') ?: (getenv('FOOTER_LINK_COLOR') ?: ($footer_content['text_secondary'] ?? $content_defaults['footer_style']['text_secondary'])),
        'link' => getenv('FOOTER_LINK_COLOR') ?: ($footer_content['link'] ?? $content_defaults['footer_style']['link']),
        'link_hover' => getenv('FOOTER_LINK_HOVER_COLOR') ?: ($footer_content['link_hover'] ?? $content_defaults['footer_style']['link_hover']),
        'border_color' => getenv('FOOTER_BORDER_COLOR') ?: ($footer_content['border_color'] ?? ($content_defaults['footer_style']['border_color'] ?? CONFIG_COLOR_BLACK)),
        'border_opacity' => (int)($footer_content['border_opacity'] ?? $content_defaults['footer_style']['border_opacity'] ?? 10),
        'border_width' => (int)($footer_content['border_width'] ?? $content_defaults['footer_style']['border_width'] ?? 0),
        'border_sides' => $footer_content['border_sides'] ?? ($content_defaults['footer_style']['border_sides'] ?? []),
    ],
    'footer_layout' => $footer_layout_content,
    'buttons' => [
        'primary' => (function () use ($buttons_content, $content_defaults, $palette_primary, $content_config) {
            $current = is_array($buttons_content['primary'] ?? null) ? $buttons_content['primary'] : [];
            $default = is_array($content_defaults['buttons']['primary'] ?? null) ? $content_defaults['buttons']['primary'] : [];
            $fontConfig = is_array($content_config) ? $content_config : null;
            $hex = static function ($value, string $fallback): string {
                $value = trim((string) $value);
                if (strtolower($value) === 'transparent') {
                    return 'transparent';
                }
                return preg_match(CONFIG_REGEX_HEX6, $value) ? strtolower($value) : $fallback;
            };
            $mode = strtolower((string) ($current['mode'] ?? ($default['mode'] ?? 'color')));
            $useGradient = !empty($current['use_gradient'] ?? ($default['use_gradient'] ?? false));
            if ($mode === 'gradient') {
                $mode = 'color';
                $useGradient = true;
            }
            if (!in_array($mode, ['color', 'image'], true)) {
                $mode = 'color';
            }
            $size = strtolower((string) ($current['image_size'] ?? ($default['image_size'] ?? 'cover')));
            if (!in_array($size, ['cover', 'contain', 'auto', 'custom'], true)) {
                $size = 'cover';
            }
            $posV = strtolower((string) ($current['image_position_vertical'] ?? ($default['image_position_vertical'] ?? 'center')));
            $posH = strtolower((string) ($current['image_position_horizontal'] ?? ($default['image_position_horizontal'] ?? 'center')));
            $posV = in_array($posV, ['top', 'center', 'bottom'], true) ? $posV : 'center';
            $posH = in_array($posH, ['left', 'center', 'right'], true) ? $posH : 'center';
            $bg = $hex($current['bg'] ?? ($default['bg'] ?? $palette_primary), $palette_primary);
            $hoverBg = $hex($current['hover_bg'] ?? ($default['hover_bg'] ?? CONFIG_COLOR_PRIMARY_HOVER), CONFIG_COLOR_PRIMARY_HOVER);
            $radius = max(0, min(80, (int) ($current['radius'] ?? ($default['radius'] ?? 8))));
            $gradientDir = strtolower((string) ($current['gradient_direction'] ?? ($default['gradient_direction'] ?? 'vertical')));
            if (!in_array($gradientDir, ['vertical', 'horizontal'], true)) {
                $gradientDir = 'vertical';
            }
            $gradientStyle = strtolower((string) ($current['gradient_style'] ?? ($default['gradient_style'] ?? 'center')));
            if (!in_array($gradientStyle, ['linear', 'center'], true)) {
                $gradientStyle = 'center';
            }
            $gradientFrom = $hex($current['gradient_from'] ?? ($default['gradient_from'] ?? $bg), $bg);
            $gradientTo = $hex($current['gradient_to'] ?? ($default['gradient_to'] ?? $hoverBg), $hoverBg);
            $gradientHoverFrom = $hex($current['gradient_hover_from'] ?? ($default['gradient_hover_from'] ?? $gradientFrom), $gradientFrom);
            $gradientHoverTo = $hex($current['gradient_hover_to'] ?? ($default['gradient_hover_to'] ?? $gradientTo), $gradientTo);
            return [
                'text' => trim((string) ($current['text'] ?? ($default['text'] ?? 'PARTECIPA'))),
                'mode' => $mode,
                'bg' => $bg,
                'text_color' => $hex($current['text_color'] ?? ($default['text_color'] ?? CONFIG_COLOR_WHITE), CONFIG_COLOR_WHITE),
                'border' => $hex($current['border'] ?? ($default['border'] ?? $bg), $bg),
                'hover_bg' => $hoverBg,
                'hover_border' => $hex($current['hover_border'] ?? ($default['hover_border'] ?? $hoverBg), $hoverBg),
                'radius' => $radius,
                'font' => contest_resolve_font_family((string) ($current['font'] ?? ($default['font'] ?? '')), $fontConfig),
                'font_size' => contest_resolve_font_size((string) ($current['font_size'] ?? ($default['font_size'] ?? ''))),
                'use_gradient' => $mode === 'color' && $useGradient,
                'gradient_style' => $gradientStyle,
                'gradient_direction' => $gradientDir,
                'gradient_from' => $gradientFrom,
                'gradient_to' => $gradientTo,
                'gradient_hover_from' => $gradientHoverFrom,
                'gradient_hover_to' => $gradientHoverTo,
                'image' => asset_path_to_relative(trim((string) ($current['image'] ?? ($default['image'] ?? '')))),
                'hover_image' => asset_path_to_relative(trim((string) ($current['hover_image'] ?? ($default['hover_image'] ?? '')))),
                'image_size' => $size,
                'image_size_custom' => trim((string) ($current['image_size_custom'] ?? ($default['image_size_custom'] ?? ''))),
                'image_position_vertical' => $posV,
                'image_position_horizontal' => $posH,
                'hide_text_on_image' => (bool) ($current['hide_text_on_image'] ?? ($default['hide_text_on_image'] ?? true)),
            ];
        })(),
        'secondary' => (function () use ($buttons_content, $content_defaults, $content_config) {
            $current = is_array($buttons_content['secondary'] ?? null) ? $buttons_content['secondary'] : [];
            $default = is_array($content_defaults['buttons']['secondary'] ?? null) ? $content_defaults['buttons']['secondary'] : [];
            $fontConfig = is_array($content_config) ? $content_config : null;
            $hex = static function ($value, string $fallback): string {
                $value = trim((string) $value);
                if (strtolower($value) === 'transparent') {
                    return 'transparent';
                }
                return preg_match(CONFIG_REGEX_HEX6, $value) ? strtolower($value) : $fallback;
            };
            $mode = strtolower((string) ($current['mode'] ?? ($default['mode'] ?? 'color')));
            $useGradient = !empty($current['use_gradient'] ?? ($default['use_gradient'] ?? false));
            if ($mode === 'gradient') {
                $mode = 'color';
                $useGradient = true;
            }
            if (!in_array($mode, ['color', 'image'], true)) {
                $mode = 'color';
            }
            $size = strtolower((string) ($current['image_size'] ?? ($default['image_size'] ?? 'cover')));
            if (!in_array($size, ['cover', 'contain', 'auto', 'custom'], true)) {
                $size = 'cover';
            }
            $posV = strtolower((string) ($current['image_position_vertical'] ?? ($default['image_position_vertical'] ?? 'center')));
            $posH = strtolower((string) ($current['image_position_horizontal'] ?? ($default['image_position_horizontal'] ?? 'center')));
            $posV = in_array($posV, ['top', 'center', 'bottom'], true) ? $posV : 'center';
            $posH = in_array($posH, ['left', 'center', 'right'], true) ? $posH : 'center';
            $bg = $hex($current['bg'] ?? ($default['bg'] ?? CONFIG_COLOR_SECONDARY), CONFIG_COLOR_SECONDARY);
            $hoverBg = $hex($current['hover_bg'] ?? ($default['hover_bg'] ?? CONFIG_COLOR_SECONDARY_DARK), CONFIG_COLOR_SECONDARY_DARK);
            $radius = max(0, min(80, (int) ($current['radius'] ?? ($default['radius'] ?? 8))));
            $gradientDir = strtolower((string) ($current['gradient_direction'] ?? ($default['gradient_direction'] ?? 'vertical')));
            if (!in_array($gradientDir, ['vertical', 'horizontal'], true)) {
                $gradientDir = 'vertical';
            }
            $gradientStyle = strtolower((string) ($current['gradient_style'] ?? ($default['gradient_style'] ?? 'center')));
            if (!in_array($gradientStyle, ['linear', 'center'], true)) {
                $gradientStyle = 'center';
            }
            $gradientFrom = $hex($current['gradient_from'] ?? ($default['gradient_from'] ?? $bg), $bg);
            $gradientTo = $hex($current['gradient_to'] ?? ($default['gradient_to'] ?? $hoverBg), $hoverBg);
            $gradientHoverFrom = $hex($current['gradient_hover_from'] ?? ($default['gradient_hover_from'] ?? $gradientFrom), $gradientFrom);
            $gradientHoverTo = $hex($current['gradient_hover_to'] ?? ($default['gradient_hover_to'] ?? $gradientTo), $gradientTo);
            return [
                'text' => trim((string) ($current['text'] ?? ($default['text'] ?? 'SECONDARIO'))),
                'mode' => $mode,
                'bg' => $bg,
                'text_color' => $hex($current['text_color'] ?? ($default['text_color'] ?? CONFIG_COLOR_WHITE), CONFIG_COLOR_WHITE),
                'border' => $hex($current['border'] ?? ($default['border'] ?? $bg), $bg),
                'hover_bg' => $hoverBg,
                'hover_border' => $hex($current['hover_border'] ?? ($default['hover_border'] ?? $hoverBg), $hoverBg),
                'radius' => $radius,
                'font' => contest_resolve_font_family((string) ($current['font'] ?? ($default['font'] ?? '')), $fontConfig),
                'font_size' => contest_resolve_font_size((string) ($current['font_size'] ?? ($default['font_size'] ?? ''))),
                'use_gradient' => $mode === 'color' && $useGradient,
                'gradient_style' => $gradientStyle,
                'gradient_direction' => $gradientDir,
                'gradient_from' => $gradientFrom,
                'gradient_to' => $gradientTo,
                'gradient_hover_from' => $gradientHoverFrom,
                'gradient_hover_to' => $gradientHoverTo,
                'image' => asset_path_to_relative(trim((string) ($current['image'] ?? ($default['image'] ?? '')))),
                'hover_image' => asset_path_to_relative(trim((string) ($current['hover_image'] ?? ($default['hover_image'] ?? '')))),
                'image_size' => $size,
                'image_size_custom' => trim((string) ($current['image_size_custom'] ?? ($default['image_size_custom'] ?? ''))),
                'image_position_vertical' => $posV,
                'image_position_horizontal' => $posH,
                'hide_text_on_image' => (bool) ($current['hide_text_on_image'] ?? ($default['hide_text_on_image'] ?? true)),
            ];
        })(),
        'regolamento' => [
            'text' => $buttons_content['regolamento']['text'] ?? $content_defaults['buttons']['regolamento']['text'],
        ],
        'thankyou' => [
            'regolamento_text' => $buttons_content['thankyou']['regolamento_text'] ?? ($buttons_content['regolamento']['text'] ?? $content_defaults['buttons']['thankyou']['regolamento_text']),
            'retry_text' => $buttons_content['thankyou']['retry_text'] ?? $content_defaults['buttons']['thankyou']['retry_text'],
        ],
        'help' => [
            'text' => trim((string) ($buttons_content['help']['text'] ?? ($content_defaults['buttons']['help']['text'] ?? 'INVIA'))),
        ],
        'quiz_submit_text' => trim((string) ($buttons_content['quiz_submit_text'] ?? ($content_defaults['buttons']['quiz_submit_text'] ?? 'INVIA RISPOSTE'))),
    ],
    'fidelity' => [
        'type' => $fidelity_content['type'] ?? $content_defaults['fidelity']['type'],
        'length' => isset($fidelity_content['length']) ? (int) $fidelity_content['length'] : $content_defaults['fidelity']['length'],
        'unique_participation' => (bool) ($fidelity_content['unique_participation'] ?? ($content_defaults['fidelity']['unique_participation'] ?? false)),
        'field_width' => (function () use ($fidelity_content, $content_defaults) {
            $width = trim((string) ($fidelity_content['field_width'] ?? ($content_defaults['fidelity']['field_width'] ?? 'large')));
            return in_array($width, ['small', 'large'], true) ? $width : 'large';
        })(),
        'help_text' => trim((string) ($fidelity_content['help_text'] ?? ($content_defaults['fidelity']['help_text'] ?? ''))),
        'help_text_color' => (function () use ($fidelity_content, $content_defaults) {
            $val = trim((string) ($fidelity_content['help_text_color'] ?? ($content_defaults['fidelity']['help_text_color'] ?? '')));
            return preg_match(CONFIG_REGEX_HEX6, $val) ? strtolower($val) : '';
        })(),
    ],
    'scontrino_help_text' => trim((string) ($content_config['scontrino_help_text'] ?? ($content_defaults['scontrino_help_text'] ?? ''))),
    'scontrino_help_text_color' => (function () use ($content_config, $content_defaults) {
        $val = trim((string) ($content_config['scontrino_help_text_color'] ?? ($content_defaults['scontrino_help_text_color'] ?? '')));
        return preg_match(CONFIG_REGEX_HEX6, $val) ? strtolower($val) : '';
    })(),
    'codice_partecipazione_help_text' => trim((string) ($content_config['codice_partecipazione_help_text'] ?? ($content_defaults['codice_partecipazione_help_text'] ?? ''))),
    'codice_partecipazione_help_text_color' => (function () use ($content_config, $content_defaults) {
        $val = trim((string) ($content_config['codice_partecipazione_help_text_color'] ?? ($content_defaults['codice_partecipazione_help_text_color'] ?? '')));
        return preg_match(CONFIG_REGEX_HEX6, $val) ? strtolower($val) : '';
    })(),
    'cellulare_digits_min' => (function () use ($content_config, $content_defaults) {
        if (isset($content_config['cellulare_digits_min'])) {
            return max(6, min(12, (int) $content_config['cellulare_digits_min']));
        }
        if (isset($content_config['cellulare_digits'])) {
            return max(6, min(12, (int) $content_config['cellulare_digits']));
        }
        return max(6, min(12, (int) ($content_defaults['cellulare_digits_min'] ?? 6)));
    })(),
    'cellulare_digits_max' => (function () use ($content_config, $content_defaults) {
        if (isset($content_config['cellulare_digits_max'])) {
            return max(6, min(12, (int) $content_config['cellulare_digits_max']));
        }
        if (isset($content_config['cellulare_digits'])) {
            return max(6, min(12, (int) $content_config['cellulare_digits']));
        }
        return max(6, min(12, (int) ($content_defaults['cellulare_digits_max'] ?? 12)));
    })(),
    'form_container' => $form_container_normalized,
    'public_page_containers' => $public_page_containers_normalized,
    'form_field_style' => (function () use ($content_config, $content_defaults) {
        $val = strtolower((string) ($content_config['form_field_style'] ?? ($content_defaults['form_field_style'] ?? 'label_top')));
        return in_array($val, ['label_top', 'placeholder'], true) ? $val : 'label_top';
    })(),
    'form_field_font' => contest_resolve_font_family(
        (string) ($content_config['form_field_font'] ?? ($content_defaults['form_field_font'] ?? '')),
        ['fonts' => normalize_fonts_config($content_config['fonts'] ?? ($content_defaults['fonts'] ?? []))]
    ),
    'form_field_font_size' => contest_resolve_font_size(
        (string) ($content_config['form_field_font_size'] ?? ($content_defaults['form_field_font_size'] ?? ''))
    ),
    'form_field_border_radius' => max(0, min(40, (int) ($content_config['form_field_border_radius'] ?? ($content_defaults['form_field_border_radius'] ?? 6)))),
    'form_field_text_color' => (function () use ($content_config, $content_defaults) {
        $val = trim((string) ($content_config['form_field_text_color'] ?? ($content_defaults['form_field_text_color'] ?? CONFIG_COLOR_BLACK)));
        return preg_match(CONFIG_REGEX_HEX6, $val) ? strtolower($val) : CONFIG_COLOR_BLACK;
    })(),
    'form_field_placeholder_color' => (function () use ($content_config, $content_defaults) {
        $val = trim((string) ($content_config['form_field_placeholder_color'] ?? ($content_defaults['form_field_placeholder_color'] ?? '')));
        return preg_match(CONFIG_REGEX_HEX6, $val) ? strtolower($val) : '';
    })(),
    'form_field_border_color' => (function () use ($content_config, $content_defaults) {
        $val = trim((string) ($content_config['form_field_border_color'] ?? ($content_defaults['form_field_border_color'] ?? CONFIG_COLOR_BORDER)));
        return preg_match(CONFIG_REGEX_HEX6, $val) ? strtolower($val) : CONFIG_COLOR_BORDER;
    })(),
    'form_field_bg_color' => (function () use ($content_config, $content_defaults) {
        $val = trim((string) ($content_config['form_field_bg_color'] ?? ($content_defaults['form_field_bg_color'] ?? CONFIG_COLOR_WHITE)));
        return preg_match(CONFIG_REGEX_HEX6, $val) ? strtolower($val) : CONFIG_COLOR_WHITE;
    })(),
    'form_field_focus_color' => (function () use ($content_config, $content_defaults) {
        $val = trim((string) ($content_config['form_field_focus_color'] ?? ($content_defaults['form_field_focus_color'] ?? '')));
        return preg_match(CONFIG_REGEX_HEX6, $val) ? strtolower($val) : '';
    })(),
    'form_field_checkbox_color' => (function () use ($content_config, $content_defaults) {
        $val = trim((string) ($content_config['form_field_checkbox_color'] ?? ($content_defaults['form_field_checkbox_color'] ?? '')));
        return preg_match(CONFIG_REGEX_HEX6, $val) ? strtolower($val) : '';
    })(),
    'form_field_consent_text_color' => (function () use ($content_config, $content_defaults) {
        $val = trim((string) ($content_config['form_field_consent_text_color'] ?? ($content_defaults['form_field_consent_text_color'] ?? CONFIG_COLOR_BLACK)));
        return preg_match(CONFIG_REGEX_HEX6, $val) ? strtolower($val) : CONFIG_COLOR_BLACK;
    })(),
    'form_field_consent_link_color' => (function () use ($content_config, $content_defaults) {
        $val = trim((string) ($content_config['form_field_consent_link_color'] ?? ($content_defaults['form_field_consent_link_color'] ?? '')));
        return preg_match(CONFIG_REGEX_HEX6, $val) ? strtolower($val) : '';
    })(),
    'form_field_consent_link_hover_color' => (function () use ($content_config, $content_defaults) {
        $val = trim((string) ($content_config['form_field_consent_link_hover_color'] ?? ($content_defaults['form_field_consent_link_hover_color'] ?? '')));
        return preg_match(CONFIG_REGEX_HEX6, $val) ? strtolower($val) : '';
    })(),
    'form_field_bg_opacity' => max(0, min(100, (int) ($content_config['form_field_bg_opacity'] ?? ($content_defaults['form_field_bg_opacity'] ?? 100)))),
    'form_field_focus_opacity' => max(0, min(100, (int) ($content_config['form_field_focus_opacity'] ?? ($content_defaults['form_field_focus_opacity'] ?? 100)))),
    'form_field_consent_link_hover_opacity' => max(0, min(100, (int) ($content_config['form_field_consent_link_hover_opacity'] ?? ($content_defaults['form_field_consent_link_hover_opacity'] ?? 100)))),
    'form_field_error_mode' => (function () use ($content_config, $content_defaults) {
        $mode = strtolower(trim((string) ($content_config['form_field_error_mode'] ?? ($content_defaults['form_field_error_mode'] ?? 'classic'))));
        return in_array($mode, ['classic', 'on_color_background'], true) ? $mode : 'classic';
    })(),
    'form_field_select2_highlight_bg' => (function () use ($content_config, $content_defaults) {
        $val = trim((string) ($content_config['form_field_select2_highlight_bg'] ?? ($content_defaults['form_field_select2_highlight_bg'] ?? '')));
        if ($val === '') {
            $val = trim((string) ($content_config['form_field_select2_color'] ?? ''));
        }
        return preg_match(CONFIG_REGEX_HEX6, $val) ? strtolower($val) : '';
    })(),
    'form_field_select2_highlight_text' => (function () use ($content_config, $content_defaults) {
        $val = trim((string) ($content_config['form_field_select2_highlight_text'] ?? ($content_defaults['form_field_select2_highlight_text'] ?? '')));
        return preg_match(CONFIG_REGEX_HEX6, $val) ? strtolower($val) : '';
    })(),
    'form_field_calendar_popup_bg' => (function () use ($content_config, $content_defaults) {
        $val = trim((string) ($content_config['form_field_calendar_popup_bg'] ?? ($content_defaults['form_field_calendar_popup_bg'] ?? '')));
        return preg_match(CONFIG_REGEX_HEX6, $val) ? strtolower($val) : '';
    })(),
    'form_field_calendar_selected_color' => (function () use ($content_config, $content_defaults) {
        $val = trim((string) ($content_config['form_field_calendar_selected_color'] ?? ($content_defaults['form_field_calendar_selected_color'] ?? '')));
        return preg_match(CONFIG_REGEX_HEX6, $val) ? strtolower($val) : '';
    })(),
    'form_field_calendar_hover_bg' => (function () use ($content_config, $content_defaults) {
        $val = trim((string) ($content_config['form_field_calendar_hover_bg'] ?? ($content_defaults['form_field_calendar_hover_bg'] ?? '')));
        return preg_match(CONFIG_REGEX_HEX6, $val) ? strtolower($val) : '';
    })(),
    'form_field_calendar_hover_text' => (function () use ($content_config, $content_defaults) {
        $val = trim((string) ($content_config['form_field_calendar_hover_text'] ?? ($content_defaults['form_field_calendar_hover_text'] ?? '')));
        return preg_match(CONFIG_REGEX_HEX6, $val) ? strtolower($val) : '';
    })(),
    'help_page_style' => is_array($content_config['help_page_style'] ?? null)
        ? $content_config['help_page_style']
        : ($content_defaults['help_page_style'] ?? []),
    'quiz_enabled' => (bool) ($content_config['quiz_enabled'] ?? ($content_defaults['quiz_enabled'] ?? false)),
    'instantwin_shared_thankyou_page' => (bool) ($content_config['instantwin_shared_thankyou_page'] ?? ($content_defaults['instantwin_shared_thankyou_page'] ?? false)),
    'quiz' => $content_config['quiz'] ?? ($content_defaults['quiz'] ?? ['num_questions' => 4, 'mode' => 'standard', 'require_all_correct' => true, 'instantwin_separate_thankyou_quiz_failed' => false]),
    'fields_enabled' => [
        'nome' => env_flag('FIELD_NOME_ENABLED', $content_fields['nome'] ?? true),
        'cognome' => env_flag('FIELD_COGNOME_ENABLED', $content_fields['cognome'] ?? true),
        'email' => env_flag('FIELD_EMAIL_ENABLED', $content_fields['email'] ?? true),
        'prefisso' => env_flag('FIELD_PREFISSO_ENABLED', $content_fields['prefisso'] ?? true),
        'cellulare' => env_flag('FIELD_CELLULARE_ENABLED', $content_fields['cellulare'] ?? true),
        'prov_pdv' => env_flag('FIELD_PROV_PDV_ENABLED', $content_fields['prov_pdv'] ?? true),
        'citta_pdv' => env_flag('FIELD_CITTA_PDV_ENABLED', $content_fields['citta_pdv'] ?? true),
        'indirizzo_pdv' => env_flag('FIELD_INDIRIZZO_PDV_ENABLED', $content_fields['indirizzo_pdv'] ?? true),
        'importo' => env_flag('FIELD_IMPORTO_ENABLED', $content_fields['importo'] ?? true),
        'data_scontrino' => env_flag('FIELD_DATA_SCONTRINO_ENABLED', $content_fields['data_scontrino'] ?? true),
        'provincia' => env_flag('FIELD_PROVINCIA_ENABLED', $content_fields['provincia'] ?? true),
        'cap' => env_flag('FIELD_CAP_ENABLED', $content_fields['cap'] ?? false),
        'citta' => env_flag('FIELD_CITTA_ENABLED', $content_fields['citta'] ?? true),
        'indirizzo' => env_flag('FIELD_INDIRIZZO_ENABLED', $content_fields['indirizzo'] ?? true),
        'scontrino' => env_flag('FIELD_SCONTRINO_ENABLED', $content_fields['scontrino'] ?? true),
        'privacy' => env_flag('FIELD_PRIVACY_ENABLED', $content_fields['privacy'] ?? true),
        'marketing' => env_flag('FIELD_MARKETING_ENABLED', $content_fields['marketing'] ?? true),
        'profilazione' => env_flag('FIELD_PROFILAZIONE_ENABLED', $content_fields['profilazione'] ?? true),
        'fidelity' => env_flag('FIELD_FIDELITY_ENABLED', $content_fields['fidelity'] ?? true),
        'codice_partecipazione' => env_flag('FIELD_CODICE_PARTECIPAZIONE_ENABLED', $content_fields['codice_partecipazione'] ?? false),
    ],
    'fields_required' => [
        'nome' => (bool) ($content_required_fields['nome'] ?? true),
        'cognome' => (bool) ($content_required_fields['cognome'] ?? true),
        'email' => (bool) ($content_required_fields['email'] ?? true),
        'prefisso' => (bool) ($content_required_fields['prefisso'] ?? true),
        'cellulare' => (bool) ($content_required_fields['cellulare'] ?? true),
        'prov_pdv' => (bool) ($content_required_fields['prov_pdv'] ?? true),
        'citta_pdv' => (bool) ($content_required_fields['citta_pdv'] ?? true),
        'indirizzo_pdv' => (bool) ($content_required_fields['indirizzo_pdv'] ?? true),
        'importo' => (bool) ($content_required_fields['importo'] ?? true),
        'data_scontrino' => (bool) ($content_required_fields['data_scontrino'] ?? true),
        'provincia' => (bool) ($content_required_fields['provincia'] ?? true),
        'cap' => (bool) ($content_required_fields['cap'] ?? false),
        'citta' => (bool) ($content_required_fields['citta'] ?? true),
        'indirizzo' => (bool) ($content_required_fields['indirizzo'] ?? true),
        'scontrino' => (bool) ($content_required_fields['scontrino'] ?? true),
        'privacy' => (bool) ($content_required_fields['privacy'] ?? true),
        'marketing' => (bool) ($content_required_fields['marketing'] ?? false),
        'profilazione' => (bool) ($content_required_fields['profilazione'] ?? false),
        'fidelity' => (bool) ($content_required_fields['fidelity'] ?? true),
        'codice_partecipazione' => (bool) ($content_required_fields['codice_partecipazione'] ?? false),
    ],
    'standard_fields_order' => (static function () use ($content_config, $content_defaults) {
        require_once __DIR__ . '/standard_fields_order.inc.php';
        $raw = $content_config['standard_fields_order'] ?? ($content_defaults['standard_fields_order'] ?? null);
        return gst_standard_fields_normalize_order($raw);
    })(),
    'custom_fields' => $custom_fields_content,
];
$contest_config['cellulare_digits_max'] = max($contest_config['cellulare_digits_max'], $contest_config['cellulare_digits_min']);

// FAQ configurabili: lette da content.json, fallback ai default di content.defaults.json
$normalize_faq_order = static function (array $items): array {
    $hasOrder = false;
    foreach ($items as $item) {
        if (is_array($item) && array_key_exists('order', $item)) {
            $hasOrder = true;
            break;
        }
    }
    if (!$hasOrder) {
        return $items;
    }
    usort($items, static function (array $a, array $b): int {
        $oa = isset($a['order']) ? (int) $a['order'] : PHP_INT_MAX;
        $ob = isset($b['order']) ? (int) $b['order'] : PHP_INT_MAX;
        if ($oa === $ob) {
            return 0;
        }
        return $oa < $ob ? -1 : 1;
    });
    return $items;
};

$faq_from_content = $content_config['faq'] ?? null;
if (is_array($faq_from_content) && !empty($faq_from_content)) {
    $contest_config['faq'] = $normalize_faq_order($faq_from_content);
} else {
    $contest_config['faq'] = $normalize_faq_order($content_defaults['faq'] ?? []);
}

// Helper for favicons
if (!function_exists('get_favicon_tags')) {
function get_favicon_tags($relative_from_manager = false) {
    global $contest_config;
    $favicon_path = $contest_config['favicon'] ?? 'assets/img/default_img/favicon.png';
    $favicon_mobile = $contest_config['favicon_mobile'] ?? $favicon_path;

    if ($relative_from_manager) {
        $favicon_path = '../' . ltrim($favicon_path, '/');
        $favicon_mobile = '../' . ltrim($favicon_mobile, '/');
    }

    $html  = '<link rel="icon" type="image/png" href="' . htmlspecialchars($favicon_path) . '">' . "\n";
    if (!empty($favicon_mobile)) {
        $html .= '<link rel="apple-touch-icon" sizes="180x180" href="' . htmlspecialchars($favicon_mobile) . '">' . "\n";
    }
    return $html;
}
}

if (!function_exists('config_background_normalize_path')) {
function config_background_normalize_path(string $path, bool $relative_from_manager): string {
    $path = trim($path);
    if ($path === '') {
        return '';
    }

    if (str_starts_with($path, CONFIG_SCHEME_HTTP) || str_starts_with($path, CONFIG_SCHEME_HTTPS) || str_starts_with($path, '//')) {
        return $path;
    }

    if ($path[0] === '/') {
        return $path;
    }

    return $relative_from_manager ? '../' . ltrim($path, '/') : $path;
}
}

if (!function_exists('config_build_bg_css')) {
function config_build_bg_css(array $style_options, string $image_url, string $bg_scope = 'full_body', string $bg_color_fallback = CONFIG_COLOR_WHITE): string {
    $pos_v = $style_options['position_vertical'] ?? 'center';
    $pos_h = $style_options['position_horizontal'] ?? 'center';
    $bg_position = trim($pos_h . ' ' . $pos_v);

    $bg_size = $style_options['size'] ?? 'cover';
    if ($bg_size === 'custom' && !empty(trim($style_options['size_custom'] ?? ''))) {
        $bg_size = htmlspecialchars(trim($style_options['size_custom']), ENT_QUOTES);
    }

    $bg_repeat = $style_options['repeat'] ?? 'no-repeat';
    $bg_attachment = ($bg_scope === 'header_main') ? 'scroll' : ($style_options['attachment'] ?? 'fixed');

    return CONFIG_CSS_BG_IMAGE_URL . htmlspecialchars($image_url, ENT_QUOTES) . "') !important; " .
           "background-size: " . htmlspecialchars($bg_size, ENT_QUOTES) . CONFIG_CSS_IMPORTANT_SPACE .
           "background-repeat: " . htmlspecialchars($bg_repeat, ENT_QUOTES) . CONFIG_CSS_IMPORTANT_SPACE .
           "background-position: " . htmlspecialchars($bg_position, ENT_QUOTES) . CONFIG_CSS_IMPORTANT_SPACE .
           "background-attachment: " . htmlspecialchars($bg_attachment, ENT_QUOTES) . CONFIG_CSS_IMPORTANT_SPACE .
           CONFIG_CSS_BG_COLOR_PREFIX_NOSPACE . htmlspecialchars($bg_color_fallback, ENT_QUOTES) . ";";
}
}

if (!function_exists('config_background_color_mode_style_block')) {
function config_background_color_mode_style_block(string $color, string $scope): string {
    $hColor = htmlspecialchars($color, ENT_QUOTES);
    if ($scope === 'header_main') {
        return "<style>\n.body-background-wrapper { background-color: transparent !important; background-image: none !important; }\n.header-main-background-wrapper { background-color: {$hColor} !important; background-image: none !important; }\n</style>\n";
    }

    return "<style>\n.body-background-wrapper { background-color: {$hColor} !important; background-image: none !important; }\n.header-main-background-wrapper { background-color: transparent !important; background-image: none !important; }\n</style>\n";
}
}

if (!function_exists('config_append_media_query_bg')) {
function config_append_media_query_bg(
    string &$css,
    string $mediaQuery,
    string $active_selector,
    string $disabled_selector,
    bool $use_pseudo_element,
    string $bg_css
): void {
    $css .= "@media {$mediaQuery} {\n";
    if ($use_pseudo_element) {
        $css .= "  {$active_selector}::before { {$bg_css} }\n";
    } else {
        $css .= "  {$active_selector} { {$bg_css} }\n";
    }
    $css .= "  {$disabled_selector} { background-image: none !important; background-color: transparent !important; }\n";
    if ($use_pseudo_element) {
        $css .= "  {$disabled_selector}::before { display: none !important; }\n";
    }
    $css .= "}\n";
}
}

if (!function_exists('get_background_style_block')) {
function get_background_style_block(bool $relative_from_manager = false): string {
    global $contest_config;

    $mode = $contest_config['background_mode'] ?? 'image';
    $scope = $contest_config['background_scope'] ?? 'full_body';

    if ($mode === 'color') {
        $color = $contest_config['background_color'] ?? CONFIG_COLOR_WHITE;
        return config_background_color_mode_style_block($color, $scope);
    }

    $backgrounds = $contest_config['backgrounds'] ?? [];
    $default = $backgrounds['default'] ?? 'assets/img/default_img/bg-generic.svg';
    $resolved = [
        'default' => config_background_normalize_path($default, $relative_from_manager),
        'mobile' => config_background_normalize_path($backgrounds['mobile'] ?? $default, $relative_from_manager),
        'tablet' => config_background_normalize_path($backgrounds['tablet'] ?? $default, $relative_from_manager),
        'laptop' => config_background_normalize_path($backgrounds['laptop'] ?? $default, $relative_from_manager),
        'desktop' => config_background_normalize_path($backgrounds['desktop'] ?? $default, $relative_from_manager),
        'ultrawide' => config_background_normalize_path($backgrounds['ultrawide'] ?? ($backgrounds['desktop'] ?? $default), $relative_from_manager),
    ];

    $bg_style = $contest_config['background_style'] ?? [];
    $customize_per_size = (bool)($bg_style['customize_per_size'] ?? false);

    $global_style = [
        'position_vertical' => $bg_style['position_vertical'] ?? 'center',
        'position_horizontal' => $bg_style['position_horizontal'] ?? 'center',
        'size' => $bg_style['size'] ?? 'cover',
        'size_custom' => $bg_style['size_custom'] ?? '',
        'repeat' => $bg_style['repeat'] ?? 'no-repeat',
        'attachment' => $bg_style['attachment'] ?? 'fixed',
    ];

    $css = "<style>\n";

    if ($scope === 'header_main') {
        $active_selector = '.header-main-background-wrapper';
        $disabled_selector = '.body-background-wrapper';
        $use_pseudo_element = true;
    } else {
        $active_selector = '.body-background-wrapper';
        $disabled_selector = '.header-main-background-wrapper';
        $use_pseudo_element = false;
    }

    $css .= "{$disabled_selector} { background-image: none !important; background-color: transparent !important; }\n";
    if ($use_pseudo_element) {
        $css .= "{$disabled_selector}::before { display: none !important; }\n";
    }

    $bg_color_fallback = $contest_config['background_color'] ?? CONFIG_COLOR_WHITE;

    $resolveStyle = static function (string $sizeKey) use ($customize_per_size, $bg_style, $global_style): array {
        return $customize_per_size && !empty($bg_style[$sizeKey] ?? []) ? $bg_style[$sizeKey] : $global_style;
    };

    $default_style = $resolveStyle('desktop');
    $base_bg = config_build_bg_css($default_style, $resolved['default'], $scope, $bg_color_fallback);

    if ($use_pseudo_element) {
        $css .= "{$active_selector} { position: relative !important; }\n";
        $css .= "{$active_selector}::before { content: ''; position: absolute; top: 0; left: 50%; transform: translateX(-50%); width: 100vw; height: 100%; z-index: 0; pointer-events: none; {$base_bg} }\n";
        $css .= "{$active_selector} > * { position: relative; z-index: 1; }\n";
    } else {
        $css .= "{$active_selector} { {$base_bg} }\n";
    }

    $mediaBreakpoints = [
        ['(max-width: 767.98px)', 'mobile', 'mobile'],
        ['(min-width: 768px) and (max-width: 1023.98px)', 'tablet', 'tablet'],
        ['(min-width: 1024px) and (max-width: 1365.98px)', 'laptop', 'laptop'],
        ['(min-width: 1366px) and (max-width: 1919.98px)', 'desktop', 'desktop'],
        ['(min-width: 1920px) and (max-width: 2559.98px)', 'desktop', 'default'],
        ['(min-width: 2560px)', 'ultrawide', 'ultrawide'],
    ];
    foreach ($mediaBreakpoints as [$query, $styleKey, $imageKey]) {
        $style = $resolveStyle($styleKey);
        $bg = config_build_bg_css($style, $resolved[$imageKey], $scope, $bg_color_fallback);
        config_append_media_query_bg($css, $query, $active_selector, $disabled_selector, $use_pseudo_element, $bg);
    }

    $css .= "</style>\n";

    return $css;
}
}

if (!function_exists('normalize_logo_desktop_size')) {
function normalize_logo_desktop_size(string $size): string {
    $size = strtolower(trim($size));
    return in_array($size, ['compact', 'medium', 'large', 'custom'], true) ? $size : 'medium';
}
}

if (!function_exists('get_logo_desktop_display_limits')) {
/**
 * @return array{max_width: string, max_height: string}|null null = larghezza piena (large)
 */
function get_logo_desktop_display_limits(?array $config = null): ?array {
    if ($config === null) {
        global $contest_config;
        $config = is_array($contest_config ?? null) ? $contest_config : [];
    }
    $size = normalize_logo_desktop_size((string) ($config['logo_desktop_size'] ?? 'medium'));
    if ($size === 'large') {
        return null;
    }
    if ($size === 'custom') {
        $widthPct = (int) ($config['logo_desktop_width_percent'] ?? 85);
        $widthPct = max(40, min(100, $widthPct));
        $maxHeightPx = (int) ($config['logo_desktop_max_height_px'] ?? 0);
        $maxHeight = 'none';
        if ($maxHeightPx > 0) {
            $maxHeightPx = max(60, min(500, $maxHeightPx));
            $maxHeight = $maxHeightPx . 'px';
        }
        return [
            'max_width' => $widthPct . '%',
            'max_height' => $maxHeight,
        ];
    }
    if ($size === 'compact') {
        return ['max_width' => '62%', 'max_height' => '110px'];
    }
    return ['max_width' => '78%', 'max_height' => '130px'];
}
}

if (!function_exists('get_logo_desktop_size_style_block')) {
function get_logo_desktop_size_style_block(): string {
    $limits = get_logo_desktop_display_limits();
    if ($limits === null) {
        return '';
    }
    $maxWidth = $limits['max_width'];
    $maxHeight = $limits['max_height'];
    $css = "<style>\n";
    $css .= "@media (min-width: 768px) {\n";
    $css .= "  .logo-stack .card-logo-wrapper {\n";
    $css .= "    display: flex;\n";
    $css .= "    justify-content: center;\n";
    $css .= "  }\n";
    // Size limits apply to the logo only — never constrain the full-bleed header banner.
    $css .= "  .logo-stack .card-logo-wrapper > picture:not(:has(.brand-header-img)) {\n";
    $css .= "    width: auto;\n";
    $css .= "    max-width: {$maxWidth};\n";
    $css .= "  }\n";
    $css .= "  .logo-stack .card-logo-img:not(.brand-header-img) {\n";
    $css .= "    width: auto !important;\n";
    $css .= "    max-width: {$maxWidth} !important;\n";
    if ($maxHeight !== 'none') {
        $css .= "    max-height: {$maxHeight} !important;\n";
    }
    $css .= "    margin-left: auto !important;\n";
    $css .= "    margin-right: auto !important;\n";
    $css .= "  }\n";
    $css .= "  .logo-stack .card-logo-wrapper > picture:has(.brand-header-img),\n";
    $css .= "  .logo-stack .card-logo-wrapper .brand-header-img {\n";
    $css .= "    width: 100vw !important;\n";
    $css .= "    max-width: none !important;\n";
    $css .= "    max-height: none !important;\n";
    $css .= "    height: auto !important;\n";
    $css .= "    margin-left: calc(50% - 50vw) !important;\n";
    $css .= "    margin-right: calc(50% - 50vw) !important;\n";
    $css .= "  }\n";
    $css .= "}\n";
    $css .= "</style>\n";
    return $css;
}
}

if (!function_exists('get_brand_layout_style_block')) {
function get_brand_layout_style_block(string $pageKey, ?array $config = null): string {
    if ($config === null) {
        global $contest_config;
        $config = is_array($contest_config ?? null) ? $contest_config : [];
    }

    $brand = resolve_brand_display_context($config);
    if (!$brand['has_brand_visual']) {
        return '';
    }

    $pageLayouts = is_array($config['brand_page_layout'] ?? null)
        ? $config['brand_page_layout']
        : normalize_brand_layout_collection($config['brand_page_layout'] ?? []);
    $layout = is_array($pageLayouts[$pageKey] ?? null)
        ? normalize_brand_layout_item($pageLayouts[$pageKey])
        : brand_layout_default_item();

    $logoTopRaw = is_array($config['logo_top_spacing'] ?? null)
        ? $config['logo_top_spacing']
        : normalize_logo_top_spacing_config($config['logo_top_spacing'] ?? []);
    $logoTop = normalize_logo_top_spacing_config($logoTopRaw);
    if (!($brand['show_logo'] && !$brand['show_header'])) {
        $logoTop = ['mobile_px' => 0, 'tablet_px' => 0, 'desktop_px' => 0];
    }

    $headerLogoGapRaw = is_array($config['brand_header_logo_gap'] ?? null)
        ? $config['brand_header_logo_gap']
        : normalize_brand_header_logo_gap_config($config['brand_header_logo_gap'] ?? []);
    $headerLogoGap = normalize_brand_header_logo_gap_config($headerLogoGapRaw);
    if (!($brand['show_logo'] && $brand['show_header'])) {
        $headerLogoGap = ['mobile_px' => 0, 'tablet_px' => 0, 'desktop_px' => 0];
    }

    $headerTopSpacingRaw = is_array($config['brand_header_top_spacing'] ?? null)
        ? $config['brand_header_top_spacing']
        : normalize_brand_header_top_spacing_config($config['brand_header_top_spacing'] ?? []);
    $headerTopSpacing = normalize_brand_header_top_spacing_config($headerTopSpacingRaw);
    if (!$brand['show_header']) {
        $headerTopSpacing = ['mobile_px' => 0, 'tablet_px' => 0, 'desktop_px' => 0];
    }

    $pageClass = preg_replace('/[^a-z0-9_-]+/i', '-', $pageKey);
    $pageClass = $pageClass !== '' ? $pageClass : 'default';

    $buildVars = static function (int $overlap, int $logoSpacing, int $headerLogoGapPx, int $headerTopSpacingPx): string {
        return "  --brand-overlap: {$overlap}px;\n"
            . "  --brand-logo-top-spacing: {$logoSpacing}px;\n"
            . "  --brand-header-logo-gap: {$headerLogoGapPx}px;\n"
            . "  --brand-header-top-spacing: {$headerTopSpacingPx}px;\n";
    };

    $css = "<style>\n";
    $css .= ".header-main-background-wrapper.brand-layout-page-active { padding-top: 0 !important; }\n";
    $css .= ".header-main-background-wrapper.brand-layout-page-active > main { margin-top: 0 !important; }\n";
    $css .= ".logo-stack.brand-layout-page--{$pageClass} {\n";
    $css .= $buildVars((int) ($layout['mobile']['overlap_px'] ?? 0), (int) ($logoTop['mobile_px'] ?? 0), (int) ($headerLogoGap['mobile_px'] ?? 0), (int) ($headerTopSpacing['mobile_px'] ?? 0));
    $css .= "}\n";
    $css .= "@media (min-width: 768px) {\n";
    $css .= ".logo-stack.brand-layout-page--{$pageClass} {\n";
    $css .= $buildVars((int) ($layout['tablet']['overlap_px'] ?? 0), (int) ($logoTop['tablet_px'] ?? 0), (int) ($headerLogoGap['tablet_px'] ?? 0), (int) ($headerTopSpacing['tablet_px'] ?? 0));
    $css .= "}\n";
    $css .= "}\n";
    $css .= "@media (min-width: 1024px) {\n";
    $css .= ".logo-stack.brand-layout-page--{$pageClass} {\n";
    $css .= $buildVars((int) ($layout['desktop']['overlap_px'] ?? 0), (int) ($logoTop['desktop_px'] ?? 0), (int) ($headerLogoGap['desktop_px'] ?? 0), (int) ($headerTopSpacing['desktop_px'] ?? 0));
    $css .= "}\n";
    $css .= "}\n";
    $css .= ".logo-stack.brand-layout-page--{$pageClass} .card-logo-wrapper {\n";
    $css .= "  position: relative;\n";
    $css .= "  z-index: 3;\n";
    $css .= "  margin-top: calc(var(--brand-logo-top-spacing, 0px) + var(--brand-header-top-spacing, 0px));\n";
    $css .= "  margin-bottom: 0;\n";
    $css .= "}\n";
    $css .= ".logo-stack.brand-layout-page--{$pageClass} .card-logo-wrapper.brand-visual-stack {\n";
    $css .= "  gap: var(--brand-header-logo-gap, 0px);\n";
    $css .= "}\n";
    $css .= ".logo-stack.brand-layout-page--{$pageClass} .logo-attached-card {\n";
    $css .= "  position: relative;\n";
    $css .= "  z-index: 2;\n";
    $css .= "  margin-top: var(--brand-overlap, 0px) !important;\n";
    $css .= "  padding-top: max(0px, calc(-1 * var(--brand-overlap, 0px)));\n";
    $css .= "}\n";
    $css .= "</style>\n";

    return $css;
}
}

if (!function_exists('brand_layout_preview_pages_config_key')) {
function brand_layout_preview_pages_config_key(string $pageKey): string {
    return public_container_content_page_key($pageKey);
}
}

if (!function_exists('brand_layout_preview_shell_meta')) {
function brand_layout_preview_shell_meta(string $pageKey): array {
    $defaults = [
        'body_class' => '',
        'card_classes' => CONFIG_CLASS_CARD_AUTO,
        'use_card_body' => false,
        'card_body_classes' => '',
    ];

    if (str_starts_with($pageKey, 'thankyou_')) {
        return [
            'body_class' => 'thank-you-page',
            'card_classes' => CONFIG_CLASS_CARD_AUTO,
            'use_card_body' => true,
            'card_body_classes' => 'card-body text-center',
        ];
    }

    return match ($pageKey) {
        'waiting_page' => [
            'body_class' => 'waiting-page',
            'card_classes' => CONFIG_CLASS_CARD_AUTO,
            'use_card_body' => true,
            'card_body_classes' => CONFIG_CLASS_CARD_BODY_CENTER,
        ],
        'waiting_page_ended' => [
            'body_class' => 'waiting-ended-page',
            'card_classes' => CONFIG_CLASS_CARD_AUTO,
            'use_card_body' => true,
            'card_body_classes' => CONFIG_CLASS_CARD_BODY_CENTER,
        ],
        'participation_page' => [
            'body_class' => 'participation-page',
            'card_classes' => CONFIG_CLASS_CARD_AUTO,
            'use_card_body' => true,
            'card_body_classes' => CONFIG_CLASS_CARD_BODY_CENTER,
        ],
        'quiz_choice_page' => [
            'body_class' => '',
            'card_classes' => CONFIG_CLASS_CARD_AUTO,
            'use_card_body' => true,
            'card_body_classes' => CONFIG_CLASS_CARD_BODY_CENTER,
        ],
        'quiz_page' => [
            'body_class' => '',
            'card_classes' => CONFIG_CLASS_CARD_AUTO,
            'use_card_body' => true,
            'card_body_classes' => 'card-body py-4',
        ],
        'help' => [
            'body_class' => 'help-page',
            'card_classes' => 'card p-4 p-md-5 mx-auto mb-5 w-100',
            'use_card_body' => false,
            'card_body_classes' => '',
        ],
        'registration' => [
            'body_class' => '',
            'card_classes' => 'card p-4 p-md-5 mx-auto w-100',
            'use_card_body' => false,
            'card_body_classes' => '',
        ],
        default => $defaults,
    };
}
}

if (!function_exists('brand_layout_preview_registration_form_mock')) {
function brand_layout_preview_registration_form_mock(): string {
    ob_start();
    ?>
    <div class="mx-auto col-12 col-lg-10">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Nome <span class="text-danger">*</span></label>
                <input type="text" class="form-control" disabled value="">
            </div>
            <div class="col-md-6">
                <label class="form-label">Cognome <span class="text-danger">*</span></label>
                <input type="text" class="form-control" disabled value="">
            </div>
            <div class="col-md-6">
                <label class="form-label">Email <span class="text-danger">*</span></label>
                <input type="email" class="form-control" disabled value="">
            </div>
            <div class="col-md-6">
                <label class="form-label">Cellulare <span class="text-danger">*</span></label>
                <input type="tel" class="form-control" disabled value="">
            </div>
        </div>
        <div class="mx-auto px-2 mt-4 col-12 col-md-6 col-lg-5">
            <button type="button" class="btn btn-success btn-lg fw-bold w-100" disabled>Partecipa</button>
        </div>
    </div>
    <?php
    return trim((string) ob_get_clean());
}
}

if (!function_exists('brand_layout_preview_help_faq_mock')) {
function brand_layout_preview_help_faq_mock(array $contest_config, int $limit = 2): string {
    $faq_items = is_array($contest_config['faq'] ?? null) ? $contest_config['faq'] : [];
    if ($faq_items === []) {
        return '<p class="text-muted text-center mb-0">Nessuna domanda FAQ configurata.</p>';
    }

    $replacements = [
        CONFIG_PH_MIN_SPEND => number_format((float) ($contest_config['min_spend'] ?? 0), 2, ',', '.'),
        CONFIG_PH_START_DATE => htmlspecialchars($contest_config['start_date_formatted'] ?? ($contest_config['start_date'] ?? ''), ENT_QUOTES),
        CONFIG_PH_END_DATE => htmlspecialchars($contest_config['end_date_formatted'] ?? ($contest_config['end_date'] ?? ''), ENT_QUOTES),
        CONFIG_PH_CONTEST_NAME => htmlspecialchars($contest_config['name'] ?? 'Concorso', ENT_QUOTES),
    ];

    ob_start();
    ?>
    <div class="concorso-annuncio help-faq-section">
        <div class="accordion help-faq-accordion" id="faqAccordion">
            <h1 class="fw-bold text-center mb-4">Domande Frequenti</h1>
            <?php
            $idx = 1;
            foreach (array_slice($faq_items, 0, max(1, $limit)) as $item) {
                if (!is_array($item)) {
                    continue;
                }
                $question = htmlspecialchars((string) ($item['q'] ?? "Domanda {$idx}"), ENT_QUOTES);
                $answer_raw = str_replace(array_keys($replacements), array_values($replacements), (string) ($item['a'] ?? ''));
                $answer_safe = nl2br(htmlspecialchars($answer_raw, ENT_QUOTES));
                $collapse_id = 'brandPreviewFaq' . $idx;
                ?>
                <div class="accordion-item">
                    <h2 class="accordion-header" id="brandPreviewFaqHeading<?php echo $idx; ?>">
                        <button class="accordion-button collapsed" type="button" disabled>
                            <?php echo $question; ?>
                        </button>
                    </h2>
                    <div id="<?php echo $collapse_id; ?>" class="accordion-collapse collapse">
                        <div class="accordion-body"><?php echo $answer_safe; ?></div>
                    </div>
                </div>
                <?php
                $idx++;
            }
            ?>
        </div>
    </div>
    <?php
    return trim((string) ob_get_clean());
}
}

if (!function_exists('brand_layout_preview_quiz_question_mock')) {
function brand_layout_preview_quiz_question_mock(): string {
    return '<div class="border rounded p-3 mb-3 text-start">'
        . '<p class="fw-semibold mb-3">Domanda 1.</p>'
        . '<div class="form-check mb-2"><input class="form-check-input" type="radio" disabled><label class="form-check-label">Risposta A</label></div>'
        . '<div class="form-check mb-2"><input class="form-check-input" type="radio" disabled><label class="form-check-label">Risposta B</label></div>'
        . '<div class="form-check mb-0"><input class="form-check-input" type="radio" disabled><label class="form-check-label">Risposta C</label></div>'
        . '</div>'
        . '<div class="text-center"><button type="button" class="btn btn-primary btn-lg" disabled>Invia risposte</button></div>';
}
}

if (!function_exists('brand_layout_preview_non_empty_or_placeholder')) {
function brand_layout_preview_non_empty_or_placeholder(string $html, callable $placeholder): string {
    $html = trim($html);
    return $html !== '' ? $html : $placeholder();
}
}

if (!function_exists('brand_layout_preview_registration_content')) {
function brand_layout_preview_registration_content(array $page_config, array $contest_config, callable $placeholder): string {
    $header = render_waiting_page_content($page_config, $contest_config);
    $form = brand_layout_preview_registration_form_mock();
    return brand_layout_preview_non_empty_or_placeholder($header . $form, $placeholder);
}
}

if (!function_exists('brand_layout_preview_help_content')) {
function brand_layout_preview_help_content(array $page_config, array $contest_config, callable $placeholder): string {
    $html = brand_layout_preview_help_faq_mock($contest_config);
    $custom = render_config_page_container_content($page_config, $contest_config, ['content_class' => 'help-page-custom-content']);
    return brand_layout_preview_non_empty_or_placeholder($html . $custom, $placeholder);
}
}

if (!function_exists('brand_layout_preview_waiting_content')) {
function brand_layout_preview_waiting_content(array $page_config, array $contest_config, callable $placeholder): string {
    return brand_layout_preview_non_empty_or_placeholder(
        render_waiting_page_content($page_config, $contest_config),
        $placeholder
    );
}
}

if (!function_exists('brand_layout_preview_container_content')) {
function brand_layout_preview_container_content(array $page_config, array $contest_config, string $contentClass, callable $placeholder): string {
    return brand_layout_preview_non_empty_or_placeholder(
        render_config_page_container_content($page_config, $contest_config, ['content_class' => $contentClass]),
        $placeholder
    );
}
}

if (!function_exists('brand_layout_preview_quiz_content')) {
function brand_layout_preview_quiz_content(array $page_config, array $contest_config, callable $placeholder): string {
    $header = function_exists('render_quiz_page_header_containers')
        ? trim(render_quiz_page_header_containers($contest_config, $page_config))
        : '';
    return brand_layout_preview_non_empty_or_placeholder($header . brand_layout_preview_quiz_question_mock(), $placeholder);
}
}

if (!function_exists('brand_layout_preview_thankyou_content')) {
function brand_layout_preview_thankyou_content(string $pageKey, array $page_config, array $contest_config, callable $placeholder): string {
    $prize = $pageKey === 'thankyou_instantwin_win_page' ? 25.0 : 0.0;
    $prizeType = $pageKey === 'thankyou_instantwin_win_page' ? 'Prepagata' : '';
    return brand_layout_preview_non_empty_or_placeholder(
        render_thankyou_page_content($page_config, $contest_config, $prize, $prizeType),
        $placeholder
    );
}
}

if (!function_exists('render_brand_layout_preview_page_content')) {
function render_brand_layout_preview_page_content(
    string $pageKey,
    array $content_config,
    array $contest_config,
    array $content_defaults = []
): string {
    $pages_config = is_array($content_config['pages'] ?? null)
        ? $content_config['pages']
        : (is_array($content_defaults['pages'] ?? null) ? $content_defaults['pages'] : []);
    $configKey = brand_layout_preview_pages_config_key($pageKey);
    $page_config = is_array($pages_config[$configKey] ?? null) ? $pages_config[$configKey] : [];
    $page_labels = brand_layout_page_definitions();
    $page_label = htmlspecialchars($page_labels[$pageKey] ?? $pageKey, ENT_QUOTES);

    $placeholder = static function () use ($page_label): string {
        return '<p class="text-muted text-center mb-0">Nessun contenuto configurato per ' . $page_label . '.</p>';
    };

    return match ($pageKey) {
        'registration' => brand_layout_preview_registration_content($page_config, $contest_config, $placeholder),
        'help' => brand_layout_preview_help_content($page_config, $contest_config, $placeholder),
        'waiting_page', 'waiting_page_ended' => brand_layout_preview_waiting_content($page_config, $contest_config, $placeholder),
        'participation_page' => brand_layout_preview_container_content($page_config, $contest_config, 'participation-page-content', $placeholder),
        'quiz_choice_page' => brand_layout_preview_container_content($page_config, $contest_config, 'quiz-choice-page-content', $placeholder),
        'quiz_page' => brand_layout_preview_quiz_content($page_config, $contest_config, $placeholder),
        default => str_starts_with($pageKey, 'thankyou_')
            ? brand_layout_preview_thankyou_content($pageKey, $page_config, $contest_config, $placeholder)
            : brand_layout_preview_non_empty_or_placeholder(
                render_config_page_container_content($page_config, $contest_config),
                $placeholder
            ),
    };
}
}

if (!function_exists('contest_buttons_use_custom_colors')) {
    function contest_buttons_use_custom_colors(array $contest_config): bool
    {
        $style = $contest_config['style_customization'] ?? [];
        if (!is_array($style)) {
            return false;
        }

        return !empty($style['buttons_colors']);
    }
}

if (!function_exists('contest_footer_use_custom_text_colors')) {
    function contest_footer_use_custom_text_colors(array $contest_config): bool
    {
        $style = $contest_config['style_customization'] ?? [];
        if (!is_array($style)) {
            return false;
        }

        return !empty($style['footer_text_colors']);
    }
}

if (!function_exists('contest_help_page_use_custom_colors')) {
    function contest_help_page_use_custom_colors(array $contest_config): bool
    {
        $style = $contest_config['style_customization'] ?? [];
        if (!is_array($style)) {
            return false;
        }

        return !empty($style['help_page_colors']);
    }
}

if (!function_exists('contest_form_fields_use_custom_colors')) {
    function contest_form_fields_use_custom_colors(array $contest_config): bool
    {
        $style = $contest_config['style_customization'] ?? [];
        if (!is_array($style)) {
            return false;
        }

        return !empty($style['form_field_colors']);
    }
}

if (!function_exists('contest_form_field_stored_hex_is_custom')) {
    function contest_form_field_stored_hex_is_custom(array $contest_config, string $key): bool
    {
        $stored = strtolower(trim((string) ($contest_config[$key] ?? '')));
        return $stored !== '' && preg_match(CONFIG_REGEX_HEX6_LOWER, $stored);
    }
}

if (!function_exists('contest_form_field_stored_base_colors_are_custom')) {
    function contest_form_field_stored_base_colors_are_custom(array $contest_config): bool
    {
        $storedText = strtolower(trim((string) ($contest_config['form_field_text_color'] ?? CONFIG_COLOR_BLACK)));
        if (!contest_color_is_default_like($storedText, [CONFIG_COLOR_BLACK, CONFIG_COLOR_TEXT])) {
            return true;
        }

        $storedBorder = strtolower(trim((string) ($contest_config['form_field_border_color'] ?? CONFIG_COLOR_BORDER)));
        if (!contest_color_is_default_like($storedBorder, [CONFIG_COLOR_BORDER, '#ced4da'])) {
            return true;
        }

        $storedBg = strtolower(trim((string) ($contest_config['form_field_bg_color'] ?? CONFIG_COLOR_WHITE)));
        return !contest_color_is_default_like($storedBg, [CONFIG_COLOR_WHITE]);
    }
}

if (!function_exists('contest_form_field_stored_optional_hex_colors_are_custom')) {
    function contest_form_field_stored_optional_hex_colors_are_custom(array $contest_config): bool
    {
        $hexKeys = [
            'form_field_focus_color',
            'form_field_checkbox_color',
            'form_field_placeholder_color',
            'form_field_consent_text_color',
            'form_field_consent_link_color',
            'form_field_consent_link_hover_color',
            'form_field_select2_highlight_text',
        ];
        foreach ($hexKeys as $key) {
            if (contest_form_field_stored_hex_is_custom($contest_config, $key)) {
                return true;
            }
        }

        if (contest_stored_select2_highlight_bg($contest_config) !== '') {
            return true;
        }

        foreach (['form_field_calendar_popup_bg', 'form_field_calendar_selected_color', 'form_field_calendar_hover_bg', 'form_field_calendar_hover_text'] as $calendarKey) {
            if (contest_form_field_stored_hex_is_custom($contest_config, $calendarKey)) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('contest_form_field_stored_opacities_are_custom')) {
    function contest_form_field_stored_opacities_are_custom(array $contest_config): bool
    {
        $bgOpacity = max(0, min(100, (int) ($contest_config['form_field_bg_opacity'] ?? 100)));
        $focusOpacity = max(0, min(100, (int) ($contest_config['form_field_focus_opacity'] ?? 100)));
        $hoverOpacity = max(0, min(100, (int) ($contest_config['form_field_consent_link_hover_opacity'] ?? 100)));

        return $bgOpacity < 100 || $focusOpacity < 100 || $hoverOpacity < 100;
    }
}

if (!function_exists('contest_form_fields_stored_colors_are_custom')) {
    /**
     * Rileva configurazioni legacy con colori campi form non allineati ai default.
     */
    function contest_form_fields_stored_colors_are_custom(array $contest_config): bool
    {
        if (contest_form_field_stored_base_colors_are_custom($contest_config)) {
            return true;
        }
        if (contest_form_field_stored_optional_hex_colors_are_custom($contest_config)) {
            return true;
        }

        return contest_form_field_stored_opacities_are_custom($contest_config);
    }
}

if (!function_exists('contest_help_page_stored_colors_are_custom')) {
    function contest_help_page_stored_colors_are_custom(array $contest_config): bool
    {
        $custom = is_array($contest_config['help_page_style'] ?? null) ? $contest_config['help_page_style'] : [];
        foreach (['accordion', 'form'] as $section) {
            $block = is_array($custom[$section] ?? null) ? $custom[$section] : [];
            foreach ($block as $value) {
                if (trim((string) $value) !== '') {
                    return true;
                }
            }
        }

        return false;
    }
}

if (!function_exists('contest_buttons_stored_colors_are_custom')) {
    /**
     * Rileva configurazioni legacy con colori Bottoni non allineati alla palette.
     */
    function contest_buttons_stored_colors_are_custom(array $contest_config, array $defaults = []): bool
    {
        $btn = is_array($contest_config['buttons'] ?? null) ? $contest_config['buttons'] : [];
        $primary = is_array($btn['primary'] ?? null) ? $btn['primary'] : [];
        $secondary = is_array($btn['secondary'] ?? null) ? $btn['secondary'] : [];
        $palette = is_array($contest_config['colors'] ?? null) ? $contest_config['colors'] : [];
        $palettePrimary = strtolower(trim((string) ($palette['primary'] ?? CONFIG_COLOR_PRIMARY)));
        $paletteSecondary = strtolower(trim((string) ($palette['secondary'] ?? CONFIG_COLOR_SECONDARY)));
        $paletteTertiary = strtolower(trim((string) ($palette['tertiary'] ?? CONFIG_COLOR_PRIMARY_DARK)));

        $toNorm = static function ($value): string {
            return strtolower(trim((string) $value));
        };

        $primaryMode = strtolower((string) ($primary['mode'] ?? 'color'));
        if ($primaryMode === 'image' && (trim((string) ($primary['image'] ?? '')) !== '' || trim((string) ($primary['hover_image'] ?? '')) !== '')) {
            return true;
        }
        if ($primaryMode === 'gradient' || !empty($primary['use_gradient'])) {
            return true;
        }

        $bg = $toNorm($primary['bg'] ?? CONFIG_COLOR_PRIMARY);
        $border = $toNorm($primary['border'] ?? $bg);
        $hoverBg = $toNorm($primary['hover_bg'] ?? CONFIG_COLOR_PRIMARY_HOVER);
        $hoverBorder = $toNorm($primary['hover_border'] ?? $hoverBg);
        $isPrimaryDefaultLike =
            $primaryMode === 'color' &&
            trim((string) ($primary['image'] ?? '')) === '' &&
            trim((string) ($primary['hover_image'] ?? '')) === '' &&
            in_array($bg, [CONFIG_COLOR_PRIMARY, CONFIG_COLOR_SUCCESS, $palettePrimary], true) &&
            in_array($toNorm($primary['text_color'] ?? CONFIG_COLOR_WHITE), [CONFIG_COLOR_WHITE], true) &&
            in_array($border, [CONFIG_COLOR_PRIMARY, CONFIG_COLOR_SUCCESS, $palettePrimary], true) &&
            in_array($hoverBg, [CONFIG_COLOR_PRIMARY_HOVER, CONFIG_COLOR_SUCCESS_DARK], true) &&
            in_array($hoverBorder, [CONFIG_COLOR_PRIMARY_HOVER, CONFIG_COLOR_SUCCESS_DARK], true);

        if (!$isPrimaryDefaultLike) {
            return true;
        }

        $secondaryMode = strtolower((string) ($secondary['mode'] ?? 'color'));
        if ($secondaryMode === 'image' && (trim((string) ($secondary['image'] ?? '')) !== '' || trim((string) ($secondary['hover_image'] ?? '')) !== '')) {
            return true;
        }
        if ($secondaryMode === 'gradient' || !empty($secondary['use_gradient'])) {
            return true;
        }

        $secBg = $toNorm($secondary['bg'] ?? CONFIG_COLOR_SECONDARY);
        $secBorder = $toNorm($secondary['border'] ?? $secBg);
        $secHoverBg = $toNorm($secondary['hover_bg'] ?? CONFIG_COLOR_SECONDARY_DARK);
        $secHoverBorder = $toNorm($secondary['hover_border'] ?? $secHoverBg);
        $isSecondaryDefaultLike =
            $secondaryMode === 'color' &&
            trim((string) ($secondary['image'] ?? '')) === '' &&
            trim((string) ($secondary['hover_image'] ?? '')) === '' &&
            (
                (
                    in_array($secBg, [CONFIG_COLOR_SECONDARY, $paletteSecondary], true) &&
                    in_array($secBorder, [CONFIG_COLOR_SECONDARY, $paletteSecondary], true) &&
                    in_array($secHoverBg, [CONFIG_COLOR_SECONDARY_DARK, $paletteTertiary], true) &&
                    in_array($secHoverBorder, [CONFIG_COLOR_SECONDARY_DARK, $paletteTertiary], true)
                )
                || (
                    in_array($secBg, [CONFIG_COLOR_SECONDARY], true) &&
                    in_array($secBorder, [CONFIG_COLOR_SECONDARY], true) &&
                    in_array($secHoverBg, [CONFIG_COLOR_SECONDARY_DARK], true) &&
                    in_array($secHoverBorder, [CONFIG_COLOR_SECONDARY_DARK], true)
                )
            );

        return !$isSecondaryDefaultLike;
    }
}

if (!function_exists('contest_primary_hover_color')) {
    function contest_primary_hover_color(string $primaryHex): string
    {
        $primaryHex = contest_normalize_hex_color($primaryHex, CONFIG_COLOR_PRIMARY) ?: CONFIG_COLOR_PRIMARY;

        return 'color-mix(in srgb, ' . $primaryHex . ' 85%, #000000)';
    }
}

if (!function_exists('resolve_button_colors')) {
    /**
     * Risolve i colori dei bottoni: palette (default) oppure JSON Bottoni se toggle attivo.
     *
     * @return array{primary: array<string, mixed>, secondary: array<string, mixed>}
     */
    function resolve_button_colors(?array $contest_config = null): array
    {
        if ($contest_config === null) {
            global $contest_config;
            $contest_config = is_array($contest_config ?? null) ? $contest_config : [];
        }

        $btn = is_array($contest_config['buttons'] ?? null) ? $contest_config['buttons'] : [];
        $primary = is_array($btn['primary'] ?? null) ? $btn['primary'] : [];
        $secondary = is_array($btn['secondary'] ?? null) ? $btn['secondary'] : [];
        $palette = is_array($contest_config['colors'] ?? null) ? $contest_config['colors'] : [];

        $palettePrimary = contest_normalize_hex_color((string) ($palette['primary'] ?? ''), CONFIG_COLOR_PRIMARY) ?: CONFIG_COLOR_PRIMARY;
        $paletteSecondary = contest_normalize_hex_color((string) ($palette['secondary'] ?? ''), CONFIG_COLOR_SECONDARY) ?: CONFIG_COLOR_SECONDARY;
        $paletteTertiary = contest_normalize_hex_color((string) ($palette['tertiary'] ?? ''), CONFIG_COLOR_PRIMARY_DARK) ?: CONFIG_COLOR_PRIMARY_DARK;

        $readHex = static function (array $source, string $key, string $fallback): string {
            $raw = strtolower(trim((string) ($source[$key] ?? $fallback)));
            if ($raw === 'transparent') {
                return 'transparent';
            }

            return contest_normalize_hex_color($raw, $fallback) ?: $fallback;
        };

        $useCustom = contest_buttons_use_custom_colors($contest_config);

        if ($useCustom) {
            $primaryBg = $readHex($primary, 'bg', $palettePrimary);
            $primaryBorder = $readHex($primary, 'border', $primaryBg);
            $primaryHoverBg = $readHex($primary, 'hover_bg', contest_primary_hover_color($primaryBg));
            $primaryHoverBorder = $readHex($primary, 'hover_border', $primaryHoverBg);
            $secondaryBg = $readHex($secondary, 'bg', $paletteSecondary);
            $secondaryBorder = $readHex($secondary, 'border', $secondaryBg);
            $secondaryHoverBg = $readHex($secondary, 'hover_bg', $paletteTertiary);
            $secondaryHoverBorder = $readHex($secondary, 'hover_border', $secondaryHoverBg);
        } else {
            $primaryBg = $palettePrimary;
            $primaryBorder = $palettePrimary;
            $primaryHoverBg = contest_primary_hover_color($palettePrimary);
            $primaryHoverBorder = $primaryHoverBg;
            $secondaryBg = $paletteSecondary;
            $secondaryBorder = $paletteSecondary;
            $secondaryHoverBg = $paletteTertiary;
            $secondaryHoverBorder = $paletteTertiary;
        }

        $primaryResolved = array_merge($primary, [
            'bg' => $primaryBg,
            'border' => $primaryBorder,
            'text_color' => $readHex($primary, 'text_color', CONFIG_COLOR_WHITE),
            'hover_bg' => $primaryHoverBg,
            'hover_border' => $primaryHoverBorder,
        ]);
        $secondaryResolved = array_merge($secondary, [
            'bg' => $secondaryBg,
            'border' => $secondaryBorder,
            'text_color' => $readHex($secondary, 'text_color', CONFIG_COLOR_WHITE),
            'hover_bg' => $secondaryHoverBg,
            'hover_border' => $secondaryHoverBorder,
        ]);

        return [
            'primary' => $primaryResolved,
            'secondary' => $secondaryResolved,
        ];
    }
}

if (!function_exists('config_button_resolve_style_vars')) {
/**
 * @return array<string, mixed>
 */
function config_button_resolve_style_vars(array $primary, array $secondary): array {
    $bg = (string) ($primary['bg'] ?? CONFIG_COLOR_PRIMARY);
    $primary_image = (string) ($primary['image'] ?? '');
    $primary_hover_image = (string) ($primary['hover_image'] ?? $primary_image);
    $primary_mode = strtolower((string) ($primary['mode'] ?? 'color'));
    $primary_image_size = strtolower((string) ($primary['image_size'] ?? 'cover'));
    $primary_image_size_custom = trim((string) ($primary['image_size_custom'] ?? ''));
    $primary_image_position = trim((string) (($primary['image_position_horizontal'] ?? 'center') . ' ' . ($primary['image_position_vertical'] ?? 'center')));

    $secondary_image = (string) ($secondary['image'] ?? '');
    $secondary_hover_image = (string) ($secondary['hover_image'] ?? $secondary_image);
    $secondary_mode = strtolower((string) ($secondary['mode'] ?? 'color'));
    $secondary_image_size = strtolower((string) ($secondary['image_size'] ?? 'cover'));
    $secondary_image_size_custom = trim((string) ($secondary['image_size_custom'] ?? ''));
    $secondary_image_position = trim((string) (($secondary['image_position_horizontal'] ?? 'center') . ' ' . ($secondary['image_position_vertical'] ?? 'center')));

    $h_bg = htmlspecialchars((string) ($primary['bg'] ?? CONFIG_COLOR_PRIMARY), ENT_QUOTES, 'UTF-8');
    $h_txt = htmlspecialchars((string) ($primary['text_color'] ?? CONFIG_COLOR_WHITE), ENT_QUOTES, 'UTF-8');
    $h_border = htmlspecialchars((string) ($primary['border'] ?? $bg), ENT_QUOTES, 'UTF-8');
    $h_hover_bg = htmlspecialchars((string) ($primary['hover_bg'] ?? contest_primary_hover_color($bg)), ENT_QUOTES, 'UTF-8');
    $h_hover_border = htmlspecialchars((string) ($primary['hover_border'] ?? ($primary['hover_bg'] ?? contest_primary_hover_color($bg))), ENT_QUOTES, 'UTF-8');
    if ($primary_mode === 'image' && trim($primary_image) !== '') {
        $h_bg = 'transparent';
        $h_border = 'transparent';
        $h_hover_bg = 'transparent';
        $h_hover_border = 'transparent';
    }

    $secondaryBg = (string) ($secondary['bg'] ?? CONFIG_COLOR_SECONDARY);
    $h_sbg = htmlspecialchars($secondaryBg, ENT_QUOTES, 'UTF-8');
    $h_stxt = htmlspecialchars((string) ($secondary['text_color'] ?? CONFIG_COLOR_WHITE), ENT_QUOTES, 'UTF-8');
    $h_sborder = htmlspecialchars((string) ($secondary['border'] ?? $secondaryBg), ENT_QUOTES, 'UTF-8');
    $h_shover_bg = htmlspecialchars((string) ($secondary['hover_bg'] ?? $secondaryBg), ENT_QUOTES, 'UTF-8');
    $h_shover_border = htmlspecialchars((string) ($secondary['hover_border'] ?? ($secondary['hover_bg'] ?? $secondaryBg)), ENT_QUOTES, 'UTF-8');
    if ($secondary_mode === 'image' && trim($secondary_image) !== '') {
        $h_sbg = 'transparent';
        $h_sborder = 'transparent';
        $h_shover_bg = 'transparent';
        $h_shover_border = 'transparent';
    }

    $primary_gradient = '';
    $primary_gradient_hover = '';
    $primary_use_gradient = ($primary_mode === 'color' && !empty($primary['use_gradient'])) || $primary_mode === 'gradient';
    if ($primary_use_gradient) {
        $primary_gradient = config_button_linear_gradient_css(
            (string) ($primary['gradient_direction'] ?? 'vertical'),
            (string) ($primary['gradient_from'] ?? $bg),
            (string) ($primary['gradient_to'] ?? ($primary['hover_bg'] ?? $bg)),
            (string) ($primary['gradient_style'] ?? 'center')
        );
        $primary_gradient_hover = config_button_linear_gradient_css(
            (string) ($primary['gradient_direction'] ?? 'vertical'),
            (string) ($primary['gradient_hover_from'] ?? ($primary['gradient_from'] ?? $bg)),
            (string) ($primary['gradient_hover_to'] ?? ($primary['gradient_to'] ?? $bg)),
            (string) ($primary['gradient_style'] ?? 'center')
        );
        $h_bg = htmlspecialchars((string) ($primary['gradient_from'] ?? $bg), ENT_QUOTES, 'UTF-8');
        $h_hover_bg = htmlspecialchars((string) ($primary['gradient_hover_from'] ?? ($primary['gradient_from'] ?? $bg)), ENT_QUOTES, 'UTF-8');
    }

    $secondary_gradient = '';
    $secondary_gradient_hover = '';
    $secondary_use_gradient = ($secondary_mode === 'color' && !empty($secondary['use_gradient'])) || $secondary_mode === 'gradient';
    if ($secondary_use_gradient) {
        $secondary_gradient = config_button_linear_gradient_css(
            (string) ($secondary['gradient_direction'] ?? 'vertical'),
            (string) ($secondary['gradient_from'] ?? $secondaryBg),
            (string) ($secondary['gradient_to'] ?? ($secondary['hover_bg'] ?? $secondaryBg)),
            (string) ($secondary['gradient_style'] ?? 'center')
        );
        $secondary_gradient_hover = config_button_linear_gradient_css(
            (string) ($secondary['gradient_direction'] ?? 'vertical'),
            (string) ($secondary['gradient_hover_from'] ?? ($secondary['gradient_from'] ?? $secondaryBg)),
            (string) ($secondary['gradient_hover_to'] ?? ($secondary['gradient_to'] ?? $secondaryBg)),
            (string) ($secondary['gradient_style'] ?? 'center')
        );
        $h_sbg = htmlspecialchars((string) ($secondary['gradient_from'] ?? $secondaryBg), ENT_QUOTES, 'UTF-8');
        $h_shover_bg = htmlspecialchars((string) ($secondary['gradient_hover_from'] ?? ($secondary['gradient_from'] ?? $secondaryBg)), ENT_QUOTES, 'UTF-8');
    }

    return [
        'h_bg' => $h_bg,
        'h_txt' => $h_txt,
        'h_border' => $h_border,
        'h_hover_bg' => $h_hover_bg,
        'h_hover_border' => $h_hover_border,
        'h_pimg' => $primary_mode === 'image' && trim($primary_image) !== '' ? CONFIG_CSS_URL_QUOTE . htmlspecialchars($primary_image, ENT_QUOTES, 'UTF-8') . "')" : '',
        'h_pimg_hover' => $primary_mode === 'image' && trim($primary_hover_image) !== '' ? CONFIG_CSS_URL_QUOTE . htmlspecialchars($primary_hover_image, ENT_QUOTES, 'UTF-8') . "')" : '',
        'h_pimg_size' => htmlspecialchars($primary_image_size === 'custom' && $primary_image_size_custom !== '' ? $primary_image_size_custom : $primary_image_size, ENT_QUOTES, 'UTF-8'),
        'h_pimg_position' => htmlspecialchars($primary_image_position !== '' ? $primary_image_position : 'center center', ENT_QUOTES, 'UTF-8'),
        'h_primary_gradient' => $primary_gradient,
        'h_primary_gradient_hover' => $primary_gradient_hover,
        'h_primary_radius' => (string) max(0, min(80, (int) ($primary['radius'] ?? 8))),
        'h_primary_font' => htmlspecialchars(trim((string) ($primary['font'] ?? '')), ENT_QUOTES, 'UTF-8'),
        'h_primary_font_size' => htmlspecialchars(contest_resolve_font_size((string) ($primary['font_size'] ?? '')), ENT_QUOTES, 'UTF-8'),
        'primary_hide_text' => !empty($primary['hide_text_on_image']),
        'h_sbg' => $h_sbg,
        'h_stxt' => $h_stxt,
        'h_sborder' => $h_sborder,
        'h_shover_bg' => $h_shover_bg,
        'h_shover_border' => $h_shover_border,
        'h_simg' => $secondary_mode === 'image' && trim($secondary_image) !== '' ? CONFIG_CSS_URL_QUOTE . htmlspecialchars($secondary_image, ENT_QUOTES, 'UTF-8') . "')" : '',
        'h_simg_hover' => $secondary_mode === 'image' && trim($secondary_hover_image) !== '' ? CONFIG_CSS_URL_QUOTE . htmlspecialchars($secondary_hover_image, ENT_QUOTES, 'UTF-8') . "')" : '',
        'h_simg_size' => htmlspecialchars($secondary_image_size === 'custom' && $secondary_image_size_custom !== '' ? $secondary_image_size_custom : $secondary_image_size, ENT_QUOTES, 'UTF-8'),
        'h_simg_position' => htmlspecialchars($secondary_image_position !== '' ? $secondary_image_position : 'center center', ENT_QUOTES, 'UTF-8'),
        'h_secondary_gradient' => $secondary_gradient,
        'h_secondary_gradient_hover' => $secondary_gradient_hover,
        'h_secondary_radius' => (string) max(0, min(80, (int) ($secondary['radius'] ?? 8))),
        'h_secondary_font' => htmlspecialchars(trim((string) ($secondary['font'] ?? '')), ENT_QUOTES, 'UTF-8'),
        'h_secondary_font_size' => htmlspecialchars(contest_resolve_font_size((string) ($secondary['font_size'] ?? '')), ENT_QUOTES, 'UTF-8'),
        'secondary_hide_text' => !empty($secondary['hide_text_on_image']),
    ];
}
}

if (!function_exists('config_button_linear_gradient_css')) {
function config_button_linear_gradient_css(string $direction, string $from, string $to, string $style = 'linear'): string {
    $from = trim($from);
    $to = trim($to);
    if ($from === '' || $to === '') {
        return '';
    }
    $angle = strtolower(trim($direction)) === 'horizontal' ? '90deg' : '180deg';
    $hFrom = htmlspecialchars($from, ENT_QUOTES, 'UTF-8');
    $hTo = htmlspecialchars($to, ENT_QUOTES, 'UTF-8');
    $style = strtolower(trim($style)) === 'center' ? 'center' : 'linear';
    if ($style === 'center') {
        // Bordo scuro → highlight al centro → bordo scuro (come button_primary.png)
        return "linear-gradient({$angle}, {$hFrom} 0%, {$hTo} 50%, {$hFrom} 100%)";
    }
    return "linear-gradient({$angle}, {$hFrom} 0%, {$hTo} 100%)";
}
}

if (!function_exists('config_button_image_background_css')) {
function config_button_image_background_css(string $h_img, string $h_size, string $h_position): string {
    if ($h_img === '') {
        return "  background-image: none !important;\n";
    }
    return "  background-image: {$h_img} !important;\n"
        . "  background-size: {$h_size} !important;\n"
        . "  background-position: {$h_position} !important;\n"
        . "  background-repeat: no-repeat !important;\n";
}
}

if (!function_exists('config_button_fill_background_css')) {
/**
 * Prefer image, then gradient, else clear background-image for solid color mode.
 */
function config_button_fill_background_css(string $h_img, string $h_size, string $h_position, string $h_gradient = ''): string {
    if ($h_img !== '') {
        return config_button_image_background_css($h_img, $h_size, $h_position);
    }
    if ($h_gradient !== '') {
        return "  background-image: {$h_gradient} !important;\n"
            . "  background-size: auto !important;\n"
            . "  background-position: center center !important;\n"
            . "  background-repeat: no-repeat !important;\n";
    }
    return "  background-image: none !important;\n";
}
}

if (!function_exists('config_button_hide_text_css')) {
function config_button_hide_text_css(bool $hide_text): string {
    return $hide_text
        ? "  color: transparent !important;\n  text-shadow: none !important;\n"
        : '';
}
}

if (!function_exists('config_button_typography_css')) {
/**
 * Sempre emettere font-family (anche Predefinito) per bloccare l'eredità Summernote/Gotham.
 */
function config_button_typography_css(string $h_font, string $h_font_size): string {
    $family = $h_font !== ''
        ? "'{$h_font}', sans-serif"
        : 'Arial, Helvetica, sans-serif';
    $css = "  font-family: {$family} !important;\n";
    if ($h_font_size !== '') {
        $css .= "  font-size: {$h_font_size} !important;\n";
    }
    return $css;
}
}

if (!function_exists('config_button_typography_descendants_css')) {
/**
 * I span Summernote dentro il bottone non devono sovrascrivere la tipografia Bottoni.
 * Attenzione: aggiungere " *" a ogni selettore (non solo all'ultimo della lista CSV).
 */
function config_button_typography_descendants_css(string $selectors): string {
    $parts = array_filter(array_map('trim', explode(',', $selectors)), static function ($part) {
        return $part !== '';
    });
    if ($parts === []) {
        return '';
    }
    $descendantSelectors = implode(', ', array_map(static function (string $sel): string {
        return $sel . ' *';
    }, $parts));

    return $descendantSelectors . " {\n"
        . "  font-family: inherit !important;\n"
        . "  font-size: inherit !important;\n"
        . "}\n";
}
}

if (!function_exists('config_button_primary_default_css')) {
function config_button_primary_default_css(array $v): string {
    $selectors = "#submitBtn, #submitBtn.btn, .btn-primary.custom-submit, .btn-primary, .btn.btn-primary, .btn-success, .btn.btn-success, .btn-invia, .btn-partecipa-nuovo, .btn-thank-primary, .btn-regolamento, .btn.btn-regolamento, .btn-regolamento-thankyou, .btn.btn-regolamento-thankyou, a.contest-block-cta.contest-block-cta--primary, a.contest-block-cta.contest-block-cta--primary:link, a.contest-block-cta.contest-block-cta--primary:visited";
    $css = $selectors . " {\n";
    $css .= "  background-color: {$v['h_bg']} !important;\n";
    $css .= "  border-color: {$v['h_border']} !important;\n";
    $css .= "  color: {$v['h_txt']} !important;\n";
    $css .= "  border-radius: {$v['h_primary_radius']}px !important;\n";
    $css .= config_button_typography_css((string) ($v['h_primary_font'] ?? ''), (string) ($v['h_primary_font_size'] ?? ''));
    $css .= config_button_fill_background_css($v['h_pimg'], $v['h_pimg_size'], $v['h_pimg_position'], (string) ($v['h_primary_gradient'] ?? ''));
    if ($v['h_pimg'] !== '' && $v['primary_hide_text']) {
        $css .= config_button_hide_text_css(true);
    }
    $css .= "}\n";
    $css .= config_button_typography_descendants_css($selectors);
    return $css;
}
}

if (!function_exists('config_button_primary_interactive_css')) {
function config_button_primary_interactive_css(array $v): string {
    $css = "#submitBtn:hover, #submitBtn:focus, #submitBtn:active, #submitBtn:focus-visible, .btn-primary.custom-submit:hover, .btn-primary.custom-submit:focus, .btn-primary.custom-submit:active, .btn-primary.custom-submit:focus-visible, .btn-primary:hover, .btn-primary:focus, .btn-primary:active, .btn-primary:focus-visible, .btn.btn-primary:hover, .btn.btn-primary:focus, .btn.btn-primary:active, .btn.btn-primary:focus-visible, .btn-success:hover, .btn-success:focus, .btn-success:active, .btn-success:focus-visible, .btn.btn-success:hover, .btn.btn-success:focus, .btn.btn-success:active, .btn.btn-success:focus-visible, .btn-invia:hover, .btn-invia:focus, .btn-invia:active, .btn-invia:focus-visible, .btn-partecipa-nuovo:hover, .btn-partecipa-nuovo:focus, .btn-partecipa-nuovo:active, .btn-partecipa-nuovo:focus-visible, .btn-thank-primary:hover, .btn-thank-primary:focus, .btn-thank-primary:active, .btn-thank-primary:focus-visible, .btn-regolamento:hover, .btn-regolamento:focus, .btn-regolamento:active, .btn-regolamento:focus-visible, .btn.btn-regolamento:hover, .btn.btn-regolamento:focus, .btn.btn-regolamento:active, .btn.btn-regolamento:focus-visible, .btn-regolamento-thankyou:hover, .btn-regolamento-thankyou:focus, .btn-regolamento-thankyou:active, .btn-regolamento-thankyou:focus-visible, .btn.btn-regolamento-thankyou:hover, .btn.btn-regolamento-thankyou:focus, .btn.btn-regolamento-thankyou:active, .btn.btn-regolamento-thankyou:focus-visible, a.contest-block-cta.contest-block-cta--primary:hover, a.contest-block-cta.contest-block-cta--primary:focus, a.contest-block-cta.contest-block-cta--primary:active, a.contest-block-cta.contest-block-cta--primary:focus-visible, .btn-check:checked + .btn-primary, .btn-check:active + .btn-primary, .show > .btn-primary.dropdown-toggle, .btn-check:checked + .btn-success, .btn-check:active + .btn-success, .show > .btn-success.dropdown-toggle {\n";
    $css .= "  background-color: {$v['h_hover_bg']} !important;\n";
    $css .= "  border-color: {$v['h_hover_border']} !important;\n";
    $css .= "  color: {$v['h_txt']} !important;\n";
    $hoverImg = $v['h_pimg_hover'] !== '' ? $v['h_pimg_hover'] : $v['h_pimg'];
    $hoverGradient = (string) ($v['h_primary_gradient_hover'] ?? '');
    if ($hoverGradient === '') {
        $hoverGradient = (string) ($v['h_primary_gradient'] ?? '');
    }
    $css .= config_button_fill_background_css($hoverImg, $v['h_pimg_size'], $v['h_pimg_position'], $hoverGradient);
    if ($v['h_pimg'] !== '' && $v['primary_hide_text']) {
        $css .= config_button_hide_text_css(true);
    }
    $css .= "}\n";
    return $css;
}
}

if (!function_exists('config_button_primary_disabled_css')) {
function config_button_primary_disabled_css(array $v): string {
    $css = ".btn-primary:disabled, .btn-primary.disabled, .btn-success:disabled, .btn-success.disabled, .btn-regolamento:disabled, .btn-regolamento.disabled, .btn-regolamento-thankyou:disabled, .btn-regolamento-thankyou.disabled, #submitBtn:disabled {\n";
    $css .= "  background-color: {$v['h_bg']} !important;\n";
    $css .= "  border-color: {$v['h_border']} !important;\n";
    $css .= "  color: {$v['h_txt']} !important;\n";
    $css .= "  opacity: 0.65 !important;\n";
    $css .= "  border-radius: {$v['h_primary_radius']}px !important;\n";
    $css .= config_button_fill_background_css($v['h_pimg'], $v['h_pimg_size'], $v['h_pimg_position'], (string) ($v['h_primary_gradient'] ?? ''));
    if ($v['h_pimg'] !== '' && $v['primary_hide_text']) {
        $css .= config_button_hide_text_css(true);
    }
    $css .= "}\n";
    return $css;
}
}

if (!function_exists('config_button_outline_css')) {
function config_button_outline_css(array $v): string {
    $css = ".btn-outline-primary, .btn-outline-success {\n";
    $css .= "  color: {$v['h_bg']} !important;\n";
    $css .= "  border-color: {$v['h_border']} !important;\n";
    $css .= "  background-color: transparent !important;\n";
    $css .= "  border-radius: {$v['h_primary_radius']}px !important;\n";
    $css .= "}\n";
    $css .= ".btn-outline-primary:hover, .btn-outline-primary:focus, .btn-outline-primary:active, .btn-outline-primary:focus-visible, .btn-outline-success:hover, .btn-outline-success:focus, .btn-outline-success:active, .btn-outline-success:focus-visible, .btn-check:checked + .btn-outline-primary, .btn-check:checked + .btn-outline-success, .btn-check:active + .btn-outline-primary, .btn-check:active + .btn-outline-success {\n";
    $css .= "  color: {$v['h_txt']} !important;\n";
    $css .= "  background-color: {$v['h_hover_bg']} !important;\n";
    $css .= "  border-color: {$v['h_hover_border']} !important;\n";
    $css .= "}\n";
    return $css;
}
}

if (!function_exists('config_button_secondary_default_css')) {
function config_button_secondary_default_css(array $v): string {
    $selectors = ".btn-secondary, .btn.btn-secondary, .btn-outline-secondary, .btn-thank-outline, a.contest-block-cta.contest-block-cta--secondary, a.contest-block-cta.contest-block-cta--secondary:link, a.contest-block-cta.contest-block-cta--secondary:visited";
    $css = $selectors . " {\n";
    $css .= "  background-color: {$v['h_sbg']} !important;\n";
    $css .= "  border-color: {$v['h_sborder']} !important;\n";
    $css .= "  color: {$v['h_stxt']} !important;\n";
    $css .= "  border-radius: {$v['h_secondary_radius']}px !important;\n";
    $css .= config_button_typography_css((string) ($v['h_secondary_font'] ?? ''), (string) ($v['h_secondary_font_size'] ?? ''));
    $css .= config_button_fill_background_css($v['h_simg'], $v['h_simg_size'], $v['h_simg_position'], (string) ($v['h_secondary_gradient'] ?? ''));
    if ($v['h_simg'] !== '' && $v['secondary_hide_text']) {
        $css .= config_button_hide_text_css(true);
    }
    $css .= "}\n";
    $css .= config_button_typography_descendants_css($selectors);
    return $css;
}
}

if (!function_exists('config_button_secondary_interactive_css')) {
function config_button_secondary_interactive_css(array $v): string {
    $css = ".btn-secondary:hover, .btn-secondary:focus, .btn-secondary:active, .btn-secondary:focus-visible, .btn.btn-secondary:hover, .btn.btn-secondary:focus, .btn.btn-secondary:active, .btn.btn-secondary:focus-visible, .btn-outline-secondary:hover, .btn-outline-secondary:focus, .btn-outline-secondary:active, .btn-outline-secondary:focus-visible, .btn-thank-outline:hover, .btn-thank-outline:focus, .btn-thank-outline:active, .btn-thank-outline:focus-visible, a.contest-block-cta.contest-block-cta--secondary:hover, a.contest-block-cta.contest-block-cta--secondary:focus, a.contest-block-cta.contest-block-cta--secondary:active, a.contest-block-cta.contest-block-cta--secondary:focus-visible, .btn-check:checked + .btn-secondary, .btn-check:active + .btn-secondary, .show > .btn-secondary.dropdown-toggle, .btn-check:checked + .btn-outline-secondary, .btn-check:active + .btn-outline-secondary {\n";
    $css .= "  background-color: {$v['h_shover_bg']} !important;\n";
    $css .= "  border-color: {$v['h_shover_border']} !important;\n";
    $css .= "  color: {$v['h_stxt']} !important;\n";
    $hoverImg = $v['h_simg_hover'] !== '' ? $v['h_simg_hover'] : $v['h_simg'];
    $hoverGradient = (string) ($v['h_secondary_gradient_hover'] ?? '');
    if ($hoverGradient === '') {
        $hoverGradient = (string) ($v['h_secondary_gradient'] ?? '');
    }
    $css .= config_button_fill_background_css($hoverImg, $v['h_simg_size'], $v['h_simg_position'], $hoverGradient);
    if ($v['h_simg'] !== '' && $v['secondary_hide_text']) {
        $css .= config_button_hide_text_css(true);
    }
    $css .= "}\n";
    $css .= ".btn-thank-outline, .btn-outline-secondary {\n";
    $css .= "  background-color: transparent !important;\n";
    $css .= "}\n";
    return $css;
}
}

if (!function_exists('config_button_secondary_disabled_css')) {
function config_button_secondary_disabled_css(array $v): string {
    $css = ".btn-secondary:disabled, .btn-secondary.disabled, .btn-outline-secondary:disabled, .btn-outline-secondary.disabled {\n";
    $css .= "  background-color: {$v['h_sbg']} !important;\n";
    $css .= "  border-color: {$v['h_sborder']} !important;\n";
    $css .= "  color: {$v['h_stxt']} !important;\n";
    $css .= "  opacity: 0.65 !important;\n";
    $css .= "  border-radius: {$v['h_secondary_radius']}px !important;\n";
    $css .= config_button_fill_background_css($v['h_simg'], $v['h_simg_size'], $v['h_simg_position'], (string) ($v['h_secondary_gradient'] ?? ''));
    if ($v['h_simg'] !== '' && $v['secondary_hide_text']) {
        $css .= config_button_hide_text_css(true);
    }
    $css .= "}\n";
    $css .= "#submitBtn:disabled { opacity: 0.9; }\n";
    return $css;
}
}

if (!function_exists('get_button_style_block')) {
function get_button_style_block(): string {
    global $contest_config;
    $resolved = resolve_button_colors($contest_config);
    $vars = config_button_resolve_style_vars($resolved['primary'], $resolved['secondary']);

    $rootVars = ":root {\n";
    $pFont = trim((string) ($vars['h_primary_font'] ?? ''));
    $pSize = trim((string) ($vars['h_primary_font_size'] ?? ''));
    $sFont = trim((string) ($vars['h_secondary_font'] ?? ''));
    $sSize = trim((string) ($vars['h_secondary_font_size'] ?? ''));
    $rootVars .= '  --contest-btn-primary-font: ' . ($pFont !== '' ? "'{$pFont}', sans-serif" : 'Arial, Helvetica, sans-serif') . ";\n";
    $rootVars .= '  --contest-btn-secondary-font: ' . ($sFont !== '' ? "'{$sFont}', sans-serif" : 'Arial, Helvetica, sans-serif') . ";\n";
    if ($pSize !== '') {
        $rootVars .= "  --contest-btn-primary-font-size: {$pSize};\n";
    }
    if ($sSize !== '') {
        $rootVars .= "  --contest-btn-secondary-font-size: {$sSize};\n";
    }
    $rootVars .= "}\n";

    // Tipografia Bottoni deve battere i clamp layout (.logo-stack .registration-card .btn-lg).
    $sizeOverride = '';
    if ($pSize !== '') {
        $sizeOverride .= "#submitBtn.btn.btn-lg,\n"
            . ".logo-stack .registration-card #submitBtn,\n"
            . ".logo-stack .registration-card .btn-lg.btn-success,\n"
            . ".logo-stack .registration-card .btn-lg.btn-primary,\n"
            . ".registration-card .btn-lg.btn-success,\n"
            . ".registration-card .btn-lg.btn-primary {\n"
            . "  font-size: {$pSize} !important;\n"
            . "}\n";
    }
    if ($sSize !== '') {
        $sizeOverride .= ".logo-stack .registration-card .btn-lg.btn-secondary,\n"
            . ".registration-card .btn-lg.btn-secondary {\n"
            . "  font-size: {$sSize} !important;\n"
            . "}\n";
    }

    return "<style>\n"
        . $rootVars
        . config_button_primary_default_css($vars)
        . config_button_primary_interactive_css($vars)
        . config_button_primary_disabled_css($vars)
        . config_button_outline_css($vars)
        . config_button_secondary_default_css($vars)
        . config_button_secondary_interactive_css($vars)
        . config_button_secondary_disabled_css($vars)
        . $sizeOverride
        . "</style>\n";
}
}

if (!function_exists('contest_color_is_default_like')) {
    function contest_color_is_default_like(string $stored, array $factoryDefaults): bool
    {
        $stored = strtolower(trim($stored));
        if ($stored === '') {
            return true;
        }
        foreach ($factoryDefaults as $candidate) {
            if (strtolower(trim((string) $candidate)) === $stored) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('contest_clamp_opacity')) {
    function contest_clamp_opacity($value): int
    {
        return max(0, min(100, (int) $value));
    }
}

if (!function_exists('contest_hex_with_opacity')) {
  /**
   * @param string $hex Colore #rrggbb
   */
    function contest_hex_with_opacity(string $hex, int $opacity): string
    {
        $opacity = contest_clamp_opacity($opacity);
        if ($opacity === 0) {
            return 'transparent';
        }
        $hex = contest_normalize_hex_color($hex, '');
        if ($hex === '') {
            return 'transparent';
        }
        if ($opacity >= 100) {
            return $hex;
        }
        $r = hexdec(substr($hex, 1, 2));
        $g = hexdec(substr($hex, 3, 2));
        $b = hexdec(substr($hex, 5, 2));
        $alpha = round($opacity / 100, 2);

        return 'rgba(' . $r . ', ' . $g . ', ' . $b . ', ' . $alpha . ')';
    }
}

if (!function_exists('contest_hex_color_mix')) {
    /**
     * @param int $weight1 Percentuale del primo colore (0-100).
     */
    function contest_hex_color_mix(string $hex1, string $hex2, int $weight1 = 50): string
    {
        $hex1 = contest_normalize_hex_color($hex1, CONFIG_COLOR_BLACK) ?: CONFIG_COLOR_BLACK;
        $hex2 = contest_normalize_hex_color($hex2, CONFIG_COLOR_WHITE) ?: CONFIG_COLOR_WHITE;
        $w = max(0, min(100, $weight1)) / 100;
        $r1 = hexdec(substr($hex1, 1, 2));
        $g1 = hexdec(substr($hex1, 3, 2));
        $b1 = hexdec(substr($hex1, 5, 2));
        $r2 = hexdec(substr($hex2, 1, 2));
        $g2 = hexdec(substr($hex2, 3, 2));
        $b2 = hexdec(substr($hex2, 5, 2));

        return sprintf(
            '#%02x%02x%02x',
            (int) round($r1 * $w + $r2 * (1 - $w)),
            (int) round($g1 * $w + $g2 * (1 - $w)),
            (int) round($b1 * $w + $b2 * (1 - $w))
        );
    }
}

if (!function_exists('contest_color_to_hex_for_luminance')) {
    function contest_color_to_hex_for_luminance(string $color): string
    {
        $color = trim($color);
        if (preg_match('/^#[0-9a-f]{6}$/i', $color)) {
            return strtolower($color);
        }
        if (preg_match('/^rgba?\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)/i', $color, $matches)) {
            return sprintf('#%02x%02x%02x', (int) $matches[1], (int) $matches[2], (int) $matches[3]);
        }

        return CONFIG_COLOR_TEXT;
    }
}

if (!function_exists('contest_readable_text_on_background')) {
    function contest_readable_text_on_background(string $backgroundColor): string
    {
        return contest_hex_color_is_light(contest_color_to_hex_for_luminance($backgroundColor)) ? CONFIG_COLOR_TEXT : CONFIG_COLOR_WHITE;
    }
}

if (!function_exists('contest_stored_select2_highlight_bg')) {
    function contest_stored_select2_highlight_bg(array $contest_config): string
    {
        $stored = strtolower(trim((string) ($contest_config['form_field_select2_highlight_bg'] ?? '')));
        if ($stored === '') {
            $stored = strtolower(trim((string) ($contest_config['form_field_select2_color'] ?? '')));
        }

        return preg_match(CONFIG_REGEX_HEX6_LOWER, $stored) ? $stored : '';
    }
}

if (!function_exists('resolve_form_field_select2_highlight')) {
    /**
     * @return array{bg: string, text: string, bg_stored: string, text_stored: string}
     */
    function resolve_form_field_select2_highlight(array $contest_config, array $formFieldResolved): array
    {
        $focusCss = (string) ($formFieldResolved['focus'] ?? CONFIG_COLOR_PRIMARY);
        $bgCss = (string) ($formFieldResolved['bg'] ?? CONFIG_COLOR_WHITE);
        $focusHex = contest_color_to_hex_for_luminance($focusCss);
        $bgHex = contest_color_to_hex_for_luminance($bgCss);
        $defaultMixHex = contest_hex_color_mix($focusHex, $bgHex, 80);
        $defaultBgCss = 'color-mix(in srgb, var(--form-field-focus) 80%, var(--form-field-bg))';

        $useCustom = contest_form_fields_use_custom_colors($contest_config);
        $storedBg = $useCustom ? contest_stored_select2_highlight_bg($contest_config) : '';
        $storedText = '';
        if ($useCustom) {
            $storedText = strtolower(trim((string) ($contest_config['form_field_select2_highlight_text'] ?? '')));
            $storedText = preg_match(CONFIG_REGEX_HEX6_LOWER, $storedText) ? $storedText : '';
        }

        $effectiveBgHex = $storedBg !== '' ? $storedBg : $defaultMixHex;
        $resolvedBg = $storedBg !== '' ? $storedBg : $defaultBgCss;
        $resolvedText = $storedText !== ''
            ? $storedText
            : contest_readable_text_on_background($effectiveBgHex);

        return [
            'bg' => $resolvedBg,
            'text' => $resolvedText,
            'bg_stored' => $storedBg,
            'text_stored' => $storedText,
        ];
    }
}

if (!function_exists('resolve_form_field_error_style')) {
    /**
     * @return array{mode: string, summary_bg: string, summary_text: string, summary_border: string, summary_shadow: string, inline_text: string, inline_bg: string, inline_shadow: string}
     */
    function resolve_form_field_error_style(array $contest_config): array
    {
        $mode = strtolower(trim((string) ($contest_config['form_field_error_mode'] ?? 'classic')));
        if (!in_array($mode, ['classic', 'on_color_background'], true)) {
            $mode = 'classic';
        }
        if ($mode === 'on_color_background') {
            return [
                'mode' => $mode,
                'summary_bg' => CONFIG_RGBA_OVERLAY_45,
                'summary_text' => CONFIG_COLOR_WHITE,
                'summary_border' => CONFIG_RGBA_OVERLAY_45,
                'summary_shadow' => '0 1px 3px rgba(0, 0, 0, 0.75)',
                'inline_text' => CONFIG_COLOR_WHITE,
                'inline_bg' => CONFIG_RGBA_OVERLAY_45,
                'inline_shadow' => '0 1px 3px rgba(0, 0, 0, 0.75)',
            ];
        }

        return [
            'mode' => 'classic',
            'summary_bg' => '#f8d7da',
            'summary_text' => '#842029',
            'summary_border' => '#f5c2c7',
            'summary_shadow' => 'none',
            'inline_text' => '#dc3545',
            'inline_bg' => 'transparent',
            'inline_shadow' => 'none',
        ];
    }
}

if (!function_exists('resolve_form_field_parse_stored_calendar_hex')) {
/**
 * @return array{popup_bg: string, selected: string, hover_bg: string, hover_text: string}
 */
function resolve_form_field_parse_stored_calendar_hex(array $contest_config): array {
    $keys = [
        'popup_bg' => 'form_field_calendar_popup_bg',
        'selected' => 'form_field_calendar_selected_color',
        'hover_bg' => 'form_field_calendar_hover_bg',
        'hover_text' => 'form_field_calendar_hover_text',
    ];
    $stored = ['popup_bg' => '', 'selected' => '', 'hover_bg' => '', 'hover_text' => ''];
    if (!contest_form_fields_use_custom_colors($contest_config)) {
        return $stored;
    }
    foreach ($keys as $resultKey => $configKey) {
        $value = strtolower(trim((string) ($contest_config[$configKey] ?? '')));
        $stored[$resultKey] = preg_match(CONFIG_REGEX_HEX6_LOWER, $value) ? $value : '';
    }
    return $stored;
}
}

if (!function_exists('resolve_form_field_calendar_hover_text')) {
function resolve_form_field_calendar_hover_text(
    string $storedHoverText,
    string $storedHoverBg,
    string $hoverBgFallback,
    string $hoverTextFallback,
    array $formFieldResolved
): string {
    if ($storedHoverText !== '') {
        return $storedHoverText;
    }
    if ($storedHoverBg === '') {
        return $hoverTextFallback;
    }
    $effectiveHoverBgHex = $storedHoverBg;
    if (strpos($hoverBgFallback, 'color-mix') !== false) {
        $focusHex = contest_color_to_hex_for_luminance((string) ($formFieldResolved['focus'] ?? CONFIG_COLOR_PRIMARY));
        $bgHex = contest_color_to_hex_for_luminance((string) ($formFieldResolved['bg'] ?? CONFIG_COLOR_WHITE));
        $effectiveHoverBgHex = contest_hex_color_mix($focusHex, $bgHex, 80);
    }
    return contest_readable_text_on_background($effectiveHoverBgHex);
}
}

if (!function_exists('resolve_form_field_calendar_colors')) {
    /**
     * @param array{bg: string, focus: string} $formFieldResolved
     * @param array{bg: string, text: string} $select2Highlight
     * @return array{popup_bg: string, selected: string, hover_bg: string, hover_text: string, popup_bg_stored: string, selected_stored: string, hover_bg_stored: string, hover_text_stored: string}
     */
    function resolve_form_field_calendar_colors(array $contest_config, array $formFieldResolved, array $select2Highlight): array
    {
        $popupBgFallback = (string) ($formFieldResolved['bg'] ?? CONFIG_COLOR_WHITE);
        $selectedFallback = (string) ($formFieldResolved['focus'] ?? CONFIG_COLOR_PRIMARY);
        $hoverBgFallback = (string) ($select2Highlight['bg'] ?? 'color-mix(in srgb, var(--form-field-focus) 80%, var(--form-field-bg))');
        $hoverTextFallback = (string) ($select2Highlight['text'] ?? CONFIG_COLOR_WHITE);

        $stored = resolve_form_field_parse_stored_calendar_hex($contest_config);
        $resolvedHoverBg = $stored['hover_bg'] !== '' ? $stored['hover_bg'] : $hoverBgFallback;
        $resolvedHoverText = resolve_form_field_calendar_hover_text(
            $stored['hover_text'],
            $stored['hover_bg'],
            $hoverBgFallback,
            $hoverTextFallback,
            $formFieldResolved
        );

        return [
            'popup_bg' => $stored['popup_bg'] !== '' ? $stored['popup_bg'] : $popupBgFallback,
            'selected' => $stored['selected'] !== '' ? $stored['selected'] : $selectedFallback,
            'hover_bg' => $resolvedHoverBg,
            'hover_text' => $resolvedHoverText,
            'popup_bg_stored' => $stored['popup_bg'],
            'selected_stored' => $stored['selected'],
            'hover_bg_stored' => $stored['hover_bg'],
            'hover_text_stored' => $stored['hover_text'],
        ];
    }
}

if (!function_exists('resolve_form_field_merge_extended_colors')) {
function resolve_form_field_merge_extended_colors(array $contest_config, array $resolvedBase): array {
    $select2Highlight = resolve_form_field_select2_highlight($contest_config, $resolvedBase);
    $calendarColors = resolve_form_field_calendar_colors($contest_config, $resolvedBase, $select2Highlight);

    return array_merge($resolvedBase, [
        'select2_highlight_bg' => $select2Highlight['bg'],
        'select2_highlight_text' => $select2Highlight['text'],
        'select2_highlight_bg_stored' => $select2Highlight['bg_stored'],
        'select2_highlight_text_stored' => $select2Highlight['text_stored'],
        'calendar_popup_bg' => $calendarColors['popup_bg'],
        'calendar_selected' => $calendarColors['selected'],
        'calendar_hover_bg' => $calendarColors['hover_bg'],
        'calendar_hover_text' => $calendarColors['hover_text'],
        'calendar_popup_bg_stored' => $calendarColors['popup_bg_stored'],
        'calendar_selected_stored' => $calendarColors['selected_stored'],
        'calendar_hover_bg_stored' => $calendarColors['hover_bg_stored'],
        'calendar_hover_text_stored' => $calendarColors['hover_text_stored'],
    ]);
}
}

if (!function_exists('resolve_form_field_palette_defaults')) {
function resolve_form_field_palette_defaults(
    string $paletteText,
    string $palettePrimary,
    string $paletteSecondary,
    string $paletteTertiary
): array {
    return [
        'text' => $paletteText,
        'placeholder' => '',
        'border' => '',
        'bg' => CONFIG_COLOR_WHITE,
        'focus' => $palettePrimary,
        'checkbox' => $paletteSecondary,
        'consent_text' => $paletteText,
        'consent_link' => $paletteSecondary,
        'consent_link_hover' => $paletteTertiary,
        'bg_opacity' => 100,
        'focus_opacity' => 100,
        'consent_link_hover_opacity' => 100,
    ];
}
}

if (!function_exists('resolve_form_field_custom_stored_colors')) {
function resolve_form_field_custom_stored_colors(array $contest_config, array $palette): array {
    $storedText = strtolower(trim((string) ($contest_config['form_field_text_color'] ?? CONFIG_COLOR_BLACK)));
    $storedPlaceholder = strtolower(trim((string) ($contest_config['form_field_placeholder_color'] ?? '')));
    $storedBorder = strtolower(trim((string) ($contest_config['form_field_border_color'] ?? CONFIG_COLOR_BORDER)));
    $storedBg = strtolower(trim((string) ($contest_config['form_field_bg_color'] ?? CONFIG_COLOR_WHITE)));
    $storedFocus = strtolower(trim((string) ($contest_config['form_field_focus_color'] ?? '')));
    $storedCheckbox = strtolower(trim((string) ($contest_config['form_field_checkbox_color'] ?? '')));
    $storedConsentText = strtolower(trim((string) ($contest_config['form_field_consent_text_color'] ?? CONFIG_COLOR_BLACK)));
    $storedConsentLink = strtolower(trim((string) ($contest_config['form_field_consent_link_color'] ?? '')));
    $storedConsentLinkHover = strtolower(trim((string) ($contest_config['form_field_consent_link_hover_color'] ?? '')));

    // Personalizzazione ON: rispettare gli hex salvati (anche #000000 / #dee2e6 / #ffffff).
    $resolveStoredHex = static function (string $stored, string $fallback): string {
        return contest_normalize_hex_color($stored, $fallback) ?: $fallback;
    };

    $resolveOptionalHex = static function (string $stored, string $fallback): string {
        return $stored !== '' && preg_match(CONFIG_REGEX_HEX6_LOWER, $stored)
            ? (contest_normalize_hex_color($stored, $fallback) ?: $fallback)
            : $fallback;
    };

    $bgOpacity = contest_clamp_opacity((int) ($contest_config['form_field_bg_opacity'] ?? 100));
    $focusOpacity = contest_clamp_opacity((int) ($contest_config['form_field_focus_opacity'] ?? 100));
    $consentLinkHoverOpacity = contest_clamp_opacity((int) ($contest_config['form_field_consent_link_hover_opacity'] ?? 100));

    $text = $resolveStoredHex($storedText, CONFIG_COLOR_BLACK);
    $bg = $resolveStoredHex($storedBg, CONFIG_COLOR_WHITE);
    $border = $resolveStoredHex($storedBorder, CONFIG_COLOR_BORDER);
    $focus = $resolveOptionalHex($storedFocus, $palette['primary']);
    $consentLinkHover = $resolveOptionalHex($storedConsentLinkHover, $palette['tertiary']);
    $placeholder = $storedPlaceholder !== '' && preg_match(CONFIG_REGEX_HEX6_LOWER, $storedPlaceholder)
        ? (contest_normalize_hex_color($storedPlaceholder, '') ?: '')
        : '';

    return [
        'text' => $text,
        'placeholder' => $placeholder,
        'border' => $border,
        'bg' => contest_hex_with_opacity($bg, $bgOpacity),
        'focus' => contest_hex_with_opacity($focus, $focusOpacity),
        'checkbox' => $resolveOptionalHex($storedCheckbox, $palette['secondary']),
        'consent_text' => $resolveStoredHex($storedConsentText, CONFIG_COLOR_BLACK),
        'consent_link' => $resolveOptionalHex($storedConsentLink, $palette['secondary']),
        'consent_link_hover' => contest_hex_with_opacity($consentLinkHover, $consentLinkHoverOpacity),
        'bg_opacity' => $bgOpacity,
        'focus_opacity' => $focusOpacity,
        'consent_link_hover_opacity' => $consentLinkHoverOpacity,
    ];
}
}

if (!function_exists('resolve_form_field_colors')) {
    /**
     * @return array{text: string, border: string, bg: string, focus: string, checkbox: string, consent_link: string, consent_link_hover: string, select2_highlight_bg: string, select2_highlight_text: string, select2_highlight_bg_stored: string, select2_highlight_text_stored: string, calendar_popup_bg: string, calendar_selected: string, calendar_hover_bg: string, calendar_hover_text: string, calendar_popup_bg_stored: string, calendar_selected_stored: string, calendar_hover_bg_stored: string, calendar_hover_text_stored: string}
     */
    function resolve_form_field_colors(array $contest_config): array
    {
        $colors = is_array($contest_config['colors'] ?? null) ? $contest_config['colors'] : [];
        $palette = [
            'text' => contest_normalize_hex_color((string) ($colors['text'] ?? ''), CONFIG_COLOR_TEXT) ?: CONFIG_COLOR_TEXT,
            'primary' => contest_normalize_hex_color((string) ($colors['primary'] ?? ''), CONFIG_COLOR_PRIMARY) ?: CONFIG_COLOR_PRIMARY,
            'secondary' => contest_normalize_hex_color((string) ($colors['secondary'] ?? ''), CONFIG_COLOR_SECONDARY) ?: CONFIG_COLOR_SECONDARY,
            'tertiary' => contest_normalize_hex_color((string) ($colors['tertiary'] ?? ''), CONFIG_COLOR_PRIMARY_DARK) ?: CONFIG_COLOR_PRIMARY_DARK,
        ];

        if (!contest_form_fields_use_custom_colors($contest_config)) {
            return resolve_form_field_merge_extended_colors(
                $contest_config,
                resolve_form_field_palette_defaults($palette['text'], $palette['primary'], $palette['secondary'], $palette['tertiary'])
            );
        }

        return resolve_form_field_merge_extended_colors(
            $contest_config,
            resolve_form_field_custom_stored_colors($contest_config, $palette)
        );
    }
}

if (!function_exists('resolve_footer_palette_text_colors')) {
function resolve_footer_palette_text_colors(array $palette): array {
    return [
        'text_primary' => $palette['text'],
        'text_secondary' => $palette['secondary'],
        'link' => $palette['secondary'],
        'link_hover' => $palette['tertiary'],
    ];
}
}

if (!function_exists('resolve_footer_custom_text_colors')) {
function resolve_footer_custom_text_colors(array $style, array $palette): array {
    $resolve = static function (string $raw, array $defaults, string $fallback) use ($palette): string {
        $raw = strtolower(trim($raw));
        return contest_color_is_default_like($raw, $defaults)
            ? $fallback
            : (contest_normalize_hex_color($raw, $fallback) ?: $fallback);
    };

    return [
        'text_primary' => $resolve((string) ($style['text_primary'] ?? CONFIG_COLOR_TEXT), [CONFIG_COLOR_TEXT], $palette['text']),
        'text_secondary' => contest_color_is_default_like(strtolower(trim((string) ($style['text_secondary'] ?? CONFIG_COLOR_SECONDARY))), [CONFIG_COLOR_SECONDARY])
            ? $palette['secondary']
            : (contest_normalize_hex_color(strtolower(trim((string) ($style['text_secondary'] ?? CONFIG_COLOR_SECONDARY))), CONFIG_COLOR_SECONDARY) ?: CONFIG_COLOR_SECONDARY),
        'link' => $resolve((string) ($style['link'] ?? CONFIG_COLOR_PRIMARY), [CONFIG_COLOR_PRIMARY, CONFIG_COLOR_SUCCESS, $palette['primary']], $palette['secondary']),
        'link_hover' => $resolve((string) ($style['link_hover'] ?? CONFIG_COLOR_PRIMARY_DARK), [CONFIG_COLOR_PRIMARY_DARK, CONFIG_COLOR_PRIMARY_HOVER, CONFIG_COLOR_SUCCESS_DARK], $palette['tertiary']),
    ];
}
}

if (!function_exists('resolve_footer_style')) {
    /**
     * @return array<string, mixed>
     */
    function resolve_footer_style(array $contest_config): array
    {
        $style = is_array($contest_config['footer_style'] ?? null) ? $contest_config['footer_style'] : [];
        $colors = is_array($contest_config['colors'] ?? null) ? $contest_config['colors'] : [];
        $palette = [
            'text' => contest_normalize_hex_color((string) ($colors['text'] ?? ''), CONFIG_COLOR_TEXT) ?: CONFIG_COLOR_TEXT,
            'secondary' => contest_normalize_hex_color((string) ($colors['secondary'] ?? ''), CONFIG_COLOR_PRIMARY) ?: CONFIG_COLOR_PRIMARY,
            'tertiary' => contest_normalize_hex_color((string) ($colors['tertiary'] ?? ''), CONFIG_COLOR_PRIMARY_DARK) ?: CONFIG_COLOR_PRIMARY_DARK,
            'primary' => contest_normalize_hex_color((string) ($colors['primary'] ?? ''), CONFIG_COLOR_PRIMARY) ?: CONFIG_COLOR_PRIMARY,
        ];

        $resolved = $style;
        $textColors = contest_footer_use_custom_text_colors($contest_config)
            ? resolve_footer_custom_text_colors($style, $palette)
            : resolve_footer_palette_text_colors($palette);

        return array_merge($resolved, $textColors);
    }
}

if (!function_exists('config_footer_hex_to_rgba')) {
function config_footer_hex_to_rgba(string $hex, float $alpha): string {
    $hex = ltrim($hex, '#');
    if (strlen($hex) === 3) {
        $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
    }
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    return "rgba({$r}, {$g}, {$b}, {$alpha})";
}
}

if (!function_exists('config_footer_normalize_path')) {
function config_footer_normalize_path(string $path, bool $relative_from_manager): string {
    $path = trim($path);
    if ($path === '') {
        return '';
    }
    if (str_starts_with($path, CONFIG_SCHEME_HTTP) || str_starts_with($path, CONFIG_SCHEME_HTTPS) || str_starts_with($path, '//')) {
        return $path;
    }
    if ($path[0] === '/') {
        return $path;
    }
    return $relative_from_manager ? '../' . ltrim($path, '/') : $path;
}
}

if (!function_exists('config_footer_build_border_css')) {
function config_footer_build_border_css(
    float $border_opacity_css,
    int $border_width,
    array $border_sides,
    string $border_rgba
): string {
    $has_visible_border = $border_opacity_css > 0 && $border_width > 0 && !empty($border_sides);
    $border_css = [];
    foreach (['top', 'right', 'bottom', 'left'] as $side) {
        if ($has_visible_border && in_array($side, $border_sides, true)) {
            $border_css[] = "border-{$side}: {$border_width}px solid " . htmlspecialchars($border_rgba, ENT_QUOTES);
        } else {
            $border_css[] = "border-{$side}: none";
        }
    }
    $css = "footer.border-top, footer, .footer-collab.border-top, .footer-collab { " . implode(' !important; ', $border_css) . CONFIG_CSS_IMPORTANT_CLOSE;
    if (!$has_visible_border) {
        $css .= "footer.border-top, footer, .footer-collab.border-top, .footer-collab { border-color: transparent !important; }\n";
    }
    return $css;
}
}

if (!function_exists('config_footer_build_background_css')) {
function config_footer_build_background_css(array $style, string $selectors, bool $relative_from_manager): string {
    $mode = $style['mode'] ?? 'color';
    $bg = $style['background'] ?? CONFIG_COLOR_WHITE;
    $image = $style['image'] ?? '';
    $opacity_css = max(0, min(1, ((int) ($style['opacity'] ?? 100)) / 100));

    if ($mode === 'image' && $image !== '' && trim($image) !== '') {
        $resolved = htmlspecialchars(config_footer_normalize_path($image, $relative_from_manager), ENT_QUOTES);
        $bg_position = trim(($style['image_position_horizontal'] ?? 'center') . ' ' . ($style['image_position_vertical'] ?? 'center'));
        $bg_size = $style['image_size'] ?? 'cover';
        if ($bg_size === 'custom' && trim((string) ($style['image_size_custom'] ?? '')) !== '') {
            $bg_size = htmlspecialchars(trim((string) $style['image_size_custom']), ENT_QUOTES);
        }
        $bg_rgba = config_footer_hex_to_rgba($bg, $opacity_css);
        $css = "{$selectors} { position: relative !important; " . CONFIG_CSS_BG_COLOR_PREFIX_NOSPACE . htmlspecialchars($bg_rgba, ENT_QUOTES) . " !important; background-image: none !important; isolation: isolate !important; }\n";
        $css .= "{$selectors}::before { content: ''; position: absolute; top: 0; left: 0; right: 0; bottom: 0; background-image: url('{$resolved}') !important; background-size: " . htmlspecialchars($bg_size, ENT_QUOTES) . " !important; background-position: " . htmlspecialchars($bg_position, ENT_QUOTES) . " !important; background-repeat: " . htmlspecialchars((string) ($style['image_repeat'] ?? 'no-repeat'), ENT_QUOTES) . " !important; opacity: {$opacity_css}; z-index: -1 !important; pointer-events: none !important; }\n";
        $css .= "{$selectors} .container { position: relative !important; z-index: 1 !important; }\n";
        $css .= "{$selectors} a, {$selectors} button, {$selectors} input, {$selectors} select, {$selectors} textarea, {$selectors} [role='button'], {$selectors} [tabindex], {$selectors} [onclick] { position: relative !important; z-index: 2 !important; pointer-events: auto !important; }\n";
        return $css;
    }

    $bg_rgba = config_footer_hex_to_rgba($bg, $opacity_css);
    return "{$selectors} { " . CONFIG_CSS_BG_COLOR_PREFIX_NOSPACE . htmlspecialchars($bg_rgba, ENT_QUOTES) . " !important; background-image: none !important; }\n";
}
}

if (!function_exists('config_footer_build_text_css')) {
function config_footer_build_text_css(array $style): string {
    $h = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES);
    $txt = $h((string) ($style['text_primary'] ?? CONFIG_COLOR_TEXT));
    $txt_secondary = $h((string) ($style['text_secondary'] ?? CONFIG_COLOR_SECONDARY));
    $link = $h((string) ($style['link'] ?? CONFIG_COLOR_PRIMARY));
    $hover = $h((string) ($style['link_hover'] ?? CONFIG_COLOR_PRIMARY_DARK));

    return "footer.text-dark, footer, .footer-collab.text-dark, .footer-collab { color: {$txt} !important; }\n"
        . ".footer-title, footer .fw-semibold, footer strong { color: {$txt_secondary} !important; }\n"
        . "footer div.fw-bold.text-success, footer div.fw-bold.text-uppercase.text-success { color: {$txt_secondary} !important; }\n"
        . "footer a.text-success, footer nav a, footer a.text-success.fw-semibold { color: {$link} !important; }\n"
        . "footer a.text-success:hover, footer a.text-success:focus, footer nav a:hover, footer nav a:focus, footer a.text-success.fw-semibold:hover, footer a.text-success.fw-semibold:focus { color: {$hover} !important; }\n"
        . "footer .footer-legal-block a, footer .footer-text a, footer .footer-legal-block a.footer-rich-link, footer .footer-text a.footer-rich-link { color: {$link} !important; text-decoration: underline; }\n"
        . "footer .footer-legal-block a:hover, footer .footer-legal-block a:focus, footer .footer-text a:hover, footer .footer-text a:focus, footer .footer-legal-block a.footer-rich-link:hover, footer .footer-legal-block a.footer-rich-link:focus, footer .footer-text a.footer-rich-link:hover, footer .footer-text a.footer-rich-link:focus { color: {$hover} !important; }\n"
        . "footer span.text-success.fw-semibold { color: {$link} !important; }\n"
        . ".footer-text, footer .small, footer .text-muted { color: {$txt} !important; }\n"
        . "footer .footer-legal-block, footer .footer-links-inline { color: {$txt} !important; }\n"
        . "footer .footer-link-sep { color: {$txt_secondary} !important; }\n";
}
}

if (!function_exists('get_footer_style_block')) {
function get_footer_style_block(bool $relative_from_manager = false): string {
    global $contest_config;

    $style = resolve_footer_style($contest_config);
    $border_color = $style['border_color'] ?? CONFIG_COLOR_BLACK;
    $border_opacity = (int)($style['border_opacity'] ?? 10);
    $border_width = (int)($style['border_width'] ?? 0);
    $border_sides = isset($style['border_sides']) ? (is_array($style['border_sides']) ? $style['border_sides'] : []) : [];
    $border_opacity_css = max(0, min(1, $border_opacity / 100));
    $border_rgba = config_footer_hex_to_rgba($border_color, $border_opacity_css);
    $selectors = 'footer.bg-white, footer, .footer-collab.bg-white, .footer-collab';

    return "<style>\n"
        . config_footer_build_border_css($border_opacity_css, $border_width, $border_sides, $border_rgba)
        . config_footer_build_background_css($style, $selectors, $relative_from_manager)
        . config_footer_build_text_css($style)
        . (static function () use ($contest_config): string {
            $family = contest_resolve_font_family(
                (string) (($contest_config['footer_layout']['font_family'] ?? '')),
                $contest_config
            );
            $size = contest_resolve_font_size((string) ($contest_config['footer_layout']['font_size'] ?? ''));
            if ($family === '' && $size === '') {
                return '';
            }
            $rules = [];
            if ($family !== '') {
                $ff = htmlspecialchars($family, ENT_QUOTES, 'UTF-8');
                $rules[] = "font-family: '{$ff}', sans-serif !important";
            }
            if ($size !== '') {
                $fs = htmlspecialchars($size, ENT_QUOTES, 'UTF-8');
                $rules[] = "font-size: {$fs} !important";
            }
            return 'footer, footer *, .footer-collab, .footer-collab * { ' . implode('; ', $rules) . "; }\n";
        })()
        . "</style>\n";
}
}

if (!function_exists('contest_hex_color_is_light')) {
    function contest_hex_color_is_light(string $hex): bool
    {
        $hex = ltrim(strtolower(trim($hex)), '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        if (strlen($hex) !== 6) {
            return false;
        }
        $r = hexdec(substr($hex, 0, 2)) / 255;
        $g = hexdec(substr($hex, 2, 2)) / 255;
        $b = hexdec(substr($hex, 4, 2)) / 255;
        $luminance = 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;

        return $luminance > 0.62;
    }
}

if (!function_exists('contest_normalize_hex_color')) {
    function contest_normalize_hex_color(string $value, string $fallback = ''): string
    {
        $value = strtolower(trim($value));
        if (preg_match(CONFIG_REGEX_HEX6_LOWER, $value)) {
            return $value;
        }
        $fallback = strtolower(trim($fallback));

        return preg_match(CONFIG_REGEX_HEX6_LOWER, $fallback) ? $fallback : '';
    }
}

if (!function_exists('contest_normalize_help_accordion_bg')) {
    /**
     * @param string $raw Valeur saisie (vide, #hex ou transparent)
     * @param string $defaultHex Couleur par défaut si vide
     */
    function contest_normalize_help_accordion_bg(string $raw, string $defaultHex): string
    {
        $raw = strtolower(trim($raw));
        if ($raw === '') {
            return contest_normalize_hex_color('', $defaultHex) ?: $defaultHex;
        }
        if ($raw === 'transparent') {
            return 'transparent';
        }

        return contest_normalize_hex_color($raw, $defaultHex) ?: $defaultHex;
    }
}

if (!function_exists('resolve_help_page_palette_defaults')) {
function resolve_help_page_palette_defaults(array $contest_config): array {
    $colors = is_array($contest_config['colors'] ?? null) ? $contest_config['colors'] : [];
    $primary = contest_normalize_hex_color((string) ($colors['primary'] ?? ''), CONFIG_COLOR_SUCCESS) ?: CONFIG_COLOR_SUCCESS;
    $textNorm = contest_normalize_hex_color((string) ($colors['text'] ?? ''), CONFIG_COLOR_BLACK) ?: CONFIG_COLOR_BLACK;
    $readableOnWhite = contest_hex_color_is_light($textNorm) ? $primary : $textNorm;
    $bodyText = contest_hex_color_is_light($textNorm) ? CONFIG_COLOR_TEXT : $textNorm;
    $formFields = resolve_form_field_colors($contest_config);

    return [
        'accordion' => [
            'header_bg' => CONFIG_COLOR_WHITE,
            'header_text' => $readableOnWhite,
            'header_bg_open' => '',
            'header_text_open' => $readableOnWhite,
            'body_bg' => CONFIG_COLOR_WHITE,
            'body_text' => $bodyText,
            'title_text' => $readableOnWhite,
            'border_color' => $primary,
        ],
        'form' => [
            'heading_text' => $readableOnWhite,
            'subtitle_text' => $bodyText,
            'field_text' => (string) ($formFields['text'] ?? CONFIG_COLOR_BLACK),
            'placeholder_text' => '',
            'field_bg' => (string) ($formFields['bg'] ?? CONFIG_COLOR_WHITE),
            'field_border' => (string) ($formFields['border'] ?? CONFIG_COLOR_BORDER),
            'checkbox' => (string) ($formFields['checkbox'] ?? CONFIG_COLOR_SECONDARY),
            'consent_text' => (string) ($formFields['consent_text'] ?? CONFIG_COLOR_BLACK),
            'consent_link' => (string) ($formFields['consent_link'] ?? CONFIG_COLOR_SECONDARY),
            'consent_link_hover' => (string) ($formFields['consent_link_hover'] ?? CONFIG_COLOR_PRIMARY_DARK),
        ],
    ];
}
}

if (!function_exists('resolve_help_page_custom_accordion')) {
function resolve_help_page_custom_accordion(array $accCustom, array $defaults): array {
    $resolveAccHex = static function (string $raw, string $defaultValue, array $legacyDefaults): string {
        $raw = trim($raw);
        if ($raw === '' || contest_color_is_default_like($raw, $legacyDefaults)) {
            return $defaultValue;
        }
        return contest_normalize_hex_color($raw, $defaultValue) ?: $defaultValue;
    };

    $accordion = [];
    $accordion['header_bg'] = contest_normalize_help_accordion_bg((string) ($accCustom['header_bg'] ?? ''), (string) $defaults['accordion']['header_bg']);
    $accordion['body_bg'] = contest_normalize_help_accordion_bg((string) ($accCustom['body_bg'] ?? ''), (string) $defaults['accordion']['body_bg']);
    foreach ($defaults['accordion'] as $key => $defaultValue) {
        if (in_array($key, ['header_bg', 'header_bg_open', 'body_bg'], true)) {
            continue;
        }
        $raw = trim((string) ($accCustom[$key] ?? ''));
        if ($raw === '') {
            $accordion[$key] = (string) $defaultValue;
            continue;
        }
        $legacy = match ($key) {
            'border_color' => [CONFIG_COLOR_PRIMARY, CONFIG_COLOR_SUCCESS, '#38a668'],
            'header_text', 'header_text_open', 'title_text', 'body_text' => [CONFIG_COLOR_TEXT, CONFIG_COLOR_BLACK],
            default => [],
        };
        $accordion[$key] = $resolveAccHex($raw, (string) $defaultValue, $legacy);
    }

    $openBgRaw = strtolower(trim((string) ($accCustom['header_bg_open'] ?? '')));
    if ($openBgRaw === 'transparent') {
        $accordion['header_bg_open'] = 'transparent';
        $accordion['header_bg_open_use_mix'] = false;
    } elseif ($openBgRaw !== '' && preg_match(CONFIG_REGEX_HEX6_LOWER, $openBgRaw)) {
        $accordion['header_bg_open'] = $openBgRaw;
        $accordion['header_bg_open_use_mix'] = false;
    } else {
        $accordion['header_bg_open'] = '';
        $accordion['header_bg_open_use_mix'] = true;
    }

    return $accordion;
}
}

if (!function_exists('resolve_help_page_custom_form')) {
function resolve_help_page_custom_form(array $formCustom, array $defaults): array {
    $form = [];
    $fieldRaw = trim((string) ($formCustom['field_text'] ?? ''));
    if ($fieldRaw === '' || contest_color_is_default_like($fieldRaw, [CONFIG_COLOR_BLACK, CONFIG_COLOR_TEXT])) {
        $form['field_text'] = $defaults['form']['field_text'];
    } else {
        $form['field_text'] = contest_normalize_hex_color($fieldRaw, $defaults['form']['field_text']) ?: $defaults['form']['field_text'];
    }
    $placeholderRaw = trim((string) ($formCustom['placeholder_text'] ?? ''));
    if ($placeholderRaw !== '' && preg_match('/^#[0-9a-f]{6}$/i', $placeholderRaw)) {
        $form['placeholder_text'] = strtolower($placeholderRaw);
        $form['placeholder_use_mix'] = false;
    } else {
        $form['placeholder_text'] = '';
        $form['placeholder_use_mix'] = true;
    }

    $fieldBgRaw = strtolower(trim((string) ($formCustom['field_bg'] ?? '')));
    if ($fieldBgRaw === 'transparent') {
        $form['field_bg'] = 'transparent';
    } elseif ($fieldBgRaw !== '' && preg_match('/^#[0-9a-f]{6}$/i', $fieldBgRaw)) {
        $form['field_bg'] = $fieldBgRaw;
    } else {
        $form['field_bg'] = (string) ($defaults['form']['field_bg'] ?? CONFIG_COLOR_WHITE);
    }

    $resolveOptionalHex = static function (string $raw, string $fallback): string {
        $raw = trim($raw);
        if ($raw === '') {
            return $fallback;
        }
        return contest_normalize_hex_color($raw, $fallback) ?: $fallback;
    };

    $form['heading_text'] = $resolveOptionalHex(
        (string) ($formCustom['heading_text'] ?? ''),
        (string) ($defaults['form']['heading_text'] ?? CONFIG_COLOR_SUCCESS)
    );
    $form['subtitle_text'] = $resolveOptionalHex(
        (string) ($formCustom['subtitle_text'] ?? ''),
        (string) ($defaults['form']['subtitle_text'] ?? CONFIG_COLOR_TEXT)
    );
    $form['field_border'] = $resolveOptionalHex(
        (string) ($formCustom['field_border'] ?? ''),
        (string) ($defaults['form']['field_border'] ?? CONFIG_COLOR_BORDER)
    );
    $form['checkbox'] = $resolveOptionalHex(
        (string) ($formCustom['checkbox'] ?? ''),
        (string) ($defaults['form']['checkbox'] ?? CONFIG_COLOR_SECONDARY)
    );
    $form['consent_text'] = $resolveOptionalHex(
        (string) ($formCustom['consent_text'] ?? ''),
        (string) ($defaults['form']['consent_text'] ?? CONFIG_COLOR_BLACK)
    );
    $form['consent_link'] = $resolveOptionalHex(
        (string) ($formCustom['consent_link'] ?? ''),
        (string) ($defaults['form']['consent_link'] ?? CONFIG_COLOR_SECONDARY)
    );
    $form['consent_link_hover'] = $resolveOptionalHex(
        (string) ($formCustom['consent_link_hover'] ?? ''),
        (string) ($defaults['form']['consent_link_hover'] ?? CONFIG_COLOR_PRIMARY_DARK)
    );

    return $form;
}
}

if (!function_exists('resolve_help_page_style')) {
    /**
     * @return array{accordion: array<string, mixed>, form: array<string, mixed>}
     */
    function resolve_help_page_style(array $contest_config): array
    {
        $defaults = resolve_help_page_palette_defaults($contest_config);

        if (!contest_help_page_use_custom_colors($contest_config)) {
            $accordion = $defaults['accordion'];
            $accordion['header_bg_open'] = '';
            $accordion['header_bg_open_use_mix'] = true;

            return [
                'accordion' => $accordion,
                'form' => array_merge($defaults['form'], ['placeholder_use_mix' => true]),
            ];
        }

        $custom = is_array($contest_config['help_page_style'] ?? null) ? $contest_config['help_page_style'] : [];
        return [
            'accordion' => resolve_help_page_custom_accordion(
                is_array($custom['accordion'] ?? null) ? $custom['accordion'] : [],
                $defaults
            ),
            'form' => resolve_help_page_custom_form(
                is_array($custom['form'] ?? null) ? $custom['form'] : [],
                $defaults
            ),
        ];
    }
}

if (!function_exists('contest_is_css_transparent_bg')) {
    function contest_is_css_transparent_bg(string $value): bool
    {
        return strtolower(trim($value)) === 'transparent';
    }
}

if (!function_exists('get_help_page_accordion_style_css')) {
function get_help_page_accordion_style_css(array $acc): string {
    $escape = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    $headerBg = (string) ($acc['header_bg'] ?? CONFIG_COLOR_WHITE);
    $bodyBg = (string) ($acc['body_bg'] ?? CONFIG_COLOR_WHITE);
    $headerBgOpen = !empty($acc['header_bg_open_use_mix']) ? '' : (string) ($acc['header_bg_open'] ?? '');
    $headerBgTransparent = contest_is_css_transparent_bg($headerBg);
    $bodyBgTransparent = contest_is_css_transparent_bg($bodyBg);
    $headerBgOpenTransparent = contest_is_css_transparent_bg($headerBgOpen);
    $borderColor = $escape((string) $acc['border_color']);

    $css = '';
    if ($headerBgTransparent || $bodyBgTransparent || $headerBgOpenTransparent) {
        $css .= ".help-page .card.registration-card.public-page-container--help {\n";
        $css .= "  background-color: transparent !important;\n";
        $css .= "  --bs-card-bg: transparent;\n";
        $css .= "  box-shadow: none !important;\n";
        $css .= "}\n";
        $css .= ".help-page .public-page-container--help .help-faq-section {\n";
        $css .= "  background-color: transparent !important;\n";
        $css .= "}\n";
    }
    $css .= ".help-page #faqAccordion > h1,\n.help-page .help-faq-section h1 {\n  color: " . $escape((string) $acc['title_text']) . " !important;\n";
    $titleFontSize = contest_resolve_font_size((string) ($acc['title_font_size'] ?? ''));
    if ($titleFontSize !== '') {
        $css .= "  font-size: " . $escape($titleFontSize) . " !important;\n";
    }
    $titleMarginTop = max(0, min(120, (int) ($acc['title_margin_top'] ?? 0)));
    if ($titleMarginTop > 0) {
        $css .= "  margin-top: {$titleMarginTop}px !important;\n";
    }
    $css .= "}\n";
    $css .= ".help-page #faqAccordion {\n  --bs-accordion-border-color: {$borderColor};\n";
    $css .= "  --bs-accordion-btn-focus-box-shadow: 0 0 0 0.15rem color-mix(in srgb, {$borderColor} 30%, transparent);\n";
    if ($headerBgTransparent) {
        $css .= "  --bs-accordion-bg: transparent;\n  --bs-accordion-btn-bg: transparent;\n  --bs-accordion-active-bg: transparent;\n";
    }
    $css .= "}\n.help-page #faqAccordion .accordion-item {\n  border-color: {$borderColor} !important;\n";
    if ($headerBgTransparent || $bodyBgTransparent || $headerBgOpenTransparent) {
        $css .= "  background-color: transparent !important;\n";
    }
    $css .= "}\n.help-page #faqAccordion .accordion-header {\n";
    if ($headerBgTransparent || $headerBgOpenTransparent) {
        $css .= "  background-color: transparent !important;\n";
    }
    $css .= "}\n.help-page #faqAccordion .accordion-button {\n";
    $css .= " " . CONFIG_CSS_BG_COLOR_PREFIX . $escape($headerBg) . CONFIG_CSS_IMPORTANT_NL;
    $css .= " " . CONFIG_CSS_COLOR_PREFIX . $escape((string) $acc['header_text']) . CONFIG_CSS_IMPORTANT_NL;
    $css .= "  border-color: {$borderColor} !important;\n  box-shadow: none !important;\n}\n";
    $css .= ".help-page #faqAccordion .accordion-button:not(.collapsed) {\n";
    $css .= !empty($acc['header_bg_open_use_mix'])
        ? "  background-color: color-mix(in srgb, var(--contest-primary) 12%, #ffffff) !important;\n"
        : " " . CONFIG_CSS_BG_COLOR_PREFIX . $escape($headerBgOpen !== '' ? $headerBgOpen : $headerBg) . CONFIG_CSS_IMPORTANT_NL;
    $css .= " " . CONFIG_CSS_COLOR_PREFIX . $escape((string) $acc['header_text_open']) . CONFIG_CSS_IMPORTANT_NL;
    $css .= "  border-color: {$borderColor} !important;\n  box-shadow: none !important;\n}\n";
    $css .= ".help-page #faqAccordion .accordion-button:focus {\n";
    $css .= "  box-shadow: 0 0 0 0.15rem color-mix(in srgb, {$borderColor} 30%, transparent) !important;\n";
    $css .= "  border-color: {$borderColor} !important;\n}\n";
    if ($bodyBgTransparent || $headerBgOpenTransparent) {
        $css .= ".help-page #faqAccordion .accordion-collapse {\n  background-color: transparent !important;\n}\n";
    }
    $css .= ".help-page #faqAccordion .accordion-body {\n";
    $css .= " " . CONFIG_CSS_BG_COLOR_PREFIX . $escape($bodyBg) . CONFIG_CSS_IMPORTANT_NL;
    $css .= " " . CONFIG_CSS_COLOR_PREFIX . $escape((string) $acc['body_text']) . CONFIG_CSS_IMPORTANT_NL . "}\n";
    return $css;
}
}

if (!function_exists('get_help_page_form_style_css')) {
function get_help_page_form_style_css(array $form, array $contest_config): string {
    $escape = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    $headingText = $escape((string) ($form['heading_text'] ?? CONFIG_COLOR_SUCCESS));
    $subtitleText = $escape((string) ($form['subtitle_text'] ?? CONFIG_COLOR_TEXT));
    $fieldText = $escape((string) $form['field_text']);
    $fieldBg = (string) ($form['field_bg'] ?? CONFIG_COLOR_WHITE);
    $fieldBorder = $escape((string) ($form['field_border'] ?? CONFIG_COLOR_BORDER));
    $checkboxAccent = $escape((string) ($form['checkbox'] ?? resolve_form_field_colors($contest_config)['checkbox'] ?? CONFIG_COLOR_SECONDARY));
    $consentText = $escape((string) ($form['consent_text'] ?? CONFIG_COLOR_BLACK));
    $consentLink = $escape((string) ($form['consent_link'] ?? CONFIG_COLOR_SECONDARY));
    $consentLinkHover = $escape((string) ($form['consent_link_hover'] ?? CONFIG_COLOR_PRIMARY_DARK));

    $css = ".help-page .help-form-heading {\n  color: {$headingText} !important;\n}\n";
    $css .= ".help-page .help-form-subtitle {\n  color: {$subtitleText} !important;\n}\n";
    $css .= ".help-page #help-form .form-control,\n.help-page #help-form textarea.form-control {\n";
    $css .= "  color: {$fieldText} !important;\n  -webkit-text-fill-color: {$fieldText} !important;\n  caret-color: {$fieldText} !important;\n";
    $css .= "  border-color: {$fieldBorder} !important;\n";
    if (contest_is_css_transparent_bg($fieldBg)) {
        $css .= "  background-color: transparent !important;\n";
    } else {
        $css .= "  background-color: " . $escape($fieldBg) . " !important;\n";
    }
    $css .= "}\n";
    if (!empty($form['placeholder_use_mix'])) {
        $css .= ".help-page #help-form .form-control::placeholder,\n.help-page #help-form textarea.form-control::placeholder {\n";
        $css .= "  color: color-mix(in srgb, {$fieldText} 50%, #ffffff) !important;\n  opacity: 1 !important;\n}\n";
    } else {
        $placeholderText = $escape((string) $form['placeholder_text']);
        $css .= ".help-page #help-form .form-control::placeholder,\n.help-page #help-form textarea.form-control::placeholder {\n";
        $css .= "  color: {$placeholderText} !important;\n  opacity: 1 !important;\n}\n";
    }
    $autofillBg = contest_is_css_transparent_bg($fieldBg) ? 'transparent' : $escape($fieldBg);
    $css .= ".help-page #help-form .form-control:-webkit-autofill,\n.help-page #help-form .form-control:-webkit-autofill:hover,\n";
    $css .= ".help-page #help-form .form-control:-webkit-autofill:focus,\n.help-page #help-form textarea.form-control:-webkit-autofill {\n";
    $css .= "  -webkit-text-fill-color: {$fieldText} !important;\n  caret-color: {$fieldText} !important;\n";
    $css .= "  -webkit-box-shadow: 0 0 0 1000px {$autofillBg} inset !important;\n";
    $css .= "  box-shadow: 0 0 0 1000px {$autofillBg} inset !important;\n}\n";
    $css .= ".help-page #help-form .form-check-input { accent-color: {$checkboxAccent}; }\n";
    $css .= ".help-page #help-form .form-check-input:checked { background-color: {$checkboxAccent} !important; border-color: {$checkboxAccent} !important; }\n";
    $css .= ".help-page #help-form .form-check-input:focus { border-color: {$checkboxAccent} !important; box-shadow: 0 0 0 0.15rem color-mix(in srgb, {$checkboxAccent} 30%, transparent) !important; }\n";
    $css .= ".help-page #help-form .form-check-label {\n  color: {$consentText} !important;\n}\n";
    $css .= ".help-page #help-form .form-check-label a {\n  color: {$consentLink} !important;\n}\n";
    $css .= ".help-page #help-form .form-check-label a:hover,\n.help-page #help-form .form-check-label a:focus {\n  color: {$consentLinkHover} !important;\n}\n";
    return $css;
}
}

if (!function_exists('get_help_page_style_block')) {
    function get_help_page_style_block(): string
    {
        global $contest_config;
        $style = resolve_help_page_style($contest_config);
        $fontCss = '';
        $helpFont = contest_resolve_font_family(
            (string) (($contest_config['help_page_style']['font_family'] ?? '')),
            $contest_config
        );
        $helpSize = contest_resolve_font_size((string) ($contest_config['help_page_style']['font_size'] ?? ''));
        $rawAcc = is_array($contest_config['help_page_style']['accordion'] ?? null)
            ? $contest_config['help_page_style']['accordion']
            : [];
        // Typo titolo FAQ: indipendente dalla personalizzazione colori
        $style['accordion']['title_font_size'] = contest_resolve_font_size((string) ($rawAcc['title_font_size'] ?? ''));
        $style['accordion']['title_margin_top'] = max(0, min(120, (int) ($rawAcc['title_margin_top'] ?? 0)));
        if ($helpFont !== '' || $helpSize !== '') {
            $rules = [];
            if ($helpFont !== '') {
                $ff = htmlspecialchars($helpFont, ENT_QUOTES, 'UTF-8');
                $rules[] = "font-family: '{$ff}', sans-serif !important";
            }
            if ($helpSize !== '') {
                $fs = htmlspecialchars($helpSize, ENT_QUOTES, 'UTF-8');
                $rules[] = "font-size: {$fs} !important";
            }
            $fontCss .= ".help-page .public-page-container--help,\n";
            $fontCss .= ".help-page .help-faq-section, .help-page #faqAccordion,\n";
            $fontCss .= ".help-page #faqAccordion .accordion-button, .help-page #faqAccordion .accordion-body,\n";
            $fontCss .= ".help-page .help-form-heading, .help-page .help-form-subtitle,\n";
            $fontCss .= ".help-page .help-form-wrapper, .help-page .help-page-custom-content,\n";
            $fontCss .= ".help-page #help-form, .help-page #help-form label, .help-page #help-form .form-label,\n";
            $fontCss .= ".help-page #help-form .form-control, .help-page #help-form .form-select,\n";
            $fontCss .= ".help-page #help-form .form-check-label {\n";
            $fontCss .= '  ' . implode('; ', $rules) . ";\n";
            $fontCss .= "}\n";
        }

        // Accordion CSS dopo fontCss così title_font_size vince sull'eredità di #faqAccordion
        return "<style>\n"
            . get_help_page_form_style_css($style['form'], $contest_config)
            . $fontCss
            . get_help_page_accordion_style_css($style['accordion'])
            . "</style>\n";
    }
}

if (!function_exists('get_flatpickr_theme_style_block')) {
    /**
     * CSS Flatpickr con colori risolti (letterali) per sovrascrivere il CDN dopo il caricamento.
     */
    function get_flatpickr_theme_style_block(array $contest_config): string
    {
        $resolved = resolve_form_field_colors($contest_config);
        $escape = static function (string $value): string {
            return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        };

        $popupBg = $escape((string) $resolved['calendar_popup_bg']);
        $selected = $escape((string) $resolved['calendar_selected']);
        $hoverBg = $escape((string) $resolved['calendar_hover_bg']);
        $hoverText = $escape((string) $resolved['calendar_hover_text']);
        $text = $escape((string) $resolved['text']);
        $border = $escape((string) ($resolved['border'] !== '' ? $resolved['border'] : CONFIG_COLOR_BORDER));

        $css = "<style>\n";
        $css .= "/* Flatpickr: tema calendario Data scontrino (colori risolti, dopo flatpickr.min.css) */\n";
        $bgTargets = ".flatpickr-calendar,\n";
        $bgTargets .= ".flatpickr-calendar .flatpickr-months,\n";
        $bgTargets .= ".flatpickr-calendar .flatpickr-innerContainer,\n";
        $bgTargets .= ".flatpickr-calendar .flatpickr-rContainer,\n";
        $bgTargets .= ".flatpickr-calendar .flatpickr-weekdays,\n";
        $bgTargets .= ".flatpickr-calendar .flatpickr-days,\n";
        $bgTargets .= ".flatpickr-calendar .dayContainer";
        $css .= "{$bgTargets} { background: {$popupBg} !important; }\n";
        $css .= ".flatpickr-calendar { border: 1px solid {$border} !important; color: {$text} !important; box-shadow: 0 8px 24px rgba(15, 23, 42, 0.14) !important; }\n";
        $css .= ".flatpickr-months .flatpickr-month,\n";
        $css .= ".flatpickr-current-month .flatpickr-monthDropdown-months,\n";
        $css .= ".flatpickr-current-month input.cur-year,\n";
        $css .= ".flatpickr-months .flatpickr-prev-month,\n";
        $css .= ".flatpickr-months .flatpickr-next-month { color: {$text} !important; fill: {$text} !important; }\n";
        $css .= ".flatpickr-months .flatpickr-prev-month:hover svg,\n";
        $css .= ".flatpickr-months .flatpickr-next-month:hover svg { fill: {$selected} !important; }\n";
        $css .= ".flatpickr-current-month .flatpickr-monthDropdown-months { display: none !important; }\n";
        $css .= ".flatpickr-current-month .cur-month { font-weight: 600 !important; pointer-events: none !important; }\n";
        $css .= ".flatpickr-current-month input.cur-year { pointer-events: none !important; -moz-appearance: textfield !important; }\n";
        $css .= ".flatpickr-current-month input.cur-year::-webkit-outer-spin-button,\n";
        $css .= ".flatpickr-current-month input.cur-year::-webkit-inner-spin-button { -webkit-appearance: none !important; margin: 0 !important; }\n";
        $css .= ".flatpickr-current-month .numInputWrapper span.arrowUp,\n";
        $css .= ".flatpickr-current-month .numInputWrapper span.arrowDown { display: none !important; }\n";
        $css .= ".flatpickr-current-month .numInputWrapper:hover { background: transparent !important; }\n";
        $css .= "span.flatpickr-weekday { color: color-mix(in srgb, {$text} 70%, transparent) !important; }\n";
        $css .= ".flatpickr-day { color: {$text} !important; background: transparent !important; border-color: transparent !important; }\n";
        $css .= ".flatpickr-day.today { border-color: {$selected} !important; }\n";
        $css .= ".flatpickr-day:hover,\n";
        $css .= ".flatpickr-day.today:hover,\n";
        $css .= ".flatpickr-day:focus,\n";
        $css .= ".flatpickr-day.today:focus { background: {$hoverBg} !important; color: {$hoverText} !important; border-color: transparent !important; }\n";
        $css .= ".flatpickr-day.selected,\n";
        $css .= ".flatpickr-day.startRange,\n";
        $css .= ".flatpickr-day.endRange,\n";
        $css .= ".flatpickr-day.selected.inRange,\n";
        $css .= ".flatpickr-day.startRange.inRange,\n";
        $css .= ".flatpickr-day.endRange.inRange,\n";
        $css .= ".flatpickr-day.selected:focus,\n";
        $css .= ".flatpickr-day.startRange:focus,\n";
        $css .= ".flatpickr-day.endRange:focus,\n";
        $css .= ".flatpickr-day.selected:hover,\n";
        $css .= ".flatpickr-day.startRange:hover,\n";
        $css .= ".flatpickr-day.endRange:hover,\n";
        $css .= ".flatpickr-day.selected.prevMonthDay,\n";
        $css .= ".flatpickr-day.startRange.prevMonthDay,\n";
        $css .= ".flatpickr-day.endRange.prevMonthDay,\n";
        $css .= ".flatpickr-day.selected.nextMonthDay,\n";
        $css .= ".flatpickr-day.startRange.nextMonthDay,\n";
        $css .= ".flatpickr-day.endRange.nextMonthDay { background: {$selected} !important; border-color: {$selected} !important; color: #ffffff !important; }\n";
        $css .= ".flatpickr-day.flatpickr-disabled,\n";
        $css .= ".flatpickr-day.flatpickr-disabled:hover { color: color-mix(in srgb, {$text} 35%, transparent) !important; background: transparent !important; }\n";
        $css .= ".flatpickr-day.prevMonthDay,\n";
        $css .= ".flatpickr-day.nextMonthDay { color: color-mix(in srgb, {$text} 35%, transparent) !important; }\n";
        $css .= "</style>\n";

        return $css;
    }
}

if (!function_exists('get_theme_colors_root_css')) {
function get_theme_colors_root_css(array $palette, array $formFieldResolved, array $formFieldError, int $formFieldBorderRadius = 6): string {
    $escape = static function (string $value): string {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    };
    $formFieldRadiusPx = max(0, min(40, $formFieldBorderRadius));

    $css = ":root {\n";
    $css .= "  --contest-primary: " . $escape($palette['primary']) . ";\n";
    $css .= "  --contest-secondary: " . $escape($palette['secondary']) . ";\n";
    $css .= "  --contest-tertiary: " . $escape($palette['tertiary']) . ";\n";
    $css .= "  --contest-text: " . $escape($palette['text']) . ";\n";
    $css .= "  --form-field-text: " . $escape($formFieldResolved['text']) . ";\n";
    $placeholderCss = trim((string) ($formFieldResolved['placeholder'] ?? ''));
    if ($placeholderCss !== '') {
        $css .= "  --form-field-placeholder: " . $escape($placeholderCss) . ";\n";
    } else {
        $css .= "  --form-field-placeholder: color-mix(in srgb, var(--form-field-text) 50%, #ffffff);\n";
    }
    $css .= "  --form-field-bg: " . $escape($formFieldResolved['bg']) . ";\n";
    $css .= "  --form-field-border: " . ($formFieldResolved['border'] !== '' ? $escape($formFieldResolved['border']) : 'var(--contest-border)') . ";\n";
    $css .= "  --form-field-focus: " . $escape($formFieldResolved['focus']) . ";\n";
    $css .= "  --form-field-radius: {$formFieldRadiusPx}px;\n";
    $css .= "  --form-field-select2-highlight-bg: " . $escape($formFieldResolved['select2_highlight_bg']) . ";\n";
    $css .= "  --form-field-select2-highlight-text: " . $escape($formFieldResolved['select2_highlight_text']) . ";\n";
    $css .= "  --form-field-calendar-popup-bg: " . $escape($formFieldResolved['calendar_popup_bg']) . ";\n";
    $css .= "  --form-field-calendar-selected: " . $escape($formFieldResolved['calendar_selected']) . ";\n";
    $css .= "  --form-field-calendar-hover-bg: " . $escape($formFieldResolved['calendar_hover_bg']) . ";\n";
    $css .= "  --form-field-calendar-hover-text: " . $escape($formFieldResolved['calendar_hover_text']) . ";\n";
    $css .= "  --form-check-accent: " . $escape($formFieldResolved['checkbox']) . ";\n";
    $css .= "  --form-consent-text: " . $escape((string) ($formFieldResolved['consent_text'] ?? CONFIG_COLOR_BLACK)) . ";\n";
    $css .= "  --form-consent-link: " . $escape($formFieldResolved['consent_link']) . ";\n";
    $css .= "  --form-consent-link-hover: " . $escape($formFieldResolved['consent_link_hover']) . ";\n";
    $css .= "  --form-error-summary-bg: " . $escape((string) $formFieldError['summary_bg']) . ";\n";
    $css .= "  --form-error-summary-text: " . $escape((string) $formFieldError['summary_text']) . ";\n";
    $css .= "  --form-error-summary-border: " . $escape((string) $formFieldError['summary_border']) . ";\n";
    $css .= "  --form-error-summary-shadow: " . $escape((string) $formFieldError['summary_shadow']) . ";\n";
    $css .= "  --form-error-inline-text: " . $escape((string) $formFieldError['inline_text']) . ";\n";
    $css .= "  --form-error-inline-bg: " . $escape((string) $formFieldError['inline_bg']) . ";\n";
    $css .= "  --form-error-inline-shadow: " . $escape((string) $formFieldError['inline_shadow']) . ";\n";
    $css .= "  --contest-surface: #ffffff;\n";
    $css .= "  --contest-surface-alt: color-mix(in srgb, #ffffff 90%, var(--contest-primary));\n";
    $css .= "  --contest-border: color-mix(in srgb, var(--contest-text) 18%, #ffffff);\n";
    $css .= "  --contest-muted: color-mix(in srgb, var(--contest-text) 65%, #ffffff);\n";
    $css .= "  --contest-primary-contrast: #ffffff;\n";
    $css .= "}\n";
    return $css;
}
}

if (!function_exists('get_theme_colors_block')) {
function get_theme_colors_block(): string {
    global $contest_config;

    $colors = $contest_config['colors'] ?? [];
    $palette = [
        'primary' => $colors['primary'] ?? '#38A668',
        'secondary' => $colors['secondary'] ?? '#EA5C11',
        'tertiary' => $colors['tertiary'] ?? CONFIG_COLOR_WHITE,
        'text' => $colors['text'] ?? CONFIG_COLOR_BLACK,
    ];

    $formFieldResolved = resolve_form_field_colors($contest_config);
    $formFieldError = resolve_form_field_error_style($contest_config);
    $formFieldBorderRadius = max(0, min(40, (int) ($contest_config['form_field_border_radius'] ?? 6)));

    $css  = "<style>\n";
    $css .= get_theme_colors_root_css($palette, $formFieldResolved, $formFieldError, $formFieldBorderRadius);
    $css .= "body, p, h1, h2, h3, h4, h5, h6, .text-body, .text-dark, .text-black, .help-content { color: var(--contest-text) !important; }\n";
    $css .= "label, .form-label { color: var(--contest-text) !important; }\n";
    $css .= "#registration-form .form-check-label,\n";
    $css .= "#help-form .form-check-label,\n";
    $css .= ".help-page #help-form .form-check-label { color: var(--form-consent-text) !important; }\n";
    $css .= "body { background-color: var(--contest-surface); }\n";
    $formFieldFont = contest_resolve_font_family((string) ($contest_config['form_field_font'] ?? ''), $contest_config);
    $formFieldFontSize = contest_resolve_font_size((string) ($contest_config['form_field_font_size'] ?? ''));
    // Variabili CSS: placeholder / Select2 / file usano le stesse dimensioni del testo digitato
    if ($formFieldFont !== '' || $formFieldFontSize !== '') {
        $css .= "#registration-form, #help-form, .help-page #help-form {\n";
        if ($formFieldFont !== '') {
            $ff = htmlspecialchars($formFieldFont, ENT_QUOTES, 'UTF-8');
            $css .= "  --form-field-font-family: '{$ff}', sans-serif;\n";
        }
        if ($formFieldFontSize !== '') {
            $fs = htmlspecialchars($formFieldFontSize, ENT_QUOTES, 'UTF-8');
            $css .= "  --form-field-font-size: {$fs};\n";
        }
        $css .= "}\n";
        $rules = [];
        if ($formFieldFont !== '') {
            $ff = htmlspecialchars($formFieldFont, ENT_QUOTES, 'UTF-8');
            $rules[] = "font-family: '{$ff}', sans-serif !important";
        }
        if ($formFieldFontSize !== '') {
            $fs = htmlspecialchars($formFieldFontSize, ENT_QUOTES, 'UTF-8');
            $rules[] = "font-size: {$fs} !important";
        }
        $joined = '  ' . implode('; ', $rules) . ";\n";
        // Non applicare font_family al root #registration-form/#help-form:
        // altrimenti bottone + testo sotto bottone ereditano Gotham dei campi.
        $css .= "#registration-form label, #registration-form .form-label,\n";
        $css .= "#registration-form .form-control, #registration-form .form-select,\n";
        $css .= "#registration-form .select2-container .select2-selection,\n";
        $css .= "#registration-form .select2-container .select2-selection__rendered,\n";
        $css .= "#registration-form .select2-container .select2-selection__placeholder,\n";
        $css .= "#registration-form .select2-results__option,\n";
        $css .= "#registration-form .select2-search--dropdown .select2-search__field,\n";
        $css .= "#registration-form .registration-file-status,\n";
        $css .= "#registration-form input[type=\"file\"].form-control,\n";
        $css .= "#registration-form input[type=\"file\"].form-control::file-selector-button,\n";
        $css .= "#help-form label, #help-form .form-label,\n";
        $css .= "#help-form .form-control, #help-form .form-select,\n";
        $css .= "#help-form .select2-container .select2-selection,\n";
        $css .= "#help-form .select2-container .select2-selection__rendered,\n";
        $css .= "#help-form .select2-container .select2-selection__placeholder,\n";
        $css .= "#help-form .select2-results__option,\n";
        $css .= "#help-form .select2-search--dropdown .select2-search__field {\n";
        $css .= $joined;
        $css .= "}\n";
        if ($formFieldFontSize !== '' || $formFieldFont !== '') {
            $css .= "#registration-form .form-control::placeholder,\n";
            $css .= "#registration-form .form-control::-webkit-input-placeholder,\n";
            $css .= "#registration-form .form-control::-moz-placeholder,\n";
            $css .= "#registration-form .form-control:-ms-input-placeholder,\n";
            $css .= "#help-form .form-control::placeholder,\n";
            $css .= "#help-form .form-control::-webkit-input-placeholder,\n";
            $css .= "#help-form .form-control::-moz-placeholder,\n";
            $css .= "#help-form .form-control:-ms-input-placeholder {\n";
            $css .= $joined;
            $css .= "}\n";
        }
    }
    $css .= "a:not(.btn) { color: var(--contest-secondary); }\n";
    $css .= "a:not(.btn):hover, a:not(.btn):focus { color: var(--contest-tertiary); }\n";
    $css .= "#registration-form .form-check-label a,\n";
    $css .= "#help-form .form-check-label a,\n";
    $css .= ".help-page #help-form .form-check-label a {\n";
    $css .= "  color: var(--form-consent-link) !important;\n";
    $css .= "  text-decoration: underline;\n";
    $css .= "}\n";
    $css .= "#registration-form .form-check-label a:hover,\n";
    $css .= "#registration-form .form-check-label a:focus,\n";
    $css .= "#help-form .form-check-label a:hover,\n";
    $css .= "#help-form .form-check-label a:focus,\n";
    $css .= ".help-page #help-form .form-check-label a:hover,\n";
    $css .= ".help-page #help-form .form-check-label a:focus {\n";
    $css .= "  color: var(--form-consent-link-hover) !important;\n";
    $css .= "}\n";
    $css .= ".text-primary, .text-success, .link-success { color: var(--contest-primary) !important; }\n";
    $css .= ".text-secondary, .text-muted { color: var(--contest-muted) !important; }\n";
    $css .= ".text-danger { color: #dc3545 !important; }\n";
    $css .= "#registration-form .error-message,\n";
    $css .= "#registration-form + .error-message,\n";
    $css .= ".registration-shell #registration-form .error-message {\n";
    $css .= "  color: var(--form-error-inline-text) !important;\n";
    $css .= "  background-color: var(--form-error-inline-bg) !important;\n";
    $css .= "  text-shadow: var(--form-error-inline-shadow);\n";
    $css .= "  display: inline-block;\n";
    $css .= "  padding: 0.1rem 0.35rem;\n";
    $css .= "  border-radius: 4px;\n";
    $css .= "}\n";
    $css .= "#registration-form .error-message:not(.d-block) { background-color: transparent !important; padding: 0; }\n";
    
    $form_container_mode = strtolower(trim($contest_config['form_container']['mode'] ?? 'color'));
    $form_container_image = trim($contest_config['form_container']['image'] ?? '');
    $has_form_image = ($form_container_mode === 'image' && $form_container_image !== '');
    $css .= ".card:not(.registration-card):not(.card.registration-card), .registration-shell .card:not(.registration-card):not(.card.registration-card), .main-container, .main-content { background-color: var(--contest-surface) !important; color: var(--contest-text); border-color: var(--contest-border) !important; }\n";
    if ($has_form_image) {
        $css .= ".registration-card, .card.registration-card, .login-container, .registration-form-container, .help-form-wrapper { color: var(--contest-text) !important; border-color: var(--contest-border) !important; }\n";
    } else {
        $css .= ".registration-card, .card.registration-card, .login-container, .registration-form-container, .help-form-wrapper { color: var(--contest-text) !important; border-color: var(--contest-border) !important; }\n";
    }
    $css .= ".accordion-button:not(.collapsed) { background-color: color-mix(in srgb, var(--contest-primary) 12%, var(--contest-surface)); color: var(--contest-text) !important; border-color: var(--contest-primary) !important; box-shadow: none; }\n";
    $css .= ".accordion-button:focus { box-shadow: 0 0 0 0.15rem color-mix(in srgb, var(--contest-primary) 30%, transparent); border-color: var(--contest-primary); }\n";
    // Campi form: testo scuro su sfondo chiaro (non --contest-text, spesso bianco su card colorata)
    $css .= ".form-control, .form-select, .select2-container--default .select2-selection--single { background-color: var(--form-field-bg); color: var(--form-field-text); border-color: var(--form-field-border); border-radius: var(--form-field-radius, 6px); -webkit-text-fill-color: var(--form-field-text); caret-color: var(--form-field-text); }\n";
    $css .= ".form-control::placeholder, textarea.form-control::placeholder { color: var(--form-field-placeholder); opacity: 1; }\n";
    $css .= ".select2-container--default .select2-selection--single .select2-selection__placeholder { color: var(--form-field-placeholder); }\n";
    $css .= ".form-control:-webkit-autofill, .form-control:-webkit-autofill:hover, textarea.form-control:-webkit-autofill { -webkit-text-fill-color: var(--form-field-text) !important; -webkit-box-shadow: 0 0 0 1000px var(--form-field-bg) inset !important; box-shadow: 0 0 0 1000px var(--form-field-bg) inset !important; caret-color: var(--form-field-text) !important; }\n";
    $css .= ".form-control:-webkit-autofill:focus, .form-control:-webkit-autofill:focus-visible, textarea.form-control:-webkit-autofill:focus { -webkit-text-fill-color: var(--form-field-text) !important; border-color: var(--form-field-focus) !important; -webkit-box-shadow: 0 0 0 1000px var(--form-field-bg) inset, 0 0 0 0.2rem color-mix(in srgb, var(--form-field-focus) 25%, transparent) !important; box-shadow: 0 0 0 1000px var(--form-field-bg) inset, 0 0 0 0.2rem color-mix(in srgb, var(--form-field-focus) 25%, transparent) !important; caret-color: var(--form-field-text) !important; }\n";
    $css .= ".form-control:focus, .form-select:focus, .select2-container--default.select2-container--focus .select2-selection--single { border-color: var(--form-field-focus) !important; box-shadow: 0 0 0 0.2rem color-mix(in srgb, var(--form-field-focus) 25%, transparent) !important; }\n";
    $css .= "input[type=\"date\"].form-control,\n";
    $css .= "input[type=\"datetime-local\"].form-control,\n";
    $css .= "input[type=\"month\"].form-control,\n";
    $css .= "input[type=\"week\"].form-control,\n";
    $css .= "input[type=\"time\"].form-control {\n";
    $css .= "  accent-color: var(--form-field-focus);\n";
    $css .= "}\n";
    $css .= ".form-control[type=\"file\"],\n";
    $css .= "input[type=\"file\"].form-control {\n";
    $css .= "  display: block !important;\n";
    $css .= "  width: 100% !important;\n";
    $css .= "  max-width: 100% !important;\n";
    $css .= "  box-sizing: border-box !important;\n";
    $css .= "  background-color: var(--form-field-bg) !important;\n";
    $css .= "  border-color: var(--form-field-border) !important;\n";
    $css .= "  border-radius: var(--form-field-radius, 6px) !important;\n";
    $css .= "  overflow: hidden !important;\n";
    $css .= "  color: var(--form-field-text) !important;\n";
    $css .= "  -webkit-text-fill-color: var(--form-field-text) !important;\n";
    $css .= "  line-height: 1.5 !important;\n";
    $css .= "  min-height: calc(1.5em + 0.75rem + 2px) !important;\n";
    $css .= "  padding: 0 0.75rem 0 0 !important;\n";
    $css .= "}\n";
    $css .= ".form-control[type=\"file\"]::file-selector-button,\n";
    $css .= "input[type=\"file\"].form-control::file-selector-button,\n";
    $css .= ".form-control[type=\"file\"]::-webkit-file-upload-button,\n";
    $css .= "input[type=\"file\"].form-control::-webkit-file-upload-button {\n";
    $css .= "  background-color: color-mix(in srgb, var(--form-field-border) 45%, var(--form-field-bg)) !important;\n";
    $css .= "  color: var(--form-field-text) !important;\n";
    $css .= "  -webkit-text-fill-color: var(--form-field-text) !important;\n";
    $css .= "  border: 0 !important;\n";
    $css .= "  border-inline-end: 1px solid var(--form-field-border) !important;\n";
    $css .= "  border-radius: 0 !important;\n";
    $css .= "  box-sizing: border-box !important;\n";
    $css .= "  height: 100% !important;\n";
    $css .= "  margin: 0 !important;\n";
    $css .= "  padding: 0.375rem 0.75rem !important;\n";
    $css .= "  cursor: pointer !important;\n";
    $css .= "}\n";
    $css .= "body.form-style-placeholder .form-control[type=\"file\"],\n";
    $css .= "body.form-style-placeholder input[type=\"file\"].form-control {\n";
    $css .= "  min-height: 42px !important;\n";
    $css .= "  height: 42px !important;\n";
    $css .= "  padding: 0 0.85rem 0 0 !important;\n";
    $css .= "  font-size: var(--form-field-font-size, 0.95rem) !important;\n";
    $css .= "  line-height: 1.4 !important;\n";
    $css .= "  border-radius: var(--form-field-radius, 6px) !important;\n";
    $css .= "}\n";
    $css .= "body.form-style-placeholder .form-control[type=\"file\"]::file-selector-button,\n";
    $css .= "body.form-style-placeholder input[type=\"file\"].form-control::file-selector-button,\n";
    $css .= "body.form-style-placeholder .form-control[type=\"file\"]::-webkit-file-upload-button,\n";
    $css .= "body.form-style-placeholder input[type=\"file\"].form-control::-webkit-file-upload-button {\n";
    $css .= "  height: 42px !important;\n";
    $css .= "  min-height: 42px !important;\n";
    $css .= "  padding: 0 0.85rem !important;\n";
    $css .= "  line-height: 42px !important;\n";
    $css .= "}\n";
    $css .= ".form-control[type=\"file\"]:focus,\n";
    $css .= ".form-control[type=\"file\"]:focus-visible,\n";
    $css .= "input[type=\"file\"].form-control:focus,\n";
    $css .= "input[type=\"file\"].form-control:focus-visible {\n";
    $css .= "  border-color: var(--form-field-focus) !important;\n";
    $css .= "  box-shadow: 0 0 0 0.2rem color-mix(in srgb, var(--form-field-focus) 25%, transparent) !important;\n";
    $css .= "  color: var(--form-field-text) !important;\n";
    $css .= "  -webkit-text-fill-color: var(--form-field-text) !important;\n";
    $css .= "  background-color: var(--form-field-bg) !important;\n";
    $css .= "}\n";
    $css .= ".form-control.is-invalid, .form-select.is-invalid, input.is-invalid, select.is-invalid, textarea.is-invalid { border-color: #dc3545 !important; box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25) !important; }\n";
    $css .= ".select2-container--default .select2-results__option--highlighted, .select2-container--default .select2-results__option--highlighted[aria-selected], .select2-container--default .select2-results__option[aria-selected='true'], .select2-container--default .select2-results__option:hover { background-color: var(--form-field-select2-highlight-bg) !important; color: var(--form-field-select2-highlight-text) !important; }\n";
    $css .= ".form-check-input { accent-color: var(--form-check-accent); border-color: var(--contest-border); }\n";
    $css .= ".form-check-input:checked { background-color: var(--form-check-accent) !important; border-color: var(--form-check-accent) !important; }\n";
    $css .= ".form-check-input:focus { border-color: var(--form-check-accent) !important; box-shadow: 0 0 0 0.15rem color-mix(in srgb, var(--form-check-accent) 30%, transparent); }\n";
    // Pulsanti: solo get_button_style_block() (Bottoni concorso), evitare verde palette su .btn-primary
    $css .= ".alert-success, .alert-primary { background-color: color-mix(in srgb, var(--contest-primary) 15%, var(--contest-surface)); border-color: color-mix(in srgb, var(--contest-primary) 40%, var(--contest-surface)); color: var(--contest-text); }\n";
    $css .= ".alert-danger, #error-messages { background-color: var(--form-error-summary-bg) !important; border-color: var(--form-error-summary-border) !important; color: var(--form-error-summary-text) !important; text-shadow: var(--form-error-summary-shadow); }\n";
    $css .= "#error-messages ul, #error-messages li { color: inherit !important; text-shadow: inherit; }\n";
    $css .= ".alert-warning, .alert-secondary { background-color: #fff3cd; border-color: #ffecb5; color: var(--contest-text); }\n";
    $css .= ".swal2-confirm.swal2-styled { background-color: var(--contest-primary) !important; border-color: var(--contest-primary) !important; color: var(--contest-primary-contrast) !important; }\n";
    $css .= ".swal2-confirm.swal2-styled:hover, .swal2-confirm.swal2-styled:focus { filter: brightness(0.92); }\n";
    // Footer: colori testo/link risolti da resolve_footer_style() in get_footer_style_block()

    $css .= "</style>\n";

    return $css;
}
}

if (!function_exists('config_form_container_hex_to_rgba')) {
function config_form_container_hex_to_rgba(string $hex, int $opacity): string {
    $hex = ltrim($hex, '#');
    if (strlen($hex) === 3) {
        $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
    }
    if (strlen($hex) !== 6) {
        return 'rgba(255, 255, 255, ' . ($opacity / 100) . ')';
    }
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    return 'rgba(' . $r . ', ' . $g . ', ' . $b . ', ' . ($opacity / 100) . ')';
}
}

if (!function_exists('config_form_container_normalize_path')) {
function config_form_container_normalize_path(string $path, bool $relative_from_manager): string {
    $path = trim($path);
    if ($path === '') {
        return '';
    }
    if (str_starts_with($path, CONFIG_SCHEME_HTTP) || str_starts_with($path, CONFIG_SCHEME_HTTPS) || str_starts_with($path, '//')) {
        $path = asset_path_to_relative($path);
    }
    $path = ltrim($path, '/');
    return $relative_from_manager ? '../' . $path : $path;
}
}

if (!function_exists('config_form_container_append_style')) {
function config_form_container_append_style(string &$css, string $selectors, array $style, bool $relative_from_manager, bool $includeLoginRadius = false): void {
    $mode = strtolower(trim($style['mode'] ?? 'color'));
    $color = $style['color'] ?? CONFIG_COLOR_WHITE;
    $opacity = isset($style['opacity']) ? max(0, min(100, (int) $style['opacity'])) : 100;
    $image = trim($style['image'] ?? '');
    $isTransparent = ($mode !== 'image' || $image === '')
        && ($opacity <= 0 || strtolower(trim((string) $color)) === 'transparent');

    if ($isTransparent) {
        $css .= "{$selectors} { background-color: transparent !important; background-image: none !important; border: none !important; box-shadow: none !important; --bs-card-bg: transparent; }\n";
        $css .= "{$selectors}.bg-white { background-color: transparent !important; background-image: none !important; }\n";
        $css .= "{$selectors}::before { content: none !important; background-image: none !important; }\n";
    } elseif ($mode === 'image' && $image !== '') {
        $resolved = htmlspecialchars(config_form_container_normalize_path($image, $relative_from_manager), ENT_QUOTES);
        $alpha = max(0, min(1, $opacity / 100));
        $css .= "{$selectors} { position: relative; z-index: 0; overflow: hidden; background-color: transparent !important; background-image: none !important; }\n";
        $css .= "{$selectors}::before { content: ''; position: absolute; inset: 0; background-image: url('{$resolved}') !important; background-size: cover !important; background-position: center !important; background-repeat: no-repeat !important; opacity: {$alpha}; z-index: -1 !important; pointer-events: none !important; border-radius: inherit; }\n";
        $css .= "{$selectors} > * { position: relative; z-index: 1; }\n";
        $css .= "{$selectors} a, {$selectors} button, {$selectors} input, {$selectors} select, {$selectors} textarea, {$selectors} [role='button'], {$selectors} [tabindex], {$selectors} [onclick] { position: relative !important; z-index: 2 !important; pointer-events: auto !important; }\n";
    } else {
        $final_color = config_form_container_hex_to_rgba($color, $opacity);
        $color_safe = htmlspecialchars($final_color, ENT_QUOTES);
        $css .= "{$selectors} { background-color: {$color_safe} !important; background-image: none !important; }\n";
        $css .= "{$selectors}.bg-white { background-color: {$color_safe} !important; background-image: none !important; }\n";
        $css .= "{$selectors}::before { content: none !important; background-image: none !important; }\n";
    }

    $radiusCfg = array_merge(
        ['top' => 0, 'right' => 0, 'bottom' => 12, 'left' => 12],
        isset($style['radius']) && is_array($style['radius']) ? $style['radius'] : []
    );
    $rTop = max(0, min(80, (int) $radiusCfg['top']));
    $rRight = max(0, min(80, (int) $radiusCfg['right']));
    $rBottom = max(0, min(80, (int) $radiusCfg['bottom']));
    $rLeft = max(0, min(80, (int) $radiusCfg['left']));
    $radiusSelectors = $includeLoginRadius ? $selectors : str_replace('.login-container, ', '', $selectors);
    $css .= "{$radiusSelectors} {\n";
    $css .= "  border-top-left-radius: {$rTop}px !important;\n";
    $css .= "  border-top-right-radius: {$rRight}px !important;\n";
    $css .= "  border-bottom-right-radius: {$rBottom}px !important;\n";
    $css .= "  border-bottom-left-radius: {$rLeft}px !important;\n";
    $css .= "}\n";
}
}

if (!function_exists('get_form_container_style_block')) {
function get_form_container_style_block(bool $relative_from_manager = false): string {
    global $contest_config;

    $form = $contest_config['form_container'] ?? [];
    $css = "<style>\n";
    config_form_container_append_style($css, '.login-container, .registration-form-container', $form, $relative_from_manager, true);

    $pageStyles = is_array($contest_config['public_page_containers'] ?? null) ? $contest_config['public_page_containers'] : [];
    $pageSelectors = [
        'registration' => '.public-page-container--registration, .card.public-page-container--registration',
        'help' => '.public-page-container--help, .card.public-page-container--help',
        'waiting_page' => '.public-page-container--waiting-page, .card.public-page-container--waiting-page',
        'participation_page' => '.public-page-container--participation-page, .card.public-page-container--participation-page',
        'waiting_page_ended' => '.public-page-container--waiting-page-ended, .card.public-page-container--waiting-page-ended',
        'thankyou_instantwin_win_page' => '.public-page-container--thankyou-win, .card.public-page-container--thankyou-win',
        'thankyou_instantwin_loss_page' => '.public-page-container--thankyou-loss, .card.public-page-container--thankyou-loss',
        'thankyou_instantwin_quiz_failed_page' => '.public-page-container--thankyou-quiz-failed, .card.public-page-container--thankyou-quiz-failed',
        'thankyou_estrazione_page' => '.public-page-container--thankyou-estrazione, .card.public-page-container--thankyou-estrazione',
        'thankyou_estrazione_quiz_failed_page' => '.public-page-container--thankyou-estrazione-quiz-failed, .card.public-page-container--thankyou-estrazione-quiz-failed',
        'quiz_page' => '.public-page-container--quiz-page, .card.public-page-container--quiz-page',
        'quiz_choice_page' => '.public-page-container--quiz-choice-page, .card.public-page-container--quiz-choice-page',
    ];
    foreach ($pageSelectors as $pageKey => $pageSelector) {
        if (isset($pageStyles[$pageKey]) && is_array($pageStyles[$pageKey])) {
            config_form_container_append_style($css, $pageSelector, $pageStyles[$pageKey], $relative_from_manager);
        }
    }

    $css .= "</style>\n";
    return $css;
}
}

/**
 * Classi per CTA waiting/thankyou: colori da Bottoni (JSON) tramite .contest-block-cta--* in get_button_style_block().
 *
 * @return array{class: string, style: string}
 */
if (!function_exists('contest_cta_button_classes_and_style')) {
function contest_cta_button_classes_and_style(array $contest_config, string $variant): array {
    $vk = $variant === 'secondary' ? 'secondary' : 'primary';
    $bootstrap = $vk === 'secondary' ? 'btn-secondary' : 'btn-primary';
    $class = 'btn ' . $bootstrap . ' btn-lg fw-bold text-uppercase contest-block-cta contest-block-cta--' . $vk;
    return ['class' => $class, 'style' => ''];
}
}


/**
 * Testo secondario registration (0.7rem): sotto un campo form (es. fidelity / scontrino / codice).
 * $colorHex: #RRGGBB oppure vuoto (= palette Testo / --contest-text).
 */
if (!function_exists('render_registration_hint_text_html')) {
function render_registration_hint_text_html(string $raw, string $align = 'center', string $id = '', string $marginClass = 'mt-2', string $colorHex = ''): string {
    $raw = trim($raw);
    if ($raw === '') {
        return '';
    }
    $alignClass = 'text-center';
    if ($align === 'start') {
        $alignClass = 'text-start';
    } elseif ($align === 'end') {
        $alignClass = 'text-end';
    }
    $idAttr = $id !== '' ? ' id="' . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . '"' : '';
    $marginClass = trim($marginClass) !== '' ? trim($marginClass) : 'mt-2';
    $safe = htmlspecialchars($raw, ENT_QUOTES, 'UTF-8');
    $with_br = nl2br($safe, false);
    $colorHex = strtolower(trim($colorHex));
    $colorCss = preg_match(CONFIG_REGEX_HEX6, $colorHex)
        ? htmlspecialchars($colorHex, ENT_QUOTES, 'UTF-8')
        : 'var(--contest-text)';
    $hintVar = preg_match(CONFIG_REGEX_HEX6, $colorHex)
        ? '--registration-hint-color: ' . htmlspecialchars($colorHex, ENT_QUOTES, 'UTF-8') . '; '
        : '';

    $hintTag = 'd' . 'iv';
    return '<' . $hintTag . ' class="registration-hint-text mb-0 ' . $alignClass . ' ' . htmlspecialchars($marginClass, ENT_QUOTES, 'UTF-8') . '"' . $idAttr . ' style="' . $hintVar . 'color: ' . $colorCss . '; font-size: 0.7rem; line-height: 1.4;">' . $with_br . '</' . $hintTag . '>';
}
}
/**
 * Testo opzionale sotto i bottoni (registration / pages.form_page e pagina partecipazione).
 */
if (!function_exists('render_registration_page_button_footer_html')) {
function render_registration_page_button_footer_html(array $form_page_config, array $contest_config): string {
    $raw = trim((string) ($form_page_config['button_footer_text'] ?? ''));
    if ($raw === '') {
        return '';
    }
    $replace = static function (string $value) use ($contest_config): string {
        $cn = (string) ($contest_config['name'] ?? 'Concorso');
        $sd = (string) ($contest_config['start_date_formatted'] ?? ($contest_config['start_date'] ?? ''));
        $ed = (string) ($contest_config['end_date_formatted'] ?? ($contest_config['end_date'] ?? ''));
        $ms = number_format((float) ($contest_config['min_spend'] ?? 0), 2, ',', '.');
        $rl = (string) ($contest_config['regolamento_link'] ?? '#');
        return contest_replace_placeholders($value, [
            CONFIG_PH_CONTEST_NAME => $cn,
            CONFIG_PH_START_DATE => $sd,
            CONFIG_PH_END_DATE => $ed,
            CONFIG_PH_MIN_SPEND => $ms,
            CONFIG_PH_REGOLAMENTO => $rl,
        ]);
    };
    $safe = htmlspecialchars($replace($raw), ENT_QUOTES, 'UTF-8');
    $with_br = nl2br($safe, false);
    $colorHex = strtolower(trim((string) ($form_page_config['button_footer_text_color'] ?? '')));
    $colorCss = preg_match(CONFIG_REGEX_HEX6, $colorHex)
        ? htmlspecialchars($colorHex, ENT_QUOTES, 'UTF-8')
        : 'var(--contest-text)';
    $footerVar = preg_match(CONFIG_REGEX_HEX6, $colorHex)
        ? '--registration-button-footer-color: ' . htmlspecialchars($colorHex, ENT_QUOTES, 'UTF-8') . '; '
        : '';
    $fontFamily = contest_resolve_font_family(
        (string) ($form_page_config['button_footer_text_font'] ?? ''),
        $contest_config
    );
    $fontCss = $fontFamily !== ''
        ? 'font-family: ' . htmlspecialchars($fontFamily, ENT_QUOTES, 'UTF-8') . ', sans-serif; '
        : '';

    $footerTag = 'd' . 'iv';
    return '<' . $footerTag . ' class="registration-button-footer-text mt-3 text-center" style="' . $footerVar . $fontCss . 'color: ' . $colorCss . '; font-size: 0.7rem; line-height: 1.4;">' . $with_br . '</' . $footerTag . '>';
}
}

if (!function_exists('layout_waiting_page_html')) {
    /**
     * Sépare le bouton .btn du paragraphe de texte (éditeur Summernote sur une seule ligne).
     */
    function layout_waiting_page_html(string $html): string
    {
        $html = trim($html);
        if ($html === '' || stripos($html, '<a') === false) {
            return $html;
        }
        if (strpos($html, '&lt;') !== false) {
            $html = htmlspecialchars_decode($html, ENT_QUOTES);
        }

        $html = preg_replace_callback(
            '/<p(\s[^>]*)>(.*?)<a(\s+[^>]*\bclass\s*=\s*["\'][^"\']*\bbtn\b[^"\']*["\'][^>]*)>(.*?)<\/a>\s*<\/p>/is',
            static function (array $matches): string {
                $pAttrs = $matches[1] ?? '';
                $before = rtrim($matches[2] ?? '');
                $aAttrs = $matches[3] ?? '';
                $label = $matches[4] ?? '';
                $btn = '<p class="text-center mt-3 mb-0"><a' . $aAttrs . '>' . $label . '</a></p>';
                if (trim(strip_tags($before)) === '') {
                    return $btn;
                }

                return '<p' . $pAttrs . '>' . $before . '</p>' . $btn;
            },
            $html
        ) ?? $html;

        return $html;
    }
}

if (!function_exists('config_page_allowed_tags')) {
function config_page_allowed_tags(): string {
    return '<p><h1><h2><h3><h4><h5><h6><strong><em><u><b><i><br><a><ul><ol><li><span><div><img><font>';
}
}

if (!function_exists('config_resolve_prize_type_from_id')) {
function config_resolve_prize_type_from_id(PDO $pdo, int $prize_id): string {
    if ($prize_id <= 0) {
        return '';
    }
    try {
        $stmt = $pdo->prepare('SELECT tipo_premio FROM premi WHERE id = ? LIMIT 1');
        if ($stmt && $stmt->execute([$prize_id])) {
            $tipo = trim((string) $stmt->fetchColumn());
            return $tipo !== '' ? $tipo : 'Premio';
        }
    } catch (Throwable $e) {
        return '';
    }

    return '';
}
}

if (!function_exists('config_page_replace_placeholders')) {
/**
 * Sostituisce i placeholder {{…}}. Include anche la forma URL-encoded
 * (%7B%7B…%7D%7D) tipica degli href dopo Summernote/browser.
 *
 * @param array<string, string> $replacements
 */
function config_page_replace_placeholders(string $value, array $replacements): string {
    $map = [
        CONFIG_PH_CONTEST_NAME => (string) ($replacements['contest_name'] ?? ''),
        CONFIG_PH_START_DATE => (string) ($replacements['start_date'] ?? ''),
        CONFIG_PH_END_DATE => (string) ($replacements['end_date'] ?? ''),
        CONFIG_PH_MIN_SPEND => (string) ($replacements['min_spend'] ?? ''),
        CONFIG_PH_REGOLAMENTO => (string) ($replacements['regolamento_link'] ?? ''),
        '{{PRIZE_WIN_AMOUNT}}' => (string) ($replacements['prize_win_amount'] ?? ''),
        CONFIG_PH_PRIZE_TYPE => (string) ($replacements['prize_type'] ?? ''),
    ];
    $value = contest_replace_placeholders($value, $map);
    return contest_replace_placeholders($value, [
        '{{THANKYOU_IW_VARIANT_HEADLINE}}' => '',
        '{{THANKYOU_IW_VARIANT_LEAD}}' => '',
    ]);
}
}

if (!function_exists('contest_replace_placeholders')) {
/**
 * @param array<string, string> $map placeholder letterale => valore
 */
function contest_replace_placeholders(string $html, array $map): string
{
    if ($html === '' || $map === []) {
        return $html;
    }
    $search = [];
    $replace = [];
    foreach ($map as $placeholder => $value) {
        $placeholder = (string) $placeholder;
        if ($placeholder === '') {
            continue;
        }
        $value = (string) $value;
        $search[] = $placeholder;
        $replace[] = $value;
        // href="{{…}}" spesso salvato URL-encoded dal browser/Summernote
        $encoded = rawurlencode($placeholder);
        if ($encoded !== $placeholder) {
            $search[] = $encoded;
            $replace[] = $value;
        }
    }
    return str_replace($search, $replace, $html);
}
}

if (!function_exists('contest_normalize_urlencoded_placeholders')) {
/**
 * Ripristina {{PLACEHOLDER}} da %7B%7BPLACEHOLDER%7D%7D (href Summernote).
 */
function contest_normalize_urlencoded_placeholders(string $html): string
{
    if ($html === '' || stripos($html, '%7B%7B') === false) {
        return $html;
    }
    return (string) preg_replace_callback(
        '/%7B%7B([A-Z0-9_]+)%7D%7D/i',
        static function (array $m): string {
            return '{{' . strtoupper($m[1]) . '}}';
        },
        $html
    );
}
}

if (!function_exists('config_page_normalize_regolamento_button')) {
function config_page_normalize_regolamento_button(string $html, bool $normalize_regolamento): string {
    if (!$normalize_regolamento || $html === '' || stripos($html, '<a') === false || stripos($html, 'REGOLAMENTO') === false) {
        return $html;
    }
    $updated = preg_replace_callback(
        '/<a\b([^>]*)>(.*?)<\/a>/is',
        static function (array $matches): string {
            $attrs = $matches[1] ?? '';
            $inner = $matches[2] ?? '';
            $plainText = trim(strip_tags($inner));
            if (mb_stripos($plainText, 'REGOLAMENTO', 0, 'UTF-8') === false) {
                return $matches[0];
            }
            if (preg_match('/\bclass\s*=\s*([\'\"])(.*?)\1/i', $attrs, $classMatch)) {
                $quote = $classMatch[1];
                $classValue = $classMatch[2];
                $classValue = preg_replace('/\bbtn-success\b/i', 'btn-primary', $classValue);
                if (!preg_match('/\bbtn-regolamento-thankyou\b/i', $classValue)) {
                    $classValue .= ' btn-regolamento-thankyou';
                }
                $classAttr = 'class=' . $quote . trim($classValue) . $quote;
                $attrs = preg_replace('/\bclass\s*=\s*([\'\"])(.*?)\1/i', $classAttr, $attrs, 1) ?? $attrs;
            }
            return '<a' . $attrs . '>' . $inner . '</a>';
        },
        $html
    );
    return is_string($updated) ? $updated : $html;
}
}

if (!function_exists('config_page_format_html')) {
function config_page_format_html(string $html, array $options, array $replacements): string {
    if ($html !== '' && (strpos($html, '&lt;') !== false || strpos($html, '&gt;') !== false)) {
        $html = htmlspecialchars_decode($html, ENT_QUOTES);
    }
    $html = config_page_replace_placeholders($html, $replacements);
    if (!empty($options['layout_waiting_html']) && function_exists('layout_waiting_page_html')) {
        $html = layout_waiting_page_html($html);
    }
    return config_page_normalize_regolamento_button($html, !empty($options['normalize_regolamento_button']));
}
}

if (!function_exists('config_page_hex2rgba')) {
function config_page_hex2rgba(string $hex, float $alpha = 1): string {
    $hex = ltrim(trim($hex), '#');
    if (strlen($hex) === 3) {
        $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
    }
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    return "rgba({$r}, {$g}, {$b}, {$alpha})";
}
}

if (!function_exists('config_page_build_container_style')) {
function config_page_build_container_style(array $container): string {
    $container_style = '';
    $background = $container['background'] ?? [];
    if (is_array($background)) {
        $bg_mode = $background['mode'] ?? 'color';
        $bg_opacity = max(0, min(100, (int) ($background['opacity'] ?? 100)));
        if ($bg_mode === 'color' && !empty($background['color'])) {
            $container_style .= CONFIG_CSS_BG_COLOR_PREFIX_NOSPACE . config_page_hex2rgba((string) $background['color'], $bg_opacity / 100) . '; ';
        } elseif ($bg_mode === 'image' && !empty($background['image'])) {
            $bg_image_url = htmlspecialchars((string) $background['image'], ENT_QUOTES);
            $container_style .= "background-image: url('{$bg_image_url}'); background-size: cover; background-position: center; ";
        }
    }

    $style = is_array($container['style'] ?? null) ? $container['style'] : [];
    $margin = array_merge(['top' => 0, 'right' => 0, 'bottom' => 16, 'left' => 0], (array) ($style['margin'] ?? []));
    $container_style .= sprintf(
        CONFIG_CSS_MARGIN_BOX,
        max(0, (int) ($margin['top'] ?? 0)),
        max(0, (int) ($margin['right'] ?? 0)),
        max(0, (int) ($margin['bottom'] ?? 0)),
        max(0, (int) ($margin['left'] ?? 0))
    );

    $radius = array_merge(['top' => 8, 'right' => 8, 'bottom' => 8, 'left' => 8], (array) ($style['radius'] ?? []));
    $container_style .= sprintf(
        CONFIG_CSS_RADIUS_BOX,
        max(0, (int) ($radius['top'] ?? 0)),
        max(0, (int) ($radius['right'] ?? 0)),
        max(0, (int) ($radius['bottom'] ?? 0)),
        max(0, (int) ($radius['left'] ?? 0))
    );

    $align = in_array(($style['align'] ?? 'center'), ['left', 'center', 'right', 'justify'], true) ? $style['align'] : 'center';
    $container_style .= "text-align: {$align}; ";
    if (isset($style['padding']) && is_array($style['padding'])) {
        $padding = $style['padding'];
        $container_style .= 'padding-top: ' . (int) ($padding['top'] ?? 20) . 'px; padding-bottom: ' . (int) ($padding['bottom'] ?? 20) . 'px; ';
    }

    return $container_style;
}
}

if (!function_exists('config_page_render_text_image_button_block')) {
function config_page_render_text_image_button_block(array $block, array $context): void {
    $allowed_tags = $context['allowed_tags'];
    $content_class_attr = $context['content_class_attr'];
    $format_html = $context['format_html'];
    $replacements = $context['replacements'];
    $contest_config = $context['contest_config'];
    $block_type = $block['type'] ?? 'text';

    if ($block_type === 'text') {
        $content = $format_html((string) ($block['content'] ?? ''));
        ?>
        <div class="mb-0 fs-6<?php echo htmlspecialchars($content_class_attr, ENT_QUOTES); ?>">
            <?php echo strip_tags($content, $allowed_tags); ?>
        </div>
        <?php
        return;
    }
    if ($block_type === 'image' && !empty($block['src'])) {
        $image_src = htmlspecialchars((string) $block['src'], ENT_QUOTES);
        $image_alt = htmlspecialchars((string) ($block['alt'] ?? ''), ENT_QUOTES);
        $image_width = htmlspecialchars((string) ($block['width'] ?? '100%'), ENT_QUOTES);
        ?>
        <div class="mb-3">
            <img src="<?php echo $image_src; ?>" alt="<?php echo $image_alt; ?>" class="img-fluid" style="max-width: <?php echo $image_width; ?>;">
        </div>
        <?php
        return;
    }
    if ($block_type === 'button') {
        $button_text = htmlspecialchars((string) ($block['text'] ?? ''), ENT_QUOTES);
        $button_url = htmlspecialchars(config_page_replace_placeholders((string) ($block['url'] ?? '#'), $replacements), ENT_QUOTES);
        $button_target = htmlspecialchars((string) ($block['target'] ?? '_self'), ENT_QUOTES);
        $variant = strtolower((string) ($block['style']['type'] ?? 'primary'));
        $variant = $variant === 'secondary' ? 'secondary' : 'primary';
        $cta = contest_cta_button_classes_and_style($contest_config, $variant);
        ?>
        <div class="mb-2">
            <a href="<?php echo $button_url; ?>" class="<?php echo htmlspecialchars($cta['class'], ENT_QUOTES, 'UTF-8'); ?>"<?php echo $cta['style'] !== '' ? ' style="' . $cta['style'] . '"' : ''; ?> target="<?php echo $button_target; ?>" rel="noopener noreferrer">
                <?php echo $button_text; ?>
            </a>
        </div>
        <?php
    }
}
}

if (!function_exists('config_page_render_blocks')) {
function config_page_render_blocks(array $blocks, array $context): void {
    usort($blocks, static fn ($a, $b) => ($a['order'] ?? 0) - ($b['order'] ?? 0));
    foreach ($blocks as $block) {
        if (!is_array($block)) {
            continue;
        }
        config_page_render_text_image_button_block($block, $context);
    }
}
}

if (!function_exists('config_page_render_container')) {
function config_page_render_container(array $container, array $context): void {
    $allowed_tags = $context['allowed_tags'];
    $content_class_attr = $context['content_class_attr'];
    $format_html = $context['format_html'];
    $container_style = config_page_build_container_style($container);
    ?>
    <div<?php echo $container_style ? ' style="' . htmlspecialchars($container_style, ENT_QUOTES) . '"' : ''; ?>>
        <?php
        if (isset($container['html']) && trim((string) $container['html']) !== '') {
            $container_html = $format_html(trim((string) $container['html']));
            ?>
            <div class="mb-0<?php echo htmlspecialchars($content_class_attr, ENT_QUOTES); ?>">
                <?php echo strip_tags($container_html, $allowed_tags); ?>
            </div>
            <?php
        } elseif (isset($container['blocks']) && is_array($container['blocks'])) {
            config_page_render_blocks($container['blocks'], $context);
        }
        if (!empty($context['append_button_footer_html'])) {
            echo $context['append_button_footer_html'];
        }
        ?>
    </div>
    <?php
}
}

if (!function_exists('render_config_page_container_content')) {
function render_config_page_container_content(array $page_config, array $contest_config, array $options = []): string {
    $replacements = [
        'contest_name' => htmlspecialchars($contest_config['name'] ?? 'Concorso', ENT_QUOTES),
        'start_date' => htmlspecialchars($contest_config['start_date_formatted'] ?? ($contest_config['start_date'] ?? ''), ENT_QUOTES),
        'end_date' => htmlspecialchars($contest_config['end_date_formatted'] ?? ($contest_config['end_date'] ?? ''), ENT_QUOTES),
        'min_spend' => number_format((float) ($contest_config['min_spend'] ?? 0), 2, ',', '.'),
        'regolamento_link' => htmlspecialchars($contest_config['regolamento_link'] ?? '#', ENT_QUOTES),
        'prize_win_amount' => number_format((float) ($options['prize_amount'] ?? 0), 2, ',', '.'),
        'prize_type' => htmlspecialchars(trim((string) ($options['prize_type'] ?? '')), ENT_QUOTES),
    ];
    $content_class = trim((string) ($options['content_class'] ?? 'waiting-page-content'));
    $content_class_attr = $content_class !== '' ? ' ' . $content_class : '';
    $allowed_tags = config_page_allowed_tags();
    $format_html = static function (string $html) use ($options, $replacements): string {
        return config_page_format_html($html, $options, $replacements);
    };
    $context = [
        'allowed_tags' => $allowed_tags,
        'content_class_attr' => $content_class_attr,
        'format_html' => $format_html,
        'replacements' => $replacements,
        'contest_config' => $contest_config,
    ];

    $page_html = isset($page_config['html']) ? trim((string) $page_config['html']) : '';
    $page_align = strtolower(trim((string) ($page_config['align'] ?? 'center')));
    if (!in_array($page_align, ['left', 'center', 'right', 'justify'], true)) {
        $page_align = 'center';
    }
    $containers = is_array($page_config) ? ($page_config['containers'] ?? []) : [];
    $buttonFooterHtml = trim((string) ($options['button_footer_html'] ?? ''));
    $buttonFooterIndex = null;
    if ($buttonFooterHtml !== '' && $page_html === '' && !empty($containers)) {
        $lastValidIndex = null;
        foreach ($containers as $index => $container) {
            if (!is_array($container)) {
                continue;
            }
            $lastValidIndex = $index;
            $html = (string) ($container['html'] ?? '');
            $hasButton = stripos($html, 'btn') !== false;
            if (!$hasButton && is_array($container['blocks'] ?? null)) {
                foreach ($container['blocks'] as $block) {
                    if (!is_array($block)) {
                        continue;
                    }
                    if (($block['type'] ?? '') === 'button' || stripos((string) ($block['content'] ?? ''), 'btn') !== false) {
                        $hasButton = true;
                        break;
                    }
                }
            }
            if ($hasButton) {
                $buttonFooterIndex = $index;
            }
        }
        if ($buttonFooterIndex === null) {
            $buttonFooterIndex = $lastValidIndex;
        }
    }

    ob_start();
    if ($page_html !== '') {
        $page_html = $format_html($page_html);
        ?>
        <div class="mb-0 fs-6<?php echo htmlspecialchars($content_class_attr, ENT_QUOTES); ?>" style="text-align: <?php echo htmlspecialchars($page_align, ENT_QUOTES); ?>;">
            <?php echo strip_tags($page_html, $allowed_tags); ?>
        </div>
        <?php
        if ($buttonFooterHtml !== '') {
            echo $buttonFooterHtml;
        }
    } elseif (!empty($containers)) {
        foreach ($containers as $index => $container) {
            if (!is_array($container)) {
                continue;
            }
            $containerContext = $context;
            if ($buttonFooterHtml !== '' && $index === $buttonFooterIndex) {
                $containerContext['append_button_footer_html'] = $buttonFooterHtml;
            }
            config_page_render_container($container, $containerContext);
        }
    } elseif ($buttonFooterHtml !== '') {
        echo $buttonFooterHtml;
    }

    return trim((string) ob_get_clean());
}
}

if (!function_exists('render_waiting_page_content')) {
function render_waiting_page_content(array $page_config, array $contest_config): string {
    return render_config_page_container_content($page_config, $contest_config, [
        'content_class' => 'waiting-page-content',
        'layout_waiting_html' => true,
    ]);
}
}
/**
 * Renderizza i container di intestazione della pagina quiz (stesso stile Waiting page).
 * $quiz_page_config = pages.quiz_page (containers + question_block_style).
 */
if (!function_exists('quiz_page_header_replacements')) {
function quiz_page_header_replacements(array $contest_config): array {
    return [
        'contest_name' => htmlspecialchars($contest_config['name'] ?? 'Concorso', ENT_QUOTES),
        'start_date' => htmlspecialchars($contest_config['start_date_formatted'] ?? ($contest_config['start_date'] ?? ''), ENT_QUOTES),
        'end_date' => htmlspecialchars($contest_config['end_date_formatted'] ?? ($contest_config['end_date'] ?? ''), ENT_QUOTES),
        'min_spend' => number_format((float) ($contest_config['min_spend'] ?? 0), 2, ',', '.'),
        'regolamento_link' => htmlspecialchars($contest_config['regolamento_link'] ?? '#', ENT_QUOTES),
    ];
}
}

if (!function_exists('quiz_page_build_container_style')) {
function quiz_page_build_container_style(array $container): string {
    $container_style = '';
    if (isset($container['background'])) {
        $bg_mode = $container['background']['mode'] ?? 'color';
        $bg_opacity = max(0, min(100, (int) ($container['background']['opacity'] ?? 100))) / 100;
        if ($bg_mode === 'color' && !empty($container['background']['color'])) {
            $container_style .= CONFIG_CSS_BG_COLOR_PREFIX_NOSPACE . config_page_hex2rgba((string) $container['background']['color'], $bg_opacity) . '; ';
        } elseif ($bg_mode === 'image' && !empty($container['background']['image'])) {
            $container_style .= CONFIG_CSS_BG_IMAGE_URL . htmlspecialchars((string) $container['background']['image'], ENT_QUOTES) . "'); background-size: cover; background-position: center; ";
        }
    }
    $margin = array_merge(['top' => 0, 'right' => 0, 'bottom' => 16, 'left' => 0], (array) ($container['style']['margin'] ?? []));
    $container_style .= sprintf(CONFIG_CSS_MARGIN_BOX, (int) $margin['top'], (int) $margin['right'], (int) $margin['bottom'], (int) $margin['left']);
    $radius = array_merge(['top' => 8, 'right' => 8, 'bottom' => 8, 'left' => 8], (array) ($container['style']['radius'] ?? []));
    $container_style .= sprintf(CONFIG_CSS_RADIUS_BOX, (int) $radius['top'], (int) $radius['right'], (int) $radius['bottom'], (int) $radius['left']);
    $container_style .= 'text-align: ' . htmlspecialchars((string) ($container['style']['align'] ?? 'center'), ENT_QUOTES) . '; ';
    if (isset($container['style']['padding'])) {
        $p = $container['style']['padding'];
        $container_style .= sprintf('padding: %dpx %dpx %dpx %dpx; ', (int) ($p['top'] ?? 20), (int) ($p['right'] ?? 20), (int) ($p['bottom'] ?? 20), (int) ($p['left'] ?? 20));
    }
    return $container_style;
}
}

if (!function_exists('quiz_page_replace_placeholders')) {
function quiz_page_replace_placeholders(string $value, array $replacements): string {
    return str_replace(
        [CONFIG_PH_CONTEST_NAME, CONFIG_PH_START_DATE, CONFIG_PH_END_DATE, CONFIG_PH_MIN_SPEND, CONFIG_PH_REGOLAMENTO],
        [$replacements['contest_name'], $replacements['start_date'], $replacements['end_date'], $replacements['min_spend'], $replacements['regolamento_link']],
        $value
    );
}
}

if (!function_exists('quiz_page_render_header_text_blocks')) {
function quiz_page_render_header_text_blocks(array $blocks, array $replacements): void {
    $allowed_tags = config_page_allowed_tags();
    foreach ($blocks as $block) {
        if (($block['type'] ?? '') !== 'text') {
            continue;
        }
        $content = quiz_page_replace_placeholders((string) ($block['content'] ?? ''), $replacements);
        echo '<div class="mb-0 fs-6">' . strip_tags($content, $allowed_tags) . '</div>';
    }
}
}

if (!function_exists('render_quiz_page_header_containers')) {
function render_quiz_page_header_containers(array $contest_config, array $quiz_page_config): string {
    $replacements = quiz_page_header_replacements($contest_config);
    $containers = $quiz_page_config['containers'] ?? [];
    if (empty($containers)) {
        return '';
    }
    ob_start();
    foreach ($containers as $container) {
        echo '<div style="' . htmlspecialchars(quiz_page_build_container_style($container), ENT_QUOTES) . '">';
        if (isset($container['blocks']) && is_array($container['blocks'])) {
            quiz_page_render_header_text_blocks($container['blocks'], $replacements);
        }
        echo '</div>';
    }
    return trim((string) ob_get_clean());
}
}

/**
 * Restituisce lo stile inline per il blocco di una singola domanda (margin, radius, background, padding).
 */
if (!function_exists('get_quiz_question_block_style')) {
function get_quiz_question_block_style(array $quiz_page_config): string {
    $qb = $quiz_page_config['question_block_style'] ?? [];
    if (empty($qb)) {
        return 'margin-bottom: 12px; border-radius: 8px; padding: 16px; background-color: #fff;';
    }
    $style = '';
    if (isset($qb['background'])) {
        $bg = $qb['background'];
        $mode = $bg['mode'] ?? 'color';
        $opacity = max(0, min(100, (int) ($bg['opacity'] ?? 100))) / 100;
        if ($mode === 'color' && !empty($bg['color'])) {
            $hex = ltrim(trim($bg['color']), '#');
            if (strlen($hex) === 3) {
                $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
            }
            $r = hexdec(substr($hex, 0, 2));
            $g = hexdec(substr($hex, 2, 2));
            $b = hexdec(substr($hex, 4, 2));
            $style .= "background-color: rgba({$r},{$g},{$b},{$opacity}) !important; ";
        } elseif ($mode === 'image' && !empty($bg['image'])) {
            $style .= CONFIG_CSS_BG_IMAGE_URL . htmlspecialchars((string) $bg['image'], ENT_QUOTES) . "') !important; background-size: cover !important; background-position: center !important; ";
        }
    }
    $st = $qb['style'] ?? [];
    $margin = array_merge(['top' => 0, 'right' => 0, 'bottom' => 12, 'left' => 0], (array) ($st['margin'] ?? []));
    $style .= sprintf(CONFIG_CSS_MARGIN_BOX, (int) $margin['top'], (int) $margin['right'], (int) $margin['bottom'], (int) $margin['left']);
    $radius = array_merge(['top' => 8, 'right' => 8, 'bottom' => 8, 'left' => 8], (array) ($st['radius'] ?? []));
    $style .= sprintf(CONFIG_CSS_RADIUS_BOX, (int) $radius['top'], (int) $radius['right'], (int) $radius['bottom'], (int) $radius['left']);
    if (isset($st['padding'])) {
        $p = $st['padding'];
        $style .= sprintf('padding: %dpx %dpx %dpx %dpx; ', (int) ($p['top'] ?? 16), (int) ($p['right'] ?? 16), (int) ($p['bottom'] ?? 16), (int) ($p['left'] ?? 16));
    }
    return $style;
}
}

/**
 * Restituisce il contenuto HTML opzionale da mostrare dentro ogni blocco domanda (centrato, colori, testo, ecc.).
 */
if (!function_exists('get_quiz_question_block_content')) {
function get_quiz_question_block_content(array $quiz_page_config, array $contest_config): string {
    $qb = $quiz_page_config['question_block_style'] ?? [];
    $content = trim((string) ($qb['content'] ?? ''));
    if ($content === '') {
        $content = '<p>{{DOMANDA_N}}</p><p>{{TESTO_DOMANDA}}</p><p>{{RISPOSTA}}</p>{{OPZIONI}}';
    }
    $contest_name = htmlspecialchars($contest_config['name'] ?? 'Concorso', ENT_QUOTES);
    $start_date_formatted = htmlspecialchars($contest_config['start_date_formatted'] ?? ($contest_config['start_date'] ?? ''), ENT_QUOTES);
    $end_date_formatted = htmlspecialchars($contest_config['end_date_formatted'] ?? ($contest_config['end_date'] ?? ''), ENT_QUOTES);
    $min_spend_formatted = number_format((float) ($contest_config['min_spend'] ?? 0), 2, ',', '.');
    $regolamento_link = htmlspecialchars($contest_config['regolamento_link'] ?? '#', ENT_QUOTES);
    $content = str_replace(
        [CONFIG_PH_CONTEST_NAME, CONFIG_PH_START_DATE, CONFIG_PH_END_DATE, CONFIG_PH_MIN_SPEND, CONFIG_PH_REGOLAMENTO],
        [$contest_name, $start_date_formatted, $end_date_formatted, $min_spend_formatted, $regolamento_link],
        $content
    );
    $allowed_tags = '<p><h1><h2><h3><h4><h5><h6><strong><em><u><b><i><br><a><ul><ol><li><span><div><img><font>';
    return strip_tags($content, $allowed_tags);
}
}

/**
 * Stile inline per uno dei 4 blocchi interni in ogni blocco domanda.
 */
if (!function_exists('get_quiz_question_inner_block_style')) {
function get_quiz_question_inner_block_style(array $quiz_page_config, int $index): string {
    $qb = $quiz_page_config['question_block_style'] ?? [];
    $blocks = $qb['inner_blocks'] ?? [];
    if (!isset($blocks[$index]) || !is_array($blocks[$index]['style'] ?? null)) {
        $defaults = [
            ['padding' => 4, 'margin_bottom' => 4, 'text_align' => 'left', 'background_color' => '', 'radius' => 0],
            ['padding' => 8, 'margin_bottom' => 8, 'text_align' => 'left', 'background_color' => '', 'radius' => 4],
            ['padding' => 4, 'margin_bottom' => 4, 'text_align' => 'left', 'background_color' => '', 'radius' => 0],
            ['padding' => 8, 'margin_bottom' => 0, 'text_align' => 'left', 'background_color' => '', 'radius' => 4],
        ];
        $st = $defaults[$index] ?? $defaults[0];
    } else {
        $st = array_merge(
            ['padding' => 4, 'margin_bottom' => 4, 'text_align' => 'left', 'background_color' => '', 'radius' => 0],
            $blocks[$index]['style']
        );
    }
    $style = '';
    $pad = (int) ($st['padding'] ?? 0);
    $style .= "padding: {$pad}px; ";
    $mb = (int) ($st['margin_bottom'] ?? 0);
    $style .= "margin-bottom: {$mb}px; ";
    $align = in_array($st['text_align'] ?? '', ['left', 'center', 'right', 'justify'], true) ? $st['text_align'] : 'left';
    $style .= "text-align: {$align}; ";
    $bg = trim((string) ($st['background_color'] ?? ''));
    if ($bg !== '') {
        $hex = ltrim($bg, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        if (preg_match('/^[0-9A-Fa-f]{6}$/', $hex)) {
            $style .= 'background-color: #' . $hex . '; ';
        }
    }
    $radius = max(0, (int) ($st['radius'] ?? 0));
    if ($radius > 0) {
        $style .= "border-radius: {$radius}px; ";
    }
    return $style;
}
}

if (!function_exists('get_quiz_question_inner_block_content')) {
function get_quiz_question_inner_block_content(array $quiz_page_config, array $contest_config, int $index, int $question_num): string {
    $qb = $quiz_page_config['question_block_style'] ?? [];
    $blocks = $qb['inner_blocks'] ?? [];
    $defaults = ['', '', '', ''];
    $content = isset($blocks[$index]['content']) ? trim((string) $blocks[$index]['content']) : ($defaults[$index] ?? '');
    if ($content === '') {
        return '';
    }
    $contest_name = htmlspecialchars($contest_config['name'] ?? 'Concorso', ENT_QUOTES);
    $start_date_formatted = htmlspecialchars($contest_config['start_date_formatted'] ?? ($contest_config['start_date'] ?? ''), ENT_QUOTES);
    $end_date_formatted = htmlspecialchars($contest_config['end_date_formatted'] ?? ($contest_config['end_date'] ?? ''), ENT_QUOTES);
    $min_spend_formatted = number_format((float) ($contest_config['min_spend'] ?? 0), 2, ',', '.');
    $regolamento_link = htmlspecialchars($contest_config['regolamento_link'] ?? '#', ENT_QUOTES);
    $content = str_replace(
        ['{{N}}', CONFIG_PH_CONTEST_NAME, CONFIG_PH_START_DATE, CONFIG_PH_END_DATE, CONFIG_PH_MIN_SPEND, CONFIG_PH_REGOLAMENTO],
        [(string) $question_num, $contest_name, $start_date_formatted, $end_date_formatted, $min_spend_formatted, $regolamento_link],
        $content
    );
    $allowed_tags = '<p><h1><h2><h3><h4><h5><h6><strong><em><u><b><i><br><a><ul><ol><li><span><div><img><font>';
    return strip_tags($content, $allowed_tags);
}
}

if (!function_exists('get_quiz_answer_input_style')) {
function get_quiz_answer_input_style(array $quiz_page_config): string {
    $qb = $quiz_page_config['question_block_style'] ?? [];
    $v = strtolower(trim((string) ($qb['answer_input_style'] ?? 'radio')));
    return ($v === 'square' || $v === 'checkbox') ? 'square' : 'radio';
}
}

if (!function_exists('get_quiz_submit_button_config')) {
function get_quiz_submit_button_config(array $quiz_page_config): array {
    global $contest_config;
    $qb = $quiz_page_config['question_block_style'] ?? [];
    $globalText = trim((string) ($contest_config['buttons']['quiz_submit_text'] ?? ''));
    $text = trim((string) ($qb['submit_button_text'] ?? ($globalText !== '' ? $globalText : 'Invia risposte')));
    if ($text === '') {
        $text = 'Invia risposte';
    }
    $bg = trim((string) ($qb['submit_button_bg_color'] ?? CONFIG_COLOR_PRIMARY));
    if ($bg !== '' && $bg[0] !== '#') {
        $bg = '#' . $bg;
    }
    $align = strtolower(trim((string) ($qb['submit_button_align'] ?? 'center')));
    if (!in_array($align, ['left', 'center', 'right'], true)) {
        $align = 'center';
    }
    return ['text' => $text, 'bg_color' => $bg, 'align' => $align];
}
}

if (!function_exists('get_quiz_answer_tick_color')) {
function get_quiz_answer_tick_color(array $quiz_page_config): string {
    $qb = $quiz_page_config['question_block_style'] ?? [];
    $c = trim((string) ($qb['answer_tick_color'] ?? '#333333'));
    if ($c !== '' && $c[0] !== '#') {
        $c = '#' . $c;
    }
    return $c === '' ? '#333333' : $c;
}
}

/**
 * CSS layout opzioni quiz (radio + lettera + testo). Centralizzato per pagina pubblica e anteprima.
 */
if (!function_exists('get_quiz_options_list_style_block')) {
function get_quiz_options_list_style_block(): string {
    return '<style>'
        . '.quiz-opzioni-list .quiz-opzioni-row{display:grid;grid-template-columns:1.15em 1.2em 0.35em auto 1fr;column-gap:0.55em;row-gap:0.25em;align-items:center;}'
        . '.quiz-opzioni-list .form-check-input{grid-column:1;width:1.15em;min-width:1.15em;margin:0;}'
        . '.quiz-opzioni-list .form-check-label{grid-column:2/-1;display:contents;}'
        . '.quiz-opzioni-list .form-check-label .quiz-option-letter{grid-column:2;}'
        . '.quiz-opzioni-list .form-check-label .quiz-option-sep{grid-column:4;padding-right:0.25em;}'
        . '.quiz-opzioni-list .form-check-label .quiz-option-text{grid-column:5;margin:0;padding:0;}'
        . '</style>';
}
}

/**
 * HTML delle opzioni di risposta (A, B, C, D) per una domanda. Usato nel placeholder {{OPZIONI}}.
 */
if (!function_exists('get_quiz_question_options_html')) {
function get_quiz_question_options_html(array $q, string $answer_style, bool $disabled = false): string {
    $id = (int) ($q['id'] ?? 0);
    $name = 'q[' . $id . ']';
    $dis = $disabled ? ' disabled' : '';
    $answer_class = ($answer_style === 'square') ? ' quiz-answer-square' : '';
    $normalize = function ($s) {
        $s = trim((string) $s);
        $s = preg_replace('/^[\s\-–—]+/u', '', $s);
        return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
    };
    $a = $normalize($q['risposta_a'] ?? '');
    $b = $normalize($q['risposta_b'] ?? '');
    $c = $normalize($q['risposta_c'] ?? '');
    $d = $normalize($q['risposta_d'] ?? '');
    $html = '<span class="form-group mt-0 quiz-opzioni-list' . $answer_class . '" style="display:inline-block;text-align:inherit">';
    $html .= '<span class="form-check quiz-opzioni-row"><input type="radio" name="' . $name . '" value="A" class="form-check-input" id="q' . $id . '_A"' . $dis . '><label class="form-check-label" for="q' . $id . '_A"><span class="quiz-option-letter">A</span><span class="quiz-option-sep"> – </span><span class="quiz-option-text">' . $a . '</span></label></span>';
    $html .= '<span class="form-check quiz-opzioni-row"><input type="radio" name="' . $name . '" value="B" class="form-check-input" id="q' . $id . '_B"' . $dis . '><label class="form-check-label" for="q' . $id . '_B"><span class="quiz-option-letter">B</span><span class="quiz-option-sep"> – </span><span class="quiz-option-text">' . $b . '</span></label></span>';
    $html .= '<span class="form-check quiz-opzioni-row"><input type="radio" name="' . $name . '" value="C" class="form-check-input" id="q' . $id . '_C"' . $dis . '><label class="form-check-label" for="q' . $id . '_C"><span class="quiz-option-letter">C</span><span class="quiz-option-sep"> – </span><span class="quiz-option-text">' . $c . '</span></label></span>';
    $html .= '<span class="form-check quiz-opzioni-row"><input type="radio" name="' . $name . '" value="D" class="form-check-input" id="q' . $id . '_D"' . $dis . '><label class="form-check-label" for="q' . $id . '_D"><span class="quiz-option-letter">D</span><span class="quiz-option-sep"> – </span><span class="quiz-option-text">' . $d . '</span></label></span>';
    $html .= '</span>';
    return $html;
}
}

if (!function_exists('render_thankyou_page_content')) {
function render_thankyou_page_content(array $page_config, array $contest_config, float $prize_amount = 0, string $prize_type = ''): string {
    return render_config_page_container_content($page_config, $contest_config, [
        'content_class' => '',
        'layout_waiting_html' => false,
        'normalize_regolamento_button' => true,
        'prize_amount' => $prize_amount,
        'prize_type' => $prize_type,
    ]);
}
}

if (!function_exists('normalize_footer_layout')) {
    /**
     * @param array<string, mixed> $layout
     * @param array<string, mixed> $defaults
     * @return array<string, mixed>
     */
    function normalize_footer_layout(array $layout, array $defaults): array
    {
        $defaultBlocks = is_array($defaults['blocks'] ?? null) ? $defaults['blocks'] : [];
        $blockKeys = [
            'contest_title', 'contest_dates', 'prize_total',
            'links_regolamento', 'links_privacy', 'links_cookie_policy', 'links_assistenza',
            'promoter', 'delegate', 'legal_block_1', 'legal_block_2',
        ];
        $blocks = [];
        foreach ($blockKeys as $key) {
            if (array_key_exists($key, $layout['blocks'] ?? [])) {
                $blocks[$key] = filter_var($layout['blocks'][$key], FILTER_VALIDATE_BOOLEAN);
            } elseif (array_key_exists($key, $defaultBlocks)) {
                $blocks[$key] = filter_var($defaultBlocks[$key], FILTER_VALIDATE_BOOLEAN);
            } else {
                $blocks[$key] = in_array($key, ['contest_title', 'contest_dates', 'prize_total', 'links_regolamento', 'links_privacy', 'links_assistenza', 'promoter', 'delegate'], true);
            }
        }

        $defaultTexts = is_array($defaults['texts'] ?? null) ? $defaults['texts'] : [];
        $textKeys = [
            'contest_title', 'intro_line', 'legal_html_1', 'legal_html_2',
            'link_regolamento_label', 'link_privacy_label', 'link_cookie_policy_label', 'link_assistenza_label',
        ];
        $texts = [];
        foreach ($textKeys as $key) {
            $texts[$key] = trim((string) (($layout['texts'][$key] ?? null) ?? ($defaultTexts[$key] ?? '')));
        }
        if ($texts['link_regolamento_label'] === '') {
            $texts['link_regolamento_label'] = 'Regolamento completo';
        }
        if ($texts['link_privacy_label'] === '') {
            $texts['link_privacy_label'] = 'Privacy Policy';
        }
        if ($texts['link_cookie_policy_label'] === '') {
            $texts['link_cookie_policy_label'] = 'Cookie Policy';
        }
        if ($texts['link_assistenza_label'] === '') {
            $texts['link_assistenza_label'] = 'Assistenza';
        }

        $template = strtolower(trim((string) ($layout['template'] ?? ($defaults['template'] ?? 'three_columns'))));
        if (!in_array($template, ['three_columns', 'centered_compact'], true)) {
            $template = 'three_columns';
        }

        $logoHeight = (int) ($layout['logo_height_px'] ?? ($defaults['logo_height_px'] ?? 60));
        $logoHeight = max(24, min(200, $logoHeight));

        $logoImage = trim((string) ($layout['logo_image'] ?? ($defaults['logo_image'] ?? '')));
        if ($logoImage !== '') {
            $logoImage = asset_path_to_relative($logoImage);
        }

        return [
            'template' => $template,
            'show_logo' => filter_var($layout['show_logo'] ?? ($defaults['show_logo'] ?? false), FILTER_VALIDATE_BOOLEAN),
            'use_contest_logo' => filter_var($layout['use_contest_logo'] ?? ($defaults['use_contest_logo'] ?? true), FILTER_VALIDATE_BOOLEAN),
            'logo_image' => $logoImage,
            'logo_height_px' => $logoHeight,
            'font_family' => trim((string) ($layout['font_family'] ?? ($defaults['font_family'] ?? ''))),
            'font_size' => contest_resolve_font_size((string) ($layout['font_size'] ?? ($defaults['font_size'] ?? ''))),
            'blocks' => $blocks,
            'texts' => $texts,
        ];
    }
}

$contest_config['footer_layout'] = normalize_footer_layout(
    $footer_layout_content,
    is_array($content_defaults['footer_layout'] ?? null) ? $content_defaults['footer_layout'] : []
);
$contest_config['footer_layout']['font_family'] = contest_resolve_font_family(
    (string) ($contest_config['footer_layout']['font_family'] ?? ''),
    $contest_config
);
$contest_config['footer_layout']['font_size'] = contest_resolve_font_size(
    (string) ($contest_config['footer_layout']['font_size'] ?? '')
);

if (!function_exists('footer_layout_replace_placeholders')) {
    function footer_layout_replace_placeholders(string $value, array $contest_config): string
    {
        $cn = (string) ($contest_config['name'] ?? 'Concorso');
        $sd = (string) ($contest_config['start_date_formatted'] ?? ($contest_config['start_date'] ?? ''));
        $ed = (string) ($contest_config['end_date_formatted'] ?? ($contest_config['end_date'] ?? ''));
        $ms = number_format((float) ($contest_config['min_spend'] ?? 0), 2, ',', '.');
        $prize = (float) ($contest_config['prize_total'] ?? 0);
        $prizeFormatted = $prize > 0
            ? number_format($prize, 2, ',', '.') . ' € (IVA inclusa)'
            : '';
        $reg = (string) ($contest_config['regolamento_link'] ?? '#');
        $privacy = (string) ($contest_config['privacy_link'] ?? '#');
        $promoter = (string) ($contest_config['promoter'] ?? '');
        $delegate = (string) ($contest_config['delegate'] ?? '');

        return contest_replace_placeholders($value, [
            CONFIG_PH_CONTEST_NAME => $cn,
            CONFIG_PH_START_DATE => $sd,
            CONFIG_PH_END_DATE => $ed,
            CONFIG_PH_MIN_SPEND => $ms,
            '{{PRIZE_TOTAL}}' => $prizeFormatted,
            CONFIG_PH_REGOLAMENTO => $reg,
            '{{PRIVACY_LINK}}' => $privacy,
            '{{PROMOTER}}' => $promoter,
            '{{DELEGATE}}' => $delegate,
        ]);
    }
}

if (!function_exists('footer_resolve_entity_link')) {
    /**
     * @return array{valid: bool, url: string}
     */
    function footer_resolve_entity_link(string $rawUrl): array
    {
        $rawUrl = trim($rawUrl);
        if ($rawUrl === '') {
            return ['valid' => false, 'url' => ''];
        }
        if (preg_match(CONFIG_REGEX_HTTP_URL, $rawUrl)) {
            $valid = filter_var($rawUrl, FILTER_VALIDATE_URL) !== false;

            return ['valid' => $valid, 'url' => $valid ? $rawUrl : ''];
        }
        if (preg_match('#^www\.#i', $rawUrl)) {
            $url = CONFIG_SCHEME_HTTPS . $rawUrl;
            $valid = filter_var($url, FILTER_VALIDATE_URL) !== false;

            return ['valid' => $valid, 'url' => $valid ? $url : ''];
        }
        $testUrl = CONFIG_SCHEME_HTTPS . $rawUrl;
        if (filter_var($testUrl, FILTER_VALIDATE_URL) !== false) {
            return ['valid' => true, 'url' => $testUrl];
        }

        return ['valid' => false, 'url' => ''];
    }
}

if (!function_exists('footer_normalize_link_href')) {
    function footer_normalize_link_href(string $href): string
    {
        $href = trim($href);
        if ($href === '' || $href === '#') {
            return $href;
        }
        if (preg_match('#^(mailto:|tel:|javascript:|/|\./|\.\./)#i', $href)) {
            return $href;
        }
        if (preg_match(CONFIG_REGEX_HTTP_URL, $href) || preg_match('#^www\.#i', $href)) {
            $resolved = footer_resolve_entity_link($href);

            return $resolved['valid'] ? $resolved['url'] : $href;
        }
        if (preg_match('#^(?:[a-z0-9-]+\.)+[a-z]{2,}(?:/.*)?$#i', $href)) {
            $resolved = footer_resolve_entity_link($href);

            return $resolved['valid'] ? $resolved['url'] : $href;
        }

        return $href;
    }
}

if (!function_exists('footer_render_entity_html')) {
    function footer_render_entity_html(string $label, string $name, string $rawLink): string
    {
        $name = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
        if ($name === '') {
            return '';
        }
        $resolved = footer_resolve_entity_link($rawLink);
        if ($resolved['valid']) {
            return '<a href="' . htmlspecialchars($resolved['url'], ENT_QUOTES) . '" target="_blank" rel="noopener noreferrer" class="text-success fw-semibold text-decoration-none">' . $name . '</a>';
        }

        return '<span class="text-success fw-semibold">' . $name . '</span>';
    }
}

if (!function_exists('footer_layout_normalize_rich_text_html')) {
    /**
     * Retire les styles inline des liens (Summernote) pour appliquer la palette footer.
     */
    function footer_layout_normalize_rich_text_html(string $html): string
    {
        if ($html === '') {
            return '';
        }
        return (string) preg_replace_callback(
            '/<a\b([^>]*)>/i',
            static function (array $matches): string {
                $attrs = (string) ($matches[1] ?? '');
                $attrs = preg_replace('/\sstyle=(["\']).*?\1/i', '', $attrs) ?? $attrs;
                $attrs = preg_replace('/\sclass=(["\']).*?\1/i', '', $attrs) ?? $attrs;
                if (preg_match('/\shref=(["\'])(.*?)\1/i', $attrs, $hrefMatch)) {
                    $quote = $hrefMatch[1];
                    $href = (string) ($hrefMatch[2] ?? '');
                    $normalized = footer_normalize_link_href(htmlspecialchars_decode($href, ENT_QUOTES));
                    if ($normalized !== $href) {
                        $safeHref = htmlspecialchars($normalized, ENT_QUOTES);
                        $attrs = preg_replace(
                            '/\shref=(["\']).*?\1/i',
                            ' href=' . $quote . $safeHref . $quote,
                            $attrs,
                            1
                        ) ?? $attrs;
                    }
                }

                return '<a' . $attrs . ' class="footer-rich-link">';
            },
            $html
        );
    }
}

if (!function_exists('footer_layout_format_html')) {
    function footer_layout_format_html(string $raw, array $contest_config): string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return '';
        }
        if (strpos($raw, '&lt;') !== false) {
            $raw = htmlspecialchars_decode($raw, ENT_QUOTES);
        }

        return footer_layout_normalize_rich_text_html(
            footer_layout_replace_placeholders($raw, $contest_config)
        );
    }
}

if (!function_exists('footer_layout_default_intro_html')) {
    function footer_layout_default_intro_html(array $contest_config, array $blocks): string
    {
        $lines = [];
        $name = (string) ($contest_config['name'] ?? '');
        if (!empty($blocks['contest_dates']) && $name !== '') {
            $lines[] = 'Concorso a premi &quot;' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '&quot;';
        }
        if (!empty($blocks['contest_dates'])) {
            $sd = htmlspecialchars((string) ($contest_config['start_date_formatted'] ?? ($contest_config['start_date'] ?? '')), ENT_QUOTES, 'UTF-8');
            $ed = htmlspecialchars((string) ($contest_config['end_date_formatted'] ?? ($contest_config['end_date'] ?? '')), ENT_QUOTES, 'UTF-8');
            $lines[] = 'Concorso valido dal ' . $sd . ' al ' . $ed;
        }
        if (!empty($blocks['prize_total']) && (float) ($contest_config['prize_total'] ?? 0) > 0) {
            $lines[] = 'Totale Montepremi: ' . number_format((float) $contest_config['prize_total'], 2, ',', '.') . ' &euro; (IVA inclusa)';
        }

        return implode('<br>', $lines);
    }
}

if (!function_exists('footer_layout_logo_src')) {
    function footer_layout_logo_src(array $layout, array $contest_config): string
    {
        $custom = trim((string) ($layout['logo_image'] ?? ''));
        if ($custom !== '') {
            return $custom;
        }
        if (!empty($layout['use_contest_logo'])) {
            return trim((string) ($contest_config['logo'] ?? ''));
        }

        return '';
    }
}

if (!function_exists('is_assistenza_page_accessible')) {
    function is_assistenza_page_accessible(): bool
    {
        global $date_debut;

        if (session_status() === PHP_SESSION_ACTIVE && !empty($_SESSION['contest_preview'])) {
            return true;
        }

        return time() >= (int) $date_debut;
    }
}

if (!function_exists('assistenza_blocked_redirect_target')) {
    function assistenza_blocked_redirect_target(): string
    {
        if (!function_exists('app_normalize_route_name')) {
            require_once __DIR__ . '/router.php';
        }

        $default = 'registrazione';
        $referrer = trim((string) ($_SERVER['HTTP_REFERER'] ?? ''));
        if ($referrer === '') {
            return $default;
        }

        $refPath = parse_url($referrer, PHP_URL_PATH);
        if (!is_string($refPath) || $refPath === '') {
            return $default;
        }

        $segment = app_normalize_route_name(basename(rtrim($refPath, '/')));
        if ($segment === '' || $segment === 'assistenza') {
            return $default;
        }

        if (function_exists('app_route') && app_route('public', $segment) !== null) {
            return $segment;
        }

        return $default;
    }
}

if (!function_exists('render_footer_links_list_html')) {
    function render_footer_links_list_html(array $layout, array $contest_config, bool $inline = false): string
    {
        $blocks = is_array($layout['blocks'] ?? null) ? $layout['blocks'] : [];
        $texts = is_array($layout['texts'] ?? null) ? $layout['texts'] : [];
        $parts = [];
        $regUrl = htmlspecialchars(footer_normalize_link_href((string) ($contest_config['regolamento_link'] ?? '#')), ENT_QUOTES, 'UTF-8');
        $privacyUrl = htmlspecialchars(footer_normalize_link_href((string) ($contest_config['privacy_link'] ?? '#')), ENT_QUOTES, 'UTF-8');
        $cookiePolicyUrlRaw = trim((string) ($contest_config['cookie_policy_link'] ?? ''));
        $cookiePolicyUrl = $cookiePolicyUrlRaw !== ''
            ? htmlspecialchars(footer_normalize_link_href($cookiePolicyUrlRaw), ENT_QUOTES, 'UTF-8')
            : '';
        $regLabel = htmlspecialchars((string) ($texts['link_regolamento_label'] ?? 'Regolamento completo'), ENT_QUOTES, 'UTF-8');
        $privacyLabel = htmlspecialchars((string) ($texts['link_privacy_label'] ?? 'Privacy Policy'), ENT_QUOTES, 'UTF-8');
        $cookiePolicyLabel = htmlspecialchars((string) ($texts['link_cookie_policy_label'] ?? 'Cookie Policy'), ENT_QUOTES, 'UTF-8');
        $assistLabel = htmlspecialchars((string) ($texts['link_assistenza_label'] ?? 'Assistenza'), ENT_QUOTES, 'UTF-8');

        if (!empty($blocks['links_regolamento'])) {
            $parts[] = '<a class="text-success fw-semibold text-decoration-none" href="' . $regUrl . '" target="_blank" rel="noopener" aria-label="' . $regLabel . '">' . $regLabel . '</a>';
        }
        if (!empty($blocks['links_privacy'])) {
            $parts[] = '<a class="text-success fw-semibold text-decoration-none" href="' . $privacyUrl . '" target="_blank" rel="noopener" aria-label="' . $privacyLabel . '">' . $privacyLabel . '</a>';
        }
        if (!empty($blocks['links_cookie_policy']) && $cookiePolicyUrl !== '' && (!empty($contest_config['cookie_consent']['enabled']) || !empty($contest_config['footer_cookie_link_enabled']))) {
            $parts[] = '<a class="text-success fw-semibold text-decoration-none" href="' . $cookiePolicyUrl . '" target="_blank" rel="noopener" aria-label="' . $cookiePolicyLabel . '">' . $cookiePolicyLabel . '</a>';
        }
        $ccEnabled = !empty($contest_config['cookie_consent']['enabled']);
        if ($ccEnabled) {
            $parts[] = '<a class="text-success fw-semibold text-decoration-none" href="#" id="cc-open-preferences" aria-label="Preferenze cookie">Preferenze cookie</a>';
        }
        if (!empty($blocks['links_assistenza'])) {
            if (array_key_exists('assistenza_link', $contest_config)) {
                if ($contest_config['assistenza_link'] !== '') {
                    $parts[] = '<a class="text-success fw-semibold text-decoration-none" href="' . htmlspecialchars($contest_config['assistenza_link'], ENT_QUOTES, 'UTF-8') . '">' . $assistLabel . '</a>';
                }
            } elseif (is_assistenza_page_accessible()) {
                $parts[] = '<a class="text-success fw-semibold text-decoration-none" href="assistenza" aria-label="' . $assistLabel . '">' . $assistLabel . '</a>';
            } else {
                $parts[] = '<a class="text-success fw-semibold text-decoration-none" href="#" onclick="event.preventDefault();" aria-label="' . $assistLabel . '" aria-disabled="true">' . $assistLabel . '</a>';
            }
        }
        if ($parts === []) {
            return '';
        }
        if ($inline) {
            return '<p class="footer-links-inline small mb-0 text-center">' . implode(' <span class="footer-link-sep">-</span> ', $parts) . '</p>';
        }
        $lis = [];
        foreach ($parts as $i => $linkHtml) {
            $class = $i < count($parts) - 1 ? 'mb-2' : '';
            $lis[] = '<li class="' . $class . '">' . $linkHtml . '</li>';
        }

        return '<nav aria-label="Link utili"><ul class="list-unstyled mb-0">' . implode('', $lis) . '</ul></nav>';
    }
}

if (!function_exists('footer_layout_resolve_title_text')) {
function footer_layout_resolve_title_text(array $texts, array $contest_config, string $defaultQuotedName = ''): string {
    $titleText = trim((string) ($texts['contest_title'] ?? ''));
    if ($titleText === '') {
        return $defaultQuotedName !== ''
            ? $defaultQuotedName
            : (string) ($contest_config['name'] ?? '');
    }
    return footer_layout_replace_placeholders($titleText, $contest_config);
}
}

if (!function_exists('footer_layout_resolve_intro_html')) {
function footer_layout_resolve_intro_html(array $texts, array $blocks, array $contest_config): string {
    $introCustom = trim((string) ($texts['intro_line'] ?? ''));
    if ($introCustom === '' && empty($blocks['contest_dates']) && empty($blocks['prize_total'])) {
        return '';
    }
    return $introCustom !== ''
        ? nl2br(htmlspecialchars(footer_layout_replace_placeholders($introCustom, $contest_config), ENT_QUOTES, 'UTF-8'), false)
        : footer_layout_default_intro_html($contest_config, $blocks);
}
}

if (!function_exists('render_footer_entities_stacked_html')) {
function render_footer_entities_stacked_html(array $blocks, array $contest_config): string {
    $html = '';
    if (!empty($blocks['promoter'])) {
        $html .= '<strong>Soggetto Promotore:</strong><br>';
        $html .= footer_render_entity_html('promoter', (string) ($contest_config['promoter'] ?? ''), (string) ($contest_config['promoter_link'] ?? ''));
        $html .= '<br><br>';
    }
    if (!empty($blocks['delegate'])) {
        $html .= '<strong>Soggetto Delegato:</strong><br>';
        $html .= footer_render_entity_html('delegate', (string) ($contest_config['delegate'] ?? ''), (string) ($contest_config['delegate_link'] ?? ''));
    }
    return $html;
}
}

if (!function_exists('render_footer_entities_inline_html')) {
function render_footer_entities_inline_html(array $blocks, array $contest_config): string {
    $html = '';
    if (!empty($blocks['promoter'])) {
        $html .= '<div><strong>Soggetto Promotore:</strong> ';
        $html .= footer_render_entity_html('promoter', (string) ($contest_config['promoter'] ?? ''), (string) ($contest_config['promoter_link'] ?? ''));
        $html .= '</div>';
    }
    if (!empty($blocks['delegate'])) {
        $html .= '<div class="mt-1"><strong>Soggetto Delegato:</strong> ';
        $html .= footer_render_entity_html('delegate', (string) ($contest_config['delegate'] ?? ''), (string) ($contest_config['delegate_link'] ?? ''));
        $html .= '</div>';
    }
    return $html;
}
}

if (!function_exists('render_footer_three_columns_col1_html')) {
function render_footer_three_columns_col1_html(array $blocks, array $texts, array $contest_config): string {
    $html = '<div class="col-12 col-md-4 text-center text-md-start">';
    if (!empty($blocks['contest_title'])) {
        $titleText = footer_layout_resolve_title_text($texts, $contest_config, 'Concorso "' . (string) ($contest_config['name'] ?? '') . '"');
        $html .= '<div class="fw-bold text-uppercase text-success">' . htmlspecialchars($titleText, ENT_QUOTES, 'UTF-8') . '</div>';
    }
    $introHtml = footer_layout_resolve_intro_html($texts, $blocks, $contest_config);
    if ($introHtml !== '') {
        $html .= '<div class="small mt-2 footer-text">' . $introHtml . '</div>';
    }
    $html .= '</div>';
    return $html;
}
}

if (!function_exists('render_footer_three_columns_html')) {
    function render_footer_three_columns_html(array $contest_config): string
    {
        $layout = normalize_footer_layout(
            is_array($contest_config['footer_layout'] ?? null) ? $contest_config['footer_layout'] : [],
            []
        );
        $blocks = $layout['blocks'];
        $texts = $layout['texts'];
        $col1 = !empty($blocks['contest_title']) || !empty($blocks['contest_dates']) || !empty($blocks['prize_total']);
        $col2 = !empty($blocks['links_regolamento']) || !empty($blocks['links_privacy']) || !empty($blocks['links_cookie_policy']) || !empty($blocks['links_assistenza']) || !empty($contest_config['cookie_consent']['enabled']);
        $col3 = !empty($blocks['promoter']) || !empty($blocks['delegate']);
        if (!$col1 && !$col2 && !$col3) {
            return '';
        }

        $html = '<div class="row g-4 align-items-start">';
        if ($col1) {
            $html .= render_footer_three_columns_col1_html($blocks, $texts, $contest_config);
        }
        if ($col2) {
            $links = render_footer_links_list_html($layout, $contest_config, false);
            if ($links !== '') {
                $html .= '<div class="col-12 col-md-4 text-center">' . $links . '</div>';
            }
        }
        if ($col3) {
            $html .= '<div class="col-12 col-md-4 text-center text-md-end"><div class="small">';
            $html .= render_footer_entities_stacked_html($blocks, $contest_config);
            $html .= '</div></div>';
        }
        $html .= '</div>';

        return $html;
    }
}

if (!function_exists('render_footer_centered_compact_html')) {
    function render_footer_centered_compact_html(array $contest_config): string
    {
        $layout = normalize_footer_layout(
            is_array($contest_config['footer_layout'] ?? null) ? $contest_config['footer_layout'] : [],
            []
        );
        $blocks = $layout['blocks'];
        $texts = $layout['texts'];
        $html = '<div class="footer-layout-centered-inner d-flex flex-column align-items-center text-center mx-auto w-100">';
        if (!empty($layout['show_logo'])) {
            $logoSrc = footer_layout_logo_src($layout, $contest_config);
            if ($logoSrc !== '') {
                $height = (int) ($layout['logo_height_px'] ?? 60);
                $html .= '<img class="footer-logo-img mb-2" src="' . htmlspecialchars($logoSrc, ENT_QUOTES, 'UTF-8') . '" alt="' . htmlspecialchars((string) ($contest_config['name'] ?? 'Logo'), ENT_QUOTES, 'UTF-8') . '" style="height: ' . $height . 'px; width: auto;">';
            }
        }
        if (!empty($blocks['contest_title'])) {
            $titleText = footer_layout_resolve_title_text($texts, $contest_config);
            if ($titleText !== '') {
                $html .= '<div class="fw-bold text-uppercase text-success mb-1">' . htmlspecialchars($titleText, ENT_QUOTES, 'UTF-8') . '</div>';
            }
        }
        $introHtml = footer_layout_resolve_intro_html($texts, $blocks, $contest_config);
        if ($introHtml !== '') {
            $html .= '<div class="small footer-text mb-2">' . $introHtml . '</div>';
        }
        if (!empty($blocks['legal_block_1'])) {
            $legal1 = footer_layout_format_html((string) ($texts['legal_html_1'] ?? ''), $contest_config);
            if ($legal1 !== '') {
                $html .= '<div class="footer-legal-block mb-1 small">' . $legal1 . '</div>';
            }
        }
        if (!empty($blocks['legal_block_2'])) {
            $legal2 = footer_layout_format_html((string) ($texts['legal_html_2'] ?? ''), $contest_config);
            if ($legal2 !== '') {
                $html .= '<div class="footer-legal-block mb-2 small">' . $legal2 . '</div>';
            }
        }
        $links = render_footer_links_list_html($layout, $contest_config, true);
        if ($links !== '') {
            $html .= $links;
        }
        if (!empty($blocks['promoter']) || !empty($blocks['delegate'])) {
            $html .= '<div class="small footer-text mt-2">' . render_footer_entities_inline_html($blocks, $contest_config) . '</div>';
        }
        $html .= '</div>';

        return $html;
    }
}

if (!function_exists('render_public_footer')) {
    function render_public_footer(array $contest_config, string $extraInner = ''): void
    {
        $layout = normalize_footer_layout(
            is_array($contest_config['footer_layout'] ?? null) ? $contest_config['footer_layout'] : [],
            []
        );
        $template = $layout['template'] ?? 'three_columns';
        $contestName = (string) ($contest_config['name'] ?? '');
        $inner = $template === 'centered_compact'
            ? render_footer_centered_compact_html($contest_config)
            : render_footer_three_columns_html($contest_config);
        $layoutClass = $template === 'centered_compact' ? 'footer-layout-centered' : 'footer-layout-three-columns';
        if ($inner !== '' || $extraInner !== '') {
            echo '<footer class="bg-white border-top text-dark w-100 mt-auto ' . htmlspecialchars($layoutClass, ENT_QUOTES, 'UTF-8') . '" role="contentinfo" aria-labelledby="footer-heading">';
            echo '<h2 id="footer-heading" class="visually-hidden">Informazioni sul concorso ' . htmlspecialchars($contestName, ENT_QUOTES, 'UTF-8') . '</h2>';
            echo '<div class="container py-4">';
            echo $inner . $extraInner;
            echo '</div></footer>';
        }

        require_once __DIR__ . '/cookie_consent.php';
        $cc = is_array($contest_config['cookie_consent'] ?? null)
            ? $contest_config['cookie_consent']
            : cookie_consent_normalize([], []);
        if (cookie_consent_is_enabled($cc)) {
            require __DIR__ . '/../../public/assets/inc/cookie_consent_markup.php';
        }
    }
}

// Environment flags
$test_config = [
    'environment' => getenv('APP_ENV') ?: 'production',
];

// SMTP — credentials from .env (SMTP_*); JSON fallback only if env empty (legacy)
$email_config = [
    'smtp_host' => getenv('SMTP_HOST') ?: $smtp_host_cfg,
    'smtp_port' => (int) (getenv('SMTP_PORT') ?: $smtp_port_cfg),
    'smtp_username' => getenv('SMTP_USERNAME') ?: $smtp_user_cfg,
    'smtp_password' => getenv('SMTP_PASSWORD') ?: $smtp_pass_cfg,
    'from_email' => getenv('SMTP_FROM_EMAIL') ?: ($smtp_from_email_cfg ?: 'no-reply@example.com'),
    'from_name' => getenv('SMTP_FROM_NAME') ?: ($smtp_from_name_cfg ?: $contest_config['name']),
    'reply_to' => '',
    'helpdesk_email' => $helpdesk_email,
];

// MailUp SMS — credentials only from .env (never content.json)
require_once __DIR__ . '/sms_mailup.inc.php';
$sms_config = gst_sms_config_from_env();
$contest_config['sms'] = [
    'enabled' => (bool) (($content_config['sms']['enabled'] ?? ($content_defaults['sms']['enabled'] ?? false))),
    'provider' => (string) (($content_config['sms']['provider'] ?? ($content_defaults['sms']['provider'] ?? 'mailup'))),
];
$contest_config['sms_sending'] = array_replace_recursive(
    is_array($content_defaults['sms_sending'] ?? null) ? $content_defaults['sms_sending'] : [],
    is_array($content_config['sms_sending'] ?? null) ? $content_config['sms_sending'] : []
);
$contest_config['sms_templates'] = array_replace_recursive(
    is_array($content_defaults['sms_templates'] ?? null) ? $content_defaults['sms_templates'] : [],
    is_array($content_config['sms_templates'] ?? null) ? $content_config['sms_templates'] : []
);

?>
