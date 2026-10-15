<?php



/** @var array $activeRules */

/** @var array $securitySettings */

/** @var array $codiceProtectionContext */

/** @var bool $codiceProtectionEnabled */

?>

<div class="row mb-4">

    <?php foreach ($activeRules as $rule): ?>

        <div class="col-md-6 col-lg-4 mb-3">

            <div class="card h-100 border <?php echo !empty($rule['active']) ? 'border-success' : 'border-light'; ?>">

                <div class="card-body">

                    <div class="d-flex justify-content-between align-items-start mb-2">

                        <h6 class="card-title mb-0"><?php echo htmlspecialchars($rule['label'], ENT_QUOTES, 'UTF-8'); ?></h6>

                        <?php if (!empty($rule['active'])): ?>

                            <span class="badge badge-success">Attiva</span>

                        <?php else: ?>

                            <span class="badge badge-secondary">Inattiva</span>

                        <?php endif; ?>

                    </div>

                    <p class="text-muted small mb-0"><?php echo htmlspecialchars($rule['detail'], ENT_QUOTES, 'UTF-8'); ?></p>

                </div>

            </div>

        </div>

    <?php endforeach; ?>

</div>



<form method="post" action="security">

    <input type="hidden" name="action" value="save_settings">

    <input type="hidden" name="tab" value="rules">



    <div class="card border mb-4">

        <div class="card-header">Protezione codice partecipazione</div>

        <div class="card-body">

            <div class="custom-control custom-checkbox mb-3">

                <input

                    type="checkbox"

                    class="custom-control-input"

                    id="codice_partecipazione_security_enabled"

                    name="codice_partecipazione_security_enabled"

                    value="1"

                    <?php echo !empty($codiceProtectionEnabled) ? 'checked' : ''; ?>

                >

                <label class="custom-control-label font-weight-semibold" for="codice_partecipazione_security_enabled">

                    Attiva protezione codici per questo concorso

                </label>

            </div>



            <div class="alert alert-light border small mb-3">

                <strong>Concorso:</strong> <?php echo htmlspecialchars((string) ($codiceProtectionContext['contest_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>

                &middot; <strong>Modalità:</strong> <?php echo htmlspecialchars((string) ($codiceProtectionContext['mode'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>

                &middot; <strong>Campo codice:</strong>

                <?php if (!empty($codiceProtectionContext['codice_field_enabled'])): ?>

                    <span class="text-success">abilitato</span>

                <?php else: ?>

                    <span class="text-warning">disabilitato</span>

                    (<a href="concorso">configura in Concorso</a>)

                <?php endif; ?>

                <?php if (!empty($codiceProtectionContext['contest_in_progress'])): ?>

                    <br><span class="text-info">Il concorso è attualmente in corso.</span>

                <?php endif; ?>

            </div>



            <p class="text-muted small">

                L'attivazione è immediata. I contatori partono da zero e si applicano solo ai tentativi successivi.

                Valore <strong>0</strong> sui campi sotto = soglia disattivata. Blocco IP + email insieme; messaggio con tentativi restanti per l'utente.

            </p>



            <div class="form-row">

                <div class="form-group col-md-4">

                    <label for="codice_fail_max_per_window">Max tentativi errati / finestra</label>

                    <input type="number" min="0" max="99" class="form-control" id="codice_fail_max_per_window" name="codice_fail_max_per_window" value="<?php echo (int) ($securitySettings['codice_fail_max_per_window'] ?? 5); ?>">

                </div>

                <div class="form-group col-md-4">

                    <label for="codice_fail_window_seconds">Finestra (secondi)</label>

                    <input type="number" min="0" max="86400" class="form-control" id="codice_fail_window_seconds" name="codice_fail_window_seconds" value="<?php echo (int) ($securitySettings['codice_fail_window_seconds'] ?? 900); ?>">

                </div>

                <div class="form-group col-md-4">

                    <label for="codice_blacklist_at_strike">Blacklist auto dopo N blocchi</label>

                    <input type="number" min="1" max="10" class="form-control" id="codice_blacklist_at_strike" name="codice_blacklist_at_strike" value="<?php echo (int) ($securitySettings['codice_blacklist_at_strike'] ?? 4); ?>">

                </div>

            </div>

            <div class="form-row">

                <div class="form-group col-md-4">

                    <label for="codice_lockout_level1_seconds">1° blocco (secondi)</label>

                    <input type="number" min="0" max="604800" class="form-control" id="codice_lockout_level1_seconds" name="codice_lockout_level1_seconds" value="<?php echo (int) ($securitySettings['codice_lockout_level1_seconds'] ?? 300); ?>">

                </div>

                <div class="form-group col-md-4">

                    <label for="codice_lockout_level2_seconds">2° blocco (secondi)</label>

                    <input type="number" min="0" max="604800" class="form-control" id="codice_lockout_level2_seconds" name="codice_lockout_level2_seconds" value="<?php echo (int) ($securitySettings['codice_lockout_level2_seconds'] ?? 1800); ?>">

                </div>

                <div class="form-group col-md-4">

                    <label for="codice_lockout_level3_seconds">3° blocco (secondi)</label>

                    <input type="number" min="0" max="604800" class="form-control" id="codice_lockout_level3_seconds" name="codice_lockout_level3_seconds" value="<?php echo (int) ($securitySettings['codice_lockout_level3_seconds'] ?? 86400); ?>">

                </div>

            </div>

        </div>

    </div>



    <div class="card border">

        <div class="card-header">Limiti anti-abuso (iscrizioni)</div>

        <div class="card-body">

            <p class="text-muted small">

                Valore <strong>0</strong> = disattivato. I blocchi mostrano all'utente un messaggio generico sul codice partecipazione (anti-enumerazione), salvo se la protezione codici è attiva.

                <?php if (!contest_security_participations_has_ip_column($connection)): ?>

                    <br><span class="text-warning">Nota: la colonna <code>ip_address</code> non è presente in <code>partecipazioni</code> — i limiti IP non possono essere applicati.</span>

                <?php endif; ?>

            </p>

            <div class="form-row">

                <div class="form-group col-md-4">

                    <label for="registration_ip_max_per_day">Max iscrizioni per IP / 24h</label>

                    <input type="number" min="0" max="999" class="form-control" id="registration_ip_max_per_day" name="registration_ip_max_per_day" value="<?php echo (int) ($securitySettings['registration_ip_max_per_day'] ?? 0); ?>">

                </div>

                <div class="form-group col-md-4">

                    <label for="registration_email_max_per_day">Max iscrizioni per email / 24h</label>

                    <input type="number" min="0" max="999" class="form-control" id="registration_email_max_per_day" name="registration_email_max_per_day" value="<?php echo (int) ($securitySettings['registration_email_max_per_day'] ?? 0); ?>">

                </div>

                <div class="form-group col-md-4">

                    <label for="registration_cooldown_seconds">Cooldown tra tentativi (secondi, stesso IP)</label>

                    <input type="number" min="0" max="3600" class="form-control" id="registration_cooldown_seconds" name="registration_cooldown_seconds" value="<?php echo (int) ($securitySettings['registration_cooldown_seconds'] ?? 0); ?>">

                </div>

            </div>

            <div class="form-group col-md-4 pl-0">

                <label for="log_retention_days">Conservazione registro (giorni)</label>

                <input type="number" min="7" max="365" class="form-control" id="log_retention_days" name="log_retention_days" value="<?php echo (int) ($securitySettings['log_retention_days'] ?? 90); ?>">

            </div>

        </div>

    </div>



    <button type="submit" class="btn btn-primary mt-3">

        <i class="fa fa-save mr-1"></i> Salva impostazioni

    </button>

</form>


