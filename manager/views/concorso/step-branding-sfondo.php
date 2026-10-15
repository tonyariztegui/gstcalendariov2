<div class="card mt-3 mb-4 config-anchor config-step branding-sfondo-section branding-section" data-step="3" id="section-branding">
                                <div class="card-body">
                                    <div class="branding-sfondo-hero">
                                        <div>
                                            <div class="section-title branding-sfondo-title"><i class="fa fa-image mr-2"></i>Branding</div>
                                            <p class="text-muted small mb-0">Gestisci logo, favicon e banner email usati nelle pagine pubbliche e nelle comunicazioni.</p>
                                        </div>
                                        <span class="branding-sfondo-badge"><i class="fa fa-picture-o mr-1"></i> Asset brand</span>
                                    </div>
                                    <div class="config-section mb-3 branding-assets-panel branding-sfondo-panel">
                                        <?php
                                            $logoFallback = (string) ($content_defaults['logo'] ?? 'assets/img/default_img/logo-generic.png');
                                            $logoMobileFallback = (string) ($content_defaults['logo_mobile'] ?? $logoFallback);
                                            $headerImageFallback = (string) ($content_defaults['header_image'] ?? '');
                                            $headerImageMobileFallback = (string) ($content_defaults['header_image_mobile'] ?? '');
                                            $faviconFallback = (string) ($content_defaults['favicon'] ?? 'assets/img/default_img/favicon.png');
                                            $faviconMobileFallback = (string) ($content_defaults['favicon_mobile'] ?? $faviconFallback);
                                            $mailBannerFallback = (string) ($content_defaults['mail_banner'] ?? 'assets/img/default_img/banner.png');
                                            $logoCurrentPath = trim((string) ($content_current['logo'] ?? ''));
                                            if ($logoCurrentPath === '') {
                                                $logoCurrentPath = $logoFallback;
                                            }
                                            $logoMobileCurrentPath = trim((string) ($content_current['logo_mobile'] ?? ''));
                                            if ($logoMobileCurrentPath === '') {
                                                $logoMobileCurrentPath = $logoMobileFallback;
                                            }
                                            $headerImageCurrentPath = trim((string) ($content_current['header_image'] ?? ''));
                                            if ($headerImageCurrentPath === '') {
                                                $headerImageCurrentPath = $headerImageFallback;
                                            }
                                            $headerImageMobileCurrentPath = trim((string) ($content_current['header_image_mobile'] ?? ''));
                                            if ($headerImageMobileCurrentPath === '') {
                                                $headerImageMobileCurrentPath = $headerImageMobileFallback;
                                            }
                                            $faviconCurrentPath = trim((string) ($content_current['favicon'] ?? ''));
                                            if ($faviconCurrentPath === '') {
                                                $faviconCurrentPath = $faviconFallback;
                                            }
                                            $faviconMobileCurrentPath = trim((string) ($content_current['favicon_mobile'] ?? ''));
                                            if ($faviconMobileCurrentPath === '') {
                                                $faviconMobileCurrentPath = $faviconMobileFallback;
                                            }
                                            $mailBannerCurrentPath = trim((string) ($content_current['mail_banner'] ?? ''));
                                            if ($mailBannerCurrentPath === '') {
                                                $mailBannerCurrentPath = $mailBannerFallback;
                                            }
                                            $logoDisplayMode = strtolower(trim((string)($content_current['logo_display_mode'] ?? '')));
                                            if (!in_array($logoDisplayMode, ['none', 'logo', 'header', 'both'], true)) {
                                                $logoEnabledFallback = array_key_exists('logo_enabled', $content_current)
                                                    ? filter_var($content_current['logo_enabled'], FILTER_VALIDATE_BOOLEAN)
                                                    : (bool)($content_defaults['logo_enabled'] ?? true);
                                                $logoDisplayMode = $logoEnabledFallback ? 'logo' : 'none';
                                            }
                                            $showLogoOptions = in_array($logoDisplayMode, ['logo', 'both'], true);
                                            $showHeaderOptions = in_array($logoDisplayMode, ['header', 'both'], true);
                                        ?>
                                        <div class="branding-sfondo-panel-header">
                                            <div>
                                                <h5>Asset pubblici</h5>
                                                <p>Gestisci intestazione, logo, icone browser e banner delle email automatiche.</p>
                                            </div>
                                            <span><i class="fa fa-cloud-upload mr-1"></i> Upload immagini</span>
                                        </div>
                                        <div class="form-row">
                                            <div class="form-group col-md-6">
                                                <label>Visualizzazione header</label>
                                                <select name="logo_display_mode" id="logo_display_mode" class="form-control">
                                                    <option value="none" <?php echo $logoDisplayMode === 'none' ? 'selected' : ''; ?>>Nessuno</option>
                                                    <option value="logo" <?php echo $logoDisplayMode === 'logo' ? 'selected' : ''; ?>>Logo</option>
                                                    <option value="header" <?php echo $logoDisplayMode === 'header' ? 'selected' : ''; ?>>Header</option>
                                                    <option value="both" <?php echo $logoDisplayMode === 'both' ? 'selected' : ''; ?>>Logo + Header</option>
                                                </select>
                                            </div>
                                            <div class="form-group col-md-6 brand-header-options<?php echo $showHeaderOptions ? '' : ' d-none'; ?>">
                                                <label>Header desktop (consigliato 1920×280) <span class="text-muted ml-1" data-toggle="tooltip" title="Bandiera panoramica per tablet e desktop. Su mobile si usa la versione dedicata se caricata."><i class="fa fa-question-circle"></i></span></label>
                                                <input type="hidden" name="header_image" value="<?php echo manager_safe_html($headerImageCurrentPath); ?>" data-fallback="<?php echo manager_safe_html($headerImageFallback); ?>">
                                                <div class="custom-file">
                                                    <input type="file" name="upload_header_image" id="upload_header_image" class="custom-file-input preview-input" accept="image/*" data-preview-target="preview_header_image">
                                                    <label class="custom-file-label" for="upload_header_image">Scegli header desktop...</label>
                                                </div>
                                                <small class="form-text text-muted mt-1">Attuale: <span id="current_header_image" data-default="<?php echo manager_safe_html($headerImageCurrentPath); ?>" data-fallback="<?php echo manager_safe_html($headerImageFallback); ?>"><?php echo manager_safe_html($headerImageCurrentPath); ?></span></small>
                                                <div class="thumb-preview mt-2">
                                                    <?php $headerImagePreview = manager_preview_url($headerImageCurrentPath, '', $baseDir); ?>
                                                    <?php $headerImageFallbackPreview = manager_preview_url($headerImageFallback, '', $baseDir); ?>
                                                    <img id="preview_header_image" data-current-src="<?php echo manager_safe_html($headerImagePreview); ?>" data-fallback-src="<?php echo manager_safe_html($headerImageFallbackPreview); ?>" src="<?php echo manager_safe_html($headerImagePreview); ?>" class="img-thumbnail <?php echo $headerImagePreview === '' ? 'd-none' : ''; ?>" alt="Anteprima header desktop" loading="lazy">
                                                </div>
                                            </div>
                                            <div class="form-group col-md-6 brand-header-options<?php echo $showHeaderOptions ? '' : ' d-none'; ?>">
                                                <label>Header mobile (consigliato 1080×480) <span class="text-muted ml-1" data-toggle="tooltip" title="Versione più alta per smartphone. Se vuota, su mobile viene usato l'header desktop (più basso)."><i class="fa fa-question-circle"></i></span></label>
                                                <input type="hidden" name="header_image_mobile" value="<?php echo manager_safe_html($headerImageMobileCurrentPath); ?>" data-fallback="<?php echo manager_safe_html($headerImageMobileFallback); ?>">
                                                <div class="custom-file">
                                                    <input type="file" name="upload_header_image_mobile" id="upload_header_image_mobile" class="custom-file-input preview-input" accept="image/*" data-preview-target="preview_header_image_mobile">
                                                    <label class="custom-file-label" for="upload_header_image_mobile">Scegli header mobile...</label>
                                                </div>
                                                <small class="form-text text-muted mt-1">Attuale: <span id="current_header_image_mobile" data-default="<?php echo manager_safe_html($headerImageMobileCurrentPath); ?>" data-fallback="<?php echo manager_safe_html($headerImageMobileFallback); ?>"><?php echo manager_safe_html($headerImageMobileCurrentPath); ?></span></small>
                                                <div class="thumb-preview mt-2">
                                                    <?php $headerImageMobilePreview = manager_preview_url($headerImageMobileCurrentPath, '', $baseDir); ?>
                                                    <?php $headerImageMobileFallbackPreview = manager_preview_url($headerImageMobileFallback, '', $baseDir); ?>
                                                    <img id="preview_header_image_mobile" data-current-src="<?php echo manager_safe_html($headerImageMobilePreview); ?>" data-fallback-src="<?php echo manager_safe_html($headerImageMobileFallbackPreview); ?>" src="<?php echo manager_safe_html($headerImageMobilePreview); ?>" class="img-thumbnail <?php echo $headerImageMobilePreview === '' ? 'd-none' : ''; ?>" alt="Anteprima header mobile" loading="lazy">
                                                </div>
                                            </div>
                                            <div class="form-group col-md-6 brand-logo-options<?php echo $showLogoOptions ? '' : ' d-none'; ?>">
                                                <label>Logo (consigliato 800x400) <span class="text-muted ml-1" data-toggle="tooltip" title="Immagine principale del concorso: appare su landing, email e nell'area manager."><i class="fa fa-question-circle"></i></span></label>
                                                <input type="hidden" name="logo" value="<?php echo manager_safe_html($logoCurrentPath); ?>" data-fallback="<?php echo manager_safe_html($logoFallback); ?>">
                                                <div class="custom-file">
                                                    <input type="file" name="upload_logo" id="upload_logo" class="custom-file-input preview-input" accept="image/*" data-preview-target="preview_logo">
                                                    <label class="custom-file-label" for="upload_logo">Scegli logo...</label>
                                                </div>
                                                <small class="form-text text-muted mt-1">Attuale: <span id="current_logo" data-default="<?php echo manager_safe_html($logoCurrentPath); ?>" data-fallback="<?php echo manager_safe_html($logoFallback); ?>"><?php echo manager_safe_html($logoCurrentPath); ?></span></small>
                                                <div class="thumb-preview mt-2">
                                                    <?php $logoPreview = manager_preview_url($logoCurrentPath, '', $baseDir); ?>
                                                    <?php $logoFallbackPreview = manager_preview_url($logoFallback, '', $baseDir); ?>
                                                    <img id="preview_logo" data-current-src="<?php echo manager_safe_html($logoPreview); ?>" data-fallback-src="<?php echo manager_safe_html($logoFallbackPreview); ?>" src="<?php echo manager_safe_html($logoPreview); ?>" class="img-thumbnail <?php echo $logoPreview === '' ? 'd-none' : ''; ?>" alt="Anteprima logo" loading="lazy">
                                                </div>
                                            </div>
                                            <div class="form-group col-md-6 brand-logo-options<?php echo $showLogoOptions ? '' : ' d-none'; ?>">
                                                <label>Logo mobile (consigliato 480x240) <span class="text-muted ml-1" data-toggle="tooltip" title="Versione ottimizzata per mobile (dimensioni ridotte). Se vuoto usa il logo principale."><i class="fa fa-question-circle"></i></span></label>
                                                <input type="hidden" name="logo_mobile" value="<?php echo manager_safe_html($logoMobileCurrentPath); ?>" data-fallback="<?php echo manager_safe_html($logoMobileFallback); ?>">
                                                <div class="custom-file">
                                                    <input type="file" name="upload_logo_mobile" id="upload_logo_mobile" class="custom-file-input preview-input" accept="image/*" data-preview-target="preview_logo_mobile">
                                                    <label class="custom-file-label" for="upload_logo_mobile">Scegli logo mobile...</label>
                                                </div>
                                                <small class="form-text text-muted mt-1">Attuale: <span id="current_logo_mobile" data-default="<?php echo manager_safe_html($logoMobileCurrentPath); ?>" data-fallback="<?php echo manager_safe_html($logoMobileFallback); ?>"><?php echo manager_safe_html($logoMobileCurrentPath); ?></span></small>
                                                <div class="thumb-preview mt-2">
                                                    <?php $logoMobilePreview = manager_preview_url($logoMobileCurrentPath, '', $baseDir); ?>
                                                    <?php $logoMobileFallbackPreview = manager_preview_url($logoMobileFallback, '', $baseDir); ?>
                                                    <img id="preview_logo_mobile" data-current-src="<?php echo manager_safe_html($logoMobilePreview); ?>" data-fallback-src="<?php echo manager_safe_html($logoMobileFallbackPreview); ?>" src="<?php echo manager_safe_html($logoMobilePreview); ?>" class="img-thumbnail <?php echo $logoMobilePreview === '' ? 'd-none' : ''; ?>" alt="Anteprima logo mobile" loading="lazy">
                                                </div>
                                            </div>
                                            <?php
                                                $logoDesktopSize = isset($content_current['logo_desktop_size']) ? trim((string) $content_current['logo_desktop_size']) : '';
                                                if (!in_array($logoDesktopSize, ['compact', 'medium', 'large', 'custom'], true)) {
                                                    $logoDesktopSize = (string) ($content_defaults['logo_desktop_size'] ?? 'medium');
                                                }
                                                $logoDesktopWidthPct = (int) ($content_current['logo_desktop_width_percent'] ?? ($content_defaults['logo_desktop_width_percent'] ?? 85));
                                                $logoDesktopWidthPct = max(40, min(100, $logoDesktopWidthPct));
                                                $logoDesktopMaxHeightPx = (int) ($content_current['logo_desktop_max_height_px'] ?? ($content_defaults['logo_desktop_max_height_px'] ?? 0));
                                                $logoDesktopMaxHeightPx = max(0, min(500, $logoDesktopMaxHeightPx));
                                                $logoDesktopCustomPanelClass = $logoDesktopSize === 'custom' ? '' : ' d-none';
                                            ?>
                                            <?php $ldTag = 'd' . 'iv'; ?>
                                            <<?php echo $ldTag; ?> class="form-group col-md-6 brand-logo-options<?php echo $showLogoOptions ? '' : ' d-none'; ?>">
                                                <label>Dimensione logo (desktop) <span class="text-muted ml-1" data-toggle="tooltip" title="Riduce il logo su schermi larghi (tablet/desktop). Il logo mobile non cambia."><i class="fa fa-question-circle"></i></span></label>
                                                <select name="logo_desktop_size" id="logo_desktop_size" class="form-control">
                                                    <option value="compact" <?php echo $logoDesktopSize === 'compact' ? 'selected' : ''; ?>>Compatto (~62% larghezza)</option>
                                                    <option value="medium" <?php echo $logoDesktopSize === 'medium' ? 'selected' : ''; ?>>Medio (~78% larghezza)</option>
                                                    <option value="large" <?php echo $logoDesktopSize === 'large' ? 'selected' : ''; ?>>Grande (larghezza piena, come prima)</option>
                                                    <option value="custom" <?php echo $logoDesktopSize === 'custom' ? 'selected' : ''; ?>>Personalizzato</option>
                                                </select>
                                                <small class="form-text text-muted">Solo da 768px in su. Per logo panoramici (es. 1600×800) usare Personalizzato.</small>
                                            </<?php echo $ldTag; ?>>
                                            <<?php echo $ldTag; ?> class="form-group col-md-12 brand-logo-options logo-desktop-custom-panel<?php echo $showLogoOptions ? $logoDesktopCustomPanelClass : ' d-none'; ?>" id="logo_desktop_custom_panel">
                                                <div class="form-row">
                                                    <<?php echo $ldTag; ?> class="form-group col-md-6 mb-md-0">
                                                        <label>Larghezza logo (%)</label>
                                                        <input type="number" name="logo_desktop_width_percent" id="logo_desktop_width_percent" class="form-control" min="40" max="100" step="1" value="<?php echo (int) $logoDesktopWidthPct; ?>">
                                                        <small class="form-text text-muted">Percentuale rispetto al form (40–100). Es. 90 per un logo quasi a tutta larghezza.</small>
                                                    </<?php echo $ldTag; ?>>
                                                    <<?php echo $ldTag; ?> class="form-group col-md-6 mb-0">
                                                        <label>Altezza massima (px)</label>
                                                        <input type="number" name="logo_desktop_max_height_px" id="logo_desktop_max_height_px" class="form-control" min="0" max="500" step="1" value="<?php echo (int) $logoDesktopMaxHeightPx; ?>">
                                                        <small class="form-text text-muted">0 = nessun limite di altezza (consigliato per logo 2:1). Es. 180 per limitare l’ingombro verticale.</small>
                                                    </<?php echo $ldTag; ?>>
                                                </div>
                                            </<?php echo $ldTag; ?>>
                                            <?php
                                                $brandLayoutPages = brand_layout_page_definitions();
                                                $brandLayoutResolved = normalize_brand_layout_collection(
                                                    $content_current['brand_page_layout'] ?? [],
                                                    is_array($content_defaults['brand_page_layout'] ?? null) ? $content_defaults['brand_page_layout'] : []
                                                );
                                                $logoTopSpacing = normalize_logo_top_spacing_config(
                                                    $content_current['logo_top_spacing'] ?? [],
                                                    is_array($content_defaults['logo_top_spacing'] ?? null) ? $content_defaults['logo_top_spacing'] : []
                                                );
                                                $headerLogoGap = normalize_brand_header_logo_gap_config(
                                                    $content_current['brand_header_logo_gap'] ?? [],
                                                    is_array($content_defaults['brand_header_logo_gap'] ?? null) ? $content_defaults['brand_header_logo_gap'] : []
                                                );
                                                $headerTopSpacing = normalize_brand_header_top_spacing_config(
                                                    $content_current['brand_header_top_spacing'] ?? [],
                                                    is_array($content_defaults['brand_header_top_spacing'] ?? null) ? $content_defaults['brand_header_top_spacing'] : []
                                                );
                                                $showBothBrandOptions = $logoDisplayMode === 'both';
                                                $showLogoOnlySpacing = $logoDisplayMode === 'logo';
                                                $showHeaderTopSpacing = in_array($logoDisplayMode, ['header', 'both'], true);
                                                $showLayoutPanels = $logoDisplayMode !== 'none';
                                                $brandLayoutActivePage = 'registration';
                                            ?>
                                            <div class="form-group col-12 brand-layout-overlap-panel<?php echo $showLayoutPanels ? '' : ' d-none'; ?>">
                                                <div class="branding-sfondo-panel mt-2 brand-layout-manager-panel">
                                                    <div class="branding-sfondo-panel-header">
                                                        <div>
                                                            <h5>Sovrapposizione container pagina</h5>
                                                            <p>Il logo o header restano fissi in alto. Qui regoli quanto ogni container pagina sale sotto il visual.</p>
                                                        </div>
                                                        <button type="button" class="btn btn-sm btn-outline-primary brand-layout-collapse-toggle" data-target="#brand-layout-collapse-body" aria-expanded="false">
                                                            <i class="fa fa-sliders mr-1"></i> Layout pagina (avanzato)
                                                        </button>
                                                    </div>
                                                    <div id="brand-layout-collapse-body" class="brand-layout-collapse-body d-none">
                                                    <div class="form-row align-items-end">
                                                        <div class="form-group col-md-6 mb-2">
                                                            <label for="brand_layout_page_selector">Pagina da configurare</label>
                                                            <select id="brand_layout_page_selector" class="form-control">
                                                                <?php foreach ($brandLayoutPages as $pageKey => $pageLabel): ?>
                                                                    <option value="<?php echo manager_safe_html($pageKey); ?>" <?php echo $brandLayoutActivePage === $pageKey ? 'selected' : ''; ?>><?php echo manager_safe_html($pageLabel); ?></option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                        </div>
                                                        <div class="form-group col-md-6 mb-2">
                                                            <div class="alert alert-light border small mb-0 brand-layout-helper-copy">
                                                                Valori negativi: il container sale dietro logo/header. Valori positivi: più spazio tra visual e container.
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <?php foreach ($brandLayoutPages as $pageKey => $pageLabel): ?>
                                                        <?php
                                                            $pageLayout = $brandLayoutResolved[$pageKey] ?? brand_layout_default_item();
                                                            $pagePreset = (string) ($pageLayout['preset'] ?? 'attached');
                                                            $pageCustomize = !empty($pageLayout['customize_per_size']);
                                                            $pageMobile = (int) ($pageLayout['mobile']['overlap_px'] ?? 0);
                                                            $pageTablet = (int) ($pageLayout['tablet']['overlap_px'] ?? 0);
                                                            $pageDesktop = (int) ($pageLayout['desktop']['overlap_px'] ?? 0);
                                                        ?>
                                                        <div class="brand-layout-page-panel<?php echo $brandLayoutActivePage === $pageKey ? ' is-active' : ' d-none'; ?>" data-brand-page="<?php echo manager_safe_html($pageKey); ?>" data-brand-page-label="<?php echo manager_safe_html($pageLabel); ?>">
                                                            <div class="form-row">
                                                                <div class="form-group col-md-6">
                                                                    <label>Preset</label>
                                                                    <select name="brand_page_layout[<?php echo manager_safe_html($pageKey); ?>][preset]" class="form-control brand-page-layout-preset">
                                                                        <option value="attached" <?php echo $pagePreset === 'attached' ? 'selected' : ''; ?>>Attaccato (default)</option>
                                                                        <option value="overlap_light" <?php echo $pagePreset === 'overlap_light' ? 'selected' : ''; ?>>Sovrapposizione leggera</option>
                                                                        <option value="overlap_strong" <?php echo $pagePreset === 'overlap_strong' ? 'selected' : ''; ?>>Sovrapposizione forte</option>
                                                                        <option value="separated" <?php echo $pagePreset === 'separated' ? 'selected' : ''; ?>>Separato</option>
                                                                        <option value="custom" <?php echo $pagePreset === 'custom' ? 'selected' : ''; ?>>Personalizzato</option>
                                                                    </select>
                                                                </div>
                                                                <div class="form-group col-md-6 d-flex align-items-end">
                                                                    <div class="form-check mb-2">
                                                                        <input class="form-check-input brand-page-layout-customize" type="checkbox" name="brand_page_layout[<?php echo manager_safe_html($pageKey); ?>][customize_per_size]" value="1" <?php echo $pageCustomize ? 'checked' : ''; ?>>
                                                                        <label class="form-check-label">Valori diversi per mobile / tablet / desktop</label>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="form-row">
                                                                <div class="form-group col-md-4">
                                                                    <label>Mobile <span class="brand-layout-value" data-for="brand_page_layout_<?php echo manager_safe_html($pageKey); ?>_mobile"><?php echo $pageMobile; ?>px</span></label>
                                                                    <input type="range" class="form-control-range brand-page-layout-slider" data-device="mobile" data-page="<?php echo manager_safe_html($pageKey); ?>" name="brand_page_layout[<?php echo manager_safe_html($pageKey); ?>][mobile][overlap_px]" id="brand_page_layout_<?php echo manager_safe_html($pageKey); ?>_mobile" min="-120" max="120" step="4" value="<?php echo $pageMobile; ?>">
                                                                </div>
                                                                <div class="form-group col-md-4 brand-page-layout-device-field<?php echo $pageCustomize ? '' : ' d-none'; ?>">
                                                                    <label>Tablet <span class="brand-layout-value" data-for="brand_page_layout_<?php echo manager_safe_html($pageKey); ?>_tablet"><?php echo $pageTablet; ?>px</span></label>
                                                                    <input type="range" class="form-control-range brand-page-layout-slider" data-device="tablet" data-page="<?php echo manager_safe_html($pageKey); ?>" name="brand_page_layout[<?php echo manager_safe_html($pageKey); ?>][tablet][overlap_px]" id="brand_page_layout_<?php echo manager_safe_html($pageKey); ?>_tablet" min="-80" max="120" step="4" value="<?php echo $pageTablet; ?>">
                                                                </div>
                                                                <div class="form-group col-md-4 brand-page-layout-device-field<?php echo $pageCustomize ? '' : ' d-none'; ?>">
                                                                    <label>Desktop <span class="brand-layout-value" data-for="brand_page_layout_<?php echo manager_safe_html($pageKey); ?>_desktop"><?php echo $pageDesktop; ?>px</span></label>
                                                                    <input type="range" class="form-control-range brand-page-layout-slider" data-device="desktop" data-page="<?php echo manager_safe_html($pageKey); ?>" name="brand_page_layout[<?php echo manager_safe_html($pageKey); ?>][desktop][overlap_px]" id="brand_page_layout_<?php echo manager_safe_html($pageKey); ?>_desktop" min="-120" max="120" step="4" value="<?php echo $pageDesktop; ?>">
                                                                </div>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>

                                                    <div class="brand-layout-preview-wrap mt-3" id="brand-layout-preview-wrap">
                                                        <div class="alert alert-warning border small py-2 px-3 mb-2 brand-layout-preview-unsaved-banner" role="status">
                                                            <i class="fa fa-info-circle mr-1"></i>
                                                            Anteprima locale — i valori degli slider si vedono subito qui; salva il concorso per applicarli sul sito pubblico.
                                                        </div>
                                                        <div class="d-flex flex-wrap align-items-center justify-content-between mb-2">
                                                            <div>
                                                                <strong class="d-block">Anteprima layout</strong>
                                                                <span class="text-muted small" id="brand-layout-preview-overlap-label">Registration - Mobile: 0px</span>
                                                            </div>
                                                            <div class="btn-group btn-group-sm brand-layout-preview-devices" role="group" aria-label="Device anteprima">
                                                                <button type="button" class="btn btn-outline-primary brand-layout-preview-device active" data-device="mobile">Mobile</button>
                                                                <button type="button" class="btn btn-outline-primary brand-layout-preview-device" data-device="tablet">Tablet</button>
                                                                <button type="button" class="btn btn-outline-primary brand-layout-preview-device" data-device="desktop">Desktop</button>
                                                            </div>
                                                        </div>
                                                        <div id="brand-layout-preview-iframe-wrap" class="brand-layout-preview-iframe-wrap is-mobile">
                                                            <iframe id="brand-layout-preview-iframe"
                                                                title="Anteprima layout brand"
                                                                src="ajax/preview_brand_layout.php?live=1&amp;page=registration&amp;device=mobile"
                                                                class="brand-layout-preview-iframe"></iframe>
                                                        </div>
                                                        <p class="small text-muted mb-0 mt-2">Anteprima fedele alla pagina pubblica (logo, sfondo, container). Valori negativi = sovrapposizione; positivi = spazio extra. Salva per applicare sul sito.</p>
                                                    </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="form-group col-12 brand-logo-only-spacing<?php echo $showLogoOnlySpacing ? '' : ' d-none'; ?>">
                                                <div class="branding-sfondo-panel brand-layout-logo-spacing-panel">
                                                    <div class="branding-sfondo-panel-header">
                                                        <div>
                                                            <h5>Spazio superiore logo</h5>
                                                            <p>Usato soprattutto quando scegli solo il logo senza header. 0px = logo attaccato in alto.</p>
                                                        </div>
                                                        <span><i class="fa fa-sort-amount-asc mr-1"></i> Logo solo</span>
                                                    </div>
                                                    <div class="form-row">
                                                        <div class="form-group col-md-4">
                                                            <label>Mobile <span class="brand-layout-value" data-for="logo_top_spacing_mobile_px"><?php echo (int) $logoTopSpacing['mobile_px']; ?>px</span></label>
                                                            <input type="range" class="form-control-range logo-top-spacing-slider" name="logo_top_spacing_mobile_px" id="logo_top_spacing_mobile_px" min="0" max="120" step="4" value="<?php echo (int) $logoTopSpacing['mobile_px']; ?>">
                                                        </div>
                                                        <div class="form-group col-md-4">
                                                            <label>Tablet <span class="brand-layout-value" data-for="logo_top_spacing_tablet_px"><?php echo (int) $logoTopSpacing['tablet_px']; ?>px</span></label>
                                                            <input type="range" class="form-control-range logo-top-spacing-slider" name="logo_top_spacing_tablet_px" id="logo_top_spacing_tablet_px" min="0" max="120" step="4" value="<?php echo (int) $logoTopSpacing['tablet_px']; ?>">
                                                        </div>
                                                        <div class="form-group col-md-4">
                                                            <label>Desktop <span class="brand-layout-value" data-for="logo_top_spacing_desktop_px"><?php echo (int) $logoTopSpacing['desktop_px']; ?>px</span></label>
                                                            <input type="range" class="form-control-range logo-top-spacing-slider" name="logo_top_spacing_desktop_px" id="logo_top_spacing_desktop_px" min="0" max="120" step="4" value="<?php echo (int) $logoTopSpacing['desktop_px']; ?>">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="form-group col-12 brand-header-top-spacing-panel<?php echo $showHeaderTopSpacing ? '' : ' d-none'; ?>">
                                                <div class="branding-sfondo-panel brand-layout-header-top-spacing-panel-inner">
                                                    <div class="branding-sfondo-panel-header">
                                                        <div>
                                                            <h5>Spazio superiore header</h5>
                                                            <p>Usato quando l'header è visibile (Header solo o Logo + Header). 0px = header attaccato in alto.</p>
                                                        </div>
                                                        <span><i class="fa fa-sort-amount-asc mr-1"></i> Header</span>
                                                    </div>
                                                    <div class="form-row">
                                                        <div class="form-group col-md-4">
                                                            <label>Mobile <span class="brand-layout-value" data-for="brand_header_top_spacing_mobile_px"><?php echo (int) $headerTopSpacing['mobile_px']; ?>px</span></label>
                                                            <input type="range" class="form-control-range brand-header-top-spacing-slider" name="brand_header_top_spacing_mobile_px" id="brand_header_top_spacing_mobile_px" min="0" max="120" step="4" value="<?php echo (int) $headerTopSpacing['mobile_px']; ?>">
                                                        </div>
                                                        <div class="form-group col-md-4">
                                                            <label>Tablet <span class="brand-layout-value" data-for="brand_header_top_spacing_tablet_px"><?php echo (int) $headerTopSpacing['tablet_px']; ?>px</span></label>
                                                            <input type="range" class="form-control-range brand-header-top-spacing-slider" name="brand_header_top_spacing_tablet_px" id="brand_header_top_spacing_tablet_px" min="0" max="120" step="4" value="<?php echo (int) $headerTopSpacing['tablet_px']; ?>">
                                                        </div>
                                                        <div class="form-group col-md-4">
                                                            <label>Desktop <span class="brand-layout-value" data-for="brand_header_top_spacing_desktop_px"><?php echo (int) $headerTopSpacing['desktop_px']; ?>px</span></label>
                                                            <input type="range" class="form-control-range brand-header-top-spacing-slider" name="brand_header_top_spacing_desktop_px" id="brand_header_top_spacing_desktop_px" min="0" max="120" step="4" value="<?php echo (int) $headerTopSpacing['desktop_px']; ?>">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="form-group col-12 brand-header-logo-gap-panel<?php echo $showBothBrandOptions ? '' : ' d-none'; ?>">
                                                <div class="branding-sfondo-panel brand-layout-header-gap-panel">
                                                    <div class="branding-sfondo-panel-header">
                                                        <div>
                                                            <h5>Spazio tra header e logo</h5>
                                                            <p>Usato in modalità Logo + Header. 0px = nessuno spazio tra i due elementi.</p>
                                                        </div>
                                                        <span><i class="fa fa-arrows-v mr-1"></i> Logo + Header</span>
                                                    </div>
                                                    <div class="form-row">
                                                        <div class="form-group col-md-4">
                                                            <label>Mobile <span class="brand-layout-value" data-for="brand_header_logo_gap_mobile_px"><?php echo (int) $headerLogoGap['mobile_px']; ?>px</span></label>
                                                            <input type="range" class="form-control-range brand-header-logo-gap-slider" name="brand_header_logo_gap_mobile_px" id="brand_header_logo_gap_mobile_px" min="0" max="48" step="4" value="<?php echo (int) $headerLogoGap['mobile_px']; ?>">
                                                        </div>
                                                        <div class="form-group col-md-4">
                                                            <label>Tablet <span class="brand-layout-value" data-for="brand_header_logo_gap_tablet_px"><?php echo (int) $headerLogoGap['tablet_px']; ?>px</span></label>
                                                            <input type="range" class="form-control-range brand-header-logo-gap-slider" name="brand_header_logo_gap_tablet_px" id="brand_header_logo_gap_tablet_px" min="0" max="48" step="4" value="<?php echo (int) $headerLogoGap['tablet_px']; ?>">
                                                        </div>
                                                        <div class="form-group col-md-4">
                                                            <label>Desktop <span class="brand-layout-value" data-for="brand_header_logo_gap_desktop_px"><?php echo (int) $headerLogoGap['desktop_px']; ?>px</span></label>
                                                            <input type="range" class="form-control-range brand-header-logo-gap-slider" name="brand_header_logo_gap_desktop_px" id="brand_header_logo_gap_desktop_px" min="0" max="48" step="4" value="<?php echo (int) $headerLogoGap['desktop_px']; ?>">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="form-group col-md-6">
                                                <label>Favicon (32x32) <span class="text-muted ml-1" data-toggle="tooltip" title="Icona del sito (favicon) usata nel browser e nei tab. Preferisci formati PNG/ICO quadrati."><i class="fa fa-question-circle"></i></span></label>
                                                <input type="hidden" name="favicon" value="<?php echo manager_safe_html($faviconCurrentPath); ?>" data-fallback="<?php echo manager_safe_html($faviconFallback); ?>">
                                                <div class="custom-file">
                                                    <input type="file" name="upload_favicon" id="upload_favicon" class="custom-file-input preview-input" accept="image/*" data-preview-target="preview_favicon">
                                                    <label class="custom-file-label" for="upload_favicon">Scegli favicon...</label>
                                                </div>
                                                <small class="form-text text-muted mt-1">Attuale: <span id="current_favicon" data-default="<?php echo manager_safe_html($faviconCurrentPath); ?>" data-fallback="<?php echo manager_safe_html($faviconFallback); ?>"><?php echo manager_safe_html($faviconCurrentPath); ?></span></small>
                                                <div class="thumb-preview mt-2">
                                                    <?php $faviconPreview = manager_preview_url($faviconCurrentPath, '', $baseDir); ?>
                                                    <?php $faviconFallbackPreview = manager_preview_url($faviconFallback, '', $baseDir); ?>
                                                    <img id="preview_favicon" data-current-src="<?php echo manager_safe_html($faviconPreview); ?>" data-fallback-src="<?php echo manager_safe_html($faviconFallbackPreview); ?>" src="<?php echo manager_safe_html($faviconPreview); ?>" class="img-thumbnail <?php echo $faviconPreview === '' ? 'd-none' : ''; ?>" alt="Anteprima favicon" loading="lazy">
                                                </div>
                                            </div>
                                            <div class="form-group col-md-6">
                                                <label>Favicon mobile (180x180) <span class="text-muted ml-1" data-toggle="tooltip" title="Icona per dispositivi mobile (apple-touch). Se vuota usa la favicon standard."><i class="fa fa-question-circle"></i></span></label>
                                                <input type="hidden" name="favicon_mobile" value="<?php echo manager_safe_html($faviconMobileCurrentPath); ?>" data-fallback="<?php echo manager_safe_html($faviconMobileFallback); ?>">
                                                <div class="custom-file">
                                                    <input type="file" name="upload_favicon_mobile" id="upload_favicon_mobile" class="custom-file-input preview-input" accept="image/*" data-preview-target="preview_favicon_mobile">
                                                    <label class="custom-file-label" for="upload_favicon_mobile">Scegli favicon mobile...</label>
                                                </div>
                                                <small class="form-text text-muted mt-1">Attuale: <span id="current_favicon_mobile" data-default="<?php echo manager_safe_html($faviconMobileCurrentPath); ?>" data-fallback="<?php echo manager_safe_html($faviconMobileFallback); ?>"><?php echo manager_safe_html($faviconMobileCurrentPath); ?></span></small>
                                            <div class="thumb-preview mt-2">
                                                <?php $faviconMobilePreview = manager_preview_url($faviconMobileCurrentPath, '', $baseDir); ?>
                                                <?php $faviconMobileFallbackPreview = manager_preview_url($faviconMobileFallback, '', $baseDir); ?>
                                                <img id="preview_favicon_mobile" data-current-src="<?php echo manager_safe_html($faviconMobilePreview); ?>" data-fallback-src="<?php echo manager_safe_html($faviconMobileFallbackPreview); ?>" src="<?php echo manager_safe_html($faviconMobilePreview); ?>" class="img-thumbnail <?php echo $faviconMobilePreview === '' ? 'd-none' : ''; ?>" alt="Anteprima favicon mobile" loading="lazy">
                                            </div>
                                        </div>
                                        </div>
                                        <div class="form-row">
                                            <div class="form-group col-md-12">
                                                <label>Banner email (consigliato 600x200) <span class="text-muted ml-1" data-toggle="tooltip" title="Immagine di intestazione nelle email automatiche."><i class="fa fa-question-circle"></i></span></label>
                                                <input type="hidden" name="mail_banner" value="<?php echo manager_safe_html($mailBannerCurrentPath); ?>" data-fallback="<?php echo manager_safe_html($mailBannerFallback); ?>">
                                                <div class="custom-file">
                                                    <input type="file" name="upload_mail_banner" id="upload_mail_banner" class="custom-file-input preview-input" accept="image/*" data-preview-target="preview_mail_banner">
                                                    <label class="custom-file-label" for="upload_mail_banner">Scegli banner...</label>
                                                </div>
                                                <small class="form-text text-muted mt-1">Attuale: <span id="current_mail_banner" data-default="<?php echo manager_safe_html($mailBannerCurrentPath); ?>" data-fallback="<?php echo manager_safe_html($mailBannerFallback); ?>"><?php echo manager_safe_html($mailBannerCurrentPath); ?></span></small>
                                                <div class="mail-banner-preview-box mt-2" style="display:block;width:100%;max-width:600px;padding:0;border:1px solid #e1e5ea;border-radius:6px;overflow:hidden;background:#fff;box-sizing:border-box;">
                                                    <?php $mailBannerPreview = manager_preview_url($mailBannerCurrentPath, '', $baseDir); ?>
                                                    <?php $mailBannerFallbackPreview = manager_preview_url($mailBannerFallback, '', $baseDir); ?>
                                                    <img id="preview_mail_banner" data-current-src="<?php echo manager_safe_html($mailBannerPreview); ?>" data-fallback-src="<?php echo manager_safe_html($mailBannerFallbackPreview); ?>" src="<?php echo manager_safe_html($mailBannerPreview); ?>" class="<?php echo $mailBannerPreview === '' ? 'd-none' : ''; ?>" alt="Anteprima banner email" loading="lazy" style="display:block;width:100%;max-width:100%;height:auto;margin:0;padding:0;border:0;">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>                            <div class="card mt-3 mb-4 config-anchor config-step branding-sfondo-section sfondo-section" data-step="3" id="section-sfondo">
                                <div class="card-body">
                                    <div class="branding-sfondo-hero branding-sfondo-hero--background">
                                        <div>
                                            <div class="section-title branding-sfondo-title"><i class="fa fa-picture-o mr-2"></i>Sfondo</div>
                                            <p class="text-muted small mb-0">Configura colore, immagini responsive e comportamento dello sfondo pubblico.</p>
                                        </div>
                                        <span class="branding-sfondo-badge branding-sfondo-badge--background"><i class="fa fa-object-group mr-1"></i> Sfondo responsive</span>
                                    </div>
                                    <div class="branding-sfondo-panel sfondo-base-panel">
                                        <div class="branding-sfondo-panel-header">
                                            <div>
                                                <h5>Impostazioni base</h5>
                                                <p>Scegli se usare colore o immagine e dove applicare lo sfondo pubblico.</p>
                                            </div>
                                            <span><i class="fa fa-paint-brush mr-1"></i> Base pagina</span>
                                        </div>
                                    <div class="form-row">
                                        <div class="form-group col-md-6">
                                            <label>Modalità sfondo <span class="text-muted ml-1" data-toggle="tooltip" title="Immagine: usa i background per dispositivo. Colore: applica solo il colore di sfondo."><i class="fa fa-question-circle"></i></span></label>
                                            <select name="background_mode" id="background_mode" class="form-control">
                                                <option value="image" <?php echo ($content_current['background_mode'] ?? '') === 'image' ? 'selected' : ''; ?>>Immagine</option>
                                                <option value="color" <?php echo ($content_current['background_mode'] ?? '') === 'color' ? 'selected' : ''; ?>>Colore</option>
                                            </select>
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label>Portata sfondo <span class="text-muted ml-1" data-toggle="tooltip" title="Scegli dove applicare lo sfondo: tutto il body o solo header e main (senza footer)."><i class="fa fa-question-circle"></i></span></label>
                                            <select name="background_scope" id="background_scope" class="form-control">
                                                <option value="full_body" <?php echo ($content_current['background_scope'] ?? 'full_body') === 'full_body' ? 'selected' : ''; ?>>Tutto il body</option>
                                                <option value="header_main" <?php echo ($content_current['background_scope'] ?? 'full_body') === 'header_main' ? 'selected' : ''; ?>>Header + Main (senza footer)</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="bg-mode bg-color-mode">
                                        <div class="form-group">
                                            <label>Colore sfondo <span class="text-muted ml-1" data-toggle="tooltip" title="Colore di sfondo o fallback se la modalità è immagine. Accetta HEX."><i class="fa fa-question-circle"></i></span></label>
                                            <div class="d-flex align-items-center color-row">
                                                <input type="color" class="form-control color-input color-swatch" data-target="background_color_text" value="<?php echo manager_safe_html($content_current['background_color'] ?? '#ffffff'); ?>">
                                                <input type="text" name="background_color" id="background_color_text" class="form-control color-code-input" value="<?php echo manager_safe_html($content_current['background_color'] ?? '#ffffff'); ?>" placeholder="#ffffff">
                                            </div>
                                            <small class="form-text text-muted d-none">Scegli dal picker oppure incolla un codice HEX (es. #0d6efd).</small>
                                        </div>
                                    </div>
                                    </div>
                                    <div class="bg-mode bg-image-mode branding-sfondo-panel">
                                        <div class="branding-sfondo-panel-header">
                                            <div>
                                                <h5>Immagini responsive</h5>
                                                <p>Carica gli sfondi per i diversi dispositivi e rifinisci il comportamento dell'immagine.</p>
                                            </div>
                                            <span><i class="fa fa-expand mr-1"></i> Responsive</span>
                                        </div>
                                        <div class="form-row">
                                            <?php
                                                $bgDefaultFallback = trim((string) ($content_defaults['backgrounds']['default'] ?? ''));
                                                $bgDefaultStored = trim((string) ($content_current['backgrounds']['default'] ?? ''));
                                                $bgDefaultEffective = $bgDefaultStored !== '' ? $bgDefaultStored : $bgDefaultFallback;
                                                $isCustomBg = function(string $path): bool {
                                                    $p = trim($path);
                                                    if ($p === '') {
                                                        return false;
                                                    }
                                                    $p = ltrim($p, '/');
                                                    return strpos($p, 'assets/img/backgrounds/') === 0;
                                                };
                                                $bgDefaultIsCustom = $isCustomBg($bgDefaultStored);
                                                $bgUseCustomDefaultForMissing = $bgDefaultIsCustom;
                                            ?>
                                            <?php foreach (['default','mobile','tablet','laptop','desktop','ultrawide'] as $bk): ?>
                                            <div class="form-group col-md-6">
                                            <?php
                                                $bgHints = [
                                                    'default' => '1920x1080',
                                                    'mobile' => '1080x1920',
                                                    'tablet' => '1280x800',
                                                    'laptop' => '1366x768',
                                                    'desktop' => '1920x1080',
                                                    'ultrawide' => '3440x1440',
                                                ];
                                                $hint = $bgHints[$bk] ?? '';
                                                $bgStored = trim((string) ($content_current['backgrounds'][$bk] ?? ''));
                                                $bgSizeFallback = trim((string) ($content_defaults['backgrounds'][$bk] ?? $bgDefaultFallback));
                                                if ($bk === 'default') {
                                                    $bgEffective = $bgStored !== '' ? $bgStored : $bgDefaultFallback;
                                                } else {
                                                    $bgEffective = $bgStored !== ''
                                                        ? $bgStored
                                                        : ($bgUseCustomDefaultForMissing ? $bgDefaultEffective : $bgSizeFallback);
                                                }
                                                // For default: deleting should restore shipped default image (content.defaults.json)
                                                $bgFallbackValue = ($bk === 'default') ? $bgDefaultFallback : '';
                                                // Per altri formati: il valore salvato resta vuoto (eredità), ma l'UI mostra il fallback effettivo.
                                                $bgFallbackDisplay = ($bk === 'default')
                                                    ? $bgDefaultFallback
                                                    : ($bgUseCustomDefaultForMissing ? $bgDefaultEffective : $bgSizeFallback);
                                            ?>
                                            <label>Background <?php echo manager_safe_html(ucfirst($bk)); ?><?php echo $hint ? ' (consigliato '.$hint.')' : ''; ?> <span class="text-muted ml-1" data-toggle="tooltip" title="Immagine di sfondo per <?php echo manager_safe_html(strtolower($bk)); ?>; se vuota usa il default."><i class="fa fa-question-circle"></i></span></label>
                                                <input
                                                    type="hidden"
                                                    name="backgrounds_<?php echo manager_safe_html($bk); ?>"
                                                    value="<?php echo manager_safe_html($bk === 'default' ? ($bgEffective !== '' ? $bgEffective : $bgDefaultFallback) : $bgStored); ?>"
                                                    data-fallback="<?php echo manager_safe_html($bgFallbackValue); ?>"
                                                    data-fallback-display="<?php echo manager_safe_html($bgFallbackDisplay); ?>"
                                                >
                                                <div class="custom-file">
                                                    <input type="file" name="upload_background_<?php echo manager_safe_html($bk); ?>" id="upload_background_<?php echo manager_safe_html($bk); ?>" class="custom-file-input preview-input" accept="image/*" data-preview-target="preview_background_<?php echo manager_safe_html($bk); ?>">
                                                    <label class="custom-file-label" for="upload_background_<?php echo manager_safe_html($bk); ?>">Scegli immagine...</label>
                                                </div>
                                                <small class="form-text text-muted mt-1">Attuale: <span id="current_background_<?php echo manager_safe_html($bk); ?>" data-default="<?php echo manager_safe_html($bgStored); ?>"><?php echo manager_safe_html($bgEffective); ?></span></small>
                                                <div class="thumb-preview mt-2">
                                                    <?php $bgPreview = manager_preview_url($bgEffective, '', $baseDir); ?>
                                                    <?php
                                                        $bgFallbackSrcPath = ($bk === 'default')
                                                            ? $bgDefaultFallback
                                                            : ($bgUseCustomDefaultForMissing ? $bgDefaultEffective : $bgSizeFallback);
                                                        $bgFallbackPreview = manager_preview_url($bgFallbackSrcPath, '', $baseDir);
                                                    ?>
                                                    <img id="preview_background_<?php echo manager_safe_html($bk); ?>" data-current-src="<?php echo manager_safe_html($bgPreview); ?>" data-fallback-src="<?php echo manager_safe_html($bgFallbackPreview); ?>" src="<?php echo manager_safe_html($bgPreview); ?>" class="img-thumbnail <?php echo $bgPreview === '' ? 'd-none' : ''; ?>" alt="Anteprima <?php echo manager_safe_html($bk); ?>" loading="lazy">
                                                </div>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <h6 class="text-uppercase text-muted mt-3 mb-2">Opzioni stile immagine (globale)</h6>
                                        <div class="form-row">
                                            <div class="form-group col-md-6">
                                                <label>Posizione verticale <span class="text-muted ml-1" data-toggle="tooltip" title="Posizione verticale dell'immagine di sfondo."><i class="fa fa-question-circle"></i></span></label>
                                                <select name="background_style_position_vertical" id="background_style_position_vertical" class="form-control">
                                                    <option value="top" <?php echo ($content_current['background_style']['position_vertical'] ?? 'center') === 'top' ? 'selected' : ''; ?>>Top (Alto)</option>
                                                    <option value="center" <?php echo ($content_current['background_style']['position_vertical'] ?? 'center') === 'center' ? 'selected' : ''; ?>>Center (Centro)</option>
                                                    <option value="bottom" <?php echo ($content_current['background_style']['position_vertical'] ?? 'center') === 'bottom' ? 'selected' : ''; ?>>Bottom (Basso)</option>
                                                </select>
                                            </div>
                                            <div class="form-group col-md-6">
                                                <label>Posizione orizzontale <span class="text-muted ml-1" data-toggle="tooltip" title="Posizione orizzontale dell'immagine di sfondo."><i class="fa fa-question-circle"></i></span></label>
                                                <select name="background_style_position_horizontal" id="background_style_position_horizontal" class="form-control">
                                                    <option value="left" <?php echo ($content_current['background_style']['position_horizontal'] ?? 'center') === 'left' ? 'selected' : ''; ?>>Left (Sinistra)</option>
                                                    <option value="center" <?php echo ($content_current['background_style']['position_horizontal'] ?? 'center') === 'center' ? 'selected' : ''; ?>>Center (Centro)</option>
                                                    <option value="right" <?php echo ($content_current['background_style']['position_horizontal'] ?? 'center') === 'right' ? 'selected' : ''; ?>>Right (Destra)</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="form-row">
                                            <div class="form-group col-md-6">
                                                <label>Dimensione <span class="text-muted ml-1" data-toggle="tooltip" title="Come l'immagine deve essere ridimensionata per adattarsi allo sfondo."><i class="fa fa-question-circle"></i></span></label>
                                                <select name="background_style_size" id="background_style_size" class="form-control">
                                                    <option value="cover" <?php echo ($content_current['background_style']['size'] ?? 'cover') === 'cover' ? 'selected' : ''; ?>>Cover (Copre tutto)</option>
                                                    <option value="contain" <?php echo ($content_current['background_style']['size'] ?? 'cover') === 'contain' ? 'selected' : ''; ?>>Contain (Contiene tutto)</option>
                                                    <option value="auto" <?php echo ($content_current['background_style']['size'] ?? 'cover') === 'auto' ? 'selected' : ''; ?>>Auto (Dimensione originale)</option>
                                                    <option value="custom" <?php echo ($content_current['background_style']['size'] ?? 'cover') === 'custom' ? 'selected' : ''; ?>>Personalizzato</option>
                                                </select>
                                            </div>
                                            <div class="form-group col-md-6" id="background_size_custom_wrapper" style="<?php echo ($content_current['background_style']['size'] ?? 'cover') === 'custom' ? '' : 'display:none;'; ?>">
                                                <label>Dimensione personalizzata <span class="text-muted ml-1" data-toggle="tooltip" title="Esempi: 100% 100% (stretch), 200px 300px, 50% auto"><i class="fa fa-question-circle"></i></span></label>
                                                <input type="text" name="background_style_size_custom" id="background_style_size_custom" class="form-control" value="<?php echo manager_safe_html($content_current['background_style']['size_custom'] ?? ''); ?>" placeholder="100% 100%">
                                                <small class="form-text text-muted d-none">Es: 100% 100%, 200px 300px, 50% auto</small>
                                            </div>
                                        </div>
                                        <div class="form-row">
                                            <div class="form-group col-md-6">
                                                <label>Ripetizione <span class="text-muted ml-1" data-toggle="tooltip" title="Come l'immagine deve essere ripetuta se più piccola dello sfondo."><i class="fa fa-question-circle"></i></span></label>
                                                <select name="background_style_repeat" id="background_style_repeat" class="form-control">
                                                    <option value="no-repeat" <?php echo ($content_current['background_style']['repeat'] ?? 'no-repeat') === 'no-repeat' ? 'selected' : ''; ?>>No-repeat (Non ripetere)</option>
                                                    <option value="repeat" <?php echo ($content_current['background_style']['repeat'] ?? 'no-repeat') === 'repeat' ? 'selected' : ''; ?>>Repeat (Ripeti in tutte le direzioni)</option>
                                                    <option value="repeat-x" <?php echo ($content_current['background_style']['repeat'] ?? 'no-repeat') === 'repeat-x' ? 'selected' : ''; ?>>Repeat-X (Ripeti orizzontalmente)</option>
                                                    <option value="repeat-y" <?php echo ($content_current['background_style']['repeat'] ?? 'no-repeat') === 'repeat-y' ? 'selected' : ''; ?>>Repeat-Y (Ripeti verticalmente)</option>
                                                </select>
                                            </div>
                                            <div class="form-group col-md-6">
                                                <label>Attacco <span class="text-muted ml-1" data-toggle="tooltip" title="Come l'immagine si comporta durante lo scroll."><i class="fa fa-question-circle"></i></span></label>
                                                <select name="background_style_attachment" id="background_style_attachment" class="form-control">
                                                    <option value="scroll" <?php echo ($content_current['background_style']['attachment'] ?? 'fixed') === 'scroll' ? 'selected' : ''; ?>>Scroll (Scorre con la pagina)</option>
                                                    <option value="fixed" <?php echo ($content_current['background_style']['attachment'] ?? 'fixed') === 'fixed' ? 'selected' : ''; ?>>Fixed (Fisso durante lo scroll)</option>
                                                </select>
                                            </div>
                                        </div>
                                        
                                        <div class="mt-3">
                                            <div class="form-group">
                                                <div class="custom-control custom-checkbox">
                                                    <input type="checkbox" class="custom-control-input" id="background_customize_per_size" name="background_style_customize_per_size" value="1" <?php echo ($content_current['background_style']['customize_per_size'] ?? false) ? 'checked' : ''; ?>>
                                                    <label class="custom-control-label" for="background_customize_per_size">
                                                        <strong>Personalizza per ogni formato</strong> <span class="text-muted ml-1" data-toggle="tooltip" title="Se attivato, puoi configurare posizione, dimensione e ripetizione specificamente per ogni formato (mobile, tablet, laptop, desktop, ultrawide)."><i class="fa fa-question-circle"></i></span>
                                                    </label>
                                                </div>
                                            </div>
                                            
                                            <div id="background_advanced_options" style="<?php echo ($content_current['background_style']['customize_per_size'] ?? false) ? '' : 'display:none;'; ?>">
                                                <div class="accordion mt-2" id="backgroundSizeAccordion">
                                                    <?php 
                                                    $sizeLabels = [
                                                        'mobile' => 'Mobile (max 767px)',
                                                        'tablet' => 'Tablet (768px - 1023px)',
                                                        'laptop' => 'Laptop (1024px - 1365px)',
                                                        'desktop' => 'Desktop (1366px - 1919px)',
                                                        'ultrawide' => 'Ultrawide (=2560px)'
                                                    ];
                                                    foreach (['mobile', 'tablet', 'laptop', 'desktop', 'ultrawide'] as $sizeKey): 
                                                        $sizeConfig = $content_current['background_style'][$sizeKey] ?? [];
                                                    ?>
                                                    <div class="card">
                                                        <div class="card-header" id="heading_<?php echo $sizeKey; ?>">
                                                            <h6 class="mb-0">
                                                                <button class="btn btn-link btn-block text-left" type="button" data-toggle="collapse" data-target="#collapse_<?php echo $sizeKey; ?>" aria-expanded="false" aria-controls="collapse_<?php echo $sizeKey; ?>">
                                                                    <i class="fa fa-chevron-down mr-2"></i><?php echo manager_safe_html($sizeLabels[$sizeKey]); ?>
                                                                </button>
                                                            </h6>
                                                        </div>
                                                        <div id="collapse_<?php echo $sizeKey; ?>" class="collapse" aria-labelledby="heading_<?php echo $sizeKey; ?>" data-parent="#backgroundSizeAccordion">
                                                            <div class="card-body">
                                                                <div class="form-row">
                                                                    <div class="form-group col-md-6">
                                                                        <label>Posizione verticale</label>
                                                                        <select name="background_style_<?php echo $sizeKey; ?>_position_vertical" class="form-control">
                                                                            <option value="top" <?php echo ($sizeConfig['position_vertical'] ?? 'center') === 'top' ? 'selected' : ''; ?>>Top</option>
                                                                            <option value="center" <?php echo ($sizeConfig['position_vertical'] ?? 'center') === 'center' ? 'selected' : ''; ?>>Center</option>
                                                                            <option value="bottom" <?php echo ($sizeConfig['position_vertical'] ?? 'center') === 'bottom' ? 'selected' : ''; ?>>Bottom</option>
                                                                        </select>
                                                                    </div>
                                                                    <div class="form-group col-md-6">
                                                                        <label>Posizione orizzontale</label>
                                                                        <select name="background_style_<?php echo $sizeKey; ?>_position_horizontal" class="form-control">
                                                                            <option value="left" <?php echo ($sizeConfig['position_horizontal'] ?? 'center') === 'left' ? 'selected' : ''; ?>>Left</option>
                                                                            <option value="center" <?php echo ($sizeConfig['position_horizontal'] ?? 'center') === 'center' ? 'selected' : ''; ?>>Center</option>
                                                                            <option value="right" <?php echo ($sizeConfig['position_horizontal'] ?? 'center') === 'right' ? 'selected' : ''; ?>>Right</option>
                                                                        </select>
                                                                    </div>
                                                                </div>
                                                                <div class="form-row">
                                                                    <div class="form-group col-md-6">
                                                                        <label>Dimensione</label>
                                                                        <select name="background_style_<?php echo $sizeKey; ?>_size" class="form-control background-size-select" data-size-key="<?php echo $sizeKey; ?>">
                                                                            <option value="cover" <?php echo ($sizeConfig['size'] ?? 'cover') === 'cover' ? 'selected' : ''; ?>>Cover</option>
                                                                            <option value="contain" <?php echo ($sizeConfig['size'] ?? 'cover') === 'contain' ? 'selected' : ''; ?>>Contain</option>
                                                                            <option value="auto" <?php echo ($sizeConfig['size'] ?? 'cover') === 'auto' ? 'selected' : ''; ?>>Auto</option>
                                                                            <option value="custom" <?php echo ($sizeConfig['size'] ?? 'cover') === 'custom' ? 'selected' : ''; ?>>Personalizzato</option>
                                                                        </select>
                                                                    </div>
                                                                    <div class="form-group col-md-6 background-size-custom-wrapper-<?php echo $sizeKey; ?>" style="<?php echo ($sizeConfig['size'] ?? 'cover') === 'custom' ? '' : 'display:none;'; ?>">
                                                                        <label>Dimensione personalizzata</label>
                                                                        <input type="text" name="background_style_<?php echo $sizeKey; ?>_size_custom" class="form-control" value="<?php echo manager_safe_html($sizeConfig['size_custom'] ?? ''); ?>" placeholder="100% 100%">
                                                                    </div>
                                                                </div>
                                                                <div class="form-row">
                                                                    <div class="form-group col-md-6">
                                                                        <label>Ripetizione</label>
                                                                        <select name="background_style_<?php echo $sizeKey; ?>_repeat" class="form-control">
                                                                            <option value="no-repeat" <?php echo ($sizeConfig['repeat'] ?? 'no-repeat') === 'no-repeat' ? 'selected' : ''; ?>>No-repeat</option>
                                                                            <option value="repeat" <?php echo ($sizeConfig['repeat'] ?? 'no-repeat') === 'repeat' ? 'selected' : ''; ?>>Repeat</option>
                                                                            <option value="repeat-x" <?php echo ($sizeConfig['repeat'] ?? 'no-repeat') === 'repeat-x' ? 'selected' : ''; ?>>Repeat-X</option>
                                                                            <option value="repeat-y" <?php echo ($sizeConfig['repeat'] ?? 'no-repeat') === 'repeat-y' ? 'selected' : ''; ?>>Repeat-Y</option>
                                                                        </select>
                                                                    </div>
                                                                    <div class="form-group col-md-6">
                                                                        <label>Attacco</label>
                                                                        <select name="background_style_<?php echo $sizeKey; ?>_attachment" class="form-control">
                                                                            <option value="scroll" <?php echo ($sizeConfig['attachment'] ?? 'fixed') === 'scroll' ? 'selected' : ''; ?>>Scroll</option>
                                                                            <option value="fixed" <?php echo ($sizeConfig['attachment'] ?? 'fixed') === 'fixed' ? 'selected' : ''; ?>>Fixed</option>
                                                                        </select>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

<?php
    if (!isset($styleCustomization) || !is_array($styleCustomization)) {
        $styleCustomization = is_array($content_current['style_customization'] ?? null)
            ? $content_current['style_customization']
            : (is_array($content_defaults['style_customization'] ?? null) ? $content_defaults['style_customization'] : []);
    }
?>
                            <div class="card mt-3 mb-4 config-anchor config-step palette-footer-section footer-section" data-step="3" id="section-footer">
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
                                            <iframe id="footer-layout-preview-iframe" title="Anteprima footer" src="ajax/preview_footer.php?live=1" style="width:100%;height:260px;border:none;display:block;"></iframe>
                                        </div>
                                    </div>
                                    </div>
                                </div>
                            </div>
