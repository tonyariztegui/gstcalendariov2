<?php

/**
 * AJAX: genera/recupera ListSecret MailUp (GET/POST listsecret) e salva in .env.
 */
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../../app/inc/sms_mailup.inc.php';
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
if (!is_readable($realPath) || !is_writable($realPath)) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'File .env non leggibile/scrivibile: ' . $realPath,
    ]);
    exit;
}

$existingBundle = mailup_sms_env_values_for_form();
$existing = $existingBundle['values'];

$listId = trim((string) ($_POST['MAILUP_SMS_LIST_ID'] ?? ''));
$listGuid = trim((string) ($_POST['MAILUP_SMS_LIST_GUID'] ?? ''));
$username = trim((string) ($_POST['MAILUP_SMS_USERNAME'] ?? ''));
$password = (string) ($_POST['MAILUP_SMS_PASSWORD'] ?? '');

if ($listId === '') {
    $listId = (string) ($existing['MAILUP_SMS_LIST_ID'] ?? '');
}
if ($listGuid === '') {
    $listGuid = (string) ($existing['MAILUP_SMS_LIST_GUID'] ?? '');
}
if ($username === '') {
    $username = (string) ($existing['MAILUP_SMS_USERNAME'] ?? '');
}
if (trim($password) === '') {
    $password = (string) ($existing['MAILUP_SMS_PASSWORD'] ?? '');
}

if ($listId === '' || $listGuid === '') {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Compila List ID e List GUID nel blocco « Configurazione invio » (e salva se necessario).',
    ]);
    exit;
}
if ($username === '' || trim($password) === '') {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Username e Password MailUp sono obbligatori per generare/recuperare il List Secret.',
    ]);
    exit;
}

// Forza chiamata API (non riusare un secret già in memoria)
$cfg = [
    'username' => $username,
    'password' => $password,
    'list_id' => $listId,
    'list_guid' => $listGuid,
    'list_secret' => '',
];

$res = gst_sms_ensure_list_secret($cfg);
if (!$res['ok'] || $res['list_secret'] === '') {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $res['error'] !== '' ? $res['error'] : 'Generazione List Secret fallita.',
    ]);
    exit;
}

$secret = $res['list_secret'];
$toWrite = ['MAILUP_SMS_LIST_SECRET' => $secret];
if ($listId !== '') {
    $toWrite['MAILUP_SMS_LIST_ID'] = $listId;
}
if ($listGuid !== '') {
    $toWrite['MAILUP_SMS_LIST_GUID'] = $listGuid;
}
if ($username !== '') {
    $toWrite['MAILUP_SMS_USERNAME'] = $username;
}
if (trim($password) !== '' && trim((string) ($_POST['MAILUP_SMS_PASSWORD'] ?? '')) !== '') {
    // Salva password solo se inviata esplicitamente nel POST (non se riusata da .env)
    $toWrite['MAILUP_SMS_PASSWORD'] = $password;
}

if (!instantwin_manager_env_write($toWrite)) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'List Secret ottenuto ma impossibile scriverlo nel .env.',
        'list_secret' => $secret,
    ]);
    exit;
}

foreach ($toWrite as $k => $v) {
    if (function_exists('instantwin_manager_env_apply_runtime')) {
        instantwin_manager_env_apply_runtime((string) $k, (string) $v);
    }
}

echo json_encode([
    'success' => true,
    'message' => 'List Secret generato/recuperato e salvato in .env.',
    'list_secret' => $secret,
]);
