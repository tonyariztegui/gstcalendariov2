<?php
/** Blocco UI «premi aggiuntivi» — incluso dal pannello IW personalizzazione (Concorso). */
if (!isset($extraPrizesPlan, $pdvWeightFormCodes, $pdvMeta) || !function_exists('manager_safe_html')) {
    return;
}
$d = 'di' . 'v';
$pdvIwExtraEmbedInTabs = !empty($pdvIwExtraEmbedInTabs);
$pdvIwExtraUseAccordion = !empty($pdvIwExtraUseAccordion);

$renderPrizeForm = static function (array $ep) use ($d, $pdvWeightFormCodes, $pdvMeta): void {
    $epPid = (int) ($ep['prize_id'] ?? 0);
    $epBase = (int) ($ep['base_per_pdv'] ?? 0);
    $epExtra = (int) ($ep['extra_total'] ?? 0);
    $epAlloc = is_array($ep['allocations'] ?? null) ? $ep['allocations'] : [];
    $epTipo = (string) ($ep['tipo_premio'] ?? '');
    $epVal = (string) ($ep['valore'] ?? '');
    $epQty = (int) ($ep['quantita_totale'] ?? 0);
    $epCustom = !empty($ep['is_custom_valid']);
    ?>
    <p class="small mb-2 iw-extra-prize-meta">
        Totale <strong><?php echo $epQty; ?></strong> —
        base/PDV <strong><?php echo $epBase; ?></strong> —
        aggiuntivi <strong class="text-success"><?php echo $epExtra; ?></strong>
        <?php if ($epCustom): ?>
            <span class="badge badge-success ml-1">personalizzato</span>
        <?php else: ?>
            <span class="badge badge-secondary ml-1">proposta (ripartizione uguale)</span>
        <?php endif; ?>
    </p>
    <div class="form-pdv-premio-extra mb-0 iw-extra-form" data-prize-id="<?php echo $epPid; ?>" data-extra-total="<?php echo $epExtra; ?>">
        <<?php echo $d; ?> class="mb-2 iw-extra-filter">
            <label class="sr-only" for="iw-extra-filter-<?php echo $epPid; ?>">Cerca PDV</label>
            <div class="input-group input-group-sm iw-extra-search-group">
                <div class="input-group-prepend">
                    <span class="input-group-text" title="Cerca"><i class="fa fa-search"></i></span>
                </div>
                <input type="search" id="iw-extra-filter-<?php echo $epPid; ?>" class="form-control js-pdv-extra-filter"
                    placeholder="Cerca per codice magazzino (punti_vendita.codice) o nome / indirizzo PDV…"
                    autocomplete="off" aria-describedby="iw-extra-filter-hint-<?php echo $epPid; ?>">
            </div>
            <small id="iw-extra-filter-hint-<?php echo $epPid; ?>" class="form-text text-muted">Filtra le righe della tabella; la somma degli slot include tutti i PDV (anche quelli nascosti).</small>
        </<?php echo $d; ?>>
        <<?php echo $d; ?> class="table-responsive iw-extra-table-wrap">
            <table class="table table-sm table-bordered mb-2 bg-white iw-extra-table">
                <thead>
                    <tr><th>Punto vendita</th><th>Codice</th><th style="min-width:120px">Slot aggiuntivi</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($pdvWeightFormCodes as $wcod): ?>
                        <?php
                            $wm = $pdvMeta[$wcod] ?? null;
                            $wragsoc = is_array($wm) ? trim((string) ($wm['ragsoc'] ?? ($wm['label'] ?? ''))) : '';
                            if ($wragsoc === '') {
                                $wragsoc = $wcod;
                            }
                            $wind = is_array($wm) ? trim((string) ($wm['indirizzo'] ?? '')) : '';
                            $wcitta = is_array($wm) ? trim((string) ($wm['citta'] ?? '')) : '';
                            $wprov = is_array($wm) ? trim((string) ($wm['prov'] ?? '')) : '';
                            $addrLine = $wind;
                            if ($wcitta !== '') {
                                $addrLine .= ($addrLine !== '' ? ', ' : '') . $wcitta;
                            }
                            if ($wprov !== '') {
                                $addrLine .= ($addrLine !== '' ? ' (' : '(') . $wprov . ')';
                            }
                            $ev = (int) ($epAlloc[$wcod] ?? 0);
                            $searchParts = array_filter([$wcod, $wragsoc, $addrLine, $wind, $wcitta, $wprov], static function ($s) {
                                return $s !== null && trim((string) $s) !== '';
                            });
                            $joined = implode(' ', $searchParts);
                            $searchHaystack = function_exists('mb_strtolower')
                                ? mb_strtolower($joined, 'UTF-8')
                                : strtolower($joined);
                        ?>
                        <tr class="js-pdv-extra-row" data-pdv-search="<?php echo manager_safe_html($searchHaystack); ?>">
                            <td class="small">
                                <strong><?php echo manager_safe_html($wragsoc); ?></strong>
                                <?php if ($addrLine !== ''): ?>
                                    <br><span class="text-muted"><?php echo manager_safe_html($addrLine); ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="small iw-extra-code"><?php echo manager_safe_html($wcod); ?></td>
                            <td>
                                <input type="number" class="form-control form-control-sm js-pdv-extra-slot"
                                    name="extras[<?php echo htmlspecialchars($wcod, ENT_QUOTES, 'UTF-8'); ?>]"
                                    value="<?php echo $ev; ?>" min="0" max="<?php echo $epExtra; ?>" step="1">
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </<?php echo $d; ?>>
        <<?php echo $d; ?> class="d-flex flex-wrap align-items-center iw-extra-actions">
            <span class="small text-muted mr-3 iw-extra-sum">Somma: <strong class="js-extra-sum">0</strong> / <?php echo $epExtra; ?></span>
            <button type="button" class="btn btn-outline-secondary btn-sm ml-auto mr-2 js-pdv-extra-reset iw-extra-reset-btn" title="Imposta tutti gli slot aggiuntivi a 0">
                <i class="fa fa-eraser mr-1"></i>Azzera tutti
            </button>
            <button type="button" class="btn btn-success btn-sm js-pdv-extra-save iw-extra-save-btn">
                <i class="fa fa-save mr-1"></i>Salva aggiuntivi e rigenera budget
            </button>
        </<?php echo $d; ?>>
    </div>
    <?php
};

if (!$pdvIwExtraEmbedInTabs): ?>
<<?php echo $d; ?> class="row mb-3 iw-extra-shell-row">
<<?php echo $d; ?> class="col-12">
<<?php echo $d; ?> class="card border-success iw-extra-card">
<<?php echo $d; ?> class="card-header iw-extra-card-header">
<h5 class="card-title mb-0 text-success">
<i class="fa fa-plus-circle mr-2"></i>Premi aggiuntivi (ripartizione personalizzata)
</h5>
</<?php echo $d; ?>>
<<?php echo $d; ?> class="card-body">
<?php endif; ?>

<p class="text-muted small mb-3 iw-extra-intro">
    Se la quantità totale non si divide in parti uguali tra i PDV attivi, ogni magazzino ha la
    <strong>quota base</strong> e restano <strong>slot aggiuntivi</strong> da ripartire.
    Se non salvi, gli aggiuntivi seguono la <strong>ripartizione uguale</strong> tra i PDV.
</p>

<?php if ($pdvIwExtraUseAccordion): ?>
<<?php echo $d; ?> class="accordion iw-extra-accordion" id="iw-extra-prizes-accordion">
    <?php $epIdx = 0; foreach ($extraPrizesPlan as $ep): ?>
        <?php
            $epIdx++;
            $epPid = (int) ($ep['prize_id'] ?? 0);
            $epTipo = (string) ($ep['tipo_premio'] ?? '');
            $epVal = (string) ($ep['valore'] ?? '');
            $epExtra = (int) ($ep['extra_total'] ?? 0);
            $epCustom = !empty($ep['is_custom_valid']);
            $collapseId = 'iw-extra-collapse-' . $epPid;
            $headingId = 'iw-extra-heading-' . $epPid;
            $isFirst = $epIdx === 1;
        ?>
        <<?php echo $d; ?> class="card border mb-2 iw-extra-prize-card">
            <<?php echo $d; ?> class="card-header py-2 px-3 iw-extra-prize-header" id="<?php echo manager_safe_html($headingId); ?>">
                <h6 class="mb-0">
                    <button class="btn btn-link btn-block text-left p-0 iw-extra-prize-toggle<?php echo $isFirst ? '' : ' collapsed'; ?>" type="button"
                        data-toggle="collapse" data-target="#<?php echo manager_safe_html($collapseId); ?>"
                        aria-expanded="<?php echo $isFirst ? 'true' : 'false'; ?>"
                        aria-controls="<?php echo manager_safe_html($collapseId); ?>">
                        Premio <span class="badge badge-secondary">ID <?php echo $epPid; ?></span>
                        <?php if ($epTipo !== ''): ?> — <?php echo manager_safe_html($epTipo); ?><?php endif; ?>
                        <?php if ($epVal !== ''): ?> (&euro;<?php echo manager_safe_html($epVal); ?>)<?php endif; ?>
                        <span class="badge badge-success ml-1"><?php echo $epExtra; ?> aggiuntivi</span>
                        <?php if ($epCustom): ?>
                            <span class="badge badge-success ml-1">personalizzato</span>
                        <?php endif; ?>
                        <i class="fa fa-chevron-down iw-extra-chevron"></i>
                    </button>
                </h6>
            </<?php echo $d; ?>>
            <<?php echo $d; ?> id="<?php echo manager_safe_html($collapseId); ?>" class="collapse<?php echo $isFirst ? ' show' : ''; ?>"
                aria-labelledby="<?php echo manager_safe_html($headingId); ?>" data-parent="#iw-extra-prizes-accordion">
                <<?php echo $d; ?> class="card-body pt-2 pb-3 iw-extra-prize-body">
                    <?php $renderPrizeForm($ep); ?>
                </<?php echo $d; ?>>
            </<?php echo $d; ?>>
        </<?php echo $d; ?>>
    <?php endforeach; ?>
</<?php echo $d; ?>>
<?php else: ?>
    <?php foreach ($extraPrizesPlan as $ep): ?>
        <?php
            $epPid = (int) ($ep['prize_id'] ?? 0);
            $epTipo = (string) ($ep['tipo_premio'] ?? '');
            $epVal = (string) ($ep['valore'] ?? '');
        ?>
        <<?php echo $d; ?> class="border rounded p-3 mb-3 bg-light iw-extra-prize-card">
            <h6 class="mb-2">
                Premio <span class="badge badge-secondary">ID <?php echo $epPid; ?></span>
                <?php if ($epTipo !== ''): ?> — <?php echo manager_safe_html($epTipo); ?><?php endif; ?>
                <?php if ($epVal !== ''): ?> (&euro;<?php echo manager_safe_html($epVal); ?>)<?php endif; ?>
            </h6>
            <?php $renderPrizeForm($ep); ?>
        </<?php echo $d; ?>>
    <?php endforeach; ?>
<?php endif; ?>

<?php if (!$pdvIwExtraEmbedInTabs): ?>
</<?php echo $d; ?>>
</<?php echo $d; ?>>
</<?php echo $d; ?>>
</<?php echo $d; ?>>
<?php endif; ?>
