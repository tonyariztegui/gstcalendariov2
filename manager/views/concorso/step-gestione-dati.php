<?php
/**
 * Sezione: Gestione Dati (Step 9)
 * Gestione operativa dei dati: Import CSV → Tabella DB
 * Stile allineato al pannello import CSV del concorso.
 */

// Utilizzare $connection già disponibile dal contesto concorso.php
if (!isset($connection)) {
    require_once __DIR__ . '/../../../app/inc/config.php';
    require_once __DIR__ . '/../../../app/inc/db.php';
    $connection = conn_pdo();
}

/**
 * Tabelle consentite per l'import
 */
// Verificare se codice_partecipazione è attivato in campi abilitati
// Utilizzare $content_current che è disponibile da concorso.php
// Se $content_current non è definito, caricarlo dal file JSON
if (!isset($content_current) || !is_array($content_current)) {
    // Fallback: caricare da config se non disponibile
    if (!function_exists('load_json_config')) {
        require_once __DIR__ . '/../../../app/inc/config.php';
    }
    $baseDir = dirname(__DIR__, 2);
    $contentPath = $baseDir . '/app/config/content.json';
    $defaultsPath = $baseDir . '/app/config/content.defaults.json';
    if (function_exists('load_json_config')) {
        $content_defaults_temp = load_json_config($defaultsPath, []);
        $content_current = load_json_config($contentPath, $content_defaults_temp);
    } else {
        $content_current = [];
    }
}
require_once dirname(__DIR__, 2) . '/res/inc/gestione_dati_context.inc.php';
require_once dirname(__DIR__, 2) . '/res/inc/gestione_dati_crud_schema.inc.php';
require_once dirname(__DIR__, 2) . '/res/inc/gestione_dati_columns.inc.php';

$fieldsEnabled = $content_current['fields_enabled'] ?? [];
$codicePartecipazioneEnabled = !empty($fieldsEnabled['codice_partecipazione']);

// Verificare se PDV è attivato (almeno uno dei campi PDV)
$pdvEnabled = !empty($fieldsEnabled['prov_pdv']) || !empty($fieldsEnabled['citta_pdv']) || !empty($fieldsEnabled['indirizzo_pdv']);

// Verificare il mode concorso
if (!isset($content_defaults) || !is_array($content_defaults)) {
    $content_defaults = [];
}
$contestMode = strtolower((string)($content_current['contest_mode'] ?? ($content_defaults['contest_mode'] ?? 'estrazione')));
$isInstantwinMode = ($contestMode === 'instantwin');

$quizEnabledGestione = !empty($content_current['quiz_enabled'] ?? false);
$allowedTables = gestione_dati_allowed_tables($content_current, $content_defaults);
$mergedConfigForCodiceMode = array_merge($content_defaults, $content_current);
$codicePartecipazioneMode = codice_partecipazione_resolve_mode($mergedConfigForCodiceMode);
$codicePartecipazionePoolEnabled = $codicePartecipazioneEnabled
    && codice_partecipazione_mode_requires_pool($codicePartecipazioneMode);
$codicePartecipazioneCombinazioneEnabled = $codicePartecipazioneEnabled && !$codicePartecipazionePoolEnabled;
$gdAllowedTables = $allowedTables;
$gdTableHints = gestione_dati_table_hints_map($connection, $gdAllowedTables, $content_current, $content_defaults);
$gdContext = gestione_dati_resolve_context($connection, $content_current, $content_defaults);
$gdCsvColumnsByTable = [];
foreach ($gdAllowedTables as $gdTbl) {
    $gdCsvColumnsByTable[$gdTbl] = gestione_dati_csv_columns_bundle($gdTbl, $gdContext, $connection);
}
$gdColumnsByTable = gestione_dati_build_all_columns_payload($connection, $gdAllowedTables, $content_current, $content_defaults);
$gdPdvRegistration = gestione_dati_pdv_registration_fields($content_current, $content_defaults);
$gdAjaxBase = 'res/inc/';
$gdIsStandalone = false;
$gdQuizEditorUrl = 'index.php?pg=concorso&concorso_step=10';
$gestioneImportStandalone = false;

function readCsvPreview_gestione(string $path, string $delimiter, bool $hasHeader, int $maxRows = 10): array {
    $handle = fopen($path, 'r');
    if (!$handle) {
        throw new RuntimeException('Impossibile aprire il file CSV.');
    }

    $header = [];
    $rows = [];
    $line = 0;

    while (($data = fgetcsv($handle, 0, $delimiter, '"', '\\')) !== false) {
        $line++;
        if ($line === 1 && $hasHeader) {
            $header = array_map(fn($v) => trim((string)$v), $data);
            continue;
        }
        $rows[] = $data;
        if (count($rows) >= $maxRows) {
            break;
        }
    }

    fclose($handle);
    return ['header' => $header, 'rows' => $rows];
}

function buildDefaultMapping_gestione(array $csvHeader, array $tableColumns): array {
    $mapping = [];
    $tableColsLower = [];
    foreach ($tableColumns as $col) {
        $tableColsLower[strtolower($col)] = $col;
    }
    foreach ($csvHeader as $index => $name) {
        $key = strtolower(trim((string)$name));
        $mapping[$index] = $tableColsLower[$key] ?? '';
    }
    return $mapping;
}

function getTableColumns_gestione(PDO $pdo, string $table): array {
    $cols = [];
    $stmt = $pdo->query('DESCRIBE `' . $table . '`');
    foreach ($stmt as $row) {
        if (!empty($row['Field'])) {
            $cols[] = (string)$row['Field'];
        }
    }
    return $cols;
}

function normalizeDelimiter_gestione(string $delimiter): string {
    return match ($delimiter) {
        'semicolon' => ';',
        'comma' => ',',
        'tab' => "\t",
        default => ';',
    };
}

// Recuperare le tabelle disponibili (utilizzare $connection)
// $tables est maintenant $allowedTables (filtré selon codice_partecipazione)

// Variabili dalla sessione/globale
$selectedTable = (string)($_POST['table'] ?? ($_SESSION['csv_import_gestione']['table'] ?? ''));
$delimiterUi = (string)($_POST['delimiter'] ?? ($_SESSION['csv_import_gestione']['delimiter'] ?? 'semicolon'));
$hasHeader = !empty($_POST['has_header']) || (!isset($_POST['step']) && !empty($_SESSION['csv_import_gestione']['has_header']));

// Utilizzare le variabili di errore/successo definite in concorso.php se disponibili
// Récupérer les messages depuis la session (après redirection PRG)
if (isset($_SESSION['gestione_dati_errors']) && is_array($_SESSION['gestione_dati_errors'])) {
    $errors_gestione = $_SESSION['gestione_dati_errors'];
    unset($_SESSION['gestione_dati_errors']);
} else {
    $errors_gestione = [];
}

if (isset($_SESSION['gestione_dati_success']) && is_array($_SESSION['gestione_dati_success'])) {
    $success_gestione = $_SESSION['gestione_dati_success'];
    unset($_SESSION['gestione_dati_success']);
} else {
    $success_gestione = [];
}

// Dati per rendering (preview)
$preview_gestione = null;
$csvHeader_gestione = [];
$previewRows_gestione = [];
$tableColumns_gestione = [];
$mappingDefaults_gestione = [];

// Nettoyage session + redirect géré dans concorso.php (avant tout output) pour éviter "headers already sent"

// Vérifier que la session existe et que le fichier existe avant d'utiliser (comme dans gestione_dati.php)
if (!empty($_SESSION['csv_import_gestione']['path']) && file_exists((string)$_SESSION['csv_import_gestione']['path']) && $connection) {
    try {
        $sess = $_SESSION['csv_import_gestione'];
        $selectedTable = (string)$sess['table'];
        $delimiterUi = (string)$sess['delimiter'];
        $delimiter = normalizeDelimiter_gestione($delimiterUi);
        $hasHeader = !empty($sess['has_header']);

        $tableColumns_gestione = getTableColumns_gestione($connection, $selectedTable);
        $mappableColumns_gestione = function_exists('gestione_dati_import_mappable_columns')
            ? gestione_dati_import_mappable_columns($selectedTable, $tableColumns_gestione)
            : array_map(static fn($c) => ['name' => (string) $c, 'label' => (string) $c], $tableColumns_gestione);
        $mappableNames_gestione = array_column($mappableColumns_gestione, 'name');
        $preview_gestione = readCsvPreview_gestione((string)$sess['path'], $delimiter, $hasHeader, 10);
        $csvHeader_gestione = $preview_gestione['header'];
        $previewRows_gestione = $preview_gestione['rows'];
        if ($hasHeader) {
            $mappingDefaults_gestione = buildDefaultMapping_gestione($csvHeader_gestione, $mappableNames_gestione);
        }
    } catch (Throwable $e) {
        if (empty($errors_gestione)) {
            $errors_gestione = [];
        }
        $errors_gestione[] = $e->getMessage();
    }
}

$gdMappingVisible = !empty($preview_gestione) && $hasHeader;

// Statistiche
$stats_pdv = 0;
$stats_codici_partecipazione = 0;
$stats_codici_premio = 0;
$stats_quiz_domande = 0;

if ($connection) {
    try {
        $stmt_pdv = $connection->query('SELECT COUNT(*) as total FROM punti_vendita WHERE active = 1');
        $stats_pdv = $stmt_pdv->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
        
        $stmt_part = $connection->query('SELECT COUNT(*) as total FROM codici_partecipazione WHERE codice IS NOT NULL AND codice <> ""');
        $stats_codici_partecipazione = $stmt_part->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
        
        $stmt_premio = $connection->query('SELECT COUNT(*) as total FROM codici_premio WHERE codice IS NOT NULL AND codice <> ""');
        $stats_codici_premio = $stmt_premio->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

        if ($quizEnabledGestione) {
            $stmt_q = $connection->query('SELECT COUNT(*) as total FROM quiz_domande WHERE attiva = 1');
            $stats_quiz_domande = (int) ($stmt_q->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
        }
    } catch (Exception $e) {
        error_log("Errore recupero statistiche: " . $e->getMessage());
    }
}

function h_gestione(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

// Verificare se la sezione Gestione Dati deve essere nascosta.
// In combinazione senza altre feature operative, non ci sono tabelle da gestire.
$shouldHideGestioneDati = (!$isInstantwinMode && !$pdvEnabled && !$codicePartecipazionePoolEnabled && !$quizEnabledGestione);
?>

<div class="card mt-3 mb-4 config-anchor config-step is-hidden configurazione-section gestione-dati-section" data-step="9" id="section-gestione-dati" data-should-hide="<?php echo $shouldHideGestioneDati ? 'true' : 'false'; ?>" data-pdv-enabled="<?php echo $pdvEnabled ? 'true' : 'false'; ?>" data-codice-enabled="<?php echo $codicePartecipazioneEnabled ? 'true' : 'false'; ?>" data-codice-pool-enabled="<?php echo $codicePartecipazionePoolEnabled ? 'true' : 'false'; ?>" data-instantwin-mode="<?php echo $isInstantwinMode ? 'true' : 'false'; ?>" data-quiz-enabled="<?php echo $quizEnabledGestione ? 'true' : 'false'; ?>">
    <div class="card-body">
        <div class="configurazione-hero gestione-dati-hero">
            <div>
                <div class="section-title configurazione-title gestione-dati-title"><i class="fa fa-database mr-2"></i>Gestione Dati</div>
                <p class="text-muted mb-0 gestione-dati-lead">
                    Gestisci i record singoli (CRUD) o importa un CSV completo per PDV, codici e<?php echo $quizEnabledGestione ? ' quiz' : ''; ?> dati operativi.
                </p>
            </div>
            <div class="configurazione-badge gestione-dati-hero-badge">
                <i class="fa fa-file-text-o mr-1"></i> Import guidato
            </div>
        </div>
        <?php if ($codicePartecipazioneCombinazioneEnabled): ?>
            <div class="alert alert-info gestione-dati-hint mb-3" role="status">
                <i class="fa fa-info-circle mr-1"></i>
                Modalità codice: <strong>Controllo combinazione</strong>. Nessun archivio
                <code>codici_partecipazione</code> da gestire in Gestione Dati.
            </div>
        <?php endif; ?>

        <!-- Statistiche: KPI nella stessa riga (CSV + quiz se abilitato) -->
        <div class="configurazione-panel gestione-dati-panel gestione-dati-stats-panel">
            <div class="configurazione-panel-header gestione-dati-panel-header">
                <span class="configurazione-panel-icon"><i class="fa fa-bar-chart"></i></span>
                <div>
                    <h6>Dati operativi</h6>
                    <p>Riepilogo rapido delle tabelle disponibili per import e verifiche.</p>
                </div>
            </div>
        <div class="text-center mb-3 gestione-dati-kpi-heading">
            <span class="text-uppercase small text-muted font-weight-bold gestione-dati-stats-csv-label">Dati operativi</span>
        </div>
        <div class="row mb-4 gestione-dati-stats-row gestione-dati-stats-row--gapped justify-content-center">
            <div class="gestione-dati-stat-col codice-partecipazione-dependent-col pdv-dependent <?php echo $codicePartecipazionePoolEnabled ? 'col-md-4' : 'col-md-6'; ?>" data-codice-enabled="<?php echo $codicePartecipazionePoolEnabled ? 'true' : 'false'; ?>" data-pdv-enabled="<?php echo $pdvEnabled ? 'true' : 'false'; ?>" style="<?php echo !$pdvEnabled ? 'display: none;' : ''; ?>">
                <div class="card text-center border-primary shadow-sm h-100 gestione-dati-stat-card gestione-dati-stat-card--primary gestione-dati-stat-card--clickable" role="button" tabindex="0" data-gd-tab="punti_vendita" title="Apri archivio PDV">
                    <div class="card-body py-3">
                        <div class="gestione-dati-stat-icon"><i class="fa fa-map-marker"></i></div>
                        <h4 class="mb-0"><?php echo number_format($stats_pdv); ?></h4>
                        <small>PDV Attivi</small>
                    </div>
                </div>
            </div>
            <?php if ($codicePartecipazionePoolEnabled): ?>
            <div class="gestione-dati-stat-col codice-partecipazione-dependent col-md-4">
                <div class="card text-center border-primary shadow-sm h-100 gestione-dati-stat-card gestione-dati-stat-card--primary gestione-dati-stat-card--clickable" role="button" tabindex="0" data-gd-tab="codici_partecipazione" title="Apri archivio codici partecipazione">
                    <div class="card-body py-3">
                        <div class="gestione-dati-stat-icon"><i class="fa fa-ticket"></i></div>
                        <h4 class="mb-0"><?php echo number_format($stats_codici_partecipazione); ?></h4>
                        <small>Codici Partecipazione</small>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            <div class="gestione-dati-stat-col codice-partecipazione-dependent-col instantwin-dependent <?php echo $codicePartecipazionePoolEnabled ? 'col-md-4' : 'col-md-6'; ?>" data-codice-enabled="<?php echo $codicePartecipazionePoolEnabled ? 'true' : 'false'; ?>" data-instantwin-mode="<?php echo $isInstantwinMode ? 'true' : 'false'; ?>" style="<?php echo !$isInstantwinMode ? 'display: none;' : ''; ?>">
                <div class="card text-center border-success shadow-sm h-100 gestione-dati-stat-card gestione-dati-stat-card--success gestione-dati-stat-card--clickable" role="button" tabindex="0" data-gd-tab="codici_premio" title="Apri archivio codici premio">
                    <div class="card-body py-3">
                        <div class="gestione-dati-stat-icon"><i class="fa fa-gift"></i></div>
                        <h4 class="mb-0"><?php echo number_format($stats_codici_premio); ?></h4>
                        <small>Codici Premio</small>
                    </div>
                </div>
            </div>
            <div class="gestione-dati-stat-col quiz-dependent-stats col-md-3" style="<?php echo !$quizEnabledGestione ? 'display: none;' : ''; ?>">
                <div class="card text-center border-info shadow-sm h-100 gestione-dati-stat-card gestione-dati-stat-card--info gestione-dati-stat-card--clickable" role="button" tabindex="0" data-gd-tab="quiz_domande" title="Apri archivio quiz">
                    <div class="card-body py-3">
                        <div class="gestione-dati-stat-icon"><i class="fa fa-question-circle"></i></div>
                        <h4 class="mb-0 gestione-dati-quiz-count"><?php echo number_format($stats_quiz_domande); ?></h4>
                        <small class="text-muted">Quiz – Domande attive</small>
                    </div>
                </div>
            </div>
        </div>
        </div>

        <?php if (!empty($errors_gestione)): ?>
            <div class="alert alert-danger gestione-dati-alert">
                <div class="fw-bold mb-2"><i class="fa fa-exclamation-triangle mr-2"></i>Errori</div>
                <ul class="mb-0">
                    <?php foreach ($errors_gestione as $e): ?>
                        <li><?php echo h_gestione((string)$e); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if (!empty($success_gestione)): ?>
            <div class="alert alert-success gestione-dati-alert">
                <div class="fw-bold mb-2"><i class="fa fa-check-circle mr-2"></i>Operazione completata</div>
                <ul class="mb-0">
                    <?php foreach ($success_gestione as $s): ?>
                        <li><?php echo h_gestione((string)$s); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php
        $gdActiveTable = $allowedTables[0] ?? '';
        if ($selectedTable !== '' && in_array($selectedTable, $allowedTables, true)) {
            $gdActiveTable = $selectedTable;
        }
        include __DIR__ . '/partials/gestione-dati-archivio.php';
        ?>

        <?php if ($preview_gestione && $hasHeader): ?>
            <div class="configurazione-panel gestione-dati-panel gestione-dati-mapping-panel">
                <div class="configurazione-panel-header gestione-dati-panel-header gestione-dati-panel-header--split">
                    <span class="configurazione-panel-icon"><i class="fa fa-random"></i></span>
                    <div>
                        <h6>3) Mapping colonne + Anteprima</h6>
                        <p>Associa le colonne e controlla le prime righe prima dell'import.</p>
                    </div>
                    <?php
                    // Construire l'URL avec le paramètre clear_gestione_preview
                    $currentParams = $_GET;
                    $currentParams['clear_gestione_preview'] = '1';
                    $clearUrl = '?' . http_build_query($currentParams);
                    ?>
                    <a href="<?php echo htmlspecialchars($clearUrl, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-sm btn-outline-secondary" onclick="if (typeof Storage !== 'undefined') { sessionStorage.setItem('concorso_active_step', '9'); }" title="Annulla preview e ricomincia">
                        <i class="fa fa-refresh mr-1"></i>Annulla
                    </a>
                </div>
                <div class="gestione-dati-panel-body">
                    <div class="row g-3 mb-3 gestione-dati-summary">
                        <div class="col-md-6">
                            <div class="gestione-dati-summary-box">
                                <div class="fw-bold mb-2"><i class="fa fa-file-text-o mr-2"></i>CSV</div>
                                <div class="text-muted mb-2">File: <span class="mono"><?php echo h_gestione((string)($_SESSION['csv_import_gestione']['name'] ?? '')); ?></span></div>
                                <div class="text-muted">Intestazioni trovate: <strong><?php echo count($csvHeader_gestione); ?></strong></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="gestione-dati-summary-box">
                                <div class="fw-bold mb-2"><i class="fa fa-table mr-2"></i>Tabella DB</div>
                                <div class="text-muted">Tabella: <span class="mono"><?php echo h_gestione($selectedTable); ?></span></div>
                                <div class="text-muted">Colonne: <strong><?php echo count($tableColumns_gestione); ?></strong></div>
                            </div>
                        </div>
                    </div>

                    <hr>

                    <form method="post" id="form-gestione-dati-import">
                        <input type="hidden" name="step" value="import">

                        <div class="mb-3">
                            <div class="form-check gestione-dati-danger-check">
                                <input class="form-check-input" type="checkbox" id="truncate_gestione" name="truncate" value="1">
                                <label class="form-check-label" for="truncate_gestione">
                                    <strong class="text-danger">TRUNCATE</strong> tabella prima dell'import (ATTENZIONE: cancella tutti i dati)
                                </label>
                            </div>
                        </div>

                        <div class="table-responsive mb-3 gestione-dati-table-wrap">
                            <table class="table table-sm table-bordered align-middle gestione-dati-table gestione-dati-mapping-table">
                                <thead>
                                <tr>
                                    <th>Colonna CSV (intestazione)</th>
                                    <th>&rarr; Colonna Tabella (mapping)</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($csvHeader_gestione as $idx => $name): ?>
                                    <tr>
                                        <td class="mono"><?php echo h_gestione((string)$name); ?></td>
                                        <td>
                                            <select class="form-control form-control-sm" name="mapping[<?php echo (int)$idx; ?>]">
                                                <option value="">— Ignora —</option>
                                                <?php
                                                $mapCols = $mappableColumns_gestione ?? array_map(
                                                    static fn($c) => ['name' => (string) $c, 'label' => (string) $c],
                                                    $tableColumns_gestione
                                                );
                                                foreach ($mapCols as $colMeta):
                                                    $col = $colMeta['name'];
                                                    $colLabel = $colMeta['label'];
                                                    $selected = '';
                                                    if (isset($mappingDefaults_gestione[$idx]) && $mappingDefaults_gestione[$idx] === $col) {
                                                        $selected = 'selected';
                                                    }
                                                ?>
                                                    <option value="<?php echo h_gestione($col); ?>" <?php echo $selected; ?>>
                                                        <?php echo h_gestione($colLabel); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="mb-3 gestione-dati-actions">
                            <button type="submit" class="btn btn-success gestione-dati-primary-btn">
                                <i class="fa fa-upload mr-2"></i>Importa nel DB
                            </button>
                        </div>
                    </form>

                    <div class="fw-bold mb-2 gestione-dati-preview-title"><i class="fa fa-search mr-2"></i>Anteprima prime righe (max 10)</div>
                    <div class="table-responsive gestione-dati-table-wrap">
                        <table class="table table-sm table-striped gestione-dati-table gestione-dati-preview-table">
                            <thead>
                            <tr>
                                <?php foreach ($csvHeader_gestione as $name): ?>
                                    <th class="mono"><?php echo h_gestione((string)$name); ?></th>
                                <?php endforeach; ?>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($previewRows_gestione as $r): ?>
                                <tr>
                                    <?php foreach ($csvHeader_gestione as $i => $_): ?>
                                        <td class="mono"><?php echo h_gestione((string)($r[$i] ?? '')); ?></td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php elseif ($preview_gestione && !$hasHeader): ?>
            <div class="alert alert-warning gestione-dati-alert">
                <div class="fw-bold mb-1"><i class="fa fa-info-circle mr-2"></i>Mapping non disponibile</div>
                Per fare il mapping, abilita "intestazione" (prima riga con nomi colonne).
            </div>
        <?php endif; ?>

    </div>
</div>

<script>
// Script pour mettre à jour le label du file input (comme dans Concorso)
(function() {
    document.querySelectorAll('.custom-file-input').forEach(function(input) {
        var label = input.nextElementSibling;
        if (!label) return;
        var defaultText = label.textContent.trim();
        
        input.addEventListener('change', function() {
            if (input.files && input.files[0]) {
                label.textContent = input.files[0].name;
            } else {
                label.textContent = defaultText || 'Scegli file...';
            }
        });
    });
})();

// Impedire che i formulari di import inviino il formulario principale
document.addEventListener('DOMContentLoaded', function() {
    const formPreview = document.getElementById('form-gestione-dati-preview');
    const formImport = document.getElementById('form-gestione-dati-import');
    const configForm = document.getElementById('config-form');
    
    if (formPreview && configForm) {
        // Empêcher la propagation vers le formulaire parent
        formPreview.addEventListener('submit', function(e) {
            e.stopPropagation();
            e.stopImmediatePropagation();
            // Laisser le formulaire se soumettre normalement, mais empêcher la propagation au parent
            return true;
        });
        
        // S'assurer que le formulaire ne déclenche pas la validation du formulaire parent
        formPreview.setAttribute('formnovalidate', 'formnovalidate');
    }
    
    if (formImport && configForm) {
        formImport.addEventListener('submit', function(e) {
            e.stopPropagation();
            // Il formulario verrà inviato normalmente, il trattamento è in concorso.php
        });
    }
});

// Gestione dinamica della visibilità degli elementi in base ai checkbox e al mode concorso
// Utilizza jQuery per garantire compatibilità e funzionamento anche se i checkbox sono in un altro step
(function() {
    function updateGestioneDatiVisibility() {
        // Verifica stato checkbox codice_partecipazione
        const $checkboxCodice = $('#field_codice_partecipazione');
        const codiceEnabled = $checkboxCodice.length > 0 ? $checkboxCodice.is(':checked') : false;
        const codiceMode = ($('#codice_partecipazione_mode').val() || 'pool').toString().toLowerCase();
        const codicePoolEnabled = codiceEnabled && (codiceMode === 'pool' || codiceMode === 'direct_win');
        
        // Verifica stato checkbox PDV (prov_pdv, citta_pdv, indirizzo_pdv)
        const $checkboxPdv = $('#field_pdv_group');
        const pdvEnabled = $checkboxPdv.length > 0 ? $checkboxPdv.is(':checked') : false;
        
        // Verifica mode concorso
        const $contestModeSelect = $('select[name="contest_mode"]');
        const contestMode = $contestModeSelect.length > 0 ? $contestModeSelect.val() : 'estrazione';
        const isInstantwinMode = (contestMode || '').toString().toLowerCase() === 'instantwin';
        
        const quizEnabled = $('#config-quiz-enabled-checkbox').length > 0 ? $('#config-quiz-enabled-checkbox').is(':checked') : false;
        
        // Nascondere se: mode estrazione AND PDV disabilitato AND codice_partecipazione disabilitato AND quiz disabilitato
        const shouldHideSection = (!isInstantwinMode && !pdvEnabled && !codicePoolEnabled && !quizEnabled);
        
        // Nascondere/mostrare la sezione Gestione Dati
        const $gestioneDatiSection = $('#section-gestione-dati');
        if ($gestioneDatiSection.length > 0) {
            // Aggiornare l'attributo data-should-hide per permettere a setActiveStep di gestirlo correttamente
            $gestioneDatiSection.attr('data-should-hide', shouldHideSection ? 'true' : 'false');
            
            if (shouldHideSection) {
                // Nascondere completamente: display: none override tutto
                $gestioneDatiSection.css('display', 'none');
            } else {
                // Rimuovere display: none inline e lasciare che setActiveStep gestisca la visibilità
                $gestioneDatiSection.css('display', '');
                
                // Richiamare setActiveStep per aggiornare correttamente la visibilità
                // Questo assicura che la sezione rispetti la logica di visibilità degli steps
                const currentStep = $('.wizard-step.active').attr('data-step');
                if (currentStep && typeof window.setActiveStep === 'function') {
                    // Richiamare setActiveStep per aggiornare la visibilità correttamente
                    setTimeout(function() {
                        window.setActiveStep(currentStep, { scroll: false });
                    }, 10);
                } else {
                    // Fallback: gestire is-hidden manualmente
                    const isActiveStep = $('.wizard-step[data-step="9"]').hasClass('active');
                    if (!isActiveStep) {
                        $gestioneDatiSection.addClass('is-hidden');
                    } else {
                        $gestioneDatiSection.removeClass('is-hidden');
                    }
                }
            }
        }
        
        // Nascondere/mostrare l'onglet "Gestione Dati" nella navigazione
        const $gestioneDatiTab = $('.wizard-step[data-step="9"], .gestione-dati-tab');
        if ($gestioneDatiTab.length > 0) {
            const wasVisible = $gestioneDatiTab.is(':visible');
            $gestioneDatiTab.toggle(!shouldHideSection);
            
            // Se l'onglet viene nascosto e l'utente è su questo step, reindirizzare al primo step disponibile
            if (shouldHideSection && wasVisible && $gestioneDatiTab.hasClass('active')) {
                const $firstStep = $('.wizard-step').not('.gestione-dati-tab').filter(':visible').first();
                if ($firstStep.length > 0) {
                    const step = $firstStep.attr('data-step');
                    if (step) {
                        // Trigger click sul primo step disponibile
                        setTimeout(function() {
                            $firstStep.trigger('click');
                        }, 100);
                    }
                }
            }
            
            // Aggiornare il progresso per riflettere il nuovo numero di steps visibili
            if (window.__concorsoWizardInit && typeof window.updateProgress === 'function') {
                setTimeout(function() {
                    window.updateProgress();
                }, 50);
            }
        }
        
        // Aggiorna la visibilità della card statistiche "Codici Partecipazione"
        const $codicePartecipazioneCard = $('.codice-partecipazione-dependent');
        if ($codicePartecipazioneCard.length > 0) {
            $codicePartecipazioneCard.toggle(codicePoolEnabled);
        }
        
        // Aggiorna la visibilità della card statistiche "PDV Attivi"
        const $pdvCard = $('.pdv-dependent');
        if ($pdvCard.length > 0) {
            $pdvCard.toggle(pdvEnabled);
        }
        
        // Aggiorna la visibilità della card statistiche "Codici Premio" (solo in mode instantwin)
        const $codiciPremioCard = $('.instantwin-dependent');
        if ($codiciPremioCard.length > 0) {
            $codiciPremioCard.toggle(isInstantwinMode);
        }
        
        const $quizStatsCard = $('.quiz-dependent-stats');
        if ($quizStatsCard.length > 0) {
            $quizStatsCard.toggle(quizEnabled);
        }
        
        // Larghezza colonne statistiche in base al numero di card visibili
        const $statCols = $('.gestione-dati-stat-col');
        let visibleStatCols = 0;
        $statCols.each(function() {
            if ($(this).is(':visible')) visibleStatCols++;
        });
        let statColClass = 'col-12';
        if (visibleStatCols === 2) statColClass = 'col-md-6';
        else if (visibleStatCols === 3) statColClass = 'col-md-4';
        else if (visibleStatCols >= 4) statColClass = 'col-md-3';
        $statCols.each(function() {
            const $c = $(this);
            $c.removeClass('col-md-3 col-md-4 col-md-6 col-12');
            if ($c.is(':visible')) {
                $c.addClass(statColClass);
            }
        });
        
        // Aggiorna le opzioni nel select delle tabelle
        const $selectTable = $('#csv_table_gestione');
        if ($selectTable.length > 0) {
            // Opzione codici_partecipazione
            const $codiceOption = $selectTable.find('option[value="codici_partecipazione"]');
            if ($codiceOption.length > 0) {
                if (codicePoolEnabled) {
                    $codiceOption.show();
                } else {
                    $codiceOption.hide();
                    if ($selectTable.val() === 'codici_partecipazione') {
                        $selectTable.val('');
                    }
                }
            }
            
            
            // Opzione punti_vendita
            const $pdvOption = $selectTable.find('option[value="punti_vendita"]');
            if ($pdvOption.length > 0) {
                if (pdvEnabled) {
                    $pdvOption.show();
                } else {
                    $pdvOption.hide();
                    if ($selectTable.val() === 'punti_vendita') {
                        $selectTable.val('');
                    }
                }
            }
            
            // Opzione codici_premio (solo in mode instantwin)
            const $premioOption = $selectTable.find('option[value="codici_premio"]');
            if ($premioOption.length > 0) {
                if (isInstantwinMode) {
                    $premioOption.show();
                } else {
                    $premioOption.hide();
                    if ($selectTable.val() === 'codici_premio') {
                        $selectTable.val('');
                    }
                }
            }
            
            const $quizOpt = $selectTable.find('option[value="quiz_domande"]');
            if ($quizOpt.length > 0) {
                if (quizEnabled) {
                    $quizOpt.show();
                } else {
                    $quizOpt.hide();
                    if ($selectTable.val() === 'quiz_domande') {
                        $selectTable.val('');
                    }
                }
            }
        }
    }
    
    // Funzione per inizializzare quando il DOM è pronto
    function initGestioneDatiVisibility() {
        // Attendi che jQuery sia disponibile
        if (typeof jQuery === 'undefined') {
            setTimeout(initGestioneDatiVisibility, 100);
            return;
        }
        
        // Attendi che il wizard sia inizializzato
        if (!window.__concorsoWizardInit) {
            setTimeout(initGestioneDatiVisibility, 100);
            return;
        }
        
        // Esegui l'aggiornamento iniziale
        updateGestioneDatiVisibility();
        
        // Aggiorna il progresso dopo un breve delay per assicurarsi che tutto sia inizializzato
        setTimeout(function() {
            if (typeof window.updateProgress === 'function') {
                window.updateProgress();
            }
        }, 200);
        
        // Validation avant soumission (comme dans gestione_dati.php)
        $('#form-gestione-dati-preview').on('submit', function(e) {
            const $selectTable = $('#csv_table_gestione');
            const $fileInput = $('#csv_file_gestione');
            
            const tableSelected = $selectTable.val() !== '' && $selectTable.val() !== null;
            const fileSelected = $fileInput[0].files && $fileInput[0].files.length > 0;
            
            if (!tableSelected || !fileSelected) {
                e.preventDefault();
                e.stopPropagation();
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Informazioni mancanti',
                        text: 'Seleziona una tabella e carica un file CSV prima di procedere.',
                        confirmButtonText: 'OK'
                    });
                } else {
                    alert('Seleziona una tabella e carica un file CSV prima di procedere.');
                }
                return false;
            }
            
            // Sauvegarder dans sessionStorage qu'on doit rester sur le step 9 après le rechargement
            if (typeof Storage !== 'undefined') {
                sessionStorage.setItem('concorso_active_step', '9');
            }
            
            // Laisser le formulaire se soumettre normalement, mais empêcher la propagation au parent
            e.stopPropagation();
            return true;
        });
        
        // Ascolta i cambiamenti del checkbox codice_partecipazione
        $(document).on('change', '#field_codice_partecipazione', updateGestioneDatiVisibility);
        $(document).on('click', '#field_codice_partecipazione', function() {
            setTimeout(updateGestioneDatiVisibility, 10);
        });
        $(document).on('change', '#codice_partecipazione_mode', updateGestioneDatiVisibility);
        
        // Ascolta i cambiamenti del checkbox PDV
        $(document).on('change', '#field_pdv_group', updateGestioneDatiVisibility);
        $(document).on('click', '#field_pdv_group', function() {
            setTimeout(updateGestioneDatiVisibility, 10);
        });
        
        // Ascolta i cambiamenti del mode concorso
        const $contestModeSelect = $('select[name="contest_mode"]');
        if ($contestModeSelect.length > 0) {
            $contestModeSelect.on('change', updateGestioneDatiVisibility);
        }
        
        $(document).on('change', '#config-quiz-enabled-checkbox', updateGestioneDatiVisibility);

        window.updateGestioneDatiVisibility = updateGestioneDatiVisibility;
    }
    
    // Inizializza quando il DOM è pronto
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initGestioneDatiVisibility);
    } else {
        // DOM già caricato
        initGestioneDatiVisibility();
    }
})();
</script>

<?php
if (!isset($GLOBALS['gestione_dati_archivio_script'])) {
    $GLOBALS['gestione_dati_archivio_script'] = true;
    echo '<script src="res/js/gestione-dati-archivio.js"></script>';
}
?>

