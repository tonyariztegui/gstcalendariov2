<?php
/**
 * Template immagini premio (content.json) + generazione GD con codice su codici_premio.
 * File generati: uploads/premi/generated/cp_{id}.png (nessun path in MySQL).
 */

if (!function_exists('prize_image_project_root')) {
    function prize_image_project_root(): string
    {
        return rtrim(dirname(__DIR__, 2), DIRECTORY_SEPARATOR . '/\\');
    }
}

if (!function_exists('prize_email_template_key')) {
    function prize_email_template_key($valore, string $tipoPremio): string
    {
        $val = is_numeric($valore) ? number_format((float) $valore, 2, '.', '') : '0.00';
        $tipo = trim($tipoPremio);
        if ($tipo === '') {
            $tipo = 'Premio';
        }

        return $val . '|' . $tipo;
    }
}

if (!function_exists('prize_email_templates_from_config')) {
    function prize_email_templates_from_config(?array $config = null): array
    {
        global $content_config, $contest_config;
        if ($config === null) {
            $config = is_array($content_config ?? null) ? $content_config : (is_array($contest_config ?? null) ? $contest_config : []);
        }
        $templates = $config['prize_email_templates'] ?? [];
        if ((!is_array($templates) || $templates === []) && is_array($content_config ?? null)) {
            $fromContent = $content_config['prize_email_templates'] ?? [];
            if (is_array($fromContent) && $fromContent !== []) {
                $templates = $fromContent;
            }
        }

        return is_array($templates) ? $templates : [];
    }
}

if (!function_exists('prize_email_normalize_overlay_config')) {
    function prize_email_normalize_overlay_config($overlay): array
    {
        $overlay = is_array($overlay) ? $overlay : [];
        $enabled = filter_var($overlay['enabled'] ?? false, FILTER_VALIDATE_BOOLEAN);

        return [
            'enabled' => $enabled,
            'x' => max(0, min(5000, (int) ($overlay['x'] ?? 200))),
            'y' => max(0, min(5000, (int) ($overlay['y'] ?? 350))),
            'font_size' => max(8, min(120, (int) ($overlay['font_size'] ?? 24))),
            'color' => prize_email_normalize_hex_color((string) ($overlay['color'] ?? '#000000'), '#000000'),
        ];
    }
}

if (!function_exists('prize_email_normalize_hex_color')) {
    function prize_email_normalize_hex_color(string $color, string $fallback = '#000000'): string
    {
        $color = trim($color);
        if (preg_match('/^#[0-9A-Fa-f]{6}$/', $color)) {
            return $color;
        }
        if (preg_match('/^#[0-9A-Fa-f]{3}$/', $color)) {
            return '#' . $color[1] . $color[1] . $color[2] . $color[2] . $color[3] . $color[3];
        }

        return $fallback;
    }
}

if (!function_exists('prize_email_normalize_voucher_code_display')) {
    function prize_email_normalize_voucher_code_display($value): string
    {
        $mode = strtolower(trim((string) $value));

        return in_array($mode, ['inherit', 'text', 'barcode', 'both'], true) ? $mode : 'inherit';
    }
}

if (!function_exists('prize_email_normalize_email_config')) {
    function prize_email_normalize_email_config($email): array
    {
        $email = is_array($email) ? $email : [];
        $enabled = filter_var($email['custom_body_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $body = trim((string) ($email['body'] ?? ''));

        return [
            'custom_body_enabled' => $enabled,
            'body' => $body,
            'voucher_code_display' => prize_email_normalize_voucher_code_display($email['voucher_code_display'] ?? 'inherit'),
        ];
    }
}

if (!function_exists('prize_email_normalize_template_entry')) {
    function prize_email_normalize_template_entry($entry): array
    {
        $entry = is_array($entry) ? $entry : [];
        $path = trim((string) ($entry['template_path'] ?? ''));

        return [
            'template_path' => $path,
            'overlay' => prize_email_normalize_overlay_config($entry['overlay'] ?? []),
            'email' => prize_email_normalize_email_config($entry['email'] ?? []),
        ];
    }
}

if (!function_exists('prize_email_template_entry_for_key')) {
    function prize_email_template_entry_for_key(string $key, ?array $config = null): array
    {
        $key = trim($key);
        if ($key === '') {
            return prize_email_normalize_template_entry([]);
        }
        $templates = prize_email_templates_from_config($config);
        $entry = $templates[$key] ?? [];

        return prize_email_normalize_template_entry(is_array($entry) ? $entry : []);
    }
}

if (!function_exists('prize_email_template_entry_for_prize')) {
    function prize_email_template_entry_for_prize(PDO $pdo, int $prizeId, ?array $config = null): array
    {
        if ($prizeId <= 0) {
            return prize_email_normalize_template_entry([]);
        }
        $stmt = $pdo->prepare('SELECT valore, tipo_premio FROM premi WHERE id = ? LIMIT 1');
        $stmt->execute([$prizeId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!is_array($row)) {
            return prize_email_normalize_template_entry([]);
        }
        $key = prize_email_template_key($row['valore'] ?? 0, (string) ($row['tipo_premio'] ?? ''));

        return prize_email_template_entry_for_key($key, $config);
    }
}

if (!function_exists('prize_email_resolve_body_for_prize')) {
    /**
     * Corps HTML email premio : global ou override par tipo premio.
     */
    function prize_email_resolve_body_for_prize(?PDO $pdo, int $prizeId, ?array $config = null, ?string $globalBodyFallback = null): string
    {
        $globalBody = $globalBodyFallback;
        if ($globalBody === null && function_exists('get_email_templates_config')) {
            $templates = get_email_templates_config();
            $globalBody = (string) (($templates['instantwin_prize'] ?? [])['body'] ?? '');
        }
        $globalBody = trim((string) $globalBody);

        if ($pdo instanceof PDO && $prizeId > 0) {
            $entry = prize_email_template_entry_for_prize($pdo, $prizeId, $config);
            $emailCfg = $entry['email'] ?? prize_email_normalize_email_config([]);
            if (!empty($emailCfg['custom_body_enabled']) && trim((string) ($emailCfg['body'] ?? '')) !== '') {
                return (string) $emailCfg['body'];
            }
        }

        return $globalBody;
    }
}

if (!function_exists('prize_email_resolve_voucher_display_mode')) {
    /**
     * Modalità effettiva per {{VOUCHER_CODE_BLOCK}} (override premio o globale).
     */
    function prize_email_resolve_voucher_display_mode(?PDO $pdo, int $prizeId, ?array $config = null): string
    {
        if ($pdo instanceof PDO && $prizeId > 0) {
            $entry = prize_email_template_entry_for_prize($pdo, $prizeId, $config);
            $emailCfg = $entry['email'] ?? prize_email_normalize_email_config([]);
            $perPrize = (string) ($emailCfg['voucher_code_display'] ?? 'inherit');
            if ($perPrize !== 'inherit' && function_exists('get_email_voucher_code_display_mode')) {
                return get_email_voucher_code_display_mode($perPrize);
            }
        }

        return function_exists('get_email_voucher_code_display_mode')
            ? get_email_voucher_code_display_mode()
            : 'text';
    }
}

if (!function_exists('prize_email_resolve_voucher_display_mode_for_key')) {
    function prize_email_resolve_voucher_display_mode_for_key(string $key, ?array $config = null): string
    {
        $key = trim($key);
        if ($key !== '') {
            $entry = prize_email_template_entry_for_key($key, $config);
            $perPrize = (string) (($entry['email'] ?? [])['voucher_code_display'] ?? 'inherit');
            if ($perPrize !== 'inherit' && function_exists('get_email_voucher_code_display_mode')) {
                return get_email_voucher_code_display_mode($perPrize);
            }
        }

        return function_exists('get_email_voucher_code_display_mode')
            ? get_email_voucher_code_display_mode()
            : 'text';
    }
}

if (!function_exists('prize_email_template_for_prize')) {
    /**
     * @return array{template_path:string,overlay:array}|null
     */
    function prize_email_template_for_prize(PDO $pdo, int $prizeId, ?array $config = null): ?array
    {
        if ($prizeId <= 0) {
            return null;
        }
        $stmt = $pdo->prepare('SELECT valore, tipo_premio FROM premi WHERE id = ? LIMIT 1');
        $stmt->execute([$prizeId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!is_array($row)) {
            return null;
        }
        $key = prize_email_template_key($row['valore'] ?? 0, (string) ($row['tipo_premio'] ?? ''));
        $templates = prize_email_templates_from_config($config);
        if (!isset($templates[$key]) || !is_array($templates[$key])) {
            return null;
        }
        $normalized = prize_email_normalize_template_entry($templates[$key]);
        if ($normalized['template_path'] === '') {
            return null;
        }

        return $normalized;
    }
}

if (!function_exists('prize_image_generated_relative_path')) {
    function prize_image_generated_relative_path(int $codiciPremioId): string
    {
        return 'uploads/premi/generated/cp_' . max(0, $codiciPremioId) . '.png';
    }
}

if (!function_exists('prize_image_resolve_absolute_path')) {
    function prize_image_resolve_absolute_path(string $relativePath): string
    {
        $relative = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, trim($relativePath));
        if ($relative === '') {
            return '';
        }
        if (preg_match('/^[A-Za-z]:' . preg_quote(DIRECTORY_SEPARATOR, '/') . '/', $relative)
            || strpos($relative, DIRECTORY_SEPARATOR) === 0) {
            return $relative;
        }

        return prize_image_project_root() . DIRECTORY_SEPARATOR . ltrim($relative, DIRECTORY_SEPARATOR);
    }
}

if (!function_exists('prize_image_premi_subdir_absolute')) {
    function prize_image_premi_subdir_absolute(string $subdir): string
    {
        $subdir = trim(str_replace('\\', '/', $subdir), '/');
        if (!in_array($subdir, ['templates', 'generated'], true)) {
            return '';
        }

        return prize_image_project_root()
            . DIRECTORY_SEPARATOR . 'uploads'
            . DIRECTORY_SEPARATOR . 'premi'
            . DIRECTORY_SEPARATOR . $subdir;
    }
}

if (!function_exists('prize_image_delete_premi_file')) {
    /**
     * Elimina un file sotto uploads/premi/templates/ o uploads/premi/generated/.
     */
    function prize_image_delete_premi_file(string $relativePath): bool
    {
        $normalized = str_replace('\\', '/', trim($relativePath));
        if ($normalized === '' || preg_match('#^https?://#i', $normalized)) {
            return false;
        }
        $normalized = ltrim($normalized, '/');
        if (!preg_match('#^uploads/premi/(templates|generated)/[a-zA-Z0-9_\.\-]+$#', $normalized)) {
            return false;
        }

        $absolute = prize_image_resolve_absolute_path($normalized);
        $subdir = strpos($normalized, 'uploads/premi/templates/') === 0 ? 'templates' : 'generated';
        $baseDir = prize_image_premi_subdir_absolute($subdir);
        $baseReal = $baseDir !== '' ? realpath($baseDir) : false;
        if ($baseReal === false) {
            return is_file($absolute) ? @unlink($absolute) : false;
        }
        $fileReal = realpath($absolute);
        if ($fileReal === false) {
            return is_file($absolute) ? @unlink($absolute) : false;
        }
        $baseReal = rtrim($baseReal, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        if (strpos($fileReal, $baseReal) !== 0) {
            return false;
        }

        return @unlink($fileReal);
    }
}

if (!function_exists('prize_image_delete_template_file')) {
    function prize_image_delete_template_file(string $relativePath): bool
    {
        $normalized = str_replace('\\', '/', trim($relativePath));
        if (strpos($normalized, 'uploads/premi/templates/') !== 0) {
            return false;
        }

        return prize_image_delete_premi_file($normalized);
    }
}

if (!function_exists('prize_image_cleanup_orphan_templates')) {
    /**
     * @param string[] $keepRelativePaths percorsi ancora referenziati in content.json
     */
    function prize_image_cleanup_orphan_templates(array $keepRelativePaths): int
    {
        $keep = [];
        foreach ($keepRelativePaths as $path) {
            $path = str_replace('\\', '/', trim((string) $path));
            if ($path !== '') {
                $keep[$path] = true;
            }
        }

        $dir = prize_image_premi_subdir_absolute('templates');
        if ($dir === '' || !is_dir($dir)) {
            return 0;
        }

        $deleted = 0;
        foreach (glob($dir . DIRECTORY_SEPARATOR . '*') ?: [] as $filePath) {
            if (!is_file($filePath)) {
                continue;
            }
            $relative = 'uploads/premi/templates/' . basename($filePath);
            if (!isset($keep[$relative]) && prize_image_delete_template_file($relative)) {
                $deleted++;
            }
        }

        return $deleted;
    }
}

if (!function_exists('prize_image_cleanup_legacy_preview_overlays')) {
    /** Elimina cache anteprima overlay (_preview_overlay*.png), non le immagini cp_{id}.png. */
    function prize_image_cleanup_legacy_preview_overlays(): int
    {
        $dir = prize_image_premi_subdir_absolute('generated');
        if ($dir === '' || !is_dir($dir)) {
            return 0;
        }

        $deleted = 0;
        foreach (glob($dir . DIRECTORY_SEPARATOR . '_preview_overlay*.png') ?: [] as $filePath) {
            if (!is_file($filePath)) {
                continue;
            }
            $relative = 'uploads/premi/generated/' . basename($filePath);
            if (prize_image_delete_premi_file($relative)) {
                $deleted++;
            }
        }

        return $deleted;
    }
}

if (!function_exists('prize_image_prize_ids_for_template_key')) {
    /** @return int[] */
    function prize_image_prize_ids_for_template_key(PDO $pdo, string $key): array
    {
        $key = trim($key);
        if ($key === '') {
            return [];
        }
        $ids = [];
        $stmt = $pdo->query('SELECT id, valore, tipo_premio FROM premi');
        if (!$stmt) {
            return [];
        }
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if (!is_array($row)) {
                continue;
            }
            $rowKey = prize_email_template_key($row['valore'] ?? 0, (string) ($row['tipo_premio'] ?? ''));
            if ($rowKey === $key) {
                $ids[] = (int) ($row['id'] ?? 0);
            }
        }

        return array_values(array_filter($ids, static fn (int $id): bool => $id > 0));
    }
}

if (!function_exists('prize_image_cleanup_generated_for_prize')) {
    function prize_image_cleanup_generated_for_prize(PDO $pdo, int $prizeId): int
    {
        if ($prizeId <= 0) {
            return 0;
        }
        $stmt = $pdo->prepare('SELECT id FROM codici_premio WHERE id_premio = ?');
        $stmt->execute([$prizeId]);
        $deleted = 0;
        while ($cpId = $stmt->fetchColumn()) {
            $rel = prize_image_generated_relative_path((int) $cpId);
            if (prize_image_delete_premi_file($rel)) {
                $deleted++;
            }
        }

        return $deleted;
    }
}

if (!function_exists('prize_image_cleanup_generated_for_template_key')) {
    function prize_image_cleanup_generated_for_template_key(PDO $pdo, string $templateKey): int
    {
        $deleted = 0;
        foreach (prize_image_prize_ids_for_template_key($pdo, $templateKey) as $prizeId) {
            $deleted += prize_image_cleanup_generated_for_prize($pdo, $prizeId);
        }

        return $deleted;
    }
}

if (!function_exists('prize_image_cleanup_all_generated_wins')) {
    /** Elimina cp_{id}.png (immagini generate alle vincite), non le anteprime. */
    function prize_image_cleanup_all_generated_wins(): int
    {
        $dir = prize_image_premi_subdir_absolute('generated');
        if ($dir === '' || !is_dir($dir)) {
            return 0;
        }

        $deleted = 0;
        foreach (glob($dir . DIRECTORY_SEPARATOR . 'cp_*.png') ?: [] as $filePath) {
            if (!is_file($filePath)) {
                continue;
            }
            $relative = 'uploads/premi/generated/' . basename($filePath);
            if (prize_image_delete_premi_file($relative)) {
                $deleted++;
            }
        }

        return $deleted;
    }
}

if (!function_exists('prize_image_cleanup_all_uploads')) {
    /**
     * Svuota templates + generated (reset configurazione).
     *
     * @return array{templates:int,generated:int}
     */
    function prize_image_cleanup_all_uploads(): array
    {
        $counts = ['templates' => 0, 'generated' => 0];
        foreach (['templates', 'generated'] as $subdir) {
            $dir = prize_image_premi_subdir_absolute($subdir);
            if ($dir === '' || !is_dir($dir)) {
                continue;
            }
            foreach (glob($dir . DIRECTORY_SEPARATOR . '*') ?: [] as $filePath) {
                if (!is_file($filePath)) {
                    continue;
                }
                $relative = 'uploads/premi/' . $subdir . '/' . basename($filePath);
                if (prize_image_delete_premi_file($relative)) {
                    $counts[$subdir]++;
                }
            }
        }

        return $counts;
    }
}

if (!function_exists('prize_image_cleanup_templates_from_config')) {
    function prize_image_cleanup_templates_from_config(?array $config = null): int
    {
        $deleted = 0;
        foreach (prize_email_templates_from_config($config) as $entry) {
            $normalized = prize_email_normalize_template_entry($entry);
            if ($normalized['template_path'] !== '' && prize_image_delete_template_file($normalized['template_path'])) {
                $deleted++;
            }
        }

        return $deleted;
    }
}

if (!function_exists('prize_image_resolve_font_path')) {
    function prize_image_resolve_font_path(): ?string
    {
        $candidates = [
            prize_image_project_root() . '/public/assets/fonts/DejaVuSans-Bold.ttf',
            prize_image_project_root() . '/public/assets/fonts/arial.ttf',
            'C:\\Windows\\Fonts\\arial.ttf',
            'C:\\Windows\\Fonts\\Arial.ttf',
            '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
            '/usr/share/fonts/truetype/liberation/LiberationSans-Bold.ttf',
        ];
        foreach ($candidates as $path) {
            if (is_file($path) && is_readable($path)) {
                return $path;
            }
        }

        return null;
    }
}

if (!function_exists('prize_image_hex_to_rgb')) {
    /** @return array{0:int,1:int,2:int} */
    function prize_image_hex_to_rgb(string $hex): array
    {
        $hex = ltrim(prize_email_normalize_hex_color($hex), '#');
        return [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
        ];
    }
}

if (!function_exists('prize_image_load_from_file')) {
    /**
     * @return resource|\GdImage|null
     */
    function prize_image_load_from_file(string $absolutePath)
    {
        if (!is_file($absolutePath) || !is_readable($absolutePath)) {
            return null;
        }
        $ext = strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION));
        if ($ext === 'png' && function_exists('imagecreatefrompng')) {
            return @imagecreatefrompng($absolutePath);
        }
        if (in_array($ext, ['jpg', 'jpeg'], true) && function_exists('imagecreatefromjpeg')) {
            return @imagecreatefromjpeg($absolutePath);
        }
        if ($ext === 'webp' && function_exists('imagecreatefromwebp')) {
            return @imagecreatefromwebp($absolutePath);
        }
        if ($ext === 'gif' && function_exists('imagecreatefromgif')) {
            return @imagecreatefromgif($absolutePath);
        }

        return null;
    }
}

if (!function_exists('prize_image_apply_code_overlay')) {
    /**
     * @param resource|\GdImage $image
     */
    function prize_image_apply_code_overlay($image, array $overlay, string $codice): void
    {
        $overlay = prize_email_normalize_overlay_config($overlay);
        $codice = trim($codice);
        if (!$overlay['enabled'] || $codice === '') {
            return;
        }
        [$r, $g, $b] = prize_image_hex_to_rgb($overlay['color']);
        $textColor = imagecolorallocate($image, $r, $g, $b);
        $fontPath = prize_image_resolve_font_path();
        if ($fontPath !== null && function_exists('imagettftext')) {
            imagettftext(
                $image,
                (float) $overlay['font_size'],
                0.0,
                $overlay['x'],
                $overlay['y'],
                $textColor,
                $fontPath,
                $codice
            );
        } else {
            imagestring($image, 5, $overlay['x'], max(0, $overlay['y'] - 12), $codice, $textColor);
        }
    }
}

if (!function_exists('prize_image_render_overlay_preview')) {
    /**
     * Genera anteprima PNG con codice esempio (manager / email preview).
     *
     * @return string|null percorso relativo uploads/premi/generated/...
     */
    function prize_image_render_overlay_preview(string $templateRelativePath, array $overlay, string $sampleCode = 'ABC123'): ?string
    {
        $templateRelativePath = trim(str_replace('\\', '/', $templateRelativePath));
        if ($templateRelativePath === '' || !function_exists('imagecreatetruecolor')) {
            return null;
        }
        $overlay = prize_email_normalize_overlay_config($overlay);
        if (!$overlay['enabled']) {
            return $templateRelativePath;
        }

        $templateAbs = prize_image_resolve_absolute_path($templateRelativePath);
        $image = prize_image_load_from_file($templateAbs);
        if ($image === null) {
            return null;
        }

        prize_image_apply_code_overlay($image, $overlay, $sampleCode);

        $outRel = 'uploads/premi/generated/_preview_overlay.png';
        $outAbs = prize_image_resolve_absolute_path($outRel);
        $outDir = dirname($outAbs);
        if (!is_dir($outDir) && !@mkdir($outDir, 0777, true)) {
            imagedestroy($image);

            return null;
        }

        $saved = @imagepng($image, $outAbs);
        imagedestroy($image);

        return $saved ? $outRel : null;
    }
}

if (!function_exists('prize_overlay_preview_manager_url')) {
    function prize_overlay_preview_manager_url(string $templatePath, array $overlay, string $sampleCode = 'ABC123'): string
    {
        $templatePath = trim(str_replace('\\', '/', $templatePath));
        $overlay = prize_email_normalize_overlay_config($overlay);
        if ($templatePath === '') {
            return '';
        }
        if (!$overlay['enabled']) {
            return prize_upload_manager_preview_url($templatePath);
        }

        $qs = http_build_query([
            'template' => $templatePath,
            'enabled' => '1',
            'x' => $overlay['x'],
            'y' => $overlay['y'],
            'font_size' => $overlay['font_size'],
            'color' => $overlay['color'],
            'code' => $sampleCode,
        ]);

        return 'res/inc/prize_overlay_preview.php?' . $qs;
    }
}

if (!function_exists('prize_image_generate')) {
    /**
     * Genera uploads/premi/generated/cp_{id}.png dal template content.json.
     *
     * @return string|null percorso relativo generato
     */
    function prize_image_generate(int $codiciPremioId, int $prizeId, ?PDO $pdo = null, ?string $codiceOverride = null, ?array $config = null): ?string
    {
        if ($codiciPremioId <= 0 || $prizeId <= 0) {
            return null;
        }
        if (!function_exists('imagecreatetruecolor')) {
            error_log('prize_image_generate: estensione GD non disponibile');

            return null;
        }

        if (!$pdo instanceof PDO) {
            if (!function_exists('conn_pdo')) {
                return null;
            }
            $pdo = conn_pdo();
        }

        $template = prize_email_template_for_prize($pdo, $prizeId, $config);
        if ($template === null) {
            return null;
        }

        $templateAbs = prize_image_resolve_absolute_path($template['template_path']);
        $image = prize_image_load_from_file($templateAbs);
        if ($image === null) {
            error_log('prize_image_generate: template non leggibile: ' . $templateAbs);

            return null;
        }

        $codice = trim((string) $codiceOverride);
        if ($codice === '') {
            $stmt = $pdo->prepare('SELECT codice FROM codici_premio WHERE id = ? LIMIT 1');
            $stmt->execute([$codiciPremioId]);
            $codice = trim((string) ($stmt->fetchColumn() ?: ''));
        }

        $overlay = $template['overlay'];
        if ($overlay['enabled'] && $codice !== '') {
            prize_image_apply_code_overlay($image, $overlay, $codice);
        }

        $outRel = prize_image_generated_relative_path($codiciPremioId);
        $outAbs = prize_image_resolve_absolute_path($outRel);
        $outDir = dirname($outAbs);
        if (!is_dir($outDir) && !@mkdir($outDir, 0777, true)) {
            imagedestroy($image);
            error_log('prize_image_generate: impossibile creare ' . $outDir);

            return null;
        }

        $saved = @imagepng($image, $outAbs);
        imagedestroy($image);
        if (!$saved) {
            error_log('prize_image_generate: salvataggio fallito ' . $outAbs);

            return null;
        }

        return $outRel;
    }
}

if (!function_exists('prize_image_try_generate_after_assign')) {
    function prize_image_try_generate_after_assign(PDO $pdo, int $codiciPremioId, int $prizeId, ?string $codice = null): void
    {
        try {
            prize_image_generate($codiciPremioId, $prizeId, $pdo, $codice);
        } catch (Throwable $e) {
            error_log('prize_image_try_generate_after_assign #' . $codiciPremioId . ': ' . $e->getMessage());
        }
    }
}

if (!function_exists('prize_image_resolve_for_email')) {
    /**
     * Priorità: immagine generata cp_{id}.png → template statico da content.json.
     *
     * @return array{path:string,tipo_premio:string}|null
     */
    function prize_image_resolve_for_email(?int $idCodicePremio, ?int $prizeId, ?PDO $pdo = null, ?array $config = null): ?array
    {
        $tipoPremio = '';
        $activeTemplate = null;
        if ($pdo instanceof PDO && $prizeId > 0) {
            $stmt = $pdo->prepare('SELECT tipo_premio FROM premi WHERE id = ? LIMIT 1');
            $stmt->execute([$prizeId]);
            $tipoPremio = trim((string) ($stmt->fetchColumn() ?: ''));
            $activeTemplate = prize_email_template_for_prize($pdo, $prizeId, $config);
        }
        $hasActiveTemplate = is_array($activeTemplate) && trim((string) ($activeTemplate['template_path'] ?? '')) !== '';

        if ($idCodicePremio > 0 && $hasActiveTemplate) {
            $generatedRel = prize_image_generated_relative_path($idCodicePremio);
            $generatedAbs = prize_image_resolve_absolute_path($generatedRel);
            if (is_file($generatedAbs) && is_readable($generatedAbs)) {
                return ['path' => $generatedRel, 'tipo_premio' => $tipoPremio];
            }
        }

        if ($pdo instanceof PDO && $prizeId > 0 && $hasActiveTemplate) {
            $template = $activeTemplate;
            if ($template !== null && $template['template_path'] !== '') {
                $abs = prize_image_resolve_absolute_path($template['template_path']);
                if (is_file($abs) && is_readable($abs)) {
                    return ['path' => $template['template_path'], 'tipo_premio' => $tipoPremio];
                }
            }
        }

        return $tipoPremio !== '' ? ['path' => '', 'tipo_premio' => $tipoPremio] : null;
    }
}

if (!function_exists('resolve_prize_upload_web_url')) {
    /**
     * URL publique pour fichiers sous uploads/ (racine projet, pas public/).
     */
    function resolve_prize_upload_web_url(string $path): string
    {
        $clean = trim(str_replace('\\', '/', $path));
        if ($clean === '') {
            return '';
        }
        if (preg_match('#^https?://#i', $clean)) {
            return $clean;
        }
        $clean = ltrim($clean, '/');
        if (strpos($clean, 'uploads/') !== 0) {
            return function_exists('resolve_email_asset_url')
                ? html_entity_decode((string) resolve_email_asset_url($clean), ENT_QUOTES, 'UTF-8')
                : '/' . $clean;
        }
        if (function_exists('resolve_email_base_url')) {
            $baseUrl = rtrim((string) resolve_email_base_url(), '/');
            $siteRoot = preg_replace('#/public$#', '', $baseUrl);
            if ($siteRoot === '') {
                $siteRoot = $baseUrl;
            }

            return $siteRoot . '/' . $clean;
        }

        return '/' . $clean;
    }
}

if (!function_exists('prize_upload_manager_preview_url')) {
    /**
     * URL per anteprima nel manager (thumb template premio).
     */
    function prize_upload_manager_preview_url(string $relativePath): string
    {
        $url = resolve_prize_upload_web_url($relativePath);
        if ($url === '') {
            return '';
        }
        if (preg_match('#^https?://#i', $url)) {
            return $url;
        }

        return '../../' . ltrim($url, '/');
    }
}

if (!function_exists('build_prize_image_block_html')) {
    function build_prize_image_block_html(string $imageRelativePath, string $alt = ''): string
    {
        $imageRelativePath = trim($imageRelativePath);
        if ($imageRelativePath === '') {
            return '';
        }
        $url = resolve_prize_upload_web_url($imageRelativePath);
        if ($url === '') {
            return '';
        }
        $safeUrl = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
        $safeAlt = htmlspecialchars($alt !== '' ? $alt : 'Premio', ENT_QUOTES, 'UTF-8');

        return '<p style="margin:16px 0 24px 0;text-align:center;">'
            . '<img src="' . $safeUrl . '" alt="' . $safeAlt . '" style="display:block;max-width:100%;width:400px;height:auto;margin:0 auto;border:0;">'
            . '</p>';
    }
}
