<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php
    // Consente alle pagine fuori da /public di forzare un base href (es. instant win thank you)
    $base_href = '';
    if (isset($BASE_HREF) && is_string($BASE_HREF)) {
        $base_trimmed = trim($BASE_HREF);
        if ($base_trimmed !== '') {
            $base_href = rtrim($base_trimmed, '/') . '/';
        }
    }
    ?>
    <?php if ($base_href !== ''): ?>
    <base href="<?php echo htmlspecialchars($base_href, ENT_QUOTES, 'UTF-8'); ?>">
    <?php endif; ?>
    <?php echo get_favicon_tags(); ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
    <link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.css" rel="stylesheet" integrity="sha384-FckWOBo7yuyMS7In0aXZ0aoVvnInlnFMwCv77x9sZpFgOonQgnBj1uLwenWVtsEj" crossorigin="anonymous"/>
    <link rel="stylesheet" href="assets/css/minimal.css">
    <?php echo function_exists('get_custom_fonts_style_block') ? get_custom_fonts_style_block(false) : ''; ?>
    <?php echo get_background_style_block(); ?>
    <?php echo get_logo_desktop_size_style_block(); ?>
    <?php echo isset($brand_layout_page_key) && is_string($brand_layout_page_key) ? get_brand_layout_style_block($brand_layout_page_key) : ''; ?>
    <?php echo get_theme_colors_block(); ?>
    <?php echo get_button_style_block(); ?>
    <?php echo get_form_container_style_block(false); ?>
    <?php echo get_footer_style_block(false); ?>
    <title><?php echo htmlspecialchars($contest_config['name'] ?? 'Concorso'); ?> - Partecipa</title>
    <?php
    $__ccInc = __DIR__;
    include $__ccInc . '/gtm_head.php';
    include $__ccInc . '/meta_pixel_head.php';
    include $__ccInc . '/tiktok_pixel_head.php';
    ?>
</head>

<body>
<?php include __DIR__ . '/gtm_body.php'; ?>
<div class="page-wrapper d-flex flex-column min-vh-100 body-background-wrapper">
