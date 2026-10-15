<?php
declare(strict_types=1);

/** Calendario a premi: la disponibilità è sempre verificata sul server (Europe/Rome). */
function advent_defaults(): array
{
    $year = (new DateTimeImmutable('now', new DateTimeZone('Europe/Rome')))->format('Y');
    return ['published' => false, 'allow_past_days' => false, 'logo_replaces_heading' => false, 'title' => 'Calendario d’Avvento',
        'intro' => 'Una sorpresa al giorno. Apri la casella di oggi e scopri il tuo premio.',
        'start_date' => "$year-12-01", 'end_date' => "$year-12-24",
        'logo' => '', 'background' => '', 'background_mobile' => '', 'background_tablet' => '', 'background_wide' => '',
        'accent' => '#185844', 'modal_overlay' => '#102a23', 'modal_overlay_opacity' => 72,
        'footer_enabled' => false, 'footer_layout' => 'centered', 'footer_use_main_logo' => true,
        'footer_logo' => '', 'footer_background_image' => '', 'footer_background' => '#ffffff',
        'footer_mode' => 'color', 'footer_image_position_vertical' => 'center', 'footer_image_position_horizontal' => 'center',
        'footer_image_size' => 'cover', 'footer_image_size_custom' => '', 'footer_image_repeat' => 'no-repeat',
        'footer_opacity' => 100, 'footer_custom_text_colors' => false, 'footer_text_color' => '#212529',
        'footer_text_secondary' => '#6c757d', 'footer_link_color' => '#0d6efd', 'footer_link_hover' => '#0a58ca',
        'footer_border_enabled' => false, 'footer_border_color' => '#000000', 'footer_border_opacity' => 10,
        'footer_border_width' => 1, 'footer_border_sides' => ['top'], 'footer_show_logo' => false, 'footer_logo_height' => 60,
        'footer_font_family' => '', 'footer_font_size' => '', 'footer_prize_total' => 0, 'footer_title' => '', 'footer_text' => '',
        'footer_legal' => '', 'footer_legal_2' => '', 'footer_promoter' => '', 'footer_promoter_url' => '',
        'footer_delegate' => '', 'footer_delegate_url' => '', 'footer_regulation_url' => '', 'footer_privacy_url' => '',
        'footer_cookie_url' => '', 'footer_assistance_url' => '', 'footer_regulation_label' => 'Regolamento completo',
        'footer_privacy_label' => 'Privacy Policy', 'footer_cookie_label' => 'Cookie Policy', 'footer_assistance_label' => 'Assistenza',
        'footer_blocks' => ['title' => true, 'dates' => true, 'prize_total' => true, 'links_regulation' => true, 'links_privacy' => true,
            'links_cookie' => false, 'links_assistance' => true, 'promoter' => true, 'delegate' => true,
            'legal_1' => false, 'legal_2' => false],
        'days' => []];
}

function advent_config_path(): string { return __DIR__ . '/../config/advent.json'; }

function advent_load(): array
{
    $path = advent_config_path();
    if (!is_file($path)) { return advent_defaults(); }
    $data = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
    if (!is_array($data)) { throw new RuntimeException('Configurazione calendario non valida.'); }
    return array_replace(advent_defaults(), $data);
}

function advent_escape($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
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

function advent_image_url(string $value): bool
{
    return $value === '' || (bool) preg_match('~^(?:https?://[^\s<>"\x27]+|/(?!/)[a-zA-Z0-9_./%\-]+)$~D', $value);
}

function advent_asset_url(string $value, string $projectBase): string
{
    if (str_starts_with($value, '/uploads/')) { return rtrim($projectBase, '/') . $value; }
    return $value;
}

function advent_validate(array $input): array
{
    $config = advent_defaults();
    $errors = [];
    foreach (['title', 'intro', 'start_date', 'end_date', 'logo', 'background', 'background_mobile', 'background_tablet', 'background_wide', 'accent', 'modal_overlay', 'footer_layout', 'footer_logo', 'footer_background_image', 'footer_background', 'footer_mode', 'footer_image_position_vertical', 'footer_image_position_horizontal', 'footer_image_size', 'footer_image_size_custom', 'footer_image_repeat', 'footer_text_color', 'footer_text_secondary', 'footer_link_color', 'footer_link_hover', 'footer_border_color', 'footer_font_family', 'footer_font_size', 'footer_title', 'footer_text', 'footer_legal', 'footer_legal_2', 'footer_promoter', 'footer_promoter_url', 'footer_delegate', 'footer_delegate_url', 'footer_regulation_url', 'footer_privacy_url', 'footer_cookie_url', 'footer_assistance_url', 'footer_regulation_label', 'footer_privacy_label', 'footer_cookie_label', 'footer_assistance_label'] as $key) {
        $config[$key] = is_string($input[$key] ?? null) ? trim($input[$key]) : '';
    }
    $config['modal_overlay_opacity'] = filter_var($input['modal_overlay_opacity'] ?? null, FILTER_VALIDATE_INT);
    $config['published'] = !empty($input['published']);
    $config['allow_past_days'] = !empty($input['allow_past_days']);
    $config['logo_replaces_heading'] = !empty($input['logo_replaces_heading']);
    $config['footer_enabled'] = !empty($input['footer_enabled']);
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
    if ($config['title'] === '' || strlen($config['title']) > 180) { $errors[] = 'Inserisci un titolo di massimo 180 caratteri.'; }
    if (strlen($config['intro']) > 2000) { $errors[] = 'Il testo introduttivo è troppo lungo (massimo 2000 caratteri).'; }
    $days = advent_days($config);
    if (!$days) { $errors[] = 'Scegli un intervallo valido da 1 a 62 giorni, con la fine successiva o uguale all’inizio.'; }
    if (!preg_match('/^#[0-9a-fA-F]{6}$/D', $config['accent'])) { $errors[] = 'Il colore deve essere nel formato #185844.'; }
    if (!preg_match('/^#[0-9a-fA-F]{6}$/D', $config['modal_overlay'])) { $errors[] = 'Il colore della velatura deve essere nel formato #102a23.'; }
    if ($config['modal_overlay_opacity'] === false || $config['modal_overlay_opacity'] < 0 || $config['modal_overlay_opacity'] > 100) {
        $errors[] = 'L’intensità della velatura deve essere compresa tra 0 e 100.';
        $config['modal_overlay_opacity'] = 72;
    }
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
    foreach (['footer_title' => 180, 'footer_text' => 2000, 'footer_legal' => 3000, 'footer_legal_2' => 3000, 'footer_promoter' => 300, 'footer_delegate' => 300, 'footer_regulation_label' => 100, 'footer_privacy_label' => 100, 'footer_cookie_label' => 100, 'footer_assistance_label' => 100] as $key => $limit) {
        if (strlen($config[$key]) > $limit) { $errors[] = 'Un testo del footer supera la lunghezza consentita.'; }
    }
    // Keep configured boxes outside the current range, so shortening the period does not erase prizes.
    for ($n = 1; $n <= 62; $n++) {
        $row = is_array($input['days'][$n] ?? null) ? $input['days'][$n] : [];
        $config['days'][$n] = [];
        foreach (['title', 'description', 'code', 'image', 'cover', 'active_cover'] as $key) {
            $value = is_string($row[$key] ?? null) ? trim($row[$key]) : '';
            $config['days'][$n][$key] = $value;
            if (strlen($value) > ($key === 'description' ? 2000 : 500)) { $errors[] = "Casella $n: il campo $key è troppo lungo."; }
            if (in_array($key, ['image', 'cover', 'active_cover'], true) && !advent_image_url($value)) {
                $errors[] = "Casella $n: percorso immagine non valido.";
            }
        }
        if ($config['published'] && isset($days[$n]) && $config['days'][$n]['title'] === '') {
            $errors[] = "Casella $n: inserisci il titolo del premio prima di pubblicare.";
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
