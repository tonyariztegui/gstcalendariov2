-- Calendario Instant Win per Ce.Di. (isolato da calendario_time_slot globale/PDV)

CREATE TABLE IF NOT EXISTS `cedi_calendario_time_slot` (
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
  KEY `idx_ccts_data_cedi` (`data`,`codice_cedi`),
  KEY `idx_ccts_token` (`data`,`codice_cedi`,`token_vincitore`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
