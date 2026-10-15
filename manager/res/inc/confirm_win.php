<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../../app/logic/instantwin.php';
require_once __DIR__ . '/../../../public/assets/mail/email_system.php';
require_once __DIR__ . '/manager_win_actions.inc.php';

try {
    $connection->exec("SET NAMES utf8mb4");
    $connection->exec("SET CHARACTER SET utf8mb4");
    $connection->exec("SET collation_connection = 'utf8mb4_unicode_ci'");
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Errore di connessione al database.'
    ]);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Sessione scaduta. Effettua nuovamente il login.'
    ]);
    exit;
}

$participationId = isset($_POST['participation_id']) ? (int) $_POST['participation_id'] : 0;
$result = manager_confirm_participation_win($connection, $participationId);
http_response_code((int) ($result['http_code'] ?? 200));
echo json_encode([
    'success' => !empty($result['success']),
    'message' => (string) ($result['message'] ?? ''),
]);
