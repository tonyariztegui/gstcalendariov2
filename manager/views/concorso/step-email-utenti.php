<?php
defined('EMAIL_STEP_CLASS_HIDDEN') || define('EMAIL_STEP_CLASS_HIDDEN', ' d-none');
defined('EMAIL_STEP_ATTR_CHECKED') || define('EMAIL_STEP_ATTR_CHECKED', ' checked');
?>
<div class="card mt-3 mb-4 config-anchor config-step configurazione-section email-ui-section" data-step="8" id="section-email-templates">
                                <div class="card-body">
                                    <div class="configurazione-hero email-ui-heading">
                                        <div>
                                            <div class="section-title configurazione-title"><i class="fa fa-envelope mr-2"></i>Email utenti</div>
                                            <p class="text-muted small mb-0 email-ui-heading-note">Configura invio, contenuto HTML e anteprima delle email automatiche.</p>
                                        </div>
                                        <span class="configurazione-badge email-ui-status-badge"><i class="fa fa-paper-plane mr-1"></i> Email di invio</span>
                                    </div>
                                    <div class="alert alert-warning email-flow-warning mb-3<?php echo !empty($instantwin_prize_flow_warning) ? '' : EMAIL_STEP_CLASS_HIDDEN; ?>" id="instantwin_prize_flow_warning" role="alert">
                                        <i class="fa fa-exclamation-triangle mr-1"></i>
                                        Configurazione incoerente rilevata: senza email di attesa e senza invio automatico, l'email premio verrebbe inviata senza codice. Seleziona una modalità di gestione vincita e salva.
                                    </div>
                                    <div class="section-card p-3 mb-3 email-prize-flow-panel<?php echo ($contestMode === 'instantwin') ? '' : EMAIL_STEP_CLASS_HIDDEN; ?>" id="instantwin_prize_flow_panel">
                                        <div class="section-subtitle mb-2">Gestione vincita</div>
                                        <p class="small text-muted mb-2" id="instantwin_prize_flow_status_banner">
                                            <?php if ($auto_prize_can_show): ?>
                                                <?php if ($instantwin_prize_flow === 'auto_immediate'): ?>
                                                    <span class="badge badge-success mr-1">Attivo</span> Codice inviato subito dopo la vincita (email premio con codice).
                                                <?php else: ?>
                                                    <span class="badge badge-info mr-1">Attivo</span> Conferma manager: email di attesa senza codice, codice dopo la conferma.
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="badge badge-secondary mr-1">Standard</span> Conferma manager disponibile in Partecipazioni.
                                            <?php endif; ?>
                                        </p>
                                        <div id="instantwin_prize_flow_radios" class="<?php echo $auto_prize_can_show ? '' : EMAIL_STEP_CLASS_HIDDEN; ?>">
                                            <p class="small text-muted mb-3">Scegli se inviare subito il codice premio o attendere la conferma del manager.</p>
                                            <div class="custom-control custom-radio mb-2">
                                                <input type="radio" id="instantwin_prize_flow_auto" name="instantwin_prize_flow" class="custom-control-input instantwin-prize-flow-radio" value="auto_immediate"<?php echo $instantwin_prize_flow === 'auto_immediate' ? EMAIL_STEP_ATTR_CHECKED : ''; ?>>
                                                <label class="custom-control-label" for="instantwin_prize_flow_auto">
                                                    <strong>Invio automatico alla vincita</strong>
                                                    <span class="d-block small text-muted font-weight-normal">Lo scontrino caricato è sufficiente: il codice viene assegnato e inviato subito nell'email premio.</span>
                                                </label>
                                            </div>
                                            <div class="custom-control custom-radio mb-0">
                                                <input type="radio" id="instantwin_prize_flow_manager" name="instantwin_prize_flow" class="custom-control-input instantwin-prize-flow-radio" value="manager_confirm"<?php echo $instantwin_prize_flow === 'manager_confirm' ? EMAIL_STEP_ATTR_CHECKED : ''; ?>>
                                                <label class="custom-control-label" for="instantwin_prize_flow_manager">
                                                    <strong>Conferma manager</strong>
                                                    <span class="d-block small text-muted font-weight-normal">Email « vincita in attesa » senza codice; il manager verifica e conferma prima dell'invio del codice.</span>
                                                </label>
                                            </div>
                                            <input type="hidden" name="instantwin_auto_prize_with_receipt" id="instantwin_auto_prize_with_receipt_hidden" value="<?php echo $auto_prize_with_receipt ? '1' : '0'; ?>">
                                            <p class="small text-muted mt-3 mb-0 email-flow-auto-note<?php echo $instantwin_prize_flow === 'auto_immediate' ? '' : EMAIL_STEP_CLASS_HIDDEN; ?>" id="email_flow_auto_note">
                                                <i class="fa fa-info-circle mr-1"></i> Conferma e annullamento vincita non sono disponibili in questo flusso.
                                            </p>
                                        </div>
                                    </div>
                                    <?php
                                        // Placeholder: stesso "box" di waiting-containers-footer, ma senza bottone.
                                        $email_placeholders_hint = '<div class="waiting-containers-footer email-placeholders-footer">'
                                            . '<div class="waiting-placeholders text-muted small">'
                                            . '<div>Placeholder disponibili: {{CONTEST_NAME}}, {{USER_NAME}}, {{START_DATE}}, {{END_DATE}}, {{MIN_SPEND}}, {{PRIZE_AMOUNT}}, {{PRIZE_WIN_AMOUNT}}, {{HELP_URL}}, {{REGOLAMENTO_LINK}}, {{VOUCHER_CODE}}, {{VOUCHER_CODE_BLOCK}}, {{PRIZE_IMAGE_BLOCK}}, {{PRIZE_TYPE}}, {{PDV}}</div>'
                                            . '</div>'
                                            . '</div>';
                                        $smsMaxSeptets = (int) GST_SMS_TEMPLATE_MAX_SEPTETS;
                                        $render_sms_template_field = static function (string $fieldId, string $value) use ($smsMaxSeptets): void {
                                            ?>
                                        <div class="form-group mb-3">
                                            <label for="<?php echo manager_safe_html($fieldId); ?>">Messaggio SMS</label>
                                            <textarea name="<?php echo manager_safe_html($fieldId); ?>" id="<?php echo manager_safe_html($fieldId); ?>" class="form-control sms-template-input" rows="4" spellcheck="true"><?php echo manager_safe_html($value); ?></textarea>
                                            <div class="sms-template-meta d-flex justify-content-between align-items-start flex-wrap mt-1">
                                                <small class="form-text text-muted mb-0">Placeholder: {{nome}}, {{codice}}, {{contest}}</small>
                                                <small class="sms-template-counter text-muted mb-0" id="<?php echo manager_safe_html($fieldId); ?>_counter">0/<?php echo $smsMaxSeptets; ?> GSM · 1 credito SMS</small>
                                            </div>
                                        </div>
                                            <?php
                                        };
                                    ?>
                                    <p class="small text-muted sms-template-global-hint mb-3">Usa solo testo semplice su una riga, massimo <?php echo $smsMaxSeptets; ?> unità GSM (1 credito SMS). I caratteri ^ { } \ [ ] ~ | € contano doppio. Non sono ammessi a capo, elenchi puntati o simboli non supportati.</p>
                                    <div class="configurazione-panel email-template-card<?php echo $contestMode === 'estrazione' ? '' : EMAIL_STEP_CLASS_HIDDEN; ?>" data-email-type="partecipazione" data-email-modes="estrazione">
                                        <div class="section-subtitle mb-2">Conferma partecipazione (estrazione)</div>
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" id="email_sending_partecipazione" name="email_sending_partecipazione" value="1"<?php echo $email_sending_partecipazione ? EMAIL_STEP_ATTR_CHECKED : ''; ?>>
                                            <label class="form-check-label" for="email_sending_partecipazione">Invia questa email</label>
                                        </div>
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" id="sms_sending_partecipazione" name="sms_sending_partecipazione" value="1"<?php echo !empty($sms_sending_partecipazione) ? EMAIL_STEP_ATTR_CHECKED : ''; ?>>
                                            <label class="form-check-label" for="sms_sending_partecipazione">Invia SMS</label>
                                        </div>
                                        <?php $render_sms_template_field('sms_template_partecipazione', (string) ($sms_template_partecipazione ?? '')); ?>
                                        <div class="row">
                                            <div class="col-lg-7">
                                                <div class="form-group">
                                                    <label>Oggetto</label>
                                                    <input type="text" name="email_partecipazione_subject" class="form-control" value="<?php echo manager_safe_html($email_partecipazione_subject); ?>">
                                                </div>
                                                <div class="form-group">
                                                    <label>Contenuto email (HTML)</label>
                                                    <?php
                                                        $email_partecipazione_body_textarea = $email_partecipazione_body;
                                                        if ($email_partecipazione_body_textarea !== '') {
                                                            $email_partecipazione_body_textarea = preg_replace('~</textarea>~i', '&lt;/textarea&gt;', $email_partecipazione_body_textarea);
                                                        }
                                                    ?>
                                                    <textarea name="email_partecipazione_body" class="form-control email-template-editor" rows="8"><?php echo $email_partecipazione_body_textarea; ?></textarea>
                                                    <?php echo $email_placeholders_hint; ?>
                                                </div>
                                            </div>
                                            <div class="col-lg-5">
                                                <div class="email-preview-frame">
                                                    <iframe class="email-preview-iframe" title="Preview email partecipazione"></iframe>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="configurazione-panel email-template-card<?php echo $contestMode === 'instantwin' ? '' : EMAIL_STEP_CLASS_HIDDEN; ?>" data-email-type="instantwin_confirm" data-email-modes="instantwin">
                                        <div class="section-subtitle mb-2">Instant Win - Vincita (in attesa di conferma)</div>
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" id="email_sending_instantwin_confirm" name="email_sending_instantwin_confirm" value="1"<?php echo $email_sending_instantwin_confirm ? EMAIL_STEP_ATTR_CHECKED : ''; ?>>
                                            <label class="form-check-label" for="email_sending_instantwin_confirm">Invia questa email</label>
                                        </div>
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" id="sms_sending_instantwin_confirm" name="sms_sending_instantwin_confirm" value="1"<?php echo !empty($sms_sending_instantwin_confirm) ? EMAIL_STEP_ATTR_CHECKED : ''; ?>>
                                            <label class="form-check-label" for="sms_sending_instantwin_confirm">Invia SMS</label>
                                        </div>
                                        <?php $render_sms_template_field('sms_template_instantwin_confirm', (string) ($sms_template_instantwin_confirm ?? '')); ?>
                                        <p class="small text-muted mb-3 email-flow-confirm-hint">Usata con « Gestione vincita » in modalità conferma manager. Se disattivi questa email con scontrino attivo, il sistema passa all'invio automatico del codice.</p>
                                        <div class="row">
                                            <div class="col-lg-7">
                                                <div class="form-group">
                                                    <label>Oggetto</label>
                                                    <input type="text" name="email_instantwin_confirm_subject" class="form-control" value="<?php echo manager_safe_html($email_instantwin_confirm_subject); ?>">
                                                </div>
                                                <div class="form-group">
                                                    <label>Contenuto email (HTML)</label>
                                                    <?php
                                                        $email_instantwin_confirm_body_textarea = $email_instantwin_confirm_body;
                                                        if ($email_instantwin_confirm_body_textarea !== '') {
                                                            $email_instantwin_confirm_body_textarea = preg_replace('~</textarea>~i', '&lt;/textarea&gt;', $email_instantwin_confirm_body_textarea);
                                                        }
                                                    ?>
                                                    <textarea name="email_instantwin_confirm_body" class="form-control email-template-editor" rows="8"><?php echo $email_instantwin_confirm_body_textarea; ?></textarea>
                                                    <?php echo $email_placeholders_hint; ?>
                                                </div>
                                            </div>
                                            <div class="col-lg-5">
                                                <div class="email-preview-frame">
                                                    <iframe class="email-preview-iframe" title="Preview email instant win (vincita da confermare)"></iframe>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="configurazione-panel email-template-card<?php echo $contestMode === 'instantwin' ? '' : EMAIL_STEP_CLASS_HIDDEN; ?>" data-email-type="instantwin_loss" data-email-modes="instantwin">
                                        <div class="section-subtitle mb-2">Instant Win - Perdita</div>
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" id="email_sending_instantwin_loss" name="email_sending_instantwin_loss" value="1"<?php echo $email_sending_instantwin_loss ? EMAIL_STEP_ATTR_CHECKED : ''; ?>>
                                            <label class="form-check-label" for="email_sending_instantwin_loss">Invia questa email</label>
                                        </div>
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" id="sms_sending_instantwin_loss" name="sms_sending_instantwin_loss" value="1"<?php echo !empty($sms_sending_instantwin_loss) ? EMAIL_STEP_ATTR_CHECKED : ''; ?>>
                                            <label class="form-check-label" for="sms_sending_instantwin_loss">Invia SMS</label>
                                        </div>
                                        <?php $render_sms_template_field('sms_template_instantwin_loss', (string) ($sms_template_instantwin_loss ?? '')); ?>
                                        <div class="row">
                                            <div class="col-lg-7">
                                                <div class="form-group">
                                                    <label>Oggetto</label>
                                                    <input type="text" name="email_instantwin_loss_subject" class="form-control" value="<?php echo manager_safe_html($email_instantwin_loss_subject); ?>">
                                                </div>
                                                <div class="form-group">
                                                    <label>Contenuto email (HTML)</label>
                                                    <?php
                                                        $email_instantwin_loss_body_textarea = $email_instantwin_loss_body;
                                                        if ($email_instantwin_loss_body_textarea !== '') {
                                                            $email_instantwin_loss_body_textarea = preg_replace('~</textarea>~i', '&lt;/textarea&gt;', $email_instantwin_loss_body_textarea);
                                                        }
                                                    ?>
                                                    <textarea name="email_instantwin_loss_body" class="form-control email-template-editor" rows="8"><?php echo $email_instantwin_loss_body_textarea; ?></textarea>
                                                    <?php echo $email_placeholders_hint; ?>
                                                </div>
                                            </div>
                                            <div class="col-lg-5">
                                                <div class="email-preview-frame">
                                                    <iframe class="email-preview-iframe" title="Preview email instant win perdita"></iframe>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="configurazione-panel email-template-card<?php echo $contestMode === 'instantwin' ? '' : EMAIL_STEP_CLASS_HIDDEN; ?>" data-email-type="instantwin_cancel" data-email-modes="instantwin">
                                        <div class="section-subtitle mb-2">Instant Win - Annullamento</div>
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" id="email_sending_instantwin_cancel" name="email_sending_instantwin_cancel" value="1"<?php echo $email_sending_instantwin_cancel ? EMAIL_STEP_ATTR_CHECKED : ''; ?>>
                                            <label class="form-check-label" for="email_sending_instantwin_cancel">Invia questa email</label>
                                        </div>
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" id="sms_sending_instantwin_cancel" name="sms_sending_instantwin_cancel" value="1"<?php echo !empty($sms_sending_instantwin_cancel) ? EMAIL_STEP_ATTR_CHECKED : ''; ?>>
                                            <label class="form-check-label" for="sms_sending_instantwin_cancel">Invia SMS</label>
                                        </div>
                                        <?php $render_sms_template_field('sms_template_instantwin_cancel', (string) ($sms_template_instantwin_cancel ?? '')); ?>
                                        <p class="small text-muted mb-3 email-flow-cancel-hint">Usata con « Gestione vincita » in modalità conferma manager per notificare l'annullamento di una vincita.</p>
                                        <div class="row">
                                            <div class="col-lg-7">
                                                <div class="form-group">
                                                    <label>Oggetto</label>
                                                    <input type="text" name="email_instantwin_cancel_subject" class="form-control" value="<?php echo manager_safe_html($email_instantwin_cancel_subject); ?>">
                                                </div>
                                                <div class="form-group">
                                                    <label>Contenuto email (HTML)</label>
                                                    <?php
                                                        $email_instantwin_cancel_body_textarea = $email_instantwin_cancel_body;
                                                        if ($email_instantwin_cancel_body_textarea !== '') {
                                                            $email_instantwin_cancel_body_textarea = preg_replace('~</textarea>~i', '&lt;/textarea&gt;', $email_instantwin_cancel_body_textarea);
                                                        }
                                                    ?>
                                                    <textarea name="email_instantwin_cancel_body" class="form-control email-template-editor" rows="8"><?php echo $email_instantwin_cancel_body_textarea; ?></textarea>
                                                    <?php echo $email_placeholders_hint; ?>
                                                </div>
                                            </div>
                                            <div class="col-lg-5">
                                                <div class="email-preview-frame">
                                                    <iframe class="email-preview-iframe" title="Preview email instant win annullamento"></iframe>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="configurazione-panel email-template-card<?php echo $contestMode === 'instantwin' ? '' : EMAIL_STEP_CLASS_HIDDEN; ?>" data-email-type="instantwin_prize" data-email-modes="instantwin">
                                        <div class="section-subtitle mb-2">Instant Win - Premio</div>
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" id="email_sending_instantwin_prize" name="email_sending_instantwin_prize" value="1"<?php echo $email_sending_instantwin_prize ? EMAIL_STEP_ATTR_CHECKED : ''; ?>>
                                            <label class="form-check-label" for="email_sending_instantwin_prize">Invia questa email</label>
                                        </div>
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" id="sms_sending_instantwin_prize" name="sms_sending_instantwin_prize" value="1"<?php echo !empty($sms_sending_instantwin_prize) ? EMAIL_STEP_ATTR_CHECKED : ''; ?>>
                                            <label class="form-check-label" for="sms_sending_instantwin_prize">Invia SMS</label>
                                        </div>
                                        <?php $render_sms_template_field('sms_template_instantwin_prize', (string) ($sms_template_instantwin_prize ?? '')); ?>
                                        <div class="prize-email-template-panel section-card p-3 mb-0">
                                            <div class="accordion iw-extra-accordion prize-email-settings-accordion mb-3" id="prize-email-settings-accordion">
                                            <div class="card border mb-2 iw-extra-prize-card">
                                                <div class="card-header py-2 px-3 iw-extra-prize-header" id="prize-settings-heading-notify">
                                                    <h6 class="mb-0">
                                                        <button class="btn btn-link btn-block text-left p-0 iw-extra-prize-toggle collapsed" type="button" data-toggle="collapse" data-target="#prize-settings-collapse-notify" aria-expanded="false" aria-controls="prize-settings-collapse-notify">
                                                            <span>Allegati e notifiche interne</span>
                                                            <?php if (!empty($email_bcc_instantwin_prize_enabled)): ?><span class="badge badge-info ml-1">CCN</span><?php endif; ?>
                                                            <i class="fa fa-chevron-down iw-extra-chevron"></i>
                                                        </button>
                                                    </h6>
                                                </div>
                                                <div id="prize-settings-collapse-notify" class="collapse" aria-labelledby="prize-settings-heading-notify" data-parent="#prize-email-settings-accordion">
                                                    <div class="card-body pt-2 pb-3 iw-extra-prize-body">
                                                        <div class="form-check mb-3">
                                                            <input class="form-check-input" type="checkbox" id="email_instantwin_prize_attach_pdf" name="email_instantwin_prize_attach_pdf" value="1"<?php echo !empty($content_current['email_instantwin_prize_attach_pdf'] ?? ($content_defaults['email_instantwin_prize_attach_pdf'] ?? false)) ? EMAIL_STEP_ATTR_CHECKED : ''; ?>>
                                                            <label class="form-check-label" for="email_instantwin_prize_attach_pdf">Allega PDF voucher se disponibile</label>
                                                            <small class="form-text text-muted">Usa il file indicato in <code>codici_premio.pdf_path</code>.</small>
                                                        </div>
                                                        <div class="email-bcc-compact-panel border rounded p-3">
                                                            <div class="form-check mb-2">
                                                                <input class="form-check-input" type="checkbox" id="email_bcc_instantwin_prize_enabled" name="email_bcc_instantwin_prize_enabled" value="1"<?php echo !empty($email_bcc_instantwin_prize_enabled) ? EMAIL_STEP_ATTR_CHECKED : ''; ?>>
                                                                <label class="form-check-label" for="email_bcc_instantwin_prize_enabled"><strong>CCN dinamiche</strong> (email interne di audit)</label>
                                                            </div>
                                                            <div class="email-bcc-details-panel<?php echo !empty($email_bcc_instantwin_prize_enabled) ? '' : EMAIL_STEP_CLASS_HIDDEN; ?>" id="email_bcc_details_panel">
                                                                <div class="form-check mb-2">
                                                                    <input class="form-check-input" type="checkbox" id="email_bcc_instantwin_prize_use_pdv_email" name="email_bcc_instantwin_prize_use_pdv_email" value="1"<?php echo !empty($email_bcc_instantwin_prize_use_pdv_email) ? EMAIL_STEP_ATTR_CHECKED : ''; ?>>
                                                                    <label class="form-check-label small" for="email_bcc_instantwin_prize_use_pdv_email">Includi email punto vendita</label>
                                                                </div>
                                                                <div class="form-group mb-2">
                                                                    <label class="small mb-1" for="email_bcc_instantwin_prize_global">CCN globali (CSV)</label>
                                                                    <input type="text" class="form-control form-control-sm" id="email_bcc_instantwin_prize_global" name="email_bcc_instantwin_prize_global" value="<?php echo manager_safe_html($email_bcc_instantwin_prize_global); ?>" placeholder="ops@example.com, audit@example.com">
                                                                </div>
                                                                <div class="form-group mb-0">
                                                                    <label class="small mb-1" for="email_bcc_instantwin_prize_by_insegna">Responsabili per insegna</label>
                                                                    <textarea class="form-control form-control-sm" id="email_bcc_instantwin_prize_by_insegna" name="email_bcc_instantwin_prize_by_insegna" rows="3" placeholder="Tato=resp1@azienda.it&#10;Dls=ops@azienda.it"><?php echo manager_safe_html($email_bcc_instantwin_prize_by_insegna_text); ?></textarea>
                                                                    <small class="form-text text-muted">Una riga per insegna, formato <code>Insegna=email1, email2</code>.</small>
                                                                    <?php if (!empty($email_bcc_insegna_suggestions)): ?>
                                                                        <div class="mt-2">
                                                                            <?php foreach ($email_bcc_insegna_suggestions as $insegnaSuggestion): ?>
                                                                                <span class="badge badge-light border mr-1 mb-1"><?php echo manager_safe_html($insegnaSuggestion); ?></span>
                                                                            <?php endforeach; ?>
                                                                        </div>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="card border mb-2 iw-extra-prize-card">
                                                <div class="card-header py-2 px-3 iw-extra-prize-header" id="prize-settings-heading-voucher">
                                                    <h6 class="mb-0">
                                                        <button class="btn btn-link btn-block text-left p-0 iw-extra-prize-toggle collapsed" type="button" data-toggle="collapse" data-target="#prize-settings-collapse-voucher" aria-expanded="false" aria-controls="prize-settings-collapse-voucher">
                                                            <span>Codice voucher</span>
                                                            <i class="fa fa-chevron-down iw-extra-chevron"></i>
                                                        </button>
                                                    </h6>
                                                </div>
                                                <div id="prize-settings-collapse-voucher" class="collapse" aria-labelledby="prize-settings-heading-voucher" data-parent="#prize-email-settings-accordion">
                                                    <div class="card-body pt-2 pb-3 iw-extra-prize-body">
                                                        <div class="form-group mb-3">
                                                            <label for="email_voucher_code_display">Visualizzazione codice premio</label>
                                                            <select class="form-control" id="email_voucher_code_display" name="email_voucher_code_display">
                                                                <option value="text"<?php echo $email_voucher_code_display === 'text' ? ' selected' : ''; ?>>Codice testuale</option>
                                                                <option value="barcode"<?php echo $email_voucher_code_display === 'barcode' ? ' selected' : ''; ?>>Barcode EAN-13</option>
                                                                <option value="both"<?php echo $email_voucher_code_display === 'both' ? ' selected' : ''; ?>>Barcode EAN-13 + codice testuale</option>
                                                            </select>
                                                            <small class="form-text text-muted">Agisce su <code>{{VOUCHER_CODE_BLOCK}}</code>. Il barcode viene mostrato solo per codici EAN-13 validi.</small>
                                                        </div>
                                                        <p class="section-subsubtitle mb-0">Colori blocco voucher</p>
                                                        <small class="form-text text-muted d-block mb-2">Bordo, sfondo e testo del codice nell'email premio.</small>
                                                        <div class="row">
                                                            <div class="col-md-4">
                                                                <label class="small" for="email_voucher_block_border_color_text">Bordo</label>
                                                                <div class="d-flex align-items-center color-row">
                                                                    <input type="color" class="form-control color-input color-swatch" data-target="email_voucher_block_border_color_text" value="<?php echo manager_safe_html($email_voucher_block_border_color); ?>">
                                                                    <input type="text" name="email_voucher_block_border_color" id="email_voucher_block_border_color_text" class="form-control color-code-input" value="<?php echo manager_safe_html($email_voucher_block_border_color); ?>" placeholder="#198754">
                                                                </div>
                                                            </div>
                                                            <div class="col-md-4">
                                                                <label class="small" for="email_voucher_block_bg_color_text">Sfondo</label>
                                                                <div class="d-flex align-items-center color-row">
                                                                    <input type="color" class="form-control color-input color-swatch" data-target="email_voucher_block_bg_color_text" value="<?php echo manager_safe_html($email_voucher_block_bg_color); ?>">
                                                                    <input type="text" name="email_voucher_block_bg_color" id="email_voucher_block_bg_color_text" class="form-control color-code-input" value="<?php echo manager_safe_html($email_voucher_block_bg_color); ?>" placeholder="#f0f0f0">
                                                                </div>
                                                            </div>
                                                            <div class="col-md-4">
                                                                <label class="small" for="email_voucher_block_text_color_text">Testo codice</label>
                                                                <div class="d-flex align-items-center color-row">
                                                                    <input type="color" class="form-control color-input color-swatch" data-target="email_voucher_block_text_color_text" value="<?php echo manager_safe_html($email_voucher_block_text_color); ?>">
                                                                    <input type="text" name="email_voucher_block_text_color" id="email_voucher_block_text_color_text" class="form-control color-code-input" value="<?php echo manager_safe_html($email_voucher_block_text_color); ?>" placeholder="#198754">
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="card border mb-0 iw-extra-prize-card prize-email-images-panel">
                                                <div class="card-header py-2 px-3 iw-extra-prize-header" id="prize-settings-heading-images">
                                                    <h6 class="mb-0">
                                                        <button class="btn btn-link btn-block text-left p-0 iw-extra-prize-toggle collapsed" type="button" data-toggle="collapse" data-target="#prize-settings-collapse-images" aria-expanded="false" aria-controls="prize-settings-collapse-images">
                                                            <span>Immagini premio (template email)</span>
                                                            <i class="fa fa-chevron-down iw-extra-chevron"></i>
                                                        </button>
                                                    </h6>
                                                </div>
                                                <div id="prize-settings-collapse-images" class="collapse" aria-labelledby="prize-settings-heading-images" data-parent="#prize-email-settings-accordion">
                                                    <div class="card-body pt-2 pb-3 iw-extra-prize-body">
                                                        <p class="small text-muted mb-3">Immagine base per tipo di premio (consigliato <strong>600×400 px</strong>). Usa <code>{{PRIZE_IMAGE_BLOCK}}</code> nel template.</p>
                                            <?php if (empty($premi_for_email_images)): ?>
                                                <p class="small text-warning mb-0">Nessun premio in database. Configura prima i <strong>Tipi di premi</strong> nel passo Dati concorso.</p>
                                            <?php else: ?>
                                                <div class="accordion iw-extra-accordion" id="prize-email-templates-accordion">
                                                <?php foreach ($premi_for_email_images as $peiIdx => $peiRow): ?>
                                                    <?php
                                                        $peiValore = (float) ($peiRow['valore'] ?? 0);
                                                        $peiValoreLabel = number_format($peiValore, 2, ',', '.');
                                                        $peiTipo = trim((string) ($peiRow['tipo_premio'] ?? 'Premio'));
                                                        $peiKey = prize_email_template_key($peiValore, $peiTipo);
                                                        $peiTpl = prize_email_normalize_template_entry($prize_email_templates_current[$peiKey] ?? []);
                                                        $peiOverlay = $peiTpl['overlay'];
                                                        $peiEmail = $peiTpl['email'];
                                                        $peiCustomBodyEnabled = !empty($peiEmail['custom_body_enabled']);
                                                        $peiEmailBody = (string) ($peiEmail['body'] ?? '');
                                                        $peiVoucherDisplay = (string) ($peiEmail['voucher_code_display'] ?? 'inherit');
                                                        $peiTemplatePath = $peiTpl['template_path'];
                                                        $peiHasImage = $peiTemplatePath !== '';
                                                        if ($peiTemplatePath !== '') {
                                                            $peiPreviewUrl = !empty($peiOverlay['enabled'])
                                                                ? prize_overlay_preview_manager_url($peiTemplatePath, $peiOverlay)
                                                                : prize_upload_manager_preview_url($peiTemplatePath);
                                                        } else {
                                                            $peiPreviewUrl = '';
                                                        }
                                                        $peiCollapseId = 'prize-email-collapse-' . (int) $peiIdx;
                                                        $peiHeadingId = 'prize-email-heading-' . (int) $peiIdx;
                                                        $peiExpandFirst = false;
                                                    ?>
                                                    <div class="card border mb-2 iw-extra-prize-card prize-email-template-row">
                                                        <div class="card-header py-2 px-3 iw-extra-prize-header" id="<?php echo manager_safe_html($peiHeadingId); ?>">
                                                            <h6 class="mb-0">
                                                                <button class="btn btn-link btn-block text-left p-0 iw-extra-prize-toggle<?php echo $peiExpandFirst ? '' : ' collapsed'; ?>" type="button"
                                                                    data-toggle="collapse" data-target="#<?php echo manager_safe_html($peiCollapseId); ?>"
                                                                    aria-expanded="<?php echo $peiExpandFirst ? 'true' : 'false'; ?>"
                                                                    aria-controls="<?php echo manager_safe_html($peiCollapseId); ?>">
                                                                    <span><?php echo manager_safe_html($peiTipo); ?></span>
                                                                    <span class="text-muted font-weight-normal"> · &euro;<?php echo manager_safe_html($peiValoreLabel); ?></span>
                                                                    <?php if ($peiHasImage): ?>
                                                                        <span class="badge badge-success ml-1">immagine</span>
                                                                    <?php else: ?>
                                                                        <span class="badge badge-light border ml-1">vuoto</span>
                                                                    <?php endif; ?>
                                                                    <?php if (!empty($peiOverlay['enabled'])): ?>
                                                                        <span class="badge badge-info ml-1">codice GD</span>
                                                                    <?php endif; ?>
                                                                    <?php if ($peiCustomBodyEnabled && $peiEmailBody !== ''): ?>
                                                                        <span class="badge badge-primary ml-1">email custom</span>
                                                                    <?php endif; ?>
                                                                    <?php if ($peiVoucherDisplay !== '' && $peiVoucherDisplay !== 'inherit'): ?>
                                                                        <span class="badge badge-secondary ml-1"><?php echo manager_safe_html($peiVoucherDisplay === 'both' ? 'barcode+testo' : $peiVoucherDisplay); ?></span>
                                                                    <?php endif; ?>
                                                                    <i class="fa fa-chevron-down iw-extra-chevron"></i>
                                                                </button>
                                                            </h6>
                                                        </div>
                                                        <div id="<?php echo manager_safe_html($peiCollapseId); ?>" class="collapse<?php echo $peiExpandFirst ? ' show' : ''; ?>"
                                                            aria-labelledby="<?php echo manager_safe_html($peiHeadingId); ?>" data-parent="#prize-email-templates-accordion">
                                                            <div class="card-body pt-2 pb-3 iw-extra-prize-body">
                                                        <input type="hidden" name="prize_email_template[<?php echo (int) $peiIdx; ?>][key]" value="<?php echo manager_safe_html($peiKey); ?>">
                                                        <input type="hidden" name="prize_email_template[<?php echo (int) $peiIdx; ?>][remove_template]" value="0" class="prize-template-remove-flag">
                                                        <p class="text-muted small mb-2">Chiave: <code><?php echo manager_safe_html($peiKey); ?></code> · ID premio: <?php echo (int) ($peiRow['id'] ?? 0); ?></p>
                                                        <div class="form-row">
                                                            <div class="form-group col-md-6 mb-2">
                                                                <label class="small mb-1">Immagine template (PNG/JPG)</label>
                                                                <div class="custom-file">
                                                                    <input type="file" name="upload_prize_email_template[<?php echo (int) $peiIdx; ?>]" id="upload_prize_email_template_<?php echo (int) $peiIdx; ?>" class="custom-file-input" accept="image/png,image/jpeg,image/webp,image/gif">
                                                                    <label class="custom-file-label" for="upload_prize_email_template_<?php echo (int) $peiIdx; ?>">Scegli immagine...</label>
                                                                </div>
                                                                <input type="hidden" name="prize_email_template[<?php echo (int) $peiIdx; ?>][template_path]" value="<?php echo manager_safe_html($peiTemplatePath); ?>">
                                                                <small class="form-text text-muted">Attuale: <span><?php echo $peiTemplatePath !== '' ? manager_safe_html($peiTemplatePath) : '—'; ?></span></small>
                                                            </div>
                                                            <div class="form-group col-md-6 mb-2">
                                                                <label class="small mb-1">Anteprima template</label>
                                                                <div class="prize-template-preview-wrap" data-pei-idx="<?php echo (int) $peiIdx; ?>" data-template-path="<?php echo manager_safe_html($peiTemplatePath); ?>">
                                                                    <?php if ($peiPreviewUrl !== ''): ?>
                                                                        <img src="<?php echo manager_safe_html($peiPreviewUrl); ?>" alt="" class="img-thumbnail prize-template-preview-img" style="max-height:120px;max-width:100%;">
                                                                        <?php if (!empty($peiOverlay['enabled'])): ?>
                                                                            <div class="small text-muted mt-1 prize-overlay-preview-hint">Codice esempio: <strong>ABC123</strong></div>
                                                                        <?php endif; ?>
                                                                    <?php else: ?>
                                                                        <div class="prize-template-preview-empty border rounded text-muted small p-3 text-center" style="min-height:80px;">
                                                                            Nessuna immagine salvata.<br>Carica un file e salva la configurazione.
                                                                        </div>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="form-check mb-2">
                                                            <input class="form-check-input" type="checkbox" name="prize_email_template[<?php echo (int) $peiIdx; ?>][overlay_enabled]" id="pei_overlay_<?php echo (int) $peiIdx; ?>" value="1"<?php echo !empty($peiOverlay['enabled']) ? EMAIL_STEP_ATTR_CHECKED : ''; ?>>
                                                            <label class="form-check-label small" for="pei_overlay_<?php echo (int) $peiIdx; ?>">Scrivi il codice premio sull'immagine (GD)</label>
                                                        </div>
                                                        <p class="small text-muted mb-2">Il codice viene scritto <strong>solo al momento della vincita</strong> (non sul file template). L'anteprima sopra mostra un esempio con <code>ABC123</code> dopo il salvataggio. Pos. Y = linea di base del testo (parte bassa dell'immagine ~360–380 su 400 px di altezza).</p>
                                                        <div class="form-row prize-overlay-fields" data-pei-idx="<?php echo (int) $peiIdx; ?>">
                                                            <div class="form-group col-6 col-md-3 mb-2">
                                                                <label class="small">Pos. X</label>
                                                                <input type="number" min="0" max="5000" class="form-control form-control-sm" name="prize_email_template[<?php echo (int) $peiIdx; ?>][overlay_x]" value="<?php echo (int) $peiOverlay['x']; ?>">
                                                            </div>
                                                            <div class="form-group col-6 col-md-3 mb-2">
                                                                <label class="small">Pos. Y</label>
                                                                <input type="number" min="0" max="5000" class="form-control form-control-sm" name="prize_email_template[<?php echo (int) $peiIdx; ?>][overlay_y]" value="<?php echo (int) $peiOverlay['y']; ?>">
                                                            </div>
                                                            <div class="form-group col-6 col-md-3 mb-2">
                                                                <label class="small">Font (px)</label>
                                                                <input type="number" min="8" max="120" class="form-control form-control-sm" name="prize_email_template[<?php echo (int) $peiIdx; ?>][overlay_font_size]" value="<?php echo (int) $peiOverlay['font_size']; ?>">
                                                            </div>
                                                            <div class="form-group col-6 col-md-3 mb-2">
                                                                <label class="small">Colore testo</label>
                                                                <input type="text" class="form-control form-control-sm" name="prize_email_template[<?php echo (int) $peiIdx; ?>][overlay_color]" value="<?php echo manager_safe_html($peiOverlay['color']); ?>" placeholder="#000000">
                                                            </div>
                                                        </div>
                                                        <hr class="my-3">
                                                        <div class="section-subsubtitle mb-2">Email personalizzata</div>
                                                        <p class="small text-muted mb-2">Di default si usa il template globale <strong>Instant Win - Premio</strong> (sotto). Attiva questa opzione per un testo HTML diverso solo per questo premio. L'oggetto resta quello globale.</p>
                                                        <div class="form-check mb-2">
                                                            <input class="form-check-input prize-custom-body-toggle" type="checkbox" name="prize_email_template[<?php echo (int) $peiIdx; ?>][custom_body_enabled]" id="pei_custom_body_<?php echo (int) $peiIdx; ?>" value="1"<?php echo $peiCustomBodyEnabled ? EMAIL_STEP_ATTR_CHECKED : ''; ?>>
                                                            <label class="form-check-label small" for="pei_custom_body_<?php echo (int) $peiIdx; ?>">Usa testo email personalizzato per questo premio</label>
                                                        </div>
                                                        <div class="prize-custom-email-panel<?php echo $peiCustomBodyEnabled ? '' : EMAIL_STEP_CLASS_HIDDEN; ?>" data-pei-idx="<?php echo (int) $peiIdx; ?>">
                                                            <div class="form-group mb-2">
                                                                <label class="small mb-1" for="pei_voucher_display_<?php echo (int) $peiIdx; ?>">Visualizzazione {{VOUCHER_CODE_BLOCK}}</label>
                                                                <select class="form-control form-control-sm prize-voucher-display-select" name="prize_email_template[<?php echo (int) $peiIdx; ?>][voucher_code_display]" id="pei_voucher_display_<?php echo (int) $peiIdx; ?>">
                                                                    <option value="inherit"<?php echo $peiVoucherDisplay === 'inherit' ? ' selected' : ''; ?>>Come template globale</option>
                                                                    <option value="text"<?php echo $peiVoucherDisplay === 'text' ? ' selected' : ''; ?>>Codice testuale</option>
                                                                    <option value="barcode"<?php echo $peiVoucherDisplay === 'barcode' ? ' selected' : ''; ?>>Barcode EAN-13</option>
                                                                    <option value="both"<?php echo $peiVoucherDisplay === 'both' ? ' selected' : ''; ?>>Barcode EAN-13 + codice testuale</option>
                                                                </select>
                                                            </div>
                                                            <div class="row">
                                                                <div class="col-lg-7">
                                                                    <div class="form-group mb-0">
                                                                        <label class="small mb-1">Contenuto email (HTML)</label>
                                                                        <?php
                                                                            $peiEmailBodyTextarea = $peiEmailBody;
                                                                            if ($peiEmailBodyTextarea !== '') {
                                                                                $peiEmailBodyTextarea = preg_replace('~</textarea>~i', '&lt;/textarea&gt;', $peiEmailBodyTextarea);
                                                                            }
                                                                        ?>
                                                                        <textarea name="prize_email_template[<?php echo (int) $peiIdx; ?>][email_body]" class="form-control prize-custom-email-editor" rows="8" data-pei-key="<?php echo manager_safe_html($peiKey); ?>"><?php echo $peiEmailBodyTextarea; ?></textarea>
                                                                        <small class="form-text text-muted d-block mt-2">Placeholder: {{PRIZE_IMAGE_BLOCK}}, {{VOUCHER_CODE_BLOCK}}, {{USER_NAME}}, {{PRIZE_TYPE}}, {{PRIZE_AMOUNT}}, {{PDV}}, ecc.</small>
                                                                    </div>
                                                                </div>
                                                                <div class="col-lg-5">
                                                                    <div class="email-preview-frame prize-custom-email-preview-frame">
                                                                        <iframe class="email-preview-iframe prize-custom-email-preview-iframe" title="Anteprima email premio <?php echo manager_safe_html($peiTipo); ?>"></iframe>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                                </div>
                                            <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                            </div>
                                            <div class="row prize-email-main-template-row">
                                                <div class="col-lg-7">
                                                    <div class="form-group">
                                                        <label>Oggetto</label>
                                                        <input type="text" name="email_instantwin_prize_subject" class="form-control" value="<?php echo manager_safe_html($email_instantwin_prize_subject); ?>">
                                                    </div>
                                                    <div class="form-group mb-0">
                                                        <label>Contenuto email (HTML)</label>
                                                        <p class="small text-muted mb-2">Template predefinito per tutti i premi Instant Win. Per un premio specifico, apri <strong>Immagini premio</strong> e attiva «Usa testo email personalizzato».</p>
                                                        <?php
                                                            $email_instantwin_prize_body_textarea = $email_instantwin_prize_body;
                                                            if ($email_instantwin_prize_body_textarea !== '') {
                                                                $email_instantwin_prize_body_textarea = preg_replace('~</textarea>~i', '&lt;/textarea&gt;', $email_instantwin_prize_body_textarea);
                                                            }
                                                        ?>
                                                        <textarea name="email_instantwin_prize_body" class="form-control email-template-editor" rows="8"><?php echo $email_instantwin_prize_body_textarea; ?></textarea>
                                                        <?php echo $email_placeholders_hint; ?>
                                                    </div>
                                                </div>
                                                <div class="col-lg-5">
                                                    <div class="email-preview-frame">
                                                        <iframe class="email-preview-iframe email-template-main-preview-iframe" title="Preview email instant win premio"></iframe>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
