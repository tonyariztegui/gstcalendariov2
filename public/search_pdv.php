<?php
/**
 * Endpoint AJAX per la ricerca dei PDV con Select2.
 *
 * Tabella: punti_vendita
 * Campi usati:
 *   prov      -> provincia (per la select delle province)
 *   citta     -> città
 *   indirizzo + insegna -> etichetta indirizzo
 *   id        -> id del PDV
 *   active    -> filtro attivi
 */

header('Content-Type: application/json; charset=UTF-8');
include __DIR__ . '/../app/inc/config.php';
include __DIR__ . '/../app/inc/functions.php';

if (!defined('SEARCH_PDV_BIND_SEARCH')) {
    define('SEARCH_PDV_BIND_SEARCH', ':search');
}
if (!defined('SEARCH_PDV_BIND_PROV')) {
    define('SEARCH_PDV_BIND_PROV', ':prov');
}
if (!defined('SEARCH_PDV_BIND_REGIONE')) {
    define('SEARCH_PDV_BIND_REGIONE', ':regione');
}

$c_pdo = conn_pdo();

// Carica pdv_config da content.json per label personalizzati
$baseDir = dirname(__DIR__);
$contentPath = $baseDir . '/app/config/content.json';
$defaultsPath = $baseDir . '/app/config/content.defaults.json';
$content_defaults = function_exists('load_json_config') ? load_json_config($defaultsPath, []) : [];
$content_config = function_exists('load_json_config') ? load_json_config($contentPath, $content_defaults) : [];
$pdv_config = $content_config['pdv_config'] ?? ($content_defaults['pdv_config'] ?? []);
$pdv_mode = strtolower(trim((string)($pdv_config['mode'] ?? 'combinato')));
$pdv_mode = in_array($pdv_mode, ['combinato', 'cascata'], true) ? $pdv_mode : 'combinato';
$combinato_cfg = $pdv_config['combinato'] ?? [];
$label_fields = $combinato_cfg['label_fields'] ?? ['ragsoc', 'indirizzo', 'citta', 'prov'];
if (!is_array($label_fields)) {
    $label_fields = ['ragsoc', 'indirizzo', 'citta', 'prov'];
}
$label_separator = $combinato_cfg['separator'] ?? ' - ';
$pdvSearchLimitCfg = (int) ($combinato_cfg['search_limit'] ?? 50);
$pdvSearchLimitAllowed = [50, 100, 150, 200, 20000];
if (!in_array($pdvSearchLimitCfg, $pdvSearchLimitAllowed, true)) {
    $pdvSearchLimitCfg = 50;
}
$envPdvLimit = getenv('PDV_SEARCH_LIMIT');
if ($envPdvLimit !== false && trim((string) $envPdvLimit) !== '') {
    $pdvSqlLimit = max(1, min(50000, (int) trim((string) $envPdvLimit)));
} else {
    $pdvSqlLimit = $pdvSearchLimitCfg;
}
$cascata_cfg = $pdv_config['cascata'] ?? [];
$punto_vendita_display = strtolower(trim((string)($cascata_cfg['punto_vendita_display'] ?? 'insegna_indirizzo')));
$punto_vendita_display = in_array($punto_vendita_display, ['indirizzo_only', 'indirizzo_insegna', 'insegna_indirizzo'], true) ? $punto_vendita_display : 'insegna_indirizzo';

if (!function_exists('resolve_pdv_label_field_value')) {
    /** Resolve a single label field value from a PDV row (ragsoc/insegna fallbacks included). */
    function resolve_pdv_label_field_value(array $row, string $key): string
    {
        if ($key === 'ragsoc') {
            $val = trim((string) ($row['ragsoc'] ?? ''));
            return $val !== '' ? $val : trim((string) ($row['insegna'] ?? ''));
        }
        if ($key === 'insegna') {
            $val = trim((string) ($row['insegna'] ?? ''));
            return $val !== '' ? $val : trim((string) ($row['ragsoc'] ?? ''));
        }
        if (in_array($key, ['indirizzo', 'citta', 'prov', 'regione', 'cap'], true)) {
            return trim((string) ($row[$key] ?? ''));
        }
        return '';
    }
}

if (!function_exists('build_pdv_combined_label')) {
    function build_pdv_combined_label(array $row, array $label_fields, string $separator): string
    {
        $parts = [];
        foreach ($label_fields as $key) {
            $val = resolve_pdv_label_field_value($row, (string) $key);
            if ($val !== '') {
                $parts[] = $val;
            }
        }
        return implode($separator, $parts);
    }
}

if (!function_exists('sort_pdv_results_by_display_text')) {
    /** Ordine alfabetico sul testo mostrato in Select2 (locale italiana se disponibile). */
    function sort_pdv_results_by_display_text(array $results): array
    {
        if (count($results) < 2) {
            return $results;
        }
        $collator = class_exists('Collator') ? new Collator('it_IT') : null;
        usort($results, static function (array $a, array $b) use ($collator): int {
            $ta = (string) ($a['text'] ?? '');
            $tb = (string) ($b['text'] ?? '');
            if ($collator instanceof Collator) {
                return $collator->compare($ta, $tb);
            }
            return strcasecmp($ta, $tb);
        });
        return $results;
    }
}

if (!function_exists('search_pdv_geo_where')) {
    function search_pdv_geo_where(string $prov_filter): string
    {
        return $prov_filter !== ''
            ? 'prov = ' . SEARCH_PDV_BIND_PROV
            : 'regione = ' . SEARCH_PDV_BIND_REGIONE;
    }
}

if (!function_exists('search_pdv_bind_geo')) {
    function search_pdv_bind_geo(PDOStatement $stmt, string $prov_filter, string $regione_filter): void
    {
        if ($prov_filter !== '') {
            $stmt->bindValue(SEARCH_PDV_BIND_PROV, $prov_filter, PDO::PARAM_STR);
        } else {
            $stmt->bindValue(SEARCH_PDV_BIND_REGIONE, $regione_filter, PDO::PARAM_STR);
        }
    }
}

if (!function_exists('search_pdv_ensure_selected_string')) {
    /** Prepend selected string option if missing from distinct-value results. */
    function search_pdv_ensure_selected_string(array $results, string $selected_value): array
    {
        if (empty($selected_value)) {
            return $results;
        }
        foreach ($results as $result) {
            if ($result['id'] === $selected_value) {
                return $results;
            }
        }
        array_unshift($results, [
            'id' => $selected_value,
            'text' => $selected_value
        ]);
        return $results;
    }
}

if (!function_exists('search_pdv_combined_row_to_result')) {
    function search_pdv_combined_row_to_result(array $row, array $label_fields, string $label_separator): array
    {
        $label = build_pdv_combined_label($row, $label_fields, $label_separator);
        if ($label === '') {
            $label = trim($row['indirizzo']) ?: (($row['ragsoc'] ?: $row['insegna']) ?: 'PDV');
        }
        $store_name = $row['ragsoc'] ?: $row['insegna'];
        return [
            'id' => $row['id'],
            'text' => $label,
            'prov' => $row['prov'],
            'citta' => $row['citta'],
            'cap' => $row['cap'] ?? '',
            'indirizzo' => $row['indirizzo'],
            'insegna' => $store_name,
            'ragsoc' => $row['ragsoc']
        ];
    }
}

if (!function_exists('search_pdv_format_indirizzo_display')) {
    function search_pdv_format_indirizzo_display(string $ind, $store_name, string $punto_vendita_display): string
    {
        if ($punto_vendita_display === 'indirizzo_insegna' && !empty($store_name)) {
            return $ind . ' - ' . $store_name;
        }
        if ($punto_vendita_display === 'insegna_indirizzo' && $ind !== '' && $store_name) {
            return $store_name . ' - ' . $ind;
        }
        if ($punto_vendita_display === 'insegna_indirizzo' && $ind !== '') {
            return $ind;
        }
        return $ind ?: $store_name;
    }
}

if (!function_exists('search_pdv_handle_combined')) {
    function search_pdv_handle_combined(
        PDO $c_pdo,
        string $search_term,
        string $selected_value,
        array $label_fields,
        string $label_separator,
        int $pdvSqlLimit
    ): array {
        $has_term = $search_term !== '';
        $search_pattern = '%' . $search_term . '%';
        $stmt = $c_pdo->prepare(
            "
                SELECT id, prov, citta, cap, regione, indirizzo,
                       NULLIF(insegna, '') AS insegna,
                       NULLIF(ragsoc, '') AS ragsoc
                FROM punti_vendita
                WHERE active = 1
                  AND (
                      :has_term = 0
                      OR prov LIKE " . SEARCH_PDV_BIND_SEARCH . "
                      OR citta LIKE " . SEARCH_PDV_BIND_SEARCH . "
                      OR regione LIKE " . SEARCH_PDV_BIND_SEARCH . "
                      OR indirizzo LIKE " . SEARCH_PDV_BIND_SEARCH . "
                      OR insegna LIKE " . SEARCH_PDV_BIND_SEARCH . "
                      OR ragsoc LIKE " . SEARCH_PDV_BIND_SEARCH . "
                  )
                ORDER BY COALESCE(NULLIF(TRIM(ragsoc), ''), NULLIF(TRIM(insegna), ''), '') ASC,
                         TRIM(indirizzo) ASC, citta ASC, prov ASC
                LIMIT {$pdvSqlLimit}
            "
        );
        $stmt->bindValue(':has_term', $has_term ? 1 : 0, PDO::PARAM_INT);
        $stmt->bindValue(SEARCH_PDV_BIND_SEARCH, $search_pattern, PDO::PARAM_STR);
        $stmt->execute();

        $results = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $results[] = search_pdv_combined_row_to_result($row, $label_fields, $label_separator);
        }

        if (!empty($selected_value) && is_numeric($selected_value)) {
            $exists = false;
            foreach ($results as $result) {
                if ((string) $result['id'] === (string) $selected_value) {
                    $exists = true;
                    break;
                }
            }
            if (!$exists) {
                $stmt = $c_pdo->prepare("
                    SELECT id, prov, citta, cap, regione, indirizzo,
                           NULLIF(insegna, '') AS insegna,
                           NULLIF(ragsoc, '') AS ragsoc
                    FROM punti_vendita
                    WHERE id = :id AND active = 1
                    LIMIT 1
                ");
                $stmt->bindValue(':id', (int) $selected_value, PDO::PARAM_INT);
                $stmt->execute();
                $selected = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($selected) {
                    array_unshift(
                        $results,
                        search_pdv_combined_row_to_result($selected, $label_fields, $label_separator)
                    );
                }
            }
        }

        return sort_pdv_results_by_display_text($results);
    }
}

if (!function_exists('search_pdv_handle_distinct_column')) {
    /**
     * Distinct prov/regione options for Select2.
     *
     * @param 'prov'|'regione' $column
     */
    function search_pdv_handle_distinct_column(
        PDO $c_pdo,
        string $column,
        string $search_term,
        string $selected_value,
        int $pdvSqlLimit
    ): array {
        $extraWhere = '';
        if ($column === 'regione') {
            $extraWhere = "
                      AND regione IS NOT NULL
                      AND TRIM(regione) <> ''";
        }

        if (!empty($search_term)) {
            $search_pattern = '%' . $search_term . '%';
            $stmt = $c_pdo->prepare(
                "
                    SELECT DISTINCT {$column} AS value, {$column} AS text
                    FROM punti_vendita
                    WHERE active = 1
                      {$extraWhere}
                      AND {$column} LIKE " . SEARCH_PDV_BIND_SEARCH . "
                    ORDER BY {$column} ASC
                    LIMIT {$pdvSqlLimit}
                "
            );
            $stmt->bindValue(SEARCH_PDV_BIND_SEARCH, $search_pattern, PDO::PARAM_STR);
        } else {
            $stmt = $c_pdo->prepare("
                SELECT DISTINCT {$column} AS value, {$column} AS text
                FROM punti_vendita
                WHERE active = 1
                  {$extraWhere}
                ORDER BY {$column} ASC
                LIMIT {$pdvSqlLimit}
            ");
        }
        $stmt->execute();

        $results = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if ($row['value'] === null || $row['value'] === '') {
                continue;
            }
            $results[] = [
                'id' => $row['value'],
                'text' => $row['text']
            ];
        }

        return search_pdv_ensure_selected_string($results, $selected_value);
    }
}

if (!function_exists('search_pdv_handle_citta')) {
    function search_pdv_handle_citta(
        PDO $c_pdo,
        string $search_term,
        string $selected_value,
        string $prov_filter,
        string $regione_filter,
        int $pdvSqlLimit
    ): array {
        if (empty($prov_filter) && empty($regione_filter)) {
            echo json_encode(['results' => []]);
            exit;
        }

        $geoWhere = search_pdv_geo_where($prov_filter);
        if (!empty($search_term)) {
            $search_pattern = '%' . $search_term . '%';
            $stmt = $c_pdo->prepare(
                "
                    SELECT DISTINCT citta AS value, citta AS text
                    FROM punti_vendita
                    WHERE active = 1
                      AND {$geoWhere}
                      AND citta LIKE " . SEARCH_PDV_BIND_SEARCH . "
                    ORDER BY citta ASC
                    LIMIT {$pdvSqlLimit}
                "
            );
            search_pdv_bind_geo($stmt, $prov_filter, $regione_filter);
            $stmt->bindValue(SEARCH_PDV_BIND_SEARCH, $search_pattern, PDO::PARAM_STR);
        } else {
            $stmt = $c_pdo->prepare("
                SELECT DISTINCT citta AS value, citta AS text
                FROM punti_vendita
                WHERE active = 1
                  AND {$geoWhere}
                ORDER BY citta ASC
                LIMIT {$pdvSqlLimit}
            ");
            search_pdv_bind_geo($stmt, $prov_filter, $regione_filter);
        }
        $stmt->execute();

        $results = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if ($row['value'] === null || $row['value'] === '') {
                continue;
            }
            $results[] = [
                'id' => $row['value'],
                'text' => $row['text']
            ];
        }

        return search_pdv_ensure_selected_string($results, $selected_value);
    }
}

if (!function_exists('search_pdv_indirizzo_label_order_sql')) {
    function search_pdv_indirizzo_label_order_sql(string $punto_vendita_display): string {
        $pdvStoreOrder = "COALESCE(NULLIF(TRIM(ragsoc), ''), NULLIF(TRIM(insegna), ''), '') ASC";
        if ($punto_vendita_display === 'indirizzo_insegna') {
            return 'TRIM(indirizzo) ASC, ' . $pdvStoreOrder;
        }
        if ($punto_vendita_display === 'indirizzo_only') {
            return 'TRIM(indirizzo) ASC';
        }
        return "{$pdvStoreOrder}, TRIM(indirizzo) ASC";
    }
}

if (!function_exists('search_pdv_indirizzo_row_to_result')) {
    function search_pdv_indirizzo_row_to_result(array $row, string $punto_vendita_display): array {
        $store_name = $row['ragsoc'] ?: $row['insegna'];
        $ind = trim($row['indirizzo'] ?? '');
        return [
            'id' => $row['id'],
            'text' => search_pdv_format_indirizzo_display($ind, $store_name, $punto_vendita_display),
            'prov' => $row['prov'],
            'citta' => $row['citta'],
            'regione' => $row['regione'],
            'indirizzo' => $row['indirizzo'],
            'insegna' => $store_name,
        ];
    }
}

if (!function_exists('search_pdv_indirizzo_query_results')) {
    function search_pdv_indirizzo_query_results(
        PDO $c_pdo,
        string $search_term,
        string $prov_filter,
        string $regione_filter,
        string $citta_filter,
        string $punto_vendita_display,
        int $pdvSqlLimit
    ): array {
        $geoWhere = search_pdv_geo_where($prov_filter);
        $pdvLabelOrderSql = search_pdv_indirizzo_label_order_sql($punto_vendita_display);

        if (!empty($search_term)) {
            $search_pattern = '%' . $search_term . '%';
            $stmt = $c_pdo->prepare(
                "
                    SELECT id, prov, citta, regione, indirizzo, NULLIF(insegna, '') AS insegna, NULLIF(ragsoc, '') AS ragsoc
                    FROM punti_vendita
                    WHERE active = 1
                      AND {$geoWhere}
                      AND citta = :citta
                      AND (
                          indirizzo LIKE " . SEARCH_PDV_BIND_SEARCH . "
                          OR insegna LIKE :search2
                          OR ragsoc LIKE :search3
                      )
                    ORDER BY {$pdvLabelOrderSql}
                    LIMIT {$pdvSqlLimit}
                "
            );
            search_pdv_bind_geo($stmt, $prov_filter, $regione_filter);
            $stmt->bindValue(':citta', $citta_filter, PDO::PARAM_STR);
            $stmt->bindValue(SEARCH_PDV_BIND_SEARCH, $search_pattern, PDO::PARAM_STR);
            $stmt->bindValue(':search2', $search_pattern, PDO::PARAM_STR);
            $stmt->bindValue(':search3', $search_pattern, PDO::PARAM_STR);
        } else {
            $stmt = $c_pdo->prepare("
                SELECT id, prov, citta, regione, indirizzo, NULLIF(insegna, '') AS insegna, NULLIF(ragsoc, '') AS ragsoc
                FROM punti_vendita
                WHERE active = 1
                  AND {$geoWhere}
                  AND citta = :citta
                ORDER BY {$pdvLabelOrderSql}
                LIMIT {$pdvSqlLimit}
            ");
            search_pdv_bind_geo($stmt, $prov_filter, $regione_filter);
            $stmt->bindValue(':citta', $citta_filter, PDO::PARAM_STR);
        }
        $stmt->execute();

        $results = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $results[] = search_pdv_indirizzo_row_to_result($row, $punto_vendita_display);
        }
        return $results;
    }
}

if (!function_exists('search_pdv_indirizzo_prepend_selected')) {
    function search_pdv_indirizzo_prepend_selected(
        PDO $c_pdo,
        array $results,
        string $selected_value,
        string $punto_vendita_display
    ): array {
        if (empty($selected_value) || !is_numeric($selected_value)) {
            return $results;
        }

        $stmt = $c_pdo->prepare("
            SELECT id, prov, citta, regione, indirizzo, NULLIF(insegna, '') AS insegna, NULLIF(ragsoc, '') AS ragsoc
            FROM punti_vendita
            WHERE id = :id AND active = 1
            LIMIT 1
        ");
        $stmt->bindValue(':id', (int) $selected_value, PDO::PARAM_INT);
        $stmt->execute();
        $selected = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$selected) {
            return $results;
        }

        foreach ($results as $result) {
            if ($result['id'] == $selected['id']) {
                return $results;
            }
        }

        array_unshift($results, search_pdv_indirizzo_row_to_result($selected, $punto_vendita_display));
        return $results;
    }
}

if (!function_exists('search_pdv_handle_indirizzo')) {
    function search_pdv_handle_indirizzo(
        PDO $c_pdo,
        string $search_term,
        string $selected_value,
        string $prov_filter,
        string $regione_filter,
        string $citta_filter,
        string $punto_vendita_display,
        int $pdvSqlLimit
    ): array {
        if ((empty($prov_filter) && empty($regione_filter)) || empty($citta_filter)) {
            echo json_encode(['results' => []]);
            exit;
        }

        $results = search_pdv_indirizzo_query_results(
            $c_pdo,
            $search_term,
            $prov_filter,
            $regione_filter,
            $citta_filter,
            $punto_vendita_display,
            $pdvSqlLimit
        );
        $results = search_pdv_indirizzo_prepend_selected(
            $c_pdo,
            $results,
            $selected_value,
            $punto_vendita_display
        );

        return sort_pdv_results_by_display_text($results);
    }
}

$action = isset($_GET['action']) ? trim($_GET['action']) : 'indirizzo'; // prov, citta, indirizzo
$search_term = isset($_GET['q']) ? trim($_GET['q']) : '';
$selected_value = isset($_GET['selected']) ? trim($_GET['selected']) : '';
$regione_filter = isset($_GET['regione']) ? trim($_GET['regione']) : '';
$prov_filter = isset($_GET['prov']) ? trim($_GET['prov']) : '';   // usiamo prov come "provincia"
$citta_filter = isset($_GET['citta']) ? trim($_GET['citta']) : '';

$results = [];

try {
    switch ($action) {
        case 'pdv_combined':
            $results = search_pdv_handle_combined(
                $c_pdo,
                $search_term,
                $selected_value,
                $label_fields,
                $label_separator,
                $pdvSqlLimit
            );
            break;

        case 'prov':
            $results = search_pdv_handle_distinct_column(
                $c_pdo,
                'prov',
                $search_term,
                $selected_value,
                $pdvSqlLimit
            );
            break;

        case 'regione':
            $results = search_pdv_handle_distinct_column(
                $c_pdo,
                'regione',
                $search_term,
                $selected_value,
                $pdvSqlLimit
            );
            break;

        case 'citta':
            $results = search_pdv_handle_citta(
                $c_pdo,
                $search_term,
                $selected_value,
                $prov_filter,
                $regione_filter,
                $pdvSqlLimit
            );
            break;

        case 'indirizzo':
        default:
            $results = search_pdv_handle_indirizzo(
                $c_pdo,
                $search_term,
                $selected_value,
                $prov_filter,
                $regione_filter,
                $citta_filter,
                $punto_vendita_display,
                $pdvSqlLimit
            );
            break;
    }

    echo json_encode([
        'results' => $results
    ]);

} catch (Exception $e) {
    echo json_encode([
        'results' => [],
        'error' => $e->getMessage()
    ]);
}
?>
