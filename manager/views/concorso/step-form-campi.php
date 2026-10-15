<?php
if (!isset($waiting_container_default_padding) || !is_array($waiting_container_default_padding)) {
    $waiting_container_default_padding = [
        'top' => 20,
        'right' => 20,
        'bottom' => 20,
        'left' => 20,
    ];
}
if (!isset($waiting_preview_name)) {
    $waiting_preview_name = $content_current['name'] ?? ($content_defaults['name'] ?? 'Concorso');
}
if (!isset($waiting_preview_start) || !isset($waiting_preview_end)) {
    if (!function_exists('contest_manager_preview_formatted_dates')) {
        require_once __DIR__ . '/../../../app/inc/contest_date_helpers.php';
    }
    $waiting_preview_date_format = $content_current['date_format'] ?? ($content_defaults['date_format'] ?? 'd/m/Y');
    $_waiting_preview_dates = contest_manager_preview_formatted_dates($content_current, $content_defaults, $waiting_preview_date_format);
    if (!isset($waiting_preview_start)) {
        $waiting_preview_start = $_waiting_preview_dates['start'];
        $waiting_preview_start_raw = $_waiting_preview_dates['start_raw'];
    }
    if (!isset($waiting_preview_end)) {
        $waiting_preview_end = $_waiting_preview_dates['end'];
        $waiting_preview_end_raw = $_waiting_preview_dates['end_raw'];
    }
    unset($_waiting_preview_dates);
}
if (!isset($waiting_preview_min_spend)) {
    $waiting_preview_min_spend = number_format((float) ($content_current['min_spend'] ?? ($content_defaults['min_spend'] ?? 0)), 2, ',', '.');
}
if (!isset($waiting_preview_rules_link)) {
    $waiting_preview_rules_link = $content_current['rules_link'] ?? ($content_defaults['rules_link'] ?? '#');
}
if (!isset($thankyou_preview_prize_win_amount)) {
    $thankyou_preview_prize_win_amount = $thankyou_preview_prize_amount ?? '0,00';
}
?>
<div class="card mt-3 mb-4 config-anchor config-step configurazione-section form-campi-section" data-step="4" id="section-form">
                                <div class="card-body">
                                    <div class="configurazione-hero form-campi-heading">
                                        <div>
                                            <div class="section-title configurazione-title mb-1"><i class="fa fa-list-alt mr-2"></i>Registrazione</div>
                                            <p class="text-muted small mb-0">Gestione dei gruppi, dei campi personalizzati e dello stile del form pubblico.</p>
                                        </div>
                                        <span class="configurazione-badge"><i class="fa fa-list-ul mr-1"></i> Form pubblico</span>
                                    </div>
                                    <div class="configurazione-panel form-campi-main-panel">
                                        <div class="configurazione-panel-header">
                                            <span class="configurazione-panel-icon"><i class="fa fa-check-square-o"></i></span>
                                            <div>
                                                <h6>Campi abilitati</h6>
                                                <p>Seleziona quali campi del form rendere disponibili agli utenti.</p>
                                            </div>
                                        </div>
                                    <h6 class="text-uppercase text-muted mb-2 form-campi-section-label">
                                        Campi abilitati
                                        <span class="text-muted ml-1" data-toggle="tooltip" title="Seleziona quali campi del form rendere disponibili agli utenti."><i class="fa fa-question-circle"></i></span>
                                    </h6>
                                    <?php
                                        $fieldsDefaults = $content_defaults['fields_enabled'] ?? [];
                                        $fieldsRequiredDefaults = $content_defaults['fields_required'] ?? $fieldsDefaults;
                                        $fieldsRequiredCurrent = $content_current['fields_required'] ?? $fieldsRequiredDefaults;
                                        $fieldRequiredChecked = static function (string $key) use ($fieldsRequiredCurrent, $fieldsRequiredDefaults): bool {
                                            return (bool) ($fieldsRequiredCurrent[$key] ?? ($fieldsRequiredDefaults[$key] ?? true));
                                        };
                                        $fieldLabels = [
                                            'nome' => 'Nome',
                                            'cognome' => 'Cognome',
                                            'email' => 'Email',
                                            'prefisso' => 'Prefisso',
                                            'cellulare' => 'Cellulare',
                                            'fidelity' => 'Fidelity',
                                            'prov_pdv' => 'Provincia PDV',
                                            'citta_pdv' => 'Citta PDV',
                                            'indirizzo_pdv' => 'Indirizzo PDV',
                                            'importo' => 'Importo',
                                            'data_scontrino' => 'Data scontrino',
                                            'scontrino' => 'Scontrino',
                                            'provincia' => 'Provincia',
                                            'cap' => 'CAP',
                                            'citta' => 'Citta',
                                            'indirizzo' => 'Indirizzo',
                                            'privacy' => 'Privacy',
                                            'marketing' => 'Marketing',
                                            'profilazione' => 'Profilazione',
                                        ];
                                        $fieldGroups = [
                                            'Dati personali' => ['nome', 'cognome', 'email', 'prefisso', 'cellulare', 'cap', 'provincia', 'citta', 'indirizzo'],
                                            'Fidelity' => ['fidelity'],
                                            'Punto vendita' => ['prov_pdv', 'citta_pdv', 'indirizzo_pdv'],
                                            'Scontrino' => ['importo', 'data_scontrino', 'scontrino'],
                                            'Consensi' => ['privacy', 'marketing', 'profilazione'],
                                        ];
                                        $groupDescriptions = [
                                            'Dati personali' => 'Anagrafica, cellulare, CAP o provincia, indirizzo.',
                                            'Fidelity' => 'Codice fidelity.',
                                            'Punto vendita' => 'PDV combinato o cascata.',
                                            'Scontrino' => 'Importo, data e upload.',
                                            'Codice partecipazione' => 'Label, lunghezza, modalità (pool / IW+codice / combinazione).',
                                            'Consensi' => 'Privacy, marketing, profilazione.',
                                            'Altri' => 'Campi extra.',
                                        ];
                                        $currentContestMode = strtolower((string) ($content_current['contest_mode'] ?? ($content_defaults['contest_mode'] ?? 'estrazione')));
                                        $isInstantwinMode = $currentContestMode === 'instantwin';
                                        $specialFields = ['codice_partecipazione'];
                                        $usedFields = [];
                                        foreach ($fieldGroups as $groupFields) {
                                            $usedFields = array_merge($usedFields, $groupFields);
                                        }
                                        $extraFields = array_diff(array_keys($fieldsDefaults), $usedFields, $specialFields);
                                        if (!empty($extraFields)) {
                                            $fieldGroups['Altri'] = $extraFields;
                                        }
                                    ?>
                                    <div class="form-campi-shell">
                                        <aside class="form-campi-menu-panel">
                                            <div class="form-campi-menu-head">
                                                <strong>Menu gruppi</strong>
                                                <div class="small text-muted mt-1" id="form-campi-enabled-count">0 / 0 attivi</div>
                                            </div>
                                            <div class="form-campi-menu-list" id="form-campi-menu-list"></div>
                                        </aside>
                                        <div class="form-campi-editor-panel">
                                            <div class="configurazione-panel-header form-campi-panel-intro">
                                                <span class="configurazione-panel-icon"><i class="fa fa-list-ul"></i></span>
                                                <div>
                                                    <h6>Campi del form</h6>
                                                    <p>Ogni gruppo raccoglie campi e opzioni collegate al form di registrazione.</p>
                                                </div>
                                            </div>
                                            <div class="row form-campi-grid">
                                        <?php $groupIndex = 0; ?>
                                        <?php foreach ($fieldGroups as $groupTitle => $groupFields): ?>
                                            <?php if ($groupTitle === 'Consensi' && array_key_exists('codice_partecipazione', $fieldsDefaults)): ?>
                                                <?php
                                                    require_once __DIR__ . '/../../../app/logic/codice_partecipazione_validation.php';
                                                    $codiceEnabled = !empty($content_current['fields_enabled']['codice_partecipazione']);
                                                    $codiceRequired = $fieldRequiredChecked('codice_partecipazione');
                                                    $codiceLabelValue = (string) ($content_current['instantwin_codice_label'] ?? ($content_defaults['instantwin_codice_label'] ?? 'Codice partecipazione'));
                                                    $codiceLengthValue = (string) ($content_current['instantwin_codice_length'] ?? ($content_defaults['instantwin_codice_length'] ?? 12));
                                                    $codiceLengthAuto = !empty($content_current['instantwin_codice_length_auto'] ?? ($content_defaults['instantwin_codice_length_auto'] ?? false));
                                                    $codicePartModeValue = codice_partecipazione_resolve_mode(array_merge(
                                                        is_array($content_defaults) ? $content_defaults : [],
                                                        is_array($content_current) ? $content_current : []
                                                    ));
                                                    $codiceStrictValue = !empty($content_current['codice_partecipazione_strict_validation'] ?? ($content_defaults['codice_partecipazione_strict_validation'] ?? false));
                                                    $codicePoolLabel = $isInstantwinMode ? 'Instant Win (pool codici)' : 'Pool codici';
                                                ?>
                                                <div class="col-12 form-campi-panel-wrap d-flex">
                                                    <div class="section-card mb-3 w-100 field-group" data-group-id="codice-partecipazione" data-group-title="Codice partecipazione" data-no-checkbox-tooltips="1">
                                                        <div class="field-group-header d-flex align-items-center justify-content-between" role="button" tabindex="0">
                                                            <div>
                                                                <div class="section-subtitle mb-0">Codice partecipazione</div>
                                                                <div class="field-group-description"><?php echo manager_safe_html($groupDescriptions['Codice partecipazione']); ?></div>
                                                            </div>
                                                        </div>
                                                        <div class="row field-group-body">
                                                            <div class="col-12 field-option-pair field-option-pair-wide">
                                                                <div class="form-check mb-2 field-option">
                                                                    <input class="form-check-input field-checkbox" type="checkbox" id="field_codice_partecipazione" name="fields_enabled[codice_partecipazione]" value="1" <?php echo $codiceEnabled ? 'checked' : ''; ?>>
                                                                    <label class="form-check-label" for="field_codice_partecipazione">
                                                                        Abilita codice partecipazione
                                                                    </label>
                                                                </div>
                                                                <div class="form-check mb-2 field-option field-required-option">
                                                                    <input class="form-check-input field-required-checkbox" type="checkbox" id="field_codice_partecipazione_required" name="fields_required[codice_partecipazione]" value="1" <?php echo $codiceRequired ? 'checked' : ''; ?> <?php echo $codiceEnabled ? '' : 'disabled'; ?>>
                                                                    <label class="form-check-label" for="field_codice_partecipazione_required">Obbligatorio</label>
                                                                </div>
                                                                <small class="form-text text-muted">Se attivo e obbligatorio, viene richiesto nel form pubblico.</small>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <div class="form-group" id="instantwin_codice_label_panel">
                                                                    <label class="mb-1">Label codice <span class="text-muted ml-1" data-toggle="tooltip" title="Testo mostrato nel form pubblico (es. Codice partecipazione / Codice voucher / Codice scontrino)."><i class="fa fa-question-circle"></i></span></label>
                                                                    <input type="text" name="instantwin_codice_label" class="form-control w-100" value="<?php echo manager_safe_html($codiceLabelValue); ?>" placeholder="Es: Codice partecipazione">
                                                                    <small class="form-text text-muted">Opzionale: se vuoto, usa il default.</small>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <div class="form-group mb-0" id="instantwin_codice_length_panel">
                                                                    <label class="mb-1">Lunghezza codice <span class="text-muted ml-1" data-toggle="tooltip" title="Numero esatto di caratteri richiesti (es. 12 per AB12CD34EF56)."><i class="fa fa-question-circle"></i></span></label>
                                                                    <input type="number" min="1" max="255" name="instantwin_codice_length" class="form-control w-100" value="<?php echo manager_safe_html($codiceLengthValue); ?>" placeholder="Es: 12">
                                                                    <input type="hidden" name="instantwin_codice_length_auto" value="0">
                                                                </div>
                                                            </div>
                                                            <div class="col-12 d-none alert alert-warning py-2 mb-0" id="codice_partecipazione_pool_empty_alert" role="alert">
                                                                <i class="fa fa-exclamation-triangle mr-1"></i>
                                                                <strong>Pool vuoto.</strong> La tabella <code>codici_partecipazione</code> non contiene codici. Importali in <strong>Gestione Dati</strong> prima di aprire le iscrizioni.
                                                            </div>
                                                            <div class="col-12" id="codice_partecipazione_mode_wrapper">
                                                                <div class="form-group" id="codice_partecipazione_mode_panel">
                                                                    <label class="mb-1">Modalità codice <span class="text-muted ml-1" data-toggle="tooltip" title="Pool: il codice deve esistere in codici_partecipazione. Instant Win + codice: pool con premio pre-assegnato. Controllo combinazione: senza pool, validazione struttura 13 cifre."><i class="fa fa-question-circle"></i></span></label>
                                                                    <select name="codice_partecipazione_mode" id="codice_partecipazione_mode" class="form-control w-100">
                                                                        <option value="pool" <?php echo $codicePartModeValue === 'pool' ? 'selected' : ''; ?>><?php echo manager_safe_html($codicePoolLabel); ?></option>
                                                                        <option value="direct_win" data-iw-only="1" <?php echo $codicePartModeValue === 'direct_win' ? 'selected' : ''; ?>>Instant Win + codice</option>
                                                                        <option value="combinazione" <?php echo $codicePartModeValue === 'combinazione' ? 'selected' : ''; ?>>Controllo combinazione (senza pool)</option>
                                                                    </select>
                                                                    <small class="form-text text-muted" id="codice_partecipazione_mode_hint"></small>
                                                                </div>
                                                            </div>
                                                            <div class="col-12 d-none" id="codice_partecipazione_strict_panel">
                                                                <div class="form-check mb-0">
                                                                    <input class="form-check-input" type="checkbox" id="codice_partecipazione_strict_validation" name="codice_partecipazione_strict_validation" value="1" <?php echo $codiceStrictValue ? 'checked' : ''; ?>>
                                                                    <label class="form-check-label" for="codice_partecipazione_strict_validation">Validazione rafforzata (check digit GG = somma 11 cifre)</label>
                                                                </div>
                                                                <small class="form-text text-muted mb-0">OFF: solo plages A…GG. ON: aggiunge il controllo sulla cifra di controllo GG.</small>
                                                            </div>
                                                            <?php
                                                                $codiceHelpText = (string) ($content_current['codice_partecipazione_help_text'] ?? ($content_defaults['codice_partecipazione_help_text'] ?? ''));
                                                                $codiceHelpColorStored = trim((string) ($content_current['codice_partecipazione_help_text_color'] ?? ($content_defaults['codice_partecipazione_help_text_color'] ?? '')));
                                                                $codiceHelpColorSwatch = preg_match('/^#[0-9A-Fa-f]{6}$/', $codiceHelpColorStored)
                                                                    ? $codiceHelpColorStored
                                                                    : (string) ($content_current['colors']['text'] ?? ($content_defaults['colors']['text'] ?? '#212529'));
                                                            ?>
                                                            <div class="col-12">
                                                                <div class="form-group mb-0">
                                                                    <label>Testo di aiuto sotto il campo <span class="text-muted ml-1" data-toggle="tooltip" title="Testo piccolo sotto il campo codice. L'errore appare sotto questo testo."><i class="fa fa-question-circle"></i></span></label>
                                                                    <textarea name="codice_partecipazione_help_text" class="form-control" rows="2" maxlength="500" placeholder="es. inserisci il codice presente sullo scontrino"><?php echo manager_safe_html($codiceHelpText); ?></textarea>
                                                                    <label class="mt-2 mb-1 small">Colore testo di aiuto</label>
                                                                    <div class="d-flex align-items-center color-row">
                                                                        <input type="color" class="form-control color-input color-swatch" data-target="codice_partecipazione_help_text_color_text" value="<?php echo manager_safe_html($codiceHelpColorSwatch); ?>">
                                                                        <input type="text" name="codice_partecipazione_help_text_color" id="codice_partecipazione_help_text_color_text" class="form-control color-code-input" value="<?php echo manager_safe_html($codiceHelpColorStored); ?>" placeholder="Palette Testo" pattern="^$|^#[0-9A-Fa-f]{6}$">
                                                                    </div>
                                                                    <small class="form-text text-muted">Vuoto = colore Testo della palette.</small>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                            <?php
                                                $groupIndex++;
                                                $groupIdBase = strtolower(preg_replace('/[^a-z0-9]+/', '-', $groupTitle));
                                                $groupIdBase = trim($groupIdBase, '-');
                                                if ($groupIdBase === '') {
                                                    $groupIdBase = 'group';
                                                }
                                                $groupId = $groupIdBase . '-' . $groupIndex;
                                            ?>
                                            <div class="col-12 form-campi-panel-wrap d-flex">
                                                <div class="section-card mb-3 w-100 field-group" data-group-id="<?php echo manager_safe_html($groupId); ?>" data-group-title="<?php echo manager_safe_html($groupTitle); ?>" data-no-checkbox-tooltips="1">
                                                    <div class="field-group-header d-flex align-items-center justify-content-between" role="button" tabindex="0">
                                                        <div>
                                                            <div class="section-subtitle mb-0"><?php echo manager_safe_html($groupTitle); ?></div>
                                                            <div class="field-group-description"><?php echo manager_safe_html($groupDescriptions[$groupTitle] ?? ''); ?></div>
                                                        </div>
                                                        <div class="form-check mb-0 field-group-toggle">
                                                            <input class="form-check-input group-toggle" type="checkbox" id="group_toggle_<?php echo manager_safe_html($groupId); ?>">
                                                            <label class="form-check-label small text-muted" for="group_toggle_<?php echo manager_safe_html($groupId); ?>">Attiva gruppo</label>
                                                        </div>
                                                    </div>
                                                    <div class="row field-group-body">
                                                        <?php 
                                                        $skipNext = false;
                                                        $skipFields = [];
                                                        foreach ($groupFields as $fieldKey): 
                                                            if (in_array($fieldKey, $skipFields, true)) {
                                                                continue;
                                                            }
                                                            if ($skipNext) {
                                                                $skipNext = false;
                                                                continue;
                                                            }
                                                            if (!array_key_exists($fieldKey, $fieldsDefaults)) { continue; } 
                                                            
                                                            if ($fieldKey === 'prefisso' && in_array('cellulare', $groupFields)) {
                                                                $prefissoChecked = !empty($content_current['fields_enabled']['prefisso']);
                                                                $cellulareChecked = !empty($content_current['fields_enabled']['cellulare']);
                                                                $bothChecked = $prefissoChecked && $cellulareChecked;
                                                                $prefissoRequiredChecked = $fieldRequiredChecked('prefisso');
                                                                $cellulareRequiredChecked = $fieldRequiredChecked('cellulare');
                                                                $prefissoCellulareRequiredChecked = $prefissoRequiredChecked && $cellulareRequiredChecked;
                                                        ?>
                                                            <div class="col-12 field-option-pair">
                                                                <div class="form-check mb-2 field-option">
                                                                    <input class="form-check-input field-checkbox prefisso-cellulare-group" type="checkbox" id="field_prefisso_cellulare" data-field-prefisso="prefisso" data-field-cellulare="cellulare" <?php echo $bothChecked ? 'checked' : ''; ?>>
                                                                    <label class="form-check-label" for="field_prefisso_cellulare">
                                                                        Prefisso/Cellulare
                                                                    </label>
                                                                </div>
                                                                <input type="hidden" name="fields_enabled[prefisso]" id="field_prefisso_hidden" value="<?php echo $prefissoChecked ? '1' : ''; ?>">
                                                                <input type="hidden" name="fields_enabled[cellulare]" id="field_cellulare_hidden" value="<?php echo $cellulareChecked ? '1' : ''; ?>">
                                                                <input type="hidden" name="fields_required[prefisso]" id="field_prefisso_required_hidden" value="<?php echo $prefissoRequiredChecked ? '1' : ''; ?>">
                                                                <input type="hidden" name="fields_required[cellulare]" id="field_cellulare_required_hidden" value="<?php echo $cellulareRequiredChecked ? '1' : ''; ?>">
                                                                <div class="form-check mb-2 field-option field-required-option">
                                                                    <input class="form-check-input field-required-checkbox prefisso-cellulare-required-group" type="checkbox" id="field_prefisso_cellulare_required" <?php echo $prefissoCellulareRequiredChecked ? 'checked' : ''; ?> <?php echo $bothChecked ? '' : 'disabled'; ?>>
                                                                    <label class="form-check-label" for="field_prefisso_cellulare_required">Obbligatorio</label>
                                                                </div>
                                                            </div>
                                                            <?php
                                                                // In caso di POST (es. errore validazione), preferire i valori inviati per non perdere l'input
                                                                $postExact = isset($_POST['cellulare_digits_exact']) ? trim((string) $_POST['cellulare_digits_exact']) : '';
                                                                $postMin = isset($_POST['cellulare_digits_min']) ? trim((string) $_POST['cellulare_digits_min']) : '';
                                                                $postMax = isset($_POST['cellulare_digits_max']) ? trim((string) $_POST['cellulare_digits_max']) : '';
                                                                $fromPost = $_SERVER['REQUEST_METHOD'] === 'POST' && ($postExact !== '' || $postMin !== '' || $postMax !== '');
                                                                if ($fromPost) {
                                                                    $cellulareExactValue = $postExact !== '' ? $postExact : '';
                                                                    $cellulareMinDisplay = $postMin !== '' ? $postMin : '';
                                                                    $cellulareMaxDisplay = $postMax !== '' ? $postMax : '';
                                                                } else {
                                                                    $hasMin = isset($content_current['cellulare_digits_min']) && $content_current['cellulare_digits_min'] !== '' && $content_current['cellulare_digits_min'] !== null;
                                                                    $hasMax = isset($content_current['cellulare_digits_max']) && $content_current['cellulare_digits_max'] !== '' && $content_current['cellulare_digits_max'] !== null;
                                                                    $hasExact = isset($content_current['cellulare_digits']) && $content_current['cellulare_digits'] !== '' && $content_current['cellulare_digits'] !== null;
                                                                    $cellulareMin = $hasMin ? max(6, min(12, (int) $content_current['cellulare_digits_min'])) : ($hasExact ? max(6, min(12, (int) $content_current['cellulare_digits'])) : null);
                                                                    $cellulareMax = $hasMax ? max(6, min(12, (int) $content_current['cellulare_digits_max'])) : ($hasExact ? max(6, min(12, (int) $content_current['cellulare_digits'])) : null);
                                                                    if ($cellulareMin !== null && $cellulareMax !== null && $cellulareMin > $cellulareMax) {
                                                                        $cellulareMax = $cellulareMin;
                                                                    }
                                                                    $cellulareUseExact = ($cellulareMin !== null && $cellulareMax !== null && $cellulareMin === $cellulareMax);
                                                                    $cellulareExactValue = $cellulareUseExact ? $cellulareMin : '';
                                                                    $cellulareMinDisplay = ($cellulareMin !== null && !$cellulareUseExact) ? $cellulareMin : '';
                                                                    $cellulareMaxDisplay = ($cellulareMax !== null && !$cellulareUseExact) ? $cellulareMax : '';
                                                                    if ($cellulareUseExact) {
                                                                        $cellulareMinDisplay = '';
                                                                        $cellulareMaxDisplay = '';
                                                                    }
                                                                }
                                                                $cellulareDigitsPanelClass = $bothChecked ? '' : ' d-none';
                                                            ?>
                                                            <div class="col-12">
                                                                <div class="form-group cellulare-digits-panel<?php echo $cellulareDigitsPanelClass; ?>" id="cellulare_digits_panel">
                                                                    <label>Numero di cifre (cellulare) <span class="text-muted ml-1" data-toggle="tooltip" title="Lascia vuoto = 10 cifre. Numero esatto = solo N cifre (se compilato, min/max vengono ignorati). Altrimenti usa min e max per l'intervallo."><i class="fa fa-question-circle"></i></span></label>
                                                                    <div class="form-row align-items-end">
                                                                        <div class="form-group col-md-3 col-6 mb-2 mb-md-0">
                                                                            <label class="small">Numero di cifre esatto</label>
                                                                            <div class="input-group">
                                                                                <input type="number" min="6" max="12" name="cellulare_digits_exact" id="cellulare_digits_exact" class="form-control" value="<?php echo $cellulareExactValue !== '' ? manager_safe_html((string) $cellulareExactValue) : ''; ?>" placeholder="Es: 10">
                                                                                <div class="input-group-append">
                                                                                    <button type="button" class="btn btn-outline-secondary cellulare-clear-btn" data-target="cellulare_digits_exact" title="Svuota" aria-label="Svuota"><i class="fa fa-times"></i></button>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-md-1 col-12 text-muted small mb-2 mb-md-0">oppure</div>
                                                                        <div class="form-group col-md-3 col-6 mb-2 mb-md-0">
                                                                            <label class="small" for="cellulare_digits_min">Valore min</label>
                                                                            <div class="input-group">
                                                                                <input type="number" min="6" max="12" name="cellulare_digits_min" id="cellulare_digits_min" class="form-control" value="<?php echo $cellulareMinDisplay !== '' ? manager_safe_html((string) $cellulareMinDisplay) : ''; ?>" placeholder="Es: 6">
                                                                                <div class="input-group-append">
                                                                                    <button type="button" class="btn btn-outline-secondary cellulare-clear-btn" data-target="cellulare_digits_min" title="Svuota" aria-label="Svuota"><i class="fa fa-times"></i></button>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                        <div class="form-group col-md-3 col-6 mb-2 mb-md-0">
                                                                            <label class="small" for="cellulare_digits_max">Valore max</label>
                                                                            <div class="input-group">
                                                                                <input type="number" min="6" max="12" name="cellulare_digits_max" id="cellulare_digits_max" class="form-control" value="<?php echo $cellulareMaxDisplay !== '' ? manager_safe_html((string) $cellulareMaxDisplay) : ''; ?>" placeholder="Es: 12">
                                                                                <div class="input-group-append">
                                                                                    <button type="button" class="btn btn-outline-secondary cellulare-clear-btn" data-target="cellulare_digits_max" title="Svuota" aria-label="Svuota"><i class="fa fa-times"></i></button>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        <?php 
                                                                $skipNext = true;
                                                                continue;
                                                            }
                                                            if ($fieldKey === 'prov_pdv' && in_array('citta_pdv', $groupFields, true) && in_array('indirizzo_pdv', $groupFields, true)) {
                                                                $pdvChecked = !empty($content_current['fields_enabled']['prov_pdv'])
                                                                    || !empty($content_current['fields_enabled']['citta_pdv'])
                                                                    || !empty($content_current['fields_enabled']['indirizzo_pdv']);
                                                                $pdvRequiredChecked = $fieldRequiredChecked('prov_pdv')
                                                                    && $fieldRequiredChecked('citta_pdv')
                                                                    && $fieldRequiredChecked('indirizzo_pdv');
                                                                $pdvConfig = $content_current['pdv_config'] ?? ($content_defaults['pdv_config'] ?? []);
                                                                $pdvMode = strtolower((string)($pdvConfig['mode'] ?? 'combinato'));
                                                                $pdvMode = in_array($pdvMode, ['combinato', 'cascata'], true) ? $pdvMode : 'combinato';
                                                                $combinatoLabelFields = $pdvConfig['combinato']['label_fields'] ?? ['ragsoc', 'indirizzo', 'citta', 'prov'];
                                                                if (!is_array($combinatoLabelFields)) {
                                                                    $combinatoLabelFields = ['ragsoc', 'indirizzo', 'citta', 'prov'];
                                                                }
                                                                $combinatoSeparator = $pdvConfig['combinato']['separator'] ?? ' - ';
                                                                $combinatoSearchLimit = (int) ($pdvConfig['combinato']['search_limit'] ?? 50);
                                                                $combinatoSearchLimitAllowed = [50, 100, 150, 200, 20000];
                                                                if (!in_array($combinatoSearchLimit, $combinatoSearchLimitAllowed, true)) {
                                                                    $combinatoSearchLimit = 50;
                                                                }
                                                                $cascataDisplay = strtolower((string)($pdvConfig['cascata']['punto_vendita_display'] ?? 'insegna_indirizzo'));
                                                                $cascataDisplay = in_array($cascataDisplay, ['indirizzo_only', 'indirizzo_insegna', 'insegna_indirizzo'], true) ? $cascataDisplay : 'insegna_indirizzo';
                                                                $cascataGeographicMode = strtolower((string)($pdvConfig['cascata']['geographic_mode'] ?? 'provincia'));
                                                                $cascataGeographicMode = in_array($cascataGeographicMode, ['provincia', 'regione'], true) ? $cascataGeographicMode : 'provincia';
                                                                $pdvDbColumns = [];
                                                                try {
                                                                    $pdoPdvMeta = conn_pdo();
                                                                    $stmtPdvMeta = $pdoPdvMeta->query("SHOW COLUMNS FROM punti_vendita");
                                                                    if ($stmtPdvMeta) {
                                                                        while ($col = $stmtPdvMeta->fetch(PDO::FETCH_ASSOC)) {
                                                                            $name = strtolower(trim((string)($col['Field'] ?? '')));
                                                                            if ($name !== '') {
                                                                                $pdvDbColumns[$name] = true;
                                                                            }
                                                                        }
                                                                    }
                                                                } catch (Throwable $e) {
                                                                    // Silent fallback: show standard fields only.
                                                                }
                                                                $pdvFieldMeta = [
                                                                    'ragsoc' => ['label' => 'Ragione sociale', 'aliases' => ['ragsoc']],
                                                                    'insegna' => ['label' => 'Insegna', 'aliases' => ['insegna']],
                                                                    'indirizzo' => ['label' => 'Indirizzo', 'aliases' => ['indirizzo']],
                                                                    'citta' => ['label' => 'Città', 'aliases' => ['citta', 'città']],
                                                                    'prov' => ['label' => 'Provincia', 'aliases' => ['prov', 'provincia']],
                                                                    'regione' => ['label' => 'Regione', 'aliases' => ['regione']],
                                                                    'cap' => ['label' => 'CAP', 'aliases' => ['cap']],
                                                                ];
                                                                $pdvDetectedFields = [];
                                                                foreach ($pdvFieldMeta as $fieldKeyMeta => $meta) {
                                                                    foreach ($meta['aliases'] as $alias) {
                                                                        if (isset($pdvDbColumns[strtolower($alias)])) {
                                                                            $pdvDetectedFields[] = $fieldKeyMeta;
                                                                            break;
                                                                        }
                                                                    }
                                                                }
                                                                if (empty($pdvDetectedFields)) {
                                                                    $pdvDetectedFields = ['ragsoc', 'insegna', 'indirizzo', 'citta', 'prov', 'regione', 'cap'];
                                                                }
                                                        ?>
                                                            <div class="col-12 field-option-pair">
                                                                <div class="form-check mb-2 field-option">
                                                                    <input class="form-check-input field-checkbox pdv-group" type="checkbox" id="field_pdv_group" name="fields_enabled_pdv_group" value="1" <?php echo $pdvChecked ? 'checked' : ''; ?>>
                                                                    <label class="form-check-label" for="field_pdv_group">
                                                                        Provincia/Citta/Indirizzo PDV
                                                                    </label>
                                                                </div>
                                                                <div class="form-check mb-2 field-option field-required-option">
                                                                    <input class="form-check-input field-required-checkbox pdv-required-group" type="checkbox" id="field_pdv_group_required" name="fields_required_pdv_group" value="1" <?php echo $pdvRequiredChecked ? 'checked' : ''; ?> <?php echo $pdvChecked ? '' : 'disabled'; ?>>
                                                                    <label class="form-check-label" for="field_pdv_group_required">Obbligatorio</label>
                                                                </div>
                                                            </div>
                                                            <div class="col-12 pdv-config-panel<?php echo $pdvChecked ? '' : ' d-none'; ?>" id="pdv_config_panel">
                                                                <div class="card card-body bg-light mt-2">
                                                                    <h6 class="text-muted mb-2">Configurazione selezione Punto Vendita</h6>
                                                                    <div class="form-group">
                                                                        <label>Modalità</label>
                                                                        <select name="pdv_config[mode]" class="form-control pdv-mode-select" id="pdv_config_mode">
                                                                            <option value="combinato" <?php echo $pdvMode === 'combinato' ? 'selected' : ''; ?>>Combinato (campo unico con ricerca)</option>
                                                                            <option value="cascata" <?php echo $pdvMode === 'cascata' ? 'selected' : ''; ?>>Cascata</option>
                                                                        </select>
                                                                    </div>
                                                                    <div class="pdv-combinato-options<?php echo $pdvMode === 'combinato' ? '' : ' d-none'; ?>" id="pdv_combinato_options">
                                                                        <div class="form-group">
                                                                            <label>Campi nel label (ordine e selezione)</label>
                                                                            <small class="form-text text-muted d-block mb-1">Ordine desiderato, separati da virgola. Es: ragsoc, indirizzo, citta, prov, regione</small>
                                                                            <input type="text" name="pdv_config[combinato][label_fields_str]" class="form-control" value="<?php echo manager_safe_html(implode(', ', $combinatoLabelFields)); ?>" placeholder="ragsoc, indirizzo, citta, prov, regione">
                                                                            <small class="form-text text-muted">Campi rilevati in <code>punti_vendita</code>: <?php echo manager_safe_html(implode(', ', $pdvDetectedFields)); ?></small>
                                                                            <div class="mt-2">
                                                                                <?php foreach ($pdvDetectedFields as $df): ?>
                                                                                    <button type="button" class="btn btn-sm btn-outline-secondary mr-1 mb-1 pdv-label-field-chip" data-field="<?php echo manager_safe_html($df); ?>"><?php echo manager_safe_html($df); ?></button>
                                                                                <?php endforeach; ?>
                                                                            </div>
                                                                        </div>
                                                                        <div class="form-group">
                                                                            <label>Separatore tra i campi</label>
                                                                            <input type="text" name="pdv_config[combinato][separator]" class="form-control" value="<?php echo manager_safe_html($combinatoSeparator); ?>" placeholder=" - " style="max-width: 120px;">
                                                                        </div>
                                                                        <div class="form-group mb-0">
                                                                            <label>Max risultati ricerca PDV (combinato)</label>
                                                                            <select name="pdv_config[combinato][search_limit]" class="form-control" style="max-width: 220px;">
                                                                                <?php
                                                                                $slOpts = [
                                                                                    50 => '50',
                                                                                    100 => '100',
                                                                                    150 => '150',
                                                                                    200 => '200',
                                                                                    20000 => 'Tutti (fino a 20000)',
                                                                                ];
                                                                                foreach ($slOpts as $slVal => $slLabel) {
                                                                                    echo '<option value="' . (int) $slVal . '"' . ($combinatoSearchLimit === (int) $slVal ? ' selected' : '') . '>' . manager_safe_html($slLabel) . '</option>';
                                                                                }
                                                                                ?>
                                                                            </select>
                                                                            <small class="form-text text-muted">Usato da <code>public/search_pdv.php</code> per provincia, città, indirizzo e ricerca combinata. Opzionale: variabile d’ambiente <code>PDV_SEARCH_LIMIT</code> (numerico) ha priorità sul valore salvato.</small>
                                                                        </div>
                                                                    </div>
                                                                    <div class="pdv-cascata-options<?php echo $pdvMode === 'cascata' ? '' : ' d-none'; ?>" id="pdv_cascata_options">
                                                                        <div class="form-group">
                                                                            <label>Area geografica della cascata</label>
                                                                            <select name="pdv_config[cascata][geographic_mode]" class="form-control">
                                                                                <option value="provincia" <?php echo $cascataGeographicMode === 'provincia' ? 'selected' : ''; ?>>Provincia - Citta - Punto vendita</option>
                                                                                <option value="regione" <?php echo $cascataGeographicMode === 'regione' ? 'selected' : ''; ?>>Regione - Citta - Punto vendita</option>
                                                                            </select>
                                                                        </div>
                                                                        <div class="form-group mb-0">
                                                                            <label>Nel campo "Punto vendita" mostra</label>
                                                                            <select name="pdv_config[cascata][punto_vendita_display]" class="form-control">
                                                                                <option value="indirizzo_insegna" <?php echo $cascataDisplay === 'indirizzo_insegna' ? 'selected' : ''; ?>>Indirizzo + Insegna/Ragione sociale</option>
                                                                                <option value="insegna_indirizzo" <?php echo $cascataDisplay === 'insegna_indirizzo' ? 'selected' : ''; ?>>Insegna/Ragione sociale + Indirizzo</option>
                                                                                <option value="indirizzo_only" <?php echo $cascataDisplay === 'indirizzo_only' ? 'selected' : ''; ?>>Solo indirizzo</option>
                                                                            </select>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        <?php
                                                                $skipFields[] = 'citta_pdv';
                                                                $skipFields[] = 'indirizzo_pdv';
                                                                continue;
                                                            }
                                                            $label = $fieldLabels[$fieldKey] ?? ucfirst(str_replace('_', ' ', $fieldKey));
                                                            $fieldEnabledChecked = !empty($content_current['fields_enabled'][$fieldKey]);
                                                            $fieldRequiredIsChecked = $fieldRequiredChecked($fieldKey);
                                                            $xorClass = ($fieldKey === 'cap' || $fieldKey === 'provincia') ? ' field-cap-provincia-xor' : '';
                                                        ?>
                                                            <div class="col-12 field-option-pair">
                                                                <div class="form-check mb-2 field-option">
                                                                    <input class="form-check-input field-checkbox<?php echo $xorClass; ?>" type="checkbox" id="field_<?php echo manager_safe_html($fieldKey); ?>" name="fields_enabled[<?php echo manager_safe_html($fieldKey); ?>]" data-field-key="<?php echo manager_safe_html($fieldKey); ?>" <?php echo $fieldEnabledChecked ? 'checked' : ''; ?>>
                                                                    <label class="form-check-label" for="field_<?php echo manager_safe_html($fieldKey); ?>">
                                                                        <?php echo manager_safe_html($label); ?>
                                                                        <?php if ($fieldKey === 'cap'): ?>
                                                                            <span class="text-muted small ml-1">(5 cifre)</span>
                                                                        <?php endif; ?>
                                                                    </label>
                                                                </div>
                                                                <div class="form-check mb-2 field-option field-required-option">
                                                                    <input class="form-check-input field-required-checkbox" type="checkbox" id="field_<?php echo manager_safe_html($fieldKey); ?>_required" name="fields_required[<?php echo manager_safe_html($fieldKey); ?>]" value="1" <?php echo $fieldRequiredIsChecked ? 'checked' : ''; ?> <?php echo $fieldEnabledChecked ? '' : 'disabled'; ?>>
                                                                    <label class="form-check-label" for="field_<?php echo manager_safe_html($fieldKey); ?>_required">Obbligatorio</label>
                                                                </div>
                                                            </div>
                                                            <?php if ($fieldKey === 'provincia'): ?>
                                                                <div class="col-12 mb-2">
                                                                    <small class="form-text text-muted mb-0">Scegli <strong>Provincia</strong> oppure <strong>CAP</strong> (non entrambi). Con CAP, Città elenca tutte le città senza filtro provincia.</small>
                                                                </div>
                                                            <?php endif; ?>
                                                            <?php if ($groupTitle === 'Fidelity' && $fieldKey === 'fidelity'): ?>
                                                                <?php
                                                                    $fidelityEnabled = !empty($content_current['fields_enabled']['fidelity']);
                                                                    $fidelityConfigPanelClass = $fidelityEnabled ? '' : ' d-none';
                                                                ?>
                                                                <div class="col-12">
                                                                    <div class="form-row fidelity-config-panel<?php echo $fidelityConfigPanelClass; ?>" id="fidelity_config_panel">
                                                                        <div class="form-group col-md-6">
                                                                            <label>Tipo codice fidelity</label>
                                                                            <select name="fidelity_type" class="form-control">
                                                                                <option value="numeric" <?php echo ($content_current['fidelity']['type'] ?? '') === 'numeric' ? 'selected' : ''; ?>>Numerico</option>
                                                                                <option value="alphanumeric" <?php echo ($content_current['fidelity']['type'] ?? '') === 'alphanumeric' ? 'selected' : ''; ?>>Alfanumerico</option>
                                                                            </select>
                                                                        </div>
                                                                        <div class="form-group col-md-6">
                                                                            <label>Lunghezza</label>
                                                                            <input type="number" min="1" name="fidelity_length" class="form-control" value="<?php echo manager_safe_html((string)($content_current['fidelity']['length'] ?? 13)); ?>">
                                                                        </div>
                                                                        <?php
                                                                            $fidelityFieldWidth = isset($content_current['fidelity']['field_width']) ? trim((string) $content_current['fidelity']['field_width']) : 'large';
                                                                            if (!in_array($fidelityFieldWidth, ['small', 'large'], true)) {
                                                                                $fidelityFieldWidth = 'large';
                                                                            }
                                                                        ?>
                                                                        <div class="form-group col-md-6">
                                                                            <label>Larghezza nel form <span class="text-muted ml-1" data-toggle="tooltip" title="Large = due colonne come Indirizzo o Foto scontrino. Small = una colonna."><i class="fa fa-question-circle"></i></span></label>
                                                                            <select name="fidelity_field_width" class="form-control">
                                                                                <option value="large" <?php echo $fidelityFieldWidth === 'large' ? 'selected' : ''; ?>>Large (600px - 2 colonne)</option>
                                                                                <option value="small" <?php echo $fidelityFieldWidth === 'small' ? 'selected' : ''; ?>>Small (288px - 1 colonna)</option>
                                                                            </select>
                                                                        </div>
                                                                        <div class="form-group col-12">
                                                                            <label>Testo di aiuto sotto il campo <span class="text-muted ml-1" data-toggle="tooltip" title="Testo piccolo mostrato sotto l'input fidelity (es. istruzioni tessera). Il messaggio di errore appare sotto questo testo."><i class="fa fa-question-circle"></i></span></label>
                                                                            <textarea name="fidelity_help_text" class="form-control" rows="2" maxlength="500" placeholder="es. numero sul retro della tessera - inserire le 13 cifre compreso lo 0 iniziale"><?php echo manager_safe_html((string)($content_current['fidelity']['help_text'] ?? '')); ?></textarea>
                                                                            <?php
                                                                                $fidelityHelpColorStored = trim((string) ($content_current['fidelity']['help_text_color'] ?? ($content_defaults['fidelity']['help_text_color'] ?? '')));
                                                                                $fidelityHelpColorSwatch = preg_match('/^#[0-9A-Fa-f]{6}$/', $fidelityHelpColorStored)
                                                                                    ? $fidelityHelpColorStored
                                                                                    : (string) ($content_current['colors']['text'] ?? ($content_defaults['colors']['text'] ?? '#212529'));
                                                                            ?>
                                                                            <label class="mt-2 mb-1 small">Colore testo di aiuto</label>
                                                                            <div class="d-flex align-items-center color-row">
                                                                                <input type="color" class="form-control color-input color-swatch" data-target="fidelity_help_text_color_text" value="<?php echo manager_safe_html($fidelityHelpColorSwatch); ?>">
                                                                                <input type="text" name="fidelity_help_text_color" id="fidelity_help_text_color_text" class="form-control color-code-input" value="<?php echo manager_safe_html($fidelityHelpColorStored); ?>" placeholder="Palette Testo" pattern="^$|^#[0-9A-Fa-f]{6}$">
                                                                            </div>
                                                                            <small class="form-text text-muted">Vuoto = colore Testo della palette.</small>
                                                                        </div>
                                                                        <div class="form-group col-12 mb-0">
                                                                            <div class="form-check">
                                                                                <input class="form-check-input" type="checkbox" id="fidelity_unique_participation" name="fidelity_unique_participation" value="1" <?php echo !empty($content_current['fidelity']['unique_participation']) ? 'checked' : ''; ?>>
                                                                                <label class="form-check-label" for="fidelity_unique_participation">
                                                                                    Fidelity univoca (1 partecipazione massima)
                                                                                </label>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            <?php endif; ?>
                                                            <?php if ($groupTitle === 'Scontrino' && $fieldKey === 'importo'): ?>
                                                                <?php
                                                                    $importoEnabled = (bool) ($content_current['fields_enabled']['importo'] ?? ($content_defaults['fields_enabled']['importo'] ?? true));
                                                                    $minSpendPanelClass = $importoEnabled ? '' : ' d-none';
                                                                ?>
                                                                <div class="col-12">
                                                                    <div class="form-group min-spend-panel<?php echo $minSpendPanelClass; ?>" id="min_spend_panel">
                                                                        <label>Spesa minima (&euro;) <span class="text-muted ml-1" data-toggle="tooltip" title="Importo minimo dello scontrino per la partecipazione. Usato anche nei testi dinamici."><i class="fa fa-question-circle"></i></span></label>
                                                                        <input type="text" inputmode="decimal" name="min_spend" class="form-control js-format-number-it w-50" value="<?php echo manager_safe_html((string)($content_current['min_spend'] ?? '')); ?>">
                                                                    </div>
                                                                </div>
                                                            <?php endif; ?>
                                                        <?php endforeach; ?>
                                                        <?php if ($groupTitle === 'Consensi'): ?>
                                                            <?php
                                                                $consensiTexts = $content_current['consensi_texts'] ?? ($content_defaults['consensi_texts'] ?? []);
                                                                $privacyText = trim((string)($consensiTexts['privacy'] ?? ''));
                                                                if ($privacyText === '') {
                                                                    $privacyText = 'Dichiaro di aver preso visione e di accettare {{PRIVACY_LINK}} *';
                                                                }
                                                                $marketingText = trim((string)($consensiTexts['marketing'] ?? ''));
                                                                if ($marketingText === '') {
                                                                    $marketingText = 'Autorizzo il trattamento dei dati per finalità di marketing (i.e. attività promozionale, commerciale e pubblicitaria)';
                                                                }
                                                                $profilazioneText = trim((string)($consensiTexts['profilazione'] ?? ''));
                                                                if ($profilazioneText === '') {
                                                                    $profilazioneText = 'Autorizzo il trattamento dei dati per finalità di profilazione (per ricevere comunicazioni personalizzate in base ai miei gusti e interessi)';
                                                                }
                                                                $privacyEnabled = !empty($content_current['fields_enabled']['privacy']);
                                                                $marketingEnabled = !empty($content_current['fields_enabled']['marketing']);
                                                                $profilazioneEnabled = !empty($content_current['fields_enabled']['profilazione']);
                                                                $hasAnyConsensiEnabled = $privacyEnabled || $marketingEnabled || $profilazioneEnabled;
                                                            ?>
                                                            <div class="col-12 mt-2 consensi-texts-block<?php echo $hasAnyConsensiEnabled ? '' : ' d-none'; ?>" id="consensi_texts_block">
                                                                <a href="#consensi-texts-collapse" class="btn btn-link btn-sm p-0 text-primary" data-toggle="collapse" role="button" aria-expanded="false" aria-controls="consensi-texts-collapse">
                                                                    <i class="fa fa-pencil-alt mr-1"></i>Modifica testi consensi
                                                                </a>
                                                                <div class="collapse mt-2" id="consensi-texts-collapse">
                                                                    <div class="form-group mb-2 consensi-text-row<?php echo $privacyEnabled ? '' : ' d-none'; ?>" id="consensi_text_privacy_row" data-consenso="privacy">
                                                                        <label class="mb-1 small">Privacy</label>
                                                                        <textarea name="consensi_texts[privacy]" class="form-control form-control-sm" rows="2" placeholder="Usa {{PRIVACY_LINK}} per il link all'informativa"><?php echo manager_safe_html($privacyText); ?></textarea>
                                                                    </div>
                                                                    <div class="form-group mb-2 consensi-text-row<?php echo $marketingEnabled ? '' : ' d-none'; ?>" id="consensi_text_marketing_row" data-consenso="marketing">
                                                                        <label class="mb-1 small">Marketing</label>
                                                                        <textarea name="consensi_texts[marketing]" class="form-control form-control-sm" rows="2"><?php echo manager_safe_html($marketingText); ?></textarea>
                                                                    </div>
                                                                    <div class="form-group mb-0 consensi-text-row<?php echo $profilazioneEnabled ? '' : ' d-none'; ?>" id="consensi_text_profilazione_row" data-consenso="profilazione">
                                                                        <label class="mb-1 small">Profilazione</label>
                                                                        <textarea name="consensi_texts[profilazione]" class="form-control form-control-sm" rows="2"><?php echo manager_safe_html($profilazioneText); ?></textarea>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        <?php endif; ?>
                                                        <?php if ($groupTitle === 'Scontrino'): ?>
                                                            <?php
                                                                $scontrinoEnabled = (bool) ($content_current['fields_enabled']['scontrino'] ?? ($content_defaults['fields_enabled']['scontrino'] ?? true));
                                                                $receiptPanelClass = $scontrinoEnabled ? '' : ' d-none';
                                                            ?>
                                                            <div class="col-12">
                                                                <div class="form-group max-upload-panel<?php echo $receiptPanelClass; ?>" id="max_upload_panel">
                                                                    <label>Max upload (MB) <span class="text-muted ml-1" data-toggle="tooltip" title="Dimensione massima del file scontrino (scegli da 4 a 8 MB)."><i class="fa fa-question-circle"></i></span></label>
                                                                    <select name="max_file_size_mb" class="form-control w-50">
                                                                        <?php foreach ($maxUploadOptionsMb as $sizeMb): ?>
                                                                            <option value="<?php echo $sizeMb; ?>" <?php echo $sizeMb === $maxFileSizeMbSelected ? 'selected' : ''; ?>>
                                                                                <?php echo $sizeMb; ?> MB
                                                                            </option>
                                                                        <?php endforeach; ?>
                                                                    </select>
                                                                </div>
                                                            </div>
                                                            <div class="col-12">
                                                                <div class="form-group mb-0 receipt-types-panel<?php echo $receiptPanelClass; ?>" id="receipt_types_panel" data-no-checkbox-tooltips="1">
                                                                    <label>Tipi di file scontrino consentiti <span class="text-muted ml-1" data-toggle="tooltip" title="Seleziona i formati accettati per l'upload dello scontrino."><i class="fa fa-question-circle"></i></span></label>
                                                                    <div class="d-flex flex-wrap align-items-center">
                                                                        <?php foreach ($receiptFileTypeOptions as $typeKey => $typeInfo): ?>
                                                                            <div class="form-check mr-3 mb-2">
                                                                                <input class="form-check-input" type="checkbox" name="receipt_allowed_extensions[]" id="receipt_type_<?php echo $typeKey; ?>" value="<?php echo $typeKey; ?>" <?php echo in_array($typeKey, $receiptAllowedTypesSelected, true) ? 'checked' : ''; ?>>
                                                                                <label class="form-check-label" for="receipt_type_<?php echo $typeKey; ?>"><?php echo manager_safe_html($typeInfo['label']); ?></label>
                                                                            </div>
                                                                        <?php endforeach; ?>
                                                                    </div>
                                                                    <small class="form-text text-muted d-none">Scegli uno o piu formati; se nessuno e selezionato la configurazione non verra salvata.</small>
                                                                </div>
                                                            </div>
                                                            <?php
                                                                $scontrinoHelpText = (string) ($content_current['scontrino_help_text'] ?? ($content_defaults['scontrino_help_text'] ?? ''));
                                                                $scontrinoHelpColorStored = trim((string) ($content_current['scontrino_help_text_color'] ?? ($content_defaults['scontrino_help_text_color'] ?? '')));
                                                                $scontrinoHelpColorSwatch = preg_match('/^#[0-9A-Fa-f]{6}$/', $scontrinoHelpColorStored)
                                                                    ? $scontrinoHelpColorStored
                                                                    : (string) ($content_current['colors']['text'] ?? ($content_defaults['colors']['text'] ?? '#212529'));
                                                            ?>
                                                            <div class="col-12">
                                                                <div class="form-group mb-0 receipt-help-panel<?php echo $receiptPanelClass; ?>" id="receipt_help_panel">
                                                                    <label>Testo di aiuto sotto il campo <span class="text-muted ml-1" data-toggle="tooltip" title="Testo piccolo sotto Scegli file. L'errore appare sotto questo testo."><i class="fa fa-question-circle"></i></span></label>
                                                                    <textarea name="scontrino_help_text" class="form-control" rows="2" maxlength="500" placeholder="es. carica una foto nitida dello scontrino"><?php echo manager_safe_html($scontrinoHelpText); ?></textarea>
                                                                    <label class="mt-2 mb-1 small">Colore testo di aiuto</label>
                                                                    <div class="d-flex align-items-center color-row">
                                                                        <input type="color" class="form-control color-input color-swatch" data-target="scontrino_help_text_color_text" value="<?php echo manager_safe_html($scontrinoHelpColorSwatch); ?>">
                                                                        <input type="text" name="scontrino_help_text_color" id="scontrino_help_text_color_text" class="form-control color-code-input" value="<?php echo manager_safe_html($scontrinoHelpColorStored); ?>" placeholder="Palette Testo" pattern="^$|^#[0-9A-Fa-f]{6}$">
                                                                    </div>
                                                                    <small class="form-text text-muted">Vuoto = colore Testo della palette.</small>
                                                                </div>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                            </div>
                                        </div>
                                    </div>
                                    </div>
                                        <?php
$standard_fields_order_ui = gst_standard_fields_normalize_order(
    $content_current['standard_fields_order'] ?? ($content_defaults['standard_fields_order'] ?? null)
);
$standard_fields_order_labels = gst_standard_fields_order_labels();
$fieldsEnabledForOrder = $content_current['fields_enabled'] ?? [];
if (!is_array($fieldsEnabledForOrder)) {
    $fieldsEnabledForOrder = [];
}
$gstOrderKeyIsEnabled = static function (string $orderKey, array $fieldsEnabled): bool {
    switch ($orderKey) {
        case 'prefisso_cellulare':
            return !empty($fieldsEnabled['prefisso']) || !empty($fieldsEnabled['cellulare']);
        case 'cap_provincia':
            return !empty($fieldsEnabled['cap']) || !empty($fieldsEnabled['provincia']);
        case 'indirizzo_pdv':
            return !empty($fieldsEnabled['indirizzo_pdv'])
                || !empty($fieldsEnabled['prov_pdv'])
                || !empty($fieldsEnabled['citta_pdv']);
        default:
            return !empty($fieldsEnabled[$orderKey]);
    }
};
?>
<div class="col-12 d-flex form-campi-order-wrap mb-3">
    <div class="section-card mb-0 w-100 configurazione-panel">
        <div class="configurazione-panel-header form-campi-panel-intro">
            <span class="configurazione-panel-icon"><i class="fa fa-arrows-v"></i></span>
            <div>
                <h6>Ordine campi</h6>
                <p>Personalizza l’ordine dei campi nel form pubblico. I consensi restano sempre alla fine.</p>
            </div>
        </div>
        <div class="px-3 pb-3">
            <a href="#standard-fields-order-collapse" id="standard-fields-order-toggle" class="d-flex align-items-center standard-fields-order-toggle text-dark text-decoration-none" data-toggle="collapse" role="button" aria-expanded="false" aria-controls="standard-fields-order-collapse">
                <span class="mb-0 mr-1 font-weight-bold">Modifica ordine campi</span>
                <span class="text-muted small">(trascina per spostare)</span>
                <i class="fa fa-chevron-down standard-fields-order-chevron ml-auto"></i>
            </a>
            <div class="collapse mt-2" id="standard-fields-order-collapse">
                <ul id="standard-fields-order-list" class="list-group standard-fields-order-list mb-2" data-full-order="<?php echo manager_safe_html(json_encode(array_values($standard_fields_order_ui), JSON_UNESCAPED_UNICODE)); ?>">
                    <?php foreach ($standard_fields_order_ui as $orderKey):
                        if (!$gstOrderKeyIsEnabled($orderKey, $fieldsEnabledForOrder)) {
                            continue;
                        }
                        ?>
                        <li class="list-group-item d-flex align-items-center standard-fields-order-item" data-order-key="<?php echo manager_safe_html($orderKey); ?>">
                            <span class="mr-2 text-muted" aria-hidden="true"><i class="fa fa-bars"></i></span>
                            <span class="standard-fields-order-label"><?php echo manager_safe_html($standard_fields_order_labels[$orderKey] ?? $orderKey); ?></span>
                            <input type="hidden" name="standard_fields_order[]" value="<?php echo manager_safe_html($orderKey); ?>">
                        </li>
                    <?php endforeach; ?>
                </ul>
                <div id="standard-fields-order-consensi" class="list-group mb-2">
                    <?php foreach (gst_standard_fields_consensi() as $consensoKey):
                        if (!$gstOrderKeyIsEnabled($consensoKey, $fieldsEnabledForOrder)) {
                            continue;
                        }
                        ?>
                        <div class="list-group-item d-flex align-items-center bg-light text-muted standard-fields-order-consenso" data-order-key="<?php echo manager_safe_html($consensoKey); ?>">
                            <span class="mr-2" aria-hidden="true"><i class="fa fa-lock"></i></span>
                            <span><?php echo manager_safe_html($standard_fields_order_labels[$consensoKey] ?? $consensoKey); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
                <small class="form-text text-muted mb-0">Solo i campi abilitati compaiono qui. I consensi non sono spostabili e restano in fondo al form.</small>
            </div>
        </div>
    </div>
</div>
<script type="application/json" id="standard-fields-order-labels-json"><?php echo json_encode($standard_fields_order_labels, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?></script>
<script type="application/json" id="standard-fields-order-consensi-json"><?php echo json_encode(gst_standard_fields_consensi(), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?></script>
                                        <?php
$custom_fields_current = $content_current['custom_fields'] ?? ($content_defaults['custom_fields'] ?? []);
if (!is_array($custom_fields_current)) {
    $custom_fields_current = [];
}
$custom_fields_has_items = false;
foreach ($custom_fields_current as $cf_item) {
    if (is_array($cf_item)) {
        $custom_fields_has_items = true;
        break;
    }
}
?>
<div class="col-12 d-flex form-campi-custom-fields-wrap">
    <div class="section-card mb-3 w-100 custom-fields-panel configurazione-panel">
        <div class="configurazione-panel-header form-campi-panel-intro form-campi-panel-intro--custom">
            <span class="configurazione-panel-icon"><i class="fa fa-plus"></i></span>
            <div>
                <h6>Campi custom</h6>
                <p>Crea campi extra e scegli come devono comparire nel form pubblico.</p>
            </div>
        </div>
        <div class="custom-fields-header">
            <div>
                <div class="section-subtitle mb-0">
                    Campi personalizzati
                    <span class="text-muted ml-1" data-toggle="tooltip" title="Gestisci i campi custom del form con una vista dedicata."><i class="fa fa-question-circle"></i></span>
                </div>
            </div>
            <button type="button" class="btn btn-outline-primary btn-sm" id="add-custom-field">
                <i class="fa fa-plus mr-1"></i>Aggiungi campo
            </button>
        </div>
        <input type="hidden" name="custom_fields_present" value="1">
        <div class="custom-fields-body<?php echo $custom_fields_has_items ? '' : ' d-none'; ?>">
            <div class="custom-fields-sidebar">
                <div class="custom-fields-list" id="custom-fields-list">
                    <?php foreach ($custom_fields_current as $index => $customField): ?>
                        <?php if (!is_array($customField)) { continue; } ?>
                        <?php
                            $customId = trim((string) ($customField['id'] ?? ''));
                            if ($customId === '') {
                                $customId = 'custom_' . uniqid();
                            }
                            $customLabel = (string) ($customField['label'] ?? '');
                            $customType = strtolower(trim((string) ($customField['type'] ?? 'varchar')));
                            $customType = in_array($customType, ['int', 'varchar', 'text', 'date', 'boolean'], true) ? $customType : 'varchar';
                            $customRequired = !empty($customField['required']);
                            $customFieldWidth = isset($customField['field_width']) ? trim((string) $customField['field_width']) : 'large';
                            $customPositionValue = $customField['position_order'] ?? ($customField['position_after'] ?? '');
                            $typeBadge = strtoupper($customType);
                            if ($customType === 'boolean') {
                                $typeBadge = 'CHECKBOX';
                            }
                        ?>
                        <div class="custom-field-tab-item" data-field-index="<?php echo $index; ?>" data-field-id="<?php echo manager_safe_html($customId); ?>">
                            <button type="button" class="custom-field-tab" data-field-index="<?php echo $index; ?>" data-field-id="<?php echo manager_safe_html($customId); ?>">
                                <div class="custom-field-tab-title"><?php echo manager_safe_html($customLabel ?: 'Nuovo campo'); ?></div>
                                <div class="custom-field-tab-meta">
                                    <span class="badge badge-light"><?php echo manager_safe_html($typeBadge); ?></span>
                                    <?php if ($customRequired): ?>
                                        <span class="badge badge-warning">Obbligatorio</span>
                                    <?php endif; ?>
                                </div>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger custom-field-remove" title="Rimuovi campo" aria-label="Rimuovi campo">
                                <i class="fa fa-trash"></i>
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="custom-fields-editor" id="custom-fields-editor">
                <?php foreach ($custom_fields_current as $index => $customField): ?>
                    <?php if (!is_array($customField)) { continue; } ?>
                    <?php
                        $customId = trim((string) ($customField['id'] ?? ''));
                        if ($customId === '') {
                            $customId = 'custom_' . uniqid();
                        }
                        $customLabel = (string) ($customField['label'] ?? '');
                        $customType = strtolower(trim((string) ($customField['type'] ?? 'varchar')));
                        $customType = in_array($customType, ['int', 'varchar', 'text', 'date', 'boolean'], true) ? $customType : 'varchar';
                        $customRequired = !empty($customField['required']);
                        $customMaxLength = isset($customField['max_length']) ? (int) $customField['max_length'] : '';
                        $customMinLength = isset($customField['min_length']) ? (int) $customField['min_length'] : '';
                        $customExactLength = isset($customField['exact_length']) ? (int) $customField['exact_length'] : '';
                        $customErrorMsg = isset($customField['error_message']) ? (string) $customField['error_message'] : '';
                        $customDateMin = isset($customField['date_min']) ? (string) $customField['date_min'] : '';
                        $customDateMax = isset($customField['date_max']) ? (string) $customField['date_max'] : '';
                        $customDateMaxToday = !empty($customField['date_max_today']);
                        $customFieldWidth = isset($customField['field_width']) ? trim((string) $customField['field_width']) : 'large';
                        if (!in_array($customFieldWidth, ['small', 'large'], true)) {
                            $customFieldWidth = 'large';
                        }
                        $customPositionValue = $customField['position_order'] ?? ($customField['position_after'] ?? '');
                    ?>
                    <div class="custom-field-editor" data-field-index="<?php echo $index; ?>" data-field-id="<?php echo manager_safe_html($customId); ?>" data-position-value="<?php echo manager_safe_html((string) $customPositionValue); ?>">
                        <div class="custom-field-editor-header">
                            <div class="custom-field-editor-title">
                                <strong><?php echo manager_safe_html($customLabel ?: 'Nuovo campo'); ?></strong>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-danger custom-field-remove" title="Rimuovi campo" aria-label="Rimuovi campo">
                                <i class="fa fa-trash"></i>
                            </button>
                        </div>
                        <input type="hidden" name="custom_fields[<?php echo $index; ?>][id]" value="<?php echo manager_safe_html($customId); ?>">
                        <div class="form-row">
                            <div class="form-group col-md-12">
                                <label>Label</label>
                                <input type="text" name="custom_fields[<?php echo $index; ?>][label]" class="form-control custom-field-label" value="<?php echo manager_safe_html($customLabel); ?>" maxlength="30">
                            </div>
                            <div class="form-group col-md-6">
                                <label>Tipo</label>
                                <select name="custom_fields[<?php echo $index; ?>][type]" class="form-control custom-field-type">
                                    <option value="int" <?php echo $customType === 'int' ? 'selected' : ''; ?>>INT</option>
                                    <option value="varchar" <?php echo $customType === 'varchar' ? 'selected' : ''; ?>>VARCHAR</option>
                                    <option value="text" <?php echo $customType === 'text' ? 'selected' : ''; ?>>TEXT</option>
                                    <option value="date" <?php echo $customType === 'date' ? 'selected' : ''; ?>>DATE</option>
                                    <option value="boolean" <?php echo $customType === 'boolean' ? 'selected' : ''; ?>>BOOLEAN (Checkbox)</option>
                                </select>
                            </div>
                            <div class="form-group col-12 position-grid-wrapper">
                                <a href="#position-grid-collapse-<?php echo $index; ?>" class="d-flex align-items-center position-grid-toggle text-dark text-decoration-none" data-toggle="collapse" role="button" aria-expanded="false" aria-controls="position-grid-collapse-<?php echo $index; ?>">
                                    <label class="mb-0 mr-1">Posizione nel form <span class="text-muted">(vista per sezioni - trascina per spostare)</span></label>
                                    <i class="fa fa-chevron-down position-grid-chevron ml-1"></i>
                                </a>
                                <div class="collapse mt-2 position-grid-collapse" id="position-grid-collapse-<?php echo $index; ?>">
                                    <input type="hidden" name="custom_fields[<?php echo $index; ?>][position_order]" class="custom-field-position" value="<?php echo manager_safe_html((string) $customPositionValue); ?>">
                                    <div class="position-grid-desktop" data-field-id="<?php echo manager_safe_html($customId); ?>" data-field-index="<?php echo $index; ?>"></div>
                                    <small class="form-text text-muted">Trascina il tuo campo nella griglia per posizionarlo.</small>
                                </div>
                            </div>
                            <div class="form-group col-md-3">
                                <label>Larghezza del campo</label>
                                <select name="custom_fields[<?php echo $index; ?>][field_width]" class="form-control custom-field-width">
                                    <option value="small" <?php echo $customFieldWidth === 'small' ? 'selected' : ''; ?>>Small (288px - 1 position)</option>
                                    <option value="large" <?php echo $customFieldWidth === 'large' ? 'selected' : ''; ?>>Large (600px - 2 positions)</option>
                                </select>
                                <small class="form-text text-muted d-none">Determina la larghezza del campo</small>
                            </div>
                            <div class="form-group col-md-3">
                                <label class="d-block">Obbligatorio</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="custom_required_<?php echo $index; ?>" name="custom_fields[<?php echo $index; ?>][required]" value="1" <?php echo $customRequired ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="custom_required_<?php echo $index; ?>" data-no-tooltip="1">Si</label>
                                </div>
                            </div>
                        </div>
                        <div class="form-row custom-field-length-controls" data-field-type="varchar">
                            <div class="form-group col-md-4">
                                <label>Lunghezza esatta</label>
                                <input type="number" name="custom_fields[<?php echo $index; ?>][exact_length]" class="form-control" value="<?php echo manager_safe_html((string) $customExactLength); ?>" min="0" placeholder="Es: 12">
                                <small class="form-text text-muted d-none">Se impostato, il campo deve avere esattamente questo numero di caratteri</small>
                            </div>
                            <div class="form-group col-md-4">
                                <label>Lunghezza minima</label>
                                <input type="number" name="custom_fields[<?php echo $index; ?>][min_length]" class="form-control" value="<?php echo manager_safe_html((string) $customMinLength); ?>" min="0" placeholder="Es: 5">
                            </div>
                            <div class="form-group col-md-4">
                                <label>Lunghezza massima</label>
                                <input type="number" name="custom_fields[<?php echo $index; ?>][max_length]" class="form-control" value="<?php echo manager_safe_html((string) $customMaxLength); ?>" min="0" placeholder="Es: 50">
                                <small class="form-text text-muted d-none"><strong>VARCHAR:</strong> Se vuoto, default = <strong>255</strong> caratteri</small>
                            </div>
                        </div>
                        <div class="form-row custom-field-int-controls" data-field-type="int" style="display: none;">
                            <div class="form-group col-md-4">
                                <label>Valore minimo <span class="text-muted ml-1" data-toggle="tooltip" title="Limite numerico (es. età). Non usare insieme a Numero di cifre esatto."><i class="fa fa-question-circle"></i></span></label>
                                <input type="number" name="custom_fields[<?php echo $index; ?>][int_min]" class="form-control custom-field-int-min" value="<?php echo manager_safe_html((string) ($customField['int_min'] ?? '')); ?>" min="-2147483648" max="2147483647" placeholder="Es: 0">
                                <small class="form-text text-muted d-none">Valore minimo accettabile (INT signed)</small>
                            </div>
                            <div class="form-group col-md-4">
                                <label>Valore massimo <span class="text-muted ml-1" data-toggle="tooltip" title="Limite numerico (es. età). Non usare insieme a Numero di cifre esatto."><i class="fa fa-question-circle"></i></span></label>
                                <input type="number" name="custom_fields[<?php echo $index; ?>][int_max]" class="form-control custom-field-int-max" value="<?php echo manager_safe_html((string) ($customField['int_max'] ?? '')); ?>" min="-2147483648" max="2147483647" placeholder="Es: 100">
                                <small class="form-text text-muted d-none">Valore massimo accettabile (INT signed)</small>
                            </div>
                            <div class="form-group col-md-4">
                                <label>Numero di cifre esatto <span class="text-muted ml-1" data-toggle="tooltip" title="Per CAP/codici: es. 5. Disattiva min/max. Solo cifre, lunghezza fissa."><i class="fa fa-question-circle"></i></span></label>
                                <input type="number" name="custom_fields[<?php echo $index; ?>][int_exact_digits]" class="form-control custom-field-int-exact-digits" value="<?php echo manager_safe_html((string) ($customField['int_exact_digits'] ?? '')); ?>" min="1" max="10" placeholder="Es: 5">
                                <small class="form-text text-muted custom-field-int-exact-hint">Per CAP/codici: usa solo cifre esatte; min/max non si applicano.</small>
                            </div>
                        </div>
                        <div class="form-row custom-field-date-controls" data-field-type="date" style="display: none;">
                            <div class="form-group col-md-4">
                                <label>Data minima</label>
                                <input type="date" name="custom_fields[<?php echo $index; ?>][date_min]" class="form-control" value="<?php echo manager_safe_html($customDateMin); ?>">
                                <small class="form-text text-muted d-none">Data minima selezionabile</small>
                            </div>
                            <div class="form-group col-md-4">
                                <label>Data massima</label>
                                <input type="date" name="custom_fields[<?php echo $index; ?>][date_max]" class="form-control" value="<?php echo manager_safe_html($customDateMax); ?>">
                                <small class="form-text text-muted d-none">Data massima selezionabile</small>
                            </div>
                            <div class="form-group col-md-4">
                                <label class="d-block">Limita a oggi</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="custom_date_max_today_<?php echo $index; ?>" name="custom_fields[<?php echo $index; ?>][date_max_today]" value="1" <?php echo $customDateMaxToday ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="custom_date_max_today_<?php echo $index; ?>">
                                        La data non puo essere superiore a oggi
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-12">
                                <label>Messaggio d'errore personalizzato</label>
                                <input type="text" name="custom_fields[<?php echo $index; ?>][error_message]" class="form-control" value="<?php echo manager_safe_html($customErrorMsg); ?>" placeholder="Es: Il codice voucher non e valido">
                                <small class="form-text text-muted d-none">Se impostato, questo messaggio sostituisce quello predefinito.</small>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
            $fields_enabled_for_template = $content_current['fields_enabled'] ?? [];
            $template_ordered_fields = [];
            foreach ($standard_fields_map as $key => $field) {
                $field_name = $field['name'];
                $template_ordered_fields[] = [
                    'type' => 'standard',
                    'label' => $field['label'],
                    'position_order' => $field['position'],
                    'name' => $field_name,
                    'grid_pos' => $field['position']
                ];
            }
            if (!function_exists('apply_fidelity_field_size_to_standard_map')) {
                require_once __DIR__ . '/../../../app/inc/functions.php';
            }
            $standard_fields_map_for_template = apply_fidelity_field_size_to_standard_map(
                $standard_fields_map,
                $content_current['fidelity'] ?? ($content_defaults['fidelity'] ?? [])
            );
            $template_grid = organize_fields_in_grid($standard_fields_map_for_template, $custom_fields_current, $fields_enabled_for_template);
            foreach ($template_grid as $pos => $item) {
                if ($item['type'] === 'custom') {
                    $template_ordered_fields[] = [
                        'type' => 'custom',
                        'label' => $item['field']['label'] ?? 'Custom Field',
                        'position_order' => $item['position_order'] ?? 5,
                        'id' => $item['field']['id'] ?? '',
                        'grid_pos' => $pos
                    ];
                }
            }
            usort($template_ordered_fields, function($a, $b) {
                return $a['position_order'] - $b['position_order'];
            });
            $template_section1_options = [];
            $template_section2_options = [];
            $template_section3_options = [];
            $template_section4_options = [];
            $template_section5_options = [];
            $template_section6_options = [];
            foreach ($template_ordered_fields as $field) {
                $pos_order = $field['position_order'];
                $label = htmlspecialchars($field['label'], ENT_QUOTES, 'UTF-8');
                $type_indicator = $field['type'] === 'custom' ? ' (custom)' : '';
                $field_section = 1;
                if ($field['type'] === 'standard' && isset($field['name'])) {
                    $field_section = get_field_section($field['name']);
                }
                $before_value = $field['type'] === 'custom' ? 'before_c_' . ($field['id'] ?? '') : 'before_s_' . ($field['name'] ?? '');
                $before_label = 'Prima di ' . $label . $type_indicator;
                $after_value = $field['type'] === 'custom' ? 'after_c_' . ($field['id'] ?? '') : 'after_s_' . ($field['name'] ?? '');
                $after_label = 'Dopo ' . $label . $type_indicator;
                if ($field_section === 1) {
                    $template_section1_options[] = ['value' => $before_value, 'label' => $before_label, 'position' => $pos_order];
                    $template_section1_options[] = ['value' => $after_value, 'label' => $after_label, 'position' => $pos_order + 1];
                } elseif ($field_section === 2) {
                    $template_section2_options[] = ['value' => $before_value, 'label' => $before_label, 'position' => $pos_order];
                    $template_section2_options[] = ['value' => $after_value, 'label' => $after_label, 'position' => $pos_order + 1];
                } elseif ($field_section === 3) {
                    $template_section3_options[] = ['value' => $before_value, 'label' => $before_label, 'position' => $pos_order];
                    $template_section3_options[] = ['value' => $after_value, 'label' => $after_label, 'position' => $pos_order + 1];
                } elseif ($field_section === 4) {
                    $template_section4_options[] = ['value' => $before_value, 'label' => $before_label, 'position' => $pos_order];
                    $template_section4_options[] = ['value' => $after_value, 'label' => $after_label, 'position' => $pos_order + 1];
                } elseif ($field_section === 5) {
                    $template_section5_options[] = ['value' => $before_value, 'label' => $before_label, 'position' => $pos_order];
                    $template_section5_options[] = ['value' => $after_value, 'label' => $after_label, 'position' => $pos_order + 1];
                } elseif ($field_section === 6) {
                    $template_section6_options[] = ['value' => $before_value, 'label' => $before_label, 'position' => $pos_order];
                    $template_section6_options[] = ['value' => $after_value, 'label' => $after_label, 'position' => $pos_order + 1];
                }
            }
            usort($template_section1_options, function($a, $b) { return $a['position'] - $b['position']; });
            usort($template_section2_options, function($a, $b) { return $a['position'] - $b['position']; });
            usort($template_section3_options, function($a, $b) { return $a['position'] - $b['position']; });
            usort($template_section4_options, function($a, $b) { return $a['position'] - $b['position']; });
            usort($template_section5_options, function($a, $b) { return $a['position'] - $b['position']; });
            usort($template_section6_options, function($a, $b) { return $a['position'] - $b['position']; });
            // Griglia drag-and-drop: lista con separatori di sezione e gestione PDV cascata/combinato
            $pdv_config = $content_current['pdv_config'] ?? ($content_defaults['pdv_config'] ?? []);
            $pdv_mode = strtolower(trim((string)($pdv_config['mode'] ?? 'combinato')));
            $pdv_mode = in_array($pdv_mode, ['combinato', 'cascata'], true) ? $pdv_mode : 'combinato';
            $pdv_enabled = !empty($content_current['fields_enabled']['prov_pdv']) || !empty($content_current['fields_enabled']['citta_pdv']) || !empty($content_current['fields_enabled']['indirizzo_pdv']);
            $last_section = 0;
            $add_section_separator = function ($section) use (&$last_section, &$position_grid_items, &$position_grid_items_cascata) {
                if ($section > 1 && $section != $last_section) {
                    $sep = ['type' => 'separator', 'ref' => '', 'label' => '', 'before_value' => '', 'after_value' => '', 'is_large' => true, 'section' => $section];
                    $position_grid_items[] = $sep;
                    $position_grid_items_cascata[] = $sep;
                    $last_section = $section;
                }
            };
            $position_grid_items = [];
            $position_grid_items_cascata = [];
            foreach ($template_grid as $pos => $item) {
                $type = $item['type'];
                $size = $item['size'] ?? 288;
                $is_large = ($size === 600);
                if ($type === 'standard') {
                    $name = $item['field']['name'] ?? '';
                    $label = $item['field']['label'] ?? '';
                    $section = get_field_section($name);
                    $add_section_separator($section);
                    if ($name === 'indirizzo_pdv' && $pdv_enabled) {
                        if ($pdv_mode === 'cascata') {
                            $position_grid_items[] = ['type' => 'standard', 'ref' => 'prov_pdv', 'label' => 'Provincia PDV', 'before_value' => 'before_s_prov_pdv', 'after_value' => 'after_s_prov_pdv', 'is_large' => false];
                            $position_grid_items[] = ['type' => 'standard', 'ref' => 'citta_pdv', 'label' => 'Città PDV', 'before_value' => 'before_s_citta_pdv', 'after_value' => 'after_s_citta_pdv', 'is_large' => false];
                            $position_grid_items[] = ['type' => 'standard', 'ref' => 'indirizzo_pdv', 'label' => 'Punto Vendita', 'before_value' => 'before_s_indirizzo_pdv', 'after_value' => 'after_s_indirizzo_pdv', 'is_large' => $is_large];
                        } else {
                            $position_grid_items[] = ['type' => 'standard', 'ref' => $name, 'label' => $label, 'before_value' => 'before_s_' . $name, 'after_value' => 'after_s_' . $name, 'is_large' => $is_large];
                        }
                        $position_grid_items_cascata[] = ['type' => 'standard', 'ref' => 'prov_pdv', 'label' => 'Provincia PDV', 'before_value' => 'before_s_prov_pdv', 'after_value' => 'after_s_prov_pdv', 'is_large' => false];
                        $position_grid_items_cascata[] = ['type' => 'standard', 'ref' => 'citta_pdv', 'label' => 'Città PDV', 'before_value' => 'before_s_citta_pdv', 'after_value' => 'after_s_citta_pdv', 'is_large' => false];
                        $position_grid_items_cascata[] = ['type' => 'standard', 'ref' => 'indirizzo_pdv', 'label' => 'Punto Vendita', 'before_value' => 'before_s_indirizzo_pdv', 'after_value' => 'after_s_indirizzo_pdv', 'is_large' => $is_large];
                    } else {
                        $entry = ['type' => 'standard', 'ref' => $name, 'label' => $label, 'before_value' => 'before_s_' . $name, 'after_value' => 'after_s_' . $name, 'is_large' => $is_large];
                        $position_grid_items[] = $entry;
                        $position_grid_items_cascata[] = $entry;
                    }
                } else {
                    $id = $item['field']['id'] ?? '';
                    $label = $item['field']['label'] ?? 'Custom';
                    $section = 1;
                    $entry = ['type' => 'custom', 'ref' => $id, 'label' => $label, 'before_value' => 'before_c_' . $id, 'after_value' => 'after_c_' . $id, 'is_large' => $is_large];
                    $position_grid_items[] = $entry;
                    $position_grid_items_cascata[] = $entry;
                }
            }
            $position_grid_items_json = json_encode($position_grid_items, JSON_UNESCAPED_UNICODE | JSON_HEX_QUOT);
            $position_grid_items_cascata_json = json_encode($position_grid_items_cascata, JSON_UNESCAPED_UNICODE | JSON_HEX_QUOT);
            $template_position_options_json = json_encode([
                'section1' => $template_section1_options,
                'section2' => $template_section2_options,
                'section3' => $template_section3_options,
                'section4' => $template_section4_options,
                'section5' => $template_section5_options,
                'section6' => $template_section6_options
            ], JSON_UNESCAPED_UNICODE | JSON_HEX_QUOT);
            $dynamic_position_options_json = $template_position_options_json;
        ?>
        <template id="custom-field-tab-template">
            <div class="custom-field-tab-item" data-field-index="__INDEX__" data-field-id="__ID__">
                <button type="button" class="custom-field-tab" data-field-index="__INDEX__" data-field-id="__ID__">
                    <div class="custom-field-tab-title">Nuovo campo</div>
                    <div class="custom-field-tab-meta">
                        <span class="badge badge-light">VARCHAR</span>
                    </div>
                </button>
                <button type="button" class="btn btn-sm btn-outline-danger custom-field-remove" title="Rimuovi campo" aria-label="Rimuovi campo">
                    <i class="fa fa-trash"></i>
                </button>
            </div>
        </template>

        <template id="custom-field-template">
            <div class="custom-field-editor" data-field-index="__INDEX__" data-field-id="__ID__" data-position-value="after_s_fidelity">
                <div class="custom-field-editor-header">
                    <div class="custom-field-editor-title">
                        <strong>Nuovo campo personalizzato</strong>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-danger custom-field-remove" title="Rimuovi campo" aria-label="Rimuovi campo">
                        <i class="fa fa-trash"></i>
                    </button>
                </div>
                <input type="hidden" name="custom_fields[__INDEX__][id]" value="__ID__">
                <div class="form-row">
                    <div class="form-group col-md-12">
                        <label>Label</label>
                        <input type="text" name="custom_fields[__INDEX__][label]" class="form-control custom-field-label" value="" maxlength="30">
                    </div>
                    <div class="form-group col-md-6">
                        <label>Tipo</label>
                        <select name="custom_fields[__INDEX__][type]" class="form-control custom-field-type">
                            <option value="int">INT</option>
                            <option value="varchar" selected>VARCHAR</option>
                            <option value="text">TEXT</option>
                            <option value="date">DATE</option>
                            <option value="boolean">BOOLEAN (Checkbox)</option>
                        </select>
                    </div>
                    <div class="form-group col-12 position-grid-wrapper">
                        <a href="#position-grid-collapse-__INDEX__" class="d-flex align-items-center position-grid-toggle text-dark text-decoration-none" data-toggle="collapse" role="button" aria-expanded="false" aria-controls="position-grid-collapse-__INDEX__">
                            <label class="mb-0 mr-1">Posizione nel form <span class="text-muted">(vista per sezioni - trascina per spostare)</span></label>
                            <i class="fa fa-chevron-down position-grid-chevron ml-1"></i>
                        </a>
                        <div class="collapse mt-2 position-grid-collapse" id="position-grid-collapse-__INDEX__">
                            <input type="hidden" name="custom_fields[__INDEX__][position_order]" class="custom-field-position" value="after_s_fidelity">
                            <div class="position-grid-desktop" data-field-id="__ID__" data-field-index="__INDEX__"></div>
                            <small class="form-text text-muted">Trascina il tuo campo nella griglia per posizionarlo.</small>
                        </div>
                    </div>
                                    <div class="form-group col-md-3">
                                        <label>Larghezza del campo</label>
                                        <select name="custom_fields[__INDEX__][field_width]" class="form-control custom-field-width">
                                            <option value="small" selected>Small (288px - 1 position)</option>
                                            <option value="large">Large (600px - 2 positions)</option>
                                        </select>
                        <small class="form-text text-muted d-none">Determina la larghezza del campo</small>
                    </div>
                    <div class="form-group col-md-3">
                        <label class="d-block">Obbligatorio</label>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="custom_required___INDEX__" name="custom_fields[__INDEX__][required]" value="1">
                            <label class="form-check-label" for="custom_required___INDEX__" data-no-tooltip="1">Si</label>
                        </div>
                    </div>
                </div>
                <div class="form-row custom-field-length-controls" data-field-type="varchar">
                    <div class="form-group col-md-4">
                        <label>Lunghezza esatta</label>
                        <input type="number" name="custom_fields[__INDEX__][exact_length]" class="form-control" value="" min="0" placeholder="Es: 12">
                        <small class="form-text text-muted d-none">Se impostato, il campo deve avere esattamente questo numero di caratteri</small>
                    </div>
                    <div class="form-group col-md-4">
                        <label>Lunghezza minima</label>
                        <input type="number" name="custom_fields[__INDEX__][min_length]" class="form-control" value="" min="0" placeholder="Es: 5">
                    </div>
                    <div class="form-group col-md-4">
                        <label>Lunghezza massima</label>
                        <input type="number" name="custom_fields[__INDEX__][max_length]" class="form-control" value="" min="0" placeholder="Es: 50">
                        <small class="form-text text-muted d-none"><strong>VARCHAR:</strong> Se vuoto, default = <strong>255</strong> caratteri</small>
                    </div>
                </div>
                <div class="form-row custom-field-int-controls" data-field-type="int" style="display: none;">
                    <div class="form-group col-md-4">
                        <label>Valore minimo <span class="text-muted ml-1" data-toggle="tooltip" title="Limite numerico (es. età). Non usare insieme a Numero di cifre esatto."><i class="fa fa-question-circle"></i></span></label>
                        <input type="number" name="custom_fields[__INDEX__][int_min]" class="form-control custom-field-int-min" value="" min="-2147483648" max="2147483647" placeholder="Es: 0">
                        <small class="form-text text-muted d-none">Valore minimo accettabile (INT signed)</small>
                    </div>
                    <div class="form-group col-md-4">
                        <label>Valore massimo <span class="text-muted ml-1" data-toggle="tooltip" title="Limite numerico (es. età). Non usare insieme a Numero di cifre esatto."><i class="fa fa-question-circle"></i></span></label>
                        <input type="number" name="custom_fields[__INDEX__][int_max]" class="form-control custom-field-int-max" value="" min="-2147483648" max="2147483647" placeholder="Es: 100">
                        <small class="form-text text-muted d-none">Valore massimo accettabile (INT signed)</small>
                    </div>
                    <div class="form-group col-md-4">
                        <label>Numero di cifre esatto <span class="text-muted ml-1" data-toggle="tooltip" title="Per CAP/codici: es. 5. Disattiva min/max. Solo cifre, lunghezza fissa."><i class="fa fa-question-circle"></i></span></label>
                        <input type="number" name="custom_fields[__INDEX__][int_exact_digits]" class="form-control custom-field-int-exact-digits" value="" min="1" max="10" placeholder="Es: 5">
                        <small class="form-text text-muted custom-field-int-exact-hint">Per CAP/codici: usa solo cifre esatte; min/max non si applicano.</small>
                    </div>
                </div>
                <div class="form-row custom-field-date-controls" data-field-type="date" style="display: none;">
                    <div class="form-group col-md-4">
                        <label>Data minima</label>
                        <input type="date" name="custom_fields[__INDEX__][date_min]" class="form-control" value="">
                        <small class="form-text text-muted d-none">Data minima selezionabile</small>
                    </div>
                    <div class="form-group col-md-4">
                        <label>Data massima</label>
                        <input type="date" name="custom_fields[__INDEX__][date_max]" class="form-control" value="">
                        <small class="form-text text-muted d-none">Data massima selezionabile</small>
                    </div>
                    <div class="form-group col-md-4">
                        <label class="d-block">Limita a oggi</label>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="custom_date_max_today___INDEX__" name="custom_fields[__INDEX__][date_max_today]" value="1">
                            <label class="form-check-label" for="custom_date_max_today___INDEX__">
                                La data non puo essere superiore a oggi
                            </label>
                        </div>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-12">
                        <label>Messaggio d'errore personalizzato</label>
                        <input type="text" name="custom_fields[__INDEX__][error_message]" class="form-control" value="" placeholder="Es: Il codice voucher non e valido">
                        <small class="form-text text-muted d-none">Se impostato, questo messaggio sostituisce quello predefinito.</small>
                    </div>
                </div>
            </div>
        </template>
    </div>
</div>
                                        <div class="col-12 d-none">
                                            <div class="form-container-panel" aria-hidden="true">
                                                <h6 class="text-uppercase text-muted mt-3 mb-2">Container form</h6>
                                                <div class="form-row form-container-main-grid">
                                                    <div class="form-group col-md-4 form-container-control-card form-container-mode-card">
                                            <label>Modalità</label>
                                            <select name="form_container_mode" id="form_container_mode" class="form-control">
                                                <option value="color" <?php echo ($content_current['form_container']['mode'] ?? '') === 'color' ? 'selected' : ''; ?>>Colore</option>
                                                <option value="image" <?php echo ($content_current['form_container']['mode'] ?? '') === 'image' ? 'selected' : ''; ?>>Immagine</option>
                                            </select>
                                                    </div>
                                                    <div class="form-group col-md-4 form-container-color form-container-control-card">
                                            <label>Colore <span class="text-muted ml-1" data-toggle="tooltip" title="Colore di sfondo del box form. Accetta HEX."><i class="fa fa-question-circle"></i></span></label>
                                            <div class="d-flex align-items-center color-row">
                                                <input type="color" class="form-control color-input color-swatch" data-target="form_container_color_text" value="<?php echo manager_safe_html($content_current['form_container']['color'] ?? '#ffffff'); ?>">
                                                <input type="text" name="form_container_color" id="form_container_color_text" class="form-control color-code-input" value="<?php echo manager_safe_html($content_current['form_container']['color'] ?? '#ffffff'); ?>" placeholder="#ffffff">
                                            </div>
                                            <small class="form-text text-muted d-none">Colore di sfondo del container form.</small>
                                                    </div>
                                                    <div class="form-group col-md-4 form-container-color form-container-control-card">
                                            <label>Trasparenza <span class="text-muted ml-1" data-toggle="tooltip" title="0 = completamente trasparente, 100 = completamente opaco."><i class="fa fa-question-circle"></i></span></label>
                                            <div class="d-flex align-items-center opacity-row">
                                                <input type="range" class="form-control-range opacity-slider" id="form_container_opacity_slider" min="0" max="100" step="1" value="<?php echo manager_safe_html((string)($content_current['form_container']['opacity'] ?? 100)); ?>">
                                                <input type="number" name="form_container_opacity" id="form_container_opacity" class="form-control opacity-input ml-2" min="0" max="100" value="<?php echo manager_safe_html((string)($content_current['form_container']['opacity'] ?? 100)); ?>">
                                                <span class="ml-1">%</span>
                                            </div>
                                            <small class="form-text text-muted d-none">Opacita del container form (0-100%).</small>
                                                    </div>
                                                    <div class="form-group col-md-4 form-container-image form-container-control-card form-container-image-card">
                                            <label>Immagine (consigliato 1200x800) <span class="text-muted ml-1" data-toggle="tooltip" title="Background immagine applicato al box form."><i class="fa fa-question-circle"></i></span></label>
                                            <input type="hidden" name="form_container_image" value="<?php echo manager_safe_html($content_current['form_container']['image'] ?? ''); ?>">
                                            <div class="custom-file">
                                                <input type="file" name="upload_form_container_image" id="upload_form_container_image" class="custom-file-input preview-input" accept="image/*" data-preview-target="preview_form_container_image">
                                                <label class="custom-file-label" for="upload_form_container_image">Scegli immagine...</label>
                                            </div>
                                            <small class="form-text text-muted mt-1">Attuale: <span id="current_form_container_image" data-default="<?php echo manager_safe_html($content_current['form_container']['image'] ?? ''); ?>"><?php echo manager_safe_html($content_current['form_container']['image'] ?? ''); ?></span></small>
                                            <div class="thumb-preview mt-2">
                                                <?php $formImgPreview = manager_preview_url($content_current['form_container']['image'] ?? '', '', $baseDir); ?>
                                                <img id="preview_form_container_image" data-current-src="<?php echo manager_safe_html($formImgPreview); ?>" src="<?php echo manager_safe_html($formImgPreview); ?>" class="img-thumbnail <?php echo $formImgPreview === '' ? 'd-none' : ''; ?>" alt="Anteprima immagine container" loading="lazy">
                                            </div>
                                                    </div>
                                                </div>
                                                <?php
                                                    $formContainerRadius = $content_current['form_container']['radius'] ?? [];
                                                    $formContainerRadiusDef = $content_defaults['form_container']['radius'] ?? ['top' => 0, 'right' => 0, 'bottom' => 12, 'left' => 12];
                                                    if (!is_array($formContainerRadius)) {
                                                        $formContainerRadius = [];
                                                    }
                                                    $formContainerRadiusMerged = array_merge($formContainerRadiusDef, $formContainerRadius);
                                                ?>
                                                <div class="form-row mt-2 form-container-radius-grid">
                                                    <div class="form-group col-12 form-container-radius-intro">
                                                        <label class="d-block">Angoli container esterno (px)</label>
                                                        <small class="form-text text-muted d-block mb-2">Stessa convenzione di <em>Pagina form</em> (sopra sx / sopra dx / sotto dx / sotto sx). Per tutti uguali, stesso valore sui quattro campi. Predefinito: 0 sopra (attaccato al logo), 12 sotto.</small>
                                                    </div>
                                                    <div class="form-group col-md-3 form-container-radius-card">
                                                        <label>Angolo sopra sx</label>
                                                        <input type="number" name="form_container_radius[top]" class="form-control" min="0" max="80" value="<?php echo manager_safe_html((string) max(0, min(80, (int) ($formContainerRadiusMerged['top'] ?? 0)))); ?>">
                                                    </div>
                                                    <div class="form-group col-md-3 form-container-radius-card">
                                                        <label>Angolo sopra dx</label>
                                                        <input type="number" name="form_container_radius[right]" class="form-control" min="0" max="80" value="<?php echo manager_safe_html((string) max(0, min(80, (int) ($formContainerRadiusMerged['right'] ?? 0)))); ?>">
                                                    </div>
                                                    <div class="form-group col-md-3 form-container-radius-card">
                                                        <label>Angolo sotto dx</label>
                                                        <input type="number" name="form_container_radius[bottom]" class="form-control" min="0" max="80" value="<?php echo manager_safe_html((string) max(0, min(80, (int) ($formContainerRadiusMerged['bottom'] ?? 12)))); ?>">
                                                    </div>
                                                    <div class="form-group col-md-3 form-container-radius-card">
                                                        <label>Angolo sotto sx</label>
                                                        <input type="number" name="form_container_radius[left]" class="form-control" min="0" max="80" value="<?php echo manager_safe_html((string) max(0, min(80, (int) ($formContainerRadiusMerged['left'] ?? 12)))); ?>">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <div class="configurazione-panel waiting-ui-panel form-page-style-panel">
                                                <div class="configurazione-panel-header">
                                                    <span class="configurazione-panel-icon"><i class="fa fa-object-group"></i></span>
                                                <div>
                                                    <h6>Container pagina - Registrazione</h6>
                                                    <p>Personalizza lo sfondo del contenitore della pagina di registrazione.</p>
                                                </div>
                                                </div>
                                            <?php
                                                echo manager_render_public_page_container_style_editor([
                                                    'page_key' => 'registration',
                                                    'label' => 'Registrazione',
                                                    'style' => $public_page_container_styles['registration'] ?? [],
                                                    'base_dir' => $baseDir,
                                                ]);
                                            ?>
                                            </div>
                                        </div>
                                        <?php
                                            $formFieldStyleValue = strtolower((string) ($content_current['form_field_style'] ?? ($content_defaults['form_field_style'] ?? 'label_top')));
                                            if (!in_array($formFieldStyleValue, ['label_top', 'placeholder'], true)) {
                                                $formFieldStyleValue = 'label_top';
                                            }
                                            $formFieldBorderRadius = max(0, min(40, (int) ($content_current['form_field_border_radius'] ?? ($content_defaults['form_field_border_radius'] ?? 6))));
                                            $formFieldTextColor = trim((string) ($content_current['form_field_text_color'] ?? ($content_defaults['form_field_text_color'] ?? '')));
                                            if ($formFieldTextColor === '' || !preg_match('/^#[0-9a-fA-F]{6}$/', $formFieldTextColor)) {
                                                $formFieldTextColor = '#000000';
                                            }
                                            $formFieldPlaceholderColorStored = trim((string) ($content_current['form_field_placeholder_color'] ?? ($content_defaults['form_field_placeholder_color'] ?? '')));
                                            $formFieldBorderColor = trim((string) ($content_current['form_field_border_color'] ?? ($content_defaults['form_field_border_color'] ?? '')));
                                            if ($formFieldBorderColor === '' || !preg_match('/^#[0-9a-fA-F]{6}$/', $formFieldBorderColor)) {
                                                $formFieldBorderColor = '#dee2e6';
                                            }
                                            $formFieldBgColor = trim((string) ($content_current['form_field_bg_color'] ?? ($content_defaults['form_field_bg_color'] ?? '')));
                                            if ($formFieldBgColor === '' || !preg_match('/^#[0-9a-fA-F]{6}$/', $formFieldBgColor)) {
                                                $formFieldBgColor = '#ffffff';
                                            }
                                            $palettePrimaryForFields = trim((string) ($content_current['colors']['primary'] ?? ($content_defaults['colors']['primary'] ?? '#0d6efd')));
                                            $paletteSecondaryForFields = trim((string) ($content_current['colors']['secondary'] ?? ($content_defaults['colors']['secondary'] ?? '#6c757d')));
                                            if (!preg_match('/^#[0-9a-fA-F]{6}$/', $palettePrimaryForFields)) {
                                                $palettePrimaryForFields = '#0d6efd';
                                            }
                                            if (!preg_match('/^#[0-9a-fA-F]{6}$/', $paletteSecondaryForFields)) {
                                                $paletteSecondaryForFields = '#6c757d';
                                            }
                                            $paletteTertiaryForFields = trim((string) ($content_current['colors']['tertiary'] ?? ($content_defaults['colors']['tertiary'] ?? '#0a58ca')));
                                            if (!preg_match('/^#[0-9a-fA-F]{6}$/', $paletteTertiaryForFields)) {
                                                $paletteTertiaryForFields = '#0a58ca';
                                            }
                                            $formFieldFocusColorStored = trim((string) ($content_current['form_field_focus_color'] ?? ($content_defaults['form_field_focus_color'] ?? '')));
                                            $formFieldCheckboxColorStored = trim((string) ($content_current['form_field_checkbox_color'] ?? ($content_defaults['form_field_checkbox_color'] ?? '')));
                                            $formFieldConsentTextColor = trim((string) ($content_current['form_field_consent_text_color'] ?? ($content_defaults['form_field_consent_text_color'] ?? '')));
                                            if ($formFieldConsentTextColor === '' || !preg_match('/^#[0-9a-fA-F]{6}$/', $formFieldConsentTextColor)) {
                                                $formFieldConsentTextColor = '#000000';
                                            }
                                            $formFieldConsentLinkColorStored = trim((string) ($content_current['form_field_consent_link_color'] ?? ($content_defaults['form_field_consent_link_color'] ?? '')));
                                            $formFieldConsentLinkHoverColorStored = trim((string) ($content_current['form_field_consent_link_hover_color'] ?? ($content_defaults['form_field_consent_link_hover_color'] ?? '')));
                                            if (!function_exists('resolve_form_field_colors')) {
                                                require_once dirname(__DIR__, 3) . '/app/inc/config.php';
                                            }
                                            $styleCustomization = is_array($content_current['style_customization'] ?? null)
                                                ? $content_current['style_customization']
                                                : (is_array($content_defaults['style_customization'] ?? null) ? $content_defaults['style_customization'] : []);
                                            $formFieldColorsCustom = !empty($styleCustomization['form_field_colors']);
                                            $formFieldPreviewContest = array_merge($content_defaults, $content_current);
                                            $formFieldPreviewContest['colors'] = $content_current['colors'] ?? ($content_defaults['colors'] ?? []);
                                            $formFieldColorsResolved = resolve_form_field_colors($formFieldPreviewContest);
                                            $formFieldFocusPreview = (string) $formFieldColorsResolved['focus'];
                                            $formFieldCheckboxPreview = (string) $formFieldColorsResolved['checkbox'];
                                            $formFieldConsentTextPreview = (string) ($formFieldColorsResolved['consent_text'] ?? $formFieldConsentTextColor);
                                            $formFieldConsentLinkPreview = (string) $formFieldColorsResolved['consent_link'];
                                            $formFieldConsentLinkHoverPreview = (string) $formFieldColorsResolved['consent_link_hover'];
                                            $formFieldPreviewText = (string) $formFieldColorsResolved['text'];
                                            $formFieldPreviewPlaceholder = trim((string) ($formFieldColorsResolved['placeholder'] ?? ''));
                                            if ($formFieldPreviewPlaceholder === '') {
                                                $formFieldPreviewPlaceholder = $formFieldPreviewText;
                                            }
                                            $formFieldPlaceholderSwatch = preg_match('/^#[0-9a-fA-F]{6}$/', $formFieldPlaceholderColorStored)
                                                ? $formFieldPlaceholderColorStored
                                                : $formFieldPreviewPlaceholder;
                                            $formFieldPreviewBorder = (string) ($formFieldColorsResolved['border'] !== '' ? $formFieldColorsResolved['border'] : '#dee2e6');
                                            $formFieldPreviewBg = (string) $formFieldColorsResolved['bg'];
                                            $paletteTextForFields = contest_normalize_hex_color(
                                                (string) ($content_current['colors']['text'] ?? ($content_defaults['colors']['text'] ?? '')),
                                                '#212529'
                                            ) ?: '#212529';
                                            $formFieldFocusSwatch = preg_match('/^#[0-9a-fA-F]{6}$/', $formFieldFocusColorStored) ? $formFieldFocusColorStored : $palettePrimaryForFields;
                                            $formFieldCheckboxSwatch = preg_match('/^#[0-9a-fA-F]{6}$/', $formFieldCheckboxColorStored) ? $formFieldCheckboxColorStored : $paletteSecondaryForFields;
                                            $formFieldConsentLinkSwatch = preg_match('/^#[0-9a-fA-F]{6}$/', $formFieldConsentLinkColorStored) ? $formFieldConsentLinkColorStored : $paletteSecondaryForFields;
                                            $formFieldConsentLinkHoverSwatch = preg_match('/^#[0-9a-fA-F]{6}$/', $formFieldConsentLinkHoverColorStored) ? $formFieldConsentLinkHoverColorStored : $paletteTertiaryForFields;
                                            $formFieldSelect2HighlightBgStored = trim((string) ($content_current['form_field_select2_highlight_bg'] ?? ($content_defaults['form_field_select2_highlight_bg'] ?? '')));
                                            if ($formFieldSelect2HighlightBgStored === '') {
                                                $formFieldSelect2HighlightBgStored = trim((string) ($content_current['form_field_select2_color'] ?? ($content_defaults['form_field_select2_color'] ?? '')));
                                            }
                                            $formFieldSelect2HighlightTextStored = trim((string) ($content_current['form_field_select2_highlight_text'] ?? ($content_defaults['form_field_select2_highlight_text'] ?? '')));
                                            $formFieldSelect2HighlightBgPreview = (string) $formFieldColorsResolved['select2_highlight_bg'];
                                            $formFieldSelect2HighlightTextPreview = (string) $formFieldColorsResolved['select2_highlight_text'];
                                            $formFieldCalendarPopupBgStored = trim((string) ($content_current['form_field_calendar_popup_bg'] ?? ($content_defaults['form_field_calendar_popup_bg'] ?? '')));
                                            $formFieldCalendarSelectedColorStored = trim((string) ($content_current['form_field_calendar_selected_color'] ?? ($content_defaults['form_field_calendar_selected_color'] ?? '')));
                                            $formFieldCalendarHoverBgStored = trim((string) ($content_current['form_field_calendar_hover_bg'] ?? ($content_defaults['form_field_calendar_hover_bg'] ?? '')));
                                            $formFieldCalendarHoverTextStored = trim((string) ($content_current['form_field_calendar_hover_text'] ?? ($content_defaults['form_field_calendar_hover_text'] ?? '')));
                                            $formFieldCalendarPopupBgPreview = (string) $formFieldColorsResolved['calendar_popup_bg'];
                                            $formFieldCalendarSelectedPreview = (string) $formFieldColorsResolved['calendar_selected'];
                                            $formFieldCalendarHoverBgPreview = (string) $formFieldColorsResolved['calendar_hover_bg'];
                                            $formFieldCalendarHoverTextPreview = (string) $formFieldColorsResolved['calendar_hover_text'];
                                            $formFieldCalendarPopupBgSwatch = preg_match('/^#[0-9a-fA-F]{6}$/', $formFieldCalendarPopupBgStored) ? $formFieldCalendarPopupBgStored : $formFieldPreviewBg;
                                            $formFieldCalendarSelectedSwatch = preg_match('/^#[0-9a-fA-F]{6}$/', $formFieldCalendarSelectedColorStored) ? $formFieldCalendarSelectedColorStored : $formFieldFocusSwatch;
                                            $formFieldCalendarHoverBgSwatch = preg_match('/^#[0-9a-fA-F]{6}$/', $formFieldCalendarHoverBgStored) ? $formFieldCalendarHoverBgStored : $formFieldFocusSwatch;
                                            $formFieldCalendarHoverTextSwatch = preg_match('/^#[0-9a-fA-F]{6}$/', $formFieldCalendarHoverTextStored) ? $formFieldCalendarHoverTextStored : $formFieldCalendarHoverTextPreview;
                                            $formFieldSelect2HighlightBgSwatch = preg_match('/^#[0-9a-fA-F]{6}$/', $formFieldSelect2HighlightBgStored) ? $formFieldSelect2HighlightBgStored : $formFieldFocusSwatch;
                                            $formFieldSelect2HighlightTextSwatch = preg_match('/^#[0-9a-fA-F]{6}$/', $formFieldSelect2HighlightTextStored) ? $formFieldSelect2HighlightTextStored : $formFieldSelect2HighlightTextPreview;
                                            $formFieldBgOpacity = max(0, min(100, (int) ($content_current['form_field_bg_opacity'] ?? ($content_defaults['form_field_bg_opacity'] ?? 100))));
                                            $formFieldFocusOpacity = max(0, min(100, (int) ($content_current['form_field_focus_opacity'] ?? ($content_defaults['form_field_focus_opacity'] ?? 100))));
                                            $formFieldConsentLinkHoverOpacity = max(0, min(100, (int) ($content_current['form_field_consent_link_hover_opacity'] ?? ($content_defaults['form_field_consent_link_hover_opacity'] ?? 100))));
                                            $formFieldErrorMode = strtolower(trim((string) ($content_current['form_field_error_mode'] ?? ($content_defaults['form_field_error_mode'] ?? 'classic'))));
                                            if (!in_array($formFieldErrorMode, ['classic', 'on_color_background'], true)) {
                                                $formFieldErrorMode = 'classic';
                                            }
                                            $fieldsEnabledFfs = $content_current['fields_enabled'] ?? ($content_defaults['fields_enabled'] ?? []);
                                            $ffsShowDate = !empty($fieldsEnabledFfs['data_scontrino']);
                                            $ffsShowFile = (bool) ($fieldsEnabledFfs['scontrino'] ?? ($fieldsDefaults['scontrino'] ?? true));
                                            $ffsShowSelect2 = !empty($fieldsEnabledFfs['provincia'])
                                                || !empty($fieldsEnabledFfs['citta'])
                                                || !empty($fieldsEnabledFfs['prov_pdv'])
                                                || !empty($fieldsEnabledFfs['citta_pdv'])
                                                || !empty($fieldsEnabledFfs['indirizzo_pdv']);
                                            $ffsShowConsents = !empty($fieldsEnabledFfs['privacy'])
                                                || !empty($fieldsEnabledFfs['marketing'])
                                                || !empty($fieldsEnabledFfs['profilazione']);
                                            $ffsPanelClass = 'form-field-style-panel configurazione-panel';
                                            if (!$ffsShowSelect2) {
                                                $ffsPanelClass .= ' form-field-style-panel--no-select2';
                                            }
                                            if (!$ffsShowDate) {
                                                $ffsPanelClass .= ' form-field-style-panel--no-date';
                                            }
                                            if (!$ffsShowFile) {
                                                $ffsPanelClass .= ' form-field-style-panel--no-file';
                                            }
                                            if (!$ffsShowConsents) {
                                                $ffsPanelClass .= ' form-field-style-panel--no-consents';
                                            }
                                        ?>
                                        <div class="col-12">
                                            <div class="<?php echo manager_safe_html($ffsPanelClass); ?>">
                                                <div class="configurazione-panel-header form-campi-panel-intro form-campi-panel-intro--style">
                                                    <span class="configurazione-panel-icon"><i class="fa fa-font"></i></span>
                                                    <div>
                                                        <h6>Stile dei campi</h6>
                                                        <p>Scegli etichette, placeholder e colori degli input nel form pubblico.</p>
                                                    </div>
                                                </div>
                                                <?php $ffspPreviewPartial = __DIR__ . '/partials/form-field-style-preview.php'; ?>
                                                <div class="form-field-style-subsection-row form-field-style-top-row mt-3">
                                                    <div class="form-field-style-subsection-pickers">
                                                        <div class="form-row">
                                                            <div class="form-group col-lg-3 form-field-style-mode-card">
                                                                <label>Etichette &amp; placeholder</label>
                                                                <select name="form_field_style" id="form_field_style" class="form-control">
                                                                    <option value="label_top" <?php echo $formFieldStyleValue === 'label_top' ? 'selected' : ''; ?>>Etichette sopra (classico)</option>
                                                                    <option value="placeholder" <?php echo $formFieldStyleValue === 'placeholder' ? 'selected' : ''; ?>>Placeholder dentro (compatto)</option>
                                                                </select>
                                                                <small class="form-text text-muted mt-1">
                                                                    <strong>Etichette sopra</strong>: stile classico, ogni campo ha una label sopra (anche Foto scontrino).
                                                                    <br>
                                                                    <strong>Placeholder dentro</strong>: le etichette spariscono e diventano testo segnaposto dentro al campo; per Foto scontrino il testo &laquo;Foto scontrino (&hellip;) *&raquo; sostituisce &laquo;Nessun file selezionato&raquo; nel controllo.
                                                                </small>
                                                            </div>
                                                            <div class="form-group col-lg-3">
                                                                <label for="form_field_error_mode">Stile messaggi di errore</label>
                                                                <select name="form_field_error_mode" id="form_field_error_mode" class="form-control">
                                                                    <option value="classic" <?php echo $formFieldErrorMode === 'classic' ? 'selected' : ''; ?>>Classico (rosa, testo rosso scuro)</option>
                                                                    <option value="on_color_background" <?php echo $formFieldErrorMode === 'on_color_background' ? 'selected' : ''; ?>>Su sfondo colorato (contrasto alto)</option>
                                                                </select>
                                                                <small class="form-text text-muted mt-1">Usa <em>Su sfondo colorato</em> se il container del form ha sfondo rosso o scuro.</small>
                                                            </div>
                                                            <div class="form-group col-lg-3">
                                                                <label for="form_field_font">Famiglia font dei campi</label>
                                                                <select name="form_field_font" id="form_field_font" class="form-control">
                                                                    <?php
                                                                        $formFieldFontValue = trim((string) ($content_current['form_field_font'] ?? ''));
                                                                        echo contest_font_select_options_html($formFieldFontValue, $content_current);
                                                                    ?>
                                                                </select>
                                                                <small class="form-text text-muted mt-1">Predefinito = tipografia della pagina.</small>
                                                            </div>
                                                            <div class="form-group col-lg-3">
                                                                <label for="form_field_font_size">Dimensione font campi</label>
                                                                <select name="form_field_font_size" id="form_field_font_size" class="form-control">
                                                                    <?php
                                                                        $formFieldFontSizeValue = contest_resolve_font_size((string) ($content_current['form_field_font_size'] ?? ''));
                                                                        echo contest_font_size_select_options_html($formFieldFontSizeValue);
                                                                    ?>
                                                                </select>
                                                                <small class="form-text text-muted mt-1">Predefinito = dimensione pagina. Visibile anche nell&apos;anteprima a destra.</small>
                                                            </div>
                                                            <div class="form-group col-lg-3">
                                                                <label for="form_field_border_radius">Arrotondamento (px)</label>
                                                                <input type="number" name="form_field_border_radius" id="form_field_border_radius" class="form-control" value="<?php echo (int) $formFieldBorderRadius; ?>" min="0" max="40" step="1">
                                                                <small class="form-text text-muted mt-1">0 = angoli a spigolo. Valori pi&ugrave; alti = campi pi&ugrave; arrotondati. Default: 6.</small>
                                                            </div>
                                                        </div>
                                                        <div class="custom-control custom-switch mt-2 mb-0">
                                                            <input type="checkbox" class="custom-control-input" id="style_customization_form_field_colors" name="style_customization_form_field_colors" value="1" <?php echo $formFieldColorsCustom ? 'checked' : ''; ?>>
                                                            <label class="custom-control-label" for="style_customization_form_field_colors">Personalizza colori campi</label>
                                                            <small class="form-text text-muted">Disattivo: eredita dalla palette. Attivo: personalizza i gruppi sotto.</small>
                                                        </div>
                                                    </div>
                                                    <div class="form-field-style-subsection-preview">
                                                        <span class="form-field-style-preview-label">Anteprima</span>
                                                        <div id="form-field-preview-top-bar-full"<?php echo $formFieldColorsCustom ? ' style="display:none;"' : ''; ?>>
                                                            <?php $preview_scope = 'full'; $preview_id = ''; include $ffspPreviewPartial; ?>
                                                        </div>
                                                        <div id="form-field-preview-top-bar-top"<?php echo $formFieldColorsCustom ? '' : ' style="display:none;"'; ?>>
                                                            <?php $preview_scope = 'top'; $preview_id = ''; include $ffspPreviewPartial; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="form-field-style-pickers-idle text-muted small mt-2" id="form-field-colors-idle"<?php echo $formFieldColorsCustom ? ' style="display:none;"' : ''; ?>>
                                                    Attiva <strong>Personalizza colori campi</strong> per modificare i colori degli input.
                                                </div>
                                                <div id="form-field-colors-panel" class="mt-3"<?php echo $formFieldColorsCustom ? '' : ' style="display:none;"'; ?>>
                                                    <div class="form-field-style-subsection-row">
                                                        <div class="form-field-style-subsection-pickers">
                                                            <div class="form-field-style-subsection">
                                                                <h6 class="form-field-style-subsection-title"><i class="fa fa-pencil-square-o mr-1"></i> Campi input</h6>
                                                                <div class="form-row form-field-style-colors-grid">
                                                                    <div class="form-group col-md-6 form-field-style-color-card">
                                                                        <label>Colore testo dei campi</label>
                                                                        <div class="d-flex align-items-center color-row">
                                                                            <input type="color" class="form-control color-input color-swatch" data-target="form_field_text_color_text" value="<?php echo manager_safe_html($formFieldTextColor); ?>">
                                                                            <input type="text" name="form_field_text_color" id="form_field_text_color_text" class="form-control color-code-input" value="<?php echo manager_safe_html($formFieldTextColor); ?>" placeholder="#000000" pattern="^#[0-9A-Fa-f]{6}$">
                                                                        </div>
                                                                        <small class="form-text text-muted mt-1">Testo digitato negli input (non i placeholder).</small>
                                                                    </div>
                                                                    <div class="form-group col-md-6 form-field-style-color-card">
                                                                        <label>Colore placeholder</label>
                                                                        <div class="d-flex align-items-center color-row">
                                                                            <input type="color" class="form-control color-input color-swatch" data-target="form_field_placeholder_color_text" value="<?php echo manager_safe_html($formFieldPlaceholderSwatch); ?>">
                                                                            <input type="text" name="form_field_placeholder_color" id="form_field_placeholder_color_text" class="form-control color-code-input" value="<?php echo manager_safe_html($formFieldPlaceholderColorStored); ?>" placeholder="Mix automatico" pattern="^$|^#[0-9A-Fa-f]{6}$">
                                                                        </div>
                                                                        <small class="form-text text-muted mt-1">Vuoto = mix automatico dal testo campi. Vale in entrambe le modalit&agrave;.</small>
                                                                    </div>
                                                                    <div class="form-group col-md-6 form-field-style-color-card">
                                                                        <label>Colore bordo dei campi</label>
                                                                        <div class="d-flex align-items-center color-row">
                                                                            <input type="color" class="form-control color-input color-swatch" data-target="form_field_border_color_text" value="<?php echo manager_safe_html($formFieldBorderColor); ?>">
                                                                            <input type="text" name="form_field_border_color" id="form_field_border_color_text" class="form-control color-code-input" value="<?php echo manager_safe_html($formFieldBorderColor); ?>" placeholder="#dee2e6" pattern="^#[0-9A-Fa-f]{6}$">
                                                                        </div>
                                                                        <small class="form-text text-muted mt-1">Default: <code>#dee2e6</code></small>
                                                                    </div>
                                                                    <div class="form-group col-md-6 form-field-style-color-card">
                                                                        <label>Colore sfondo dei campi</label>
                                                                        <div class="d-flex align-items-center color-row">
                                                                            <input type="color" class="form-control color-input color-swatch" data-target="form_field_bg_color_text" value="<?php echo manager_safe_html($formFieldBgColor); ?>">
                                                                            <input type="text" name="form_field_bg_color" id="form_field_bg_color_text" class="form-control color-code-input" value="<?php echo manager_safe_html($formFieldBgColor); ?>" placeholder="#ffffff" pattern="^#[0-9A-Fa-f]{6}$">
                                                                        </div>
                                                                        <div class="form-field-opacity-row mt-2">
                                                                            <label class="small text-muted mb-1">Opacit&agrave; sfondo</label>
                                                                            <div class="d-flex align-items-center opacity-row flex-wrap">
                                                                                <input type="range" class="form-control-range opacity-slider form-field-opacity-slider" min="0" max="100" step="1" value="<?php echo manager_safe_html((string) $formFieldBgOpacity); ?>" data-target="form_field_bg_opacity">
                                                                                <input type="number" name="form_field_bg_opacity" id="form_field_bg_opacity" class="form-control opacity-input ml-2" min="0" max="100" value="<?php echo manager_safe_html((string) $formFieldBgOpacity); ?>">
                                                                                <span class="ml-1 small text-muted">%</span>
                                                                                <button type="button" class="btn btn-sm btn-outline-secondary ml-2 form-field-set-transparent" data-target="form_field_bg_opacity">Trasparente</button>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="form-group col-md-6 form-field-style-color-card">
                                                                        <label>Colore focus campi</label>
                                                                        <div class="d-flex align-items-center color-row">
                                                                            <input type="color" class="form-control color-input color-swatch" data-target="form_field_focus_color_text" value="<?php echo manager_safe_html($formFieldFocusSwatch); ?>">
                                                                            <input type="text" name="form_field_focus_color" id="form_field_focus_color_text" class="form-control color-code-input" value="<?php echo manager_safe_html($formFieldFocusColorStored); ?>" placeholder="Primario palette" pattern="^$|^#[0-9A-Fa-f]{6}$">
                                                                        </div>
                                                                        <div class="form-field-opacity-row mt-2">
                                                                            <label class="small text-muted mb-1">Opacit&agrave; focus</label>
                                                                            <div class="d-flex align-items-center opacity-row">
                                                                                <input type="range" class="form-control-range opacity-slider form-field-opacity-slider" min="0" max="100" step="1" value="<?php echo manager_safe_html((string) $formFieldFocusOpacity); ?>" data-target="form_field_focus_opacity">
                                                                                <input type="number" name="form_field_focus_opacity" id="form_field_focus_opacity" class="form-control opacity-input ml-2" min="0" max="100" value="<?php echo manager_safe_html((string) $formFieldFocusOpacity); ?>">
                                                                                <span class="ml-1 small text-muted">%</span>
                                                                            </div>
                                                                        </div>
                                                                        <small class="form-text text-muted mt-1">Vuoto = <strong>Primario</strong> palette. Vale per input, select nativi e date picker.</small>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="form-field-style-subsection-preview">
                                                            <span class="form-field-style-preview-label">Anteprima</span>
                                                            <?php $preview_scope = 'input'; $preview_id = ''; include $ffspPreviewPartial; ?>
                                                        </div>
                                                    </div>
                                                    <div class="form-field-style-subsection-row mt-3<?php echo $ffsShowSelect2 ? '' : ' d-none'; ?>" data-ffs-section="select2">
                                                        <div class="form-field-style-subsection-pickers">
                                                            <div class="form-field-style-subsection">
                                                                <h6 class="form-field-style-subsection-title"><i class="fa fa-list-alt mr-1"></i> Select2</h6>
                                                                <p class="small text-muted mb-2">Il bordo focus del Select2 usa lo stesso colore di <strong>Colore focus campi</strong>.</p>
                                                                <div class="form-row form-field-style-colors-grid">
                                                                    <div class="form-group col-md-6 form-field-style-color-card">
                                                                        <label>Colore sfondo opzione evidenziata</label>
                                                                        <div class="d-flex align-items-center color-row">
                                                                            <input type="color" class="form-control color-input color-swatch" data-target="form_field_select2_highlight_bg_text" value="<?php echo manager_safe_html($formFieldSelect2HighlightBgSwatch); ?>">
                                                                            <input type="text" name="form_field_select2_highlight_bg" id="form_field_select2_highlight_bg_text" class="form-control color-code-input" value="<?php echo manager_safe_html($formFieldSelect2HighlightBgStored); ?>" placeholder="Mix focus + sfondo" pattern="^$|^#[0-9A-Fa-f]{6}$">
                                                                        </div>
                                                                        <small class="form-text text-muted mt-1">Vuoto = mix tra <strong>focus campi</strong> e <strong>sfondo campo</strong>.</small>
                                                                    </div>
                                                                    <div class="form-group col-md-6 form-field-style-color-card">
                                                                        <label>Colore testo opzione evidenziata</label>
                                                                        <div class="d-flex align-items-center color-row">
                                                                            <input type="color" class="form-control color-input color-swatch" data-target="form_field_select2_highlight_text_text" value="<?php echo manager_safe_html($formFieldSelect2HighlightTextSwatch); ?>">
                                                                            <input type="text" name="form_field_select2_highlight_text" id="form_field_select2_highlight_text_text" class="form-control color-code-input" value="<?php echo manager_safe_html($formFieldSelect2HighlightTextStored); ?>" placeholder="Automatico" pattern="^$|^#[0-9A-Fa-f]{6}$">
                                                                        </div>
                                                                        <small class="form-text text-muted mt-1">Vuoto = contrasto automatico sullo sfondo evidenziato.</small>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="form-field-style-subsection-preview">
                                                            <span class="form-field-style-preview-label">Anteprima</span>
                                                            <?php $preview_scope = 'select2'; $preview_id = ''; include $ffspPreviewPartial; ?>
                                                        </div>
                                                    </div>
                                                    <div class="form-field-style-subsection-row mt-3<?php echo $ffsShowDate ? '' : ' d-none'; ?>" data-ffs-section="date">
                                                        <div class="form-field-style-subsection-pickers">
                                                            <div class="form-field-style-subsection">
                                                                <h6 class="form-field-style-subsection-title"><i class="fa fa-calendar mr-1"></i> Calendario date</h6>
                                                                <p class="small text-muted mb-2">Colori del popup Flatpickr per <strong>Data scontrino</strong>. Vuoto = eredita dai colori campi / Select2.</p>
                                                                <div class="form-row form-field-style-colors-grid">
                                                                    <div class="form-group col-md-6 form-field-style-color-card">
                                                                        <label>Sfondo popup calendario</label>
                                                                        <div class="d-flex align-items-center color-row">
                                                                            <input type="color" class="form-control color-input color-swatch" data-target="form_field_calendar_popup_bg_text" value="<?php echo manager_safe_html($formFieldCalendarPopupBgSwatch); ?>">
                                                                            <input type="text" name="form_field_calendar_popup_bg" id="form_field_calendar_popup_bg_text" class="form-control color-code-input" value="<?php echo manager_safe_html($formFieldCalendarPopupBgStored); ?>" placeholder="Sfondo campi" pattern="^$|^#[0-9A-Fa-f]{6}$">
                                                                        </div>
                                                                        <small class="form-text text-muted mt-1">Vuoto = <strong>sfondo campi</strong>.</small>
                                                                    </div>
                                                                    <div class="form-group col-md-6 form-field-style-color-card">
                                                                        <label>Colore giorno selezionato</label>
                                                                        <div class="d-flex align-items-center color-row">
                                                                            <input type="color" class="form-control color-input color-swatch" data-target="form_field_calendar_selected_color_text" value="<?php echo manager_safe_html($formFieldCalendarSelectedSwatch); ?>">
                                                                            <input type="text" name="form_field_calendar_selected_color" id="form_field_calendar_selected_color_text" class="form-control color-code-input" value="<?php echo manager_safe_html($formFieldCalendarSelectedColorStored); ?>" placeholder="Focus campi" pattern="^$|^#[0-9A-Fa-f]{6}$">
                                                                        </div>
                                                                        <small class="form-text text-muted mt-1">Vuoto = <strong>colore focus campi</strong>.</small>
                                                                    </div>
                                                                    <div class="form-group col-md-6 form-field-style-color-card">
                                                                        <label>Sfondo giorno al survol</label>
                                                                        <div class="d-flex align-items-center color-row">
                                                                            <input type="color" class="form-control color-input color-swatch" data-target="form_field_calendar_hover_bg_text" value="<?php echo manager_safe_html($formFieldCalendarHoverBgSwatch); ?>">
                                                                            <input type="text" name="form_field_calendar_hover_bg" id="form_field_calendar_hover_bg_text" class="form-control color-code-input" value="<?php echo manager_safe_html($formFieldCalendarHoverBgStored); ?>" placeholder="Evidenziazione Select2" pattern="^$|^#[0-9A-Fa-f]{6}$">
                                                                        </div>
                                                                        <small class="form-text text-muted mt-1">Vuoto = <strong>evidenziazione Select2</strong>.</small>
                                                                    </div>
                                                                    <div class="form-group col-md-6 form-field-style-color-card">
                                                                        <label>Testo giorno al survol</label>
                                                                        <div class="d-flex align-items-center color-row">
                                                                            <input type="color" class="form-control color-input color-swatch" data-target="form_field_calendar_hover_text_text" value="<?php echo manager_safe_html($formFieldCalendarHoverTextSwatch); ?>">
                                                                            <input type="text" name="form_field_calendar_hover_text" id="form_field_calendar_hover_text_text" class="form-control color-code-input" value="<?php echo manager_safe_html($formFieldCalendarHoverTextStored); ?>" placeholder="Automatico" pattern="^$|^#[0-9A-Fa-f]{6}$">
                                                                        </div>
                                                                        <small class="form-text text-muted mt-1">Vuoto = contrasto automatico sullo sfondo survol.</small>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="form-field-style-subsection-preview">
                                                            <span class="form-field-style-preview-label">Anteprima</span>
                                                            <?php $preview_scope = 'date'; $preview_id = ''; include $ffspPreviewPartial; ?>
                                                        </div>
                                                    </div>
                                                    <div class="form-field-style-subsection-row mt-3<?php echo $ffsShowConsents ? '' : ' d-none'; ?>" data-ffs-section="consents">
                                                        <div class="form-field-style-subsection-pickers">
                                                            <div class="form-field-style-subsection">
                                                                <h6 class="form-field-style-subsection-title"><i class="fa fa-check-square-o mr-1"></i> Consensi</h6>
                                                                <div class="form-row form-field-style-colors-grid">
                                                                    <div class="form-group col-md-6 form-field-style-color-card">
                                                                        <label>Colore testo consensi</label>
                                                                        <div class="d-flex align-items-center color-row">
                                                                            <input type="color" class="form-control color-input color-swatch" data-target="form_field_consent_text_color_text" value="<?php echo manager_safe_html($formFieldConsentTextColor); ?>">
                                                                            <input type="text" name="form_field_consent_text_color" id="form_field_consent_text_color_text" class="form-control color-code-input" value="<?php echo manager_safe_html($formFieldConsentTextColor); ?>" placeholder="#000000" pattern="^#[0-9A-Fa-f]{6}$">
                                                                        </div>
                                                                        <small class="form-text text-muted mt-1">Testo privacy/marketing/profilazione (escluso il link).</small>
                                                                    </div>
                                                                    <div class="form-group col-md-6 form-field-style-color-card">
                                                                        <label>Colore checkbox</label>
                                                                        <div class="d-flex align-items-center color-row">
                                                                            <input type="color" class="form-control color-input color-swatch" data-target="form_field_checkbox_color_text" value="<?php echo manager_safe_html($formFieldCheckboxSwatch); ?>">
                                                                            <input type="text" name="form_field_checkbox_color" id="form_field_checkbox_color_text" class="form-control color-code-input" value="<?php echo manager_safe_html($formFieldCheckboxColorStored); ?>" placeholder="Secondario palette" pattern="^$|^#[0-9A-Fa-f]{6}$">
                                                                        </div>
                                                                        <small class="form-text text-muted mt-1">Vuoto = <strong>Secondario</strong></small>
                                                                    </div>
                                                                    <div class="form-group col-md-6 form-field-style-color-card">
                                                                        <label>Colore link consensi</label>
                                                                        <div class="d-flex align-items-center color-row">
                                                                            <input type="color" class="form-control color-input color-swatch" data-target="form_field_consent_link_color_text" value="<?php echo manager_safe_html($formFieldConsentLinkSwatch); ?>">
                                                                            <input type="text" name="form_field_consent_link_color" id="form_field_consent_link_color_text" class="form-control color-code-input" value="<?php echo manager_safe_html($formFieldConsentLinkColorStored); ?>" placeholder="Secondario palette" pattern="^$|^#[0-9A-Fa-f]{6}$">
                                                                        </div>
                                                                    </div>
                                                                    <div class="form-group col-md-6 form-field-style-color-card">
                                                                        <label>Colore hover link consensi</label>
                                                                        <div class="d-flex align-items-center color-row">
                                                                            <input type="color" class="form-control color-input color-swatch" data-target="form_field_consent_link_hover_color_text" value="<?php echo manager_safe_html($formFieldConsentLinkHoverSwatch); ?>">
                                                                            <input type="text" name="form_field_consent_link_hover_color" id="form_field_consent_link_hover_color_text" class="form-control color-code-input" value="<?php echo manager_safe_html($formFieldConsentLinkHoverColorStored); ?>" placeholder="Terziario palette" pattern="^$|^#[0-9A-Fa-f]{6}$">
                                                                        </div>
                                                                        <div class="form-field-opacity-row mt-2">
                                                                            <label class="small text-muted mb-1">Opacit&agrave; hover link</label>
                                                                            <div class="d-flex align-items-center opacity-row">
                                                                                <input type="range" class="form-control-range opacity-slider form-field-opacity-slider" min="0" max="100" step="1" value="<?php echo manager_safe_html((string) $formFieldConsentLinkHoverOpacity); ?>" data-target="form_field_consent_link_hover_opacity">
                                                                                <input type="number" name="form_field_consent_link_hover_opacity" id="form_field_consent_link_hover_opacity" class="form-control opacity-input ml-2" min="0" max="100" value="<?php echo manager_safe_html((string) $formFieldConsentLinkHoverOpacity); ?>">
                                                                                <span class="ml-1 small text-muted">%</span>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="form-field-style-subsection-preview">
                                                            <span class="form-field-style-preview-label">Anteprima</span>
                                                            <?php $preview_scope = 'consents'; $preview_id = ''; include $ffspPreviewPartial; ?>
                                                        </div>
                                                    </div>
                                                    <div class="form-row form-field-style-note-row mt-2" id="form_field_aspetto_note_row">
                                                        <div class="col-12">
                                                            <small class="form-text text-muted"><i class="fa fa-info-circle mr-1"></i>Colori applicati in entrambe le modalit&agrave; etichette, inclusi Select2, consensi (registrazione e Assistenza) e messaggi errore.</small>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                            <div class="configurazione-panel waiting-ui-panel waiting-ui-section form-page-container-panel config-anchor config-step" data-step="4" id="section-form-page">
                                <div class="configurazione-panel-header">
                                    <span class="configurazione-panel-icon"><i class="fa fa-th-large"></i></span>
                                    <div>
                                        <h6>Contenuti pagina registrazione</h6>
                                        <p>Gestisci container, testi, immagini e anteprima della pagina di registrazione.</p>
                                    </div>
                                </div>
                                <?php
            echo manager_render_page_container_editor([
                'tab_id' => 'form-page',
                'page_key' => 'form_page',
                'align_value' => $form_page_align_value,
                'containers' => $form_page_containers,
                'preview_containers' => $form_preview_containers,
                'preview_id' => 'form-page-preview',
                'default_background' => $waiting_container_default_background,
                'default_margin' => $waiting_container_default_margin,
                'default_radius' => $waiting_container_default_radius,
                'base_dir' => $baseDir,
                'placeholders' => [
                    'contest_name' => $waiting_preview_name,
                    'start_date' => $waiting_preview_start,
                    'end_date' => $waiting_preview_end,
                    'min_spend' => $waiting_preview_min_spend,
                    'regolamento_link' => $waiting_preview_rules_link,
                ],
            ]);
        ?>
                        </div>
                            </div>
                        
                        </div>

<?php
$render_thankyou_tab = static function (
    string $tab_id,
    string $page_key,
    string $align_value,
    array $containers,
    array $preview_containers,
    bool $is_active = false
) use (
    $waiting_container_default_background,
    $waiting_container_default_margin,
    $waiting_container_default_radius,
    $waiting_preview_name,
    $waiting_preview_start,
    $waiting_preview_end,
    $waiting_preview_min_spend,
    $waiting_preview_rules_link,
    $thankyou_preview_prize_win_amount,
    $thankyou_preview_prize_type,
    $baseDir
): string {
    return manager_render_page_container_editor([
        'tab_id' => $tab_id,
        'page_key' => $page_key,
        'align_value' => $align_value,
        'containers' => $containers,
        'preview_containers' => $preview_containers,
        'default_background' => $waiting_container_default_background,
        'default_margin' => $waiting_container_default_margin,
        'default_radius' => $waiting_container_default_radius,
        'base_dir' => $baseDir,
        'show_prize_placeholder' => strpos($page_key, 'win') !== false,
        'placeholders' => [
            'contest_name' => $waiting_preview_name,
            'start_date' => $waiting_preview_start,
            'end_date' => $waiting_preview_end,
            'min_spend' => $waiting_preview_min_spend,
            'regolamento_link' => $waiting_preview_rules_link,
            'prize_win_amount' => $thankyou_preview_prize_win_amount,
            'prize_type' => $thankyou_preview_prize_type,
        ],
    ]);
};
?>
