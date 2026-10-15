<?php
/**
 * Pannello premi aggiuntivi IW per PDV (step Concorso 11).
 * La ripartizione iniziale (quota_giornaliera + pdv_premio_budget) avviene al salvataggio
 * concorso solo se le tabelle sono vuote — vedi instantwin_pdv_rebuild_after_config_save().
 */
if (!function_exists('manager_safe_html')) {
    function manager_safe_html(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

$pdoIw = null;
if (isset($connection) && $connection instanceof PDO) {
    $pdoIw = $connection;
} elseif (function_exists('conn_pdo')) {
    try {
        $pdoIw = conn_pdo();
    } catch (Throwable $e) {
        $pdoIw = null;
    }
}

$pdvIwPanelReady = false;
$extraPrizesPlanIw = [];
$pdvIwAjaxBase = isset($pdvIwAjaxBase) ? (string) $pdvIwAjaxBase : 'res/inc/';
$nPdvIw = 0;

if ($pdoIw instanceof PDO) {
    require_once __DIR__ . '/../../../app/logic/instantwin_pdv/instantwin_pdv.php';
    if (!isset($contest_config)) {
        require_once __DIR__ . '/../../../app/inc/config.php';
    }
    if (instantwin_pdv_enabled()) {
        instantwin_pdv_ensure_schema($pdoIw);
        $pdvsForSplit = instantwin_pdv_active_pdvs_for_split($pdoIw);
        $nPdvIw = count($pdvsForSplit);

        $premiRowsIw = [];
        try {
            $stP = $pdoIw->query('SELECT `id`, `tipo_premio`, `valore`, `quantita_totale` FROM `premi` ORDER BY `id` ASC');
            $premiRowsIw = $stP ? $stP->fetchAll(PDO::FETCH_ASSOC) : [];
        } catch (Throwable $e) {
            $premiRowsIw = [];
        }

        if ($pdvsForSplit !== [] && $premiRowsIw !== []) {
            $extraPrizesPlanIw = instantwin_pdv_manager_extra_prizes_plan($pdoIw, $pdvsForSplit, $premiRowsIw);
        }

        $pdvIwPanelReady = $nPdvIw > 0;
    }
}

$extraPrizesPlan = $extraPrizesPlanIw;
$hasExtraPrizes = $extraPrizesPlanIw !== [];
$nExtraPrizes = count($extraPrizesPlanIw);

if (!$pdvIwPanelReady) {
    echo '<p class="text-muted small mb-0">Nessun punto vendita attivo con codice magazzino. Importa o attiva i PDV prima di configurare la ripartizione.</p>';
    return;
}

$pdvMeta = [];
$pdvWeightFormCodes = [];
foreach ($pdvsForSplit ?? [] as $row) {
    $c = trim((string) ($row['codice'] ?? ''));
    if ($c === '') {
        continue;
    }
    $ragsoc = trim((string) ($row['ragsoc'] ?? ''));
    if ($ragsoc === '') {
        $ragsoc = trim((string) ($row['insegna'] ?? ''));
    }
    $indirizzo = trim((string) ($row['indirizzo'] ?? ''));
    $citta = trim((string) ($row['citta'] ?? ''));
    $prov = trim((string) ($row['prov'] ?? ''));
    $pdvMeta[$c] = [
        'label' => $ragsoc !== '' ? $ragsoc : $c,
        'ragsoc' => $ragsoc,
        'indirizzo' => $indirizzo,
        'citta' => $citta,
        'prov' => $prov,
    ];
    $pdvWeightFormCodes[] = $c;
}
natcasesort($pdvWeightFormCodes);
$pdvWeightFormCodes = array_values($pdvWeightFormCodes);
?>

<?php if ($hasExtraPrizes): ?>
    <h6 class="mb-2"><i class="fa fa-plus-circle mr-1"></i>Premi aggiuntivi <span class="badge badge-success"><?php echo (int) $nExtraPrizes; ?></span></h6>
    <p class="text-muted small mb-2">
        Se la quantità totale non è divisibile per il numero di PDV, ripartisci qui gli slot in più.
        Il salvataggio concorso non sovrascrive <code>quota_giornaliera</code> né <code>pdv_premio_budget</code> se le tabelle contengono già dati.
    </p>
    <?php
    $pdvIwExtraEmbedInTabs = false;
    $pdvIwExtraUseAccordion = !empty($pdvIwExtraUseAccordion);
    include __DIR__ . '/../premi_pdv_extra_block.php';
    ?>
<?php else: ?>
    <p class="text-muted small mb-0">
        <i class="fa fa-check text-success mr-1"></i>
        Tutti i premi si dividono in parti uguali tra i <?php echo (int) $nPdvIw; ?> PDV: nessun premio aggiuntivo da personalizzare.
    </p>
<?php endif; ?>
