<?php
/**
 * Form impostazioni tecniche Instant Win (variabili .env).
 */
if (!function_exists('manager_safe_html')) {
    function manager_safe_html(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

require_once __DIR__ . '/../../../app/logic/instantwin/manager_env.php';

$iwEnvAjaxBase = isset($iwEnvAjaxBase) ? (string) $iwEnvAjaxBase : 'res/inc/';
$iwEnvValues = instantwin_manager_env_values_for_form();
$iwEnvWritable = is_writable(instantwin_manager_env_file_path());
if (!function_exists('contest_normalize_instantwin_prize_distribution')) {
    require_once __DIR__ . '/../../../app/inc/functions.php';
}
$iwShowPdvPacing = contest_normalize_instantwin_prize_distribution(is_array($content_current) ? $content_current : []) === 'pdv';
$iwShowCedi = contest_normalize_instantwin_prize_distribution(is_array($content_current) ? $content_current : []) === 'cedi';

$iwEnvBool = static function (string $key, bool $default = false) use ($iwEnvValues): bool {
    if (!array_key_exists($key, $iwEnvValues)) {
        return $default;
    }
    $v = strtolower((string) $iwEnvValues[$key]);
    return in_array($v, ['1', 'true', 'yes', 'on'], true);
};

$iwEnvVal = static function (string $key, string $default = '') use ($iwEnvValues): string {
    return (string) ($iwEnvValues[$key] ?? $default);
};

$dayNames = [1 => 'Lun', 2 => 'Mar', 3 => 'Mer', 4 => 'Gio', 5 => 'Ven', 6 => 'Sab', 7 => 'Dom'];
$distDaysRaw = $iwEnvVal('INSTANTWIN_DISTRIBUTION_DAYS', '1,2,3,4,5,6,7');
$distDaysSelected = array_map('intval', array_filter(array_map('trim', explode(',', $distDaysRaw))));
$pdvPacingDefaultOn = !array_key_exists('INSTANTWIN_PDV_DAILY_PACING_ENABLED', $iwEnvValues);
?>

<div class="card border mb-4 iw-env-card" id="iw-env-settings-card">
    <div class="card-header py-2 bg-light iw-env-card-header">
        <div>
            <strong><i class="fa fa-cog mr-1"></i>Impostazioni tecniche Instant Win</strong>
            <span class="badge badge-secondary ml-2">.env</span>
        </div>
        <small class="text-muted">Salvataggio AJAX</small>
    </div>
    <div class="card-body">
        <?php if (!$iwEnvWritable): ?>
            <div class="alert alert-warning small mb-3">
                Il file <code>.env</code> non è scrivibile: le modifiche non possono essere salvate da qui.
            </div>
        <?php endif; ?>
        <p class="text-muted small mb-3 iw-env-intro">
            Parametri letti dal file <code>.env</code> alla richiesta PHP.
            Dopo il salvataggio, ricarica questa pagina; in alcuni ambienti può servire un riavvio di Apache/PHP-FPM.
            La data di <strong>inizio</strong> concorso resta in Configurazione (content.json).
        </p>

        <div id="formInstantwinEnv" class="iw-env-settings-fields" data-ajax-base="<?php echo manager_safe_html($iwEnvAjaxBase); ?>" data-save-url="<?php echo manager_safe_html($iwEnvSaveUrl ?? ($iwEnvAjaxBase . 'instantwin_env_save.php')); ?>"<?php echo $iwEnvWritable ? '' : ' style="pointer-events:none;opacity:0.6;"'; ?>>
            <?php if ($iwShowPdvPacing): ?>
                <input type="hidden" name="iw_env_include_pdv_pacing" value="1">
            <?php endif; ?>
            <?php if ($iwShowCedi): ?>
                <input type="hidden" name="iw_env_include_cedi" value="1">
            <?php endif; ?>
            <div class="accordion iw-env-accordion" id="iw-env-accordion">

                <?php if ($iwShowCedi): ?>
                <div class="card mb-2 iw-env-accordion-card border-primary">
                    <div class="card-header p-2 iw-env-accordion-header">
                        <button class="btn btn-link btn-sm btn-block text-left iw-env-accordion-toggle" type="button" data-toggle="collapse" data-target="#iw-env-c-cedi" aria-expanded="true">
                            <span><i class="fa fa-store mr-2"></i>Ce.Di. (rete / calendario / quote)</span>
                            <i class="fa fa-chevron-down iw-env-chevron"></i>
                        </button>
                    </div>
                    <div id="iw-env-c-cedi" class="collapse show" data-parent="#iw-env-accordion">
                        <div class="card-body py-2 small iw-env-accordion-body">
                            <p class="text-muted mb-2">
                                Con <strong>Ripartizione premi per Ce.Di.</strong> nel concorso, quota giornaliera e calendario slot sono attivi automaticamente (richiede la matrice Ce.Di. salvata).
                            </p>
                            <div class="custom-control custom-checkbox mb-0">
                                <input type="checkbox" class="custom-control-input js-iw-env-bool" id="iw-env-INSTANTWIN_CEDI_WEEK_BUDGET_CARRYOVER" name="INSTANTWIN_CEDI_WEEK_BUDGET_CARRYOVER" value="1" <?php echo $iwEnvBool('INSTANTWIN_CEDI_WEEK_BUDGET_CARRYOVER', true) ? 'checked' : ''; ?>>
                                <label class="custom-control-label" for="iw-env-INSTANTWIN_CEDI_WEEK_BUDGET_CARRYOVER">Riporto budget settimanale</label>
                            </div>
                            <p class="small text-muted mb-0 mt-2">Con quota giornaliera Ce.Di. attiva, il ribilanciamento quotidiano è il meccanismo principale; il riporto settimanale è un filet tra due settimane.</p>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <div class="card mb-2 iw-env-accordion-card">
                    <div class="card-header p-2 iw-env-accordion-header" id="iw-env-h-date">
                        <button class="btn btn-link btn-sm btn-block text-left collapsed iw-env-accordion-toggle" type="button" data-toggle="collapse" data-target="#iw-env-c-date" aria-expanded="false">
                            <span><i class="fa fa-calendar-o mr-2"></i>Date</span>
                            <i class="fa fa-chevron-down iw-env-chevron"></i>
                        </button>
                    </div>
                    <div id="iw-env-c-date" class="collapse" data-parent="#iw-env-accordion">
                        <div class="card-body py-2 small iw-env-accordion-body">
                            <div class="custom-control custom-checkbox mb-2">
                                <input type="checkbox" class="custom-control-input js-iw-env-bool" id="iw-env-INSTANTWIN_ENABLE_CUSTOM_END_DATE" name="INSTANTWIN_ENABLE_CUSTOM_END_DATE" value="1" <?php echo $iwEnvBool('INSTANTWIN_ENABLE_CUSTOM_END_DATE') ? 'checked' : ''; ?>>
                                <label class="custom-control-label" for="iw-env-INSTANTWIN_ENABLE_CUSTOM_END_DATE">Data fine IW diversa da quella del concorso</label>
                            </div>
                            <div class="form-group mb-0 js-iw-env-dep" data-show-if="INSTANTWIN_ENABLE_CUSTOM_END_DATE">
                                <label for="iw-env-INSTANTWIN_END_DATE">Data fine IW</label>
                                <input type="date" class="form-control form-control-sm" id="iw-env-INSTANTWIN_END_DATE" name="INSTANTWIN_END_DATE" value="<?php echo manager_safe_html($iwEnvVal('INSTANTWIN_END_DATE')); ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-2 iw-env-accordion-card">
                    <div class="card-header p-2 iw-env-accordion-header">
                        <button class="btn btn-link btn-sm btn-block text-left collapsed iw-env-accordion-toggle" type="button" data-toggle="collapse" data-target="#iw-env-c-hours" aria-expanded="false">
                            <span><i class="fa fa-clock-o mr-2"></i>Fascia oraria</span>
                            <i class="fa fa-chevron-down iw-env-chevron"></i>
                        </button>
                    </div>
                    <div id="iw-env-c-hours" class="collapse" data-parent="#iw-env-accordion">
                        <div class="card-body py-2 small iw-env-accordion-body">
                            <div class="custom-control custom-checkbox mb-2">
                                <input type="checkbox" class="custom-control-input js-iw-env-bool" id="iw-env-INSTANTWIN_ENABLE_TIME_RESTRICTIONS" name="INSTANTWIN_ENABLE_TIME_RESTRICTIONS" value="1" <?php echo $iwEnvBool('INSTANTWIN_ENABLE_TIME_RESTRICTIONS') ? 'checked' : ''; ?>>
                                <label class="custom-control-label" for="iw-env-INSTANTWIN_ENABLE_TIME_RESTRICTIONS">Limita orari (disattivo = 24/7)</label>
                            </div>
                            <div class="form-row js-iw-env-dep" data-show-if="INSTANTWIN_ENABLE_TIME_RESTRICTIONS">
                                <div class="form-group col-6 mb-2">
                                    <label for="iw-env-INSTANTWIN_START_HOUR">Ora inizio</label>
                                    <input type="number" min="0" max="23" class="form-control form-control-sm" id="iw-env-INSTANTWIN_START_HOUR" name="INSTANTWIN_START_HOUR" value="<?php echo manager_safe_html($iwEnvVal('INSTANTWIN_START_HOUR', '9')); ?>">
                                </div>
                                <div class="form-group col-6 mb-0">
                                    <label for="iw-env-INSTANTWIN_END_HOUR">Ora fine (esclusiva)</label>
                                    <input type="number" min="1" max="24" class="form-control form-control-sm" id="iw-env-INSTANTWIN_END_HOUR" name="INSTANTWIN_END_HOUR" value="<?php echo manager_safe_html($iwEnvVal('INSTANTWIN_END_HOUR', '24')); ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-2 iw-env-accordion-card">
                    <div class="card-header p-2 iw-env-accordion-header">
                        <button class="btn btn-link btn-sm btn-block text-left collapsed iw-env-accordion-toggle" type="button" data-toggle="collapse" data-target="#iw-env-c-days" aria-expanded="false">
                            <span><i class="fa fa-calendar-check-o mr-2"></i>Giorni della settimana</span>
                            <i class="fa fa-chevron-down iw-env-chevron"></i>
                        </button>
                    </div>
                    <div id="iw-env-c-days" class="collapse" data-parent="#iw-env-accordion">
                        <div class="card-body py-2 small iw-env-accordion-body">
                            <div class="custom-control custom-checkbox mb-2">
                                <input type="checkbox" class="custom-control-input js-iw-env-bool" id="iw-env-INSTANTWIN_ENABLE_DAY_CUSTOMIZATION" name="INSTANTWIN_ENABLE_DAY_CUSTOMIZATION" value="1" <?php echo $iwEnvBool('INSTANTWIN_ENABLE_DAY_CUSTOMIZATION') ? 'checked' : ''; ?>>
                                <label class="custom-control-label" for="iw-env-INSTANTWIN_ENABLE_DAY_CUSTOMIZATION">Personalizza giorni e orari per giorno</label>
                            </div>
                            <div class="js-iw-env-dep mb-3" data-show-if="INSTANTWIN_ENABLE_DAY_CUSTOMIZATION">
                                <label class="d-block mb-1">Giorni attivi</label>
                                <?php for ($d = 1; $d <= 7; $d++): ?>
                                    <div class="custom-control custom-checkbox custom-control-inline">
                                        <input type="checkbox" class="custom-control-input" id="iw-env-day-<?php echo $d; ?>" name="INSTANTWIN_DISTRIBUTION_DAYS[]" value="<?php echo $d; ?>" <?php echo in_array($d, $distDaysSelected, true) ? 'checked' : ''; ?>>
                                        <label class="custom-control-label" for="iw-env-day-<?php echo $d; ?>"><?php echo manager_safe_html($dayNames[$d]); ?></label>
                                    </div>
                                <?php endfor; ?>
                            </div>
                            <div class="js-iw-env-dep" data-show-if="INSTANTWIN_ENABLE_DAY_CUSTOMIZATION">
                                <label class="d-block mb-1">Orari per giorno (inizio,fine)</label>
                                <div class="table-responsive iw-env-table-wrap">
                                    <table class="table table-sm table-bordered mb-0 iw-env-table">
                                        <tbody>
                                            <?php
                                            $dayLabels = ['', 'Lunedì', 'Martedì', 'Mercoledì', 'Giovedì', 'Venerdì', 'Sabato', 'Domenica'];
                                            for ($d = 1; $d <= 7; $d++):
                                                $k = 'INSTANTWIN_DAY_' . $d . '_HOURS';
                                            ?>
                                            <tr>
                                                <td class="align-middle" style="width: 28%;"><?php echo manager_safe_html($dayLabels[$d]); ?></td>
                                                <td>
                                                    <input type="text" class="form-control form-control-sm" name="<?php echo manager_safe_html($k); ?>" value="<?php echo manager_safe_html($iwEnvVal($k, '9,19')); ?>" placeholder="9,19" pattern="^\d{1,2},\d{1,2}$">
                                                </td>
                                            </tr>
                                            <?php endfor; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-2 iw-env-accordion-card">
                    <div class="card-header p-2 iw-env-accordion-header">
                        <button class="btn btn-link btn-sm btn-block text-left collapsed iw-env-accordion-toggle" type="button" data-toggle="collapse" data-target="#iw-env-c-quota" aria-expanded="false">
                            <span><i class="fa fa-cogs mr-2"></i><?php echo $iwShowCedi ? 'Quota e calendario' : 'Quota giornaliera e motore'; ?></span>
                            <i class="fa fa-chevron-down iw-env-chevron"></i>
                        </button>
                    </div>
                    <div id="iw-env-c-quota" class="collapse" data-parent="#iw-env-accordion">
                        <div class="card-body py-2 small iw-env-accordion-body">
                            <div class="form-group">
                                <label for="iw-env-INSTANTWIN_PRIZE_SELECTION_MODE">Selezione premio</label>
                                <select class="form-control form-control-sm" id="iw-env-INSTANTWIN_PRIZE_SELECTION_MODE" name="INSTANTWIN_PRIZE_SELECTION_MODE">
                                    <?php
                                    $psm = strtolower($iwEnvVal('INSTANTWIN_PRIZE_SELECTION_MODE', 'highest_value'));
                                    foreach (['highest_value' => 'Valore più alto', 'lowest_id' => 'ID più basso', 'random' => 'Casuale'] as $optVal => $optLab):
                                    ?>
                                    <option value="<?php echo manager_safe_html($optVal); ?>" <?php echo $psm === $optVal ? 'selected' : ''; ?>><?php echo manager_safe_html($optLab); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="iw-env-INSTANTWIN_QUOTA_DISTRIBUTION">Distribuzione quota globale</label>
                                <select class="form-control form-control-sm js-iw-quota-dist" id="iw-env-INSTANTWIN_QUOTA_DISTRIBUTION" name="INSTANTWIN_QUOTA_DISTRIBUTION">
                                    <?php $qd = strtolower($iwEnvVal('INSTANTWIN_QUOTA_DISTRIBUTION', 'progressive')); ?>
                                    <option value="balanced" <?php echo $qd === 'balanced' ? 'selected' : ''; ?>>Bilanciata</option>
                                    <option value="progressive" <?php echo $qd === 'progressive' ? 'selected' : ''; ?>>Progressiva</option>
                                </select>
                            </div>
                            <div class="form-row js-iw-env-quota-prog">
                                <div class="form-group col-6">
                                    <label for="iw-env-INSTANTWIN_QUOTA_COEF_START">Coeff. primo giorno</label>
                                    <input type="text" inputmode="decimal" class="form-control form-control-sm" id="iw-env-INSTANTWIN_QUOTA_COEF_START" name="INSTANTWIN_QUOTA_COEF_START" value="<?php echo manager_safe_html($iwEnvVal('INSTANTWIN_QUOTA_COEF_START', '1.5')); ?>">
                                </div>
                                <div class="form-group col-6">
                                    <label for="iw-env-INSTANTWIN_QUOTA_COEF_END">Coeff. ultimo giorno</label>
                                    <input type="text" inputmode="decimal" class="form-control form-control-sm" id="iw-env-INSTANTWIN_QUOTA_COEF_END" name="INSTANTWIN_QUOTA_COEF_END" value="<?php echo manager_safe_html($iwEnvVal('INSTANTWIN_QUOTA_COEF_END', '0.5')); ?>">
                                </div>
                            </div>
                            <?php if (!$iwShowCedi): ?>
                            <hr class="my-2">
                            <div class="form-group mb-2">
                                <label for="iw-env-INSTANTWIN_ENGINE">Motore</label>
                                <select class="form-control form-control-sm" id="iw-env-INSTANTWIN_ENGINE" name="INSTANTWIN_ENGINE">
                                    <?php $eng = strtolower($iwEnvVal('INSTANTWIN_ENGINE', 'v2')); ?>
                                    <option value="v2" <?php echo $eng === 'v2' ? 'selected' : ''; ?>>v2</option>
                                    <option value="legacy" <?php echo $eng === 'legacy' ? 'selected' : ''; ?>>Legacy</option>
                                </select>
                            </div>
                            <?php endif; ?>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="iw-env-INSTANTWIN_MIN_SLOT_INTERVAL_SECONDS">Intervallo min. tra vincite (s)</label>
                                    <input type="number" min="0" max="86400" class="form-control form-control-sm" id="iw-env-INSTANTWIN_MIN_SLOT_INTERVAL_SECONDS" name="INSTANTWIN_MIN_SLOT_INTERVAL_SECONDS" value="<?php echo manager_safe_html($iwEnvVal('INSTANTWIN_MIN_SLOT_INTERVAL_SECONDS', '180')); ?>">
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="iw-env-INSTANTWIN_CATCHUP_HOURS_BEFORE_END">Catch-up (ore prima fine fascia)</label>
                                    <input type="number" min="1" max="24" class="form-control form-control-sm" id="iw-env-INSTANTWIN_CATCHUP_HOURS_BEFORE_END" name="INSTANTWIN_CATCHUP_HOURS_BEFORE_END" value="<?php echo manager_safe_html($iwEnvVal('INSTANTWIN_CATCHUP_HOURS_BEFORE_END', '2')); ?>">
                                </div>
                            </div>
                            <div class="custom-control custom-checkbox mb-2">
                                <input type="checkbox" class="custom-control-input js-iw-env-bool" id="iw-env-INSTANTWIN_FIRST_FASCIA_RANDOM_ENABLED" name="INSTANTWIN_FIRST_FASCIA_RANDOM_ENABLED" value="1" <?php echo $iwEnvBool('INSTANTWIN_FIRST_FASCIA_RANDOM_ENABLED', true) ? 'checked' : ''; ?>>
                                <label class="custom-control-label" for="iw-env-INSTANTWIN_FIRST_FASCIA_RANDOM_ENABLED">Sorteggio ad ogni tentativo di vincita sul slot</label>
                            </div>
                            <div class="form-group js-iw-env-dep" data-show-if="INSTANTWIN_FIRST_FASCIA_RANDOM_ENABLED">
                                <label for="iw-env-INSTANTWIN_FIRST_FASCIA_WIN_PROBABILITY">Probabilità di vincita per tentativo (vuoto = 50%)</label>
                                <input type="text" inputmode="decimal" class="form-control form-control-sm" id="iw-env-INSTANTWIN_FIRST_FASCIA_WIN_PROBABILITY" name="INSTANTWIN_FIRST_FASCIA_WIN_PROBABILITY" value="<?php echo manager_safe_html($iwEnvVal('INSTANTWIN_FIRST_FASCIA_WIN_PROBABILITY', '')); ?>" placeholder="0.5">
                                <small class="form-text text-muted">Vuoto: 50% win / 50% lose a ogni tentativo (1ª, 2ª, 3ª partecipazione sullo slot…). Se disattivo: FCFS.</small>
                            </div>
                            <?php
                                $iwTestOutcomeMode = strtolower($iwEnvVal('INSTANTWIN_TEST_OUTCOME_MODE', 'off'));
                                if (!in_array($iwTestOutcomeMode, ['off', 'direct_win', 'direct_loss'], true)) {
                                    $iwTestOutcomeMode = 'off';
                                }
                            ?>
                            <div class="form-group mb-0">
                                <label class="text-warning mb-1" for="iw-env-INSTANTWIN_TEST_OUTCOME_MODE">Esito test senza calendario (solo test)</label>
                                <select class="form-control form-control-sm" id="iw-env-INSTANTWIN_TEST_OUTCOME_MODE" name="INSTANTWIN_TEST_OUTCOME_MODE">
                                    <option value="off" <?php echo $iwTestOutcomeMode === 'off' ? 'selected' : ''; ?>>Disattivato (calendario normale)</option>
                                    <option value="direct_win" <?php echo $iwTestOutcomeMode === 'direct_win' ? 'selected' : ''; ?>>Vincita diretta</option>
                                    <option value="direct_loss" <?php echo $iwTestOutcomeMode === 'direct_loss' ? 'selected' : ''; ?>>Perdita diretta</option>
                                </select>
                                <small class="form-text text-muted">Ignora slot e calendario: ogni partecipazione vince o perde subito. Solo per test/staging.</small>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if ($iwShowPdvPacing): ?>
                <div class="card mb-2 iw-env-accordion-card">
                    <div class="card-header p-2 iw-env-accordion-header">
                        <button class="btn btn-link btn-sm btn-block text-left collapsed iw-env-accordion-toggle" type="button" data-toggle="collapse" data-target="#iw-env-c-pdv" aria-expanded="false">
                            <span><i class="fa fa-map-marker mr-2"></i>Pacing PDV</span>
                            <i class="fa fa-chevron-down iw-env-chevron"></i>
                        </button>
                    </div>
                    <div id="iw-env-c-pdv" class="collapse" data-parent="#iw-env-accordion">
                        <div class="card-body py-2 small iw-env-accordion-body">
                            <div class="custom-control custom-checkbox mb-2">
                                <input type="checkbox" class="custom-control-input js-iw-env-bool" id="iw-env-INSTANTWIN_PDV_DAILY_PACING_ENABLED" name="INSTANTWIN_PDV_DAILY_PACING_ENABLED" value="1" <?php echo $iwEnvBool('INSTANTWIN_PDV_DAILY_PACING_ENABLED', $pdvPacingDefaultOn) ? 'checked' : ''; ?>>
                                <label class="custom-control-label" for="iw-env-INSTANTWIN_PDV_DAILY_PACING_ENABLED">Pacing giornaliero soft per PDV</label>
                            </div>
                            <div class="form-group mb-0 js-iw-env-dep" data-show-if="INSTANTWIN_PDV_DAILY_PACING_ENABLED">
                                <label for="iw-env-INSTANTWIN_PDV_PACING_RELAX_DAYS">Giorni di rilassamento a fine concorso</label>
                                <input type="number" min="0" max="365" class="form-control form-control-sm" id="iw-env-INSTANTWIN_PDV_PACING_RELAX_DAYS" name="INSTANTWIN_PDV_PACING_RELAX_DAYS" value="<?php echo manager_safe_html($iwEnvVal('INSTANTWIN_PDV_PACING_RELAX_DAYS', '3')); ?>">
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <div class="card mb-2 iw-env-accordion-card">
                    <div class="card-header p-2 iw-env-accordion-header" id="iw-env-h-winner-dedup">
                        <button class="btn btn-link btn-sm btn-block text-left collapsed iw-env-accordion-toggle" type="button" data-toggle="collapse" data-target="#iw-env-c-winner-dedup" aria-expanded="false">
                            <span><i class="fa fa-user-check mr-2"></i>Vincitore unico</span>
                            <i class="fa fa-chevron-down iw-env-chevron"></i>
                        </button>
                    </div>
                    <div id="iw-env-c-winner-dedup" class="collapse" data-parent="#iw-env-accordion">
                        <div class="card-body py-2 small iw-env-accordion-body">
                            <div class="custom-control custom-checkbox mb-2">
                                <input type="checkbox" class="custom-control-input js-iw-env-bool" id="iw-env-INSTANTWIN_ALLOW_MULTIPLE_WINS" name="INSTANTWIN_ALLOW_MULTIPLE_WINS" value="1" <?php echo $iwEnvBool('INSTANTWIN_ALLOW_MULTIPLE_WINS', false) ? 'checked' : ''; ?>>
                                <label class="custom-control-label" for="iw-env-INSTANTWIN_ALLOW_MULTIPLE_WINS">Consenti vittorie multiple per lo stesso utente</label>
                            </div>
                            <p class="small text-muted mb-0">Se attivo, uno stesso email o fidelity può vincere più volte. Se disattivo <strong>(default)</strong>, ogni identità vince al massimo una volta.</p>
                        </div>
                    </div>
                </div>

            </div>

            <button type="button" class="btn btn-primary btn-sm mt-3 iw-env-save-btn" id="btnSaveInstantwinEnv"<?php echo $iwEnvWritable ? '' : ' disabled'; ?>>
                <i class="fa fa-save mr-1"></i>Salva impostazioni .env
            </button>
        </div>
    </div>
</div>
