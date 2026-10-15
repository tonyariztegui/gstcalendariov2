<div class="card mt-3 mb-4 config-anchor config-step configurazione-section" data-step="1" id="section-dati">
                                <div class="card-body">
                                    <div class="configurazione-hero">
                                        <div>
                                            <div class="section-title configurazione-title"><i class="fa fa-list-alt mr-2"></i>Dati concorso</div>
                                            <p class="text-muted small mb-0">Imposta identita, date, modalita e documenti principali del concorso.</p>
                                        </div>
                                        <span class="configurazione-badge"><i class="fa fa-cog mr-1"></i> Configurazione base</span>
                                    </div>
                                    <div class="configurazione-panel">
                                        <div class="configurazione-panel-header">
                                            <span class="configurazione-panel-icon"><i class="fa fa-pencil"></i></span>
                                            <div>
                                                <h6>Identita concorso</h6>
                                                <p>Informazioni principali mostrate nelle pagine pubbliche e nelle comunicazioni.</p>
                                            </div>
                                        </div>
                                    <div class="form-group">
                                        <label>Nome concorso</label>
                                        <input type="text" name="name" class="form-control" value="<?php echo manager_safe_html($content_current['name'] ?? ''); ?>">
                                        <small class="form-text text-muted d-none">Titolo pubblico mostrato nelle pagine, nelle email e nell'area manager.</small>
                                    </div>
                                    </div>
                                    <div class="configurazione-panel">
                                        <div class="configurazione-panel-header">
                                            <span class="configurazione-panel-icon"><i class="fa fa-calendar"></i></span>
                                            <div>
                                                <h6>Date e formato</h6>
                                                <p>Gestisci periodo reale del concorso e formato con cui le date vengono mostrate.</p>
                                            </div>
                                        </div>
                                    <div class="form-row">
                                        <?php
                                            // Date dinamiche nei defaults: "today/oggi" e "+30 days" (solo per precompilare la UI).
                                            $start_date_input_raw = $content_current['start_date'] ?? ($content_defaults['start_date'] ?? '');
                                            $end_date_input_raw = $content_current['end_date'] ?? ($content_defaults['end_date'] ?? '');

                                            $start_date_input_raw = is_string($start_date_input_raw) ? trim($start_date_input_raw) : '';
                                            $end_date_input_raw = is_string($end_date_input_raw) ? trim($end_date_input_raw) : '';

                                            $start_token = strtolower($start_date_input_raw);
                                            $start_date_input = ($start_date_input_raw === '' || $start_token === 'today' || $start_token === 'oggi')
                                                ? date('Y-m-d')
                                                : $start_date_input_raw;

                                            $end_token = strtolower($end_date_input_raw);
                                            if ($end_date_input_raw === '' || $end_token === 'today' || $end_token === 'oggi') {
                                                $end_date_input = date('Y-m-d');
                                            } elseif ($end_date_input_raw !== '' && ($end_date_input_raw[0] === '+' || $end_date_input_raw[0] === '-')) {
                                                $computed_end = strtotime($start_date_input . ' ' . $end_date_input_raw);
                                                $end_date_input = $computed_end ? date('Y-m-d', $computed_end) : $end_date_input_raw;
                                            } else {
                                                $end_date_input = $end_date_input_raw;
                                            }
                                        ?>
                                        <div class="form-group col-md-6">
                                            <label>Data inizio operatività</label>
                                            <input type="date" name="start_date" class="form-control" value="<?php echo manager_safe_html($start_date_input); ?>">
                                            <small class="form-text text-muted">Periodo operativo (logica concorso, IW, validazione form, test).</small>
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label>Data fine operatività</label>
                                            <input type="date" name="end_date" class="form-control" value="<?php echo manager_safe_html($end_date_input); ?>">
                                            <small class="form-text text-muted">Ultimo giorno di partecipazione consentita.</small>
                                        </div>
                                    </div>
                                    <div class="form-row">
                                        <?php
                                            $display_start_input = trim((string) ($content_current['display_start_date'] ?? ''));
                                            $display_end_input = trim((string) ($content_current['display_end_date'] ?? ''));
                                            if ($display_start_input !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $display_start_input)) {
                                                $display_start_input = '';
                                            }
                                            if ($display_end_input !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $display_end_input)) {
                                                $display_end_input = '';
                                            }
                                        ?>
                                        <div class="form-group col-md-6">
                                            <label>Data inizio visualizzata</label>
                                            <input type="date" name="display_start_date" class="form-control" value="<?php echo manager_safe_html($display_start_input); ?>">
                                            <small class="form-text text-muted">Data mostrata nel sito, footer, email e placeholder. Vuoto = stessa data inizio operatività.</small>
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label>Data fine visualizzata</label>
                                            <input type="date" name="display_end_date" class="form-control" value="<?php echo manager_safe_html($display_end_input); ?>">
                                            <small class="form-text text-muted">Vuoto = stessa data fine operatività.</small>
                                        </div>
                                    </div>
                                    <div class="form-row">
                                        <?php
                                            $date_example = date('Y-m-d');
                                        ?>
                                        <div class="form-group col-md-6">
                                            <label>Formato data <span class="text-muted ml-1" data-toggle="tooltip" title="Scegli come visualizzare le date nelle pagine pubbliche (es. <?php echo date('j F Y', strtotime($date_example)); ?> invece di <?php echo $date_example; ?>)."><i class="fa fa-question-circle"></i></span></label>
                                            <select name="date_format" id="date_format" class="form-control">
                                                <option value="Y-m-d" <?php echo ($content_current['date_format'] ?? 'd/m/Y') === 'Y-m-d' ? 'selected' : ''; ?>><?php echo date('Y-m-d', strtotime($date_example)); ?> (Anno-Mese-Giorno)</option>
                                                <option value="d/m/Y" <?php echo ($content_current['date_format'] ?? 'd/m/Y') === 'd/m/Y' ? 'selected' : ''; ?>><?php echo date('d/m/Y', strtotime($date_example)); ?> (Giorno/Mese/Anno)</option>
                                                <option value="d-m-Y" <?php echo ($content_current['date_format'] ?? 'd/m/Y') === 'd-m-Y' ? 'selected' : ''; ?>><?php echo date('d-m-Y', strtotime($date_example)); ?> (Giorno-Mese-Anno)</option>
                                                <option value="j F Y" <?php echo ($content_current['date_format'] ?? 'd/m/Y') === 'j F Y' ? 'selected' : ''; ?>><?php echo date('j F Y', strtotime($date_example)); ?> (Giorno Mese Anno)</option>
                                                <option value="j M Y" <?php echo ($content_current['date_format'] ?? 'd/m/Y') === 'j M Y' ? 'selected' : ''; ?>><?php echo date('j M Y', strtotime($date_example)); ?> (Giorno Mese Abbr. Anno)</option>
                                                <option value="l j F Y" <?php echo ($content_current['date_format'] ?? 'd/m/Y') === 'l j F Y' ? 'selected' : ''; ?>><?php echo date('l j F Y', strtotime($date_example)); ?> (Giorno settimana completo)</option>
                                                <option value="custom" <?php echo (!empty($content_current['date_format']) && !in_array($content_current['date_format'], ['Y-m-d', 'd/m/Y', 'd-m-Y', 'j F Y', 'j M Y', 'l j F Y'])) ? 'selected' : ''; ?>>Personalizzato</option>
                                            </select>
                                            <small class="form-text text-muted d-none">Esempi: <code>d/m/Y</code> = 24/12/2025, <code>j F Y</code> = 24 dicembre 2025</small>
                                        </div>
                                        <div class="form-group col-md-6" id="date_format_custom_wrapper" style="display: none;">
                                            <label>Formato personalizzato <span class="text-muted ml-1" data-toggle="tooltip" title="Inserisci un formato PHP date() personalizzato (es. d/m/Y, j F Y, ecc.)."><i class="fa fa-question-circle"></i></span></label>
                                            <input type="text" name="date_format_custom" id="date_format_custom" class="form-control" value="<?php echo manager_safe_html((!empty($content_current['date_format']) && !in_array($content_current['date_format'], ['Y-m-d', 'd/m/Y', 'd-m-Y', 'j F Y', 'j M Y', 'l j F Y'])) ? $content_current['date_format'] : ''); ?>" placeholder="es. d/m/Y → <?php echo date('d/m/Y'); ?>">
                                        </div>
                                    </div>
                                    </div>
                                    <div class="configurazione-panel">
                                        <div class="configurazione-panel-header">
                                            <span class="configurazione-panel-icon"><i class="fa fa-sliders"></i></span>
                                            <div>
                                                <h6>Esperienza partecipante</h6>
                                                <p>Scegli modalit&agrave; del concorso, sotto modalit&agrave; e valore montepremi mostrato nei testi.</p>
                                            </div>
                                        </div>
                                    <div class="form-group">
                                        <label>Modalit&#224; concorso <span class="text-muted ml-1" data-toggle="tooltip" title="Scegli la logica: Estrazione (raffle a fine periodo) o Instant Win (esito immediato)."><i class="fa fa-question-circle"></i></span></label>
                                        <select name="contest_mode" class="form-control">
                                            <option value="estrazione" <?php echo ($content_current['contest_mode'] ?? ($content_defaults['contest_mode'] ?? '')) === 'estrazione' ? 'selected' : ''; ?>>Estrazione</option>
                                            <option value="instantwin" <?php echo ($content_current['contest_mode'] ?? ($content_defaults['contest_mode'] ?? '')) === 'instantwin' ? 'selected' : ''; ?>>Instant Win</option>
                                        </select>
                                    </div>
                                    <?php
                                    $quiz_enabled_ui = !empty($content_current['quiz_enabled'] ?? false);
                                    $quiz_help_tooltip = 'Flusso pubblico: iscrizione → quiz (se abilitato) → thank-you. Attiva o disattiva qui, poi salva il concorso con il pulsante in fondo alla pagina. Domande per sessione, tipo di percorso e richiesta di tutte le risposte corrette: passo Quiz.';
                                    ?>
                                    <div class="configurazione-subsection mt-3 mb-3" id="esperienza-sotto-modalita">
                                        <label class="font-weight-bold d-block mb-1">
                                            Sotto modalit&agrave;
                                            <span class="text-muted font-weight-normal ml-1" data-toggle="tooltip" title="Opzioni aggiuntive al flusso di partecipazione, indipendenti dalla modalit&agrave; e dalla ripartizione premi."><i class="fa fa-question-circle" aria-hidden="true"></i></span>
                                        </label>
                                        <p class="small text-muted mb-2">Opzioni aggiuntive al flusso di partecipazione, indipendenti dalla modalit&agrave; e dalla ripartizione premi.</p>
                                        <div class="custom-control custom-checkbox" data-sub-mode="quiz">
                                            <input type="checkbox" class="custom-control-input" id="config-quiz-enabled-checkbox" name="quiz_enabled" value="1" <?php echo $quiz_enabled_ui ? 'checked' : ''; ?>>
                                            <label class="custom-control-label" for="config-quiz-enabled-checkbox">
                                                Quiz
                                                <span class="text-muted font-weight-normal ml-1" data-toggle="tooltip" title="<?php echo htmlspecialchars($quiz_help_tooltip, ENT_QUOTES, 'UTF-8'); ?>"><i class="fa fa-question-circle" aria-hidden="true"></i></span>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="form-row">
                                        <div class="form-group col-md-12">
                                            <label>Montepremi (€) <span class="text-muted ml-1" data-toggle="tooltip" title="Valore totale premi mostrato nei testi informativi (non modifica la logica). Solo numeri validi (es. 1000 o 1000,50)."><i class="fa fa-question-circle"></i></span></label>
                                            <input type="text" inputmode="decimal" name="prize_total" id="prize_total" class="form-control js-format-number-it" value="<?php echo manager_safe_html((string)($content_current['prize_total'] ?? '')); ?>">
                                        </div>
                                    </div>
                                    </div>
                                    <?php
                                    require_once __DIR__ . '/../../../app/inc/qr_config.php';
                                    $qrAccessDefaults = is_array($content_defaults['qr_access'] ?? null) ? $content_defaults['qr_access'] : [];
                                    $qrAccessCurrent = array_merge($qrAccessDefaults, is_array($content_current['qr_access'] ?? null) ? $content_current['qr_access'] : []);
                                    $qrAccessEnabled = !empty($qrAccessCurrent['enabled']);
                                    $qrVerifySignature = array_key_exists('verify_signature', $qrAccessCurrent) ? !empty($qrAccessCurrent['verify_signature']) : true;
                                    $qrUsePayloadGiocate = array_key_exists('use_payload_giocate', $qrAccessCurrent) ? !empty($qrAccessCurrent['use_payload_giocate']) : true;
                                    $qrVerifyContestCodeSp = array_key_exists('verify_contest_code_sp', $qrAccessCurrent) ? !empty($qrAccessCurrent['verify_contest_code_sp']) : false;
                                    $qrVerifyContestCodeMc = array_key_exists('verify_contest_code_mc', $qrAccessCurrent) ? !empty($qrAccessCurrent['verify_contest_code_mc']) : true;
                                    $qrSignatureAlgorithms = qr_config_normalize_signature_algorithms($qrAccessCurrent['signature_algorithms'] ?? null);
                                    $qrSignatureSha256 = in_array('sha256', $qrSignatureAlgorithms, true);
                                    $qrSignatureSha1 = in_array('sha1', $qrSignatureAlgorithms, true);
                                    $qrReceiptMode = strtolower(trim((string) ($qrAccessCurrent['receipt_date_mode'] ?? 'contest_dates')));
                                    if ($qrReceiptMode === 'whitelist') {
                                        $qrReceiptMode = 'contest_dates';
                                    }
                                    if (!in_array($qrReceiptMode, ['none', 'min', 'contest_dates'], true)) {
                                        $qrReceiptMode = 'contest_dates';
                                    }
                                    $qrErrorLabels = qr_config_system_error_labels();
                                    $qrErrorsDefaultsUi = qr_config_default_errors();
                                    $qrErrorsSaved = is_array($qrAccessCurrent['errors'] ?? null) ? $qrAccessCurrent['errors'] : [];
                                    $qrErrorsListUi = [];
                                    foreach (qr_config_system_error_keys() as $qrErrKey) {
                                        $entry = is_array($qrErrorsSaved[$qrErrKey] ?? null) ? $qrErrorsSaved[$qrErrKey] : [];
                                        if ($qrErrKey === 'qr_invalid') {
                                            foreach (['qr_invalid', 'qr_used', 'qr_receipt', 'qr_contest'] as $legacyKey) {
                                                if (trim((string) ($entry['title'] ?? '')) === '' && is_array($qrErrorsSaved[$legacyKey] ?? null)) {
                                                    $legacyTitle = trim((string) ($qrErrorsSaved[$legacyKey]['title'] ?? ''));
                                                    if ($legacyTitle !== '') {
                                                        $entry['title'] = $legacyTitle;
                                                    }
                                                }
                                            }
                                        }
                                        $qrErrorsListUi[] = [
                                            'key' => $qrErrKey,
                                            'label' => (string) ($qrErrorLabels[$qrErrKey] ?? $qrErrKey),
                                            'title' => trim((string) ($entry['title'] ?? '')) !== ''
                                                ? (string) $entry['title']
                                                : (string) ($qrErrorsDefaultsUi[$qrErrKey]['title'] ?? ''),
                                            'text' => trim((string) ($entry['text'] ?? '')) !== ''
                                                ? (string) $entry['text']
                                                : (string) ($qrErrorsDefaultsUi[$qrErrKey]['text'] ?? ''),
                                        ];
                                    }
                                    ?>
                                    <div class="configurazione-panel qr-access-panel" id="qr-access-panel">
                                        <div class="configurazione-panel-header">
                                            <span class="configurazione-panel-icon"><i class="fa fa-qrcode"></i></span>
                                            <div>
                                                <h6>Accesso QR (verify)</h6>
                                                <p class="mb-0 small">Scansione obbligatoria dello scontrino. URL: <code>/verifica?code=...</code></p>
                                            </div>
                                        </div>
                                        <div class="form-group mb-2">
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox" class="custom-control-input" id="qr_access_enabled" name="qr_access[enabled]" value="1" <?php echo $qrAccessEnabled ? 'checked' : ''; ?>>
                                                <label class="custom-control-label font-weight-bold" for="qr_access_enabled">Abilita accesso tramite QR</label>
                                            </div>
                                        </div>
                                        <div id="qr-access-options" class="qr-access-options" style="<?php echo $qrAccessEnabled ? '' : 'display:none;'; ?>">
                                            <div class="form-row align-items-end">
                                                <div class="form-group col-md-4 col-lg-3 mb-2">
                                                    <label class="small mb-1">Codice concorso</label>
                                                    <input type="text" name="qr_access[contest_code]" class="form-control form-control-sm" value="<?php echo manager_safe_html((string) ($qrAccessCurrent['contest_code'] ?? '')); ?>" placeholder="es. MD202606">
                                                    <div class="d-flex flex-wrap mt-1" style="gap: 0.75rem;">
                                                        <div class="custom-control custom-checkbox">
                                                            <input type="checkbox" class="custom-control-input" id="qr_access_verify_contest_code_sp" name="qr_access[verify_contest_code_sp]" value="1" <?php echo $qrVerifyContestCodeSp ? 'checked' : ''; ?>>
                                                            <label class="custom-control-label small" for="qr_access_verify_contest_code_sp" title="Confronta il 10° segmento del QR con il codice concorso per gli emettitori SP">Verifica SP</label>
                                                        </div>
                                                        <div class="custom-control custom-checkbox">
                                                            <input type="checkbox" class="custom-control-input" id="qr_access_verify_contest_code_mc" name="qr_access[verify_contest_code_mc]" value="1" <?php echo $qrVerifyContestCodeMc ? 'checked' : ''; ?>>
                                                            <label class="custom-control-label small" for="qr_access_verify_contest_code_mc" title="Confronta il 10° segmento del QR con il codice concorso per gli emettitori MC">Verifica MC</label>
                                                        </div>
                                                    </div>
                                                    <small class="form-text text-muted">Attiva la verifica per SP e/o MC. Se disattivata, il segmento concorso nel QR viene ignorato per quell'emettitore.</small>
                                                </div>
                                                <div class="form-group col-md-4 col-lg-2 mb-2">
                                                    <label class="small mb-1">Max utilizzi</label>
                                                    <input type="number" min="1" name="qr_access[max_uses_per_code]" class="form-control form-control-sm" value="<?php echo (int) ($qrAccessCurrent['max_uses_per_code'] ?? 1); ?>">
                                                </div>
                                                <div class="form-group col-md-4 col-lg-2 mb-2">
                                                    <label class="small mb-1">Tempo sessione (s)</label>
                                                    <input type="number" min="0" name="qr_access[rate_limit_seconds]" class="form-control form-control-sm" value="<?php echo (int) ($qrAccessCurrent['rate_limit_seconds'] ?? 10); ?>">
                                                </div>
                                                <div class="form-group col-md-6 col-lg-5 mb-2">
                                                    <div class="d-flex flex-wrap" style="gap: 1rem;">
                                                        <div class="custom-control custom-checkbox">
                                                            <input type="checkbox" class="custom-control-input" id="qr_access_verify_signature" name="qr_access[verify_signature]" value="1" <?php echo $qrVerifySignature ? 'checked' : ''; ?>>
                                                            <label class="custom-control-label small" for="qr_access_verify_signature" title="Verifica la firma RSA del payload con la chiave SP o MC">Firma digitale</label>
                                                        </div>
                                                        <div class="custom-control custom-checkbox">
                                                            <input type="checkbox" class="custom-control-input" id="qr_access_signature_sha256" name="qr_access[signature_sha256]" value="1" <?php echo $qrSignatureSha256 ? 'checked' : ''; ?>>
                                                            <label class="custom-control-label small" for="qr_access_signature_sha256" title="Accetta firme RSA con hash SHA-256">Hash SHA-256</label>
                                                        </div>
                                                        <div class="custom-control custom-checkbox">
                                                            <input type="checkbox" class="custom-control-input" id="qr_access_signature_sha1" name="qr_access[signature_sha1]" value="1" <?php echo $qrSignatureSha1 ? 'checked' : ''; ?>>
                                                            <label class="custom-control-label small" for="qr_access_signature_sha1" title="Accetta firme RSA legacy con hash SHA-1 (scontrini SP)">Hash SHA-1</label>
                                                        </div>
                                                        <div class="custom-control custom-checkbox">
                                                            <input type="checkbox" class="custom-control-input" id="qr_access_use_payload_giocate" name="qr_access[use_payload_giocate]" value="1" <?php echo $qrUsePayloadGiocate ? 'checked' : ''; ?>>
                                                            <label class="custom-control-label small" for="qr_access_use_payload_giocate" title="Usa il campo giocate (8° segmento) del QR">Giocate da payload</label>
                                                        </div>
                                                    </div>
                                                    <small class="form-text text-muted">Per la firma RSA: seleziona uno o entrambi gli hash. SHA-1 per QR SP legacy; SHA-256 per firme recenti.</small>
                                                </div>
                                            </div>
                                            <p class="small text-muted mb-2">
                                                <strong>SP / MC</strong> = emettitore nel QR (ultimo campo). La chiave pubblica PEM corrispondente verifica la firma del payload.
                                            </p>
                                            <a class="btn btn-link btn-sm p-0 qr-access-toggle" data-toggle="collapse" href="#qr-access-advanced" role="button" aria-expanded="false" aria-controls="qr-access-advanced">
                                                <i class="fa fa-key mr-1"></i> Chiavi pubbliche e controllo data scontrino
                                            </a>
                                            <div class="collapse mt-2" id="qr-access-advanced">
                                                <div class="form-row">
                                                    <div class="form-group col-md-6 mb-2">
                                                        <label class="small mb-1">Chiave pubblica SP (PEM) <span class="text-muted" title="Supermercato / emettitore SP">(?)</span></label>
                                                        <textarea name="qr_access[public_key_sp]" class="form-control form-control-sm qr-access-key-input" rows="2" placeholder="-----BEGIN PUBLIC KEY-----"><?php echo manager_safe_html((string) ($qrAccessCurrent['public_key_sp'] ?? '')); ?></textarea>
                                                    </div>
                                                    <div class="form-group col-md-6 mb-2">
                                                        <label class="small mb-1">Chiave pubblica MC (PEM) <span class="text-muted" title="Emettitore MC">(?)</span></label>
                                                        <textarea name="qr_access[public_key_mc]" class="form-control form-control-sm qr-access-key-input" rows="2" placeholder="-----BEGIN PUBLIC KEY-----"><?php echo manager_safe_html((string) ($qrAccessCurrent['public_key_mc'] ?? '')); ?></textarea>
                                                    </div>
                                                </div>
                                                <div class="form-row align-items-end">
                                                    <div class="form-group col-md-4 mb-2">
                                                        <label class="small mb-1">Controllo data scontrino</label>
                                                        <select name="qr_access[receipt_date_mode]" id="qr_access_receipt_date_mode" class="form-control form-control-sm">
                                                            <option value="contest_dates" <?php echo $qrReceiptMode === 'contest_dates' ? 'selected' : ''; ?>>Periodo concorso (date operatività)</option>
                                                            <option value="min" <?php echo $qrReceiptMode === 'min' ? 'selected' : ''; ?>>Data minima (YYMMDD)</option>
                                                            <option value="none" <?php echo $qrReceiptMode === 'none' ? 'selected' : ''; ?>>Nessuno</option>
                                                        </select>
                                                        <small class="form-text text-muted">Periodo concorso: confronta la data nel QR (YYMMDD) con le date operative del concorso.</small>
                                                    </div>
                                                    <div class="form-group col-md-4 mb-2" id="qr_access_receipt_min_wrap" style="<?php echo $qrReceiptMode === 'min' ? '' : 'display:none;'; ?>">
                                                        <label class="small mb-1">Data minima (YYMMDD)</label>
                                                        <input type="text" name="qr_access[receipt_date_min]" class="form-control form-control-sm" value="<?php echo manager_safe_html((string) ($qrAccessCurrent['receipt_date_min'] ?? '')); ?>" placeholder="250630">
                                                    </div>
                                                </div>
                                            </div>
                                            <a class="btn btn-link btn-sm p-0 qr-access-toggle mt-1" data-toggle="collapse" href="#qr-access-errors" role="button" aria-expanded="false" aria-controls="qr-access-errors">
                                                <i class="fa fa-commenting-o mr-1"></i> Messaggi errore (<span id="qr-errors-count"><?php echo count($qrErrorsListUi); ?></span>)
                                            </a>
                                            <div class="collapse mt-2" id="qr-access-errors">
                                                <p class="small text-muted mb-2">Come md_petfood2: 3 messaggi (codice assente, non valido/già usato, rate limit).</p>
                                                <div id="qr-errors-list" class="qr-errors-list">
                                                    <?php foreach ($qrErrorsListUi as $qrErrIndex => $qrErrRow): ?>
                                                        <div class="qr-error-row form-row align-items-start mb-2">
                                                            <input type="hidden" name="qr_access[errors_list][<?php echo (int) $qrErrIndex; ?>][key]" value="<?php echo manager_safe_html((string) ($qrErrRow['key'] ?? '')); ?>">
                                                            <div class="form-group col-md-4 mb-1">
                                                                <label class="small mb-0 text-muted"><?php echo manager_safe_html((string) ($qrErrRow['label'] ?? '')); ?></label>
                                                                <input type="text" name="qr_access[errors_list][<?php echo (int) $qrErrIndex; ?>][title]" class="form-control form-control-sm" value="<?php echo manager_safe_html((string) ($qrErrRow['title'] ?? '')); ?>">
                                                            </div>
                                                            <div class="form-group col-md-8 mb-1">
                                                                <label class="small mb-0">Testo aggiuntivo (opzionale)</label>
                                                                <input type="text" name="qr_access[errors_list][<?php echo (int) $qrErrIndex; ?>][text]" class="form-control form-control-sm" value="<?php echo manager_safe_html((string) ($qrErrRow['text'] ?? '')); ?>">
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                                <button type="button" class="btn btn-outline-secondary btn-sm" id="qr-error-reset-defaults">Ripristina testi predefiniti</button>
                                            </div>
                                        </div>
                                    </div>
                                    <script>
                                    (function () {
                                        var enabled = document.getElementById('qr_access_enabled');
                                        var options = document.getElementById('qr-access-options');
                                        var mode = document.getElementById('qr_access_receipt_date_mode');
                                        var minWrap = document.getElementById('qr_access_receipt_min_wrap');
                                        var errorsList = document.getElementById('qr-errors-list');
                                        var resetBtn = document.getElementById('qr-error-reset-defaults');
                                        var qrDefaults = <?php echo json_encode(qr_config_default_errors(), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;

                                        function syncQrPanel() {
                                            if (!enabled || !options) return;
                                            options.style.display = enabled.checked ? '' : 'none';
                                            if (typeof window.syncQrVerifyAnteprima === 'function') {
                                                window.syncQrVerifyAnteprima();
                                            }
                                        }
                                        function syncReceiptMode() {
                                            if (!mode || !minWrap) return;
                                            var value = mode.value || 'contest_dates';
                                            minWrap.style.display = value === 'min' ? '' : 'none';
                                        }
                                        if (resetBtn && errorsList) {
                                            resetBtn.addEventListener('click', function () {
                                                errorsList.querySelectorAll('.qr-error-row').forEach(function (row) {
                                                    var keyInput = row.querySelector('input[type="hidden"][name$="[key]"]');
                                                    var titleInput = row.querySelector('[name$="[title]"]');
                                                    var textInput = row.querySelector('[name$="[text]"]');
                                                    var key = keyInput ? keyInput.value : '';
                                                    var entry = qrDefaults[key] || {};
                                                    if (titleInput) titleInput.value = entry.title || '';
                                                    if (textInput) textInput.value = entry.text || '';
                                                });
                                            });
                                        }
                                        if (enabled) enabled.addEventListener('change', syncQrPanel);
                                        if (mode) mode.addEventListener('change', syncReceiptMode);
                                        syncQrPanel();
                                        syncReceiptMode();
                                    })();
                                    </script>
                                    <?php
                                    // Source de vérité pour l'affichage : DB (pas content). En cas de POST après soumission, afficher les données POST (préserve l'input utilisateur en cas d'erreur).
                                    $premiCurrent = [];
                                    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_config']) && isset($_POST['premi']) && is_array($_POST['premi'])) {
                                        foreach ($_POST['premi'] as $p) {
                                            $premiCurrent[] = [
                                                'valore' => isset($p['valore']) ? trim((string)$p['valore']) : '',
                                                'tipo_premio' => isset($p['tipo_premio']) ? trim((string)$p['tipo_premio']) : '',
                                                'quantita_totale' => isset($p['quantita_totale']) ? (int)$p['quantita_totale'] : 0,
                                                'is_active' => isset($p['is_active']) && ($p['is_active'] === '1' || $p['is_active'] === 1),
                                            ];
                                        }
                                    } else {
                                        $pdo_premi = isset($connection) && $connection instanceof PDO ? $connection : (function_exists('conn_pdo') ? conn_pdo() : null);
                                        if ($pdo_premi instanceof PDO) {
                                            try {
                                                // Ordine = id ASC: deve coincidere con la sync al salvataggio
                                                // (match per indice su premi ORDER BY id). Altrimenti un Salva
                                                // scambia valore/quantità tra id e rompe l'import prize_id.
                                                $stmt = $pdo_premi->query('
                                                    SELECT MIN(`id`) AS `id`, `valore`, `tipo_premio`,
                                                           SUM(`quantita_totale`) AS `quantita_totale`,
                                                           MAX(`is_active`) AS `is_active`
                                                    FROM `premi`
                                                    GROUP BY `valore`, `tipo_premio`
                                                    ORDER BY MIN(`id`) ASC
                                                ');
                                                $premiRows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
                                                foreach ($premiRows as $row) {
                                                    $premiCurrent[] = [
                                                        'valore' => isset($row['valore']) ? (string)$row['valore'] : '',
                                                        'tipo_premio' => trim((string)($row['tipo_premio'] ?? '')),
                                                        'quantita_totale' => (int)($row['quantita_totale'] ?? 0),
                                                        'is_active' => !empty($row['is_active']),
                                                    ];
                                                }
                                            } catch (Exception $e) {
                                                $premiCurrent = [];
                                            }
                                        }
                                        if ($premiCurrent === [] && !empty($content_current['premi']) && is_array($content_current['premi'])) {
                                            foreach ($content_current['premi'] as $p) {
                                                if (!is_array($p)) {
                                                    continue;
                                                }
                                                if (!empty($p['codice_cedi'])) {
                                                    continue;
                                                }
                                                $premiCurrent[] = [
                                                    'valore' => isset($p['valore']) ? (string)$p['valore'] : '',
                                                    'tipo_premio' => trim((string)($p['tipo_premio'] ?? '')),
                                                    'quantita_totale' => (int)($p['quantita_totale'] ?? 0),
                                                    'is_active' => !isset($p['is_active']) || $p['is_active'] === true || $p['is_active'] === '1' || $p['is_active'] === 1,
                                                ];
                                            }
                                        }
                                    }
                                    $premiCurrent = is_array($premiCurrent) ? $premiCurrent : [];
                                    $isInstantwinConfig = ($content_current['contest_mode'] ?? ($content_defaults['contest_mode'] ?? '')) === 'instantwin';
                                    if (!function_exists('contest_normalize_instantwin_prize_distribution')) {
                                        require_once __DIR__ . '/../../../app/inc/functions.php';
                                    }
                                    $iwPrizeDistributionUi = contest_normalize_instantwin_prize_distribution(is_array($content_current) ? $content_current : []);
                                    $perPdvChecked = ($iwPrizeDistributionUi === 'pdv');
                                    $perCediChecked = ($iwPrizeDistributionUi === 'cedi');
                                    if (!$perCediChecked && $premiCurrent === []) {
                                        $premiCurrent[] = [
                                            'valore' => '',
                                            'tipo_premio' => '',
                                            'quantita_totale' => 0,
                                            'is_active' => true,
                                        ];
                                    }
                                    $premiCodiciCounts = [];
                                    $pdo_premi_counts = isset($connection) && $connection instanceof PDO ? $connection : (function_exists('conn_pdo') ? conn_pdo() : null);
                                    if ($pdo_premi_counts instanceof PDO) {
                                        try {
                                            $stmt_p = $pdo_premi_counts->query('SELECT MIN(id) AS id, valore, tipo_premio FROM premi GROUP BY valore, tipo_premio ORDER BY MIN(id) ASC');
                                            $premi_ids = $stmt_p ? $stmt_p->fetchAll(PDO::FETCH_ASSOC) : [];
                                            $stmt_cnt = $pdo_premi_counts->prepare('
                                                SELECT COUNT(*)
                                                FROM `codici_premio` cp
                                                INNER JOIN `premi` p ON p.`id` = cp.`prize_id`
                                                WHERE p.`valore` = ? AND p.`tipo_premio` = ?
                                            ');
                                            foreach ($premi_ids as $idx => $prow) {
                                                $stmt_cnt->execute([(float)($prow['valore'] ?? 0), (string)($prow['tipo_premio'] ?? '')]);
                                                $premiCodiciCounts[$idx] = (int)$stmt_cnt->fetchColumn();
                                            }
                                        } catch (Exception $e) {
                                            $premiCodiciCounts = [];
                                        }
                                    }
                                    ?>
                                    <div class="form-group premi-tipi-section instantwin-dependent-section <?php echo $isInstantwinConfig ? '' : 'd-none'; ?>" data-instantwin-only="true">
                                        <div class="section-subtitle"><i class="fa fa-gift mr-2"></i>Tipi di premi <span class="text-muted ml-1" data-toggle="tooltip" title="Definisce i tipi di premio per l'Instant Win."><i class="fa fa-question-circle"></i></span></div>
                                        <small class="form-text text-muted mb-2 premi-tipi-standard-hint <?php echo ($isInstantwinConfig && !$perCediChecked) ? '' : 'd-none'; ?>">Configura i premi prima di importare i codici premio in Gestione Dati. L'ordine delle righe = <code>prize_id</code> in CSV (1ª riga → id 1, 2ª → id 2, …). Se torni da modalità Ce.Di., i totali sono raggruppati per importo.</small>
                                        <p class="small text-info mb-2 premi-tipi-cedi-hint <?php echo ($isInstantwinConfig && $perCediChecked) ? '' : 'd-none'; ?>">
                                            In modalità <strong>Per Cedi</strong> i premi si gestiscono nella <strong>matrice Ce.Di.</strong> sotto (non qui).
                                        </p>
                                        <div class="form-group mb-3" id="premi-ripartizione-premi">
                                            <label>
                                                Ripartizione premi
                                                <span class="text-muted ml-1" data-toggle="tooltip" title="Definisce come i premi Instant Win vengono ripartiti (globale, per punto vendita o per Cedi)."><i class="fa fa-question-circle"></i></span>
                                            </label>
                                            <select name="instantwin_prize_distribution" id="instantwin_prize_distribution" class="form-control">
                                                <option value="default" <?php echo $iwPrizeDistributionUi === 'default' ? 'selected' : ''; ?>>Standard (montepremi globale)</option>
                                                <option value="pdv" <?php echo $iwPrizeDistributionUi === 'pdv' ? 'selected' : ''; ?>>Per punto vendita (PDV)</option>
                                                <option value="cedi" <?php echo $iwPrizeDistributionUi === 'cedi' ? 'selected' : ''; ?>>Per Cedi (Ce.Di.)</option>
                                            </select>
                                            <p class="small text-muted mb-0 mt-2 iw-pdv-pers-hint <?php echo ($isInstantwinConfig && $perPdvChecked) ? '' : 'd-none'; ?>">
                                                Impostazioni IW e premi aggiuntivi per PDV: passo <strong>Instant Win</strong> nel percorso guidato.
                                            </p>
                                            <p class="small text-muted mb-0 mt-2 iw-cedi-pers-hint <?php echo ($isInstantwinConfig && $perCediChecked) ? '' : 'd-none'; ?>">
                                                Matrice premi per Ce.Di. e parametri .env: passo <strong>Instant Win</strong> nel percorso guidato.
                                            </p>
                                        </div>
                                        <?php
                                        require_once __DIR__ . '/../../../app/logic/instantwin_cedi/cedi_core.php';
                                        $pdo_premi_matrix = isset($connection) && $connection instanceof PDO ? $connection : (function_exists('conn_pdo') ? conn_pdo() : null);
                                        $cediMatrixConfig = is_array($content_current) ? $content_current : [];

                                        $cediDefsUiList = array_values(instantwin_cedi_definitions_configured($cediMatrixConfig));
                                        $cediPrizeDefsUiList = instantwin_cedi_prize_definitions_configured($cediMatrixConfig);

                                        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_config']) && $perCediChecked) {
                                            if (isset($_POST['cedi_definitions']) && is_array($_POST['cedi_definitions'])) {
                                                $tmpErr = [];
                                                $cediDefsUiList = instantwin_cedi_parse_definitions_post($_POST['cedi_definitions'], $tmpErr);
                                            }
                                            if (isset($_POST['cedi_prize_definitions']) && is_array($_POST['cedi_prize_definitions'])) {
                                                $tmpErr = [];
                                                $cediPrizeDefsUiList = instantwin_cedi_parse_prize_definitions_post($_POST['cedi_prize_definitions'], $tmpErr);
                                            }
                                            $cediValsForConfig = [];
                                            foreach ($cediPrizeDefsUiList as $defRow) {
                                                $cediValsForConfig[] = (float) $defRow['value'];
                                            }
                                            $cediMatrixConfig = array_merge($cediMatrixConfig, [
                                                'cedi_definitions' => $cediDefsUiList,
                                                'cedi_prize_values' => $cediValsForConfig,
                                                'cedi_prize_definitions' => $cediPrizeDefsUiList,
                                            ]);
                                        }

                                        if ($cediDefsUiList === [] && $pdo_premi_matrix instanceof PDO) {
                                            $cediDefsUiList = instantwin_cedi_definitions_from_pdv($pdo_premi_matrix);
                                        }
                                        if ($cediDefsUiList === []) {
                                            $cediDefsUiList = instantwin_cedi_default_definitions_ui();
                                        }
                                        if ($cediPrizeDefsUiList === []) {
                                            $cediPrizeDefsUiList = [['value' => '', 'tipo' => '']];
                                        }

                                        $premiMatrix = instantwin_cedi_empty_matrix($cediMatrixConfig);
                                        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_config']) && $perCediChecked && isset($_POST['premi_matrix']) && is_array($_POST['premi_matrix'])) {
                                            $matrixErrorsTmp = [];
                                            $parsed = instantwin_cedi_parse_matrix_post($_POST['premi_matrix'], $matrixErrorsTmp, $cediMatrixConfig);
                                            if ($parsed !== null) {
                                                $premiMatrix = $parsed;
                                            }
                                        } elseif ($pdo_premi_matrix instanceof PDO) {
                                            $premiMatrix = instantwin_cedi_load_matrix_from_db($pdo_premi_matrix, $cediMatrixConfig);
                                            $matrixSum = 0;
                                            foreach ($premiMatrix as $row) {
                                                $matrixSum += array_sum($row);
                                            }
                                            if ($matrixSum === 0 && !empty($content_current['premi_matrix']) && is_array($content_current['premi_matrix'])) {
                                                $premiMatrix = $content_current['premi_matrix'];
                                            }
                                        } elseif (!empty($content_current['premi_matrix']) && is_array($content_current['premi_matrix'])) {
                                            $premiMatrix = $content_current['premi_matrix'];
                                        }
                                        try {
                                            $premiMatrixWeeks = instantwin_cedi_contest_week_count($start_date_input, $end_date_input);
                                        } catch (Throwable $e) {
                                            $premiMatrixWeeks = 6;
                                        }
                                        $premiMatrixWeeksCustom = !empty($content_current['premi_matrix_weeks_custom']);
                                        $premiMatrixWeekPlan = null;
                                        $premiMatrixWeekPlanErrors = [];
                                        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_config']) && !empty($_POST['premi_matrix_weeks_custom'])) {
                                            $premiMatrixWeeksCustom = true;
                                            if (!isset($_POST['premi_matrix_weeks']) || !is_array($_POST['premi_matrix_weeks']) || $_POST['premi_matrix_weeks'] === []) {
                                                $premiMatrixWeekPlanErrors[] = 'Ripartizione settimanale personalizzata mancante: compilare il passo 4.';
                                            } else {
                                                $premiMatrixWeekPlan = instantwin_cedi_parse_week_plan_post($_POST['premi_matrix_weeks'], $premiMatrixWeeks, $premiMatrixWeekPlanErrors, $cediMatrixConfig);
                                                if ($premiMatrixWeekPlan !== null) {
                                                    instantwin_cedi_validate_week_plan_against_matrix(
                                                        $premiMatrixWeekPlan,
                                                        $premiMatrix,
                                                        $premiMatrixWeeks,
                                                        $premiMatrixWeekPlanErrors,
                                                        $cediMatrixConfig
                                                    );
                                                }
                                            }
                                        } elseif ($pdo_premi_matrix instanceof PDO) {
                                            $premiMatrixWeekPlan = instantwin_cedi_load_week_plan_from_db($pdo_premi_matrix, $premiMatrixWeeks);
                                        }
                                        if ($premiMatrixWeekPlan === null) {
                                            $premiMatrixWeekPlan = instantwin_cedi_build_week_plan_from_matrix($premiMatrix, $premiMatrixWeeks);
                                        }
                                        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_config'])) {
                                            $premiMatrixWeeksCustom = !empty($_POST['premi_matrix_weeks_custom']);
                                        }
                                        ?>
                                        <div id="premi-cedi-matrix-block" class="<?php echo ($isInstantwinConfig && $perCediChecked) ? '' : 'd-none'; ?>" data-cedi-matrix-active="<?php echo ($isInstantwinConfig && $perCediChecked) ? '1' : '0'; ?>">
                                            <?php include __DIR__ . '/../inc/premi_cedi_matrix_panel.php'; ?>
                                        </div>
                                        <div id="premi-tipi-container" class="<?php echo ($isInstantwinConfig && $perCediChecked) ? 'd-none' : ''; ?>">
                                            <?php foreach ($premiCurrent as $idx => $p): ?>
                                            <?php $pValoreRaw = trim((string)($p['valore'] ?? '')); $pTipo = trim((string)($p['tipo_premio'] ?? '')); $pQuantita = isset($p['quantita_totale']) ? (int)$p['quantita_totale'] : ''; $cpCount = (int)($premiCodiciCounts[$idx] ?? 0); ?>
                                            <div class="premi-tipo-row row align-items-end mb-2" data-index="<?php echo (int)$idx; ?>" data-codici-premio-count="<?php echo $cpCount; ?>">
                                                <div class="col-md-3"><label class="small">Valore (€)</label><input type="text" inputmode="decimal" name="premi[<?php echo (int)$idx; ?>][valore]" class="form-control form-control-sm js-format-number-it js-premi-valore" data-max="1000000" value="<?php echo manager_safe_html($pValoreRaw); ?>" placeholder="0 = senza importo" title="Vuoto = 0. Solo cifre, max 1.000.000"></div>
                                                <div class="col-md-3"><label class="small">Tipo premio</label><input type="text" name="premi[<?php echo (int)$idx; ?>][tipo_premio]" class="form-control form-control-sm js-premi-tipo" maxlength="255" value="<?php echo manager_safe_html($pTipo); ?>" placeholder="Prepagata" title="Min. 2 caratteri, max 255"></div>
                                                <div class="col-md-2"><label class="small">Quantità</label><input type="number" min="1" max="1000000" name="premi[<?php echo (int)$idx; ?>][quantita_totale]" class="form-control form-control-sm js-premi-quantita" value="<?php echo manager_safe_html($pQuantita !== '' ? (string)$pQuantita : ''); ?>" placeholder="200" title="Solo cifre, max 1.000.000"></div>
                                                <input type="hidden" name="premi[<?php echo (int)$idx; ?>][is_active]" value="1">
                                                <div class="col-md-2"><button type="button" class="btn btn-outline-danger btn-sm premi-remove-row" title="Rimuovi"><i class="fa fa-trash"></i></button></div>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <button type="button" id="premi-add-row" class="btn btn-outline-primary btn-sm mt-2"><i class="fa fa-plus mr-1"></i>Aggiungi premio</button>
                                        <?php if ($isInstantwinConfig): ?>
                                        <p class="small text-muted mt-2 mb-0">
                                            Parametri tecnici Instant Win (.env): passo <strong>Instant Win</strong> nel percorso guidato.
                                        </p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="configurazione-panel">
                                        <div class="configurazione-panel-header">
                                            <span class="configurazione-panel-icon"><i class="fa fa-link"></i></span>
                                            <div>
                                                <h6>Link e documenti</h6>
                                                <p>Carica i documenti ufficiali usati nel concorso.</p>
                                            </div>
                                        </div>
                                    <div class="form-row">
                                        <div class="form-group col-md-6">
                                            <label>Privacy PDF</label>
                                            <?php
                                                // PDF di default: se usiamo il default non mostrare la "poubelle" (UX).
                                                $privacyFallback = (string) ($content_defaults['privacy_link'] ?? '');
                                                $privacyCurrent = trim((string) ($content_current['privacy_link'] ?? ''));
                                                if ($privacyCurrent === '' && $privacyFallback !== '') {
                                                    $privacyCurrent = $privacyFallback;
                                                }
                                            ?>
                                            <input type="hidden" name="privacy_link" value="<?php echo manager_safe_html($privacyCurrent); ?>" data-fallback="<?php echo manager_safe_html($privacyFallback); ?>">
                                            <div class="custom-file">
                                                <input type="file" name="upload_privacy_pdf" id="upload_privacy_pdf" class="custom-file-input" accept="application/pdf">
                                                <label class="custom-file-label" for="upload_privacy_pdf">Scegli PDF...</label>
                                            </div>
                                            <small class="form-text text-muted mt-1">Attuale: <span id="current_privacy_pdf"><?php echo manager_safe_html($privacyCurrent); ?></span></small>
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label>Regolamento PDF</label>
                                            <?php
                                                $rulesFallback = (string) ($content_defaults['rules_link'] ?? '');
                                                $rulesCurrent = trim((string) ($content_current['rules_link'] ?? ''));
                                                if ($rulesCurrent === '' && $rulesFallback !== '') {
                                                    $rulesCurrent = $rulesFallback;
                                                }
                                            ?>
                                            <input type="hidden" name="rules_link" value="<?php echo manager_safe_html($rulesCurrent); ?>" data-fallback="<?php echo manager_safe_html($rulesFallback); ?>">
                                            <div class="custom-file">
                                                <input type="file" name="upload_rules_pdf" id="upload_rules_pdf" class="custom-file-input" accept="application/pdf">
                                                <label class="custom-file-label" for="upload_rules_pdf">Scegli PDF...</label>
                                            </div>
                                            <small class="form-text text-muted mt-1">Attuale: <span id="current_rules_pdf"><?php echo manager_safe_html($rulesCurrent); ?></span></small>
                                        </div>
                                    </div>
                                    </div>
                                    <?php include __DIR__ . '/step-cookie-consent.php'; ?>
                                    <div class="configurazione-panel mb-0">
                                        <div class="configurazione-panel-header">
                                            <span class="configurazione-panel-icon"><i class="fa fa-building"></i></span>
                                            <div>
                                                <h6>Soggetti e riferimenti</h6>
                                                <p>Dati di promotore e delegato visualizzati nelle informazioni pubbliche.</p>
                                            </div>
                                        </div>
                                    <div class="form-row">
                                        <div class="form-group col-md-6">
                                            <label>Promotore</label>
                                            <input type="text" name="promoter" class="form-control" value="<?php echo manager_safe_html($content_current['promoter'] ?? ''); ?>">
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label>Delegato</label>
                                            <input type="text" name="delegate" class="form-control" value="<?php echo manager_safe_html($content_current['delegate'] ?? ''); ?>">
                                        </div>
                                    </div>
                                    <div class="form-row">
                                        <div class="form-group col-md-6">
                                            <label>Link Promotore <span class="text-muted ml-1" data-toggle="tooltip" title="URL del sito web del promotore (opzionale). Se inserito, il nome del promotore diventerà un link cliccabile nel footer."><i class="fa fa-question-circle"></i></span></label>
                                            <input type="url" name="promoter_link" class="form-control" value="<?php echo manager_safe_html($content_current['promoter_link'] ?? ''); ?>" placeholder="https://example.com">
                                            <small class="form-text text-muted d-none">URL completo (es: https://www.example.com)</small>
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label>Link Delegato <span class="text-muted ml-1" data-toggle="tooltip" title="URL del sito web del delegato (opzionale). Se inserito, il nome del delegato diventerà un link cliccabile nel footer."><i class="fa fa-question-circle"></i></span></label>
                                            <input type="url" name="delegate_link" class="form-control" value="<?php echo manager_safe_html($content_current['delegate_link'] ?? ''); ?>" placeholder="https://example.com">
                                            <small class="form-text text-muted d-none">URL completo (es: https://www.example.com)</small>
                                        </div>
                                    </div>
                                    </div>
                                </div>
                            </div>                            <div class="card mt-3 mb-4 config-anchor config-step configurazione-section configurazione-email-section" data-step="1" id="section-email">
                                <div class="card-body">
                                    <div class="configurazione-hero configurazione-hero--email">
                                        <div>
                                            <div class="section-title configurazione-title"><i class="fa fa-envelope mr-2"></i>Email & SMTP</div>
                                            <p class="text-muted small mb-0">Configura mittente, assistenza e parametri SMTP usati dalle comunicazioni.</p>
                                        </div>
                                        <span class="configurazione-badge configurazione-badge--email"><i class="fa fa-paper-plane mr-1"></i> Comunicazioni</span>
                                    </div>
                                    <div class="configurazione-panel">
                                        <div class="configurazione-panel-header">
                                            <span class="configurazione-panel-icon"><i class="fa fa-life-ring"></i></span>
                                            <div>
                                                <h6>Assistenza</h6>
                                                <p>Indirizzo usato per ricevere le richieste inviate dagli utenti.</p>
                                            </div>
                                        </div>
                                    <div class="form-group">
                                        <label>Email assistenza <span class="text-muted ml-1" data-toggle="tooltip" title="Indirizzo che riceve le richieste di supporto dal form help. Opzionale."><i class="fa fa-question-circle"></i></span></label>
                                        <input type="email" name="helpdesk_email" class="form-control" placeholder="es. assistenza@dominio.it" value="<?php echo manager_safe_html($content_current['helpdesk_email'] ?? ($content_defaults['helpdesk_email'] ?? '')); ?>">
                                    </div>
                                    </div>
                                    <div class="configurazione-panel mb-0">
                                        <div class="configurazione-panel-header">
                                            <span class="configurazione-panel-icon"><i class="fa fa-paper-plane"></i></span>
                                            <div>
                                                <h6>Parametri SMTP</h6>
                                                <p>Configurazione tecnica per inviare le email del concorso. Le credenziali restano nel file <code>.env</code> (non in content.json).</p>
                                            </div>
                                        </div>
                                    <div id="formSmtpEnv">
                                    <div class="section-subtitle">SMTP</div>
                                    <p class="small text-muted mb-2">Usato da registrazione, assistenza e reinvio email dal manager. Salva con il pulsante qui sotto (non con il salvataggio generale del concorso).</p>
                                    <?php
                                        $smtpProfileLabels = is_array($smtpProfilesUi['labels'] ?? null) ? $smtpProfilesUi['labels'] : [];
                                        if ($smtpProfileLabels === []) {
                                            $smtpProfileLabels = ['general' => 'Generale', 'aruba' => 'Aruba', 'sendgrid' => 'SendGrid'];
                                        }
                                        $smtpActiveProfileUi = (string) ($smtp_active_profile ?? 'general');
                                    ?>
                                    <div class="form-row">
                                        <div class="form-group col-md-6">
                                            <label for="smtp_env_SMTP_PROFILE">
                                                Configurazione SMTP
                                                <span class="text-muted ml-1" data-toggle="tooltip" title="Profili pronti (Generale / Aruba / SendGrid). Cambiare profilo riempie i campi; diventa attivo solo dopo Salva SMTP. Tutti e 3 restano salvati nel file .env."><i class="fa fa-question-circle"></i></span>
                                            </label>
                                            <select class="form-control" id="smtp_env_SMTP_PROFILE" name="SMTP_PROFILE" autocomplete="off">
                                                <?php foreach ($smtpProfileLabels as $profileId => $profileLabel): ?>
                                                    <option value="<?php echo manager_safe_html((string) $profileId); ?>"<?php echo $smtpActiveProfileUi === (string) $profileId ? ' selected' : ''; ?>>
                                                        <?php echo manager_safe_html((string) $profileLabel); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <small class="form-text text-muted">Aruba: host <code>smtps.aruba.it</code>. SendGrid: host <code>smtp.sendgrid.net</code>, username tipico <code>apikey</code>.</small>
                                        </div>
                                    </div>
                                    <div class="form-row">
                                        <?php
                                        $smtpEnvValues = is_array($smtp_env_values ?? null) ? $smtp_env_values : [];
                                        $smtpEnvSet = is_array($smtp_env_set ?? null) ? $smtp_env_set : [];
                                        if (function_exists('smtp_env_render_fields') && function_exists('smtp_env_schema')) {
                                            smtp_env_render_fields(smtp_env_schema(), $smtpEnvValues, $smtpEnvSet);
                                        }
                                        ?>
                                    </div>
                                    <script type="application/json" id="smtp-profiles-data"><?php echo $smtp_profiles_json ?? '{}'; ?></script>
                                    <div class="form-row align-items-center mt-1 mb-2">
                                        <div class="form-group col-md-auto mb-2 mb-md-0">
                                            <button type="button" class="btn btn-primary" id="btn_smtp_env_save">
                                                <i class="fa fa-save mr-1"></i> Salva SMTP
                                            </button>
                                        </div>
                                        <div class="col-md">
                                            <div id="smtp_env_save_result" class="small" role="status" aria-live="polite"></div>
                                        </div>
                                    </div>
                                    <small class="form-text text-muted mb-3">Le variabili attive <code>SMTP_HOST</code>…<code>SMTP_FROM_NAME</code> e i profili <code>SMTP_GENERAL_*</code> / <code>SMTP_ARUBA_*</code> / <code>SMTP_SENDGRID_*</code> (+ <code>SMTP_PROFILE</code>) sono salvate solo nel file <code>.env</code>.</small>
                                    </div>
                                    </div>
                                    <div class="configurazione-panel mb-0 mt-3">
                                        <div class="configurazione-panel-header">
                                            <span class="configurazione-panel-icon"><i class="fa fa-mobile"></i></span>
                                            <div>
                                                <h6>SMS (MailUp)</h6>
                                                <p>Abilita gli SMS transazionali. Le credenziali restano nel file <code>.env</code> (modificabili qui sotto).</p>
                                            </div>
                                        </div>
                                        <div class="form-check mb-3">
                                            <input class="form-check-input" type="checkbox" id="sms_enabled" name="sms_enabled" value="1"<?php echo !empty($sms_enabled) ? ' checked' : ''; ?>>
                                            <label class="form-check-label" for="sms_enabled">
                                                <strong>SMS abilitato</strong>
                                                <span class="text-muted ml-1" data-toggle="tooltip" title="Attiva l’invio SMS del concorso. Le credenziali MailUp si configurano qui e vengono salvate solo nel file .env (non in content.json)."><i class="fa fa-question-circle"></i></span>
                                            </label>
                                        </div>
                                        <div class="form-group mb-3">
                                            <label for="sms_provider">
                                                Provider
                                                <span class="text-muted ml-1" data-toggle="tooltip" title="Provider SMS utilizzato. Al momento è supportato solo MailUp (API REST transazionale)."><i class="fa fa-question-circle"></i></span>
                                            </label>
                                            <select name="sms_provider" id="sms_provider" class="form-control" style="max-width: 240px;">
                                                <option value="mailup"<?php echo (($sms_provider ?? 'mailup') === 'mailup') ? ' selected' : ''; ?>>MailUp</option>
                                            </select>
                                        </div>

                                        <div id="sms_mailup_credentials_panel" class="<?php echo !empty($sms_enabled) ? '' : 'd-none'; ?>">
                                            <?php
                                            $smsEnvValues = is_array($sms_env_values ?? null) ? $sms_env_values : [];
                                            $smsEnvSet = is_array($sms_env_set ?? null) ? $sms_env_set : [];
                                            $smsSendFields = function_exists('mailup_sms_env_fields_by_group')
                                                ? mailup_sms_env_fields_by_group('send')
                                                : [];
                                            $smsSecretFields = function_exists('mailup_sms_env_fields_by_group')
                                                ? mailup_sms_env_fields_by_group('secret')
                                                : [];
                                            ?>
                                            <div id="formMailupSmsEnv">
                                                <div class="section-subtitle section-subtitle--sms-send mb-2">
                                                    1. Configurazione invio
                                                    <span class="text-muted ml-1" data-toggle="tooltip" title="Campi necessari per inviare SMS. Salvati nel file .env."><i class="fa fa-question-circle"></i></span>
                                                </div>
                                                <p class="small text-muted mb-2">Campi necessari per l’invio SMS. Se hai già il List Secret, compila solo questo blocco.</p>
                                                <div class="form-row">
                                                    <?php
                                                    if (function_exists('mailup_sms_env_render_fields')) {
                                                        mailup_sms_env_render_fields($smsSendFields, $smsEnvValues, $smsEnvSet);
                                                    }
                                                    ?>
                                                    <div class="form-group col-md-6">
                                                        <label for="sms_campaign_code">
                                                            Codice campagna SMS
                                                            <span class="text-muted ml-1" data-toggle="tooltip" title="Etichetta usata da MailUp per raggruppare le statistiche di invio (CampaignCode). Non compare nel testo del messaggio. Salvato nella configurazione del concorso."><i class="fa fa-question-circle"></i></span>
                                                        </label>
                                                        <input
                                                            type="text"
                                                            class="form-control"
                                                            id="sms_campaign_code"
                                                            name="sms_campaign_code"
                                                            value="<?php echo manager_safe_html($sms_campaign_code ?? ''); ?>"
                                                            placeholder="es. Sisa Concorso"
                                                            maxlength="100"
                                                            autocomplete="off"
                                                        >
                                                        <small class="form-text text-muted">Usato per tutti gli SMS del concorso (vincita, partecipazione, test). Se vuoto, viene usato un codice tecnico predefinito.</small>
                                                    </div>
                                                </div>
                                                <p class="small text-muted mb-3">
                                                    <i class="fa fa-info-circle mr-1"></i>
                                                    <strong>Sender SMS:</strong> il mittente alfanumerico (es. « Sisa ») deve essere approvato da MailUp e dall’operatore telefonico prima dell’avvio del concorso. Fino all’approvazione, il destinatario può vedere un numero invece del nome o l’SMS può non essere recapitato.
                                                </p>
                                                <div class="form-row align-items-center mt-1 mb-4">
                                                    <div class="form-group col-md-auto mb-2 mb-md-0">
                                                        <button type="button" class="btn btn-primary" id="btn_sms_env_save">
                                                            <i class="fa fa-save mr-1"></i> Salva credenziali
                                                        </button>
                                                    </div>
                                                    <div class="col-md">
                                                        <div id="sms_env_save_result" class="small" role="status" aria-live="polite"></div>
                                                    </div>
                                                </div>

                                                <div class="section-subtitle section-subtitle--sms-secret mb-2">
                                                    2. Genera List Secret
                                                    <span class="text-muted ml-1" data-toggle="tooltip" title="Serve solo se non hai ancora un List Secret. Usa Basic Auth MailUp."><i class="fa fa-question-circle"></i></span>
                                                </div>
                                                <p class="small text-muted mb-2">
                                                    Serve solo se non hai ancora un List Secret. Usa Basic Auth MailUp (Username + Password).
                                                    <a href="https://helpmailup.atlassian.net/wiki/spaces/mailupapi/pages/36342040/Transactional+SMS+using+APIs" target="_blank" rel="noopener noreferrer">Documentazione MailUp</a>
                                                </p>
                                                <div class="form-row">
                                                    <?php
                                                    if (function_exists('mailup_sms_env_render_fields')) {
                                                        mailup_sms_env_render_fields($smsSecretFields, $smsEnvValues, $smsEnvSet);
                                                    }
                                                    ?>
                                                </div>
                                                <div class="form-row align-items-center mt-1 mb-4">
                                                    <div class="form-group col-md-auto mb-2 mb-md-0">
                                                        <button type="button" class="btn btn-outline-secondary" id="btn_sms_ensure_secret">
                                                            <i class="fa fa-key mr-1"></i> Genera List Secret
                                                        </button>
                                                    </div>
                                                    <div class="col-md">
                                                        <div id="sms_ensure_secret_result" class="small" role="status" aria-live="polite"></div>
                                                    </div>
                                                </div>

                                                <div class="section-subtitle section-subtitle--sms-test mb-2">
                                                    3. Test
                                                    <span class="text-muted ml-1" data-toggle="tooltip" title="Invia un SMS di prova con le credenziali salvate."><i class="fa fa-question-circle"></i></span>
                                                </div>
                                                <div class="form-row align-items-end">
                                                    <div class="form-group col-md-5 mb-2 mb-md-0">
                                                        <label for="sms_test_recipient">
                                                            Destinatario test (opzionale)
                                                            <span class="text-muted ml-1" data-toggle="tooltip" title="Numero per il test. Puoi scrivere solo le cifre (es. 3331234567): viene aggiunto automaticamente +39. Oppure inserisci già il formato internazionale (+3933…)."><i class="fa fa-question-circle"></i></span>
                                                        </label>
                                                        <input type="text" id="sms_test_recipient" name="MAILUP_SMS_TEST_RECIPIENT" class="form-control" placeholder="es. 333… oppure +3933…" value="<?php echo manager_safe_html($sms_env_values['MAILUP_SMS_TEST_RECIPIENT'] ?? ''); ?>" autocomplete="off">
                                                    </div>
                                                    <div class="form-group col-md-4 mb-0">
                                                        <button type="button" class="btn btn-outline-primary" id="btn_sms_test_send">
                                                            <i class="fa fa-paper-plane mr-1"></i> Invia SMS di test
                                                        </button>
                                                    </div>
                                                </div>
                                                <div id="sms_test_result" class="small mt-2" role="status" aria-live="polite"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
