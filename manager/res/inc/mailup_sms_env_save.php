<?php

/**
 * AJAX: salva MAILUP_SMS_* nel file .env (Configurazione SMS MailUp).
 */
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../../app/logic/mailup_sms_env.php';

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
        'message' => 'File .env non trovato: ' . $path,
    ]);
    exit;
}
if (!is_writable($realPath)) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Il file .env non è modificabile da Apache/PHP. Percorso: ' . $realPath,
    ]);
    exit;
}

$existingBundle = mailup_sms_env_values_for_form();
$existing = $existingBundle['values'];

$validated = mailup_sms_env_validate_submission($_POST, $existing);
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

foreach ($values as $k => $v) {
    if (function_exists('instantwin_manager_env_apply_runtime')) {
        instantwin_manager_env_apply_runtime((string) $k, (string) $v);
    }
}

echo json_encode([
    'success' => true,
    'message' => 'Credenziali MailUp salvate in .env. Puoi inviare un SMS di test.',
    'path' => $path,
    'keys_updated' => count($values),
]);
