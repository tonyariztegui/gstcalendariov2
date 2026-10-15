<script>
(function () {
    'use strict';
    var plannerBooted = false;

    window.bootCediWeekPlanner = function () {
        var $ = window.jQuery;
        if (!$ || !$.fn) {
            return false;
        }
        if (plannerBooted) {
            if (typeof window.refreshCediWeekPlanner === 'function') {
                window.refreshCediWeekPlanner();
            }
            return true;
        }
        var $matrixSection = $('#premi-cedi-matrix-section');
        var $planner = $('#premi-week-planner');
        if (!$matrixSection.length || !$planner.length) {
            return false;
        }
        plannerBooted = true;

    var valueKeys = [];
    var cediCodes = [];
    var cediLabels = {};

    function openStep4() {
        $('#cedi-wizard-step-4').collapse('show');
    }

    function contestWeekCount() {
        var $start = $('input[name="start_date"]');
        var $end = $('input[name="end_date"]');
        if (!$start.length || !$end.length) {
            return parseInt($planner.attr('data-week-count'), 10) || 6;
        }
        var s = $start.val();
        var e = $end.val();
        if (!s || !e) {
            return 6;
        }
        var d1 = new Date(s + 'T00:00:00');
        var d2 = new Date(e + 'T00:00:00');
        if (isNaN(d1.getTime()) || isNaN(d2.getTime()) || d2 < d1) {
            return 6;
        }
        var days = Math.round((d2 - d1) / 86400000) + 1;
        return Math.max(1, Math.ceil(days / 7));
    }

    function splitAcrossWeeks(total, weeks) {
        weeks = Math.max(1, weeks);
        if (total <= 0) {
            return Array(weeks).fill(0);
        }
        var base = Math.floor(total / weeks);
        var rem = total % weeks;
        var out = [];
        for (var i = 0; i < weeks; i++) {
            out.push(base + (i >= weeks - rem ? 1 : 0));
        }
        return out;
    }

    function collectMatrixSchema() {
        valueKeys = [];
        cediCodes = [];
        cediLabels = {};
        $matrixSection.find('tbody tr[data-value-key]').each(function () {
            var vk = $(this).data('value-key');
            if (vk && valueKeys.indexOf(String(vk)) === -1) {
                valueKeys.push(String(vk));
            }
        });
        $matrixSection.find('.js-premi-matrix-cell').each(function () {
            var c = String($(this).data('cedi') || '');
            if (c && cediCodes.indexOf(c) === -1) {
                cediCodes.push(c);
            }
        });
        cediCodes.sort();
        var $table = $matrixSection.find('#premi-cedi-matrix-totale-table');
        $table.find('thead th').each(function (i) {
            if (i === 0) {
                return;
            }
            var $firstRow = $table.find('tbody tr[data-value-key]').first();
            var $inputs = $firstRow.find('.js-premi-matrix-cell');
            var idx = i - 1;
            if ($inputs.eq(idx).length) {
                cediLabels[String($inputs.eq(idx).data('cedi'))] = $(this).text().trim();
            }
        });
    }

    function matrixGrandTotal() {
        var g = 0;
        valueKeys.forEach(function (vk) {
            cediCodes.forEach(function (c) {
                var $cell = $matrixSection.find('.js-premi-matrix-cell[data-value="' + vk + '"][data-cedi="' + c + '"]');
                g += parseInt($cell.val(), 10) || 0;
            });
        });
        return g;
    }

    function readWeekPlanFromDom() {
        var plan = {};
        $planner.find('.js-premi-week-cell').each(function () {
            var w = parseInt($(this).data('week'), 10);
            var vk = String($(this).data('value'));
            var c = String($(this).data('cedi'));
            if (!plan[w]) {
                plan[w] = {};
            }
            if (!plan[w][vk]) {
                plan[w][vk] = {};
            }
            plan[w][vk][c] = parseInt($(this).val(), 10) || 0;
        });
        return plan;
    }

    function buildAutoWeekPlan(weeks) {
        var plan = {};
        for (var w = 1; w <= weeks; w++) {
            plan[w] = {};
        }
        valueKeys.forEach(function (vk) {
            cediCodes.forEach(function (c) {
                var $cell = $matrixSection.find('.js-premi-matrix-cell[data-value="' + vk + '"][data-cedi="' + c + '"]');
                var total = parseInt($cell.val(), 10) || 0;
                var parts = splitAcrossWeeks(total, weeks);
                parts.forEach(function (part, idx) {
                    if (!plan[idx + 1][vk]) {
                        plan[idx + 1][vk] = {};
                    }
                    plan[idx + 1][vk][c] = part;
                });
            });
        });
        return plan;
    }

    function balancedWeekTargets(grand, weeks) {
        weeks = Math.max(1, weeks);
        if (grand <= 0) {
            return Array(weeks).fill(0);
        }
        var base = Math.floor(grand / weeks);
        var rem = grand % weeks;
        var targets = [];
        for (var i = 0; i < weeks; i++) {
            targets.push(base);
        }
        for (var r = 0; r < rem; r++) {
            var minIdx = 0;
            for (var j = 1; j < weeks; j++) {
                if (targets[j] < targets[minIdx]) {
                    minIdx = j;
                }
            }
            targets[minIdx]++;
        }
        return targets;
    }

    function distributeByWeights(total, weights) {
        var n = weights.length;
        if (n === 0 || total <= 0) {
            return Array(Math.max(0, n)).fill(0);
        }
        var sumW = 0;
        weights.forEach(function (w) { sumW += w; });
        if (sumW <= 0) {
            return Array(n).fill(0);
        }
        var out = [];
        var fracs = [];
        var allocated = 0;
        for (var i = 0; i < n; i++) {
            var ideal = total * weights[i] / sumW;
            var f = Math.floor(ideal);
            out.push(f);
            allocated += f;
            fracs.push({ i: i, f: ideal - f });
        }
        var rem = total - allocated;
        fracs.sort(function (a, b) { return b.f - a.f; });
        for (var r = 0; r < rem; r++) {
            out[fracs[r % n].i]++;
        }
        return out;
    }

    function weekPlanGrandSum(plan, week) {
        var s = 0;
        if (!plan[week]) {
            return 0;
        }
        valueKeys.forEach(function (vk) {
            cediCodes.forEach(function (c) {
                s += (plan[week][vk] && plan[week][vk][c]) ? parseInt(plan[week][vk][c], 10) : 0;
            });
        });
        return s;
    }

    function balanceWeekPlanTotals(plan, weekTargets, weeks) {
        var maxIter = 8000;
        for (var iter = 0; iter < maxIter; iter++) {
            var moved = false;
            for (var w = 1; w <= weeks; w++) {
                var sum = weekPlanGrandSum(plan, w);
                var target = weekTargets[w - 1] || 0;
                if (sum === target) {
                    continue;
                }
                var needUp = sum < target;
                var bestFrom = 0;
                var bestVk = '';
                var bestC = '';
                var bestScore = needUp ? 1e15 : -1;
                valueKeys.forEach(function (vk) {
                    cediCodes.forEach(function (c) {
                        var $cell = $matrixSection.find('.js-premi-matrix-cell[data-value="' + vk + '"][data-cedi="' + c + '"]');
                        var cellTotal = parseInt($cell.val(), 10) || 0;
                        if (cellTotal <= 0) {
                            return;
                        }
                        for (var wf = 1; wf <= weeks; wf++) {
                            if (wf === w) {
                                continue;
                            }
                            if (!plan[w][vk]) {
                                plan[w][vk] = {};
                            }
                            if (!plan[wf][vk]) {
                                plan[wf][vk] = {};
                            }
                            var here = parseInt(plan[w][vk][c], 10) || 0;
                            var from = parseInt(plan[wf][vk][c], 10) || 0;
                            if (needUp) {
                                if (from < 1) {
                                    continue;
                                }
                                var score = here * 1000 - from + cellTotal;
                                if (score < bestScore) {
                                    bestScore = score;
                                    bestFrom = wf;
                                    bestVk = vk;
                                    bestC = c;
                                }
                            } else {
                                if (here < 1) {
                                    continue;
                                }
                                var wfSum = weekPlanGrandSum(plan, wf);
                                var wfTarget = weekTargets[wf - 1] || 0;
                                if (wfSum >= wfTarget) {
                                    continue;
                                }
                                var score2 = here * 1000 - wfSum;
                                if (score2 > bestScore) {
                                    bestScore = score2;
                                    bestFrom = wf;
                                    bestVk = vk;
                                    bestC = c;
                                }
                            }
                        }
                    });
                });
                if (bestVk && bestFrom > 0) {
                    if (needUp) {
                        plan[bestFrom][bestVk][bestC]--;
                        plan[w][bestVk][bestC] = (parseInt(plan[w][bestVk][bestC], 10) || 0) + 1;
                    } else {
                        plan[w][bestVk][bestC]--;
                        plan[bestFrom][bestVk][bestC] = (parseInt(plan[bestFrom][bestVk][bestC], 10) || 0) + 1;
                    }
                    moved = true;
                }
            }
            if (!moved) {
                break;
            }
        }
        return plan;
    }

    function buildBalancedWeekPlan(weeks) {
        var plan = {};
        for (var w = 1; w <= weeks; w++) {
            plan[w] = {};
        }
        var grand = matrixGrandTotal();
        if (grand <= 0) {
            return plan;
        }
        var weekTargets = balancedWeekTargets(grand, weeks);
        valueKeys.forEach(function (vk) {
            cediCodes.forEach(function (c) {
                var $cell = $matrixSection.find('.js-premi-matrix-cell[data-value="' + vk + '"][data-cedi="' + c + '"]');
                var total = parseInt($cell.val(), 10) || 0;
                if (total <= 0) {
                    return;
                }
                var parts = distributeByWeights(total, weekTargets);
                parts.forEach(function (part, idx) {
                    if (!plan[idx + 1][vk]) {
                        plan[idx + 1][vk] = {};
                    }
                    plan[idx + 1][vk][c] = part;
                });
            });
        });
        return balanceWeekPlanTotals(plan, weekTargets, weeks);
    }

    function weekPlanFromDataAttr(weeks) {
        var raw = $planner.attr('data-plan');
        if (!raw) {
            return buildAutoWeekPlan(weeks);
        }
        try {
            var parsed = JSON.parse(raw);
            var plan = {};
            for (var w = 1; w <= weeks; w++) {
                plan[w] = parsed[w] || parsed[String(w)] || {};
            }
            return plan;
        } catch (e) {
            return buildAutoWeekPlan(weeks);
        }
    }

    function isWeekCustom() {
        return $('#premi_matrix_weeks_custom').is(':checked');
    }

    function updateAllWeekPaneTotals() {
        var weeks = contestWeekCount();
        for (var w = 1; w <= weeks; w++) {
            var weekGrand = 0;
            var cediTotals = {};
            cediCodes.forEach(function (c) { cediTotals[c] = 0; });
            valueKeys.forEach(function (vk) {
                var rowSum = 0;
                cediCodes.forEach(function (c) {
                    var $inp = $planner.find('.js-premi-week-cell[data-week="' + w + '"][data-value="' + vk + '"][data-cedi="' + c + '"]');
                    var v = parseInt($inp.val(), 10) || 0;
                    rowSum += v;
                    cediTotals[c] += v;
                    weekGrand += v;
                });
                $planner.find('#premi-week-pane-' + w + ' tbody tr').each(function () {
                    if ($(this).find('.js-premi-week-cell[data-value="' + vk + '"]').length) {
                        $(this).find('.js-week-row-total').text(rowSum);
                    }
                });
            });
            cediCodes.forEach(function (c) {
                $planner.find('.js-week-cedi-total[data-week="' + w + '"][data-cedi="' + c + '"]').text(cediTotals[c] || 0);
            });
            $planner.find('.js-week-grand-total[data-week="' + w + '"]').text(weekGrand);
            $planner.find('.js-week-recap-sum[data-week="' + w + '"]').text(weekGrand);
        }
    }

    function validateWeekPlanStatus() {
        var weeks = contestWeekCount();
        var custom = isWeekCustom();
        var issues = [];
        updateAllWeekPaneTotals();
        valueKeys.forEach(function (vk) {
            cediCodes.forEach(function (c) {
                var expected = parseInt($matrixSection.find('.js-premi-matrix-cell[data-value="' + vk + '"][data-cedi="' + c + '"]').val(), 10) || 0;
                var sum = 0;
                for (var w = 1; w <= weeks; w++) {
                    var $inp = $planner.find('.js-premi-week-cell[data-week="' + w + '"][data-value="' + vk + '"][data-cedi="' + c + '"]');
                    sum += parseInt($inp.val(), 10) || 0;
                }
                if (sum !== expected) {
                    var lbl = cediLabels[c] || c;
                    issues.push(vk + '€ ' + lbl + ': settimane=' + sum + ', totale=' + expected);
                    $planner.find('.js-premi-week-cell[data-value="' + vk + '"][data-cedi="' + c + '"]').addClass('is-invalid');
                } else {
                    $planner.find('.js-premi-week-cell[data-value="' + vk + '"][data-cedi="' + c + '"]').removeClass('is-invalid');
                }
            });
        });
        var $status = $('#premi-week-planner-status');
        if (!valueKeys.length || !cediCodes.length) {
            $status.html('<span class="text-muted">Completa la matrice (passo 3) per visualizzare la ripartizione settimanale.</span>');
        } else if (!custom) {
            $status.html('<span class="text-muted">Modalità <strong>per cella (auto)</strong> — ripartizione automatica, nessuna personalizzazione da salvare. Per una ripartizione custom: attiva <strong>Personalizza</strong> o usa <strong>Equilibrio settimane</strong>.</span>');
        } else if (!$planner.find('.js-premi-week-cell').length) {
            $status.html('<span class="text-warning"><strong>Ripartizione settimanale personalizzata mancante:</strong> compilare il passo 4 (griglia settimanale non generata).</span>');
        } else if (issues.length === 0) {
            $status.html('<span class="text-success"><i class="fa fa-check mr-1"></i>OK — ogni cella coincide col totale concorso. Puoi salvare.</span>');
        } else {
            $status.html('<span class="text-danger"><strong>Da correggere:</strong> ' + issues.slice(0, 6).join(' · ')
                + (issues.length > 6 ? ' …' : '') + '</span>');
        }
    }

    function renderWeekPlanner(preserveCustomValues, forcedPlan) {
        collectMatrixSchema();
        if (!valueKeys.length || !cediCodes.length) {
            $planner.empty();
            validateWeekPlanStatus();
            return;
        }

        var weeks = contestWeekCount();
        $('.premi-matrix-week-count-label').text(weeks);
        $planner.attr('data-week-count', weeks);

        var plan;
        if (forcedPlan !== undefined && forcedPlan !== null) {
            plan = forcedPlan;
        } else if (preserveCustomValues && isWeekCustom() && $planner.find('.js-premi-week-cell').length) {
            plan = readWeekPlanFromDom();
        } else if (isWeekCustom() && !preserveCustomValues) {
            plan = readWeekPlanFromDom();
            if (Object.keys(plan).length === 0) {
                plan = weekPlanFromDataAttr(weeks);
            }
        } else {
            plan = buildAutoWeekPlan(weeks);
        }

        var custom = isWeekCustom();
        $planner.empty();

        var grand = matrixGrandTotal();
        var weekTargets = balancedWeekTargets(grand, weeks);
        var $recap = $('<div class="small mb-2 p-2 bg-light rounded js-week-targets-recap"></div>');
        var recapParts = [];
        for (var w = 1; w <= weeks; w++) {
            recapParts.push('<span class="mr-2"><strong>S' + w + ':</strong> <span class="js-week-recap-sum" data-week="' + w + '">0</span>'
                + ' <span class="text-muted">/ ' + (weekTargets[w - 1] || 0) + '</span></span>');
        }
        $recap.html('<span class="text-muted mr-2">Totale settimanale:</span>' + recapParts.join(''));
        $planner.append($recap);

        var $nav = $('<ul class="nav nav-pills nav-pills-sm mb-2 flex-wrap"></ul>');
        var $content = $('<div class="tab-content"></div>');

        for (var w = 1; w <= weeks; w++) {
            var $tab = $('<li class="nav-item"></li>');
            var active = w === 1 ? ' active' : '';
            $tab.append(
                '<a class="nav-link' + active + '" data-toggle="pill" href="#premi-week-pane-' + w + '">Sett. ' + w + '</a>'
            );
            $nav.append($tab);

            var $pane = $('<div class="tab-pane fade' + (w === 1 ? ' show active' : '') + '" id="premi-week-pane-' + w + '"></div>');
            var $table = $('<table class="table table-sm table-bordered mb-2"><thead class="thead-light"><tr><th>€</th></tr></thead><tbody></tbody><tfoot><tr class="table-secondary font-weight-bold"><th>Totale sett.</th></tr></tfoot></table>');
            var $head = $table.find('thead tr');
            var $foot = $table.find('tfoot tr');
            cediCodes.forEach(function (c) {
                var lbl = cediLabels[c] || ('Ce.Di. ' + c);
                $head.append('<th class="text-center">' + $('<div>').text(lbl).html() + '</th>');
                $foot.append('<td class="text-center js-week-cedi-total" data-week="' + w + '" data-cedi="' + c + '">0</td>');
            });
            $head.append('<th class="text-center">Σ riga</th>');
            $foot.append('<td class="text-center js-week-grand-total" data-week="' + w + '">0</td>');

            var $tbody = $table.find('tbody');
            valueKeys.forEach(function (vk) {
                var $tr = $('<tr></tr>');
                $tr.append('<th class="text-nowrap">' + vk + '</th>');
                var rowSum = 0;
                cediCodes.forEach(function (c) {
                    var qty = (plan[w] && plan[w][vk] && plan[w][vk][c]) ? parseInt(plan[w][vk][c], 10) : 0;
                    rowSum += qty;
                    var nameAttr = 'premi_matrix_weeks[' + w + '][' + vk + '][' + c + ']';
                    var $inp = $('<input type="number" min="0" max="1000000" step="1" class="form-control form-control-sm text-center js-premi-week-cell">')
                        .attr('name', custom ? nameAttr : '')
                        .attr('data-week', w)
                        .attr('data-value', vk)
                        .attr('data-cedi', c)
                        .val(qty);
                    if (!custom) {
                        $inp.prop('readonly', true);
                    }
                    $tr.append($('<td class="p-1"></td>').append($inp));
                });
                $tr.append($('<td class="text-center align-middle font-weight-bold js-week-row-total"></td>').text(rowSum));
                $tbody.append($tr);
            });
            $pane.append($table);
            $content.append($pane);
        }

        $planner.append($nav).append($content);
        validateWeekPlanStatus();
    }

    function refreshCediWeekPlanner() {
        renderWeekPlanner(!isWeekCustom());
    }

    window.refreshCediWeekPlanner = refreshCediWeekPlanner;

    $matrixSection.on('input change', '.js-premi-matrix-cell', refreshCediWeekPlanner);
    $('#premi_matrix_weeks_custom').on('change', function () {
        renderWeekPlanner(false);
    });
    $matrixSection.on('click', '.js-premi-week-mode-auto', function () {
        $('#premi_matrix_weeks_custom').prop('checked', false);
        renderWeekPlanner(false, buildAutoWeekPlan(contestWeekCount()));
    });
    $matrixSection.on('click', '.js-premi-week-mode-balanced', function () {
        var weeks = contestWeekCount();
        $('#premi_matrix_weeks_custom').prop('checked', true);
        renderWeekPlanner(false, buildBalancedWeekPlan(weeks));
        openStep4();
    });
    $('input[name="start_date"], input[name="end_date"]').on('change', refreshCediWeekPlanner);
    $planner.on('input change', '.js-premi-week-cell', function () {
        updateAllWeekPaneTotals();
        validateWeekPlanStatus();
    });

    var $form = $('#config-form');
    if ($form.length) {
        $form.on('submit.premiWeekPlan', function (e) {
            if (!$('#premi-cedi-matrix-section').length || !isWeekCustom()) {
                return;
            }
            collectMatrixSchema();
            if (!valueKeys.length || !cediCodes.length) {
                return;
            }
            var weeks = contestWeekCount();
            var issues = [];
            valueKeys.forEach(function (vk) {
                cediCodes.forEach(function (c) {
                    var expected = parseInt($matrixSection.find('.js-premi-matrix-cell[data-value="' + vk + '"][data-cedi="' + c + '"]').val(), 10) || 0;
                    var sum = 0;
                    for (var w = 1; w <= weeks; w++) {
                        var $inp = $planner.find('.js-premi-week-cell[data-week="' + w + '"][data-value="' + vk + '"][data-cedi="' + c + '"]');
                        if (!$inp.length) {
                            issues.push('Ripartizione settimanale non generata: apri il passo 4.');
                            return;
                        }
                        sum += parseInt($inp.val(), 10) || 0;
                        if (!$inp.attr('name')) {
                            $inp.attr('name', 'premi_matrix_weeks[' + w + '][' + vk + '][' + c + ']');
                        }
                    }
                    if (sum !== expected) {
                        issues.push('Settimane ' + vk + '€ ' + (cediLabels[c] || c) + ': ' + sum + ' ≠ ' + expected);
                    }
                });
            });
            if (issues.length > 0) {
                e.preventDefault();
                $('#config-form-alerts .config-premi-validation-alert').remove();
                var alertHtml = '<div class="alert alert-danger alert-dismissible mb-3 config-premi-validation-alert" role="alert">'
                    + '<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>'
                    + '<strong>Ripartizione settimanale</strong><ul class="mb-0 pl-3">'
                    + issues.slice(0, 8).map(function (m) { return '<li>' + $('<div>').text(m).html() + '</li>'; }).join('')
                    + '</ul></div>';
                $('#config-form-alerts').prepend(alertHtml);
                openStep4();
                $('html, body').animate({ scrollTop: $('#premi-week-planner-status').offset().top - 120 }, 300);
                return false;
            }
        });
    }

        window.refreshCediWeekPlanner = refreshCediWeekPlanner;
        refreshCediWeekPlanner();
        return true;
    };
})();
</script>
