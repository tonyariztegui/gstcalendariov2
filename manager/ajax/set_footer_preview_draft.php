<?php
/**
 * Brouillon footer (style + layout) en session pour preview_footer.php?live=1
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

$p = static function (string $key, string $default = ''): string {
    return isset($_POST[$key]) ? trim((string) $_POST[$key]) : $default;
};
$pint = static function (string $key, int $default = 0): int {
    return isset($_POST[$key]) ? (int) $_POST[$key] : $default;
};

$footerLayoutBlockKeys = [
    'contest_title', 'contest_dates', 'prize_total',
    'links_regolamento', 'links_privacy', 'links_cookie_policy', 'links_assistenza',
    'promoter', 'delegate', 'legal_block_1', 'legal_block_2',
];
$footerLayoutBlocks = [];
foreach ($footerLayoutBlockKeys as $blockKey) {
    $footerLayoutBlocks[$blockKey] = isset($_POST['footer_layout_block_' . $blockKey]) && $_POST['footer_layout_block_' . $blockKey] === '1';
}

$footerLayoutTemplate = $p('footer_layout_template', 'three_columns');
if (!in_array($footerLayoutTemplate, ['three_columns', 'centered_compact'], true)) {
    $footerLayoutTemplate = 'three_columns';
}

$borderSides = [];
if (isset($_POST['footer_style_border_sides']) && is_array($_POST['footer_style_border_sides'])) {
    $borderSides = array_values(array_intersect($_POST['footer_style_border_sides'], ['top', 'right', 'bottom', 'left']));
}
$borderEnabled = isset($_POST['footer_style_border_enabled']) && $_POST['footer_style_border_enabled'] === '1';

$_SESSION['footer_preview_draft'] = [
    'footer_style' => [
        'mode' => $p('footer_style_mode', 'color') === 'image' ? 'image' : 'color',
        'background' => $p('footer_style_background', '#ffffff'),
        'image' => $p('footer_style_image', ''),
        'opacity' => max(0, min(100, $pint('footer_style_opacity', 100))),
        'image_position_vertical' => $p('footer_style_image_position_vertical', 'center'),
        'image_position_horizontal' => $p('footer_style_image_position_horizontal', 'center'),
        'image_size' => $p('footer_style_image_size', 'cover'),
        'image_size_custom' => $p('footer_style_image_size_custom', ''),
        'image_repeat' => $p('footer_style_image_repeat', 'no-repeat'),
        'text_primary' => $p('footer_style_text_primary', '#212529'),
        'text_secondary' => $p('footer_style_text_secondary', '#6c757d'),
        'link' => $p('footer_style_link', '#0d6efd'),
        'link_hover' => $p('footer_style_link_hover', '#0a58ca'),
        'border_color' => $p('footer_style_border_color', '#000000'),
        'border_opacity' => max(0, min(100, $pint('footer_style_border_opacity', 10))),
        'border_width' => $borderEnabled ? max(0, min(20, $pint('footer_style_border_width', 1))) : 0,
        'border_sides' => $borderEnabled ? $borderSides : [],
    ],
    'footer_layout' => [
        'template' => $footerLayoutTemplate,
        'show_logo' => isset($_POST['footer_layout_show_logo']) && $_POST['footer_layout_show_logo'] === '1',
        'use_contest_logo' => isset($_POST['footer_layout_use_contest_logo']) && $_POST['footer_layout_use_contest_logo'] === '1',
        'logo_image' => $p('footer_layout_logo_image', ''),
        'logo_height_px' => max(24, min(200, $pint('footer_layout_logo_height_px', 60))),
        'font_family' => $p('footer_layout_font_family', ''),
        'font_size' => $p('footer_layout_font_size', ''),
        'blocks' => $footerLayoutBlocks,
        'texts' => [
            'contest_title' => trim((string) ($_POST['footer_layout_text_contest_title'] ?? '')),
            'intro_line' => trim((string) ($_POST['footer_layout_text_intro_line'] ?? '')),
            'legal_html_1' => trim((string) ($_POST['footer_layout_text_legal_html_1'] ?? '')),
            'legal_html_2' => trim((string) ($_POST['footer_layout_text_legal_html_2'] ?? '')),
            'link_regolamento_label' => $p('footer_layout_text_link_regolamento_label', 'Regolamento completo'),
            'link_privacy_label' => $p('footer_layout_text_link_privacy_label', 'Privacy Policy'),
            'link_cookie_policy_label' => $p('footer_layout_text_link_cookie_policy_label', 'Cookie Policy'),
            'link_assistenza_label' => $p('footer_layout_text_link_assistenza_label', 'Assistenza'),
        ],
    ],
    'cookie_policy_link' => $p('cookie_policy_link', ''),
    'cookie_consent_enabled' => isset($_POST['cookie_consent_enabled']) && $_POST['cookie_consent_enabled'] === '1',
    'style_customization' => [
        'footer_text_colors' => isset($_POST['style_customization_footer_text_colors']) && $_POST['style_customization_footer_text_colors'] === '1',
    ],
    'colors' => [
        'primary' => $p('colors_primary', ''),
        'secondary' => $p('colors_secondary', ''),
        'tertiary' => $p('colors_tertiary', ''),
        'text' => $p('colors_text', ''),
    ],
];

if (empty($_SESSION['footer_preview_draft']['cookie_consent_enabled'])) {
    $_SESSION['footer_preview_draft']['footer_layout']['blocks']['links_cookie_policy'] = false;
}
echo json_encode(['ok' => true]);
