<?php
/**
 * Archivio CRUD Gestione Dati — tabs, toolbar, DataTables, modal.
 *
 * Variabili attese:
 * - $gdAllowedTables (list<string>)
 * - $gdAjaxBase (string) es. res/inc/
 * - $gdIsStandalone (bool)
 * - $gdQuizEditorUrl (string)
 * - $gdMappingVisible (bool) — preview import in corso
 */

if (!isset($gdAllowedTables) || !is_array($gdAllowedTables)) {
    $gdAllowedTables = [];
}
if (!isset($gdAjaxBase)) {
    $gdAjaxBase = 'res/inc/';
}
if (!isset($gdIsStandalone)) {
    $gdIsStandalone = false;
}
if (!isset($gdQuizEditorUrl)) {
    $gdQuizEditorUrl = 'index.php?pg=quiz_domande';
}
if (!isset($gdMappingVisible)) {
    $gdMappingVisible = false;
}
if (!isset($gdTableHints) || !is_array($gdTableHints)) {
    $gdTableHints = [];
}
if (!isset($gdCsvColumnsByTable) || !is_array($gdCsvColumnsByTable)) {
    $gdCsvColumnsByTable = [];
}
if (!isset($gdContext) || !is_array($gdContext)) {
    $gdContext = [];
}
if (!isset($gdColumnsByTable) || !is_array($gdColumnsByTable)) {
    $gdColumnsByTable = [];
}
if (!isset($gdPdvRegistration) || !is_array($gdPdvRegistration)) {
    $gdPdvRegistration = [];
}

$gdTableLabels = gestione_dati_table_labels();
if (!isset($gdActiveTable) || $gdActiveTable === '' || !in_array($gdActiveTable, $gdAllowedTables, true)) {
    $gdActiveTable = $gdAllowedTables[0] ?? '';
}
$gdFirstTable = $gdActiveTable;
?>

<div class="configurazione-panel gestione-dati-panel gestione-dati-archivio-panel"
     id="gestione-dati-archivio"
     data-ajax-base="<?php echo h_gestione(rtrim($gdAjaxBase, '/') . '/'); ?>"
     data-first-table="<?php echo h_gestione($gdFirstTable); ?>"
     data-standalone="<?php echo $gdIsStandalone ? '1' : '0'; ?>"
     data-quiz-editor-url="<?php echo h_gestione($gdQuizEditorUrl); ?>"
     data-columns-config="<?php echo h_gestione(json_encode($gdColumnsByTable, JSON_UNESCAPED_UNICODE)); ?>"
     data-csv-columns="<?php echo h_gestione(json_encode($gdCsvColumnsByTable, JSON_UNESCAPED_UNICODE)); ?>">

    <div class="configurazione-panel-header gestione-dati-panel-header">
        <span class="configurazione-panel-icon"><i class="fa fa-table"></i></span>
        <div>
            <h6>Archivio dati</h6>
            <p>Consulta, crea, modifica ed esporta i record. Filtrato in base alle opzioni attive del concorso.
                <?php if (empty($gdIsStandalone)): ?>
                    Colonne visibili salvate con <strong>Salva configurazione</strong>.
                <?php endif; ?>
            </p>
        </div>
    </div>

    <?php if (empty($gdAllowedTables)): ?>
        <div class="alert alert-light border gestione-dati-alert mb-0">
            Nessuna tabella operativa richiesta per la configurazione attuale. Abilita PDV, codice partecipazione, Instant Win o Quiz.
        </div>
    <?php else: ?>

    <?php if (!empty($gdTableHints['_global'])): ?>
        <div class="alert alert-info gestione-dati-hint gd-global-hint mb-3" role="status">
            <i class="fa fa-info-circle mr-1"></i><?php echo h_gestione($gdTableHints['_global']); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($gdContext['codice_mode_label']) || !empty($gdContext['iw_distribution_label'])): ?>
        <div class="gestione-dati-context-badges mb-3 small text-muted">
            <?php if (!empty($gdContext['codice_field_enabled']) && !empty($gdContext['codice_mode_label'])): ?>
                <span class="badge badge-light border mr-1">Codice: <?php echo h_gestione((string) $gdContext['codice_mode_label']); ?></span>
            <?php endif; ?>
            <?php if (!empty($gdContext['is_instantwin']) && !empty($gdContext['iw_distribution_label'])): ?>
                <span class="badge badge-light border">Premi IW: <?php echo h_gestione((string) $gdContext['iw_distribution_label']); ?></span>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <ul class="nav nav-tabs gestione-dati-tabs" role="tablist">
        <?php foreach ($gdAllowedTables as $i => $tbl): ?>
            <li class="nav-item">
                <a class="nav-link gestione-dati-tab-link <?php echo $tbl === $gdActiveTable ? 'active' : ''; ?>"
                   id="gd-tab-<?php echo h_gestione($tbl); ?>"
                   data-toggle="tab"
                   href="#gd-pane-<?php echo h_gestione($tbl); ?>"
                   role="tab"
                   data-table="<?php echo h_gestione($tbl); ?>"
                   data-gd-tab="<?php echo h_gestione($tbl); ?>">
                    <?php echo h_gestione($gdTableLabels[$tbl] ?? $tbl); ?>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>

    <div class="tab-content gestione-dati-tab-content pt-3">
        <?php foreach ($gdAllowedTables as $i => $tbl): ?>
            <div class="tab-pane fade <?php echo $tbl === $gdActiveTable ? 'show active' : ''; ?>"
                 id="gd-pane-<?php echo h_gestione($tbl); ?>"
                 role="tabpanel"
                 data-table="<?php echo h_gestione($tbl); ?>">

                <?php if (!empty($gdTableHints[$tbl])): ?>
                    <div class="alert alert-info gestione-dati-hint gd-table-hint mb-3 py-2 small" role="status">
                        <i class="fa fa-info-circle mr-1"></i><?php echo h_gestione($gdTableHints[$tbl]); ?>
                        <?php if ($tbl === 'punti_vendita' && !empty($gdPdvRegistration['registration_line'])): ?>
                            <div class="gestione-dati-registration-hint mt-2">
                                <strong>Campi in registrazione</strong>
                                <?php if (!empty($gdPdvRegistration['mode_label'])): ?>
                                    <span class="text-muted">(<?php echo h_gestione((string) $gdPdvRegistration['mode_label']); ?>)</span>
                                <?php endif; ?>
                                <ul class="mb-0 mt-1 pl-3">
                                    <?php foreach ($gdPdvRegistration['field_labels'] ?? [] as $fl): ?>
                                        <li><?php echo h_gestione((string) $fl); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <div class="gestione-dati-toolbar d-flex flex-wrap align-items-center mb-3">
                    <div class="gestione-dati-toolbar-filters d-flex flex-wrap align-items-center mr-auto">
                        <?php if ($tbl === 'punti_vendita' || $tbl === 'quiz_domande' || str_starts_with($tbl, 'codici_')): ?>
                            <label class="small text-muted mb-0 mr-1">Stato</label>
                            <select class="form-control form-control-sm gd-filter-active mr-2" style="width:auto;min-width:110px;">
                                <option value="all">Tutti</option>
                                <option value="1"><?php echo $tbl === 'punti_vendita' ? 'Attivi' : ($tbl === 'quiz_domande' ? 'Attive' : 'Attivi'); ?></option>
                                <option value="0"><?php echo $tbl === 'punti_vendita' ? 'Inattivi' : ($tbl === 'quiz_domande' ? 'Disattive' : 'Inattivi'); ?></option>
                            </select>
                        <?php endif; ?>
                        <?php if ($tbl === 'codici_partecipazione' || $tbl === 'codici_premio'): ?>
                            <label class="small text-muted mb-0 mr-1">Utilizzo</label>
                            <select class="form-control form-control-sm gd-filter-used mr-2" style="width:auto;min-width:110px;">
                                <option value="all">Tutti</option>
                                <option value="0">Liberi</option>
                                <option value="1">Usati</option>
                            </select>
                        <?php endif; ?>
                        <div class="dropdown gd-column-picker-wrap mr-2">
                            <?php
                            $tblCatalog = $gdColumnsByTable[$tbl]['catalog'] ?? [];
                            $tblVisible = $gdColumnsByTable[$tbl]['visible'] ?? [];
                            $visibleFlip = array_flip($tblVisible);
                            $optionalVisibleCount = 0;
                            foreach ($tblCatalog as $colDef) {
                                if (!empty($colDef['locked'])) {
                                    continue;
                                }
                                $colKey = (string) ($colDef['data'] ?? '');
                                if ($colKey === '' || $colKey === 'azioni') {
                                    continue;
                                }
                                if (isset($visibleFlip[$colKey])) {
                                    $optionalVisibleCount++;
                                }
                            }
                            ?>
                            <button type="button"
                                    class="btn btn-sm btn-outline-secondary dropdown-toggle gd-column-toggle"
                                    data-table="<?php echo h_gestione($tbl); ?>"
                                    data-toggle="dropdown"
                                    aria-haspopup="true"
                                    aria-expanded="false"
                                    title="Colonne visibili">
                                Colonne<?php echo $optionalVisibleCount > 0 ? ' (' . (int) $optionalVisibleCount . ')' : ''; ?>
                            </button>
                            <div class="dropdown-menu dropdown-menu-right gd-column-menu p-2" data-table="<?php echo h_gestione($tbl); ?>">
                                <?php foreach ($tblCatalog as $colDef):
                                    if (!empty($colDef['locked'])) {
                                        continue;
                                    }
                                    $colKey = (string) ($colDef['data'] ?? '');
                                    if ($colKey === '' || $colKey === 'azioni') {
                                        continue;
                                    }
                                    $colTitle = (string) ($colDef['title'] ?? $colKey);
                                    $inputId = 'gd-col-' . $tbl . '-' . preg_replace('/[^a-z0-9_-]/i', '_', $colKey);
                                ?>
                                    <div class="custom-control custom-checkbox gd-column-item mb-1">
                                        <input type="checkbox"
                                               class="custom-control-input gd-column-check"
                                               id="<?php echo h_gestione($inputId); ?>"
                                               value="<?php echo h_gestione($colKey); ?>"
                                               data-table="<?php echo h_gestione($tbl); ?>"
                                               <?php echo isset($visibleFlip[$colKey]) ? 'checked' : ''; ?>>
                                        <label class="custom-control-label" for="<?php echo h_gestione($inputId); ?>">
                                            <?php echo h_gestione($colTitle); ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <div class="gestione-dati-toolbar-actions btn-group-sm d-flex flex-wrap align-items-center">
                        <button type="button" class="btn btn-sm btn-success gd-btn-new" data-table="<?php echo h_gestione($tbl); ?>">
                            <i class="fa fa-plus mr-1"></i>Nuovo
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary gd-btn-export" data-table="<?php echo h_gestione($tbl); ?>">
                            <i class="fa fa-download mr-1"></i>Esporta CSV
                        </button>
                        <?php if ($tbl === 'quiz_domande'): ?>
                            <a href="<?php echo h_gestione($gdQuizEditorUrl); ?>" class="btn btn-sm btn-outline-primary" target="_blank" rel="noopener">
                                <i class="fa fa-external-link mr-1"></i>Editor completo
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="table-responsive gestione-dati-table-wrap">
                    <table class="table table-sm table-striped table-bordered gestione-dati-crud-table w-100"
                           id="gd-table-<?php echo h_gestione($tbl); ?>"
                           data-table="<?php echo h_gestione($tbl); ?>">
                        <thead><tr></tr></thead>
                    </table>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="gestione-dati-import-collapse mt-4">
        <button class="btn btn-link btn-sm gestione-dati-import-toggle p-0" type="button" data-toggle="collapse" data-target="#gestione-dati-import-collapse" aria-expanded="<?php echo !empty($gdMappingVisible) ? 'true' : 'false'; ?>">
            <i class="fa fa-upload mr-1"></i>Import massivo CSV (sostituisce o aggiunge in blocco)
        </button>
        <div class="collapse <?php echo $gdMappingVisible ? 'show' : ''; ?>" id="gestione-dati-import-collapse">
            <div class="mt-2">
                <?php include __DIR__ . '/gestione-dati-import-csv.php'; ?>
            </div>
        </div>
    </div>

    <?php endif; ?>
</div>

<div class="modal fade" id="gestione-dati-crud-modal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="gestione-dati-crud-modal-title">Record</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Chiudi"><span aria-hidden="true">&times;</span></button>
            </div>
            <form id="gestione-dati-crud-form">
                <div class="modal-body" id="gestione-dati-crud-modal-body">
                    <input type="hidden" name="id" id="gd-crud-id" value="">
                    <input type="hidden" name="table" id="gd-crud-table" value="">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Annulla</button>
                    <button type="submit" class="btn btn-primary" id="gestione-dati-crud-save">Salva</button>
                </div>
            </form>
        </div>
    </div>
</div>
