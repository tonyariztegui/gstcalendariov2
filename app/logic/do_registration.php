<?php
if (!defined('REG_DATE_FORMAT_IT')) {
    define('REG_DATE_FORMAT_IT', 'd/m/Y');
}
if (!defined('REG_REGEX_SPACES_UNICODE')) {
    define('REG_REGEX_SPACES_UNICODE', '/\s+/u');
}
if (!defined('REG_REGEX_CUSTOM_FIELD_ID')) {
    define('REG_REGEX_CUSTOM_FIELD_ID', '/[^a-z0-9_-]/i');
}
if (!defined('REG_HEADER_LOCATION_REGISTRAZIONE')) {
    define('REG_HEADER_LOCATION_REGISTRAZIONE', 'Location: registrazione');
}
if (!defined('REG_ERROR_PREFISSO_MAX_3')) {
    define('REG_ERROR_PREFISSO_MAX_3', '- Prefisso dovrebbe contenere al massimo 3 caratteri.');
}
if (!defined('REG_LABEL_CIFRE_SUFFIX')) {
    define('REG_LABEL_CIFRE_SUFFIX', ' cifre.');
}
if (!defined('REG_LABEL_CARATTERI_SUFFIX')) {
    define('REG_LABEL_CARATTERI_SUFFIX', ' caratteri.');
}
if (!defined('REG_SQL_SHOW_FIDELITY_COLUMN')) {
    define('REG_SQL_SHOW_FIDELITY_COLUMN', "SHOW COLUMNS FROM utenti LIKE 'fidelity'");
}
if (!defined('REG_SQL_BIND_FIDELITY')) {
    define('REG_SQL_BIND_FIDELITY', ':fidelity');
}
if (!defined('REG_LABEL_CODICE_PARTECIPAZIONE')) {
    define('REG_LABEL_CODICE_PARTECIPAZIONE', 'Codice partecipazione');
}
if (!defined('REG_REQUIRE_CONTEST_SECURITY')) {
    define('REG_REQUIRE_CONTEST_SECURITY', '/contest_security.php');
}
if (!defined('REG_ERROR_FIELD_PREFIX')) {
    define('REG_ERROR_FIELD_PREFIX', '- Il campo "');
}
if (!defined('REG_ERROR_NON_PUO_SUPERARE')) {
    define('REG_ERROR_NON_PUO_SUPERARE', '" non può superare ');
}
if (!defined('REG_SQL_BIND_FILE_ORIGINALE')) {
    define('REG_SQL_BIND_FILE_ORIGINALE', ':file_originale');
}
if (!defined('REG_SQL_BIND_EMAIL')) {
    define('REG_SQL_BIND_EMAIL', ':email');
}
if (!defined('REG_HEADER_LOCATION_VERIFICA')) {
    define('REG_HEADER_LOCATION_VERIFICA', 'Location: verifica');
}
if (!defined('REG_SQL_BIND_CODICE')) {
    define('REG_SQL_BIND_CODICE', ':codice');
}
if (!defined('REG_ERROR_GIA_UTILIZZATO_SUFFIX')) {
    define('REG_ERROR_GIA_UTILIZZATO_SUFFIX', '" è già stato utilizzato.');
}
if (!defined('REG_REGEX_CUSTOM_COLUMN_NAME')) {
    define('REG_REGEX_CUSTOM_COLUMN_NAME', '/[^a-z0-9_]/i');
}
// Includi le date del concorso dal file di configurazione
include __DIR__ . '/../inc/config.php';
require_once __DIR__ . '/../inc/functions.php';
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
if (empty($contest_config['published']) && empty($_SESSION['contest_preview'] ?? null)) {
    header('Location: benvenuto');
    exit;
}
$now = time();
$fields_enabled = $contest_config['fields_enabled'] ?? [];
$fields_required = $contest_config['fields_required'] ?? [];
$pdv_mode = strtolower(trim((string) ($contest_config['pdv_config']['mode'] ?? 'combinato')));
if (!in_array($pdv_mode, ['combinato', 'cascata'], true)) {
    $pdv_mode = 'combinato';
}

// Lettura robusta dei flag booleani (gestisce anche stringhe "false"/"0")
$flag = static function ($value, bool $default = true): bool {
    if ($value === null) {
        return $default;
    }
    $parsed = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    return $parsed === null ? $default : $parsed;
};

function field_enabled(string $key): bool {
    global $fields_enabled, $flag;
    if (!array_key_exists($key, $fields_enabled)) {
        return true;
    }
    return $flag($fields_enabled[$key], true);
}

function field_required(string $key): bool {
    global $fields_required, $fields_enabled, $flag;
    if (!field_enabled($key)) {
        return false;
    }
    if (!array_key_exists($key, $fields_required)) {
        return $flag($fields_enabled[$key] ?? null, true);
    }
    return $flag($fields_required[$key], true);
}

require_once __DIR__ . '/codice_partecipazione_validation.php';

$require_receipt = field_required('scontrino');
$require_amount = field_required('importo');
$require_receipt_date = field_required('data_scontrino');
$receipt_upload_present = isset($_FILES['scontrino'])
    && ($_FILES['scontrino']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
$auto_prize_with_receipt = $flag($contest_config['instantwin_auto_prize_with_receipt'] ?? null, false);
if (!$require_receipt && isset($_FILES['scontrino'])) {
    unset($_FILES['scontrino']);
}
$max_upload_size = $contest_config['max_file_size'] ?? (5 * 1024 * 1024);
$max_upload_size_mb = max(1, (int) ceil($max_upload_size / (1024 * 1024)));
$importo_db_value = null;
$receipt_filename = null;
$receipt_file_type_options = [
    'jpg' => [
        'label' => 'JPG',
        'extensions' => ['jpg'],
        'mimes' => ['image/jpeg', 'image/pjpeg'],
    ],
    'png' => [
        'label' => 'PNG',
        'extensions' => ['png'],
        'mimes' => ['image/png'],
    ],
    'pdf' => [
        'label' => 'PDF',
        'extensions' => ['pdf'],
        'mimes' => ['application/pdf'],
    ],
];
$receipt_type_keys = array_keys($receipt_file_type_options);
$receipt_allowed_types = $contest_config['receipt_allowed_extensions'] ?? $receipt_type_keys;
$receipt_allowed_types = array_values(array_intersect($receipt_type_keys, array_map(static function ($val) {
    return strtolower((string) $val);
}, (array) $receipt_allowed_types)));
if (empty($receipt_allowed_types)) {
    $receipt_allowed_types = $receipt_type_keys;
}
$receipt_allowed_extension_map = [];
$receipt_allowed_labels = [];
foreach ($receipt_allowed_types as $typeKey) {
    if (!isset($receipt_file_type_options[$typeKey])) {
        continue;
    }
    $typeInfo = $receipt_file_type_options[$typeKey];
    $receipt_allowed_labels[] = $typeInfo['label'];
    foreach ($typeInfo['extensions'] as $ext) {
        $receipt_allowed_extension_map[$ext] = $typeInfo['mimes'];
    }
}
if (empty($receipt_allowed_extension_map)) {
    $receipt_allowed_extension_map = [
        'jpg' => ['image/jpeg', 'image/pjpeg'],
        'png' => ['image/png'],
        'pdf' => ['application/pdf'],
    ];
    $receipt_allowed_labels = ['JPG', 'PNG', 'PDF'];
}
$format_receipt_label = static function (array $items): string {
    $items = array_values(array_filter($items, static function ($val) {
        return trim((string) $val) !== '';
    }));
    $items = array_map(static function ($val) {
        return strtoupper((string) $val);
    }, $items);
    $count = count($items);
    if ($count === 0) {
        return '';
    }
    if ($count === 1) {
        return $items[0];
    }
    if ($count === 2) {
        return $items[0] . ' o ' . $items[1];
    }
    $last = array_pop($items);
    return implode(', ', $items) . ' o ' . $last;
};
$receipt_allowed_label_string = $format_receipt_label(array_keys($receipt_allowed_extension_map));
if ($receipt_allowed_label_string === '') {
    $receipt_allowed_label_string = 'JPG, PNG o PDF';
}
$receipt_format_error = '- Formato file non supportato';
$receipt_invalid_error = '- Il file caricato non è valido. Usa ' . $receipt_allowed_label_string . '.';

$contest_start_date = null;
if (!empty($contest_dates['start_date'])) {
    $contest_start_date = DateTime::createFromFormat('Y-m-d', $contest_dates['start_date']);
    if ($contest_start_date instanceof DateTime) {
        $contest_start_date->setTime(0, 0, 0);
    } else {
        $contest_start_date = null;
    }
}

$contest_end_date = null;
if (!empty($contest_dates['end_date'])) {
    $contest_end_date = DateTime::createFromFormat('Y-m-d', $contest_dates['end_date']);
    if ($contest_end_date instanceof DateTime) {
        $contest_end_date->setTime(23, 59, 59);
    } else {
        $contest_end_date = null;
    }
}

// Se siamo prima della data di inizio del concorso, mostra la pagina di attesa
if ($now < $date_debut) {
    include 'waiting_page.php';
    exit;
}

// Se siamo dopo la data di fine del concorso, mostra la pagina di concorso terminato
if ($now > $date_fin) {
    include 'waiting_page_ended.php';
    exit;
}

// La connessione è già inclusa in functions.php
$c_pdo = null;
$pdv_info = null;

require_once __DIR__ . '/../../public/assets/inc/sanitize.inc.php';
require_once __DIR__ . '/verify/qr_access.php';

// Funzione per ottenere il vero indirizzo IP
function getRealIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        return $_SERVER['REMOTE_ADDR'];
    }
}

function formatDateItalian($date) {
    if ($date instanceof DateTime) {
        return $date->format(REG_DATE_FORMAT_IT);
    }
    return '';
}

// Se abbiamo dati POST, processiamo la registrazione
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recupero dei campi del modulo
    $nome = html_entity_decode(sanitize(trim($_POST['nome'] ?? ''), SQL | HTML), ENT_QUOTES, 'UTF-8');
    $nome = preg_replace(REG_REGEX_SPACES_UNICODE, ' ', $nome);
    $nome = trim($nome);
    $cognome = html_entity_decode(sanitize(trim($_POST['cognome'] ?? ''), SQL | HTML), ENT_QUOTES, 'UTF-8');
    $cognome = preg_replace(REG_REGEX_SPACES_UNICODE, ' ', $cognome);
    $cognome = trim($cognome);
    $email = html_entity_decode(sanitize(trim($_POST['email'] ?? ''), SQL | HTML), ENT_QUOTES, 'UTF-8');
    $prefisso = sanitize(trim($_POST['prefisso'] ?? ''), SQL | HTML);
    $cellulare = sanitize(trim($_POST['cellulare'] ?? ''), SQL | HTML);
    $prov_pdv = html_entity_decode(sanitize(trim($_POST['prov_pdv'] ?? ''), SQL | HTML), ENT_QUOTES, 'UTF-8');
    $citta_pdv = html_entity_decode(sanitize(trim($_POST['citta_pdv'] ?? ''), SQL | HTML), ENT_QUOTES, 'UTF-8');
    $indirizzo_pdv_id = sanitize($_POST['indirizzo_pdv'] ?? '', SQL | HTML);
    $importo_raw = sanitize(trim($_POST['importo'] ?? ''), SQL | HTML);
    $importo = $importo_raw;
    $importo_numeric = null;
    $importo_db_value = null;
    $data_scontrino = html_entity_decode(sanitize(trim($_POST['data_scontrino'] ?? ''), SQL | HTML), ENT_QUOTES, 'UTF-8');
    $codice_partecipazione = html_entity_decode(sanitize(trim($_POST['codice_partecipazione'] ?? ''), SQL | HTML), ENT_QUOTES, 'UTF-8');
    $codice_partecipazione = preg_replace(REG_REGEX_SPACES_UNICODE, '', (string) $codice_partecipazione);
    // Campo ora_scontrino rimosso completamente
    $regione = '';
    $provincia = html_entity_decode(sanitize(trim($_POST['provincia'] ?? ''), SQL | HTML), ENT_QUOTES, 'UTF-8');
    $citta = html_entity_decode(sanitize(trim($_POST['citta'] ?? ''), SQL | HTML), ENT_QUOTES, 'UTF-8');
    $cap = preg_replace('/\D+/', '', (string) ($_POST['cap'] ?? ''));
    if (strlen($cap) > 5) {
        $cap = substr($cap, 0, 5);
    }
    $indirizzo = html_entity_decode(sanitize(trim($_POST['indirizzo'] ?? ''), SQL | HTML), ENT_QUOTES, 'UTF-8');
    $privacy = isset($_POST['privacy']) ? 1 : 0;
    $marketing = isset($_POST['marketing']) ? 1 : 0;
    $profilazione = isset($_POST['profilazione']) ? 1 : 0;
    $fidelity_raw = html_entity_decode(sanitize(trim($_POST['fidelity'] ?? ''), SQL | HTML), ENT_QUOTES, 'UTF-8');
    $fidelity = $fidelity_raw;
    $receipt_extension = null;
    $receipt_original = '';

    // Applica flag di campo: se disabilitato, svuota e considera non richiesto
    if (!field_enabled('nome')) {
        $nome = '';
    }
    if (!field_enabled('cognome')) {
        $cognome = '';
    }
    if (!field_enabled('email')) {
        $email = '';
    }
    if (!field_enabled('prefisso')) {
        $prefisso = '';
    }
    if (!field_enabled('cellulare')) {
        $cellulare = '';
    }
    if (!field_enabled('prov_pdv')) {
        $prov_pdv = '';
    }
    if (!field_enabled('citta_pdv')) {
        $citta_pdv = '';
    }
    if (!field_enabled('indirizzo_pdv')) {
        $indirizzo_pdv_id = '';
    }
    if (!field_enabled('importo')) {
        $importo_raw = $importo = '';
    }
    if (!field_enabled('data_scontrino')) {
        $data_scontrino = '';
    }
    if (!field_enabled('provincia')) {
        $provincia = '';
    }
    if (!field_enabled('cap')) {
        $cap = '';
    }
    if (!field_enabled('citta')) {
        $citta = '';
    }
    if (!field_enabled('indirizzo')) {
        $indirizzo = '';
    }
    if (!field_enabled('privacy')) {
        $privacy = 0;
    }
    if (!field_enabled('marketing')) {
        $marketing = 0;
    }
    if (!field_enabled('profilazione')) {
        $profilazione = 0;
    }
    if (!field_enabled('fidelity')) {
        $fidelity = '';
    }
    if (!field_enabled('codice_partecipazione')) {
        $codice_partecipazione = '';
    }

    // Campi personalizzabili
    $custom_fields_config = $contest_config['custom_fields'] ?? [];
    $custom_fields_values = [];
    if (is_array($custom_fields_config)) {
        foreach ($custom_fields_config as $customField) {
            if (!is_array($customField)) {
                continue;
            }
            $customId = preg_replace(REG_REGEX_CUSTOM_FIELD_ID, '', (string) ($customField['id'] ?? ''));
            $customLabel = trim((string) ($customField['label'] ?? ''));
            if ($customId === '' || $customLabel === '') {
                continue;
            }
            $rawValue = $_POST['custom_fields'][$customId] ?? '';
            $customValue = html_entity_decode(sanitize(trim((string) $rawValue), SQL | HTML), ENT_QUOTES, 'UTF-8');
            $custom_fields_values[$customId] = $customValue;
        }
    }

    // Recupero dei dati di sicurezza
    $ip_address = getRealIP();
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';

    // Recupera il punto vendita per precompilare provincia e città
    if (field_enabled('indirizzo_pdv') && $indirizzo_pdv_id && is_numeric($indirizzo_pdv_id)) {
        if ($c_pdo === null) {
            try {
                $c_pdo = conn_pdo();
            } catch (Throwable $e) {
                $_SESSION['registration_error'] = 'Errore di connessione. Riprovare più tardi.';
                header(REG_HEADER_LOCATION_REGISTRAZIONE);
                exit;
            }
        }

        if ($c_pdo !== null) {
            $stmt = $c_pdo->prepare('SELECT id, codice, prov, citta, indirizzo, ragsoc, insegna FROM punti_vendita WHERE id = :id AND active = 1 LIMIT 1');
            $stmt->bindValue(':id', (int)$indirizzo_pdv_id, PDO::PARAM_INT);
            $stmt->execute();
            $pdv_info = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

            if ($pdv_info) {
                if (field_enabled('prov_pdv') && !$prov_pdv) {
                    $prov_pdv = $pdv_info['prov'];
                }
                if (field_enabled('citta_pdv') && !$citta_pdv) {
                    $citta_pdv = $pdv_info['citta'];
                }
            }
        }
    }

    // Validazione rafforzata lato server
    $errors = [];
    $field_errors = [];

    // Nome
    if (field_enabled('nome')) {
        if (field_required('nome') && (!$nome || strlen($nome) < 2)) {
            $errors[] = '- Il nome è obbligatorio e deve contenere almeno 2 caratteri.';
            $field_errors['nome'] = '- Il nome è obbligatorio e deve contenere almeno 2 caratteri.';
        } elseif ($nome !== '' && strlen($nome) < 2) {
            $errors[] = '- Il nome deve contenere almeno 2 caratteri.';
            $field_errors['nome'] = '- Il nome deve contenere almeno 2 caratteri.';
        } elseif ($nome !== '' && strlen($nome) > 50) {
            $errors[] = '- Il nome non può superare i 50 caratteri.';
            $field_errors['nome'] = '- Il nome non può superare i 50 caratteri.';
        } elseif ($nome !== '' && !preg_match("/^[a-zA-Zà-ÿ \-']+$/u", $nome)) {
            $errors[] = '- Il nome può contenere solo lettere, spazi, trattini e apostrofo (\').';
            $field_errors['nome'] = '- Il nome può contenere solo lettere, spazi, trattini e apostrofo (\').';
        }
    }

    // Cognome
    if (field_enabled('cognome')) {
        if (field_required('cognome') && (!$cognome || strlen($cognome) < 2)) {
            $errors[] = '- Il cognome è obbligatorio e deve contenere almeno 2 caratteri.';
            $field_errors['cognome'] = '- Il cognome è obbligatorio e deve contenere almeno 2 caratteri.';
        } elseif ($cognome !== '' && strlen($cognome) < 2) {
            $errors[] = '- Il cognome deve contenere almeno 2 caratteri.';
            $field_errors['cognome'] = '- Il cognome deve contenere almeno 2 caratteri.';
        } elseif ($cognome !== '' && strlen($cognome) > 50) {
            $errors[] = '- Il cognome non può superare i 50 caratteri.';
            $field_errors['cognome'] = '- Il cognome non può superare i 50 caratteri.';
        } elseif ($cognome !== '' && !preg_match("/^[a-zA-Zà-ÿ \-']+$/u", $cognome)) {
            $errors[] = '- Il cognome può contenere solo lettere, spazi, trattini e apostrofo (\').';
            $field_errors['cognome'] = '- Il cognome può contenere solo lettere, spazi, trattini e apostrofo (\').';
        }
    }
    
    // Email
    if (field_enabled('email')) {
        $email = strtolower(html_entity_decode($email, ENT_QUOTES, 'UTF-8'));
        if (field_required('email') && !$email) {
            $errors[] = '- L\'email è obbligatoria.';
            $field_errors['email'] = '- L\'email è obbligatoria.';
        } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = '- Inserisci un indirizzo email valido.';
            $field_errors['email'] = '- Inserisci un indirizzo email valido.';
        } elseif ($email !== '' && !preg_match('/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', $email)) {
            $errors[] = '- Formato email non valido.';
            $field_errors['email'] = '- Formato email non valido.';
        } elseif ($email !== '' && strlen($email) > 254) {
            $errors[] = '- L\'email non può superare i 254 caratteri.';
            $field_errors['email'] = '- L\'email non può superare i 254 caratteri.';
        }
    }

    // Prefisso internazionale
    if (field_enabled('prefisso')) {
        $prefisso_clean = html_entity_decode($prefisso, ENT_QUOTES, 'UTF-8');
        $prefisso_clean = trim($prefisso_clean);
        if (field_required('prefisso') && !$prefisso_clean) {
            $errors[] = REG_ERROR_PREFISSO_MAX_3;
            $field_errors['prefisso'] = REG_ERROR_PREFISSO_MAX_3;
        } elseif ($prefisso_clean !== '' && !preg_match('/^\+\d{1,3}$/', $prefisso_clean)) {
            $errors[] = REG_ERROR_PREFISSO_MAX_3;
            $field_errors['prefisso'] = REG_ERROR_PREFISSO_MAX_3;
        } else {
            $prefisso = $prefisso_clean;
        }
    }

    // Cellulare (solo cifre, intervallo configurato tra 6 e 12)
    if (field_enabled('cellulare')) {
        $cellulare_min = max(6, min(12, (int) ($contest_config['cellulare_digits_min'] ?? 6)));
        $cellulare_max = max(6, min(12, (int) ($contest_config['cellulare_digits_max'] ?? 12)));
        if ($cellulare_min > $cellulare_max) {
            $cellulare_max = $cellulare_min;
        }
        $cellulare_clean = html_entity_decode($cellulare, ENT_QUOTES, 'UTF-8');
        $cellulare_clean = preg_replace('/\D/', '', $cellulare_clean);
        $cellulare_clean = trim($cellulare_clean);
        $len = strlen($cellulare_clean);
        if (field_required('cellulare') && (!$cellulare_clean || $len < $cellulare_min || $len > $cellulare_max)) {
            $cellulare_msg = $cellulare_min === $cellulare_max
                ? '- Il numero di cellulare è obbligatorio e deve contenere esattamente ' . $cellulare_min . REG_LABEL_CIFRE_SUFFIX
                : '- Il numero di cellulare è obbligatorio e deve contenere tra ' . $cellulare_min . ' e ' . $cellulare_max . REG_LABEL_CIFRE_SUFFIX;
            $errors[] = $cellulare_msg;
            $field_errors['cellulare'] = $cellulare_msg;
        } elseif ($cellulare_clean !== '' && ($len < $cellulare_min || $len > $cellulare_max)) {
            $cellulare_msg = $cellulare_min === $cellulare_max
                ? '- Il numero di cellulare deve contenere esattamente ' . $cellulare_min . REG_LABEL_CIFRE_SUFFIX
                : '- Il numero di cellulare deve contenere tra ' . $cellulare_min . ' e ' . $cellulare_max . REG_LABEL_CIFRE_SUFFIX;
            $errors[] = $cellulare_msg;
            $field_errors['cellulare'] = $cellulare_msg;
        } else {
            $cellulare = $cellulare_clean;
        }
    }

    // Codice fidelity
    if (field_enabled('fidelity')) {
        $rules = $contest_config['fidelity'] ?? [];
        $fidelity_length = isset($rules['length']) ? (int) $rules['length'] : 0;
        $fidelity_type = strtolower($rules['type'] ?? 'numeric');
        $fidelity_clean = $fidelity_type === 'alphanumeric'
            ? preg_replace('/[^A-Za-z0-9]/', '', $fidelity)
            : preg_replace('/\D/', '', $fidelity);
        $fidelity = $fidelity_clean;

        $pattern_ok = $fidelity_type === 'alphanumeric'
            ? preg_match('/^[A-Za-z0-9]+$/', $fidelity_clean)
            : preg_match('/^[0-9]+$/', $fidelity_clean);

        if (field_required('fidelity') && $fidelity_clean === '') {
            $fid_obbl_msg = '- il codice fidelity è obbligatorio.';
            $errors[] = $fid_obbl_msg;
            $field_errors['fidelity'] = $fid_obbl_msg;
        } elseif ($fidelity_clean !== '' && !$pattern_ok) {
            $errors[] = '- Il codice fidelity può contenere solo ' . ($fidelity_type === 'alphanumeric' ? 'caratteri alfanumerici.' : 'cifre numeriche.');
            $field_errors['fidelity'] = '- Il codice fidelity può contenere solo ' . ($fidelity_type === 'alphanumeric' ? 'caratteri alfanumerici.' : 'cifre numeriche.');
        } elseif ($fidelity_clean !== '' && $fidelity_length > 0 && strlen($fidelity_clean) !== $fidelity_length) {
            $errors[] = '- Il codice fidelity deve avere esattamente ' . $fidelity_length . REG_LABEL_CARATTERI_SUFFIX;
            $field_errors['fidelity'] = '- Il codice fidelity deve avere esattamente ' . $fidelity_length . REG_LABEL_CARATTERI_SUFFIX;
        }

        $fidelity_unique_participation = !empty($rules['unique_participation']);
        if ($fidelity_unique_participation && $fidelity_clean !== '' && !isset($field_errors['fidelity'])) {
            try {
                if ($c_pdo === null) {
                    $c_pdo = conn_pdo();
                }
                $columns_check = $c_pdo->query(REG_SQL_SHOW_FIDELITY_COLUMN)->fetch();
                if ($columns_check !== false) {
                    $stmt_fidelity_used = $c_pdo->prepare('
                        SELECT 1
                        FROM partecipazioni p
                        JOIN utenti u ON u.id_cliente = p.id_cliente
                        WHERE u.fidelity = :fidelity
                        LIMIT 1
                    ');
                    $stmt_fidelity_used->bindValue(REG_SQL_BIND_FIDELITY, $fidelity_clean, PDO::PARAM_STR);
                    $stmt_fidelity_used->execute();
                    if ($stmt_fidelity_used->fetchColumn()) {
                        $errors[] = '- Questa carta fidelity ha già partecipato al concorso.';
                        $field_errors['fidelity'] = '- Questa carta fidelity ha già partecipato al concorso.';
                    }
                }
            } catch (Throwable $e) {
                $errors[] = '- Errore durante la verifica della carta fidelity. Riprova.';
                $field_errors['fidelity'] = '- Errore durante la verifica della carta fidelity. Riprova.';
            }
        }
    }

    // CAP residenza: esattamente 5 cifre (XOR con provincia lato config)
    if (field_enabled('cap')) {
        if (field_required('cap') && $cap === '') {
            $capMsg = '- Il CAP è obbligatorio e deve contenere esattamente 5 cifre.';
            $errors[] = $capMsg;
            $field_errors['cap'] = $capMsg;
        } elseif ($cap !== '' && !preg_match('/^\d{5}$/', $cap)) {
            $capMsg = '- Il CAP deve contenere esattamente 5 cifre.';
            $errors[] = $capMsg;
            $field_errors['cap'] = $capMsg;
        }
    }

    $provinciaRequiredEffective = field_required('provincia')
        || (field_enabled('citta') && field_required('citta'));
    if (field_enabled('provincia') && $provinciaRequiredEffective && $provincia === '') {
        $provMsg = '- Seleziona una provincia.';
        $errors[] = $provMsg;
        $field_errors['provincia'] = $provMsg;
    }
    if (field_enabled('citta') && field_required('citta') && $citta === '') {
        $cittaMsg = '- Seleziona una città.';
        $errors[] = $cittaMsg;
        $field_errors['citta'] = $cittaMsg;
    }
    if (field_enabled('indirizzo') && field_required('indirizzo') && $indirizzo === '') {
        $indirizzoMsg = '- L\'indirizzo è obbligatorio.';
        $errors[] = $indirizzoMsg;
        $field_errors['indirizzo'] = $indirizzoMsg;
    }

    // Codice partecipazione (Instant Win ou Estrazione): obbligatorio se il campo è abilitato
    $contest_mode = strtolower((string) ($contest_config['mode'] ?? ''));
    $codiceFieldActive = ($contest_mode === 'instantwin' || $contest_mode === 'estrazione') && field_enabled('codice_partecipazione');
    $labelCodice = trim((string) ($contest_config['instantwin_codice_label'] ?? ''));
    if ($labelCodice === '') {
        $labelCodice = REG_LABEL_CODICE_PARTECIPAZIONE;
    }
    if ($codiceFieldActive) {
        require_once __DIR__ . REG_REQUIRE_CONTEST_SECURITY;
        if ($c_pdo === null) {
            try {
                $c_pdo = conn_pdo();
            } catch (Throwable $e) {
                // Validazione codice gestirà l'errore DB se necessario
            }
        }
        $contestConfigArr = is_array($contest_config) ? $contest_config : [];
        if ($c_pdo instanceof PDO && contest_security_codice_protection_active($c_pdo, $contestConfigArr)) {
            $lockInfo = contest_security_codice_is_locked($c_pdo, $ip_address, $email);
            if ($lockInfo !== null) {
                $lockMsg = contest_security_codice_user_message('locked', [
                    'seconds_remaining' => (int) ($lockInfo['seconds_remaining'] ?? 0),
                ]);
                $errors[] = $lockMsg;
                $field_errors['codice_partecipazione'] = $lockMsg;
            }
        }
        if (!isset($field_errors['codice_partecipazione'])) {
            $pdvCodiceForm = trim((string) ($pdv_info['codice'] ?? ''));
            registration_validate_codice_partecipazione_submit(
                $codice_partecipazione,
                $labelCodice,
                field_required('codice_partecipazione'),
                $contestConfigArr,
                $pdvCodiceForm,
                $c_pdo,
                $errors,
                $field_errors
            );
        }
    }

    // Campi personalizzabili
    if (!empty($custom_fields_config) && is_array($custom_fields_config)) {
        foreach ($custom_fields_config as $customField) {
            if (!is_array($customField)) {
                continue;
            }
            $customId = preg_replace('/[^a-z0-9_-]/i', '', (string) ($customField['id'] ?? ''));
            $customLabel = trim((string) ($customField['label'] ?? ''));
            if ($customId === '' || $customLabel === '') {
                continue;
            }
            // Convertire i vecchi tipi nei nuovi per compatibilità
            $customType = strtolower(trim((string) ($customField['type'] ?? 'varchar')));
            $typeMapping = [
                'text' => 'varchar',
                'textarea' => 'text',
                'number' => 'int',
                'email' => 'varchar',
                'date' => 'date',
                'int' => 'int',
                'varchar' => 'varchar',
                'text' => 'text',
                'boolean' => 'boolean',
                'bool' => 'boolean'
            ];
            $customType = $typeMapping[$customType] ?? 'varchar';
            $customType = in_array($customType, ['int', 'varchar', 'text', 'date', 'boolean'], true) ? $customType : 'varchar';
            
            $customRequired = !empty($customField['required']);
            $customValue = $custom_fields_values[$customId] ?? '';
            
            // Per i campi boolean, convertire il valore
            if ($customType === 'boolean') {
                $customValue = ($customValue === '1' || $customValue === 'on' || $customValue === true) ? '1' : '0';
            }
            
            $customValueLength = mb_strlen($customValue, 'UTF-8');
            
            // Validazione campo obbligatorio (per boolean, verificare che la checkbox sia selezionata)
            if ($customRequired) {
                if ($customType === 'boolean' && $customValue !== '1') {
                    $message = REG_ERROR_FIELD_PREFIX . $customLabel . '" è obbligatorio.';
                    $errors[] = $message;
                    if (!isset($field_errors['custom_fields']) || !is_array($field_errors['custom_fields'])) {
                        $field_errors['custom_fields'] = [];
                    }
                    $field_errors['custom_fields'][$customId] = $message;
                    continue;
                } elseif ($customType !== 'boolean' && $customValue === '') {
                    $message = REG_ERROR_FIELD_PREFIX . $customLabel . '" è obbligatorio.';
                    $errors[] = $message;
                    if (!isset($field_errors['custom_fields']) || !is_array($field_errors['custom_fields'])) {
                        $field_errors['custom_fields'] = [];
                    }
                    $field_errors['custom_fields'][$customId] = $message;
                    continue;
                }
            }
            
            // Per i campi boolean, non è necessaria alcuna validazione supplementare
            if ($customType === 'boolean') {
                continue; // Skip altre validazioni per boolean
            }
            
            // Validazione date
            if ($customType === 'date' && $customValue !== '') {
                $dateValue = $customValue;
                $dateMin = isset($customField['date_min']) && trim($customField['date_min']) !== '' ? trim($customField['date_min']) : null;
                $dateMax = isset($customField['date_max_today']) && $customField['date_max_today'] ? date('Y-m-d') : (isset($customField['date_max']) && trim($customField['date_max']) !== '' ? trim($customField['date_max']) : null);
                $customErrorMessage = isset($customField['error_message']) && trim($customField['error_message']) !== '' ? trim($customField['error_message']) : null;
                
                if ($dateMin !== null && $dateValue < $dateMin) {
                    $message = $customErrorMessage !== null ? '- ' . $customErrorMessage : REG_ERROR_FIELD_PREFIX . $customLabel . '" non può essere precedente a ' . date(REG_DATE_FORMAT_IT, strtotime($dateMin)) . '.';
                    $errors[] = $message;
                    if (!isset($field_errors['custom_fields']) || !is_array($field_errors['custom_fields'])) {
                        $field_errors['custom_fields'] = [];
                    }
                    $field_errors['custom_fields'][$customId] = $message;
                    continue;
                }
                
                if ($dateMax !== null && $dateValue > $dateMax) {
                    $message = $customErrorMessage !== null ? '- ' . $customErrorMessage : REG_ERROR_FIELD_PREFIX . $customLabel . '" non può essere successivo a ' . date(REG_DATE_FORMAT_IT, strtotime($dateMax)) . '.';
                    $errors[] = $message;
                    if (!isset($field_errors['custom_fields']) || !is_array($field_errors['custom_fields'])) {
                        $field_errors['custom_fields'] = [];
                    }
                    $field_errors['custom_fields'][$customId] = $message;
                    continue;
                }
            }
            
            // Validazione campi INT
            if ($customType === 'int' && $customValue !== '') {
                $intExactDigits = isset($customField['int_exact_digits']) && $customField['int_exact_digits'] !== '' && $customField['int_exact_digits'] !== null ? (int)$customField['int_exact_digits'] : null;
                if ($intExactDigits !== null) {
                    $intExactDigits = max(1, min(10, $intExactDigits));
                }
                $customErrorMessage = isset($customField['error_message']) && trim($customField['error_message']) !== '' ? trim($customField['error_message']) : null;

                // Mode codice numerico (CAP): esattamente N cifre, senza range INT32/min/max
                if ($intExactDigits !== null) {
                    $rawDigits = trim((string) $customValue);
                    if (!preg_match('/^\d{' . $intExactDigits . '}$/', $rawDigits)) {
                        $message = $customErrorMessage !== null
                            ? '- ' . $customErrorMessage
                            : REG_ERROR_FIELD_PREFIX . $customLabel . '" deve contenere esattamente ' . $intExactDigits . ' cifre.';
                        $errors[] = $message;
                        if (!isset($field_errors['custom_fields']) || !is_array($field_errors['custom_fields'])) {
                            $field_errors['custom_fields'] = [];
                        }
                        $field_errors['custom_fields'][$customId] = $message;
                        continue;
                    }
                } else {
                $intValue = is_numeric($customValue) ? (int)$customValue : null;
                if ($intValue === null) {
                    $message = REG_ERROR_FIELD_PREFIX . $customLabel . '" deve essere un numero intero valido.';
                    $errors[] = $message;
                    if (!isset($field_errors['custom_fields']) || !is_array($field_errors['custom_fields'])) {
                        $field_errors['custom_fields'] = [];
                    }
                    $field_errors['custom_fields'][$customId] = $message;
                    continue;
                }
                
                // Verificare le limitazioni di un INT signed
                if ($intValue < -2147483648 || $intValue > 2147483647) {
                    $message = REG_ERROR_FIELD_PREFIX . $customLabel . '" deve essere compreso tra -2,147,483,648 e 2,147,483,647.';
                    $errors[] = $message;
                    if (!isset($field_errors['custom_fields']) || !is_array($field_errors['custom_fields'])) {
                        $field_errors['custom_fields'] = [];
                    }
                    $field_errors['custom_fields'][$customId] = $message;
                    continue;
                }
                
                $intMin = isset($customField['int_min']) && $customField['int_min'] !== '' && $customField['int_min'] !== null ? (int)$customField['int_min'] : null;
                $intMax = isset($customField['int_max']) && $customField['int_max'] !== '' && $customField['int_max'] !== null ? (int)$customField['int_max'] : null;
                
                if ($intMin !== null && $intValue < $intMin) {
                    $message = $customErrorMessage !== null ? '- ' . $customErrorMessage : REG_ERROR_FIELD_PREFIX . $customLabel . '" deve essere maggiore o uguale a ' . number_format($intMin, 0, ',', '.') . '.';
                    $errors[] = $message;
                    if (!isset($field_errors['custom_fields']) || !is_array($field_errors['custom_fields'])) {
                        $field_errors['custom_fields'] = [];
                    }
                    $field_errors['custom_fields'][$customId] = $message;
                    continue;
                }
                
                if ($intMax !== null && $intValue > $intMax) {
                    $message = $customErrorMessage !== null ? '- ' . $customErrorMessage : REG_ERROR_FIELD_PREFIX . $customLabel . '" deve essere minore o uguale a ' . number_format($intMax, 0, ',', '.') . '.';
                    $errors[] = $message;
                    if (!isset($field_errors['custom_fields']) || !is_array($field_errors['custom_fields'])) {
                        $field_errors['custom_fields'] = [];
                    }
                    $field_errors['custom_fields'][$customId] = $message;
                    continue;
                }
                }
            }
            
            // Non applicare le validazioni di lunghezza per i campi date, int
            $skipLengthValidation = in_array($customType, ['date', 'int'], true);
            
            // Se il campo non è vuoto, applica le validazioni di lunghezza (solo per text/textarea)
            if ($customValue !== '' && !$skipLengthValidation) {
                // Recuperare il messaggio di errore personalizzato se esiste
                $customErrorMessage = isset($customField['error_message']) && trim($customField['error_message']) !== '' 
                    ? trim($customField['error_message']) 
                    : null;
                
                // Validazione lunghezza esatta
                if (isset($customField['exact_length']) && $customField['exact_length'] !== null) {
                    $exactLength = (int)$customField['exact_length'];
                    if ($exactLength > 0 && $customValueLength !== $exactLength) {
                        // Utilizzare il messaggio personnalisato se esiste, altrimenti il messaggio di default
                        $message = $customErrorMessage !== null 
                            ? '- ' . $customErrorMessage
                            : REG_ERROR_FIELD_PREFIX . $customLabel . '" deve avere esattamente ' . $exactLength . REG_LABEL_CARATTERI_SUFFIX;
                        $errors[] = $message;
                        if (!isset($field_errors['custom_fields']) || !is_array($field_errors['custom_fields'])) {
                            $field_errors['custom_fields'] = [];
                        }
                        $field_errors['custom_fields'][$customId] = $message;
                        continue; // Skip altre validazioni se exact_length fallisce
                    }
                }
                
                // Validazione lunghezza minima (solo se exact_length non è impostato)
                if (!isset($customField['exact_length']) && isset($customField['min_length']) && $customField['min_length'] !== null) {
                    $minLength = (int)$customField['min_length'];
                    if ($minLength > 0 && $customValueLength < $minLength) {
                        // Utilizzare il messaggio personnalisato se esiste, altrimenti il messaggio di default
                        $message = $customErrorMessage !== null 
                            ? '- ' . $customErrorMessage
                            : REG_ERROR_FIELD_PREFIX . $customLabel . '" deve avere almeno ' . $minLength . REG_LABEL_CARATTERI_SUFFIX;
                        $errors[] = $message;
                        if (!isset($field_errors['custom_fields']) || !is_array($field_errors['custom_fields'])) {
                            $field_errors['custom_fields'] = [];
                        }
                        $field_errors['custom_fields'][$customId] = $message;
                    }
                }
                
                // Validazione lunghezza massima (solo se exact_length non è impostato)
                if (!isset($customField['exact_length']) && isset($customField['max_length']) && $customField['max_length'] !== null) {
                    $maxLength = (int)$customField['max_length'];
                    if ($maxLength > 0 && $customValueLength > $maxLength) {
                        // Utilizzare il messaggio personnalisato se esiste, altrimenti il messaggio di default
                        $message = $customErrorMessage !== null 
                            ? '- ' . $customErrorMessage
                            : REG_ERROR_FIELD_PREFIX . $customLabel . REG_ERROR_NON_PUO_SUPERARE . $maxLength . REG_LABEL_CARATTERI_SUFFIX;
                        $errors[] = $message;
                        if (!isset($field_errors['custom_fields']) || !is_array($field_errors['custom_fields'])) {
                            $field_errors['custom_fields'] = [];
                        }
                        $field_errors['custom_fields'][$customId] = $message;
                    }
                }
                
                // Sicurezza di default per i campi TEXT : limite di default 5000, configurabile da 5000 a 65535
                if ($customType === 'text') {
                    $textDefaultLimit = 5000; // Limite di default per TEXT
                    $textMaxLimit = 65535; // Limite MySQL per il tipo TEXT
                    // Se max_length non è definito, utilizzare 5000 di default
                    // Altrimenti, assicurarsi che sia compreso tra 5000 e 65535
                    if (isset($customField['max_length']) && $customField['max_length'] !== null) {
                        $configuredMaxLength = (int)$customField['max_length'];
                        $effectiveMaxLength = max($textDefaultLimit, min($configuredMaxLength, $textMaxLimit));
                    } else {
                        $effectiveMaxLength = $textDefaultLimit;
                    }
                    
                    if ($customValueLength > $effectiveMaxLength) {
                        $message = $customErrorMessage !== null 
                            ? '- ' . $customErrorMessage
                            : REG_ERROR_FIELD_PREFIX . $customLabel . REG_ERROR_NON_PUO_SUPERARE . number_format($effectiveMaxLength, 0, ',', '.') . REG_LABEL_CARATTERI_SUFFIX;
                        $errors[] = $message;
                        if (!isset($field_errors['custom_fields']) || !is_array($field_errors['custom_fields'])) {
                            $field_errors['custom_fields'] = [];
                        }
                        $field_errors['custom_fields'][$customId] = $message;
                    }
                }
                
                // Sicurezza di default per i campi VARCHAR : limite a 255 caratteri se max_length non è definito
                if ($customType === 'varchar') {
                    $varcharDefaultLimit = 255; // Limite di default per VARCHAR
                    // Se max_length non è definito, utilizzare 255 di default
                    $effectiveMaxLength = isset($customField['max_length']) && $customField['max_length'] !== null 
                        ? (int)$customField['max_length'] 
                        : $varcharDefaultLimit;
                    
                    if ($customValueLength > $effectiveMaxLength) {
                        $message = $customErrorMessage !== null 
                            ? '- ' . $customErrorMessage
                            : REG_ERROR_FIELD_PREFIX . $customLabel . REG_ERROR_NON_PUO_SUPERARE . number_format($effectiveMaxLength, 0, ',', '.') . REG_LABEL_CARATTERI_SUFFIX;
                        $errors[] = $message;
                        if (!isset($field_errors['custom_fields']) || !is_array($field_errors['custom_fields'])) {
                            $field_errors['custom_fields'] = [];
                        }
                        $field_errors['custom_fields'][$customId] = $message;
                    }
                }
            }
        }
    }

    // Punto vendita: in modalità cascata validare provincia+città+indirizzo.
    // In modalità combinato validare solo indirizzo_pdv.
    if ($pdv_mode === 'cascata' && field_required('prov_pdv')) {
        if (!$prov_pdv) {
            $errors[] = '- Seleziona una provincia del punto vendita.';
            $field_errors['prov_pdv'] = '- Seleziona una provincia del punto vendita.';
        }
    }
    if ($pdv_mode === 'cascata' && field_required('citta_pdv')) {
        if (!$citta_pdv) {
            $errors[] = '- Seleziona una città del punto vendita.';
            $field_errors['citta_pdv'] = '- Seleziona una città del punto vendita.';
        }
    }
    if (field_required('indirizzo_pdv')) {
        if (!$indirizzo_pdv_id || !is_numeric($indirizzo_pdv_id)) {
            $errors[] = '- Seleziona un indirizzo del punto vendita valido.';
            $field_errors['indirizzo_pdv'] = '- Seleziona un indirizzo del punto vendita valido.';
        }
    }

    // Privacy
    if (field_required('privacy') && !$privacy) {
        $errors[] = '- Devi accettare l\'informativa privacy per continuare.';
        $field_errors['privacy'] = '- Devi accettare l\'informativa privacy per continuare.';
    }
    
    if ((field_enabled('scontrino') && ($require_receipt || $receipt_upload_present))
        || (field_enabled('importo') && ($require_amount || trim((string) $importo_raw) !== ''))
        || (field_enabled('data_scontrino') && ($require_receipt_date || trim((string) $data_scontrino) !== ''))
    ) {
        // Importo scontrino
        if (field_enabled('importo') && ($require_amount || trim((string) $importo_raw) !== '')) {
            $importo_invalid_msg = '- l\'importo dello scontrino deve essere un numero valido. inclusi decimali, senza virgole o punti';
            $importo_numeric = parse_importo_scontrino($importo_raw);

            if ($importo_numeric === null) {
                $errors[] = $importo_invalid_msg;
                $field_errors['importo'] = $importo_invalid_msg;
            } else {

                $min_spend = $contest_config['min_spend'] ?? 10.00;
                if ($importo_numeric < 0) {
                    $errors[] = '- L\'importo dello scontrino non può essere negativo.';
                    $field_errors['importo'] = '- L\'importo dello scontrino non può essere negativo.';
                } elseif ($min_spend > 0 && $importo_numeric < $min_spend) {
                    $errors[] = '- L\'importo dello scontrino deve essere di almeno €' . number_format($min_spend, 2, ',', '') . '.';
                    $field_errors['importo'] = '- L\'importo dello scontrino deve essere di almeno €' . number_format($min_spend, 2, ',', '') . '.';
                } elseif ($importo_numeric > 9999.99) {
                    $errors[] = '- L\'importo dello scontrino non può superare €9999,99.';
                    $field_errors['importo'] = '- L\'importo dello scontrino non può superare €9999,99.';
                } else {
                    $importo = number_format($importo_numeric, 2, ',', '');
                    $importo_db_value = number_format($importo_numeric, 2, '.', '');
                }
            }
        }
        
        // Data scontrino
        if (field_enabled('data_scontrino') && ($require_receipt_date || trim((string) $data_scontrino) !== '')) {
            if (!$data_scontrino) {
                $errors[] = '- La data dello scontrino è obbligatoria.';
                $field_errors['data_scontrino'] = '- La data dello scontrino è obbligatoria.';
            } else {
                $receipt_date = DateTime::createFromFormat('Y-m-d', $data_scontrino);
                if (!($receipt_date instanceof DateTime)) {
                    $receipt_date = DateTime::createFromFormat('d/m/Y', $data_scontrino);
                }
                if ($receipt_date instanceof DateTime) {
                    $errorsBag = DateTime::getLastErrors();
                    if (is_array($errorsBag) && (($errorsBag['warning_count'] ?? 0) > 0 || ($errorsBag['error_count'] ?? 0) > 0)) {
                        $receipt_date = false;
                    } else {
                        $receipt_date->setTime(0, 0, 0);
                        $data_scontrino = $receipt_date->format('Y-m-d');
                    }
                }
                
                $today = new DateTime();
                $today->setTime(0, 0, 0);
                
                if (!$receipt_date) {
                    $errors[] = '- Formato data non valido. Usa il formato GG/MM/AAAA.';
                    $field_errors['data_scontrino'] = '- Formato data non valido. Usa il formato GG/MM/AAAA.';
                } elseif ($contest_start_date && $receipt_date < $contest_start_date) {
                    $message = '- La data dello scontrino non può essere precedente all\'inizio del concorso.';
                    $errors[] = $message;
                    $field_errors['data_scontrino'] = $message;
                } elseif ($receipt_date > $today) {
                    $errors[] = '- La data dello scontrino non può essere futura.';
                    $field_errors['data_scontrino'] = '- La data dello scontrino non può essere futura.';
                } elseif ($contest_end_date && $receipt_date > $contest_end_date) {
                    $message = '- La data dello scontrino non può essere successiva alla fine del concorso.';
                    $errors[] = $message;
                    $field_errors['data_scontrino'] = $message;
                }
            }
        }
        
        // Validazione file scontrino
        if ($require_receipt && (!isset($_FILES['scontrino']) || $_FILES['scontrino']['error'] !== UPLOAD_ERR_OK)) {
            $errors[] = '- Carica una foto dello scontrino.';
            $field_errors['scontrino'] = '- Carica una foto dello scontrino.';
        } elseif (isset($_FILES['scontrino']) && $_FILES['scontrino']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['scontrino'];
            $allowedExtensions = $receipt_allowed_extension_map;
            $max_size = $max_upload_size;
            $originalName = $file['name'] ?? '';
            $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

            if (!$originalName || !isset($allowedExtensions[$extension])) {
                $errors[] = $receipt_format_error;
                $field_errors['scontrino'] = $receipt_format_error;
            } else {
                $isUploaded = !empty($file['tmp_name']) && is_uploaded_file($file['tmp_name']);

                if (!$isUploaded) {
                    $errors[] = $receipt_invalid_error;
                    $field_errors['scontrino'] = $receipt_invalid_error;
                } elseif ($file['size'] > $max_size) {
                    $errors[] = '- Il file è troppo grande. Dimensione massima: ' . $max_upload_size_mb . ' MB.';
                    $field_errors['scontrino'] = '- Il file è troppo grande. Dimensione massima: ' . $max_upload_size_mb . ' MB.';
                } else {
                    $detectedType = "";
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    if ($finfo) {
                        $detectedType = (string) finfo_file($finfo, $file['tmp_name']);
                        finfo_close($finfo);
                    }
                    if ($detectedType === "" && function_exists("mime_content_type")) {
                        $detectedType = (string) mime_content_type($file['tmp_name']);
                    }
                    $detectedType = strtolower(trim($detectedType));
                    $validMime = $detectedType !== "" && in_array($detectedType, $allowedExtensions[$extension], true);
                    if (!$validMime) {
                        $errors[] = $receipt_invalid_error;
                        $field_errors["scontrino"] = $receipt_invalid_error;
                    } else {
                        // Verificare duplicati solo se scontrino è abilitato
                        $candidate_original = substr(preg_replace('/[^A-Za-z0-9._-]/', '_', $originalName), 0, 255);
                        if (field_enabled('scontrino')) {
                            // Connessione DB necessaria per verificare duplicati
                            global $c_pdo;
                            if ($c_pdo === null) {
                                try {
                                    $c_pdo = conn_pdo();
                                } catch (Throwable $e) {
                                    $errors[] = '- Errore di connessione durante la verifica del file. Riprova.';
                                    $field_errors['scontrino'] = '- Errore di connessione durante la verifica del file. Riprova.';
                                }
                            }
                            
                            if ($c_pdo !== null) {
                                $duplicate_found = false;
                                
                                // Priorità: se fidelity è abilitato, verificare su fidelity, altrimenti su email
                                if (field_enabled('fidelity') && !empty($fidelity)) {
                                    // Verifica su fidelity + file_originale
                                    $columns_check = $c_pdo->query(REG_SQL_SHOW_FIDELITY_COLUMN)->fetch();
                                    if ($columns_check !== false) {
                                        $stmt_duplicate = $c_pdo->prepare('SELECT 1 FROM partecipazioni p JOIN utenti u ON u.id_cliente = p.id_cliente WHERE u.fidelity = ' . REG_SQL_BIND_FIDELITY . ' AND p.file_originale = ' . REG_SQL_BIND_FILE_ORIGINALE . ' LIMIT 1');
                                        $stmt_duplicate->bindValue(REG_SQL_BIND_FIDELITY, $fidelity, PDO::PARAM_STR);
                                        $stmt_duplicate->bindValue(REG_SQL_BIND_FILE_ORIGINALE, $candidate_original, PDO::PARAM_STR);
                                        $stmt_duplicate->execute();
                                        
                                        if ($stmt_duplicate->fetchColumn()) {
                                            $duplicate_found = true;
                                            $errors[] = '- Scontrino già registrato per questa carta fidelity.';
                                            $field_errors['scontrino'] = '- Scontrino già registrato per questa carta fidelity.';
                                        }
                                    }
                                } else {
                                    // Verifica su email + file_originale
                                    $stmt_duplicate = $c_pdo->prepare('SELECT 1 FROM partecipazioni p JOIN utenti u ON u.id_cliente = p.id_cliente WHERE LOWER(u.email) = LOWER(' . REG_SQL_BIND_EMAIL . ') AND p.file_originale = ' . REG_SQL_BIND_FILE_ORIGINALE . ' LIMIT 1');
                                    $stmt_duplicate->bindValue(REG_SQL_BIND_EMAIL, $email, PDO::PARAM_STR);
                                    $stmt_duplicate->bindValue(REG_SQL_BIND_FILE_ORIGINALE, $candidate_original, PDO::PARAM_STR);
                                    $stmt_duplicate->execute();

                                    if ($stmt_duplicate->fetchColumn()) {
                                        $duplicate_found = true;
                                        $errors[] = '- Scontrino già registrato per questo indirizzo email.';
                                        $field_errors['scontrino'] = '- Scontrino già registrato per questo indirizzo email.';
                                    }
                                }
                                
                                if (!$duplicate_found) {
                                    $receipt_extension = $extension === 'jpeg' ? 'jpg' : $extension;
                                    $receipt_original = $candidate_original;
                                }
                            }
                        } else {
                            // Scontrino disabilitato: nessun controllo duplicati
                            $receipt_extension = $extension === 'jpeg' ? 'jpg' : $extension;
                            $receipt_original = $candidate_original;
                        }
                    }
                }
            }
        }
    } else {
        // Campi scontrino disabilitati: non validare e non salvare
        $importo = null;
        $importo_numeric = null;
        $importo_db_value = null;
        $data_scontrino = null;
        $receipt_extension = null;
        $receipt_original = null;
        $receipt_date = null;
    }

    if (!empty($errors)) {
        if ($codiceFieldActive && isset($field_errors['codice_partecipazione'])) {
            if (!function_exists('contest_security_codice_protection_active')) {
                require_once __DIR__ . REG_REQUIRE_CONTEST_SECURITY;
            }
            if ($c_pdo === null) {
                try {
                    $c_pdo = conn_pdo();
                } catch (Throwable $e) {
                    $c_pdo = null;
                }
            }
            $contestConfigArr = is_array($contest_config) ? $contest_config : [];
            $codiceErr = (string) $field_errors['codice_partecipazione'];
            if ($c_pdo instanceof PDO
                && contest_security_codice_protection_active($c_pdo, $contestConfigArr)
                && contest_security_codice_failure_is_countable($codiceErr)
            ) {
                $failResult = contest_security_codice_record_failure($c_pdo, $ip_address, $email);
                if (!empty($failResult['blacklisted'])) {
                    $codiceErr = contest_security_codice_user_message('blacklist');
                } elseif (!empty($failResult['locked'])) {
                    $codiceErr = contest_security_codice_user_message('locked', [
                        'lock_seconds' => (int) ($failResult['lock_seconds'] ?? 0),
                    ]);
                } elseif (($failResult['remaining'] ?? 0) > 0) {
                    $codiceErr .= contest_security_codice_user_message('remaining', [
                        'remaining' => (int) $failResult['remaining'],
                    ]);
                }
                $field_errors['codice_partecipazione'] = $codiceErr;
                foreach ($errors as $idx => $err) {
                    if (!is_string($err)) {
                        continue;
                    }
                    if (str_contains($err, $labelCodice)
                        || str_contains($err, 'Attenzione: ti rest')
                        || str_contains($err, 'temporaneamente bloccato')
                    ) {
                        $errors[$idx] = $codiceErr;
                        break;
                    }
                }
            }
        }

        $_SESSION['registration_error'] = '<ul class="list-unstyled mb-0"><li>' . implode('</li><li>', $errors) . '</li></ul>';
        
        // Salvare i dati del modulo per ripristinarli
        $_SESSION['form_data'] = [
            'nome' => $nome,
            'cognome' => $cognome,
            'email' => $email,
            'prefisso' => $prefisso,
            'cellulare' => $cellulare,
            'prov_pdv' => $prov_pdv,
            'citta_pdv' => $citta_pdv,
            'indirizzo_pdv' => $indirizzo_pdv_id,
            'importo' => $importo_db_value !== null ? $importo : $importo_raw,
            'data_scontrino' => $data_scontrino,
            'regione' => $regione,
            'provincia' => $provincia,
            'citta' => $citta,
            'cap' => $cap,
            'indirizzo' => $indirizzo,
            'marketing' => $marketing,
            'profilazione' => $profilazione,
            'privacy' => $privacy,
            'fidelity' => $fidelity,
            'custom_fields' => $custom_fields_values,
        ];
        
        $_SESSION['field_errors'] = $field_errors;
        header(REG_HEADER_LOCATION_REGISTRAZIONE);
        exit;
    }

    // Sync cookie consent marketing ↔ checkbox form (se attivo in Configurazione)
    require_once __DIR__ . '/../inc/cookie_consent.php';
    $ccCfg = cookie_consent_normalize(
        $contest_config['cookie_consent'] ?? [],
        []
    );
    if (!empty($ccCfg['enabled']) && !empty($ccCfg['sync_registration_marketing'])) {
        cookie_consent_set_cookie((bool) $marketing);
    }

    // Connessione al DB solo dopo aver validato i campi
    global $c_pdo;
    if ($c_pdo === null) {
        try {
            $c_pdo = conn_pdo();
        } catch (Throwable $e) {
            $_SESSION['registration_error'] = 'Errore di connessione. Riprovare più tardi.';
            header(REG_HEADER_LOCATION_REGISTRAZIONE);
            exit;
        }
    }

    require_once __DIR__ . REG_REQUIRE_CONTEST_SECURITY;
    $securityBlock = contest_security_evaluate_registration([
        'email' => $email,
        'ip' => $ip_address,
        'fidelity' => $fidelity,
        'pdo' => $c_pdo,
    ]);
    if ($securityBlock !== null) {
        $contestConfigArr = is_array($contest_config) ? $contest_config : [];
        if (contest_security_codice_protection_active($c_pdo, $contestConfigArr)) {
            $codiceErrMsg = contest_security_registration_block_user_message($securityBlock, $c_pdo, $contestConfigArr);
            $_SESSION['registration_error'] = '<ul class="list-unstyled mb-0"><li>' . $codiceErrMsg . '</li></ul>';
            if (in_array($securityBlock, ['email_blacklist', 'domain_blocked', 'ip_blocked', 'rate_limit_ip', 'rate_limit_email', 'rate_limit_cooldown'], true)) {
                $_SESSION['field_errors'] = [];
            } else {
                $_SESSION['field_errors'] = ['codice_partecipazione' => $codiceErrMsg];
            }
        } else {
            $labelCodice = trim((string) ($contest_config['instantwin_codice_label'] ?? ''));
            if ($labelCodice === '') {
                $labelCodice = REG_LABEL_CODICE_PARTECIPAZIONE;
            }
            $codiceErrMsg = REG_ERROR_FIELD_PREFIX . $labelCodice . '" è errato.';
            $_SESSION['registration_error'] = '<ul class="list-unstyled mb-0"><li>' . $codiceErrMsg . '</li></ul>';
            $_SESSION['field_errors'] = ['codice_partecipazione' => $codiceErrMsg];
        }
        $_SESSION['form_data'] = [
            'nome' => $nome,
            'cognome' => $cognome,
            'email' => $email,
            'prefisso' => $prefisso,
            'cellulare' => $cellulare,
            'prov_pdv' => $prov_pdv,
            'citta_pdv' => $citta_pdv,
            'indirizzo_pdv' => $indirizzo_pdv_id,
            'importo' => $importo_db_value !== null ? $importo : $importo_raw,
            'data_scontrino' => $data_scontrino,
            'regione' => $regione,
            'provincia' => $provincia,
            'citta' => $citta,
            'cap' => $cap,
            'indirizzo' => $indirizzo,
            'marketing' => $marketing,
            'profilazione' => $profilazione,
            'privacy' => $privacy,
            'fidelity' => $fidelity,
            'custom_fields' => $custom_fields_values,
        ];
        header(REG_HEADER_LOCATION_REGISTRAZIONE);
        exit;
    }

    $pdv_required = field_required('indirizzo_pdv');
    $pdv_should_resolve = field_enabled('indirizzo_pdv') && ($pdv_required || $indirizzo_pdv_id !== '');
    if ($pdv_should_resolve) {
        // Recuperare le informazioni del punto vendita selezionato da punti_vendita
    if ($pdv_info === null) {
        $stmt_pdv = $c_pdo->prepare('SELECT id, codice, prov, citta, indirizzo, ragsoc, insegna, regione FROM punti_vendita WHERE id = :id AND active = 1 LIMIT 1');
        $stmt_pdv->bindValue(':id', (int)$indirizzo_pdv_id, PDO::PARAM_INT);
        $stmt_pdv->execute();
        $pdv_info = $stmt_pdv->fetch(PDO::FETCH_ASSOC);
    }

    if (!$pdv_info) {
        $_SESSION['registration_error'] = '- Punto vendita non trovato. Riprova.';
        header(REG_HEADER_LOCATION_REGISTRAZIONE);
        exit;
    }
    
    // In modalità cascata, verificare che provincia e città corrispondano ai select inviati.
    if ($pdv_mode === 'cascata' && ($pdv_info['prov'] !== $prov_pdv || $pdv_info['citta'] !== $citta_pdv)) {
        $_SESSION['registration_error'] = '- I dati del punto vendita non corrispondono. Riprova.';
        header(REG_HEADER_LOCATION_REGISTRAZIONE);
        exit;
    }
    
    // Usare insegna o ragsoc come INSEGNA_COOP
    $insegna_coop = !empty($pdv_info['insegna']) ? $pdv_info['insegna'] : (!empty($pdv_info['ragsoc']) ? $pdv_info['ragsoc'] : '');
    $codicePdv = $pdv_info['codice'] ?? null; // Stocker codice au lieu de id
    if (!$codicePdv) {
        $_SESSION['registration_error'] = '- Punto vendita senza codice. Seleziona un punto vendita valido e riprova.';
        header(REG_HEADER_LOCATION_REGISTRAZIONE);
        exit;
    }
    } else {
        $insegna_coop = '';
        $codicePdv = '';
    }

    // Gestione upload file scontrino
    $receipt_filename = '';
    if ($receipt_extension && isset($_FILES['scontrino']) && $_FILES['scontrino']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['scontrino'];
        if ($receipt_original === '' && !empty($file['name'])) {
            $receipt_original = substr(preg_replace('/[^A-Za-z0-9._-]/', '_', $file['name']), 0, 255);
        }
        $upload_dir = 'uploads/scontrini/';
        
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $receipt_filename = uniqid('scontrino_') . '_' . time() . '.' . $receipt_extension;
        $upload_path = $upload_dir . $receipt_filename;
        
        if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
            $_SESSION['registration_error'] = '- Errore durante il caricamento del file. Riprova.';
            header(REG_HEADER_LOCATION_REGISTRAZIONE);
            exit;
        }
    }

        // Quiz attivo: nessun INSERT finché il quiz non è completato (dati in sessione, come Todis)
        $quiz_active_early = !empty($contest_config['quiz_enabled']);
        if ($quiz_active_early) {
            $has_codice_snap = (($contest_mode === 'instantwin' || $contest_mode === 'estrazione') && field_enabled('codice_partecipazione') && !empty($codice_partecipazione));
            $_SESSION['gst_quiz_pending_registration'] = [
                'v' => 1,
                'created_at' => time(),
                'last_touch' => time(),
                'secret' => bin2hex(random_bytes(16)),
                'payload' => [
                    'nome' => $nome,
                    'cognome' => $cognome,
                    'email' => $email,
                    'prefisso' => $prefisso,
                    'cellulare' => $cellulare,
                    'privacy' => $privacy,
                    'marketing' => $marketing,
                    'profilazione' => $profilazione,
                    'provincia' => $provincia,
                    'citta' => $citta,
                    'cap' => $cap,
                    'indirizzo' => $indirizzo,
                    'fidelity' => $fidelity,
                    'custom_fields_values' => $custom_fields_values,
                    'custom_fields_config' => $contest_config['custom_fields'] ?? [],
                    'contest_mode' => $contest_mode,
                    'has_codice_partecipazione' => $has_codice_snap,
                    'codice_partecipazione' => $codice_partecipazione,
                    'codicePdv' => $codicePdv,
                    'insegna_coop' => $insegna_coop,
                    'receipt_filename' => $receipt_filename,
                    'receipt_original' => $receipt_original !== null ? (string) $receipt_original : '',
                    'importo_db_value' => $importo_db_value,
                    'data_scontrino' => $data_scontrino,
                    'ip_address' => $ip_address,
                    'user_agent' => $user_agent,
                ],
            ];
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            unset($_SESSION['form_data'], $_SESSION['registration_error']);
            $base = rtrim((string) ($contest_config['base_url'] ?? $app_config['base_url'] ?? ''), '/');
            if ($base === '') {
                $base = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/\\');
            }
            header('Location: quiz');
            exit;
        }

        // Creare sempre un nuovo utente per ogni partecipazione
        // Verifica se le colonne PROVINCIA, CITTA, INDIRIZZO esistono nella tabella
        $columns_check = $c_pdo->query("SHOW COLUMNS FROM utenti LIKE 'provincia'")->fetch();
        $has_provincia = $columns_check !== false;
        
        $columns_check = $c_pdo->query("SHOW COLUMNS FROM utenti LIKE 'cap'")->fetch();
        $has_cap = $columns_check !== false;

        $columns_check = $c_pdo->query("SHOW COLUMNS FROM utenti LIKE 'citta'")->fetch();
        $has_citta = $columns_check !== false;
        
        $columns_check = $c_pdo->query("SHOW COLUMNS FROM utenti LIKE 'indirizzo'")->fetch();
        $has_indirizzo = $columns_check !== false;

        // Verifica se la colonna fidelity esiste (colonne standard)
        $columns_check = $c_pdo->query(REG_SQL_SHOW_FIDELITY_COLUMN)->fetch();
        $has_fidelity = $columns_check !== false;
        
        // Costruisci la query INSERT dinamicamente in base alle colonne esistenti
        $columns = ['nome', 'cognome', 'email', 'prefisso', 'cellulare', 'privacy', 'marketing', 'profilazione', 'attivo'];
        $placeholders = [':nome', ':cognome', REG_SQL_BIND_EMAIL, ':prefisso', ':cellulare', ':privacy', ':marketing', ':profilazione', ':attivo'];
        
        if ($has_provincia) {
            $columns[] = 'provincia';
            $placeholders[] = ':provincia';
        }
        if ($has_cap) {
            $columns[] = 'cap';
            $placeholders[] = ':cap';
        }
        if ($has_citta) {
            $columns[] = 'citta';
            $placeholders[] = ':citta';
        }
        if ($has_indirizzo) {
            $columns[] = 'indirizzo';
            $placeholders[] = ':indirizzo';
        }
        if ($has_fidelity) {
            $columns[] = 'fidelity';
            $placeholders[] = REG_SQL_BIND_FIDELITY;
        }
        
        $sql = 'INSERT INTO utenti (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $placeholders) . ')';
        $stmt_new_user = $c_pdo->prepare($sql);
        
        $stmt_new_user->bindValue(':nome', $nome, PDO::PARAM_STR);
        $stmt_new_user->bindValue(':cognome', $cognome, PDO::PARAM_STR);
        $stmt_new_user->bindValue(REG_SQL_BIND_EMAIL, $email, PDO::PARAM_STR);
        $stmt_new_user->bindValue(':prefisso', $prefisso, PDO::PARAM_STR);
        $stmt_new_user->bindValue(':cellulare', $cellulare, PDO::PARAM_STR);
        $stmt_new_user->bindValue(':privacy', $privacy, PDO::PARAM_INT);
        $stmt_new_user->bindValue(':marketing', $marketing, PDO::PARAM_INT);
        $stmt_new_user->bindValue(':profilazione', $profilazione, PDO::PARAM_INT);
        $stmt_new_user->bindValue(':attivo', 1, PDO::PARAM_INT);
        
        if ($has_provincia) {
            $stmt_new_user->bindValue(':provincia', $provincia ? $provincia : null, PDO::PARAM_STR);
        }
        if ($has_cap) {
            $stmt_new_user->bindValue(':cap', $cap !== '' ? $cap : null, PDO::PARAM_STR);
        }
        if ($has_citta) {
            $stmt_new_user->bindValue(':citta', $citta ? $citta : null, PDO::PARAM_STR);
        }
        if ($has_indirizzo) {
            $stmt_new_user->bindValue(':indirizzo', $indirizzo ? $indirizzo : null, PDO::PARAM_STR);
        }
        if ($has_fidelity) {
            $stmt_new_user->bindValue(REG_SQL_BIND_FIDELITY, $fidelity ? $fidelity : null, PDO::PARAM_STR);
        }
        
        if (!$stmt_new_user->execute()) {
            $_SESSION['registration_error'] = '- Errore durante la creazione dell\'utente. Riprova.';
            header(REG_HEADER_LOCATION_REGISTRAZIONE);
            exit;
        }
        
        $user_id = $c_pdo->lastInsertId();

    // Generare un token univoco per questa partecipazione
    $token = bin2hex(random_bytes(32));

    $qr_code_registration = null;
    $has_qr_code_col = false;
    if (qr_access_is_active()) {
        $qr_code_registration = qr_access_get_code();
        if ($qr_code_registration === null) {
            $_SESSION['registration_error'] = '- Per partecipare devi scansionare il QR code presente sullo scontrino.';
            header(REG_HEADER_LOCATION_VERIFICA);
            exit;
        }
        $qrPayload = is_array($_SESSION['qr_access']['payload'] ?? null) ? $_SESSION['qr_access']['payload'] : [];
        if (!qr_access_is_usage_available($c_pdo, $qr_code_registration, $qrPayload)) {
            $_SESSION['registration_error'] = qr_config_get_error('qr_used')['text'];
            header(REG_HEADER_LOCATION_VERIFICA);
            exit;
        }
        $qrColCheck = $c_pdo->query("SHOW COLUMNS FROM partecipazioni LIKE 'qr_code'")->fetch(PDO::FETCH_ASSOC);
        $has_qr_code_col = (bool) $qrColCheck;
    }

    // Creare la partecipazione
    // Includere codice_partecipazione se presente (colonna denormalizzata)
    // In modalità estrazione o instantwin, se il campo è attivato e il codice fornito
    $has_codice_partecipazione = (($contest_mode === 'instantwin' || $contest_mode === 'estrazione') && field_enabled('codice_partecipazione') && !empty($codice_partecipazione));
    $codice_partecipazione_col = $has_codice_partecipazione ? ', codice_partecipazione' : '';
    $codice_partecipazione_val = $has_codice_partecipazione ? ', :codice_partecipazione' : '';
    $qr_code_col = ($has_qr_code_col && $qr_code_registration !== null) ? ', qr_code' : '';
    $qr_code_val = ($has_qr_code_col && $qr_code_registration !== null) ? ', :qr_code' : '';
    
    $stmt_partecipation = $c_pdo->prepare('
        INSERT INTO partecipazioni (
            id_cliente, token, data_partecipazione, importo_scontrino, data_scontrino,
            file_scontrino, file_originale, id_pdv_concorso, insegna, ip_address, user_agent,
            confermato, annullato, has_won' . $codice_partecipazione_col . $qr_code_col . '
    ) VALUES (
            :id_cliente, :token, :data_partecipazione, :importo_scontrino, :data_scontrino,
            :file_scontrino, ' . REG_SQL_BIND_FILE_ORIGINALE . ', :id_pdv_concorso, :insegna, :ip_address, :user_agent,
            :confermato, :annullato, :has_won' . $codice_partecipazione_val . $qr_code_val . '
        )
    ');

    $stmt_partecipation->bindValue(':id_cliente', $user_id, PDO::PARAM_INT);
    $stmt_partecipation->bindValue(':token', $token, PDO::PARAM_STR);
    $currentDateTime = new DateTime('now', new DateTimeZone('Europe/Rome'));
    $stmt_partecipation->bindValue(':data_partecipazione', $currentDateTime->format('Y-m-d H:i:s'), PDO::PARAM_STR);
    // Se importo_db_value è null (campo disabilitato), usare '0.00' come valore di default
    $stmt_partecipation->bindValue(':importo_scontrino', $importo_db_value !== null ? $importo_db_value : '0.00', PDO::PARAM_STR);
    // Se data_scontrino è null (campo disabilitato), usare la data corrente come valore di default
    $stmt_partecipation->bindValue(':data_scontrino', $data_scontrino ? $data_scontrino : $currentDateTime->format('Y-m-d'), PDO::PARAM_STR);
    $stmt_partecipation->bindValue(':file_scontrino', $receipt_filename, PDO::PARAM_STR);
    $stmt_partecipation->bindValue(REG_SQL_BIND_FILE_ORIGINALE, $receipt_original, PDO::PARAM_STR);
    $stmt_partecipation->bindValue(':id_pdv_concorso', $codicePdv, PDO::PARAM_STR);
    $stmt_partecipation->bindValue(':insegna', $insegna_coop, PDO::PARAM_STR);
    $stmt_partecipation->bindValue(':ip_address', $ip_address, PDO::PARAM_STR);
    $stmt_partecipation->bindValue(':user_agent', $user_agent, PDO::PARAM_STR);
    $stmt_partecipation->bindValue(':confermato', 0, PDO::PARAM_INT);
    $stmt_partecipation->bindValue(':annullato', 0, PDO::PARAM_INT);
    $stmt_partecipation->bindValue(':has_won', 0, PDO::PARAM_INT);
    if ($has_codice_partecipazione) {
        $stmt_partecipation->bindValue(':codice_partecipazione', $codice_partecipazione, PDO::PARAM_STR);
    }
    if ($has_qr_code_col && $qr_code_registration !== null) {
        $stmt_partecipation->bindValue(':qr_code', $qr_code_registration, PDO::PARAM_STR);
    }

    $codice_partecipazione_mode = codice_partecipazione_resolve_mode(is_array($contest_config) ? $contest_config : []);
    $codice_requires_pool = codice_partecipazione_mode_requires_pool($codice_partecipazione_mode);

    // Transaction + lock per evitare doppio utilizzo del codice in concorrenza
    $codice_already_used = false;
    $qr_code_already_used = false;
    if (($has_codice_partecipazione && !empty($codice_partecipazione)) || ($has_qr_code_col && $qr_code_registration !== null)) {
        $c_pdo->beginTransaction();
    }
    if ($has_codice_partecipazione && !empty($codice_partecipazione)) {
        try {
            if ($codice_requires_pool) {
                $stmt_lock = $c_pdo->prepare('SELECT id, used FROM codici_partecipazione WHERE codice = ' . REG_SQL_BIND_CODICE . ' FOR UPDATE');
                $stmt_lock->bindValue(REG_SQL_BIND_CODICE, $codice_partecipazione, PDO::PARAM_STR);
                $stmt_lock->execute();
                $lock_row = $stmt_lock->fetch(PDO::FETCH_ASSOC);
                if (!$lock_row || (int) ($lock_row['used'] ?? 0) === 1) {
                    $c_pdo->rollBack();
                    $labelCodiceLock = trim((string) ($contest_config['instantwin_codice_label'] ?? ''));
                    if ($labelCodiceLock === '') {
                        $labelCodiceLock = REG_LABEL_CODICE_PARTECIPAZIONE;
                    }
                    $errors[] = REG_ERROR_FIELD_PREFIX . $labelCodiceLock . REG_ERROR_GIA_UTILIZZATO_SUFFIX;
                    $field_errors['codice_partecipazione'] = REG_ERROR_FIELD_PREFIX . $labelCodiceLock . REG_ERROR_GIA_UTILIZZATO_SUFFIX;
                    $codice_already_used = true;
                }
            } else {
                $stmt_lock = $c_pdo->prepare('
                    SELECT id
                    FROM partecipazioni
                    WHERE codice_partecipazione = ' . REG_SQL_BIND_CODICE . '
                      AND COALESCE(annullato, 0) = 0
                    LIMIT 1
                    FOR UPDATE
                ');
                $stmt_lock->bindValue(REG_SQL_BIND_CODICE, $codice_partecipazione, PDO::PARAM_STR);
                $stmt_lock->execute();
                if ($stmt_lock->fetch(PDO::FETCH_ASSOC)) {
                    $c_pdo->rollBack();
                    $labelCodiceLock = trim((string) ($contest_config['instantwin_codice_label'] ?? ''));
                    if ($labelCodiceLock === '') {
                        $labelCodiceLock = REG_LABEL_CODICE_PARTECIPAZIONE;
                    }
                    $errors[] = REG_ERROR_FIELD_PREFIX . $labelCodiceLock . REG_ERROR_GIA_UTILIZZATO_SUFFIX;
                    $field_errors['codice_partecipazione'] = REG_ERROR_FIELD_PREFIX . $labelCodiceLock . REG_ERROR_GIA_UTILIZZATO_SUFFIX;
                    $codice_already_used = true;
                }
            }
        } catch (Throwable $e) {
            $c_pdo->rollBack();
            error_log("Errore lock codici_partecipazione: " . $e->getMessage());
            $labelCodiceLock = trim((string) ($contest_config['instantwin_codice_label'] ?? ''));
            if ($labelCodiceLock === '') {
                $labelCodiceLock = REG_LABEL_CODICE_PARTECIPAZIONE;
            }
            $errors[] = '- Errore durante la validazione del codice. Riprova.';
            $field_errors['codice_partecipazione'] = '- Errore durante la validazione del codice. Riprova.';
            $codice_already_used = true;
        }
    }

    if (!$codice_already_used && $has_qr_code_col && $qr_code_registration !== null) {
        try {
            $stmt_qr_lock = $c_pdo->prepare('SELECT id FROM partecipazioni WHERE qr_code = :code FOR UPDATE');
            $stmt_qr_lock->bindValue(':code', $qr_code_registration, PDO::PARAM_STR);
            $stmt_qr_lock->execute();
            $qrPayloadLock = is_array($_SESSION['qr_access']['payload'] ?? null) ? $_SESSION['qr_access']['payload'] : [];
            if (!qr_access_is_usage_available($c_pdo, $qr_code_registration, $qrPayloadLock)) {
                $c_pdo->rollBack();
                $_SESSION['registration_error'] = qr_config_get_error('qr_used')['text'];
                header(REG_HEADER_LOCATION_VERIFICA);
                exit;
            }
        } catch (Throwable $e) {
            if ($c_pdo->inTransaction()) {
                $c_pdo->rollBack();
            }
            error_log('Errore lock qr_code: ' . $e->getMessage());
            $_SESSION['registration_error'] = 'Errore durante la validazione del codice QR. Riprova.';
            header(REG_HEADER_LOCATION_VERIFICA);
            exit;
        }
    }

    $participation_ok = false;
    if (!$codice_already_used) {
        $participation_ok = $stmt_partecipation->execute();
        if (!$participation_ok && $c_pdo->inTransaction()) {
            $c_pdo->rollBack();
        }
    } elseif ($c_pdo->inTransaction()) {
        $c_pdo->rollBack();
    }

    if ($participation_ok) {
        $participation_id = $c_pdo->lastInsertId();

        // Se è stato utilizzato un codice_partecipazione, aggiornare codici_partecipazione
        // In entrambe le modalità (instantwin e estrazione): segnare il codice come utilizzato (used = 1)
        // In transazione con SELECT FOR UPDATE per evitare doppio utilizzo in concorrenza
        if ($has_codice_partecipazione && !empty($codice_partecipazione) && $codice_requires_pool) {
            try {
                $stmt_update_codice = $c_pdo->prepare('
                    UPDATE codici_partecipazione 
                    SET used = 1,
                        id_partecipazione = :id_partecipazione,
                        id_cliente = :id_cliente,
                        data_utilizzo = NOW(),
                        isActive = 0
                    WHERE codice = ' . REG_SQL_BIND_CODICE . ' 
                      AND used = 0
                ');
                $stmt_update_codice->bindValue(':id_partecipazione', $participation_id, PDO::PARAM_INT);
                $stmt_update_codice->bindValue(':id_cliente', $user_id, PDO::PARAM_INT);
                $stmt_update_codice->bindValue(REG_SQL_BIND_CODICE, $codice_partecipazione, PDO::PARAM_STR);
                $stmt_update_codice->execute();
                $c_pdo->commit();
            } catch (Throwable $e) {
                $c_pdo->rollBack();
                error_log("Errore aggiornamento codici_partecipazione: " . $e->getMessage());
            }
        } elseif ($c_pdo->inTransaction()) {
            $c_pdo->commit();
        }

        if ($codiceFieldActive && $c_pdo instanceof PDO) {
            if (!function_exists('contest_security_codice_reset_on_success')) {
                require_once __DIR__ . REG_REQUIRE_CONTEST_SECURITY;
            }
            $contestConfigArr = is_array($contest_config) ? $contest_config : [];
            if (contest_security_codice_protection_active($c_pdo, $contestConfigArr)) {
                contest_security_codice_reset_on_success($c_pdo, $ip_address, $email);
            }
        }

        if (qr_access_is_active()) {
            qr_access_clear();
        }

        // Salvare i campi personalizzati nelle colonne individuali (Opzione 2)
        if (!empty($custom_fields_values) && !empty($custom_fields_config)) {
            // Costruire la query UPDATE con le colonne dinamiche
            $update_fields = [];
            $update_values = [':id' => $participation_id];
            
            foreach ($custom_fields_config as $customField) {
                if (!is_array($customField) || !isset($customField['id'])) {
                    continue;
                }
                
                $customId = preg_replace(REG_REGEX_CUSTOM_FIELD_ID, '', (string) ($customField['id'] ?? ''));
                if ($customId === '') {
                    continue;
                }
                
                $column_name = preg_replace(REG_REGEX_CUSTOM_COLUMN_NAME, '_', strtolower($customId));
                $customValue = $custom_fields_values[$customId] ?? null;
                
                // Verificare se la colonna esiste prima di utilizzarla
                try {
                    $col_check = $c_pdo->query("SHOW COLUMNS FROM partecipazioni LIKE '{$column_name}'")->fetch();
                    if ($col_check !== false) {
                        $param_name = ':' . preg_replace(REG_REGEX_CUSTOM_COLUMN_NAME, '_', strtolower($customId));
                        $update_fields[] = "`{$column_name}` = {$param_name}";
                        $update_values[$param_name] = $customValue !== null && $customValue !== '' ? $customValue : null;
                    }
                } catch (Throwable $e) {
                    // Colonna non esistente, ignorare
                    error_log("Colonne {$column_name} n'existe pas: " . $e->getMessage());
                }
            }
            
            // Eseguire l'UPDATE se ci sono campi da aggiornare
            if (!empty($update_fields)) {
                try {
                    $update_sql = 'UPDATE partecipazioni SET ' . implode(', ', $update_fields) . ' WHERE id = :id';
                    $stmt_update = $c_pdo->prepare($update_sql);
                    
                    foreach ($update_values as $key => $value) {
                        if ($key === ':id') {
                            $stmt_update->bindValue($key, $value, PDO::PARAM_INT);
                        } else {
                            // Per i campi DATE, assicurarsi che il valore sia nel formato YYYY-MM-DD o NULL
                            $fieldId = str_replace(':', '', $key);
                            $fieldId = preg_replace(REG_REGEX_CUSTOM_COLUMN_NAME, '', strtolower($fieldId));
                            
                            // Trovare il tipo del campo nella configurazione
                            $fieldType = null;
                            foreach ($custom_fields_config as $cf) {
                                if (isset($cf['id']) && preg_replace(REG_REGEX_CUSTOM_FIELD_ID, '', strtolower($cf['id'])) === $fieldId) {
                                    $typeMapping = [
                                        'text' => 'varchar',
                                        'textarea' => 'text',
                                        'number' => 'int',
                                        'email' => 'varchar',
                                        'date' => 'date',
                                        'int' => 'int',
                                        'varchar' => 'varchar',
                                        'text' => 'text',
                                        'boolean' => 'boolean',
                                        'bool' => 'boolean'
                                    ];
                                    $rawType = strtolower(trim((string) ($cf['type'] ?? 'varchar')));
                                    $fieldType = $typeMapping[$rawType] ?? 'varchar';
                                    $fieldType = in_array($fieldType, ['int', 'varchar', 'text', 'date', 'boolean'], true) ? $fieldType : 'varchar';
                                    break;
                                }
                            }
                            
                            if ($fieldType === 'date') {
                                // Per le date, assicurarsi che il valore sia nel formato YYYY-MM-DD o NULL
                                if ($value !== null && $value !== '') {
                                    // Verificare il formato della date
                                    $dateValue = trim($value);
                                    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateValue)) {
                                        $stmt_update->bindValue($key, $dateValue, PDO::PARAM_STR);
                                    } else {
                                        // Formato invalido, mettere NULL
                                        $stmt_update->bindValue($key, null, PDO::PARAM_NULL);
                                    }
                                } else {
                                    $stmt_update->bindValue($key, null, PDO::PARAM_NULL);
                                }
                            } elseif ($fieldType === 'boolean') {
                                // Per i boolean, convertire in 0 o 1
                                $boolValue = ($value === '1' || $value === 'on' || $value === true || $value === 'true') ? 1 : 0;
                                $stmt_update->bindValue($key, $boolValue, PDO::PARAM_INT);
                            } elseif ($fieldType === 'int') {
                                // Per i INT, utilizzare PARAM_INT
                                $intValue = is_numeric($value) ? (int)$value : null;
                                $stmt_update->bindValue($key, $intValue, PDO::PARAM_INT);
                            } else {
                                $stmt_update->bindValue($key, $value, PDO::PARAM_STR);
                            }
                        }
                    }
                    
                    $stmt_update->execute();
                } catch (Throwable $e) {
                    error_log("Erreur sauvegarde custom fields: " . $e->getMessage());
                }
            }
        }

        // Includere il sistema email
        include_once __DIR__ . '/../../public/assets/mail/email_system.php';
        require_once __DIR__ . '/registration_email_helpers.php';

        if (!function_exists('get_email_sending_config')) {
            function get_email_sending_config(): array {
                global $content_config, $content_defaults;
                $defaults = $content_defaults['email_sending'] ?? [];
                $current = $content_config['email_sending'] ?? [];
                if (!is_array($defaults)) {
                    $defaults = [];
                }
                if (!is_array($current)) {
                    $current = [];
                }
                return array_replace_recursive($defaults, $current);
            }
        }

        // Verifica del modo di concorso e gestione instantwin / vincita diretta
        global $contest_mode;
        $instantwin_result = null;
        $email_sent = false;
        $email_error = null;
        $auto_confirmed = false;
        $sms_type = null;
        $sms_codice = '';
        $codice_mode = codice_partecipazione_resolve_mode(is_array($contest_config) ? $contest_config : []);
        $quiz_active = !empty($contest_config['quiz_enabled']);

        if ($contest_mode === 'instantwin' && !$quiz_active) {
            $iwPerPdv = !empty($contest_config['instantwin_per_pdv']);
            $iwPerCedi = !empty($contest_config['instantwin_per_cedi']);
            if ($iwPerPdv) {
                require_once __DIR__ . '/instantwin_pdv/instantwin_pdv.php';
            } elseif ($iwPerCedi) {
                require_once __DIR__ . '/instantwin_cedi/instantwin_cedi.php';
            } else {
                require_once __DIR__ . '/instantwin.php';
            }
            $auto_prize_flow = $require_receipt && $auto_prize_with_receipt;

            // Modalità Vincita diretta: il codice ha già un premio pre-assignato, assegnarlo subito
            if ($codice_mode === 'direct_win' && $has_codice_partecipazione && !empty($codice_partecipazione)) {
                try {
                    $stmt_direct = $c_pdo->prepare('
                        SELECT premio.id AS codice_premio_id, premio.prize_id, premio.used AS premio_used,
                               p.valore AS prize_value
                        FROM codici_partecipazione cp
                        INNER JOIN codici_premio premio ON premio.id_codice_partecipazione = cp.id
                        INNER JOIN premi p ON p.id = premio.prize_id
                        WHERE cp.codice = ' . REG_SQL_BIND_CODICE . ' AND cp.used = 1 AND premio.used = 0
                        LIMIT 1
                    ');
                    $stmt_direct->bindValue(REG_SQL_BIND_CODICE, $codice_partecipazione, PDO::PARAM_STR);
                    $stmt_direct->execute();
                    $direct_row = $stmt_direct->fetch(PDO::FETCH_ASSOC);
                    if ($direct_row) {
                        $codice_premio_id = (int) $direct_row['codice_premio_id'];
                        $prize_id = (int) $direct_row['prize_id'];
                        $prize_value = (float) $direct_row['prize_value'];
                        $c_pdo->beginTransaction();
                        $c_pdo->prepare('UPDATE codici_premio SET used = 1, id_partecipazione = :pid, data_assegnazione = NOW(), isActive = 0 WHERE id = :cid')
                            ->execute([':pid' => $participation_id, ':cid' => $codice_premio_id]);
                        $c_pdo->prepare('UPDATE partecipazioni SET has_won = 1, prize_id = :pid, prize_amount = :pval, id_codice_premio = :cpid, win_date = NOW() WHERE id = :partid')
                            ->execute([':pid' => $prize_id, ':pval' => $prize_value, ':cpid' => $codice_premio_id, ':partid' => $participation_id]);
                        $c_pdo->prepare('UPDATE premi SET quantita_assegnati = quantita_assegnati + 1 WHERE id = :pid')
                            ->execute([':pid' => $prize_id]);
                        $c_pdo->commit();
                        $instantwin_result = ['has_won' => true, 'prize_id' => $prize_id, 'prize_value' => $prize_value, 'prize_type' => 'Vincita diretta'];
                        // Recuperare codice e pdf_path da codici_premio per email premio (e allegato PDF)
                        $codice_premio = null;
                        $pdf_path = null;
                        $stmt_cp = $c_pdo->prepare('SELECT codice, pdf_path FROM codici_premio WHERE id = ? LIMIT 1');
                        $stmt_cp->execute([$codice_premio_id]);
                        $cp_row = $stmt_cp->fetch(PDO::FETCH_ASSOC);
                        if ($cp_row) {
                            $codice_premio = !empty($cp_row['codice']) ? trim((string) $cp_row['codice']) : null;
                            $pdf_path = !empty($cp_row['pdf_path']) ? trim((string) $cp_row['pdf_path']) : null;
                        }
                        require_once __DIR__ . '/prize_image_generator.php';
                        prize_image_try_generate_after_assign($c_pdo, $codice_premio_id, $prize_id, $codice_premio);
                        $directWinEmail = registration_apply_instantwin_win_email(
                            $email,
                            $nome,
                            $prize_value,
                            $codice_premio,
                            $pdf_path,
                            $auto_prize_flow,
                            (string) ($codicePdv ?? ''),
                            (string) ($insegna_coop ?? ''),
                            [
                                'prize_id' => $prize_id,
                                'id_codice_premio' => $codice_premio_id,
                            ]
                        );
                        registration_record_email_attempt(
                            $directWinEmail['result'],
                            $email_sent,
                            $email_error,
                            'Errore invio email vincita (vincita diretta).'
                        );
                        $sms_type = registration_resolve_instantwin_win_sms_type($auto_prize_flow);
                        $sms_codice = (string) ($codice_premio ?? '');
                        if ($email_sent && ($directWinEmail['type'] ?? null) === 'instantwin_prize') {
                            $auto_confirmed = true;
                        } elseif (($directWinEmail['result'] ?? null) === null) {
                            error_log('registration: vincita diretta partecipazione #' . $participation_id . ' ma nessun template email vincita abilitato');
                        }
                    } else {
                        $instantwin_result = null;
                        $lossResult = registration_send_instantwin_loss_email($email, $nome);
                        $sms_type = 'instantwin_loss';
                        if ($lossResult === true) {
                            $email_sent = true;
                        } elseif ($lossResult === false) {
                            $email_error = 'Errore invio email di non vincita Instant Win';
                        }
                    }
                } catch (Throwable $e) {
                    if ($c_pdo->inTransaction()) {
                        $c_pdo->rollBack();
                    }
                    error_log("Errore vincita diretta: " . $e->getMessage());
                    $instantwin_result = null;
                    $lossResult = registration_send_instantwin_loss_email($email, $nome);
                    $sms_type = 'instantwin_loss';
                    if ($lossResult === true) {
                        $email_sent = true;
                    } elseif ($lossResult === false) {
                        $email_error = 'Errore invio email Instant Win';
                    }
                }
                if (session_status() === PHP_SESSION_NONE) {
                    session_start();
                }
                $_SESSION['instantwin_result'] = $instantwin_result;
            } else {
                // Modalità Instant Win: estrazione tramite slot/quota
                try {
                    if ($iwPerPdv) {
                        $instantwin_result = checkInstantWinPdv($token);
                    } elseif ($iwPerCedi) {
                        $instantwin_result = checkInstantWinCedi($token);
                    } else {
                        $instantwin_result = checkInstantWin($token);
                    }
                } catch (Throwable $e) {
                    error_log("Errore checkInstantWin per token " . substr($token, 0, 8) . ": " . $e->getMessage());
                    $instantwin_result = null;
                }

                $partWinState = registration_load_participation_win_state($c_pdo, (int) $participation_id);
                $hasWonInDb = is_array($partWinState) && !empty($partWinState['has_won']);

                if (registration_instantwin_is_win($instantwin_result) && !$hasWonInDb) {
                    try {
                        $stmt_update_win = $c_pdo->prepare('
                            UPDATE partecipazioni 
                            SET has_won = 1, 
                                prize_id = :prize_id, 
                                prize_amount = :prize_value,
                                win_date = NOW()
                            WHERE id = :participation_id
                        ');
                        $stmt_update_win->execute([
                            ':prize_id' => $instantwin_result['prize_id'],
                            ':prize_value' => $instantwin_result['prize_value'],
                            ':participation_id' => $participation_id,
                        ]);
                        $partWinState = registration_load_participation_win_state($c_pdo, (int) $participation_id);
                        $hasWonInDb = is_array($partWinState) && !empty($partWinState['has_won']);
                    } catch (Throwable $e) {
                        error_log('Errore update vincita partecipazione #' . $participation_id . ': ' . $e->getMessage());
                    }
                }

                if ($hasWonInDb) {
                    if (!registration_instantwin_is_win($instantwin_result) && is_array($partWinState)) {
                        $instantwin_result = [
                            'has_won' => true,
                            'prize_id' => (int) ($partWinState['prize_id'] ?? 0),
                            'prize_value' => (float) ($partWinState['prize_amount'] ?? 0),
                            'prize_type' => 'Carta Prepagata Fisica',
                        ];
                    }

                    $prize_value = $instantwin_result['prize_value'] ?? ($partWinState['prize_amount'] ?? 0);
                    $codice_premio = $instantwin_result['codice_premio'] ?? null;
                    $pdf_path = $instantwin_result['pdf_path'] ?? null;
                    $winPrizeId = (int) ($instantwin_result['prize_id'] ?? ($partWinState['prize_id'] ?? 0));
                    $winCodicePremioId = (int) ($instantwin_result['codice_premio_id'] ?? ($partWinState['id_codice_premio'] ?? 0));
                    try {
                        $winEmail = registration_apply_instantwin_win_email(
                            $email,
                            $nome,
                            (float) $prize_value,
                            $codice_premio,
                            $pdf_path,
                            $auto_prize_flow,
                            (string) ($codicePdv ?? ''),
                            (string) ($insegna_coop ?? ''),
                            [
                                'prize_id' => $winPrizeId,
                                'id_codice_premio' => $winCodicePremioId,
                            ]
                        );
                        registration_record_email_attempt(
                            $winEmail['result'],
                            $email_sent,
                            $email_error,
                            'Errore invio email vincita (verificare SMTP o checkbox email vincita nel concorso).'
                        );
                        $sms_type = registration_resolve_instantwin_win_sms_type($auto_prize_flow);
                        $sms_codice = (string) ($codice_premio ?? '');
                        if ($email_sent && ($winEmail['type'] ?? null) === 'instantwin_prize') {
                            $hasCodicePremio = (trim((string) ($codice_premio ?? '')) !== '')
                                || (int) ($winCodicePremioId ?? 0) > 0
                                || (int) ($partWinState['id_codice_premio'] ?? 0) > 0;
                            if ($hasCodicePremio) {
                                $auto_confirmed = true;
                            }
                        } elseif (($winEmail['result'] ?? null) === null) {
                            error_log('registration: vincita partecipazione #' . $participation_id . ' ma nessun template email vincita abilitato');
                        }
                    } catch (Throwable $e) {
                        error_log('Errore invio email vincita partecipazione #' . $participation_id . ': ' . $e->getMessage());
                        $email_error = 'Errore invio email vincita Instant Win';
                    }
                } elseif (is_array($instantwin_result)) {
                    $lossResult = registration_send_instantwin_loss_email($email, $nome);
                    $sms_type = 'instantwin_loss';
                    registration_record_email_attempt(
                        $lossResult,
                        $email_sent,
                        $email_error,
                        'Errore invio email di non vincita Instant Win'
                    );
                }

                if (session_status() === PHP_SESSION_NONE) {
                    session_start();
                }
                $_SESSION['instantwin_result'] = $instantwin_result;
            }
        } else {
            // Estrazione senza quiz, oppure Instant Win con quiz: email dopo completamento quiz
            if (!$quiz_active) {
                $email_sent = sendPartecipazioneEmail($email, $nome);
                $sms_type = 'partecipazione';
                if (!$email_sent) {
                    $email_error = "Errore invio email di conferma partecipazione";
                }
            }
        }

        // `email_inviato` = 1 solo se l'email è stata recapitata (SMTP OK).
        $email_inviato_flag = $email_sent ? 1 : 0;

        if ($auto_confirmed) {
            $stmt_email = $c_pdo->prepare('
                UPDATE partecipazioni 
                SET confermato = 1,
                    annullato = 0,
                    email_inviato = :sent,
                    email_error = :error
                WHERE id = :participation_id
            ');
        } else {
            $stmt_email = $c_pdo->prepare('
                UPDATE partecipazioni 
                SET email_inviato = :sent, email_error = :error
                WHERE id = :participation_id
            ');
        }
        $stmt_email->bindValue(':sent', $email_inviato_flag, PDO::PARAM_INT);
        $stmt_email->bindValue(':error', $email_error, PDO::PARAM_STR);
        $stmt_email->bindValue(':participation_id', $participation_id, PDO::PARAM_INT);
        $stmt_email->execute();

        // SMS indipendente dall'email (fallimento SMS non blocca il flusso)
        if (is_string($sms_type) && $sms_type !== '' && function_exists('gst_sms_try_send_for_participation')) {
            try {
                gst_sms_try_send_for_participation($c_pdo, (int) $participation_id, $sms_type, [
                    'nome' => (string) $nome,
                    'prefisso' => (string) $prefisso,
                    'cellulare' => (string) $cellulare,
                    'codice' => (string) $sms_codice,
                ]);
            } catch (Throwable $smsEx) {
                error_log('registration SMS: ' . $smsEx->getMessage());
            }
        }
        
        // Pulizia dei dati di sessione del form per evitare ripopolamento
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        unset($_SESSION['form_data'], $_SESSION['registration_error']);
        
        // Reindirizzare alla pagina di ringraziamento (URL assoluto per evitare 404)
        $base = rtrim((string)($contest_config['base_url'] ?? $app_config['base_url'] ?? ''), '/');
        if ($base === '') {
            $base = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/\\');
        }
        if ($quiz_active) {
            $_SESSION[SUMMERCOOP_QUIZFLOW_TOKEN_SESSION_KEY] = $token;
            $thankyou_url = 'quiz';
        } else {
            $_SESSION[SUMMERCOOP_THANKYOU_TOKEN_SESSION_KEY] = $token;
            $thankyou_url = $base . '/public/grazie';
        }
        header('Location: ' . $thankyou_url);
        exit;
    } else {
        $_SESSION['registration_error'] = '- Errore durante la registrazione. Riprova.';
        header(REG_HEADER_LOCATION_REGISTRAZIONE);
        exit;
    }
}
?>
