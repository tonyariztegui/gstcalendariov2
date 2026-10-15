<?php
include __DIR__ . '/../app/inc/config.php';
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
if (!empty($_GET['preview'])) {
    if (!empty($contest_config['published'])) {
        header('Location: partecipazione');
        exit;
    }
    $_SESSION['contest_preview'] = true;
}
if (empty($contest_config['published']) && empty($_SESSION['contest_preview'] ?? null)) {
    header('Location: benvenuto');
    exit;
}

$is_preview = !empty($_SESSION['contest_preview'] ?? null);
$participation_page_enabled = (bool) ($contest_config['participation_page_enabled'] ?? false);
if (!$participation_page_enabled && !$is_preview) {
    header('Location: registrazione');
    exit;
}

$now = time();
if (!$is_preview && $now < $date_debut) {
    include 'waiting_page.php';
    exit;
}
if (!$is_preview && $now > $date_fin) {
    include 'waiting_page_ended.php';
    exit;
}

require_once __DIR__ . '/../app/logic/verify/qr_access.php';
qr_access_require();

$logo_display_mode = strtolower(trim((string)($contest_config['logo_display_mode'] ?? '')));
if (!in_array($logo_display_mode, ['none', 'logo', 'header', 'both'], true)) {
    $logo_display_mode = (array_key_exists('logo_enabled', $contest_config) && !filter_var($contest_config['logo_enabled'], FILTER_VALIDATE_BOOLEAN)) ? 'none' : 'logo';
}
$show_logo = in_array($logo_display_mode, ['logo', 'both'], true);
$show_header = in_array($logo_display_mode, ['header', 'both'], true) && trim((string)($contest_config['header_image'] ?? '')) !== '';
$has_brand_visual = $show_logo || $show_header;
$pages_config = $content_config['pages'] ?? ($content_defaults['pages'] ?? []);
$participation_page = is_array($pages_config['participation_page'] ?? null) ? $pages_config['participation_page'] : [];
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($contest_config['name']); ?> - Partecipa</title>
    <?php echo get_favicon_tags(); ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" integrity="sha384-tViUnnbYAV00FLIhhi3v/dWt3Jxw4gZQcNoSCxCIFNJVCx7/D55/wXsrNIRANwdD" crossorigin="anonymous">
    <link rel="stylesheet" href="assets/css/minimal.css">
    <?php echo get_background_style_block(); ?>
    <?php echo function_exists('get_custom_fonts_style_block') ? get_custom_fonts_style_block(false) : ''; ?>
    <?php echo get_logo_desktop_size_style_block(); ?>
    <?php echo get_brand_layout_style_block('participation_page'); ?>
    <?php echo get_theme_colors_block(); ?>
    <?php echo get_button_style_block(); ?>
    <?php echo get_form_container_style_block(); ?>
    <?php echo get_footer_style_block(); ?>
</head>
<body class="participation-page">
<div class="d-flex flex-column min-vh-100 body-background-wrapper">
    <div class="container-xxl py-4 flex-grow-1 header-main-background-wrapper brand-layout-page-active<?php echo $show_header ? ' brand-header-active' : ''; ?><?php echo ($show_header && !$show_logo) ? ' brand-header-only' : ''; ?>">
        <main class="row justify-content-center w-100 mx-0">
            <div class="col-12 col-lg-11 col-xl-10 col-xxl-9 px-0 px-md-3">
                <div class="logo-stack mx-auto brand-layout-page--participation_page">
                    <?php if ($has_brand_visual): ?>
                    <header class="text-center card-logo-wrapper brand-visual-stack" role="banner">
                        <?php
                            $logo_main = htmlspecialchars($contest_config['logo'] ?? '', ENT_QUOTES);
                            $logo_mobile = htmlspecialchars($contest_config['logo_mobile'] ?? ($contest_config['logo'] ?? ''), ENT_QUOTES);
                            $header_image = htmlspecialchars($contest_config['header_image'] ?? '', ENT_QUOTES);
                        ?>
                        <?php if ($show_header): ?>
                            <img src="<?php echo $header_image; ?>" alt="Header <?php echo htmlspecialchars($contest_config['name']); ?>" class="img-fluid card-logo-img brand-header-img">
                        <?php endif; ?>
                        <?php if ($show_logo): ?>
                        <picture>
                            <?php if (!empty($logo_mobile)): ?>
                                <source media="(max-width: 767.98px)" srcset="<?php echo $logo_mobile; ?>">
                            <?php endif; ?>
                            <img src="<?php echo $logo_main; ?>" alt="Logo <?php echo htmlspecialchars($contest_config['name']); ?>" class="img-fluid card-logo-img">
                        </picture>
                        <?php endif; ?>
                    </header>
                    <?php endif; ?>
                    <div class="card registration-card public-page-container--participation-page <?php echo $has_brand_visual ? 'logo-attached-card ' : ''; ?>mx-auto">
                        <div class="card-body text-center py-4 py-md-5">
                            <?php echo render_config_page_container_content($participation_page, $contest_config, [
                                'content_class' => 'participation-page-content',
                                'button_footer_html' => render_registration_page_button_footer_html($participation_page, $contest_config),
                            ]); ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <?php include 'assets/inc/footer.php'; ?>
</div>
</body>
</html>
