<?php

date_default_timezone_set('Europe/Rome');

// Includere la configurazione centralizzata del concorso
include_once(__DIR__ . '/../../app/inc/config.php');
include_once(__DIR__ . '/../../app/inc/functions.php');

// DB config centralizzata
define('DB_HOST', $db_config['host']);
define('DB_NAME', $db_config['database']);
define('DB_USER', $db_config['username']);
define('DB_PASS', $db_config['password']);

define('COOKIE_RUNTIME', 1209600);
define('COOKIE_DOMAIN', getenv('COOKIE_DOMAIN') ?: '');

final class ManagerSecretKeyException extends RuntimeException
{
}

if (!function_exists('managerIsLocalEnvironment')) {
    function managerIsLocalEnvironment(): bool
    {
        $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? ''));
        if ($host === '' || PHP_SAPI === 'cli') {
            return true;
        }
        if ($host === 'localhost' || str_starts_with($host, '127.0.0.1') || str_starts_with($host, 'localhost:')) {
            return true;
        }
        return str_ends_with($host, '.local') || str_ends_with($host, '.test');
    }
}

if (!function_exists('managerResolveSecretKey')) {
    function managerResolveSecretKey(string $envKey, string $purpose): string
    {
        $value = getenv($envKey);
        if ($value !== false && $value !== '') {
            return $value;
        }

        $isProduction = (getenv('APP_ENV') === 'production');
        if ($isProduction && !managerIsLocalEnvironment()) {
            throw new ManagerSecretKeyException($envKey . ' environment variable is required in production.');
        }

        return hash('sha256', DB_NAME . '|' . DB_HOST . '|' . $purpose);
    }
}

define('COOKIE_SECRET_KEY', managerResolveSecretKey('COOKIE_SECRET_KEY', 'cookie'));
define('TOKEN_SECRET_KEY', managerResolveSecretKey('TOKEN_SECRET_KEY', 'token'));

$is_production = (getenv('APP_ENV') === 'production');

// Email manager - riusa configurazione condivisa o fallback sandbox

define('MAILTRAP_HOST', $email_config['smtp_host'] ?: ($is_production ? '' : 'sandbox.smtp.mailtrap.io'));
define('MAILTRAP_PORT', $email_config['smtp_port'] ?: ($is_production ? 587 : 2525));
define('MAILTRAP_USERNAME', $email_config['smtp_username'] ?: '');
define('MAILTRAP_PASSWORD', $email_config['smtp_password'] ?: '');
define('MAILTRAP_FROM_EMAIL', $email_config['from_email'] ?: 'no-reply@example.com');
define('MAILTRAP_FROM_NAME', ($email_config['from_name'] ?: ($contest_config['name'] ?? 'Contest')) . ' - Manager');

define('EMAIL_USE_SMTP', true);
define('EMAIL_SMTP_AUTH', true);
define('EMAIL_SMTP_ENCRYPTION', $is_production ? 'tls' : '');
define('EMAIL_SMTP_HOST', MAILTRAP_HOST);
define('EMAIL_SMTP_USERNAME', MAILTRAP_USERNAME);
define('EMAIL_SMTP_PASSWORD', MAILTRAP_PASSWORD);
define('EMAIL_SMTP_PORT', MAILTRAP_PORT);

define('HASH_COST_FACTOR', '10');

// URL base per manager
$base_url = rtrim(getBaseUrl(), '/');
$manager_url = $base_url . '/manager';

// Email reset password
define('EMAIL_PASSWORDRESET_FROM', MAILTRAP_FROM_EMAIL);
define('EMAIL_PASSWORDRESET_FROM_NAME', MAILTRAP_FROM_NAME);
define('EMAIL_PASSWORDRESET_SUBJECT', 'Reset Password - Manager');
define('EMAIL_PASSWORDRESET_URL', $manager_url . '/password_reset.php');
define('EMAIL_PASSWORDRESET_CONTENT', 'Please click on this link to reset your password: ');

// Connessione PDO
try {
    $connection = conn_pdo();
} catch (PDOException $e) {
    echo $e->getMessage();
}

// Utility
function myTruncate($string, $limit, $break='.', $pad='...') {
    if(strlen($string) <= $limit) return $string;
    if(false !== ($breakpoint = strpos($string, $break, $limit))) {
      if($breakpoint < strlen($string) - 1) {
        $string = substr($string, 0, $breakpoint) . $pad;
      }
    }
    return $string;
}
  
function get_string_between($string, $start, $end){
    $string = ' ' . $string;
    $ini = strpos($string, $start);
    if ($ini == 0) return '';
    $ini += strlen($start);
    $len = strpos($string, $end, $ini) - $ini;
    return substr($string, $ini, $len);
}

?>
