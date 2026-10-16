<?php
require_once __DIR__ . '/../logic/advent_footer.php';
require_once __DIR__ . '/../inc/config.php';
$savedFooterConfig = $contest_config;
$contest_config = advent_footer_config($calendar, $adventBase);
?>
<link rel="stylesheet" href="<?= advent_escape($adventBase) ?>/public/assets/css/advent-footer.css">
<link rel="stylesheet" href="<?= advent_escape($adventBase) ?>/public/assets/css/footer-layout.css">
<?= get_footer_style_block() ?>
<?php render_public_footer($contest_config, advent_footer_button_html($calendar, $adventBase)); $contest_config = $savedFooterConfig; ?>
