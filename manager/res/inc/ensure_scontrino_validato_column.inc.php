<?php

/**
 * Assicura la colonna scontrino_validato su partecipazioni.
 * NULL = da validare, 1 = valido, 0 = non valido.
 */
function manager_ensure_scontrino_validato_column(PDO $connection): bool
{
    static $ensured = null;
    if ($ensured !== null) {
        return $ensured;
    }

    try {
        $col = $connection->query("SHOW COLUMNS FROM partecipazioni LIKE 'scontrino_validato'")->fetch(PDO::FETCH_ASSOC);
        if ($col === false) {
            $connection->exec(
                "ALTER TABLE partecipazioni
                 ADD COLUMN scontrino_validato TINYINT(1) NULL DEFAULT NULL
                 COMMENT 'Validazione scontrino: 1=valido, 0=non valido, NULL=da validare'"
            );
        }
        $ensured = true;
    } catch (Throwable $e) {
        $ensured = false;
    }

    return $ensured;
}
