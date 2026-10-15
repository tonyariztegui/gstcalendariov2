<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../../app/logic/instantwin/manager_env.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Sessione scaduta. Effettua nuovamente il login.',
    ]);
    exit;
}

$path = instantwin_manager_env_file_path();
$realPath = realpath($path) ?: $path;
if (!is_readable($realPath)) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'File .env introuvable : ' . $path,
    ]);
    exit;
}
if (!is_writable($realPath)) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Le fichier .env n\'est pas modifiable par Apache/PHP. Chemin : ' . $realPath,
    ]);
    exit;
}

$keysToSave = instantwin_manager_env_flat_keys();
if (empty($_POST['iw_env_include_pdv_pacing'])) {
    $keysToSave = array_values(array_diff($keysToSave, [
        'INSTANTWIN_PDV_DAILY_PACING_ENABLED',
        'INSTANTWIN_PDV_PACING_RELAX_DAYS',
    ]));
}
if (!empty($_POST['iw_env_include_cedi'])) {
    $keysToSave = array_values(array_diff($keysToSave, [
        'INSTANTWIN_ENGINE',
    ]));
}
if (empty($_POST['iw_env_include_cedi'])) {
    $keysToSave = array_values(array_diff($keysToSave, [
        'INSTANTWIN_CEDI_WEEK_BUDGET_CARRYOVER',
    ]));
}

$validated = instantwin_manager_env_validate_submission($_POST, $keysToSave);
if (empty($validated['ok'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $validated['message'] ?? 'Dati non validi.',
    ]);
    exit;
}

$values = $validated['values'] ?? [];
if ($values === [] || !instantwin_manager_env_write($values)) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Impossibile aggiornare il file .env.',
    ]);
    exit;
}

echo json_encode([
    'success' => true,
    'message' => 'Impostazioni salvate in ' . basename($path) . ' (' . $path . '). Ricarica le pagine PHP se i valori non si applicano subito.',
    'path' => $path,
    'keys_updated' => count($values),
]);
