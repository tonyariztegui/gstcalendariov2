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
    <div class="card card-body mb-3 advent-footer-button-settings">
        <h6 class="mb-1">Pulsante centrale</h6>
        <p class="text-muted small">Opzionale. Appare sotto gli altri contenuti del footer; lascia entrambi i campi vuoti per nasconderlo.</p>
        <div class="advent-fields">
            <div class="advent-field"><label for="cal-footer-button-label">Testo del pulsante</label><input class="form-control" id="cal-footer-button-label" name="footer_button_label" maxlength="100" value="<?= advent_escape($calendar['footer_button_label'] ?? '') ?>" placeholder="Es. Scopri di più"></div>
            <div class="advent-field"><label for="cal-footer-button-url">Link del pulsante</label><input class="form-control" id="cal-footer-button-url" name="footer_button_url" type="text" maxlength="2048" value="<?= advent_escape($calendar['footer_button_url'] ?? '') ?>" placeholder="https://esempio.it oppure /pagina"></div>
            <div class="advent-field"><label for="cal-footer-button-background">Colore sfondo</label><input class="form-control" id="cal-footer-button-background" name="footer_button_background" type="color" value="<?= advent_escape($calendar['footer_button_background'] ?? '#185844') ?>"></div>
            <div class="advent-field"><label for="cal-footer-button-text-color">Colore testo</label><input class="form-control" id="cal-footer-button-text-color" name="footer_button_text_color" type="color" value="<?= advent_escape($calendar['footer_button_text_color'] ?? '#ffffff') ?>"></div>
            <div class="advent-field"><label for="cal-footer-button-hover-background">Colore al passaggio del mouse</label><input class="form-control" id="cal-footer-button-hover-background" name="footer_button_hover_background" type="color" value="<?= advent_escape($calendar['footer_button_hover_background'] ?? '#124232') ?>"></div>
            <div class="advent-field"><label for="cal-footer-button-size">Dimensione</label><select class="form-control" id="cal-footer-button-size" name="footer_button_size"><?php foreach (['small'=>'Piccolo','medium'=>'Medio','large'=>'Grande'] as $value=>$label): ?><option value="<?= $value ?>" <?= ($calendar['footer_button_size'] ?? 'medium') === $value ? 'selected' : '' ?>><?= $label ?></option><?php endforeach; ?></select></div>
            <div class="advent-field"><label for="cal-footer-button-width">Larghezza</label><select class="form-control" id="cal-footer-button-width" name="footer_button_width"><option value="auto" <?= ($calendar['footer_button_width'] ?? 'auto') === 'auto' ? 'selected' : '' ?>>Adatta al testo</option><option value="full" <?= ($calendar['footer_button_width'] ?? 'auto') === 'full' ? 'selected' : '' ?>>Tutta la larghezza</option></select></div>
            <div class="advent-field"><label for="cal-footer-button-radius">Angoli arrotondati (px)</label><input class="form-control" id="cal-footer-button-radius" name="footer_button_radius" type="number" min="0" max="32" step="1" value="<?= (int) ($calendar['footer_button_radius'] ?? 8) ?>"></div>
            <div class="advent-field advent-field--wide"><div class="custom-control custom-checkbox"><input class="custom-control-input" id="cal-footer-button-new-tab" name="footer_button_new_tab" type="checkbox" value="1" <?= !empty($calendar['footer_button_new_tab']) ? 'checked' : '' ?>><label class="custom-control-label" for="cal-footer-button-new-tab">Apri il link in una nuova scheda</label></div></div>
        </div>
    </div>
    <details class="card card-body mb-3"><summary>Dati e collegamenti del calendario</summary><div class="advent-fields mt-3">
    <?php foreach (['regulation_url'=>'Link regolamento','privacy_url'=>'Link privacy','cookie_url'=>'Link Cookie Policy','assistance_url'=>'Link assistenza','promoter'=>'Promotore','promoter_url'=>'Link promotore','delegate'=>'Delegato','delegate_url'=>'Link delegato','prize_total'=>'Montepremi (€)'] as $key=>$label) { advent_editor_field('footer_'.$key,$label,(string)($calendar['footer_'.$key] ?? '')); } ?>
    </div></details></section><?php
})();
