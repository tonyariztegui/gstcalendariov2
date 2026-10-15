-- Budget premi Instant Win per settimana (cedi × valore × settimana)
CREATE TABLE IF NOT EXISTS `premi_budget_settimana` (
  `id` int NOT NULL AUTO_INCREMENT,
  `prize_id` int NOT NULL,
  `numero_settimana` int NOT NULL,
  `budget_total` int NOT NULL DEFAULT 0,
  `budget_assigned` int NOT NULL DEFAULT 0,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_premi_budget_settimana` (`prize_id`,`numero_settimana`),
  KEY `idx_pbs_settimana` (`numero_settimana`),
  CONSTRAINT `fk_pbs_prize` FOREIGN KEY (`prize_id`) REFERENCES `premi` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
