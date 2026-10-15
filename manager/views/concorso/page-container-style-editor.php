<?php

if (!function_exists('manager_public_page_container_style_defaults')) {
    function manager_public_page_container_style_defaults(): array
    {
        return [
            'mode' => 'color',
            'color' => '#ffffff',
            'opacity' => 100,
            'image' => '',
            'radius' => [
                'top' => 0,
                'right' => 0,
                'bottom' => 12,
                'left' => 12,
            ],
        ];
    }
}

if (!function_exists('manager_normalize_public_page_container_style')) {
    function manager_normalize_public_page_container_style($style, array $fallback = []): array
    {
        $defaults = array_replace_recursive(manager_public_page_container_style_defaults(), $fallback);
        $style = is_array($style) ? $style : [];

        $mode = strtolower(trim((string) ($style['mode'] ?? $defaults['mode'] ?? 'color')));
        if (!in_array($mode, ['color', 'image'], true)) {
            $mode = 'color';
        }

        $color = trim((string) ($style['color'] ?? $defaults['color'] ?? '#ffffff'));
        if (strtolower($color) !== 'transparent' && !preg_match('/^#[0-9A-Fa-f]{6}$/', $color)) {
            $color = '#ffffff';
        }

        $radiusInput = is_array($style['radius'] ?? null) ? $style['radius'] : [];
        $radiusFallback = is_array($defaults['radius'] ?? null) ? $defaults['radius'] : [];
        $radius = [];
        foreach (['top', 'right', 'bottom', 'left'] as $side) {
            $radius[$side] = max(0, min(80, (int) ($radiusInput[$side] ?? $radiusFallback[$side] ?? 0)));
        }

        return [
            'mode' => $mode,
            'color' => $color,
            'opacity' => max(0, min(100, (int) ($style['opacity'] ?? $defaults['opacity'] ?? 100))),
            'image' => trim((string) ($style['image'] ?? $defaults['image'] ?? '')),
            'radius' => $radius,
        ];
    }
}

if (!function_exists('manager_public_page_container_definitions')) {
    function manager_public_page_container_definitions(): array
    {
        return [
            'registration' => ['label' => 'Registrazione', 'step' => '4'],
            'help' => ['label' => 'Assistenza / FAQ', 'step' => '7'],
            'waiting_page' => ['label' => 'Pagina attesa', 'step' => '6'],
            'participation_page' => ['label' => 'Pagina partecipazione', 'step' => '6'],
            'waiting_page_ended' => ['label' => 'Pagina attesa - Fine', 'step' => '6'],
            'thankyou_instantwin_win_page' => ['label' => 'Thank-you WIN', 'step' => '5'],
            'thankyou_instantwin_loss_page' => ['label' => 'Thank-you LOSE', 'step' => '5'],
            'thankyou_instantwin_quiz_failed_page' => ['label' => 'Thank-you quiz non superato', 'step' => '5'],
            'thankyou_estrazione_page' => ['label' => 'Thank-you partecipazione', 'step' => '5'],
            'thankyou_estrazione_quiz_failed_page' => ['label' => 'Thank-you quiz non superato (estrazione)', 'step' => '5'],
            'quiz_page' => ['label' => 'Quiz – Intestazione', 'step' => '10'],
            'quiz_choice_page' => ['label' => 'Quiz – Pagina scelta', 'step' => '10'],
        ];
    }
}

if (!function_exists('manager_public_page_container_content_page_key')) {
    function manager_public_page_container_content_page_key(string $pageKey): string
    {
        if ($pageKey === 'registration') {
            return 'form_page';
        }
        if ($pageKey === 'help') {
            return 'help_page';
        }
        return $pageKey;
    }
}

if (!function_exists('manager_public_page_container_fallback_style')) {
    function manager_public_page_container_fallback_style(string $pageKey, array $currentConfig, array $defaultConfig = []): array
    {
        $defaultStyles = is_array($defaultConfig['public_page_containers'] ?? null) ? $defaultConfig['public_page_containers'] : [];
        $fallback = manager_normalize_public_page_container_style($defaultStyles[$pageKey] ?? []);

        $contentPageKey = manager_public_page_container_content_page_key($pageKey);
        if ($contentPageKey === '') {
            return $fallback;
        }

        $page = is_array($currentConfig['pages'][$contentPageKey] ?? null)
            ? $currentConfig['pages'][$contentPageKey]
            : (is_array($defaultConfig['pages'][$contentPageKey] ?? null) ? $defaultConfig['pages'][$contentPageKey] : []);
        $containers = is_array($page['containers'] ?? null) ? $page['containers'] : [];
        $first = is_array($containers[0] ?? null) ? $containers[0] : [];
        $background = is_array($first['background'] ?? null) ? $first['background'] : [];
        if (!empty($background)) {
            $fallback['mode'] = in_array(($background['mode'] ?? ''), ['color', 'image'], true) ? (string) $background['mode'] : $fallback['mode'];
            $fallback['color'] = (strtolower(trim((string) ($background['color'] ?? ''))) === 'transparent' || preg_match('/^#[0-9A-Fa-f]{6}$/', (string) ($background['color'] ?? '')))
                ? (string) $background['color']
                : $fallback['color'];
            $fallback['opacity'] = max(0, min(100, (int) ($background['opacity'] ?? $fallback['opacity'])));
            $fallback['image'] = trim((string) ($background['image'] ?? $fallback['image']));
        }

        $style = is_array($first['style'] ?? null) ? $first['style'] : [];
        if (is_array($style['radius'] ?? null)) {
            $fallback['radius'] = manager_normalize_public_page_container_style(['radius' => $style['radius']], $fallback)['radius'];
        }

        return $fallback;
    }
}

if (!function_exists('manager_resolve_public_page_container_styles')) {
    function manager_resolve_public_page_container_styles(array $currentConfig, array $defaultConfig = []): array
    {
        $currentStyles = is_array($currentConfig['public_page_containers'] ?? null) ? $currentConfig['public_page_containers'] : [];
        $defaultStyles = is_array($defaultConfig['public_page_containers'] ?? null) ? $defaultConfig['public_page_containers'] : [];

        $styles = [];
        foreach (manager_public_page_container_definitions() as $pageKey => $_meta) {
            $fallback = manager_public_page_container_fallback_style($pageKey, $currentConfig, $defaultConfig);
            $styles[$pageKey] = manager_normalize_public_page_container_style(
                $currentStyles[$pageKey] ?? ($defaultStyles[$pageKey] ?? []),
                $fallback
            );
        }

        return $styles;
    }
}

if (!function_exists('manager_collect_public_page_container_styles_from_post')) {
    function manager_collect_public_page_container_styles_from_post(array $currentConfig, array $defaultConfig = []): array
    {
        $currentStyles = manager_resolve_public_page_container_styles($currentConfig, $defaultConfig);
        $posted = $_POST['public_page_containers'] ?? [];
        $posted = is_array($posted) ? $posted : [];

        $styles = [];
        foreach (manager_public_page_container_definitions() as $pageKey => $_meta) {
            $fallback = manager_public_page_container_fallback_style($pageKey, $currentConfig, $defaultConfig);
            $normalized = manager_normalize_public_page_container_style(
                $posted[$pageKey] ?? ($currentStyles[$pageKey] ?? []),
                $fallback
            );
            $styles[$pageKey] = $normalized == $fallback ? [] : $normalized;
        }

        return $styles;
    }
}

if (!function_exists('manager_public_page_container_upload_input')) {
    function manager_public_page_container_upload_input(string $pageKey): string
    {
        return 'upload_public_page_container_' . preg_replace('/[^a-z0-9_]+/i', '_', $pageKey) . '_image';
    }
}

if (!function_exists('manager_render_public_page_container_style_editor')) {
    function manager_render_public_page_container_style_editor(array $args): string
    {
        $pageKey = (string) ($args['page_key'] ?? '');
        if ($pageKey === '') {
            return '';
        }

        $label = (string) ($args['label'] ?? 'Pagina pubblica');
        $style = manager_normalize_public_page_container_style($args['style'] ?? []);
        $baseDir = (string) ($args['base_dir'] ?? '');
        $inputBase = 'public_page_containers[' . $pageKey . ']';
        $idBase = 'public_page_container_' . preg_replace('/[^a-z0-9_]+/i', '_', $pageKey);
        $uploadInput = manager_public_page_container_upload_input($pageKey);
        $formAttr = ' form="config-form"';
        $imagePreview = function_exists('manager_preview_url')
            ? manager_preview_url($style['image'], '', $baseDir)
            : $style['image'];

        ob_start();
        ?>
        <div class="form-container-panel public-page-container-panel" data-public-page-container="<?php echo manager_safe_html($pageKey); ?>">
            <h6 class="text-uppercase text-muted mt-3 mb-2">Container pagina - <?php echo manager_safe_html($label); ?></h6>
            <div class="form-row form-container-main-grid">
                <div class="form-group col-md-4 form-container-control-card form-container-mode-card">
                    <label>Modalita</label>
                    <select name="<?php echo manager_safe_html($inputBase); ?>[mode]" id="<?php echo manager_safe_html($idBase); ?>_mode" class="form-control public-page-container-mode"<?php echo $formAttr; ?>>
                        <option value="color" <?php echo $style['mode'] === 'color' ? 'selected' : ''; ?>>Colore</option>
                        <option value="image" <?php echo $style['mode'] === 'image' ? 'selected' : ''; ?>>Immagine</option>
                    </select>
                </div>
                <div class="form-group col-md-4 form-container-color form-container-control-card">
                    <label>Colore <span class="text-muted ml-1" data-toggle="tooltip" title="Colore di sfondo del container di questa pagina pubblica."><i class="fa fa-question-circle"></i></span></label>
                    <div class="d-flex align-items-center color-row">
                        <input type="color" class="form-control color-input color-swatch" data-target="<?php echo manager_safe_html($idBase); ?>_color_text" value="<?php echo manager_safe_html($style['color']); ?>">
                        <input type="text" name="<?php echo manager_safe_html($inputBase); ?>[color]" id="<?php echo manager_safe_html($idBase); ?>_color_text" class="form-control color-code-input" value="<?php echo manager_safe_html($style['color']); ?>" placeholder="#ffffff"<?php echo $formAttr; ?>>
                    </div>
                </div>
                <div class="form-group col-md-4 form-container-color form-container-control-card">
                    <label>Opacita <span class="text-muted ml-1" data-toggle="tooltip" title="0 = completamente trasparente, 100 = completamente opaco."><i class="fa fa-question-circle"></i></span></label>
                    <div class="d-flex align-items-center opacity-row">
                        <input type="range" class="form-control-range opacity-slider public-page-container-opacity-slider" min="0" max="100" step="1" value="<?php echo manager_safe_html((string) $style['opacity']); ?>">
                        <input type="number" name="<?php echo manager_safe_html($inputBase); ?>[opacity]" class="form-control opacity-input ml-2 public-page-container-opacity" min="0" max="100" value="<?php echo manager_safe_html((string) $style['opacity']); ?>"<?php echo $formAttr; ?>>
                        <span class="ml-1">%</span>
                    </div>
                </div>
                <div class="form-group col-md-4 form-container-image form-container-control-card form-container-image-card">
                    <label>Immagine (consigliato 1200x800) <span class="text-muted ml-1" data-toggle="tooltip" title="Background immagine applicato al container di questa pagina."><i class="fa fa-question-circle"></i></span></label>
                    <input type="hidden" name="<?php echo manager_safe_html($inputBase); ?>[image]" value="<?php echo manager_safe_html($style['image']); ?>"<?php echo $formAttr; ?>>
                    <div class="custom-file">
                        <input type="file" name="<?php echo manager_safe_html($uploadInput); ?>" id="<?php echo manager_safe_html($uploadInput); ?>" class="custom-file-input preview-input public-page-container-image-input" accept="image/png,image/jpeg,image/gif,image/webp,image/svg+xml"<?php echo $formAttr; ?> data-preview-target="<?php echo manager_safe_html($idBase); ?>_preview">
                        <label class="custom-file-label" for="<?php echo manager_safe_html($uploadInput); ?>">Scegli immagine...</label>
                    </div>
                    <small class="form-text text-muted mt-1">Attuale: <span id="<?php echo manager_safe_html($idBase); ?>_current" data-default="<?php echo manager_safe_html($style['image']); ?>"><?php echo manager_safe_html($style['image']); ?></span></small>
                    <div class="thumb-preview mt-2">
                        <img id="<?php echo manager_safe_html($idBase); ?>_preview" data-current-src="<?php echo manager_safe_html($imagePreview); ?>" src="<?php echo manager_safe_html($imagePreview); ?>" class="img-thumbnail <?php echo $imagePreview === '' ? 'd-none' : ''; ?>" alt="Anteprima immagine container pagina" loading="lazy">
                    </div>
                </div>
            </div>
            <div class="form-row mt-2 form-container-radius-grid">
                <div class="form-group col-12 form-container-radius-intro">
                    <label class="d-block">Angoli container esterno (px)</label>
                </div>
                <?php foreach (['top' => 'Angolo sopra sx', 'right' => 'Angolo sopra dx', 'bottom' => 'Angolo sotto dx', 'left' => 'Angolo sotto sx'] as $side => $sideLabel): ?>
                    <div class="form-group col-md-3 form-container-radius-card">
                        <label><?php echo manager_safe_html($sideLabel); ?></label>
                        <input type="number" name="<?php echo manager_safe_html($inputBase); ?>[radius][<?php echo manager_safe_html($side); ?>]" class="form-control" min="0" max="80" value="<?php echo manager_safe_html((string) $style['radius'][$side]); ?>"<?php echo $formAttr; ?>>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}
