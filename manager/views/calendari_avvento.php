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
if (empty($_SESSION['advent_csrf'])) { $_SESSION['advent_csrf'] = bin2hex(random_bytes(32)); }
$saved = !empty($_SESSION['advent_saved']);
unset($_SESSION['advent_saved']);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $saved = false;
    if (!is_string($_POST['csrf'] ?? null) || !hash_equals($_SESSION['advent_csrf'], $_POST['csrf'])) {
        $errors[] = 'Sessione del modulo scaduta. Ricarica la pagina e riprova.';
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
                    header('Location: anteprima-calendario?' . http_build_query(['mode' => 'date', 'date' => $calendar['end_date']]));
                    exit;
                }
                advent_save($calendar);
                unset($_SESSION['advent_preview']);
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
<link rel="stylesheet" href="views/calendari_avvento/editor.css">
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
            <?php foreach (['Configurazione', 'Branding', 'Caselle e premi', 'Footer'] as $index => $label): ?><button class="wizard-step" type="button" role="tab" id="advent-tab-<?= $index + 1 ?>" aria-controls="advent-step-<?= $index + 1 ?>" data-calendar-step="<?= $index + 1 ?>"><?= $label ?></button><?php endforeach; ?>
        </div>
    </div>
    <form method="post" enctype="multipart/form-data" id="advent-form">
        <input type="hidden" name="csrf" value="<?= advent_escape($_SESSION['advent_csrf']) ?>">
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
            <div class="configurazione-hero"><div><div class="section-title configurazione-title"><i class="fa fa-gift mr-2" aria-hidden="true"></i>Caselle e premi</div><p class="text-muted small mb-0">Ogni premio si apre soltanto nella sua data. Le caselle passate risultano scadute, quelle future non sono ancora disponibili.</p></div></div>
            <p class="text-muted small">Il titolo rende il premio disponibile. Immagini, descrizione e codice sono facoltativi. Il barcode EAN-13 viene generato a partire dal codice.</p>
            <?php if (!$days): ?><p role="status">Imposta un intervallo di date valido per configurare le caselle.</p><?php endif; ?>
            <div class="advent-day-list">
            <?php foreach ($days as $number => $day): $prize = $calendar['days'][$number] ?? []; $prefix = "days[$number]"; ?>
                <details class="advent-day-editor" <?= $number === 1 ? 'open' : '' ?>>
                    <summary><span class="advent-day-number"><?= $number ?></span><span><strong>Casella <?= $number ?> <span class="text-muted">· <?= advent_escape($day['label']) ?></span></strong><small><?= advent_escape(($prize['title'] ?? '') ?: 'Premio da configurare') ?></small></span><span class="advent-day-status"><?= empty($prize['title']) ? 'Da completare' : 'Configurata' ?></span></summary>
                    <div class="advent-fields">
                        <?php advent_editor_field($prefix . '[title]', 'Titolo del premio', $prize['title'] ?? ''); advent_editor_field($prefix . '[code]', 'Codice premio', $prize['code'] ?? ''); ?>
                        <div class="advent-field advent-field--wide"><label for="description-<?= $number ?>">Descrizione del premio</label><textarea id="description-<?= $number ?>" name="<?= $prefix ?>[description]" rows="2" maxlength="2000" class="form-control"><?= advent_escape($prize['description'] ?? '') ?></textarea></div>
                        <?php foreach (['cover' => 'Immagine della casella', 'active_cover' => 'Immagine della casella attiva (anche GIF)', 'image' => 'Immagine del premio'] as $key => $label) { advent_editor_image($prefix . '[' . $key . ']', $label, $prize[$key] ?? '', 'day_' . $number . '_' . $key); } ?>
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
<script src="views/calendari_avvento/editor.js"></script>
<?php $scripts = ($scripts ?? '') . '<script src="views/calendari_avvento/footer-editor.js"></script><script src="views/concorso/footer-editor.js"></script>'; include __DIR__ . '/_footer.php'; ?>
