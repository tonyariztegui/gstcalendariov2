-- Quota giornaliera Instant Win per Ce.Di. (target/assigned par magazzino central)
CREATE TABLE IF NOT EXISTS `cedi_quota_giornaliera` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
