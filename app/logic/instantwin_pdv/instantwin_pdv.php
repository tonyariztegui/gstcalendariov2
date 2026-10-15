<?php

require_once __DIR__ . '/../instantwin/core.php';

/**
 * Instant Win per PDV (codice magazzino = partecipazioni.id_pdv_concorso).
 *
 * Tetto giornaliero: `quota_giornaliera` globale (concorso) + `pdv_quota_giornaliera` (lissage soft par magasin).
 * Per magazzino: `pdv_premio_budget` ripartisce le quantità `premi` sui PDV; `is_active` spegne
 * la riga quando `budget_assigned` raggiunge `budget_total`.
 *
 * Dipendenze condivise: `functions_instantwin_core.php`.
 */

function instantwin_pdv_enabled(?array $cfg = null): bool {
    global $contest_config;
    $c = $cfg ?? $contest_config ?? null;
    return is_array($c) && !empty($c['instantwin_per_pdv']);
}

function instantwin_pdv_ensure_schema(PDO $pdo): void {
    $pdo->exec("CREATE TABLE IF NOT EXISTS `pdv_premio_budget` (
        `codice_pdv` VARCHAR(64) NOT NULL,
        `prize_id` INT NOT NULL,
        `budget_total` INT NOT NULL DEFAULT 0,
        `budget_assigned` INT NOT NULL DEFAULT 0,
        `is_active` TINYINT(1) NOT NULL DEFAULT 1,
        `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`codice_pdv`, `prize_id`),
        KEY `idx_pdv_premio_budget_prize` (`prize_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    instantwin_pdv_migrate_premio_budget_schema_if_needed($pdo);
    instantwin_pdv_migrate_budget_is_active_if_needed($pdo);
    instantwin_pdv_migrate_iw_traffic_weight_if_needed($pdo);
    instantwin_pdv_migrate_premio_extra_if_needed($pdo);

    try {
        $pdo->query('SELECT `codice_pdv` FROM `calendario_time_slot` LIMIT 1');
    } catch (Throwable $e) {
        try {
            $pdo->exec("ALTER TABLE `calendario_time_slot` ADD COLUMN `codice_pdv` VARCHAR(64) NOT NULL DEFAULT '' AFTER `data`");
        } catch (Throwable $e2) {
            error_log('instantwin_pdv_ensure_schema add codice_pdv: ' . $e2->getMessage());
        }
    }

    $hasOld = false;
    try {
        $st = $pdo->query("SHOW INDEX FROM `calendario_time_slot` WHERE Key_name = 'uk_data_slot'");
        $hasOld = $st && $st->fetch();
    } catch (Throwable $e) {
    }
    if ($hasOld) {
        try {
            $pdo->exec('ALTER TABLE `calendario_time_slot` DROP INDEX `uk_data_slot`');
        } catch (Throwable $e) {
            error_log('instantwin_pdv_ensure_schema drop uk_data_slot: ' . $e->getMessage());
        }
    }
    $hasNew = false;
    try {
        $st = $pdo->query("SHOW INDEX FROM `calendario_time_slot` WHERE Key_name = 'uk_data_slot_pdv'");
        $hasNew = $st && $st->fetch();
    } catch (Throwable $e) {
    }
    if (!$hasNew) {
        try {
            $pdo->exec('ALTER TABLE `calendario_time_slot` ADD UNIQUE KEY `uk_data_slot_pdv` (`data`, `slot_timestamp`, `codice_pdv`)');
        } catch (Throwable $e) {
            error_log('instantwin_pdv_ensure_schema add uk_data_slot_pdv: ' . $e->getMessage());
        }
    }

    try {
        $pdo->exec("UPDATE `calendario_time_slot` SET `codice_pdv` = '' WHERE `codice_pdv` IS NULL");
    } catch (Throwable $e) {
    }

    try {
        // Allinea is_active al dato reale (corregge righe rovinate da UPDATE multi-colonna con +1 sul valore già incrementato).
        $pdo->exec('UPDATE `pdv_premio_budget` SET `is_active` = IF(`budget_assigned` < `budget_total`, 1, 0)');
    } catch (Throwable $e) {
    }

    instantwin_pdv_ensure_pdv_quota_table($pdo);
}

/** Migrazione da schema legacy (solo codice_pdv PK) a budget per premio (codice_pdv, prize_id). */
function instantwin_pdv_migrate_premio_budget_schema_if_needed(PDO $pdo): void {
    try {
        $st = $pdo->query("SHOW COLUMNS FROM `pdv_premio_budget` LIKE 'prize_id'");
        if ($st && $st->fetch()) {
            return;
        }
    } catch (Throwable $e) {
        return;
    }
    try {
        $pdo->exec('TRUNCATE TABLE `pdv_premio_budget`');
        $pdo->exec('ALTER TABLE `pdv_premio_budget` DROP PRIMARY KEY');
        $pdo->exec('ALTER TABLE `pdv_premio_budget` ADD COLUMN `prize_id` INT NOT NULL AFTER `codice_pdv`');
        $pdo->exec('ALTER TABLE `pdv_premio_budget` ADD PRIMARY KEY (`codice_pdv`, `prize_id`)');
        $pdo->exec('ALTER TABLE `pdv_premio_budget` ADD KEY `idx_pdv_premio_budget_prize` (`prize_id`)');
    } catch (Throwable $e) {
        error_log('instantwin_pdv_migrate_premio_budget_schema_if_needed: ' . $e->getMessage());
    }
}

/** Aggiunge `is_active` (1 finché `budget_assigned` < `budget_total`, altrimenti 0). */
function instantwin_pdv_migrate_budget_is_active_if_needed(PDO $pdo): void {
    try {
        $st = $pdo->query("SHOW COLUMNS FROM `pdv_premio_budget` LIKE 'is_active'");
        if ($st && $st->fetch()) {
            return;
        }
    } catch (Throwable $e) {
        return;
    }
    try {
        $pdo->exec('ALTER TABLE `pdv_premio_budget` ADD COLUMN `is_active` TINYINT(1) NOT NULL DEFAULT 1 AFTER `budget_assigned`');
    } catch (Throwable $e) {
        error_log('instantwin_pdv_migrate_budget_is_active_if_needed add: ' . $e->getMessage());
        return;
    }
    try {
        $pdo->exec('UPDATE `pdv_premio_budget` SET `is_active` = IF(`budget_assigned` < `budget_total`, 1, 0)');
    } catch (Throwable $e) {
        error_log('instantwin_pdv_migrate_budget_is_active_if_needed backfill: ' . $e->getMessage());
    }
}

/** Tabella slot «premi aggiuntivi» (resto di `quantita_totale` / N PDV) assegnati manualmente per `premi.id`. */
function instantwin_pdv_migrate_premio_extra_if_needed(PDO $pdo): void {
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS `pdv_premio_extra` (
            `codice_pdv` VARCHAR(64) NOT NULL,
            `prize_id` INT NOT NULL,
            `extra_slots` INT NOT NULL DEFAULT 0,
            `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`codice_pdv`, `prize_id`),
            KEY `idx_pdv_premio_extra_prize` (`prize_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    } catch (Throwable $e) {
        error_log('instantwin_pdv_migrate_premio_extra_if_needed: ' . $e->getMessage());
    }
}

/** Aggiunge `iw_traffic_weight` su `punti_vendita` (default 1 = ripartizione uniforme). */
function instantwin_pdv_migrate_iw_traffic_weight_if_needed(PDO $pdo): void {
    try {
        $st = $pdo->query("SHOW COLUMNS FROM `punti_vendita` LIKE 'iw_traffic_weight'");
        if ($st && $st->fetch()) {
            return;
        }
    } catch (Throwable $e) {
        return;
    }
    try {
        $pdo->exec('ALTER TABLE `punti_vendita` ADD COLUMN `iw_traffic_weight` DECIMAL(10,4) NOT NULL DEFAULT 1.0000 AFTER `active`');
    } catch (Throwable $e) {
        error_log('instantwin_pdv_migrate_iw_traffic_weight_if_needed add: ' . $e->getMessage());
    }
}

/**
 * PDV attivi ordinati per `codice` con peso traffico IW (per ripartizione `pdv_premio_budget`).
 * Richiede che `instantwin_pdv_ensure_schema` sia già stato chiamato (colonna `iw_traffic_weight`).
 *
 * @return list<array{codice: string, weight: float, ragsoc?: string, insegna?: string, indirizzo?: string, citta?: string, prov?: string}>
 */
function instantwin_pdv_active_pdvs_for_split(PDO $pdo): array {
    $out = [];
    try {
        $st = $pdo->query("
            SELECT TRIM(`codice`) AS `c`,
                   `iw_traffic_weight`,
                   NULLIF(TRIM(`ragsoc`), '') AS `ragsoc`,
                   NULLIF(TRIM(`insegna`), '') AS `insegna`,
                   NULLIF(TRIM(`indirizzo`), '') AS `indirizzo`,
                   NULLIF(TRIM(`citta`), '') AS `citta`,
                   NULLIF(TRIM(`prov`), '') AS `prov`
            FROM `punti_vendita`
            WHERE `active` = 1 AND `codice` IS NOT NULL AND TRIM(`codice`) <> ''
            ORDER BY TRIM(`codice`) ASC
        ");
        if (!$st) {
            return [];
        }
        while ($rw = $st->fetch(PDO::FETCH_ASSOC)) {
            $rw = array_change_key_case($rw, CASE_LOWER);
            $c = trim((string) ($rw['c'] ?? ''));
            if ($c === '') {
                continue;
            }
            $w = isset($rw['iw_traffic_weight']) ? (float) $rw['iw_traffic_weight'] : 1.0;
            if (!is_finite($w) || $w <= 0) {
                $w = 1.0;
            }
            if ($w > 1000.0) {
                $w = 1000.0;
            }
            $out[] = [
                'codice' => $c,
                'weight' => $w,
                'ragsoc' => trim((string) ($rw['ragsoc'] ?? '')),
                'insegna' => trim((string) ($rw['insegna'] ?? '')),
                'indirizzo' => trim((string) ($rw['indirizzo'] ?? '')),
                'citta' => trim((string) ($rw['citta'] ?? '')),
                'prov' => trim((string) ($rw['prov'] ?? '')),
            ];
        }
    } catch (Throwable $e) {
        error_log('instantwin_pdv_active_pdvs_for_split: ' . $e->getMessage());
        return [];
    }
    return $out;
}

function instantwin_pdv_active_codes(PDO $pdo): array {
    $pdvs = instantwin_pdv_active_pdvs_for_split($pdo);
    if ($pdvs === []) {
        return [];
    }
    $codes = [];
    foreach ($pdvs as $row) {
        $codes[] = $row['codice'];
    }
    return $codes;
}

/** Riparto interi "largest remainder": somma = $total */
function instantwin_pdv_split_integers(int $total, int $n): array {
    if ($n <= 0) {
        return [];
    }
    $base = intdiv($total, $n);
    $rem = $total % $n;
    $out = array_fill(0, $n, $base);
    for ($i = 0; $i < $rem; $i++) {
        $out[$i]++;
    }
    return $out;
}

/**
 * Ripartizione intera di $total su n pesi (somma = $total, metodo dei più grandi resti su quantità scalate intere).
 *
 * @param list<float|int|string> $weights stessa lunghezza dei PDV ordinati
 * @return list<int>
 */
function instantwin_pdv_split_integers_by_weights(int $total, array $weights): array {
    $n = count($weights);
    if ($n <= 0) {
        return [];
    }
    if ($total <= 0) {
        return array_fill(0, $n, 0);
    }
    $scaled = [];
    foreach ($weights as $i => $w) {
        $f = (float) $w;
        if (!is_finite($f) || $f <= 0) {
            $f = 1.0;
        }
        if ($f > 1000.0) {
            $f = 1000.0;
        }
        $scaled[$i] = (int) max(1, (int) round($f * 10000));
    }
    $sumScaled = array_sum($scaled);
    if ($sumScaled <= 0) {
        $scaled = array_fill(0, $n, 10000);
        $sumScaled = 10000 * $n;
    }
    $parts = array_fill(0, $n, 0);
    $acc = 0;
    foreach ($scaled as $i => $s) {
        $parts[$i] = intdiv($total * $s, $sumScaled);
        $acc += $parts[$i];
    }
    $rem = $total - $acc;
    if ($rem <= 0) {
        return $parts;
    }
    $ord = [];
    foreach ($scaled as $i => $s) {
        $ord[] = ['i' => $i, 'r' => ($total * $s) % $sumScaled];
    }
    usort($ord, static function (array $a, array $b): int {
        if ($b['r'] !== $a['r']) {
            return $b['r'] <=> $a['r'];
        }
        return $a['i'] <=> $b['i'];
    });
    for ($k = 0; $k < $rem && $k < $n; $k++) {
        $parts[$ord[$k]['i']]++;
    }
    return $parts;
}

/** Vincite già assegnate per PDV e per tipo premio (`premi.id`). */
function instantwin_pdv_wins_count(PDO $pdo, string $codice_pdv, int $prize_id): int {
    if ($prize_id <= 0) {
        return 0;
    }
    $q = $pdo->prepare('SELECT COUNT(*) FROM `partecipazioni` WHERE `has_won` = 1 AND COALESCE(`annullato`, 0) = 0 AND `id_pdv_concorso` = :c AND `prize_id` = :p');
    $q->execute([':c' => $codice_pdv, ':p' => $prize_id]);
    return (int) $q->fetchColumn();
}

function instantwin_pdv_codice_for_token(PDO $pdo, string $token): ?string {
    $q = $pdo->prepare('SELECT `id_pdv_concorso` FROM `partecipazioni` WHERE `token` = :t LIMIT 1');
    $q->execute([':t' => $token]);
    $v = $q->fetchColumn();
    if ($v === false || $v === null) {
        return null;
    }
    $s = trim((string) $v);
    return $s !== '' ? $s : null;
}

/** Somma dei posti vincita ancora disponibili per quel PDV su tutti i tipi premio (per densità slot / quota giornaliera). */
function instantwin_pdv_get_budget_remaining(PDO $pdo, string $codice_pdv): int {
    $q = $pdo->prepare('SELECT COALESCE(SUM(GREATEST(0, `budget_total` - `budget_assigned`)), 0) FROM `pdv_premio_budget` WHERE `codice_pdv` = :c');
    $q->execute([':c' => $codice_pdv]);
    return (int) $q->fetchColumn();
}

function instantwin_pdv_get_budget_remaining_for_prize(PDO $pdo, string $codice_pdv, int $prize_id): int {
    if ($prize_id <= 0) {
        return 0;
    }
    $q = $pdo->prepare('SELECT `budget_total`, `budget_assigned` FROM `pdv_premio_budget` WHERE `codice_pdv` = :c AND `prize_id` = :p LIMIT 1');
    $q->execute([':c' => $codice_pdv, ':p' => $prize_id]);
    $row = $q->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        return 0;
    }
    return max(0, (int) $row['budget_total'] - (int) $row['budget_assigned']);
}

// =============================================================================
// Quota giornaliera PDV (soft pacing) — solo in questo file, non modifica il core
// .env optionnel :
//   INSTANTWIN_PDV_DAILY_PACING_ENABLED=true
//   INSTANTWIN_PDV_PACING_RELAX_DAYS=3  (assouplissement progressif en fin de concours)
// =============================================================================

function instantwin_pdv_daily_pacing_enabled(): bool
{
    static $enabled = null;
    if ($enabled !== null) {
        return $enabled;
    }
    $raw = getenv('INSTANTWIN_PDV_DAILY_PACING_ENABLED');
    if ($raw === false || trim((string) $raw) === '') {
        $enabled = true;
    } else {
        $enabled = filter_var($raw, FILTER_VALIDATE_BOOLEAN) !== false;
    }
    return $enabled;
}

function instantwin_pdv_pacing_relax_last_days(): int
{
    return max(0, env_int('INSTANTWIN_PDV_PACING_RELAX_DAYS', 3));
}

function instantwin_pdv_ensure_pdv_quota_table(PDO $pdo): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $sql = "
    CREATE TABLE IF NOT EXISTS `pdv_quota_giornaliera` (
        `codice_pdv` VARCHAR(64) NOT NULL,
        `data` DATE NOT NULL,
        `target` INT NOT NULL DEFAULT 0,
        `assigned` INT NOT NULL DEFAULT 0,
        `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`codice_pdv`, `data`),
        KEY `idx_pdv_quota_data` (`data`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    try {
        $pdo->exec($sql);
        $done = true;
    } catch (Throwable $e) {
        error_log('instantwin_pdv_ensure_pdv_quota_table: ' . $e->getMessage());
        $done = true;
    }
}

function instantwin_pdv_count_wins_on_date(PDO $pdo, string $codice_pdv, string $date): int
{
    $pdv = trim($codice_pdv);
    if ($pdv === '') {
        return 0;
    }
    try {
        $st = $pdo->prepare('
            SELECT COUNT(*)
            FROM `partecipazioni`
            WHERE `has_won` = 1
              AND COALESCE(`annullato`, 0) = 0
              AND `win_date` IS NOT NULL
              AND DATE(`win_date`) = :d
              AND TRIM(`id_pdv_concorso`) = :pdv
        ');
        $st->execute([':d' => $date, ':pdv' => $pdv]);
        return (int) $st->fetchColumn();
    } catch (Throwable $e) {
        return 0;
    }
}

/**
 * Target jour PDV : budget magasin restant / jours de distribution restants (base + remainder).
 * Dernier jour concours : tout le budget restant du magasin (comme quota globale).
 */
function instantwin_pdv_get_daily_target(PDO $pdo, string $codice_pdv, string $date): int
{
    $pdv = trim($codice_pdv);
    if ($pdv === '' || !isDateInContestRange($date)) {
        return 0;
    }

    $stockRemaining = instantwin_pdv_get_budget_remaining($pdo, $pdv);
    if ($stockRemaining <= 0) {
        return 0;
    }

    $remainingDates = getRemainingDistributionDates($date);
    $daysRemaining = count($remainingDates);
    if ($daysRemaining <= 0) {
        return $stockRemaining;
    }

    $contestDates = getContestDates();
    if ($contestDates && $date === $contestDates['end_date']) {
        return $stockRemaining;
    }

    $base = intdiv($stockRemaining, $daysRemaining);
    $rem = $stockRemaining % $daysRemaining;
    $idx = array_search($date, $remainingDates, true);
    if ($idx === false) {
        return max(1, $base);
    }

    $target = $base + (($idx < $rem) ? 1 : 0);
    return max(0, $target);
}

function instantwin_pdv_init_daily_target_if_needed(PDO $pdo, string $codice_pdv, string $date, $check_time = null, bool $force_recalculate = false): int
{
    $pdv = trim($codice_pdv);
    if ($pdv === '' || !instantwin_pdv_daily_pacing_enabled()) {
        return 0;
    }
    if (!isDateInContestRange($date)) {
        return 0;
    }

    instantwin_pdv_ensure_pdv_quota_table($pdo);

    $today = date('Y-m-d');
    if ($date === $today) {
        $force_recalculate = true;
    }

    $contestDates = getContestDates();
    if ($contestDates && $date === $contestDates['end_date']) {
        $force_recalculate = true;
    }

    $q = $pdo->prepare('SELECT `target`, `assigned` FROM `pdv_quota_giornaliera` WHERE `codice_pdv` = :p AND `data` = :d');
    $q->execute([':p' => $pdv, ':d' => $date]);
    $existing = $q->fetch(PDO::FETCH_ASSOC);

    $winsOnDate = instantwin_pdv_count_wins_on_date($pdo, $pdv, $date);

    if ($existing !== false && !$force_recalculate) {
        $assigned = max((int) $existing['assigned'], $winsOnDate);
        if ($assigned !== (int) $existing['assigned']) {
            $pdo->prepare('UPDATE `pdv_quota_giornaliera` SET `assigned` = :a WHERE `codice_pdv` = :p AND `data` = :d')
                ->execute([':a' => $assigned, ':p' => $pdv, ':d' => $date]);
        }
        return (int) $existing['target'];
    }

    if ($check_time === null) {
        $check_time = strtotime($date . ' 12:00:00');
    }

    $newTarget = instantwin_pdv_get_daily_target($pdo, $pdv, $date);
    $alreadyAssigned = ($existing !== false) ? max((int) $existing['assigned'], $winsOnDate) : $winsOnDate;
    if ($newTarget < $alreadyAssigned) {
        $newTarget = $alreadyAssigned;
    }

    $pdo->prepare('
        INSERT INTO `pdv_quota_giornaliera` (`codice_pdv`, `data`, `target`, `assigned`)
        VALUES (:p, :d, :t, :a)
        ON DUPLICATE KEY UPDATE `target` = VALUES(`target`), `assigned` = GREATEST(`assigned`, VALUES(`assigned`))
    ')->execute([
        ':p' => $pdv,
        ':d' => $date,
        ':t' => $newTarget,
        ':a' => $alreadyAssigned,
    ]);

    return $newTarget;
}

/**
 * Phase de lissage : strict | normal | relaxed | off (dernier jour distribution = off).
 */
function instantwin_pdv_pacing_phase(string $date): string
{
    if (!isDateInContestRange($date)) {
        return 'off';
    }

    $contestDates = getContestDates();
    if ($contestDates && $date === $contestDates['end_date']) {
        return 'off';
    }

    $daysLeft = count(getRemainingDistributionDates($date));
    if ($daysLeft <= 0) {
        return 'off';
    }

    $relaxDays = instantwin_pdv_pacing_relax_last_days();
    if ($relaxDays > 0 && $daysLeft <= $relaxDays) {
        if ($daysLeft <= max(1, (int) floor($relaxDays / 2))) {
            return 'relaxed';
        }
        return 'normal';
    }

    if ($daysLeft > 7) {
        return 'normal';
    }

    return 'strict';
}

/**
 * @return array{target: int, assigned: int, remaining: int, phase: string, days_left: int, over_ratio: float}
 */
function instantwin_pdv_get_pacing_state(PDO $pdo, string $codice_pdv, string $date): array
{
    $pdv = trim($codice_pdv);
    $phase = instantwin_pdv_pacing_phase($date);
    $daysLeft = count(getRemainingDistributionDates($date));

    if ($pdv === '' || !instantwin_pdv_daily_pacing_enabled() || $phase === 'off') {
        return [
            'target' => 0,
            'assigned' => 0,
            'remaining' => PHP_INT_MAX,
            'phase' => 'off',
            'days_left' => $daysLeft,
            'over_ratio' => 0.0,
        ];
    }

    instantwin_pdv_init_daily_target_if_needed($pdo, $pdv, $date);

    $q = $pdo->prepare('SELECT `target`, `assigned` FROM `pdv_quota_giornaliera` WHERE `codice_pdv` = :p AND `data` = :d');
    $q->execute([':p' => $pdv, ':d' => $date]);
    $row = $q->fetch(PDO::FETCH_ASSOC);
    $target = $row ? (int) $row['target'] : 0;
    $assigned = $row ? (int) $row['assigned'] : 0;
    if ($target < $assigned) {
        $target = $assigned;
    }

    $remaining = max(0, $target - $assigned);
    $overRatio = ($target > 0) ? ($assigned / $target) : 0.0;

    return [
        'target' => $target,
        'assigned' => $assigned,
        'remaining' => $remaining,
        'phase' => $phase,
        'days_left' => $daysLeft,
        'over_ratio' => $overRatio,
    ];
}

/**
 * Réduit la densité de slots (soft) si le PDV a déjà dépassé son rythme du jour.
 * Ne bloque jamais seul : budget PDV + quota globale restent les limites dures.
 */
function instantwin_pdv_apply_pacing_to_slot_cap(PDO $pdo, string $date, string $codice_pdv, int $baseCap): int
{
    if ($baseCap <= 0 || !instantwin_pdv_daily_pacing_enabled()) {
        return max(0, $baseCap);
    }

    $state = instantwin_pdv_get_pacing_state($pdo, $codice_pdv, $date);
    if ($state['phase'] === 'off' || $state['target'] <= 0) {
        return $baseCap;
    }

    $ratio = (float) $state['over_ratio'];
    if ($ratio <= 1.0) {
        return $baseCap;
    }

    $excess = $ratio - 1.0;
    $divisor = match ($state['phase']) {
        'relaxed' => 1.0 + ($excess * 0.35),
        'normal' => 1.0 + ($excess * 0.75),
        default => 1.0 + ($excess * 1.5),
    };

    return max(1, (int) floor($baseCap / $divisor));
}

/** min(quota globale, budget PDV) puis lissage soft quota journalier PDV pour la densité des slots */
function instantwin_pdv_effective_daily_remaining_for_slots(PDO $pdo, string $date, string $codice_pdv): int
{
    $g = (int) getDailyRemaining($date);
    $b = instantwin_pdv_get_budget_remaining($pdo, $codice_pdv);
    $base = max(0, min($g, $b));
    return instantwin_pdv_apply_pacing_to_slot_cap($pdo, $date, $codice_pdv, $base);
}

function instantwin_pdv_increment_daily_assigned(PDO $pdo, string $codice_pdv, string $date): void
{
    if (!instantwin_pdv_daily_pacing_enabled()) {
        return;
    }
    $pdv = trim($codice_pdv);
    if ($pdv === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        return;
    }
    instantwin_pdv_ensure_pdv_quota_table($pdo);
    instantwin_pdv_init_daily_target_if_needed($pdo, $pdv, $date);
    $pdo->prepare('
        UPDATE `pdv_quota_giornaliera`
        SET `assigned` = `assigned` + 1
        WHERE `codice_pdv` = :p AND `data` = :d
    ')->execute([':p' => $pdv, ':d' => $date]);
}

/** Symétrique de `instantwin_pdv_increment_daily_assigned` (annulation vincita manager). */
function instantwin_pdv_decrement_daily_assigned(PDO $pdo, string $codice_pdv, string $date): void
{
    if (!instantwin_pdv_daily_pacing_enabled()) {
        return;
    }
    $pdv = trim($codice_pdv);
    if ($pdv === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        return;
    }
    instantwin_pdv_ensure_pdv_quota_table($pdo);
    $pdo->prepare('
        UPDATE `pdv_quota_giornaliera`
        SET `assigned` = GREATEST(`assigned` - 1, 0)
        WHERE `codice_pdv` = :p AND `data` = :d AND `assigned` > 0
    ')->execute([':p' => $pdv, ':d' => $date]);
}

/**
 * Aggregato vincite per (codice_pdv, prize_id) — una query.
 *
 * @return array<string, array<int, int>>
 */
function instantwin_pdv_load_wins_budget_by_pdv_prize(PDO $pdo): array {
    $out = [];
    try {
        $st = $pdo->query("
            SELECT TRIM(`id_pdv_concorso`) AS `c`, `prize_id`, COUNT(*) AS `n`
            FROM `partecipazioni`
            WHERE `has_won` = 1 AND COALESCE(`annullato`, 0) = 0 AND `prize_id` IS NOT NULL
              AND `id_pdv_concorso` IS NOT NULL AND TRIM(`id_pdv_concorso`) <> ''
            GROUP BY TRIM(`id_pdv_concorso`), `prize_id`
        ");
        if ($st) {
            while ($rw = $st->fetch(PDO::FETCH_ASSOC)) {
                $rw = array_change_key_case($rw, CASE_LOWER);
                $cc = trim((string) ($rw['c'] ?? ''));
                $pid = (int) ($rw['prize_id'] ?? 0);
                if ($cc === '' || $pid <= 0) {
                    continue;
                }
                $out[$cc][$pid] = (int) ($rw['n'] ?? 0);
            }
        }
    } catch (Throwable $e) {
        error_log('instantwin_pdv_load_wins_budget_by_pdv_prize: ' . $e->getMessage());
    }
    return $out;
}

/**
 * Quota uguale per PDV + eventuali slot aggiuntivi (resto della divisione intera).
 *
 * @return array{base: int, extra_total: int}
 */
function instantwin_pdv_equal_base_and_extra_total(int $qty, int $nPdv): array {
    if ($nPdv <= 0 || $qty <= 0) {
        return ['base' => 0, 'extra_total' => 0];
    }
    return [
        'base' => intdiv($qty, $nPdv),
        'extra_total' => $qty % $nPdv,
    ];
}

/**
 * @return array<int, array<string, int>> prize_id => [ codice_pdv => extra_slots ]
 */
function instantwin_pdv_load_extra_allocations(PDO $pdo): array {
    $out = [];
    try {
        $st = $pdo->query('SELECT `prize_id`, TRIM(`codice_pdv`) AS `c`, `extra_slots` FROM `pdv_premio_extra`');
        if (!$st) {
            return [];
        }
        while ($r = $st->fetch(PDO::FETCH_ASSOC)) {
            $r = array_change_key_case($r, CASE_LOWER);
            $pid = (int) ($r['prize_id'] ?? 0);
            $c = trim((string) ($r['c'] ?? ''));
            $n = (int) ($r['extra_slots'] ?? 0);
            if ($pid <= 0 || $c === '' || $n <= 0) {
                continue;
            }
            if (!isset($out[$pid])) {
                $out[$pid] = [];
            }
            $out[$pid][$c] = ($out[$pid][$c] ?? 0) + $n;
        }
    } catch (Throwable $e) {
        error_log('instantwin_pdv_load_extra_allocations: ' . $e->getMessage());
    }
    return $out;
}

/**
 * Ripone gli slot aggiuntivi per un premio (sostituisce le righe esistenti per quel prize_id).
 *
 * @param array<string, int> $byCode codice_pdv => extra_slots (>= 0)
 */
function instantwin_pdv_save_extra_allocations_for_prize(PDO $pdo, int $prizeId, array $byCode): void {
    if ($prizeId <= 0) {
        return;
    }
    $pdo->prepare('DELETE FROM `pdv_premio_extra` WHERE `prize_id` = :pid')->execute([':pid' => $prizeId]);
    $ins = $pdo->prepare('INSERT INTO `pdv_premio_extra` (`codice_pdv`, `prize_id`, `extra_slots`) VALUES (:c, :pid, :n)');
    foreach ($byCode as $cod => $n) {
        $cod = trim((string) $cod);
        $n = (int) $n;
        if ($cod === '' || $n <= 0) {
            continue;
        }
        $ins->execute([':c' => $cod, ':pid' => $prizeId, ':n' => $n]);
    }
}

/**
 * Distribuisce `$extraTotal` slot (interi) sui codici PDV con pesi (somma = extraTotal).
 *
 * @param list<string> $codes
 * @param list<float> $weights stessa lunghezza di $codes
 * @return array<string, int>
 */
function instantwin_pdv_distribute_extra_slots(int $extraTotal, array $codes, array $weights): array {
    $n = count($codes);
    if ($n === 0 || $extraTotal <= 0) {
        return [];
    }
    $parts = instantwin_pdv_split_integers_by_weights($extraTotal, $weights);
    $map = [];
    foreach ($codes as $i => $cod) {
        $v = (int) ($parts[$i] ?? 0);
        if ($v > 0) {
            $map[$cod] = $v;
        }
    }
    return $map;
}

/**
 * Allocazione aggiuntivi per un premio: salvata se valida, altrimenti default sui pesi traffico.
 *
 * @param list<array{codice: string, weight: float}> $pdvs
 * @return array<string, int>
 */
function instantwin_pdv_resolve_extra_allocations_for_prize(
    int $prizeId,
    int $extraTotal,
    array $pdvs,
    array $savedByPrize
): array {
    if ($extraTotal <= 0 || $pdvs === []) {
        return [];
    }
    $saved = $savedByPrize[$prizeId] ?? [];
    $sumSaved = array_sum($saved);
    if ($sumSaved === $extraTotal) {
        $allowed = [];
        foreach ($pdvs as $row) {
            $allowed[$row['codice']] = true;
        }
        $out = [];
        foreach ($saved as $cod => $n) {
            $cod = trim((string) $cod);
            if ($cod !== '' && isset($allowed[$cod]) && (int) $n > 0) {
                $out[$cod] = (int) $n;
            }
        }
        if (array_sum($out) === $extraTotal) {
            return $out;
        }
    }
    $codes = [];
    $weights = [];
    foreach ($pdvs as $row) {
        $codes[] = $row['codice'];
        $weights[] = $row['weight'];
    }
    return instantwin_pdv_distribute_extra_slots($extraTotal, $codes, $weights);
}

/**
 * Dati per UI manager «premi aggiuntivi» (solo premi con resto > 0).
 *
 * @param list<array{codice: string, weight: float}> $pdvs
 * @return list<array{
 *   prize_id: int,
 *   tipo_premio: string,
 *   valore: string,
 *   quantita_totale: int,
 *   base_per_pdv: int,
 *   extra_total: int,
 *   allocations: array<string, int>,
 *   sum_allocations: int,
 *   is_custom_valid: bool
 * }>
 */
function instantwin_pdv_manager_extra_prizes_plan(PDO $pdo, array $pdvs, array $premiRows): array {
    $n = count($pdvs);
    if ($n === 0) {
        return [];
    }
    $saved = instantwin_pdv_load_extra_allocations($pdo);
    $plan = [];
    foreach ($premiRows as $pr) {
        $pr = array_change_key_case($pr, CASE_LOWER);
        $prizeId = (int) ($pr['id'] ?? 0);
        $qty = (int) ($pr['quantita_totale'] ?? 0);
        if ($prizeId <= 0 || $qty <= 0) {
            continue;
        }
        $split = instantwin_pdv_equal_base_and_extra_total($qty, $n);
        $extraTotal = (int) $split['extra_total'];
        if ($extraTotal <= 0) {
            continue;
        }
        $alloc = instantwin_pdv_resolve_extra_allocations_for_prize($prizeId, $extraTotal, $pdvs, $saved);
        $sumAlloc = array_sum($alloc);
        $custom = $saved[$prizeId] ?? [];
        $plan[] = [
            'prize_id' => $prizeId,
            'tipo_premio' => trim((string) ($pr['tipo_premio'] ?? '')),
            'valore' => trim((string) ($pr['valore'] ?? '')),
            'quantita_totale' => $qty,
            'base_per_pdv' => (int) $split['base'],
            'extra_total' => $extraTotal,
            'allocations' => $alloc,
            'sum_allocations' => $sumAlloc,
            'is_custom_valid' => array_sum($custom) === $extraTotal,
        ];
    }
    return $plan;
}

/**
 * Ripartisce `pdv_premio_budget` da `premi.quantita_totale` e vincite già registrate.
 *
 * Se `quantita_totale % N_PDV > 0`: quota base uguale per tutti + slot aggiuntivi (tabella `pdv_premio_extra` o default sui pesi).
 * Altrimenti: ripartizione proporzionale ai pesi `iw_traffic_weight` (comportamento precedente).
 *
 * @param bool $ensureSchema Se false, salta `instantwin_pdv_ensure_schema` (obbligatorio dentro transazione PDO).
 */
function instantwin_pdv_resplit_pdv_budget(PDO $pdo, ?array $winsBudgetByPdv = null, bool $ensureSchema = true): void {
    if (!instantwin_pdv_enabled()) {
        return;
    }
    if ($ensureSchema) {
        instantwin_pdv_ensure_schema($pdo);
    }
    $pdvs = instantwin_pdv_active_pdvs_for_split($pdo);
    if ($pdvs === []) {
        return;
    }
    $codes = [];
    $weightList = [];
    foreach ($pdvs as $row) {
        $codes[] = $row['codice'];
        $weightList[] = $row['weight'];
    }
    $nPdv = count($codes);
    if ($winsBudgetByPdv === null) {
        $winsBudgetByPdv = instantwin_pdv_load_wins_budget_by_pdv_prize($pdo);
    }
    $savedExtras = instantwin_pdv_load_extra_allocations($pdo);
    $premiRows = $pdo->query('SELECT `id`, `quantita_totale` FROM `premi` WHERE `quantita_totale` > 0 ORDER BY `id` ASC')->fetchAll(PDO::FETCH_ASSOC);
    if ($premiRows === false) {
        $premiRows = [];
    }
    $pdo->exec('DELETE FROM `pdv_premio_budget`');
    $insB = $pdo->prepare('INSERT INTO `pdv_premio_budget` (`codice_pdv`, `prize_id`, `budget_total`, `budget_assigned`, `is_active`) VALUES (:c, :pid, :t, :a, :ia)');
    foreach ($premiRows as $pr) {
        $prizeId = (int) ($pr['id'] ?? 0);
        $qty = (int) ($pr['quantita_totale'] ?? 0);
        if ($prizeId <= 0 || $qty <= 0) {
            continue;
        }
        $split = instantwin_pdv_equal_base_and_extra_total($qty, $nPdv);
        $extraTotal = (int) $split['extra_total'];
        if ($extraTotal > 0) {
            $base = (int) $split['base'];
            $extraMap = instantwin_pdv_resolve_extra_allocations_for_prize($prizeId, $extraTotal, $pdvs, $savedExtras);
            foreach ($codes as $code) {
                $bt = $base + (int) ($extraMap[$code] ?? 0);
                $ba = (int) ($winsBudgetByPdv[$code][$prizeId] ?? 0);
                if ($ba > $bt) {
                    $bt = $ba;
                }
                $ia = ($ba < $bt) ? 1 : 0;
                $insB->execute([':c' => $code, ':pid' => $prizeId, ':t' => $bt, ':a' => $ba, ':ia' => $ia]);
            }
            continue;
        }
        $shares = instantwin_pdv_split_integers_by_weights($qty, $weightList);
        foreach ($codes as $i => $code) {
            $bt = (int) ($shares[$i] ?? 0);
            $ba = (int) ($winsBudgetByPdv[$code][$prizeId] ?? 0);
            if ($ba > $bt) {
                $bt = $ba;
            }
            $ia = ($ba < $bt) ? 1 : 0;
            $insB->execute([':c' => $code, ':pid' => $prizeId, ':t' => $bt, ':a' => $ba, ':ia' => $ia]);
        }
    }
}

/**
 * Se `pdv_premio_budget` è vuota (es. TRUNCATE manuale),
 * esegue `instantwin_pdv_rebuild_after_config_save` una tantum al primo passaggio in Instant Win PDV (front).
 * Evita di dipendere solo dal salvataggio manager.
 */
function instantwin_pdv_lazy_rebuild_if_tables_empty(PDO $pdo): void {
    if (!instantwin_pdv_enabled()) {
        return;
    }
    instantwin_pdv_ensure_schema($pdo);
    try {
        $nBudget = (int) $pdo->query('SELECT COUNT(*) FROM `pdv_premio_budget`')->fetchColumn();
        if ($nBudget > 0) {
            return;
        }
    } catch (Throwable $e) {
        error_log('instantwin_pdv_lazy_rebuild_if_tables_empty: ' . $e->getMessage());
        return;
    }
    if (instantwin_pdv_auxiliary_tables_have_data($pdo)) {
        instantwin_pdv_rebuild_budget_tables_only($pdo);

        return;
    }
    instantwin_pdv_rebuild_after_config_save($pdo, null, true);
}

/**
 * Rigenera solo `pdv_premio_budget` da `premi` + vincite esistenti, senza ricalcolare `quota_giornaliera`.
 * Usare dopo modifica pesi traffico PDV (manager), non sostituisce `instantwin_pdv_rebuild_after_config_save`.
 *
 * @param bool $ensureSchema Se false, non esegue `instantwin_pdv_ensure_schema` (necessario dentro una
 *                           transazione PDO: il DDL MySQL fa commit implicito e rompe `commit()`).
 */
function instantwin_pdv_rebuild_budget_tables_only(PDO $pdo, bool $ensureSchema = true): void {
    if (!instantwin_pdv_enabled()) {
        return;
    }
    if ($ensureSchema) {
        instantwin_pdv_ensure_schema($pdo);
    }
    $winsBudget = instantwin_pdv_load_wins_budget_by_pdv_prize($pdo);
    instantwin_pdv_resplit_pdv_budget($pdo, $winsBudget, $ensureSchema);
}

/**
 * Quando `instantwin_per_pdv` viene disattivato: TRUNCATE delle tabelle ausiliarie (non modifica `premi`).
 */
function instantwin_pdv_truncate_auxiliary_tables(PDO $pdo): void {
    instantwin_pdv_ensure_schema($pdo);
    $pdo->exec('TRUNCATE TABLE `pdv_premio_budget`');
}

/**
 * True se esistono già righe in `quota_giornaliera` o `pdv_premio_budget` (setup già fatto).
 */
function instantwin_pdv_auxiliary_tables_have_data(PDO $pdo): bool {
    try {
        $nQuota = (int) $pdo->query('SELECT COUNT(*) FROM `quota_giornaliera`')->fetchColumn();
        if ($nQuota > 0) {
            return true;
        }
        $nBudget = (int) $pdo->query('SELECT COUNT(*) FROM `pdv_premio_budget`')->fetchColumn();

        return $nBudget > 0;
    } catch (Throwable $e) {
        error_log('instantwin_pdv_auxiliary_tables_have_data: ' . $e->getMessage());

        return true;
    }
}

/**
 * Dopo salvataggio config / sync premi: ripopola `quota_giornaliera` + `pdv_premio_budget`.
 *
 * @param bool $force se false, non fa nulla se le tabelle ausiliarie contengono già dati
 * @return bool true se il rebuild è stato eseguito
 */
function instantwin_pdv_rebuild_after_config_save(PDO $pdo, ?array $config = null, bool $force = false): bool {
    if (!instantwin_pdv_enabled($config)) {
        return false;
    }
    instantwin_pdv_ensure_schema($pdo);
    if (!$force && instantwin_pdv_auxiliary_tables_have_data($pdo)) {
        error_log('instantwin_pdv_rebuild_after_config_save: skipped (quota_giornaliera or pdv_premio_budget already populated)');

        return false;
    }

    global $c_pdo;
    $prevCpdo = $c_pdo instanceof PDO ? $c_pdo : null;
    $c_pdo = $pdo;
    try {
        initializeAllDailyQuotas();
    } finally {
        $c_pdo = $prevCpdo;
    }

    $codes = instantwin_pdv_active_codes($pdo);
    if ($codes === []) {
        error_log('instantwin_pdv_rebuild: nessun codice PDV attivo in punti_vendita');

        return false;
    }

    $winsBudget = instantwin_pdv_load_wins_budget_by_pdv_prize($pdo);
    instantwin_pdv_resplit_pdv_budget($pdo, $winsBudget);

    return true;
}

/**
 * SELECT `premi` per assegnazione PDV: solo righe con budget disponibile per quel `premi.id`.
 */
function instantwin_pdv_assign_prize_prepare_premi_select(PDO $pdo, string $prize_mode, string $codice_pdv): PDOStatement {
    $prize_mode = in_array($prize_mode, ['highest_value', 'lowest_id', 'random'], true) ? $prize_mode : 'highest_value';
    $order_by = match ($prize_mode) {
        'lowest_id' => 'p.id ASC',
        'random' => 'RAND()',
        default => 'p.valore DESC, p.id ASC',
    };
    $sql = "
        SELECT p.id, p.valore
        FROM premi p
        WHERE p.quantita_assegnati < p.quantita_totale
          AND EXISTS (
            SELECT 1 FROM `pdv_premio_budget` b
            WHERE b.`codice_pdv` = :pdv_cod_bud
              AND b.`prize_id` = p.`id`
              AND b.`budget_assigned` < b.`budget_total`
              AND (b.`is_active` = 1 OR b.`is_active` IS NULL)
        )
        ORDER BY {$order_by}
        LIMIT 1
        FOR UPDATE
    ";
    $st = $pdo->prepare($sql);
    $st->bindValue(':pdv_cod_bud', trim($codice_pdv), PDO::PARAM_STR);
    return $st;
}

/** Incrementa `budget_assigned` per (`codice_pdv`, `premi.id`). */
function instantwin_pdv_assign_prize_increment_budget(PDO $pdo, string $codice_pdv, array $prize_row): bool {
    $prize = array_change_key_case($prize_row, CASE_LOWER);
    $pid = (int) ($prize['id'] ?? 0);
    if ($pid <= 0) {
        return false;
    }
    // MySQL valuta le colonne a sinistra per prime: qui `budget_assigned` nella IF è già il valore DOPO +1 → usare >= total, non +1.
    $st = $pdo->prepare('UPDATE `pdv_premio_budget` SET `budget_assigned` = `budget_assigned` + 1, `is_active` = IF(`budget_assigned` >= `budget_total`, 0, 1) WHERE `codice_pdv` = :c AND `prize_id` = :pid AND `budget_assigned` < `budget_total`');
    $st->execute([':c' => trim($codice_pdv), ':pid' => $pid]);
    return $st->rowCount() === 1;
}

/**
 * Assegnazione premio Instant Win con `instantwin_per_pdv` (budget per `premi.id` + quota giornaliera globale).
 * Stessa semantica di ritorno di `assignPrize` in `functions_instantwin.php`.
 */
function instantwin_pdv_assign_prize($token, $simulated_timestamp = null, string $codice_pdv_premio = '') {
    global $c_pdo;
    /** @var PDO $c_pdo */
    $pdvCod = trim($codice_pdv_premio);
    if ($pdvCod === '') {
        error_log('instantwin_pdv_assign_prize: codice PDV vuoto');
        return INSTANTWIN_NO_WIN;
    }

    instantwin_pdv_lazy_rebuild_if_tables_empty($c_pdo);

    $already_in_transaction = false;

    try {
        if ($c_pdo->inTransaction()) {
            $already_in_transaction = true;
        } else {
            $c_pdo->beginTransaction();
        }

        $c_pdo->exec('UPDATE premi SET is_active = 1 WHERE quantita_assegnati < quantita_totale');

        $defer_voucher = instantwin_should_defer_voucher_code_until_manager_confirm();

        $prize_mode = strtolower(trim((string) (getenv('INSTANTWIN_PRIZE_SELECTION_MODE') ?: 'highest_value')));
        $prize_mode = in_array($prize_mode, ['highest_value', 'lowest_id', 'random'], true) ? $prize_mode : 'highest_value';

        $stmt_prize = instantwin_pdv_assign_prize_prepare_premi_select($c_pdo, $prize_mode, $pdvCod);
        $stmt_prize->execute();
        $prize = $stmt_prize->fetch(PDO::FETCH_ASSOC);
        if (!$prize) {
            if (!$already_in_transaction && $c_pdo->inTransaction()) {
                $c_pdo->rollBack();
            }
            return INSTANTWIN_NO_WIN;
        }
        $prize = array_change_key_case($prize, CASE_LOWER);

        $stmt_part = $c_pdo->prepare('SELECT id, data_partecipazione, codice_partecipazione FROM partecipazioni WHERE token = :token');
        $stmt_part->bindValue(':token', $token, PDO::PARAM_STR);
        $stmt_part->execute();
        $part = $stmt_part->fetch();
        $participation_id = $part ? (int) $part['id'] : null;
        $codice_partecipazione_value = $part ? ($part['codice_partecipazione'] ?? null) : null;
        if ($simulated_timestamp !== null) {
            $win_date = date('Y-m-d H:i:s', $simulated_timestamp);
            $participation_date = date('Y-m-d', $simulated_timestamp);
        } elseif ($part && !empty($part['data_partecipazione'])) {
            $win_date = $part['data_partecipazione'];
            $participation_date_obj = new DateTime($part['data_partecipazione']);
            $participation_date = $participation_date_obj->format('Y-m-d');
        } else {
            $win_date = date('Y-m-d H:i:s');
            $participation_date = date('Y-m-d');
        }

        if (!isDateInContestRange($participation_date)) {
            if (!$already_in_transaction && $c_pdo->inTransaction()) {
                $c_pdo->rollBack();
            }
            error_log("instantwin_pdv_assign_prize: data {$participation_date} fuori range");
            return INSTANTWIN_NO_WIN;
        }

        if (!instantwin_pdv_assign_prize_increment_budget($c_pdo, $pdvCod, $prize)) {
            if (!$already_in_transaction && $c_pdo->inTransaction()) {
                $c_pdo->rollBack();
            }
            return INSTANTWIN_NO_WIN;
        }

        createDailyQuotaTable();

        $contest_dates = getContestDates();
        $is_last_day = ($contest_dates && $participation_date === $contest_dates['end_date']);
        $force_recalculate = ($participation_date === date('Y-m-d')) || $is_last_day;
        initDailyTargetIfNeeded($participation_date, null, $force_recalculate);

        $prizes_remaining = getTotalPrizesAvailable();
        $allow_catchup = $is_last_day && $prizes_remaining > 0;

        $apply_max_daily_limit = (defined('MAX_DAILY_TARGET') && MAX_DAILY_TARGET > 0);

        if ($allow_catchup) {
            if ($apply_max_daily_limit) {
                $stmt_quota = $c_pdo->prepare('
                    UPDATE quota_giornaliera
                    SET assigned = assigned + 1
                    WHERE data = :date AND assigned < :max_daily
                ');
                $stmt_quota->execute([':date' => $participation_date, ':max_daily' => MAX_DAILY_TARGET]);
            } else {
                $stmt_quota = $c_pdo->prepare('
                    UPDATE quota_giornaliera
                    SET assigned = assigned + 1
                    WHERE data = :date
                ');
                $stmt_quota->execute([':date' => $participation_date]);
            }
        } else {
            if ($apply_max_daily_limit) {
                $stmt_quota = $c_pdo->prepare('
                    UPDATE quota_giornaliera
                    SET assigned = assigned + 1
                    WHERE data = :date
                      AND assigned < target
                      AND assigned < :max_daily
                ');
                $stmt_quota->execute([':date' => $participation_date, ':max_daily' => MAX_DAILY_TARGET]);
            } else {
                $stmt_quota = $c_pdo->prepare('
                    UPDATE quota_giornaliera
                    SET assigned = assigned + 1
                    WHERE data = :date AND assigned < target
                ');
                $stmt_quota->execute([':date' => $participation_date]);
            }
        }

        if ($stmt_quota->rowCount() !== 1) {
            if (!$already_in_transaction && $c_pdo->inTransaction()) {
                $c_pdo->rollBack();
            }
            return INSTANTWIN_NO_WIN;
        }

        instantwin_pdv_increment_daily_assigned($c_pdo, $pdvCod, $participation_date);

        if ($defer_voucher) {
            $stmt_stock = $c_pdo->prepare('
                SELECT COUNT(*) FROM codici_premio
                WHERE prize_id = :prize_id AND used = 0 AND (isActive = 1 OR isActive IS NULL) AND id_codice_partecipazione IS NULL
            ');
            $stmt_stock->bindValue(':prize_id', $prize['id'], PDO::PARAM_INT);
            $stmt_stock->execute();
            if ((int) $stmt_stock->fetchColumn() < 1) {
                if (!$already_in_transaction && $c_pdo->inTransaction()) {
                    $c_pdo->rollBack();
                }
                error_log('instantwin_pdv_assign_prize: defer voucher — nessun codice_premio per prize_id ' . $prize['id']);
                return INSTANTWIN_NO_WIN;
            }

            $stmt_participation = $c_pdo->prepare('
                UPDATE partecipazioni
                SET has_won = 1, prize_id = :prize_id, prize_amount = :prize_value,
                    win_date = :win_date, annullato = 0, confermato = 0,
                    id_codice_premio = NULL
                WHERE token = :token
            ');
            $stmt_participation->execute([
                ':prize_id' => $prize['id'],
                ':prize_value' => $prize['valore'],
                ':win_date' => $win_date,
                ':token' => $token,
            ]);

            $stmt_update_prize = $c_pdo->prepare('
                UPDATE premi
                SET quantita_assegnati = quantita_assegnati + 1,
                    is_active = CASE WHEN quantita_assegnati >= quantita_totale THEN 0 ELSE 1 END
                WHERE id = :prize_id
            ');
            $stmt_update_prize->execute([':prize_id' => $prize['id']]);

            if (!$already_in_transaction && $c_pdo->inTransaction()) {
                $c_pdo->commit();
            }
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

        $id_codice_partecipazione = null;
        if ($codice_partecipazione_value) {
            $stmt_cp = $c_pdo->prepare('SELECT id FROM codici_partecipazione WHERE codice = :codice LIMIT 1');
            $stmt_cp->bindValue(':codice', $codice_partecipazione_value, PDO::PARAM_STR);
            $stmt_cp->execute();
            $cp_row = $stmt_cp->fetch();
            $id_codice_partecipazione = $cp_row ? (int) $cp_row['id'] : null;
        }

        $stmt_codice_premio = $c_pdo->prepare('
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
            if (!$already_in_transaction && $c_pdo->inTransaction()) {
                $c_pdo->rollBack();
            }
            error_log('instantwin_pdv_assign_prize: nessun codice_premio per prize_id ' . $prize['id']);
            return INSTANTWIN_NO_WIN;
        }

        $codice_premio_id = (int) $codice_premio_row['id'];
        $codice_premio_code = $codice_premio_row['codice'];
        $codice_premio_pdf_path = !empty($codice_premio_row['pdf_path']) ? trim((string) $codice_premio_row['pdf_path']) : null;

        $stmt_update_codice_premio = $c_pdo->prepare('
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
            $c_pdo,
            $codice_premio_id,
            (int) $prize['id'],
            trim((string) $codice_premio_code)
        );

        $stmt_participation = $c_pdo->prepare('
            UPDATE partecipazioni
            SET has_won = 1, prize_id = :prize_id, prize_amount = :prize_value,
                win_date = :win_date, annullato = 0, confermato = 0,
                id_codice_premio = :id_codice_premio
            WHERE token = :token
        ');
        $stmt_participation->execute([
            ':prize_id' => $prize['id'],
            ':prize_value' => $prize['valore'],
            ':win_date' => $win_date,
            ':token' => $token,
            ':id_codice_premio' => $codice_premio_id,
        ]);

        $stmt_update_prize = $c_pdo->prepare('
            UPDATE premi
            SET quantita_assegnati = quantita_assegnati + 1,
                is_active = CASE WHEN quantita_assegnati >= quantita_totale THEN 0 ELSE 1 END
            WHERE id = :prize_id
        ');
        $stmt_update_prize->execute([':prize_id' => $prize['id']]);

        if (!$already_in_transaction && $c_pdo->inTransaction()) {
            $c_pdo->commit();
        }
        return [
            'has_won' => true,
            'prize_id' => $prize['id'],
            'prize_value' => $prize['valore'],
            'prize_type' => 'Carta Prepagata Fisica',
            'codice_premio_id' => $codice_premio_id,
            'codice_premio' => $codice_premio_code,
            'pdf_path' => $codice_premio_pdf_path,
        ];
    } catch (Exception $e) {
        if (!$already_in_transaction && $c_pdo->inTransaction()) {
            $c_pdo->rollBack();
        }
        error_log('Errore instantwin_pdv_assign_prize: ' . $e->getMessage());
        return INSTANTWIN_NO_WIN;
    }
}

/**
 * Instant Win con `instantwin_per_pdv`: calendario slot per magazzino + `instantwin_pdv_assign_prize`.
 * Non richiede `functions_instantwin.php` (usa `functions_instantwin_core.php` già incluso in cima).
 */
function checkInstantWinPdv($token, $simulated_timestamp = null) {
    global $c_pdo, $contest_mode, $contest_config;

    try {
        if (!isset($contest_mode)) {
            require_once __DIR__ . '/../../inc/config.php';
        }
        if (!isset($contest_mode) || $contest_mode !== 'instantwin') {
            error_log("checkInstantWinPdv: contest_mode non è 'instantwin' (valore: " . ($contest_mode ?? 'non definito') . ") - rifiuto");
            return INSTANTWIN_NO_WIN;
        }
        if (!is_array($contest_config ?? null) || empty($contest_config['instantwin_per_pdv'])) {
            error_log('checkInstantWinPdv: instantwin_per_pdv non attivo');
            return INSTANTWIN_NO_WIN;
        }
        if (!($c_pdo instanceof PDO)) {
            return INSTANTWIN_NO_WIN;
        }

        $pdv = instantwin_pdv_codice_for_token($c_pdo, $token);
        if ($pdv === null || $pdv === '') {
            error_log('checkInstantWinPdv: id_pdv_concorso mancante o vuoto sul token');
            return INSTANTWIN_NO_WIN;
        }

        instantwin_pdv_lazy_rebuild_if_tables_empty($c_pdo);

        createTimeSlotsTable();
        createDailyQuotaTable();

        if (function_exists('instantwin_test_direct_loss_enabled') && instantwin_test_direct_loss_enabled()) {
            error_log('checkInstantWinPdv: INSTANTWIN_TEST_OUTCOME_MODE=direct_loss — perdita forzata (solo test) pdv=' . $pdv);
            return INSTANTWIN_NO_WIN;
        }

        $check_time = $simulated_timestamp !== null ? $simulated_timestamp : time();
        $current_date = date('Y-m-d', $check_time);
        $day_of_week = (int) date('N', $check_time);

        if (!isDateInContestRange($current_date)) {
            error_log("checkInstantWinPdv: data $current_date non nel range di distribuzione");
            return INSTANTWIN_NO_WIN;
        }

        $distribution_days = instantwin_get_distribution_days();
        if (!in_array($day_of_week, $distribution_days, true)) {
            return INSTANTWIN_NO_WIN;
        }

        $prizes_info = checkPrizesAvailable();
        if (!$prizes_info['available'] || $prizes_info['quantity'] <= 0) {
            return INSTANTWIN_NO_WIN;
        }
        if (!instantwin_allow_multiple_wins() && hasEmailAlreadyWon($token)) {
            return INSTANTWIN_NO_WIN;
        }

        $time_range = instantwin_get_time_range_for_day($day_of_week);
        if (!($time_range['start'] == 0 && $time_range['end'] == 24)) {
            if (!isPrizeDistributionTime($check_time)) {
                return INSTANTWIN_NO_WIN;
            }
        }

        $force_recalc = ($check_time !== null && $check_time < time());
        $target_quota = initDailyTargetIfNeeded($current_date, $check_time, $force_recalc);
        instantwin_pdv_init_daily_target_if_needed($c_pdo, $pdv, $current_date, $check_time, $force_recalc);

        if (instantwin_legacy_direct_win_enabled()) {
            if (!instantwin_legacy_direct_win_allowed($c_pdo, $current_date, $pdv)) {
                return INSTANTWIN_NO_WIN;
            }
            error_log('checkInstantWinPdv: INSTANTWIN_TEST_OUTCOME_MODE=direct_win — assegnazione senza calendario slot pdv=' . $pdv);
            return instantwin_pdv_assign_prize($token, $simulated_timestamp, $pdv);
        }

        instantwin_pdv_ensure_schema($c_pdo);
        instantwin_ensure_calendario_pdv_column();

        $effRem = instantwin_pdv_effective_daily_remaining_for_slots($c_pdo, $current_date, $pdv);
        $GLOBALS['__iw_slot_context'] = new InstantwinSlotContext($pdv, $effRem);
        try {
            $slot_window = checkTimeSlotWindow($check_time);
        } finally {
            unset($GLOBALS['__iw_slot_context']);
        }

        if (!$slot_window) {
            error_log("checkInstantWinPdv: nessuno slot per $current_date target=$target_quota pdv=$pdv effRem=$effRem");
            return INSTANTWIN_NO_WIN;
        }

        $participantsBeforeIncrement = instantwin_fascia_participants_count_today($c_pdo, $current_date, $pdv);

        try {
            $ins_participants = $c_pdo->prepare('
                INSERT INTO calendario_time_slot
                (data, slot_timestamp, finestra_inizio, finestra_fine, token_vincitore, partecipanti_totali, data_tiraggio, codice_pdv)
                VALUES (:d,:s,:ws,:we,NULL,1,NULL,:pdv)
                ON DUPLICATE KEY UPDATE partecipanti_totali = partecipanti_totali + 1
            ');
            $ins_participants->execute([
                ':d' => $current_date,
                ':s' => $slot_window['slot_timestamp'],
                ':ws' => $slot_window['window_start'],
                ':we' => $slot_window['window_end'],
                ':pdv' => $pdv,
            ]);
        } catch (Exception $e) {
            error_log('checkInstantWinPdv incremento partecipanti: ' . $e->getMessage());
        }

        $remaining = $effRem;
        if (defined('MAX_DAILY_TARGET') && MAX_DAILY_TARGET > 0) {
            if ($remaining <= 0) {
                return INSTANTWIN_NO_WIN;
            }
        }

        if (instantwin_should_block_first_fascia_claim(
            $c_pdo,
            $token,
            $current_date,
            $slot_window,
            $participantsBeforeIncrement,
            $pdv
        )) {
            return INSTANTWIN_NO_WIN;
        }

        $c_pdo->beginTransaction();
        $dataTiraggio = ($simulated_timestamp !== null)
            ? date('Y-m-d H:i:s', (int) $simulated_timestamp)
            : date('Y-m-d H:i:s');
        $claim = $c_pdo->prepare('
            UPDATE calendario_time_slot
            SET token_vincitore = :tk, data_tiraggio = :tir
            WHERE data = :d AND slot_timestamp = :s AND token_vincitore IS NULL AND codice_pdv = :pdv
        ');
        $claim->execute([
            ':tk' => $token,
            ':tir' => $dataTiraggio,
            ':d' => $current_date,
            ':s' => $slot_window['slot_timestamp'],
            ':pdv' => $pdv,
        ]);
        if ($claim->rowCount() !== 1) {
            $c_pdo->rollBack();
            return INSTANTWIN_NO_WIN;
        }
        $prize = instantwin_pdv_assign_prize($token, $simulated_timestamp, $pdv);
        if (!$prize['has_won']) {
            $c_pdo->rollBack();
            return INSTANTWIN_NO_WIN;
        }

        $c_pdo->commit();
        return $prize;
    } catch (Exception $e) {
        if (($c_pdo instanceof PDO) && $c_pdo->inTransaction()) {
            $c_pdo->rollBack();
        }
        error_log('Errore checkInstantWinPdv: ' . $e->getMessage());
        return INSTANTWIN_NO_WIN;
    }
}

/**
 * Alias esplicito verso `instantwin_pdv_assign_prize` (nome alternativo richiesto).
 */
function assignPrizePdv($token, $simulated_timestamp, string $codice_pdv_premio) {
    return instantwin_pdv_assign_prize($token, $simulated_timestamp, $codice_pdv_premio);
}
