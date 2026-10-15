<?php

include("_header.php");
include("res/inc/utenze.php");
include("res/inc/header.php");
include("res/inc/sanitize.inc.php");

$_title = 'Gestione Dati';

// Vérifier si codice_partecipazione est activé dans campi abilitati
$fieldsEnabled = $contest_config['fields_enabled'] ?? [];
$codicePartecipazioneEnabled = !empty($fieldsEnabled['codice_partecipazione']);
function h_gestione(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function normalizeDelimiter_gestione(string $delimiter): string {
    return match ($delimiter) {
        'semicolon' => ';',
        'comma' => ',',
        'tab' => "\t",
        default => ';',
    };
}

function safeTableName_gestione(string $table): ?string {
    $table = trim($table);
    if ($table === '' || !preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
        return null;
    }
    return $table;
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

// Tabelle consentite (filtrate per configurazione concorso)
require_once __DIR__ . '/res/inc/gestione_dati_context.inc.php';
require_once __DIR__ . '/res/inc/gestione_dati_crud_schema.inc.php';
require_once __DIR__ . '/res/inc/gestione_dati_columns.inc.php';
$contentForGd = is_array($contest_config ?? null) ? $contest_config : [];
$gdDefaults = [];
$defaultsPathGd = dirname(__DIR__) . '/app/config/content.defaults.json';
if (is_file($defaultsPathGd) && function_exists('load_json_config')) {
    $gdDefaults = load_json_config($defaultsPathGd, []);
}
$allowedTables = gestione_dati_allowed_tables($contentForGd, $gdDefaults);
$pdvEnabledStandalone = in_array('punti_vendita', $allowedTables, true);
$codicePartecipazioneEnabled = in_array('codici_partecipazione', $allowedTables, true);
$isInstantwinStandalone = in_array('codici_premio', $allowedTables, true);
$quizEnabledGestioneStandalone = in_array('quiz_domande', $allowedTables, true);

// Pulizia dei CSV orfani più vecchi di 24 ore
$upload_dir_csv = __DIR__ . '/../uploads/csv/';
if (is_dir($upload_dir_csv)) {
    $maxAge = 24 * 3600;
    $now = time();
    $currentSessionPath = isset($_SESSION['csv_import_gestione']['path']) ? (string)$_SESSION['csv_import_gestione']['path'] : '';
    foreach (glob($upload_dir_csv . 'gestione_*.csv') ?: [] as $file) {
        if ($file === $currentSessionPath) continue;
        if (($now - filemtime($file)) > $maxAge) {
            @unlink($file);
        }
    }
}

// Variabili
$errors = [];
$success = [];
$step = $_POST['step'] ?? 'form';
$selectedTable = (string)($_POST['table'] ?? ($_SESSION['csv_import_gestione']['table'] ?? ''));
$delimiterUi = (string)($_POST['delimiter'] ?? ($_SESSION['csv_import_gestione']['delimiter'] ?? 'semicolon'));
$hasHeader = !empty($_POST['has_header']) || (!isset($_POST['step']) && !empty($_SESSION['csv_import_gestione']['has_header']));
$truncate = !empty($_POST['truncate']);

// Trattamento POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['step'])) {
    // Passo preview
    if ($step === 'preview') {
        $safeTable = safeTableName_gestione($selectedTable);
        if (!$safeTable || !in_array($safeTable, $allowedTables, true)) {
            $errors[] = 'Seleziona una tabella valida.';
        }
        
        if (empty($_FILES['csv_file']) || ($_FILES['csv_file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $errors[] = 'Carica un file CSV valido.';
        }
        
        if (!$errors && $connection) {
            $tmpName = (string)$_FILES['csv_file']['tmp_name'];
            $originalName = (string)$_FILES['csv_file']['name'];
            $upload_dir = __DIR__ . '/../uploads/csv/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            // Rimuovere il file precedente dalla sessione prima di crearne uno nuovo
            if (isset($_SESSION['csv_import_gestione']['path']) && file_exists((string)$_SESSION['csv_import_gestione']['path'])) {
                @unlink((string)$_SESSION['csv_import_gestione']['path']);
            }
            $dest = $upload_dir . 'gestione_' . bin2hex(random_bytes(8)) . '.csv';
            
            if (!move_uploaded_file($tmpName, $dest)) {
                $errors[] = 'Impossibile salvare il file caricato.';
            } else {
                $_SESSION['csv_import_gestione'] = [
                    'path' => $dest,
                    'name' => $originalName,
                    'table' => $safeTable,
                    'delimiter' => $delimiterUi,
                    'has_header' => $hasHeader ? 1 : 0,
                ];
            }
        }
    }
    
    // Passo import
    if ($step === 'import' && $connection) {
        gestione_dati_raise_import_limits();
        $sess = $_SESSION['csv_import_gestione'] ?? null;
        if (!is_array($sess) || empty($sess['path']) || empty($sess['table'])) {
            $errors[] = 'Sessione mancante: rifai la preview e poi importa.';
        } else {
            $safeTable = safeTableName_gestione((string)$sess['table']);
            if (!$safeTable || !in_array($safeTable, $allowedTables, true)) {
                $errors[] = 'Tabella non valida nella sessione.';
            }
            if (empty($sess['path']) || !file_exists((string)$sess['path'])) {
                $errors[] = 'File CSV non trovato. Rifai la preview.';
            }
        }
        
        $mapping = $_POST['mapping'] ?? [];
        if (!$errors && (!is_array($mapping) || count($mapping) === 0)) {
            $errors[] = 'Mapping non valido.';
        }
        
        if (!$errors) {
            $csvPath = (string)$sess['path'];
            $delimiter = normalizeDelimiter_gestione((string)($sess['delimiter'] ?? 'semicolon'));
            $hasHeader = !empty($sess['has_header']);
            $tableColumns = getTableColumns_gestione($connection, $safeTable);
            
            $mapped = [];
            foreach ($mapping as $csvIndex => $colName) {
                $csvIndexInt = (int)$csvIndex;
                $colName = (string)$colName;
                if ($colName === '' || !in_array($colName, $tableColumns, true)) {
                    continue;
                }
                $mapped[$csvIndexInt] = $colName;
            }
            
            if (!$mapped) {
                $errors[] = 'Seleziona almeno una colonna da importare.';
            } else {
                $columns = array_values(array_unique(array_values($mapped)));
                
                // Validazione codici_premio: conteggio totale e per prize_id (deve coincidere con premi.quantita_totale)
                $codiciPremioValidationOk = true;
                $codiciPartecipazioneValidationOk = true;
                if ($safeTable === 'codici_premio') {
                    $prizeIdCsvIndex = null;
                    foreach ($mapped as $idx => $col) {
                        if ($col === 'prize_id') {
                            $prizeIdCsvIndex = $idx;
                            break;
                        }
                    }
                    if ($prizeIdCsvIndex !== null) {
                        $csvCountsByPrize = [];
                        $h = fopen($csvPath, 'r');
                        if ($h) {
                            $ln = 0;
                            while (($r = fgetcsv($h, 0, $delimiter, '"', '\\')) !== false) {
                                $ln++;
                                if ($ln === 1 && $hasHeader) {
                                    continue;
                                }
                                $pid = isset($r[$prizeIdCsvIndex]) ? trim((string)$r[$prizeIdCsvIndex]) : '';
                                $pid = ($pid !== '' && is_numeric($pid)) ? (int)$pid : 0;
                                if ($pid > 0) {
                                    $csvCountsByPrize[$pid] = ($csvCountsByPrize[$pid] ?? 0) + 1;
                                }
                            }
                            fclose($h);
                        }
                        $premiRows = $connection->query("SELECT id, quantita_totale FROM premi WHERE is_active = 1")->fetchAll(PDO::FETCH_ASSOC);
                        if (empty($premiRows)) {
                            $errors[] = "Nessun premio attivo configurato. Configura prima i Premi nel concorso (Dati concorso > Tipi di premi).";
                            $codiciPremioValidationOk = false;
                        }
                        foreach ($premiRows ?: [] as $pr) {
                            $pid = (int)$pr['id'];
                            $qt = (int)($pr['quantita_totale'] ?? 0);
                            $csvCount = (int)($csvCountsByPrize[$pid] ?? 0);
                            if ($truncate) {
                                if ($csvCount !== $qt) {
                                    $errors[] = "Premio ID {$pid}: il CSV contiene {$csvCount} codici, ma ne servono {$qt} (quantità configurata in Premi).";
                                    $codiciPremioValidationOk = false;
                                }
                            } else {
                                $stmtExist = $connection->prepare('SELECT COUNT(*) FROM codici_premio WHERE prize_id = ?');
                                $stmtExist->execute([$pid]);
                                $existingCount = (int)$stmtExist->fetchColumn();
                                $afterImport = $existingCount + $csvCount;
                                if ($afterImport !== $qt) {
                                    $errors[] = "Premio ID {$pid}: dopo l'import avresti {$afterImport} codici (esistenti: {$existingCount}, CSV: {$csvCount}), ma ne servono {$qt}.";
                                    $codiciPremioValidationOk = false;
                                }
                            }
                        }
                        $expectedTotal = array_sum(array_column($premiRows ?: [], 'quantita_totale'));
                        $csvTotalWithPrize = array_sum($csvCountsByPrize);
                        if ($truncate && $expectedTotal > 0 && $csvTotalWithPrize !== (int)$expectedTotal) {
                            $errors[] = "Totale: il CSV contiene {$csvTotalWithPrize} codici con prize_id valido, ma la somma delle quantità nei Premi è " . (int)$expectedTotal . ".";
                            $codiciPremioValidationOk = false;
                        }
                    } else {
                        $errors[] = "Per codici_premio è obbligatorio mappare la colonna prize_id per verificare la coerenza con i Premi.";
                        $codiciPremioValidationOk = false;
                    }
                }
                
                // Validazione codici_partecipazione: lunghezza campo codice deve coincidere con Lunghezza codice configurata
                $codiciPartecipazioneValidationOk = true;
                if ($safeTable === 'codici_partecipazione') {
                    $codiceCsvIndex = null;
                    foreach ($mapped as $idx => $col) {
                        if ($col === 'codice') {
                            $codiceCsvIndex = $idx;
                            break;
                        }
                    }
                    if ($codiceCsvIndex !== null) {
                        $expectedLen = 0;
                        $useAutoLen = !empty($contest_config['instantwin_codice_length_auto'] ?? false);
                        if ($useAutoLen && $connection) {
                            try {
                                $stmtLen = $connection->query("SELECT CHAR_LENGTH(codice) AS len FROM codici_partecipazione WHERE isActive = 1 AND codice IS NOT NULL AND codice <> '' LIMIT 1");
                                $rowLen = $stmtLen ? $stmtLen->fetch(PDO::FETCH_ASSOC) : null;
                                $expectedLen = (int) ($rowLen['len'] ?? 0);
                            } catch (Throwable $e) {
                                $expectedLen = 0;
                            }
                        }
                        if ($expectedLen < 1) {
                            $expectedLen = (int) ($contest_config['instantwin_codice_length'] ?? 12);
                        }
                        if ($expectedLen < 1) {
                            $expectedLen = 12;
                        } elseif ($expectedLen > 255) {
                            $expectedLen = 255;
                        }
                        $h = fopen($csvPath, 'r');
                        if ($h) {
                            $ln = 0;
                            $lenErrors = [];
                            while (($r = fgetcsv($h, 0, $delimiter, '"', '\\')) !== false) {
                                $ln++;
                                if ($ln === 1 && $hasHeader) {
                                    continue;
                                }
                                $codiceVal = isset($r[$codiceCsvIndex]) ? trim((string)$r[$codiceCsvIndex]) : '';
                                if ($codiceVal !== '') {
                                    $actualLen = mb_strlen($codiceVal, 'UTF-8');
                                    if ($actualLen !== $expectedLen) {
                                        $lenErrors[] = "Riga {$ln}: il codice ha {$actualLen} caratteri, ma deve averne esattamente {$expectedLen}.";
                                        $codiciPartecipazioneValidationOk = false;
                                    }
                                }
                            }
                            fclose($h);
                            foreach (array_slice($lenErrors, 0, 20) as $err) {
                                $errors[] = $err;
                            }
                            if (count($lenErrors) > 20) {
                                $errors[] = "... e altri " . (count($lenErrors) - 20) . " codici con lunghezza errata.";
                            }
                        }
                    } else {
                        $errors[] = "Per codici_partecipazione è obbligatorio mappare la colonna codice per verificare la lunghezza.";
                        $codiciPartecipazioneValidationOk = false;
                    }
                }
                
                $skipImportForValidation = ($safeTable === 'codici_premio' && !$codiciPremioValidationOk)
                    || ($safeTable === 'codici_partecipazione' && !$codiciPartecipazioneValidationOk);
                if ($skipImportForValidation) {
                    if ($safeTable === 'codici_premio') {
                        $errors[] = "Import codici premio bloccato: verifica le quantità per prize_id e riprova.";
                    } elseif ($safeTable === 'codici_partecipazione') {
                        $errors[] = "Import codici partecipazione bloccato: verifica che ogni codice abbia la lunghezza configurata (Lunghezza codice in Campi form) e riprova.";
                    }
                }
                
                $inserted = 0;
                $failed = 0;
                $failRows = [];
                
                if (!$skipImportForValidation) {
                $connection->beginTransaction();
                try {
                    if ($truncate) {
                        $connection->exec('TRUNCATE TABLE `' . $safeTable . '`');
                    }
                    
                    $handle = fopen($csvPath, 'r');
                    if (!$handle) {
                        throw new RuntimeException('Impossibile aprire il CSV per l\'import.');
                    }

                    $batchSize = 500;
                    $batchRows = [];
                    $batchLines = [];
                    
                    $line = 0;
                    while (($row = fgetcsv($handle, 0, $delimiter, '"', '\\')) !== false) {
                        $line++;
                        if ($line === 1 && $hasHeader) {
                            continue;
                        }
                        
                        $payload = [];
                        foreach ($mapped as $csvIndex => $colName) {
                            $payload[$colName] = isset($row[$csvIndex])
                                ? normalize_csv_import_cell_value(trim((string) $row[$csvIndex]))
                                : null;
                        }

                        $batchRows[] = $payload;
                        $batchLines[] = $line;
                        if (count($batchRows) >= $batchSize) {
                            gestione_dati_flush_insert_batch(
                                $connection,
                                $safeTable,
                                $columns,
                                $batchRows,
                                $batchLines,
                                $inserted,
                                $failed,
                                $failRows
                            );
                            $batchRows = [];
                            $batchLines = [];
                        }
                    }
                    fclose($handle);

                    if ($batchRows !== []) {
                        gestione_dati_flush_insert_batch(
                            $connection,
                            $safeTable,
                            $columns,
                            $batchRows,
                            $batchLines,
                            $inserted,
                            $failed,
                            $failRows
                        );
                    }
                    
                    $connection->commit();
                    gestione_dati_reset_auto_increment_if_empty($connection, $safeTable);
                    $success[] = "Import completato. Inseriti: {$inserted}. Errori: {$failed}.";
                    if ($failed > 0) {
                        $errors[] = "Alcune righe hanno fallito l'inserimento (prime 20 sotto).";
                        $errors = array_merge($errors, $failRows);
                    }
                } catch (Throwable $e) {
                    $connection->rollBack();
                    $errors[] = 'Import fallito: ' . $e->getMessage();
                } finally {
                    if (file_exists($csvPath)) {
                        @unlink($csvPath);
                    }
                    unset($_SESSION['csv_import_gestione']);
                }
                } else {
                    if (file_exists($csvPath)) {
                        @unlink($csvPath);
                    }
                    unset($_SESSION['csv_import_gestione']);
                }
            }
        }
    }
}

// Dati per preview
$preview = null;
$csvHeader = [];
$previewRows = [];
$tableColumns = [];
$mappingDefaults = [];

if (!empty($_SESSION['csv_import_gestione']['path']) && file_exists((string)$_SESSION['csv_import_gestione']['path']) && $connection) {
    try {
        $sess = $_SESSION['csv_import_gestione'];
        $selectedTable = (string)$sess['table'];
        $delimiterUi = (string)$sess['delimiter'];
        $delimiter = normalizeDelimiter_gestione($delimiterUi);
        $hasHeader = !empty($sess['has_header']);

        $tableColumns = getTableColumns_gestione($connection, $selectedTable);
        $mappableColumns = function_exists('gestione_dati_import_mappable_columns')
            ? gestione_dati_import_mappable_columns($selectedTable, $tableColumns)
            : array_map(static fn($c) => ['name' => (string) $c, 'label' => (string) $c], $tableColumns);
        $mappableNames = array_column($mappableColumns, 'name');
        $preview = readCsvPreview_gestione((string)$sess['path'], $delimiter, $hasHeader, 10);
        $csvHeader = $preview['header'];
        $previewRows = $preview['rows'];
        if ($hasHeader) {
            $mappingDefaults = buildDefaultMapping_gestione($csvHeader, $mappableNames);
        }
    } catch (Throwable $e) {
        $errors[] = $e->getMessage();
    }
}

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

        if ($quizEnabledGestioneStandalone) {
            $stmt_q = $connection->query('SELECT COUNT(*) as total FROM quiz_domande WHERE attiva = 1');
            $stats_quiz_domande = (int) ($stmt_q->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
        }
    } catch (Exception $e) {
        error_log("Errore recupero statistiche: " . $e->getMessage());
    }
}

?>
<style>
.gestione-dati-standalone-stats {
    margin-left: -14px;
    margin-right: -14px;
}
.gestione-dati-standalone-stat-col {
    padding-left: 14px;
    padding-right: 14px;
}
</style>

<div class="app-main__inner">
    <div class="app-page-title">
        <div class="page-title-wrapper">
            <div class="page-title-heading">
                <div class="page-title-icon">
                    <i class="pe-7s-database icon-gradient bg-happy-green"></i>
                </div>
                <div>
                    <?php echo $_title; ?>
                    <div class="page-title-subheading">
                        Gestisci i record singoli (CRUD) o importa un CSV completo per i dati operativi del concorso.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <p class="text-muted mb-3 small text-uppercase font-weight-bold text-center" style="letter-spacing: 0.04em;">Dati operativi</p>
    <!-- Statistiche: stessa riga (incluso quiz se abilitato) -->
    <div class="row mb-4 gestione-dati-standalone-stats justify-content-center">
        <?php
        $statColsTotal = 2 + ($codicePartecipazioneEnabled ? 1 : 0) + ($quizEnabledGestioneStandalone ? 1 : 0);
        if ($statColsTotal <= 2) {
            $statColClassGestione = 'col-md-6';
        } elseif ($statColsTotal === 3) {
            $statColClassGestione = 'col-md-4';
        } else {
            $statColClassGestione = 'col-md-3';
        }
        ?>
        <div class="<?php echo h_gestione($statColClassGestione); ?> gestione-dati-standalone-stat-col">
            <div class="card mb-3 widget-content bg-primary">
                <div class="widget-content-wrapper text-white">
                    <div class="widget-content-left">
                        <div class="widget-heading">PDV Attivi</div>
                        <div class="widget-subheading">Punti vendita attivi</div>
                    </div>
                    <div class="widget-content-right">
                        <div class="widget-numbers text-white"><?php echo number_format($stats_pdv); ?></div>
                    </div>
                </div>
            </div>
        </div>
        <?php if ($codicePartecipazioneEnabled): ?>
        <div class="<?php echo h_gestione($statColClassGestione); ?> gestione-dati-standalone-stat-col">
            <div class="card mb-3 widget-content bg-info">
                <div class="widget-content-wrapper text-white">
                    <div class="widget-content-left">
                        <div class="widget-heading">Codici Partecipazione</div>
                        <div class="widget-subheading">Totale codici</div>
                    </div>
                    <div class="widget-content-right">
                        <div class="widget-numbers text-white"><?php echo number_format($stats_codici_partecipazione); ?></div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        <?php if ($isInstantwinStandalone): ?>
        <div class="<?php echo h_gestione($statColClassGestione); ?> gestione-dati-standalone-stat-col">
            <div class="card mb-3 widget-content bg-success">
                <div class="widget-content-wrapper text-white">
                    <div class="widget-content-left">
                        <div class="widget-heading">Codici Premio</div>
                        <div class="widget-subheading">Totale codici</div>
                    </div>
                    <div class="widget-content-right">
                        <div class="widget-numbers text-white"><?php echo number_format($stats_codici_premio); ?></div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        <?php if ($quizEnabledGestioneStandalone): ?>
        <div class="<?php echo h_gestione($statColClassGestione); ?> gestione-dati-standalone-stat-col">
            <div class="card mb-3 widget-content text-white border-0" style="background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);">
                <div class="widget-content-wrapper text-white">
                    <div class="widget-content-left">
                        <div class="widget-heading">Quiz – Domande attive</div>
                        <div class="widget-subheading">Stato attiva · quiz_domande</div>
                    </div>
                    <div class="widget-content-right">
                        <div class="widget-numbers text-white"><?php echo number_format($stats_quiz_domande); ?></div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <div class="fw-bold mb-2">Errori</div>
            <ul class="mb-0">
                <?php foreach ($errors as $e): ?>
                    <li><?php echo h_gestione((string)$e); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
        <div class="alert alert-success">
            <ul class="mb-0">
                <?php foreach ($success as $s): ?>
                    <li><?php echo h_gestione((string)$s); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php
    $gdAllowedTables = $allowedTables;
    $gdTableHints = gestione_dati_table_hints_map($connection, $gdAllowedTables, $contentForGd, $gdDefaults);
    $gdContext = gestione_dati_resolve_context($connection, $contentForGd, $gdDefaults);
    $gdCsvColumnsByTable = [];
    foreach ($gdAllowedTables as $gdTbl) {
        $gdCsvColumnsByTable[$gdTbl] = gestione_dati_csv_columns_bundle($gdTbl, $gdContext, $connection);
    }
    $gdColumnsByTable = gestione_dati_build_all_columns_payload($connection, $gdAllowedTables, $contentForGd, $gdDefaults);
    $gdPdvRegistration = gestione_dati_pdv_registration_fields($contentForGd, $gdDefaults);
    $gdAjaxBase = 'res/inc/';
    $gdIsStandalone = true;
    $gdQuizEditorUrl = 'index.php?pg=quiz_domande';
    $gdMappingVisible = !empty($preview) && $hasHeader;
    $gestioneImportStandalone = true;
    $gdActiveTable = $allowedTables[0] ?? '';
    if ($selectedTable !== '' && in_array($selectedTable, $allowedTables, true)) {
        $gdActiveTable = $selectedTable;
    }
    include __DIR__ . '/concorso/partials/gestione-dati-archivio.php';
    ?>

    <?php if ($preview && $hasHeader): ?>
        <div class="main-card mb-3 card">
            <div class="card-header">
                <i class="header-icon lnr-map icon-gradient bg-happy-green"></i>
                3) Mapping colonne + Anteprima
            </div>
            <div class="card-body">
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <div class="fw-bold mb-2">CSV</div>
                        <div class="text-muted mb-2">File: <span class="mono"><?php echo h_gestione((string)($_SESSION['csv_import_gestione']['name'] ?? '')); ?></span></div>
                        <div class="text-muted">Intestazioni trovate: <strong><?php echo count($csvHeader); ?></strong></div>
                    </div>
                    <div class="col-md-6">
                        <div class="fw-bold mb-2">Tabella DB</div>
                        <div class="text-muted">Tabella: <span class="mono"><?php echo h_gestione($selectedTable); ?></span></div>
                        <div class="text-muted">Colonne: <strong><?php echo count($tableColumns); ?></strong></div>
                    </div>
                </div>

                <hr>

                <form method="post">
                    <input type="hidden" name="step" value="import">

                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="truncate" name="truncate" value="1">
                            <label class="form-check-label" for="truncate">
                                <strong class="text-danger">TRUNCATE</strong> tabella prima dell'import (ATTENZIONE: cancella tutti i dati)
                            </label>
                        </div>
                    </div>

                    <div class="table-responsive mb-3">
                        <table class="table table-sm table-bordered align-middle">
                            <thead class="table-light">
                            <tr>
                                <th style="width: 45%">Colonna CSV (intestazione)</th>
                                <th style="width: 55%">→ Colonna Tabella (mapping)</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($csvHeader as $idx => $name): ?>
                                <tr>
                                    <td class="mono"><?php echo h_gestione((string)$name); ?></td>
                                    <td>
                                        <select class="form-control form-control-sm" name="mapping[<?php echo (int)$idx; ?>]">
                                            <option value="">— Ignora —</option>
                                            <?php
                                            $mapCols = $mappableColumns ?? array_map(
                                                static fn($c) => ['name' => (string) $c, 'label' => (string) $c],
                                                $tableColumns
                                            );
                                            foreach ($mapCols as $colMeta):
                                                $col = $colMeta['name'];
                                                $colLabel = $colMeta['label'];
                                                $selected = '';
                                                if (isset($mappingDefaults[$idx]) && $mappingDefaults[$idx] === $col) {
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

                    <div class="mb-3">
                        <button type="submit" class="btn btn-success">
                            <i class="fa fa-upload mr-2"></i>Importa nel DB
                        </button>
                    </div>
                </form>

                <div class="fw-bold mb-2">Anteprima prime righe (max 10)</div>
                <div class="table-responsive">
                    <table class="table table-sm table-striped">
                        <thead>
                        <tr>
                            <?php foreach ($csvHeader as $name): ?>
                                <th class="mono"><?php echo h_gestione((string)$name); ?></th>
                            <?php endforeach; ?>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($previewRows as $r): ?>
                            <tr>
                                <?php foreach ($csvHeader as $i => $_): ?>
                                    <td class="mono"><?php echo h_gestione((string)($r[$i] ?? '')); ?></td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php elseif ($preview && !$hasHeader): ?>
        <div class="alert alert-warning">
            Per fare il mapping, abilita "intestazione" (prima riga con nomi colonne).
        </div>
    <?php endif; ?>

</div>

<?php $scripts = ($scripts ?? '') . '<script src="res/js/gestione-dati-archivio.js"></script>'; ?>

<style>
.mono {
    font-family: Consolas, "Courier New", monospace;
    font-size: 0.9rem;
}
</style>

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
</script>

<?php include("_footer.php"); ?>
