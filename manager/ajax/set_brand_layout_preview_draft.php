<?php
/**
 * Brouillon branding/layout en session pour preview_brand_layout.php?live=1
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['user_name']) || $_SESSION['user_name'] !== 'pm_admin') {
    echo json_encode(['ok' => false, 'error' => 'Unauthorized']);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

require_once __DIR__ . '/../config/config.php';

global $content_defaults;

$p = static function (string $key, string $default = ''): string {
    return isset($_POST[$key]) ? trim((string) $_POST[$key]) : $default;
};
$pint = static function (string $key, int $default = 0): int {
    return isset($_POST[$key]) ? (int) $_POST[$key] : $default;
};

$logoDisplayMode = strtolower($p('logo_display_mode', 'logo'));
if (!in_array($logoDisplayMode, ['none', 'logo', 'header', 'both'], true)) {
    $logoDisplayMode = 'logo';
}

$logoDesktopSize = normalize_logo_desktop_size($p('logo_desktop_size', 'medium'));

$brandPageLayout = [];
foreach (brand_layout_page_definitions() as $pageKey => $_label) {
    $rawPagePost = $_POST['brand_page_layout'][$pageKey] ?? [];
    $rawPagePost = is_array($rawPagePost) ? $rawPagePost : [];
    $preset = strtolower(trim((string) ($rawPagePost['preset'] ?? 'attached')));
    $customizePerSize = !empty($rawPagePost['customize_per_size']);
    $mobileOverlap = brand_layout_clamp_overlap((int) ($rawPagePost['mobile']['overlap_px'] ?? 0), 'mobile');
    $tabletOverlap = $customizePerSize
        ? brand_layout_clamp_overlap((int) ($rawPagePost['tablet']['overlap_px'] ?? $mobileOverlap), 'tablet')
        : $mobileOverlap;
    $desktopOverlap = $customizePerSize
        ? brand_layout_clamp_overlap((int) ($rawPagePost['desktop']['overlap_px'] ?? $mobileOverlap), 'desktop')
        : $mobileOverlap;
    $brandPageLayout[$pageKey] = normalize_brand_layout_item([
        'preset' => $preset,
        'customize_per_size' => $customizePerSize,
        'mobile' => ['overlap_px' => $mobileOverlap],
        'tablet' => ['overlap_px' => $tabletOverlap],
        'desktop' => ['overlap_px' => $desktopOverlap],
    ]);
}

$backgrounds = [];
foreach (['default', 'mobile', 'tablet', 'laptop', 'desktop', 'ultrawide'] as $bk) {
    $backgrounds[$bk] = $p('backgrounds_' . $bk, '');
}

$bgStyle = is_array($content_defaults['background_style'] ?? null) ? $content_defaults['background_style'] : [];
$bgStyleInput = [
    'position_vertical' => $p('background_style_position_vertical', (string) ($bgStyle['position_vertical'] ?? 'center')),
    'position_horizontal' => $p('background_style_position_horizontal', (string) ($bgStyle['position_horizontal'] ?? 'center')),
    'size' => $p('background_style_size', (string) ($bgStyle['size'] ?? 'cover')),
    'size_custom' => $p('background_style_size_custom', (string) ($bgStyle['size_custom'] ?? '')),
    'repeat' => $p('background_style_repeat', (string) ($bgStyle['repeat'] ?? 'no-repeat')),
    'attachment' => $p('background_style_attachment', (string) ($bgStyle['attachment'] ?? 'fixed')),
    'customize_per_size' => isset($_POST['background_style_customize_per_size']) && $_POST['background_style_customize_per_size'] === '1',
];
foreach (['mobile', 'tablet', 'laptop', 'desktop', 'ultrawide'] as $sizeKey) {
    $sizeFallback = is_array($bgStyle[$sizeKey] ?? null) ? $bgStyle[$sizeKey] : [];
    $bgStyleInput[$sizeKey] = [
        'position_vertical' => $p('background_style_' . $sizeKey . '_position_vertical', (string) ($sizeFallback['position_vertical'] ?? 'center')),
        'position_horizontal' => $p('background_style_' . $sizeKey . '_position_horizontal', (string) ($sizeFallback['position_horizontal'] ?? 'center')),
        'size' => $p('background_style_' . $sizeKey . '_size', (string) ($sizeFallback['size'] ?? 'cover')),
        'size_custom' => $p('background_style_' . $sizeKey . '_size_custom', (string) ($sizeFallback['size_custom'] ?? '')),
        'repeat' => $p('background_style_' . $sizeKey . '_repeat', (string) ($sizeFallback['repeat'] ?? 'no-repeat')),
        'attachment' => $p('background_style_' . $sizeKey . '_attachment', (string) ($sizeFallback['attachment'] ?? 'fixed')),
    ];
}

$previewPage = $p('preview_page', 'registration');
if (!array_key_exists($previewPage, brand_layout_page_definitions())) {
    $previewPage = 'registration';
}
$previewDevice = $p('preview_device', 'mobile');
if (!in_array($previewDevice, ['mobile', 'tablet', 'desktop'], true)) {
    $previewDevice = 'mobile';
}

$helpTitleFontSize = function_exists('contest_resolve_font_size')
    ? contest_resolve_font_size($p('help_accordion_title_font_size', ''))
    : '';
$helpTitleMarginTop = max(0, min(120, $pint('help_accordion_title_margin_top', 0)));
$helpPageFontFamily = $p('help_page_font_family', '');
$helpPageFontSize = function_exists('contest_resolve_font_size')
    ? contest_resolve_font_size($p('help_page_font_size', ''))
    : '';

$_SESSION['brand_layout_preview_draft'] = [
    'preview_page' => $previewPage,
    'preview_device' => $previewDevice,
    'logo_display_mode' => $logoDisplayMode,
    'logo' => $p('logo', ''),
    'logo_mobile' => $p('logo_mobile', ''),
    'header_image' => $p('header_image', ''),
    'header_image_mobile' => $p('header_image_mobile', ''),
    'logo_desktop_size' => $logoDesktopSize,
    'logo_desktop_width_percent' => max(40, min(100, $pint('logo_desktop_width_percent', 85))),
    'logo_desktop_max_height_px' => max(0, min(500, $pint('logo_desktop_max_height_px', 0))),
    'logo_top_spacing' => normalize_logo_top_spacing_config([
        'mobile_px' => brand_layout_clamp_logo_top_spacing($pint('logo_top_spacing_mobile_px', 0)),
        'tablet_px' => brand_layout_clamp_logo_top_spacing($pint('logo_top_spacing_tablet_px', 0)),
        'desktop_px' => brand_layout_clamp_logo_top_spacing($pint('logo_top_spacing_desktop_px', 0)),
    ]),
    'brand_header_logo_gap' => normalize_brand_header_logo_gap_config([
        'mobile_px' => brand_layout_clamp_header_logo_gap($pint('brand_header_logo_gap_mobile_px', 0)),
        'tablet_px' => brand_layout_clamp_header_logo_gap($pint('brand_header_logo_gap_tablet_px', 0)),
        'desktop_px' => brand_layout_clamp_header_logo_gap($pint('brand_header_logo_gap_desktop_px', 0)),
    ]),
    'brand_header_top_spacing' => normalize_brand_header_top_spacing_config([
        'mobile_px' => brand_layout_clamp_header_top_spacing($pint('brand_header_top_spacing_mobile_px', 0)),
        'tablet_px' => brand_layout_clamp_header_top_spacing($pint('brand_header_top_spacing_tablet_px', 0)),
        'desktop_px' => brand_layout_clamp_header_top_spacing($pint('brand_header_top_spacing_desktop_px', 0)),
    ]),
    'brand_page_layout' => $brandPageLayout,
    'background_mode' => $p('background_mode', 'image') === 'color' ? 'color' : 'image',
    'background_scope' => $p('background_scope', 'full_body') === 'header_main' ? 'header_main' : 'full_body',
    'background_color' => $p('background_color', '#ffffff'),
    'backgrounds' => $backgrounds,
    'background_style' => $bgStyleInput,
    'help_page_style' => [
        'font_family' => $helpPageFontFamily,
        'font_size' => $helpPageFontSize,
        'accordion' => [
            'title_font_size' => $helpTitleFontSize,
            'title_margin_top' => $helpTitleMarginTop,
        ],
    ],
];

echo json_encode(['ok' => true]);
