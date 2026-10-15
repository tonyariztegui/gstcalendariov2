<?php
/**
 * Step 11 – Instant Win: impostazioni .env + ripartizione PDV (premi aggiuntivi).
 */
if (!function_exists('contest_normalize_instantwin_prize_distribution')) {
    require_once __DIR__ . '/../../../app/inc/functions.php';
}
$contestModeIwPers = strtolower((string) ($content_current['contest_mode'] ?? ($content_defaults['contest_mode'] ?? 'estrazione')));
$isInstantwinModeIwPers = ($contestModeIwPers === 'instantwin');
$iwPrizeDistributionPers = contest_normalize_instantwin_prize_distribution(is_array($content_current) ? $content_current : []);
$iwPerPdvCheckedPers = ($iwPrizeDistributionPers === 'pdv');
$showIwPdvPersStep = $isInstantwinModeIwPers;
$showIwPdvRepartition = $isInstantwinModeIwPers && $iwPerPdvCheckedPers;
?>
<div class="card mt-3 mb-4 config-anchor config-step iw-personalizzazione-section" data-step="11" id="section-iw-pdv-personalizzazione" data-should-hide="<?php echo $showIwPdvPersStep ? 'false' : 'true'; ?>" style="<?php echo $showIwPdvPersStep ? '' : 'display: none;'; ?>">
    <div class="card-body">
        <div class="iw-personalizzazione-hero">
            <div>
        <div class="section-title"><i class="fa fa-sliders-h mr-2"></i>Instant Win — personalizzazione</div>
        <p class="text-muted small mb-3">
            Parametri tecnici del motore Instant Win (file <code>.env</code>)
            <?php if ($showIwPdvRepartition): ?>
                e ripartizione per punto vendita (premi aggiuntivi).
            <?php endif; ?>
            Le statistiche aggregate per magazzino sono in <a href="statistiche-pdv">Statistiche PDV</a>.
        </p>
            </div>
            <div class="iw-personalizzazione-hero-actions">
                <span class="iw-personalizzazione-badge"><i class="fa fa-bolt mr-1"></i> Motore IW</span>
                <?php if ($showIwPdvRepartition): ?>
                    <span class="iw-personalizzazione-badge iw-personalizzazione-badge--success"><i class="fa fa-map-marker mr-1"></i> PDV attivo</span>
                <?php endif; ?>
            </div>
        </div>
        <div class="iw-personalizzazione-panel">
            <div class="iw-personalizzazione-panel-header">
                <span class="iw-personalizzazione-panel-icon"><i class="fa fa-sliders-h"></i></span>
                <div>
                    <h6>Parametri motore</h6>
                    <p>Configura il comportamento Instant Win salvato nel file <code>.env</code>.</p>
                </div>
            </div>
        <?php
        $managerBasePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/manager/index.php')), '/');
        $iwEnvAjaxBase = $managerBasePath . '/res/inc/';
        $iwEnvSaveUrl = $iwEnvAjaxBase . 'instantwin_env_save.php';
        include __DIR__ . '/../inc/instantwin_env_panel.php';
        ?>
        </div>

        <?php
        if ($showIwPdvRepartition):
        ?>
        <div class="iw-personalizzazione-panel mb-0" id="iw-pdv-repartition-block">
            <div class="iw-personalizzazione-panel-header iw-personalizzazione-panel-header--success">
                <span class="iw-personalizzazione-panel-icon iw-personalizzazione-panel-icon--success"><i class="fa fa-plus-circle"></i></span>
                <div>
                    <h6>Premi aggiuntivi per PDV</h6>
                    <p>Distribuisci gli slot residui sui magazzini coinvolti.</p>
                </div>
            </div>
        <?php
            $pdvIwAjaxBase = $iwEnvAjaxBase;
            $pdvIwUseTabs = false;
            $pdvIwExtraEmbedInTabs = false;
            $pdvIwExtraUseAccordion = true;
            include __DIR__ . '/../inc/pdv_iw_repartition_panel.php';
        ?>
        </div>
        <?php
        endif;
        ?>
    </div>
</div>
