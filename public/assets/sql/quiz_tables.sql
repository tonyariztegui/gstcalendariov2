-- Quiz: domande (testo/audio/video) e tentativi
-- Eseguire questo script per abilitare il modulo quiz nel concorso.

-- Domande quiz: tipo = text | audio | video (formato); tema = percorso/tema (es. sport, citta); risposta_corretta = A|B|C|D
CREATE TABLE IF NOT EXISTS `quiz_domande` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `testo` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Testo della domanda (sempre presente)',
  `tipo` enum('text','audio','video') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'text' COMMENT 'Tipo richiesta: text, audio, video',
  `tema` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Codice tema stabile (es. sport, citta). Stesso tipo puo avere piu temi; NULL = non assegnato / tutte le domande senza filtro tema',
  `audio_file` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Percorso file audio (se tipo=audio)',
  `video_file` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Percorso/URL video (se tipo=video)',
  `risposta_a` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `risposta_b` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `risposta_c` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `risposta_d` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `risposta_corretta` enum('A','B','C','D') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'A',
  `ordine` int(11) NOT NULL DEFAULT 0 COMMENT 'Ordine di visualizzazione',
  `attiva` tinyint(1) NOT NULL DEFAULT 1,
  `creata_il` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `aggiornata_il` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_attiva_ordine` (`attiva`,`ordine`),
  KEY `idx_attiva_tipo_tema` (`attiva`,`tipo`,`tema`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Migrazione DB gia esistente (tabella quiz_domande senza colonna tema):
-- Eseguire UNA VOLTA a mano; ignorare errori "Duplicate column" se gia applicata.
--
-- ALTER TABLE `quiz_domande`
--   ADD COLUMN `tema` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Codice tema stabile (es. sport, citta). Stesso tipo puo avere piu temi; NULL = non assegnato / legacy' AFTER `tipo`;
-- ALTER TABLE `quiz_domande` ADD KEY `idx_attiva_tipo_tema` (`attiva`,`tipo`,`tema`);
-- ---------------------------------------------------------------------------

-- Un tentativo per id_cliente (un quiz completato per partecipazione)
CREATE TABLE IF NOT EXISTS `quiz_tentativi` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_cliente` int(11) NOT NULL COMMENT 'Utente (da partecipazioni -> utenti)',
  `token_partecipazione` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Token della partecipazione collegata',
  `quiz_mode` enum('standard','audio') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'standard',
  `corrette` int(11) NOT NULL DEFAULT 0,
  `totali` int(11) NOT NULL DEFAULT 0,
  `esito` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1=superato (es. tutte corrette o soglia), 0=non superato',
  `risposte_json` text COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'JSON id_domanda => A|B|C|D',
  `creata_il` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_cliente_token` (`id_cliente`,`token_partecipazione`),
  KEY `idx_token` (`token_partecipazione`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
