<?php

/**
 * Ce.Di. per statistiche/filtri manager (allineato a instantwin_cedi_resolve_for_token).
 */

if (!function_exists('manager_partecipazioni_has_column')) {
    function manager_partecipazioni_has_column(PDO $connection, string $column): bool
    {
        static $cache = [];
        $key = spl_object_id($connection) . ':' . strtolower($column);
        if (array_key_exists($key, $cache)) {
            return $cache[$key];
        }
        try {
            $stmt = $connection->prepare('SHOW COLUMNS FROM partecipazioni LIKE :col');
            $stmt->execute(['col' => $column]);
            if ($stmt->fetch(PDO::FETCH_ASSOC) !== false) {
                return $cache[$key] = true;
            }
            $stmt->execute(['col' => strtoupper($column)]);
            if ($stmt->fetch(PDO::FETCH_ASSOC) !== false) {
                return $cache[$key] = true;
            }
            $stmt->execute(['col' => strtolower($column)]);
            $cache[$key] = $stmt->fetch(PDO::FETCH_ASSOC) !== false;
        } catch (Throwable $e) {
            error_log('manager_partecipazioni_has_column: ' . $e->getMessage());
            $cache[$key] = false;
        }

        return $cache[$key];
    }
}

if (!function_exists('manager_partecipazioni_id_column')) {
    function manager_partecipazioni_id_column(PDO $connection): string
    {
        static $byConn = [];
        $cid = spl_object_id($connection);
        if (isset($byConn[$cid])) {
            return $byConn[$cid];
        }
        foreach (['ID', 'id'] as $candidate) {
            if (manager_partecipazioni_has_column($connection, $candidate)) {
                return $byConn[$cid] = $candidate;
            }
        }

        return $byConn[$cid] = 'ID';
    }
}

if (!function_exists('manager_partecipazioni_codice_column')) {
    function manager_partecipazioni_codice_column(PDO $connection): ?string
    {
        static $byConn = [];
        $cid = spl_object_id($connection);
        if (array_key_exists($cid, $byConn)) {
            return $byConn[$cid];
        }
        foreach (['codice_partecipazione', 'CODICE_PARTECIPAZIONE'] as $candidate) {
            if (manager_partecipazioni_has_column($connection, $candidate)) {
                return $byConn[$cid] = $candidate;
            }
        }

        return $byConn[$cid] = null;
    }
}

if (!function_exists('manager_cedi_labels')) {
    /** @return array<string, string> codice => etichetta */
    function manager_cedi_labels(): array
    {
        return [
            '1' => 'General',
            '2' => 'Meloni',
            '3' => 'Ingromarket',
            '4' => 'Essedrug',
        ];
    }
}

if (!function_exists('manager_cedi_sql_expression')) {
    function manager_cedi_sql_expression(
        string $pAlias = 'p',
        string $pdvAlias = 'mpdv',
        ?string $codicePartecipazioneColumn = null
    ): string {
        $pdvCedi = "TRIM(COALESCE({$pdvAlias}.codice_cedi, ''))";
        if ($codicePartecipazioneColumn === null || $codicePartecipazioneColumn === '') {
            return $pdvCedi;
        }
        $cp = $pAlias . '.`' . str_replace('`', '', $codicePartecipazioneColumn) . '`';

        return "(CASE
            WHEN {$cp} IS NOT NULL AND TRIM({$cp}) <> '' AND LEFT(TRIM({$cp}), 1) IN ('1','2','3','4')
            THEN LEFT(TRIM({$cp}), 1)
            ELSE {$pdvCedi}
        END)";
    }
}

if (!function_exists('manager_cedi_label')) {
    function manager_cedi_label(string $code): string
    {
        $code = trim($code);
        if ($code === '') {
            return 'Non associato';
        }
        $labels = manager_cedi_labels();

        return $labels[$code] ?? ('Ce.Di. ' . $code);
    }
}

if (!function_exists('manager_fetch_partecipazioni_count_by_cedi')) {
    /**
     * @param list<string> $whereSqlConditions
     * @param array<string, mixed> $bindings
     * @return list<array{codice_cedi: string, totale: int}>
     */
    function manager_fetch_partecipazioni_count_by_cedi(PDO $connection, array $whereSqlConditions = [], array $bindings = []): array
    {
        try {
            $codiceCol = manager_partecipazioni_codice_column($connection);
            $cediExpr = manager_cedi_sql_expression('p', 'mpdv', $codiceCol);
            $idCol = manager_partecipazioni_id_column($connection);
            $where = $whereSqlConditions !== [] ? ' WHERE ' . implode(' AND ', $whereSqlConditions) : '';
            $sql = "
                SELECT {$cediExpr} AS codice_cedi, COUNT(DISTINCT p.`{$idCol}`) AS totale
                FROM partecipazioni p
                LEFT JOIN punti_vendita mpdv ON mpdv.codice = p.ID_PDV_CONCORSO
                {$where}
                GROUP BY {$cediExpr}
                ORDER BY codice_cedi ASC
            ";
            $stmt = $connection->prepare($sql);
            foreach ($bindings as $name => $value) {
                $stmt->bindValue(':' . ltrim((string) $name, ':'), $value);
            }
            $stmt->execute();
            $rows = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $rows[] = [
                    'codice_cedi' => trim((string) ($row['codice_cedi'] ?? '')),
                    'totale' => (int) ($row['totale'] ?? 0),
                ];
            }

            return $rows;
        } catch (Throwable $e) {
            error_log('manager_fetch_partecipazioni_count_by_cedi: ' . $e->getMessage());
            return [];
        }
    }
}

if (!function_exists('manager_cedi_filter_sql')) {
    function manager_cedi_filter_sql(PDO $connection, string $pAlias = 'p', string $pdvAlias = 'mpdv'): string
    {
        return manager_cedi_sql_expression($pAlias, $pdvAlias, manager_partecipazioni_codice_column($connection));
    }
}
