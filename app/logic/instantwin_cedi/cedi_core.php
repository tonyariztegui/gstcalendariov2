<?php

/**
 * Ripartizione premi Instant Win per Ce.Di. e settimana (matrice manager → DB).
 */

if (!defined('IW_CEDI_MSG_IMPORTO_PREMIO_RIGA')) {
    define('IW_CEDI_MSG_IMPORTO_PREMIO_RIGA', 'Importo premio riga ');
}

/** @return array<string, array{code: string, label: string}> codice => meta */
function instantwin_cedi_hardcoded_definitions(): array
{
    return [
        '1' => ['code' => '1', 'label' => 'General'],
        '2' => ['code' => '2', 'label' => 'Meloni'],
        '3' => ['code' => '3', 'label' => 'Ingromarket'],
        '4' => ['code' => '4', 'label' => 'Essedrug'],
    ];
}

/** @return list<float> */
function instantwin_cedi_hardcoded_prize_values(): array
{
    return [100.0, 50.0, 25.0, 10.0, 5.0];
}

/** @return array<string, mixed> */
function instantwin_cedi_resolve_content_config(?array $config = null): array
{
    if ($config !== null) {
        return $config;
    }
    global $contest_config;

    return is_array($contest_config ?? null) ? $contest_config : [];
}

/** @return array<string, array{code: string, label: string}> */
function instantwin_cedi_definitions_configured(?array $config = null): array
{
    $cfg = instantwin_cedi_resolve_content_config($config);
    $custom = $cfg['cedi_definitions'] ?? null;
    if (!is_array($custom)) {
        return [];
    }
    $out = [];
    foreach ($custom as $item) {
        if (!is_array($item)) {
            continue;
        }
        $code = instantwin_cedi_sanitize_codice((string) ($item['code'] ?? ''));
        if ($code === null) {
            continue;
        }
        $label = trim((string) ($item['label'] ?? ''));
        $out[$code] = ['code' => $code, 'label' => $label !== '' ? $label : $code];
    }

    return $out;
}

if (!function_exists('instantwin_cedi_label_map_from_config')) {
    /** @return array<string, string> */
    function instantwin_cedi_label_map_from_config(?array $config = null): array
    {
        $out = [];
        foreach (instantwin_cedi_definitions_configured($config) as $code => $def) {
            if (!is_array($def)) {
                continue;
            }
            $code = trim((string) ($def['code'] ?? $code));
            if ($code === '') {
                continue;
            }
            $label = trim((string) ($def['label'] ?? ''));
            $out[$code] = $label !== '' ? $label : $code;
        }

        return $out;
    }
}

if (!function_exists('instantwin_cedi_label_map_merge_premi')) {
    /** @param array<string, string> $out */
    function instantwin_cedi_label_map_merge_premi(PDO $pdo, array &$out): void
    {
        try {
            $hasCodice = $pdo->query("SHOW COLUMNS FROM `premi` LIKE 'codice_cedi'")->fetch(PDO::FETCH_ASSOC) !== false;
            $hasNome = $pdo->query("SHOW COLUMNS FROM `premi` LIKE 'cedi_nome'")->fetch(PDO::FETCH_ASSOC) !== false;
            if (!$hasCodice) {
                return;
            }
            $sql = 'SELECT DISTINCT TRIM(`codice_cedi`) AS `c`';
            $sql .= $hasNome ? ", TRIM(COALESCE(`cedi_nome`, '')) AS `n`" : ", '' AS `n`";
            $sql .= " FROM `premi` WHERE `codice_cedi` IS NOT NULL AND TRIM(`codice_cedi`) <> '' ORDER BY `c` ASC";
            $st = $pdo->query($sql);
            while ($st && ($r = $st->fetch(PDO::FETCH_ASSOC))) {
                $c = trim((string) ($r['c'] ?? ''));
                $n = trim((string) ($r['n'] ?? ''));
                if ($c === '') {
                    continue;
                }
                if ($n !== '') {
                    $out[$c] = $n;
                } elseif (!isset($out[$c])) {
                    $out[$c] = $c;
                }
            }
        } catch (Throwable $e) {
            error_log('instantwin_cedi_label_map: ' . $e->getMessage());
        }
    }
}

if (!function_exists('instantwin_cedi_label_map_merge_pdv')) {
    /** @param array<string, string> $out */
    function instantwin_cedi_label_map_merge_pdv(PDO $pdo, array &$out): void
    {
        foreach (instantwin_cedi_definitions_from_pdv($pdo) as $def) {
            $code = trim((string) ($def['code'] ?? ''));
            if ($code === '') {
                continue;
            }
            $label = trim((string) ($def['label'] ?? ''));
            $out[$code] = $label !== '' ? $label : $code;
        }
    }
}

/**
 * Mappa codice Ce.Di. → etichetta display (nome: A, B, Nord…).
 * Fonti: content.json cedi_definitions, tabella premi (cedi_nome), poi punti_vendita (insegna).
 *
 * @return array<string, string>
 */
function instantwin_cedi_label_map(PDO $pdo, ?array $config = null): array
{
    $out = instantwin_cedi_label_map_from_config($config);
    instantwin_cedi_label_map_merge_premi($pdo, $out);
    instantwin_cedi_label_map_merge_pdv($pdo, $out);

    uksort($out, static function (string $a, string $b): int {
        return strnatcasecmp($a, $b);
    });

    return $out;
}

/** Etichetta UI: preferisce il nome Ce.Di. (es. «A»), non «CEDI 1». */
function instantwin_cedi_display_label(string $codice, string $nome = '', ?array $labelMap = null): string
{
    $nome = trim($nome);
    $codice = trim($codice);
    if ($nome !== '') {
        return $nome;
    }
    if ($codice !== '' && is_array($labelMap) && trim((string) ($labelMap[$codice] ?? '')) !== '') {
        return trim((string) $labelMap[$codice]);
    }

    return $codice !== '' ? $codice : 'Senza CEDI';
}

/** @return list<float> */
function instantwin_cedi_prize_values_configured(?array $config = null): array
{
    $vals = [];
    foreach (instantwin_cedi_prize_definitions_configured($config) as $def) {
        $vals[] = (float) $def['value'];
    }

    return $vals;
}

if (!function_exists('instantwin_cedi_parse_positive_prize_amount')) {
    function instantwin_cedi_parse_positive_prize_amount(string $raw): ?float
    {
        $s = str_replace([',', ' '], ['.', ''], trim($raw));
        if ($s === '' || !is_numeric($s)) {
            return null;
        }
        $v = round((float) $s, 2);
        if ($v <= 0 || $v > 1000000) {
            return null;
        }

        return $v;
    }
}

if (!function_exists('instantwin_cedi_prize_defs_from_custom')) {
    /**
     * @param list<mixed> $custom
     * @return list<array{value: float, tipo: string}>
     */
    function instantwin_cedi_prize_defs_from_custom(array $custom): array
    {
        $out = [];
        $seen = [];
        foreach ($custom as $item) {
            if (!is_array($item)) {
                continue;
            }
            $v = instantwin_cedi_parse_positive_prize_amount((string) ($item['value'] ?? ''));
            if ($v === null) {
                continue;
            }
            $key = instantwin_cedi_value_key($v);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $out[] = [
                'value' => $v,
                'tipo' => trim((string) ($item['tipo'] ?? '')),
            ];
        }
        usort($out, static function (array $a, array $b): int {
            return $b['value'] <=> $a['value'];
        });

        return $out;
    }
}

if (!function_exists('instantwin_cedi_prize_defs_from_legacy')) {
    /**
     * @param list<mixed> $legacy
     * @return list<array{value: float, tipo: string}>
     */
    function instantwin_cedi_prize_defs_from_legacy(array $legacy): array
    {
        $out = [];
        $seen = [];
        foreach ($legacy as $raw) {
            $v = instantwin_cedi_parse_positive_prize_amount((string) $raw);
            if ($v === null) {
                continue;
            }
            $key = instantwin_cedi_value_key($v);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $out[] = ['value' => $v, 'tipo' => ''];
        }
        usort($out, static function (array $a, array $b): int {
            return $b['value'] <=> $a['value'];
        });

        return $out;
    }
}

/**
 * @return list<array{value: float, tipo: string}>
 */
function instantwin_cedi_prize_definitions_configured(?array $config = null): array
{
    $cfg = instantwin_cedi_resolve_content_config($config);
    $custom = $cfg['cedi_prize_definitions'] ?? null;
    if (is_array($custom) && $custom !== []) {
        return instantwin_cedi_prize_defs_from_custom($custom);
    }

    $legacy = $cfg['cedi_prize_values'] ?? null;
    if (!is_array($legacy) || $legacy === []) {
        return [];
    }

    return instantwin_cedi_prize_defs_from_legacy($legacy);
}

/** @return array<string, array{code: string, label: string}> codice => meta */
function instantwin_cedi_definitions(?array $config = null): array
{
    $configured = instantwin_cedi_definitions_configured($config);
    if ($configured !== []) {
        return $configured;
    }

    return instantwin_cedi_hardcoded_definitions();
}

/** @return list<float> */
function instantwin_cedi_prize_values(?array $config = null): array
{
    $configured = instantwin_cedi_prize_values_configured($config);
    if ($configured !== []) {
        return $configured;
    }

    return instantwin_cedi_hardcoded_prize_values();
}

/**
 * @return list<array{code: string, label: string}>
 */
function instantwin_cedi_definitions_from_pdv(PDO $pdo): array
{
    $out = [];
    try {
        $st = $pdo->query("
            SELECT DISTINCT TRIM(`codice_cedi`) AS c, MAX(NULLIF(TRIM(`insegna`), '')) AS label
            FROM `punti_vendita`
            WHERE `active` = 1
              AND `codice_cedi` IS NOT NULL
              AND TRIM(`codice_cedi`) <> ''
            GROUP BY TRIM(`codice_cedi`)
            ORDER BY c ASC
        ");
        if (!$st) {
            return [];
        }
        while ($row = $st->fetch(PDO::FETCH_ASSOC)) {
            $code = instantwin_cedi_sanitize_codice((string) ($row['c'] ?? ''));
            if ($code === null) {
                continue;
            }
            $label = trim((string) ($row['label'] ?? ''));
            $out[] = ['code' => $code, 'label' => $label !== '' ? $label : $code];
        }
    } catch (Throwable $e) {
        error_log('instantwin_cedi_definitions_from_pdv: ' . $e->getMessage());
    }

    return $out;
}

/** @return list<array{code: string, label: string}> */
function instantwin_cedi_default_definitions_ui(): array
{
    return [['code' => '1', 'label' => 'Generale test']];
}

/**
 * @param array<int, array{code?: string, label?: string}> $rows
 * @return list<array{code: string, label: string}>
 */
function instantwin_cedi_parse_definitions_post(array $rows, array &$errors): array
{
    $out = [];
    $seen = [];
    foreach ($rows as $idx => $row) {
        if (!is_array($row)) {
            continue;
        }
        $codeRaw = trim((string) ($row['code'] ?? ''));
        $label = trim((string) ($row['label'] ?? ''));
        if ($codeRaw === '' && $label === '') {
            continue;
        }
        $code = instantwin_cedi_sanitize_codice($codeRaw);
        if ($code === null) {
            $errors[] = 'Ce.Di. riga ' . ($idx + 1) . ': codice non valido — usare solo cifre (1, 2, 3). La lettera (A, B, C) va nel campo Nome.';
            continue;
        }
        if ($label === '') {
            $errors[] = 'Ce.Di. riga ' . ($idx + 1) . ': inserire un nome.';
            continue;
        }
        if (isset($seen[$code])) {
            $errors[] = 'Ce.Di. duplicato nel codice «' . $code . '».';
            continue;
        }
        $seen[$code] = true;
        if (mb_strlen($label) > 128) {
            $errors[] = 'Ce.Di. «' . $label . '»: nome troppo lungo (max 128).';
            continue;
        }
        $out[] = ['code' => $code, 'label' => $label];
    }

    return $out;
}

/**
 * @param list<string|int|float> $rows
 * @return list<float>
 */
function instantwin_cedi_parse_prize_values_post(array $rows, array &$errors): array
{
    $vals = [];
    foreach ($rows as $idx => $raw) {
        $s = str_replace([',', ' '], ['.', ''], trim((string) $raw));
        if ($s === '') {
            continue;
        }
        if (!is_numeric($s)) {
            $errors[] = IW_CEDI_MSG_IMPORTO_PREMIO_RIGA . ($idx + 1) . ': valore non numerico.';
            continue;
        }
        $v = round((float) $s, 2);
        if ($v <= 0) {
            $errors[] = IW_CEDI_MSG_IMPORTO_PREMIO_RIGA . ($idx + 1) . ': deve essere maggiore di zero.';
            continue;
        }
        if ($v > 1000000) {
            $errors[] = IW_CEDI_MSG_IMPORTO_PREMIO_RIGA . ($idx + 1) . ': supera 1.000.000.';
            continue;
        }
        $vals[] = $v;
    }
    $vals = array_values(array_unique($vals, SORT_REGULAR));
    rsort($vals, SORT_NUMERIC);

    return $vals;
}

if (!function_exists('instantwin_cedi_parse_prize_definition_row')) {
    /**
     * @param array<string, mixed> $row
     * @param array<string, true> $seen
     * @param list<string> $errors
     * @return array{value: float, tipo: string}|null
     */
    function instantwin_cedi_parse_prize_definition_row(array $row, int $idx, array &$seen, array &$errors): ?array
    {
        $valueRaw = trim((string) ($row['value'] ?? ''));
        $tipo = trim((string) ($row['tipo'] ?? ''));
        if ($valueRaw === '' && $tipo === '') {
            return null;
        }
        $s = str_replace([',', ' '], ['.', ''], $valueRaw);
        if ($s === '' || !is_numeric($s)) {
            $errors[] = IW_CEDI_MSG_IMPORTO_PREMIO_RIGA . ($idx + 1) . ': valore numerico obbligatorio (€).';
            return null;
        }
        $v = round((float) $s, 2);
        if ($v <= 0) {
            $errors[] = IW_CEDI_MSG_IMPORTO_PREMIO_RIGA . ($idx + 1) . ': deve essere maggiore di zero.';
            return null;
        }
        if ($v > 1000000) {
            $errors[] = IW_CEDI_MSG_IMPORTO_PREMIO_RIGA . ($idx + 1) . ': supera 1.000.000.';
            return null;
        }
        $key = instantwin_cedi_value_key($v);
        if (isset($seen[$key])) {
            $errors[] = 'Importo premio duplicato: ' . $key . ' €.';
            return null;
        }
        if ($tipo !== '' && mb_strlen($tipo) < 2) {
            $errors[] = 'Tipo premio riga ' . ($idx + 1) . ': almeno 2 caratteri, oppure lasciare vuoto per «Gift card ' . $key . ' euro».';
            return null;
        }
        if ($tipo !== '' && mb_strlen($tipo) > 255) {
            $errors[] = 'Tipo premio riga ' . ($idx + 1) . ': massimo 255 caratteri.';
            return null;
        }
        $seen[$key] = true;

        return ['value' => $v, 'tipo' => $tipo];
    }
}

/**
 * @param array<int, array{value?: string|float, tipo?: string}> $rows
 * @return list<array{value: float, tipo: string}>
 */
function instantwin_cedi_parse_prize_definitions_post(array $rows, array &$errors): array
{
    $out = [];
    $seen = [];
    foreach ($rows as $idx => $row) {
        if (!is_array($row)) {
            continue;
        }
        $parsed = instantwin_cedi_parse_prize_definition_row($row, (int) $idx, $seen, $errors);
        if ($parsed !== null) {
            $out[] = $parsed;
        }
    }
    usort($out, static function (array $a, array $b): int {
        return $b['value'] <=> $a['value'];
    });

    return $out;
}

function instantwin_cedi_ensure_schema(PDO $pdo): void
{
    $hasPbs = false;
    try {
        $st = $pdo->query("SHOW TABLES LIKE 'premi_budget_settimana'");
        $hasPbs = $st && (bool) $st->fetch();
    } catch (Throwable $e) {
    }
    if (!$hasPbs) {
        $sqlPath = dirname(__DIR__, 3) . '/sql/premi_budget_settimana.sql';
        if (is_readable($sqlPath)) {
            $pdo->exec((string) file_get_contents($sqlPath));
        } else {
            $pdo->exec("CREATE TABLE IF NOT EXISTS `premi_budget_settimana` (
                `id` int NOT NULL AUTO_INCREMENT,
                `prize_id` int NOT NULL,
                `numero_settimana` int NOT NULL,
                `budget_total` int NOT NULL DEFAULT 0,
                `budget_assigned` int NOT NULL DEFAULT 0,
                `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uk_premi_budget_settimana` (`prize_id`,`numero_settimana`),
                KEY `idx_pbs_settimana` (`numero_settimana`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        }
    }
    instantwin_cedi_ensure_daily_quota_table($pdo);
    if (file_exists(__DIR__ . '/../instantwin_cedi/cedi_calendar_schema.php')) {
        require_once __DIR__ . '/cedi_calendar_schema.php';
        if (function_exists('instantwin_cedi_ensure_calendar_schema')) {
            instantwin_cedi_ensure_calendar_schema($pdo);
        }
    }
}

function instantwin_cedi_ensure_daily_quota_table(PDO $pdo): void
{
    try {
        $st = $pdo->query("SHOW TABLES LIKE 'cedi_quota_giornaliera'");
        if ($st && $st->fetch()) {
            return;
        }
    } catch (Throwable $e) {
    }
    $sqlPath = dirname(__DIR__, 3) . '/sql/cedi_quota_giornaliera.sql';
    if (is_readable($sqlPath)) {
        $pdo->exec((string) file_get_contents($sqlPath));
        return;
    }
    $pdo->exec("CREATE TABLE IF NOT EXISTS `cedi_quota_giornaliera` (
        `id` int NOT NULL AUTO_INCREMENT,
        `data` date NOT NULL,
        `codice_cedi` varchar(16) NOT NULL,
        `target` int NOT NULL DEFAULT 0,
        `assigned` int NOT NULL DEFAULT 0,
        `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `uk_cedi_quota_giornaliera` (`data`,`codice_cedi`),
        KEY `idx_cqg_data` (`data`),
        KEY `idx_cqg_cedi` (`codice_cedi`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

/** Evita log/sync ripetuti durante simulate_players.php */
function instantwin_cedi_simulation_context_active(): bool
{
    return !empty($GLOBALS['instantwin_simulation_context']);
}

if (!function_exists('instantwin_cedi_enabled')) {
    function instantwin_cedi_enabled(?array $cfg = null): bool
    {
        global $contest_config;
        $c = $cfg ?? $contest_config ?? null;

        return is_array($c) && !empty($c['instantwin_per_cedi']);
    }
}

/** @return bool */
function instantwin_cedi_daily_quota_enabled(PDO $pdo): bool
{
    if (!instantwin_cedi_enabled()) {
        return false;
    }

    return instantwin_cedi_weekly_budget_enabled($pdo);
}

/** @return bool */
function instantwin_cedi_weekly_budget_enabled(PDO $pdo): bool
{
    try {
        instantwin_cedi_ensure_schema($pdo);
        return (int) $pdo->query('SELECT COUNT(*) FROM `premi_budget_settimana`')->fetchColumn() > 0;
    } catch (Throwable $e) {
        return false;
    }
}

function instantwin_cedi_contest_week_count(?string $startDate = null, ?string $endDate = null): int
{
    if ($startDate === null || $endDate === null) {
        $dates = function_exists('getContestDates') ? getContestDates() : null;
        if (!$dates) {
            return 6;
        }
        $startDate = $dates['start_date'];
        $endDate = $dates['end_date'];
    }
    try {
        $start = new DateTime($startDate);
        $end = new DateTime($endDate);
    } catch (Exception $e) {
        return 6;
    }
    $start->setTime(0, 0, 0);
    $end->setTime(0, 0, 0);
    if ($end < $start) {
        return 1;
    }
    $days = (int) $start->diff($end)->days + 1;
    return max(1, (int) ceil($days / 7));
}

function instantwin_cedi_week_number_for_date(string $dateYmd, ?string $startDate = null): int
{
    if ($startDate === null) {
        $dates = function_exists('getContestDates') ? getContestDates() : null;
        $startDate = $dates['start_date'] ?? $dateYmd;
    }
    try {
        $start = new DateTime($startDate);
        $current = new DateTime($dateYmd);
    } catch (Exception $e) {
        return 1;
    }
    $start->setTime(0, 0, 0);
    $current->setTime(0, 0, 0);
    if ($current < $start) {
        return 1;
    }
    $days = (int) $start->diff($current)->days;
    $week = (int) floor($days / 7) + 1;
    $max = instantwin_cedi_contest_week_count($startDate, null);
    return min(max(1, $week), $max);
}

/**
 * Répartit $total en $n parts entières (reliquats sur les dernières semaines).
 *
 * @return list<int>
 */
function instantwin_cedi_split_total_across_weeks(int $total, int $weekCount): array
{
    $weekCount = max(1, $weekCount);
    if ($total <= 0) {
        return array_fill(0, $weekCount, 0);
    }
    $base = intdiv($total, $weekCount);
    $rem = $total % $weekCount;
    $out = [];
    for ($i = 0; $i < $weekCount; $i++) {
        $out[] = $base + ($i >= $weekCount - $rem ? 1 : 0);
    }
    return $out;
}

/**
 * Premi ancora da assegnare per una settimana (somma budget settimanali Ce.Di.).
 */
function instantwin_cedi_week_prizes_remaining(PDO $pdo, int $weekNo, bool $subtractAssigned = true): int
{
    if ($weekNo < 1) {
        return 0;
    }
    instantwin_cedi_ensure_schema($pdo);
    if ($subtractAssigned) {
        $sql = '
            SELECT COALESCE(SUM(GREATEST(0, b.`budget_total` - b.`budget_assigned`)), 0)
            FROM `premi_budget_settimana` b
            INNER JOIN `premi` p ON p.`id` = b.`prize_id`
            WHERE b.`numero_settimana` = :w
              AND p.`quantita_assegnati` < p.`quantita_totale`
        ';
    } else {
        $sql = '
            SELECT COALESCE(SUM(b.`budget_total`), 0)
            FROM `premi_budget_settimana` b
            INNER JOIN `premi` p ON p.`id` = b.`prize_id`
            WHERE b.`numero_settimana` = :w
              AND p.`quantita_assegnati` < p.`quantita_totale`
        ';
    }
    $st = $pdo->prepare($sql);
    $st->execute([':w' => $weekNo]);
    return (int) ($st->fetchColumn() ?: 0);
}

function instantwin_cedi_week_budget_carryover_enabled(): bool
{
    $raw = getenv('INSTANTWIN_CEDI_WEEK_BUDGET_CARRYOVER');
    if ($raw === false || $raw === '') {
        return true;
    }
    $parsed = filter_var($raw, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

    return $parsed !== false;
}

/**
 * Date di distribuzione (giorni attivi) per una settimana concorso.
 *
 * @return list<string> Y-m-d
 */
function instantwin_cedi_distribution_dates_in_week(int $weekNo, ?string $startDate = null): array
{
    if ($weekNo < 1 || !function_exists('getAllContestDistributionDates')) {
        return [];
    }
    if ($startDate === null) {
        $dates = function_exists('getContestDates') ? getContestDates() : null;
        $startDate = is_array($dates) ? ($dates['start_date'] ?? date('Y-m-d')) : date('Y-m-d');
    }
    $out = [];
    foreach (getAllContestDistributionDates() as $dateYmd) {
        if (instantwin_cedi_week_number_for_date($dateYmd, $startDate) === $weekNo) {
            $out[] = $dateYmd;
        }
    }

    return $out;
}

function instantwin_cedi_is_week_catchup_day(PDO $pdo, string $dateYmd): bool
{
    $contest = function_exists('getContestDates') ? getContestDates() : null;
    $start = is_array($contest) ? ($contest['start_date'] ?? $dateYmd) : $dateYmd;
    $weekNo = instantwin_cedi_week_number_for_date($dateYmd, $start);
    $lastWeekDay = instantwin_cedi_last_distribution_date_of_week($pdo, $weekNo, $start);
    if ($lastWeekDay !== null && $dateYmd === $lastWeekDay) {
        return true;
    }

    return is_array($contest) && $dateYmd === ($contest['end_date'] ?? '');
}

if (!function_exists('instantwin_cedi_roll_carryover_begin_tx')) {
    function instantwin_cedi_roll_carryover_begin_tx(PDO $pdo, bool $dryRun): bool
    {
        if ($dryRun || $pdo->inTransaction()) {
            return false;
        }
        $pdo->beginTransaction();

        return true;
    }
}

if (!function_exists('instantwin_cedi_roll_carryover_apply_rows')) {
    /**
     * @param list<array<string, mixed>> $rows
     * @return array{moved: int, rows: list<array<string, int>>, errors: list<string>}
     */
    function instantwin_cedi_roll_carryover_apply_rows(PDO $pdo, array $rows, int $toWeek, bool $dryRun): array
    {
        $totalMoved = 0;
        $details = [];
        $errors = [];
        $updFrom = $pdo->prepare('
            UPDATE `premi_budget_settimana`
            SET `budget_total` = `budget_assigned`
            WHERE `id` = ?
              AND `budget_assigned` < `budget_total`
        ');
        $updTo = $pdo->prepare('
            UPDATE `premi_budget_settimana`
            SET `budget_total` = `budget_total` + :add
            WHERE `prize_id` = :pid AND `numero_settimana` = :tw
        ');
        foreach ($rows as $row) {
            $rem = (int) ($row['rem'] ?? 0);
            if ($rem <= 0) {
                continue;
            }
            $prizeId = (int) ($row['prize_id'] ?? 0);
            $totalMoved += $rem;
            $details[] = ['prize_id' => $prizeId, 'moved' => $rem];
            if ($dryRun) {
                continue;
            }
            $updTo->execute([':add' => $rem, ':pid' => $prizeId, ':tw' => $toWeek]);
            if ($updTo->rowCount() === 0) {
                $errors[] = 'Riga settimana ' . $toWeek . ' mancante per prize_id ' . $prizeId;
                continue;
            }
            $updFrom->execute([(int) $row['id']]);
        }

        return ['moved' => $totalMoved, 'rows' => $details, 'errors' => $errors];
    }
}

/**
 * Reporte i premi non assegnati da una settimana alla successiva (budget_total).
 *
 * @return array{moved: int, rows: list<array<string, int>>, errors: list<string>}
 */
function instantwin_cedi_roll_week_budget_carryover(PDO $pdo, int $fromWeek, int $toWeek, bool $dryRun = false): array
{
    instantwin_cedi_ensure_schema($pdo);
    if ($fromWeek < 1 || $toWeek <= $fromWeek) {
        return ['moved' => 0, 'rows' => [], 'errors' => ['Settimane non valide (from < to richiesto).']];
    }

    $st = $pdo->prepare('
        SELECT b.`id`, b.`prize_id`, b.`budget_total`, b.`budget_assigned`,
               GREATEST(0, b.`budget_total` - b.`budget_assigned`) AS `rem`
        FROM `premi_budget_settimana` b
        INNER JOIN `premi` p ON p.`id` = b.`prize_id`
        WHERE b.`numero_settimana` = :fw
          AND p.`quantita_assegnati` < p.`quantita_totale`
    ');
    $st->execute([':fw' => $fromWeek]);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $ownsTransaction = instantwin_cedi_roll_carryover_begin_tx($pdo, $dryRun);
    try {
        $result = instantwin_cedi_roll_carryover_apply_rows($pdo, $rows, $toWeek, $dryRun);
        if (!$dryRun && $ownsTransaction) {
            $pdo->commit();
        }
    } catch (Throwable $e) {
        if (!$dryRun && $ownsTransaction && $pdo->inTransaction()) {
            $pdo->rollBack();
        }

        return ['moved' => 0, 'rows' => [], 'errors' => [$e->getMessage()]];
    }

    if ($result['moved'] > 0 && !instantwin_cedi_simulation_context_active()) {
        error_log('instantwin_cedi_roll_week_budget_carryover: S' . $fromWeek . ' → S' . $toWeek . ', moved=' . $result['moved'] . ($dryRun ? ' (dry-run)' : ''));
    }

    return $result;
}

/**
 * All'inizio di una nuova settimana, riporta automaticamente il budget non assegnato.
 */
function instantwin_cedi_auto_carryover_if_needed(PDO $pdo, string $asOfDate): void
{
    if (!instantwin_cedi_week_budget_carryover_enabled()) {
        return;
    }
    $contest = function_exists('getContestDates') ? getContestDates() : null;
    if (!$contest) {
        return;
    }
    $start = (string) ($contest['start_date'] ?? $asOfDate);
    $weekNo = instantwin_cedi_week_number_for_date($asOfDate, $start);
    if ($weekNo <= 1) {
        return;
    }
    $prevWeek = $weekNo - 1;
    $prevRem = instantwin_cedi_week_prizes_remaining($pdo, $prevWeek, true);
    if ($prevRem <= 0) {
        return;
    }
    $result = instantwin_cedi_roll_week_budget_carryover($pdo, $prevWeek, $weekNo, false);
    if (($result['moved'] ?? 0) > 0) {
        instantwin_cedi_resync_daily_quota_targets($pdo, $asOfDate);
        if (!instantwin_cedi_simulation_context_active()) {
            error_log('instantwin_cedi_auto_carryover_if_needed: S' . $prevWeek . ' → S' . $weekNo
                . ', moved=' . (int) $result['moved'] . ' (quota giornaliere ricalcolate)');
        }
    }
    if (!empty($result['errors']) && !instantwin_cedi_simulation_context_active()) {
        error_log('instantwin_cedi_auto_carryover_if_needed: ' . implode('; ', $result['errors']));
    }
}

/**
 * Carryover settimanale — indipendente dalla quota giornaliera Ce.Di. (max 1× per data / richiesta).
 */
function instantwin_cedi_ensure_week_budget_carryover(PDO $pdo, string $asOfDate): void
{
    if (!instantwin_cedi_weekly_budget_enabled($pdo)) {
        return;
    }
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $asOfDate)) {
        $asOfDate = date('Y-m-d');
    }
    if (!isset($GLOBALS['instantwin_cedi_carryover_done']) || !is_array($GLOBALS['instantwin_cedi_carryover_done'])) {
        $GLOBALS['instantwin_cedi_carryover_done'] = [];
    }
    if (!empty($GLOBALS['instantwin_cedi_carryover_done'][$asOfDate])) {
        return;
    }
    $GLOBALS['instantwin_cedi_carryover_done'][$asOfDate] = true;
    instantwin_cedi_auto_carryover_if_needed($pdo, $asOfDate);
}

function instantwin_cedi_clear_daily_quota_sync_cache(): void
{
    $GLOBALS['instantwin_cedi_synced_for_date'] = [];
}

/**
 * Si Σ budget settimanali < Σ quantità premi, ajoute l'écart sur la dernière semaine.
 */
function instantwin_cedi_reconcile_budget_totals(PDO $pdo, int $lastWeekNo): void
{
    if ($lastWeekNo < 1) {
        return;
    }
    instantwin_cedi_ensure_schema($pdo);
    $premiSum = (int) $pdo->query('
        SELECT COALESCE(SUM(`quantita_totale`), 0)
        FROM `premi`
        WHERE `codice_cedi` IS NOT NULL AND TRIM(`codice_cedi`) <> ""
    ')->fetchColumn();
    $budgetSum = (int) $pdo->query('SELECT COALESCE(SUM(`budget_total`), 0) FROM `premi_budget_settimana`')->fetchColumn();
    $diff = $premiSum - $budgetSum;
    if ($diff <= 0) {
        return;
    }
    $st = $pdo->prepare('
        SELECT b.`id`
        FROM `premi_budget_settimana` b
        INNER JOIN `premi` p ON p.`id` = b.`prize_id`
        WHERE b.`numero_settimana` = :w
        ORDER BY p.`valore` DESC, b.`id` ASC
    ');
    $st->execute([':w' => $lastWeekNo]);
    $ids = $st->fetchAll(PDO::FETCH_COLUMN);
    if ($ids === []) {
        return;
    }
    $upd = $pdo->prepare('UPDATE `premi_budget_settimana` SET `budget_total` = `budget_total` + 1 WHERE `id` = ?');
    $n = count($ids);
    for ($i = 0; $i < $diff; $i++) {
        $upd->execute([(int) $ids[$i % $n]]);
    }
    error_log("instantwin_cedi_reconcile_budget_totals: +{$diff} sur settimana {$lastWeekNo} (premi={$premiSum}, budget={$budgetSum})");
}

/**
 * Budget settimanale (somma budget_total) per un Ce.Di. — plafond du plan quota giornaliero.
 * Allineato a premi_budget_settimana : le stock globali (premi.quantita_*) sono verificati
 * all'assegnazione, non nel piano cedi_quota_giornaliera (evita Σ target < budget settimanale).
 */
function instantwin_cedi_week_budget_cap_for_cedi(PDO $pdo, int $weekNo, string $cedi): int
{
    if ($weekNo < 1 || $cedi === '') {
        return 0;
    }
    instantwin_cedi_ensure_schema($pdo);
    $st = $pdo->prepare('
        SELECT COALESCE(SUM(b.`budget_total`), 0)
        FROM `premi_budget_settimana` b
        INNER JOIN `premi` p ON p.`id` = b.`prize_id`
        WHERE b.`numero_settimana` = :w
          AND p.`codice_cedi` = :c
    ');
    $st->execute([':w' => $weekNo, ':c' => $cedi]);

    return (int) ($st->fetchColumn() ?: 0);
}

/**
 * Budget settimanale (somma premi) per un Ce.Di.
 */
function instantwin_cedi_week_total_for_cedi(PDO $pdo, int $weekNo, string $cedi, bool $subtractAssigned = true): int
{
    if ($weekNo < 1 || $cedi === '') {
        return 0;
    }
    instantwin_cedi_ensure_schema($pdo);
    if ($subtractAssigned) {
        $sql = '
            SELECT COALESCE(SUM(GREATEST(0, b.`budget_total` - b.`budget_assigned`)), 0)
            FROM `premi_budget_settimana` b
            INNER JOIN `premi` p ON p.`id` = b.`prize_id`
            WHERE b.`numero_settimana` = :w
              AND p.`codice_cedi` = :c
        ';
    } else {
        $sql = '
            SELECT COALESCE(SUM(b.`budget_total`), 0)
            FROM `premi_budget_settimana` b
            INNER JOIN `premi` p ON p.`id` = b.`prize_id`
            WHERE b.`numero_settimana` = :w
              AND p.`codice_cedi` = :c
        ';
    }
    $st = $pdo->prepare($sql);
    $st->execute([':w' => $weekNo, ':c' => $cedi]);
    return (int) ($st->fetchColumn() ?: 0);
}

/**
 * @return list<string>
 */
function instantwin_cedi_distribution_dates_for_plan(PDO $pdo, string $asOfDate, bool $fullContestCalendar): array
{
    if (!function_exists('getRemainingDistributionDates') || !function_exists('getAllContestDistributionDates')) {
        return [];
    }
    return $fullContestCalendar
        ? getAllContestDistributionDates()
        : getRemainingDistributionDates($asOfDate);
}

/**
 * Ultimo giorno di distribuzione del concorso per la settimana concorso $weekNo.
 */
function instantwin_cedi_last_distribution_date_of_week(PDO $pdo, int $weekNo, ?string $contestStart = null): ?string
{
    $dates = instantwin_cedi_distribution_dates_for_plan($pdo, $contestStart ?? date('Y-m-d'), true);
    if ($dates === []) {
        return null;
    }
    $start = $contestStart;
    if ($start === null) {
        $contest = function_exists('getContestDates') ? getContestDates() : null;
        $start = is_array($contest) ? ($contest['start_date'] ?? date('Y-m-d')) : date('Y-m-d');
    }
    $last = null;
    foreach ($dates as $d) {
        if (instantwin_cedi_week_number_for_date($d, $start) === $weekNo) {
            $last = $d;
        }
    }
    return $last;
}

/**
 * Somme des `assigned` journaliers pour un Ce.Di. sur une liste de dates de distribution.
 *
 * @param list<string> $dates Y-m-d
 */
function instantwin_cedi_sum_assigned_for_dates(PDO $pdo, array $dates, string $cedi): int
{
    if ($dates === [] || $cedi === '') {
        return 0;
    }
    $sum = 0;
    foreach ($dates as $d) {
        $row = instantwin_cedi_get_daily_quota_row($pdo, $d, $cedi);
        if ($row !== null) {
            $sum += (int) ($row['assigned'] ?? 0);
        }
    }

    return $sum;
}

if (!function_exists('instantwin_cedi_split_week_dates_past_future')) {
    /**
     * @param list<string> $allWeekDates
     * @return array{0: list<string>, 1: list<string>}
     */
    function instantwin_cedi_split_week_dates_past_future(array $allWeekDates, string $asOfDate, bool $fullContestCalendar): array
    {
        $futureDates = [];
        $pastDates = [];
        foreach ($allWeekDates as $d) {
            if ($fullContestCalendar || $d >= $asOfDate) {
                $futureDates[] = $d;
            } else {
                $pastDates[] = $d;
            }
        }

        return [$pastDates, $futureDates];
    }
}

if (!function_exists('instantwin_cedi_week_has_remaining_budget')) {
    function instantwin_cedi_week_has_remaining_budget(PDO $pdo, int $weekNo): bool
    {
        foreach (array_keys(instantwin_cedi_definitions()) as $code) {
            if (instantwin_cedi_week_total_for_cedi($pdo, $weekNo, (string) $code, true) > 0) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('instantwin_cedi_plan_set_quota')) {
    /** @param array<string, array<string, int>> $plan */
    function instantwin_cedi_plan_set_quota(array &$plan, string $date, string $code, int $qty): void
    {
        if (!isset($plan[$date])) {
            $plan[$date] = [];
        }
        $plan[$date][$code] = $qty;
    }
}

if (!function_exists('instantwin_cedi_plan_merge_historical_targets')) {
    /**
     * @param array<string, array<string, int>> $plan
     * @param list<string> $pastDates
     */
    function instantwin_cedi_plan_merge_historical_targets(PDO $pdo, array &$plan, array $pastDates, string $code): void
    {
        foreach ($pastDates as $d) {
            $row = instantwin_cedi_get_daily_quota_row($pdo, $d, $code);
            if ($row === null) {
                continue;
            }
            $historicalTarget = max((int) $row['assigned'], (int) $row['target']);
            if ($historicalTarget <= 0) {
                continue;
            }
            if (!isset($plan[$d])) {
                $plan[$d] = [];
            }
            $plan[$d][$code] = max($plan[$d][$code] ?? 0, $historicalTarget);
        }
    }
}

if (!function_exists('instantwin_cedi_plan_distribute_future_for_cedi')) {
    /**
     * @param array<string, array<string, int>> $plan
     * @param list<string> $pastDates
     * @param list<string> $futureDates
     */
    function instantwin_cedi_plan_distribute_future_for_cedi(
        PDO $pdo,
        array &$plan,
        int $weekNo,
        string $code,
        array $pastDates,
        array $futureDates
    ): void {
        $budgetCap = instantwin_cedi_week_budget_cap_for_cedi($pdo, $weekNo, $code);
        if ($budgetCap <= 0 || $futureDates === []) {
            return;
        }

        $pastAssignedSum = instantwin_cedi_sum_assigned_for_dates($pdo, $pastDates, $code);
        $toDistribute = $budgetCap - $pastAssignedSum;
        if ($toDistribute <= 0) {
            return;
        }

        if (count($futureDates) === 1) {
            instantwin_cedi_plan_set_quota($plan, $futureDates[0], $code, $toDistribute);

            return;
        }

        $weekPlan = instantwin_distribute_quota_on_dates($toDistribute, $futureDates);
        foreach ($weekPlan as $d => $q) {
            instantwin_cedi_plan_set_quota($plan, (string) $d, $code, (int) $q);
        }
    }
}

if (!function_exists('instantwin_cedi_build_daily_quota_plan_for_week')) {
    /** @param array<string, array<string, int>> $plan */
    function instantwin_cedi_build_daily_quota_plan_for_week(
        PDO $pdo,
        array &$plan,
        int $weekNo,
        string $start,
        string $asOfDate,
        bool $fullContestCalendar,
        bool $subtractAssigned
    ): void {
        $allWeekDates = instantwin_cedi_distribution_dates_in_week($weekNo, $start);
        if ($allWeekDates === []) {
            return;
        }

        [$pastDates, $futureDates] = instantwin_cedi_split_week_dates_past_future(
            $allWeekDates,
            $asOfDate,
            $fullContestCalendar
        );

        if ($futureDates === [] && $subtractAssigned && !instantwin_cedi_week_has_remaining_budget($pdo, $weekNo)) {
            return;
        }

        foreach (instantwin_cedi_definitions() as $code => $_meta) {
            $code = (string) $code;
            instantwin_cedi_plan_merge_historical_targets($pdo, $plan, $pastDates, $code);
            instantwin_cedi_plan_distribute_future_for_cedi($pdo, $plan, $weekNo, $code, $pastDates, $futureDates);
        }
    }
}

/**
 * Piano quota giornaliera per Ce.Di. : [data][codice_cedi] => target.
 *
 * Hiérarchie : rebalance quotidien intra-semaine (assigned réels) > carryover
 * premi_budget_settimana > catchup dernier jour de distribution (filet).
 * Périmètre : une semaine concours × un Ce.Di. à la fois (INSTANTWIN_DISTRIBUTION_DAYS).
 *
 * @return array<string, array<string, int>>
 */
function instantwin_cedi_build_daily_quota_plan(PDO $pdo, string $asOfDate, bool $fullContestCalendar): array
{
    if (!instantwin_cedi_weekly_budget_enabled($pdo)) {
        return [];
    }
    if (!function_exists('instantwin_distribute_quota_on_dates')) {
        return [];
    }

    $contest = function_exists('getContestDates') ? getContestDates() : null;
    $start = is_array($contest) ? ($contest['start_date'] ?? $asOfDate) : $asOfDate;
    $maxWeek = instantwin_cedi_contest_week_count($start, is_array($contest) ? ($contest['end_date'] ?? null) : null);
    $subtractAssigned = !$fullContestCalendar;

    /** @var array<string, array<string, int>> $plan */
    $plan = [];

    for ($weekNo = 1; $weekNo <= $maxWeek; $weekNo++) {
        instantwin_cedi_build_daily_quota_plan_for_week(
            $pdo,
            $plan,
            $weekNo,
            $start,
            $asOfDate,
            $fullContestCalendar,
            $subtractAssigned
        );
    }

    return $plan;
}

/**
 * @param array<string, array<string, int>> $plan
 */
function instantwin_cedi_persist_daily_targets(PDO $pdo, array $plan): int
{
    instantwin_cedi_ensure_daily_quota_table($pdo);
    $n = 0;
    $sel = $pdo->prepare('SELECT `assigned` FROM `cedi_quota_giornaliera` WHERE `data` = :d AND `codice_cedi` = :c');
    $upd = $pdo->prepare('UPDATE `cedi_quota_giornaliera` SET `target` = :t WHERE `data` = :d AND `codice_cedi` = :c');
    $ins = $pdo->prepare('
        INSERT INTO `cedi_quota_giornaliera` (`data`, `codice_cedi`, `target`, `assigned`)
        VALUES (:d, :c, :t, 0)
    ');

    foreach ($plan as $dateStr => $byCedi) {
        if (!is_array($byCedi)) {
            continue;
        }
        foreach ($byCedi as $cedi => $target) {
            $target = max(0, (int) $target);
            $cedi = (string) $cedi;
            $sel->execute([':d' => $dateStr, ':c' => $cedi]);
            $row = $sel->fetch(PDO::FETCH_ASSOC);
            if ($row !== false) {
                $assigned = (int) ($row['assigned'] ?? 0);
                if ($target < $assigned) {
                    $target = $assigned;
                }
                $upd->execute([':d' => $dateStr, ':c' => $cedi, ':t' => $target]);
            } else {
                $ins->execute([':d' => $dateStr, ':c' => $cedi, ':t' => $target]);
            }
            $n++;
        }
    }

    return $n;
}

/**
 * @return array{target: int, assigned: int}|null
 */
function instantwin_cedi_get_daily_quota_row(PDO $pdo, string $dateYmd, string $cedi): ?array
{
    instantwin_cedi_ensure_daily_quota_table($pdo);
    $st = $pdo->prepare('
        SELECT `target`, `assigned`
        FROM `cedi_quota_giornaliera`
        WHERE `data` = :d AND `codice_cedi` = :c
    ');
    $st->execute([':d' => $dateYmd, ':c' => $cedi]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if ($row === false) {
        return null;
    }
    return [
        'target' => (int) ($row['target'] ?? 0),
        'assigned' => (int) ($row['assigned'] ?? 0),
    ];
}

function instantwin_cedi_get_daily_remaining(PDO $pdo, string $dateYmd, string $cedi, bool $skipSync = false): int
{
    if (!isDateInContestRange($dateYmd)) {
        return 0;
    }
    if (!instantwin_cedi_daily_quota_enabled($pdo)) {
        return 0;
    }

    if (!$skipSync) {
        instantwin_cedi_sync_daily_quota_targets($pdo, $dateYmd);
    }

    $row = instantwin_cedi_get_daily_quota_row($pdo, $dateYmd, $cedi);
    if ($row === null) {
        instantwin_cedi_sync_daily_quota_targets($pdo, $dateYmd);
        $row = instantwin_cedi_get_daily_quota_row($pdo, $dateYmd, $cedi);
    }
    if ($row === null) {
        return 0;
    }

    $target = (int) $row['target'];
    $assigned = (int) $row['assigned'];
    if ($target < $assigned) {
        $target = $assigned;
    }
    $remaining = max(0, $target - $assigned);

    // Filet de sécurité : dernier jour de distribution de la semaine (ou fin concours).
    if ($remaining <= 0 && instantwin_cedi_is_week_catchup_day($pdo, $dateYmd)) {
        $contest = function_exists('getContestDates') ? getContestDates() : null;
        $start = is_array($contest) ? ($contest['start_date'] ?? $dateYmd) : $dateYmd;
        $weekNo = instantwin_cedi_week_number_for_date($dateYmd, $start);
        $weekRem = instantwin_cedi_week_total_for_cedi($pdo, $weekNo, $cedi, true);
        if ($weekRem > 0) {
            $remaining = $weekRem;
        }
    }

    return max(0, $remaining);
}

function instantwin_cedi_get_global_daily_remaining(PDO $pdo, string $dateYmd): int
{
    if (!instantwin_cedi_daily_quota_enabled($pdo)) {
        return 0;
    }

    instantwin_cedi_sync_daily_quota_targets($pdo, $dateYmd);

    $sum = 0;
    foreach (array_keys(instantwin_cedi_definitions()) as $code) {
        $sum += instantwin_cedi_get_daily_remaining($pdo, $dateYmd, (string) $code, true);
    }

    $contest_dates = function_exists('getContestDates') ? getContestDates() : null;
    if ($contest_dates && $dateYmd === ($contest_dates['end_date'] ?? '')) {
        if (function_exists('getTotalPrizesAvailable')) {
            $prizes_remaining = getTotalPrizesAvailable();
            if ($prizes_remaining > 0) {
                $sum = max($sum, $prizes_remaining);
            }
        }
    }

    if (defined('MAX_DAILY_TARGET') && MAX_DAILY_TARGET > 0) {
        instantwin_cedi_ensure_daily_quota_table($pdo);
        $st = $pdo->prepare('
            SELECT COALESCE(SUM(`assigned`), 0)
            FROM `cedi_quota_giornaliera`
            WHERE `data` = :d
        ');
        $st->execute([':d' => $dateYmd]);
        $assignedSum = (int) $st->fetchColumn();
        $maxRemaining = MAX_DAILY_TARGET - $assignedSum;
        $sum = min($sum, max(0, $maxRemaining));
    }

    return max(0, $sum);
}

function instantwin_cedi_get_global_daily_target(PDO $pdo, string $dateYmd): int
{
    if (!instantwin_cedi_daily_quota_enabled($pdo)) {
        return 0;
    }
    instantwin_cedi_sync_daily_quota_targets($pdo, $dateYmd);
    instantwin_cedi_ensure_daily_quota_table($pdo);
    $st = $pdo->prepare('
        SELECT COALESCE(SUM(`target`), 0)
        FROM `cedi_quota_giornaliera`
        WHERE `data` = :d
    ');
    $st->execute([':d' => $dateYmd]);
    return (int) $st->fetchColumn();
}

function instantwin_cedi_try_consume_daily_quota(PDO $pdo, string $dateYmd, string $cedi): bool
{
    if ($cedi === '' || !instantwin_cedi_daily_quota_enabled($pdo)) {
        return false;
    }
    if (instantwin_cedi_get_daily_remaining($pdo, $dateYmd, $cedi, true) <= 0) {
        return false;
    }
    instantwin_cedi_ensure_daily_quota_table($pdo);

    // Filet de sécurité (dernier jour de distribution / fin concours) : pas de plafond target journalier.
    $isCatchupDay = instantwin_cedi_is_week_catchup_day($pdo, $dateYmd);
    if ($isCatchupDay) {
        $contest = function_exists('getContestDates') ? getContestDates() : null;
        $start = is_array($contest) ? ($contest['start_date'] ?? $dateYmd) : $dateYmd;
        $weekNo = instantwin_cedi_week_number_for_date($dateYmd, $start);
        if (instantwin_cedi_week_total_for_cedi($pdo, $weekNo, $cedi, true) <= 0) {
            return false;
        }
        $st = $pdo->prepare('
            UPDATE `cedi_quota_giornaliera`
            SET `assigned` = `assigned` + 1
            WHERE `data` = :d AND `codice_cedi` = :c
        ');
        $st->execute([':d' => $dateYmd, ':c' => $cedi]);

        return $st->rowCount() === 1;
    }

    $st = $pdo->prepare('
        UPDATE `cedi_quota_giornaliera`
        SET `assigned` = `assigned` + 1
        WHERE `data` = :d AND `codice_cedi` = :c AND `assigned` < `target`
    ');
    $st->execute([':d' => $dateYmd, ':c' => $cedi]);

    return $st->rowCount() === 1;
}

/**
 * Sync al massimo una volta per data per richiesta HTTP/CLI (evita O(n) rebuild in simulate_players).
 *
 * @return array<string, array<string, int>>
 */
function instantwin_cedi_sync_daily_quota_targets(PDO $pdo, string $asOfDate): array
{
    instantwin_cedi_ensure_week_budget_carryover($pdo, $asOfDate);

    if (!instantwin_cedi_daily_quota_enabled($pdo)) {
        return [];
    }

    if (!isset($GLOBALS['instantwin_cedi_synced_for_date']) || !is_array($GLOBALS['instantwin_cedi_synced_for_date'])) {
        $GLOBALS['instantwin_cedi_synced_for_date'] = [];
    }
    $today = date('Y-m-d');
    if ($asOfDate !== $today && isset($GLOBALS['instantwin_cedi_synced_for_date'][$asOfDate])) {
        return $GLOBALS['instantwin_cedi_synced_for_date'][$asOfDate];
    }

    $plan = instantwin_cedi_build_daily_quota_plan($pdo, $asOfDate, false);
    if ($plan === []) {
        return [];
    }
    instantwin_cedi_persist_daily_targets($pdo, $plan);
    $dayTotals = [];
    foreach ($plan as $d => $byCedi) {
        $dayTotals[$d] = is_array($byCedi) ? array_sum($byCedi) : 0;
    }
    if (!instantwin_cedi_simulation_context_active()) {
        error_log('instantwin_cedi_sync_daily_quota_targets(' . $asOfDate . '): giorni=' . count($plan)
            . ' somma=' . array_sum($dayTotals));
    }
    $GLOBALS['instantwin_cedi_synced_for_date'][$asOfDate] = $plan;

    return $plan;
}

/**
 * Ricalcolo completo quote giornaliere (dopo carryover o cambio giorni distribuzione).
 *
 * @return array<string, array<string, int>>
 */
function instantwin_cedi_resync_daily_quota_targets(PDO $pdo, string $asOfDate): array
{
    instantwin_cedi_clear_daily_quota_sync_cache();
    if (!instantwin_cedi_daily_quota_enabled($pdo)) {
        return [];
    }
    $plan = instantwin_cedi_build_daily_quota_plan($pdo, $asOfDate, false);
    if ($plan !== []) {
        instantwin_cedi_persist_daily_targets($pdo, $plan);
    }
    if (!instantwin_cedi_simulation_context_active()) {
        $dayTotals = [];
        foreach ($plan as $d => $byCedi) {
            $dayTotals[$d] = is_array($byCedi) ? array_sum($byCedi) : 0;
        }
        error_log('instantwin_cedi_resync_daily_quota_targets(' . $asOfDate . '): giorni=' . count($plan)
            . ' somma=' . array_sum($dayTotals));
    }

    return $plan;
}

if (!function_exists('instantwin_cedi_build_quota_plan_from_daily')) {
    /** @return array<string, int> */
    function instantwin_cedi_build_quota_plan_from_daily(PDO $pdo, string $asOfDate, bool $fullContestCalendar): array
    {
        $daily = instantwin_cedi_build_daily_quota_plan($pdo, $asOfDate, $fullContestCalendar);
        if ($daily === []) {
            return [];
        }
        $plan = [];
        foreach ($daily as $d => $byCedi) {
            $plan[$d] = is_array($byCedi) ? array_sum($byCedi) : 0;
        }
        $expected = array_sum($plan);
        $dates = array_keys($plan);
        if (function_exists('instantwin_quota_adjust_to_total') && $expected > 0) {
            return instantwin_quota_adjust_to_total($plan, $expected, array_values($dates));
        }

        return $plan;
    }
}

if (!function_exists('instantwin_cedi_group_dates_by_contest_week')) {
    /**
     * @param list<string> $dates
     * @return array<int, list<string>>
     */
    function instantwin_cedi_group_dates_by_contest_week(array $dates, string $start): array
    {
        $byWeek = [];
        foreach ($dates as $d) {
            $w = instantwin_cedi_week_number_for_date($d, $start);
            if (!isset($byWeek[$w])) {
                $byWeek[$w] = [];
            }
            $byWeek[$w][] = $d;
        }
        ksort($byWeek);

        return $byWeek;
    }
}

if (!function_exists('instantwin_cedi_accumulate_week_quota_into_plan')) {
    /**
     * @param array<string, int> $plan
     * @param list<string> $weekDates
     */
    function instantwin_cedi_accumulate_week_quota_into_plan(array &$plan, array $weekDates, int $weekRemaining): void
    {
        if (count($weekDates) === 1) {
            $plan[$weekDates[0]] = ($plan[$weekDates[0]] ?? 0) + $weekRemaining;

            return;
        }
        $weekPlan = instantwin_distribute_quota_on_dates($weekRemaining, $weekDates);
        foreach ($weekPlan as $d => $q) {
            $plan[$d] = ($plan[$d] ?? 0) + $q;
        }
    }
}

if (!function_exists('instantwin_cedi_build_quota_plan_by_weeks')) {
    /** @return array<string, int> */
    function instantwin_cedi_build_quota_plan_by_weeks(PDO $pdo, string $asOfDate, bool $fullContestCalendar): array
    {
        if (!function_exists('getRemainingDistributionDates') || !function_exists('instantwin_distribute_quota_on_dates')) {
            return [];
        }

        $dates = instantwin_cedi_distribution_dates_for_plan($pdo, $asOfDate, $fullContestCalendar);
        if ($dates === []) {
            return [];
        }

        $contest = function_exists('getContestDates') ? getContestDates() : null;
        $start = is_array($contest) ? ($contest['start_date'] ?? $asOfDate) : $asOfDate;
        $byWeek = instantwin_cedi_group_dates_by_contest_week($dates, $start);
        $subtractAssigned = !$fullContestCalendar;
        $plan = [];
        $expected = 0;
        foreach ($byWeek as $weekNo => $weekDates) {
            $weekRemaining = instantwin_cedi_week_prizes_remaining($pdo, (int) $weekNo, $subtractAssigned);
            if ($weekRemaining <= 0) {
                continue;
            }
            $expected += $weekRemaining;
            instantwin_cedi_accumulate_week_quota_into_plan($plan, $weekDates, $weekRemaining);
        }

        if ($plan === [] || $expected <= 0) {
            return [];
        }

        if (function_exists('instantwin_quota_adjust_to_total')) {
            return instantwin_quota_adjust_to_total($plan, $expected, array_values($dates));
        }

        return $plan;
    }
}

/**
 * Plan B : quota giornaliera globale (somma Ce.Di.) per compat slots / legacy.
 *
 * @return array<string, int>
 */
function instantwin_cedi_build_quota_plan(PDO $pdo, string $asOfDate, bool $fullContestCalendar): array
{
    if (!instantwin_cedi_weekly_budget_enabled($pdo)) {
        return [];
    }

    if (instantwin_cedi_daily_quota_enabled($pdo)) {
        return instantwin_cedi_build_quota_plan_from_daily($pdo, $asOfDate, $fullContestCalendar);
    }

    return instantwin_cedi_build_quota_plan_by_weeks($pdo, $asOfDate, $fullContestCalendar);
}

function instantwin_cedi_sanitize_codice(?string $raw): ?string
{
    if ($raw === null) {
        return null;
    }
    $digits = preg_replace('/\D/', '', trim($raw));
    if ($digits === '') {
        return null;
    }
    $code = ltrim($digits, '0');
    if ($code === '') {
        $code = '0';
    }
    if (strlen($code) > 16) {
        return null;
    }

    return $code;
}

function instantwin_cedi_normalize_codice(?string $raw, ?array $config = null): ?string
{
    $code = instantwin_cedi_sanitize_codice($raw);
    if ($code === null) {
        return null;
    }
    $defs = instantwin_cedi_definitions($config);
    if (!isset($defs[$code])) {
        return null;
    }

    return $code;
}

function instantwin_cedi_resolve_for_token(PDO $pdo, string $token): ?string
{
    $st = $pdo->prepare('
        SELECT p.`codice_partecipazione`, p.`id_pdv_concorso`, pv.`codice_cedi`
        FROM `partecipazioni` p
        LEFT JOIN `punti_vendita` pv ON TRIM(pv.`codice`) = TRIM(p.`id_pdv_concorso`)
        WHERE p.`token` = :t
        LIMIT 1
    ');
    $st->execute([':t' => $token]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        return null;
    }
    $cp = trim((string) ($row['codice_partecipazione'] ?? ''));
    if (strlen($cp) >= 1 && ctype_digit($cp[0])) {
        $fromCode = instantwin_cedi_normalize_codice($cp[0]);
        if ($fromCode !== null) {
            return $fromCode;
        }
    }
    return instantwin_cedi_normalize_codice((string) ($row['codice_cedi'] ?? ''));
}

/**
 * @return array<string, array<string, int>> [valore_key][cedi_code] => qty
 */
function instantwin_cedi_empty_matrix(?array $config = null): array
{
    $matrix = [];
    foreach (instantwin_cedi_prize_values($config) as $v) {
        $key = instantwin_cedi_value_key($v);
        $matrix[$key] = [];
        foreach (instantwin_cedi_definitions($config) as $code => $_) {
            $matrix[$key][$code] = 0;
        }
    }
    return $matrix;
}

function instantwin_cedi_value_key(float $valore): string
{
    return (string) (int) round($valore);
}

function instantwin_cedi_tipo_label(float $valore): string
{
    $n = (int) round($valore);
    return 'Gift card ' . $n . ' euro';
}

function instantwin_cedi_tipo_label_for_value(float $valore, ?array $config = null): string
{
    $key = instantwin_cedi_value_key($valore);
    foreach (instantwin_cedi_prize_definitions_configured($config) as $def) {
        if (instantwin_cedi_value_key((float) $def['value']) !== $key) {
            continue;
        }
        $tipo = trim((string) ($def['tipo'] ?? ''));
        if ($tipo !== '') {
            return mb_strlen($tipo) > 255 ? mb_substr($tipo, 0, 255) : $tipo;
        }
        break;
    }

    return instantwin_cedi_tipo_label($valore);
}

/**
 * @return array<string, array<string, int>>
 */
function instantwin_cedi_load_matrix_from_db(PDO $pdo, ?array $config = null): array
{
    $matrix = instantwin_cedi_empty_matrix($config);
    try {
        $st = $pdo->query('
            SELECT `valore`, `codice_cedi`, `quantita_totale`
            FROM `premi`
            WHERE `codice_cedi` IS NOT NULL AND TRIM(`codice_cedi`) <> \'\'
        ');
        if (!$st) {
            return $matrix;
        }
        while ($row = $st->fetch(PDO::FETCH_ASSOC)) {
            $code = instantwin_cedi_normalize_codice((string) ($row['codice_cedi'] ?? ''));
            if ($code === null) {
                continue;
            }
            $key = instantwin_cedi_value_key((float) ($row['valore'] ?? 0));
            if (!isset($matrix[$key])) {
                $matrix[$key] = [];
            }
            if (!array_key_exists($code, $matrix[$key])) {
                $matrix[$key][$code] = 0;
            }
            $matrix[$key][$code] = (int) ($row['quantita_totale'] ?? 0);
        }
    } catch (Throwable $e) {
        error_log('instantwin_cedi_load_matrix_from_db: ' . $e->getMessage());
    }
    return $matrix;
}

/**
 * @param array<string, array<string, int|string>> $matrixPost
 * @param list<string> $errors
 * @return array<string, array<string, int>>|null
 */
function instantwin_cedi_parse_matrix_post(array $matrixPost, array &$errors, ?array $config = null): ?array
{
    $matrix = instantwin_cedi_empty_matrix($config);
    $defs = instantwin_cedi_definitions($config);
    foreach (instantwin_cedi_prize_values($config) as $v) {
        $key = instantwin_cedi_value_key($v);
        $rowIn = $matrixPost[$key] ?? $matrixPost[(string) $v] ?? [];
        if (!is_array($rowIn)) {
            continue;
        }
        foreach ($defs as $code => $_) {
            $qty = isset($rowIn[$code]) ? (int) $rowIn[$code] : 0;
            if ($qty < 0) {
                $errors[] = 'La matrice premi contiene quantità negative.';
                return null;
            }
            if ($qty > 1000000) {
                $errors[] = 'La matrice premi supera il limite di 1.000.000 per cella.';
                return null;
            }
            $matrix[$key][$code] = $qty;
        }
    }
    return $matrix;
}

/**
 * Piano settimanale vuoto: [numero_settimana => matrice valore×cedi].
 *
 * @return array<int, array<string, array<string, int>>>
 */
function instantwin_cedi_empty_week_plan(int $weekCount): array
{
    $weekCount = max(1, $weekCount);
    $plan = [];
    for ($w = 1; $w <= $weekCount; $w++) {
        $plan[$w] = instantwin_cedi_empty_matrix();
    }
    return $plan;
}

/**
 * @param array<string, array<string, int>> $matrix
 * @return array<int, array<string, array<string, int>>>
 */
function instantwin_cedi_build_week_plan_from_matrix(array $matrix, int $weekCount): array
{
    $plan = instantwin_cedi_empty_week_plan($weekCount);
    $defs = instantwin_cedi_definitions();
    foreach (instantwin_cedi_prize_values() as $v) {
        $key = instantwin_cedi_value_key($v);
        foreach ($defs as $code => $_) {
            $qt = (int) ($matrix[$key][$code] ?? 0);
            if ($qt <= 0) {
                continue;
            }
            $parts = instantwin_cedi_split_total_across_weeks($qt, $weekCount);
            foreach ($parts as $i => $part) {
                $plan[$i + 1][$key][$code] = $part;
            }
        }
    }
    return $plan;
}

/**
 * Cibles par semaine proches de total/weekCount (reste sur les semaines les plus basses).
 *
 * @return list<int>
 */
function instantwin_cedi_balanced_week_targets(int $total, int $weekCount): array
{
    $weekCount = max(1, $weekCount);
    if ($total <= 0) {
        return array_fill(0, $weekCount, 0);
    }
    $base = intdiv($total, $weekCount);
    $rem = $total % $weekCount;
    $targets = array_fill(0, $weekCount, $base);
    for ($r = 0; $r < $rem; $r++) {
        $minIdx = 0;
        for ($i = 1; $i < $weekCount; $i++) {
            if ($targets[$i] < $targets[$minIdx]) {
                $minIdx = $i;
            }
        }
        $targets[$minIdx]++;
    }
    return $targets;
}

/**
 * Répartit $total selon des poids (plus grand reste).
 *
 * @param list<int|float> $weights
 * @return list<int>
 */
function instantwin_cedi_distribute_by_weights(int $total, array $weights): array
{
    $n = count($weights);
    if ($n === 0 || $total <= 0) {
        return array_fill(0, max(0, $n), 0);
    }
    $sumW = array_sum($weights);
    if ($sumW <= 0) {
        return array_fill(0, $n, 0);
    }
    $out = array_fill(0, $n, 0);
    $fracs = [];
    $allocated = 0;
    foreach ($weights as $i => $w) {
        $ideal = $total * (float) $w / $sumW;
        $f = (int) floor($ideal);
        $out[$i] = $f;
        $allocated += $f;
        $fracs[$i] = $ideal - $f;
    }
    $rem = $total - $allocated;
    if ($rem > 0) {
        $order = array_keys($fracs);
        usort($order, static function ($a, $b) use ($fracs): int {
            if ($fracs[$a] === $fracs[$b]) {
                return 0;
            }
            return ($fracs[$a] < $fracs[$b]) ? 1 : -1;
        });
        $c = count($order);
        for ($r = 0; $r < $rem; $r++) {
            $out[$order[$r % $c]]++;
        }
    }
    return $out;
}

function instantwin_cedi_week_plan_grand_sum(array $plan, int $week): int
{
    $sum = 0;
    if (!isset($plan[$week]) || !is_array($plan[$week])) {
        return 0;
    }
    foreach ($plan[$week] as $row) {
        if (!is_array($row)) {
            continue;
        }
        foreach ($row as $q) {
            $sum += (int) $q;
        }
    }
    return $sum;
}

/**
 * Déplace 1 unité entre deux semaines pour la même cellule (conserve le total par cellule).
 */
function instantwin_cedi_week_plan_nudge(
    array &$plan,
    int $weekTo,
    int $weekFrom,
    string $valueKey,
    string $cediCode
): void {
    if ($weekTo === $weekFrom) {
        return;
    }
    if (!isset($plan[$weekFrom][$valueKey][$cediCode])) {
        $plan[$weekFrom][$valueKey][$cediCode] = 0;
    }
    if (!isset($plan[$weekTo][$valueKey][$cediCode])) {
        $plan[$weekTo][$valueKey][$cediCode] = 0;
    }
    if ((int) $plan[$weekFrom][$valueKey][$cediCode] < 1) {
        return;
    }
    $plan[$weekFrom][$valueKey][$cediCode]--;
    $plan[$weekTo][$valueKey][$cediCode]++;
}

if (!function_exists('instantwin_cedi_balance_score_up_candidate')) {
    /**
     * @param array<int, array<string, array<string, int>>> $plan
     * @return array{from: int, vk: string, code: string, score: int}|null
     */
    function instantwin_cedi_balance_score_up_candidate(
        array $plan,
        string $vk,
        string $code,
        int $cellTotal,
        int $w,
        int $wf,
        int $bestScore
    ): ?array {
        if ($wf === $w) {
            return null;
        }
        $here = (int) ($plan[$w][$vk][$code] ?? 0);
        $from = (int) ($plan[$wf][$vk][$code] ?? 0);
        if ($from < 1) {
            return null;
        }
        $score = $here * 1000 - $from + $cellTotal;
        if ($score >= $bestScore) {
            return null;
        }

        return ['from' => $wf, 'vk' => $vk, 'code' => $code, 'score' => $score];
    }
}

if (!function_exists('instantwin_cedi_balance_score_down_candidate')) {
    /**
     * @param array<int, array<string, array<string, int>>> $plan
     * @param list<int> $weekTargets
     * @return array{from: int, vk: string, code: string, score: int}|null
     */
    function instantwin_cedi_balance_score_down_candidate(
        array $plan,
        array $weekTargets,
        string $vk,
        string $code,
        int $w,
        int $wf,
        int $bestScore
    ): ?array {
        if ($wf === $w) {
            return null;
        }
        $here = (int) ($plan[$w][$vk][$code] ?? 0);
        if ($here < 1) {
            return null;
        }
        $wfSum = instantwin_cedi_week_plan_grand_sum($plan, $wf);
        $wfTarget = (int) ($weekTargets[$wf - 1] ?? 0);
        if ($wfSum >= $wfTarget) {
            return null;
        }
        $score = $here * 1000 - $wfSum;
        if ($score <= $bestScore) {
            return null;
        }

        return ['from' => $wf, 'vk' => $vk, 'code' => $code, 'score' => $score];
    }
}

if (!function_exists('instantwin_cedi_balance_find_move_up')) {
    /**
     * @param array<int, array<string, array<string, int>>> $plan
     * @param array<string, array<string, int>> $matrix
     * @param array<string, mixed> $defs
     * @return array{from: int, vk: string, code: string}|null
     */
    function instantwin_cedi_balance_find_move_up(array $plan, array $matrix, array $defs, int $w, int $weekCount): ?array
    {
        $best = null;
        $bestScore = PHP_INT_MAX;
        foreach (instantwin_cedi_prize_values() as $v) {
            $vk = instantwin_cedi_value_key($v);
            foreach ($defs as $code => $_) {
                $cellTotal = (int) ($matrix[$vk][$code] ?? 0);
                if ($cellTotal <= 0) {
                    continue;
                }
                for ($wf = 1; $wf <= $weekCount; $wf++) {
                    $cand = instantwin_cedi_balance_score_up_candidate(
                        $plan,
                        $vk,
                        (string) $code,
                        $cellTotal,
                        $w,
                        $wf,
                        $bestScore
                    );
                    if ($cand === null) {
                        continue;
                    }
                    $bestScore = $cand['score'];
                    $best = ['from' => $cand['from'], 'vk' => $cand['vk'], 'code' => $cand['code']];
                }
            }
        }

        return $best;
    }
}

if (!function_exists('instantwin_cedi_balance_find_move_down')) {
    /**
     * @param array<int, array<string, array<string, int>>> $plan
     * @param array<string, array<string, int>> $matrix
     * @param array<string, mixed> $defs
     * @param list<int> $weekTargets
     * @return array{from: int, vk: string, code: string}|null
     */
    function instantwin_cedi_balance_find_move_down(
        array $plan,
        array $matrix,
        array $defs,
        array $weekTargets,
        int $w,
        int $weekCount
    ): ?array {
        $best = null;
        $bestScore = -1;
        foreach (instantwin_cedi_prize_values() as $v) {
            $vk = instantwin_cedi_value_key($v);
            foreach ($defs as $code => $_) {
                $cellTotal = (int) ($matrix[$vk][$code] ?? 0);
                if ($cellTotal <= 0) {
                    continue;
                }
                for ($wf = 1; $wf <= $weekCount; $wf++) {
                    $cand = instantwin_cedi_balance_score_down_candidate(
                        $plan,
                        $weekTargets,
                        $vk,
                        (string) $code,
                        $w,
                        $wf,
                        $bestScore
                    );
                    if ($cand === null) {
                        continue;
                    }
                    $bestScore = $cand['score'];
                    $best = ['from' => $cand['from'], 'vk' => $cand['vk'], 'code' => $cand['code']];
                }
            }
        }

        return $best;
    }
}

if (!function_exists('instantwin_cedi_balance_week_apply_one')) {
    /**
     * @param array<int, array<string, array<string, int>>> $plan
     * @param list<int> $weekTargets
     * @param array<string, array<string, int>> $matrix
     * @param array<string, mixed> $defs
     */
    function instantwin_cedi_balance_week_apply_one(
        array &$plan,
        array $weekTargets,
        int $weekCount,
        array $matrix,
        array $defs,
        int $w
    ): bool {
        $sum = instantwin_cedi_week_plan_grand_sum($plan, $w);
        $target = (int) ($weekTargets[$w - 1] ?? 0);
        if ($sum === $target) {
            return false;
        }
        $needUp = $sum < $target;
        $best = $needUp
            ? instantwin_cedi_balance_find_move_up($plan, $matrix, $defs, $w, $weekCount)
            : instantwin_cedi_balance_find_move_down($plan, $matrix, $defs, $weekTargets, $w, $weekCount);
        if ($best === null) {
            return false;
        }
        if ($needUp) {
            instantwin_cedi_week_plan_nudge($plan, $w, $best['from'], $best['vk'], $best['code']);
        } else {
            instantwin_cedi_week_plan_nudge($plan, $best['from'], $w, $best['vk'], $best['code']);
        }

        return true;
    }
}

function instantwin_cedi_balance_week_plan_totals(
    array &$plan,
    array $weekTargets,
    int $weekCount,
    array $matrix
): void {
    $defs = instantwin_cedi_definitions();
    $maxIter = 8000;
    for ($iter = 0; $iter < $maxIter; $iter++) {
        $moved = false;
        for ($w = 1; $w <= $weekCount; $w++) {
            if (instantwin_cedi_balance_week_apply_one($plan, $weekTargets, $weekCount, $matrix, $defs, $w)) {
                $moved = true;
            }
        }
        if (!$moved) {
            break;
        }
    }
}

/**
 * Ripartizione ~égale par semaine (~333), puis répartition proportionnelle par cellule.
 *
 * @param array<string, array<string, int>> $matrix
 * @return array<int, array<string, array<string, int>>>
 */
function instantwin_cedi_build_balanced_week_plan(array $matrix, int $weekCount): array
{
    $plan = instantwin_cedi_empty_week_plan($weekCount);
    $defs = instantwin_cedi_definitions();
    $grand = 0;
    foreach (instantwin_cedi_prize_values() as $v) {
        $key = instantwin_cedi_value_key($v);
        foreach ($defs as $code => $_) {
            $grand += (int) ($matrix[$key][$code] ?? 0);
        }
    }
    if ($grand <= 0) {
        return $plan;
    }
    $weekTargets = instantwin_cedi_balanced_week_targets($grand, $weekCount);
    foreach (instantwin_cedi_prize_values() as $v) {
        $key = instantwin_cedi_value_key($v);
        foreach ($defs as $code => $_) {
            $qt = (int) ($matrix[$key][$code] ?? 0);
            if ($qt <= 0) {
                continue;
            }
            $parts = instantwin_cedi_distribute_by_weights($qt, $weekTargets);
            foreach ($parts as $i => $part) {
                $plan[$i + 1][$key][$code] = $part;
            }
        }
    }
    instantwin_cedi_balance_week_plan_totals($plan, $weekTargets, $weekCount, $matrix);
    return $plan;
}

/**
 * Carica il piano da `premi_budget_settimana` (valori effettivi in DB).
 *
 * @return array<int, array<string, array<string, int>>>|null
 */
function instantwin_cedi_load_week_plan_from_db(PDO $pdo, int $weekCount): ?array
{
    instantwin_cedi_ensure_schema($pdo);
    try {
        $st = $pdo->query('
            SELECT p.`valore`, p.`codice_cedi`, b.`numero_settimana`, b.`budget_total`
            FROM `premi_budget_settimana` b
            INNER JOIN `premi` p ON p.`id` = b.`prize_id`
            WHERE p.`codice_cedi` IS NOT NULL AND TRIM(p.`codice_cedi`) <> ""
        ');
        $plan = instantwin_cedi_empty_week_plan($weekCount);
        $hasRows = false;
        while ($row = $st->fetch(PDO::FETCH_ASSOC)) {
            $code = instantwin_cedi_normalize_codice((string) ($row['codice_cedi'] ?? ''));
            if ($code === null) {
                continue;
            }
            $w = (int) ($row['numero_settimana'] ?? 0);
            if ($w < 1 || $w > $weekCount) {
                continue;
            }
            $key = instantwin_cedi_value_key((float) ($row['valore'] ?? 0));
            $qty = (int) ($row['budget_total'] ?? 0);
            $plan[$w][$key][$code] = ($plan[$w][$key][$code] ?? 0) + $qty;
            if ($qty > 0) {
                $hasRows = true;
            }
        }
        return $hasRows ? $plan : null;
    } catch (Throwable $e) {
        error_log('instantwin_cedi_load_week_plan_from_db: ' . $e->getMessage());
        return null;
    }
}

if (!function_exists('instantwin_cedi_parse_week_plan_value_row')) {
    /**
     * @param array<int, array<string, array<string, int>>> $plan
     * @param array<string, mixed> $valueRow
     * @param array<string, mixed> $defs
     * @param list<string> $errors
     */
    function instantwin_cedi_parse_week_plan_value_row(
        array &$plan,
        int $w,
        string $vk,
        array $valueRow,
        array $defs,
        array &$errors
    ): bool {
        foreach ($defs as $code => $_) {
            if (!array_key_exists($code, $valueRow)) {
                continue;
            }
            $qty = (int) $valueRow[$code];
            if ($qty < 0) {
                $errors[] = 'Quantità settimanale negativa (settimana ' . $w . ').';

                return false;
            }
            if ($qty > 1000000) {
                $errors[] = 'Quantità settimanale troppo elevata (settimana ' . $w . ').';

                return false;
            }
            $plan[$w][$vk][$code] = $qty;
        }

        return true;
    }
}

if (!function_exists('instantwin_cedi_parse_week_plan_week_block')) {
    /**
     * @param array<int, array<string, array<string, int>>> $plan
     * @param array<string, mixed> $defs
     * @param list<string> $errors
     */
    function instantwin_cedi_parse_week_plan_week_block(
        array &$plan,
        $weekKey,
        $weekRow,
        int $weekCount,
        array $defs,
        ?array $config,
        array &$errors
    ): bool {
        if (!is_array($weekRow)) {
            return true;
        }
        $w = (int) $weekKey;
        if ($w < 1 || $w > $weekCount) {
            $errors[] = 'Settimana non valida nella ripartizione personalizzata.';

            return false;
        }
        foreach (instantwin_cedi_prize_values($config) as $v) {
            $vk = instantwin_cedi_value_key($v);
            $valueRow = $weekRow[$vk] ?? $weekRow[(string) $v] ?? [];
            if (!is_array($valueRow)) {
                continue;
            }
            if (!instantwin_cedi_parse_week_plan_value_row($plan, $w, $vk, $valueRow, $defs, $errors)) {
                return false;
            }
        }

        return true;
    }
}

/**
 * @param array<string, mixed> $weekPost premi_matrix_weeks[numero_settimana][valore][cedi]
 * @param list<string> $errors
 * @return array<int, array<string, array<string, int>>>|null
 */
function instantwin_cedi_parse_week_plan_post(array $weekPost, int $weekCount, array &$errors, ?array $config = null): ?array
{
    $weekCount = max(1, $weekCount);
    $plan = instantwin_cedi_empty_week_plan($weekCount);
    $defs = instantwin_cedi_definitions($config);
    foreach ($weekPost as $weekKey => $weekRow) {
        if (!instantwin_cedi_parse_week_plan_week_block($plan, $weekKey, $weekRow, $weekCount, $defs, $config, $errors)) {
            return null;
        }
    }
    if ($weekPost === []) {
        $errors[] = 'Ripartizione settimanale personalizzata mancante: compilare il passo 4.';

        return null;
    }

    return $plan;
}

/**
 * Verifica che ogni cella totale = somma delle settimane.
 */
function instantwin_cedi_validate_week_plan_against_matrix(
    array $weekPlan,
    array $matrix,
    int $weekCount,
    array &$errors,
    ?array $config = null
): bool {
    $weekCount = max(1, $weekCount);
    $defs = instantwin_cedi_definitions($config);
    $ok = true;
    foreach (instantwin_cedi_prize_values($config) as $v) {
        $vk = instantwin_cedi_value_key($v);
        foreach ($defs as $code => $meta) {
            $expected = (int) ($matrix[$vk][$code] ?? 0);
            $sum = 0;
            for ($w = 1; $w <= $weekCount; $w++) {
                $sum += (int) ($weekPlan[$w][$vk][$code] ?? 0);
            }
            if ($sum !== $expected) {
                $errors[] = sprintf(
                    'Ce.Di. %s — %s€: somma settimane (%d) diversa dal totale concorso (%d).',
                    $meta['label'],
                    $vk,
                    $sum,
                    $expected
                );
                $ok = false;
            }
        }
    }
    return $ok;
}

/**
 * Parti settimanali per una cella (personalizzate o automatiche).
 *
 * @param array<int, array<string, array<string, int>>>|null $weekPlan
 * @return list<int>
 */
function instantwin_cedi_week_parts_for_cell(
    ?array $weekPlan,
    string $valueKey,
    string $cediCode,
    int $totalQty,
    int $weekCount
): array {
    if ($weekPlan !== null) {
        $parts = [];
        for ($w = 1; $w <= $weekCount; $w++) {
            $parts[] = (int) ($weekPlan[$w][$valueKey][$cediCode] ?? 0);
        }
        return $parts;
    }
    return instantwin_cedi_split_total_across_weeks($totalQty, $weekCount);
}

/**
 * @param array<string, array<string, int>> $matrix
 * @return list<array{valore: float, tipo_premio: string, quantita_totale: int, is_active: bool, codice_cedi: string, cedi_nome: string}>
 */
function instantwin_cedi_matrix_to_premi_config(array $matrix, ?array $config = null): array
{
    $out = [];
    $defs = instantwin_cedi_definitions($config);
    foreach (instantwin_cedi_prize_values($config) as $v) {
        $key = instantwin_cedi_value_key($v);
        foreach ($defs as $code => $meta) {
            $qty = (int) ($matrix[$key][$code] ?? 0);
            if ($qty <= 0) {
                continue;
            }
            $out[] = [
                'valore' => $v,
                'tipo_premio' => instantwin_cedi_tipo_label_for_value($v, $config),
                'quantita_totale' => $qty,
                'is_active' => true,
                'codice_cedi' => $code,
                'cedi_nome' => $meta['label'],
            ];
        }
    }
    return $out;
}

/**
 * True se si può azzerare `premi` (nessun premio assegnato / vincita legata).
 */
function instantwin_cedi_premi_safe_for_truncate(PDO $pdo): bool
{
    try {
        $assigned = (int) $pdo->query('SELECT COALESCE(SUM(`quantita_assegnati`), 0) FROM `premi`')->fetchColumn();
        if ($assigned > 0) {
            return false;
        }
        $wins = (int) $pdo->query('
            SELECT COUNT(*) FROM `partecipazioni`
            WHERE COALESCE(`annullato`, 0) = 0 AND COALESCE(`has_won`, 0) = 1 AND `prize_id` IS NOT NULL
        ')->fetchColumn();

        return $wins === 0;
    } catch (Throwable $e) {
        error_log('instantwin_cedi_premi_safe_for_truncate: ' . $e->getMessage());

        return false;
    }
}

/**
 * TRUNCATE premi + tabelle collegate (solo se nessuna assegnazione / vincita).
 * Reset AUTO_INCREMENT → prossimi INSERT partono da id = 1.
 */
function instantwin_cedi_truncate_premi_for_resync(PDO $pdo): void
{
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
    try {
        if ($pdo->query("SHOW TABLES LIKE 'codici_premio'")->fetchColumn()) {
            $pdo->exec('TRUNCATE TABLE `codici_premio`');
        }
        if ($pdo->query("SHOW TABLES LIKE 'premi_budget_settimana'")->fetchColumn()) {
            $pdo->exec('TRUNCATE TABLE `premi_budget_settimana`');
        }
        if ($pdo->query("SHOW TABLES LIKE 'cedi_quota_giornaliera'")->fetchColumn()) {
            $assignedQuota = (int) $pdo->query('SELECT COALESCE(SUM(`assigned`), 0) FROM `cedi_quota_giornaliera`')->fetchColumn();
            if ($assignedQuota === 0) {
                $pdo->exec('TRUNCATE TABLE `cedi_quota_giornaliera`');
            }
        }
        $pdo->exec('TRUNCATE TABLE `premi`');
    } finally {
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
    }
}

/**
 * Elimina una riga `premi` (e dipendenze) se non ha assegnazioni.
 */
function instantwin_cedi_delete_premio_if_unassigned(PDO $pdo, int $prizeId): bool
{
    if ($prizeId <= 0) {
        return false;
    }
    $st = $pdo->prepare('SELECT `quantita_assegnati` FROM `premi` WHERE `id` = ? LIMIT 1');
    $st->execute([$prizeId]);
    $assigned = (int) ($st->fetchColumn() ?: 0);
    if ($assigned > 0) {
        return false;
    }
    $pdo->prepare('DELETE FROM `codici_premio` WHERE `prize_id` = ?')->execute([$prizeId]);
    $pdo->prepare('DELETE FROM `premi_budget_settimana` WHERE `prize_id` = ?')->execute([$prizeId]);
    $pdo->prepare('DELETE FROM `premi` WHERE `id` = ?')->execute([$prizeId]);

    return true;
}

/**
 * Elimina righe Ce.Di. non assegnate (es. passaggio a ripartizione Standard).
 */
function instantwin_cedi_purge_cedi_premi_unassigned(PDO $pdo): void
{
    $st = $pdo->query("
        SELECT `id`, `quantita_assegnati`
        FROM `premi`
        WHERE `codice_cedi` IS NOT NULL AND TRIM(`codice_cedi`) <> ''
    ");
    if (!$st) {
        return;
    }
    while ($row = $st->fetch(PDO::FETCH_ASSOC)) {
        $pid = (int) ($row['id'] ?? 0);
        if ($pid <= 0) {
            continue;
        }
        $assigned = (int) ($row['quantita_assegnati'] ?? 0);
        if ($assigned > 0) {
            $pdo->prepare('UPDATE `premi` SET `is_active` = 0 WHERE `id` = ?')->execute([$pid]);
            continue;
        }
        instantwin_cedi_delete_premio_if_unassigned($pdo, $pid);
    }
}

/**
 * Elimina (o disattiva) righe legacy senza codice Ce.Di. prima della sync matrice.
 */
function instantwin_cedi_purge_non_cedi_premi(PDO $pdo): void
{
    $st = $pdo->query("
        SELECT `id`, `quantita_assegnati`
        FROM `premi`
        WHERE `codice_cedi` IS NULL OR TRIM(`codice_cedi`) = ''
    ");
    if (!$st) {
        return;
    }
    while ($row = $st->fetch(PDO::FETCH_ASSOC)) {
        $pid = (int) ($row['id'] ?? 0);
        if ($pid <= 0) {
            continue;
        }
        $assigned = (int) ($row['quantita_assegnati'] ?? 0);
        if ($assigned > 0) {
            $pdo->prepare('UPDATE `premi` SET `is_active` = 0 WHERE `id` = ?')->execute([$pid]);
            continue;
        }
        instantwin_cedi_delete_premio_if_unassigned($pdo, $pid);
    }
}

/**
 * @return array<string, array{valore: float, codice_cedi: string, cedi_nome: string, quantita_totale: int}>
 */
function instantwin_cedi_build_matrix_expected_keys(array $matrix, ?array $config = null): array
{
    $expectedKeys = [];
    $defs = instantwin_cedi_definitions($config);
    foreach (instantwin_cedi_prize_values($config) as $v) {
        $key = instantwin_cedi_value_key($v);
        foreach ($defs as $code => $meta) {
            $qty = (int) ($matrix[$key][$code] ?? 0);
            if ($qty <= 0) {
                continue;
            }
            $expectedKeys[$code . '|' . $key] = [
                'valore' => $v,
                'codice_cedi' => $code,
                'cedi_nome' => $meta['label'],
                'quantita_totale' => $qty,
            ];
        }
    }

    return $expectedKeys;
}

/**
 * True se quantità per (Ce.Di., valore) in DB coincidono con la matrice inviata.
 */
function instantwin_cedi_matrix_totals_match_db(PDO $pdo, array $expectedKeys): bool
{
    if ($expectedKeys === []) {
        return false;
    }
    try {
        $dbKeys = [];
        $st = $pdo->query("
            SELECT `valore`, `codice_cedi`, `quantita_totale`
            FROM `premi`
            WHERE `codice_cedi` IS NOT NULL AND TRIM(`codice_cedi`) <> ''
        ");
        if (!$st) {
            return false;
        }
        while ($row = $st->fetch(PDO::FETCH_ASSOC)) {
            $code = instantwin_cedi_normalize_codice((string) ($row['codice_cedi'] ?? ''));
            if ($code === null) {
                return false;
            }
            $k = $code . '|' . instantwin_cedi_value_key((float) ($row['valore'] ?? 0));
            $dbKeys[$k] = (int) ($row['quantita_totale'] ?? 0);
        }
        foreach ($expectedKeys as $k => $cell) {
            if (!array_key_exists($k, $dbKeys) || $dbKeys[$k] !== (int) $cell['quantita_totale']) {
                return false;
            }
            unset($dbKeys[$k]);
        }

        return $dbKeys === [];
    } catch (Throwable $e) {
        error_log('instantwin_cedi_matrix_totals_match_db: ' . $e->getMessage());

        return false;
    }
}

if (!function_exists('instantwin_cedi_db_weeks_match_parts')) {
    /**
     * @param list<int> $expectedParts
     * @param array<int, int> $dbWeeks
     */
    function instantwin_cedi_db_weeks_match_parts(array $expectedParts, array $dbWeeks, int $weekCount): bool
    {
        for ($i = 0; $i < $weekCount; $i++) {
            $weekNo = $i + 1;
            if ((int) ($expectedParts[$i] ?? 0) !== (int) ($dbWeeks[$weekNo] ?? 0)) {
                return false;
            }
        }
        foreach ($dbWeeks as $weekNo => $amt) {
            if ($weekNo > $weekCount && $amt > 0) {
                return false;
            }
        }

        return true;
    }
}

if (!function_exists('instantwin_cedi_week_budget_cell_matches')) {
    function instantwin_cedi_week_budget_cell_matches(
        PDOStatement $selPrize,
        PDOStatement $selWeeks,
        array $cell,
        ?array $customWeekPlan,
        int $weekCount
    ): bool {
        $selPrize->execute([':c' => $cell['codice_cedi'], ':v' => $cell['valore']]);
        $prizeId = (int) ($selPrize->fetchColumn() ?: 0);
        if ($prizeId <= 0) {
            return false;
        }
        $vk = instantwin_cedi_value_key($cell['valore']);
        $expectedParts = instantwin_cedi_week_parts_for_cell(
            $customWeekPlan,
            $vk,
            $cell['codice_cedi'],
            (int) $cell['quantita_totale'],
            $weekCount
        );
        $selWeeks->execute([':pid' => $prizeId]);
        $dbWeeks = [];
        while ($w = $selWeeks->fetch(PDO::FETCH_ASSOC)) {
            $dbWeeks[(int) $w['numero_settimana']] = (int) $w['budget_total'];
        }

        return instantwin_cedi_db_weeks_match_parts($expectedParts, $dbWeeks, $weekCount);
    }
}

/**
 * True se `premi_budget_settimana` riflette il piano settimanale atteso.
 */
function instantwin_cedi_week_budgets_match_plan(
    PDO $pdo,
    array $expectedKeys,
    ?array $customWeekPlan,
    int $weekCount
): bool {
    if ($weekCount < 1 || $expectedKeys === []) {
        return true;
    }
    try {
        $selPrize = $pdo->prepare('SELECT `id` FROM `premi` WHERE `codice_cedi` = :c AND `valore` = :v LIMIT 1');
        $selWeeks = $pdo->prepare('
            SELECT `numero_settimana`, `budget_total`
            FROM `premi_budget_settimana`
            WHERE `prize_id` = :pid
            ORDER BY `numero_settimana` ASC
        ');
        foreach ($expectedKeys as $cell) {
            if (!instantwin_cedi_week_budget_cell_matches($selPrize, $selWeeks, $cell, $customWeekPlan, $weekCount)) {
                return false;
            }
        }

        return true;
    } catch (Throwable $e) {
        error_log('instantwin_cedi_week_budgets_match_plan: ' . $e->getMessage());

        return false;
    }
}

/**
 * True se serve riscrivere `premi` / budget (matrice o ripartizione settimanale cambiata).
 */
function instantwin_cedi_premi_sync_needed(
    PDO $pdo,
    array $matrix,
    ?string $startDate,
    ?string $endDate,
    ?array $customWeekPlan,
    ?array $config
): bool {
    $expectedKeys = instantwin_cedi_build_matrix_expected_keys($matrix, $config);
    if ($expectedKeys === []) {
        return true;
    }
    if (!instantwin_cedi_matrix_totals_match_db($pdo, $expectedKeys)) {
        return true;
    }
    $weekCount = instantwin_cedi_contest_week_count($startDate, $endDate);

    return !instantwin_cedi_week_budgets_match_plan($pdo, $expectedKeys, $customWeekPlan, $weekCount);
}

if (!function_exists('instantwin_cedi_sync_init_flags')) {
    function instantwin_cedi_sync_init_flags(?bool &$truncatedPremi, ?bool &$syncPerformed): void
    {
        if ($truncatedPremi !== null) {
            $truncatedPremi = false;
        }
        if ($syncPerformed !== null) {
            $syncPerformed = false;
        }
    }
}

if (!function_exists('instantwin_cedi_sync_maybe_truncate')) {
    function instantwin_cedi_sync_maybe_truncate(PDO $pdo, bool $matrixChanged, ?bool &$truncatedPremi): bool
    {
        if (!$matrixChanged || !instantwin_cedi_premi_safe_for_truncate($pdo)) {
            return false;
        }
        instantwin_cedi_truncate_premi_for_resync($pdo);
        if ($truncatedPremi !== null) {
            $truncatedPremi = true;
        }

        return true;
    }
}

if (!function_exists('instantwin_cedi_sync_load_existing_premi')) {
    /** @return array<string, array<string, mixed>> */
    function instantwin_cedi_sync_load_existing_premi(PDO $pdo): array
    {
        $existing = [];
        $st = $pdo->query('SELECT `id`, `valore`, `codice_cedi`, `quantita_assegnati` FROM `premi`');
        while ($row = $st->fetch(PDO::FETCH_ASSOC)) {
            $code = instantwin_cedi_normalize_codice((string) ($row['codice_cedi'] ?? ''));
            if ($code === null) {
                continue;
            }
            $k = $code . '|' . instantwin_cedi_value_key((float) ($row['valore'] ?? 0));
            $existing[$k] = $row;
        }

        return $existing;
    }
}

if (!function_exists('instantwin_cedi_sync_resolve_prize_assigned')) {
    /**
     * @param array<string, array<string, mixed>> $existing
     * @param array<string, mixed> $cell
     * @return array{0: int, 1: int}
     */
    function instantwin_cedi_sync_resolve_prize_assigned(
        PDO $pdo,
        PDOStatement $sel,
        array $existing,
        string $k,
        array $cell
    ): array {
        if (isset($existing[$k])) {
            return [
                (int) $existing[$k]['id'],
                (int) ($existing[$k]['quantita_assegnati'] ?? 0),
            ];
        }
        $sel->execute([':c' => $cell['codice_cedi'], ':v' => $cell['valore']]);
        $prizeId = (int) ($sel->fetchColumn() ?: 0);
        $assigned = 0;
        if ($prizeId > 0) {
            $stA = $pdo->prepare('SELECT `quantita_assegnati` FROM `premi` WHERE `id` = ?');
            $stA->execute([$prizeId]);
            $assigned = (int) ($stA->fetchColumn() ?: 0);
        }

        return [$prizeId, $assigned];
    }
}

if (!function_exists('instantwin_cedi_sync_upsert_premio')) {
    /**
     * @param array<string, mixed> $cell
     */
    function instantwin_cedi_sync_upsert_premio(
        PDO $pdo,
        PDOStatement $upd,
        PDOStatement $ins,
        int $prizeId,
        array $cell,
        int $qt,
        int $assigned,
        ?array $config
    ): int {
        $active = $assigned < $qt ? 1 : 0;
        if ($prizeId > 0) {
            $upd->execute([
                ':tipo' => instantwin_cedi_tipo_label_for_value($cell['valore'], $config),
                ':nome' => $cell['cedi_nome'],
                ':qt' => $qt,
                ':active' => $active,
                ':id' => $prizeId,
            ]);

            return $prizeId;
        }
        $ins->execute([
            ':v' => $cell['valore'],
            ':tipo' => instantwin_cedi_tipo_label_for_value($cell['valore'], $config),
            ':c' => $cell['codice_cedi'],
            ':nome' => $cell['cedi_nome'],
            ':qt' => $qt,
        ]);

        return (int) $pdo->lastInsertId();
    }
}

if (!function_exists('instantwin_cedi_sync_write_week_budgets')) {
    /**
     * @param array<string, mixed> $cell
     * @param array<int, array<string, array<string, int>>>|null $customWeekPlan
     */
    function instantwin_cedi_sync_write_week_budgets(
        PDO $pdo,
        int $prizeId,
        array $cell,
        int $qt,
        int $weekCount,
        ?array $customWeekPlan
    ): void {
        $vk = instantwin_cedi_value_key($cell['valore']);
        $weekParts = instantwin_cedi_week_parts_for_cell(
            $customWeekPlan,
            $vk,
            $cell['codice_cedi'],
            $qt,
            $weekCount
        );
        $del = $pdo->prepare('DELETE FROM `premi_budget_settimana` WHERE `prize_id` = :pid');
        $del->execute([':pid' => $prizeId]);
        $insW = $pdo->prepare('
            INSERT INTO `premi_budget_settimana` (`prize_id`, `numero_settimana`, `budget_total`, `budget_assigned`)
            VALUES (:pid, :w, :t, 0)
        ');
        foreach ($weekParts as $i => $part) {
            if ($part <= 0) {
                continue;
            }
            $insW->execute([':pid' => $prizeId, ':w' => $i + 1, ':t' => $part]);
        }
    }
}

if (!function_exists('instantwin_cedi_sync_purge_stale_premi')) {
    /**
     * @param array<string, array<string, mixed>> $existing
     * @param list<int> $keepIds
     */
    function instantwin_cedi_sync_purge_stale_premi(PDO $pdo, array $existing, array $keepIds): void
    {
        foreach ($existing as $row) {
            $pid = (int) $row['id'];
            if (in_array($pid, $keepIds, true)) {
                continue;
            }
            $assigned = (int) ($row['quantita_assegnati'] ?? 0);
            if ($assigned > 0) {
                continue;
            }
            instantwin_cedi_delete_premio_if_unassigned($pdo, $pid);
        }
    }
}

if (!function_exists('instantwin_cedi_sync_qty_below_assigned_error')) {
    /**
     * @param array<string, mixed> $cell
     * @return list<string>
     */
    function instantwin_cedi_sync_qty_below_assigned_error(PDO $pdo, array $cell, int $qt, int $assigned): array
    {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        return [
            sprintf(
                'Ce.Di. %s — %s€: quantità (%d) inferiore ai premi già assegnati (%d).',
                $cell['cedi_nome'],
                instantwin_cedi_value_key($cell['valore']),
                $qt,
                $assigned
            ),
        ];
    }
}

if (!function_exists('instantwin_cedi_sync_process_expected_cells')) {
    /**
     * @param array<string, array<string, mixed>> $expectedKeys
     * @param array<string, array<string, mixed>> $existing
     * @param array<int, array<string, array<string, int>>>|null $customWeekPlan
     * @param list<int> $keepIds
     * @return list<string>|null
     */
    function instantwin_cedi_sync_process_expected_cells(
        PDO $pdo,
        array $expectedKeys,
        array $existing,
        int $weekCount,
        ?array $customWeekPlan,
        ?array $config,
        array &$keepIds
    ): ?array {
        $sel = $pdo->prepare('SELECT `id` FROM `premi` WHERE `codice_cedi` = :c AND `valore` = :v LIMIT 1');
        $upd = $pdo->prepare('
            UPDATE `premi`
            SET `tipo_premio` = :tipo, `cedi_nome` = :nome, `quantita_totale` = :qt, `is_active` = :active
            WHERE `id` = :id
        ');
        $ins = $pdo->prepare('
            INSERT INTO `premi` (`valore`, `tipo_premio`, `codice_cedi`, `cedi_nome`, `quantita_totale`, `quantita_assegnati`, `is_active`, `data_creazione`)
            VALUES (:v, :tipo, :c, :nome, :qt, 0, 1, NOW())
        ');

        foreach ($expectedKeys as $k => $cell) {
            [$prizeId, $assigned] = instantwin_cedi_sync_resolve_prize_assigned(
                $pdo,
                $sel,
                $existing,
                (string) $k,
                $cell
            );
            $qt = $cell['quantita_totale'];
            if ($qt < $assigned) {
                return instantwin_cedi_sync_qty_below_assigned_error($pdo, $cell, $qt, $assigned);
            }
            $prizeId = instantwin_cedi_sync_upsert_premio(
                $pdo,
                $upd,
                $ins,
                $prizeId,
                $cell,
                $qt,
                $assigned,
                $config
            );
            $keepIds[] = $prizeId;
            instantwin_cedi_sync_write_week_budgets($pdo, $prizeId, $cell, $qt, $weekCount, $customWeekPlan);
        }

        return null;
    }
}

/**
 * Sincronizza `premi` + `premi_budget_settimana` dalla matrice totale.
 *
 * @param array<string, array<string, int>> $matrix
 * @param array<int, array<string, array<string, int>>>|null $customWeekPlan ripartizione manuale (salvata in premi_budget_settimana)
 * @return list<string> errori
 */
function instantwin_cedi_sync_premi_matrix(
    PDO $pdo,
    array $matrix,
    ?string $startDate = null,
    ?string $endDate = null,
    ?array $customWeekPlan = null,
    ?array $config = null,
    ?bool &$truncatedPremi = null,
    ?bool &$syncPerformed = null
): array {
    $errors = [];
    instantwin_cedi_sync_init_flags($truncatedPremi, $syncPerformed);
    instantwin_cedi_ensure_schema($pdo);
    $weekCount = instantwin_cedi_contest_week_count($startDate, $endDate);

    $expectedKeys = instantwin_cedi_build_matrix_expected_keys($matrix, $config);

    if ($expectedKeys === []) {
        $errors[] = 'Inserire almeno una quantità nella matrice premi per Ce.Di.';

        return $errors;
    }

    if (!instantwin_cedi_premi_sync_needed($pdo, $matrix, $startDate, $endDate, $customWeekPlan, $config)) {
        return $errors;
    }
    if ($syncPerformed !== null) {
        $syncPerformed = true;
    }

    $matrixChanged = !instantwin_cedi_matrix_totals_match_db($pdo, $expectedKeys);

    // TRUNCATE fa commit implicito in MySQL: va eseguito fuori dalla transazione PDO.
    $didTruncatePremi = instantwin_cedi_sync_maybe_truncate($pdo, $matrixChanged, $truncatedPremi);

    $pdo->beginTransaction();
    try {
        if (!$didTruncatePremi) {
            instantwin_cedi_purge_non_cedi_premi($pdo);
        }

        $existing = $didTruncatePremi ? [] : instantwin_cedi_sync_load_existing_premi($pdo);
        $keepIds = [];
        $cellErrors = instantwin_cedi_sync_process_expected_cells(
            $pdo,
            $expectedKeys,
            $existing,
            $weekCount,
            $customWeekPlan,
            $config,
            $keepIds
        );
        if ($cellErrors !== null) {
            return $cellErrors;
        }

        // Rimuovi righe matrice non più presenti (solo se nessun assegnato)
        instantwin_cedi_sync_purge_stale_premi($pdo, $existing, $keepIds);

        if ($customWeekPlan === null) {
            instantwin_cedi_reconcile_budget_totals($pdo, $weekCount);
        }

        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('instantwin_cedi_sync_premi_matrix: ' . $e->getMessage());
        $errors[] = 'Errore salvataggio matrice premi: ' . $e->getMessage();
    }

    return $errors;
}

function instantwin_cedi_week_budget_remaining(PDO $pdo, int $prizeId, int $weekNo): int
{
    if ($prizeId <= 0 || $weekNo < 1) {
        return 0;
    }
    $st = $pdo->prepare('
        SELECT GREATEST(0, `budget_total` - `budget_assigned`) AS `rem`
        FROM `premi_budget_settimana`
        WHERE `prize_id` = :p AND `numero_settimana` = :w
        LIMIT 1
    ');
    $st->execute([':p' => $prizeId, ':w' => $weekNo]);
    return (int) ($st->fetchColumn() ?: 0);
}

function instantwin_cedi_can_assign_for_token(PDO $pdo, string $token, string $dateYmd): bool
{
    if (!instantwin_cedi_weekly_budget_enabled($pdo)) {
        return true;
    }
    instantwin_cedi_ensure_week_budget_carryover($pdo, $dateYmd);
    $cedi = instantwin_cedi_resolve_for_token($pdo, $token);
    if ($cedi === null) {
        return false;
    }
    $week = instantwin_cedi_week_number_for_date($dateYmd);
    $st = $pdo->prepare('
        SELECT COUNT(*) FROM `premi` p
        INNER JOIN `premi_budget_settimana` b ON b.`prize_id` = p.`id`
        WHERE p.`codice_cedi` = :c
          AND p.`quantita_assegnati` < p.`quantita_totale`
          AND b.`numero_settimana` = :w
          AND b.`budget_assigned` < b.`budget_total`
        LIMIT 1
    ');
    $st->execute([':c' => $cedi, ':w' => $week]);
    return (int) $st->fetchColumn() > 0;
}

function instantwin_cedi_increment_week_budget(PDO $pdo, int $prizeId, int $weekNo): bool
{
    if ($prizeId <= 0 || $weekNo < 1) {
        return false;
    }
    $st = $pdo->prepare('
        UPDATE `premi_budget_settimana`
        SET `budget_assigned` = `budget_assigned` + 1
        WHERE `prize_id` = :p AND `numero_settimana` = :w AND `budget_assigned` < `budget_total`
    ');
    $st->execute([':p' => $prizeId, ':w' => $weekNo]);
    return $st->rowCount() === 1;
}

/**
 * SQL EXISTS pour filtrer i premi con budget settimanale Ce.Di.
 */
function instantwin_cedi_prize_week_exists_sql(string $premiAlias = 'p'): string
{
    return "EXISTS (
        SELECT 1 FROM `premi_budget_settimana` pb
        WHERE pb.`prize_id` = {$premiAlias}.`id`
          AND pb.`numero_settimana` = :iw_week
          AND pb.`budget_assigned` < pb.`budget_total`
    )";
}

/**
 * @return array{sql: string, params: array<string, mixed>}|null
 */
function instantwin_cedi_assign_prize_filters(PDO $pdo, string $token, string $dateYmd): ?array
{
    if (!instantwin_cedi_weekly_budget_enabled($pdo)) {
        return null;
    }
    $cedi = instantwin_cedi_resolve_for_token($pdo, $token);
    if ($cedi === null) {
        return ['sql' => ' AND 1=0 ', 'params' => []];
    }
    $week = instantwin_cedi_week_number_for_date($dateYmd);
    $sql = ' AND p.`codice_cedi` = :iw_cedi AND ' . instantwin_cedi_prize_week_exists_sql('p');
    return [
        'sql' => $sql,
        'params' => [':iw_cedi' => $cedi, ':iw_week' => $week],
    ];
}
