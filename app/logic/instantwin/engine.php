<?php

/**
 * Moteur Instant Win v2 : grille du jour (une fois) + fenêtres continues + catch-up explicite.
 */

if (!defined('INSTANTWIN_CATCHUP_HOURS_BEFORE_END')) {
    define('INSTANTWIN_CATCHUP_HOURS_BEFORE_END', env_int('INSTANTWIN_CATCHUP_HOURS_BEFORE_END', 2));
}

/**
 * Contexte optionnel pour le calendrier (PDV, override quota slots).
 */
final class InstantwinSlotContext
{
    public ?string $codice_pdv = null;

    /** Override du « remaining » pour calculer N (ex. budget PDV). */
    public ?int $slots_count_override = null;

    public function __construct(?string $codice_pdv = null, ?int $slots_count_override = null)
    {
        $this->codice_pdv = $codice_pdv !== null ? trim($codice_pdv) : null;
        $this->slots_count_override = $slots_count_override;
    }

    public function pdvKey(): string
    {
        return ($this->codice_pdv !== null && $this->codice_pdv !== '') ? $this->codice_pdv : '';
    }
}

function instantwin_engine_is_v2(): bool
{
    static $v2 = null;
    if ($v2 !== null) {
        return $v2;
    }
    $raw = strtolower(trim((string) (getenv('INSTANTWIN_ENGINE') ?: 'v2')));
    $v2 = ($raw !== 'legacy');
    return $v2;
}

function instantwin_slot_context_from_globals(): ?InstantwinSlotContext
{
    if (isset($GLOBALS['__iw_slot_context']) && $GLOBALS['__iw_slot_context'] instanceof InstantwinSlotContext) {
        return $GLOBALS['__iw_slot_context'];
    }
    $pdv = '';
    if (isset($GLOBALS['__iw_pdv_cal_prefix']) && is_string($GLOBALS['__iw_pdv_cal_prefix'])) {
        $pdv = trim($GLOBALS['__iw_pdv_cal_prefix']);
    }
    $override = null;
    if (array_key_exists('__iw_pdv_daily_rem_override', $GLOBALS) && $GLOBALS['__iw_pdv_daily_rem_override'] !== null) {
        $override = (int) $GLOBALS['__iw_pdv_daily_rem_override'];
    }
    if ($pdv !== '' || $override !== null) {
        return new InstantwinSlotContext($pdv !== '' ? $pdv : null, $override);
    }
    return null;
}

function instantwin_slot_cal_filter(string $pdvKey): array
{
    if ($pdvKey !== '') {
        return [' AND codice_pdv = :iw_slot_pdv ', [':iw_slot_pdv' => $pdvKey]];
    }
    return [" AND IFNULL(TRIM(codice_pdv), '') = '' ", []];
}

function instantwin_ensure_calendario_pdv_column(): void
{
    global $c_pdo;
    if (!($c_pdo instanceof PDO)) {
        return;
    }
    static $done = false;
    if ($done) {
        return;
    }
    try {
        $c_pdo->query('SELECT `codice_pdv` FROM `calendario_time_slot` LIMIT 1');
    } catch (Exception $e) {
        try {
            $c_pdo->exec("ALTER TABLE `calendario_time_slot` ADD COLUMN `codice_pdv` VARCHAR(64) NOT NULL DEFAULT '' AFTER `data`");
        } catch (Exception $e2) {
            error_log('instantwin_ensure_calendario_pdv_column: ' . $e2->getMessage());
        }
    }
    $done = true;
}

/**
 * Plage horaire du jour pour une date (timestamp ou Y-m-d).
 */
function instantwin_day_bounds_for_date(string $date): array
{
    $ts = strtotime($date . ' 12:00:00');
    $dow = (int) date('N', $ts);
    $time_range = instantwin_get_time_range_for_day($dow);
    return instantwin_get_day_bounds_ts($date, (int) $time_range['start'], (int) $time_range['end']);
}

function instantwin_slots_remaining_for_build(string $date, ?InstantwinSlotContext $ctx): int
{
    if ($ctx !== null && $ctx->slots_count_override !== null) {
        return max(0, (int) $ctx->slots_count_override);
    }
    return (int) getDailyRemaining($date);
}

function instantwin_is_aggressive_catchup(int $now, string $date, int $remaining): bool
{
    if ($remaining <= 0) {
        return false;
    }
    $contest_dates = getContestDates();
    if ($contest_dates && $date === $contest_dates['end_date']) {
        return true;
    }
    [$day_start_ts, $day_end_ts] = instantwin_day_bounds_for_date($date);
    unset($day_start_ts);
    $hours = max(1, (int) INSTANTWIN_CATCHUP_HOURS_BEFORE_END);
    return $now >= ($day_end_ts - $hours * 3600);
}

/**
 * Construit la grille du jour (idempotent si des slots existent déjà pour date+pdv).
 */
function instantwin_build_day_slots(string $date, ?InstantwinSlotContext $ctx = null): bool
{
    global $c_pdo;
    if (!($c_pdo instanceof PDO)) {
        return false;
    }
    if (!isDateInContestRange($date)) {
        return false;
    }

    createTimeSlotsTable();
    instantwin_ensure_calendario_pdv_column();

    $ctx = $ctx ?? new InstantwinSlotContext();
    $pdvKey = $ctx->pdvKey();

    $remaining = instantwin_slots_remaining_for_build($date, $ctx);
    if ($remaining <= 0) {
        return false;
    }

    $N = max(3, $remaining);
    [$day_start_ts, $day_end_ts] = instantwin_day_bounds_for_date($date);
    $span = $day_end_ts - $day_start_ts;
    if ($span <= 60) {
        return false;
    }

    [$calFilter, $calBindExtra] = instantwin_slot_cal_filter($pdvKey);
    $calBind = array_merge([':d' => $date], $calBindExtra);

    $stmtCount = $c_pdo->prepare('SELECT COUNT(*) FROM calendario_time_slot WHERE data = :d ' . $calFilter);
    $stmtCount->execute($calBind);
    $existing = (int) $stmtCount->fetchColumn();
    if ($existing >= $N) {
        return true;
    }
    if ($existing > 0) {
        $del = $c_pdo->prepare('
            DELETE FROM calendario_time_slot
            WHERE data = :d AND token_vincitore IS NULL ' . $calFilter . '
        ');
        $del->execute($calBind);
    }

    $slotTs = [];
    for ($i = 1; $i <= $N; $i++) {
        $slotTs[$i] = (int) round($day_start_ts + ($i * $span) / ($N + 1));
    }

    $windows = [];
    for ($i = 1; $i <= $N; $i++) {
        $ws = ($i === 1) ? $day_start_ts : $windows[$i - 1]['end'];
        $we = ($i === $N) ? $day_end_ts : (int) floor(($slotTs[$i] + $slotTs[$i + 1]) / 2);
        if ($we <= $ws) {
            $we = min($day_end_ts, $ws + 60);
        }
        $windows[$i] = ['start' => $ws, 'end' => $we];
    }

    if ($pdvKey !== '') {
        $ins = $c_pdo->prepare('
            INSERT IGNORE INTO calendario_time_slot
            (data, codice_pdv, slot_timestamp, finestra_inizio, finestra_fine, token_vincitore, partecipanti_totali, data_tiraggio)
            VALUES (:d, :pdv, :s, :ws, :we, NULL, 0, NULL)
        ');
    } else {
        $ins = $c_pdo->prepare('
            INSERT IGNORE INTO calendario_time_slot
            (data, slot_timestamp, finestra_inizio, finestra_fine, token_vincitore, partecipanti_totali, data_tiraggio)
            VALUES (:d, :s, :ws, :we, NULL, 0, NULL)
        ');
    }

    foreach ($slotTs as $i => $ts) {
        $params = [
            ':d' => $date,
            ':s' => $ts,
            ':ws' => $windows[$i]['start'],
            ':we' => $windows[$i]['end'],
        ];
        if ($pdvKey !== '') {
            $params[':pdv'] = $pdvKey;
        }
        try {
            $ins->execute($params);
        } catch (Exception $e) {
            error_log('instantwin_build_day_slots insert: ' . $e->getMessage());
        }
    }

    return true;
}

function instantwin_last_win_timestamp(string $date, string $pdvKey): ?int
{
    global $c_pdo;
    [$calFilter, $calBindExtra] = instantwin_slot_cal_filter($pdvKey);
    $calBind = array_merge([':d' => $date], $calBindExtra);
    $stmt = $c_pdo->prepare('
        SELECT UNIX_TIMESTAMP(MAX(data_tiraggio)) AS last_win_ts
        FROM calendario_time_slot
        WHERE data = :d AND token_vincitore IS NOT NULL AND data_tiraggio IS NOT NULL
        ' . $calFilter . '
    ');
    $stmt->execute($calBind);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row || $row['last_win_ts'] === null) {
        return null;
    }
    return (int) $row['last_win_ts'];
}

/**
 * Trouve le slot actif (fenêtre continue) pour $now.
 */
function instantwin_find_active_slot(int $now, ?InstantwinSlotContext $ctx = null): array|false
{
    global $c_pdo;
    if (!($c_pdo instanceof PDO)) {
        return false;
    }

    $current_date = date('Y-m-d', $now);
    if (!isDateInContestRange($current_date)) {
        return false;
    }

    $dow = (int) date('N', $now);
    if (!in_array($dow, instantwin_get_distribution_days(), true)) {
        return false;
    }

    $time_range = instantwin_get_time_range_for_day($dow);
    if (!($time_range['start'] === 0 && $time_range['end'] === 24)) {
        if (!isPrizeDistributionTime($now)) {
            return false;
        }
    }

    [$day_start_ts, $day_end_ts] = instantwin_day_bounds_for_date($current_date);
    if ($now < $day_start_ts || $now >= $day_end_ts) {
        return false;
    }

    $ctx = $ctx ?? new InstantwinSlotContext();
    $pdvKey = $ctx->pdvKey();
    $remaining = instantwin_slots_remaining_for_build($current_date, $ctx);

    if (defined('MAX_DAILY_TARGET') && MAX_DAILY_TARGET > 0 && $remaining <= 0) {
        return false;
    }

    instantwin_build_day_slots($current_date, $ctx);

    $last_win_ts = instantwin_last_win_timestamp($current_date, $pdvKey);
    $min_interval = instantwin_effective_min_win_interval_seconds($last_win_ts, $current_date);
    if ($last_win_ts !== null && ($now - $last_win_ts) < $min_interval) {
        return false;
    }

    $catchup = instantwin_is_aggressive_catchup($now, $current_date, $remaining);
    [$calFilter, $calBindExtra] = instantwin_slot_cal_filter($pdvKey);
    $calBind = array_merge([':d' => $current_date], $calBindExtra);

    if ($catchup) {
        $stmt = $c_pdo->prepare('
            SELECT slot_timestamp, finestra_inizio, finestra_fine
            FROM calendario_time_slot
            WHERE data = :d AND token_vincitore IS NULL
            ' . $calFilter . '
            ORDER BY slot_timestamp ASC
        ');
        $stmt->execute($calBind);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $slot_ts = (int) $row['slot_timestamp'];
            $ws = (int) $row['finestra_inizio'];
            $we = $day_end_ts;
            if ($now >= $ws && $now < $we) {
                return [
                    'slot_timestamp' => $slot_ts,
                    'window_start' => $ws,
                    'window_end' => $we,
                    'slots_vides_precedents' => 0,
                ];
            }
        }
        return false;
    }

    $stmt = $c_pdo->prepare('
        SELECT slot_timestamp, finestra_inizio, finestra_fine
        FROM calendario_time_slot
        WHERE data = :d AND token_vincitore IS NULL
          AND finestra_inizio <= :now AND finestra_fine > :now
        ' . $calFilter . '
        ORDER BY slot_timestamp ASC
        LIMIT 1
    ');
    $calBind[':now'] = $now;
    $stmt->execute($calBind);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        return false;
    }

    return [
        'slot_timestamp' => (int) $row['slot_timestamp'],
        'window_start' => (int) $row['finestra_inizio'],
        'window_end' => (int) $row['finestra_fine'],
        'slots_vides_precedents' => 0,
    ];
}

function checkTimeSlotWindow_v2($timestamp = null): array|false
{
    $now = $timestamp !== null ? (int) $timestamp : time();
    $ctx = instantwin_slot_context_from_globals();
    return instantwin_find_active_slot($now, $ctx);
}
