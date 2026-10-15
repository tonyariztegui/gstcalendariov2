<?php

declare(strict_types=1);

require_once __DIR__ . '/concorso-constants.php';

/**
 * @return array<string, int|string>|null
 */
function concorso_get_indexed_upload(string $inputName, $index): ?array
{
    if (!isset($_FILES[$inputName]) || !is_array($_FILES[$inputName])) {
        return null;
    }
    $fileSpec = $_FILES[$inputName];
    if (!isset($fileSpec['error'][$index])) {
        return null;
    }

    return [
        'name' => $fileSpec['name'][$index] ?? '',
        'type' => $fileSpec['type'][$index] ?? '',
        'tmp_name' => $fileSpec['tmp_name'][$index] ?? '',
        'error' => $fileSpec['error'][$index] ?? UPLOAD_ERR_NO_FILE,
        'size' => $fileSpec['size'][$index] ?? 0,
    ];
}

function concorso_config_upload_build_error_messages(string $configAssetUploadMaxLabel, bool $verbose): array
{
    $errorMessages = [
        UPLOAD_ERR_INI_SIZE => $verbose
            ? "Il file è troppo grande. Dimensione massima consentita: " . ini_get('upload_max_filesize') . ". Riduci la dimensione dell'immagine o aumenta upload_max_filesize in php.ini."
            : "Il file è troppo grande. Dimensione massima consentita: " . ini_get('upload_max_filesize') . ".",
        UPLOAD_ERR_FORM_SIZE => "Il file supera la dimensione massima consentita dal form.",
        UPLOAD_ERR_PARTIAL => "Il file è stato caricato solo parzialmente. Riprova.",
        UPLOAD_ERR_NO_FILE => "Nessun file è stato caricato.",
        UPLOAD_ERR_NO_TMP_DIR => "Cartella temporanea mancante sul server.",
        UPLOAD_ERR_CANT_WRITE => "Impossibile scrivere il file sul disco.",
        UPLOAD_ERR_EXTENSION => "Un'estensione PHP ha bloccato il caricamento del file.",
    ];
    $errorMessages[UPLOAD_ERR_INI_SIZE] = "Il file supera il limite massimo consentito per gli asset del manager ({$configAssetUploadMaxLabel}). Riduci la dimensione dell'immagine.";
    $errorMessages[UPLOAD_ERR_FORM_SIZE] = "Il file supera il limite massimo consentito per gli asset del manager ({$configAssetUploadMaxLabel}).";

    return $errorMessages;
}

function concorso_config_upload_handle_error_code(
    int $errorCode,
    string $inputLabel,
    array &$errors,
    bool &$hasManagerUploadSizeError,
    string $configAssetUploadMaxLabel,
    bool $verbose
): bool
{
    $errorMessages = concorso_config_upload_build_error_messages($configAssetUploadMaxLabel, $verbose);
    if ($errorCode === UPLOAD_ERR_INI_SIZE || $errorCode === UPLOAD_ERR_FORM_SIZE) {
        $hasManagerUploadSizeError = true;
    }
    $errorMsg = $errorMessages[$errorCode] ?? "Errore sconosciuto durante il caricamento (codice {$errorCode}).";
    $errors[] = "Errore caricamento file {$inputLabel}: {$errorMsg}";

    return true;
}

function concorso_config_upload_validate_file_size(
    int $fileSize,
    string $fileName,
    array &$errors,
    bool &$hasManagerUploadSizeError,
    int $configAssetUploadMaxBytes,
    string $configAssetUploadMaxLabel,
    bool $verbose
): bool
{
    if ($fileSize > $configAssetUploadMaxBytes) {
        $hasManagerUploadSizeError = true;
        if ($verbose) {
            $errors[] = "Il file '{$fileName}' e troppo grande. Limite manager: {$configAssetUploadMaxLabel}. Dimensione file: " . round($fileSize / 1024 / 1024, 2) . " MB. Riduci la dimensione dell'immagine.";
        } else {
            $errors[] = "Il file '{$fileName}' e troppo grande. Limite manager: {$configAssetUploadMaxLabel}.";
        }

        return false;
    }

    $maxSize = $configAssetUploadMaxLabel;
    $maxBytes = $configAssetUploadMaxBytes;
    if ($fileSize > $maxBytes) {
        if ($verbose) {
            $errors[] = "Il file '{$fileName}' è troppo grande ({$maxSize} max). Dimensione file: " . round($fileSize / 1024 / 1024, 2) . " MB. Riduci la dimensione dell'immagine.";
        } else {
            $errors[] = "Il file '{$fileName}' è troppo grande ({$maxSize} max).";
        }

        return false;
    }

    return true;
}

function concorso_config_upload_resolve_final_name(
    ?string $targetName,
    ?callable $nameResolver,
    string $inputName,
    string $ext,
    string $targetDir,
    array $file
): string
{
    $finalName = $targetName;
    if ($nameResolver !== null && is_callable($nameResolver)) {
        $finalName = $nameResolver($file, $ext);
    }
    if ($finalName === null) {
        return $inputName . '_' . uniqid('', true) . '.' . $ext;
    }

    $baseName = pathinfo($finalName, PATHINFO_FILENAME);
    $finalName = $baseName . '.' . $ext;
    if ($targetName !== null && $targetName === $baseName) {
        $pattern = rtrim($targetDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $baseName . '.*';
        $existingFiles = glob($pattern);
        if ($existingFiles !== false) {
            foreach ($existingFiles as $existingFile) {
                if (is_file($existingFile)) {
                    @unlink($existingFile);
                }
            }
        }
    }

    return $finalName;
}

function concorso_config_upload_move_file(
    array $file,
    string $targetDir,
    string $finalName,
    string $targetBase,
    string $inputLabel,
    array &$errors,
    string $mkdirErrorMessage
): ?string
{
    if (!is_dir($targetDir) && !@mkdir($targetDir, 0777, true)) {
        $errors[] = $mkdirErrorMessage;

        return null;
    }

    $targetPath = rtrim($targetDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $finalName;
    if (is_file($targetPath)) {
        @unlink($targetPath);
    }
    if (!@move_uploaded_file($file['tmp_name'], $targetPath)) {
        $errors[] = "Impossibile salvare il file {$inputLabel}.";

        return null;
    }

    return rtrim($targetBase, '/') . '/' . $finalName;
}

function concorso_handle_config_upload(
    string $inputName,
    string $targetDir,
    string $targetBase,
    array &$errors,
    bool &$hasManagerUploadSizeError,
    int $configAssetUploadMaxBytes,
    string $configAssetUploadMaxLabel,
    ?array $allowed = null,
    ?string $targetName = null,
    ?callable $nameResolver = null
): ?string {
    if (!isset($_FILES[$inputName]) || ($_FILES[$inputName]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    $file = $_FILES[$inputName];
    $errorCode = $file['error'] ?? UPLOAD_ERR_NO_FILE;
    if ($errorCode !== UPLOAD_ERR_OK) {
        concorso_config_upload_handle_error_code(
            $errorCode,
            $inputName,
            $errors,
            $hasManagerUploadSizeError,
            $configAssetUploadMaxLabel,
            true
        );

        return null;
    }

    if (!concorso_config_upload_validate_file_size(
        (int) ($file['size'] ?? 0),
        (string) ($file['name'] ?? ''),
        $errors,
        $hasManagerUploadSizeError,
        $configAssetUploadMaxBytes,
        $configAssetUploadMaxLabel,
        true
    )) {
        return null;
    }

    $allowedExt = $allowed ?? ['png', 'jpg', 'jpeg', 'gif', 'webp', 'svg', 'ico'];
    $ext = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExt, true)) {
        $errors[] = "Formato non supportato per {$inputName}. Estensioni ammesse: " . implode(', ', $allowedExt) . '.';

        return null;
    }

    $finalName = concorso_config_upload_resolve_final_name($targetName, $nameResolver, $inputName, $ext, $targetDir, $file);

    return concorso_config_upload_move_file(
        $file,
        $targetDir,
        $finalName,
        $targetBase,
        $inputName,
        $errors,
        'Impossibile creare la cartella upload configurazioni.'
    );
}

function concorso_handle_indexed_upload(
    ?array $file,
    string $targetDir,
    string $targetBase,
    array $allowedExt,
    string $prefix,
    array &$errors,
    bool &$hasManagerUploadSizeError,
    int $configAssetUploadMaxBytes,
    string $configAssetUploadMaxLabel
): ?string {
    if ($file === null) {
        return null;
    }
    $errorCode = $file['error'] ?? UPLOAD_ERR_NO_FILE;
    if ($errorCode === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($errorCode !== UPLOAD_ERR_OK) {
        concorso_config_upload_handle_error_code(
            $errorCode,
            $prefix,
            $errors,
            $hasManagerUploadSizeError,
            $configAssetUploadMaxLabel,
            false
        );

        return null;
    }

    if (!concorso_config_upload_validate_file_size(
        (int) ($file['size'] ?? 0),
        (string) ($file['name'] ?? ''),
        $errors,
        $hasManagerUploadSizeError,
        $configAssetUploadMaxBytes,
        $configAssetUploadMaxLabel,
        false
    )) {
        return null;
    }

    $ext = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExt, true)) {
        $errors[] = "Formato non supportato per {$prefix}. Estensioni ammesse: " . implode(', ', $allowedExt) . '.';

        return null;
    }

    $finalName = $prefix . '_' . uniqid('', true) . '.' . $ext;

    return concorso_config_upload_move_file(
        $file,
        $targetDir,
        $finalName,
        $targetBase,
        $prefix,
        $errors,
        'Impossibile creare la cartella upload waiting page.'
    );
}

function concorso_extract_waiting_container_images(array $container): array
{
    $paths = [];
    $background = $container['background'] ?? [];
    if (is_array($background)) {
        $bgImage = trim((string) ($background['image'] ?? ''));
        if ($bgImage !== '') {
            $paths[] = $bgImage;
        }
    }
    $blocks = $container['blocks'] ?? [];
    if (is_array($blocks)) {
        foreach ($blocks as $block) {
            if (!is_array($block)) {
                continue;
            }
            if (($block['type'] ?? '') !== 'image') {
                continue;
            }
            $src = trim((string) ($block['src'] ?? ''));
            if ($src !== '') {
                $paths[] = $src;
            }
        }
    }

    return $paths;
}

function concorso_delete_config_image_is_protected_path(string $normalizedPath): bool
{
    if (strpos($normalizedPath, '/default_img/') !== false) {
        return true;
    }
    $defaultPdfNames = ['privacy_concorso_generico.pdf', 'regolamento_concorso_generico.pdf'];
    foreach ($defaultPdfNames as $defaultPdfName) {
        if (substr($normalizedPath, -strlen($defaultPdfName)) === $defaultPdfName) {
            return true;
        }
    }

    return false;
}

/**
 * @return array{0: ?string, 1: string}|null
 */
function concorso_delete_config_image_resolve_base(string $relative): ?array
{
    if (strpos($relative, CONCORSO_UPLOADS_CONFIG) === 0) {
        return ['uploads/config', substr($relative, strlen(CONCORSO_UPLOADS_CONFIG))];
    }
    if (strpos($relative, 'assets/') === 0) {
        if (strpos($relative, CONCORSO_ASSETS_PDF) === 0) {
            return ['assets/pdf', substr($relative, strlen(CONCORSO_ASSETS_PDF))];
        }

        return ['assets', substr($relative, strlen('assets/'))];
    }

    return null;
}

function concorso_delete_config_image_unlink_resolved(string $base, string $relative, string $baseDir, string $uploadDir, string $pdfUploadDir): void
{
    if ($base === 'uploads/config') {
        $basePath = rtrim($uploadDir, DIRECTORY_SEPARATOR);
    } elseif ($base === 'assets/pdf') {
        $basePath = rtrim($pdfUploadDir, DIRECTORY_SEPARATOR);
    } else {
        $basePath = rtrim($baseDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'assets';
    }

    $relative = ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relative), DIRECTORY_SEPARATOR);
    if ($relative === '' || strpos($relative, '..') !== false || preg_match(CONCORSO_REGEX_WIN_DRIVE, $relative)) {
        return;
    }

    $fullPath = $basePath . DIRECTORY_SEPARATOR . $relative;
    $baseReal = realpath($basePath);
    if ($baseReal === false) {
        return;
    }
    $fileReal = realpath($fullPath);
    if ($fileReal === false) {
        if (is_file($fullPath)) {
            @unlink($fullPath);
        }

        return;
    }
    $baseReal = rtrim($baseReal, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    if (strpos($fileReal, $baseReal) !== 0) {
        return;
    }
    @unlink($fileReal);
}

function concorso_delete_config_image(string $path, string $baseDir, string $uploadDir, string $pdfUploadDir): void
{
    $path = trim($path);
    if ($path === '' || strpos($path, '://') !== false || strpos($path, '//') === 0) {
        return;
    }
    $normalizedPath = str_replace('\\', '/', $path);
    if (concorso_delete_config_image_is_protected_path($normalizedPath)) {
        return;
    }
    $pathOnly = parse_url($path, PHP_URL_PATH);
    if (!is_string($pathOnly)) {
        return;
    }
    $ext = strtolower(pathinfo($pathOnly, PATHINFO_EXTENSION));
    $allowed = ['png', 'jpg', 'jpeg', 'gif', 'webp', 'svg', 'ico', 'pdf'];
    if ($ext === '' || !in_array($ext, $allowed, true)) {
        return;
    }
    $relative = ltrim(str_replace('\\', '/', $pathOnly), '/');
    $resolved = concorso_delete_config_image_resolve_base($relative);
    if ($resolved === null) {
        return;
    }

    concorso_delete_config_image_unlink_resolved($resolved[0], $resolved[1], $baseDir, $uploadDir, $pdfUploadDir);
}

function concorso_collect_config_add_path(array &$paths, $value): void
{
    $value = trim((string) $value);
    if ($value !== '') {
        $paths[$value] = true;
    }
}

function concorso_collect_config_top_level_images(array $config, array &$paths): void
{
    foreach (['logo', 'logo_mobile', 'header_image', 'header_image_mobile', 'favicon', 'favicon_mobile', 'mail_banner', 'privacy_link', 'rules_link'] as $key) {
        if (isset($config[$key])) {
            concorso_collect_config_add_path($paths, $config[$key]);
        }
    }
}

function concorso_collect_config_backgrounds(array $config, array &$paths): void
{
    if (!isset($config['backgrounds']) || !is_array($config['backgrounds'])) {
        return;
    }
    foreach ($config['backgrounds'] as $value) {
        concorso_collect_config_add_path($paths, $value);
    }
}

function concorso_collect_config_form_and_public_containers(array $config, array &$paths): void
{
    if (isset($config['form_container']['image'])) {
        concorso_collect_config_add_path($paths, $config['form_container']['image']);
    }
    if (!isset($config['public_page_containers']) || !is_array($config['public_page_containers'])) {
        return;
    }
    foreach ($config['public_page_containers'] as $pageContainerStyle) {
        if (is_array($pageContainerStyle) && isset($pageContainerStyle['image'])) {
            concorso_collect_config_add_path($paths, $pageContainerStyle['image']);
        }
    }
}

function concorso_collect_config_footer_images(array $config, array &$paths): void
{
    if (isset($config['footer_style']['image'])) {
        concorso_collect_config_add_path($paths, $config['footer_style']['image']);
    }
    if (isset($config['footer_layout']['logo_image'])) {
        concorso_collect_config_add_path($paths, $config['footer_layout']['logo_image']);
    }
}

function concorso_collect_config_button_images(array $config, array &$paths): void
{
    if (!isset($config['buttons']) || !is_array($config['buttons'])) {
        return;
    }
    foreach (['primary', 'secondary'] as $buttonKey) {
        if (!isset($config['buttons'][$buttonKey]) || !is_array($config['buttons'][$buttonKey])) {
            continue;
        }
        if (isset($config['buttons'][$buttonKey]['image'])) {
            concorso_collect_config_add_path($paths, $config['buttons'][$buttonKey]['image']);
        }
        if (isset($config['buttons'][$buttonKey]['hover_image'])) {
            concorso_collect_config_add_path($paths, $config['buttons'][$buttonKey]['hover_image']);
        }
    }
}

function concorso_collect_page_container_images_from_page(array $page, array &$paths): void
{
    if (!isset($page['containers']) || !is_array($page['containers'])) {
        return;
    }
    foreach ($page['containers'] as $container) {
        if (!is_array($container)) {
            continue;
        }
        foreach (concorso_extract_waiting_container_images($container) as $path) {
            concorso_collect_config_add_path($paths, $path);
        }
    }
}

function concorso_collect_config_page_container_images(array $config, array &$paths): void
{
    if (!isset($config['pages']) || !is_array($config['pages'])) {
        return;
    }
    foreach ($config['pages'] as $page) {
        if (!is_array($page)) {
            continue;
        }
        concorso_collect_page_container_images_from_page($page, $paths);
    }
}

function concorso_collect_config_images(array $config): array
{
    $paths = [];
    concorso_collect_config_top_level_images($config, $paths);
    concorso_collect_config_backgrounds($config, $paths);
    concorso_collect_config_form_and_public_containers($config, $paths);
    concorso_collect_config_footer_images($config, $paths);
    concorso_collect_config_button_images($config, $paths);
    concorso_collect_config_page_container_images($config, $paths);

    return $paths;
}

/**
 * @return array{q: string, a: string, order: int|null}|null
 */
function concorso_normalize_faq_item($item): ?array
{
    if (!is_array($item)) {
        return null;
    }
    $q = trim((string) ($item['q'] ?? ''));
    $a = trim((string) ($item['a'] ?? ''));
    if ($q === '' && $a === '') {
        return null;
    }
    $order_raw = $item['order'] ?? null;
    $order = ($order_raw === null || $order_raw === '') ? null : (int) $order_raw;

    return [
        'q' => $q,
        'a' => $a,
        'order' => $order,
    ];
}

function concorso_compare_faq_order(array $a, array $b): int
{
    $oa = isset($a['order']) && $a['order'] !== null ? (int) $a['order'] : PHP_INT_MAX;
    $ob = isset($b['order']) && $b['order'] !== null ? (int) $b['order'] : PHP_INT_MAX;
    if ($oa === $ob) {
        return 0;
    }

    return $oa < $ob ? -1 : 1;
}

function concorso_normalize_faq_reindex_orders(array &$new_faq): void
{
    usort($new_faq, 'concorso_compare_faq_order');
    foreach ($new_faq as $idx => &$faqItem) {
        $faqItem['order'] = $idx + 1;
    }
    unset($faqItem);
}

function concorso_normalize_faq_list(array $items): array
{
    $new_faq = [];
    foreach ($items as $item) {
        $normalized = concorso_normalize_faq_item($item);
        if ($normalized !== null) {
            $new_faq[] = $normalized;
        }
    }
    if (!empty($new_faq)) {
        concorso_normalize_faq_reindex_orders($new_faq);
    }

    return $new_faq;
}

function concorso_process_config_page_resolve_align(
    bool $page_present,
    string $post_align_key,
    string $page_key,
    array $pages_config,
    array $content_defaults,
    array $allowedWaitingAlign
): string
{
    $page_align = $page_present ? strtolower(trim((string) ($_POST[$post_align_key] ?? ''))) : '';
    if (!in_array($page_align, $allowedWaitingAlign, true)) {
        $page_align = $pages_config[$page_key]['align'] ?? ($content_defaults['pages'][$page_key]['align'] ?? 'center');
    }

    return $page_align;
}

function concorso_process_config_page_resolve_title(
    bool $preserve_title,
    string $page_key,
    callable $postText,
    array $pages_config,
    array $content_defaults
): ?string
{
    if (!$preserve_title) {
        return null;
    }
    $post_title_key = $page_key . '_title';
    $page_title = $postText($post_title_key, '');
    if ($page_title === '') {
        return $pages_config[$page_key]['title'] ?? ($content_defaults['pages'][$page_key]['title'] ?? CONCORSO_THANKYOU_TITLE);
    }

    return trim($page_title);
}

function concorso_process_config_page_get_posted_containers(bool $page_containers_provided, string $post_containers_key): array
{
    if (!$page_containers_provided || !isset($_POST[$post_containers_key])) {
        return [];
    }
    $posted_containers = $_POST[$post_containers_key];
    if (!is_array($posted_containers)) {
        return [];
    }

    return $posted_containers;
}

function concorso_process_config_page_existing_containers_map(string $page_key, array $pages_config): array
{
    $existing_containers = [];
    if (!isset($pages_config[$page_key]['containers']) || !is_array($pages_config[$page_key]['containers'])) {
        return $existing_containers;
    }
    foreach ($pages_config[$page_key]['containers'] as $container) {
        if (!is_array($container)) {
            continue;
        }
        $container_id = trim((string) ($container['id'] ?? ''));
        if ($container_id !== '') {
            $existing_containers[$container_id] = $container;
        }
    }

    return $existing_containers;
}

/**
 * @return array{
 *     default_padding: array<string, int>,
 *     default_margin: array<string, int>,
 *     default_radius: array<string, int>,
 *     default_style: array<string, mixed>,
 *     default_background: array<string, mixed>,
 * }
 */
function concorso_process_config_page_default_container_defs(string $page_align): array
{
    $default_padding = ['top' => 20, 'right' => 20, 'bottom' => 20, 'left' => 20];
    $default_margin = ['top' => 0, 'right' => 0, 'bottom' => 16, 'left' => 0];
    $default_radius = ['top' => 8, 'right' => 8, 'bottom' => 8, 'left' => 8];

    return [
        'default_padding' => $default_padding,
        'default_margin' => $default_margin,
        'default_radius' => $default_radius,
        'default_style' => [
            'width' => 'auto',
            'padding' => $default_padding,
            'margin' => $default_margin,
            'radius' => $default_radius,
            'align' => $page_align,
            'visible' => ['desktop' => true, 'mobile' => true],
        ],
        'default_background' => [
            'mode' => 'color',
            'color' => CONCORSO_COLOR_WHITE,
            'image' => '',
            'opacity' => 100,
        ],
    ];
}

function concorso_process_config_container_resolve_align(string $container_align, string $page_align, array $allowedWaitingAlign): string
{
    $container_align = strtolower(trim($container_align));
    if (!in_array($container_align, $allowedWaitingAlign, true)) {
        return $page_align;
    }

    return $container_align;
}

function concorso_process_config_container_background(
    array $base_container,
    array $containerData,
    int $containerIndex,
    string $post_bg_upload_key,
    string $upload_prefix,
    string $assetUploadDir,
    string $assetBasePath,
    array $default_background,
    array &$errors,
    bool &$hasManagerUploadSizeError,
    int $configAssetUploadMaxBytes,
    string $configAssetUploadMaxLabel
): array
{
    $background = $base_container['background'] ?? [];
    if (!is_array($background)) {
        $background = [];
    }
    $background_input = $containerData['background'] ?? [];
    if (!is_array($background_input)) {
        $background_input = [];
    }
    $background = array_merge($default_background, $background, $background_input);
    $background_upload = concorso_get_indexed_upload($post_bg_upload_key, $containerIndex);
    $background_upload_path = concorso_handle_indexed_upload(
        $background_upload,
        $assetUploadDir,
        $assetBasePath,
        ['png', 'jpg', 'jpeg'],
        $upload_prefix . '_bg',
        $errors,
        $hasManagerUploadSizeError,
        $configAssetUploadMaxBytes,
        $configAssetUploadMaxLabel
    );
    $background_mode = strtolower(trim((string) ($background['mode'] ?? 'color')));
    if (!in_array($background_mode, ['transparent', 'color', 'image'], true)) {
        $background_mode = 'color';
    }
    $background_color = trim((string) ($background['color'] ?? CONCORSO_COLOR_WHITE));
    $background_image = trim((string) ($background['image'] ?? ''));
    if ($background_upload_path !== null) {
        $background_image = $background_upload_path;
    }
    $background_opacity = max(0, min(100, (int) ($background['opacity'] ?? 100)));
    if ($background_mode === 'transparent') {
        $background_mode = 'color';
        $background_opacity = 0;
    }
    if ($background_mode !== 'image') {
        $background_image = '';
    }

    return [
        'mode' => $background_mode,
        'color' => $background_color,
        'image' => $background_image,
        'opacity' => $background_opacity,
    ];
}

function concorso_process_config_container_normalize_box(array $box, array $default_box): array
{
    if (!is_array($box)) {
        $box = [];
    }
    $box = array_merge($default_box, $box);
    foreach (['top', 'right', 'bottom', 'left'] as $side) {
        $box[$side] = max(0, (int) ($box[$side] ?? 0));
    }

    return $box;
}

function concorso_process_config_container_style(
    array $base_container,
    array $containerData,
    string $container_align,
    array $default_defs
): array
{
    $style = $base_container['style'] ?? [];
    if (!is_array($style)) {
        $style = [];
    }
    $padding = $style['padding'] ?? [];
    if (!is_array($padding)) {
        $padding = [];
    }
    $margin = $style['margin'] ?? [];
    if (!is_array($margin)) {
        $margin = [];
    }
    $radius = $style['radius'] ?? [];
    if (!is_array($radius)) {
        $radius = [];
    }
    $margin_input = $containerData['style']['margin'] ?? [];
    if (!is_array($margin_input)) {
        $margin_input = [];
    }
    $radius_input = $containerData['style']['radius'] ?? [];
    if (!is_array($radius_input)) {
        $radius_input = [];
    }
    $visible = $style['visible'] ?? [];
    if (!is_array($visible)) {
        $visible = [];
    }
    $style = array_merge($default_defs['default_style'], $style);
    $style['padding'] = array_merge($default_defs['default_padding'], $padding);
    $style['margin'] = concorso_process_config_container_normalize_box(
        array_merge($default_defs['default_margin'], $margin, $margin_input),
        $default_defs['default_margin']
    );
    $style['radius'] = concorso_process_config_container_normalize_box(
        array_merge($default_defs['default_radius'], $radius, $radius_input),
        $default_defs['default_radius']
    );
    $style['visible'] = array_merge(['desktop' => true, 'mobile' => true], $visible);
    $style['align'] = $container_align;
    if (isset($style['border'])) {
        unset($style['border']);
    }
    if (isset($style['text_color'])) {
        unset($style['text_color']);
    }

    return $style;
}

/**
 * @return array{content_type: string, blocks: array<int, array<string, mixed>>, image_src: string, image_alt: string, image_width: string, html_content: string}
 */
function concorso_process_config_container_blocks(
    array $containerData,
    int $containerIndex,
    string $post_image_upload_key,
    string $upload_prefix,
    string $assetUploadDir,
    string $assetBasePath,
    callable $maybe_decode_waiting_html,
    array &$errors,
    bool &$hasManagerUploadSizeError,
    int $configAssetUploadMaxBytes,
    string $configAssetUploadMaxLabel
): array
{
    $content_type = strtolower(trim((string) ($containerData['content_type'] ?? 'text')));
    if (!in_array($content_type, ['text', 'image'], true)) {
        $content_type = 'text';
    }

    $blocks = [];
    $image_data = $containerData['image'] ?? [];
    if (!is_array($image_data)) {
        $image_data = [];
    }
    $image_src = trim((string) ($image_data['src'] ?? ''));
    $image_alt = trim((string) ($image_data['alt'] ?? ''));
    $image_width = trim((string) ($image_data['width'] ?? ''));
    $image_upload = concorso_get_indexed_upload($post_image_upload_key, $containerIndex);
    $image_upload_path = concorso_handle_indexed_upload(
        $image_upload,
        $assetUploadDir,
        $assetBasePath,
        ['png', 'jpg', 'jpeg'],
        $upload_prefix . '_image',
        $errors,
        $hasManagerUploadSizeError,
        $configAssetUploadMaxBytes,
        $configAssetUploadMaxLabel
    );
    if ($image_upload_path !== null) {
        $image_src = $image_upload_path;
    }
    if ($image_src !== '') {
        $content_type = 'image';
    }

    $html_content = '';
    if ($content_type === 'image') {
        $blocks[] = [
            'id' => 'block-' . uniqid(),
            'type' => 'image',
            'order' => 1,
            'src' => $image_src,
            'alt' => $image_alt,
            'width' => $image_width,
        ];
    } else {
        $html_content = trim((string) ($containerData['html'] ?? ''));
        $html_content = $maybe_decode_waiting_html($html_content);
        if ($html_content !== '') {
            $blocks[] = [
                'id' => 'block-' . uniqid(),
                'type' => 'text',
                'order' => 1,
                'content' => $html_content,
            ];
        }
    }

    return [
        'content_type' => $content_type,
        'blocks' => $blocks,
        'image_src' => $image_src,
        'image_alt' => $image_alt,
        'image_width' => $image_width,
        'html_content' => $html_content,
    ];
}

function concorso_process_config_page_build_container(
    array $containerData,
    int $containerIndex,
    string $page_align,
    array $existing_containers,
    array $default_container_template,
    array $default_defs,
    array $allowedWaitingAlign,
    string $post_bg_upload_key,
    string $post_image_upload_key,
    string $upload_prefix,
    string $assetUploadDir,
    string $assetBasePath,
    callable $maybe_decode_waiting_html,
    array &$errors,
    bool &$hasManagerUploadSizeError,
    int $configAssetUploadMaxBytes,
    string $configAssetUploadMaxLabel
): ?array
{
    if (!is_array($containerData)) {
        return null;
    }
    $container_id = preg_replace('/[^a-z0-9_-]/i', '', (string) ($containerData['id'] ?? ''));
    if ($container_id === '') {
        $container_id = 'container_' . uniqid();
    }
    $container_align = concorso_process_config_container_resolve_align(
        (string) ($containerData['align'] ?? $page_align),
        $page_align,
        $allowedWaitingAlign
    );

    $base_container = $existing_containers[$container_id] ?? $default_container_template;
    $background = concorso_process_config_container_background(
        $base_container,
        $containerData,
        $containerIndex,
        $post_bg_upload_key,
        $upload_prefix,
        $assetUploadDir,
        $assetBasePath,
        $default_defs['default_background'],
        $errors,
        $hasManagerUploadSizeError,
        $configAssetUploadMaxBytes,
        $configAssetUploadMaxLabel
    );
    $style = concorso_process_config_container_style($base_container, $containerData, $container_align, $default_defs);
    $block_result = concorso_process_config_container_blocks(
        $containerData,
        $containerIndex,
        $post_image_upload_key,
        $upload_prefix,
        $assetUploadDir,
        $assetBasePath,
        $maybe_decode_waiting_html,
        $errors,
        $hasManagerUploadSizeError,
        $configAssetUploadMaxBytes,
        $configAssetUploadMaxLabel
    );

    return [
        'id' => $container_id,
        'background' => $background,
        'style' => $style,
        'content_type' => $block_result['content_type'],
        'blocks' => $block_result['blocks'],
        'image_src' => $block_result['image_src'],
        'image_alt' => $block_result['image_alt'],
        'image_width' => $block_result['image_width'],
        'html_content' => $block_result['html_content'],
    ];
}

function concorso_process_config_page_build_containers(
    array $posted_containers,
    string $page_align,
    array $existing_containers,
    array $default_container_template,
    array $default_defs,
    array $allowedWaitingAlign,
    string $post_bg_upload_key,
    string $post_image_upload_key,
    string $upload_prefix,
    string $assetUploadDir,
    string $assetBasePath,
    callable $maybe_decode_waiting_html,
    array &$errors,
    bool &$hasManagerUploadSizeError,
    int $configAssetUploadMaxBytes,
    string $configAssetUploadMaxLabel
): array
{
    $page_containers = [];
    $order = 1;
    foreach ($posted_containers as $containerIndex => $containerData) {
        $built = concorso_process_config_page_build_container(
            $containerData,
            $containerIndex,
            $page_align,
            $existing_containers,
            $default_container_template,
            $default_defs,
            $allowedWaitingAlign,
            $post_bg_upload_key,
            $post_image_upload_key,
            $upload_prefix,
            $assetUploadDir,
            $assetBasePath,
            $maybe_decode_waiting_html,
            $errors,
            $hasManagerUploadSizeError,
            $configAssetUploadMaxBytes,
            $configAssetUploadMaxLabel
        );
        if ($built === null) {
            continue;
        }
        $page_containers[] = [
            'id' => $built['id'],
            'order' => $order++,
            'background' => $built['background'],
            'style' => $built['style'],
            'content_type' => $built['content_type'],
            'blocks' => $built['blocks'],
            'image' => $built['content_type'] === 'image'
                ? ['src' => $built['image_src'], 'alt' => $built['image_alt'], 'width' => $built['image_width']]
                : null,
            'html' => $built['content_type'] === 'text' ? $built['html_content'] : null,
        ];
    }

    return $page_containers;
}

function concorso_process_config_page_collect_used_images(array $page_containers, array &$used_waiting_images): void
{
    foreach ($page_containers as $saved_container) {
        if (!is_array($saved_container)) {
            continue;
        }
        $container_background = $saved_container['background'] ?? [];
        if (is_array($container_background) && !empty($container_background['image'])) {
            $used_waiting_images[] = $container_background['image'];
        }
        $container_image = $saved_container['image'] ?? null;
        if (is_array($container_image) && !empty($container_image['src'])) {
            $used_waiting_images[] = $container_image['src'];
        }
    }
}

function concorso_process_config_page_handle_legacy_html(
    bool $page_present,
    bool $page_containers_provided,
    string $page_key,
    array &$pages_config,
    callable $maybe_decode_waiting_html
): void
{
    if (!$page_present || $page_containers_provided) {
        return;
    }
    if (!array_key_exists($page_key . '_html', $_POST)) {
        return;
    }
    $page_html = trim((string) ($_POST[$page_key . '_html'] ?? ''));
    $page_html = $maybe_decode_waiting_html($page_html);
    if (!isset($pages_config[$page_key]) || !is_array($pages_config[$page_key])) {
        $pages_config[$page_key] = [];
    }
    if ($page_html === '') {
        unset($pages_config[$page_key]['html']);
    } else {
        $pages_config[$page_key]['html'] = $page_html;
    }
}

function concorso_process_config_page_merge_results(
    bool $page_present,
    bool $page_containers_provided,
    string $page_key,
    array $page_containers,
    string $page_align,
    ?string $page_title,
    bool $preserve_title,
    array &$pages_config
): string
{
    if (!$page_present) {
        return $page_align;
    }
    if (!isset($pages_config[$page_key])) {
        $pages_config[$page_key] = [];
    }
    if ($page_containers_provided) {
        $pages_config[$page_key]['containers'] = $page_containers;
        if (isset($pages_config[$page_key]['html'])) {
            unset($pages_config[$page_key]['html']);
        }
        if (!empty($page_containers[0]['style']['align'])) {
            $page_align = $page_containers[0]['style']['align'];
        }
    }
    $pages_config[$page_key]['align'] = $page_align;
    if ($preserve_title && $page_title !== null) {
        $pages_config[$page_key]['title'] = $page_title;
    }

    return $page_align;
}

function concorso_process_config_page_containers(
    string $page_key,
    string $post_present_key,
    string $post_align_key,
    string $post_containers_key,
    string $post_bg_upload_key,
    string $post_image_upload_key,
    string $upload_prefix,
    string $assetUploadDir,
    string $assetBasePath,
    array &$pages_config,
    array $content_defaults,
    array $allowedWaitingAlign,
    array &$used_waiting_images,
    callable $postText,
    callable $maybe_decode_waiting_html,
    array &$errors,
    bool &$hasManagerUploadSizeError,
    int $configAssetUploadMaxBytes,
    string $configAssetUploadMaxLabel,
    bool $preserve_title = false
): array {
    $page_present = isset($_POST[$post_present_key]);
    $page_align = concorso_process_config_page_resolve_align(
        $page_present,
        $post_align_key,
        $page_key,
        $pages_config,
        $content_defaults,
        $allowedWaitingAlign
    );
    $page_title = concorso_process_config_page_resolve_title(
        $preserve_title,
        $page_key,
        $postText,
        $pages_config,
        $content_defaults
    );

    $page_containers = [];
    $page_containers_provided = $page_present && isset($_POST[$post_containers_key . '_present']);
    $posted_containers = concorso_process_config_page_get_posted_containers($page_containers_provided, $post_containers_key);

    if ($page_containers_provided) {
        $existing_containers = concorso_process_config_page_existing_containers_map($page_key, $pages_config);
        $default_container_template = [];
        if (isset($content_defaults['pages'][$page_key]['containers'][0]) && is_array($content_defaults['pages'][$page_key]['containers'][0])) {
            $default_container_template = $content_defaults['pages'][$page_key]['containers'][0];
        }
        $default_defs = concorso_process_config_page_default_container_defs($page_align);
        $page_containers = concorso_process_config_page_build_containers(
            $posted_containers,
            $page_align,
            $existing_containers,
            $default_container_template,
            $default_defs,
            $allowedWaitingAlign,
            $post_bg_upload_key,
            $post_image_upload_key,
            $upload_prefix,
            $assetUploadDir,
            $assetBasePath,
            $maybe_decode_waiting_html,
            $errors,
            $hasManagerUploadSizeError,
            $configAssetUploadMaxBytes,
            $configAssetUploadMaxLabel
        );
        concorso_process_config_page_collect_used_images($page_containers, $used_waiting_images);
    }

    if ($page_present && !$page_containers_provided) {
        if (isset($pages_config[$page_key]['containers']) && is_array($pages_config[$page_key]['containers'])) {
            $page_containers = $pages_config[$page_key]['containers'];
        }
        concorso_process_config_page_handle_legacy_html(
            $page_present,
            $page_containers_provided,
            $page_key,
            $pages_config,
            $maybe_decode_waiting_html
        );
    }

    $page_align = concorso_process_config_page_merge_results(
        $page_present,
        $page_containers_provided,
        $page_key,
        $page_containers,
        $page_align,
        $page_title,
        $preserve_title,
        $pages_config
    );

    return ['containers' => $page_containers, 'align' => $page_align, 'title' => $page_title];
}

function concorso_apply_posted_page_button_footer(string $page_key, array &$pages_config): void
{
    $textPostKey = $page_key . '_button_footer_text';
    $colorPostKey = $page_key . '_button_footer_text_color';
    $fontPostKey = $page_key . '_button_footer_text_font';
    if (array_key_exists($textPostKey, $_POST)) {
        $text = (string) $_POST[$textPostKey];
        if (!isset($pages_config[$page_key]) || !is_array($pages_config[$page_key])) {
            $pages_config[$page_key] = [];
        }
        if (trim($text) === '') {
            unset($pages_config[$page_key]['button_footer_text']);
        } else {
            $pages_config[$page_key]['button_footer_text'] = $text;
        }
    }
    if (array_key_exists($colorPostKey, $_POST)) {
        $colorRaw = trim((string) $_POST[$colorPostKey]);
        if (!isset($pages_config[$page_key]) || !is_array($pages_config[$page_key])) {
            $pages_config[$page_key] = [];
        }
        if ($colorRaw !== '' && preg_match(CONCORSO_REGEX_HEX6, $colorRaw)) {
            $pages_config[$page_key]['button_footer_text_color'] = strtolower($colorRaw);
        } else {
            unset($pages_config[$page_key]['button_footer_text_color']);
        }
    }
    if (array_key_exists($fontPostKey, $_POST)) {
        $fontRaw = trim((string) $_POST[$fontPostKey]);
        if (!isset($pages_config[$page_key]) || !is_array($pages_config[$page_key])) {
            $pages_config[$page_key] = [];
        }
        if ($fontRaw === '') {
            unset($pages_config[$page_key]['button_footer_text_font']);
        } else {
            $pages_config[$page_key]['button_footer_text_font'] = $fontRaw;
        }
    }
}
