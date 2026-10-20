<?php
function advent_editor_base(): string
{
    return (string) preg_replace('~/manager(?:/.*)?$~', '', str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/manager/index.php'));
}

function advent_editor_field(string $name, string $label, string $value, string $type = 'text', bool $required = false): void
{
    $id = 'cal-' . preg_replace('/[^a-z0-9_-]/i', '-', $name);
    ?><div class="advent-field"><label for="<?= $id ?>"><?= advent_escape($label) ?></label><input class="form-control" id="<?= $id ?>" name="<?= advent_escape($name) ?>" type="<?= $type ?>" value="<?= advent_escape($value) ?>" <?= $required ? 'required' : '' ?> <?= $type === 'text' ? 'maxlength="500"' : '' ?>></div><?php
}

function advent_editor_image(string $name, string $label, string $value, ?string $uploadKey = null, string $gridRatio = 'classic'): void
{
    $uploadKey = $uploadKey ?? $name;
    $id = 'image-' . $uploadKey;
    $isTileCover = str_ends_with($uploadKey, '_cover');
    $coverResolution = ['square' => '1200 × 1200 px', 'classic' => '1200 × 1000 px', 'wide' => '1200 × 800 px'][$gridRatio] ?? '1200 × 1000 px';
    $resolution = match (true) {
        $uploadKey === 'logo' => '600 × 300 px per il logo semplice; 1600 × 600 px per la testata completa',
        $uploadKey === 'background' => '1920 × 1080 px',
        $uploadKey === 'background_mobile' => '1080 × 1920 px',
        $uploadKey === 'background_tablet' => '1536 × 2048 px',
        $uploadKey === 'background_wide' => '2560 × 1440 px',
        $uploadKey === 'footer_logo' => '600 × 240 px',
        $uploadKey === 'footer_background_image' => '1920 × 500 px',
        $isTileCover => $coverResolution,
        str_ends_with($uploadKey, '_image') => '1200 × 1200 px',
        default => '1200 × 1200 px',
    };
    $previewUrl = ($value !== '' && advent_image_url($value)) ? advent_asset_url($value, advent_editor_base()) : '';
    ?><div class="advent-field advent-image-field"><label for="<?= $id ?>"><?= advent_escape($label) ?></label>
    <input class="form-control" id="<?= $id ?>" name="<?= advent_escape($name) ?>" value="<?= advent_escape($value) ?>" placeholder="https://… oppure /uploads/…" aria-describedby="hint-<?= $id ?>">
    <input type="file" name="<?= $uploadKey ?>_upload" accept="image/png,image/jpeg,image/webp,image/gif" aria-label="Carica <?= advent_escape($label) ?>">
    <small id="hint-<?= $id ?>" class="text-muted"><strong>Risoluzione consigliata: <?php if ($isTileCover): ?><span data-advent-cover-resolution><?= advent_escape($resolution) ?></span><?php else: ?><?= advent_escape($resolution) ?><?php endif; ?>.</strong> <?php if ($isTileCover): ?><span data-advent-fit-note>Usa la stessa proporzione della casella per evitare tagli o margini.</span> <?php endif; ?>PNG, JPG, WebP o GIF, massimo 6 MB. Svuota il percorso per rimuovere l’immagine.</small>
    <img class="advent-image-preview" alt="Anteprima <?= advent_escape($label) ?>" <?php if ($previewUrl !== ''): ?>src="<?= advent_escape($previewUrl) ?>"<?php else: ?>hidden<?php endif; ?>></div><?php
}

function advent_editor_uploads(array &$input): void
{
    $fields = ['logo' => ['logo'], 'background' => ['background'], 'background_mobile' => ['background_mobile'], 'background_tablet' => ['background_tablet'], 'background_wide' => ['background_wide'], 'footer_logo' => ['footer_logo'], 'footer_background_image' => ['footer_background_image']];
    $fields['container_background'] = ['containers', 'appearance', 'background_image'];
    $fields['container_close'] = ['containers', 'appearance', 'close_image'];
    for ($n = 1; $n <= 62; $n++) {
        foreach (['image', 'cover', 'active_cover'] as $key) { $fields['day_' . $n . '_' . $key] = ['days', $n, $key]; }
    }
    foreach ($fields as $key => $path) {
        $file = $_FILES[$key . '_upload'] ?? null;
        if (!$file || $file['error'] === UPLOAD_ERR_NO_FILE) { continue; }
        if ($file['error'] !== UPLOAD_ERR_OK || $file['size'] > 6 * 1024 * 1024) {
            throw new RuntimeException('Caricamento non riuscito: ogni immagine deve essere inferiore a 6 MB.');
        }
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        $extensions = ['image/png' => 'png', 'image/jpeg' => 'jpg', 'image/webp' => 'webp', 'image/gif' => 'gif'];
        if (!isset($extensions[$mime]) || !getimagesize($file['tmp_name'])) { throw new RuntimeException('Carica un’immagine PNG, JPG, WebP o GIF valida.'); }
        $directory = __DIR__ . '/../../../uploads/calendari';
        if (!is_dir($directory) && !mkdir($directory, 0755, true)) { throw new RuntimeException('Impossibile creare la cartella immagini.'); }
        $filename = bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
        if (!move_uploaded_file($file['tmp_name'], $directory . '/' . $filename)) { throw new RuntimeException('Impossibile salvare l’immagine caricata.'); }
        $target =& $input;
        foreach ($path as $segment) { $target =& $target[$segment]; }
        $target = advent_editor_base() . '/uploads/calendari/' . $filename;
        unset($target);
    }
}
