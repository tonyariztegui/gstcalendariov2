/**
 * Gestione Dati — archivio CRUD + DataTables
 */
(function() {
    'use strict';

    function bootGestioneDatiArchivio() {
        if (typeof jQuery === 'undefined' || typeof jQuery.fn.DataTable === 'undefined') {
            setTimeout(bootGestioneDatiArchivio, 100);
            return;
        }

        (function($) {

    var $root = $('#gestione-dati-archivio');
    if (!$root.length) {
        return;
    }

    var ajaxBase = $root.data('ajax-base') || 'res/inc/';
    if (ajaxBase.slice(-1) !== '/') {
        ajaxBase += '/';
    }

    var columnsConfig = {};
    try {
        columnsConfig = JSON.parse($root.attr('data-columns-config') || '{}');
    } catch (e) {
        columnsConfig = {};
    }

    var isStandalone = String($root.data('standalone') || '0') === '1';
    var tables = {};
    var activeTable = $root.data('first-table') || '';
    var standaloneSaveTimer = null;
    var metaCache = {};
    var csvColumnsByTable = {};
    try {
        csvColumnsByTable = JSON.parse($('#gestione-dati-csv-columns-data').text() || $root.attr('data-csv-columns') || '{}');
    } catch (e2) {
        csvColumnsByTable = {};
    }

    function updateCsvColumnsHint(table) {
        var $hint = $('#gestione-dati-csv-columns-hint');
        var $list = $hint.find('.gd-csv-columns-list');
        if (!$hint.length || !$list.length) {
            return;
        }
        var bundle = csvColumnsByTable[table];
        if (!bundle || !bundle.all || !bundle.all.length) {
            $hint.addClass('d-none');
            return;
        }
        var required = bundle.required || [];
        var html = bundle.all.map(function(col) {
            var esc = escapeHtml(col);
            return required.indexOf(col) !== -1 ? '<strong>' + esc + '</strong>' : esc;
        }).join(', ');
        $list.html(html);
        $hint.removeClass('d-none');
    }

    function escapeHtml(str) {
        return $('<div>').text(str == null ? '' : str).html();
    }

    function getActiveTable() {
        var $link = $root.find('.gestione-dati-tab-link.active');
        return ($link.data('table') || activeTable || '').toString();
    }

    function syncImportTableSelect(table) {
        var $sel = $('#csv_table_gestione');
        if ($sel.length && table) {
            $sel.val(table);
        }
        updateCsvColumnsHint(table);
    }

    function getPaneFilters(table) {
        var $pane = $('#gd-pane-' + table);
        return {
            filterActive: ($pane.find('.gd-filter-active').val() || 'all').toString(),
            filterUsed: ($pane.find('.gd-filter-used').val() || 'all').toString()
        };
    }

    function getTableConfig(table) {
        return columnsConfig[table] || { catalog: [], visible: [], recommended: [] };
    }

    function resolveVisibleFromPicker(table) {
        var picker = {};
        $('#gd-pane-' + table).find('.gd-column-check:checked').each(function() {
            picker[$(this).val()] = true;
        });
        var out = [];
        (getTableConfig(table).catalog || []).forEach(function(c) {
            if (c.locked || picker[c.data]) {
                out.push(c.data);
            }
        });
        return out;
    }

    function updateColumnToggleLabel(table) {
        var count = $('#gd-pane-' + table).find('.gd-column-check:checked').length;
        var $toggle = $('#gd-pane-' + table).find('.gd-column-toggle');
        $toggle.text(count > 0 ? ('Colonne (' + count + ')') : 'Colonne');
    }

    function serializeAllColumnsPayload() {
        var out = {};
        Object.keys(columnsConfig).forEach(function(table) {
            out[table] = resolveVisibleFromPicker(table);
        });
        return out;
    }

    function syncGestioneDatiColumnsPayload() {
        var payload = document.getElementById('config-gd-columns-json');
        if (!payload) {
            return;
        }
        try {
            payload.value = JSON.stringify(serializeAllColumnsPayload());
        } catch (e) {
            payload.value = '{}';
        }
    }

    function saveColumnsStandalone(table) {
        if (!isStandalone || !table) {
            return;
        }
        var visible = resolveVisibleFromPicker(table);
        $.ajax({
            url: ajaxBase + 'gestione_dati_save_columns.php',
            type: 'POST',
            dataType: 'json',
            data: {
                table: table,
                columns: JSON.stringify(visible)
            }
        }).done(function(res) {
            if (res && res.success) {
                var cfg = getTableConfig(table);
                cfg.visible = res.visible || visible;
                columnsConfig[table] = cfg;
            }
        });
    }

    function buildDataTableColumns(table) {
        var cfg = getTableConfig(table);
        var visibleSet = {};
        (cfg.visible || []).forEach(function(k) {
            visibleSet[k] = true;
        });
        return (cfg.catalog || []).map(function(c) {
            return {
                data: c.data,
                title: c.title,
                orderable: c.orderable !== false,
                searchable: c.searchable !== false,
                visible: !!visibleSet[c.data] || !!c.locked
            };
        });
    }

    function applyPickerToTable(table, updateConfig) {
        if (updateConfig !== false) {
            var cfg = getTableConfig(table);
            cfg.visible = resolveVisibleFromPicker(table);
            columnsConfig[table] = cfg;
        }
        var dt = tables[table];
        if (!dt) {
            return;
        }
        var visibleSet = {};
        (getTableConfig(table).visible || []).forEach(function(k) {
            visibleSet[k] = true;
        });
        (getTableConfig(table).catalog || []).forEach(function(c, idx) {
            dt.column(idx).visible(!!visibleSet[c.data] || !!c.locked, false);
        });
        dt.columns.adjust().draw(false);
    }

    function setPickerFromVisible(table, visibleList) {
        var $checks = $('#gd-pane-' + table).find('.gd-column-check');
        if (!$checks.length) {
            return;
        }
        var set = {};
        (visibleList || []).forEach(function(k) {
            set[k] = true;
        });
        $checks.each(function() {
            $(this).prop('checked', !!set[$(this).val()]);
        });
        updateColumnToggleLabel(table);
    }

    function initTable(table) {
        if (!table || tables[table]) {
            return tables[table];
        }
        var $el = $('#gd-table-' + table);
        if (!$el.length || typeof $.fn.DataTable === 'undefined') {
            return null;
        }

        var cols = buildDataTableColumns(table);
        var dt = $el.DataTable({
            processing: true,
            serverSide: true,
            searching: true,
            lengthChange: true,
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
            order: [[0, 'asc']],
            language: {
                url: '//cdn.datatables.net/plug-ins/1.12.1/i18n/it-IT.json'
            },
            ajax: {
                url: ajaxBase + 'gestione_dati_table.php',
                type: 'POST',
                data: function(d) {
                    var f = getPaneFilters(table);
                    d.table = table;
                    d.filterActive = f.filterActive;
                    d.filterUsed = f.filterUsed;
                    if (d.order && d.order[0]) {
                        var catalog = getTableConfig(table).catalog || [];
                        var colIdx = parseInt(d.order[0].column, 10);
                        if (catalog[colIdx] && catalog[colIdx].data) {
                            d.orderField = catalog[colIdx].data;
                        }
                    }
                }
            },
            columns: cols,
            columnDefs: [
                { targets: -1, orderable: false, searchable: false }
            ]
        });

        tables[table] = dt;
        return dt;
    }

    function reloadTable(table) {
        if (tables[table]) {
            tables[table].ajax.reload();
        }
    }

    function activateTab(table, scroll) {
        if (!table) {
            return;
        }
        var $link = $root.find('.gestione-dati-tab-link[data-table="' + table + '"]');
        if (!$link.length) {
            return;
        }
        activeTable = table;
        $link.tab('show');
        syncImportTableSelect(table);
        initTable(table);
        if (scroll) {
            var el = document.getElementById('gestione-dati-archivio');
            if (el) {
                el.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }
    }

    function fetchMeta(table) {
        if (metaCache[table]) {
            return $.Deferred().resolve(metaCache[table]).promise();
        }
        return $.post(ajaxBase + 'gestione_dati_crud.php', { action: 'meta', table: table }).then(function(res) {
            if (res && res.success) {
                metaCache[table] = res.meta || {};
                return metaCache[table];
            }
            throw new Error((res && res.error) || 'Errore meta');
        });
    }

    function isFieldLocked(record, fieldDef) {
        if (!fieldDef || !fieldDef.lock_when) {
            return false;
        }
        if (fieldDef.lock_when === 'used') {
            return parseInt(record.used, 10) === 1;
        }
        if (fieldDef.lock_when === 'assigned') {
            return parseInt(record.used, 10) === 1 || (record.id_partecipazione && parseInt(record.id_partecipazione, 10) > 0);
        }
        return false;
    }

    function renderField(fieldDef, record, meta, lockedGlobal) {
        var name = fieldDef.name;
        var locked = lockedGlobal || isFieldLocked(record, fieldDef);
        var reqAttr = fieldDef.required && !locked ? ' required' : '';
        var label = fieldDef.label + (fieldDef.required ? ' *' : '');
        var value = record[name];
        if (value == null && fieldDef.default != null) {
            value = fieldDef.default;
        }
        var help = fieldDef.help ? '<small class="form-text text-muted gd-field-help">' + escapeHtml(fieldDef.help) + '</small>' : '';
        var col = fieldDef.col || 'col-12';

        if (fieldDef.type === 'checkbox') {
            var checked = value !== 0 && value !== '0' && value !== false && value !== '';
            if (value == null && fieldDef.default === '1') {
                checked = true;
            }
            return '<div class="form-check mb-3">' +
                '<input class="form-check-input" type="checkbox" name="' + name + '" value="1"' + (checked ? ' checked' : '') + '>' +
                '<label class="form-check-label">' + escapeHtml(label) + '</label></div>' + help;
        }

        if (fieldDef.type === 'textarea') {
            return '<div class="form-group"><label>' + escapeHtml(label) + '</label>' +
                '<textarea class="form-control" name="' + name + '" rows="3"' + (locked ? ' readonly' : '') + reqAttr + '>' +
                escapeHtml(value || '') + '</textarea>' + help + '</div>';
        }

        if (fieldDef.type === 'select') {
            var opts = fieldDef.options || [];
            var selHtml = '<div class="form-group ' + col + '"><label>' + escapeHtml(label) + '</label><select class="form-control" name="' + name + '"' + (locked ? ' disabled' : '') + reqAttr + '>';
            opts.forEach(function(o) {
                var sel = String(value || fieldDef.default || '') === String(o.value) ? ' selected' : '';
                selHtml += '<option value="' + escapeHtml(o.value) + '"' + sel + '>' + escapeHtml(o.label) + '</option>';
            });
            selHtml += '</select>' + help + '</div>';
            return selHtml;
        }

        if (fieldDef.type === 'prize_select') {
            var premi = meta.premi || [];
            var phtml = '<div class="form-group"><label>' + escapeHtml(label) + '</label><select class="form-control" name="prize_id"' + (locked ? ' disabled' : '') + reqAttr + '>';
            phtml += '<option value="">— Seleziona —</option>';
            premi.forEach(function(p) {
                var sel = parseInt(record.prize_id, 10) === parseInt(p.id, 10) ? ' selected' : '';
                phtml += '<option value="' + escapeHtml(p.id) + '"' + sel + '>' + escapeHtml(p.nome || ('ID ' + p.id)) + '</option>';
            });
            phtml += '</select>';
            if (locked && record.prize_id) {
                phtml += '<input type="hidden" name="prize_id" value="' + escapeHtml(record.prize_id) + '">';
            }
            phtml += help + '</div>';
            return phtml;
        }

        if (fieldDef.type === 'cp_select') {
            var cps = meta.codici_partecipazione_options || [];
            var chtml = '<div class="form-group"><label>' + escapeHtml(label) + '</label><select class="form-control" name="id_codice_partecipazione"' + (locked ? ' disabled' : '') + reqAttr + '>';
            chtml += '<option value="">— Seleziona —</option>';
            cps.forEach(function(cp) {
                var sel = parseInt(record.id_codice_partecipazione, 10) === parseInt(cp.id, 10) ? ' selected' : '';
                chtml += '<option value="' + escapeHtml(cp.id) + '"' + sel + '>' + escapeHtml(cp.codice) + ' (#' + escapeHtml(cp.id) + ')</option>';
            });
            if (record.id_codice_partecipazione && !cps.some(function(cp) { return parseInt(cp.id, 10) === parseInt(record.id_codice_partecipazione, 10); })) {
                chtml += '<option value="' + escapeHtml(record.id_codice_partecipazione) + '" selected>#' + escapeHtml(record.id_codice_partecipazione) + '</option>';
            }
            chtml += '</select>' + help + '</div>';
            return chtml;
        }

        var inputType = fieldDef.type === 'number' ? 'number' : 'text';
        return '<div class="form-group ' + col + '"><label>' + escapeHtml(label) + '</label>' +
            '<input type="' + inputType + '" class="form-control" name="' + name + '" value="' + escapeHtml(value != null ? value : '') + '"' +
            (locked ? ' readonly' : '') + reqAttr + ' step="any">' + help + '</div>';
    }

    function buildFormFields(table, record, meta) {
        record = record || {};
        meta = meta || {};
        var schema = meta.fields || {};
        var core = schema.core || [];
        var optional = schema.optional || [];
        var lockedGlobal = false;

        if (table === 'codici_partecipazione') {
            lockedGlobal = parseInt(record.used, 10) === 1;
        } else if (table === 'codici_premio') {
            lockedGlobal = parseInt(record.used, 10) === 1 || (record.id_partecipazione && parseInt(record.id_partecipazione, 10) > 0);
        }

        var html = '';
        if (meta.hint) {
            html += '<p class="small text-muted gd-modal-hint mb-3">' + escapeHtml(meta.hint) + '</p>';
        }

        core.forEach(function(f) {
            html += renderField(f, record, meta, lockedGlobal);
        });

        if (optional.length) {
            html += '<div class="gestione-dati-optional-fields mt-2">';
            html += '<button class="btn btn-link btn-sm p-0 gestione-dati-optional-toggle" type="button" data-toggle="collapse" data-target="#gd-optional-collapse" aria-expanded="false">';
            html += '<i class="fa fa-chevron-down mr-1"></i>Campi opzionali</button>';
            html += '<div class="collapse mt-2" id="gd-optional-collapse">';
            optional.forEach(function(f) {
                if (f.col && (f.col.indexOf('col-md') === 0)) {
                    html += renderField(f, record, meta, lockedGlobal);
                } else {
                    html += renderField(f, record, meta, lockedGlobal);
                }
            });
            html += '</div></div>';
        }

        if (table === 'codici_partecipazione' && lockedGlobal) {
            html += '<p class="small text-muted">Codice già utilizzato: puoi modificare solo lo stato attivo.</p>';
        }
        if (table === 'codici_premio' && lockedGlobal) {
            html += '<p class="small text-muted">Codice già assegnato: puoi modificare solo lo stato attivo.</p>';
        }
        if (table === 'quiz_domande' && meta.quiz_editor_url) {
            html += '<p class="small"><a href="' + escapeHtml(meta.quiz_editor_url) + '" target="_blank" rel="noopener">Editor completo</a> per upload audio e opzioni avanzate.</p>';
        }

        return html;
    }

    function openModal(mode, table, id) {
        $('#gd-crud-table').val(table);
        $('#gd-crud-id').val(id || '');
        var title = mode === 'create' ? 'Nuovo record' : 'Modifica record';
        $('#gestione-dati-crud-modal-title').text(title);

        var $body = $('#gestione-dati-crud-modal-body');
        $body.find('.gd-dynamic-fields').remove();

        $.when(fetchMeta(table)).done(function(meta) {
            var record = {};
            if (mode === 'edit' && id) {
                $.post(ajaxBase + 'gestione_dati_crud.php', { action: 'get', table: table, id: id }).done(function(res) {
                    if (!res || !res.success) {
                        swalError((res && res.error) || 'Record non trovato.');
                        return;
                    }
                    record = res.record || {};
                    $body.append('<div class="gd-dynamic-fields">' + buildFormFields(table, record, meta) + '</div>');
                    $('#gestione-dati-crud-modal').modal('show');
                }).fail(function() {
                    swalError('Errore di caricamento.');
                });
            } else {
                $body.append('<div class="gd-dynamic-fields">' + buildFormFields(table, {}, meta) + '</div>');
                $('#gestione-dati-crud-modal').modal('show');
            }
        }).fail(function(err) {
            swalError(err.message || 'Errore.');
        });
    }

    function swalError(msg) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({ icon: 'error', title: 'Errore', text: msg });
        } else {
            alert(msg);
        }
    }

    function swalSuccess(msg) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({ icon: 'success', title: 'OK', text: msg, timer: 2000, showConfirmButton: false });
        }
    }

    $root.on('shown.bs.tab', '.gestione-dati-tab-link', function() {
        var table = $(this).data('table');
        activeTable = table;
        syncImportTableSelect(table);
        initTable(table);
    });

    $root.on('change', '.gd-filter-active, .gd-filter-used', function() {
        reloadTable(getActiveTable());
    });

    $root.on('click', '.gd-btn-new', function() {
        openModal('create', $(this).data('table'), 0);
    });

    $root.on('click', '.gd-btn-export', function() {
        var table = $(this).data('table');
        var f = getPaneFilters(table);
        var qs = $.param({
            table: table,
            filterActive: f.filterActive,
            filterUsed: f.filterUsed
        });
        window.location.href = ajaxBase + 'export_gestione_dati.php?' + qs;
    });

    $root.on('click', '.gd-btn-edit', function() {
        var id = $(this).data('id');
        openModal('edit', getActiveTable(), id);
    });

    $root.on('click', '.gd-btn-delete', function() {
        var id = $(this).data('id');
        var table = getActiveTable();
        var doDelete = function() {
            $.post(ajaxBase + 'gestione_dati_crud.php', { action: 'delete', table: table, id: id }).done(function(res) {
                if (res && res.success) {
                    swalSuccess(res.message || 'Eliminato.');
                    reloadTable(table);
                } else {
                    swalError((res && res.error) || 'Eliminazione fallita.');
                }
            }).fail(function() {
                swalError('Errore di rete.');
            });
        };
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'warning',
                title: 'Eliminare questo record?',
                showCancelButton: true,
                confirmButtonText: 'Elimina',
                cancelButtonText: 'Annulla',
                confirmButtonColor: '#dc3545'
            }).then(function(r) {
                if (r.isConfirmed) {
                    doDelete();
                }
            });
        } else if (confirm('Eliminare questo record?')) {
            doDelete();
        }
    });

    $('#gestione-dati-crud-form').on('submit', function(e) {
        e.preventDefault();
        var table = $('#gd-crud-table').val();
        var id = parseInt($('#gd-crud-id').val(), 10) || 0;
        var action = id > 0 ? 'update' : 'create';
        var data = $(this).serializeArray();
        data.push({ name: 'action', value: action });
        data.push({ name: 'table', value: table });
        if (!$('input[name="active"]').is(':checked') && table === 'punti_vendita') {
            data.push({ name: 'active', value: '0' });
        }
        if (!$('input[name="isActive"]').is(':checked') && (table === 'codici_partecipazione' || table === 'codici_premio')) {
            data.push({ name: 'isActive', value: '0' });
        }
        if (!$('input[name="attiva"]').is(':checked') && table === 'quiz_domande') {
            data.push({ name: 'attiva', value: '0' });
        }

        $.post(ajaxBase + 'gestione_dati_crud.php', $.param(data)).done(function(res) {
            if (res && res.success) {
                $('#gestione-dati-crud-modal').modal('hide');
                swalSuccess(res.message || 'Salvato.');
                reloadTable(table);
                metaCache[table] = null;
            } else {
                swalError((res && res.error) || 'Salvataggio fallito.');
            }
        }).fail(function(xhr) {
            var msg = 'Errore di rete.';
            try {
                var j = JSON.parse(xhr.responseText);
                if (j.error) msg = j.error;
            } catch (err) {}
            swalError(msg);
        });
    });

    $(document).on('click keydown', '.gestione-dati-stat-card--clickable', function(e) {
        if (e.type === 'keydown' && e.which !== 13 && e.which !== 32) {
            return;
        }
        if (e.type === 'keydown') {
            e.preventDefault();
        }
        var table = $(this).data('gd-tab');
        if (table) {
            activateTab(table, true);
        }
    });

    $root.on('click', '.gd-column-menu', function(e) {
        e.stopPropagation();
    });

    $root.on('change', '.gd-column-check', function() {
        var table = ($(this).data('table') || '').toString();
        if (table) {
            updateColumnToggleLabel(table);
            applyPickerToTable(table, true);
            if (isStandalone) {
                clearTimeout(standaloneSaveTimer);
                standaloneSaveTimer = setTimeout(function() {
                    saveColumnsStandalone(table);
                }, 400);
            }
        }
    });

    var configForm = document.getElementById('config-form');
    if (configForm && !isStandalone) {
        configForm.addEventListener('submit', function() {
            syncGestioneDatiColumnsPayload();
        });
    }

    $root.on('change', '#csv_table_gestione', function() {
        updateCsvColumnsHint(($(this).val() || '').toString());
    });

    if (activeTable) {
        activateTab(activeTable, false);
    }

    window.syncGestioneDatiColumnsPayload = syncGestioneDatiColumnsPayload;

    window.gestioneDatiArchivio = {
        activateTab: activateTab,
        reloadTable: reloadTable
    };
        })(jQuery);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bootGestioneDatiArchivio);
    } else {
        bootGestioneDatiArchivio();
    }
})();
