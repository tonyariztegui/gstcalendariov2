<?php

if (!function_exists('manager_page_container_field_prefix')) {
    function manager_page_container_field_prefix(string $tabId): string
    {
        $prefix = preg_replace('/[^a-z0-9_]+/i', '_', $tabId);
        return trim((string) $prefix, '_');
    }
}

if (!function_exists('manager_normalize_page_container')) {
    function manager_normalize_page_container(array $container, string $alignValue, array $defaultBackground, array $defaultMargin, array $defaultRadius): array
    {
        $contentType = $container['content_type'] ?? 'text';
        if (!in_array($contentType, ['text', 'image'], true)) {
            $contentType = 'text';
        }

        $background = $container['background'] ?? $defaultBackground;
        if (!is_array($background)) {
            $background = $defaultBackground;
        }
        $background = array_merge($defaultBackground, $background);
        $backgroundMode = strtolower((string) ($background['mode'] ?? 'color'));
        if (!in_array($backgroundMode, ['transparent', 'color', 'image'], true)) {
            $backgroundMode = 'color';
        }
        $backgroundOpacity = max(0, min(100, (int) ($background['opacity'] ?? 100)));
        if ($backgroundMode === 'transparent') {
            $backgroundMode = 'color';
            $backgroundOpacity = 0;
        }
        $background['mode'] = $backgroundMode;
        $background['opacity'] = $backgroundOpacity;
        $background['color'] = (string) ($background['color'] ?? '#ffffff');
        $background['image'] = (string) ($background['image'] ?? '');

        $image = $container['image'] ?? ['src' => '', 'alt' => '', 'width' => ''];
        if (!is_array($image)) {
            $image = ['src' => '', 'alt' => '', 'width' => ''];
        }

        $style = $container['style'] ?? [];
        if (!is_array($style)) {
            $style = [];
        }

        $margin = $container['margin'] ?? ($style['margin'] ?? $defaultMargin);
        if (!is_array($margin)) {
            $margin = $defaultMargin;
        }
        $margin = array_merge($defaultMargin, $margin);
        $radius = $container['radius'] ?? ($style['radius'] ?? $defaultRadius);
        if (!is_array($radius)) {
            $radius = $defaultRadius;
        }
        $radius = array_merge($defaultRadius, $radius);
        foreach (['top', 'right', 'bottom', 'left'] as $side) {
            $margin[$side] = max(0, (int) ($margin[$side] ?? 0));
            $radius[$side] = max(0, (int) ($radius[$side] ?? 0));
        }

        $align = (string) ($container['align'] ?? ($style['align'] ?? $alignValue));

        return [
            'id' => (string) ($container['id'] ?? ''),
            'align' => $align,
            'content_type' => $contentType,
            'background' => $background,
            'margin' => $margin,
            'radius' => $radius,
            'html' => (string) ($container['html'] ?? ''),
            'image' => [
                'src' => (string) ($image['src'] ?? ''),
                'alt' => (string) ($image['alt'] ?? ''),
                'width' => (string) ($image['width'] ?? ''),
            ],
        ];
    }
}

if (!function_exists('manager_render_page_container_item')) {
    function manager_render_page_container_item(array $args, array $container, string $indexToken, ?int $displayIndex = null): string
    {
        $pageKey = (string) $args['page_key'];
        $tabId = (string) $args['tab_id'];
        $alignValue = (string) $args['align_value'];
        $baseDir = (string) $args['base_dir'];
        $fieldPrefix = manager_page_container_field_prefix($tabId);
        $defaultBackground = (array) $args['default_background'];
        $defaultMargin = (array) $args['default_margin'];
        $defaultRadius = (array) $args['default_radius'];
        $isTemplate = $displayIndex === null;
        $formId = (string) ($args['form_id'] ?? 'config-form');
        $formAttr = $formId !== '' ? ' form="' . manager_safe_html($formId) . '"' : '';

        if ($isTemplate) {
            $container = [
                'id' => '__ID__',
                'align' => '__ALIGN__',
                'content_type' => 'text',
                'background' => $defaultBackground,
                'margin' => $defaultMargin,
                'radius' => $defaultRadius,
                'html' => '',
                'image' => ['src' => '', 'alt' => '', 'width' => ''],
            ];
        }

        $container = manager_normalize_page_container($container, $alignValue, $defaultBackground, $defaultMargin, $defaultRadius);
        $textarea = $container['html'];
        if ($textarea !== '') {
            $textarea = preg_replace('~</textarea>~i', '&lt;/textarea&gt;', $textarea);
        }
        $bg = $container['background'];
        $image = $container['image'];
        $bgPreview = $isTemplate ? '' : manager_preview_url((string) $bg['image'], '', $baseDir);
        $imagePreview = $isTemplate ? '' : manager_preview_url((string) $image['src'], '', $baseDir);
        $colorTextId = $fieldPrefix . '_container_color_' . $indexToken . '_text';
        $opacityId = $fieldPrefix . '_container_opacity_' . $indexToken;
        $title = $isTemplate ? 'Container' : 'Container ' . ((int) $displayIndex + 1);

        ob_start();
        ?>
        <div class="waiting-container-item card mb-3" data-container-index="<?php echo manager_safe_html($indexToken); ?>">
            <div class="card-header d-flex justify-content-between align-items-center">
                <strong class="waiting-container-title"><?php echo manager_safe_html($title); ?></strong>
                <div class="waiting-container-actions d-flex align-items-center">
                    <div class="btn-group btn-group-sm" role="group" aria-label="Sposta container">
                        <button type="button" class="btn btn-outline-secondary btn-move-waiting-container" data-direction="up" title="Sposta su" aria-label="Sposta su">
                            <i class="fa fa-chevron-up"></i>
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-move-waiting-container" data-direction="down" title="Sposta giu" aria-label="Sposta giu">
                            <i class="fa fa-chevron-down"></i>
                        </button>
                    </div>
                    <button type="button" class="btn btn-outline-danger btn-sm btn-remove-waiting-container ml-2" title="Rimuovi container" aria-label="Rimuovi container">
                        <i class="fa fa-trash"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <input type="hidden" name="<?php echo manager_safe_html($pageKey); ?>_containers[<?php echo manager_safe_html($indexToken); ?>][id]" value="<?php echo manager_safe_html($container['id']); ?>"<?php echo $formAttr; ?>>
                <input type="hidden" name="<?php echo manager_safe_html($pageKey); ?>_containers[<?php echo manager_safe_html($indexToken); ?>][align]" class="waiting-container-align" data-align-default="<?php echo manager_safe_html($container['align']); ?>" value="<?php echo manager_safe_html($container['align']); ?>"<?php echo $formAttr; ?>>
                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label>Tipo contenuto</label>
                        <select name="<?php echo manager_safe_html($pageKey); ?>_containers[<?php echo manager_safe_html($indexToken); ?>][content_type]" class="form-control waiting-container-type"<?php echo $formAttr; ?>>
                            <option value="text" <?php echo $container['content_type'] === 'text' ? 'selected' : ''; ?>>Testo</option>
                            <option value="image" <?php echo $container['content_type'] === 'image' ? 'selected' : ''; ?>>Immagine</option>
                        </select>
                    </div>
                    <div class="form-group col-md-4">
                        <label>Sfondo</label>
                        <select name="<?php echo manager_safe_html($pageKey); ?>_containers[<?php echo manager_safe_html($indexToken); ?>][background][mode]" class="form-control waiting-container-bg-mode"<?php echo $formAttr; ?>>
                            <option value="color" <?php echo $bg['mode'] === 'color' ? 'selected' : ''; ?>>Colore</option>
                            <option value="image" <?php echo $bg['mode'] === 'image' ? 'selected' : ''; ?>>Immagine</option>
                        </select>
                    </div>
                    <div class="form-group col-md-4 waiting-container-align-select-wrapper d-none">
                        <label>Allineamento</label>
                        <select class="form-control waiting-container-align-select">
                            <?php foreach (['left' => 'Sinistra', 'center' => 'Centro', 'right' => 'Destra', 'justify' => 'Giustificato'] as $value => $label): ?>
                                <option value="<?php echo manager_safe_html($value); ?>" <?php echo $container['align'] === $value ? 'selected' : ''; ?>><?php echo manager_safe_html($label); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-row waiting-container-bg-color">
                    <div class="form-group col-md-6">
                        <label>Colore</label>
                        <div class="d-flex align-items-center color-row">
                            <input type="color" class="form-control color-input color-swatch" data-target="<?php echo manager_safe_html($colorTextId); ?>" value="<?php echo manager_safe_html((string) $bg['color']); ?>">
                            <input type="text" name="<?php echo manager_safe_html($pageKey); ?>_containers[<?php echo manager_safe_html($indexToken); ?>][background][color]" id="<?php echo manager_safe_html($colorTextId); ?>" class="form-control color-code-input waiting-container-bg-color-text" value="<?php echo manager_safe_html((string) $bg['color']); ?>" placeholder="#ffffff"<?php echo $formAttr; ?>>
                        </div>
                    </div>
                    <div class="form-group col-md-6">
                        <label>Trasparenza</label>
                        <div class="d-flex align-items-center opacity-row">
                            <input type="range" class="form-control-range opacity-slider waiting-bg-opacity-slider" min="0" max="100" step="1" value="<?php echo manager_safe_html((string) $bg['opacity']); ?>">
                            <input type="number" name="<?php echo manager_safe_html($pageKey); ?>_containers[<?php echo manager_safe_html($indexToken); ?>][background][opacity]" id="<?php echo manager_safe_html($opacityId); ?>" class="form-control opacity-input ml-2 waiting-bg-opacity-input" min="0" max="100" value="<?php echo manager_safe_html((string) $bg['opacity']); ?>"<?php echo $formAttr; ?>>
                            <span class="ml-1">%</span>
                        </div>
                    </div>
                </div>
                <div class="form-row waiting-container-bg-image">
                    <div class="form-group col-md-12">
                        <label>Immagine di sfondo</label>
                        <input type="hidden" name="<?php echo manager_safe_html($pageKey); ?>_containers[<?php echo manager_safe_html($indexToken); ?>][background][image]" class="waiting-bg-image-value" value="<?php echo manager_safe_html((string) $bg['image']); ?>" data-preview-src="<?php echo manager_safe_html($bgPreview); ?>"<?php echo $formAttr; ?>>
                        <div class="custom-file mt-2">
                            <input type="file" name="<?php echo manager_safe_html($pageKey); ?>_container_bg_upload[<?php echo manager_safe_html($indexToken); ?>]" class="custom-file-input waiting-bg-file-input" accept="image/png,image/jpeg"<?php echo $formAttr; ?>>
                            <label class="custom-file-label">Carica immagine (PNG/JPEG)</label>
                        </div>
                        <small class="form-text text-muted mt-1">Attuale: <span class="waiting-bg-current"><?php echo manager_safe_html((string) $bg['image']); ?></span></small>
                    </div>
                </div>
                <div class="form-row waiting-container-margins">
                    <?php foreach (['top' => 'Margine sopra', 'right' => 'Margine destra', 'bottom' => 'Margine sotto', 'left' => 'Margine sinistra'] as $side => $label): ?>
                        <div class="form-group col-md-3">
                            <label><?php echo manager_safe_html($label); ?> (px)</label>
                            <input type="number" name="<?php echo manager_safe_html($pageKey); ?>_containers[<?php echo manager_safe_html($indexToken); ?>][style][margin][<?php echo manager_safe_html($side); ?>]" class="form-control waiting-container-margin waiting-container-margin-<?php echo manager_safe_html($side); ?>" min="0" value="<?php echo manager_safe_html((string) $container['margin'][$side]); ?>"<?php echo $formAttr; ?>>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="form-row waiting-container-radius">
                    <?php foreach (['top' => 'Angolo sopra sx', 'right' => 'Angolo sopra dx', 'bottom' => 'Angolo sotto dx', 'left' => 'Angolo sotto sx'] as $side => $label): ?>
                        <div class="form-group col-md-3">
                            <label><?php echo manager_safe_html($label); ?> (px)</label>
                            <input type="number" name="<?php echo manager_safe_html($pageKey); ?>_containers[<?php echo manager_safe_html($indexToken); ?>][style][radius][<?php echo manager_safe_html($side); ?>]" class="form-control waiting-container-radius-input waiting-container-radius-<?php echo manager_safe_html($side); ?>" min="0" value="<?php echo manager_safe_html((string) $container['radius'][$side]); ?>"<?php echo $formAttr; ?>>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="waiting-container-content-text">
                    <div class="waiting-editor-mode-bar d-flex flex-wrap align-items-center justify-content-between mb-2">
                        <div class="waiting-editor-mode-toggle btn-group btn-group-sm" role="group" aria-label="Modalita editor">
                            <button type="button" class="btn btn-outline-secondary is-active" data-editor-mode="visual">Mode testo</button>
                            <button type="button" class="btn btn-outline-secondary" data-editor-mode="html">Mode HTML</button>
                        </div>
                        <small class="form-text text-muted mb-0 waiting-editor-mode-hint">Mode testo = modifica senza vedere il codice. Mode HTML = markup diretto (pulsanti, link).</small>
                    </div>
                    <textarea name="<?php echo manager_safe_html($pageKey); ?>_containers[<?php echo manager_safe_html($indexToken); ?>][html]" class="form-control waiting-container-editor summernote" rows="6"<?php echo $formAttr; ?>><?php echo $textarea; ?></textarea>
                </div>
                <div class="waiting-container-content-image">
                    <div class="form-group">
                        <label>Immagine</label>
                        <input type="hidden" name="<?php echo manager_safe_html($pageKey); ?>_containers[<?php echo manager_safe_html($indexToken); ?>][image][src]" class="waiting-image-src-value" value="<?php echo manager_safe_html((string) $image['src']); ?>" data-preview-src="<?php echo manager_safe_html($imagePreview); ?>"<?php echo $formAttr; ?>>
                        <div class="custom-file mt-2">
                            <input type="file" name="<?php echo manager_safe_html($pageKey); ?>_container_image_upload[<?php echo manager_safe_html($indexToken); ?>]" class="custom-file-input waiting-image-file-input" accept="image/png,image/jpeg"<?php echo $formAttr; ?>>
                            <label class="custom-file-label">Carica immagine (PNG/JPEG)</label>
                        </div>
                        <small class="form-text text-muted mt-1">Attuale: <span class="waiting-image-current"><?php echo manager_safe_html((string) $image['src']); ?></span></small>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Testo alternativo</label>
                            <input type="text" name="<?php echo manager_safe_html($pageKey); ?>_containers[<?php echo manager_safe_html($indexToken); ?>][image][alt]" class="form-control waiting-container-image-alt" value="<?php echo manager_safe_html((string) $image['alt']); ?>"<?php echo $formAttr; ?>>
                        </div>
                        <div class="form-group col-md-6">
                            <label>Larghezza (es. 100%, 320px)</label>
                            <input type="text" name="<?php echo manager_safe_html($pageKey); ?>_containers[<?php echo manager_safe_html($indexToken); ?>][image][width]" class="form-control waiting-container-image-width" value="<?php echo manager_safe_html((string) $image['width']); ?>"<?php echo $formAttr; ?>>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}

if (!function_exists('manager_render_page_container_preview')) {
    function manager_render_page_container_preview(array $args): string
    {
        $previewId = (string) $args['preview_id'];
        $alignValue = (string) $args['align_value'];
        $defaultMargin = (array) $args['default_margin'];
        $defaultRadius = (array) $args['default_radius'];
        $placeholders = (array) ($args['placeholders'] ?? []);

        ob_start();
        ?>
        <div id="<?php echo manager_safe_html($previewId); ?>"
             class="waiting-page-preview"
             data-align-default="<?php echo manager_safe_html($alignValue); ?>"
             data-placeholder-contest-name="<?php echo manager_safe_html((string) ($placeholders['contest_name'] ?? '')); ?>"
             data-placeholder-start-date="<?php echo manager_safe_html((string) ($placeholders['start_date'] ?? '')); ?>"
             data-placeholder-end-date="<?php echo manager_safe_html((string) ($placeholders['end_date'] ?? '')); ?>"
             data-placeholder-min-spend="<?php echo manager_safe_html((string) ($placeholders['min_spend'] ?? '')); ?>"
             data-placeholder-regolamento-link="<?php echo manager_safe_html((string) ($placeholders['regolamento_link'] ?? '')); ?>"
             data-placeholder-prize-win-amount="<?php echo manager_safe_html((string) ($placeholders['prize_win_amount'] ?? '')); ?>"
             data-placeholder-prize-type="<?php echo manager_safe_html((string) ($placeholders['prize_type'] ?? '')); ?>">
            <?php foreach ((array) $args['preview_containers'] as $previewContainer): ?>
                <?php
                    if (!is_array($previewContainer)) {
                        continue;
                    }
                    $previewBackground = $previewContainer['background'] ?? [];
                    if (!is_array($previewBackground)) {
                        $previewBackground = [];
                    }
                    $previewBgMode = strtolower((string) ($previewBackground['mode'] ?? 'color'));
                    $previewBgOpacity = (int) ($previewBackground['opacity'] ?? 100);
                    $previewIsTransparent = $previewBgMode === 'transparent' || ($previewBgMode === 'color' && $previewBgOpacity <= 0);
                    $previewClass = 'waiting-container-preview' . ($previewIsTransparent ? ' is-transparent' : '');
                    $previewMargin = $previewContainer['margin'] ?? $defaultMargin;
                    if (!is_array($previewMargin)) {
                        $previewMargin = $defaultMargin;
                    }
                    $previewMargin = array_merge($defaultMargin, $previewMargin);
                    $previewRadius = $previewContainer['radius'] ?? $defaultRadius;
                    if (!is_array($previewRadius)) {
                        $previewRadius = $defaultRadius;
                    }
                    $previewRadius = array_merge($defaultRadius, $previewRadius);
                    foreach (['top', 'right', 'bottom', 'left'] as $side) {
                        $previewMargin[$side] = max(0, (int) ($previewMargin[$side] ?? 0));
                        $previewRadius[$side] = max(0, (int) ($previewRadius[$side] ?? 0));
                    }
                    $previewMarginStyle = sprintf('%dpx %dpx %dpx %dpx', $previewMargin['top'], $previewMargin['right'], $previewMargin['bottom'], $previewMargin['left']);
                    $previewRadiusStyle = sprintf('%dpx %dpx %dpx %dpx', $previewRadius['top'], $previewRadius['right'], $previewRadius['bottom'], $previewRadius['left']);
                ?>
                <div class="<?php echo manager_safe_html($previewClass); ?>" style="text-align: <?php echo manager_safe_html((string) ($previewContainer['align'] ?? $alignValue)); ?>; margin: <?php echo manager_safe_html($previewMarginStyle); ?>; border-radius: <?php echo manager_safe_html($previewRadiusStyle); ?>;">
                    <?php if (($previewContainer['content_type'] ?? 'text') === 'image'): ?>
                        <?php
                            $previewImage = $previewContainer['image'] ?? [];
                            $previewImageSrc = trim((string) ($previewImage['src'] ?? ''));
                            $previewImageAlt = trim((string) ($previewImage['alt'] ?? ''));
                            $previewImageWidth = trim((string) ($previewImage['width'] ?? ''));
                        ?>
                        <?php if ($previewImageSrc !== ''): ?>
                            <img src="<?php echo manager_safe_html($previewImageSrc); ?>" alt="<?php echo manager_safe_html($previewImageAlt); ?>" class="img-fluid" style="<?php echo $previewImageWidth !== '' ? 'max-width: ' . manager_safe_html($previewImageWidth) . ';' : ''; ?>">
                        <?php else: ?>
                            <p class="text-muted mb-0">Nessun contenuto impostato.</p>
                        <?php endif; ?>
                    <?php else: ?>
                        <?php echo $previewContainer['html'] ?? ''; ?>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <small class="form-text text-muted">Anteprima con i dati di configurazione attuali.</small>
        <?php
        return ob_get_clean();
    }
}

if (!function_exists('manager_render_page_container_editor')) {
    function manager_render_page_container_editor(array $args): string
    {
        $tabId = (string) $args['tab_id'];
        $pageKey = (string) $args['page_key'];
        $containers = (array) $args['containers'];
        $showPrizePlaceholder = !empty($args['show_prize_placeholder']);
        $listId = (string) ($args['list_id'] ?? ($tabId . '-containers-list'));
        $previewId = (string) ($args['preview_id'] ?? ($tabId . '-preview'));
        $addBtnId = (string) ($args['add_btn_id'] ?? ('add-' . $tabId . '-container'));
        $templateId = (string) ($args['template_id'] ?? ($tabId . '-container-template'));
        $emptyId = (string) ($args['empty_id'] ?? ($tabId . '-containers-empty'));
        $alignInputId = (string) ($args['align_input_id'] ?? ($pageKey . '_align'));
        $alignValue = (string) $args['align_value'];
        $formId = (string) ($args['form_id'] ?? 'config-form');
        $formAttr = $formId !== '' ? ' form="' . manager_safe_html($formId) . '"' : '';
        $args['preview_id'] = $previewId;

        ob_start();
        ?>
        <div>
            <input type="hidden" name="<?php echo manager_safe_html($pageKey); ?>_present" value="1"<?php echo $formAttr; ?>>
            <input type="hidden" name="<?php echo manager_safe_html($pageKey); ?>_containers_present" value="1"<?php echo $formAttr; ?>>
            <input type="hidden" name="<?php echo manager_safe_html($pageKey); ?>_align" id="<?php echo manager_safe_html($alignInputId); ?>" class="waiting-page-align" value="<?php echo manager_safe_html($alignValue); ?>"<?php echo $formAttr; ?>>
            <div class="form-row waiting-page-editor-row">
                <div class="form-group col-lg-7 waiting-editor-col">
                    <div class="waiting-containers-scroll">
                        <div id="<?php echo manager_safe_html($listId); ?>" class="waiting-containers-list">
                            <?php foreach ($containers as $index => $container): ?>
                                <?php echo manager_render_page_container_item($args, is_array($container) ? $container : [], (string) (int) $index, (int) $index); ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="waiting-containers-footer">
                        <div class="d-flex align-items-center flex-wrap gap-2">
                            <button type="button" class="btn btn-outline-primary btn-sm add-waiting-container" id="<?php echo manager_safe_html($addBtnId); ?>"><i class="fa fa-plus mr-1"></i>Aggiungi container</button>
                            <div id="<?php echo manager_safe_html($emptyId); ?>" class="text-muted small ml-2 waiting-containers-empty" style="<?php echo !empty($containers) ? 'display:none;' : ''; ?>">Nessun container. Usa "Aggiungi container" per crearne uno.</div>
                        </div>
                        <div class="waiting-placeholders text-muted small">
                            <div>Placeholder disponibili: {{CONTEST_NAME}}, {{START_DATE}}, {{END_DATE}}, {{MIN_SPEND}}, {{REGOLAMENTO_LINK}}<?php echo $showPrizePlaceholder ? ', {{PRIZE_WIN_AMOUNT}}, {{PRIZE_TYPE}}' : ''; ?>.</div>
                            <div>Per un bottone primario (stesso stile di PARTECIPA): <code>&lt;a class="btn btn-success btn-lg fw-bold text-uppercase" href="{{REGOLAMENTO_LINK}}"&gt;REGOLAMENTO&lt;/a&gt;</code> — <code>btn-success</code> = stile Primario (storico Bootstrap), non un colore verde fisso.</div>
                            <div>Font e dimensione dei bottoni si impostano in <strong>Bottoni</strong>, non in Summernote.</div>
                        </div>
                    </div>
                    <template id="<?php echo manager_safe_html($templateId); ?>" class="waiting-container-template">
                        <?php echo manager_render_page_container_item($args, [], '__INDEX__', null); ?>
                    </template>
                </div>
                <div class="form-group col-lg-5 waiting-preview-col">
                    <?php echo manager_render_page_container_preview($args); ?>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}
