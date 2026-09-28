-- phpMyAdmin SQL Dump
-- version 4.6.6deb5
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Oct 25, 2018 at 11:49 PM
-- Server version: 5.7.23-0ubuntu0.18.04.1
-- PHP Version: 7.2.10-0ubuntu0.18.04.1

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `shortny`
--

-- --------------------------------------------------------


--
-- Table structure for table `shortny_users`
--

CREATE TABLE `shortny_users` (
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

-- --------------------------------------------------------

--
-- Table structure for table `ads`
--

CREATE TABLE `ads` (
  `ad1` text NOT NULL,
  `ad2` text NOT NULL,
  `ad3` text NOT NULL,
  `ad4` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `ads`
--

INSERT INTO `ads` (`ad1`, `ad2`, `ad3`, `ad4`) VALUES
('<h2>这是什么？</h2><p><h4>一个可添加任何所需内容的可用空间。描述，广告，公告。前往管理面板进行编辑</h4> </p>', '<h2>这是什么？</h2><p><h4>一个可添加任何所需内容的可用空间。描述，广告，公告。前往管理面板进行编辑</h4> </p>', '<h2>这是什么？</h2><p></p><h4>一个可添加任何所需内容的可用空间。描述，广告，公告。前往管理面板进行编辑</h4> <p></p><p>ads 300x250 recommended </p> ', '<h2>这是什么？</h2><p></p><h4>一个可添加任何所需内容的可用空间。描述，广告，公告。前往管理面板进行编辑</h4> <p></p><p>ads 300x250 recommended </p> ');

-- --------------------------------------------------------

--
-- Table structure for table `links`
--

CREATE TABLE `links` (
  `id` int(11) NOT NULL,
  `URL` varchar(255) NOT NULL,
  `link` varchar(16) NOT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `hits` int(16) NOT NULL DEFAULT '0',
  `pass` varchar(255) NOT NULL,
  `custom` int(1) NOT NULL DEFAULT '0',
  `last_visit` datetime NOT NULL,
  `owner_user_id` bigint(20) unsigned DEFAULT NULL,
  `status` enum('active','paused') NOT NULL DEFAULT 'active',
  `expires_at` datetime DEFAULT NULL,
  `max_hits` int(10) unsigned DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `name` varchar(64) NOT NULL,
  `URL` varchar(255) NOT NULL,
  `redirect` int(1) NOT NULL DEFAULT '1',
  `admin_user` varchar(255) NOT NULL,
  `admin_pass` varchar(255) NOT NULL,
  `logo_url` varchar(255) NOT NULL,
  `logo_type` int(1) NOT NULL DEFAULT '1',
  `email` varchar(255) NOT NULL,
  `cstm-style` longtext NOT NULL,
  `wait` int(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`name`, `URL`, `redirect`, `admin_user`, `admin_pass`, `logo_url`, `logo_type`, `email`, `cstm-style`,`wait`) VALUES
('Shortny', 'http://localhost/shortny', 1, 'admin', '', 'img/logo-light.png;img/logo-dark.png', 1, 'example@email.com', 'body {\r\n  background-color: #f1f1f1;\r\n}', 10);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `links`
--
ALTER TABLE `links`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `id` (`id`),
  ADD UNIQUE KEY `link` (`link`),
  ADD KEY `links_owner_user_id` (`owner_user_id`),
  ADD KEY `links_status_expires` (`status`,`expires_at`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD UNIQUE KEY `URL` (`URL`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `links`
--
ALTER TABLE `links`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=129;

CREATE TABLE `shortny_clicks` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `link_id` int(11) NOT NULL,
  `clicked_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `referrer` varchar(512) NOT NULL DEFAULT '',
  `device_type` varchar(32) NOT NULL DEFAULT 'unknown',
  `browser` varchar(64) NOT NULL DEFAULT 'unknown',
  `operating_system` varchar(64) NOT NULL DEFAULT 'unknown',
  `ip_hash` char(64) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`), KEY `link_clicked` (`link_id`,`clicked_at`), KEY `clicked_at` (`clicked_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `shortny_api_keys` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `label` varchar(100) NOT NULL DEFAULT '',
  `key_prefix` varchar(16) NOT NULL,
  `key_hash` char(64) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_used_at` datetime DEFAULT NULL,
  `revoked_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`), UNIQUE KEY `key_hash` (`key_hash`), KEY `user_status` (`user_id`,`revoked_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `shortny_rate_limits` (
  `subject_hash` char(64) NOT NULL,
  `action` varchar(64) NOT NULL,
  `bucket_start` datetime NOT NULL,
  `hits` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`subject_hash`,`action`,`bucket_start`), KEY `bucket_start` (`bucket_start`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `shortny_blocked_domains` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `hostname` varchar(255) NOT NULL,
  `note` varchar(255) NOT NULL DEFAULT '',
  `enabled` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`), UNIQUE KEY `hostname` (`hostname`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `shortny_audit_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `actor_type` varchar(32) NOT NULL,
  `actor_user_id` bigint(20) unsigned DEFAULT NULL,
  `action` varchar(64) NOT NULL,
  `target_type` varchar(32) NOT NULL DEFAULT '',
  `target_id` bigint(20) unsigned DEFAULT NULL,
  `metadata_json` text NOT NULL,
  `ip_hash` char(64) NOT NULL DEFAULT '',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`), KEY `audit_created` (`created_at`), KEY `audit_target` (`target_type`,`target_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
