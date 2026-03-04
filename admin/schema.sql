-- Database Schema
-- Version: 1.0.0

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- --------------------------------------------------------

-- Table: admin_logs
DROP TABLE IF EXISTS `admin_logs`;
CREATE TABLE `admin_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `admin_id` int(11) DEFAULT NULL,
  `action_type` varchar(32) NOT NULL,
  `details` text,
  `ip_address` varchar(45) DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `admin_id` (`admin_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

-- Table: admin_users
DROP TABLE IF EXISTS `admin_users`;
CREATE TABLE `admin_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(64) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('super','limited','pay') NOT NULL DEFAULT 'limited',
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

-- Table: badges
DROP TABLE IF EXISTS `badges`;
CREATE TABLE `badges` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `emoji` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `color` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'blue',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `badges` (`id`, `name`, `emoji`, `description`, `color`) VALUES
(1, 'Early Bird', '🌅', 'Submitted more than 48 hours before deadline!', 'emerald'),
(2, 'Dark Horse', '🐴', 'Picked a Seed > 4 to win it all.', 'purple'),
(3, 'Chalk', '🧱', 'Picked all #1 Seeds to make the Semifinals.', 'stone'),
(4, 'Local Hero', '🏡', 'Picked a team from your region.', 'orange'),
(5, 'Upset City', '🌪️', 'Picked a 13+ seed to win a game.', 'red'),
(6, 'Perfect First Round', '💯', 'Correctly picked all 32 games in Round 1.', 'gold'),
(7, 'Underdog Lover', '🐕', 'Picked 5+ upsets in the First Round.', 'teal'),
(8, 'Heartbreak', '💔', 'Your Champion was eliminated in the First Round.', 'red'),
(9, 'Crystal Ball', '🔮', 'Correctly predicted the entire Semifinals.', 'indigo'),
(10, 'Close But No Cigar', '🚬', 'Your Champion lost in the Final Game.', 'gray'),
(11, 'Back-to-Back', '🏆', 'Won the tournament two years in a row!', 'gold'),
(12, 'High Roller', '🤑', 'Won over $100 in lifetime earnings.', 'green'),
(13, 'Money Bags', '💰', 'Finished in the money (Top 3) in 3+ seasons.', 'emerald');

-- --------------------------------------------------------

-- Table: best_scores
DROP TABLE IF EXISTS `best_scores`;
CREATE TABLE `best_scores` (
  `id` int(11) NOT NULL DEFAULT '0',
  `name` varchar(128) NOT NULL DEFAULT '',
  `score` double NOT NULL DEFAULT '0',
  `scoring_type` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`scoring_type`,`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- Table: blog
DROP TABLE IF EXISTS `blog`;
CREATE TABLE `blog` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` tinytext NOT NULL,
  `subtitle` tinytext NOT NULL,
  `content` text NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- Table: users
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` varchar(20) DEFAULT 'player',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `reset_token` varchar(64) DEFAULT NULL,
  `reset_expires` datetime DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `fav_team` varchar(191) DEFAULT NULL,
  `theme` varchar(32) DEFAULT 'default',
  `avatar_data` longblob,
  `avatar_type` varchar(50) DEFAULT 'image/jpeg',
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Table: brackets
DROP TABLE IF EXISTS `brackets`;
CREATE TABLE `brackets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL DEFAULT '0',
  `person` text NOT NULL,
  `name` varchar(128) NOT NULL DEFAULT '',
  `email` text NOT NULL,
  `tiebreaker` int(3) NOT NULL DEFAULT '0',
  `paid` tinyint(1) NOT NULL DEFAULT '0' COMMENT '1=paid,0=unpaid,2=exempted',
  `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'time bracket was submitted',
  `1` varchar(32) NOT NULL DEFAULT '',
  `2` varchar(32) NOT NULL DEFAULT '',
  `3` varchar(32) NOT NULL DEFAULT '',
  `4` varchar(32) NOT NULL DEFAULT '',
  `5` varchar(32) NOT NULL DEFAULT '',
  `6` varchar(32) NOT NULL DEFAULT '',
  `7` varchar(32) NOT NULL DEFAULT '',
  `8` varchar(32) NOT NULL DEFAULT '',
  `9` varchar(32) NOT NULL DEFAULT '',
  `10` varchar(32) NOT NULL DEFAULT '',
  `11` varchar(32) NOT NULL DEFAULT '',
  `12` varchar(32) NOT NULL DEFAULT '',
  `13` varchar(32) NOT NULL DEFAULT '',
  `14` varchar(32) NOT NULL DEFAULT '',
  `15` varchar(32) NOT NULL DEFAULT '',
  `16` varchar(32) NOT NULL DEFAULT '',
  `17` varchar(32) NOT NULL DEFAULT '',
  `18` varchar(32) NOT NULL DEFAULT '',
  `19` varchar(32) NOT NULL DEFAULT '',
  `20` varchar(32) NOT NULL DEFAULT '',
  `21` varchar(32) NOT NULL DEFAULT '',
  `22` varchar(32) NOT NULL DEFAULT '',
  `23` varchar(32) NOT NULL DEFAULT '',
  `24` varchar(32) NOT NULL DEFAULT '',
  `25` varchar(32) NOT NULL DEFAULT '',
  `26` varchar(32) NOT NULL DEFAULT '',
  `27` varchar(32) NOT NULL DEFAULT '',
  `28` varchar(32) NOT NULL DEFAULT '',
  `29` varchar(32) NOT NULL DEFAULT '',
  `30` varchar(32) NOT NULL DEFAULT '',
  `31` varchar(32) NOT NULL DEFAULT '',
  `32` varchar(32) NOT NULL DEFAULT '',
  `33` varchar(32) NOT NULL DEFAULT '',
  `34` varchar(32) NOT NULL DEFAULT '',
  `35` varchar(32) NOT NULL DEFAULT '',
  `36` varchar(32) NOT NULL DEFAULT '',
  `37` varchar(32) NOT NULL DEFAULT '',
  `38` varchar(32) NOT NULL DEFAULT '',
  `39` varchar(32) NOT NULL DEFAULT '',
  `40` varchar(32) NOT NULL DEFAULT '',
  `41` varchar(32) NOT NULL DEFAULT '',
  `42` varchar(32) NOT NULL DEFAULT '',
  `43` varchar(32) NOT NULL DEFAULT '',
  `44` varchar(32) NOT NULL DEFAULT '',
  `45` varchar(32) NOT NULL DEFAULT '',
  `46` varchar(32) NOT NULL DEFAULT '',
  `47` varchar(32) NOT NULL DEFAULT '',
  `48` varchar(32) NOT NULL DEFAULT '',
  `49` varchar(32) NOT NULL DEFAULT '',
  `50` varchar(32) NOT NULL DEFAULT '',
  `51` varchar(32) NOT NULL DEFAULT '',
  `52` varchar(32) NOT NULL DEFAULT '',
  `53` varchar(32) NOT NULL DEFAULT '',
  `54` varchar(32) NOT NULL DEFAULT '',
  `55` varchar(32) NOT NULL DEFAULT '',
  `56` varchar(32) NOT NULL DEFAULT '',
  `57` varchar(32) NOT NULL DEFAULT '',
  `58` varchar(32) NOT NULL DEFAULT '',
  `59` varchar(32) NOT NULL DEFAULT '',
  `60` varchar(32) NOT NULL DEFAULT '',
  `61` varchar(32) NOT NULL DEFAULT '',
  `62` varchar(32) NOT NULL DEFAULT '',
  `63` varchar(32) NOT NULL DEFAULT '',
  `eliminated` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Equals 1 when eliminated',
  `avatar_url` varchar(255) DEFAULT NULL,
  `type` varchar(20) DEFAULT 'main',
  `disabled` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `idx_paid_type` (`paid`,`type`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- Table: bracket_badges
DROP TABLE IF EXISTS `bracket_badges`;
CREATE TABLE `bracket_badges` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `bracket_id` int(11) NOT NULL,
  `badge_id` int(11) NOT NULL,
  `awarded_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_badge` (`bracket_id`,`badge_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

-- Table: broadcasts
DROP TABLE IF EXISTS `broadcasts`;
CREATE TABLE `broadcasts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `message` text COLLATE utf8mb4_unicode_ci,
  `type` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'info',
  `active` tinyint(4) DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Table: comments
DROP TABLE IF EXISTS `comments`;
CREATE TABLE `comments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `bracket` int(11) NOT NULL,
  `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `from` tinytext NOT NULL,
  `subject` tinytext NOT NULL,
  `content` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- Table: end_games
DROP TABLE IF EXISTS `end_games`;
CREATE TABLE `end_games` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `49` varchar(32) DEFAULT NULL,
  `50` varchar(32) DEFAULT NULL,
  `51` varchar(32) DEFAULT NULL,
  `52` varchar(32) DEFAULT NULL,
  `53` varchar(32) DEFAULT NULL,
  `54` varchar(32) DEFAULT NULL,
  `55` varchar(32) DEFAULT NULL,
  `56` varchar(32) DEFAULT NULL,
  `57` varchar(32) DEFAULT NULL,
  `58` varchar(32) DEFAULT NULL,
  `59` varchar(32) DEFAULT NULL,
  `60` varchar(32) DEFAULT NULL,
  `61` varchar(32) DEFAULT NULL,
  `62` varchar(32) DEFAULT NULL,
  `63` varchar(32) DEFAULT NULL,
  `round` int(11) DEFAULT NULL,
  `eliminated` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_round_elim` (`round`,`eliminated`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- Table: endgame_summary
DROP TABLE IF EXISTS `endgame_summary`;
CREATE TABLE `endgame_summary` (
  `bracket_id` int(11) NOT NULL,
  `rank` int(11) NOT NULL,
  `num_paths` int(11) NOT NULL,
  `p_win` float DEFAULT 0,
  PRIMARY KEY (`bracket_id`, `rank`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

-- Table: hall_of_fame
DROP TABLE IF EXISTS `hall_of_fame`;
CREATE TABLE `hall_of_fame` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `bracket_id` int(11) NOT NULL,
  `year` int(4) NOT NULL,
  `achievement` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'Champion',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Table: historical_results
DROP TABLE IF EXISTS `historical_results`;
CREATE TABLE `historical_results` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `bracket_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `year` int(4) NOT NULL,
  `tourney_type` varchar(20) NOT NULL DEFAULT 'main',
  `rank` int(11) NOT NULL,
  `score` int(11) NOT NULL,
  `earnings` decimal(10,2) NOT NULL DEFAULT '0.00',
  `chat_count` int(11) NOT NULL DEFAULT '0',
  `champion_pick` varchar(64) DEFAULT NULL,
  `games_correct` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `email` (`email`(250)),
  KEY `year` (`year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Table: historical_badges
DROP TABLE IF EXISTS `historical_badges`;
CREATE TABLE `historical_badges` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `badge_id` int(11) NOT NULL,
  `year` int(4) NOT NULL,
  `tourney_type` varchar(20) NOT NULL DEFAULT 'main',
  `awarded_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `email` (`email`(250)),
  KEY `year` (`year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Table: master
DROP TABLE IF EXISTS `master`;
CREATE TABLE `master` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `1` varchar(32) NOT NULL DEFAULT '',
  `2` varchar(32) NOT NULL DEFAULT '',
  `3` varchar(32) NOT NULL DEFAULT '',
  `4` varchar(32) NOT NULL DEFAULT '',
  `5` varchar(32) NOT NULL DEFAULT '',
  `6` varchar(32) NOT NULL DEFAULT '',
  `7` varchar(32) NOT NULL DEFAULT '',
  `8` varchar(32) NOT NULL DEFAULT '',
  `9` varchar(32) NOT NULL DEFAULT '',
  `10` varchar(32) NOT NULL DEFAULT '',
  `11` varchar(32) NOT NULL DEFAULT '',
  `12` varchar(32) NOT NULL DEFAULT '',
  `13` varchar(32) NOT NULL DEFAULT '',
  `14` varchar(32) NOT NULL DEFAULT '',
  `15` varchar(32) NOT NULL DEFAULT '',
  `16` varchar(32) NOT NULL DEFAULT '',
  `17` varchar(32) NOT NULL DEFAULT '',
  `18` varchar(32) NOT NULL DEFAULT '',
  `19` varchar(32) NOT NULL DEFAULT '',
  `20` varchar(32) NOT NULL DEFAULT '',
  `21` varchar(32) NOT NULL DEFAULT '',
  `22` varchar(32) NOT NULL DEFAULT '',
  `23` varchar(32) NOT NULL DEFAULT '',
  `24` varchar(32) NOT NULL DEFAULT '',
  `25` varchar(32) NOT NULL DEFAULT '',
  `26` varchar(32) NOT NULL DEFAULT '',
  `27` varchar(32) NOT NULL DEFAULT '',
  `28` varchar(32) NOT NULL DEFAULT '',
  `29` varchar(32) NOT NULL DEFAULT '',
  `30` varchar(32) NOT NULL DEFAULT '',
  `31` varchar(32) NOT NULL DEFAULT '',
  `32` varchar(32) NOT NULL DEFAULT '',
  `33` varchar(32) NOT NULL DEFAULT '',
  `34` varchar(32) NOT NULL DEFAULT '',
  `35` varchar(32) NOT NULL DEFAULT '',
  `36` varchar(32) NOT NULL DEFAULT '',
  `37` varchar(32) NOT NULL DEFAULT '',
  `38` varchar(32) NOT NULL DEFAULT '',
  `39` varchar(32) NOT NULL DEFAULT '',
  `40` varchar(32) NOT NULL DEFAULT '',
  `41` varchar(32) NOT NULL DEFAULT '',
  `42` varchar(32) NOT NULL DEFAULT '',
  `43` varchar(32) NOT NULL DEFAULT '',
  `44` varchar(32) NOT NULL DEFAULT '',
  `45` varchar(32) NOT NULL DEFAULT '',
  `46` varchar(32) NOT NULL DEFAULT '',
  `47` varchar(32) NOT NULL DEFAULT '',
  `48` varchar(32) NOT NULL DEFAULT '',
  `49` varchar(32) NOT NULL DEFAULT '',
  `50` varchar(32) NOT NULL DEFAULT '',
  `51` varchar(32) NOT NULL DEFAULT '',
  `52` varchar(32) NOT NULL DEFAULT '',
  `53` varchar(32) NOT NULL DEFAULT '',
  `54` varchar(32) NOT NULL DEFAULT '',
  `55` varchar(32) NOT NULL DEFAULT '',
  `56` varchar(32) NOT NULL DEFAULT '',
  `57` varchar(32) NOT NULL DEFAULT '',
  `58` varchar(32) NOT NULL DEFAULT '',
  `59` varchar(32) NOT NULL DEFAULT '',
  `60` varchar(32) NOT NULL DEFAULT '',
  `61` varchar(32) NOT NULL DEFAULT '',
  `62` varchar(32) NOT NULL DEFAULT '',
  `63` varchar(32) NOT NULL DEFAULT '',
  `64` varchar(32) NOT NULL DEFAULT '',
  `type` varchar(32) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- Table: meta
DROP TABLE IF EXISTS `meta`;
CREATE TABLE `meta` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(64) NOT NULL,
  `subtitle` varchar(128) NOT NULL,
  `name` varchar(128) NOT NULL,
  `email` varchar(128) NOT NULL,
  `cost` double NOT NULL,
  `cut` double NOT NULL,
  `cutType` int(1) NOT NULL COMMENT '1=percent, 0=dollars',
  `closed` tinyint(1) NOT NULL COMMENT '1=submission is closed',
  `sweet16` tinyint(1) NOT NULL COMMENT '1=sweet 16 has started',
  `rules` text NOT NULL,
  `mail` int(1) NOT NULL,
  `tiebreaker` int(3) DEFAULT NULL,
  `sweet16Competition` tinyint(1) NOT NULL COMMENT '1=this is a sweet 16 tourney',
  `region1` varchar(64) NOT NULL,
  `region2` varchar(64) NOT NULL,
  `region3` varchar(64) NOT NULL,
  `region4` varchar(64) NOT NULL,
  `db_version` varchar(255) NOT NULL DEFAULT '0',
  `deadline` datetime DEFAULT NULL,
  `use_live_scoring` tinyint(1) NOT NULL DEFAULT '0',
  `payout_1` decimal(5,2) NOT NULL DEFAULT '60.00',
  `payout_2` decimal(5,2) NOT NULL DEFAULT '30.00',
  `payout_3` decimal(5,2) NOT NULL DEFAULT '10.00',
  `refund_last` tinyint(1) NOT NULL DEFAULT '0',
  `reg_mode` int(1) DEFAULT '0',
  `reg_password` varchar(255) DEFAULT '',
  `reg_token` varchar(255) DEFAULT '',
  `max_brackets` int(11) NOT NULL DEFAULT '1',
  `sweet16_cost` DECIMAL(10,2) DEFAULT '0.00',
  `sweet16_cut` DECIMAL(10,2) DEFAULT '0.00',
  `sweet16_cutType` TINYINT(1) DEFAULT '0',
  `sweet16_payout_1` INT DEFAULT '60',
  `sweet16_payout_2` INT DEFAULT '30',
  `sweet16_payout_3` INT DEFAULT '10',
  `sweet16_deadline` DATETIME DEFAULT NULL,
  `sweet16_closed` TINYINT(1) DEFAULT '0',
  `sweet16_reg_mode` TINYINT(1) DEFAULT '0',
  `sweet16_reg_password` VARCHAR(255) DEFAULT '',
  `sweet16_reg_token` VARCHAR(255) DEFAULT '',
  `sweet16_refund_last` TINYINT(1) DEFAULT '0',
  `max_sweet16_brackets` int(11) NOT NULL DEFAULT '1',
  `qr_code_data` LONGBLOB,
  `qr_code_type` VARCHAR(50),
  `sweet16_qr_data` LONGBLOB,
  `sweet16_qr_type` VARCHAR(50),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- Table: passwords
DROP TABLE IF EXISTS `passwords`;
CREATE TABLE `passwords` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `label` varchar(255) NOT NULL,
  `hash` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `label` (`label`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Used for user login validation';

-- --------------------------------------------------------

-- Table: possible_scores
DROP TABLE IF EXISTS `possible_scores`;
CREATE TABLE `possible_scores` (
  `outcome_id` int(11) DEFAULT NULL,
  `bracket_id` int(11) DEFAULT NULL,
  `score` double DEFAULT NULL,
  `type` char(32) DEFAULT NULL,
  `rank` int(11) DEFAULT NULL,
  `eliminated` tinyint(1) NOT NULL DEFAULT '0',
  KEY `idx_outcome` (`outcome_id`),
  KEY `idx_bracket` (`bracket_id`),
  KEY `idx_eliminated` (`eliminated`),
  KEY `idx_calc_cover` (`type`,`rank`,`bracket_id`,`outcome_id`),
  KEY `idx_calc_elim` (`type`,`outcome_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- Table: possible_scores_eliminated
DROP TABLE IF EXISTS `possible_scores_eliminated`;
CREATE TABLE `possible_scores_eliminated` (
  `outcome_id` int(11) DEFAULT NULL,
  `bracket_id` int(11) DEFAULT NULL,
  `score` double DEFAULT NULL,
  `type` char(32) DEFAULT NULL,
  `rank` int(11) DEFAULT NULL,
  `eliminated` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- Table: probability_of_winning
DROP TABLE IF EXISTS `probability_of_winning`;
CREATE TABLE `probability_of_winning` (
  `id` int(11) DEFAULT NULL,
  `rank` int(11) DEFAULT NULL,
  `probability_win` double DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- Table: rank_history
DROP TABLE IF EXISTS `rank_history`;
CREATE TABLE `rank_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `bracket_id` int(11) NOT NULL,
  `rank` int(11) NOT NULL,
  `score` int(11) NOT NULL,
  `timestamp` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `bracket_id` (`bracket_id`),
  KEY `timestamp` (`timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- Table: rivals
DROP TABLE IF EXISTS `rivals`;
CREATE TABLE `rivals` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `rival_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- Table: scores
DROP TABLE IF EXISTS `scores`;
CREATE TABLE `scores` (
  `id` int(11) NOT NULL DEFAULT '0',
  `name` varchar(128) NOT NULL DEFAULT '',
  `score` double NOT NULL DEFAULT '0',
  `scoring_type` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`scoring_type`,`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- Table: scoring
DROP TABLE IF EXISTS `scoring`;
CREATE TABLE `scoring` (
  `seed` int(11) NOT NULL DEFAULT '0',
  `1` double DEFAULT NULL,
  `2` double DEFAULT NULL,
  `3` double DEFAULT NULL,
  `4` double DEFAULT NULL,
  `5` double DEFAULT NULL,
  `6` double DEFAULT NULL,
  `type` char(255) DEFAULT NULL,
  KEY `system` (`type`,`seed`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

INSERT INTO `scoring` (`seed`, `1`, `2`, `3`, `4`, `5`, `6`, `type`) VALUES
(2, 1, 2, 4, 8, 16, 32, 'geometric'),
(3, 1, 2, 4, 8, 16, 32, 'geometric'),
(4, 1, 2, 4, 8, 16, 32, 'geometric'),
(5, 1, 2, 4, 8, 16, 32, 'geometric'),
(6, 1, 2, 4, 8, 16, 32, 'geometric'),
(7, 1, 2, 4, 8, 16, 32, 'geometric'),
(8, 1, 2, 4, 8, 16, 32, 'geometric'),
(9, 1, 2, 4, 8, 16, 32, 'geometric'),
(10, 1, 2, 4, 8, 16, 32, 'geometric'),
(11, 1, 2, 4, 8, 16, 32, 'geometric'),
(12, 1, 2, 4, 8, 16, 32, 'geometric'),
(13, 1, 2, 4, 8, 16, 32, 'geometric'),
(14, 1, 2, 4, 8, 16, 32, 'geometric'),
(15, 1, 2, 4, 8, 16, 32, 'geometric'),
(16, 1, 2, 4, 8, 16, 32, 'geometric'),
(1, 1, 2, 4, 8, 16, 32, 'geometric'),
(16, 10, 20, 40, 80, 120, 160, 'standard'),
(15, 10, 20, 40, 80, 120, 160, 'standard'),
(14, 10, 20, 40, 80, 120, 160, 'standard'),
(13, 10, 20, 40, 80, 120, 160, 'standard'),
(12, 10, 20, 40, 80, 120, 160, 'standard'),
(11, 10, 20, 40, 80, 120, 160, 'standard'),
(10, 10, 20, 40, 80, 120, 160, 'standard'),
(9, 10, 20, 40, 80, 120, 160, 'standard'),
(8, 10, 20, 40, 80, 120, 160, 'standard'),
(7, 10, 20, 40, 80, 120, 160, 'standard'),
(6, 10, 20, 40, 80, 120, 160, 'standard'),
(5, 10, 20, 40, 80, 120, 160, 'standard'),
(4, 10, 20, 40, 80, 120, 160, 'standard'),
(3, 10, 20, 40, 80, 120, 160, 'standard'),
(2, 10, 20, 40, 80, 120, 160, 'standard'),
(1, 10, 20, 40, 80, 120, 160, 'standard'),
(1, 2, 3, 5, 8, 13, 21, 'fibonacci'),
(2, 2, 3, 5, 8, 13, 21, 'fibonacci'),
(3, 2, 3, 5, 8, 13, 21, 'fibonacci'),
(4, 2, 3, 5, 8, 13, 21, 'fibonacci'),
(5, 2, 3, 5, 8, 13, 21, 'fibonacci'),
(6, 2, 3, 5, 8, 13, 21, 'fibonacci'),
(7, 2, 3, 5, 8, 13, 21, 'fibonacci'),
(8, 2, 3, 5, 8, 13, 21, 'fibonacci'),
(9, 2, 3, 5, 8, 13, 21, 'fibonacci'),
(10, 2, 3, 5, 8, 13, 21, 'fibonacci'),
(11, 2, 3, 5, 8, 13, 21, 'fibonacci'),
(12, 2, 3, 5, 8, 13, 21, 'fibonacci'),
(13, 2, 3, 5, 8, 13, 21, 'fibonacci'),
(14, 2, 3, 5, 8, 13, 21, 'fibonacci'),
(15, 2, 3, 5, 8, 13, 21, 'fibonacci'),
(16, 2, 3, 5, 8, 13, 21, 'fibonacci');

-- --------------------------------------------------------

-- Table: scoring_info
DROP TABLE IF EXISTS `scoring_info`;
CREATE TABLE `scoring_info` (
  `type` varchar(255) NOT NULL DEFAULT '',
  `display_name` varchar(255) DEFAULT NULL,
  `description` blob,
  PRIMARY KEY (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

INSERT INTO `scoring_info` (`type`, `display_name`, `description`) VALUES
('standard', 'Standard Scoring', 0x5374616e646172642053636f72696e67),
('geometric', 'Geometric', 0x3c7461626c6520626f726465723d2731273e3c747220616c69676e3d2763656e746572273e3c746420636f6c7370616e3d2737273e47656f6d65747269633c2f74643e3c2f74723e3c747220616c69676e3d2763656e746572273e3c74643e53656564733c2f74643e3c746420636f6c7370616e3d2736273e526f756e64733c2f74643e3c2f74723e3c74723e3c74643e266e6273703b3c2f74643e3c74643e313c2f74643e3c74643e323c2f74643e3c74643e333c2f74643e3c74643e343c2f74643e3c74643e353c2f74643e3c74643e363c2f74643e3c2f74723e3c74723e3c74643e313c2f74643e3c74643e313c2f74643e3c74643e323c2f74643e3c74643e343c2f74643e3c74643e383c2f74643e3c74643e31363c2f74643e3c74643e33323c2f74643e3c74723e3c74723e3c74643e323c2f74643e3c74643e313c2f74643e3c74643e323c2f74643e3c74643e343c2f74643e3c74643e383c2f74643e3c74643e31363c2f74643e3c74643e33323c2f74643e3c74723e3c74723e3c74643e333c2f74643e3c74643e313c2f74643e3c74643e323c2f74643e3c74643e343c2f74643e3c74643e383c2f74643e3c74643e31363c2f74643e3c74643e33323c2f74643e3c74723e3c74723e3c74643e343c2f74643e3c74643e313c2f74643e3c74643e323c2f74643e3c74643e343c2f74643e3c74643e383c2f74643e3c74643e31363c2f74643e3c74643e33323c2f74643e3c74723e3c74723e3c74643e353c2f74643e3c74643e313c2f74643e3c74643e323c2f74643e3c74643e343c2f74643e3c74643e383c2f74643e3c74643e31363c2f74643e3c74643e33323c2f74643e3c74723e3c74723e3c74643e363c2f74643e3c74643e313c2f74643e3c74643e323c2f74643e3c74643e343c2f74643e3c74643e383c2f74643e3c74643e31363c2f74643e3c74643e33323c2f74643e3c74723e3c74723e3c74643e373c2f74643e3c74643e313c2f74643e3c74643e323c2f74643e3c74643e343c2f74643e3c74643e383c2f74643e3c74643e31363c2f74643e3c74643e33323c2f74643e3c74723e3c74723e3c74643e383c2f74643e3c74643e313c2f74643e3c74643e323c2f74643e3c74643e343c2f74643e3c74643e383c2f74643e3c74643e31363c2f74643e3c74643e33323c2f74643e3c74723e3c74723e3c74643e393c2f74643e3c74643e313c2f74643e3c74643e323c2f74643e3c74643e343c2f74643e3c74643e383c2f74643e3c74643e31363c2f74643e3c74643e33323c2f74643e3c74723e3c74723e3c74643e31303c2f74643e3c74643e313c2f74643e3c74643e323c2f74643e3c74643e343c2f74643e3c74643e383c2f74643e3c74643e31363c2f74643e3c74643e33323c2f74643e3c74723e3c74723e3c74643e31313c2f74643e3c74643e313c2f74643e3c74643e323c2f74643e3c74643e343c2f74643e3c74643e383c2f74643e3c74643e31363c2f74643e3c74643e33323c2f74643e3c74723e3c74723e3c74643e31323c2f74643e3c74643e313c2f74643e3c74643e323c2f74643e3c74643e343c2f74643e3c74643e383c2f74643e3c74643e31363c2f74643e3c74643e33323c2f74643e3c74723e3c74723e3c74643e31333c2f74643e3c74643e313c2f74643e3c74643e323c2f74643e3c74643e343c2f74643e3c74643e383c2f74643e3c74643e31363c2f74643e3c74643e33323c2f74643e3c74723e3c74723e3c74643e31343c2f74643e3c74643e313c2f74643e3c74643e323c2f74643e3c74643e343c2f74643e3c74643e383c2f74643e3c74643e31363c2f74643e3c74643e33323c2f74643e3c74723e3c74723e3c74643e31353c2f74643e3c74643e313c2f74643e3c74643e323c2f74643e3c74643e343c2f74643e3c74643e383c2f74643e3c74643e31363c2f74643e3c74643e33323c2f74643e3c74723e3c74723e3c74643e31363c2f74643e3c74643e313c2f74643e3c74643e323c2f74643e3c74643e343c2f74643e3c74643e383c2f74643e3c74643e31363c2f74643e3c74643e33323c2f74643e3c74723e3c2f7461626c653e);

-- --------------------------------------------------------

-- Table: themes
DROP TABLE IF EXISTS `themes`;
CREATE TABLE `themes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `theme_key` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `accent` varchar(20) NOT NULL,
  `header1` varchar(20) NOT NULL,
  `header2` varchar(20) NOT NULL,
  `bg1` varchar(20) NOT NULL,
  `bg2` varchar(20) NOT NULL,
  `group_name` varchar(50) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `theme_key` (`theme_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `themes` (`theme_key`, `name`, `accent`, `header1`, `header2`, `bg1`, `bg2`, `group_name`) VALUES
('default', 'Default (Midnight)', '#f97316', '#0f172a', '#1e293b', '#0f172a', '#1e293b', 'System');
