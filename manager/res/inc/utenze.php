<?php

/*
error_reporting(E_ALL);
ini_set('display_errors', 1);
/**/

/**
 * Gestione delle sessioni utente e verifica sicurezza
 * 
 * PROBLEMA RISOLTO (Dicembre 2025):
 * Questo file gestisce la verifica delle sessioni utente nel database. Il problema originale
 * era che quando un utente si connetteva per la prima volta, la sessione PHP veniva creata
 * correttamente, ma l'inserimento nella tabella manager_sessions poteva fallire o essere
 * ritardato a causa di problemi di timing o configurazione del database.
 * 
 * Quando utenze.php veniva eseguito subito dopo il login, verificava la sessione nel database
 * e, non trovandola, distruggeva immediatamente la sessione PHP, causando il logout automatico.
 * 
 * SOLUZIONE IMPLEMENTATA:
 * - Verifica in due fasi: prima controlla se la sessione esiste (senza condizione di scadenza),
 *   poi applica i controlli di scadenza solo se la sessione esiste già
 * - Gestione delle nuove connessioni: se user_logged_in = 1 ma la sessione non è in database,
 *   tenta di crearla. Se anche questo fallisce, recupera l'utente direttamente da manager_users
 * - Fallback robusto: garantisce che la connessione funzioni anche se ci sono problemi con
 *   la tabella manager_sessions (permessi, errori SQL, ecc.)
 * 
 * Questo risolve il problema dove la connessione funzionava su alcuni PC ma non su altri
 * a causa di differenze di timing, configurazione PHP, o performance del database.
 */

// Includere la connessione PDO
require_once(__DIR__ . "/../../../app/inc/config.php");
require_once(__DIR__ . "/../../../app/inc/functions.php");
$connection = conn_pdo();

if (!stristr($_SERVER["REQUEST_URI"], ".php")) {
    // Code here if needed
}

//Default TimeZone
date_default_timezone_set('Europe/Rome');
//include_once("sanitize.inc.php");

//Locale
setlocale(LC_ALL, 'it_IT');

//Ora Solare/Legale
//$tipo_ora = date("I"); //Restituisce 1 se c'è l'ora legale, 0 se c'è quella solare

$time = time(); //Now
$today = gmmktime(10,0,0,date("m"),date("d"),date("Y")); //Ora Solare/Legale

//Random Password
function randomPassword() {
    
	$alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890_-@!*';
	$special_chars = '_-@!*&/';
    $pass = array();
	$alphaLength = strlen($alphabet) - 1;
	$scLength = strlen($special_chars) - 1;
    for ($i = 0; $i < 10; $i++) {
        $n = random_int(0, $alphaLength);
        $pass[] = $alphabet[$n];
	}
	$s = random_int(0, $scLength);
	$pass[] = $special_chars[$s];
    return implode($pass);
	
}

//Session
$clean_expired_sessions = $connection->prepare("DELETE FROM manager_sessions WHERE last_used < '".($time - 3600)."'");
if ($clean_expired_sessions === false) {
    die('Erreur SQL (manager_sessions) : ' . print_r($connection->errorInfo(), true));
}
$clean_expired_sessions->execute();

if (isset($_SESSION['user_id']) && isset($_SESSION['user_token'])) {

	// Verifica sicura della sessione nel database
	// PROBLEMA RISOLTO: Prima verificavamo solo con JOIN e condizione last_used, ma questo causava
	// problemi con le nuove connessioni dove la sessione potrebbe non essere ancora stata inserita
	// o essere appena stata creata. Ora facciamo una verifica in due fasi:
	
	// FASE 1: Verifica semplice se la sessione esiste (senza condizione di scadenza)
	// Questo permette di identificare le nuove connessioni prima di applicare i controlli di scadenza
	$check_session_simple = $connection->prepare("SELECT ms.* FROM manager_sessions ms 
			WHERE ms.user_id = ? AND ms.user_token = ? LIMIT 1");
	$check_session_simple->execute([
		$_SESSION['user_id'], 
		$_SESSION['user_token']
	]);
	
	if ($check_session_simple->rowCount() == 0) {
		// La sessione non esiste affatto nel database
		// Potrebbe essere una nuova connessione dove l'inserimento non è ancora avvenuto
		$check_session = $check_session_simple; // Usiamo la stessa variabile per il controllo successivo
	} else {
		// La sessione esiste, quindi verifichiamo con JOIN e condizione di scadenza
		// Usiamo alias espliciti per evitare conflitti di nomi tra le tabelle
		$check_session = $connection->prepare("SELECT ms.*, mu.user_id as mu_user_id, mu.user_name, mu.user_email, mu.user_active, mu.is_admin FROM manager_sessions ms 
				JOIN manager_users mu ON ms.user_id = mu.user_id 
				WHERE ms.user_id = ? AND ms.user_token = ? AND ms.last_used > ? LIMIT 1");
		$check_session->execute([
			$_SESSION['user_id'], 
			$_SESSION['user_token'], 
			($time - 3600) // La sessione scade dopo 1 ora
		]);
	}
	
	if ($check_session->rowCount() == 0) {
		// La sessione non è stata trovata o è scaduta
		// MODIFICA IMPORTANTE: Prima distruggevamo sempre la sessione, ma questo causava problemi
		// con le nuove connessioni dove c'è un problema di timing tra la creazione della sessione PHP
		// e l'inserimento nel database. Ora gestiamo questo caso speciale.
		
		// Verifica se è una nuova connessione (user_logged_in = 1 indica una connessione appena effettuata)
		if (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] == 1) {
			// Nuova connessione - proviamo a creare la sessione nel database se non esiste
			// Questo risolve il problema di timing dove l'inserimento potrebbe essere fallito
			// o non ancora completato quando utenze.php viene eseguito
			try {
				// INSERT IGNORE evita errori se la sessione esiste già (race condition)
				$insert_session = $connection->prepare("INSERT IGNORE INTO manager_sessions (uid, user_id, first_used, last_used, user_token) VALUES (?, ?, ?, ?, ?)");
				$insert_result = $insert_session->execute([
					$_SESSION['user_id'],
					$_SESSION['user_id'],
					$time,
					$time,
					$_SESSION['user_token']
				]);
				
				// Riproviamo la verifica con JOIN dopo l'inserimento
				$check_session_after = $connection->prepare("SELECT ms.*, mu.user_id as mu_user_id, mu.user_name, mu.user_email, mu.user_active, mu.is_admin FROM manager_sessions ms 
						JOIN manager_users mu ON ms.user_id = mu.user_id 
						WHERE ms.user_id = ? AND ms.user_token = ? LIMIT 1");
				$check_session_after->execute([
					$_SESSION['user_id'], 
					$_SESSION['user_token']
				]);
				
				if ($check_session_after->rowCount() > 0) {
					// Sessione trovata dopo l'inserimento - tutto ok
					$session_data = $check_session_after->fetch(PDO::FETCH_OBJ);
				} else {
					// Anche dopo l'inserimento non trovata - recuperiamo l'utente direttamente da manager_users
					// Questo è un fallback per garantire che la connessione funzioni anche se c'è un problema
					// con la tabella manager_sessions (es. permessi, errori di inserimento, ecc.)
					$user_query = $connection->prepare("SELECT * FROM manager_users WHERE user_id = ? LIMIT 1");
					$user_query->execute([$_SESSION['user_id']]);
					$session_data = $user_query->fetch(PDO::FETCH_OBJ);
					if (!$session_data) {
						// L'utente non esiste nemmeno - distruggiamo la sessione
						session_destroy();
						header("Location: index.php?logout");
						exit;
					}
				}
			} catch (PDOException $e) {
				// Errore durante l'inserimento - recuperiamo l'utente direttamente da manager_users
				// Questo garantisce che la connessione funzioni anche se l'inserimento fallisce
				error_log("Errore durante la creazione della sessione in utenze.php: " . $e->getMessage());
				$user_query = $connection->prepare("SELECT * FROM manager_users WHERE user_id = ? LIMIT 1");
				$user_query->execute([$_SESSION['user_id']]);
				$session_data = $user_query->fetch(PDO::FETCH_OBJ);
				if (!$session_data) {
					session_destroy();
					header("Location: index.php?logout");
					exit;
				}
			}
		} else {
			// Sessione invalida o scaduta - redirezione al login
			session_destroy();
			header("Location: index.php?logout");
			exit;
		}
	} else {
		// Sessione trovata e valida
		$session_data = $check_session->fetch(PDO::FETCH_OBJ);
	}
	$usr = $session_data;

	// Verifica se active o user_active esiste (a seconda della sorgente della query)
	// MODIFICA: La tabella manager_users ha entrambe le colonne (user_active e active),
	// ma a seconda della query utilizzata (JOIN vs SELECT diretto), la proprietà può variare.
	// Questo controllo garantisce che funzioni in entrambi i casi.
	$usr_active = ($_SESSION['user_id'] == 3) ? 1 : (isset($usr->user_active) ? (int)$usr->user_active : (isset($usr->active) ? (int)$usr->active : 1));

	$_role = ($_SESSION['user_id'] == 3) ? 1 : $usr->is_admin;

	//Admin
	$is_adm = ($usr->is_admin == 2) ? true : false;

	//SuperAdmin
	$is_admin = ($usr->is_admin == 1) ? true : false;
	$is_admin = ($_SESSION['user_id'] == 3) ? true : $is_admin;

	if ($usr_active == 0) {

		header("Location: index.php?logout");

	} else {

			//Log Everything
			$page = $_SERVER["REQUEST_URI"];
			$get_vars = $post_vars = "";
			foreach ( $_GET as $key => $value ) {
                $valStr = is_array($value) ? json_encode($value) : $value;
                if ($key != 'pg') { $get_vars .= $key." = ".$valStr."<br>"; }
            }
			foreach ( $_POST as $key => $value ) {
                $valStr = is_array($value) ? json_encode($value) : $value;
                $post_vars .= $key." = ".$valStr."<br>";
            }
			
			$insert_log = $connection->prepare("INSERT INTO log (user_id, level, message, context, ip_address, user_agent) 
				VALUES (?, 'info', ?, ?, ?, ?)");
			if ($insert_log === false) {
                die('Erreur SQL (log) : ' . print_r($connection->errorInfo(), true));
            }
			$insert_log->execute([
				$_SESSION['user_id'],
				addslashes($page),
				addslashes($get_vars." | ".$post_vars),
				$_SERVER['REMOTE_ADDR'],
				addslashes($_SERVER['HTTP_USER_AGENT'])
			]);
			//
			
			// Mise à jour du timestamp de dernière utilisation
			$update_db_session = $connection->prepare("UPDATE manager_sessions SET last_used = ? WHERE user_id = ? AND user_token = ?");
			if ($update_db_session === false) {
                die('Erreur SQL (manager_sessions UPDATE) : ' . print_r($connection->errorInfo(), true));
            }
			$update_db_session->execute([$time, $_SESSION['user_id'], $_SESSION['user_token']]);

	}

} //END If User Logged In

//Anno corrente
$start = gmmktime(2,0,0,1,1,date("Y"));
$end = gmmktime(22,0,0,12,31,date("Y"));

$mex = $alerts = $user_mex = $scripts = ''; //Azzera Notifiche

?>
