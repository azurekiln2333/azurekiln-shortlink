SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

ALTER TABLE `settings` ADD `cstm-style` LONGTEXT NOT NULL AFTER `email`;
UPDATE `settings` SET `logo_url`='img/logo-light.png;img/logo-dark.png';
CREATE TABLE IF NOT EXISTS `shortny_users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `provider` varchar(32) NOT NULL DEFAULT 'oauth2',
  `subject` varchar(128) NOT NULL,
  `email` varchar(255) NOT NULL DEFAULT '',
  `display_name` varchar(255) NOT NULL DEFAULT '',
  `status` enum('pending','approved','suspended','rejected') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_login_at` datetime DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `approved_by` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `provider_subject` (`provider`,`subject`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Run this upgrade once after backing up the existing database.
ALTER TABLE `links`
  ADD COLUMN `owner_user_id` bigint(20) unsigned DEFAULT NULL,
  ADD COLUMN `status` enum('active','paused') NOT NULL DEFAULT 'active',
  ADD COLUMN `expires_at` datetime DEFAULT NULL,
  ADD COLUMN `max_hits` int(10) unsigned DEFAULT NULL,
  ADD KEY `links_owner_user_id` (`owner_user_id`),
  ADD KEY `links_status_expires` (`status`,`expires_at`);

CREATE TABLE IF NOT EXISTS `shortny_clicks` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT, `link_id` int(11) NOT NULL,
  `clicked_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP, `referrer` varchar(512) NOT NULL DEFAULT '',
  `device_type` varchar(32) NOT NULL DEFAULT 'unknown', `browser` varchar(64) NOT NULL DEFAULT 'unknown',
  `operating_system` varchar(64) NOT NULL DEFAULT 'unknown', `ip_hash` char(64) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`), KEY `link_clicked` (`link_id`,`clicked_at`), KEY `clicked_at` (`clicked_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS `shortny_api_keys` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT, `user_id` bigint(20) unsigned NOT NULL,
  `label` varchar(100) NOT NULL DEFAULT '', `key_prefix` varchar(16) NOT NULL, `key_hash` char(64) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP, `last_used_at` datetime DEFAULT NULL, `revoked_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`), UNIQUE KEY `key_hash` (`key_hash`), KEY `user_status` (`user_id`,`revoked_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS `shortny_rate_limits` (
  `subject_hash` char(64) NOT NULL, `action` varchar(64) NOT NULL, `bucket_start` datetime NOT NULL,
  `hits` int(10) unsigned NOT NULL DEFAULT '0', PRIMARY KEY (`subject_hash`,`action`,`bucket_start`), KEY `bucket_start` (`bucket_start`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS `shortny_blocked_domains` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT, `hostname` varchar(255) NOT NULL, `note` varchar(255) NOT NULL DEFAULT '',
  `enabled` tinyint(1) NOT NULL DEFAULT '1', `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP, `created_by` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`), UNIQUE KEY `hostname` (`hostname`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS `shortny_audit_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT, `actor_type` varchar(32) NOT NULL, `actor_user_id` bigint(20) unsigned DEFAULT NULL,
  `action` varchar(64) NOT NULL, `target_type` varchar(32) NOT NULL DEFAULT '', `target_id` bigint(20) unsigned DEFAULT NULL,
  `metadata_json` text NOT NULL, `ip_hash` char(64) NOT NULL DEFAULT '', `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`), KEY `audit_created` (`created_at`), KEY `audit_target` (`target_type`,`target_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
