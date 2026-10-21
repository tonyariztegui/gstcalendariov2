<?php
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
if (empty($_SESSION['user_name']) || $_SESSION['user_name'] !== 'pm_admin') {
    http_response_code(403);
    include __DIR__ . '/_header.php';
    include __DIR__ . '/../res/inc/header.php';
    include __DIR__ . '/access_denied.php';
    include __DIR__ . '/_footer.php';
    return;
}
require_once __DIR__ . '/../../app/logic/advent_calendar.php';
require_once __DIR__ . '/../../app/logic/advent_footer.php';
require_once __DIR__ . '/calendari_avvento/editor.php';
$errors = [];
try { $calendar = advent_load(); }
catch (Throwable $e) { $calendar = advent_defaults(); $errors[] = 'Impossibile leggere la configurazione del calendario.'; }
$configVersion = is_file(advent_config_path()) ? (hash_file('sha256', advent_config_path()) ?: '') : '';
if (empty($_SESSION['advent_csrf'])) { $_SESSION['advent_csrf'] = bin2hex(random_bytes(32)); }
$saved = !empty($_SESSION['advent_saved']);
unset($_SESSION['advent_saved']);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $saved = false;
    if (!is_string($_POST['csrf'] ?? null) || !hash_equals($_SESSION['advent_csrf'], $_POST['csrf'])) {
        $errors[] = 'Sessione del modulo scaduta. Ricarica la pagina e riprova.';
    } elseif (!is_string($_POST['config_version'] ?? null) || !hash_equals($configVersion, $_POST['config_version'])) {
        $errors[] = 'Il calendario è stato aggiornato dopo l’apertura di questa scheda. Ricarica il manager e riapri l’anteprima per vedere le immagini aggiornate.';
    } else {
        try {
            $input = advent_footer_from_post($_POST);
            if (isset($_FILES['upload_footer_image'])) { $_FILES['footer_background_image_upload'] = $_FILES['upload_footer_image']; }
            if (isset($_FILES['upload_footer_logo_image'])) { $_FILES['footer_logo_upload'] = $_FILES['upload_footer_logo_image']; }
            $input['days'] = array_replace($calendar['days'], is_array($_POST['days'] ?? null) ? $_POST['days'] : []);
            advent_editor_uploads($input);
            // Preview and draft saving also work with an incomplete prize list.
            if (($_POST['action'] ?? '') === 'preview') { $input['published'] = false; }
            [$calendar, $validationErrors] = advent_validate($input);
            $errors = array_merge($errors, $validationErrors);
            if (!$errors) {
                if (($_POST['action'] ?? '') === 'preview') {
                    $_SESSION['advent_preview'] = $calendar;
                    $_SESSION['advent_preview_config_hash'] = hash_file('sha256', advent_config_path());
                    header('Location: anteprima-calendario?' . http_build_query(['mode' => 'date', 'date' => $calendar['end_date']]));
                    exit;
                }
                advent_save($calendar);
                unset($_SESSION['advent_preview']);
                unset($_SESSION['advent_preview_config_hash']);
                $_SESSION['advent_saved'] = true;
                header('Location: calendari-avvento?step=' . max(1, min(4, (int) ($_POST['calendar_step'] ?? 1))));
                exit;
            }
        } catch (Throwable $e) { $errors[] = $e instanceof RuntimeException ? $e->getMessage() : 'Salvataggio non riuscito. Riprova.'; }
    }
}
$days = advent_days($calendar);
$ready = count(array_filter($days, static function ($day) use ($calendar) {
    return trim((string) ($calendar['days'][$day['day']]['title'] ?? '')) !== '';
}));
include __DIR__ . '/_header.php';
include __DIR__ . '/../res/inc/header.php';
?>
<link rel="stylesheet" href="views/concorso/concorso.css">
<link rel="stylesheet" href="views/calendari_avvento/editor.css?v=<?= (int) filemtime(__DIR__ . '/calendari_avvento/editor.css') ?>">
<main class="advent-editor app-main__outer"><div class="app-main__inner"><div class="row"><div class="col-12"><div class="card mb-4 config-shell-card">
    <header class="card-header config-header">
        <div class="config-header-main">
            <div class="config-title-block"><span class="config-title-icon"><i class="fa fa-calendar-alt" aria-hidden="true"></i></span><div>
                <h5 class="card-title mb-0">Configurazione calendario d’Avvento</h5>
                <p class="config-title-help mb-0">Gestisci impostazioni principali, anteprime e pubblicazione del calendario.</p>
            </div></div>
            <div class="d-flex align-items-center config-publicato">
                <label for="cal-published" class="mb-0 mr-2 config-publicato-label">Pubblicato</label>
                <select id="cal-published" name="published" form="advent-form" class="form-control form-control-sm config-published-select"><option value="1" <?= $calendar['published'] ? 'selected' : '' ?>>Sì</option><option value="0" <?= !$calendar['published'] ? 'selected' : '' ?>>No</option></select>
                <span class="config-status-pill <?= $calendar['published'] ? 'config-status-pill--online' : 'config-status-pill--draft' ?>"><i class="fa <?= $calendar['published'] ? 'fa-globe' : 'fa-eye-slash' ?> mr-1" aria-hidden="true"></i><?= $calendar['published'] ? 'Online' : 'Bozza' ?></span>
            </div>
        </div>
        <div class="config-header-actions">
            <div class="config-preview-panel"><div class="config-panel-heading"><span><i class="fa fa-eye mr-1" aria-hidden="true"></i>Anteprima le pagine</span></div><div class="config-anteprima-wrap config-anteprima-preview">
                <button class="btn btn-sm btn-anteprima" type="submit" form="advent-form" name="action" value="preview" formtarget="_blank">Calendario e premi</button>
                <a class="btn btn-sm btn-outline-secondary" href="<?= advent_escape(advent_editor_base()) ?>/public/calendario-avvento" target="_blank" rel="noopener">Pagina pubblica <i class="fa fa-external-link-alt ml-1" aria-hidden="true"></i></a>
            </div></div>
            <div class="config-reset-save-btns"><div class="config-panel-heading"><span><i class="fa fa-save mr-1" aria-hidden="true"></i>Azioni</span></div>
                <button type="submit" form="advent-form" name="action" value="save" class="btn btn-primary btn-sm flex-fill config-save-btn"><i class="fa fa-save mr-1" aria-hidden="true"></i>Salva configurazione</button>
            </div>
        </div>
    </header>
    <div class="card-body">
    <?php if ($saved): ?><div class="alert alert-success" role="status">Calendario salvato correttamente.</div><?php endif; ?>
    <?php if ($errors): ?><div class="alert alert-danger" role="alert"><strong>Controlla i dati del calendario.</strong><ul class="mb-0"><?php foreach ($errors as $error): ?><li><?= advent_escape($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
    <div class="config-overview mb-3" aria-label="Riepilogo calendario">
        <div class="config-overview-card"><span class="config-overview-icon"><i class="fa fa-calendar" aria-hidden="true"></i></span><div><span class="config-overview-label">Durata calendario</span><strong><?= count($days) ?> giorni</strong><small>Una casella per ogni giorno del periodo.</small></div></div>
        <div class="config-overview-card config-overview-card--primary"><span class="config-overview-icon"><i class="fa fa-gift" aria-hidden="true"></i></span><div><span class="config-overview-label">Premi configurati</span><strong><?= $ready ?> / <?= count($days) ?></strong><small>Completa i premi prima di pubblicare.</small></div></div>
        <div class="config-overview-card"><span class="config-overview-icon"><i class="fa fa-clock" aria-hidden="true"></i></span><div><span class="config-overview-label">Disponibilità</span><strong><?= !empty($calendar['allow_past_days']) ? 'Premi passati visibili' : 'Solo il giorno corrente' ?></strong><small>Le caselle seguono l’orario italiano.</small></div></div>
    </div>
    <div class="config-wizard mb-3">
        <div class="config-wizard-header"><div><div class="config-wizard-title"><i class="fa fa-map mr-2" aria-hidden="true"></i>Percorso guidato</div><p class="config-wizard-help mb-0">Spostati tra le sezioni quando serve. Le modifiche vengono salvate con il pulsante Salva configurazione.</p></div><div class="text-muted small" id="advent-progress-text" aria-live="polite"></div></div>
        <div class="wizard-progress" aria-hidden="true"><div class="wizard-progress-bar" id="advent-progress-bar"></div></div>
        <div class="wizard-steps" role="tablist" aria-label="Sezioni del calendario">
            <?php foreach (['Configurazione', 'Branding', 'Caselle e contenitori', 'Footer'] as $index => $label): ?><button class="wizard-step" type="button" role="tab" id="advent-tab-<?= $index + 1 ?>" aria-controls="advent-step-<?= $index + 1 ?>" data-calendar-step="<?= $index + 1 ?>"><?= $label ?></button><?php endforeach; ?>
        </div>
    </div>
    <form method="post" enctype="multipart/form-data" id="advent-form">
        <input type="hidden" name="csrf" value="<?= advent_escape($_SESSION['advent_csrf']) ?>">
        <input type="hidden" name="config_version" value="<?= advent_escape($configVersion) ?>">
        <input type="hidden" name="calendar_step" id="advent-current-step" value="<?= max(1, min(4, (int) ($_POST['calendar_step'] ?? $_GET['step'] ?? 1))) ?>">
        <div class="config-columns">
        <section class="card mt-3 mb-4 config-step configurazione-section" id="advent-step-1" role="tabpanel" aria-labelledby="advent-tab-1" tabindex="0"><div class="card-body">
            <div class="configurazione-hero"><div><div class="section-title configurazione-title"><i class="fa fa-list-alt mr-2" aria-hidden="true"></i>Dati calendario</div><p class="text-muted small mb-0">Imposta identità e periodo del calendario.</p></div><span class="configurazione-badge"><i class="fa fa-cog mr-1" aria-hidden="true"></i>Configurazione base</span></div>
            <div class="configurazione-panel"><div class="configurazione-panel-header"><span class="configurazione-panel-icon"><i class="fa fa-pencil" aria-hidden="true"></i></span><div><h6>Identità calendario</h6><p>Informazioni principali mostrate nella pagina pubblica.</p></div></div><div class="advent-fields">
                <?php advent_editor_field('title', 'Titolo del calendario', $calendar['title'], 'text', true); ?>
                <div class="advent-field advent-field--wide"><label for="cal-intro">Testo introduttivo</label><textarea id="cal-intro" name="intro" rows="3" maxlength="2000" class="form-control"><?= advent_escape($calendar['intro']) ?></textarea></div>
            </div></div>
            <div class="configurazione-panel"><div class="configurazione-panel-header"><span class="configurazione-panel-icon"><i class="fa fa-calendar" aria-hidden="true"></i></span><div><h6>Date del calendario</h6><p>Gestisci il periodo di apertura delle caselle.</p></div></div><div class="advent-fields">
                <?php advent_editor_field('start_date', 'Data di inizio', $calendar['start_date'], 'date', true); advent_editor_field('end_date', 'Data di fine', $calendar['end_date'], 'date', true); ?>
                <div class="advent-field advent-field--wide">
                    <label for="cal-allow-past-days">Contenuto delle caselle passate</label>
                    <select id="cal-allow-past-days" name="allow_past_days" class="form-control" aria-describedby="allow-past-days-help"><option value="0" <?= empty($calendar['allow_past_days']) ? 'selected' : '' ?>>Non visibile</option><option value="1" <?= !empty($calendar['allow_past_days']) ? 'selected' : '' ?>>Visibile</option></select>
                    <small id="allow-past-days-help" class="text-muted">Se visibile, immagine e titolo del premio vengono mostrati direttamente nelle caselle dei giorni precedenti. Le caselle passate non si aprono e quelle future restano chiuse.</small>
                </div>
            </div><p class="text-muted small mt-3 mb-0">Da 1 a 62 giorni. Salva in bozza dopo aver cambiato le date per aggiornare l’elenco delle caselle. Orario di riferimento: Italia.</p></div>
        </div></section>
        <section class="card mt-3 mb-4 config-step configurazione-section" id="advent-step-2" role="tabpanel" aria-labelledby="advent-tab-2" tabindex="0"><div class="card-body">
            <div class="configurazione-hero"><div><div class="section-title configurazione-title"><i class="fa fa-image mr-2" aria-hidden="true"></i>Branding</div><p class="text-muted small mb-0">Personalizza logo, sfondi, colori e velatura del popup.</p></div></div>
            <div class="configurazione-panel"><div class="configurazione-panel-header"><span class="configurazione-panel-icon"><i class="fa fa-paint-brush" aria-hidden="true"></i></span><div><h6>Grafica e identità</h6><p>Carica un’immagine per ogni formato. Se tablet, mobile o schermo grande non sono impostati, viene usato automaticamente lo sfondo desktop.</p></div></div><div class="advent-fields">
                <?php foreach (['logo' => 'Logo', 'background' => 'Sfondo desktop', 'background_wide' => 'Sfondo schermi grandi', 'background_tablet' => 'Sfondo tablet', 'background_mobile' => 'Sfondo mobile'] as $key => $label) { advent_editor_image($key, $label, $calendar[$key]); } ?>
                <div class="advent-field advent-field--wide">
                    <label for="cal-logo-replaces-heading">Utilizzo del logo</label>
                    <select id="cal-logo-replaces-heading" name="logo_replaces_heading" class="form-control" aria-describedby="logo-replaces-heading-help"><option value="0" <?= empty($calendar['logo_replaces_heading']) ? 'selected' : '' ?>>Logo con titolo e descrizione separati</option><option value="1" <?= !empty($calendar['logo_replaces_heading']) ? 'selected' : '' ?>>Testata completa: il logo contiene tutte le scritte</option></select>
                    <small id="logo-replaces-heading-help" class="text-muted">Con “Testata completa” vengono nascosti visivamente titolo, etichetta e testo introduttivo, perché sono già presenti nell’immagine.</small>
                </div>
                <div class="advent-field advent-field--wide advent-logo-sizes" <?= empty($calendar['logo_replaces_heading']) ? 'hidden' : '' ?>>
                    <h6>Dimensione della testata completa per dispositivo</h6>
                    <p class="text-muted small">Imposta la larghezza massima. L’altezza si adatta automaticamente e l’immagine non supera la larghezza dello schermo.</p>
                    <div class="advent-logo-size-fields">
                        <?php foreach (['mobile' => 'Smartphone', 'tablet' => 'Tablet', 'desktop' => 'Computer', 'wide' => 'Schermi grandi'] as $device => $label): ?>
                            <label for="cal-logo-width-<?= $device ?>"><?= $label ?><span class="advent-logo-size-input"><input class="form-control" id="cal-logo-width-<?= $device ?>" name="logo_width_<?= $device ?>" type="number" min="60" max="1000" step="1" value="<?= (int) $calendar['logo_width_' . $device] ?>" required><span>px</span></span></label>
                        <?php endforeach; ?>
                    </div>
                    <div class="advent-logo-size-preview"><label for="cal-logo-preview-device">Anteprima della testata</label><select id="cal-logo-preview-device" class="form-control form-control-sm"><option value="mobile">Smartphone</option><option value="tablet">Tablet</option><option value="desktop" selected>Computer</option><option value="wide">Schermi grandi</option></select><div class="advent-logo-preview-canvas"><img id="cal-logo-size-preview" <?php if ($calendar['logo'] !== ''): ?>src="<?= advent_escape(advent_asset_url($calendar['logo'], advent_editor_base())) ?>"<?php else: ?>hidden<?php endif; ?> alt="Anteprima dimensione della testata completa"><span id="cal-logo-preview-empty" <?= $calendar['logo'] !== '' ? 'hidden' : '' ?>>Carica una testata completa per vedere l’anteprima.</span></div></div>
                </div>
                <?php advent_editor_field('accent', 'Colore principale', $calendar['accent'], 'color'); ?>
                <?php advent_editor_field('modal_overlay', 'Colore velatura popup', $calendar['modal_overlay'], 'color'); ?>
                <div class="advent-field">
                    <label for="cal-modal-overlay-opacity">Intensità velatura popup</label>
                    <div class="advent-range-control"><input id="cal-modal-overlay-opacity" name="modal_overlay_opacity" type="range" min="0" max="100" step="1" value="<?= (int) $calendar['modal_overlay_opacity'] ?>" aria-describedby="modal-overlay-opacity-help"><output for="cal-modal-overlay-opacity"><?= (int) $calendar['modal_overlay_opacity'] ?>%</output></div>
                    <small id="modal-overlay-opacity-help" class="text-muted">0% trasparente, 100% completamente coprente.</small>
                </div>
            </div></div>
        </div></section>
        <section class="card mt-3 mb-4 config-step configurazione-section" id="advent-step-3" role="tabpanel" aria-labelledby="advent-tab-3" tabindex="0"><div class="card-body">
            <div class="configurazione-hero"><div><div class="section-title configurazione-title"><i class="fa fa-gift mr-2" aria-hidden="true"></i>Caselle e contenitori</div><p class="text-muted small mb-0">Configura la griglia e il contenitore che si apre. Ogni casella resta impostata qui sotto.</p></div></div>
            <div class="advent-surface-switch" role="tablist" aria-label="Vista della casella">
                <button class="advent-surface-tab" type="button" role="tab" id="surface-tab-grid" aria-controls="surface-pane-grid" aria-selected="true" data-surface="grid">Griglia</button>
                <button class="advent-surface-tab" type="button" role="tab" id="surface-tab-container" aria-controls="surface-pane-container" aria-selected="false" data-surface="container">Contenitore</button>
            </div>
            <div id="surface-pane-grid" role="tabpanel" aria-labelledby="surface-tab-grid" data-surface-pane="grid">
            <div class="configurazione-panel"><div class="configurazione-panel-header"><span class="configurazione-panel-icon"><i class="fa fa-th" aria-hidden="true"></i></span><div><h6>Griglia delle caselle</h6><p>Personalizza la disposizione nella pagina pubblica. Le colonne si adattano al dispositivo.</p></div></div>
                <fieldset class="advent-grid-presets"><legend class="small font-weight-bold">Scegli un modello</legend>
                    <div class="advent-grid-preset-list">
                        <button type="button" class="advent-grid-preset" data-grid-preset="classic" data-mobile="2" data-tablet="3" data-desktop="4" data-gap="16" data-ratio="classic" data-radius="16" data-fit="contain" aria-pressed="false"><span class="advent-grid-preset-art advent-grid-preset-art--classic" aria-hidden="true"><i></i><i></i><i></i><i></i></span><strong>Classico</strong><small>4 colonne, arioso</small></button>
                        <button type="button" class="advent-grid-preset" data-grid-preset="compact" data-mobile="2" data-tablet="4" data-desktop="6" data-gap="8" data-ratio="square" data-radius="8" data-fit="contain" aria-pressed="false"><span class="advent-grid-preset-art advent-grid-preset-art--compact" aria-hidden="true"><i></i><i></i><i></i><i></i><i></i><i></i></span><strong>Compatto</strong><small>6 colonne, ravvicinate</small></button>
                        <button type="button" class="advent-grid-preset" data-grid-preset="gallery" data-mobile="1" data-tablet="2" data-desktop="3" data-gap="24" data-ratio="wide" data-radius="20" data-fit="cover" aria-pressed="false"><span class="advent-grid-preset-art advent-grid-preset-art--gallery" aria-hidden="true"><i></i><i></i><i></i></span><strong>Galleria</strong><small>3 colonne, immagini ampie</small></button>
                    </div>
                </fieldset>
                <div class="advent-grid-layout">
                <div class="advent-grid-live-preview" aria-label="Anteprima della griglia">
                    <div class="advent-grid-live-header"><strong>Anteprima del modello</strong><label for="cal-grid-preview-device">Dispositivo <select id="cal-grid-preview-device" class="form-control form-control-sm"><option value="desktop">Computer</option><option value="tablet">Tablet</option><option value="mobile">Smartphone</option></select></label></div>
                    <div id="cal-grid-preview" class="advent-grid-preview" aria-hidden="true"><?php
                        $previewCaptions = [['01/12', 'Apri la sorpresa'], ['02/12', 'Non ancora disponibile'], ['03/12', 'Scaduta · Premio']];
                        for ($n=1; $n<=8; $n++): $previewCaption = $previewCaptions[($n - 1) % 3];
                    ?><span class="advent-grid-preview-tile<?= ($n - 1) % 3 === 2 ? ' is-preview-expired' : '' ?>"><span class="advent-grid-preview-art"><?= $n ?><?php if (($n - 1) % 3 === 2): ?><img class="advent-grid-preview-banner" src="<?= advent_escape(advent_asset_url('/public/assets/images/calendario/promozione-non-piu-valida.png', advent_editor_base())) ?>" alt=""><?php endif; ?></span><span class="advent-grid-preview-caption"><strong><?= $previewCaption[0] ?></strong><span><?= $previewCaption[1] ?></span></span></span><?php endfor; ?></div>
                    <p id="cal-grid-preset-status" class="text-muted small mb-0" role="status"></p>
                </div>
                <div class="advent-grid-settings">
                <details class="advent-disclosure advent-grid-advanced"><summary>Regola il modello manualmente</summary><div class="advent-fields">
                    <div class="advent-field"><label for="cal-grid-columns-mobile">Colonne su smartphone</label><select class="form-control" id="cal-grid-columns-mobile" name="grid_columns_mobile"><?php foreach ([1,2] as $n): ?><option value="<?= $n ?>" <?= (int) ($calendar['grid_columns_mobile'] ?? 2) === $n ? 'selected' : '' ?>><?= $n ?></option><?php endforeach; ?></select></div>
                    <div class="advent-field"><label for="cal-grid-columns-tablet">Colonne su tablet</label><select class="form-control" id="cal-grid-columns-tablet" name="grid_columns_tablet"><?php foreach ([2,3,4] as $n): ?><option value="<?= $n ?>" <?= (int) ($calendar['grid_columns_tablet'] ?? 3) === $n ? 'selected' : '' ?>><?= $n ?></option><?php endforeach; ?></select></div>
                    <div class="advent-field"><label for="cal-grid-columns-desktop">Colonne su computer</label><select class="form-control" id="cal-grid-columns-desktop" name="grid_columns_desktop"><?php foreach ([2,3,4,5,6] as $n): ?><option value="<?= $n ?>" <?= (int) ($calendar['grid_columns_desktop'] ?? 4) === $n ? 'selected' : '' ?>><?= $n ?></option><?php endforeach; ?></select></div>
                    <div class="advent-field"><label for="cal-grid-gap">Spazio tra le caselle (px)</label><input class="form-control" id="cal-grid-gap" name="grid_gap" type="number" min="0" max="40" step="1" value="<?= (int) ($calendar['grid_gap'] ?? 16) ?>"></div>
                    <div class="advent-field"><label for="cal-grid-tile-ratio">Proporzione dell’immagine</label><select class="form-control" id="cal-grid-tile-ratio" name="grid_tile_ratio"><?php foreach (['square'=>'Quadrata','classic'=>'Rettangolare classica','wide'=>'Rettangolare larga'] as $value=>$label): ?><option value="<?= $value ?>" <?= ($calendar['grid_tile_ratio'] ?? 'classic') === $value ? 'selected' : '' ?>><?= $label ?></option><?php endforeach; ?></select></div>
                    <div class="advent-field"><label for="cal-grid-tile-radius">Angoli arrotondati (px)</label><input class="form-control" id="cal-grid-tile-radius" name="grid_tile_radius" type="number" min="0" max="32" step="1" value="<?= (int) ($calendar['grid_tile_radius'] ?? 16) ?>"></div>
                    <div class="advent-field advent-field--wide"><label for="cal-grid-image-fit">Adattamento immagini delle caselle</label><select class="form-control" id="cal-grid-image-fit" name="grid_image_fit"><option value="contain" <?= ($calendar['grid_image_fit'] ?? 'contain') === 'contain' ? 'selected' : '' ?>>Mostra l’immagine intera</option><option value="cover" <?= ($calendar['grid_image_fit'] ?? 'contain') === 'cover' ? 'selected' : '' ?>>Riempi la casella ritagliando l’immagine</option></select></div>
                    <div class="advent-field advent-field--wide"><label for="cal-grid-show-caption">Testo sotto la casella</label><select class="form-control" id="cal-grid-show-caption" name="grid_show_caption" aria-describedby="grid-show-caption-help"><option value="1" <?= !empty($calendar['grid_show_caption']) ? 'selected' : '' ?>>Visibile</option><option value="0" <?= empty($calendar['grid_show_caption']) ? 'selected' : '' ?>>Nascosto</option></select><small id="grid-show-caption-help" class="text-muted">Data, Apri la sorpresa, Non ancora disponibile e Scaduta.</small></div>
                </div></details>
                <details class="advent-disclosure">
                    <summary>Caselle scadute</summary>
                    <div class="advent-fields advent-expired-options">
                        <div class="advent-field">
                            <label for="cal-expired-image">Immagine della casella scaduta</label>
                            <select class="form-control" id="cal-expired-image" name="expired_image" aria-describedby="expired-image-help">
                                <option value="promotion" <?= ($calendar['expired_image'] ?? 'promotion') !== 'case' ? 'selected' : '' ?>>Immagine della promozione</option>
                                <option value="case" <?= ($calendar['expired_image'] ?? 'promotion') === 'case' ? 'selected' : '' ?>>Immagine della casella</option>
                            </select>
                            <small id="expired-image-help" class="text-muted">L’immagine della casella usa Adattamento immagini delle caselle, nel riquadro sopra.</small>
                        </div>
                        <div class="advent-field" id="cal-expired-image-fit-field" <?= ($calendar['expired_image'] ?? 'promotion') === 'case' ? 'hidden' : '' ?>>
                            <label for="cal-expired-image-fit">Adattamento immagine della promozione</label>
                            <select class="form-control" id="cal-expired-image-fit" name="expired_image_fit" aria-describedby="expired-image-fit-help">
                                <option value="contain" <?= ($calendar['expired_image_fit'] ?? 'contain') !== 'cover' ? 'selected' : '' ?>>Mostra l’immagine intera</option>
                                <option value="cover" <?= ($calendar['expired_image_fit'] ?? 'contain') === 'cover' ? 'selected' : '' ?>>Riempi la casella ritagliando l’immagine</option>
                            </select>
                            <small id="expired-image-fit-help" class="text-muted">Compare solo quando mostri l’immagine della promozione.</small>
                        </div>
                        <div class="advent-field">
                            <label for="cal-expired-message">Messaggio casella scaduta</label>
                            <select class="form-control" id="cal-expired-message" name="expired_message" aria-describedby="expired-message-help">
                                <option value="badge" <?= ($calendar['expired_message'] ?? 'badge') !== 'banner' ? 'selected' : '' ?>>Scaduta</option>
                                <option value="banner" <?= ($calendar['expired_message'] ?? 'badge') === 'banner' ? 'selected' : '' ?>>Promozione non più valida</option>
                            </select>
                            <small id="expired-message-help" class="text-muted">Scaduta resta in alto a sinistra. Promozione non più valida lo sostituisce con il banner in basso.</small>
                        </div>
                        <div class="advent-field" id="cal-expired-color">
                            <label for="cal-expired-grayscale">Colore della casella scaduta</label>
                            <select class="form-control" id="cal-expired-grayscale" name="expired_grayscale">
                                <option value="0" <?= empty($calendar['expired_grayscale']) ? 'selected' : '' ?>>A colori</option>
                                <option value="1" <?= !empty($calendar['expired_grayscale']) ? 'selected' : '' ?>>Bianco e nero</option>
                            </select>
                            <small class="text-muted">Bianco e nero vale per l’immagine scelta. Con il banner, anche il banner diventa bianco e nero.</small>
                        </div>
                    </div>
                </details>
                </div>
                </div>
                <p class="text-muted small mt-3 mb-0">Scegli un modello, controlla l’anteprima e salva la configurazione.</p>
            </div>
            </div>
            <?php include __DIR__ . '/calendari_avvento/container-editor.php'; ?>
            <p class="text-muted small">Scegli un tipo per ogni casella: premio, video YouTube o link. Il titolo la rende disponibile.</p>
            <?php if (!$days): ?><p role="status">Imposta un intervallo di date valido per configurare le caselle.</p><?php endif; ?>
            <div class="advent-day-list">
            <?php foreach ($days as $number => $day):
                $prize = $calendar['days'][$number] ?? [];
                $prefix = "days[$number]";
                $dayType = advent_day_type($prize);
                $typeLabels = ['prize' => 'Premio', 'video' => 'Video', 'link' => 'Link'];
                $summaryTitle = trim((string) ($prize['title'] ?? ''));
                $summary = $summaryTitle !== '' ? $typeLabels[$dayType] . ' · ' . $summaryTitle : $typeLabels[$dayType] . ' da configurare';
            ?>
                <details class="advent-day-editor" <?= $number === 1 ? 'open' : '' ?>>
                    <summary><span class="advent-day-number"><?= $number ?></span><span><strong>Casella <?= $number ?> <span class="text-muted">· <?= advent_escape($day['label']) ?></span></strong><small><?= advent_escape($summary) ?></small></span><span class="advent-day-status"><?= $summaryTitle === '' ? 'Da completare' : 'Configurata' ?></span></summary>
                    <div class="advent-fields">
                        <div class="advent-field advent-field--wide">
                            <label for="day-type-<?= $number ?>">Tipo</label>
                            <select class="form-control js-day-type" id="day-type-<?= $number ?>" name="<?= $prefix ?>[type]">
                                <?php foreach ($typeLabels as $value => $label): ?><option value="<?= $value ?>" <?= $dayType === $value ? 'selected' : '' ?>><?= $label ?></option><?php endforeach; ?>
                            </select>
                            <small class="text-muted js-day-type-help"></small>
                        </div>
                        <?php advent_editor_field($prefix . '[title]', 'Titolo', $prize['title'] ?? ''); ?>
                        <div class="advent-field advent-field--wide"><label for="description-<?= $number ?>">Descrizione</label><textarea id="description-<?= $number ?>" name="<?= $prefix ?>[description]" rows="2" maxlength="2000" class="form-control"><?= advent_escape($prize['description'] ?? '') ?></textarea></div>
                        <?php foreach (['cover' => 'Immagine della casella', 'active_cover' => 'Immagine della casella attiva (anche GIF)'] as $key => $label) { advent_editor_image($prefix . '[' . $key . ']', $label, $prize[$key] ?? '', 'day_' . $number . '_' . $key, $calendar['grid_tile_ratio'] ?? 'classic'); } ?>
                        <div class="advent-day-type-panel" data-day-panel="prize" <?= $dayType === 'prize' ? '' : 'hidden' ?>>
                            <div class="advent-fields">
                                <?php advent_editor_field($prefix . '[code]', 'Codice premio', $prize['code'] ?? ''); ?>
                            </div>
                        </div>
                        <div class="advent-day-type-panel" data-day-panel="prize link video" data-day-image>
                            <div class="advent-fields">
                                <?php advent_editor_image($prefix . '[image]', ['link' => 'Immagine link', 'video' => 'Immagine della casella scaduta'][$dayType] ?? 'Immagine del premio', $prize['image'] ?? '', 'day_' . $number . '_image', $calendar['grid_tile_ratio'] ?? 'classic'); ?>
                            </div>
                        </div>
                        <div class="advent-day-type-panel" data-day-panel="video" <?= $dayType === 'video' ? '' : 'hidden' ?>>
                            <div class="advent-field">
                                <label for="video-<?= $number ?>">Link YouTube</label>
                                <input class="form-control" id="video-<?= $number ?>" name="<?= $prefix ?>[media_url]" type="url" maxlength="2048" value="<?= $dayType === 'video' ? advent_escape($prize['media_url'] ?? '') : '' ?>" placeholder="https://www.youtube.com/watch?v=…" <?= $dayType === 'video' ? '' : 'disabled' ?>>
                            </div>
                        </div>
                        <div class="advent-day-type-panel" data-day-panel="link" <?= $dayType === 'link' ? '' : 'hidden' ?>>
                            <div class="advent-fields">
                                <div class="advent-field advent-field--wide">
                                    <label for="link-<?= $number ?>">Link</label>
                                    <input class="form-control" id="link-<?= $number ?>" name="<?= $prefix ?>[media_url]" type="url" maxlength="2048" value="<?= $dayType === 'link' ? advent_escape($prize['media_url'] ?? '') : '' ?>" placeholder="https://open.spotify.com/playlist/…" <?= $dayType === 'link' ? '' : 'disabled' ?>>
                                    <small class="text-muted">Il pulsante apre questo indirizzo. L’immagine resta sopra il pulsante.</small>
                                </div>
                                <?php advent_editor_field($prefix . '[link_label]', 'Testo del pulsante', ($prize['link_label'] ?? '') !== '' ? $prize['link_label'] : 'Apri link'); ?>
                            </div>
                        </div>
                    </div>
                </details>
            <?php endforeach; ?>
            </div>
        </div></section>
<?php include __DIR__ . '/calendari_avvento/footer-editor.php'; ?>
        </div>
        <div class="wizard-actions wizard-actions-bottom"><button class="btn btn-outline-secondary btn-sm" type="button" id="advent-prev"><i class="fa fa-arrow-left mr-1" aria-hidden="true"></i>Indietro</button><span class="text-muted small" id="advent-save-status" role="status">Le modifiche vengono salvate con Salva configurazione.</span><button class="btn btn-primary btn-sm" type="button" id="advent-next">Avanti<i class="fa fa-arrow-right ml-1" aria-hidden="true"></i></button></div>
    </form>
</div></div></div></div></div></main>
<script src="views/calendari_avvento/editor.js?v=<?= (int) filemtime(__DIR__ . '/calendari_avvento/editor.js') ?>"></script>
<?php $scripts = ($scripts ?? '') . '<script src="views/calendari_avvento/footer-editor.js"></script><script src="views/concorso/footer-editor.js"></script>'; include __DIR__ . '/_footer.php'; ?>
