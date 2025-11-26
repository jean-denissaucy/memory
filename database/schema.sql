-- Schéma pour l'application "memory_game" (version robuste)

CREATE DATABASE IF NOT EXISTS `memory_game` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `memory_game`;

-- Table users
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `email` VARCHAR(150) DEFAULT NULL,
  `password_hash` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table games
CREATE TABLE IF NOT EXISTS `games` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED DEFAULT NULL,
  `difficulty` ENUM('easy','medium','hard','hardcore') DEFAULT 'medium',
  `moves` INT UNSIGNED DEFAULT 0,
  `time_seconds` INT UNSIGNED DEFAULT 0,
  `started_at` DATETIME DEFAULT NULL,
  `ended_at` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_games_user` (`user_id`),
  CONSTRAINT `fk_games_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table cards
CREATE TABLE IF NOT EXISTS `cards` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `game_id` INT UNSIGNED NOT NULL,
  `position` INT UNSIGNED NOT NULL,
  `pair_token` VARCHAR(100) NOT NULL,
  `image` VARCHAR(255) DEFAULT NULL,
  `is_matched` TINYINT(1) DEFAULT 0,
  `is_revealed` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_cards_game` (`game_id`),
  CONSTRAINT `fk_cards_game` FOREIGN KEY (`game_id`) REFERENCES `games`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table scores (avec player_name)
CREATE TABLE IF NOT EXISTS `scores` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED DEFAULT NULL,
  `game_id` INT UNSIGNED DEFAULT NULL,
  `player_name` VARCHAR(100) DEFAULT NULL,
  `score` INT NOT NULL DEFAULT 0,
  `moves` INT UNSIGNED DEFAULT 0,
  `time_seconds` INT UNSIGNED DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_scores_user` (`user_id`),
  KEY `idx_scores_game` (`game_id`),
  KEY `idx_scores_score` (`score`),
  CONSTRAINT `fk_scores_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_scores_game` FOREIGN KEY (`game_id`) REFERENCES `games`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Ajout sécurisé de la colonne player_name si absente (compatible MySQL <8 / >=8)
SET @col_exists = (
  SELECT COUNT(1)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'scores'
    AND COLUMN_NAME = 'player_name'
);
SET @sql = IF(@col_exists = 0,
  'ALTER TABLE `scores` ADD COLUMN `player_name` VARCHAR(100) DEFAULT NULL',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

/* --- ajout sécurisé d'un index sur scores.player_name (pour accélérer les recherches par nom) --- */
SET @idx_exists := (
  SELECT COUNT(1)
  FROM INFORMATION_SCHEMA.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'scores'
    AND INDEX_NAME = 'idx_scores_player_name'
);

SET @sql_idx := IF(@idx_exists = 0,
  'CREATE INDEX `idx_scores_player_name` ON `scores` (`player_name`(100))',
  'SELECT 1'
);

PREPARE stmt_idx FROM @sql_idx;
EXECUTE stmt_idx;
DEALLOCATE PREPARE stmt_idx;

-- Création sécurisée d'un index pour accélérer les recherches par paire (procédure temporaire)
DROP PROCEDURE IF EXISTS create_idx_cards_pair_token;
DELIMITER $$
CREATE PROCEDURE create_idx_cards_pair_token()
BEGIN
  IF (SELECT COUNT(1)
        FROM INFORMATION_SCHEMA.STATISTICS
       WHERE TABLE_SCHEMA = DATABASE()
         AND TABLE_NAME = 'cards'
         AND INDEX_NAME = 'idx_cards_pair_token') = 0
  THEN
    ALTER TABLE `cards` ADD INDEX `idx_cards_pair_token` (`game_id`, `pair_token`, `is_matched`);
  END IF;
END$$
DELIMITER ;

CALL create_idx_cards_pair_token();
DROP PROCEDURE IF EXISTS create_idx_cards_pair_token;

-- Vue pratique : top 50 meilleurs scores (par score desc, puis temps asc, puis moves asc)
DROP VIEW IF EXISTS top_scores;
CREATE VIEW top_scores AS
SELECT s.id, COALESCE(s.player_name, u.username) AS player, s.score, s.moves, s.time_seconds, s.created_at
FROM scores s
LEFT JOIN users u ON u.id = s.user_id
ORDER BY s.score DESC, s.time_seconds ASC, s.moves ASC
LIMIT 50;

-- Données d'exemple non destructives
INSERT IGNORE INTO `users` (`id`,`username`,`email`,`password_hash`) VALUES
(1,'demo','demo@example.com',NULL);

INSERT IGNORE INTO `games` (`id`,`user_id`,`difficulty`,`moves`,`time_seconds`,`started_at`) VALUES
(1,1,'normal',0,0,NOW());

-- Exemple de cartes (6 paires)
INSERT IGNORE INTO `cards` (`id`,`game_id`,`position`,`pair_token`,`image`) VALUES
(1,1,1,'p1','/assets/img/card1.png'),
(2,1,2,'p2','/assets/img/card2.png'),
(3,1,3,'p3','/assets/img/card3.png'),
(4,1,4,'p4','/assets/img/card4.png'),
(5,1,5,'p5','/assets/img/card5.png'),
(6,1,6,'p6','/assets/img/card6.png'),
(7,1,7,'p1','/assets/img/card1.png'),
(8,1,8,'p2','/assets/img/card2.png'),
(9,1,9,'p3','/assets/img/card3.png'),
(10,1,10,'p4','/assets/img/card4.png'),
(11,1,11,'p5','/assets/img/card5.png'),
(12,1,12,'p6','/assets/img/card6.png');

-- Exemple de score avec player_name
INSERT IGNORE INTO `scores` (`id`,`user_id`,`game_id`,`player_name`,`score`,`moves`,`time_seconds`) VALUES
(1,1,1,'Demo Player',1000,12,45);

-- Fin du fichier
