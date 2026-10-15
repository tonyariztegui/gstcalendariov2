<?php

/**
 * AJAX: invio SMS di test MailUp (credenziali solo da .env).
 * Destinatario: POST recipient (one-shot) oppure MAILUP_SMS_TEST_RECIPIENT.
 */
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../../app/inc/sms_mailup.inc.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Sessione scaduta. Effettua nuovamente il login.']);
    exit;
}

$recipient = trim((string) ($_POST['recipient'] ?? ''));
$cfg = gst_sms_config_from_env();
if ($recipient === '') {
    $recipient = $cfg['test_recipient'];
}
$recipient = gst_sms_normalize_recipient_e164($recipient);
if ($recipient === '') {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Indica un destinatario oppure imposta MAILUP_SMS_TEST_RECIPIENT nel .env.',
    ]);
    exit;
}

$content = 'gst-con test SMS ' . date('Y-m-d H:i:s');
$res = send_sms_mailup($recipient, $content, 'gst-con-test');

if ($res['ok']) {
    echo json_encode([
        'success' => true,
        'message' => 'SMS inviato. DeliveryId: ' . $res['delivery_id'] . ' — Cost: ' . $res['cost'],
        'delivery_id' => $res['delivery_id'],
        'cost' => $res['cost'],
    ]);
    exit;
}

http_response_code(500);
echo json_encode([
    'success' => false,
    'message' => $res['error'] !== '' ? $res['error'] : 'Invio SMS di test fallito.',
]);
