<?php

if (!function_exists('instantwin_cedi_ensure_calendar_schema')) {
    function instantwin_cedi_ensure_calendar_schema(PDO $pdo): void
    {
        static $done = false;
        if ($done) {
            return;
        }

        try {
            $st = $pdo->query("SHOW TABLES LIKE 'cedi_calendario_time_slot'");
            if ($st && $st->fetch()) {
                $done = true;
                return;
            }
        } catch (Throwable $e) {
        }

        $sqlPath = dirname(__DIR__, 3) . '/sql/cedi_calendario_time_slot.sql';
        if (is_readable($sqlPath)) {
            $pdo->exec((string) file_get_contents($sqlPath));
            $done = true;
            return;
        }

        $pdo->exec("CREATE TABLE IF NOT EXISTS `cedi_calendario_time_slot` (
            `id` int NOT NULL AUTO_INCREMENT,
            `data` date NOT NULL,
            `codice_cedi` varchar(16) NOT NULL,
            `slot_timestamp` int NOT NULL,
            `finestra_inizio` int NOT NULL,
            `finestra_fine` int NOT NULL,
            `token_vincitore` varchar(64) DEFAULT NULL,
            `partecipanti_totali` int DEFAULT 0,
            `data_tiraggio` datetime DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uk_cedi_data_slot` (`data`,`codice_cedi`,`slot_timestamp`),
            KEY `idx_ccts_data_cedi` (`data`,`codice_cedi`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $done = true;
    }
}
