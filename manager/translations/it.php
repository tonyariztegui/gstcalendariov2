<?php

/**
 * Please note: we can use unencoded characters like ö, é etc here as we use the html5 doctype with utf8 encoding
 * in the application's header (in views/_header.php). To add new languages simply copy this file,
 * and create a language switch in your root files.
 */

// login & registration classes
define("MESSAGE_ACCOUNT_NOT_ACTIVATED", "La tua utenza non è attiva, contatta l'amministratore di sistema.");
define("MESSAGE_CAPTCHA_WRONG", "Codice di verifica non corretto!");
define("MESSAGE_COOKIE_INVALID", "Cookie non valido");
define("MESSAGE_DATABASE_ERROR", "Problemi nella connessione al Database");
define("MESSAGE_EMAIL_ALREADY_EXISTS", "Attenzione, indirizzo email già presente in Database.");
define("MESSAGE_EMAIL_CHANGE_FAILED", "Ops! La modifica richiesta non è stata effettuata.");
define("MESSAGE_EMAIL_CHANGED_SUCCESSFULLY", "Il tuo indirizzo email di riferimento è stato modificato con successo. Il tuo nuovo indirizzo è ");
define("MESSAGE_EMAIL_EMPTY", "Inserisci un indirizo email");
define("MESSAGE_EMAIL_INVALID", "Ops! Verifica l'indirizzo email inserito.");
define("MESSAGE_EMAIL_SAME_LIKE_OLD_ONE", "Ops! L'indirizzo email inserito è identico al precdente.");
define("MESSAGE_EMAIL_TOO_LONG", "Il campo email non può essere più lungo di 64 caratteri.");
define("MESSAGE_NOMINATIVO_INVALID", "Ops! Non hai inserito un nominativo.");
define("MESSAGE_LINK_PARAMETER_EMPTY", "Empty link parameter data.");
define("MESSAGE_LOGGED_OUT", "Logout effettuato con successo!");
// The "login failed"-message is a security improved feedback that doesn't show a potential attacker if the user exists or not
define("MESSAGE_LOGIN_FAILED", "Login fallito.");
define("MESSAGE_OLD_PASSWORD_WRONG", "La vecchia password inserita è errata");
define("MESSAGE_PASSWORD_BAD_CONFIRM", "I campi Password e Ripeti Password sono diversi");
define("MESSAGE_PASSWORD_CHANGE_FAILED", "Sorry, your password changing failed.");
define("MESSAGE_PASSWORD_CHANGED_SUCCESSFULLY", "Password modificata con successo.");
define("MESSAGE_PASSWORD_EMPTY", "Il campo password è vuoto!");
define("MESSAGE_PASSWORD_RESET_MAIL_FAILED", "Password reset mail NOT successfully sent! Error: ");
define("MESSAGE_PASSWORD_RESET_MAIL_SUCCESSFULLY_SENT", "Informazioni per il reset della tua password correttamente inviate al tuo indirizzo email.");
define("MESSAGE_PASSWORD_TOO_SHORT", "La password deve essere lunga lameno 6 caratteri.");
define("MESSAGE_PASSWORD_WRONG", "Ops! Password non corretta. Riprova.");
define("MESSAGE_PASSWORD_WRONG_3_TIMES", "Hai inserito una password non corretta per 3 volte. Attendi 30 secondi prima di riprovare.");
define("MESSAGE_REGISTRATION_ACTIVATION_NOT_SUCCESSFUL", "Attenzione, nessuna corrispondenza trovata in database.");
define("MESSAGE_REGISTRATION_ACTIVATION_SUCCESSFUL", "Utenza attivata. Adesso puoi effettuare il Login.");
define("MESSAGE_REGISTRATION_FAILED", "Sorry, your registration failed. Please go back and try again.");
define("MESSAGE_RESET_LINK_HAS_EXPIRED", "Your reset link has expired. Please use the reset link within one hour.");
define("MESSAGE_VERIFICATION_MAIL_ERROR", "Sorry, we could not send you an verification mail. Your account has NOT been created.");
define("MESSAGE_VERIFICATION_MAIL_NOT_SENT", "Verification Mail NOT successfully sent! Error: ");
define("MESSAGE_VERIFICATION_MAIL_SENT", "Account creato con successo, è stata inviata una mail all'utente con il link di verifica e attivazione automatica.");
define("MESSAGE_USER_DOES_NOT_EXIST", "Utente non esistente");
define("MESSAGE_USERNAME_BAD_LENGTH", "Il campo Username non può essere più corto di 2 caratteri");
define("MESSAGE_USERNAME_CHANGE_FAILED", "Sorry, your chosen username renaming failed");
define("MESSAGE_USERNAME_CHANGED_SUCCESSFULLY", "Il tuo username è stato modificato con successo. Il tuo nuovo username è ");
define("MESSAGE_USERNAME_EMPTY", "Ops! Inserisci il tuo Username");
define("MESSAGE_USERNAME_EXISTS", "Ops! Sembra che lo username inserito sia già utilizzato da un altro utente.");
define("MESSAGE_USERNAME_INVALID", "Il tuo username può contenere solo caratteri alfanumerici, e deve essere lungo da 2 a 64 caratteri");
define("MESSAGE_USERNAME_SAME_LIKE_OLD_ONE", "Ops! Lo username inserito è identico a quello utilizzato in precedenza.");

// views
define("WORDING_BACK_TO_LOGIN", "Back to Login Page");
define("WORDING_CHANGE_EMAIL", "Change email");
define("WORDING_CHANGE_PASSWORD", "Change password");
define("WORDING_CHANGE_USERNAME", "Change username");
define("WORDING_CURRENTLY", "currently");
define("WORDING_EDIT_USER_DATA", "Edit user data");
define("WORDING_EDIT_YOUR_CREDENTIALS", "You are logged in and can edit your credentials here");
define("WORDING_FORGOT_MY_PASSWORD", "I forgot my password");
define("WORDING_LOGIN", "Log in");
define("WORDING_LOGOUT", "Log out");
define("WORDING_NEW_EMAIL", "New email");
define("WORDING_NEW_PASSWORD", "New password");
define("WORDING_NEW_PASSWORD_REPEAT", "Repeat new password");
define("WORDING_NEW_USERNAME", "New username (username cannot be empty and must be azAZ09 and 2-64 characters)");
define("WORDING_OLD_PASSWORD", "Your OLD Password");
define("WORDING_PASSWORD", "Password");
define("WORDING_PROFILE_PICTURE", "Your profile picture (from gravatar):");
define("WORDING_REGISTER", "Register");
define("WORDING_REGISTER_NEW_ACCOUNT", "Register new account");
define("WORDING_REGISTRATION_CAPTCHA", "Please enter these characters");
define("WORDING_REGISTRATION_EMAIL", "User's email (please provide a real email address, you'll get a verification mail with an activation link)");
define("WORDING_REGISTRATION_PASSWORD", "Password (min. 6 characters!)");
define("WORDING_REGISTRATION_PASSWORD_REPEAT", "Password repeat");
define("WORDING_REGISTRATION_USERNAME", "Username (only letters and numbers, 2 to 64 characters)");
define("WORDING_REMEMBER_ME", "Keep me logged in (for 2 weeks)");
define("WORDING_REQUEST_PASSWORD_RESET", "Request a password reset. Enter your username and you'll get a mail with instructions:");
define("WORDING_RESET_PASSWORD", "Reset my password");
define("WORDING_SUBMIT_NEW_PASSWORD", "Submit new password");
define("WORDING_USERNAME", "Username");
define("WORDING_YOU_ARE_LOGGED_IN_AS", "You are logged in as ");
