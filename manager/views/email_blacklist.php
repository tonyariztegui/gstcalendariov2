<?php



/**

 * Redirect legacy route → hub Sicurezza.

 */

if (session_status() !== PHP_SESSION_ACTIVE) {

    session_start();

}



if (empty($_SESSION['user_name']) || $_SESSION['user_name'] !== 'pm_admin') {

    include(__DIR__ . '/_header.php');

    include(__DIR__ . '/res/inc/header.php');

    include(__DIR__ . '/access_denied.php');

    include(__DIR__ . '/_footer.php');

    exit;

}



header('Location: security?tab=emails', true, 301);

exit;


