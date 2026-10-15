<?php
header('Content-Type: application/json; charset=utf-8');

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../../config/config.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Sessione scaduta. Effettua nuovamente il login.']);
    exit;
}

$email = isset($_POST['email']) ? trim((string) $_POST['email']) : '';
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Indirizzo email non valido.']);
    exit;
}

// Validazione email account: non implementata in summercoop (schema utenti senza validation_token).
echo json_encode([
    'success' => false,
    'message' => 'Il reinvio email di validazione account non è disponibile in questa installazione. Usare il reinvio dalle partecipazioni per le email di concorso.'
]);
