<?php
// Punto di ingresso pubblico per la registrazione: delega alla logica interna
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    header('Allow: POST');
    $public_error_code = 405;
    require __DIR__ . '/error.php';
    exit;
}

require_once __DIR__ . '/../app/logic/do_registration.php';
