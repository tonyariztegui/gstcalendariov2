<?php
/**
 * Statistiche premi raggruppate per Ce.Di. (tabella premi con codice_cedi / cedi_nome).
 */

if (!function_exists('manager_premi_detect_cedi_columns')) {
    /**
     * @return array{has_codice: bool, has_nome: bool, codice_sql: string, nome_sql: string, nome_order_sql: string}
     */
    function manager_premi_detect_cedi_columns(PDO $pdo): array
    {
        $hasCodice = false;
        $hasNome = false;
        try {
            $hasCodice = $pdo->query("SHOW COLUMNS FROM `premi` LIKE 'codice_cedi'")->fetch(PDO::FETCH_ASSOC) !== false;
            $hasNome = $pdo->query("SHOW COLUMNS FROM `premi` LIKE 'cedi_nome'")->fetch(PDO::FETCH_ASSOC) !== false;
        } catch (Throwable $e) {
            $hasCodice = false;
            $hasNome = false;
        }

        return [
            'has_codice' => $hasCodice,
            'has_nome' => $hasNome,
            'codice_sql' => $hasCodice ? 'pr.codice_cedi' : 'NULL',
            'nome_sql' => $hasNome ? 'pr.cedi_nome' : 'NULL',
            'nome_order_sql' => $hasNome ? 'pr.cedi_nome' : "''",
        ];
    }
}

if (!function_exists('manager_premi_fetch_stats_per_tipo')) {
    /**
     * @return array{rows: list<array<string, mixed>>, by_cedi: array<string, array{codice: string, nome: string, rows: list<array<string, mixed>>}>}
     */
    function manager_premi_fetch_stats_per_tipo(PDO $pdo, array $cediCols): array
    {
        $codiceSel = $cediCols['codice_sql'];
        $nomeSel = $cediCols['nome_sql'];
        $nomeOrder = $cediCols['nome_order_sql'];

        $rows = $pdo->query("
            SELECT
                pr.TIPO_PREMIO,
                pr.VALORE,
                {$codiceSel} AS CODICE_CEDI_PREMIO,
                {$nomeSel} AS CEDI_NOME_PREMIO,
                pr.QUANTITA_ASSEGNATI AS assegnati,
                pr.QUANTITA_TOTALE,
                (pr.QUANTITA_TOTALE - pr.QUANTITA_ASSEGNATI) AS rimanenti,
                COUNT(DISTINCT CASE WHEN p.HAS_WON = 1 AND COALESCE(p.ANNULLATO, 0) = 0 THEN p.ID END) AS assegnati_verifica
            FROM premi pr
            LEFT JOIN partecipazioni p ON pr.ID = p.PRIZE_ID AND p.HAS_WON = 1 AND COALESCE(p.ANNULLATO, 0) = 0
            GROUP BY pr.ID, pr.QUANTITA_ASSEGNATI, pr.QUANTITA_TOTALE, pr.TIPO_PREMIO, pr.VALORE
            ORDER BY {$codiceSel} ASC, {$nomeOrder} ASC, pr.QUANTITA_ASSEGNATI DESC
        ")->fetchAll(PDO::FETCH_ASSOC);

        $byCedi = [];
        foreach ($rows as $statRow) {
            $statCediCodice = trim((string) ($statRow['CODICE_CEDI_PREMIO'] ?? ''));
            $statCediNome = trim((string) ($statRow['CEDI_NOME_PREMIO'] ?? ''));
            $statCediKey = $statCediCodice !== ''
                ? 'code:' . $statCediCodice
                : ($statCediNome !== '' ? 'name:' . $statCediNome : 'senza-cedi');
            if (!isset($byCedi[$statCediKey])) {
                $byCedi[$statCediKey] = [
                    'codice' => $statCediCodice,
                    'nome' => $statCediNome,
                    'rows' => [],
                ];
            }
            $byCedi[$statCediKey]['rows'][] = $statRow;
        }

        return ['rows' => $rows, 'by_cedi' => $byCedi];
    }
}
