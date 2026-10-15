<?php
// Same template as Concorso, with independent calendar data and preview endpoint.
if (!function_exists('manager_safe_html')) {
    function manager_safe_html(string $value): string { return advent_escape($value); }
}
if (!function_exists('manager_preview_url')) {
    function manager_preview_url(string $path, string $baseUrl, string $baseDir): string { return advent_asset_url($path, advent_editor_base()); }
}
(static function () use ($calendar) {
    $content_current = advent_footer_config($calendar, advent_editor_base());
    $content_defaults = json_decode(file_get_contents(__DIR__ . '/../../../app/config/content.defaults.json'), true);
    $styleCustomization = $content_current['style_customization'];
    $content_current['cookie_consent']['enabled'] = true;
    $baseDir = dirname(__DIR__, 3);
    $footerEditorStep = 4;
    $footerPreviewUrl = 'ajax/preview_advent_footer.php';
    ?><section id="advent-step-4" class="config-step" role="tabpanel" aria-labelledby="advent-tab-4">
    <div class="custom-control custom-switch my-3"><input type="checkbox" class="custom-control-input" id="cal-footer-enabled" name="footer_enabled" value="1" <?= !empty($calendar['footer_enabled'])?'checked':'' ?>><label class="custom-control-label" for="cal-footer-enabled">Attiva footer calendario</label></div>
    <?php include __DIR__ . '/../concorso/partials/footer-editor.php'; ?>
    <details class="card card-body mb-3"><summary>Dati e collegamenti del calendario</summary><div class="advent-fields mt-3">
    <?php foreach (['regulation_url'=>'Link regolamento','privacy_url'=>'Link privacy','cookie_url'=>'Link Cookie Policy','assistance_url'=>'Link assistenza','promoter'=>'Promotore','promoter_url'=>'Link promotore','delegate'=>'Delegato','delegate_url'=>'Link delegato','prize_total'=>'Montepremi (€)'] as $key=>$label) { advent_editor_field('footer_'.$key,$label,(string)($calendar['footer_'.$key] ?? '')); } ?>
    </div></details></section><?php
})();
