<?php

/**
 * AJAX: salva SMTP_* nel file .env (Configurazione Parametri SMTP).
 * Scrive anche le chiavi del profilo selezionato (plan B: generale / aruba / sendgrid).
 */
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../../app/logic/smtp_env.php';

if (!isset($_SESSION['user_id']) || empty($_SESSION['user_name']) || $_SESSION['user_name'] !== 'pm_admin') {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Accesso non autorizzato.',
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

$profile = smtp_env_normalize_profile_id((string) ($_POST['SMTP_PROFILE'] ?? 'general'));
$profileBundle = smtp_env_read_profile_values($profile);
$existing = $profileBundle['values'];

$validated = smtp_env_validate_submission($_POST, $existing);
if (empty($validated['ok'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $validated['message'] ?? 'Dati non validi.',
    ]);
    exit;
}

$activeValues = $validated['values'] ?? [];
$values = smtp_env_profile_values_to_write($profile, $activeValues);
if ($values === [] || !instantwin_manager_env_write($values)) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Impossibile aggiornare il file .env.',
    ]);
    exit;
}

foreach ($values as $k => $v) {
    instantwin_manager_env_apply_runtime((string) $k, (string) $v);
}

$contentPath = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'content.json';
smtp_env_persist_stripped_content_json($contentPath);

$uiBundle = smtp_env_profiles_bundle_for_ui();

echo json_encode([
    'success' => true,
    'message' => 'Parametri SMTP salvati in .env (profilo ' . ($uiBundle['labels'][$profile] ?? $profile) . '). Le credenziali non restano in content.json.',
    'path' => $path,
    'keys_updated' => count($values),
    'password_set' => (($activeValues['SMTP_PASSWORD'] ?? '') !== ''),
    'profile' => $profile,
    'profiles' => $uiBundle['profiles'],
    'active_profile' => $uiBundle['active_profile'],
]);
