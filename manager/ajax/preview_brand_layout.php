<?php
/**
 * Anteprima layout brand (manager). ?live=1 utilise la session brouillon.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (empty($_SESSION['user_name']) || $_SESSION['user_name'] !== 'pm_admin') {
    header('HTTP/1.1 403 Forbidden');
    exit('Accesso non autorizzato.');
}

$BASE_HREF = '../../public/';
require_once __DIR__ . '/../config/config.php';

if (!function_exists('brand_layout_preview_container_suffix')) {
    function brand_layout_preview_container_suffix(string $pageKey): string
    {
        static $map = [
            'registration' => 'registration',
            'help' => 'help',
            'waiting_page' => 'waiting-page',
            'participation_page' => 'participation-page',
            'waiting_page_ended' => 'waiting-page-ended',
            'thankyou_instantwin_win_page' => 'thankyou-win',
            'thankyou_instantwin_loss_page' => 'thankyou-loss',
            'thankyou_instantwin_quiz_failed_page' => 'thankyou-quiz-failed',
            'thankyou_estrazione_page' => 'thankyou-estrazione',
            'thankyou_estrazione_quiz_failed_page' => 'thankyou-estrazione-quiz-failed',
            'quiz_page' => 'quiz-page',
            'quiz_choice_page' => 'quiz-choice-page',
        ];
        return $map[$pageKey] ?? 'registration';
    }
}

if (!function_exists('apply_brand_layout_preview_draft')) {
    function apply_brand_layout_preview_draft(array $draft, array &$content_config, array &$contest_config): void
    {
        $scalarKeys = [
            'logo_display_mode', 'logo', 'logo_mobile', 'header_image', 'header_image_mobile',
            'logo_desktop_size', 'logo_desktop_width_percent', 'logo_desktop_max_height_px',
            'background_mode', 'background_scope', 'background_color',
        ];
        foreach ($scalarKeys as $key) {
            if (array_key_exists($key, $draft)) {
                $content_config[$key] = $draft[$key];
                $contest_config[$key] = $draft[$key];
            }
        }
        if (isset($draft['backgrounds']) && is_array($draft['backgrounds'])) {
            $content_config['backgrounds'] = $draft['backgrounds'];
            $contest_config['backgrounds'] = $draft['backgrounds'];
        }
        if (isset($draft['background_style']) && is_array($draft['background_style'])) {
            $content_config['background_style'] = $draft['background_style'];
            $contest_config['background_style'] = $draft['background_style'];
        }
        if (isset($draft['logo_top_spacing']) && is_array($draft['logo_top_spacing'])) {
            $normalized = normalize_logo_top_spacing_config($draft['logo_top_spacing']);
            $content_config['logo_top_spacing'] = $normalized;
            $contest_config['logo_top_spacing'] = $normalized;
        }
        if (isset($draft['brand_header_logo_gap']) && is_array($draft['brand_header_logo_gap'])) {
            $normalized = normalize_brand_header_logo_gap_config($draft['brand_header_logo_gap']);
            $content_config['brand_header_logo_gap'] = $normalized;
            $contest_config['brand_header_logo_gap'] = $normalized;
        }
        if (isset($draft['brand_header_top_spacing']) && is_array($draft['brand_header_top_spacing'])) {
            $normalized = normalize_brand_header_top_spacing_config($draft['brand_header_top_spacing']);
            $content_config['brand_header_top_spacing'] = $normalized;
            $contest_config['brand_header_top_spacing'] = $normalized;
        }
        if (isset($draft['brand_page_layout']) && is_array($draft['brand_page_layout'])) {
            $normalized = normalize_brand_layout_collection($draft['brand_page_layout']);
            $content_config['brand_page_layout'] = $normalized;
            $contest_config['brand_page_layout'] = $normalized;
        }
        if (isset($draft['help_page_style']) && is_array($draft['help_page_style'])) {
            $currentHelp = is_array($content_config['help_page_style'] ?? null) ? $content_config['help_page_style'] : [];
            $mergedHelp = array_replace_recursive($currentHelp, $draft['help_page_style']);
            $content_config['help_page_style'] = $mergedHelp;
            $contest_config['help_page_style'] = $mergedHelp;
        }
    }
}

$isLive = !empty($_GET['live']);
$pageKey = isset($_GET['page']) ? trim((string) $_GET['page']) : 'registration';
if (!array_key_exists($pageKey, brand_layout_page_definitions())) {
    $pageKey = 'registration';
}

if ($isLive && !empty($_SESSION['brand_layout_preview_draft']) && is_array($_SESSION['brand_layout_preview_draft'])) {
    $draft = $_SESSION['brand_layout_preview_draft'];
    apply_brand_layout_preview_draft($draft, $content_config, $contest_config);
    if (!empty($draft['preview_page']) && array_key_exists($draft['preview_page'], brand_layout_page_definitions())) {
        $pageKey = (string) $draft['preview_page'];
    }
}

$brand = resolve_brand_display_context($contest_config);
$show_logo = $brand['show_logo'];
$show_header = $brand['show_header'];
$has_brand_visual = $brand['has_brand_visual'];
$wrapper_modifiers = trim((string) ($brand['wrapper_modifier_classes'] ?? ''));

$pageClass = preg_replace('/[^a-z0-9_-]+/i', '-', $pageKey);
$pageClass = $pageClass !== '' ? $pageClass : 'registration';
$containerSuffix = brand_layout_preview_container_suffix($pageKey);
$containerClass = 'public-page-container--' . $containerSuffix;

$contest_name = htmlspecialchars($contest_config['name'] ?? 'Concorso', ENT_QUOTES, 'UTF-8');
$logo_main = htmlspecialchars($contest_config['logo'] ?? '', ENT_QUOTES);
$logo_mobile = htmlspecialchars($contest_config['logo_mobile'] ?? ($contest_config['logo'] ?? ''), ENT_QUOTES);
$header_image = htmlspecialchars($contest_config['header_image'] ?? '', ENT_QUOTES);
$header_image_mobile = htmlspecialchars($contest_config['header_image_mobile'] ?? '', ENT_QUOTES);

$pageTitles = brand_layout_page_definitions();
$pageTitle = htmlspecialchars($pageTitles[$pageKey] ?? 'Pagina', ENT_QUOTES, 'UTF-8');
$shell = brand_layout_preview_shell_meta($pageKey);
$bodyClass = trim('brand-layout-preview-shell ' . ($shell['body_class'] ?? ''));
$pageContent = render_brand_layout_preview_page_content($pageKey, $content_config, $contest_config, $content_defaults ?? []);
$cardBaseClasses = trim('registration-card ' . $containerClass . ($has_brand_visual ? ' logo-attached-card' : '') . ' ' . ($shell['card_classes'] ?? 'card mx-auto w-100'));
$useCardBody = !empty($shell['use_card_body']);
$cardBodyClasses = trim((string) ($shell['card_body_classes'] ?? ''));
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <base href="<?php echo htmlspecialchars(rtrim($BASE_HREF, '/') . '/', ENT_QUOTES, 'UTF-8'); ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
    <link rel="stylesheet" href="assets/css/minimal.css">
    <link rel="stylesheet" href="assets/css/custom.css">
    <?php echo function_exists('get_custom_fonts_style_block') ? get_custom_fonts_style_block(true) : ''; ?>
    <?php echo get_background_style_block(true); ?>
    <?php echo get_logo_desktop_size_style_block(); ?>
    <?php echo get_brand_layout_style_block($pageKey, $contest_config); ?>
    <?php echo get_theme_colors_block(); ?>
    <?php echo get_form_container_style_block(true); ?>
    <?php
    if ($pageKey === 'help' && function_exists('get_help_page_style_block')) {
        echo get_help_page_style_block();
    }
    ?>
    <title>Anteprima layout <?php echo $pageTitle; ?></title>
    <style>
        html, body { margin: 0; padding: 0; min-height: 100%; }
        body { overflow-x: hidden; }
        .brand-layout-preview-shell { min-height: 480px; }
    </style>
</head>
<body class="<?php echo htmlspecialchars($bodyClass, ENT_QUOTES, 'UTF-8'); ?>">
<div class="page-wrapper d-flex flex-column min-vh-100 body-background-wrapper">
    <div class="container-xxl pt-0 pb-4 flex-grow-1 header-main-background-wrapper brand-layout-page-active<?php echo $wrapper_modifiers; ?>">
        <main class="w-100">
            <div class="row justify-content-center">
                <div class="col-12 col-lg-11 col-xl-10 col-xxl-9 mx-auto logo-stack brand-layout-page--<?php echo htmlspecialchars($pageClass, ENT_QUOTES, 'UTF-8'); ?>">
                    <?php if ($has_brand_visual): ?>
                    <header class="text-center card-logo-wrapper<?php echo ($show_logo && $show_header) ? ' brand-visual-stack' : ''; ?>" role="banner">
                        <?php if ($show_header): ?>
                        <picture>
                            <?php if ($header_image_mobile !== ''): ?>
                                <source media="(max-width: 767.98px)" srcset="<?php echo $header_image_mobile; ?>">
                            <?php endif; ?>
                            <img src="<?php echo $header_image; ?>" alt="Header <?php echo $contest_name; ?>" class="img-fluid card-logo-img brand-header-img">
                        </picture>
                        <?php endif; ?>
                        <?php if ($show_logo): ?>
                        <picture>
                            <?php if ($logo_mobile !== ''): ?>
                                <source media="(max-width: 767.98px)" srcset="<?php echo $logo_mobile; ?>">
                            <?php endif; ?>
                            <img src="<?php echo $logo_main; ?>" alt="Logo <?php echo $contest_name; ?>" class="img-fluid card-logo-img">
                        </picture>
                        <?php endif; ?>
                    </header>
                    <?php endif; ?>
                    <div class="<?php echo htmlspecialchars($cardBaseClasses, ENT_QUOTES, 'UTF-8'); ?>">
                        <?php if ($useCardBody): ?>
                        <div class="<?php echo htmlspecialchars($cardBodyClasses, ENT_QUOTES, 'UTF-8'); ?>">
                            <?php echo $pageContent; ?>
                        </div>
                        <?php else: ?>
                            <?php echo $pageContent; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>
</body>
</html>
