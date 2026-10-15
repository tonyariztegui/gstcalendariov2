<?php
/**
 * Matrice premi per Ce.Di. + editor Ce.Di. / importi personalizzati.
 * Richiede: $premiMatrix, $premiMatrixWeeks, $cediDefsUiList, $cediValuesUiList, $cediMatrixConfig
 */
if (!function_exists('instantwin_cedi_definitions')) {
    require_once __DIR__ . '/../../../app/logic/instantwin_cedi/cedi_core.php';
}

if (!function_exists('manager_safe_html')) {
    function manager_safe_html(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

$cediDefsUiList = is_array($cediDefsUiList ?? null) ? $cediDefsUiList : instantwin_cedi_default_definitions_ui();
$cediPrizeDefsUiList = is_array($cediPrizeDefsUiList ?? null) ? $cediPrizeDefsUiList : [['value' => '', 'tipo' => '']];
$cediMatrixConfig = is_array($cediMatrixConfig ?? null) ? $cediMatrixConfig : [];
$premiMatrixWeekPlanErrors = is_array($premiMatrixWeekPlanErrors ?? null) ? $premiMatrixWeekPlanErrors : [];

$cedis = [];
foreach ($cediDefsUiList as $item) {
    if (!is_array($item)) {
        continue;
    }
    $code = trim((string) ($item['code'] ?? ''));
    $label = trim((string) ($item['label'] ?? ''));
    if ($code === '') {
        continue;
    }
    $cedis[$code] = ['code' => $code, 'label' => $label !== '' ? $label : $code];
}

$values = [];
foreach ($cediPrizeDefsUiList as $defRow) {
    if (!is_array($defRow)) {
        continue;
    }
    $s = str_replace([',', ' '], ['.', ''], trim((string) ($defRow['value'] ?? '')));
    if ($s === '' || !is_numeric($s)) {
        continue;
    }
    $v = round((float) $s, 2);
    if ($v > 0) {
        $values[] = $v;
    }
}
$values = array_values(array_unique($values, SORT_REGULAR));
rsort($values, SORT_NUMERIC);

$matrix = is_array($premiMatrix ?? null) ? $premiMatrix : instantwin_cedi_empty_matrix($cediMatrixConfig);
$weekCount = isset($premiMatrixWeeks) ? max(1, (int) $premiMatrixWeeks) : 6;
$weeksCustom = !empty($premiMatrixWeeksCustom);
$weekPlan = is_array($premiMatrixWeekPlan ?? null) ? $premiMatrixWeekPlan : null;
if ($weekPlan === null && $cedis !== [] && $values !== []) {
    $weekPlan = instantwin_cedi_build_week_plan_from_matrix($matrix, $weekCount);
}

$grandTotal = 0;
$cediTotals = array_fill_keys(array_keys($cedis), 0);
$valueTotals = [];
foreach ($values as $v) {
    $vk = instantwin_cedi_value_key($v);
    $valueTotals[$vk] = 0;
    foreach ($cedis as $code => $_) {
        $q = (int) ($matrix[$vk][$code] ?? 0);
        $grandTotal += $q;
        $cediTotals[$code] += $q;
        $valueTotals[$vk] += $q;
    }
}
$schemaReady = ($cedis !== [] && $values !== []);
?>

<div class="premi-cedi-matrix-section mt-3" id="premi-cedi-matrix-section">
    <div class="section-subtitle"><i class="fa fa-table mr-2"></i>Ripartizione premi per Ce.Di.</div>
    <p class="small text-muted mb-3">Configura i premi in <strong>4 passi</strong>. Apri ogni sezione per compilare.</p>

    <div class="cedi-wizard-accordion iw-env-accordion" id="cedi-wizard-accordion">

        <div class="card iw-env-accordion-card mb-2">
            <div class="card-header iw-env-accordion-header p-0" id="cedi-wizard-heading-1">
                <button type="button" class="btn btn-link iw-env-accordion-toggle w-100 px-3 py-2 text-left" data-toggle="collapse" data-target="#cedi-wizard-step-1" aria-expanded="true" aria-controls="cedi-wizard-step-1">
                    <span><span class="badge badge-primary mr-2">1</span> Ce.Di. (magazzini)</span>
                    <i class="fa fa-chevron-down iw-env-chevron"></i>
                </button>
            </div>
            <div id="cedi-wizard-step-1" class="collapse show" aria-labelledby="cedi-wizard-heading-1" data-parent="#cedi-wizard-accordion">
                <div class="card-body py-3 iw-env-accordion-body">
                    <p class="small text-muted mb-2">
                        <strong>Codice</strong> = numero (1, 2, 3…). <strong>Nome</strong> = etichetta in matrice (es. A, Nord, Meloni).
                    </p>
                    <div id="cedi-definitions-list">
                        <?php foreach ($cediDefsUiList as $i => $defRow): ?>
                            <div class="form-row align-items-end mb-2 js-cedi-def-row">
                                <div class="col-3">
                                    <label class="small mb-0">Codice</label>
                                    <input type="text" class="form-control form-control-sm js-cedi-code-input" name="cedi_definitions[<?php echo (int) $i; ?>][code]" maxlength="16" inputmode="numeric" pattern="[0-9]*" value="<?php echo manager_safe_html((string) ($defRow['code'] ?? '')); ?>" placeholder="1">
                                </div>
                                <div class="col">
                                    <label class="small mb-0">Nome</label>
                                    <input type="text" class="form-control form-control-sm" name="cedi_definitions[<?php echo (int) $i; ?>][label]" maxlength="128" value="<?php echo manager_safe_html((string) ($defRow['label'] ?? '')); ?>" placeholder="es. A">
                                </div>
                                <div class="col-auto pb-1">
                                    <button type="button" class="btn btn-outline-danger btn-sm js-cedi-def-remove" title="Rimuovi"><i class="fa fa-trash"></i></button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" class="btn btn-outline-primary btn-sm mt-1" id="cedi-def-add"><i class="fa fa-plus mr-1"></i>Aggiungi Ce.Di.</button>
                </div>
            </div>
        </div>

        <div class="card iw-env-accordion-card mb-2">
            <div class="card-header iw-env-accordion-header p-0" id="cedi-wizard-heading-2">
                <button type="button" class="btn btn-link iw-env-accordion-toggle w-100 px-3 py-2 text-left collapsed" data-toggle="collapse" data-target="#cedi-wizard-step-2" aria-expanded="false" aria-controls="cedi-wizard-step-2">
                    <span><span class="badge badge-primary mr-2">2</span> Importi premio (€)</span>
                    <i class="fa fa-chevron-down iw-env-chevron"></i>
                </button>
            </div>
            <div id="cedi-wizard-step-2" class="collapse" aria-labelledby="cedi-wizard-heading-2" data-parent="#cedi-wizard-accordion">
                <div class="card-body py-3 iw-env-accordion-body">
                    <p class="small text-muted mb-2">
                        <strong>Importo €</strong> = valore del coupon. <strong>Tipo premio</strong> = etichetta in tabella <code>premi</code> (vuoto → «Gift card N euro»).
                    </p>
                    <div id="cedi-prize-values-list">
                        <?php foreach ($cediPrizeDefsUiList as $i => $defRow): ?>
                            <div class="form-row align-items-end mb-2 js-cedi-value-row">
                                <div class="col-4">
                                    <label class="small mb-0">€</label>
                                    <input type="text" inputmode="decimal" class="form-control form-control-sm js-cedi-prize-value-input" name="cedi_prize_definitions[<?php echo (int) $i; ?>][value]" value="<?php echo manager_safe_html((string) ($defRow['value'] ?? '')); ?>" placeholder="50">
                                </div>
                                <div class="col">
                                    <label class="small mb-0">Tipo premio</label>
                                    <input type="text" class="form-control form-control-sm js-cedi-prize-tipo-input" name="cedi_prize_definitions[<?php echo (int) $i; ?>][tipo]" maxlength="255" value="<?php echo manager_safe_html((string) ($defRow['tipo'] ?? '')); ?>" placeholder="Gift card 50 euro">
                                </div>
                                <div class="col-auto pb-1">
                                    <button type="button" class="btn btn-outline-danger btn-sm js-cedi-value-remove" title="Rimuovi"><i class="fa fa-trash"></i></button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" class="btn btn-outline-primary btn-sm mt-1" id="cedi-value-add"><i class="fa fa-plus mr-1"></i>Aggiungi importo</button>
                </div>
            </div>
        </div>

        <div class="alert alert-info mb-2 <?php echo $schemaReady ? 'd-none' : ''; ?>" id="cedi-schema-hint">
            Completa il <strong>passo 2</strong> (almeno un importo €) per aprire la matrice quantità.
        </div>

        <div class="card iw-env-accordion-card mb-2 <?php echo $schemaReady ? '' : 'd-none'; ?>" id="cedi-step-matrix-card">
            <div class="card-header iw-env-accordion-header p-0" id="cedi-wizard-heading-3">
                <button type="button" class="btn btn-link iw-env-accordion-toggle w-100 px-3 py-2 text-left collapsed" data-toggle="collapse" data-target="#cedi-wizard-step-3" aria-expanded="false" aria-controls="cedi-wizard-step-3">
                    <span><span class="badge badge-primary mr-2">3</span> Matrice quantità (totali concorso)</span>
                    <i class="fa fa-chevron-down iw-env-chevron"></i>
                </button>
            </div>
            <div id="cedi-wizard-step-3" class="collapse" aria-labelledby="cedi-wizard-heading-3" data-parent="#cedi-wizard-accordion">
                <div class="card-body py-3 iw-env-accordion-body">
                    <p class="small text-muted mb-2">Indica <strong>quante gift card</strong> di ogni importo assegnare a ogni Ce.Di.</p>
                    <div id="cedi-matrix-totale-mount"></div>
                    <p class="small mb-0 mt-2">
                        Totale coupon: <strong id="premi-matrix-grand-total-footer">0</strong>
                    </p>
                </div>
            </div>
        </div>

        <div class="card iw-env-accordion-card mb-2 <?php echo $schemaReady ? '' : 'd-none'; ?>" id="cedi-step-week-card">
            <div class="card-header iw-env-accordion-header p-0" id="cedi-wizard-heading-4">
                <button type="button" class="btn btn-link iw-env-accordion-toggle w-100 px-3 py-2 text-left collapsed" data-toggle="collapse" data-target="#cedi-wizard-step-4" aria-expanded="false" aria-controls="cedi-wizard-step-4">
                    <span><span class="badge badge-secondary mr-2">4</span> Pianificazione settimanale <span class="text-muted small font-weight-normal">(opzionale)</span></span>
                    <i class="fa fa-chevron-down iw-env-chevron"></i>
                </button>
            </div>
            <div id="cedi-wizard-step-4" class="collapse" aria-labelledby="cedi-wizard-heading-4" data-parent="#cedi-wizard-accordion">
                <div class="card-body py-3 iw-env-accordion-body">
                    <p class="small text-muted mb-2">
                        Di default la ripartizione settimanale è <strong>automatica</strong> su
                        <strong><span class="premi-matrix-week-count-label"><?php echo (int) $weekCount; ?></span> settimane</strong>
                        (<code>premi_budget_settimana</code>). Personalizza solo se necessario.
                    </p>
                    <div class="form-check mb-2">
                        <input type="checkbox" class="form-check-input" name="premi_matrix_weeks_custom" id="premi_matrix_weeks_custom" value="1"
                            <?php echo $weeksCustom ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="premi_matrix_weeks_custom">
                            <strong>Personalizza</strong> la ripartizione settimanale
                        </label>
                    </div>
                    <?php if (!empty($premiMatrixWeekPlanErrors) && is_array($premiMatrixWeekPlanErrors)): ?>
                        <div class="alert alert-warning py-2 small mb-2">
                            <ul class="mb-0 pl-3">
                                <?php foreach ($premiMatrixWeekPlanErrors as $weekErr): ?>
                                    <li><?php echo manager_safe_html((string) $weekErr); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    <div id="cedi-week-custom-panel" class="<?php echo $weeksCustom ? '' : 'd-none'; ?>">
                        <div class="mb-2 btn-group btn-group-sm flex-wrap" id="premi-week-repartition-actions" role="group">
                            <button type="button" class="btn btn-outline-secondary js-premi-week-mode-auto">
                                <i class="fa fa-th mr-1"></i>Per cella (auto)
                            </button>
                            <button type="button" class="btn btn-outline-primary js-premi-week-mode-balanced">
                                <i class="fa fa-balance-scale mr-1"></i>Equilibrio settimane
                            </button>
                        </div>
                        <div id="premi-week-planner-status" class="small mb-2"></div>
                        <div id="premi-week-planner"
                            data-week-count="<?php echo (int) $weekCount; ?>"
                            data-custom="<?php echo $weeksCustom ? '1' : '0'; ?>"
                            data-plan="<?php echo manager_safe_html(json_encode($weekPlan, JSON_UNESCAPED_UNICODE) ?: '{}'); ?>"></div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
(function () {
    var defList = document.getElementById('cedi-definitions-list');
    var valList = document.getElementById('cedi-prize-values-list');
    var totaleMount = document.getElementById('cedi-matrix-totale-mount');
    var stepMatrixCard = document.getElementById('cedi-step-matrix-card');
    var stepWeekCard = document.getElementById('cedi-step-week-card');
    var schemaHint = document.getElementById('cedi-schema-hint');
    var grandTotalFooter = document.getElementById('premi-matrix-grand-total-footer');
    var weeksCustomCb = document.getElementById('premi_matrix_weeks_custom');
    var weeksCustomPanel = document.getElementById('cedi-week-custom-panel');
    var initialMatrix = <?php echo json_encode($matrix, JSON_UNESCAPED_UNICODE) ?: '{}'; ?>;
    if (!defList || !valList || !totaleMount) {
        return;
    }

    function parseAmount(raw) {
        var s = String(raw).replace(/\s/g, '').replace(',', '.');
        if (s === '' || !/^\d+(\.\d+)?$/.test(s)) {
            return null;
        }
        var v = parseFloat(s);
        if (!(v > 0) || v > 1000000) {
            return null;
        }
        return String(Math.round(v));
    }

    function readSchemaFromForm() {
        var cedis = [];
        var seenCodes = {};
        defList.querySelectorAll('.js-cedi-def-row').forEach(function (row) {
            var codeInput = row.querySelector('.js-cedi-code-input');
            var labelInput = row.querySelector('input[name*="[label]"]');
            var code = (codeInput && codeInput.value ? codeInput.value : '').trim();
            var label = (labelInput && labelInput.value ? labelInput.value : '').trim();
            if (code === '' || !/^\d+$/.test(code) || seenCodes[code]) {
                return;
            }
            seenCodes[code] = true;
            cedis.push({ code: code, label: label !== '' ? label : code });
        });
        var values = [];
        var seenValues = {};
        valList.querySelectorAll('.js-cedi-value-row').forEach(function (row) {
            var amountInput = row.querySelector('.js-cedi-prize-value-input');
            var tipoInput = row.querySelector('.js-cedi-prize-tipo-input');
            var vk = parseAmount(amountInput && amountInput.value ? amountInput.value : '');
            if (vk && !seenValues[vk]) {
                seenValues[vk] = true;
                var tipo = (tipoInput && tipoInput.value ? tipoInput.value : '').trim();
                values.push({ key: vk, label: tipo !== '' ? tipo : vk, tipo: tipo });
            }
        });
        values.sort(function (a, b) {
            return parseInt(b.key, 10) - parseInt(a.key, 10);
        });
        return { cedis: cedis, values: values };
    }

    function captureMatrixValues() {
        var out = {};
        totaleMount.querySelectorAll('input.js-premi-matrix-cell').forEach(function (input) {
            var m = input.name.match(/^premi_matrix\[([^\]]+)\]\[([^\]]+)\]$/);
            if (!m) {
                return;
            }
            if (!out[m[1]]) {
                out[m[1]] = {};
            }
            out[m[1]][m[2]] = parseInt(input.value, 10) || 0;
        });
        return out;
    }

    function cellValue(preserved, vk, code) {
        if (preserved[vk] && typeof preserved[vk][code] !== 'undefined') {
            return parseInt(preserved[vk][code], 10) || 0;
        }
        if (initialMatrix[vk] && typeof initialMatrix[vk][code] !== 'undefined') {
            return parseInt(initialMatrix[vk][code], 10) || 0;
        }
        return 0;
    }

    function updateMatrixTotals(table) {
        if (!table) {
            return;
        }
        var grand = 0;
        table.querySelectorAll('tbody tr[data-value-key]').forEach(function (row) {
            var rowTotal = 0;
            row.querySelectorAll('.js-premi-matrix-cell').forEach(function (input) {
                rowTotal += parseInt(input.value, 10) || 0;
            });
            grand += rowTotal;
            var rowTotalCell = row.querySelector('.js-matrix-row-total');
            if (rowTotalCell) {
                rowTotalCell.textContent = String(rowTotal);
            }
        });
        var cediTotals = {};
        table.querySelectorAll('.js-premi-matrix-cell').forEach(function (input) {
            var cedi = input.getAttribute('data-cedi');
            if (!cedi) {
                return;
            }
            cediTotals[cedi] = (cediTotals[cedi] || 0) + (parseInt(input.value, 10) || 0);
        });
        Object.keys(cediTotals).forEach(function (code) {
            var cell = table.querySelector('.js-matrix-cedi-total[data-cedi="' + code + '"]');
            if (cell) {
                cell.textContent = String(cediTotals[code]);
            }
        });
        var grandCell = table.querySelector('#premi-matrix-grand-total');
        if (grandCell) {
            grandCell.textContent = String(grand);
        }
        if (grandTotalFooter) {
            grandTotalFooter.textContent = String(grand);
        }
    }

    function rebuildLiveMatrix() {
        var schema = readSchemaFromForm();
        var schemaReady = schema.cedis.length > 0 && schema.values.length > 0;
        if (stepMatrixCard) {
            stepMatrixCard.classList.toggle('d-none', !schemaReady);
        }
        if (stepWeekCard) {
            stepWeekCard.classList.toggle('d-none', !schemaReady);
        }
        if (schemaHint) {
            schemaHint.classList.toggle('d-none', schemaReady);
        }
        totaleMount.innerHTML = '';
        if (!schemaReady) {
            if (grandTotalFooter) {
                grandTotalFooter.textContent = '0';
            }
            return;
        }
        var preserved = captureMatrixValues();
        var html = '<div class="table-responsive"><table class="table table-sm table-bordered table-hover mb-0 premi-cedi-matrix-table" id="premi-cedi-matrix-totale-table">'
            + '<thead class="thead-light"><tr><th class="text-nowrap">Importo €</th>';
        schema.cedis.forEach(function (c) {
            html += '<th class="text-center text-nowrap">' + escapeHtml(c.label) + ' <span class="text-muted small">(' + escapeHtml(c.code) + ')</span></th>';
        });
        html += '<th class="text-center bg-light">Totale riga</th></tr></thead><tbody>';
        schema.values.forEach(function (v) {
            var rowTotal = 0;
            var rowHeading = v.tipo && v.tipo !== ''
                ? escapeHtml(v.label) + ' <span class="text-muted small">(' + escapeHtml(v.key) + ' €)</span>'
                : escapeHtml(v.key);
            html += '<tr data-value-key="' + escapeHtml(v.key) + '"><th class="text-nowrap">' + rowHeading + '</th>';
            schema.cedis.forEach(function (c) {
                var qty = cellValue(preserved, v.key, c.code);
                rowTotal += qty;
                html += '<td class="p-1"><input type="number" min="0" max="1000000" step="1" '
                    + 'class="form-control form-control-sm text-center js-premi-matrix-cell" '
                    + 'name="premi_matrix[' + escapeHtml(v.key) + '][' + escapeHtml(c.code) + ']" '
                    + 'data-value="' + escapeHtml(v.key) + '" data-cedi="' + escapeHtml(c.code) + '" '
                    + 'value="' + qty + '"></td>';
            });
            html += '<td class="text-center align-middle font-weight-bold js-matrix-row-total" data-value="' + escapeHtml(v.key) + '">' + rowTotal + '</td></tr>';
        });
        html += '<tr class="table-secondary font-weight-bold"><th>Totale</th>';
        schema.cedis.forEach(function (c) {
            html += '<td class="text-center js-matrix-cedi-total" data-cedi="' + escapeHtml(c.code) + '">0</td>';
        });
        html += '<td class="text-center" id="premi-matrix-grand-total">0</td></tr></tbody></table></div>';
        totaleMount.innerHTML = html;
        var table = totaleMount.querySelector('#premi-cedi-matrix-totale-table');
        if (table) {
            table.addEventListener('input', function () {
                updateMatrixTotals(table);
            });
            updateMatrixTotals(table);
        }
        if (typeof window.refreshCediWeekPlanner === 'function') {
            window.refreshCediWeekPlanner();
        } else if (typeof window.bootCediWeekPlanner === 'function') {
            window.bootCediWeekPlanner();
        }
    }

    function escapeHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/"/g, '&quot;');
    }

    function nextCediCode() {
        var max = 0;
        defList.querySelectorAll('.js-cedi-code-input').forEach(function (input) {
            var n = parseInt(String(input.value || '').replace(/\D/g, ''), 10);
            if (!isNaN(n) && n > max) {
                max = n;
            }
        });
        return String(max + 1);
    }

    function reindexRows(container, baseName, isDef) {
        var rows = container.querySelectorAll(isDef ? '.js-cedi-def-row' : '.js-cedi-value-row');
        rows.forEach(function (row, idx) {
            row.querySelectorAll('input').forEach(function (input) {
                if (isDef) {
                    if (input.classList.contains('js-cedi-code-input')) {
                        input.name = 'cedi_definitions[' + idx + '][code]';
                    } else {
                        input.name = 'cedi_definitions[' + idx + '][label]';
                    }
                } else {
                    if (input.classList.contains('js-cedi-prize-value-input')) {
                        input.name = 'cedi_prize_definitions[' + idx + '][value]';
                    } else if (input.classList.contains('js-cedi-prize-tipo-input')) {
                        input.name = 'cedi_prize_definitions[' + idx + '][tipo]';
                    }
                }
            });
        });
    }

    document.getElementById('cedi-def-add') && document.getElementById('cedi-def-add').addEventListener('click', function () {
        var nextCode = nextCediCode();
        var row = document.createElement('div');
        row.className = 'form-row align-items-end mb-2 js-cedi-def-row';
        row.innerHTML = '<div class="col-3"><label class="small mb-0">Codice</label><input type="text" class="form-control form-control-sm js-cedi-code-input" maxlength="16" inputmode="numeric" pattern="[0-9]*" placeholder="' + escapeHtml(nextCode) + '" value="' + escapeHtml(nextCode) + '"></div>'
            + '<div class="col"><label class="small mb-0">Nome</label><input type="text" class="form-control form-control-sm" maxlength="128" placeholder="es. Cedi nome"></div>'
            + '<div class="col-auto pb-1"><button type="button" class="btn btn-outline-danger btn-sm js-cedi-def-remove" title="Rimuovi"><i class="fa fa-trash"></i></button></div>';
        defList.appendChild(row);
        reindexRows(defList, 'cedi_definitions', true);
        rebuildLiveMatrix();
    });

    document.getElementById('cedi-value-add') && document.getElementById('cedi-value-add').addEventListener('click', function () {
        var row = document.createElement('div');
        row.className = 'form-row align-items-end mb-2 js-cedi-value-row';
        row.innerHTML = '<div class="col-4"><label class="small mb-0">€</label><input type="text" inputmode="decimal" class="form-control form-control-sm js-cedi-prize-value-input" placeholder="50"></div>'
            + '<div class="col"><label class="small mb-0">Tipo premio</label><input type="text" class="form-control form-control-sm js-cedi-prize-tipo-input" maxlength="255" placeholder="Gift card 50 euro"></div>'
            + '<div class="col-auto pb-1"><button type="button" class="btn btn-outline-danger btn-sm js-cedi-value-remove" title="Rimuovi"><i class="fa fa-trash"></i></button></div>';
        valList.appendChild(row);
        reindexRows(valList, 'cedi_prize_definitions', false);
    });

    defList.addEventListener('input', rebuildLiveMatrix);
    defList.addEventListener('change', rebuildLiveMatrix);
    valList.addEventListener('change', rebuildLiveMatrix);
    valList.addEventListener('focusout', function (e) {
        if (e.target && e.target.closest('.js-cedi-value-row')) {
            rebuildLiveMatrix();
        }
    });

    defList.addEventListener('click', function (e) {
        var btn = e.target.closest('.js-cedi-def-remove');
        if (!btn) return;
        var rows = defList.querySelectorAll('.js-cedi-def-row');
        if (rows.length <= 1) return;
        btn.closest('.js-cedi-def-row') && btn.closest('.js-cedi-def-row').remove();
        reindexRows(defList, 'cedi_definitions', true);
        rebuildLiveMatrix();
    });

    valList.addEventListener('click', function (e) {
        var btn = e.target.closest('.js-cedi-value-remove');
        if (!btn) return;
        var rows = valList.querySelectorAll('.js-cedi-value-row');
        if (rows.length <= 1) return;
        btn.closest('.js-cedi-value-row') && btn.closest('.js-cedi-value-row').remove();
        reindexRows(valList, 'cedi_prize_definitions', false);
        rebuildLiveMatrix();
    });

    window.rebuildCediPremiMatrix = rebuildLiveMatrix;

    if (weeksCustomCb && weeksCustomPanel) {
        weeksCustomCb.addEventListener('change', function () {
            weeksCustomPanel.classList.toggle('d-none', !weeksCustomCb.checked);
            if (weeksCustomCb.checked && window.jQuery) {
                window.jQuery('#cedi-wizard-step-4').collapse('show');
            }
            if (typeof window.refreshCediWeekPlanner === 'function') {
                window.refreshCediWeekPlanner();
            } else if (typeof window.bootCediWeekPlanner === 'function') {
                window.bootCediWeekPlanner();
            }
        });
    }

    rebuildLiveMatrix();
})();
</script>
<?php require __DIR__ . '/premi_cedi_week_planner.script.php'; ?>
