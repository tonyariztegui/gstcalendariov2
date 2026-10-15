<div class="card mt-3 mb-4 config-anchor config-step palette-footer-section palette-section" data-step="2" id="section-palette">
                                <div class="card-body">
                                    <div class="palette-footer-hero">
                                        <div>
                                            <div class="section-title palette-footer-title"><i class="fa fa-paint-brush mr-2"></i>Palette colori</div>
                                            <p class="text-muted small mb-0">Colori base e stile dei bottoni pubblici. Footer e contenuti legali: passo <strong>Branding</strong>.</p>
                                        </div>
                                        <span class="palette-footer-badge"><i class="fa fa-eyedropper mr-1"></i> Identita visiva</span>
                                    </div>
                                    <div class="config-section palette-footer-panel mb-3">
                                        <div class="palette-footer-panel-header">
                                            <span class="palette-footer-panel-icon"><i class="fa fa-paint-brush"></i></span>
                                            <div>
                                                <h6>Colori principali</h6>
                                                <p>Imposta i riferimenti cromatici principali del concorso.</p>
                                            </div>
                                        </div>
                                    <?php
                                        $colorTips = [
                                            'primary' => 'Dove si vede: bottone PARTECIPA (Registration), INVIA (Assistenza), REGOLAMENTO (Thank-you e Waiting), Continua/Invia (Quiz), Torna alla pagina principale (Errore). Anche: bordo dei campi quando clicchi dentro (focus), messaggi di conferma SweetAlert. Hover: il bottone diventa automaticamente una versione più scura di questo colore (non serve scegliere un altro colore).',
                                            'secondary' => 'Dove si vede: colore dei link cliccabili nel testo delle pagine (es. link al regolamento nel paragrafo). Anche: bottone PARTECIPA DI NUOVO e tutti i bottoni secondari — solo a riposo (prima di passarci sopra con il mouse).',
                                            'tertiary' => 'Dove si vede: colore quando passi il mouse sopra un link nel testo. Anche: colore quando passi il mouse sopra un bottone secondario (es. PARTECIPA DI NUOVO diventa questo colore al hover). Non si usa per i bottoni principali (PARTECIPA, REGOLAMENTO).',
                                            'text' => 'Dove si vede: titoli (h1, h2, h3), paragrafi e testi descrittivi nelle pagine pubbliche: Registration, Waiting, Thank-you, Assistenza, Quiz. Non cambia il colore dei bottoni (quelli seguono Primario/Secondario).',
                                        ];
                                        $colorSubtexts = [
                                            'primary' => 'Bottoni principali, focus campi, conferme',
                                            'secondary' => 'Link nel testo e bottoni secondari (a riposo)',
                                            'tertiary' => 'Hover di link e bottoni secondari',
                                            'text' => 'Titoli e paragrafi delle pagine',
                                        ];
                                    ?>
                                    <div class="form-row">
                                        <?php foreach (['primary'=>'Primario','secondary'=>'Secondario','tertiary'=>'Terziario','text'=>'Testo'] as $key => $label): ?>
                                        <div class="form-group col-md-6">
                                            <label>Colore <?php echo manager_safe_html($label); ?> <span class="text-muted ml-1" data-toggle="tooltip" title="<?php echo manager_safe_html($colorTips[$key] ?? ('Colore '.$label)); ?>"><i class="fa fa-question-circle"></i></span></label>
                                            <div class="d-flex align-items-center color-row">
                                                <input type="color" class="form-control color-input color-swatch" data-target="colors_<?php echo manager_safe_html($key); ?>_text" value="<?php echo manager_safe_html($content_current['colors'][$key] ?? '#000000'); ?>">
                                                <input type="text" name="colors_<?php echo manager_safe_html($key); ?>" id="colors_<?php echo manager_safe_html($key); ?>_text" class="form-control color-code-input" value="<?php echo manager_safe_html($content_current['colors'][$key] ?? '#000000'); ?>" placeholder="#000000">
                                            </div>
                                            <small class="form-text text-muted"><?php echo manager_safe_html($colorSubtexts[$key] ?? ''); ?></small>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <p class="small text-muted mb-0 mt-2">Questi colori si applicano automaticamente a tutte le pagine. Per cambiare solo i colori dei bottoni: Bottoni → attiva « Personalizza colori bottoni ».</p>
                                    </div>
                                    <?php
                                        $fontsConfigUi = normalize_fonts_config($content_current['fonts'] ?? ($content_defaults['fonts'] ?? []));
                                        $fontsItemsUi = $fontsConfigUi['items'];
                                    ?>
                                    <div class="config-section palette-footer-panel mb-0 mt-3">
                                        <div class="palette-footer-panel-header">
                                            <span class="palette-footer-panel-icon"><i class="fa fa-font"></i></span>
                                            <div>
                                                <h6>Font personalizzati</h6>
                                                <p>Carica .woff2 / .woff / .ttf. Restano disponibili in Famiglia Font (Summernote) e nei selettori locali. Non cambiano la tipografia globale delle pagine.</p>
                                            </div>
                                        </div>
                                        <?php if ($fontsItemsUi !== []): ?>
                                        <ul class="list-group mb-3" id="custom-fonts-list">
                                            <?php foreach ($fontsItemsUi as $fontItem): ?>
                                                <?php
                                                    $fid = (string) ($fontItem['id'] ?? '');
                                                    $ffamily = (string) ($fontItem['family'] ?? '');
                                                    $ffile = (string) ($fontItem['file'] ?? '');
                                                ?>
                                                <li class="list-group-item d-flex align-items-center justify-content-between flex-wrap">
                                                    <div>
                                                        <strong style="font-family: '<?php echo manager_safe_html($ffamily); ?>', sans-serif;"><?php echo manager_safe_html($ffamily); ?></strong>
                                                        <small class="text-muted d-block"><?php echo manager_safe_html($ffile); ?></small>
                                                    </div>
                                                    <div class="custom-control custom-checkbox ml-2">
                                                        <input type="checkbox" class="custom-control-input" id="remove_font_<?php echo manager_safe_html($fid); ?>" name="remove_font_ids[]" value="<?php echo manager_safe_html($fid); ?>">
                                                        <label class="custom-control-label text-danger" for="remove_font_<?php echo manager_safe_html($fid); ?>">Rimuovi</label>
                                                    </div>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                        <?php else: ?>
                                        <p class="small text-muted mb-3">Nessun font caricato.</p>
                                        <?php endif; ?>
                                        <div id="custom-fonts-upload-rows">
                                            <div class="custom-font-upload-row form-row align-items-end" data-font-row="0">
                                                <div class="form-group col-md-5 mb-2 mb-md-3">
                                                    <label for="custom_font_family_0">Nome famiglia</label>
                                                    <input type="text" class="form-control" name="custom_font_family[]" id="custom_font_family_0" maxlength="80" placeholder="Es. BrandSans" autocomplete="off">
                                                </div>
                                                <div class="form-group col-md-7 mb-2 mb-md-3">
                                                    <label for="upload_custom_font_0">File font</label>
                                                    <div class="custom-file">
                                                        <input type="file" class="custom-file-input" name="upload_custom_font[]" id="upload_custom_font_0" accept=".woff2,.woff,.ttf,.otf,font/woff2,font/woff,font/ttf,font/otf">
                                                        <label class="custom-file-label" for="upload_custom_font_0">Scegli file…</label>
                                                    </div>
                                                </div>
                                                <div class="form-group col-md-1 mb-2 mb-md-3 d-none custom-font-row-remove-wrap">
                                                    <button type="button" class="btn btn-outline-danger btn-block custom-font-row-remove" title="Rimuovi riga" aria-label="Rimuovi riga">
                                                        <i class="fa fa-times" aria-hidden="true"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mb-2">
                                            <button type="button" class="btn btn-sm btn-outline-secondary" id="custom-fonts-add-row">
                                                <i class="fa fa-plus mr-1" aria-hidden="true"></i>Aggiungi un altro font
                                            </button>
                                        </div>
                                        <small class="form-text text-muted mb-0">Salva il concorso per caricare. Max consigliato 2&nbsp;MB per file. Se il nome famiglia è vuoto, viene usato il nome del file.</small>
                                        <template id="custom-font-upload-row-template">
                                            <div class="custom-font-upload-row form-row align-items-end" data-font-row="">
                                                <div class="form-group col-md-5 mb-2 mb-md-3">
                                                    <label>Nome famiglia</label>
                                                    <input type="text" class="form-control" name="custom_font_family[]" maxlength="80" placeholder="Es. BrandSans" autocomplete="off">
                                                </div>
                                                <div class="form-group col-md-6 mb-2 mb-md-3">
                                                    <label>File font</label>
                                                    <div class="custom-file">
                                                        <input type="file" class="custom-file-input" name="upload_custom_font[]" accept=".woff2,.woff,.ttf,.otf,font/woff2,font/woff,font/ttf,font/otf">
                                                        <label class="custom-file-label">Scegli file…</label>
                                                    </div>
                                                </div>
                                                <div class="form-group col-md-1 mb-2 mb-md-3 custom-font-row-remove-wrap">
                                                    <button type="button" class="btn btn-outline-danger btn-block custom-font-row-remove" title="Rimuovi riga" aria-label="Rimuovi riga">
                                                        <i class="fa fa-times" aria-hidden="true"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>
                            <div class="card mt-3 mb-4 config-anchor config-step palette-footer-section buttons-section" data-step="2" id="section-buttons">
                                <div class="card-body">
                                    <div class="palette-footer-hero palette-footer-hero--buttons">
                                        <div>
                                            <div class="section-title palette-footer-title"><i class="fa fa-hand-pointer-o mr-2"></i>Bottoni</div>
                                            <p class="text-muted small mb-0">Personalizza i bottoni pubblici del concorso. Il primario viene usato per PARTECIPA/REGOLAMENTO principale; il secondario per azioni alternative come PARTECIPA DI NUOVO.</p>
                                        </div>
                                        <span class="palette-footer-badge palette-footer-badge--buttons"><i class="fa fa-mouse-pointer mr-1"></i> CTA</span>
                                    </div>
                                    <?php
                                        $styleCustomization = is_array($content_current['style_customization'] ?? null)
                                            ? $content_current['style_customization']
                                            : (is_array($content_defaults['style_customization'] ?? null) ? $content_defaults['style_customization'] : []);
                                        $buttonsColorsCustom = !empty($styleCustomization['buttons_colors']);
                                    ?>
                                    <div class="custom-control custom-switch mb-3">
                                        <input type="checkbox" class="custom-control-input" id="style_customization_buttons_colors" name="style_customization_buttons_colors" value="1" <?php echo $buttonsColorsCustom ? 'checked' : ''; ?>>
                                        <label class="custom-control-label" for="style_customization_buttons_colors">Personalizza colori bottoni</label>
                                        <small class="form-text text-muted">Disattivo: i bottoni ereditano Primario/Secondario/Terziario dalla palette. Attivo: usa i colori impostati sotto per Primario e Secondario.</small>
                                    </div>
                                    <div id="buttons-custom-editor-wrap"<?php echo $buttonsColorsCustom ? '' : ' style="display:none;"'; ?>>
                                    <div class="button-config-layout">
                                        <aside class="button-config-sidebar">
                                            <div class="button-config-sidebar-title">Seleziona bottone</div>
                                            <div class="button-config-menu" id="button-config-menu">
                                                <button type="button" class="button-config-menu-item active" data-target-key="primary">
                                                    <p class="button-config-menu-title">Bottone primario</p>
                                                    <p class="button-config-menu-sub">PARTECIPA, INVIA, REGOLAMENTO principale.</p>
                                                </button>
                                                <button type="button" class="button-config-menu-item" data-target-key="secondary">
                                                    <p class="button-config-menu-title">Bottone secondario</p>
                                                    <p class="button-config-menu-sub">PARTECIPA DI NUOVO, azioni alternative.</p>
                                                </button>
                                            </div>
                                            <div class="button-config-sidebar-note small text-muted mt-2">
                                                Il bottone <strong>Regolamento</strong> nel contenuto HTML usa lo stile primario se ha
                                                <code>btn-success</code> o <code>btn-primary</code> (è lo stesso stile Bottoni → Primario).
                                                Usa <code>btn-secondary</code> solo se vuoi lo stile secondario.
                                            </div>
                                        </aside>
                                        <div class="button-config-editor">
                                    <?php
                                        $buttonDefaults = $content_defaults['buttons'] ?? [];
                                        $buttonConfig = $content_current['buttons'] ?? [];
                                        $buttonGroups = [
                                            'primary' => [
                                                'title' => 'Bottone primario',
                                                'description' => 'PARTECIPA, INVIA, REGOLAMENTO principale e bottoni di conferma.',
                                                'fallback_text' => 'PARTECIPA',
                                                'fallback_bg' => $content_current['colors']['primary'] ?? ($content_defaults['colors']['primary'] ?? '#0d6efd'),
                                                'fallback_hover' => '#0b5ed7',
                                            ],
                                            'secondary' => [
                                                'title' => 'Bottone secondario',
                                                'description' => 'PARTECIPA DI NUOVO, bottoni alternativi e azioni secondarie.',
                                                'fallback_text' => 'SECONDARIO',
                                                'fallback_bg' => $content_defaults['buttons']['secondary']['bg'] ?? '#6c757d',
                                                'fallback_hover' => '#5c636a',
                                            ],
                                        ];
                                        $buttonPositionLabels = ['top' => 'Top (Alto)', 'center' => 'Center (Centro)', 'bottom' => 'Bottom (Basso)', 'left' => 'Left (Sinistra)', 'right' => 'Right (Destra)'];
                                        foreach ($buttonGroups as $buttonKey => $buttonMeta):
                                            $buttonCurrent = $buttonConfig[$buttonKey] ?? [];
                                            $buttonDefault = $buttonDefaults[$buttonKey] ?? [];
                                            $buttonText = (string) ($buttonCurrent['text'] ?? ($buttonDefault['text'] ?? $buttonMeta['fallback_text']));
                                            $buttonMode = strtolower((string) ($buttonCurrent['mode'] ?? ($buttonDefault['mode'] ?? 'color')));
                                            // Legacy: mode "gradient" → color + use_gradient
                                            $buttonUseGradient = !empty($buttonCurrent['use_gradient'] ?? ($buttonDefault['use_gradient'] ?? false));
                                            if ($buttonMode === 'gradient') {
                                                $buttonMode = 'color';
                                                $buttonUseGradient = true;
                                            }
                                            if (!in_array($buttonMode, ['color', 'image'], true)) {
                                                $buttonMode = 'color';
                                            }
                                            $buttonBg = (string) ($buttonCurrent['bg'] ?? ($buttonDefault['bg'] ?? $buttonMeta['fallback_bg']));
                                            $buttonTextColor = (string) ($buttonCurrent['text_color'] ?? ($buttonDefault['text_color'] ?? '#ffffff'));
                                            $buttonBorder = (string) ($buttonCurrent['border'] ?? ($buttonDefault['border'] ?? $buttonBg));
                                            $buttonHoverBg = (string) ($buttonCurrent['hover_bg'] ?? ($buttonDefault['hover_bg'] ?? $buttonMeta['fallback_hover']));
                                            $buttonHoverBorder = (string) ($buttonCurrent['hover_border'] ?? ($buttonDefault['hover_border'] ?? $buttonHoverBg));
                                            $buttonRadius = max(0, min(80, (int) ($buttonCurrent['radius'] ?? ($buttonDefault['radius'] ?? 8))));
                                            $buttonFont = contest_resolve_font_family(
                                                (string) ($buttonCurrent['font'] ?? ($buttonDefault['font'] ?? '')),
                                                is_array($content_current ?? null) ? $content_current : null
                                            );
                                            $buttonFontSize = contest_resolve_font_size(
                                                (string) ($buttonCurrent['font_size'] ?? ($buttonDefault['font_size'] ?? ''))
                                            );
                                            $buttonGradientDir = strtolower((string) ($buttonCurrent['gradient_direction'] ?? ($buttonDefault['gradient_direction'] ?? 'vertical')));
                                            if (!in_array($buttonGradientDir, ['vertical', 'horizontal'], true)) {
                                                $buttonGradientDir = 'vertical';
                                            }
                                            $buttonGradientStyle = strtolower((string) ($buttonCurrent['gradient_style'] ?? ($buttonDefault['gradient_style'] ?? 'center')));
                                            if (!in_array($buttonGradientStyle, ['linear', 'center'], true)) {
                                                $buttonGradientStyle = 'center';
                                            }
                                            $buttonGradientFrom = (string) ($buttonCurrent['gradient_from'] ?? ($buttonDefault['gradient_from'] ?? $buttonBg));
                                            $buttonGradientTo = (string) ($buttonCurrent['gradient_to'] ?? ($buttonDefault['gradient_to'] ?? $buttonHoverBg));
                                            $buttonGradientHoverFrom = (string) ($buttonCurrent['gradient_hover_from'] ?? ($buttonDefault['gradient_hover_from'] ?? $buttonGradientFrom));
                                            $buttonGradientHoverTo = (string) ($buttonCurrent['gradient_hover_to'] ?? ($buttonDefault['gradient_hover_to'] ?? $buttonGradientTo));
                                            $buttonImage = (string) ($buttonCurrent['image'] ?? ($buttonDefault['image'] ?? ''));
                                            $buttonHoverImage = (string) ($buttonCurrent['hover_image'] ?? ($buttonDefault['hover_image'] ?? ''));
                                            $buttonImageSize = (string) ($buttonCurrent['image_size'] ?? ($buttonDefault['image_size'] ?? 'cover'));
                                            $buttonImageSizeCustom = (string) ($buttonCurrent['image_size_custom'] ?? ($buttonDefault['image_size_custom'] ?? ''));
                                            $buttonImagePosV = (string) ($buttonCurrent['image_position_vertical'] ?? ($buttonDefault['image_position_vertical'] ?? 'center'));
                                            $buttonImagePosH = (string) ($buttonCurrent['image_position_horizontal'] ?? ($buttonDefault['image_position_horizontal'] ?? 'center'));
                                            $buttonHideTextOnImage = !empty($buttonCurrent['hide_text_on_image'] ?? ($buttonDefault['hide_text_on_image'] ?? true));
                                            $buttonImagePreview = manager_preview_url($buttonImage, '', $baseDir);
                                            $buttonHoverImagePreview = manager_preview_url($buttonHoverImage, '', $baseDir);
                                            $previewBgImage = $buttonMode === 'image' && $buttonImagePreview !== '' ? "url('" . manager_safe_html($buttonImagePreview) . "')" : 'none';
                                            $previewBgSize = $buttonImageSize === 'custom' && trim($buttonImageSizeCustom) !== '' ? $buttonImageSizeCustom : $buttonImageSize;
                                            $previewGradient = 'none';
                                            $previewDataMode = $buttonMode;
                                            if ($buttonMode === 'color' && $buttonUseGradient) {
                                                $gAngle = $buttonGradientDir === 'horizontal' ? '90deg' : '180deg';
                                                if ($buttonGradientStyle === 'center') {
                                                    $previewGradient = 'linear-gradient(' . $gAngle . ', ' . $buttonGradientFrom . ' 0%, ' . $buttonGradientTo . ' 50%, ' . $buttonGradientFrom . ' 100%)';
                                                } else {
                                                    $previewGradient = 'linear-gradient(' . $gAngle . ', ' . $buttonGradientFrom . ' 0%, ' . $buttonGradientTo . ' 100%)';
                                                }
                                                $previewDataMode = 'gradient';
                                            }
                                    ?>
                                    <div class="section-card mb-3 button-style-panel" data-button-key="<?php echo manager_safe_html($buttonKey); ?>">
                                        <div class="d-flex justify-content-between align-items-start flex-wrap mb-3">
                                            <div>
                                                <h6 class="text-uppercase text-muted mb-1"><?php echo manager_safe_html($buttonMeta['title']); ?></h6>
                                                <div class="small text-muted"><?php echo manager_safe_html($buttonMeta['description']); ?></div>
                                            </div>
                                            <div class="button-style-preview-wrap mt-2 mt-md-0">
                                                <span class="button-style-preview"
                                                    data-mode="<?php echo manager_safe_html($previewDataMode); ?>"
                                                    style="--button-preview-bg: <?php echo manager_safe_html($buttonBg); ?>; --button-preview-text: <?php echo manager_safe_html($buttonTextColor); ?>; --button-preview-border: <?php echo manager_safe_html($buttonBorder); ?>; --button-preview-radius: <?php echo (int) $buttonRadius; ?>px; --button-preview-image: <?php echo $previewBgImage; ?>; --button-preview-image-size: <?php echo manager_safe_html($previewBgSize); ?>; --button-preview-image-position: <?php echo manager_safe_html($buttonImagePosH . ' ' . $buttonImagePosV); ?>; --button-preview-gradient: <?php echo manager_safe_html($previewGradient); ?>; --button-preview-font: <?php echo $buttonFont !== '' ? '\'' . manager_safe_html($buttonFont) . '\', sans-serif' : 'Arial, Helvetica, sans-serif'; ?>; --button-preview-font-size: <?php echo $buttonFontSize !== '' ? manager_safe_html($buttonFontSize) : '0.9rem'; ?>;">
                                                    <span class="button-style-preview-text"><?php echo manager_safe_html($buttonText); ?></span>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="form-row">
                                            <div class="form-group col-md-4">
                                                <label>Modalità <span class="text-muted ml-1" data-toggle="tooltip" title="Colore = tinta (con opzione gradiente); Immagine = PNG come sfondo."><i class="fa fa-question-circle"></i></span></label>
                                                <select name="buttons_<?php echo manager_safe_html($buttonKey); ?>_mode" class="form-control button-mode-select" data-button-key="<?php echo manager_safe_html($buttonKey); ?>">
                                                    <option value="color" <?php echo $buttonMode === 'color' ? 'selected' : ''; ?>>Colore</option>
                                                    <option value="image" <?php echo $buttonMode === 'image' ? 'selected' : ''; ?>>Immagine</option>
                                                </select>
                                            </div>
                                            <div class="form-group col-md-4">
                                                <label>Testo bottone</label>
                                                <input type="text" name="buttons_<?php echo manager_safe_html($buttonKey); ?>_text" class="form-control button-preview-text-input" data-button-key="<?php echo manager_safe_html($buttonKey); ?>" value="<?php echo manager_safe_html($buttonText); ?>" placeholder="<?php echo manager_safe_html($buttonMeta['fallback_text']); ?>">
                                            </div>
                                            <div class="form-group col-md-4">
                                                <label>Arrotondamento (px)</label>
                                                <input type="number" name="buttons_<?php echo manager_safe_html($buttonKey); ?>_radius" class="form-control button-radius-input" data-button-key="<?php echo manager_safe_html($buttonKey); ?>" value="<?php echo (int) $buttonRadius; ?>" min="0" max="80" step="1">
                                            </div>
                                            <div class="form-group col-md-4">
                                                <label>Font <span class="text-muted ml-1" data-toggle="tooltip" title="Tipografia del bottone (non modificabile da Summernote)."><i class="fa fa-question-circle"></i></span></label>
                                                <select name="buttons_<?php echo manager_safe_html($buttonKey); ?>_font" class="form-control button-font-select" data-button-key="<?php echo manager_safe_html($buttonKey); ?>">
                                                    <?php echo contest_font_select_options_html($buttonFont, is_array($content_current ?? null) ? $content_current : null); ?>
                                                </select>
                                            </div>
                                            <div class="form-group col-md-4">
                                                <label>Dimensione</label>
                                                <select name="buttons_<?php echo manager_safe_html($buttonKey); ?>_font_size" class="form-control button-font-size-select" data-button-key="<?php echo manager_safe_html($buttonKey); ?>">
                                                    <?php echo contest_font_size_select_options_html($buttonFontSize); ?>
                                                </select>
                                            </div>
                                            <div class="form-group col-md-4">
                                                <div class="custom-control custom-checkbox mt-md-4 pt-md-2">
                                                    <input type="checkbox" class="custom-control-input button-hide-text-input" id="buttons_<?php echo manager_safe_html($buttonKey); ?>_hide_text_on_image" name="buttons_<?php echo manager_safe_html($buttonKey); ?>_hide_text_on_image" value="1" <?php echo $buttonHideTextOnImage ? 'checked' : ''; ?> data-button-key="<?php echo manager_safe_html($buttonKey); ?>">
                                                    <label class="custom-control-label" for="buttons_<?php echo manager_safe_html($buttonKey); ?>_hide_text_on_image">Nascondi testo quando uso immagine</label>
                                                </div>
                                            </div>
                                        </div>
                                        <?php
                                            $buttonColorFields = [
                                                'bg' => ['label' => 'Colore sfondo', 'value' => $buttonBg, 'placeholder' => $buttonMeta['fallback_bg'], 'var' => '--button-preview-bg'],
                                                'text_color' => ['label' => 'Colore testo', 'value' => $buttonTextColor, 'placeholder' => '#ffffff', 'var' => '--button-preview-text'],
                                                'border' => ['label' => 'Colore bordo', 'value' => $buttonBorder, 'placeholder' => $buttonBg, 'var' => '--button-preview-border'],
                                                'hover_bg' => ['label' => 'Sfondo hover', 'value' => $buttonHoverBg, 'placeholder' => $buttonMeta['fallback_hover'], 'var' => ''],
                                                'hover_border' => ['label' => 'Bordo hover', 'value' => $buttonHoverBorder, 'placeholder' => $buttonHoverBg, 'var' => ''],
                                            ];
                                            $renderButtonColorField = static function (string $fieldKey, array $field) use ($buttonKey): void {
                                                $inputId = 'buttons_' . $buttonKey . '_' . $fieldKey . '_text';
                                                $fieldValue = (string) ($field['value'] ?? '');
                                                $isTransparent = strtolower(trim($fieldValue)) === 'transparent';
                                                $fieldPlaceholder = (string) ($field['placeholder'] ?? '#ffffff');
                                                $pickerValue = (!$isTransparent && preg_match('/^#[0-9A-Fa-f]{6}$/', $fieldValue))
                                                    ? $fieldValue
                                                    : (preg_match('/^#[0-9A-Fa-f]{6}$/', $fieldPlaceholder) ? $fieldPlaceholder : '#ffffff');
                                        ?>
                                                <div class="form-group col-md-4" data-color-field="<?php echo manager_safe_html($fieldKey); ?>">
                                                    <label><?php echo manager_safe_html($field['label']); ?></label>
                                                    <div class="d-flex align-items-center color-row">
                                                        <input type="color" class="form-control color-input color-swatch button-preview-color" data-target="<?php echo manager_safe_html($inputId); ?>" data-button-key="<?php echo manager_safe_html($buttonKey); ?>" data-preview-var="<?php echo manager_safe_html($field['var']); ?>" value="<?php echo manager_safe_html($pickerValue); ?>"<?php echo $isTransparent ? ' disabled' : ''; ?>>
                                                        <input type="text" name="buttons_<?php echo manager_safe_html($buttonKey); ?>_<?php echo manager_safe_html($fieldKey); ?>" id="<?php echo manager_safe_html($inputId); ?>" class="form-control color-code-input button-preview-color-text" data-button-key="<?php echo manager_safe_html($buttonKey); ?>" data-preview-var="<?php echo manager_safe_html($field['var']); ?>" value="<?php echo manager_safe_html($fieldValue); ?>" placeholder="<?php echo manager_safe_html($fieldPlaceholder); ?>" pattern="^(#[0-9A-Fa-f]{6}|transparent)$" title="Usa un colore HEX (#RRGGBB) oppure 'transparent'">
                                                        <button type="button" class="btn btn-sm btn-outline-secondary button-set-transparent" data-target="<?php echo manager_safe_html($inputId); ?>">Transparent</button>
                                                    </div>
                                                </div>
                                        <?php
                                            };
                                        ?>
                                        <div class="button-text-color-options buttons-custom-colors-panel" style="<?php echo $buttonsColorsCustom ? '' : 'display:none;'; ?>">
                                            <div class="form-row">
                                                <?php $renderButtonColorField('text_color', $buttonColorFields['text_color']); ?>
                                            </div>
                                        </div>
                                        <div class="button-color-options buttons-custom-colors-panel" style="<?php echo ($buttonMode === 'image' || !$buttonsColorsCustom) ? 'display:none;' : ''; ?>">
                                            <div class="form-row">
                                                <?php
                                                    foreach (['bg', 'hover_bg', 'border', 'hover_border'] as $fieldKey) {
                                                        $renderButtonColorField($fieldKey, $buttonColorFields[$fieldKey]);
                                                    }
                                                ?>
                                            </div>
                                            <div class="form-row mt-1">
                                                <div class="form-group col-md-12 mb-2">
                                                    <div class="custom-control custom-checkbox">
                                                        <input type="checkbox" class="custom-control-input button-use-gradient-input" id="buttons_<?php echo manager_safe_html($buttonKey); ?>_use_gradient" name="buttons_<?php echo manager_safe_html($buttonKey); ?>_use_gradient" value="1" <?php echo $buttonUseGradient ? 'checked' : ''; ?> data-button-key="<?php echo manager_safe_html($buttonKey); ?>">
                                                        <label class="custom-control-label" for="buttons_<?php echo manager_safe_html($buttonKey); ?>_use_gradient">Usa gradiente</label>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="button-gradient-options" style="<?php echo $buttonUseGradient ? '' : 'display:none;'; ?>">
                                                <div class="form-row">
                                                    <div class="form-group col-md-4">
                                                        <label>Stile</label>
                                                        <select name="buttons_<?php echo manager_safe_html($buttonKey); ?>_gradient_style" class="form-control button-gradient-style" data-button-key="<?php echo manager_safe_html($buttonKey); ?>">
                                                            <option value="center" <?php echo $buttonGradientStyle === 'center' ? 'selected' : ''; ?>>Centrato (bordo → chiaro → bordo)</option>
                                                            <option value="linear" <?php echo $buttonGradientStyle === 'linear' ? 'selected' : ''; ?>>Lineare (inizio → fine)</option>
                                                        </select>
                                                        <small class="form-text text-muted">Centrato = highlight al centro come il PNG bottone.</small>
                                                    </div>
                                                    <div class="form-group col-md-4">
                                                        <label>Direzione</label>
                                                        <select name="buttons_<?php echo manager_safe_html($buttonKey); ?>_gradient_direction" class="form-control button-gradient-direction" data-button-key="<?php echo manager_safe_html($buttonKey); ?>">
                                                            <option value="horizontal" <?php echo $buttonGradientDir === 'horizontal' ? 'selected' : ''; ?>>Orizzontale (sinistra ↔ destra)</option>
                                                            <option value="vertical" <?php echo $buttonGradientDir === 'vertical' ? 'selected' : ''; ?>>Verticale (alto ↔ basso)</option>
                                                        </select>
                                                    </div>
                                                    <?php
                                                        $isCenterStyle = $buttonGradientStyle === 'center';
                                                        $gradientFields = [
                                                            'gradient_from' => [
                                                                'label' => $isCenterStyle ? 'Colore bordo (scuro)' : 'Gradiente inizio',
                                                                'value' => $buttonGradientFrom,
                                                                'placeholder' => '#e55a1a',
                                                                'var' => '',
                                                            ],
                                                            'gradient_to' => [
                                                                'label' => $isCenterStyle ? 'Colore centro (chiaro)' : 'Gradiente fine',
                                                                'value' => $buttonGradientTo,
                                                                'placeholder' => '#ff9a4a',
                                                                'var' => '',
                                                            ],
                                                            'gradient_hover_from' => [
                                                                'label' => $isCenterStyle ? 'Hover bordo' : 'Hover inizio',
                                                                'value' => $buttonGradientHoverFrom,
                                                                'placeholder' => $buttonGradientFrom,
                                                                'var' => '',
                                                            ],
                                                            'gradient_hover_to' => [
                                                                'label' => $isCenterStyle ? 'Hover centro' : 'Hover fine',
                                                                'value' => $buttonGradientHoverTo,
                                                                'placeholder' => $buttonGradientTo,
                                                                'var' => '',
                                                            ],
                                                        ];
                                                        foreach ($gradientFields as $fieldKey => $field) {
                                                            $renderButtonColorField($fieldKey, $field);
                                                        }
                                                    ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="button-image-options" style="<?php echo $buttonMode === 'image' ? '' : 'display:none;'; ?>">
                                            <div class="form-row">
                                                <div class="form-group col-md-6">
                                                    <label>Immagine bottone</label>
                                                    <input type="hidden" name="buttons_<?php echo manager_safe_html($buttonKey); ?>_image" value="<?php echo manager_safe_html($buttonImage); ?>">
                                                    <div class="custom-file">
                                                        <input type="file" name="upload_button_<?php echo manager_safe_html($buttonKey); ?>_image" id="upload_button_<?php echo manager_safe_html($buttonKey); ?>_image" class="custom-file-input preview-input button-image-input" accept="image/*" data-preview-target="preview_button_<?php echo manager_safe_html($buttonKey); ?>_image" data-button-key="<?php echo manager_safe_html($buttonKey); ?>" data-button-image-type="normal">
                                                        <label class="custom-file-label" for="upload_button_<?php echo manager_safe_html($buttonKey); ?>_image">Scegli immagine...</label>
                                                    </div>
                                                    <small class="form-text text-muted mt-1">Attuale: <span id="current_button_<?php echo manager_safe_html($buttonKey); ?>_image" data-default="<?php echo manager_safe_html($buttonImage); ?>"><?php echo manager_safe_html($buttonImage); ?></span></small>
                                                    <div class="thumb-preview mt-2">
                                                        <img id="preview_button_<?php echo manager_safe_html($buttonKey); ?>_image" data-current-src="<?php echo manager_safe_html($buttonImagePreview); ?>" src="<?php echo manager_safe_html($buttonImagePreview); ?>" class="img-thumbnail <?php echo $buttonImagePreview === '' ? 'd-none' : ''; ?>" alt="Anteprima bottone <?php echo manager_safe_html($buttonKey); ?>" loading="lazy">
                                                    </div>
                                                </div>
                                                <div class="form-group col-md-6">
                                                    <label>Immagine hover opzionale</label>
                                                    <input type="hidden" name="buttons_<?php echo manager_safe_html($buttonKey); ?>_hover_image" value="<?php echo manager_safe_html($buttonHoverImage); ?>">
                                                    <div class="custom-file">
                                                        <input type="file" name="upload_button_<?php echo manager_safe_html($buttonKey); ?>_hover_image" id="upload_button_<?php echo manager_safe_html($buttonKey); ?>_hover_image" class="custom-file-input preview-input button-image-input" accept="image/*" data-preview-target="preview_button_<?php echo manager_safe_html($buttonKey); ?>_hover_image" data-button-key="<?php echo manager_safe_html($buttonKey); ?>" data-button-image-type="hover">
                                                        <label class="custom-file-label" for="upload_button_<?php echo manager_safe_html($buttonKey); ?>_hover_image">Scegli immagine...</label>
                                                    </div>
                                                    <small class="form-text text-muted mt-1">Attuale: <span id="current_button_<?php echo manager_safe_html($buttonKey); ?>_hover_image" data-default="<?php echo manager_safe_html($buttonHoverImage); ?>"><?php echo manager_safe_html($buttonHoverImage); ?></span></small>
                                                    <div class="thumb-preview mt-2">
                                                        <img id="preview_button_<?php echo manager_safe_html($buttonKey); ?>_hover_image" data-current-src="<?php echo manager_safe_html($buttonHoverImagePreview); ?>" src="<?php echo manager_safe_html($buttonHoverImagePreview); ?>" class="img-thumbnail <?php echo $buttonHoverImagePreview === '' ? 'd-none' : ''; ?>" alt="Anteprima hover bottone <?php echo manager_safe_html($buttonKey); ?>" loading="lazy">
                                                    </div>
                                                </div>
                                            </div>
                                            <h6 class="text-uppercase text-muted mt-3 mb-2">Opzioni stile immagine</h6>
                                            <div class="form-row">
                                                <div class="form-group col-md-4">
                                                    <label>Dimensione</label>
                                                    <select name="buttons_<?php echo manager_safe_html($buttonKey); ?>_image_size" class="form-control button-image-size-select" data-button-key="<?php echo manager_safe_html($buttonKey); ?>">
                                                        <?php foreach (['cover' => 'Cover', 'contain' => 'Contain', 'auto' => 'Auto', 'custom' => 'Personalizzato'] as $sizeKey => $sizeLabel): ?>
                                                        <option value="<?php echo manager_safe_html($sizeKey); ?>" <?php echo $buttonImageSize === $sizeKey ? 'selected' : ''; ?>><?php echo manager_safe_html($sizeLabel); ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="form-group col-md-4 button-image-size-custom-wrapper" style="<?php echo $buttonImageSize === 'custom' ? '' : 'display:none;'; ?>">
                                                    <label>Dimensione personalizzata</label>
                                                    <input type="text" name="buttons_<?php echo manager_safe_html($buttonKey); ?>_image_size_custom" class="form-control button-image-size-custom" data-button-key="<?php echo manager_safe_html($buttonKey); ?>" value="<?php echo manager_safe_html($buttonImageSizeCustom); ?>" placeholder="100% 100%">
                                                </div>
                                                <div class="form-group col-md-4">
                                                    <label>Posizione verticale</label>
                                                    <select name="buttons_<?php echo manager_safe_html($buttonKey); ?>_image_position_vertical" class="form-control button-image-position" data-button-key="<?php echo manager_safe_html($buttonKey); ?>">
                                                        <?php foreach (['top', 'center', 'bottom'] as $positionKey): ?>
                                                        <option value="<?php echo manager_safe_html($positionKey); ?>" <?php echo $buttonImagePosV === $positionKey ? 'selected' : ''; ?>><?php echo manager_safe_html($buttonPositionLabels[$positionKey]); ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="form-group col-md-4">
                                                    <label>Posizione orizzontale</label>
                                                    <select name="buttons_<?php echo manager_safe_html($buttonKey); ?>_image_position_horizontal" class="form-control button-image-position" data-button-key="<?php echo manager_safe_html($buttonKey); ?>">
                                                        <?php foreach (['left', 'center', 'right'] as $positionKey): ?>
                                                        <option value="<?php echo manager_safe_html($positionKey); ?>" <?php echo $buttonImagePosH === $positionKey ? 'selected' : ''; ?>><?php echo manager_safe_html($buttonPositionLabels[$positionKey]); ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                        </div>
                                    </div>
                                    <div class="section-card mt-3">
                                        <h6 class="text-uppercase text-muted mb-2">Bottoni per pagina usata</h6>
                                        <p class="small text-muted mb-3">Mostra i bottoni presenti nelle pagine del concorso corrente (waiting/thank-you). Se cambi il testo qui, viene aggiornato anche il relativo preview.</p>
                                        <input type="hidden" name="buttons_help_text" id="buttons_help_text" value="<?php echo manager_safe_html((string) (($content_current['buttons']['help']['text'] ?? $content_defaults['buttons']['help']['text'] ?? 'INVIA'))); ?>">
                                        <input type="hidden" name="buttons_quiz_submit_text" id="buttons_quiz_submit_text" value="<?php echo manager_safe_html((string) (($content_current['buttons']['quiz_submit_text'] ?? $content_defaults['buttons']['quiz_submit_text'] ?? 'INVIA RISPOSTE'))); ?>">
                                        <div class="page-buttons-layout" id="page-buttons-layout">
                                            <aside class="page-buttons-pages">
                                                <div class="page-buttons-head d-flex justify-content-between align-items-center mb-2">
                                                    <strong class="small text-uppercase text-muted mb-0">Pagine</strong>
                                                    <button type="button" class="btn btn-sm btn-outline-secondary" id="refresh-page-buttons">Aggiorna</button>
                                                </div>
                                                <div id="page-buttons-pages-list" class="page-buttons-list"></div>
                                            </aside>
                                            <aside class="page-buttons-items">
                                                <strong class="small text-uppercase text-muted d-block mb-2">Bottoni trovati</strong>
                                                <div id="page-buttons-items-list" class="page-buttons-list"></div>
                                            </aside>
                                            <div class="page-buttons-editor">
                                                <strong class="small text-uppercase text-muted d-block mb-2">Modifica bottone</strong>
                                                <div id="page-buttons-editor-empty" class="page-buttons-empty small text-muted">
                                                    Seleziona una pagina e poi un bottone.
                                                </div>
                                                <div id="page-buttons-editor-form" class="d-none">
                                                    <div class="form-group">
                                                        <label class="mb-1">Testo bottone</label>
                                                        <input type="text" class="form-control" id="page-button-text-input" maxlength="120" placeholder="Testo bottone">
                                                    </div>
                                                    <div class="small text-muted">
                                                        <span id="page-button-meta"></span>
                                                    </div>
                                                </div>
                                                <?php
                                                $pagesForRegFooter = is_array($content_current['pages'] ?? null) ? $content_current['pages'] : [];
                                                $defaultsPagesRegFooter = is_array($content_defaults['pages'] ?? null) ? $content_defaults['pages'] : [];
                                                $paletteTextColor = (string) ($content_current['colors']['text'] ?? ($content_defaults['colors']['text'] ?? '#212529'));
                                                $regFooterStoreVal = (string) (($pagesForRegFooter['form_page']['button_footer_text'] ?? $defaultsPagesRegFooter['form_page']['button_footer_text']) ?? '');
                                                $regFooterColorStored = trim((string) (($pagesForRegFooter['form_page']['button_footer_text_color'] ?? $defaultsPagesRegFooter['form_page']['button_footer_text_color']) ?? ''));
                                                if ($regFooterColorStored !== '' && !preg_match('/^#[0-9A-Fa-f]{6}$/', $regFooterColorStored)) {
                                                    $regFooterColorStored = '';
                                                }
                                                $partFooterStoreVal = (string) (($pagesForRegFooter['participation_page']['button_footer_text'] ?? $defaultsPagesRegFooter['participation_page']['button_footer_text']) ?? '');
                                                $partFooterColorStored = trim((string) (($pagesForRegFooter['participation_page']['button_footer_text_color'] ?? $defaultsPagesRegFooter['participation_page']['button_footer_text_color']) ?? ''));
                                                if ($partFooterColorStored !== '' && !preg_match('/^#[0-9A-Fa-f]{6}$/', $partFooterColorStored)) {
                                                    $partFooterColorStored = '';
                                                }
                                                $regFooterColorSwatch = $regFooterColorStored !== '' ? $regFooterColorStored : $paletteTextColor;
                                                ?>
                                                <div id="page-button-footer-wrap" class="d-none mt-3 pt-3 border-top">
                                                    <div class="form-group mb-0">
                                                        <label class="mb-1">Testo sotto i bottoni <span id="page-button-footer-context-label" class="text-muted font-weight-normal">(opzionale — sotto i bottoni della pagina)</span></label>
                                                        <textarea class="form-control" id="page-button-footer-input" rows="4" placeholder="Es. validità concorso, montepremi, link al regolamento…" autocomplete="off"></textarea>
                                                        <label class="mt-2 mb-1 small">Colore testo sotto i bottoni</label>
                                                        <div class="d-flex align-items-center color-row">
                                                            <input type="color" class="form-control color-input color-swatch" id="page-button-footer-color-swatch" data-target="page-button-footer-color-text" value="<?php echo htmlspecialchars($regFooterColorSwatch, ENT_QUOTES, 'UTF-8'); ?>">
                                                            <input type="text" id="page-button-footer-color-text" class="form-control color-code-input" value="<?php echo htmlspecialchars($regFooterColorStored, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Palette Testo" pattern="^$|^#[0-9A-Fa-f]{6}$" autocomplete="off">
                                                        </div>
                                                        <?php
                                                            $regFooterFontStored = trim((string) (($content_current['pages']['form_page']['button_footer_text_font'] ?? '') ?: ''));
                                                            $partFooterFontStored = trim((string) (($content_current['pages']['participation_page']['button_footer_text_font'] ?? '') ?: ''));
                                                        ?>
                                                        <label class="mt-2 mb-1 small" for="page-button-footer-font">Font testo sotto i bottoni</label>
                                                        <select class="form-control" id="page-button-footer-font" autocomplete="off">
                                                            <?php echo contest_font_select_options_html($regFooterFontStored, $content_current); ?>
                                                        </select>
                                                        <small class="form-text text-muted">Vuoto = tipografia della pagina. Segnaposto: {{CONTEST_NAME}}, {{START_DATE}}, {{END_DATE}}, {{MIN_SPEND}}, {{REGOLAMENTO_LINK}}</small>
                                                    </div>
                                                </div>
                                                <textarea class="d-none" name="form_page_button_footer_text" id="store_form_page_button_footer_text" aria-hidden="true"><?php echo htmlspecialchars($regFooterStoreVal, ENT_QUOTES, 'UTF-8'); ?></textarea>
                                                <input type="hidden" name="form_page_button_footer_text_color" id="store_form_page_button_footer_text_color" value="<?php echo htmlspecialchars($regFooterColorStored, ENT_QUOTES, 'UTF-8'); ?>">
                                                <input type="hidden" name="form_page_button_footer_text_font" id="store_form_page_button_footer_text_font" value="<?php echo htmlspecialchars($regFooterFontStored, ENT_QUOTES, 'UTF-8'); ?>">
                                                <textarea class="d-none" name="participation_page_button_footer_text" id="store_participation_page_button_footer_text" aria-hidden="true"><?php echo htmlspecialchars($partFooterStoreVal, ENT_QUOTES, 'UTF-8'); ?></textarea>
                                                <input type="hidden" name="participation_page_button_footer_text_color" id="store_participation_page_button_footer_text_color" value="<?php echo htmlspecialchars($partFooterColorStored, ENT_QUOTES, 'UTF-8'); ?>">
                                                <input type="hidden" name="participation_page_button_footer_text_font" id="store_participation_page_button_footer_text_font" value="<?php echo htmlspecialchars($partFooterFontStored, ENT_QUOTES, 'UTF-8'); ?>">
                                            </div>
                                        </div>
                                    </div>
                                    </div>
                                </div>
                            </div>
