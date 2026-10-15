<?php
session_start();
include("../../config/config.php");

header('Content-Type: application/json');

if (!isset($_SESSION["user_id"])) {
    echo json_encode(["success" => false, "error" => "Sessione scaduta. Effettua nuovamente il login."]);
    exit;
}

$email = isset($_GET['email']) ? trim($_GET['email']) : '';

if (empty($email)) {
    echo json_encode(["success" => false, "error" => "Email mancante"]);
    exit;
}

try {
    // Récupérer le token de validation pour cet email
    $sql = "SELECT validation_token FROM utenti WHERE EMAIL = ?";
    $stmt = $connection->prepare($sql);
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo json_encode(["success" => false, "error" => "Utente non trovato"]);
        exit;
    }
    
    $validation_token = $user['validation_token'];
    $validation_link = "https://anniversario.supersisa.com/validate_confirm.php?token=" . $validation_token;
    
    echo json_encode([
        "success" => true,
        "token" => $validation_token,
        "link" => $validation_link
    ]);
    
} catch (Exception $e) {
    error_log("Errore get_validation_token.php: " . $e->getMessage());
    echo json_encode(["success" => false, "error" => "Errore del server"]);
}
?>

