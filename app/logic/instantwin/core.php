<?php
define('INSTANTWIN_NO_WIN', ['has_won'=>false,'prize_id'=>0,'prize_value'=>0]);

if (!defined('IW_TIMEZONE_EUROPE_ROME')) {
    define('IW_TIMEZONE_EUROPE_ROME', 'Europe/Rome');
}
if (!defined('IW_DATE_INTERVAL_ONE_DAY')) {
    define('IW_DATE_INTERVAL_ONE_DAY', '+1 day');
}
if (!defined('IW_SQL_BIND_TOKEN')) {
    define('IW_SQL_BIND_TOKEN', ':token');
}
if (!defined('IW_SQL_QUOTA_DAILY_SELECT')) {
    define('IW_SQL_QUOTA_DAILY_SELECT', 'SELECT target, assigned FROM quota_giornaliera WHERE data = :d');
}
if (!defined('IW_SQL_CONCAT_SPACE')) {
    define('IW_SQL_CONCAT_SPACE', ' ');
}
if (!defined('IW_SQL_ORDER_SLOT_TIMESTAMP_ASC')) {
    define('IW_SQL_ORDER_SLOT_TIMESTAMP_ASC', ' ORDER BY slot_timestamp ASC ');
}

function getContestPrizes() {
    global $contest_config;
    if (isset($contest_config['premi']) && is_array($contest_config['premi']) && !empty($contest_config['premi'])) {
        return $contest_config['premi'];
    }
    return [];
}

function initializePrizes() {
    global $c_pdo;
    /** @var PDO $c_pdo */
    try {
        $stmt_check = $c_pdo->prepare('SELECT COUNT(*) as count FROM premi');
        $stmt_check->execute();
        $existing_count = $stmt_check->fetch()['count'];
        
        if ($existing_count > 0) {
            return;
        }
        $contest_prizes = getContestPrizes();
        if (empty($contest_prizes)) {
            return;
        }
        $stmt_insert = $c_pdo->prepare('
            INSERT INTO premi (valore, tipo_premio, quantita_totale, quantita_assegnati, data_creazione)
            VALUES (:valore, :tipo, :quantita, 0, NOW())
        ');
        
        foreach ($contest_prizes as $prize_config) {
            $stmt_insert->bindValue(':valore', $prize_config['value'], PDO::PARAM_STR);
            $stmt_insert->bindValue(':tipo', $prize_config['type'], PDO::PARAM_STR);
            $stmt_insert->bindValue(':quantita', $prize_config['quantity'], PDO::PARAM_INT);
            $stmt_insert->execute();
        }
        
        error_log("Premi inizializzati: " . array_sum(array_column($contest_prizes, 'quantity')) . " premi creati");
        
    } catch (Exception $e) {
        error_log("Errore inizializzazione premi: " . $e->getMessage());
    }
}

/**
 * Funzione helper per leggere un intero dalle variabili d'ambiente
 */
if (!function_exists('env_int')) {
    function env_int(string $key, int $default = 0): int {
        $value = getenv($key);
        if ($value === false) {
            return $default;
        }
        $parsed = filter_var($value, FILTER_VALIDATE_INT);
        return $parsed === false ? $default : $parsed;
    }
}

/**
 * Funzione helper per leggere un float dalle variabili d'ambiente
 */
if (!function_exists('env_float')) {
    function env_float(string $key, float $default = 0.0): float {
        $value = getenv($key);
        if ($value === false) {
            return $default;
        }
        $parsed = filter_var($value, FILTER_VALIDATE_FLOAT);
        return $parsed === false ? $default : $parsed;
    }
}

/**
 * Funzione helper per leggere una data dalle variabili d'ambiente
 */
if (!function_exists('env_date')) {
    function env_date(string $key, ?string $default = null): ?string {
        $value = getenv($key);
        // Fallback su $_ENV se getenv() fallisce
        if ($value === false && isset($_ENV[$key])) {
            $value = $_ENV[$key];
        }
        if ($value === false || trim($value) === '') {
            return $default;
        }
        // Validare il formato data YYYY-MM-DD
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', trim($value))) {
            return trim($value);
        }
        return $default;
    }
}

/**
 * Funzione helper per leggere un booleano dalle variabili d'ambiente
 */
if (!function_exists('env_bool')) {
    function env_bool(string $key, bool $default = false): bool {
        $value = getenv($key);
        if ($value === false) {
            return $default;
        }
        return filter_var($value, FILTER_VALIDATE_BOOLEAN) === true;
    }
}

/**
 * Funzione helper per leggere una lista di giorni dalle variabili d'ambiente
 * Formato: "1,2,3,4,5" → [1,2,3,4,5]
 */
if (!function_exists('env_days')) {
    function env_days(string $key, array $default = []): array {
        $value = getenv($key);
        // Fallback verso $_ENV se getenv() fallisce
        if ($value === false && isset($_ENV[$key])) {
            $value = $_ENV[$key];
        }
        if ($value === false || trim($value) === '') {
            return $default;
        }
        $days = array_map('intval', array_map('trim', explode(',', $value)));
        // Filtrare per mantenere solo i giorni validi (1-7)
        $days = array_filter($days, function($day) {
            return $day >= 1 && $day <= 7;
        });
        return array_values($days);
    }
}

/**
 * Funzione helper per leggere gli orari di un giorno dalle variabili d'ambiente
 * Formato: "9,19" → ['start' => 9, 'end' => 19]
 */
if (!function_exists('env_day_hours')) {
    function env_day_hours(string $key, ?array $default = null): ?array {
        $value = getenv($key);
        if ($value === false || trim($value) === '') {
            return $default;
        }
        $parts = array_map('intval', array_map('trim', explode(',', $value)));
        if (count($parts) !== 2 || $parts[0] < 0 || $parts[0] > 23 || $parts[1] < 1 || $parts[1] > 24) {
            return $default;
        }
        return ['start' => $parts[0], 'end' => $parts[1]];
    }
}

// ============================================
// CONFIGURAZIONE INSTANT WIN
// ============================================

// ============================================
// CONFIGURAZIONE DATE INSTANT WIN
// ============================================
// La data di inizio viene SEMPRE presa da content.json (non modificabile via .env)
// La data di fine può essere personalizzata se INSTANTWIN_ENABLE_CUSTOM_END_DATE=true
// Se INSTANTWIN_ENABLE_CUSTOM_END_DATE=false, viene utilizzata la data di fine del concorso da content.json
// Utile per distribuire tutti i premi prima della fine ufficiale del concorso
if (!defined('INSTANTWIN_ENABLE_CUSTOM_END_DATE')) {
    define('INSTANTWIN_ENABLE_CUSTOM_END_DATE', env_bool('INSTANTWIN_ENABLE_CUSTOM_END_DATE', false));
}
$INSTANTWIN_CUSTOM_DATES = null;
$instantwin_end = INSTANTWIN_ENABLE_CUSTOM_END_DATE ? env_date('INSTANTWIN_END_DATE') : null;

// Log per debug: verificare che INSTANTWIN_END_DATE sia letto correttamente
if (INSTANTWIN_ENABLE_CUSTOM_END_DATE) {
    if ($instantwin_end === null) {
        error_log("INSTANTWIN: ATTENZIONE! INSTANTWIN_ENABLE_CUSTOM_END_DATE=true ma INSTANTWIN_END_DATE non definito o non valido nel .env. Verificare che INSTANTWIN_END_DATE=2025-12-26 sia presente nel file .env");
    } else {
        error_log("INSTANTWIN: INSTANTWIN_END_DATE letto correttamente: $instantwin_end");
    }
}

// Funzionalità attivabili/disattivabili
if (!defined('INSTANTWIN_ENABLE_TIME_RESTRICTIONS')) {
    define('INSTANTWIN_ENABLE_TIME_RESTRICTIONS', env_bool('INSTANTWIN_ENABLE_TIME_RESTRICTIONS', false));
}
if (!defined('INSTANTWIN_ENABLE_DAY_CUSTOMIZATION')) {
    define('INSTANTWIN_ENABLE_DAY_CUSTOMIZATION', env_bool('INSTANTWIN_ENABLE_DAY_CUSTOMIZATION', false));
}
// Offset progressivo e logica ibrida sempre attivi (non modificabili via .env)
if (!defined('INSTANTWIN_ENABLE_ADVANCED_SLOTS')) {
    define('INSTANTWIN_ENABLE_ADVANCED_SLOTS', true); // Sempre attivo
}
// La logica ibrida per i quota è sempre attiva nel codice (non più controllata da questa costante)

// Quota giornaliera globale: unica formula (init manager + runtime + rattrapage)
// balanced = stesso target ogni giorno (±1 sur le reliquats) ; progressive = plus au début de la fenêtre
if (!defined('INSTANTWIN_QUOTA_DISTRIBUTION')) {
    $quotaDist = strtolower(trim((string) (getenv('INSTANTWIN_QUOTA_DISTRIBUTION') ?: 'progressive')));
    if (!in_array($quotaDist, ['balanced', 'progressive', 'equal'], true)) {
        $quotaDist = 'progressive';
    }
    if ($quotaDist === 'equal') {
        $quotaDist = 'balanced';
    }
    define('INSTANTWIN_QUOTA_DISTRIBUTION', $quotaDist);
}
if (!defined('INSTANTWIN_QUOTA_COEF_START')) {
    define('INSTANTWIN_QUOTA_COEF_START', env_float('INSTANTWIN_QUOTA_COEF_START', 1.5));
}
if (!defined('INSTANTWIN_QUOTA_COEF_END')) {
    define('INSTANTWIN_QUOTA_COEF_END', env_float('INSTANTWIN_QUOTA_COEF_END', 0.5));
}

// Orari di base (se restrizioni attivate ma senza personalizzazione giorno per giorno)
// Questi orari vengono utilizzati per tutti i giorni se INSTANTWIN_ENABLE_DAY_CUSTOMIZATION=false
// Se INSTANTWIN_ENABLE_DAY_CUSTOMIZATION=true, vengono utilizzati solo se INSTANTWIN_DAY_X_HOURS non è definito
// IMPORTANTE: I valori devono essere definiti nel .env, nessun valore di default nel codice
// Se non definiti nel .env, si utilizzano 0 e 24 (24/7) come fallback
if (!defined('INSTANTWIN_START_HOUR')) {
    $start_hour_env = getenv('INSTANTWIN_START_HOUR');
    $start_hour = ($start_hour_env !== false && $start_hour_env !== '') ? (int)$start_hour_env : 0;
    define('INSTANTWIN_START_HOUR', $start_hour); // 0 se non definito nel .env
}
if (!defined('INSTANTWIN_END_HOUR')) {
    $end_hour_env = getenv('INSTANTWIN_END_HOUR');
    $end_hour = ($end_hour_env !== false && $end_hour_env !== '') ? (int)$end_hour_env : 24;
    define('INSTANTWIN_END_HOUR', $end_hour); // 24 se non definito nel .env (fine esclusiva)
}

// Personalizzazione orari per ogni giorno (se INSTANTWIN_ENABLE_DAY_CUSTOMIZATION=true)
// Formato: INSTANTWIN_DAY_X_HOURS=ORA_INIZIO,ORA_FINE
// X = 1 (Lunedì), 2 (Martedì), 3 (Mercoledì), 4 (Giovedì), 5 (Venerdì), 6 (Sabato), 7 (Domenica)
// Esempio: INSTANTWIN_DAY_1_HOURS=9,19 (Lunedì dalle 9 alle 19)
//          INSTANTWIN_DAY_6_HOURS=9,13 (Sabato dalle 9 alle 13)
// Se non definito per un giorno specifico, vengono utilizzati INSTANTWIN_START_HOUR e INSTANTWIN_END_HOUR

// Configurazione avanzata degli slot (se INSTANTWIN_ENABLE_ADVANCED_SLOTS=true)
if (!defined('MAX_FIRST_SLOT_OFFSET_SECONDS')) {
    define('MAX_FIRST_SLOT_OFFSET_SECONDS', env_int('INSTANTWIN_FIRST_SLOT_OFFSET_MAX_SECONDS', 14 * 60 + 30));
}
if (!defined('SLOT_MARGIN_PERCENTAGE')) {
    define('SLOT_MARGIN_PERCENTAGE', env_float('INSTANTWIN_SLOT_MARGIN_PERCENTAGE', 0.20));
}
if (!defined('MIN_SLOT_MARGIN_SECONDS')) {
    define('MIN_SLOT_MARGIN_SECONDS', env_int('INSTANTWIN_MIN_SLOT_MARGIN_SECONDS', 180));
}
if (!defined('MAX_SLOT_MARGIN_SECONDS')) {
    define('MAX_SLOT_MARGIN_SECONDS', env_int('INSTANTWIN_MAX_SLOT_MARGIN_SECONDS', 600));
}
if (!defined('DEFAULT_SLOT_MARGIN_SINGLE')) {
    define('DEFAULT_SLOT_MARGIN_SINGLE', env_int('INSTANTWIN_DEFAULT_SLOT_MARGIN_SINGLE', 300));
}

// Configurazione dei quota (logica ibrida sempre attiva)
// MAX_DAILY_TARGET è opzionale: se definito e > 0, applica limite quotidiana stretta
if (!defined('MAX_DAILY_TARGET')) {
    define('MAX_DAILY_TARGET', env_int('INSTANTWIN_MAX_DAILY_TARGET', 0)); // 0 = nessun limite
}

// Configurazione della strategia di ripartizione degli slot
// Riduzione della fine normale per meglio ripartire (di default 1h = 3600 secondi)
if (!defined('INSTANTWIN_NORMAL_END_REDUCTION_SECONDS')) {
    define('INSTANTWIN_NORMAL_END_REDUCTION_SECONDS', env_int('INSTANTWIN_NORMAL_END_REDUCTION_SECONDS', 0));
}
// Intervallo minimo tra gli slot (di default 3 minuti = 180 secondi)
if (!defined('INSTANTWIN_MIN_SLOT_INTERVAL_SECONDS')) {
    define('INSTANTWIN_MIN_SLOT_INTERVAL_SECONDS', env_int('INSTANTWIN_MIN_SLOT_INTERVAL_SECONDS', 180)); // 3 min di default
}
// Secondi aggiuntivi randomizzati (deterministici) dopo l'ultima vincita.
// Con JITTER_MIN=0 e JITTER_MAX>0: jitter in [0, JITTER_MAX]. Con JITTER_MIN>0: jitter in [JITTER_MIN, JITTER_MAX] (inclusi).
// Esempio base 180 + jitter 60..300 => attesa totale 4..8 min tra due vincite. JITTER_MAX=0 => disattivo.
if (!defined('INSTANTWIN_MIN_WIN_INTERVAL_JITTER_MIN_SECONDS')) {
    define('INSTANTWIN_MIN_WIN_INTERVAL_JITTER_MIN_SECONDS', env_int('INSTANTWIN_MIN_WIN_INTERVAL_JITTER_MIN_SECONDS', 0));
}
if (!defined('INSTANTWIN_MIN_WIN_INTERVAL_JITTER_MAX_SECONDS')) {
    define('INSTANTWIN_MIN_WIN_INTERVAL_JITTER_MAX_SECONDS', env_int('INSTANTWIN_MIN_WIN_INTERVAL_JITTER_MAX_SECONDS', 0));
}
// Nota: Il ritardo basato su una percentuale è stato rimosso per una ripartizione equa
// Gli slot sono vincibili non appena si entra nella loro finestra (window_start a window_end)
// Finestra di recupero per gli slot passati (di default 30 minuti = 1800 secondi)
if (!defined('INSTANTWIN_SLOT_RECOVERY_WINDOW_SECONDS')) {
    define('INSTANTWIN_SLOT_RECOVERY_WINDOW_SECONDS', env_int('INSTANTWIN_SLOT_RECOVERY_WINDOW_SECONDS', 1800)); // 30 min di default
}

// Recupero **solo ultima fascia**: da normal_end (es. ~23h) a mezzanotte, slot densi se resta quota.
// (Rimossa la seconda logica "sera dalle 18h" per evitare doppioni; il buco 14h-23h si copre con finestre
// primo slot + fallback 24/7, vedi checkTimeSlotWindow.)
if (!defined('INSTANTWIN_DEFAULT_24H_WIDE_WINDOW_FALLBACK')) {
    define('INSTANTWIN_DEFAULT_24H_WIDE_WINDOW_FALLBACK', env_bool('INSTANTWIN_DEFAULT_24H_WIDE_WINDOW_FALLBACK', false));
}
if (!defined('INSTANTWIN_WIDE_FALLBACK_HALF_SECONDS')) {
    define('INSTANTWIN_WIDE_FALLBACK_HALF_SECONDS', env_int('INSTANTWIN_WIDE_FALLBACK_HALF_SECONDS', 3600));
}
if (!defined('INSTANTWIN_WIDE_FALLBACK_MAX_SLOT_LEAD_SECONDS')) {
    define('INSTANTWIN_WIDE_FALLBACK_MAX_SLOT_LEAD_SECONDS', env_int('INSTANTWIN_WIDE_FALLBACK_MAX_SLOT_LEAD_SECONDS', 7200));
}
// Modalità test / legacy: niente calendario slot — esito forzato vincita o perdita.
if (!defined('INSTANTWIN_LEGACY_DIRECT_WIN')) {
    define('INSTANTWIN_LEGACY_DIRECT_WIN', env_bool('INSTANTWIN_LEGACY_DIRECT_WIN', false));
}

/**
 * @return 'off'|'direct_win'|'direct_loss'
 */
function instantwin_test_outcome_mode(): string {
    static $resolved = null;
    if ($resolved !== null) {
        return $resolved;
    }
    $mode = strtolower(trim((string) getenv('INSTANTWIN_TEST_OUTCOME_MODE')));
    if (in_array($mode, ['direct_win', 'direct_loss'], true)) {
        $resolved = $mode;
        return $resolved;
    }
    if ((defined('INSTANTWIN_LEGACY_DIRECT_WIN') && INSTANTWIN_LEGACY_DIRECT_WIN) || env_bool('INSTANTWIN_LEGACY_DIRECT_WIN', false)) {
        $resolved = 'direct_win';
        return $resolved;
    }
    $resolved = 'off';
    return $resolved;
}

/**
 * true = salta calendario_time_slot e claim; usa solo assignPrize / instantwin_pdv_assign_prize.
 */
function instantwin_legacy_direct_win_enabled(): bool {
    return instantwin_test_outcome_mode() === 'direct_win';
}

/** true = perdita immediata senza calendario (solo test). */
function instantwin_test_direct_loss_enabled(): bool {
    return instantwin_test_outcome_mode() === 'direct_loss';
}

/**
 * Verifica quota giornaliera e (se PDV) budget residuo prima del legacy direct win.
 */
function instantwin_legacy_direct_win_allowed(PDO $pdo, string $currentDate, ?string $pdvCod = null): bool {
    if (defined('MAX_DAILY_TARGET') && MAX_DAILY_TARGET > 0) {
        if ((int) getDailyRemaining($currentDate) <= 0) {
            return false;
        }
    }
    $pdv = $pdvCod !== null ? trim($pdvCod) : '';
    if ($pdv !== '' && function_exists('instantwin_pdv_get_budget_remaining')) {
        if (instantwin_pdv_get_budget_remaining($pdo, $pdv) <= 0) {
            return false;
        }
    }
    return true;
}

// ============================================
// FUNZIONI HELPER PER LA CONFIGURAZIONE
// ============================================

/**
 * Verifica se una funzionalità è attivata
 */
function instantwin_is_feature_enabled(string $feature): bool {
    $feature_map = [
        'time_restrictions' => INSTANTWIN_ENABLE_TIME_RESTRICTIONS,
        'day_customization' => INSTANTWIN_ENABLE_DAY_CUSTOMIZATION,
        'advanced_slots' => INSTANTWIN_ENABLE_ADVANCED_SLOTS,
        'daily_quotas' => true, // Logica ibrida sempre attiva
    ];
    return $feature_map[$feature] ?? false;
}

/** Fascia 0-24 senza TIME_RESTRICTIONS ne' DAY_CUSTOMIZATION. */
function instantwin_is_default_full_day_schedule(): bool {
    return !INSTANTWIN_ENABLE_TIME_RESTRICTIONS && !INSTANTWIN_ENABLE_DAY_CUSTOMIZATION;
}

/**
 * Secondi da attendere dopo l'ultima vincita prima che un'altra sia possibile.
 * Base = INSTANTWIN_MIN_SLOT_INTERVAL_SECONDS + jitter (deterministico da data|last_win_ts).
 */
function instantwin_effective_min_win_interval_seconds(?int $last_win_ts, string $current_date): int {
    $base = INSTANTWIN_MIN_SLOT_INTERVAL_SECONDS;
    $jmin = max(0, INSTANTWIN_MIN_WIN_INTERVAL_JITTER_MIN_SECONDS);
    $jmax = max(0, INSTANTWIN_MIN_WIN_INTERVAL_JITTER_MAX_SECONDS);
    if ($jmax <= 0 || $last_win_ts === null) {
        return $base;
    }
    if ($jmin > $jmax) {
        $jmin = $jmax;
    }
    $seed = $current_date . '|' . (string) $last_win_ts;
    $bin = hash('sha256', $seed, true);
    if ($bin === false || strlen($bin) < 4) {
        return $base;
    }
    $u = unpack('N', substr($bin, 0, 4))[1];
    $span = $jmax - $jmin + 1;
    $jitter = $jmin + (int) ($u % $span);
    return $base + $jitter;
}

/**
 * Restituisce gli orari per un giorno dato
 * Se la personalizzazione giorno per giorno è attivata, utilizza INSTANTWIN_DAY_X_HOURS
 * Altrimenti, utilizza INSTANTWIN_START_HOUR e INSTANTWIN_END_HOUR
 * Se gli orari sono disattivati, restituisce [0, 23] (24/7)
 */
function instantwin_get_time_range_for_day(int $day_of_week): array {
    // PRIORITÀ 1: Se la personalizzazione giorno per giorno è attivata, utilizzare gli orari personalizzati
    if (INSTANTWIN_ENABLE_DAY_CUSTOMIZATION) {
        $day_hours = env_day_hours("INSTANTWIN_DAY_{$day_of_week}_HOURS");
        if ($day_hours !== null) {
            return $day_hours;
        }
        // Se DAY_CUSTOMIZATION è attivato ma non ci sono orari personalizzati per questo giorno,
        // utilizzare gli orari di base se TIME_RESTRICTIONS è attivato
        if (INSTANTWIN_ENABLE_TIME_RESTRICTIONS) {
            return ['start' => INSTANTWIN_START_HOUR, 'end' => INSTANTWIN_END_HOUR];
        }
        // Se DAY_CUSTOMIZATION è attivato ma né orari personalizzati né TIME_RESTRICTIONS,
        // utilizzare 24/7 (00:00 a 24:00 escluso)
        return ['start' => 0, 'end' => 24];
    }
    
    // PRIORITÀ 2: Se le restrizioni orarie sono attivate, utilizzare gli orari di base dal .env
    if (INSTANTWIN_ENABLE_TIME_RESTRICTIONS) {
        // Utilizzare i valori dal .env (INSTANTWIN_START_HOUR e INSTANTWIN_END_HOUR)
        return ['start' => INSTANTWIN_START_HOUR, 'end' => INSTANTWIN_END_HOUR];
    }
    
    // PRIORITÀ 3: Nessuna restrizione (24/7) - 00:00:00 a 24:00:00 (escluso)
    // Se INSTANTWIN_ENABLE_TIME_RESTRICTIONS=false, è 24/7
    return ['start' => 0, 'end' => 24];
}

/**
 * Restituisce la lista dei giorni di distribuzione
 * Se disattivato, restituisce [1,2,3,4,5,6,7] (tutti i giorni)
 */
function instantwin_get_distribution_days(): array {
    if (!INSTANTWIN_ENABLE_DAY_CUSTOMIZATION) {
        return [1, 2, 3, 4, 5, 6, 7]; // Tous les jours
    }
    
    $days = env_days('INSTANTWIN_DISTRIBUTION_DAYS', [1, 2, 3, 4, 5, 6, 7]);
    return empty($days) ? [1, 2, 3, 4, 5, 6, 7] : $days;
}

/**
 * Verifica se si è negli orari di distribuzione per un giorno dato
 */
function instantwin_is_in_time_range(int $day_of_week, int $hour): bool {
    // Recuperare la fascia oraria per questo giorno (può essere personalizzata o di base)
    $time_range = instantwin_get_time_range_for_day($day_of_week);
    
    // Se la fascia è [0, 24], è 24/7 (nessuna restrizione)
    if ($time_range['start'] == 0 && $time_range['end'] == 24) {
        return true;
    }
    
    // Verificare se l'ora è nella fascia
    return $hour >= $time_range['start'] && $hour < $time_range['end'];
}

/**
 * Calcola i limiti della giornata (start e end timestamps) in modo standardizzato
 * Gestisce correttamente il caso end_hour=24 (fine esclusiva = giorno successivo 00:00)
 * 
 * @param string $date Data nel formato 'Y-m-d'
 * @param int $start_hour Ora di inizio (0-23)
 * @param int $end_hour Ora di fine (1-24, dove 24 = fine esclusiva = giorno successivo 00:00)
 * @return array [day_start_ts, day_end_ts] Timestamp di inizio e fine (fine esclusiva)
 */
function instantwin_get_day_bounds_ts(string $date, int $start_hour, int $end_hour): array {
    // Inizio
    $day_start_ts = strtotime($date . IW_SQL_CONCAT_SPACE . $start_hour . ':00:00');

    // Fine (fine esclusiva)
    if ($end_hour === 24) {
        // 24:00 = giorno successivo 00:00
        $day_end_ts = strtotime($date . ' 00:00:00') + 86400;
    } else {
        $day_end_ts = strtotime($date . IW_SQL_CONCAT_SPACE . $end_hour . ':00:00');
    }

    // Sicurezza (evita durate negative)
    if ($day_end_ts <= $day_start_ts) {
        // Fallback 24h complete a partire dall'inizio
        $day_end_ts = $day_start_ts + 86400;
    }

    return [$day_start_ts, $day_end_ts];
}

/**
 * Mode rattrapage (catch-up) : dernières heures avant la fin de plage.
 * Si end_hour=24, day_end_ts = minuit J+1 (H=0) — ne pas activer le catch-up toute la journée.
 *
 * @return array{is_catchup: bool, catchup_start_ts: int}
 */
function instantwin_resolve_catchup_mode(
    int $now,
    int $day_start_ts,
    int $day_end_ts,
    int $normal_end_ts,
    int $end_hour,
    int $daily_remaining
): array {
    if ($daily_remaining <= 0) {
        return ['is_catchup' => false, 'catchup_start_ts' => $normal_end_ts];
    }

    $catchup_hours = defined('INSTANTWIN_CATCHUP_HOURS_BEFORE_END')
        ? max(0, (int) INSTANTWIN_CATCHUP_HOURS_BEFORE_END)
        : 2;

    if ($end_hour === 24) {
        $catchup_start_ts = max($day_start_ts, $day_end_ts - $catchup_hours * 3600);

        return [
            'is_catchup' => $now >= $catchup_start_ts,
            'catchup_start_ts' => $catchup_start_ts,
        ];
    }

    $current_hour = (int) date('H', $now);
    $normal_end_hour = (int) date('H', $normal_end_ts);
    $is_catchup = $current_hour >= $normal_end_hour;

    return [
        'is_catchup' => $is_catchup,
        'catchup_start_ts' => $is_catchup ? $normal_end_ts : $day_start_ts,
    ];
}

// ============================================
// FUNZIONI PRINCIPALI (sempre attive)
// ============================================

function createTimeSlotsTable() {
    global $c_pdo;
    /** @var PDO $c_pdo */
    static $table_created = false;
    if ($table_created) {
        return;
    }

    $sql = "
    CREATE TABLE IF NOT EXISTS `calendario_time_slot` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `data` DATE NOT NULL,
        `slot_timestamp` INT NOT NULL,
        `finestra_inizio` INT NOT NULL,
        `finestra_fine` INT NOT NULL,
        `token_vincitore` VARCHAR(64) DEFAULT NULL,
        `partecipanti_totali` INT DEFAULT 0,
        `data_tiraggio` DATETIME DEFAULT NULL,
        UNIQUE KEY `uk_data_slot` (`data`, `slot_timestamp`),
        INDEX `idx_data_slot` (`data`, `slot_timestamp`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    try {
        $c_pdo->exec($sql);
        $table_created = true;
    } catch (Exception $e) {
        error_log("Nota: creazione tabella calendario_time_slot: ".$e->getMessage());
        $table_created = true;
    }
}

function createDailyQuotaTable(?PDO $pdo = null): void
{
    if ($pdo === null) {
        global $c_pdo;
        $pdo = $c_pdo;
    }
    if (!$pdo instanceof PDO) {
        error_log('createDailyQuotaTable: connessione PDO non disponibile');

        return;
    }

    static $done = [];
    $cid = spl_object_id($pdo);
    if (!empty($done[$cid])) {
        return;
    }

    $sql = "
    CREATE TABLE IF NOT EXISTS `quota_giornaliera` (
        `data` DATE PRIMARY KEY,
        `target` INT NOT NULL,
        `assigned` INT NOT NULL DEFAULT 0,
        `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    try {
        $pdo->exec($sql);
        $done[$cid] = true;
    } catch (Exception $e) {
        error_log('Creazione quota_giornaliera: ' . $e->getMessage());
        $done[$cid] = true;
    }
}

function isDateInContestRange($date) {
    $contest_dates = getContestDates();
    if (!$contest_dates) {
        error_log("isDateInContestRange: contest_dates non disponibile per data $date");
        return false;
    }
    
    $tz = new DateTimeZone(IW_TIMEZONE_EUROPE_ROME);
    $check_date = new DateTime($date, $tz);
    $start_date = new DateTime($contest_dates['start_date'], $tz);
    $end_date = new DateTime($contest_dates['end_date'], $tz); // <– qui: fine di DISTRIBUZIONE
    
    // Si include l'intera giornata di fine distribuzione (00:00:00 → 23:59:59)
    // IMPORTANTE: Utilizzare <= per includere esplicitamente la data di fine
    $cd = (clone $check_date)->setTime(0, 0, 0);
    $sd = (clone $start_date)->setTime(0, 0, 0);
    $ed = (clone $end_date)->setTime(0, 0, 0);
    
    // Verificare se la data è nel range (inclusivo delle due estremità)
    $is_in_range = ($cd >= $sd && $cd <= $ed);
    
    // Log dettagliato per debug - SEMPRE loggare per capire il problema
    if (!$is_in_range) {
        if (INSTANTWIN_ENABLE_CUSTOM_END_DATE) {
            error_log("isDateInContestRange (InstantWin): $date NON nel range di distribuzione premi (start: {$contest_dates['start_date']}, distrib_end: {$contest_dates['end_date']}, fine ufficiale concorso: {$contest_dates['official_end_date']})");
        } else {
            error_log("isDateInContestRange (InstantWin): $date NON nel range del concorso (start: {$contest_dates['start_date']}, end: {$contest_dates['end_date']})");
        }
    } else {
        // Log per confermare l'inclusione della data di fine
        if ($cd->format('Y-m-d') === $ed->format('Y-m-d')) {
            error_log("isDateInContestRange (InstantWin): $date è la data di fine distribuzione - INCLUSA");
        }
    }
    
    return $is_in_range;
}

/**
 * Restituisce le date del concorso Instant Win
 * La data di inizio viene sempre da content.json (non modificabile)
 * La data di fine può essere personalizzata se INSTANTWIN_ENABLE_CUSTOM_END_DATE=true
 */
/**
 * Restituisce la lista delle date di distribuzione rimanenti (oggi incluso)
 * secondo la configurazione (distribution_days + range concorso).
 * @param string $from_date 'Y-m-d'
 * @return array<string> es: ['2025-12-01','2025-12-02',...]
 */
function getRemainingDistributionDates(string $from_date): array {
    $contest_dates = getContestDates();
    if (!$contest_dates) {
        return [];
    }

    $tz = new DateTimeZone(IW_TIMEZONE_EUROPE_ROME);
    $start = new DateTime($contest_dates['start_date'], $tz);
    $end   = new DateTime($contest_dates['end_date'], $tz);

    $from = new DateTime($from_date, $tz);
    $from->setTime(0,0,0);

    // limitare from >= start
    $start0 = (clone $start)->setTime(0,0,0);
    if ($from < $start0) {
        $from = clone $start0;
    }

    // fine inclusiva -> si cicla fino al giorno successivo 00:00
    $end0_next = (clone $end)->setTime(0,0,0)->modify(IW_DATE_INTERVAL_ONE_DAY);

    $distribution_days = instantwin_get_distribution_days();
    $dates = [];
    $cur = clone $from;
    while ($cur < $end0_next) {
        $ds = $cur->format('Y-m-d');
        $dow = (int)$cur->format('N');
        if (isDateInContestRange($ds) && in_array($dow, $distribution_days, true)) {
            $dates[] = $ds;
        }
        $cur->modify(IW_DATE_INTERVAL_ONE_DAY);
    }
    return $dates;
}

if (!function_exists('getContestOfficialDates')) {
    /** @return array<string, string>|null */
    function getContestOfficialDates(): ?array {
        $official = null;

        if (isset($GLOBALS['contest_dates']) && !empty($GLOBALS['contest_dates'])) {
            $official = $GLOBALS['contest_dates'];
            error_log("getContestDates: Utilizzo GLOBALS['contest_dates']: " . json_encode($official));
        } else {
            global $contest_dates;
            if (empty($contest_dates)) {
                require_once __DIR__ . '/../../inc/config.php';
            }
            if (isset($contest_dates) && !empty($contest_dates)) {
                $official = $contest_dates;
                error_log("getContestDates: Utilizzo contest_dates globale: " . json_encode($official));
            }
        }

        if (!$official || empty($official['start_date']) || empty($official['end_date'])) {
            error_log("getContestDates: ERRORE! Date ufficiali non disponibili. official=" . json_encode($official));
            return null;
        }

        return $official;
    }
}

if (!function_exists('getContestDistributionEndDate')) {
    function getContestDistributionEndDate(string $start_date, string $official_end_date): string {
        global $instantwin_end;

        if (!INSTANTWIN_ENABLE_CUSTOM_END_DATE) {
            error_log("getContestDates: INSTANTWIN_ENABLE_CUSTOM_END_DATE=false, uso la data ufficiale: $official_end_date");
            return $official_end_date;
        }

        if (!isset($instantwin_end) || $instantwin_end === null) {
            $instantwin_end = env_date('INSTANTWIN_END_DATE');
            error_log("getContestDates: Rilettura INSTANTWIN_END_DATE dal .env: " . ($instantwin_end ?? 'NULL'));
        }

        if ($instantwin_end === null) {
            error_log("getContestDates: ATTENZIONE! INSTANTWIN_ENABLE_CUSTOM_END_DATE=true ma INSTANTWIN_END_DATE non definito nel .env. Uso la data ufficiale: $official_end_date");
            return $official_end_date;
        }

        if ($instantwin_end < $start_date) {
            error_log("getContestDates: ATTENZIONE! INSTANTWIN_END_DATE ($instantwin_end) è prima della data di inizio ($start_date). Uso la data di inizio.");
            return $start_date;
        }

        if ($instantwin_end > $official_end_date) {
            error_log("getContestDates: ATTENZIONE! INSTANTWIN_END_DATE ($instantwin_end) è dopo la fine ufficiale ($official_end_date). Uso la fine ufficiale.");
            return $official_end_date;
        }

        error_log("getContestDates: CUSTOM_END_DATE attivo. Distribuzione premi dal $start_date al $instantwin_end (fine ufficiale concorso: $official_end_date)");
        return $instantwin_end;
    }
}

function getContestDates() {
    $official = getContestOfficialDates();
    if ($official === null) {
        return null;
    }

    $start_date = $official['start_date'];
    $official_end_date = $official['end_date'];

    error_log("getContestDates: Date ufficiali caricate - start: $start_date, end: $official_end_date");

    $distribution_end = getContestDistributionEndDate($start_date, $official_end_date);

    return [
        'start_date'         => $start_date,
        'end_date'           => $distribution_end,
        'official_end_date'  => $official_end_date,
    ];
}

/**
 * Se true, lo stesso email/fidelity può vincere più volte (flag .env INSTANTWIN_ALLOW_MULTIPLE_WINS).
 */
function instantwin_allow_multiple_wins(): bool
{
    return filter_var(getenv('INSTANTWIN_ALLOW_MULTIPLE_WINS') ?: 'false', FILTER_VALIDATE_BOOLEAN);
}

function hasEmailAlreadyWon($token) {
    global $c_pdo, $contest_config;
    /** @var PDO|null $c_pdo */
    try {
        if (!($c_pdo instanceof PDO)) {
            return false;
        }
        // Verificare se fidelity è attivato nella configurazione
        $fidelity_enabled = !empty($contest_config['fields_enabled']['fidelity'] ?? false);
        
        // Recuperare l'email e la fidelity della partecipazione attuale
        $stmt_current = $c_pdo->prepare("
            SELECT u1.EMAIL, u1.FIDELITY
            FROM partecipazioni p1
            JOIN utenti u1 ON p1.ID_CLIENTE = u1.ID_CLIENTE
            WHERE p1.TOKEN = ' . IW_SQL_BIND_TOKEN . '
            LIMIT 1
        ");
        $stmt_current->bindValue(IW_SQL_BIND_TOKEN, $token, PDO::PARAM_STR);
        $stmt_current->execute();
        $current_user = $stmt_current->fetch();
        
        if (!$current_user) {
            return false; // Token non trovato
        }
        
        $email = strtolower($current_user['EMAIL'] ?? '');
        $fidelity = $current_user['FIDELITY'] ?? null;
        
        // Determinare il campo da verificare secondo la configurazione
        $check_field = '';
        $check_value = '';
        
        // Se fidelity è attivato nella config e il valore fidelity esiste e non è vuoto
        if ($fidelity_enabled && !empty($fidelity)) {
            $check_field = 'u2.FIDELITY';
            $check_value = $fidelity;
        } elseif (!empty($email)) {
            // Altrimenti, utilizzare l'email
            $check_field = 'LOWER(u2.EMAIL)';
            $check_value = $email;
        }
        
        // Se nessun campo da verificare, restituire false (nessun controllo)
        if (empty($check_field) || empty($check_value)) {
            return false;
        }
        
        // Verificare se questa email/fidelity ha già vinto
        $stmt = $c_pdo->prepare("
            SELECT COUNT(*) as count
            FROM partecipazioni p2
            JOIN utenti u2 ON p2.ID_CLIENTE = u2.ID_CLIENTE
            WHERE {$check_field} = :check_value
              AND p2.HAS_WON = 1
              AND COALESCE(p2.ANNULLATO, 0) = 0
              AND p2.TOKEN != ' . IW_SQL_BIND_TOKEN . '
            LIMIT 1
        ");
        $stmt->bindValue(':check_value', $check_value, PDO::PARAM_STR);
        $stmt->bindValue(IW_SQL_BIND_TOKEN, $token, PDO::PARAM_STR);
        $stmt->execute();
        $result = $stmt->fetch();
        return (int)$result['count'] > 0;
    } catch (Exception $e) {
        error_log("Errore hasEmailAlreadyWon per token " . substr($token, 0, 8) . ": " . $e->getMessage());
        return false;
    }
}

function checkPrizesAvailable() {
    global $c_pdo;
    /** @var PDO $c_pdo */
    try {
        $stmt = $c_pdo->prepare('
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN quantita_assegnati < quantita_totale THEN 1 ELSE 0 END) as available_count,
                SUM(quantita_totale - quantita_assegnati) as available_quantity
            FROM premi
            WHERE quantita_assegnati < quantita_totale
        ');
        $stmt->execute();
        $r = $stmt->fetch();
        return [
            'available' => (int)$r['available_quantity'] > 0,
            'count'     => (int)$r['available_count'],
            'quantity'  => (int)$r['available_quantity'],
            'total'     => (int)$r['total']
        ];
    } catch (Exception $e) {
        error_log("Errore verifica premi disponibili: " . $e->getMessage());
        return ['available'=>false,'count'=>0,'quantity'=>0,'total'=>0];
    }
}

/**
 * Restituisce il numero totale di premi disponibili dalla DB
 * Utilizzato per il calcolo ibrido degli slot
 */
function getTotalPrizesAvailable() {
    global $c_pdo;
    /** @var PDO $c_pdo */
    try {
        $stmt = $c_pdo->prepare('
            SELECT COALESCE(SUM(quantita_totale - quantita_assegnati), 0) as available
            FROM premi 
            WHERE quantita_assegnati < quantita_totale
        ');
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    } catch (Exception $e) {
        error_log("Errore getTotalPrizesAvailable: " . $e->getMessage());
        return 0;
    }
}

/**
 * Calcola il numero di slot per giorno secondo la logica ibrida
 * Basato sul numero di premi disponibili e i giorni rimanenti
 */
function calculateHybridSlotsPerDay($date, $start_hour, $end_hour) {
    // 1. Recuperare il numero totale di premi dal database
    $total_prizes = getTotalPrizesAvailable();
    
    // 2. Calcolare i giorni secondo le date inizio/fine
    $days_left = countDistributionDaysLeft($date);
    
    if ($days_left <= 0 || $total_prizes <= 0) {
        // Fallback: minimo 1 slot per ora
        return max(1, $end_hour - $start_hour);
    }
    
    // 3. Calcolo ibrido: premi per giorno
    $prizes_per_day = ceil($total_prizes / $days_left);
    
    // 4. Numero di slot = numero di premi da distribuire
    // (garantisce la distribuzione massima)
    // ✅ IMPORTANTE: Garantire sempre almeno un minimo di slot anche con pochi premi
    // per permettere la partecipazione e la generazione del calendario
    $hours_range = $end_hour - $start_hour;
    $min_slots = max(1, min($hours_range, 3)); // Almeno 1-3 slot selon la plage horaire
    
    return max($min_slots, $prizes_per_day);
}

// ============================================
// FUNZIONI SECONDARIE (condizionali)
// ============================================

/**
 * Verifica se si è negli orari di distribuzione
 * Verifica gli orari personalizzati se DAY_CUSTOMIZATION è attivato, anche se TIME_RESTRICTIONS è disattivato
 */
function isPrizeDistributionTime($timestamp = null) {
    $now = $timestamp !== null ? $timestamp : time();
    $day_of_week = (int)date('N', $now);
    
    // Utilizzare instantwin_is_in_time_range che verifica automaticamente gli orari personalizzati
    // se DAY_CUSTOMIZATION è attivato, anche se TIME_RESTRICTIONS è disattivato
    $hour = (int)date('H', $now);
    return instantwin_is_in_time_range($day_of_week, $hour);
}

/**
 * Conta i giorni di distribuzione TOTALI dal inizio alla fine del concorso
 * Utile per calcolare la distribuzione iniziale dei premi
 */
function countTotalDistributionDays() {
    $contest_dates = getContestDates();
    if (!$contest_dates) {
        error_log("countTotalDistributionDays: contest_dates non definito");
        return 0;
    }

    $tz = new DateTimeZone(IW_TIMEZONE_EUROPE_ROME);
    $start = new DateTime($contest_dates['start_date'], $tz);
    $end   = new DateTime($contest_dates['end_date'], $tz);

    $start_date_only = (clone $start)->setTime(0,0,0);
    $end_date_only   = (clone $end)->setTime(0,0,0)->modify(IW_DATE_INTERVAL_ONE_DAY); // Giorno successivo per includere l'intera giornata di fine

    $distribution_days = instantwin_get_distribution_days();
    $cur = clone $start_date_only;
    $count = 0;
    while ($cur < $end_date_only) {
        $dow = (int)$cur->format('N');
        if (in_array($dow, $distribution_days)) {
            $count++;
        }
        $cur->modify(IW_DATE_INTERVAL_ONE_DAY);
    }
    return $count;
}

/**
 * Conta i giorni di distribuzione rimanenti secondo la configurazione
 */
function countDistributionDaysLeft($date) {
    if (!isDateInContestRange($date)) {
        return 0;
    }
    
    $contest_dates = getContestDates();
    if (!$contest_dates) {
        error_log("countDistributionDaysLeft: contest_dates non definito per data $date");
        return 0;
    }

    $tz = new DateTimeZone(IW_TIMEZONE_EUROPE_ROME);
    $today = new DateTime($date, $tz);
    $start = new DateTime($contest_dates['start_date'], $tz);
    $end   = new DateTime($contest_dates['end_date'], $tz);

    if ($today < $start) {
        $today = clone $start;
    }

    $today_date_only = (clone $today)->setTime(0,0,0);
    $end_date_only   = (clone $end)->setTime(0,0,0)->modify(IW_DATE_INTERVAL_ONE_DAY); // Giorno successivo per includere l'intera giornata di fine
    if ($today_date_only >= $end_date_only) {
        return 0; // >= perché $end_date_only è già il giorno successivo
    }

    $distribution_days = instantwin_get_distribution_days();
    $cur = clone $today_date_only;
    $count = 0;
    while ($cur < $end_date_only) { // < invece di <= perché $end_date_only è già il giorno successivo
        $dow = (int)$cur->format('N');
        if (in_array($dow, $distribution_days)) {
            $count++;
        }
        $cur->modify(IW_DATE_INTERVAL_ONE_DAY);
    }
    return $count;
}

/**
 * Tutte le date di distribuzione del concorso (inizio → fine, giorni attivi).
 *
 * @return list<string> Y-m-d
 */
function getAllContestDistributionDates(): array {
    $contest_dates = getContestDates();
    if (!$contest_dates) {
        return [];
    }

    return getRemainingDistributionDates($contest_dates['start_date']);
}

/** @return 'balanced'|'progressive' */
function instantwin_quota_distribution_mode(): string {
    return INSTANTWIN_QUOTA_DISTRIBUTION === 'balanced' ? 'balanced' : 'progressive';
}

function instantwin_get_total_initial_prizes(PDO $pdo): int {
    $stmt = $pdo->prepare('SELECT COALESCE(SUM(quantita_totale), 0) FROM premi');
    $stmt->execute();

    return (int) $stmt->fetchColumn();
}

/** Vincite con win_date strictement avant $beforeDate. */
function instantwin_count_wins_before_date(PDO $pdo, string $beforeDate): int {
    $contest_dates = getContestDates();
    if (!$contest_dates) {
        return 0;
    }

    try {
        $stmt = $pdo->prepare('
            SELECT COUNT(*)
            FROM partecipazioni
            WHERE has_won = 1
              AND COALESCE(annullato, 0) = 0
              AND win_date IS NOT NULL
              AND DATE(win_date) < :today
              AND DATE(win_date) >= :start_date
        ');
        $stmt->execute([
            ':today' => $beforeDate,
            ':start_date' => $contest_dates['start_date'],
        ]);

        return (int) $stmt->fetchColumn();
    } catch (Exception $e) {
        try {
            createDailyQuotaTable();
            $stmt_fb = $pdo->prepare('
                SELECT COALESCE(SUM(assigned), 0)
                FROM quota_giornaliera
                WHERE data < :today AND data >= :start_date
            ');
            $stmt_fb->execute([
                ':today' => $beforeDate,
                ':start_date' => $contest_dates['start_date'],
            ]);

            return (int) $stmt_fb->fetchColumn();
        } catch (Exception $e2) {
            return 0;
        }
    }
}

if (!function_exists('instantwin_quota_adjust_increase')) {
    /**
     * @param array<string, int> $quotas
     * @param list<string> $datesOrder
     * @return array<string, int>
     */
    function instantwin_quota_adjust_increase(array $quotas, int $diff, array $datesOrder): array {
        for ($i = count($datesOrder) - 1; $i >= 0 && $diff > 0; $i--) {
            $d = $datesOrder[$i];
            if (!isset($quotas[$d])) {
                continue;
            }
            $quotas[$d]++;
            $diff--;
        }
        return $quotas;
    }
}

if (!function_exists('instantwin_quota_adjust_decrease')) {
    /**
     * @param array<string, int> $quotas
     * @param list<string> $datesOrder
     * @return array<string, int>
     */
    function instantwin_quota_adjust_decrease(array $quotas, int $to_remove, array $datesOrder): array {
        for ($i = count($datesOrder) - 1; $i >= 0 && $to_remove > 0; $i--) {
            $d = $datesOrder[$i];
            if (!isset($quotas[$d]) || $quotas[$d] <= 0) {
                continue;
            }
            if ($quotas[$d] > 1) {
                $quotas[$d]--;
                $to_remove--;
            }
        }
        if ($to_remove > 0) {
            error_log("instantwin_quota_adjust_to_total: impossibile rimuovere {$to_remove} unità");
        }
        return $quotas;
    }
}

/**
 * @param array<string, int> $quotas
 * @param list<string> $datesOrder
 * @return array<string, int>
 */
function instantwin_quota_adjust_to_total(array $quotas, int $targetTotal, array $datesOrder): array {
    $diff = $targetTotal - array_sum($quotas);
    if ($diff === 0 || $datesOrder === []) {
        return $quotas;
    }

    if ($diff > 0) {
        return instantwin_quota_adjust_increase($quotas, $diff, $datesOrder);
    }

    return instantwin_quota_adjust_decrease($quotas, abs($diff), $datesOrder);
}

if (!function_exists('instantwin_distribute_quota_balanced')) {
    /**
     * @param list<string> $dates
     * @return array<string, int>
     */
    function instantwin_distribute_quota_balanced(int $toDistribute, array $dates): array {
        $n = count($dates);
        $out = array_fill_keys($dates, 0);
        $base = intdiv($toDistribute, $n);
        $rem = $toDistribute % $n;
        foreach ($dates as $i => $d) {
            $out[$d] = $base + ($i < $rem ? 1 : 0);
        }
        return $out;
    }
}

if (!function_exists('instantwin_distribute_quota_weighted')) {
    /**
     * @param list<string> $dates
     * @return array<string, int>
     */
    function instantwin_distribute_quota_weighted(int $toDistribute, array $dates): array {
        $n = count($dates);
        $out = array_fill_keys($dates, 0);

        $coefStart = (float) INSTANTWIN_QUOTA_COEF_START;
        $coefEnd = (float) INSTANTWIN_QUOTA_COEF_END;
        if ($coefStart < $coefEnd) {
            [$coefStart, $coefEnd] = [$coefEnd, $coefStart];
        }

        $weights = [];
        $sumW = 0.0;
        foreach ($dates as $i => $d) {
            $position = ($n > 1) ? ($i / ($n - 1)) : 0.0;
            $coef = $coefStart - ($coefStart - $coefEnd) * $position;
            $w = max(0.01, $coef);
            $weights[$d] = $w;
            $sumW += $w;
        }

        $minOnePerDay = ($toDistribute >= $n);
        $allocated = 0;
        foreach ($dates as $i => $d) {
            if ($i === $n - 1) {
                $out[$d] = max(0, $toDistribute - $allocated);
                break;
            }
            $q = (int) floor($toDistribute * $weights[$d] / $sumW);
            $q = $minOnePerDay ? max(1, $q) : max(0, $q);
            $out[$d] = $q;
            $allocated += $q;
        }

        return instantwin_quota_adjust_to_total($out, $toDistribute, $dates);
    }
}

/**
 * Répartit $toDistribute sur $dates (ordre chronologique) — INSTANTWIN_QUOTA_DISTRIBUTION.
 *
 * @param list<string> $dates
 * @return array<string, int>
 */
function instantwin_distribute_quota_on_dates(int $toDistribute, array $dates): array {
    $dates = array_values($dates);
    $n = count($dates);
    if ($n === 0) {
        return [];
    }

    $out = array_fill_keys($dates, 0);
    if ($toDistribute <= 0) {
        return $out;
    }

    if ($n === 1) {
        $out[$dates[0]] = $toDistribute;
        return $out;
    }

    if (instantwin_quota_distribution_mode() === 'balanced') {
        return instantwin_distribute_quota_balanced($toDistribute, $dates);
    }

    return instantwin_distribute_quota_weighted($toDistribute, $dates);
}

/**
 * Plan quota : prémios restants × jours restants (ou calendrier complet avant concours).
 *
 * @return array<string, int>
 */
function instantwin_build_quota_plan(PDO $pdo, string $asOfDate, bool $fullContestCalendar): array {
    $total_initial = instantwin_get_total_initial_prizes($pdo);
    if ($total_initial <= 0) {
        return [];
    }

    if ($fullContestCalendar) {
        $dates = getAllContestDistributionDates();
        $to_distribute = $total_initial;
    } else {
        $dates = getRemainingDistributionDates($asOfDate);
        $assigned_so_far = instantwin_count_wins_before_date($pdo, $asOfDate);
        $to_distribute = max(0, $total_initial - $assigned_so_far);
    }

    if ($dates === []) {
        return [];
    }

    if (count($dates) === 1) {
        return [$dates[0] => $to_distribute];
    }

    return instantwin_distribute_quota_on_dates($to_distribute, $dates);
}

/**
 * @param array<string, int> $quotas
 */
function instantwin_persist_quota_targets(PDO $pdo, array $quotas): int {
    createDailyQuotaTable($pdo);
    $n = 0;
    $sel = $pdo->prepare('SELECT assigned FROM quota_giornaliera WHERE data = :d');
    $upd = $pdo->prepare('UPDATE quota_giornaliera SET target = :t WHERE data = :d');
    $ins = $pdo->prepare('INSERT INTO quota_giornaliera (data, target, assigned) VALUES (:d, :t, 0)');

    foreach ($quotas as $date_str => $target) {
        $target = max(0, (int) $target);
        $sel->execute([':d' => $date_str]);
        $row = $sel->fetch(PDO::FETCH_ASSOC);
        if ($row !== false) {
            $assigned = (int) ($row['assigned'] ?? 0);
            if ($target < $assigned) {
                $target = $assigned;
            }
            $upd->execute([':d' => $date_str, ':t' => $target]);
        } else {
            $ins->execute([':d' => $date_str, ':t' => $target]);
        }
        $n++;
    }

    return $n;
}

/** Ricalcola e salva i target per i giorni di distribuzione ancora da coprire. */
function instantwin_sync_remaining_quota_targets(PDO $pdo, string $asOfDate): array {
    $plan = instantwin_build_quota_plan($pdo, $asOfDate, false);
    if ($plan === []) {
        return [];
    }

    instantwin_persist_quota_targets($pdo, $plan);

    error_log('instantwin_sync_remaining_quota_targets(' . $asOfDate . '): mode=' . instantwin_quota_distribution_mode()
        . ' giorni=' . count($plan) . ' somma=' . array_sum($plan));

    return $plan;
}

/**
 * Calcola il numero di premi per giorno secondo la configurazione
 * Formula HYBRIDE migliorata: distribuzione progressiva DYNAMIQUE
 * 
 * MIGLIORAMENTO: Calcola dinamicamente ogni giorno secondo l'avanzamento reale:
 * - Utilizza i PREMI RIMANENTI (non il totale iniziale)
 * - Utilizza i GIORNI RIMANENTI (non il totale)
 * - Applica il coefficiente progressivo basato sulla posizione nel concorso
 * - Ridistribuisce i quota mancati dei giorni precedenti
 * 
 * Questo garantisce che tutti i premi saranno distribuiti anche se un giorno viene saltato.
 */
function getPrizesPerDay($timestamp = null, $pdo = null) {
    if ($pdo === null) {
        global $c_pdo;
        $pdo = $c_pdo;
    }
    /** @var PDO $pdo */

    $now = $timestamp !== null ? (int) $timestamp : time();
    $date = date('Y-m-d', $now);

    if (!isDateInContestRange($date)) {
        return 0;
    }

    $plan = instantwin_build_quota_plan($pdo, $date, false);
    if ($plan === []) {
        return 0;
    }

    $quota = (int) ($plan[$date] ?? 0);
    if ($quota < 1 && array_sum($plan) > 0) {
        $quota = 1;
    }

    error_log('getPrizesPerDay(' . $date . '): mode=' . instantwin_quota_distribution_mode()
        . ' target=' . $quota . ' somma_piano=' . array_sum($plan));

    return $quota;
}

if (!function_exists('initDailyTargetIfNeededCedi')) {
    function initDailyTargetIfNeededCedi(PDO $pdo, string $date, bool $force_recalculate): int {
        $today = date('Y-m-d');
        if ($date === $today) {
            $force_recalculate = true;
        }
        $contest_dates = getContestDates();
        if ($contest_dates && $date === ($contest_dates['end_date'] ?? '')) {
            $force_recalculate = true;
        }

        if ($force_recalculate) {
            instantwin_cedi_resync_daily_quota_targets($pdo, $date);
        } else {
            instantwin_cedi_ensure_daily_quota_table($pdo);
            $chk = $pdo->prepare('SELECT COUNT(*) FROM `cedi_quota_giornaliera` WHERE `data` = :d');
            $chk->execute([':d' => $date]);
            if ((int) $chk->fetchColumn() === 0) {
                instantwin_cedi_sync_daily_quota_targets($pdo, $date);
            }
        }

        $new_target = instantwin_cedi_get_global_daily_target($pdo, $date);
        if (!function_exists('instantwin_cedi_simulation_context_active')
            || !instantwin_cedi_simulation_context_active()) {
            error_log('initDailyTargetIfNeeded(' . $date . '): cedi_daily target=' . $new_target
                . ' force=' . ($force_recalculate ? '1' : '0'));
        }
        return $new_target;
    }
}

if (!function_exists('initDailyTargetIfNeededStandard')) {
    function initDailyTargetIfNeededStandard(PDO $pdo, string $date, $check_time, bool $force_recalculate): int {
        createDailyQuotaTable();

        $today = date('Y-m-d');
        if ($date === $today) {
            $force_recalculate = true;
        }

        $q = $pdo->prepare(IW_SQL_QUOTA_DAILY_SELECT);
        $q->execute([':d' => $date]);
        $existing = $q->fetch(PDO::FETCH_ASSOC);

        if ($existing !== false && !$force_recalculate) {
            return (int) $existing['target'];
        }

        if ($check_time === null) {
            $check_time = strtotime($date . ' 12:00:00');
        }

        $plan = instantwin_sync_remaining_quota_targets($pdo, $date);
        $new_target = (int) ($plan[$date] ?? (int) getPrizesPerDay($check_time, $pdo));
        if ($new_target < 0) {
            $new_target = 0;
        }

        $already_assigned = ($existing !== false) ? (int) $existing['assigned'] : 0;
        if ($new_target < $already_assigned) {
            $new_target = $already_assigned;
            $pdo->prepare('UPDATE quota_giornaliera SET target = :t WHERE data = :d')
                ->execute([':d' => $date, ':t' => $new_target]);
        }

        error_log('initDailyTargetIfNeeded(' . $date . '): target=' . $new_target . ' assigned=' . $already_assigned
            . ' force=' . ($force_recalculate ? '1' : '0') . ' mode=' . instantwin_quota_distribution_mode());

        return $new_target;
    }
}

/**
 * Inizializza il quota giornaliero se necessario
 * IMPORTANTE: Se i quota non sono ancora inizializzati, inizializza TUTTI i quota in una volta
 * per garantire che il totale sia esattamente uguale al totale di premi (non 512 ma 500)
 */
function initDailyTargetIfNeeded($date, $check_time = null, $force_recalculate = false) {
    global $c_pdo;
    /** @var PDO $c_pdo */

    if (!isDateInContestRange($date)) {
        return 0;
    }

    if (!function_exists('instantwin_cedi_daily_quota_enabled')) {
        require_once __DIR__ . '/../instantwin_cedi/cedi_core.php';
    }
    if (function_exists('instantwin_cedi_daily_quota_enabled') && instantwin_cedi_daily_quota_enabled($c_pdo)) {
        return initDailyTargetIfNeededCedi($c_pdo, $date, $force_recalculate);
    }

    return initDailyTargetIfNeededStandard($c_pdo, $date, $check_time, $force_recalculate);
}

/**
 * Inizializza i quota per tutte le date valide del concorso
 * Utile per pre-inizializzare i quota prima dell'inizio del concorso
 * IMPORTANTE: Calcola tutti i quota in una volta e aggiusta per che il totale sia ESATTAMENTE uguale al totale di premi
 */
function initializeAllDailyQuotas() {
    global $c_pdo;
    /** @var PDO $c_pdo */
    
    $contest_dates = getContestDates();
    if (!$contest_dates) {
        error_log("initializeAllDailyQuotas: impossibile ottenere le date del concorso");
        return 0;
    }
    
    createDailyQuotaTable();

    $total_initial_prizes = instantwin_get_total_initial_prizes($c_pdo);
    if ($total_initial_prizes <= 0) {
        error_log('initializeAllDailyQuotas: Nessun totale premi trovato nel database');
        return 0;
    }

    $today = date('Y-m-d');
    $beforeStart = ($today < $contest_dates['start_date']);
    $fullCalendar = $beforeStart && instantwin_count_wins_before_date($c_pdo, $today) === 0;

    if ($fullCalendar) {
        $quotas = instantwin_build_quota_plan($c_pdo, $contest_dates['start_date'], true);
    } else {
        $quotas = instantwin_build_quota_plan($c_pdo, $today, false);
    }

    if ($quotas === []) {
        error_log('initializeAllDailyQuotas: nessun giorno di distribuzione nel piano quota');
        return 0;
    }

    $initialized_count = instantwin_persist_quota_targets($c_pdo, $quotas);
    $final_total = array_sum($quotas);

    error_log('initializeAllDailyQuotas: mode=' . instantwin_quota_distribution_mode()
        . ' full_calendar=' . ($fullCalendar ? '1' : '0')
        . " righe={$initialized_count} somma={$final_total} atteso={$total_initial_prizes}");

    return $initialized_count;
}

function getDailyRemaining($date) {
    global $c_pdo;
    /** @var PDO $c_pdo */

    if (!isDateInContestRange($date)) {
        return 0;
    }

    initDailyTargetIfNeeded($date);

    $q = $c_pdo->prepare(IW_SQL_QUOTA_DAILY_SELECT);
    $q->execute([':d' => $date]);
    $row = $q->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        return 0;
    }

    $target  = (int)$row['target'];
    $assigned = (int)$row['assigned'];

    // ✅ clamp target >= assigned
    if ($target < $assigned) {
        $target = $assigned;
    }

    $remaining = $target - $assigned;

    // ✅ CATCH-UP: Se è l'ultimo giorno e ci sono ancora premi disponibili, permettere sforamento
    $contest_dates = getContestDates();
    if ($contest_dates && $date === $contest_dates['end_date']) {
        $prizes_remaining = getTotalPrizesAvailable();
        if ($prizes_remaining > 0) {
            // Permettere di distribuire tutti i premi restanti, anche se supera il target
            $remaining = max($remaining, $prizes_remaining);
        }
    }

    if (defined('MAX_DAILY_TARGET') && MAX_DAILY_TARGET > 0) {
        $max_remaining = MAX_DAILY_TARGET - $assigned;
        $remaining = min($remaining, $max_remaining);
    }

    return max(0, $remaining);
}

function getDailyRemaining_OLD_BACKUP($date) {
    global $c_pdo;
    /** @var PDO $c_pdo */

    if (!isDateInContestRange($date)) {
        return 0;
    }

    initDailyTargetIfNeeded($date);

    $q = $c_pdo->prepare(IW_SQL_QUOTA_DAILY_SELECT);
    $q->execute([':d' => $date]);
    $row = $q->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        return 0;
    }

    $target  = (int)$row['target'];
    $assigned = (int)$row['assigned'];

    // ✅ clamp target >= assigned
    if ($target < $assigned) {
        $target = $assigned;
    }

    $remaining = $target - $assigned;

    if (defined('MAX_DAILY_TARGET') && MAX_DAILY_TARGET > 0) {
        $max_remaining = MAX_DAILY_TARGET - $assigned;
        $remaining = min($remaining, $max_remaining);
    }

    return max(0, $remaining);
}

function getDailyRemaining_OLD($date) {
    global $c_pdo;
    /** @var PDO $c_pdo */
    
    // Verifica di sicurezza: se la data non è nel range del concorso, restituire 0
    if (!isDateInContestRange($date)) {
        return 0;
    }
    
    // MIGLIORAMENTO: Ricalcolare dinamicamente il quota per questa data
    // Questo garantisce che il quota sia sempre aggiornato secondo l'avanzamento reale
    $dynamic_target = initDailyTargetIfNeeded($date);
    
    // Logica ibrida sempre attiva: calcolo quota rimanente
    createDailyQuotaTable();
    $q = $c_pdo->prepare(IW_SQL_QUOTA_DAILY_SELECT);
    $q->execute([':d'=>$date]);
    $row = $q->fetch();
    if (!$row) {
        return 0; // Fallback
    }
    
    // Utilizzare il target dinamico ricalcolato (che può essere diverso dal target nel DB)
    $current_target = max((int)$dynamic_target, (int)$row['target']);
    $assigned = (int)$row['assigned'];
    $remaining = $current_target - $assigned;
    
    // IMPORTANTE: Se è l'ultimo giorno e restano premi, permettere di superare il target
    $contest_dates = getContestDates();
    if ($contest_dates && $date === $contest_dates['end_date']) {
        // Ultimo giorno: verificare i premi rimanenti globali
        $prizes_remaining = getTotalPrizesAvailable();
        if ($prizes_remaining > 0) {
            // Permettere di distribuire tutti i premi rimanenti, anche se supera il target
            $remaining = max($remaining, $prizes_remaining);
        }
    }
    
    // Se MAX_DAILY_TARGET è definito, applicare limite
    if (defined('MAX_DAILY_TARGET') && MAX_DAILY_TARGET > 0) {
        $max_remaining = MAX_DAILY_TARGET - $assigned;
        return max(0, min($remaining, $max_remaining));
    }
    
    return max(0, $remaining);
}

function getContestDayNumber($date) {
    $contest_dates = getContestDates();
    if (!$contest_dates) {
        error_log("getContestDayNumber: contest_dates non definito per data $date, ritorno giorno 1");
        return 1;
    }
    
    $tz = new DateTimeZone(IW_TIMEZONE_EUROPE_ROME);
    $check_date = new DateTime($date, $tz);
    $start_date = new DateTime($contest_dates['start_date'], $tz);
    
    // IMPORTANTE: Contare solo i giorni di distribuzione (non tutti i giorni del calendario)
    $distribution_days = instantwin_get_distribution_days();
    $day_number = 1;
    $current = clone $start_date;
    $current->setTime(0, 0, 0);
    $check_date_only = (clone $check_date)->setTime(0, 0, 0);
    
    while ($current < $check_date_only) {
        $day_of_week = (int)$current->format('N');
        if (in_array($day_of_week, $distribution_days)) {
            $day_number++;
        }
        $current->modify(IW_DATE_INTERVAL_ONE_DAY);
    }
    
    return $day_number;
}

function calculateFirstSlotTimestamp($date, $start_hour) {
    // Offset progressivo sempre attivo (INSTANTWIN_ENABLE_ADVANCED_SLOTS sempre true)
    $day_number = getContestDayNumber($date);
    $seconds_offset = min(($day_number - 1) * 30, MAX_FIRST_SLOT_OFFSET_SECONDS);
    $d = new DateTime($date);
    $d->setTime($start_hour, 0, 0);
    $base_timestamp = $d->getTimestamp();
    $first_slot = $base_timestamp + $seconds_offset;
    $max_first_slot = $base_timestamp + (15 * 60);
    if ($first_slot > $max_first_slot) {
        $first_slot = $max_first_slot;
    }
    return $first_slot;
}

if (!function_exists('generateTimeSlotsFirstOffset')) {
    function generateTimeSlotsFirstOffset(string $date, $first_winner_timestamp, int $day_start_timestamp, int $slot_interval): int {
        if ($first_winner_timestamp !== null) {
            return max(0, $first_winner_timestamp - $day_start_timestamp);
        }
        $day_number = getContestDayNumber($date);
        $seconds_offset = min(($day_number - 1) * 30, MAX_FIRST_SLOT_OFFSET_SECONDS ?? 870);
        return min($seconds_offset, (int)floor($slot_interval / 2));
    }
}

if (!function_exists('generateTimeSlotsFillRemaining')) {
    /** @return list<int> */
    function generateTimeSlotsFillRemaining(array $slots, int $first_slot, int $remaining_slots, int $adjusted_interval, int $effective_end_timestamp): array {
        $current_slot = $first_slot;
        for ($i = 0; $i < $remaining_slots; $i++) {
            $current_slot += $adjusted_interval;

            if ($current_slot >= $effective_end_timestamp) {
                $remaining_slots_count = $remaining_slots - $i;
                if ($remaining_slots_count > 0) {
                    $last_slot_before = end($slots);
                    $remaining_time = $effective_end_timestamp - $last_slot_before;
                    $final_interval = (int)floor($remaining_time / ($remaining_slots_count + 1));
                    for ($j = 0; $j < $remaining_slots_count; $j++) {
                        $last_slot_before += $final_interval;
                        if ($last_slot_before < $effective_end_timestamp) {
                            $slots[] = $last_slot_before;
                        }
                    }
                }
                break;
            }

            $slots[] = $current_slot;
        }
        return $slots;
    }
}

if (!function_exists('generateTimeSlotsPadToCount')) {
    /** @param list<int> $slots @return list<int> */
    function generateTimeSlotsPadToCount(array $slots, int $num_slots, int $effective_end_timestamp): array {
        if (count($slots) > $num_slots) {
            return array_slice($slots, 0, $num_slots);
        }
        if (count($slots) >= $num_slots) {
            return $slots;
        }

        $last_slot = end($slots);
        $remaining_count = $num_slots - count($slots);
        $remaining_time = $effective_end_timestamp - $last_slot;
        if ($remaining_time > 0 && $remaining_count > 0) {
            $final_interval = (int)floor($remaining_time / ($remaining_count + 1));
            for ($i = 0; $i < $remaining_count; $i++) {
                $last_slot += $final_interval;
                if ($last_slot < $effective_end_timestamp) {
                    $slots[] = $last_slot;
                }
            }
        }
        return $slots;
    }
}

function generateTimeSlotsForDay($date, $first_winner_timestamp = null) {
    if (!isDateInContestRange($date)) {
        return [];
    }

    $d = new DateTime($date);
    $dow = (int)$d->format('N');
    
    // Verificare se questo giorno è nei giorni di distribuzione
    $distribution_days = instantwin_get_distribution_days();
    if (!in_array($dow, $distribution_days)) {
        return [];
    }

    // Ottenere gli orari per questo giorno
    $time_range = instantwin_get_time_range_for_day($dow);
    $start_hour = (int)$time_range['start'];
    $end_hour = (int)$time_range['end'];

    // Logica ibrida sempre attiva: calcolo slot secondo quota giornaliero
    $num_slots = calculateHybridSlotsPerDay($date, $start_hour, $end_hour);
    
    // ✅ IMPORTANTE: Garantire sempre almeno un minimo di slot pour permettre la génération du calendrier
    // même avec peu de prix
    $hours_range = $end_hour - $start_hour;
    $min_slots_for_calendar = max(1, min($hours_range, 3)); // Au moins 1-3 slots selon la plage horaire
    $num_slots = max($min_slots_for_calendar, $num_slots);
    
    if ($num_slots <= 0) {
        return [];
    }

    // ✅ Utilizzare l'helper standardizzato per calcolare i limiti
    [$day_start_timestamp, $day_end_timestamp] = instantwin_get_day_bounds_ts($date, $start_hour, $end_hour);
    
    // ✅ STRATEGIA: Ridurre la fine per meglio ripartire e garantire il quota (configurabile)
    $reduction_seconds = INSTANTWIN_NORMAL_END_REDUCTION_SECONDS;
    $effective_end_timestamp = $day_end_timestamp - $reduction_seconds;
    if ($effective_end_timestamp <= $day_start_timestamp) {
        $effective_end_timestamp = $day_start_timestamp + $reduction_seconds; // Minimo = riduzione
    }
    
    // Calcolare la durata totale disponibile in secondi (con fine effettiva ridotta)
    $total_duration = $effective_end_timestamp - $day_start_timestamp;
    
    if ($total_duration <= 0) {
        return [];
    }
    
    if ($num_slots === 1) {
        // Un solo slot: posizionarlo al centro della fascia oraria
        $single_slot = $day_start_timestamp + (int)floor($total_duration / 2);
        return [$single_slot];
    }

    // Ripartire gli slot uniformemente su tutta la fascia oraria
    // Calcolare l'intervallo tra ogni slot
    $slot_interval = (int)floor($total_duration / $num_slots);
    
    // Se l'intervallo è troppo piccolo, utilizzare il minimo configurabile
    $min_interval = INSTANTWIN_MIN_SLOT_INTERVAL_SECONDS;
    if ($slot_interval < $min_interval) {
        $slot_interval = $min_interval;
    }

    $first_slot_offset = generateTimeSlotsFirstOffset($date, $first_winner_timestamp, $day_start_timestamp, $slot_interval);
    $first_slot = $day_start_timestamp + $first_slot_offset;
    $slots = [$first_slot];

    $remaining_duration = $effective_end_timestamp - $first_slot;
    $remaining_slots = $num_slots - 1;

    if ($remaining_slots > 0) {
        $adjusted_interval = (int)floor($remaining_duration / $remaining_slots);
        $slots = generateTimeSlotsFillRemaining($slots, $first_slot, $remaining_slots, $adjusted_interval, $effective_end_timestamp);
    }

    $slots = generateTimeSlotsPadToCount($slots, $num_slots, $effective_end_timestamp);
    sort($slots);

    return $slots;
}
function getSlotDuration($num_slots, $day_of_week = null) {
    $default_duration = INSTANTWIN_NORMAL_END_REDUCTION_SECONDS; // Utilizzare la riduzione come durata di default
    if ($num_slots <= 0) {
        return $default_duration;
    }
    
    $time_range = instantwin_get_time_range_for_day($day_of_week ?? (int)date('N'));
    $hours = $time_range['end'] - $time_range['start'];
    if ($hours <= 0) {
        $hours = 24; // Modalità 24/7
    }
    $available_seconds = $hours * 3600;
    $duration = (int)floor($available_seconds / $num_slots);
    $min_interval = INSTANTWIN_MIN_SLOT_INTERVAL_SECONDS;
    return max($min_interval, $duration);
}

function getSlotMargin($num_slots, $day_of_week = null) {
    // Margini sempre calcolati secondo quota giornaliero (logica ibrida sempre attiva)
    $slot_duration = getSlotDuration($num_slots, $day_of_week);
    $calculated_margin = (int)floor($slot_duration / 2);
    
    // Applicare limiti min/max se definiti
    if (defined('MIN_SLOT_MARGIN_SECONDS') && defined('MAX_SLOT_MARGIN_SECONDS')) {
        $calculated_margin = max(MIN_SLOT_MARGIN_SECONDS, min($calculated_margin, MAX_SLOT_MARGIN_SECONDS));
    }
    
    return $calculated_margin;
}

if (!function_exists('calculateDynamicSlotEffectiveEnd')) {
    function calculateDynamicSlotEffectiveEnd(int $now_timestamp, int $day_start_timestamp, int $day_end_timestamp, int $normal_end_timestamp, int $daily_remaining): array {
        $current_hour = (int)date('H', $now_timestamp);
        $normal_end_hour = (int)date('H', $normal_end_timestamp);
        $is_catchup_mode = ($current_hour >= $normal_end_hour && $daily_remaining > 0);
        $effective_end_timestamp = $is_catchup_mode ? $day_end_timestamp : $normal_end_timestamp;

        if ($now_timestamp < $day_start_timestamp) {
            $now_timestamp = $day_start_timestamp;
        }

        return [$now_timestamp, $effective_end_timestamp];
    }
}

if (!function_exists('calculateDynamicSlotWindowBounds')) {
    function calculateDynamicSlotWindowBounds(int $now_timestamp, int $day_start_timestamp, int $effective_end_timestamp, int $next_slot_timestamp, int $daily_remaining): array {
        $window_start = max($now_timestamp, $day_start_timestamp);
        $remaining_after_next = $daily_remaining - 1;

        if ($remaining_after_next > 0) {
            $next_minutes_left = ($effective_end_timestamp - $next_slot_timestamp) / 60;
            if ($next_minutes_left > 0) {
                $next_interval = $next_minutes_left / $remaining_after_next;
                if ($next_interval < 1) {
                    $next_interval = 1;
                }
                $next_slot_end = $next_slot_timestamp + ($next_interval * 60);
                $window_end = min($next_slot_end, $effective_end_timestamp);
            } else {
                $window_end = $effective_end_timestamp;
            }
        } else {
            $window_end = $effective_end_timestamp;
        }

        $min_window_duration = 30;
        if (($window_end - $window_start) < $min_window_duration) {
            $window_end = min($window_start + $min_window_duration, $effective_end_timestamp);
        }

        return [(int)$window_start, (int)min($window_end, $effective_end_timestamp)];
    }
}

/**
 * Calcola dinamicamente il prossimo slot in tempo reale
 * basato sui premi rimanenti e il tempo rimanente nella giornata
 * 
 * Logica dinamica intelligente:
 * - Calcola l'intervalle: (tempo_restante_minuti / premi_rimanenti)
 * - Se un slot non è utilizzato, viene automaticamente reintegrato nel calcolo
 * - Garantisce che tutti i premi siano distribuiti prima della fine della giornata
 * - Funziona con tutte le configurazioni .env (orari personalizzati, giorni, etc.)
 * 
 * Esempio: 30 premi su 10h (600 min) = 20 min per slot
 * Se 12 premi già distribuiti, restano 18 premi su 500 min = 27.78 min per slot
 */
function calculateDynamicSlot($date, $now_timestamp, $day_of_week) {
    $daily_remaining = getDailyRemaining($date);
    if ($daily_remaining <= 0) {
        return false;
    }

    $time_range = instantwin_get_time_range_for_day($day_of_week);
    $start_hour = (int)$time_range['start'];
    $end_hour = (int)$time_range['end'];

    [$day_start_timestamp, $day_end_timestamp] = instantwin_get_day_bounds_ts($date, $start_hour, $end_hour);

    $reduction_seconds = INSTANTWIN_NORMAL_END_REDUCTION_SECONDS;
    $normal_end_timestamp = $day_end_timestamp - $reduction_seconds;
    if ($normal_end_timestamp <= $day_start_timestamp) {
        $normal_end_timestamp = $day_start_timestamp + $reduction_seconds;
    }

    [$now_timestamp, $effective_end_timestamp] = calculateDynamicSlotEffectiveEnd(
        $now_timestamp,
        $day_start_timestamp,
        $day_end_timestamp,
        $normal_end_timestamp,
        $daily_remaining
    );

    if ($now_timestamp >= $effective_end_timestamp) {
        return false;
    }

    $minutes_left = ($effective_end_timestamp - $now_timestamp) / 60;
    if ($minutes_left <= 0) {
        return false;
    }

    $slot_interval_minutes = $minutes_left / $daily_remaining;
    if ($slot_interval_minutes < 1) {
        $slot_interval_minutes = 1;
    }

    $next_slot_timestamp = $now_timestamp + ($slot_interval_minutes * 60);
    if ($next_slot_timestamp > $effective_end_timestamp) {
        $next_slot_timestamp = $effective_end_timestamp;
    }

    [$window_start, $window_end] = calculateDynamicSlotWindowBounds(
        $now_timestamp,
        $day_start_timestamp,
        $effective_end_timestamp,
        (int)$next_slot_timestamp,
        $daily_remaining
    );

    return [
        'slot_timestamp' => (int)$next_slot_timestamp,
        'window_start' => $window_start,
        'window_end' => $window_end,
    ];
}

if (!function_exists('generateRemainingSlotsCalFilter')) {
    /** @return array{0: string, 1: array<string, mixed>} */
    function generateRemainingSlotsCalFilter(string $date, ?string $codice_pdv_cal): array {
        $calBind = [':d' => $date];
        if ($codice_pdv_cal !== null && trim($codice_pdv_cal) !== '') {
            $calBind[':iw_gen_pdv'] = trim($codice_pdv_cal);
            return [' AND codice_pdv = :iw_gen_pdv ', $calBind];
        }
        return [" AND IFNULL(TRIM(codice_pdv), '') = '' ", $calBind];
    }
}

if (!function_exists('generateRemainingSlotsFetchExisting')) {
    /**
     * @return array{0: array<int, true>, 1: list<int>}
     */
    function generateRemainingSlotsFetchExisting(PDO $pdo, string $date, string $calClause, array $calBind): array {
        $stmt_occupied = $pdo->prepare('
            SELECT slot_timestamp
            FROM calendario_time_slot
            WHERE data = :d AND token_vincitore IS NOT NULL
            ' . $calClause . '
        ');
        $stmt_occupied->execute($calBind);
        $occupied_slots = [];
        while ($row = $stmt_occupied->fetch()) {
            $occupied_slots[(int)$row['slot_timestamp']] = true;
        }

        $stmt_existing = $pdo->prepare('
            SELECT slot_timestamp
            FROM calendario_time_slot
            WHERE data = :d
            ' . $calClause . IW_SQL_ORDER_SLOT_TIMESTAMP_ASC . '
        ');
        $stmt_existing->execute($calBind);
        $existing_slots = [];
        while ($row = $stmt_existing->fetch()) {
            $existing_slots[] = (int)$row['slot_timestamp'];
        }

        return [$occupied_slots, $existing_slots];
    }
}

if (!function_exists('generateRemainingSlotsFromTail')) {
    /** @param list<int> $existing_slots @return list<int> */
    function generateRemainingSlotsFromTail(array $existing_unoccupied_in_range, array $existing_slots, int $day_start_ts, int $day_end_ts, int $now, int $slots_to_generate, int $effective_daily_remaining, int $min_interval): array {
        $now_ts = (int) $now;
        $range_start = max($day_start_ts, min($now_ts, $day_end_ts - 1));
        $remaining_span = $day_end_ts - $range_start;
        if ($remaining_span <= 0) {
            return [];
        }

        $interval_seconds = (int) floor($remaining_span / $effective_daily_remaining);
        if ($interval_seconds < $min_interval) {
            $interval_seconds = $min_interval;
        }

        $last_existing = max($existing_unoccupied_in_range);
        $base_ts = max($last_existing + $interval_seconds, $range_start);
        if ($base_ts >= $day_end_ts) {
            return [];
        }

        $slots = [];
        $tail_span = $day_end_ts - $base_ts;
        $step_seconds = (int) floor($tail_span / max(1, $slots_to_generate));
        if ($step_seconds < $min_interval) {
            $step_seconds = $min_interval;
        }
        for ($i = 0; $i < $slots_to_generate; $i++) {
            $slot_ts = $base_ts + ($i * $step_seconds);
            if ($slot_ts >= $day_end_ts) {
                break;
            }
            if (!in_array($slot_ts, $existing_slots, true)) {
                $slots[] = $slot_ts;
            }
        }
        return $slots;
    }
}

if (!function_exists('generateRemainingSlotsFromPlan')) {
    /** @param list<int> $existing_slots @return list<int> */
    function generateRemainingSlotsFromPlan(array $existing_slots, int $day_start_ts, int $day_end_ts, int $slots_to_generate, int $min_interval): array {
        $plan_span = $day_end_ts - $day_start_ts;
        if ($plan_span <= 0) {
            return [];
        }

        $slots = [];
        $step = (int) floor($plan_span / ($slots_to_generate + 1));
        if ($step < $min_interval) {
            $step = $min_interval;
        }
        for ($i = 1; $i <= $slots_to_generate; $i++) {
            $slot_ts = $day_start_ts + ($i * $step);
            if ($slot_ts >= $day_end_ts) {
                break;
            }
            if (!in_array($slot_ts, $existing_slots, true)) {
                $slots[] = $slot_ts;
            }
        }
        return $slots;
    }
}

/**
 * Genera gli slot rimanenti per la giornata in modo dinamico.
 * Densità calcolata sul tempo ancora disponibile nella fascia (da max(inizio fascia, ora) a fine), non sull'intera fascia.
 * @param string $date Data nel formato 'Y-m-d'
 * @param int $now Timestamp attuale
 * @param int $day_start_ts Timestamp di inizio giornata
 * @param int $day_end_ts Timestamp di fine giornata (escluso)
 * @param int $daily_remaining Numero di premi rimanenti da distribuire
 * @param int|null $min_interval_override Se non null, sostituisce INSTANTWIN_MIN_SLOT_INTERVAL_SECONDS (min 30)
 * @return array<int> Lista dei timestamp degli slot
 */
function generateRemainingSlotsDynamic($date, $now, $day_start_ts, $day_end_ts, $daily_remaining, $min_interval_override = null, ?string $codice_pdv_cal = null) {
    global $c_pdo;
    /** @var PDO|null $c_pdo */

    if (!($c_pdo instanceof PDO)) {
        return [];
    }

    [$calClause, $calBind] = generateRemainingSlotsCalFilter($date, $codice_pdv_cal);

    $band_width = $day_end_ts - $day_start_ts;
    if ($band_width <= 0) {
        return [];
    }

    $hours_range = (int)floor($band_width / 3600);
    $min_slots_for_calendar = max(1, min($hours_range, 3));
    $effective_daily_remaining = max($min_slots_for_calendar, $daily_remaining);

    if ($effective_daily_remaining <= 0) {
        return [];
    }

    [$occupied_slots, $existing_slots] = generateRemainingSlotsFetchExisting($c_pdo, $date, $calClause, $calBind);

    $existing_unoccupied_in_range = [];
    foreach ($existing_slots as $slot_ts) {
        if ($slot_ts >= $day_start_ts && $slot_ts < $day_end_ts && !isset($occupied_slots[$slot_ts])) {
            $existing_unoccupied_in_range[] = $slot_ts;
        }
    }

    $slots_to_generate = max(0, $effective_daily_remaining - count($existing_unoccupied_in_range));
    if ($slots_to_generate <= 0) {
        return [];
    }

    $candidate_min = $min_interval_override !== null
        ? max(30, (int) $min_interval_override)
        : INSTANTWIN_MIN_SLOT_INTERVAL_SECONDS;
    $min_interval = max(INSTANTWIN_MIN_SLOT_INTERVAL_SECONDS, $candidate_min);

    if (count($existing_unoccupied_in_range) > 0) {
        return generateRemainingSlotsFromTail(
            $existing_unoccupied_in_range,
            $existing_slots,
            $day_start_ts,
            $day_end_ts,
            $now,
            $slots_to_generate,
            $effective_daily_remaining,
            $min_interval
        );
    }

    return generateRemainingSlotsFromPlan(
        $existing_slots,
        $day_start_ts,
        $day_end_ts,
        $slots_to_generate,
        $min_interval
    );
}

if (!function_exists('checkTimeSlotWindowLegacyCalendarFilter')) {
    /** @return array{pdvCal: string, usePdvCal: bool, calFilter: string, calBindBase: array<string, mixed>} */
    function checkTimeSlotWindowLegacyCalendarFilter(string $current_date): array {
        $pdvCal = '';
        if (isset($GLOBALS['__iw_pdv_cal_prefix']) && is_string($GLOBALS['__iw_pdv_cal_prefix'])) {
            $pdvCal = trim($GLOBALS['__iw_pdv_cal_prefix']);
        }
        $usePdvCal = ($pdvCal !== '');
        $calFilter = $usePdvCal ? ' AND codice_pdv = :iw_slot_pdv ' : " AND IFNULL(TRIM(codice_pdv), '') = '' ";
        $calBindBase = [':d' => $current_date];
        if ($usePdvCal) {
            $calBindBase[':iw_slot_pdv'] = $pdvCal;
        }
        return compact('pdvCal', 'usePdvCal', 'calFilter', 'calBindBase');
    }
}

if (!function_exists('checkTimeSlotWindowLegacyValidateEarly')) {
    function checkTimeSlotWindowLegacyValidateEarly(int $now, string $current_date): bool {
        if (!isDateInContestRange($current_date)) {
            return false;
        }
        $day_of_week = (int)date('N', $now);
        $distribution_days = instantwin_get_distribution_days();
        if (!in_array($day_of_week, $distribution_days, true)) {
            return false;
        }
        $time_range = instantwin_get_time_range_for_day($day_of_week);
        $start_hour = (int)$time_range['start'];
        $end_hour = (int)$time_range['end'];
        if (!($start_hour === 0 && $end_hour === 24)) {
            $hour = (int)date('H', $now);
            if (!instantwin_is_in_time_range($day_of_week, $hour)) {
                return false;
            }
        }
        return true;
    }
}

if (!function_exists('checkTimeSlotWindowLegacyResolveBounds')) {
    /** @return array{day_start_ts: int, day_end_ts: int, normal_end_ts: int, effective_end_ts: int, daily_remaining: int, is_catchup_mode: bool}|false */
    function checkTimeSlotWindowLegacyResolveBounds(int $now, string $current_date, int $start_hour, int $end_hour) {
        [$day_start_ts, $day_end_ts] = instantwin_get_day_bounds_ts($current_date, $start_hour, $end_hour);

        $reduction_seconds = INSTANTWIN_NORMAL_END_REDUCTION_SECONDS;
        $normal_end_ts = $day_end_ts - $reduction_seconds;
        if ($normal_end_ts <= $day_start_ts) {
            $normal_end_ts = $day_start_ts + $reduction_seconds;
        }

        if (array_key_exists('__iw_pdv_daily_rem_override', $GLOBALS) && $GLOBALS['__iw_pdv_daily_rem_override'] !== null) {
            $daily_remaining = (int) $GLOBALS['__iw_pdv_daily_rem_override'];
        } else {
            $daily_remaining = (int) getDailyRemaining($current_date);
        }

        $current_hour = (int)date('H', $now);
        $normal_end_hour = (int)date('H', $normal_end_ts);
        $is_catchup_mode = ($current_hour >= $normal_end_hour && $daily_remaining > 0);
        $effective_end_ts = $is_catchup_mode ? $day_end_ts : $normal_end_ts;

        if ($now < $day_start_ts || $now >= $effective_end_ts) {
            return false;
        }

        if (defined('MAX_DAILY_TARGET') && MAX_DAILY_TARGET > 0 && $daily_remaining <= 0) {
            return false;
        }

        return compact('day_start_ts', 'day_end_ts', 'normal_end_ts', 'effective_end_ts', 'daily_remaining', 'is_catchup_mode');
    }
}

if (!function_exists('checkTimeSlotWindowLegacyLastWinGate')) {
    function checkTimeSlotWindowLegacyLastWinGate(PDO $pdo, int $now, string $current_date, string $calFilter, array $calBindBase): array {
        $stmt_last_win = $pdo->prepare('
            SELECT UNIX_TIMESTAMP(MAX(data_tiraggio)) AS last_win_ts
            FROM calendario_time_slot
            WHERE data = :d AND token_vincitore IS NOT NULL AND data_tiraggio IS NOT NULL
            ' . $calFilter . '
        ');
        $stmt_last_win->execute($calBindBase);
        $row_last = $stmt_last_win->fetch();
        $last_win_ts = ($row_last && $row_last['last_win_ts'] !== null) ? (int) $row_last['last_win_ts'] : null;
        $min_interval = instantwin_effective_min_win_interval_seconds($last_win_ts, $current_date);
        if ($last_win_ts !== null && ($now - $last_win_ts) < $min_interval) {
            return ['blocked' => true, 'row_last' => $row_last, 'min_interval' => $min_interval];
        }
        return ['blocked' => false, 'row_last' => $row_last, 'min_interval' => $min_interval, 'last_win_ts' => $last_win_ts];
    }
}

if (!function_exists('checkTimeSlotWindowLegacyFetchUnoccupiedSlots')) {
    /** @return list<int> */
    function checkTimeSlotWindowLegacyFetchUnoccupiedSlots(PDO $pdo, string $calFilter, array $calBindBase, int $day_start_ts, int $effective_end_ts): array {
        $stmt_existing = $pdo->prepare('
            SELECT slot_timestamp
            FROM calendario_time_slot
            WHERE data = :d AND token_vincitore IS NULL
            ' . $calFilter . IW_SQL_ORDER_SLOT_TIMESTAMP_ASC . '
        ');
        $stmt_existing->execute($calBindBase);
        $existing_unoccupied_slots = [];
        while ($row = $stmt_existing->fetch()) {
            $slot_ts = (int)$row['slot_timestamp'];
            if ($slot_ts >= $day_start_ts && $slot_ts < $effective_end_ts) {
                $existing_unoccupied_slots[] = $slot_ts;
            }
        }
        return $existing_unoccupied_slots;
    }
}

if (!function_exists('checkTimeSlotWindowLegacyInsertNewSlots')) {
    /** @param list<int> $new_slots */
    function checkTimeSlotWindowLegacyInsertNewSlots(PDO $pdo, array $new_slots, string $current_date, int $now, int $day_start_ts, int $effective_end_ts, bool $usePdvCal, string $pdvCal): void {
        if ($new_slots === []) {
            return;
        }
        try {
            if ($usePdvCal) {
                $ins_slots = $pdo->prepare('
                    INSERT IGNORE INTO calendario_time_slot
                    (data, slot_timestamp, finestra_inizio, finestra_fine, token_vincitore, partecipanti_totali, data_tiraggio, codice_pdv)
                    VALUES (:d, :s, :ws, :we, NULL, 0, NULL, :pdv_ins)
                ');
            } else {
                $ins_slots = $pdo->prepare('
                    INSERT IGNORE INTO calendario_time_slot
                    (data, slot_timestamp, finestra_inizio, finestra_fine, token_vincitore, partecipanti_totali, data_tiraggio)
                    VALUES (:d, :s, :ws, :we, NULL, 0, NULL)
                ');
            }
            foreach ($new_slots as $new_slot_ts) {
                $temp_window_start = max($now, $day_start_ts);
                $temp_window_duration = INSTANTWIN_NORMAL_END_REDUCTION_SECONDS;
                $temp_window_end = min($new_slot_ts + $temp_window_duration, $effective_end_ts);
                $execIns = [
                    ':d' => $current_date,
                    ':s' => $new_slot_ts,
                    ':ws' => $temp_window_start,
                    ':we' => $temp_window_end,
                ];
                if ($usePdvCal) {
                    $execIns[':pdv_ins'] = $pdvCal;
                }
                $ins_slots->execute($execIns);
            }
        } catch (Exception $e) {
            error_log("Errore inserimento nuovi slot in checkTimeSlotWindow: " . $e->getMessage());
        }
    }
}

if (!function_exists('checkTimeSlotWindowLegacyComputeWindowStart')) {
    function checkTimeSlotWindowLegacyComputeWindowStart(int $i, int $slot_ts, int $prev, int $t0, int $day_start_ts, int $slot_recovery): int {
        if ($i !== 1) {
            return (int)floor(($prev + $slot_ts) / 2);
        }
        if (instantwin_is_default_full_day_schedule()) {
            $span = max(60, $slot_ts - $t0);
            $back = min((int) floor($span * 0.9), 10 * 3600);
            return max($t0, $slot_ts - $back);
        }
        return max($day_start_ts, $slot_ts - $slot_recovery);
    }
}

if (!function_exists('checkTimeSlotWindowLegacyFindActiveWindow')) {
    /** @param list<int> $all_available_slots @return array<string, int>|false */
    function checkTimeSlotWindowLegacyFindActiveWindow(int $now, int $day_start_ts, int $effective_end_ts, array $all_available_slots) {
        $slot_recovery = max(0, (int) INSTANTWIN_SLOT_RECOVERY_WINDOW_SECONDS);
        $t0 = max($now, $day_start_ts);
        $all = array_merge([$t0], $all_available_slots, [$effective_end_ts]);

        for ($i = 1; $i < count($all) - 1; $i++) {
            $slot_ts = (int)$all[$i];
            $prev = (int)$all[$i - 1];
            $next = (int)$all[$i + 1];

            if ($now < $slot_ts - $slot_recovery) {
                continue;
            }

            $window_start = checkTimeSlotWindowLegacyComputeWindowStart($i, $slot_ts, $prev, $t0, $day_start_ts, $slot_recovery);
            $window_end = (int)floor(($slot_ts + $next) / 2);
            $window_start = max($window_start, $day_start_ts, $slot_ts - $slot_recovery);
            $window_end = min($window_end, $effective_end_ts);

            if ($window_start >= $window_end || $now < $window_start) {
                continue;
            }

            if ($now >= $window_start && $now < $window_end) {
                return [
                    'slot_timestamp' => $slot_ts,
                    'window_start' => $window_start,
                    'window_end' => $window_end,
                    'slots_vides_precedents' => 0,
                ];
            }
        }

        return false;
    }
}

if (!function_exists('checkTimeSlotWindowLegacyWideFallback')) {
    /** @param list<int> $all_available_slots @return array<string, int>|false */
    function checkTimeSlotWindowLegacyWideFallback(int $now, int $day_start_ts, int $effective_end_ts, int $daily_remaining, array $all_available_slots, $row_last, int $min_interval) {
        if (
            !INSTANTWIN_DEFAULT_24H_WIDE_WINDOW_FALLBACK
            || !instantwin_is_default_full_day_schedule()
            || $daily_remaining <= 0
            || $now < $day_start_ts
            || $now >= $effective_end_ts
            || $all_available_slots === []
        ) {
            return false;
        }

        $tr_fb = instantwin_get_time_range_for_day((int) date('N', $now));
        if ((int) $tr_fb['start'] !== 0 || (int) $tr_fb['end'] !== 24) {
            return false;
        }

        $earliest_gate_ts = $day_start_ts;
        if ($row_last && $row_last['last_win_ts'] !== null) {
            $earliest_gate_ts = max($day_start_ts, (int) $row_last['last_win_ts'] + $min_interval);
        }
        if ($now < $earliest_gate_ts) {
            return false;
        }

        $pick_slot = (int) $all_available_slots[0];
        $half = max(300, INSTANTWIN_WIDE_FALLBACK_HALF_SECONDS);
        $half = min($half, 12 * 3600);
        if ($pick_slot > $now + INSTANTWIN_WIDE_FALLBACK_MAX_SLOT_LEAD_SECONDS || $pick_slot > $now) {
            return false;
        }

        $window_start = max($earliest_gate_ts, $pick_slot - $half, $day_start_ts);
        $recovery_cap = max(120, (int) INSTANTWIN_SLOT_RECOVERY_WINDOW_SECONDS);
        $window_end = min(
            $effective_end_ts,
            max($pick_slot + $half, min($now + $recovery_cap, $effective_end_ts))
        );
        if ($window_end <= $window_start) {
            $window_end = min($effective_end_ts, $window_start + 120);
        }
        if ($now >= $window_start && $now < $window_end) {
            return [
                'slot_timestamp' => $pick_slot,
                'window_start' => $window_start,
                'window_end' => $window_end,
                'slots_vides_precedents' => 0,
            ];
        }

        return false;
    }
}

function checkTimeSlotWindow_legacy($timestamp = null) {
    global $c_pdo;
    /** @var PDO $c_pdo */

    $now = $timestamp !== null ? (int)$timestamp : time();
    $current_date = date('Y-m-d', $now);

    extract(checkTimeSlotWindowLegacyCalendarFilter($current_date));

    if (!checkTimeSlotWindowLegacyValidateEarly($now, $current_date)) {
        return false;
    }

    $day_of_week = (int)date('N', $now);
    $time_range = instantwin_get_time_range_for_day($day_of_week);
    $start_hour = (int)$time_range['start'];
    $end_hour = (int)$time_range['end'];

    $bounds = checkTimeSlotWindowLegacyResolveBounds($now, $current_date, $start_hour, $end_hour);
    if ($bounds === false) {
        return false;
    }
    extract($bounds);

    $lastWin = checkTimeSlotWindowLegacyLastWinGate($c_pdo, $now, $current_date, $calFilter, $calBindBase);
    if ($lastWin['blocked']) {
        return false;
    }

    if ($is_catchup_mode) {
        $new_slots = generateRemainingSlotsDynamic($current_date, $now, $normal_end_ts, $day_end_ts, $daily_remaining, null, $usePdvCal ? $pdvCal : null);
    } else {
        $new_slots = generateRemainingSlotsDynamic($current_date, $now, $day_start_ts, $normal_end_ts, $daily_remaining, null, $usePdvCal ? $pdvCal : null);
    }

    checkTimeSlotWindowLegacyInsertNewSlots($c_pdo, $new_slots, $current_date, $now, $day_start_ts, $effective_end_ts, $usePdvCal, $pdvCal);

    $existing_unoccupied_slots = checkTimeSlotWindowLegacyFetchUnoccupiedSlots($c_pdo, $calFilter, $calBindBase, $day_start_ts, $effective_end_ts);
    $all_available_slots = array_unique(array_merge($existing_unoccupied_slots, $new_slots));
    sort($all_available_slots);

    if ($all_available_slots === []) {
        return false;
    }

    $active = checkTimeSlotWindowLegacyFindActiveWindow($now, $day_start_ts, $effective_end_ts, $all_available_slots);
    if ($active !== false) {
        return $active;
    }

    return checkTimeSlotWindowLegacyWideFallback(
        $now,
        $day_start_ts,
        $effective_end_ts,
        $daily_remaining,
        $all_available_slots,
        $lastWin['row_last'],
        $lastWin['min_interval']
    );
}
if (!function_exists('instantwin_email_sending_enabled')) {
    function instantwin_email_sending_enabled(string $type): bool
    {
        if (!function_exists('should_send_email_type')) {
            $emailSystemPath = __DIR__ . '/../../../public/assets/mail/email_system.php';
            if (is_file($emailSystemPath)) {
                require_once $emailSystemPath;
            }
        }
        return function_exists('should_send_email_type') && should_send_email_type($type);
    }
}

if (!function_exists('instantwin_is_auto_prize_with_receipt_flow')) {
    function instantwin_is_auto_prize_with_receipt_flow(): bool
    {
        global $contest_config;
        if (!is_array($contest_config ?? null)) {
            return false;
        }
        $fields = $contest_config['fields_enabled'] ?? [];
        $flag = static function ($value, bool $default = true): bool {
            if ($value === null) {
                return $default;
            }
            $parsed = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            return $parsed === null ? $default : $parsed;
        };
        $require_receipt = $flag($fields['scontrino'] ?? null, true);
        $auto_prize_with_receipt = $flag($contest_config['instantwin_auto_prize_with_receipt'] ?? null, false);
        return $require_receipt && $auto_prize_with_receipt;
    }
}

if (!function_exists('instantwin_is_manager_confirm_flow_enabled')) {
    /** Flusso IW con conferma manager (email « Vincita in attesa di conferma » attiva, senza auto-premio scontrino). */
    function instantwin_is_manager_confirm_flow_enabled(): bool
    {
        if (instantwin_is_auto_prize_with_receipt_flow()) {
            return false;
        }
        return instantwin_email_sending_enabled('instantwin_confirm');
    }
}

if (!function_exists('instantwin_is_manager_cancel_flow_enabled')) {
    /** Annullamento vincita da manager (email « Annullamento » attiva, senza auto-premio scontrino). */
    function instantwin_is_manager_cancel_flow_enabled(): bool
    {
        if (instantwin_is_auto_prize_with_receipt_flow()) {
            return false;
        }
        return instantwin_email_sending_enabled('instantwin_cancel');
    }
}

/**
 * Il codice voucher non va consumato a vincita se:
 * - è attiva l'email « Vincita in attesa di conferma » (conferma manager), oppure
 * - scontrino obbligatorio senza invio premio automatico (flusso legacy).
 */
function instantwin_should_defer_voucher_code_until_manager_confirm(): bool
{
    if (instantwin_is_auto_prize_with_receipt_flow()) {
        return false;
    }
    if (instantwin_is_manager_confirm_flow_enabled()) {
        return true;
    }
    global $contest_config;
    if (!is_array($contest_config ?? null)) {
        return false;
    }
    $fields = $contest_config['fields_enabled'] ?? [];
    $flag = static function ($value, bool $default = true): bool {
        if ($value === null) {
            return $default;
        }
        $parsed = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        return $parsed === null ? $default : $parsed;
    };
    $require_receipt = $flag($fields['scontrino'] ?? null, true);
    $auto_prize_with_receipt = $flag($contest_config['instantwin_auto_prize_with_receipt'] ?? null, false);
    return $require_receipt && !$auto_prize_with_receipt;
}

if (!function_exists('instantwin_return_existing_voucher_assignment')) {
    function instantwin_return_existing_voucher_assignment(PDO $pdo, int $existingCp): array {
        $q = $pdo->prepare('SELECT codice, pdf_path FROM codici_premio WHERE id = ? LIMIT 1');
        $q->execute([$existingCp]);
        $r = $q->fetch(PDO::FETCH_ASSOC) ?: [];
        $r = array_change_key_case($r, CASE_LOWER);
        return [
            'codice' => isset($r['codice']) ? trim((string) $r['codice']) : null,
            'pdf_path' => !empty($r['pdf_path']) ? trim((string) $r['pdf_path']) : null,
            'codice_premio_id' => $existingCp,
        ];
    }
}

if (!function_exists('instantwin_resolve_id_codice_partecipazione')) {
    function instantwin_resolve_id_codice_partecipazione(PDO $pdo, string $codicePartStr): ?int {
        if ($codicePartStr === '') {
            return null;
        }
        $s = $pdo->prepare('SELECT id FROM codici_partecipazione WHERE codice = ? LIMIT 1');
        $s->execute([$codicePartStr]);
        $idCodicePartecipazione = (int) ($s->fetchColumn() ?: 0);
        return $idCodicePartecipazione > 0 ? $idCodicePartecipazione : null;
    }
}

if (!function_exists('instantwin_claim_voucher_from_pool')) {
    function instantwin_claim_voucher_from_pool(PDO $pdo, int $participationId, int $prizeId, ?int $idCodicePartecipazione): array {
        $stmtPool = $pdo->prepare('
            SELECT id, codice, pdf_path
            FROM codici_premio
            WHERE prize_id = ?
              AND used = 0
              AND (isActive = 1 OR isActive IS NULL)
              AND id_codice_partecipazione IS NULL
            LIMIT 1
            FOR UPDATE
        ');
        $stmtPool->execute([$prizeId]);
        $row = $stmtPool->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            throw new RuntimeException('Nessun codice premio disponibile per questo premio.');
        }
        $row = array_change_key_case($row, CASE_LOWER);
        $codicePremioId = (int) $row['id'];

        $upd = $pdo->prepare('
            UPDATE codici_premio
            SET id_codice_partecipazione = :id_cp,
                id_partecipazione = :id_part,
                used = 1,
                isActive = 0,
                data_assegnazione = NOW()
            WHERE id = :cid
        ');
        $upd->execute([
            ':id_cp' => $idCodicePartecipazione,
            ':id_part' => $participationId,
            ':cid' => $codicePremioId,
        ]);

        $updPart = $pdo->prepare('UPDATE partecipazioni SET id_codice_premio = ? WHERE id = ?');
        $updPart->execute([$codicePremioId, $participationId]);

        require_once __DIR__ . '/../prize_image_generator.php';
        prize_image_try_generate_after_assign(
            $pdo,
            $codicePremioId,
            $prizeId,
            trim((string) ($row['codice'] ?? ''))
        );

        return [
            'codice' => trim((string) ($row['codice'] ?? '')),
            'pdf_path' => !empty($row['pdf_path']) ? trim((string) $row['pdf_path']) : null,
            'codice_premio_id' => $codicePremioId,
        ];
    }
}

/**
 * Assegna un codice da codici_premio al momento della conferma manager (vincita già registrata, senza id_codice_premio).
 *
 * @return array{codice: ?string, pdf_path: ?string}
 */
function instantwin_assign_voucher_code_on_manager_confirm(PDO $pdo, int $participationId): array {
    $stmt = $pdo->prepare('
        SELECT id, prize_id, codice_partecipazione, id_codice_premio, has_won
        FROM partecipazioni
        WHERE id = ?
        FOR UPDATE
    ');
    $stmt->execute([$participationId]);
    $part = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$part) {
        throw new RuntimeException('Partecipazione non trovata.');
    }
    $part = array_change_key_case($part, CASE_LOWER);

    if ((int) ($part['has_won'] ?? 0) !== 1) {
        throw new RuntimeException('Partecipazione non vincente.');
    }

    $existingCp = !empty($part['id_codice_premio']) ? (int) $part['id_codice_premio'] : 0;
    if ($existingCp > 0) {
        return instantwin_return_existing_voucher_assignment($pdo, $existingCp);
    }

    $prizeId = (int) ($part['prize_id'] ?? 0);
    if ($prizeId <= 0) {
        throw new RuntimeException('Premio non associato alla partecipazione.');
    }

    $codicePartStr = isset($part['codice_partecipazione']) ? trim((string) $part['codice_partecipazione']) : '';
    $idCodicePartecipazione = instantwin_resolve_id_codice_partecipazione($pdo, $codicePartStr);

    return instantwin_claim_voucher_from_pool($pdo, $participationId, $prizeId, $idCodicePartecipazione);
}

require_once __DIR__ . '/engine.php';

if (!function_exists('instantwin_first_fascia_random_enabled')) {
    function instantwin_first_fascia_random_enabled(): bool
    {
        return env_bool('INSTANTWIN_FIRST_FASCIA_RANDOM_ENABLED', true);
    }
}

if (!function_exists('instantwin_fascia_participants_count_today')) {
    function instantwin_fascia_participants_count_today(PDO $pdo, string $date, string $pdvKey = ''): int
    {
        instantwin_ensure_calendario_pdv_column();
        [$calFilter, $calBindExtra] = instantwin_slot_cal_filter($pdvKey);
        $calBind = array_merge([':d' => $date], $calBindExtra);
        $stmt = $pdo->prepare(
            'SELECT COALESCE(SUM(partecipanti_totali), 0) FROM calendario_time_slot WHERE data = :d' . IW_SQL_CONCAT_SPACE . $calFilter
        );
        $stmt->execute($calBind);

        return (int) $stmt->fetchColumn();
    }
}

if (!function_exists('instantwin_first_fascia_win_probability')) {
    function instantwin_first_fascia_win_probability(PDO $pdo, string $date, string $pdvKey = ''): float
    {
        unset($pdo, $date, $pdvKey);

        $fixedRaw = trim((string) getenv('INSTANTWIN_FIRST_FASCIA_WIN_PROBABILITY'));
        if ($fixedRaw !== '' && is_numeric($fixedRaw)) {
            return max(0.0, min(1.0, (float) $fixedRaw));
        }

        // Sans override : 50% win / 50% lose ad ogni tentativo di claim sullo slot.
        return 0.5;
    }
}

if (!function_exists('instantwin_roll_first_fascia_outcome')) {
    function instantwin_roll_first_fascia_outcome(string $token, string $date, int $slotTs, float $probability): bool
    {
        unset($token, $date, $slotTs);

        if ($probability <= 0.0) {
            return false;
        }
        if ($probability >= 1.0) {
            return true;
        }

        $roll = random_int(0, 999999) / 1000000.0;

        return $roll < $probability;
    }
}

if (!function_exists('instantwin_should_block_first_fascia_claim')) {
    /**
     * Tirage casuale win/lose su ogni tentativo di claim (slot aperto).
     * Se disabilitato: nessun blocco → claim FCFS.
     *
     * @param array{slot_timestamp: int} $slotWindow
     */
    function instantwin_should_block_first_fascia_claim(
        PDO $pdo,
        string $token,
        string $date,
        array $slotWindow,
        int $participantsBeforeIncrement,
        string $pdvKey = ''
    ): bool {
        unset($participantsBeforeIncrement);

        if (!instantwin_first_fascia_random_enabled()) {
            return false;
        }

        $probability = instantwin_first_fascia_win_probability($pdo, $date, $pdvKey);
        $winsRoll = instantwin_roll_first_fascia_outcome(
            $token,
            $date,
            (int) ($slotWindow['slot_timestamp'] ?? 0),
            $probability
        );

        if (!$winsRoll) {
            error_log(
                'instantwin_slot_claim_random: perdita casuale '
                . $date
                . ($pdvKey !== '' ? ' pdv=' . $pdvKey : '')
                . ' (p=' . round($probability, 4) . ')'
            );

            return true;
        }

        error_log(
            'instantwin_slot_claim_random: vincita casuale '
            . $date
            . ($pdvKey !== '' ? ' pdv=' . $pdvKey : '')
            . ' (p=' . round($probability, 4) . ') — claim FCFS'
        );

        return false;
    }
}

/**
 * Point d'entrée fenêtre slot : moteur v2 (défaut) ou legacy (.env INSTANTWIN_ENGINE=legacy).
 */
function checkTimeSlotWindow($timestamp = null)
{
    if (instantwin_engine_is_v2()) {
        return checkTimeSlotWindow_v2($timestamp);
    }
    return checkTimeSlotWindow_legacy($timestamp);
}

// ============================================
// INSTANT WIN GLOBAL — assignPrize + checkInstantWin
// ============================================

if (!function_exists('assignPrizeSelectLockedPrize')) {
    function assignPrizeSelectLockedPrize(PDO $pdo): ?array {
        $prize_mode = strtolower(trim((string) (getenv('INSTANTWIN_PRIZE_SELECTION_MODE') ?: 'highest_value')));
        $prize_mode = in_array($prize_mode, ['highest_value', 'lowest_id', 'random'], true) ? $prize_mode : 'highest_value';
        $order_by = match ($prize_mode) {
            'lowest_id' => 'id ASC',
            'random' => 'RAND()',
            default => 'valore DESC, id ASC',
        };
        $stmt_prize = $pdo->prepare("
            SELECT id, valore
            FROM premi
            WHERE quantita_assegnati < quantita_totale
            ORDER BY {$order_by}
            LIMIT 1
            FOR UPDATE
        ");
        $stmt_prize->execute();
        $prize = $stmt_prize->fetch();
        return $prize ?: null;
    }
}

if (!function_exists('assignPrizeResolveWinContext')) {
    /** @return array{participation_id: ?int, codice_partecipazione_value: mixed, win_date: string, participation_date: string}|false */
    function assignPrizeResolveWinContext(PDO $pdo, string $token, $simulated_timestamp) {
        $stmt_part = $pdo->prepare('SELECT id, data_partecipazione, codice_partecipazione FROM partecipazioni WHERE token = ' . IW_SQL_BIND_TOKEN);
        $stmt_part->bindValue(IW_SQL_BIND_TOKEN, $token, PDO::PARAM_STR);
        $stmt_part->execute();
        $part = $stmt_part->fetch();
        $participation_id = $part ? (int)$part['id'] : null;
        $codice_partecipazione_value = $part ? ($part['codice_partecipazione'] ?? null) : null;

        if ($simulated_timestamp !== null) {
            $win_date = date('Y-m-d H:i:s', $simulated_timestamp);
            $participation_date = date('Y-m-d', $simulated_timestamp);
        } elseif ($part && !empty($part['data_partecipazione'])) {
            $win_date = $part['data_partecipazione'];
            $participation_date = (new DateTime($part['data_partecipazione']))->format('Y-m-d');
        } else {
            $win_date = date('Y-m-d H:i:s');
            $participation_date = date('Y-m-d');
        }

        if (!isDateInContestRange($participation_date)) {
            error_log("assignPrize: tentativo di assegnare premio per data fuori range: $participation_date");
            return false;
        }

        return compact('participation_id', 'codice_partecipazione_value', 'win_date', 'participation_date');
    }
}

if (!function_exists('assignPrizeIncrementDailyQuota')) {
    function assignPrizeIncrementDailyQuota(PDO $pdo, string $participation_date, bool $allow_catchup): bool {
        $apply_max_daily_limit = (defined('MAX_DAILY_TARGET') && MAX_DAILY_TARGET > 0);

        if ($allow_catchup) {
            if ($apply_max_daily_limit) {
                $stmt_quota = $pdo->prepare('
                    UPDATE quota_giornaliera
                    SET assigned = assigned + 1
                    WHERE data = :date AND assigned < :max_daily
                ');
                $stmt_quota->execute([':date' => $participation_date, ':max_daily' => MAX_DAILY_TARGET]);
            } else {
                $stmt_quota = $pdo->prepare('
                    UPDATE quota_giornaliera
                    SET assigned = assigned + 1
                    WHERE data = :date
                ');
                $stmt_quota->execute([':date' => $participation_date]);
            }
        } elseif ($apply_max_daily_limit) {
            $stmt_quota = $pdo->prepare('
                UPDATE quota_giornaliera
                SET assigned = assigned + 1
                WHERE data = :date
                  AND assigned < target
                  AND assigned < :max_daily
            ');
            $stmt_quota->execute([':date' => $participation_date, ':max_daily' => MAX_DAILY_TARGET]);
        } else {
            $stmt_quota = $pdo->prepare('
                UPDATE quota_giornaliera
                SET assigned = assigned + 1
                WHERE data = :date AND assigned < target
            ');
            $stmt_quota->execute([':date' => $participation_date]);
        }

        return $stmt_quota->rowCount() === 1;
    }
}

if (!function_exists('assignPrizeFinalizePrizeStock')) {
    function assignPrizeFinalizePrizeStock(PDO $pdo, int $prize_id): void {
        $stmt_update_prize = $pdo->prepare('
            UPDATE premi
            SET quantita_assegnati = quantita_assegnati + 1,
                is_active = CASE WHEN quantita_assegnati >= quantita_totale THEN 0 ELSE 1 END
            WHERE id = :prize_id
        ');
        $stmt_update_prize->execute([':prize_id' => $prize_id]);
    }
}

if (!function_exists('assignPrizeCompleteDeferredWin')) {
    function assignPrizeCompleteDeferredWin(PDO $pdo, array $prize, string $token, string $win_date): array|false {
        $stmt_stock = $pdo->prepare('
            SELECT COUNT(*) FROM codici_premio
            WHERE prize_id = :prize_id AND used = 0 AND (isActive = 1 OR isActive IS NULL) AND id_codice_partecipazione IS NULL
        ');
        $stmt_stock->bindValue(':prize_id', $prize['id'], PDO::PARAM_INT);
        $stmt_stock->execute();
        if ((int) $stmt_stock->fetchColumn() < 1) {
            error_log('assignPrize: defer voucher — nessun codice_premio disponibile per prize_id ' . $prize['id']);
            return false;
        }

        $stmt_participation = $pdo->prepare('
            UPDATE partecipazioni
            SET has_won = 1, prize_id = :prize_id, prize_amount = :prize_value,
                win_date = :win_date, annullato = 0, confermato = 0,
                id_codice_premio = NULL
            WHERE token = ' . IW_SQL_BIND_TOKEN . '
        ');
        $stmt_participation->execute([
            ':prize_id' => $prize['id'],
            ':prize_value' => $prize['valore'],
            ':win_date' => $win_date,
            IW_SQL_BIND_TOKEN => $token,
        ]);

        assignPrizeFinalizePrizeStock($pdo, (int) $prize['id']);

        return [
            'has_won' => true,
            'prize_id' => $prize['id'],
            'prize_value' => $prize['valore'],
            'prize_type' => 'Carta Prepagata Fisica',
            'codice_premio_id' => null,
            'codice_premio' => null,
            'pdf_path' => null,
        ];
    }
}

if (!function_exists('assignPrizeCompleteImmediateWin')) {
    function assignPrizeCompleteImmediateWin(PDO $pdo, array $prize, string $token, string $win_date, ?int $participation_id, $codice_partecipazione_value): array|false {
        $id_codice_partecipazione = null;
        if ($codice_partecipazione_value) {
            $stmt_cp = $pdo->prepare('SELECT id FROM codici_partecipazione WHERE codice = :codice LIMIT 1');
            $stmt_cp->bindValue(':codice', $codice_partecipazione_value, PDO::PARAM_STR);
            $stmt_cp->execute();
            $cp_row = $stmt_cp->fetch();
            $id_codice_partecipazione = $cp_row ? (int)$cp_row['id'] : null;
        }

        $stmt_codice_premio = $pdo->prepare('
            SELECT id, codice, pdf_path
            FROM codici_premio
            WHERE prize_id = :prize_id
              AND used = 0
              AND (isActive = 1 OR isActive IS NULL)
              AND id_codice_partecipazione IS NULL
            LIMIT 1
            FOR UPDATE
        ');
        $stmt_codice_premio->bindValue(':prize_id', $prize['id'], PDO::PARAM_INT);
        $stmt_codice_premio->execute();
        $codice_premio_row = $stmt_codice_premio->fetch();

        if (!$codice_premio_row) {
            error_log("assignPrize: Nessun codice_premio disponibile per prize_id " . $prize['id']);
            return false;
        }

        $codice_premio_id = (int) $codice_premio_row['id'];
        $codice_premio_code = $codice_premio_row['codice'];
        $codice_premio_pdf_path = !empty($codice_premio_row['pdf_path']) ? trim((string) $codice_premio_row['pdf_path']) : null;

        $stmt_update_codice_premio = $pdo->prepare('
            UPDATE codici_premio
            SET id_codice_partecipazione = :id_codice_partecipazione,
                id_partecipazione = :id_partecipazione,
                used = 1,
                isActive = 0,
                data_assegnazione = NOW()
            WHERE id = :codice_premio_id
        ');
        $stmt_update_codice_premio->bindValue(':id_codice_partecipazione', $id_codice_partecipazione, PDO::PARAM_INT);
        $stmt_update_codice_premio->bindValue(':id_partecipazione', $participation_id, PDO::PARAM_INT);
        $stmt_update_codice_premio->bindValue(':codice_premio_id', $codice_premio_id, PDO::PARAM_INT);
        $stmt_update_codice_premio->execute();

        require_once __DIR__ . '/../prize_image_generator.php';
        prize_image_try_generate_after_assign(
            $pdo,
            $codice_premio_id,
            (int) $prize['id'],
            trim((string) $codice_premio_code)
        );

        $stmt_participation = $pdo->prepare('
            UPDATE partecipazioni
            SET has_won = 1, prize_id = :prize_id, prize_amount = :prize_value,
                win_date = :win_date, annullato = 0, confermato = 0,
                id_codice_premio = :id_codice_premio
            WHERE token = ' . IW_SQL_BIND_TOKEN . '
        ');
        $stmt_participation->execute([
            ':prize_id' => $prize['id'],
            ':prize_value' => $prize['valore'],
            ':win_date' => $win_date,
            IW_SQL_BIND_TOKEN => $token,
            ':id_codice_premio' => $codice_premio_id,
        ]);

        assignPrizeFinalizePrizeStock($pdo, (int) $prize['id']);

        return [
            'has_won' => true,
            'prize_id' => $prize['id'],
            'prize_value' => $prize['valore'],
            'prize_type' => 'Carta Prepagata Fisica',
            'codice_premio_id' => $codice_premio_id,
            'codice_premio' => $codice_premio_code,
            'pdf_path' => $codice_premio_pdf_path,
        ];
    }
}

function assignPrize($token, $simulated_timestamp = null) {
    global $c_pdo;
    /** @var PDO $c_pdo */
    $already_in_transaction = false;

    try {
        if ($c_pdo->inTransaction()) {
            $already_in_transaction = true;
        } else {
            $c_pdo->beginTransaction();
        }

        $c_pdo->exec('UPDATE premi SET is_active = 1 WHERE quantita_assegnati < quantita_totale');

        $prize = assignPrizeSelectLockedPrize($c_pdo);
        if (!$prize) {
            if (!$already_in_transaction && $c_pdo->inTransaction()) {
                $c_pdo->rollBack();
            }
            return INSTANTWIN_NO_WIN;
        }

        $context = assignPrizeResolveWinContext($c_pdo, $token, $simulated_timestamp);
        if ($context === false) {
            if (!$already_in_transaction && $c_pdo->inTransaction()) {
                $c_pdo->rollBack();
            }
            return INSTANTWIN_NO_WIN;
        }
        extract($context);

        createDailyQuotaTable();

        $contest_dates = getContestDates();
        $is_last_day = ($contest_dates && $participation_date === $contest_dates['end_date']);
        $force_recalculate = ($participation_date === date('Y-m-d')) || $is_last_day;
        initDailyTargetIfNeeded($participation_date, null, $force_recalculate);

        $allow_catchup = $is_last_day && getTotalPrizesAvailable() > 0;
        if (!assignPrizeIncrementDailyQuota($c_pdo, $participation_date, $allow_catchup)) {
            if (!$already_in_transaction && $c_pdo->inTransaction()) {
                $c_pdo->rollBack();
            }
            return INSTANTWIN_NO_WIN;
        }

        if (instantwin_should_defer_voucher_code_until_manager_confirm()) {
            $result = assignPrizeCompleteDeferredWin($c_pdo, $prize, $token, $win_date);
            if ($result === false) {
                if (!$already_in_transaction && $c_pdo->inTransaction()) {
                    $c_pdo->rollBack();
                }
                return INSTANTWIN_NO_WIN;
            }
            if (!$already_in_transaction && $c_pdo->inTransaction()) {
                $c_pdo->commit();
            }
            return $result;
        }

        $result = assignPrizeCompleteImmediateWin(
            $c_pdo,
            $prize,
            $token,
            $win_date,
            $participation_id,
            $codice_partecipazione_value
        );
        if ($result === false) {
            if (!$already_in_transaction && $c_pdo->inTransaction()) {
                $c_pdo->rollBack();
            }
            return INSTANTWIN_NO_WIN;
        }

        if (!$already_in_transaction && $c_pdo->inTransaction()) {
            $c_pdo->commit();
        }
        return $result;
    } catch (Exception $e) {
        if (!$already_in_transaction && $c_pdo->inTransaction()) {
            $c_pdo->rollBack();
        }
        error_log("Errore assignPrize: " . $e->getMessage());
        return INSTANTWIN_NO_WIN;
    }
}
if (!function_exists('checkInstantWinValidateEntry')) {
    /** @return array{check_time: int, current_date: string, day_of_week: int}|false */
    function checkInstantWinValidateEntry(string $token, $simulated_timestamp) {
        global $contest_mode;
        if (!isset($contest_mode)) {
            require_once __DIR__ . '/../../inc/config.php';
        }
        if (!isset($contest_mode) || $contest_mode !== 'instantwin') {
            error_log("checkInstantWin: contest_mode non è 'instantwin' (valore: " . ($contest_mode ?? 'non definito') . ") - rifiuto");
            return false;
        }

        createTimeSlotsTable();
        createDailyQuotaTable();
        instantwin_ensure_calendario_pdv_column();

        if (instantwin_test_direct_loss_enabled()) {
            error_log('checkInstantWin: INSTANTWIN_TEST_OUTCOME_MODE=direct_loss — perdita forzata (solo test)');
            return false;
        }

        $check_time = $simulated_timestamp !== null ? $simulated_timestamp : time();
        $current_date = date('Y-m-d', $check_time);
        $day_of_week = (int)date('N', $check_time);

        if (!isDateInContestRange($current_date)) {
            error_log("checkInstantWin: data $current_date non nel range di distribuzione - rifiuto immediato");
            return false;
        }

        $distribution_days = instantwin_get_distribution_days();
        if (!in_array($day_of_week, $distribution_days)) {
            return false;
        }

        $prizes_info = checkPrizesAvailable();
        if (!$prizes_info['available'] || $prizes_info['quantity'] <= 0) {
            return false;
        }
        if (!instantwin_allow_multiple_wins() && hasEmailAlreadyWon($token)) {
            return false;
        }

        $time_range = instantwin_get_time_range_for_day($day_of_week);
        if (!($time_range['start'] == 0 && $time_range['end'] == 24)) {
            if (!isPrizeDistributionTime($check_time)) {
                return false;
            }
        }

        return compact('check_time', 'current_date', 'day_of_week');
    }
}

if (!function_exists('checkInstantWinIncrementSlotParticipants')) {
    function checkInstantWinIncrementSlotParticipants(PDO $pdo, string $current_date, array $slot_window): void {
        try {
            $ins_participants = $pdo->prepare('
                INSERT INTO calendario_time_slot
                (data, slot_timestamp, finestra_inizio, finestra_fine, token_vincitore, partecipanti_totali, data_tiraggio, codice_pdv)
                VALUES (:d,:s,:ws,:we,NULL,1,NULL, \'\')
                ON DUPLICATE KEY UPDATE partecipanti_totali = partecipanti_totali + 1
            ');
            $ins_participants->execute([
                ':d' => $current_date,
                ':s' => $slot_window['slot_timestamp'],
                ':ws' => $slot_window['window_start'],
                ':we' => $slot_window['window_end'],
            ]);
        } catch (Exception $e) {
            try {
                $ins_participants = $pdo->prepare('
                    INSERT INTO calendario_time_slot
                    (data, slot_timestamp, finestra_inizio, finestra_fine, token_vincitore, partecipanti_totali, data_tiraggio)
                    VALUES (:d,:s,:ws,:we,NULL,1,NULL)
                    ON DUPLICATE KEY UPDATE partecipanti_totali = partecipanti_totali + 1
                ');
                $ins_participants->execute([
                    ':d' => $current_date,
                    ':s' => $slot_window['slot_timestamp'],
                    ':ws' => $slot_window['window_start'],
                    ':we' => $slot_window['window_end'],
                ]);
            } catch (Exception $e2) {
                error_log("Errore incremento PARTECIPANTI_TOTALI: " . $e2->getMessage());
            }
        }
    }
}

if (!function_exists('checkInstantWinClaimSlotAndAssignPrize')) {
    function checkInstantWinClaimSlotAndAssignPrize(PDO $pdo, string $token, string $current_date, array $slot_window, $simulated_timestamp) {
        $pdo->beginTransaction();
        $dataTiraggio = ($simulated_timestamp !== null)
            ? date('Y-m-d H:i:s', (int) $simulated_timestamp)
            : date('Y-m-d H:i:s');
        $claim = $pdo->prepare('
            UPDATE calendario_time_slot
            SET token_vincitore = :tk, data_tiraggio = :tir
            WHERE data = :d AND slot_timestamp = :s AND token_vincitore IS NULL
              AND IFNULL(TRIM(codice_pdv), \'\') = \'\'
        ');
        $claim->execute([
            ':tk' => $token,
            ':tir' => $dataTiraggio,
            ':d' => $current_date,
            ':s' => $slot_window['slot_timestamp'],
        ]);
        if ($claim->rowCount() !== 1) {
            $pdo->rollBack();
            return INSTANTWIN_NO_WIN;
        }

        $prize = assignPrize($token, $simulated_timestamp);
        if (!$prize['has_won']) {
            $pdo->rollBack();
            return INSTANTWIN_NO_WIN;
        }

        $pdo->commit();
        return $prize;
    }
}

function checkInstantWin($token, $simulated_timestamp = null) {
    global $c_pdo;
    /** @var PDO $c_pdo */

    try {
        $entry = checkInstantWinValidateEntry($token, $simulated_timestamp);
        if ($entry === false) {
            return INSTANTWIN_NO_WIN;
        }
        extract($entry);

        $force_recalc = ($check_time !== null && $check_time < time());
        $target_quota = initDailyTargetIfNeeded($current_date, $check_time, $force_recalc);

        if (instantwin_legacy_direct_win_enabled()) {
            if (!instantwin_legacy_direct_win_allowed($c_pdo, $current_date, null)) {
                return INSTANTWIN_NO_WIN;
            }
            error_log('checkInstantWin: INSTANTWIN_TEST_OUTCOME_MODE=direct_win — assegnazione senza calendario slot');
            return assignPrize($token, $simulated_timestamp);
        }

        if (instantwin_engine_is_v2()) {
            instantwin_build_day_slots($current_date, new InstantwinSlotContext());
        }

        $slot_window = checkTimeSlotWindow($check_time);
        if (!$slot_window) {
            error_log("checkInstantWin: Nessuno slot disponibile per $current_date (target=$target_quota, remaining=" . getDailyRemaining($current_date) . ")");
            return INSTANTWIN_NO_WIN;
        }

        $participantsBeforeIncrement = instantwin_fascia_participants_count_today($c_pdo, $current_date, '');
        checkInstantWinIncrementSlotParticipants($c_pdo, $current_date, $slot_window);

        $remaining = getDailyRemaining($current_date);
        if (defined('MAX_DAILY_TARGET') && MAX_DAILY_TARGET > 0 && $remaining <= 0) {
            return INSTANTWIN_NO_WIN;
        }

        if (instantwin_should_block_first_fascia_claim(
            $c_pdo,
            $token,
            $current_date,
            $slot_window,
            $participantsBeforeIncrement,
            ''
        )) {
            return INSTANTWIN_NO_WIN;
        }

        return checkInstantWinClaimSlotAndAssignPrize($c_pdo, $token, $current_date, $slot_window, $simulated_timestamp);
    } catch (Exception $e) {
        if (($c_pdo instanceof PDO) && $c_pdo->inTransaction()) {
            $c_pdo->rollBack();
        }
        error_log("Errore checkInstantWin: " . $e->getMessage());
        return INSTANTWIN_NO_WIN;
    }
}
