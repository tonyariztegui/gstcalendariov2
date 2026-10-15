<?php

//Main Container

if (version_compare(PHP_VERSION, '5.3.7', '<')) {
    exit('Sorry, this script does not run on a PHP version smaller than 5.3.7 !');
} else if (version_compare(PHP_VERSION, '5.5.0', '<')) {
    require_once('libraries/password_compatibility_library.php');
}

require_once('config/config.php');
require_once('translations/it.php');
require_once('classes/Login.php');
require_once(__DIR__ . '/../app/inc/router.php');

$login = new Login();

// Pattern POST-Redirect-GET per il login: salva messaggi in sessione e reindirizza
// Questo pattern evita la risottomissione accidentale del modulo quando l'utente ricarica la pagina
// MODIFICA: Aggiunta gestione esplicita per connessioni riuscite e fallite
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    if ($login->isUserLoggedIn() === true) {
        // Connessione riuscita - reindirizza per evitare la risottomissione del modulo
        // Questo garantisce che dopo il login, l'utente veda la homepage senza dati POST
        header('Location: dashboard');
        exit;
    } else {
        // Connessione fallita - salva gli errori in sessione e reindirizza
        // Gli errori verranno mostrati nella pagina di login dopo il redirect
        $_SESSION['flash_errors'] = $login->errors ?? [];
        $_SESSION['flash_messages'] = $login->messages ?? [];
        header('Location: ./');
        exit;
    }
}

//Logged-In
if ($login->isUserLoggedIn() == true) {
    $routeName = app_manager_route_from_request(true);
    $routePath = app_route_view_path('manager', $routeName, __DIR__ . '/views');
    if ($routePath === null) {
        $routePath = app_route_view_path('manager', 'access_denied', __DIR__ . '/views');
    }
    if ($routePath !== null) {
        include $routePath;
    }

} else {
    $routeName = app_manager_route_from_request(false);
    $routePath = app_route_view_path('manager', $routeName, __DIR__ . '/views');
    if ($routePath === null) {
        $routePath = app_route_view_path('manager', 'not_logged_in', __DIR__ . '/views');
    }
    if ($routePath !== null) {
        include $routePath;
    }
	
}
