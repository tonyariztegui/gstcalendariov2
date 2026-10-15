<div class="card mt-3 mb-4 config-anchor config-step configurazione-section faq-ui-section" data-step="7" id="section-faq">
                            <div class="card-body">
                                <div class="configurazione-hero faq-ui-heading">
                                    <div>
                                        <div class="section-title configurazione-title"><i class="fa fa-question-circle mr-2"></i>Assistenza / FAQ</div>
                                        <p class="text-muted small mb-0 faq-ui-heading-note">Configura pagina assistenza, form, contenuti alternativi e domande frequenti.</p>
                                    </div>
                                    <span class="configurazione-badge faq-ui-status-badge"><i class="fa fa-life-ring mr-1"></i> Assistenza</span>
                                </div>
                                <?php
                                    $helpStyleDefaults = $content_defaults['help_page_style'] ?? [];
                                    $helpStyleCurrent = $content_current['help_page_style'] ?? $helpStyleDefaults;
                                    if (!is_array($helpStyleCurrent)) {
                                        $helpStyleCurrent = [];
                                    }
                                    $helpAccStyle = is_array($helpStyleCurrent['accordion'] ?? null)
                                        ? $helpStyleCurrent['accordion']
                                        : (is_array($helpStyleDefaults['accordion'] ?? null) ? $helpStyleDefaults['accordion'] : []);
                                    $helpFormStyle = is_array($helpStyleCurrent['form'] ?? null)
                                        ? $helpStyleCurrent['form']
                                        : (is_array($helpStyleDefaults['form'] ?? null) ? $helpStyleDefaults['form'] : []);
                                    $palettePrimary = trim((string) ($content_current['colors']['primary'] ?? ($content_defaults['colors']['primary'] ?? '#198754')));
                                    $paletteText = trim((string) ($content_current['colors']['text'] ?? ($content_defaults['colors']['text'] ?? '#000000')));
                                    $paletteSecondary = trim((string) ($content_current['colors']['secondary'] ?? ($content_defaults['colors']['secondary'] ?? '#6c757d')));
                                    $defaultFormText = trim((string) ($content_current['form_field_text_color'] ?? ($content_defaults['form_field_text_color'] ?? '#000000')));
                                    $helpFormEnabled = array_key_exists('help_form_enabled', $content_current)
                                        ? (bool) $content_current['help_form_enabled']
                                        : (bool) ($content_defaults['help_form_enabled'] ?? true);
                                    $styleCustomization = is_array($content_current['style_customization'] ?? null)
                                        ? $content_current['style_customization']
                                        : (is_array($content_defaults['style_customization'] ?? null) ? $content_defaults['style_customization'] : []);
                                    $helpPageColorsCustom = !empty($styleCustomization['help_page_colors']);

                                    $helpColorFields = static function (string $name, string $label, string $value, string $placeholder, string $hint = ''): void {
                                        $safeValue = manager_safe_html($value);
                                        $pickerValue = preg_match('/^#[0-9A-Fa-f]{6}$/', $value) ? $value : '#000000';
                                        ?>
                                        <div class="form-group col-md-6 col-lg-4 faq-style-color-card">
                                            <label><?php echo $label; ?></label>
                                            <div class="d-flex align-items-center color-row">
                                                <input type="color" class="form-control color-input color-swatch help-style-color-swatch" data-target="<?php echo manager_safe_html($name); ?>_text" value="<?php echo manager_safe_html($pickerValue); ?>">
                                                <input type="text" name="<?php echo manager_safe_html($name); ?>" id="<?php echo manager_safe_html($name); ?>_text" class="form-control color-code-input help-style-color-input" value="<?php echo $safeValue; ?>" placeholder="<?php echo manager_safe_html($placeholder); ?>" pattern="^#[0-9A-Fa-f]{6}$">
                                            </div>
                                            <?php if ($hint !== ''): ?>
                                                <small class="form-text text-muted"><?php echo $hint; ?></small>
                                            <?php endif; ?>
                                        </div>
                                        <?php
                                    };

                                    $helpBgColorFields = static function (string $name, string $label, string $value, string $placeholder, string $hint = ''): void {
                                        $isTransparent = strtolower(trim($value)) === 'transparent';
                                        $displayValue = $isTransparent ? 'transparent' : trim($value);
                                        $safeValue = manager_safe_html($displayValue);
                                        $pickerValue = (!$isTransparent && preg_match('/^#[0-9A-Fa-f]{6}$/', $displayValue)) ? $displayValue : '#ffffff';
                                        $transparentId = manager_safe_html($name . '_transparent');
                                        ?>
                                        <div class="form-group col-md-6 col-lg-4 faq-style-color-card faq-style-bg-color-card">
                                            <label><?php echo $label; ?></label>
                                            <div class="d-flex align-items-center color-row">
                                                <input type="color" class="form-control color-input color-swatch help-style-color-swatch help-style-bg-color-swatch" data-target="<?php echo manager_safe_html($name); ?>_text" value="<?php echo manager_safe_html($pickerValue); ?>"<?php echo $isTransparent ? ' disabled' : ''; ?>>
                                                <input type="text" name="<?php echo manager_safe_html($name); ?>" id="<?php echo manager_safe_html($name); ?>_text" class="form-control color-code-input help-style-color-input help-style-bg-color-input" value="<?php echo $safeValue; ?>" placeholder="<?php echo manager_safe_html($placeholder); ?>"<?php echo $isTransparent ? ' readonly' : ''; ?>>
                                            </div>
                                            <div class="custom-control custom-checkbox mt-1">
                                                <input type="checkbox" class="custom-control-input help-style-bg-transparent-toggle" id="<?php echo $transparentId; ?>" data-target="<?php echo manager_safe_html($name); ?>_text"<?php echo $isTransparent ? ' checked' : ''; ?>>
                                                <label class="custom-control-label small" for="<?php echo $transparentId; ?>">Trasparente</label>
                                            </div>
                                            <?php if ($hint !== ''): ?>
                                                <small class="form-text text-muted"><?php echo $hint; ?></small>
                                            <?php endif; ?>
                                        </div>
                                        <?php
                                    };

                                    if (array_key_exists('faq', $content_current)) {
                                        $faq_items = $content_current['faq'] ?? [];
                                    } else {
                                        $faq_items = $content_defaults['faq'] ?? [];
                                    }
                                    if (empty($faq_items)) {
                                        $faq_items = [['q' => '', 'a' => '']];
                                    }

                                    if (!function_exists('resolve_help_page_style')) {
                                        require_once dirname(__DIR__, 3) . '/app/inc/config.php';
                                    }
                                    $helpPreviewContest = array_merge($content_defaults, $content_current);
                                    $helpPreviewContest['colors'] = $content_current['colors'] ?? ($content_defaults['colors'] ?? []);
                                    $helpPreviewContest['help_page_style'] = $helpStyleCurrent;
                                    $helpPreviewContest['form_field_text_color'] = $defaultFormText;
                                    $resolvedHelpPreview = resolve_help_page_style($helpPreviewContest);
                                    $helpPreviewAcc = $resolvedHelpPreview['accordion'];
                                    $helpPreviewForm = $resolvedHelpPreview['form'];
                                    $helpPreviewOpenBgMix = !empty($helpPreviewAcc['header_bg_open_use_mix']);
                                    $helpPreviewPlaceholderMix = !empty($helpPreviewForm['placeholder_use_mix']);
                                    $helpPreviewFormText = (string) $helpPreviewForm['field_text'];
                                    $helpPreviewPlaceholder = $helpPreviewPlaceholderMix
                                        ? ''
                                        : (string) $helpPreviewForm['placeholder_text'];
                                    $helpPreviewFormFieldColors = resolve_form_field_colors($helpPreviewContest);
                                    $helpPreviewCheckboxAccent = (string) ($helpPreviewForm['checkbox'] ?? $helpPreviewFormFieldColors['checkbox'] ?? $paletteSecondary);
                                    $helpPreviewFormBg = (string) ($helpPreviewForm['field_bg'] ?? $helpPreviewFormFieldColors['bg'] ?? '#ffffff');
                                    $helpPreviewFormBorder = (string) ($helpPreviewForm['field_border'] ?? $helpPreviewFormFieldColors['border'] ?? '#dee2e6');
                                    $helpPreviewConsentText = (string) ($helpPreviewForm['consent_text'] ?? $helpPreviewFormFieldColors['consent_text'] ?? '#000000');
                                    $helpPreviewConsentLink = (string) ($helpPreviewForm['consent_link'] ?? $helpPreviewFormFieldColors['consent_link'] ?? $paletteSecondary);
                                    $helpPreviewConsentLinkHover = (string) ($helpPreviewForm['consent_link_hover'] ?? $helpPreviewFormFieldColors['consent_link_hover'] ?? '#0a58ca');
                                    $helpPreviewFormHeading = (string) ($helpPreviewForm['heading_text'] ?? $palettePrimary);
                                    $helpPreviewFormSubtitle = (string) ($helpPreviewForm['subtitle_text'] ?? '#212529');
                                    $helpContainerStyle = manager_normalize_public_page_container_style($public_page_container_styles['help'] ?? []);
                                    $helpCardBg = '#ffffff';
                                    if (($helpContainerStyle['mode'] ?? 'color') === 'color') {
                                        $helpCardColor = (string) ($helpContainerStyle['color'] ?? '#ffffff');
                                        $helpCardOpacity = max(0, min(100, (int) ($helpContainerStyle['opacity'] ?? 100)));
                                        if (strtolower($helpCardColor) === 'transparent' || $helpCardOpacity <= 0) {
                                            $helpCardBg = 'transparent';
                                        } elseif (preg_match('/^#[0-9A-Fa-f]{6}$/', $helpCardColor)) {
                                            $hr = hexdec(substr($helpCardColor, 1, 2));
                                            $hg = hexdec(substr($helpCardColor, 3, 2));
                                            $hb = hexdec(substr($helpCardColor, 5, 2));
                                            $helpCardBg = 'rgba(' . $hr . ', ' . $hg . ', ' . $hb . ', ' . round($helpCardOpacity / 100, 2) . ')';
                                        }
                                    }
                                ?>
                                <div class="configurazione-panel faq-list-panel faq-page-container-panel">
                                    <div class="configurazione-panel-header">
                                        <span class="configurazione-panel-icon"><i class="fa fa-paint-brush"></i></span>
                                        <div>
                                            <h6>Container pagina - Assistenza / FAQ</h6>
                                            <p>Personalizza lo sfondo del box principale della pagina assistenza.</p>
                                        </div>
                                    </div>
                                <?php
                                    echo manager_render_public_page_container_style_editor([
                                        'page_key' => 'help',
                                        'label' => 'Assistenza / FAQ',
                                        'style' => $public_page_container_styles['help'] ?? [],
                                        'base_dir' => $baseDir,
                                    ]);
                                ?>
                                </div>
                                <div class="configurazione-panel faq-list-panel help-form-mode-panel">
                                    <div class="configurazione-panel-header">
                                        <span class="configurazione-panel-icon"><i class="fa fa-toggle-on"></i></span>
                                        <div>
                                            <h6>Visualizzazione form assistenza</h6>
                                            <p>Decidi se mostrare il form pubblico o i container alternativi creati qui sotto.</p>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-start justify-content-between flex-wrap">
                                        <div class="pr-md-3">
                                            <div class="faq-list-note">Se il form viene disabilitato, nella pagina pubblica viene mostrato il contenuto creato con i container qui sotto.</div>
                                        </div>
                                        <div class="custom-control custom-switch mt-2 mt-md-0">
                                            <input type="checkbox" class="custom-control-input" id="help_form_enabled" name="help_form_enabled" value="1" <?php echo $helpFormEnabled ? 'checked' : ''; ?>>
                                            <label class="custom-control-label font-weight-bold" for="help_form_enabled">Abilita form assistenza</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="configurazione-panel faq-list-panel faq-style-panel">
                                    <div class="configurazione-panel-header">
                                        <span class="configurazione-panel-icon"><i class="fa fa-sliders"></i></span>
                                        <div>
                                            <h6>Stile pagina assistenza</h6>
                                            <p>Personalizza font e colori di accordion, form e controlla il risultato nell'anteprima live.</p>
                                        </div>
                                    </div>
                                    <div class="form-row mb-3">
                                        <div class="form-group col-md-6 mb-md-0">
                                            <label for="help_page_font_family">Famiglia font pagina Assistenza / FAQ</label>
                                            <select name="help_page_font_family" id="help_page_font_family" class="form-control">
                                                <?php
                                                    $helpPageFontFamily = trim((string) ($helpStyleCurrent['font_family'] ?? ''));
                                                    echo contest_font_select_options_html($helpPageFontFamily, $content_current);
                                                ?>
                                            </select>
                                            <small class="form-text text-muted">Predefinito = tipografia pagina. Carica font in Stili → Font personalizzati.</small>
                                        </div>
                                        <div class="form-group col-md-6 mb-0">
                                            <label for="help_page_font_size">Dimensione font Assistenza / FAQ</label>
                                            <select name="help_page_font_size" id="help_page_font_size" class="form-control">
                                                <?php
                                                    $helpPageFontSize = contest_resolve_font_size((string) ($helpStyleCurrent['font_size'] ?? ''));
                                                    echo contest_font_size_select_options_html($helpPageFontSize);
                                                ?>
                                            </select>
                                            <small class="form-text text-muted">Predefinito = dimensione pagina. Visibile nell&apos;anteprima FAQ sotto.</small>
                                        </div>
                                    </div>
                                    <?php
                                        $helpAccTitleFontSize = contest_resolve_font_size((string) ($helpAccStyle['title_font_size'] ?? ''));
                                        $helpAccTitleMarginTop = max(0, min(120, (int) ($helpAccStyle['title_margin_top'] ?? 0)));
                                    ?>
                                    <div class="form-row mb-3">
                                        <div class="form-group col-md-6 mb-md-0">
                                            <label for="help_accordion_title_font_size">Dimensione titolo FAQ</label>
                                            <select name="help_accordion_title_font_size" id="help_accordion_title_font_size" class="form-control">
                                                <?php echo contest_font_size_select_options_html($helpAccTitleFontSize); ?>
                                            </select>
                                            <small class="form-text text-muted">« Domande Frequenti ». Predefinito = dimensione titolo Bootstrap (h1).</small>
                                        </div>
                                        <div class="form-group col-md-6 mb-0">
                                            <label for="help_accordion_title_margin_top">Margine superiore titolo (px)</label>
                                            <input type="number" name="help_accordion_title_margin_top" id="help_accordion_title_margin_top" class="form-control" value="<?php echo (int) $helpAccTitleMarginTop; ?>" min="0" max="120" step="1">
                                            <small class="form-text text-muted">Sposta il titolo verso il basso nella card. 0 = nessun margine extra.</small>
                                        </div>
                                    </div>
                                    <div class="custom-control custom-switch mb-3">
                                        <input type="checkbox" class="custom-control-input" id="style_customization_help_page_colors" name="style_customization_help_page_colors" value="1" <?php echo $helpPageColorsCustom ? 'checked' : ''; ?>>
                                        <label class="custom-control-label" for="style_customization_help_page_colors">Personalizza stile pagina assistenza</label>
                                        <small class="form-text text-muted">Disattivo: accordion e form ereditano i colori dalla palette. Attivo: usa i colori impostati sotto.</small>
                                    </div>
                                <div id="help-page-style-editor-wrap">
                                <?php
                                    $helpPreviewFontFamily = trim((string) ($helpStyleCurrent['font_family'] ?? ''));
                                    $helpPreviewFontSize = contest_resolve_font_size((string) ($helpStyleCurrent['font_size'] ?? ''));
                                    $helpPreviewStyleAttr =
                                        '--hpp-acc-header-bg: ' . manager_safe_html((string) $helpPreviewAcc['header_bg']) . '; '
                                        . '--hpp-acc-header-text: ' . manager_safe_html((string) $helpPreviewAcc['header_text']) . '; '
                                        . '--hpp-acc-header-bg-open: ' . manager_safe_html((string) $helpPreviewAcc['header_bg_open']) . '; '
                                        . '--hpp-acc-header-text-open: ' . manager_safe_html((string) $helpPreviewAcc['header_text_open']) . '; '
                                        . '--hpp-acc-body-bg: ' . manager_safe_html((string) $helpPreviewAcc['body_bg']) . '; '
                                        . '--hpp-acc-body-text: ' . manager_safe_html((string) $helpPreviewAcc['body_text']) . '; '
                                        . '--hpp-acc-title-text: ' . manager_safe_html((string) $helpPreviewAcc['title_text']) . '; '
                                        . '--hpp-acc-border-color: ' . manager_safe_html((string) $helpPreviewAcc['border_color']) . '; '
                                        . '--hpp-form-heading: ' . manager_safe_html($helpPreviewFormHeading) . '; '
                                        . '--hpp-form-subtitle: ' . manager_safe_html($helpPreviewFormSubtitle) . '; '
                                        . '--hpp-form-text: ' . manager_safe_html($helpPreviewFormText) . '; '
                                        . '--hpp-form-placeholder: ' . manager_safe_html($helpPreviewPlaceholder) . '; '
                                        . '--hpp-form-bg: ' . manager_safe_html($helpPreviewFormBg) . '; '
                                        . '--hpp-form-border: ' . manager_safe_html($helpPreviewFormBorder) . '; '
                                        . '--hpp-palette-primary: ' . manager_safe_html($palettePrimary) . '; '
                                        . '--hpp-palette-text: ' . manager_safe_html($paletteText) . '; '
                                        . '--hpp-checkbox-accent: ' . manager_safe_html($helpPreviewCheckboxAccent) . '; '
                                        . '--hpp-consent-text: ' . manager_safe_html($helpPreviewConsentText) . '; '
                                        . '--hpp-consent-link: ' . manager_safe_html($helpPreviewConsentLink) . '; '
                                        . '--hpp-consent-link-hover: ' . manager_safe_html($helpPreviewConsentLinkHover) . '; '
                                        . '--hpp-card-bg: ' . manager_safe_html($helpCardBg) . ';';
                                    if ($helpPreviewFontFamily !== '') {
                                        $helpPreviewStyleAttr .= ' --hpp-font-family: \'' . manager_safe_html($helpPreviewFontFamily) . '\', sans-serif;';
                                    }
                                    if ($helpPreviewFontSize !== '') {
                                        $helpPreviewStyleAttr .= ' --hpp-font-size: ' . manager_safe_html($helpPreviewFontSize) . ';';
                                    }
                                    $helpPreviewTitleFontSize = contest_resolve_font_size((string) ($helpAccStyle['title_font_size'] ?? ''));
                                    $helpPreviewTitleMarginTop = max(0, min(120, (int) ($helpAccStyle['title_margin_top'] ?? 0)));
                                    if ($helpPreviewTitleFontSize !== '') {
                                        $helpPreviewStyleAttr .= ' --hpp-faq-title-size: ' . manager_safe_html($helpPreviewTitleFontSize) . ';';
                                    }
                                    $helpPreviewStyleAttr .= ' --hpp-faq-title-margin-top: ' . (int) $helpPreviewTitleMarginTop . 'px;';
                                    $helpPreviewDataAttrs =
                                        'data-palette-primary="' . manager_safe_html($palettePrimary) . '" '
                                        . 'data-palette-text="' . manager_safe_html($paletteText) . '" '
                                        . 'data-default-form-text="' . manager_safe_html($defaultFormText) . '" '
                                        . 'data-open-bg-mix="' . ($helpPreviewOpenBgMix ? '1' : '0') . '" '
                                        . 'data-placeholder-mix="' . ($helpPreviewPlaceholderMix ? '1' : '0') . '"';
                                    $helpEditorColStyle = $helpPageColorsCustom ? '' : ' style="display:none;"';
                                    $helpPreviewColClass = $helpPageColorsCustom ? 'col-xl-4 faq-preview-col' : 'col-xl-12 faq-preview-col';
                                ?>

                                <div class="row help-page-style-editor faq-style-layout help-style-block help-style-block--faq">
                                    <div class="col-xl-8 faq-style-editor-col"<?php echo $helpEditorColStyle; ?>>
                                        <h6 class="text-uppercase text-muted mt-2 mb-2">Stile accordion FAQ</h6>
                                        <p class="text-muted small mb-3">Lascia vuoto per usare i colori della palette (testo leggibile su sfondo chiaro). Se compili un campo, ha priorità solo su questa pagina.</p>
                                        <div class="form-row">
                                            <?php
                                            $helpBgColorFields(
                                                'help_accordion_header_bg',
                                                'Sfondo intestazione (chiusa)',
                                                trim((string) ($helpAccStyle['header_bg'] ?? '')),
                                                '#ffffff',
                                                'Default: bianco. Trasparente = mostra lo sfondo del sito (card e accordion inclusi).'
                                            );
                                            $helpColorFields(
                                                'help_accordion_header_text',
                                                'Testo intestazione (chiusa)',
                                                trim((string) ($helpAccStyle['header_text'] ?? '')),
                                                $palettePrimary,
                                                'Default: colore primario o testo palette'
                                            );
                                            $helpBgColorFields(
                                                'help_accordion_header_bg_open',
                                                'Sfondo intestazione (aperta)',
                                                trim((string) ($helpAccStyle['header_bg_open'] ?? '')),
                                                '',
                                                'Default: primario al 12% su bianco. Trasparente = mostra lo sfondo del sito (card e accordion inclusi).'
                                            );
                                            $helpColorFields(
                                                'help_accordion_header_text_open',
                                                'Testo intestazione (aperta)',
                                                trim((string) ($helpAccStyle['header_text_open'] ?? '')),
                                                $palettePrimary,
                                                'Default: come intestazione chiusa'
                                            );
                                            $helpBgColorFields(
                                                'help_accordion_body_bg',
                                                'Sfondo risposta (aperta)',
                                                trim((string) ($helpAccStyle['body_bg'] ?? '')),
                                                '#ffffff',
                                                'Default: bianco. Trasparente = mostra lo sfondo del sito (card e accordion inclusi).'
                                            );
                                            $helpColorFields(
                                                'help_accordion_body_text',
                                                'Testo risposta (aperta)',
                                                trim((string) ($helpAccStyle['body_text'] ?? '')),
                                                '#212529',
                                                'Default: testo scuro se palette chiara'
                                            );
                                            $helpColorFields(
                                                'help_accordion_title_text',
                                                'Titolo FAQ',
                                                trim((string) ($helpAccStyle['title_text'] ?? '')),
                                                $palettePrimary,
                                                '« Domande Frequenti » — default: come intestazione FAQ'
                                            );
                                            $helpColorFields(
                                                'help_accordion_border_color',
                                                'Colore bordo accordion',
                                                trim((string) ($helpAccStyle['border_color'] ?? '')),
                                                $palettePrimary,
                                                'Default: colore primario palette'
                                            );
                                            ?>
                                        </div>
                                    </div>
                                    <div class="<?php echo $helpPreviewColClass; ?>">
                                        <div class="help-page-style-preview-panel">
                                            <label class="d-block font-weight-bold mb-2">Anteprima Domande Frequenti</label>
                                            <p class="text-muted small mb-2">Aggiornamento in tempo reale (font e colori FAQ).</p>
                                            <div
                                                id="help-page-style-preview-faq"
                                                class="help-page-style-preview help-page-style-preview--faq"
                                                <?php echo $helpPreviewDataAttrs; ?>
                                                style="<?php echo $helpPreviewStyleAttr; ?>"
                                            >
                                                <div class="hpp-card">
                                                    <div class="hpp-faq-title">Domande Frequenti</div>
                                                    <div class="hpp-accordion">
                                                        <div class="hpp-acc-item hpp-acc-open">
                                                            <button type="button" class="hpp-acc-btn hpp-acc-btn-open" tabindex="-1">Come posso partecipare al concorso?</button>
                                                            <div class="hpp-acc-body">Effettua una spesa minima, conserva lo scontrino e registrati sul sito.</div>
                                                        </div>
                                                        <div class="hpp-acc-item">
                                                            <button type="button" class="hpp-acc-btn hpp-acc-btn-closed" tabindex="-1">Quali documenti devo conservare?</button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div id="help-form-style-options" class="help-form-enabled-only help-style-block help-style-block--form<?php echo $helpFormEnabled ? '' : ' d-none'; ?>">
                                <div class="row help-page-style-editor faq-style-layout">
                                    <div class="col-xl-8 faq-style-editor-col"<?php echo $helpEditorColStyle; ?>>
                                        <h6 class="text-uppercase text-muted mt-2 mb-2">Stile modulo « Hai bisogno del nostro supporto? »</h6>
                                        <p class="text-muted small mb-3">Titoli del modulo, campi, checkbox privacy e link. Vuoto = colori di Registrazione / palette.</p>
                                        <div class="form-row">
                                            <?php
                                            $helpColorFields(
                                                'help_form_heading_text',
                                                'Titolo modulo',
                                                trim((string) ($helpFormStyle['heading_text'] ?? '')),
                                                $palettePrimary,
                                                '« Hai bisogno del nostro supporto? » — default: primario / testo leggibile'
                                            );
                                            $helpColorFields(
                                                'help_form_subtitle_text',
                                                'Sottotitolo modulo',
                                                trim((string) ($helpFormStyle['subtitle_text'] ?? '')),
                                                '#212529',
                                                '« Compila il modulo » — default: testo palette (scuro se palette chiara)'
                                            );
                                            $helpColorFields(
                                                'help_form_field_text',
                                                'Colore testo digitato',
                                                trim((string) ($helpFormStyle['field_text'] ?? '')),
                                                $defaultFormText,
                                                'Default: colore testo campi form (config. Registrazione)'
                                            );
                                            $helpColorFields(
                                                'help_form_placeholder_text',
                                                'Colore placeholder',
                                                trim((string) ($helpFormStyle['placeholder_text'] ?? '')),
                                                '',
                                                'Default: 50% del colore testo'
                                            );
                                            $helpBgColorFields(
                                                'help_form_field_bg',
                                                'Sfondo input',
                                                trim((string) ($helpFormStyle['field_bg'] ?? '')),
                                                trim((string) ($content_current['form_field_bg_color'] ?? ($content_defaults['form_field_bg_color'] ?? '#ffffff'))),
                                                'Default: sfondo campi form'
                                            );
                                            $helpColorFields(
                                                'help_form_field_border',
                                                'Bordo input',
                                                trim((string) ($helpFormStyle['field_border'] ?? '')),
                                                trim((string) ($content_current['form_field_border_color'] ?? ($content_defaults['form_field_border_color'] ?? '#dee2e6'))),
                                                'Default: bordo campi form'
                                            );
                                            $helpColorFields(
                                                'help_form_checkbox',
                                                'Colore checkbox',
                                                trim((string) ($helpFormStyle['checkbox'] ?? '')),
                                                $paletteSecondary,
                                                'Default: checkbox Registrazione / Secondario palette'
                                            );
                                            $helpColorFields(
                                                'help_form_consent_text',
                                                'Colore testo privacy',
                                                trim((string) ($helpFormStyle['consent_text'] ?? '')),
                                                trim((string) ($content_current['form_field_consent_text_color'] ?? ($content_defaults['form_field_consent_text_color'] ?? '#000000'))),
                                                'Testo « Dichiaro di aver preso visione… »'
                                            );
                                            $helpColorFields(
                                                'help_form_consent_link',
                                                'Colore link privacy',
                                                trim((string) ($helpFormStyle['consent_link'] ?? '')),
                                                $paletteSecondary,
                                                'Link « informativa della privacy »'
                                            );
                                            $helpColorFields(
                                                'help_form_consent_link_hover',
                                                'Colore link privacy (hover)',
                                                trim((string) ($helpFormStyle['consent_link_hover'] ?? '')),
                                                trim((string) ($content_current['colors']['tertiary'] ?? ($content_defaults['colors']['tertiary'] ?? '#0a58ca'))),
                                                'Hover / focus del link privacy'
                                            );
                                            ?>
                                        </div>
                                    </div>
                                    <div class="<?php echo $helpPreviewColClass; ?>">
                                        <div class="help-page-style-preview-panel">
                                            <label class="d-block font-weight-bold mb-2">Anteprima modulo assistenza</label>
                                            <p class="text-muted small mb-2">Aggiornamento in tempo reale (font e colori del modulo).</p>
                                            <div
                                                id="help-page-style-preview-form"
                                                class="help-page-style-preview help-page-style-preview--form"
                                                <?php echo $helpPreviewDataAttrs; ?>
                                                style="<?php echo $helpPreviewStyleAttr; ?>"
                                            >
                                                <div class="hpp-card">
                                                    <div class="hpp-form-section hpp-form-section--standalone">
                                                        <div class="hpp-form-title">Hai bisogno del nostro supporto?</div>
                                                        <div class="hpp-form-sub">Compila il modulo</div>
                                                        <div class="hpp-form-fields">
                                                            <div class="hpp-field hpp-field-filled">Mario Rossi</div>
                                                            <div class="hpp-field hpp-field-placeholder">E-mail</div>
                                                        </div>
                                                        <div class="hpp-privacy">
                                                            <span class="hpp-privacy-box" aria-hidden="true"></span>
                                                            <span>Dichiaro di aver preso visione e di accettare <a href="#" class="hpp-privacy-link" tabindex="-1">l'informativa della privacy</a> *</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                </div>
                                </div>
                                </div>

                                <div id="help-page-container-editor-wrap" class="configurazione-panel faq-list-panel">
                                    <div class="configurazione-panel-header">
                                        <span class="configurazione-panel-icon"><i class="fa fa-th-large"></i></span>
                                        <div>
                                            <h6>Contenuti alternativi assistenza</h6>
                                            <p>Puoi modificare i testi dei container in qualsiasi momento. Sul pubblico vengono mostrati al posto del form solo quando il form assistenza è disabilitato.</p>
                                        </div>
                                    </div>
                                    <?php
                                    echo manager_render_page_container_editor([
                                        'tab_id' => 'help-page',
                                        'page_key' => 'help_page',
                                        'align_value' => $help_page_align_value,
                                        'containers' => $help_page_containers,
                                        'preview_containers' => $help_page_preview_containers,
                                        'preview_id' => 'help-page-preview',
                                        'default_background' => $waiting_container_default_background,
                                        'default_margin' => $waiting_container_default_margin,
                                        'default_radius' => $waiting_container_default_radius,
                                        'base_dir' => $baseDir,
                                    ]);
                                    ?>
                                </div>

                                <div class="configurazione-panel faq-list-panel mb-0">
                                <div class="configurazione-panel-header faq-list-head">
                                    <span class="configurazione-panel-icon"><i class="fa fa-question"></i></span>
                                    <div>
                                        <h6>Domande e risposte</h6>
                                        <p>Ordina, modifica o aggiungi le FAQ mostrate nella pagina pubblica.</p>
                                    </div>
                                </div>
                                <div id="faq-list">
                                    <?php foreach ($faq_items as $idx => $faq): ?>
                                        <div class="faq-block border rounded p-3 mb-3 faq-ui-block">
                                            <div class="d-flex justify-content-between align-items-center mb-2 faq-block-head">
                                                <strong>FAQ</strong>
                                                <button type="button" class="btn btn-sm btn-outline-danger btn-remove-faq" title="Rimuovi questa FAQ"><i class="fa fa-trash"></i></button>
                                            </div>
                                            <div class="form-group">
                                                <label>Ordine (numero)</label>
                                                <input type="number" name="faq_order[]" class="form-control" value="<?php echo isset($faq['order']) ? (int) $faq['order'] : ($idx + 1); ?>" min="1" step="1" placeholder="1, 2, 3...">
                                            </div>
                                            <div class="form-group">
                                                <label>Domanda</label>
                                                <input type="text" name="faq_q[]" class="form-control" value="<?php echo manager_safe_html($faq['q'] ?? ''); ?>" placeholder="Inserisci la domanda">
                                            </div>
                                            <div class="form-group mb-0">
                                                <label>Risposta</label>
                                                <textarea name="faq_a[]" class="form-control" rows="2" placeholder="Inserisci la risposta"><?php echo manager_safe_html($faq['a'] ?? ''); ?></textarea>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <div class="d-flex justify-content-between align-items-center faq-actions-row">
                                    <small class="text-muted">
                                        Usa il campo <strong>Ordine</strong> per impostare la posizione delle domande (numeri crescenti).
                                        Lascia vuote le righe che non ti servono; se tutte vuote, verranno usate le FAQ di default.
                                    </small>
                                    <button type="button" class="btn btn-outline-primary btn-sm" id="add-faq">
                                        <i class="fa fa-plus mr-1"></i>Aggiungi FAQ
                                    </button>
                                </div>
                                </div>
                                <template id="faq-template">
                                    <div class="faq-block border rounded p-3 mb-3 faq-ui-block">
                                        <div class="d-flex justify-content-between align-items-center mb-2 faq-block-head">
                                            <strong>FAQ</strong>
                                            <button type="button" class="btn btn-sm btn-outline-danger btn-remove-faq" title="Rimuovi questa FAQ"><i class="fa fa-trash"></i></button>
                                        </div>
                                        <div class="form-group">
                                            <label>Ordine (numero)</label>
                                            <input type="number" name="faq_order[]" class="form-control" value="" min="1" step="1" placeholder="1, 2, 3...">
                                        </div>
                                        <div class="form-group">
                                            <label>Domanda</label>
                                            <input type="text" name="faq_q[]" class="form-control" value="" placeholder="Inserisci la domanda">
                                        </div>
                                        <div class="form-group mb-0">
                                            <label>Risposta</label>
                                            <textarea name="faq_a[]" class="form-control" rows="2" placeholder="Inserisci la risposta"></textarea>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
