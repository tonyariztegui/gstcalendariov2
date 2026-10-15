<?php

include('_header.php');
include('res/inc/utenze.php');
include('res/inc/header.php');
include('res/inc/sanitize.inc.php');

if (!isset($contest_config)) {
    require_once __DIR__ . '/../../app/inc/config.php';
}

require_once __DIR__ . '/../../app/inc/stats_pdv_manager.inc.php';
require_once __DIR__ . '/../res/inc/pdv_display.inc.php';

if (!function_exists('manager_safe_html')) {
    function manager_safe_html(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

$defaultFrom = (string) ($contest_config['start_date'] ?? date('Y-m-d', strtotime('-30 days')));
$defaultTo = (string) ($contest_config['end_date'] ?? date('Y-m-d'));

$from = isset($_GET['from']) ? trim((string) $_GET['from']) : $defaultFrom;
$to = isset($_GET['to']) ? trim((string) $_GET['to']) : $defaultTo;
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) {
    $from = $defaultFrom;
}
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
    $to = $defaultTo;
}
if ($from > $to) {
    [$from, $to] = [$to, $from];
}

$filterPdv = isset($_GET['pdv']) ? trim((string) $_GET['pdv']) : '';
$filterCedi = isset($_GET['cedi']) ? trim((string) $_GET['cedi']) : '';
global $content_config;
$cediConfigForLabels = is_array($content_config ?? null) ? $content_config : (is_array($contest_config ?? null) ? $contest_config : null);
$cediSelectOptions = stats_pdv_load_cedi_options($connection, $cediConfigForLabels);
$cediRagsocMap = stats_pdv_load_cedi_ragsoc_map($connection);
if ($filterCedi !== '' && !isset($cediSelectOptions[$filterCedi])) {
    $filterCedi = '';
}

$pdvMetaAll = stats_pdv_load_meta($connection);
$pdvSelectOptions = [];
foreach ($pdvMetaAll as $cod => $meta) {
    $pdvSelectOptions[$cod] = manager_pdv_select_label($meta);
}
uasort($pdvSelectOptions, static function (string $a, string $b): int {
    return strcasecmp($a, $b);
});

if ($filterPdv !== '' && !isset($pdvSelectOptions[$filterPdv])) {
    $filterPdv = '';
}

$report = stats_pdv_build_report($connection, $from, $to, [
    'pdv_codice' => $filterPdv,
    'cedi_codice' => $filterCedi,
]);

$err = isset($report['error']) ? (string) $report['error'] : '';
$summary = $report['summary'] ?? [];
$byDay = $report['by_day'] ?? [];
$byHour = $report['by_hour'] ?? [];
$byPdv = $report['by_pdv'] ?? [];
$byCedi = [];
foreach ($byPdv as $pdvRow) {
    $cediCode = trim((string) ($pdvRow['codice_cedi'] ?? ''));
    $cediKey = $cediCode !== '' ? $cediCode : '_unknown';
    if (!isset($byCedi[$cediKey])) {
        $cediLabel = $cediCode !== ''
            ? trim((string) ($cediRagsocMap[$cediCode] ?? ''))
            : 'CEDI non associato';
        if ($cediCode !== '' && $cediLabel === '') {
            $cediLabel = '—';
        }
        $byCedi[$cediKey] = [
            'cod' => $cediCode,
            'label' => $cediLabel,
            'part' => 0,
            'wins' => 0,
        ];
    }
    $byCedi[$cediKey]['part'] += (int) ($pdvRow['part'] ?? 0);
    $byCedi[$cediKey]['wins'] += (int) ($pdvRow['wins'] ?? 0);
}
usort($byCedi, static function (array $a, array $b): int {
    return $b['part'] <=> $a['part'];
});

$exportQuery = [
    'from' => $from,
    'to' => $to,
];
if ($filterPdv !== '') {
    $exportQuery['pdv'] = $filterPdv;
}
if ($filterCedi !== '') {
    $exportQuery['cedi'] = $filterCedi;
}
$exportExcelUrl = 'res/inc/export_stats_pdv_excel.php?' . http_build_query($exportQuery);

$chartDayLabels = json_encode(array_column($byDay, 'date'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$chartDayPart = json_encode(array_map('intval', array_column($byDay, 'part')), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$chartDayWins = json_encode(array_map('intval', array_column($byDay, 'wins')), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$chartHourLabels = json_encode(array_map(static fn($r) => sprintf('%02d:00', (int) ($r['hour'] ?? 0)), $byHour), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$chartHourPart = json_encode(array_map('intval', array_column($byHour, 'part')), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$chartHourWins = json_encode(array_map('intval', array_column($byHour, 'wins')), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

$premiSubnavActive = 'stats';
$_title = 'Statistiche PDV';

$scripts = <<<'HTML'
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<style>
.stats-pdv-kpi .kpi-value { font-size: 1.5rem; font-weight: 700; }
.stats-pdv-chart-card { background: #fff; border: 1px solid #e9ecef; border-radius: 8px; padding: 1rem 1.25rem; margin-bottom: 1rem; }
.select2-container { width: 100% !important; }
{$statsPdvTableStyles}
</style>
<script>
jQuery(function ($) {
    var $pdv = $('#stats-pdv-filter');
    if ($pdv.length && $.fn.select2) {
        $pdv.select2({
            theme: 'bootstrap-5',
            width: '100%',
            placeholder: 'Tutti i punti vendita',
            allowClear: true
        });
    }
    var dayLabels = {$chartDayLabels};
    if (dayLabels.length && document.getElementById('statsChartDay')) {
        new Chart(document.getElementById('statsChartDay'), {
            type: 'bar',
            data: {
                labels: dayLabels,
                datasets: [
                    { label: 'Partecipazioni', data: {$chartDayPart}, backgroundColor: 'rgba(13,110,253,0.75)' },
                    { label: 'Vincite', data: {$chartDayWins}, backgroundColor: 'rgba(25,135,84,0.85)' }
                ]
            },
            options: { responsive: true, scales: { y: { beginAtZero: true } } }
        });
    }
    var hourLabels = {$chartHourLabels};
    if (hourLabels.length && document.getElementById('statsChartHour')) {
        new Chart(document.getElementById('statsChartHour'), {
            type: 'line',
            data: {
                labels: hourLabels,
                datasets: [
                    {
                        label: 'Partecipazioni',
                        data: {$chartHourPart},
                        borderColor: '#0d6efd',
                        backgroundColor: 'rgba(13,110,253,0.12)',
                        fill: true,
                        tension: 0.25,
                        yAxisID: 'y'
                    },
                    {
                        label: 'Vincite',
                        data: {$chartHourWins},
                        borderColor: '#198754',
                        backgroundColor: 'rgba(25,135,84,0.08)',
                        fill: false,
                        tension: 0.25,
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                interaction: { mode: 'index', intersect: false },
                scales: {
                    y: { type: 'linear', position: 'left', beginAtZero: true },
                    y1: { type: 'linear', position: 'right', beginAtZero: true, grid: { drawOnChartArea: false } }
                }
            }
        });
    }
});
</script>
HTML;

$statsPdvTableStyles = manager_stats_pdv_table_styles();
$scripts = str_replace(
    ['{$chartDayLabels}', '{$chartDayPart}', '{$chartDayWins}', '{$chartHourLabels}', '{$chartHourPart}', '{$chartHourWins}', '{$statsPdvTableStyles}'],
    [$chartDayLabels ?: '[]', $chartDayPart ?: '[]', $chartDayWins ?: '[]', $chartHourLabels ?: '[]', $chartHourPart ?: '[]', $chartHourWins ?: '[]', $statsPdvTableStyles],
    $scripts
);

?>
<div class="app-main__outer">
<div class="app-main__inner">
    <?php include __DIR__ . '/inc/premi_iw_subnav.php'; ?>

    <div class="row">
        <div class="col-12">
            <div class="card mb-3">
                <div class="card-header d-flex flex-wrap justify-content-between align-items-center">
                    <h5 class="card-title mb-0"><i class="fa fa-filter mr-2"></i>Filtri</h5>
                    <a href="<?php echo manager_safe_html($exportExcelUrl); ?>" class="btn btn-success btn-sm mt-2 mt-md-0">
                        <i class="fa fa-file-excel-o mr-1"></i>Download Excel
                    </a>
                </div>
                <div class="card-body">
                    <form method="get" action="statistiche-pdv" class="form-row align-items-end">
                        <div class="form-group col-md-2">
                            <label for="from">Data inizio</label>
                            <input type="date" class="form-control" id="from" name="from" value="<?php echo manager_safe_html($from); ?>">
                        </div>
                        <div class="form-group col-md-2">
                            <label for="to">Data fine</label>
                            <input type="date" class="form-control" id="to" name="to" value="<?php echo manager_safe_html($to); ?>">
                        </div>
                        <div class="form-group col-md-3">
                            <label for="stats-pdv-filter">Punto vendita</label>
                            <select class="form-control" id="stats-pdv-filter" name="pdv" data-placeholder="Tutti i punti vendita">
                                <option value=""></option>
                                <?php foreach ($pdvSelectOptions as $cod => $lab): ?>
                                    <option value="<?php echo manager_safe_html($cod); ?>"<?php echo $filterPdv === $cod ? ' selected' : ''; ?>><?php echo manager_safe_html($lab); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php if ($cediSelectOptions !== []): ?>
                        <div class="form-group col-md-3">
                            <label for="stats-cedi-filter">Codice CEDI</label>
                            <select class="form-control" id="stats-cedi-filter" name="cedi">
                                <option value="">Tutti i CEDI</option>
                                <?php foreach ($cediSelectOptions as $cod => $lab): ?>
                                    <option value="<?php echo manager_safe_html($cod); ?>"<?php echo $filterCedi === $cod ? ' selected' : ''; ?>><?php echo manager_safe_html($lab); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php endif; ?>
                        <div class="form-group col-md-2">
                            <label class="d-none d-md-block">&nbsp;</label>
                            <button type="submit" class="btn btn-primary btn-block">Aggiorna analisi</button>
                        </div>
                    </form>
                    <p class="text-muted small mb-0 mt-2">
                        Aggregati su <code>data_partecipazione</code> nel periodo<?php echo $cediSelectOptions !== [] ? ' e sul <code>codice_cedi</code> del punto vendita' : ''; ?>.
                    </p>
                </div>
            </div>

            <?php if ($err !== ''): ?>
                <div class="alert alert-danger"><?php echo manager_safe_html($err); ?></div>
            <?php else: ?>

            <div class="row stats-pdv-kpi mb-3">
                <div class="col-6 col-md-3 mb-2">
                    <div class="card p-3 h-100">
                        <div class="text-muted small">Partecipazioni</div>
                        <div class="kpi-value"><?php echo number_format((int) ($summary['participations'] ?? 0), 0, ',', '.'); ?></div>
                    </div>
                </div>
                <div class="col-6 col-md-3 mb-2">
                    <div class="card p-3 h-100">
                        <div class="text-muted small">Vincite</div>
                        <div class="kpi-value text-success"><?php echo number_format((int) ($summary['wins'] ?? 0), 0, ',', '.'); ?></div>
                    </div>
                </div>
                <div class="col-6 col-md-3 mb-2">
                    <div class="card p-3 h-100">
                        <div class="text-muted small">Win rate</div>
                        <div class="kpi-value"><?php echo manager_safe_html(number_format((float) ($summary['win_rate'] ?? 0), 2, ',', '.')); ?>%</div>
                    </div>
                </div>
                <div class="col-6 col-md-3 mb-2">
                    <div class="card p-3 h-100">
                        <div class="text-muted small">PDV attivi / giorni</div>
                        <div class="kpi-value" style="font-size:1.25rem"><?php echo (int) ($summary['pdv_with_activity'] ?? 0); ?> / <?php echo (int) ($summary['days_with_activity'] ?? 0); ?></div>
                    </div>
                </div>
                <?php if ($cediSelectOptions !== []): ?>
                <div class="col-6 col-md-3 mb-2">
                    <div class="card p-3 h-100">
                        <div class="text-muted small">CEDI con attività</div>
                        <div class="kpi-value" style="font-size:1.25rem"><?php echo count($byCedi); ?></div>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <div class="row">
                <div class="col-lg-7">
                    <div class="stats-pdv-chart-card">
                        <h6 class="mb-3">Partecipazioni e vincite per giorno</h6>
                        <canvas id="statsChartDay" height="110"></canvas>
                    </div>
                </div>
                <div class="col-lg-5">
                    <div class="stats-pdv-chart-card">
                        <h6 class="mb-3">Distribuzione oraria (tutto il periodo)</h6>
                        <canvas id="statsChartHour" height="140"></canvas>
                        <p class="small text-muted mb-2 mt-2">Blu = partecipazioni · Verde = vincite (asse destro).</p>
                        <div class="table-responsive" style="max-height: 200px; overflow-y: auto;">
                            <table class="table table-sm table-striped mb-0 small">
                                <thead class="thead-light"><tr><th>Ora</th><th class="text-right">Part.</th><th class="text-right">Vincite</th><th class="text-right">Win %</th></tr></thead>
                                <tbody>
                                <?php foreach ($byHour as $hr): ?>
                                    <?php
                                    $hp = (int) ($hr['part'] ?? 0);
                                    $hw = (int) ($hr['wins'] ?? 0);
                                    $hwr = $hp > 0 ? round(100.0 * $hw / $hp, 2) : 0.0;
                                    ?>
                                    <tr>
                                        <td><?php echo sprintf('%02d:00', (int) ($hr['hour'] ?? 0)); ?></td>
                                        <td class="text-right"><?php echo number_format($hp, 0, ',', '.'); ?></td>
                                        <td class="text-right<?php echo $hw > 0 ? ' text-success font-weight-bold' : ''; ?>"><?php echo number_format($hw, 0, ',', '.'); ?></td>
                                        <td class="text-right"><?php echo $hp > 0 ? manager_safe_html((string) $hwr) . '%' : '—'; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header">
                    <h5 class="card-title mb-0">Partecipazioni e vincite per punto vendita</h5>
                </div>
                <div class="card-body p-0">
                    <?php if ($byPdv === []): ?>
                        <p class="text-muted p-3 mb-0">Nessun dato nel periodo selezionato.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm table-striped mb-0 stats-pdv-pdv-table">
                                <colgroup>
                                    <col class="col-pdv">
                                    <col class="col-part">
                                    <col class="col-win">
                                    <col class="col-rate">
                                </colgroup>
                                <thead class="thead-light">
                                    <tr>
                                        <th>Punto Vendita</th>
                                        <th class="text-right">Partecipazioni</th>
                                        <th class="text-right">Vincite</th>
                                        <th class="text-right">% vincite / part.</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $tPart = 0;
                                    $tWin = 0;
                                    foreach ($byPdv as $row):
                                        $p = (int) ($row['part'] ?? 0);
                                        $w = (int) ($row['wins'] ?? 0);
                                        $tPart += $p;
                                        $tWin += $w;
                                        $pct = $p > 0 ? round(100 * $w / $p, 1) : 0.0;
                                    ?>
                                    <tr>
                                        <td><?php echo manager_render_pdv_cell_html($row, 'compact'); ?></td>
                                        <td class="text-right font-weight-bold"><?php echo $p; ?></td>
                                        <td class="text-right font-weight-bold"><?php echo $w; ?></td>
                                        <td class="text-right text-muted"><?php echo $pct; ?>%</td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot class="bg-light font-weight-bold">
                                    <tr>
                                        <td>Totali</td>
                                        <td class="text-right"><?php echo (int) $tPart; ?></td>
                                        <td class="text-right"><?php echo (int) $tWin; ?></td>
                                        <td class="text-right"><?php echo $tPart > 0 ? round(100 * $tWin / $tPart, 1) : 0; ?>%</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($cediSelectOptions !== []): ?>
            <div class="card mb-3">
                <div class="card-header">
                    <h5 class="card-title mb-0">Partecipazioni e vincite per CEDI</h5>
                </div>
                <div class="card-body p-0">
                    <?php if ($byCedi === []): ?>
                        <p class="text-muted p-3 mb-0">Nessun dato nel periodo selezionato.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm table-striped mb-0">
                                <thead class="thead-light">
                                    <tr>
                                        <th>Ragione sociale</th>
                                        <th>Codice CEDI</th>
                                        <th class="text-right">Partecipazioni</th>
                                        <th class="text-right">Vincite</th>
                                        <th class="text-right">% vincite / part.</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $tPartCedi = 0;
                                    $tWinCedi = 0;
                                    foreach ($byCedi as $row):
                                        $p = (int) ($row['part'] ?? 0);
                                        $w = (int) ($row['wins'] ?? 0);
                                        $tPartCedi += $p;
                                        $tWinCedi += $w;
                                        $pct = $p > 0 ? round(100 * $w / $p, 1) : 0.0;
                                    ?>
                                    <tr>
                                        <td><?php echo manager_safe_html((string) ($row['label'] ?? '')); ?></td>
                                        <td style="font-family: ui-monospace, monospace;"><?php echo manager_safe_html((string) ($row['cod'] ?? '')); ?></td>
                                        <td class="text-right font-weight-bold"><?php echo $p; ?></td>
                                        <td class="text-right font-weight-bold"><?php echo $w; ?></td>
                                        <td class="text-right text-muted"><?php echo $pct; ?>%</td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot class="bg-light font-weight-bold">
                                    <tr>
                                        <td colspan="2">Totali</td>
                                        <td class="text-right"><?php echo (int) $tPartCedi; ?></td>
                                        <td class="text-right"><?php echo (int) $tWinCedi; ?></td>
                                        <td class="text-right"><?php echo $tPartCedi > 0 ? round(100 * $tWinCedi / $tPartCedi, 1) : 0; ?>%</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php endif; ?>
        </div>
    </div>
</div>
</div>

<?php include('_footer.php'); ?>
