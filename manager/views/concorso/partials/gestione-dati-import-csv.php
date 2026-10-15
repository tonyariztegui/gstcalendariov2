<?php
/**
 * Form upload CSV per Gestione Dati (incluso nell'archivio).
 *
 * Variabili attese dal parent step-gestione-dati.php / gestione_dati.php:
 * - $allowedTables, $selectedTable, $delimiterUi, $hasHeader
 * - $codicePartecipazioneEnabled, $pdvEnabled, $isInstantwinMode, $quizEnabledGestione
 * - $gestioneImportStandalone (bool) — true su gestione_dati.php
 */

if (!isset($gestioneImportStandalone)) {
    $gestioneImportStandalone = false;
}
if (!isset($allowedTables)) {
    $allowedTables = [];
}
if (!isset($gdCsvColumnsByTable) || !is_array($gdCsvColumnsByTable)) {
    $gdCsvColumnsByTable = [];
}
$tableLabels = gestione_dati_table_labels();
$formAction = htmlspecialchars($_SERVER['REQUEST_URI'] ?? '', ENT_QUOTES, 'UTF-8');
?>

<form method="post" enctype="multipart/form-data" id="form-gestione-dati-preview" class="gestione-dati-import-form" action="<?php echo $formAction; ?>" novalidate>
    <input type="hidden" name="step" value="preview">
    <?php if (!$gestioneImportStandalone): ?>
        <input type="hidden" name="gestione_dati_only" value="1">
    <?php endif; ?>
    <div class="row g-3 gestione-dati-form-grid">
        <div class="col-md-4 gestione-dati-field">
            <label class="form-label" for="csv_table_gestione">Tabella</label>
            <select id="csv_table_gestione" name="table" class="form-control">
                <option value="">— Seleziona —</option>
                <?php
                foreach ($allowedTables as $t):
                    $label = $tableLabels[$t] ?? $t;
                ?>
                    <option value="<?php echo h_gestione($t); ?>" <?php echo ($t === $selectedTable) ? 'selected' : ''; ?>>
                        <?php echo h_gestione($label); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <small class="form-text text-muted">Sincronizzata con la scheda attiva in Archivio dati.</small>
        </div>
        <div class="col-12 gestione-dati-csv-columns-hint d-none" id="gestione-dati-csv-columns-hint">
            <div class="alert alert-light border small mb-0 py-2 gestione-dati-hint">
                <strong>Colonne attese</strong> (obbligatorie in grassetto):
                <span class="gd-csv-columns-list mono"></span>
            </div>
        </div>
        <script type="application/json" id="gestione-dati-csv-columns-data"><?php echo htmlspecialchars(json_encode($gdCsvColumnsByTable, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8'); ?></script>
        <div class="col-md-4 gestione-dati-field">
            <label class="form-label" for="csv_delimiter_gestione">Delimitatore</label>
            <select id="csv_delimiter_gestione" name="delimiter" class="form-control">
                <option value="semicolon" <?php echo ($delimiterUi === 'semicolon') ? 'selected' : ''; ?>>; (punto e virgola)</option>
                <option value="comma" <?php echo ($delimiterUi === 'comma') ? 'selected' : ''; ?>>, (virgola)</option>
                <option value="tab" <?php echo ($delimiterUi === 'tab') ? 'selected' : ''; ?>>TAB</option>
            </select>
        </div>
        <div class="col-md-4 gestione-dati-field">
            <label class="form-label" for="csv_file_gestione">File CSV</label>
            <div class="custom-file">
                <input type="file" id="csv_file_gestione" name="csv_file" class="custom-file-input" accept=".csv,text/csv">
                <label class="custom-file-label" for="csv_file_gestione">Scegli CSV...</label>
            </div>
        </div>
        <div class="col-12">
            <div class="form-check gestione-dati-check">
                <input class="form-check-input" type="checkbox" id="has_header_gestione" name="has_header" value="1" <?php echo !empty($hasHeader) ? 'checked' : ''; ?>>
                <label class="form-check-label" for="has_header_gestione">Il CSV ha una riga di intestazione (header)</label>
            </div>
        </div>
        <div class="col-12 gestione-dati-actions">
            <button type="submit" id="btn-preview-mapping" class="btn btn-primary gestione-dati-primary-btn btn-sm">
                <i class="fa fa-eye mr-1"></i>Anteprima &amp; Mapping
            </button>
        </div>
    </div>
</form>
