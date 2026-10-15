<?php
/**
 * Anteprima contestuale stile campi form.
 * Variabili attese: $preview_scope, $formFieldStyleValue, $paletteTextForFields,
 * $palettePrimaryForFields, $paletteSecondaryForFields, $paletteTertiaryForFields,
 * $formFieldErrorMode, $formFieldPreviewText, $formFieldPreviewBorder,
 * $formFieldPreviewBg, $formFieldFocusPreview, $formFieldSelect2HighlightBgPreview,
 * $formFieldSelect2HighlightTextPreview, $formFieldCheckboxPreview, $formFieldConsentLinkPreview, $formFieldConsentLinkHoverPreview.
 */
$previewScope = isset($preview_scope) ? (string) $preview_scope : 'full';
if (!in_array($previewScope, ['full', 'top', 'input', 'select2', 'date', 'consents'], true)) {
    $previewScope = 'full';
}
$previewIdAttr = isset($preview_id) && $preview_id !== '' ? ' id="' . manager_safe_html((string) $preview_id) . '"' : '';
?>
<div class="form-field-style-preview form-field-style-preview--compact form-field-style-preview--scope-<?php echo manager_safe_html($previewScope); ?>"<?php echo $previewIdAttr; ?> data-style="<?php echo manager_safe_html($formFieldStyleValue); ?>" data-default-text="<?php echo manager_safe_html($paletteTextForFields); ?>" data-default-border="#dee2e6" data-default-bg="#ffffff" data-default-focus="<?php echo manager_safe_html($palettePrimaryForFields); ?>" data-default-checkbox="<?php echo manager_safe_html($paletteSecondaryForFields); ?>" data-default-consent-link="<?php echo manager_safe_html($paletteSecondaryForFields); ?>" data-default-consent-link-hover="<?php echo manager_safe_html($paletteTertiaryForFields); ?>" data-error-mode="<?php echo manager_safe_html($formFieldErrorMode); ?>" style="--ffsp-radius: <?php echo (int) ($formFieldBorderRadius ?? 6); ?>px; --ffsp-text-color: <?php echo manager_safe_html($formFieldPreviewText); ?>; --ffsp-placeholder-color: <?php echo manager_safe_html($formFieldPreviewPlaceholder ?? $formFieldPreviewText); ?>; --ffsp-border-color: <?php echo manager_safe_html($formFieldPreviewBorder); ?>; --ffsp-bg-color: <?php echo manager_safe_html($formFieldPreviewBg); ?>; --ffsp-focus-color: <?php echo manager_safe_html($formFieldFocusPreview); ?>; --ffsp-select2-highlight-bg: <?php echo manager_safe_html($formFieldSelect2HighlightBgPreview); ?>; --ffsp-select2-highlight-text: <?php echo manager_safe_html($formFieldSelect2HighlightTextPreview); ?>; --ffsp-calendar-popup-bg: <?php echo manager_safe_html($formFieldCalendarPopupBgPreview ?? $formFieldPreviewBg); ?>; --ffsp-calendar-selected: <?php echo manager_safe_html($formFieldCalendarSelectedPreview ?? $formFieldFocusPreview); ?>; --ffsp-calendar-hover-bg: <?php echo manager_safe_html($formFieldCalendarHoverBgPreview ?? $formFieldSelect2HighlightBgPreview); ?>; --ffsp-calendar-hover-text: <?php echo manager_safe_html($formFieldCalendarHoverTextPreview ?? $formFieldSelect2HighlightTextPreview); ?>; --ffsp-checkbox-color: <?php echo manager_safe_html($formFieldCheckboxPreview); ?>; --ffsp-consent-text-color: <?php echo manager_safe_html($formFieldConsentTextPreview ?? '#000000'); ?>; --ffsp-consent-link-color: <?php echo manager_safe_html($formFieldConsentLinkPreview); ?>; --ffsp-consent-link-hover-color: <?php echo manager_safe_html($formFieldConsentLinkHoverPreview); ?>;">
    <div class="ffsp-mode ffsp-label-top">
        <div class="ffsp-row" data-scope="input top full">
            <div class="ffsp-label">Nome *</div>
            <div class="ffsp-input">Mario</div>
        </div>
        <div class="ffsp-row" data-scope="input top full">
            <div class="ffsp-label">Email *</div>
            <div class="ffsp-input">mario@example.it</div>
        </div>
        <div class="ffsp-row" data-scope="input full">
            <div class="ffsp-label">Campo in focus</div>
            <div class="ffsp-input ffsp-input--focus">Esempio focus</div>
        </div>
        <div class="ffsp-row" data-scope="select2 full">
            <div class="ffsp-label">Citt&agrave; (Select2)</div>
            <div class="ffsp-select2-mock">
                <div class="ffsp-select2-field ffsp-select2-field--open">
                    <span class="ffsp-select2-field-value">Milano</span>
                    <span class="ffsp-select2-caret" aria-hidden="true"></span>
                </div>
                <div class="ffsp-select2-dropdown" aria-hidden="true">
                    <div class="ffsp-select2-option">Andriano</div>
                    <div class="ffsp-select2-option ffsp-select2-option--highlighted">Aldino</div>
                    <div class="ffsp-select2-option">Anterivo</div>
                </div>
            </div>
        </div>
        <div class="ffsp-row" data-scope="date full">
            <div class="ffsp-label">Data scontrino</div>
            <div class="ffsp-calendar-mock" aria-hidden="true">
                <div class="ffsp-calendar-header">Luglio 2026</div>
                <div class="ffsp-calendar-weekdays">
                    <span>L</span><span>M</span><span>M</span><span>G</span><span>V</span><span>S</span><span>D</span>
                </div>
                <div class="ffsp-calendar-grid">
                    <span class="ffsp-calendar-day ffsp-calendar-day--muted">29</span>
                    <span class="ffsp-calendar-day ffsp-calendar-day--muted">30</span>
                    <span class="ffsp-calendar-day">1</span>
                    <span class="ffsp-calendar-day">2</span>
                    <span class="ffsp-calendar-day">3</span>
                    <span class="ffsp-calendar-day">4</span>
                    <span class="ffsp-calendar-day">5</span>
                    <span class="ffsp-calendar-day">6</span>
                    <span class="ffsp-calendar-day ffsp-calendar-day--hover">7</span>
                    <span class="ffsp-calendar-day">8</span>
                    <span class="ffsp-calendar-day">9</span>
                    <span class="ffsp-calendar-day">10</span>
                    <span class="ffsp-calendar-day">11</span>
                    <span class="ffsp-calendar-day">12</span>
                    <span class="ffsp-calendar-day">13</span>
                    <span class="ffsp-calendar-day ffsp-calendar-day--selected">14</span>
                </div>
            </div>
        </div>
        <div class="ffsp-row ffsp-error-row" data-scope="top full">
            <span class="ffsp-error-sample">Il nome &egrave; obbligatorio.</span>
        </div>
        <div class="ffsp-row ffsp-consent-row" data-scope="consents full">
            <span class="ffsp-checkbox" aria-hidden="true"></span>
            <span class="ffsp-consent-text">Accetto <a href="#" class="ffsp-consent-link" onclick="return false;">l'informativa privacy</a> <span class="ffsp-consent-hover-label">(hover)</span> <a href="#" class="ffsp-consent-link ffsp-consent-link--hover-demo" onclick="return false;">link hover</a> *</span>
        </div>
        <div class="ffsp-row" data-scope="file full">
            <div class="ffsp-file-label">Foto scontrino (JPG, PNG o PDF, max 5 MB) *</div>
            <div class="ffsp-file-input">
                <span class="ffsp-file-button">Scegli file</span>
                <span class="ffsp-file-name">Nessun file selezionato</span>
            </div>
        </div>
    </div>
    <div class="ffsp-mode ffsp-placeholder">
        <div class="ffsp-row" data-scope="input top full">
            <div class="ffsp-input ffsp-placeholder-input">NOME *</div>
        </div>
        <div class="ffsp-row" data-scope="input top full">
            <div class="ffsp-input ffsp-placeholder-input">EMAIL *</div>
        </div>
        <div class="ffsp-row" data-scope="input full">
            <div class="ffsp-input ffsp-input--focus ffsp-placeholder-input">CAMPO IN FOCUS</div>
        </div>
        <div class="ffsp-row" data-scope="select2 full">
            <div class="ffsp-select2-mock ffsp-select2-mock--placeholder">
                <div class="ffsp-select2-field ffsp-select2-field--open ffsp-placeholder-input">
                    <span class="ffsp-select2-field-value">CITT&Agrave; (SELECT2)</span>
                    <span class="ffsp-select2-caret" aria-hidden="true"></span>
                </div>
                <div class="ffsp-select2-dropdown" aria-hidden="true">
                    <div class="ffsp-select2-option">ANDRIANO</div>
                    <div class="ffsp-select2-option ffsp-select2-option--highlighted">ALDINO</div>
                    <div class="ffsp-select2-option">ANTERIVO</div>
                </div>
            </div>
        </div>
        <div class="ffsp-row" data-scope="date full">
            <div class="ffsp-calendar-mock" aria-hidden="true">
                <div class="ffsp-calendar-header">Luglio 2026</div>
                <div class="ffsp-calendar-weekdays">
                    <span>L</span><span>M</span><span>M</span><span>G</span><span>V</span><span>S</span><span>D</span>
                </div>
                <div class="ffsp-calendar-grid">
                    <span class="ffsp-calendar-day ffsp-calendar-day--muted">29</span>
                    <span class="ffsp-calendar-day ffsp-calendar-day--muted">30</span>
                    <span class="ffsp-calendar-day">1</span>
                    <span class="ffsp-calendar-day">2</span>
                    <span class="ffsp-calendar-day">3</span>
                    <span class="ffsp-calendar-day">4</span>
                    <span class="ffsp-calendar-day">5</span>
                    <span class="ffsp-calendar-day">6</span>
                    <span class="ffsp-calendar-day ffsp-calendar-day--hover">7</span>
                    <span class="ffsp-calendar-day">8</span>
                    <span class="ffsp-calendar-day">9</span>
                    <span class="ffsp-calendar-day">10</span>
                    <span class="ffsp-calendar-day">11</span>
                    <span class="ffsp-calendar-day">12</span>
                    <span class="ffsp-calendar-day">13</span>
                    <span class="ffsp-calendar-day ffsp-calendar-day--selected">14</span>
                </div>
            </div>
        </div>
        <div class="ffsp-row ffsp-error-row" data-scope="top full">
            <span class="ffsp-error-sample">Il nome &egrave; obbligatorio.</span>
        </div>
        <div class="ffsp-row ffsp-consent-row" data-scope="consents full">
            <span class="ffsp-checkbox" aria-hidden="true"></span>
            <span class="ffsp-consent-text">Accetto <a href="#" class="ffsp-consent-link" onclick="return false;">l'informativa privacy</a> <span class="ffsp-consent-hover-label">(hover)</span> <a href="#" class="ffsp-consent-link ffsp-consent-link--hover-demo" onclick="return false;">link hover</a> *</span>
        </div>
        <div class="ffsp-row" data-scope="file full">
            <div class="ffsp-file-label">Foto scontrino (JPG, PNG o PDF, max 5 MB) *</div>
            <div class="ffsp-file-input">
                <span class="ffsp-file-button">Scegli file</span>
                <span class="ffsp-file-name">Foto scontrino (JPG, PNG o PDF, max 5 MB) *</span>
            </div>
        </div>
    </div>
</div>
