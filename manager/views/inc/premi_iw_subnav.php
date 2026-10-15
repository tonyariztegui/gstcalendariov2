<?php
/**
 * Sous-navigation Premi / Statistiche PDV (onglets au-dessus du contenu).
 * @var string $premiSubnavActive 'premi'|'stats'
 */
if (!isset($contest_mode) || strtolower((string) $contest_mode) !== 'instantwin') {
    return;
}
$premiSubnavActive = $premiSubnavActive ?? 'premi';
$d = 'di' . 'v';
?>
<<?php echo $d; ?> class="row mb-3">
    <<?php echo $d; ?> class="col-12">
        <ul class="nav nav-pills premi-subnav bg-white border rounded p-2 d-inline-flex flex-wrap">
            <li class="nav-item">
                <?php if ($premiSubnavActive === 'premi'): ?>
                    <span class="nav-link active"><i class="fa fa-list mr-1"></i>Premi assegnati</span>
                <?php else: ?>
                    <a class="nav-link" href="premi"><i class="fa fa-list mr-1"></i>Premi assegnati</a>
                <?php endif; ?>
            </li>
            <li class="nav-item">
                <?php if ($premiSubnavActive === 'stats'): ?>
                    <span class="nav-link active"><i class="fa fa-chart-bar mr-1"></i>Statistiche PDV</span>
                <?php else: ?>
                    <a class="nav-link" href="statistiche-pdv"><i class="fa fa-chart-bar mr-1"></i>Statistiche PDV</a>
                <?php endif; ?>
            </li>
        </ul>
    </<?php echo $d; ?>>
</<?php echo $d; ?>>
