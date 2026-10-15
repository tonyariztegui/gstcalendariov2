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

<?php include __DIR__ . '/partials/footer-editor.php'; ?>