<?php

require_once __DIR__ . '/../../inc/qr_config.php';

if (!function_exists('qr_verify_render_error')) {
    function qr_verify_render_error(string $reason): void
    {
        global $contest_config, $date_debut, $date_fin;

        $error = qr_config_get_error($reason);
        $home_target = !empty($contest_config['published'])
            ? (!empty($contest_config['participation_page_enabled']) ? 'partecipazione' : 'registrazione')
            : 'benvenuto';
        $primary_button_bg = (string) ($contest_config['buttons']['primary']['bg'] ?? '#0d6efd');

        $now = time();
        $contest_in_progress = isset($date_debut, $date_fin) && $now >= $date_debut && $now <= $date_fin;
        $show_home_button = !$contest_in_progress;

        $logo_display_mode = strtolower(trim((string)($contest_config['logo_display_mode'] ?? '')));
        if (!in_array($logo_display_mode, ['none', 'logo', 'header', 'both'], true)) {
            $logo_display_mode = (array_key_exists('logo_enabled', $contest_config) && !filter_var($contest_config['logo_enabled'], FILTER_VALIDATE_BOOLEAN)) ? 'none' : 'logo';
        }
        $show_logo = in_array($logo_display_mode, ['logo', 'both'], true);
        $show_header = in_array($logo_display_mode, ['header', 'both'], true) && trim((string)($contest_config['header_image'] ?? '')) !== '';
        $has_brand_visual = $show_logo || $show_header;
        $brand_layout_page_key = 'registration';

        include __DIR__ . '/../../../public/assets/inc/header.php';
        ?>
<style>
.public-error-code {
    font-size: clamp(2rem, 8vw, 4rem);
    line-height: 0.95;
    font-weight: 800;
    color: <?php echo htmlspecialchars($primary_button_bg, ENT_QUOTES, 'UTF-8'); ?>;
    letter-spacing: 0;
}
</style>
<div class="container-xxl py-4 flex-grow-1 header-main-background-wrapper brand-layout-page-active<?php echo $show_header ? ' brand-header-active' : ''; ?><?php echo ($show_header && !$show_logo) ? ' brand-header-only' : ''; ?>">
    <main class="w-100">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-11 col-xl-10 col-xxl-9 mx-auto logo-stack brand-layout-page--registration">
                <?php if ($has_brand_visual): ?>
                <header class="text-center card-logo-wrapper brand-visual-stack" role="banner">
                    <?php
                    $logo_main = htmlspecialchars($contest_config['logo'] ?? '', ENT_QUOTES, 'UTF-8');
                    $logo_mobile = htmlspecialchars($contest_config['logo_mobile'] ?? ($contest_config['logo'] ?? ''), ENT_QUOTES, 'UTF-8');
                    $header_image = htmlspecialchars($contest_config['header_image'] ?? '', ENT_QUOTES, 'UTF-8');
                    $header_image_mobile = htmlspecialchars($contest_config['header_image_mobile'] ?? '', ENT_QUOTES, 'UTF-8');
                    ?>
                    <?php if ($show_header): ?>
                    <picture>
                        <?php if ($header_image_mobile !== ''): ?>
                            <source media="(max-width: 767.98px)" srcset="<?php echo $header_image_mobile; ?>">
                        <?php endif; ?>
                        <img src="<?php echo $header_image; ?>" alt="Header <?php echo htmlspecialchars($contest_config['name'] ?? 'Concorso', ENT_QUOTES, 'UTF-8'); ?>" class="img-fluid card-logo-img brand-header-img">
                    </picture>
                    <?php endif; ?>
                    <?php if ($show_logo): ?>
                    <picture>
                        <?php if ($logo_mobile !== ''): ?>
                            <source media="(max-width: 767.98px)" srcset="<?php echo $logo_mobile; ?>">
                        <?php endif; ?>
                        <img src="<?php echo $logo_main; ?>" alt="Logo <?php echo htmlspecialchars($contest_config['name'] ?? 'Concorso', ENT_QUOTES, 'UTF-8'); ?>" class="img-fluid card-logo-img">
                    </picture>
                    <?php endif; ?>
                </header>
                <?php endif; ?>
                <div class="registration-card public-page-container--registration card p-4 p-md-5 mx-auto w-100 text-center<?php echo $has_brand_visual ? ' logo-attached-card' : ''; ?>">
                    <div class="mx-auto col-12 col-lg-10">
                        <div class="public-error-code mb-3"><i class="fa fa-qrcode" aria-hidden="true"></i></div>
                        <h1 class="h4 fw-bold mb-3"><?php echo htmlspecialchars($error['title'], ENT_QUOTES, 'UTF-8'); ?></h1>
                        <?php if (trim((string) ($error['text'] ?? '')) !== ''): ?>
                        <p class="mb-4"><?php echo htmlspecialchars($error['text'], ENT_QUOTES, 'UTF-8'); ?></p>
                        <?php endif; ?>
                        <?php if ($show_home_button): ?>
                        <div class="mx-auto px-2 mt-4 col-12 col-md-6 col-lg-5">
                            <a class="btn btn-success btn-lg fw-bold w-100" href="<?php echo htmlspecialchars($home_target, ENT_QUOTES, 'UTF-8'); ?>">Torna alla pagina principale</a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>
        <?php
        include __DIR__ . '/../../../public/assets/inc/footer.php';
    }
}
