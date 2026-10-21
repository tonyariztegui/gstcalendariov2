<?php
declare(strict_types=1);

/** Calendario a premi: la disponibilità è sempre verificata sul server (Europe/Rome). */
function advent_defaults(): array
{
    $year = (new DateTimeImmutable('now', new DateTimeZone('Europe/Rome')))->format('Y');
    return ['published' => false, 'allow_past_days' => false, 'logo_replaces_heading' => false, 'title' => 'Calendario d’Avvento',
        'intro' => 'Una sorpresa al giorno. Apri la casella di oggi e scopri il tuo premio.',
        'start_date' => "$year-12-01", 'end_date' => "$year-12-24",
        'logo' => '', 'logo_width_mobile' => 320, 'logo_width_tablet' => 480,
        'logo_width_desktop' => 700, 'logo_width_wide' => 850,
        'logo_overlap_mobile' => 0, 'logo_overlap_tablet' => 0, 'logo_overlap_desktop' => 0,
        'background' => '', 'background_mobile' => '', 'background_tablet' => '', 'background_wide' => '',
        'accent' => '#185844', 'modal_overlay' => '#102a23', 'modal_overlay_opacity' => 72,
        'grid_columns_mobile' => 2, 'grid_columns_tablet' => 3, 'grid_columns_desktop' => 4,
        'grid_gap' => 16, 'grid_tile_ratio' => 'classic', 'grid_tile_radius' => 16, 'grid_image_fit' => 'contain', 'grid_show_caption' => true,
        'expired_message' => 'badge', 'expired_grayscale' => false, 'expired_image' => 'promotion', 'expired_image_fit' => 'contain',
        'footer_enabled' => false, 'footer_layout' => 'centered', 'footer_use_main_logo' => true,
        'footer_logo' => '', 'footer_background_image' => '', 'footer_background' => '#ffffff',
        'footer_mode' => 'color', 'footer_image_position_vertical' => 'center', 'footer_image_position_horizontal' => 'center',
        'footer_image_size' => 'cover', 'footer_image_size_custom' => '', 'footer_image_repeat' => 'no-repeat',
        'footer_opacity' => 100, 'footer_custom_text_colors' => false, 'footer_text_color' => '#212529',
        'footer_text_secondary' => '#6c757d', 'footer_link_color' => '#0d6efd', 'footer_link_hover' => '#0a58ca',
        'footer_border_enabled' => false, 'footer_border_color' => '#000000', 'footer_border_opacity' => 10,
        'footer_border_width' => 1, 'footer_border_sides' => ['top'], 'footer_show_logo' => false, 'footer_logo_height' => 60,
        'footer_font_family' => '', 'footer_font_size' => '', 'footer_prize_total' => 0, 'footer_title' => '', 'footer_text' => '',
        'footer_button_label' => '', 'footer_button_url' => '', 'footer_button_background' => '#185844',
        'footer_button_text_color' => '#ffffff', 'footer_button_hover_background' => '#124232',
        'footer_button_size' => 'medium', 'footer_button_width' => 'auto', 'footer_button_radius' => 8,
        'footer_button_new_tab' => false,
        'footer_legal' => '', 'footer_legal_2' => '', 'footer_promoter' => '', 'footer_promoter_url' => '',
        'footer_delegate' => '', 'footer_delegate_url' => '', 'footer_regulation_url' => '', 'footer_privacy_url' => '',
        'footer_cookie_url' => '', 'footer_assistance_url' => '', 'footer_regulation_label' => 'Regolamento completo',
        'footer_privacy_label' => 'Privacy Policy', 'footer_cookie_label' => 'Cookie Policy', 'footer_assistance_label' => 'Assistenza',
        'footer_blocks' => ['title' => true, 'dates' => true, 'prize_total' => true, 'links_regulation' => true, 'links_privacy' => true,
            'links_cookie' => false, 'links_assistance' => true, 'promoter' => true, 'delegate' => true,
            'legal_1' => false, 'legal_2' => false],
        'containers' => advent_container_defaults(),
        'days' => []];
}

function advent_container_types(): array
{
    return ['prize', 'video', 'link'];
}

function advent_container_defaults(): array
{
    $box = [
        'width_desktop' => 680, 'width_tablet' => 680, 'width_mobile' => 680,
        'padding' => 28, 'radius' => 20,
        'background_mode' => 'color', 'background_color' => '#fffdf9', 'background_image' => '',
        'show_kicker' => true, 'show_title' => true, 'show_description' => true, 'show_code' => true,
        'media_max_height' => 420,
        'close_visible' => true, 'close_kind' => 'icon',
        'close_color' => '#243c34', 'close_background' => '#ffffff', 'close_border' => '#d8ddd6',
        'close_size' => 42, 'close_shape' => 'circle', 'close_image' => '',
    ];
    return array_fill_keys(advent_container_types(), $box);
}

function advent_containers(array $calendar): array
{
    $saved = is_array($calendar['containers'] ?? null) ? $calendar['containers'] : [];
    $containers = [];
    foreach (advent_container_types() as $type) {
        $current = is_array($saved[$type] ?? null) ? $saved[$type] : [];
        $containers[$type] = array_replace(advent_container_defaults()[$type], $current);
        $containers[$type]['close_visible'] = true;
    }
    return $containers;
}

function advent_container_appearance_from_source(array $source, array $fallback, bool &$measureError, bool &$colorError, bool &$imageError): array
{
    $box = $fallback;
    foreach (['width_desktop' => [280, 900], 'width_tablet' => [280, 900], 'width_mobile' => [280, 900], 'padding' => [12, 64], 'radius' => [0, 40], 'close_size' => [28, 72]] as $key => $range) {
        $box[$key] = filter_var($source[$key] ?? $box[$key], FILTER_VALIDATE_INT);
        if ($box[$key] === false || $box[$key] < $range[0] || $box[$key] > $range[1]) {
            $measureError = true;
            $box[$key] = $fallback[$key];
        }
    }
    $box['background_mode'] = (($source['background_mode'] ?? 'color') === 'image') ? 'image' : 'color';
    $box['close_kind'] = (($source['close_kind'] ?? 'icon') === 'image') ? 'image' : 'icon';
    $box['close_shape'] = (($source['close_shape'] ?? 'circle') === 'square') ? 'square' : 'circle';
    $box['close_visible'] = true;
    foreach (['background_color' => '#fffdf9', 'close_color' => '#243c34', 'close_background' => '#ffffff', 'close_border' => '#d8ddd6'] as $key => $colorFallback) {
        $box[$key] = is_string($source[$key] ?? null) ? trim($source[$key]) : $colorFallback;
        if (!preg_match('/^#[0-9a-fA-F]{6}$/D', $box[$key])) {
            $colorError = true;
            $box[$key] = $colorFallback;
        }
    }
    foreach (['background_image', 'close_image'] as $key) {
        $box[$key] = is_string($source[$key] ?? null) ? trim($source[$key]) : '';
        if (!advent_image_url($box[$key])) {
            $imageError = true;
            $box[$key] = '';
        }
    }
    return $box;
}

function advent_containers_from_input(?array $input): array
{
    if ($input === null) { return [advent_container_defaults(), []]; }
    $defaults = advent_container_defaults();
    $containers = [];
    $errors = [];
    $measureError = false;
    $colorError = false;
    $imageError = false;
    $shared = is_array($input['appearance'] ?? null) ? $input['appearance'] : null;
    $sharedBox = $shared !== null ? advent_container_appearance_from_source($shared, $defaults['prize'], $measureError, $colorError, $imageError) : null;
    foreach (advent_container_types() as $type) {
        $source = is_array($input[$type] ?? null) ? $input[$type] : [];
        $box = $sharedBox ?? advent_container_appearance_from_source($source, $defaults[$type], $measureError, $colorError, $imageError);
        $box['media_max_height'] = filter_var($source['media_max_height'] ?? $defaults[$type]['media_max_height'], FILTER_VALIDATE_INT);
        if ($box['media_max_height'] === false || $box['media_max_height'] < 120 || $box['media_max_height'] > 640) {
            $measureError = true;
            $box['media_max_height'] = $defaults[$type]['media_max_height'];
        }
        foreach (['show_kicker', 'show_title', 'show_description', 'show_code'] as $key) {
            $box[$key] = ($source[$key] ?? '1') !== '0';
        }
        $box['close_visible'] = true;
        $containers[$type] = $box;
    }
    if ($measureError) { $errors[] = 'Controlla misure, padding e angoli dei contenitori.'; }
    if ($colorError) { $errors[] = 'I colori dei contenitori devono essere nel formato #fffdf9.'; }
    if ($imageError) { $errors[] = 'Le immagini dei contenitori devono essere URL http/https o percorsi che iniziano con /.'; }
    return [$containers, $errors];
}

function advent_container_css_url(string $value, string $base): string
{
    if ($value === '' || !advent_image_url($value)) { return 'none'; }
    return 'url(' . json_encode(advent_asset_url($value, $base), JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) . ')';
}

function advent_container_public_css(array $containers, string $base): string
{
    $css = '';
    foreach (advent_container_types() as $type) {
        $box = $containers[$type];
        $color = static function (string $value, string $fallback): string {
            return preg_match('/^#[0-9a-fA-F]{6}$/D', $value) ? $value : $fallback;
        };
        $image = $box['background_mode'] === 'image' ? advent_container_css_url((string) $box['background_image'], $base) : 'none';
        $radius = $box['close_shape'] === 'square' ? '8px' : '50%';
        $selector = '.advent-modal[data-container="' . $type . '"],.advent-prize.is-' . $type;
        $css .= $selector . '{--c-width:' . (int) $box['width_mobile'] . 'px;--c-pad:' . (int) $box['padding'] . 'px;--c-radius:' . (int) $box['radius'] . 'px;--c-bg:' . $color((string) $box['background_color'], '#fffdf9') . ';--c-bg-image:' . $image . ';--c-media:' . (int) $box['media_max_height'] . 'px;--c-close-size:' . (int) $box['close_size'] . 'px;--c-close-radius:' . $radius . ';--c-close-color:' . $color((string) $box['close_color'], '#243c34') . ';--c-close-bg:' . $color((string) $box['close_background'], '#ffffff') . ';--c-close-border:' . $color((string) $box['close_border'], '#d8ddd6') . '}';
        $css .= '@media(min-width:600px){' . $selector . '{--c-width:' . (int) $box['width_tablet'] . 'px}}';
        $css .= '@media(min-width:1000px){' . $selector . '{--c-width:' . (int) $box['width_desktop'] . 'px}}';
    }
    $css .= '.advent-modal[data-container]{width:min(var(--c-width),calc(100% - 24px));border-radius:var(--c-radius);background-color:var(--c-bg);background-image:var(--c-bg-image);background-size:cover;background-position:center}';
    $css .= '.advent-modal[data-container] .advent-modal-card{padding:calc(var(--c-pad) + 14px) var(--c-pad) var(--c-pad)}';
    $css .= '.advent-modal[data-container] .advent-modal-close{width:var(--c-close-size);height:var(--c-close-size);padding:0;overflow:hidden;border-color:var(--c-close-border);border-radius:var(--c-close-radius);background:var(--c-close-bg);color:var(--c-close-color);font-size:calc(var(--c-close-size) * .67)}';
    $css .= '.advent-modal[data-container] .advent-modal-close img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;border-radius:inherit}';
    $css .= '.advent-modal.is-kicker-off #advent-modal-day,.advent-prize.is-kicker-off .advent-kicker{display:none!important}';
    $css .= '.advent-modal.is-title-off #advent-modal-title,.advent-prize.is-title-off > h2{display:none!important}';
    $css .= '.advent-modal.is-description-off #advent-modal-description,.advent-prize.is-description-off .advent-prize-description{display:none!important}';
    $css .= '.advent-modal.is-code-off .advent-barcode p,.advent-prize.is-code-off .advent-barcode p{display:none!important}';
    $css .= '.advent-modal[data-container] .advent-prize-image,.advent-prize[class*="is-"] .advent-prize-image{max-height:var(--c-media)}';
    $css .= '.advent-modal[data-container] .advent-embed.is-video,.advent-prize[class*="is-"] .advent-embed.is-video{width:min(100%,640px,calc(var(--c-media) * 16 / 9))}';
    $css .= '.advent-prize[class*="is-"]{max-width:min(var(--c-width),calc(100% - 24px));border-radius:var(--c-radius);background-color:var(--c-bg);background-image:var(--c-bg-image);background-size:cover;background-position:center;padding:var(--c-pad)}';
    return $css;
}

function advent_container_client(array $containers, string $base): array
{
    $client = [];
    foreach (advent_container_types() as $type) {
        $box = $containers[$type];
        $image = ($box['close_kind'] === 'image' && $box['close_image'] !== '') ? advent_asset_url((string) $box['close_image'], $base) : '';
        $client[$type] = [
            'show_kicker' => !empty($box['show_kicker']),
            'show_title' => !empty($box['show_title']),
            'show_description' => !empty($box['show_description']),
            'show_code' => !empty($box['show_code']),
            'close_visible' => !empty($box['close_visible']),
            'close_kind' => $box['close_kind'] === 'image' ? 'image' : 'icon',
            'close_image' => $image,
        ];
    }
    return $client;
}

function advent_config_path(): string { return __DIR__ . '/../config/advent.json'; }

function advent_load(): array
{
    $path = advent_config_path();
    if (!is_file($path)) { return advent_defaults(); }
    $data = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
    if (!is_array($data)) { throw new RuntimeException('Configurazione calendario non valida.'); }
    $config = array_replace(advent_defaults(), $data);
    $config['containers'] = advent_containers($config);
    return $config;
}

function advent_escape($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function advent_intro_is_blank(string $value): bool
{
    $text = html_entity_decode(strip_tags((string) preg_replace('/<br\s*\/?>/i', ' ', $value)), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    return trim(str_replace("\xc2\xa0", ' ', $text)) === '';
}

function advent_intro_style(string $style): string
{
    $kept = [];
    foreach (explode(';', $style) as $part) {
        if (!preg_match('/^\s*(color|background-color|font-size)\s*:\s*(.+)$/i', trim($part), $match)) {
            continue;
        }
        $prop = strtolower($match[1]);
        $val = strtolower(trim($match[2]));
        $color = '(?:#[0-9a-f]{3,8}|rgba?\(\s*\d{1,3}\s*,\s*\d{1,3}\s*,\s*\d{1,3}(?:\s*,\s*(?:0|1|0?\.\d+))?\s*\)|[a-z]{3,20})';
        $ok = $prop === 'font-size'
            ? (bool) preg_match('/^\d+(?:\.\d+)?(?:px|em|rem|%)$/', $val)
            : (bool) preg_match('/^' . $color . '$/', $val);
        if ($ok) {
            $kept[] = $prop . ': ' . $val;
        }
    }
    return implode('; ', $kept);
}

function advent_intro_sanitize(string $html): string
{
    if (function_exists('contest_normalize_summernote_font_html')) {
        $html = contest_normalize_summernote_font_html($html);
    }
    if (!class_exists('DOMDocument')) {
        return nl2br(advent_escape(trim(strip_tags($html))));
    }
    $prev = libxml_use_internal_errors(true);
    $dom = new DOMDocument();
    $loaded = $dom->loadHTML('<?xml encoding="UTF-8"><div id="advent-intro-root">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    if (!$loaded) {
        libxml_clear_errors();
        libxml_use_internal_errors($prev);
        return nl2br(advent_escape(trim(strip_tags($html))));
    }
    $allowed = ['p' => true, 'br' => true, 'strong' => true, 'b' => true, 'em' => true, 'i' => true, 'u' => true, 'span' => true, 'font' => true, 'ul' => true, 'ol' => true, 'li' => true, 'div' => true];
    $drop = ['script' => true, 'style' => true, 'iframe' => true, 'object' => true, 'embed' => true, 'link' => true, 'meta' => true];
    $nodes = [];
    foreach ((new DOMXPath($dom))->query('//*') ?: [] as $node) {
        $nodes[] = $node;
    }
    foreach (array_reverse($nodes) as $node) {
        if (!$node instanceof DOMElement || !$node->parentNode || $node->getAttribute('id') === 'advent-intro-root') {
            continue;
        }
        $tag = strtolower($node->tagName);
        if (isset($drop[$tag])) {
            $node->parentNode->removeChild($node);
            continue;
        }
        if (!isset($allowed[$tag])) {
            while ($node->firstChild) {
                $node->parentNode->insertBefore($node->firstChild, $node);
            }
            $node->parentNode->removeChild($node);
            continue;
        }
        $style = $node->getAttribute('style');
        if ($tag === 'font' && preg_match('/^#?[0-9a-f]{3,8}$/i', $node->getAttribute('color'))) {
            $color = $node->getAttribute('color');
            $style = 'color: ' . (str_starts_with($color, '#') ? $color : '#' . $color) . '; ' . $style;
        }
        while ($node->attributes->length) {
            $node->removeAttribute($node->attributes->item(0)->name);
        }
        $clean = advent_intro_style($style);
        if ($clean !== '') {
            $node->setAttribute('style', $clean);
        }
        if ($tag === 'font') {
            $span = $dom->createElement('span');
            if ($clean !== '') {
                $span->setAttribute('style', $clean);
            }
            while ($node->firstChild) {
                $span->appendChild($node->firstChild);
            }
            $node->parentNode->replaceChild($span, $node);
        }
    }
    $root = $dom->getElementById('advent-intro-root');
    $out = '';
    if ($root) {
        foreach ($root->childNodes as $child) {
            $out .= $dom->saveHTML($child);
        }
    }
    libxml_clear_errors();
    libxml_use_internal_errors($prev);
    return $out;
}

function advent_intro_store(string $value): string
{
    $value = trim($value);
    if (advent_intro_is_blank($value)) {
        return '';
    }
    return str_contains($value, '<') ? advent_intro_sanitize($value) : $value;
}

function advent_intro_html(string $value): string
{
    $value = advent_intro_store($value);
    if ($value === '') {
        return '';
    }
    return str_contains($value, '<') ? $value : nl2br(advent_escape($value));
}

function advent_date(string $value): ?DateTimeImmutable
{
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value, new DateTimeZone('Europe/Rome'));
    return $date && $date->format('Y-m-d') === $value ? $date : null;
}

function advent_days(array $config, ?DateTimeImmutable $today = null, bool $openAll = false): array
{
    $start = advent_date($config['start_date']);
    $end = advent_date($config['end_date']);
    if (!$start || !$end || $end < $start || $start->diff($end)->days > 61) { return []; }
    $today = ($today ?? new DateTimeImmutable('now', new DateTimeZone('Europe/Rome')))
        ->setTimezone(new DateTimeZone('Europe/Rome'))->format('Y-m-d');
    $days = [];
    for ($date = $start, $n = 1; $date <= $end; $date = $date->modify('+1 day'), $n++) {
        $iso = $date->format('Y-m-d');
        $days[$n] = ['day' => $n, 'date' => $iso, 'label' => $date->format('d/m'),
            'is_open' => $openAll || $iso === $today,
            'state' => $iso === $today ? 'today' : ($iso < $today ? 'past' : 'future')];
    }
    return $days;
}

function advent_day_result(array $config, int $day, ?DateTimeImmutable $today = null, bool $openAll = false): array
{
    $status = advent_days($config, $today, $openAll)[$day] ?? null;
    if (!$status) { return ['status' => 404, 'error' => 'Giorno non trovato.']; }
    if (!$status['is_open']) {
        return ['status' => $status['state'] === 'past' ? 410 : 423,
            'error' => $status['state'] === 'past' ? 'Questa casella è scaduta.' : 'Questa casella non è ancora disponibile.'];
    }
    $prize = $config['days'][$day] ?? [];
    if (trim((string) ($prize['title'] ?? '')) === '') {
        return ['status' => 422, 'error' => 'Premio non ancora configurato per questo giorno.'];
    }
    return ['status' => 200, 'day' => $status, 'prize' => $prize];
}

function advent_day_type(array $prize): string
{
    $type = (string) ($prize['type'] ?? 'prize');
    return in_array($type, ['prize', 'video', 'link'], true) ? $type : 'prize';
}

function advent_youtube_id(string $url): string
{
    if (preg_match('~(?:youtu\.be/|youtube(?:-nocookie)?\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/|live/))([A-Za-z0-9_-]{11})~', $url, $match)) {
        return $match[1];
    }
    return '';
}


function advent_image_url(string $value): bool
{
    return $value === '' || (bool) preg_match('~^(?:https?://[^\s<>"\x27]+|/(?!/)[a-zA-Z0-9_./%\-]+)$~D', $value);
}

function advent_asset_url(string $value, string $projectBase): string
{
    if (str_starts_with($value, '/uploads/') || str_starts_with($value, '/public/assets/')) { return rtrim($projectBase, '/') . $value; }
    return $value;
}

function advent_validate(array $input): array
{
    $config = advent_defaults();
    $errors = [];
    foreach (['title', 'intro', 'start_date', 'end_date', 'logo', 'background', 'background_mobile', 'background_tablet', 'background_wide', 'accent', 'modal_overlay', 'footer_layout', 'footer_logo', 'footer_background_image', 'footer_background', 'footer_mode', 'footer_image_position_vertical', 'footer_image_position_horizontal', 'footer_image_size', 'footer_image_size_custom', 'footer_image_repeat', 'footer_text_color', 'footer_text_secondary', 'footer_link_color', 'footer_link_hover', 'footer_border_color', 'footer_font_family', 'footer_font_size', 'footer_title', 'footer_text', 'footer_button_label', 'footer_button_url', 'footer_legal', 'footer_legal_2', 'footer_promoter', 'footer_promoter_url', 'footer_delegate', 'footer_delegate_url', 'footer_regulation_url', 'footer_privacy_url', 'footer_cookie_url', 'footer_assistance_url', 'footer_regulation_label', 'footer_privacy_label', 'footer_cookie_label', 'footer_assistance_label'] as $key) {
        $config[$key] = is_string($input[$key] ?? null) ? trim($input[$key]) : '';
    }
    $config['modal_overlay_opacity'] = filter_var($input['modal_overlay_opacity'] ?? null, FILTER_VALIDATE_INT);
    foreach (['grid_columns_mobile', 'grid_columns_tablet', 'grid_columns_desktop', 'grid_gap', 'grid_tile_radius', 'logo_width_mobile', 'logo_width_tablet', 'logo_width_desktop', 'logo_width_wide'] as $key) {
        $config[$key] = filter_var($input[$key] ?? advent_defaults()[$key], FILTER_VALIDATE_INT);
    }
    foreach (['logo_width_mobile', 'logo_width_tablet', 'logo_width_desktop', 'logo_width_wide'] as $key) {
        if ($config[$key] === false || $config[$key] < 60 || $config[$key] > 1000) {
            $errors[] = 'La larghezza del logo deve essere tra 60 e 1000 px per ogni dispositivo.';
            break;
        }
    }
    foreach (['logo_overlap_mobile', 'logo_overlap_tablet', 'logo_overlap_desktop'] as $key) {
        $config[$key] = filter_var($input[$key] ?? advent_defaults()[$key], FILTER_VALIDATE_INT);
        if ($config[$key] === false || $config[$key] < -200 || $config[$key] > 120) {
            $errors[] = 'La sovrapposizione deve essere tra -200 e 120 px per ogni dispositivo.';
            $config[$key] = 0;
            break;
        }
    }
    $config['grid_show_caption'] = !array_key_exists('grid_show_caption', $input) || !empty($input['grid_show_caption']);
    $config['expired_message'] = (($input['expired_message'] ?? 'badge') === 'banner') ? 'banner' : 'badge';
    $config['expired_grayscale'] = !empty($input['expired_grayscale']);
    $config['expired_image'] = (($input['expired_image'] ?? 'promotion') === 'case') ? 'case' : 'promotion';
    $config['expired_image_fit'] = (($input['expired_image_fit'] ?? 'contain') === 'cover') ? 'cover' : 'contain';
    [$config['containers'], $containerErrors] = advent_containers_from_input(is_array($input['containers'] ?? null) ? $input['containers'] : null);
    $errors = array_merge($errors, $containerErrors);
    $config['grid_tile_ratio'] = is_string($input['grid_tile_ratio'] ?? null) ? $input['grid_tile_ratio'] : 'classic';
    $config['grid_image_fit'] = is_string($input['grid_image_fit'] ?? null) ? $input['grid_image_fit'] : 'contain';
    $config['published'] = !empty($input['published']);
    $config['allow_past_days'] = !empty($input['allow_past_days']);
    $config['logo_replaces_heading'] = !empty($input['logo_replaces_heading']);
    $config['footer_enabled'] = !empty($input['footer_enabled']);
    foreach (['footer_button_background', 'footer_button_text_color', 'footer_button_hover_background', 'footer_button_size', 'footer_button_width'] as $key) {
        $config[$key] = is_string($input[$key] ?? null) ? trim($input[$key]) : advent_defaults()[$key];
    }
    $config['footer_button_radius'] = filter_var($input['footer_button_radius'] ?? advent_defaults()['footer_button_radius'], FILTER_VALIDATE_INT);
    $config['footer_button_new_tab'] = !empty($input['footer_button_new_tab']);
    $config['footer_use_main_logo'] = !empty($input['footer_use_main_logo']);
    $config['footer_show_logo'] = !empty($input['footer_show_logo']);
    $config['footer_custom_text_colors'] = !empty($input['footer_custom_text_colors']);
    $config['footer_border_enabled'] = !empty($input['footer_border_enabled']);
    $config['footer_opacity'] = filter_var($input['footer_opacity'] ?? null, FILTER_VALIDATE_INT);
    $config['footer_border_opacity'] = filter_var($input['footer_border_opacity'] ?? null, FILTER_VALIDATE_INT);
    $config['footer_border_width'] = filter_var($input['footer_border_width'] ?? null, FILTER_VALIDATE_INT);
    $config['footer_logo_height'] = filter_var($input['footer_logo_height'] ?? null, FILTER_VALIDATE_INT);
    $config['footer_prize_total'] = max(0, (float) ($input['footer_prize_total'] ?? 0));
    $config['footer_border_sides'] = array_values(array_intersect(['top', 'right', 'bottom', 'left'], is_array($input['footer_border_sides'] ?? null) ? $input['footer_border_sides'] : []));
    foreach (array_keys(advent_defaults()['footer_blocks']) as $key) { $config['footer_blocks'][$key] = !empty($input['footer_blocks'][$key]); }
    $config['intro'] = advent_intro_store($config['intro']);
    if ($config['title'] === '' || strlen($config['title']) > 180) { $errors[] = 'Inserisci un titolo di massimo 180 caratteri.'; }
    if (strlen($config['intro']) > 8000) { $errors[] = 'Il testo introduttivo è troppo lungo.'; }
    $days = advent_days($config);
    if (!$days) { $errors[] = 'Scegli un intervallo valido da 1 a 62 giorni, con la fine successiva o uguale all’inizio.'; }
    if (!preg_match('/^#[0-9a-fA-F]{6}$/D', $config['accent'])) { $errors[] = 'Il colore deve essere nel formato #185844.'; }
    if (!preg_match('/^#[0-9a-fA-F]{6}$/D', $config['modal_overlay'])) { $errors[] = 'Il colore della velatura deve essere nel formato #102a23.'; }
    if ($config['modal_overlay_opacity'] === false || $config['modal_overlay_opacity'] < 0 || $config['modal_overlay_opacity'] > 100) {
        $errors[] = 'L’intensità della velatura deve essere compresa tra 0 e 100.';
        $config['modal_overlay_opacity'] = 72;
    }
    foreach (['grid_columns_mobile'=>[1,2], 'grid_columns_tablet'=>[2,4], 'grid_columns_desktop'=>[2,6], 'grid_gap'=>[0,40], 'grid_tile_radius'=>[0,32]] as $key=>$range) {
        if ($config[$key] === false || $config[$key] < $range[0] || $config[$key] > $range[1]) { $errors[] = 'Controlla colonne, spaziatura e angoli della griglia.'; break; }
    }
    if (!in_array($config['grid_tile_ratio'], ['square','classic','wide'], true) || !in_array($config['grid_image_fit'], ['contain','cover'], true)) { $errors[] = 'Scegli una forma e un adattamento immagini validi per le caselle.'; }
    if (!in_array($config['footer_layout'], ['centered', 'columns'], true)) { $errors[] = 'Scegli un layout footer valido.'; }
    if (!in_array($config['footer_mode'], ['color', 'image'], true)) { $errors[] = 'Scegli una modalità footer valida.'; }
    if (!in_array($config['footer_image_position_vertical'], ['top', 'center', 'bottom'], true) || !in_array($config['footer_image_position_horizontal'], ['left', 'center', 'right'], true) || !in_array($config['footer_image_size'], ['cover', 'contain', 'auto', 'custom'], true) || !in_array($config['footer_image_repeat'], ['no-repeat', 'repeat', 'repeat-x', 'repeat-y'], true)) { $errors[] = 'Controlla le opzioni dell’immagine footer.'; }
    if ($config['footer_image_size_custom'] !== '' && !preg_match('/^(?:auto|\d+(?:\.\d+)?(?:px|%|rem|em|vw|vh))(?:\s+(?:auto|\d+(?:\.\d+)?(?:px|%|rem|em|vw|vh)))?$/D', $config['footer_image_size_custom'])) { $errors[] = 'La dimensione personalizzata del footer non è valida.'; }
    if ($config['footer_font_family'] !== '' && !preg_match('/^[a-zA-Z0-9 À-ÿ,_\x27"-]{1,100}$/D', $config['footer_font_family'])) { $errors[] = 'La famiglia font del footer non è valida.'; }
    if ($config['footer_font_size'] !== '' && !preg_match('/^\d+(?:\.\d+)?(?:px|rem|em|%)$/D', $config['footer_font_size'])) { $errors[] = 'La dimensione font del footer non è valida.'; }
    foreach (['footer_background', 'footer_text_color', 'footer_text_secondary', 'footer_link_color', 'footer_link_hover', 'footer_border_color'] as $key) {
        if (!preg_match('/^#[0-9a-fA-F]{6}$/D', $config[$key])) { $errors[] = 'I colori del footer devono essere nel formato #000000.'; }
    }
    if ($config['footer_opacity'] === false || $config['footer_opacity'] < 0 || $config['footer_opacity'] > 100) {
        $errors[] = 'L’opacità del footer deve essere compresa tra 0 e 100.';
        $config['footer_opacity'] = 100;
    }
    if ($config['footer_border_opacity'] === false || $config['footer_border_opacity'] < 0 || $config['footer_border_opacity'] > 100 || $config['footer_border_width'] === false || $config['footer_border_width'] < 0 || $config['footer_border_width'] > 20) { $errors[] = 'Controlla opacità e spessore del bordo footer.'; }
    if ($config['footer_logo_height'] === false || $config['footer_logo_height'] < 24 || $config['footer_logo_height'] > 200) { $errors[] = 'L’altezza del logo footer deve essere tra 24 e 200 px.'; }
    foreach (['logo', 'background', 'background_mobile', 'background_tablet', 'background_wide', 'footer_logo', 'footer_background_image'] as $key) {
        if (!advent_image_url($config[$key])) { $errors[] = 'Usa URL http/https o percorsi immagini che iniziano con /.'; }
    }
    foreach (['footer_regulation_url', 'footer_privacy_url', 'footer_cookie_url', 'footer_assistance_url', 'footer_promoter_url', 'footer_delegate_url'] as $key) {
        if ($config[$key] !== '' && !preg_match('~^(?:https?://[^\s<>"\x27]+|/(?!/)[^\s<>"\x27]+)$~D', $config[$key])) { $errors[] = 'I collegamenti del footer devono essere URL http/https o percorsi che iniziano con /.'; }
    }
    if (($config['footer_button_label'] === '') !== ($config['footer_button_url'] === '')) { $errors[] = 'Inserisci sia il testo sia il link del pulsante footer.'; }
    if (strlen($config['footer_button_label']) > 200) { $errors[] = 'Il testo del pulsante footer è troppo lungo.'; }
    if (strlen($config['footer_button_url']) > 2048) { $errors[] = 'Il link del pulsante footer è troppo lungo.'; }
    if ($config['footer_button_url'] !== '' && !preg_match('~^(?:https?://[^\s<>"\x27]+|/(?!/)[^\s<>"\x27]+)$~D', $config['footer_button_url'])) { $errors[] = 'Il link del pulsante footer deve essere un URL http/https o un percorso che inizia con /.'; }
    foreach (['footer_button_background', 'footer_button_text_color', 'footer_button_hover_background'] as $key) {
        if (!preg_match('/^#[0-9a-fA-F]{6}$/D', $config[$key])) { $errors[] = 'I colori del pulsante footer devono essere nel formato #000000.'; break; }
    }
    if (!in_array($config['footer_button_size'], ['small', 'medium', 'large'], true) || !in_array($config['footer_button_width'], ['auto', 'full'], true)) { $errors[] = 'Scegli una dimensione valida per il pulsante footer.'; }
    if ($config['footer_button_radius'] === false || $config['footer_button_radius'] < 0 || $config['footer_button_radius'] > 32) { $errors[] = 'Gli angoli del pulsante footer devono essere tra 0 e 32 px.'; }
    foreach (['footer_title' => 180, 'footer_text' => 2000, 'footer_legal' => 3000, 'footer_legal_2' => 3000, 'footer_promoter' => 300, 'footer_delegate' => 300, 'footer_regulation_label' => 100, 'footer_privacy_label' => 100, 'footer_cookie_label' => 100, 'footer_assistance_label' => 100] as $key => $limit) {
        if (strlen($config[$key]) > $limit) { $errors[] = 'Un testo del footer supera la lunghezza consentita.'; }
    }
    // Keep configured boxes outside the current range, so shortening the period does not erase prizes.
    for ($n = 1; $n <= 62; $n++) {
        $row = is_array($input['days'][$n] ?? null) ? $input['days'][$n] : [];
        $config['days'][$n] = [];
        $type = is_string($row['type'] ?? null) ? $row['type'] : 'prize';
        $config['days'][$n]['type'] = in_array($type, ['prize', 'video', 'link'], true) ? $type : 'prize';
        foreach (['title', 'description', 'code', 'image', 'cover', 'active_cover', 'media_url', 'link_label'] as $key) {
            $value = is_string($row[$key] ?? null) ? trim($row[$key]) : '';
            $config['days'][$n][$key] = $value;
            $limit = $key === 'description' ? 2000 : ($key === 'media_url' ? 2048 : ($key === 'link_label' ? 80 : 500));
            if (strlen($value) > $limit) { $errors[] = "Casella $n: il campo $key è troppo lungo."; }
            if (in_array($key, ['image', 'cover', 'active_cover'], true) && !advent_image_url($value)) {
                $errors[] = "Casella $n: percorso immagine non valido.";
            }
        }
        $mediaUrl = $config['days'][$n]['media_url'];
        if ($mediaUrl !== '' && !preg_match('~^https://[^\s<>"\']+$~D', $mediaUrl)) {
            $errors[] = "Casella $n: il link deve iniziare con https://.";
        } elseif ($config['days'][$n]['type'] === 'video' && $mediaUrl !== '' && advent_youtube_id($mediaUrl) === '') {
            $errors[] = "Casella $n: inserisci un link YouTube valido.";
        }
        if ($config['published'] && isset($days[$n]) && $config['days'][$n]['title'] === '') {
            $errors[] = "Casella $n: inserisci un titolo prima di pubblicare.";
        }
        if ($config['published'] && isset($days[$n]) && $config['days'][$n]['type'] !== 'prize' && $mediaUrl === '') {
            $errors[] = "Casella $n: inserisci il link del video o della pagina prima di pubblicare.";
        }
    }
    return [$config, array_values(array_unique($errors))];
}

function advent_save(array $config): void
{
    $path = advent_config_path();
    $temporary = tempnam(dirname($path), '.advent-');
    if ($temporary === false) { throw new RuntimeException('Impossibile creare il file di configurazione.'); }
    try {
        $json = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        if (file_put_contents($temporary, $json, LOCK_EX) === false || !rename($temporary, $path)) {
            throw new RuntimeException('Impossibile salvare il calendario. Controlla i permessi della cartella app/config.');
        }
    } finally { if (is_file($temporary)) { unlink($temporary); } }
}

/** Same code normalization as the Pasqua project, including its generated check digit. */
function advent_ean13(string $code): string
{
    $code = trim($code);
    if ($code === '') { return ''; }
    $digits = preg_replace('/\D+/', '', $code);
    $seed = sprintf('%u', crc32($code));
    if ($seed === '0') { $seed = '1234567890'; }
    if ($digits === '') { $digits = $seed; }
    while (strlen($digits) < 12) { $digits .= $seed; }
    $payload = substr($digits, 0, 12);
    require_once __DIR__ . '/ean13_helpers.php';
    return $payload . ean13_payload_checksum($payload);
}

/** EAN-13 SVG with quiet zones and human-readable digits; no external runtime needed. */
function advent_barcode(string $code): string
{
    $ean = advent_ean13($code);
    if ($ean === '') { return ''; }
    $left = ['0001101','0011001','0010011','0111101','0100011','0110001','0101111','0111011','0110111','0001011'];
    $parity = ['LLLLLL','LLGLGG','LLGGLG','LLGGGL','LGLLGG','LGGLLG','LGGGLL','LGLGLG','LGLGGL','LGGLGL'];
    $invert = static function (string $bits): string { return strtr($bits, '01', '10'); };
    $bits = '101';
    for ($i = 1; $i <= 6; $i++) {
        $pattern = $left[(int) $ean[$i]];
        $bits .= $parity[(int) $ean[0]][$i - 1] === 'L' ? $pattern : strrev($invert($pattern));
    }
    $bits .= '01010';
    for ($i = 7; $i <= 12; $i++) { $bits .= $invert($left[(int) $ean[$i]]); }
    $bits .= '101';
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 234 110"><rect width="234" height="110" fill="white"/><g fill="black">';
    for ($i = 0; $i < strlen($bits); $i++) {
        if ($bits[$i] === '1') { $svg .= '<rect x="' . (22 + $i * 2) . '" y="8" width="2" height="76"/>'; }
    }
    return $svg . '<text x="117" y="103" text-anchor="middle" font-family="monospace" font-size="16">' . $ean . '</text></g></svg>';
}
