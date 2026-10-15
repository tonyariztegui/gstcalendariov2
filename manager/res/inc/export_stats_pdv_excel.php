<?php

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../../app/inc/stats_pdv_manager.inc.php';

$autoload_paths = [
    __DIR__ . '/../../vendor/autoload.php',
    __DIR__ . '/../../../vendor/autoload.php',
];
foreach ($autoload_paths as $path) {
    if (file_exists($path)) {
        require_once $path;
        break;
    }
}
if (!class_exists(Spreadsheet::class)) {
    header('Content-Type: text/plain; charset=utf-8');
    exit('PhpSpreadsheet non trovato. Eseguire composer install nella root del progetto.');
}

if (ob_get_level() > 0) {
    ob_end_clean();
}

if (!isset($connection) || !$connection instanceof PDO) {
    header('Content-Type: text/plain; charset=utf-8');
    exit('Connessione database non disponibile.');
}

$defaultFrom = (string) ($contest_config['start_date'] ?? date('Y-m-d'));
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

$report = stats_pdv_build_report($connection, $from, $to, [
    'pdv_codice' => $filterPdv,
    'cedi_codice' => $filterCedi,
]);

if (!empty($report['error'])) {
    header('Content-Type: text/plain; charset=utf-8');
    exit((string) $report['error']);
}

$summary = $report['summary'] ?? [];
$byDay = $report['by_day'] ?? [];
$byHour = $report['by_hour'] ?? [];
$byPdv = $report['by_pdv'] ?? [];
$pdvExportColumns = $report['meta']['pdv_export_columns'] ?? stats_pdv_punti_vendita_columns($connection);
$pdvExportHeaders = $report['meta']['pdv_export_headers'] ?? [];
if ($pdvExportHeaders === []) {
    $columnDefs = stats_pdv_export_column_defs();
    foreach ($pdvExportColumns as $col) {
        $pdvExportHeaders[$col] = $columnDefs[$col] ?? $col;
    }
}

$spreadsheet = new Spreadsheet();
$spreadsheet->getProperties()
    ->setTitle('Statistiche PDV')
    ->setSubject($from . ' - ' . $to);

// Foglio Riepilogo
$ws = $spreadsheet->getActiveSheet();
$ws->setTitle('Riepilogo');
$ws->setCellValue('A1', 'Statistiche PDV');
$ws->setCellValue('A2', 'Periodo');
$ws->setCellValue('B2', $from . ' → ' . $to);
$ws->setCellValue('A3', 'Filtro PDV');
$ws->setCellValue('B3', $filterPdv !== '' ? $filterPdv : 'Tutti');
$ws->setCellValue('A5', 'Partecipazioni');
$ws->setCellValue('B5', (int) ($summary['participations'] ?? 0));
$ws->setCellValue('A6', 'Vincite');
$ws->setCellValue('B6', (int) ($summary['wins'] ?? 0));
$ws->setCellValue('A7', 'Win rate %');
$ws->setCellValue('B7', (float) ($summary['win_rate'] ?? 0));
$ws->setCellValue('A8', 'PDV attivi');
$ws->setCellValue('B8', (int) ($summary['pdv_with_activity'] ?? 0));
$ws->setCellValue('A9', 'Giorni con attività');
$ws->setCellValue('B9', (int) ($summary['days_with_activity'] ?? 0));
$ws->setCellValue('A10', 'Generato il');
$ws->setCellValue('B10', date('Y-m-d H:i:s'));

// Foglio Orario
$wsH = $spreadsheet->createSheet();
$wsH->setTitle('Orario');
$wsH->fromArray(['Ora', 'Partecipazioni', 'Vincite', 'Win rate %'], null, 'A1');
$row = 2;
foreach ($byHour as $hr) {
    $hp = (int) ($hr['part'] ?? 0);
    $hw = (int) ($hr['wins'] ?? 0);
    $hwr = $hp > 0 ? round(100.0 * $hw / $hp, 2) : 0.0;
    $wsH->fromArray([
        sprintf('%02d:00', (int) ($hr['hour'] ?? 0)),
        $hp,
        $hw,
        $hwr,
    ], null, 'A' . $row);
    $row++;
}

// Foglio Giornaliero
$wsD = $spreadsheet->createSheet();
$wsD->setTitle('Giornaliero');
$wsD->fromArray(['Data', 'Partecipazioni', 'Vincite', 'Win rate %'], null, 'A1');
$row = 2;
foreach ($byDay as $day) {
    $wsD->fromArray([
        (string) ($day['date'] ?? ''),
        (int) ($day['part'] ?? 0),
        (int) ($day['wins'] ?? 0),
        (float) ($day['win_rate'] ?? 0),
    ], null, 'A' . $row);
    $row++;
}

// Foglio PDV
$wsP = $spreadsheet->createSheet();
$wsP->setTitle('Per PDV');
$pdvHeaderRow = [];
foreach ($pdvExportColumns as $col) {
    $pdvHeaderRow[] = (string) ($pdvExportHeaders[$col] ?? $col);
}
$pdvHeaderRow[] = 'Partecipazioni';
$pdvHeaderRow[] = 'Vincite';
$pdvHeaderRow[] = 'Win rate %';
$wsP->fromArray($pdvHeaderRow, null, 'A1');
$row = 2;
foreach ($byPdv as $pdv) {
    $p = (int) ($pdv['part'] ?? 0);
    $w = (int) ($pdv['wins'] ?? 0);
    $wr = $p > 0 ? round(100.0 * $w / $p, 2) : 0.0;
    $line = [];
    foreach ($pdvExportColumns as $col) {
        if ($col === 'codice') {
            $line[] = (string) ($pdv['cod'] ?? $pdv['codice'] ?? '');
            continue;
        }
        $line[] = (string) ($pdv[$col] ?? '');
    }
    $line[] = $p;
    $line[] = $w;
    $line[] = $wr;
    $wsP->fromArray($line, null, 'A' . $row);
    $row++;
}

foreach ($spreadsheet->getAllSheets() as $sheet) {
  $maxCol = $sheet->getHighestDataColumn();
  if ($maxCol === '') {
    $maxCol = 'F';
  }
  foreach (range('A', $maxCol) as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
  }
  $sheet->getStyle('A1:' . $maxCol . '1')->getFont()->setBold(true);
}

$filename = 'statistiche_pdv_' . $from . '_' . $to;
if ($filterPdv !== '') {
    $filename .= '_pdv' . preg_replace('/[^a-zA-Z0-9_-]/', '', $filterPdv);
}
$filename .= '.xlsx';

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
