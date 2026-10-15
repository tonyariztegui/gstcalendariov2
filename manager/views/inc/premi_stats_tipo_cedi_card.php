<?php
/**
 * Card « Statistiche per Tipo di Premio » con tab per Ce.Di.
 * Richiede: $stats_per_tipo_by_cedi (array)
 */
if (!function_exists('manager_safe_html')) {
    function manager_safe_html(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
$stats_per_tipo_by_cedi = is_array($stats_per_tipo_by_cedi ?? null) ? $stats_per_tipo_by_cedi : [];
$useCardWrapper = !isset($premiStatsCardInnerOnly) || !$premiStatsCardInnerOnly;
$cediLabelMap = [];
if (isset($connection) && $connection instanceof PDO) {
    if (!function_exists('instantwin_cedi_label_map')) {
        require_once __DIR__ . '/../../../app/logic/instantwin_cedi/cedi_core.php';
    }
    global $content_config;
    $cediCfg = is_array($content_config ?? null) ? $content_config : null;
    $cediLabelMap = instantwin_cedi_label_map($connection, $cediCfg);
}
?>
<?php if ($useCardWrapper): ?>
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fa fa-chart-pie mr-2"></i>Statistiche per Tipo di Premio
                </h5>
            </div>
            <div class="card-body">
<?php endif; ?>
                <?php if ($stats_per_tipo_by_cedi === []): ?>
                    <div class="text-center text-muted">Nessun dato disponibile.</div>
                <?php else: ?>
                    <?php
                    $cediStatsTabs = array_values($stats_per_tipo_by_cedi);
                    usort($cediStatsTabs, static function (array $a, array $b) use ($cediLabelMap): int {
                        $la = function_exists('instantwin_cedi_display_label')
                            ? instantwin_cedi_display_label((string) ($a['codice'] ?? ''), (string) ($a['nome'] ?? ''), $cediLabelMap)
                            : (string) ($a['nome'] ?? $a['codice'] ?? '');
                        $lb = function_exists('instantwin_cedi_display_label')
                            ? instantwin_cedi_display_label((string) ($b['codice'] ?? ''), (string) ($b['nome'] ?? ''), $cediLabelMap)
                            : (string) ($b['nome'] ?? $b['codice'] ?? '');
                        $cmp = strnatcasecmp($la, $lb);
                        if ($cmp !== 0) {
                            return $cmp;
                        }
                        return strnatcasecmp((string) ($a['codice'] ?? ''), (string) ($b['codice'] ?? ''));
                    });
                    ?>
                    <ul class="nav nav-pills mb-3 flex-wrap" role="tablist" aria-label="Statistiche premi per CEDI">
                        <?php foreach ($cediStatsTabs as $cediIndex => $cediStats): ?>
                            <?php
                            $cediStatsCodice = (string) ($cediStats['codice'] ?? '');
                            $cediStatsNome = (string) ($cediStats['nome'] ?? '');
                            $cediTabId = 'premi-stats-cedi-' . $cediIndex;
                            $cediLabel = function_exists('instantwin_cedi_display_label')
                                ? instantwin_cedi_display_label($cediStatsCodice, $cediStatsNome, $cediLabelMap)
                                : ($cediStatsNome !== '' ? $cediStatsNome : $cediStatsCodice);
                            ?>
                            <li class="nav-item" role="presentation">
                                <a class="nav-link <?php echo $cediIndex === 0 ? 'active' : ''; ?>"
                                   id="<?php echo manager_safe_html($cediTabId); ?>-tab"
                                   data-toggle="pill"
                                   href="#<?php echo manager_safe_html($cediTabId); ?>"
                                   role="tab"
                                   aria-controls="<?php echo manager_safe_html($cediTabId); ?>"
                                   aria-selected="<?php echo $cediIndex === 0 ? 'true' : 'false'; ?>">
                                    <?php echo manager_safe_html(trim($cediLabel)); ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <div class="tab-content">
                        <?php foreach ($cediStatsTabs as $cediIndex => $cediStats): ?>
                            <?php
                            $cediStatsCodice = (string) ($cediStats['codice'] ?? '');
                            $cediStatsNome = (string) ($cediStats['nome'] ?? '');
                            $cediTabId = 'premi-stats-cedi-' . $cediIndex;
                            $cediHeading = function_exists('instantwin_cedi_display_label')
                                ? instantwin_cedi_display_label($cediStatsCodice, $cediStatsNome, $cediLabelMap)
                                : ($cediStatsNome !== '' ? $cediStatsNome : $cediStatsCodice);
                            ?>
                            <section class="tab-pane fade <?php echo $cediIndex === 0 ? 'show active' : ''; ?>"
                                     id="<?php echo manager_safe_html($cediTabId); ?>"
                                     role="tabpanel"
                                     aria-labelledby="<?php echo manager_safe_html($cediTabId); ?>-tab">
                                <h6 class="mb-2"><?php echo manager_safe_html($cediHeading); ?></h6>
                                <div class="table-responsive">
                                    <table class="table table-hover table-striped table-bordered mb-0">
                                        <thead>
                                            <tr>
                                                <th>Tipo Premio</th>
                                                <th>Nome Premio</th>
                                                <th>Valore</th>
                                                <th>Assegnati</th>
                                                <th>Disponibili</th>
                                                <th>% Utilizzo</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($cediStats['rows'] as $stat): ?>
                                                <?php
                                                $percentuale = $stat['QUANTITA_TOTALE'] > 0
                                                    ? ($stat['assegnati'] / $stat['QUANTITA_TOTALE']) * 100
                                                    : 0;
                                                $classe = $percentuale > 80 ? 'danger' : ($percentuale > 50 ? 'warning' : 'success');
                                                $tipoLabel = (string) ($stat['TIPO_PREMIO'] ?? '');
                                                ?>
                                                <tr>
                                                    <td>
                                                        <span class="badge badge-success"><?php echo manager_safe_html($tipoLabel); ?></span>
                                                    </td>
                                                    <td><?php echo manager_safe_html($tipoLabel); ?></td>
                                                    <td><strong>&euro;<?php echo number_format((float) $stat['VALORE'], 2); ?></strong></td>
                                                    <td><span class="badge badge-info"><?php echo (int) $stat['assegnati']; ?></span></td>
                                                    <td><?php echo (int) $stat['rimanenti']; ?></td>
                                                    <td>
                                                        <div class="progress" style="height: 20px;">
                                                            <div class="progress-bar bg-<?php echo $classe; ?>" role="progressbar"
                                                                 style="width: <?php echo $percentuale; ?>%"
                                                                 aria-valuenow="<?php echo $percentuale; ?>"
                                                                 aria-valuemin="0" aria-valuemax="100">
                                                                <?php echo number_format($percentuale, 1); ?>%
                                                            </div>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </section>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
<?php if ($useCardWrapper): ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
