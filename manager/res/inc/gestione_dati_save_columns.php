<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/gestione_dati_context.inc.php';
require_once __DIR__ . '/gestione_dati_columns.inc.php';

gestione_dati_require_auth_json();

if (!isset($connection) || !$connection) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'DB non disponibile.']);
    exit;
}

$ctx = gestione_dati_load_content();
$content = $ctx['content'];
$defaults = $ctx['defaults'];

$table = gestione_dati_safe_table_name((string) ($_POST['table'] ?? ''), $content, $defaults);
if ($table === null) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Tabella non consentita.']);
    exit;
}

$columnsRaw = $_POST['columns'] ?? [];
if (is_string($columnsRaw)) {
    $decoded = json_decode($columnsRaw, true);
    $columns = is_array($decoded) ? $decoded : [];
} elseif (is_array($columnsRaw)) {
    $columns = $columnsRaw;
} else {
    $columns = [];
}

$result = gestione_dati_save_visible_columns($table, $columns, $connection, $content, $defaults);
if (empty($result['success'])) {
    http_response_code(422);
    echo json_encode(['success' => false, 'error' => $result['error'] ?? 'Salvataggio fallito.']);
    exit;
}

if (!gestione_dati_persist_content_json($content)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Impossibile salvare content.json.']);
    exit;
}

echo json_encode([
    'success' => true,
    'visible' => $result['visible'] ?? [],
    'message' => 'Colonne salvate nel concorso.',
]);
