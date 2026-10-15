<?php
    if (!isset($styleCustomization) || !is_array($styleCustomization)) {
        $styleCustomization = is_array($content_current['style_customization'] ?? null)
            ? $content_current['style_customization']
            : (is_array($content_defaults['style_customization'] ?? null) ? $content_defaults['style_customization'] : []);
    }
?>
                            <div class="card mt-3 mb-4 config-anchor <?= isset($footerEditorStep) ? '' : 'config-step' ?> palette-footer-section footer-section" data-step="<?= $footerEditorStep ?? 3 ?>" id="section-footer">
                                <div class="card-body">
                                    <div class="palette-footer-hero palette-footer-hero--footer">
                                        <div>
                                            <div class="section-title palette-footer-title"><i class="fa fa-columns mr-2"></i>Footer</div>
                                            <p class="text-muted small mb-0">Configura aspetto, disposizione, contenuti visibili e testi del footer pubblico.</p>
                                        </div>
                                        <span class="palette-footer-badge palette-footer-badge--footer"><i class="fa fa-columns mr-1"></i> Footer pubblico</span>
                                    </div>
                                    <div class="palette-footer-panel">
                                        <div class="palette-footer-panel-header">
                                            <span class="palette-footer-panel-icon"><i class="fa fa-magic"></i></span>
                                            <div>
                                                <h6>Aspetto footer</h6>
                                                <p>Scegli sfondo, colori dei testi, link e bordo del footer pubblico.</p>
                                            </div>
                                        </div>
                                    <div class="form-group">
                                        <label>Modalità <span class="text-muted ml-1" data-toggle="tooltip" title="Tipo di sfondo del footer: colore solido o immagine."><i class="fa fa-question-circle"></i></span></label>
                                        <select name="footer_style_mode" id="footer_style_mode" class="form-control">
                                            <option value="color" <?php echo ($content_current['footer_style']['mode'] ?? 'color') === 'color' ? 'selected' : ''; ?>>Colore</option>
                                            <option value="image" <?php echo ($content_current['footer_style']['mode'] ?? 'color') === 'image' ? 'selected' : ''; ?>>Immagine</option>
                                        </select>
                                    </div>
                                    <div id="footer_color_options" style="<?php echo ($content_current['footer_style']['mode'] ?? 'color') === 'image' ? 'display:none;' : ''; ?>">
                                        <div class="form-row">
                                            <div class="form-group col-md-6">
                                                <label>Sfondo <span class="text-muted ml-1" data-toggle="tooltip" title="Colore di sfondo del footer."><i class="fa fa-question-circle"></i></span></label>
                                                <div class="d-flex align-items-center color-row">
                                                    <input type="color" class="form-control color-input color-swatch" data-target="footer_style_background_text" value="<?php echo manager_safe_html($content_current['footer_style']['background'] ?? '#ffffff'); ?>">
                                                    <input type="text" name="footer_style_background" id="footer_style_background_text" class="form-control color-code-input" value="<?php echo manager_safe_html($content_current['footer_style']['background'] ?? '#ffffff'); ?>" placeholder="#ffffff">
                                                </div>
                                            </div>
                                            <div class="form-group col-md-6">
                                                <label>Trasparenza <span class="text-muted ml-1" data-toggle="tooltip" title="0 = completamente trasparente, 100 = completamente opaco."><i class="fa fa-question-circle"></i></span></label>
                                                <div class="d-flex align-items-center opacity-row">
                                                    <input type="range" class="form-control-range opacity-slider" id="footer_style_opacity_slider" min="0" max="100" step="1" value="<?php echo manager_safe_html((string)($content_current['footer_style']['opacity'] ?? 100)); ?>">
                                                    <input type="number" name="footer_style_opacity" id="footer_style_opacity" class="form-control opacity-input ml-2" min="0" max="100" value="<?php echo manager_safe_html((string)($content_current['footer_style']['opacity'] ?? 100)); ?>">
                                                    <span class="ml-1">%</span>
                                                </div>
                                                <small class="form-text text-muted d-none">Opacita del footer (0-100%).</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div id="footer_image_options" style="<?php echo ($content_current['footer_style']['mode'] ?? 'color') === 'color' ? 'display:none;' : ''; ?>">
                                        <div class="form-group">
                                            <label>Immagine sfondo <span class="text-muted ml-1" data-toggle="tooltip" title="Immagine di sfondo del footer. Verrà ripetuta o ridimensionata per coprire l'area."><i class="fa fa-question-circle"></i></span></label>
                                            <input type="hidden" name="footer_style_image" value="<?php echo manager_safe_html($content_current['footer_style']['image'] ?? ''); ?>">
                                            <div class="custom-file">
                                                <input type="file" name="upload_footer_image" id="upload_footer_image" class="custom-file-input preview-input" accept="image/*" data-preview-target="preview_footer_image">
                                                <label class="custom-file-label" for="upload_footer_image">Scegli immagine...</label>
                                            </div>
                                            <small class="form-text text-muted mt-1">Attuale: <span id="current_footer_image" data-default="<?php echo manager_safe_html($content_current['footer_style']['image'] ?? ''); ?>"><?php echo manager_safe_html($content_current['footer_style']['image'] ?? ''); ?></span></small>
                                            <div class="thumb-preview mt-2">
                                                <?php $footerImgPreview = manager_preview_url($content_current['footer_style']['image'] ?? '', '', $baseDir); ?>
                                                <img id="preview_footer_image" data-current-src="<?php echo manager_safe_html($footerImgPreview); ?>" src="<?php echo manager_safe_html($footerImgPreview); ?>" class="img-thumbnail <?php echo $footerImgPreview === '' ? 'd-none' : ''; ?>" alt="Anteprima immagine footer" loading="lazy">
                                            </div>
                                        </div>
                                        <h6 class="text-uppercase text-muted mt-3 mb-2">Opzioni stile immagine</h6>
                                        <div class="form-row">
                                            <div class="form-group col-md-6">
                                                <label>Posizione verticale <span class="text-muted ml-1" data-toggle="tooltip" title="Posizione verticale dell'immagine nel footer."><i class="fa fa-question-circle"></i></span></label>
                                                <select name="footer_style_image_position_vertical" id="footer_style_image_position_vertical" class="form-control">
                                                    <option value="top" <?php echo ($content_current['footer_style']['image_position_vertical'] ?? 'center') === 'top' ? 'selected' : ''; ?>>Top (Alto)</option>
                                                    <option value="center" <?php echo ($content_current['footer_style']['image_position_vertical'] ?? 'center') === 'center' ? 'selected' : ''; ?>>Center (Centro)</option>
                                                    <option value="bottom" <?php echo ($content_current['footer_style']['image_position_vertical'] ?? 'center') === 'bottom' ? 'selected' : ''; ?>>Bottom (Basso)</option>
                                                </select>
                                            </div>
                                            <div class="form-group col-md-6">
                                                <label>Posizione orizzontale <span class="text-muted ml-1" data-toggle="tooltip" title="Posizione orizzontale dell'immagine nel footer."><i class="fa fa-question-circle"></i></span></label>
                                                <select name="footer_style_image_position_horizontal" id="footer_style_image_position_horizontal" class="form-control">
                                                    <option value="left" <?php echo ($content_current['footer_style']['image_position_horizontal'] ?? 'center') === 'left' ? 'selected' : ''; ?>>Left (Sinistra)</option>
                                                    <option value="center" <?php echo ($content_current['footer_style']['image_position_horizontal'] ?? 'center') === 'center' ? 'selected' : ''; ?>>Center (Centro)</option>
                                                    <option value="right" <?php echo ($content_current['footer_style']['image_position_horizontal'] ?? 'center') === 'right' ? 'selected' : ''; ?>>Right (Destra)</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="form-row">
                                            <div class="form-group col-md-6">
                                                <label>Dimensione <span class="text-muted ml-1" data-toggle="tooltip" title="Come l'immagine deve essere ridimensionata per adattarsi al footer."><i class="fa fa-question-circle"></i></span></label>
                                                <select name="footer_style_image_size" id="footer_style_image_size" class="form-control">
                                                    <option value="cover" <?php echo ($content_current['footer_style']['image_size'] ?? 'cover') === 'cover' ? 'selected' : ''; ?>>Cover (Copre tutto)</option>
                                                    <option value="contain" <?php echo ($content_current['footer_style']['image_size'] ?? 'cover') === 'contain' ? 'selected' : ''; ?>>Contain (Contiene tutto)</option>
                                                    <option value="auto" <?php echo ($content_current['footer_style']['image_size'] ?? 'cover') === 'auto' ? 'selected' : ''; ?>>Auto (Dimensione originale)</option>
                                                    <option value="custom" <?php echo ($content_current['footer_style']['image_size'] ?? 'cover') === 'custom' ? 'selected' : ''; ?>>Personalizzato</option>
                                                </select>
                                            </div>
                                            <div class="form-group col-md-6" id="footer_image_size_custom_wrapper" style="<?php echo ($content_current['footer_style']['image_size'] ?? 'cover') === 'custom' ? '' : 'display:none;'; ?>">
                                                <label>Dimensione personalizzata <span class="text-muted ml-1" data-toggle="tooltip" title="Esempi: 100% 100% (stretch), 200px 300px, 50% auto"><i class="fa fa-question-circle"></i></span></label>
                                                <input type="text" name="footer_style_image_size_custom" id="footer_style_image_size_custom" class="form-control" value="<?php echo manager_safe_html($content_current['footer_style']['image_size_custom'] ?? ''); ?>" placeholder="100% 100%">
                                                <small class="form-text text-muted d-none">Es: 100% 100%, 200px 300px, 50% auto</small>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label>Ripetizione <span class="text-muted ml-1" data-toggle="tooltip" title="Come l'immagine deve essere ripetuta se più piccola del footer."><i class="fa fa-question-circle"></i></span></label>
                                            <select name="footer_style_image_repeat" id="footer_style_image_repeat" class="form-control">
                                                <option value="no-repeat" <?php echo ($content_current['footer_style']['image_repeat'] ?? 'no-repeat') === 'no-repeat' ? 'selected' : ''; ?>>No-repeat (Non ripetere)</option>
                                                <option value="repeat" <?php echo ($content_current['footer_style']['image_repeat'] ?? 'no-repeat') === 'repeat' ? 'selected' : ''; ?>>Repeat (Ripeti in tutte le direzioni)</option>
                                                <option value="repeat-x" <?php echo ($content_current['footer_style']['image_repeat'] ?? 'no-repeat') === 'repeat-x' ? 'selected' : ''; ?>>Repeat-X (Ripeti orizzontalmente)</option>
                                                <option value="repeat-y" <?php echo ($content_current['footer_style']['image_repeat'] ?? 'no-repeat') === 'repeat-y' ? 'selected' : ''; ?>>Repeat-Y (Ripeti verticalmente)</option>
                                            </select>
                                        </div>
                                    </div>
                                    <?php $footerTextColorsCustom = !empty($styleCustomization['footer_text_colors']); ?>
                                    <div class="custom-control custom-switch mt-3 mb-2">
                                        <input type="checkbox" class="custom-control-input" id="style_customization_footer_text_colors" name="style_customization_footer_text_colors" value="1" <?php echo $footerTextColorsCustom ? 'checked' : ''; ?>>
                                        <label class="custom-control-label" for="style_customization_footer_text_colors">Personalizza colori testo footer</label>
                                        <small class="form-text text-muted">Disattivo: testi e link ereditano dalla palette. Attivo: usa i colori impostati sotto.</small>
                                    </div>
                                    <div id="footer-text-colors-panel"<?php echo $footerTextColorsCustom ? '' : ' style="display:none;"'; ?>>
                                    <h6 class="text-uppercase text-muted mt-3 mb-2">Colori testo</h6>
                                    <div class="form-row">
                                        <div class="form-group col-md-6">
                                            <label>Testo primario <span class="text-muted ml-1" data-toggle="tooltip" title="Colore del testo principale (titoli, info concorso)."><i class="fa fa-question-circle"></i></span></label>
                                            <div class="d-flex align-items-center color-row">
                                                <input type="color" class="form-control color-input color-swatch" data-target="footer_style_text_primary_text" value="<?php echo manager_safe_html($content_current['footer_style']['text_primary'] ?? '#212529'); ?>">
                                                <input type="text" name="footer_style_text_primary" id="footer_style_text_primary_text" class="form-control color-code-input" value="<?php echo manager_safe_html($content_current['footer_style']['text_primary'] ?? '#212529'); ?>" placeholder="#212529">
                                            </div>
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label>Testo secondario <span class="text-muted ml-1" data-toggle="tooltip" title="Colore del testo secondario (descrizioni, note)."><i class="fa fa-question-circle"></i></span></label>
                                            <div class="d-flex align-items-center color-row">
                                                <input type="color" class="form-control color-input color-swatch" data-target="footer_style_text_secondary_text" value="<?php echo manager_safe_html($content_current['footer_style']['text_secondary'] ?? '#6c757d'); ?>">
                                                <input type="text" name="footer_style_text_secondary" id="footer_style_text_secondary_text" class="form-control color-code-input" value="<?php echo manager_safe_html($content_current['footer_style']['text_secondary'] ?? '#6c757d'); ?>" placeholder="#6c757d">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-row">
                                        <div class="form-group col-md-6">
                                            <label>Colore link <span class="text-muted ml-1" data-toggle="tooltip" title="Colore dei link nel footer (Regolamento, Privacy, ecc.)."><i class="fa fa-question-circle"></i></span></label>
                                            <div class="d-flex align-items-center color-row">
                                                <input type="color" class="form-control color-input color-swatch" data-target="footer_style_link_text" value="<?php echo manager_safe_html($content_current['footer_style']['link'] ?? '#0d6efd'); ?>">
                                                <input type="text" name="footer_style_link" id="footer_style_link_text" class="form-control color-code-input" value="<?php echo manager_safe_html($content_current['footer_style']['link'] ?? '#0d6efd'); ?>" placeholder="#0d6efd">
                                            </div>
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label>Link hover <span class="text-muted ml-1" data-toggle="tooltip" title="Colore dei link al passaggio del mouse."><i class="fa fa-question-circle"></i></span></label>
                                            <div class="d-flex align-items-center color-row">
                                                <input type="color" class="form-control color-input color-swatch" data-target="footer_style_link_hover_text" value="<?php echo manager_safe_html($content_current['footer_style']['link_hover'] ?? '#0a58ca'); ?>">
                                                <input type="text" name="footer_style_link_hover" id="footer_style_link_hover_text" class="form-control color-code-input" value="<?php echo manager_safe_html($content_current['footer_style']['link_hover'] ?? '#0a58ca'); ?>" placeholder="#0a58ca">
                                            </div>
                                        </div>
                                    </div>
                                    </div>
                                    <div class="mt-3">
                                        <div class="form-group">
                                            <div class="custom-control custom-checkbox">
                                                <?php 

                                                $borderWidth = (int)($content_current['footer_style']['border_width'] ?? 0);


                                                $borderSides = isset($content_current['footer_style']['border_sides']) 
                                                    ? (is_array($content_current['footer_style']['border_sides']) ? $content_current['footer_style']['border_sides'] : [])
                                                    : [];
                                                $borderEnabled = $borderWidth > 0 && !empty($borderSides);
                                                ?>
                                                <input type="checkbox" class="custom-control-input" id="footer_border_enabled" name="footer_style_border_enabled" value="1" <?php echo $borderEnabled ? 'checked' : ''; ?>>
                                                <label class="custom-control-label" for="footer_border_enabled">
                                                    <strong>Abilita bordo</strong> <span class="text-muted ml-1" data-toggle="tooltip" title="Se attivato, puoi personalizzare colore, trasparenza, spessore e lati del bordo del footer."><i class="fa fa-question-circle"></i></span>
                                                </label>
                                            </div>
                                        </div>
                                        
                                        <div id="footer_border_options" style="<?php echo $borderEnabled ? '' : 'display:none;'; ?>">
                                            <h6 class="text-uppercase text-muted mt-3 mb-2">Bordo</h6>
                                            <div class="form-row">
                                                <div class="form-group col-md-6">
                                                    <label>Colore bordo <span class="text-muted ml-1" data-toggle="tooltip" title="Colore del bordo del footer."><i class="fa fa-question-circle"></i></span></label>
                                                    <div class="d-flex align-items-center color-row">
                                                        <?php 

                                                        $borderColor = '#000000';
                                                        if (isset($content_current['footer_style']['border_color'])) {
                                                            $borderColor = $content_current['footer_style']['border_color'];
                                                        } elseif (isset($content_current['footer_style']['border']) && preg_match('/rgba?\((\d+),\s*(\d+),\s*(\d+)/', $content_current['footer_style']['border'], $matches)) {
                                                            $r = str_pad(dechex($matches[1]), 2, '0', STR_PAD_LEFT);
                                                            $g = str_pad(dechex($matches[2]), 2, '0', STR_PAD_LEFT);
                                                            $b = str_pad(dechex($matches[3]), 2, '0', STR_PAD_LEFT);
                                                            $borderColor = '#' . $r . $g . $b;
                                                        }
                                                        ?>
                                                        <input type="color" class="form-control color-input color-swatch" data-target="footer_style_border_color_text" value="<?php echo manager_safe_html($borderColor); ?>">
                                                        <input type="text" name="footer_style_border_color" id="footer_style_border_color_text" class="form-control color-code-input" value="<?php echo manager_safe_html($borderColor); ?>" placeholder="#000000">
                                                    </div>
                                                </div>
                                                <div class="form-group col-md-6">
                                                    <label>Trasparenza bordo <span class="text-muted ml-1" data-toggle="tooltip" title="0 = completamente trasparente (bordo invisibile), 100 = completamente opaco."><i class="fa fa-question-circle"></i></span></label>
                                                    <div class="d-flex align-items-center opacity-row">
                                                        <?php 

                                                        $borderOpacity = 10;
                                                        if (isset($content_current['footer_style']['border_opacity'])) {
                                                            $borderOpacity = (int)$content_current['footer_style']['border_opacity'];
                                                        } elseif (isset($content_current['footer_style']['border']) && preg_match('/rgba?\(\d+,\s*\d+,\s*\d+,\s*([\d.]+)\)/', $content_current['footer_style']['border'], $matches)) {
                                                            $borderOpacity = (int)round((float)$matches[1] * 100);
                                                        }
                                                        ?>
                                                        <input type="range" class="form-control-range opacity-slider" id="footer_style_border_opacity_slider" min="0" max="100" step="1" value="<?php echo manager_safe_html((string)$borderOpacity); ?>">
                                                        <input type="number" name="footer_style_border_opacity" id="footer_style_border_opacity" class="form-control opacity-input ml-2" min="0" max="100" value="<?php echo manager_safe_html((string)$borderOpacity); ?>">
                                                        <span class="ml-1">%</span>
                                                    </div>
                                                    <small class="form-text text-muted d-none">Opacita del bordo (0-100%).</small>
                                                </div>
                                            </div>
                                            <div class="form-row">
                                                <div class="form-group col-md-6">
                                                    <label>Spessore bordo (px) <span class="text-muted ml-1" data-toggle="tooltip" title="Spessore del bordo in pixel."><i class="fa fa-question-circle"></i></span></label>
                                                    <input type="number" name="footer_style_border_width" id="footer_style_border_width" class="form-control" min="0" max="20" value="<?php echo manager_safe_html((string)$borderWidth); ?>">
                                                    <small class="form-text text-muted d-none">Spessore del bordo in pixel (0-20px).</small>
                                                </div>
                                                <div class="form-group col-md-6">
                                                    <label>Lati bordo <span class="text-muted ml-1" data-toggle="tooltip" title="Seleziona su quali lati del footer mostrare il bordo."><i class="fa fa-question-circle"></i></span></label>
                                                    <div class="d-flex flex-wrap">
                                                        <?php 
                                                        $sideLabels = ['top' => 'Alto', 'right' => 'Destra', 'bottom' => 'Basso', 'left' => 'Sinistra'];
                                                        foreach (['top', 'right', 'bottom', 'left'] as $side):
                                                        ?>
                                                        <div class="custom-control custom-checkbox mr-3 mb-2">
                                                            <input type="checkbox" class="custom-control-input" id="footer_border_<?php echo $side; ?>" name="footer_style_border_sides[]" value="<?php echo $side; ?>" <?php echo in_array($side, $borderSides, true) ? 'checked' : ''; ?>>
                                                            <label class="custom-control-label" for="footer_border_<?php echo $side; ?>"><?php echo manager_safe_html($sideLabels[$side]); ?></label>
                                                        </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                    <small class="form-text text-muted d-none">Seleziona i lati dove mostrare il bordo.</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    </div>

                                    <?php
                                    $footerLayoutDefaults = is_array($content_defaults['footer_layout'] ?? null) ? $content_defaults['footer_layout'] : [];
                                    $footerLayout = normalize_footer_layout(
                                        is_array($content_current['footer_layout'] ?? null) ? $content_current['footer_layout'] : [],
                                        $footerLayoutDefaults
                                    );
                                    $flBlocks = $footerLayout['blocks'];
                                    $flTexts = $footerLayout['texts'];
                                    $flTemplate = $footerLayout['template'];
                                    $flShowLogo = !empty($footerLayout['show_logo']);
                                    $flUseContestLogo = !empty($footerLayout['use_contest_logo']);
                                    $flLogoImage = (string) ($footerLayout['logo_image'] ?? '');
                                    $flLogoHeight = (int) ($footerLayout['logo_height_px'] ?? 60);
                                    $footerLogoPreview = manager_preview_url($flLogoImage, '', $baseDir);
                                    ?>
                                    <div class="palette-footer-panel mb-0">
                                    <div class="palette-footer-panel-header">
                                        <span class="palette-footer-panel-icon"><i class="fa fa-th-large"></i></span>
                                        <div>
                                            <h6>Disposizione e contenuti</h6>
                                            <p>Scegli layout e elementi visibili. Apri personalizzazione per testi e logo.</p>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label>Layout footer</label>
                                        <select name="footer_layout_template" id="footer_layout_template" class="form-control">
                                            <option value="three_columns" <?php echo $flTemplate === 'three_columns' ? 'selected' : ''; ?>>3 colonne (informativo)</option>
                                            <option value="centered_compact" <?php echo $flTemplate === 'centered_compact' ? 'selected' : ''; ?>>Centrato compatto (logo + testi legali)</option>
                                        </select>
                                    </div>
                                    <div class="form-row">
                                        <div class="form-group col-md-6">
                                            <label for="footer_layout_font_family">Font footer</label>
                                            <select name="footer_layout_font_family" id="footer_layout_font_family" class="form-control">
                                                <?php
                                                    $flFontFamily = trim((string) ($footerLayout['font_family'] ?? ''));
                                                    echo contest_font_select_options_html($flFontFamily, $content_current);
                                                ?>
                                            </select>
                                            <small class="form-text text-muted">Predefinito = tipografia pagina.</small>
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label for="footer_layout_font_size">Dimensione font footer</label>
                                            <select name="footer_layout_font_size" id="footer_layout_font_size" class="form-control">
                                                <?php
                                                    $flFontSize = contest_resolve_font_size((string) ($footerLayout['font_size'] ?? ''));
                                                    echo contest_font_size_select_options_html($flFontSize);
                                                ?>
                                            </select>
                                            <small class="form-text text-muted">Predefinito = dimensione pagina. Visibile nell&apos;anteprima footer.</small>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="d-block mb-2">Elementi visibili</label>
                                        <p class="small text-muted mb-2">Deseleziona un elemento per nasconderlo dal footer. I testi legali 1/2 (layout centrato) mostrano l&apos;editor solo se attivati.</p>
                                        <div class="row" id="footer-layout-blocks-grid">
                                            <?php
                                            $ccFooterEnabled = !empty(($content_current['cookie_consent'] ?? [])['enabled']);
                                            $blockLabels = [
                                                'contest_title' => 'Titolo concorso',
                                                'contest_dates' => 'Date / intro',
                                                'prize_total' => 'Montepremi',
                                                'links_regolamento' => 'Link regolamento',
                                                'links_privacy' => 'Link privacy',
                                                'links_cookie_policy' => 'Link Cookie Policy',
                                                'links_assistenza' => 'Link assistenza',
                                                'promoter' => 'Promotore',
                                                'delegate' => 'Delegato',
                                                'legal_block_1' => 'Testo legale 1',
                                                'legal_block_2' => 'Testo legale 2',
                                            ];
                                            foreach ($blockLabels as $blockKey => $blockLabel):
                                                $isCookiePolicyBlock = ($blockKey === 'links_cookie_policy');
                                                $cookiePolicyHidden = $isCookiePolicyBlock && !$ccFooterEnabled;
                                                $blockChecked = !empty($flBlocks[$blockKey]) && !($isCookiePolicyBlock && !$ccFooterEnabled);
                                            ?>
                                            <div class="col-md-6 col-lg-4 mb-2 footer-layout-block-item" data-block-key="<?php echo manager_safe_html($blockKey); ?>"<?php echo $cookiePolicyHidden ? ' style="display:none;"' : ''; ?>>
                                                <div class="custom-control custom-checkbox">
                                                    <input type="checkbox" class="custom-control-input" id="footer_layout_block_<?php echo manager_safe_html($blockKey); ?>" name="footer_layout_block_<?php echo manager_safe_html($blockKey); ?>" value="1" <?php echo $blockChecked ? 'checked' : ''; ?><?php echo $cookiePolicyHidden ? ' disabled' : ''; ?>>
                                                    <label class="custom-control-label" for="footer_layout_block_<?php echo manager_safe_html($blockKey); ?>"><?php echo manager_safe_html($blockLabel); ?></label>
                                                </div>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>

                                    <?php
                                    $flLegal1On = ($flTemplate === 'centered_compact') && !empty($flBlocks['legal_block_1']);
                                    $flLegal2On = ($flTemplate === 'centered_compact') && !empty($flBlocks['legal_block_2']);
                                    ?>
                                    <div class="footer-layout-perso-wrap mb-3">
                                        <button type="button"
                                                class="btn btn-sm btn-outline-secondary footer-layout-perso-toggle w-100 text-left"
                                                data-toggle="collapse"
                                                data-target="#footer-layout-perso-body"
                                                aria-expanded="false"
                                                aria-controls="footer-layout-perso-body">
                                            <span><i class="fa fa-sliders mr-1"></i> Personalizzazione footer</span>
                                            <i class="fa fa-chevron-down footer-layout-perso-chevron" aria-hidden="true"></i>
                                        </button>
                                        <div id="footer-layout-perso-body" class="collapse footer-layout-perso-body">
                                            <div id="footer-layout-centered-only" style="<?php echo $flTemplate === 'centered_compact' ? '' : 'display:none;'; ?>">
                                                <div class="form-row footer-logo-options-row">
                                                    <div class="form-group col-md-4">
                                                        <div class="footer-logo-option-card footer-logo-option-card--check">
                                                            <div class="custom-control custom-checkbox">
                                                                <input type="checkbox" class="custom-control-input" id="footer_layout_show_logo" name="footer_layout_show_logo" value="1" <?php echo $flShowLogo ? 'checked' : ''; ?>>
                                                                <label class="custom-control-label" for="footer_layout_show_logo">Mostra logo nel footer</label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="form-group col-md-4">
                                                        <div class="footer-logo-option-card footer-logo-option-card--check">
                                                            <div class="custom-control custom-checkbox">
                                                                <input type="checkbox" class="custom-control-input" id="footer_layout_use_contest_logo" name="footer_layout_use_contest_logo" value="1" <?php echo $flUseContestLogo ? 'checked' : ''; ?>>
                                                                <label class="custom-control-label" for="footer_layout_use_contest_logo">Usa logo concorso se vuoto</label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="form-group col-md-4">
                                                        <div class="footer-logo-option-card footer-logo-option-card--input">
                                                            <label for="footer_layout_logo_height_px">Altezza logo (px)</label>
                                                            <input type="number" name="footer_layout_logo_height_px" id="footer_layout_logo_height_px" class="form-control" min="24" max="200" value="<?php echo manager_safe_html((string) $flLogoHeight); ?>">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="alert alert-light border small py-2 mb-3">
                                                    Il logo è un&apos;immagine <strong>sopra il testo</strong> (non sostituisce lo sfondo). Attiva <strong>Mostra logo nel footer</strong> e salva: senza questa spunta l&apos;immagine caricata non viene mostrata.
                                                </div>
                                                <div class="form-group" id="footer_layout_logo_upload_wrap">
                                                    <label>Immagine logo footer (opzionale)</label>
                                                    <input type="hidden" name="footer_layout_logo_image" id="footer_layout_logo_image" value="<?php echo manager_safe_html($flLogoImage); ?>">
                                                    <div class="custom-file">
                                                        <input type="file" name="upload_footer_logo_image" id="upload_footer_logo_image" class="custom-file-input preview-input" accept="image/*" data-preview-target="preview_footer_logo_image">
                                                        <label class="custom-file-label" for="upload_footer_logo_image">Scegli immagine...</label>
                                                    </div>
                                                    <small class="form-text text-muted mt-1">Attuale: <span id="current_footer_logo_image"><?php echo manager_safe_html($flLogoImage); ?></span></small>
                                                    <div class="thumb-preview mt-2">
                                                        <img id="preview_footer_logo_image" data-current-src="<?php echo manager_safe_html($footerLogoPreview); ?>" src="<?php echo manager_safe_html($footerLogoPreview); ?>" class="img-thumbnail <?php echo $footerLogoPreview === '' ? 'd-none' : ''; ?>" alt="Anteprima logo footer" loading="lazy">
                                                    </div>
                                                </div>
                                            </div>
                                            <div id="footer-layout-texts-wrap">
                                                <h6 class="text-uppercase text-muted mt-2 mb-2">Testi personalizzati</h6>
                                                <p class="small text-muted">Lascia vuoto per il testo automatico. Segnaposto: {{CONTEST_NAME}}, {{START_DATE}}, {{END_DATE}}, {{MIN_SPEND}}, {{PRIZE_TOTAL}}, {{REGOLAMENTO_LINK}}, {{PRIVACY_LINK}}, {{PROMOTER}}, {{DELEGATE}}</p>
                                                <div class="form-group footer-layout-text-three">
                                                    <label>Titolo concorso (override)</label>
                                                    <input type="text" name="footer_layout_text_contest_title" class="form-control" value="<?php echo manager_safe_html($flTexts['contest_title']); ?>" placeholder="Es. Concorso &quot;{{CONTEST_NAME}}&quot;">
                                                </div>
                                                <div class="form-group footer-layout-text-three footer-layout-text-centered">
                                                    <label>Testo intro / date / montepremi</label>
                                                    <textarea name="footer_layout_text_intro_line" class="form-control" rows="3" placeholder="Testo sotto il titolo (una riga per riga)"><?php echo manager_safe_html($flTexts['intro_line']); ?></textarea>
                                                </div>
                                                <div id="footer-layout-legal-editors" style="<?php echo ($flLegal1On || $flLegal2On) ? '' : 'display:none;'; ?>">
                                                    <p class="small text-muted">Blocchi HTML opzionali. Attivali con le spunte <strong>Testo legale 1 / 2</strong> in Elementi visibili. Se disattivati non compaiono nel footer.</p>
                                                    <div class="form-group footer-legal-editor-wrap" id="footer-legal-editor-wrap-1" data-legal-block="legal_block_1" style="<?php echo $flLegal1On ? '' : 'display:none;'; ?>">
                                                        <label>Testo legale 1 (HTML)</label>
                                                        <textarea name="footer_layout_text_legal_html_1" id="footer_layout_text_legal_html_1" class="form-control footer-legal-editor" rows="4"><?php echo htmlspecialchars($flTexts['legal_html_1'], ENT_QUOTES, 'UTF-8'); ?></textarea>
                                                    </div>
                                                    <div class="form-group footer-legal-editor-wrap" id="footer-legal-editor-wrap-2" data-legal-block="legal_block_2" style="<?php echo $flLegal2On ? '' : 'display:none;'; ?>">
                                                        <label>Testo legale 2 (HTML)</label>
                                                        <textarea name="footer_layout_text_legal_html_2" id="footer_layout_text_legal_html_2" class="form-control footer-legal-editor" rows="4"><?php echo htmlspecialchars($flTexts['legal_html_2'], ENT_QUOTES, 'UTF-8'); ?></textarea>
                                                    </div>
                                                </div>
                                                <div class="form-row">
                                                    <div class="form-group col-md-3">
                                                        <label>Etichetta regolamento</label>
                                                        <input type="text" name="footer_layout_text_link_regolamento_label" class="form-control" value="<?php echo manager_safe_html($flTexts['link_regolamento_label']); ?>">
                                                    </div>
                                                    <div class="form-group col-md-3">
                                                        <label>Etichetta privacy</label>
                                                        <input type="text" name="footer_layout_text_link_privacy_label" class="form-control" value="<?php echo manager_safe_html($flTexts['link_privacy_label']); ?>">
                                                    </div>
                                                    <div class="form-group col-md-3" id="footer-layout-cookie-policy-label-wrap"<?php echo empty(($content_current['cookie_consent'] ?? [])['enabled']) ? ' style="display:none;"' : ''; ?>>
                                                        <label>Etichetta Cookie Policy</label>
                                                        <input type="text" name="footer_layout_text_link_cookie_policy_label" class="form-control" value="<?php echo manager_safe_html($flTexts['link_cookie_policy_label'] ?? 'Cookie Policy'); ?>">
                                                    </div>
                                                    <div class="form-group col-md-3">
                                                        <label>Etichetta assistenza</label>
                                                        <input type="text" name="footer_layout_text_link_assistenza_label" class="form-control" value="<?php echo manager_safe_html($flTexts['link_assistenza_label']); ?>">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mt-3" id="footer-layout-preview-wrap">
                                        <div class="d-flex justify-content-between align-items-center flex-wrap mb-2">
                                            <strong class="small text-uppercase text-muted">Anteprima footer</strong>
                                            <span class="small text-muted">Aggiornamento automatico</span>
                                        </div>
                                        <div class="footer-layout-preview-frame border rounded overflow-hidden bg-white">
                                            <iframe id="footer-layout-preview-iframe" title="Anteprima footer" src="<?= htmlspecialchars($footerPreviewUrl ?? 'ajax/preview_footer.php?live=1', ENT_QUOTES, 'UTF-8') ?>" style="width:100%;height:260px;border:none;display:block;"></iframe>
                                        </div>
                                    </div>
                                    </div>
                                </div>
                            </div>
