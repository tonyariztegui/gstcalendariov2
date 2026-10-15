<?php
require_once __DIR__ . '/../app/inc/config.php';

if (!empty($contest_config['published'])) {
    $target = !empty($contest_config['participation_page_enabled']) ? 'partecipazione' : 'registrazione';
    header('Location: ' . $target);
    exit;
}

header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sito in costruzione</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <style>
        body { min-height: 100vh; margin: 0; background: #f0f0f0; color: #333; display: flex; align-items: center; justify-content: center; }
        .welcome-box { background: #fff; padding: 2rem 2.5rem; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); max-width: 540px; text-align: center; }
    </style>
</head>
<body>
<div class="welcome-box">
    <h1 class="h4 mb-4">Sito in costruzione</h1>
    <p class="text-muted mb-0">Arriva prossimamente.</p>
</div>
</body>
</html>
