<?php

if (!function_exists('conn')) {
    function conn() {
        global $db_config;

        $connection = mysqli_connect(
            $db_config['host'],
            $db_config['username'],
            $db_config['password'],
            $db_config['database']
        );

        if (!$connection) {
            die('Errore connessione: ' . mysqli_connect_error());
        }

        mysqli_set_charset($connection, $db_config['charset']);
        return $connection;
    }
}

if (!function_exists('conn_pdo')) {
    function conn_pdo() {
        global $db_config;

        try {
            $dsn = sprintf(
                'mysql:host=%s;dbname=%s;charset=%s',
                $db_config['host'],
                $db_config['database'],
                $db_config['charset']
            );

            $conn = new PDO($dsn, $db_config['username'], $db_config['password']);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $conn->exec("SET NAMES 'utf8'");

            return $conn;
        } catch (PDOException $e) {
            die('Connection failed: ' . $e->getMessage());
        }
    }
}

if (!function_exists('isTestMode')) {
    function isTestMode() {
        global $test_config;
        return ($test_config['environment'] ?? '') === 'test';
    }
}

if (!function_exists('getBaseUrl')) {
    function getBaseUrl() {
        global $app_config;
        if (!empty($app_config['base_url'])) {
            return rtrim($app_config['base_url'], '/');
        }

        if (!isset($_SERVER['HTTP_HOST'])) {
            return 'http://localhost/contest-app';
        }

        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        $path = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');

        if (strpos($host, 'localhost') !== false) {
            return $protocol . '://' . $host . '/contest-app';
        }

        return $protocol . '://' . $host . $path;
    }
}

if (!function_exists('formatPhoneWithPrefix')) {
    function formatPhoneWithPrefix(string $prefix, string $number): string
    {
        $prefix = trim($prefix);
        $digits = preg_replace('/\D+/', '', $number);

        if ($prefix === '' && $digits === '') {
            return '';
        }

        if ($prefix === '') {
            if ($digits === '') {
                return '';
            }

            if (strlen($digits) > 2) {
                $guessedPrefix = '+' . substr($digits, 0, 2);
                $digits = substr($digits, 2);
                if ($digits === '') {
                    return $guessedPrefix;
                }
                if (strlen($digits) > 3) {
                    $digits = substr($digits, 0, 3) . ' ' . substr($digits, 3);
                }
                return trim($guessedPrefix . ' ' . $digits);
            }

            return $digits;
        }

        if ($digits === '') {
            return $prefix;
        }

        if (strlen($digits) > 3) {
            $digits = substr($digits, 0, 3) . ' ' . substr($digits, 3);
        }

        return trim($prefix . ' ' . $digits);
    }
}

if (!function_exists('isFieldEnabled')) {
    function isFieldEnabled(string $field): bool
    {
        global $contest_config;
        if (!isset($contest_config['fields_enabled']) || !is_array($contest_config['fields_enabled'])) {
            return true;
        }
        return !empty($contest_config['fields_enabled'][$field]);
    }
}

if (!function_exists('parse_importo_scontrino')) {
    /**
     * Parse receipt amount (comma, dot, or space as decimal separator).
     * Returns null when empty or invalid.
     */
    function parse_importo_scontrino(string $raw): ?float
    {
        $s = html_entity_decode(trim($raw), ENT_QUOTES, 'UTF-8');
        $s = str_replace(["\u{00A0}", '€'], [' ', ''], $s);
        $s = trim($s);
        if ($s === '') {
            return null;
        }

        if (preg_match('/^\d+\s+\d{1,2}$/u', $s)) {
            $s = preg_replace('/\s+/u', '.', $s, 1);
        } else {
            $s = preg_replace('/\s+/u', '', $s);
        }

        if (strpos($s, ',') !== false && strpos($s, '.') !== false) {
            return null;
        }
        if (substr_count($s, ',') > 1 || substr_count($s, '.') > 1) {
            return null;
        }

        $s = str_replace(',', '.', $s);

        if (!preg_match('/^\d+(?:\.\d{1,2})?$/', $s) || !is_numeric($s)) {
            return null;
        }

        return round((float) $s, 2);
    }
}

if (!function_exists('get_fidelity_field_width')) {
    function get_fidelity_field_width(array $fidelity_config): string
    {
        $width = isset($fidelity_config['field_width']) ? trim((string) $fidelity_config['field_width']) : 'large';

        return in_array($width, ['small', 'large'], true) ? $width : 'large';
    }
}

if (!function_exists('get_fidelity_field_size')) {
    function get_fidelity_field_size(array $fidelity_config): int
    {
        return get_fidelity_field_width($fidelity_config) === 'small' ? 288 : 600;
    }
}

if (!function_exists('apply_fidelity_field_size_to_standard_map')) {
    function apply_fidelity_field_size_to_standard_map(array $standard_fields_map, array $fidelity_config): array
    {
        $size = get_fidelity_field_size($fidelity_config);
        foreach ($standard_fields_map as $letter => $field_info) {
            if (($field_info['name'] ?? '') === 'fidelity') {
                $standard_fields_map[$letter]['size'] = $size;
                break;
            }
        }

        return $standard_fields_map;
    }
}

if (!defined('SUMMERCOOP_THANKYOU_TOKEN_SESSION_KEY')) {
    define('SUMMERCOOP_THANKYOU_TOKEN_SESSION_KEY', 'sc_thankyou_participation_token_v1');
}
if (!defined('SUMMERCOOP_QUIZFLOW_TOKEN_SESSION_KEY')) {
    define('SUMMERCOOP_QUIZFLOW_TOKEN_SESSION_KEY', 'sc_quizflow_participation_token_v1');
}

if (!function_exists('contest_normalize_instantwin_prize_distribution')) {
    /**
     * Ripartizione premi Instant Win: default | pdv | cedi.
     * Migra da instantwin_per_pdv se instantwin_prize_distribution non è presente.
     */
    function contest_normalize_instantwin_prize_distribution(array $config): string
    {
        $raw = strtolower(trim((string) ($config['instantwin_prize_distribution'] ?? '')));
        if (in_array($raw, ['default', 'pdv', 'cedi'], true)) {
            return $raw;
        }
        if (!empty($config['instantwin_per_cedi'])) {
            return 'cedi';
        }
        if (!empty($config['instantwin_per_pdv'])) {
            return 'pdv';
        }

        return 'default';
    }
}

if (!function_exists('normalize_csv_import_cell_value')) {
    /**
     * Rimuove il wrapper formula Excel ="valore" usato da alcuni export CSV.
     */
    function normalize_csv_import_cell_value(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $value = trim($value);
        if ($value === '') {
            return '';
        }
        if (preg_match('/^="((?:[^"]|"")*)"$/', $value, $matches)) {
            return str_replace('""', '"', $matches[1]);
        }

        return $value;
    }
}

?>
