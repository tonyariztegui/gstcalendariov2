<?php
/** Shared public/manager rendering. Preview flags are assigned only by the authenticated manager route. */
$days = advent_days($calendar, $adventToday, $adventOpenAll);
$dayInput = $_GET['day'] ?? null;
$selectedDay = is_string($dayInput) && ctype_digit($dayInput) ? (int) $dayInput : 0;
$visible = $adventPreview || !empty($calendar['published']);
$result = null;
if (!$visible && !$adventFailure) {
    http_response_code(404);
    $adventFailure = 'Il calendario non è ancora disponibile. Torna a trovarci presto.';
} elseif ($visible && $dayInput !== null) {
    $result = advent_day_result($calendar, $selectedDay, $adventToday, $adventOpenAll);
    http_response_code($result['status']);
}
$query = $adventPreview ? ['mode' => $adventOpenAll ? 'all' : 'date', 'date' => ($adventToday ?? new DateTimeImmutable('now', new DateTimeZone('Europe/Rome')))->format('Y-m-d')] : [];
$backUrl = $adventPageUrl . ($query ? '?' . http_build_query($query) : '');
$isJsonRequest = isset($_GET['advent_json']) && $_GET['advent_json'] === '1';
if ($isJsonRequest) {
    header('Content-Type: application/json; charset=UTF-8');
    header('Cache-Control: no-store, private');
    if (!$visible || $result === null || isset($result['error'])) {
        echo json_encode([
            'ok' => false,
            'message' => $result['error'] ?? $adventFailure ?? 'Casella non disponibile.',
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
    $jsonPrize = $result['prize'];
    echo json_encode([
        'ok' => true,
        'data' => [
            'day' => $selectedDay,
            'date_label' => $result['day']['label'],
            'title' => (string) $jsonPrize['title'],
            'description' => (string) ($jsonPrize['description'] ?? ''),
            'image_url' => advent_asset_url((string) ($jsonPrize['image'] ?? ''), $adventBase),
            'code' => (string) ($jsonPrize['code'] ?? ''),
            'barcode_ean13' => advent_ean13((string) ($jsonPrize['code'] ?? '')),
            'barcode_svg_data_uri' => empty($jsonPrize['code']) ? '' : 'data:image/svg+xml;base64,' . base64_encode(advent_barcode((string) $jsonPrize['code'])),
        ],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
$accent = preg_match('/^#[0-9a-fA-F]{6}$/D', $calendar['accent']) ? $calendar['accent'] : '#185844';
$rgb = array_map(static function ($channel) {
    $value = hexdec($channel) / 255;
    return $value <= 0.04045 ? $value / 12.92 : (($value + 0.055) / 1.055) ** 2.4;
}, str_split(substr($accent, 1), 2));
$luminance = 0.2126 * $rgb[0] + 0.7152 * $rgb[1] + 0.0722 * $rgb[2];
$accentText = $luminance > 0.179 ? '#000000' : '#ffffff';
$overlay = preg_match('/^#[0-9a-fA-F]{6}$/D', (string) ($calendar['modal_overlay'] ?? '')) ? $calendar['modal_overlay'] : '#102a23';
$overlayOpacity = max(0, min(100, (int) ($calendar['modal_overlay_opacity'] ?? 72))) / 100;
$overlayChannels = sscanf($overlay, '#%02x%02x%02x');
$overlayAlpha = $overlayOpacity === 0.0 ? '0' : rtrim(rtrim(number_format($overlayOpacity, 2, '.', ''), '0'), '.');
$overlayColor = 'rgba(' . implode(',', $overlayChannels) . ',' . $overlayAlpha . ')';
$background = advent_image_url($calendar['background']) ? advent_asset_url($calendar['background'], $adventBase) : '';
$mobileBackground = advent_image_url($calendar['background_mobile']) ? advent_asset_url($calendar['background_mobile'], $adventBase) : '';
$tabletBackground = advent_image_url($calendar['background_tablet']) ? advent_asset_url($calendar['background_tablet'], $adventBase) : '';
$wideBackground = advent_image_url($calendar['background_wide']) ? advent_asset_url($calendar['background_wide'], $adventBase) : '';
$footerBg = preg_match('/^#[0-9a-fA-F]{6}$/D', (string) ($calendar['footer_background'] ?? '')) ? $calendar['footer_background'] : '#ffffff';
$footerBgChannels = sscanf($footerBg, '#%02x%02x%02x');
$footerOpacity = max(0, min(100, (int) ($calendar['footer_opacity'] ?? 100))) / 100;
$footerAlpha = $footerOpacity === 0.0 ? '0' : rtrim(rtrim(number_format($footerOpacity, 2, '.', ''), '0'), '.');
$footerBgColor = 'rgba(' . implode(',', $footerBgChannels) . ',' . $footerAlpha . ')';
$footerCustomColors = !empty($calendar['footer_custom_text_colors']);
$footerTextColor = $footerCustomColors && preg_match('/^#[0-9a-fA-F]{6}$/D', (string) ($calendar['footer_text_color'] ?? '')) ? $calendar['footer_text_color'] : '#233c34';
$footerSecondaryColor = $footerCustomColors && preg_match('/^#[0-9a-fA-F]{6}$/D', (string) ($calendar['footer_text_secondary'] ?? '')) ? $calendar['footer_text_secondary'] : '#536459';
$footerLinkColor = $footerCustomColors && preg_match('/^#[0-9a-fA-F]{6}$/D', (string) ($calendar['footer_link_color'] ?? '')) ? $calendar['footer_link_color'] : $accent;
$footerLinkHover = $footerCustomColors && preg_match('/^#[0-9a-fA-F]{6}$/D', (string) ($calendar['footer_link_hover'] ?? '')) ? $calendar['footer_link_hover'] : $accent;
$footerBackgroundImage = $calendar['footer_mode'] === 'image' && advent_image_url((string) ($calendar['footer_background_image'] ?? '')) ? advent_asset_url((string) $calendar['footer_background_image'], $adventBase) : '';
$footerBorderChannels = sscanf((string) $calendar['footer_border_color'], '#%02x%02x%02x');
$footerBorderColor = 'rgba(' . implode(',', $footerBorderChannels) . ',' . max(0, min(100, (int) $calendar['footer_border_opacity'])) / 100 . ')';
$footerBorder = !empty($calendar['footer_border_enabled']) ? max(0, min(20, (int) $calendar['footer_border_width'])) . 'px solid ' . $footerBorderColor : '0 solid transparent';
$footerSides = is_array($calendar['footer_border_sides'] ?? null) ? $calendar['footer_border_sides'] : [];
$footerImageSize = $calendar['footer_image_size'] === 'custom' ? ($calendar['footer_image_size_custom'] ?: 'cover') : $calendar['footer_image_size'];
$gridMobile = max(1, min(2, (int) ($calendar['grid_columns_mobile'] ?? 2)));
$gridTablet = max(2, min(4, (int) ($calendar['grid_columns_tablet'] ?? 3)));
$gridDesktop = max(2, min(6, (int) ($calendar['grid_columns_desktop'] ?? 4)));
$gridGap = max(0, min(40, (int) ($calendar['grid_gap'] ?? 16)));
$gridRadius = max(0, min(32, (int) ($calendar['grid_tile_radius'] ?? 16)));
$gridRatio = ['square' => '1', 'classic' => '1.2', 'wide' => '1.5'][$calendar['grid_tile_ratio'] ?? 'classic'] ?? '1.2';
$gridImageFit = ($calendar['grid_image_fit'] ?? 'contain') === 'cover' ? 'cover' : 'contain';
$logoWidthMobile = max(60, min(1000, (int) ($calendar['logo_width_mobile'] ?? 320)));
$logoWidthTablet = max(60, min(1000, (int) ($calendar['logo_width_tablet'] ?? 480)));
$logoWidthDesktop = max(60, min(1000, (int) ($calendar['logo_width_desktop'] ?? 700)));
$logoWidthWide = max(60, min(1000, (int) ($calendar['logo_width_wide'] ?? 850)));
$style = '--calendar-accent:' . $accent . ';--calendar-accent-text:' . $accentText . ';--calendar-modal-overlay:' . $overlayColor . ';--calendar-bg:url(' . json_encode($background) . ');--calendar-mobile-bg:url(' . json_encode($mobileBackground ?: $background) . ');--calendar-tablet-bg:url(' . json_encode($tabletBackground ?: $background) . ');--calendar-wide-bg:url(' . json_encode($wideBackground ?: $background) . ');--calendar-footer-bg:' . $footerBgColor . ';--calendar-footer-image:url(' . json_encode($footerBackgroundImage) . ');--calendar-footer-text:' . $footerTextColor . ';--calendar-footer-secondary:' . $footerSecondaryColor . ';--calendar-footer-link:' . $footerLinkColor . ';--calendar-footer-link-hover:' . $footerLinkHover . ';--calendar-footer-font:' . ($calendar['footer_font_family'] ?: 'inherit') . ';--calendar-footer-font-size:' . ($calendar['footer_font_size'] ?: 'inherit') . ';--calendar-footer-position:' . $calendar['footer_image_position_horizontal'] . ' ' . $calendar['footer_image_position_vertical'] . ';--calendar-footer-size:' . $footerImageSize . ';--calendar-footer-repeat:' . $calendar['footer_image_repeat'] . ';--calendar-footer-border-top:' . (in_array('top', $footerSides, true) ? $footerBorder : '0 solid transparent') . ';--calendar-footer-border-right:' . (in_array('right', $footerSides, true) ? $footerBorder : '0 solid transparent') . ';--calendar-footer-border-bottom:' . (in_array('bottom', $footerSides, true) ? $footerBorder : '0 solid transparent') . ';--calendar-footer-border-left:' . (in_array('left', $footerSides, true) ? $footerBorder : '0 solid transparent') . ';';
?>
<!doctype html>
<html lang="it">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= advent_escape($calendar['title']) ?></title>
    <link rel="stylesheet" href="<?= advent_escape($adventBase) ?>/public/assets/css/advent-calendar.css">
    <style>
        .advent-grid{grid-template-columns:repeat(<?= $gridMobile ?>,minmax(0,1fr));gap:<?= $gridGap ?>px}
        .advent-tile{border-radius:<?= $gridRadius ?>px}
        .advent-tile-art{aspect-ratio:<?= $gridRatio ?>}
        .advent-tile-art img{object-fit:<?= $gridImageFit ?>}
        @media(min-width:600px){.advent-grid{grid-template-columns:repeat(<?= $gridTablet ?>,minmax(0,1fr))}}
        @media(min-width:1000px){.advent-grid{grid-template-columns:repeat(<?= $gridDesktop ?>,minmax(0,1fr))}}
        .advent-modal::backdrop{background:var(--calendar-modal-overlay,#102a23b8)}
        .advent-tile.is-past.is-revealed{border-style:dashed;border-color:#a9a69d;background:#f2f0e9}
        .advent-tile.is-past.is-revealed .advent-tile-art{filter:saturate(.58);opacity:.84}
        .advent-tile.is-past.is-revealed .advent-tile-caption{background:#f2f0e9;color:#59615c}
        .advent-expired-badge{position:absolute;z-index:2;top:10px;left:10px;padding:4px 9px;border-radius:999px;background:#3d4943dd;color:#fff;font-size:10px;font-weight:800;letter-spacing:.08em;text-transform:uppercase;box-shadow:0 2px 8px #0002}
        .advent-visually-hidden{position:absolute!important;width:1px!important;height:1px!important;padding:0!important;margin:-1px!important;overflow:hidden!important;clip:rect(0,0,0,0)!important;white-space:nowrap!important;border:0!important}
        .advent-hero .advent-logo.is-heading{display:block;width:min(100%,<?= $logoWidthMobile ?>px);max-width:none;max-height:none;height:auto;margin:0 auto 20px;object-fit:contain}
        @media(min-width:600px){.advent-hero .advent-logo.is-heading{width:min(100%,<?= $logoWidthTablet ?>px)}}
        @media(min-width:1000px){.advent-hero .advent-logo.is-heading{width:min(100%,<?= $logoWidthDesktop ?>px)}}
        @media(min-width:1921px){.advent-hero .advent-logo.is-heading{width:min(100%,<?= $logoWidthWide ?>px)}}
        .advent-footer{position:relative;color:var(--calendar-footer-text);font-family:var(--calendar-footer-font);font-size:var(--calendar-footer-font-size);background-color:var(--calendar-footer-bg);background-image:var(--calendar-footer-image);background-position:var(--calendar-footer-position);background-size:var(--calendar-footer-size);background-repeat:var(--calendar-footer-repeat);border-top:var(--calendar-footer-border-top);border-right:var(--calendar-footer-border-right);border-bottom:var(--calendar-footer-border-bottom);border-left:var(--calendar-footer-border-left)}
        .advent-footer-inner{max-width:1120px;margin:auto;padding:32px 20px;display:grid;gap:20px;align-items:center}
        .advent-footer.is-centered .advent-footer-inner{text-align:center;justify-items:center}
        .advent-footer.is-columns .advent-footer-inner{grid-template-columns:minmax(0,1fr);text-align:center}
        .advent-footer-logo{display:block;max-width:240px;max-height:100px;object-fit:contain}
        .advent-footer h2{margin:0 0 8px;font:600 22px/1.3 Georgia,serif}.advent-footer p{margin:6px 0}.advent-footer-secondary,.advent-footer-legal{color:var(--calendar-footer-secondary)}.advent-footer-legal{font-size:12px;max-width:760px}.advent-footer-links{display:flex;gap:8px 18px;flex-wrap:wrap;justify-content:center}.advent-footer a{color:var(--calendar-footer-link);font-weight:700;text-underline-offset:3px}.advent-footer a:hover,.advent-footer a:focus{color:var(--calendar-footer-link-hover)}
        @media (min-width:760px){.advent-footer.is-columns .advent-footer-inner{grid-template-columns:repeat(3,minmax(0,1fr));text-align:left}.advent-footer.is-columns .advent-footer-links{justify-content:center;flex-direction:column;align-items:center}.advent-footer.is-columns .advent-footer-entities{text-align:right}.advent-footer.is-columns .advent-footer-legal{grid-column:1/-1}}
        @media (min-width:600px) and (max-width:999px){.advent-page{background-image:var(--calendar-tablet-bg)}}
        @media (min-width:1600px){.advent-page{background-image:var(--calendar-wide-bg)}}
    </style>
</head>
<body class="advent-page" style="<?= advent_escape($style) ?>">
<?php if ($adventPreview): ?>
<aside class="advent-preview" aria-label="Controlli di anteprima">
    <div><strong>Anteprima riservata</strong><span>Le modifiche mostrate non sono necessariamente salvate.</span></div>
    <form method="get" action="<?= advent_escape($adventPageUrl) ?>">
        <label for="preview-mode">Visualizzazione</label><select id="preview-mode" name="mode"><option value="all">Tutte le caselle aperte</option><option value="date" <?= !$adventOpenAll ? 'selected' : '' ?>>Simula una data</option></select>
        <label for="preview-date">Data</label><input id="preview-date" type="date" name="date" value="<?= advent_escape($query['date']) ?>" required>
        <button type="submit">Aggiorna anteprima</button>
        <a href="<?= advent_escape($adventBase) ?>/manager/calendari-avvento">Torna alla configurazione</a>
    </form>
</aside>
<?php endif; ?>
<main class="advent-main" id="inizio">
    <header class="advent-hero">
        <?php $logoIsHeading = $visible && $calendar['logo'] !== '' && !empty($calendar['logo_replaces_heading']); ?>
        <?php if ($visible && $calendar['logo'] !== ''): ?><img class="advent-logo<?= $logoIsHeading ? ' is-heading' : '' ?>" src="<?= advent_escape(advent_asset_url($calendar['logo'], $adventBase)) ?>" alt="<?= $logoIsHeading ? '' : 'Logo ' . advent_escape($calendar['title']) ?>"><?php endif; ?>
        <p class="advent-kicker<?= $logoIsHeading ? ' advent-visually-hidden' : '' ?>">UNA SORPRESA AL GIORNO</p>
        <h1 class="<?= $logoIsHeading ? 'advent-visually-hidden' : '' ?>"><?= advent_escape($calendar['title']) ?></h1>
        <?php if ($visible): ?><p class="advent-intro<?= $logoIsHeading ? ' advent-visually-hidden' : '' ?>"><?= nl2br(advent_escape($calendar['intro'])) ?></p><?php endif; ?>
    </header>
    <?php if ($adventFailure): ?>
        <section class="advent-message" role="status"><h2>A presto!</h2><p><?= advent_escape($adventFailure) ?></p></section>
    <?php elseif ($result !== null): ?>
        <a class="advent-back" href="<?= advent_escape($backUrl) ?>">← Torna al calendario</a>
        <section class="advent-prize">
        <?php if (isset($result['error'])): ?>
            <h2>Casella non disponibile</h2><p role="status"><?= advent_escape($result['error']) ?></p>
        <?php else: $prize = $result['prize']; ?>
            <span class="advent-kicker">CASELLA <?= $selectedDay ?> · <?= advent_escape($result['day']['label']) ?></span>
            <h2><?= advent_escape($prize['title']) ?></h2>
            <?php if (!empty($prize['image'])): ?><img class="advent-prize-image" src="<?= advent_escape(advent_asset_url($prize['image'], $adventBase)) ?>" alt="<?= advent_escape($prize['title']) ?>"><?php endif; ?>
            <?php if (!empty($prize['description'])): ?><p><?= nl2br(advent_escape($prize['description'])) ?></p><?php endif; ?>
            <?php if (!empty($prize['code'])): ?><div class="advent-barcode"><img src="data:image/svg+xml;base64,<?= base64_encode(advent_barcode($prize['code'])) ?>" alt="Barcode EAN-13 <?= advent_escape(advent_ean13($prize['code'])) ?>"><p>Codice premio: <strong><?= advent_escape($prize['code']) ?></strong></p></div><?php endif; ?>
        <?php endif; ?>
        </section>
    <?php else: ?>
        <div class="advent-legend"><span>● <?= $adventOpenAll ? 'Anteprima: tutte le caselle disponibili' : 'Disponibile oggi' ?></span><span><?= $adventOpenAll ? 'Prova ogni premio prima di pubblicare' : (!empty($calendar['allow_past_days']) ? 'I premi passati sono mostrati nelle caselle' : 'Le altre caselle si aprono nella loro data') ?></span></div>
        <?php
        $todayIso = ($adventToday ?? new DateTimeImmutable('now', new DateTimeZone('Europe/Rome')))->format('Y-m-d');
        if (!$adventOpenAll && $todayIso < $calendar['start_date']): ?>
            <p class="advent-notice" role="status">Il calendario inizia il <?= advent_escape(advent_date($calendar['start_date'])->format('d/m/Y')) ?>. Ti aspettiamo!</p>
        <?php elseif (!$adventOpenAll && $todayIso > $calendar['end_date']): ?>
            <p class="advent-notice" role="status">Il calendario è terminato. Grazie per aver partecipato!</p>
        <?php endif; ?>
        <div class="advent-grid" aria-label="Caselle del calendario">
        <?php foreach ($days as $number => $day):
            $prize = $calendar['days'][$number] ?? [];
            $revealPastPrize = !$adventOpenAll && !empty($calendar['allow_past_days']) && $day['state'] === 'past';
            $cover = $revealPastPrize && !empty($prize['image']) ? $prize['image'] : (($day['state'] === 'today' && !empty($prize['active_cover'])) ? $prize['active_cover'] : ($prize['cover'] ?? ''));
            $cover = advent_asset_url($cover, $adventBase);
            $label = $day['is_open'] ? 'Apri la sorpresa' : ($revealPastPrize ? ((string) ($prize['title'] ?? '') ?: 'Premio non configurato') : ($day['state'] === 'past' ? 'Scaduta' : 'Non ancora disponibile'));
            $tag = $day['is_open'] ? 'a' : 'div';
            $href = $adventPageUrl . '?' . http_build_query(array_merge($query, ['day' => $number]));
        ?>
            <<?= $tag ?> class="advent-tile <?= $day['is_open'] ? 'is-open' : 'is-' . $day['state'] ?><?= $revealPastPrize ? ' is-revealed' : '' ?>" <?= $day['is_open'] ? 'href="' . advent_escape($href) . '"' : 'aria-disabled="true"' ?> aria-label="Casella <?= $number ?>, <?= advent_escape($day['label'] . ', ' . $label) ?>">
                <div class="advent-tile-art"><?php if ($revealPastPrize): ?><span class="advent-expired-badge">Scaduta</span><?php endif; ?><?php if ($cover !== ''): ?><img src="<?= advent_escape($cover) ?>" alt="" loading="lazy"><?php else: ?><span class="advent-tile-number" aria-hidden="true"><?= $number ?></span><span class="advent-star" aria-hidden="true">✦</span><?php endif; ?></div>
                <div class="advent-tile-caption"><strong><?= advent_escape($day['label']) ?></strong><span><?= $revealPastPrize ? 'Scaduta · ' : '' ?><?= advent_escape($label) ?></span></div>
            </<?= $tag ?>>
        <?php endforeach; ?>
        </div>
        <?php if (!$days): ?><p class="advent-notice" role="status">Il calendario è in preparazione.</p><?php endif; ?>
        <p class="advent-footnote"><?= !empty($calendar['allow_past_days']) ? 'I premi trascorsi sono visibili direttamente nelle caselle. Le prossime si aprono nel giorno indicato.' : 'Ogni sorpresa è disponibile solo nel giorno indicato.' ?> Orario italiano.</p>
    <?php endif; ?>
</main>
<?php if ($visible && !empty($calendar['footer_enabled'])) { require __DIR__ . '/advent_footer.php'; } ?>
<?php if ($visible && $result === null): ?>
<dialog class="advent-modal" id="advent-prize-modal" aria-labelledby="advent-modal-title" aria-describedby="advent-modal-description">
    <div class="advent-modal-card">
        <button class="advent-modal-close" type="button" aria-label="Chiudi il premio">×</button>
        <div class="advent-modal-loading" role="status">Apro la sorpresa…</div>
        <div class="advent-modal-error" role="alert" hidden></div>
        <article class="advent-modal-content" hidden>
            <span class="advent-kicker" id="advent-modal-day"></span>
            <h2 id="advent-modal-title" tabindex="-1"></h2>
            <img class="advent-prize-image" id="advent-modal-image" src="" alt="" hidden>
            <p id="advent-modal-description"></p>
            <div class="advent-barcode" id="advent-modal-barcode" hidden>
                <img src="" alt=""><p>Codice premio: <strong></strong></p>
            </div>
        </article>
    </div>
</dialog>
<script src="<?= advent_escape($adventBase) ?>/public/assets/js/advent-calendar.js" defer></script>
<?php endif; ?>
</body>
</html>
