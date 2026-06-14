-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- ホスト: wp_survey_mysqld
-- 生成日時: 2026 年 6 月 10 日 10:47
-- サーバのバージョン： 10.6.27-MariaDB-ubu2204
-- PHP のバージョン: 8.3.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- データベース: `survey`
--

-- --------------------------------------------------------

--
-- テーブルの構造 `phinxlog`
--

CREATE TABLE `phinxlog` (
  `version` bigint(20) NOT NULL,
  `migration_name` varchar(100) DEFAULT NULL,
  `start_time` timestamp NULL DEFAULT NULL,
  `end_time` timestamp NULL DEFAULT NULL,
  `breakpoint` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- テーブルの構造 `respondents`
--

CREATE TABLE `respondents` (
  `id` int(11) UNSIGNED NOT NULL,
  `line_user_id` varchar(255) DEFAULT NULL,
  `line_display_name` varchar(255) DEFAULT NULL,
  `respondent_master_id` int(11) UNSIGNED DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `honorific` varchar(50) DEFAULT NULL,
  `is_manually_entered` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- テーブルの構造 `respondent_masters`
--

CREATE TABLE `respondent_masters` (
  `id` int(11) UNSIGNED NOT NULL,
  `master_code` varchar(100) DEFAULT NULL,
  `line_display_name` varchar(255) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `honorific` varchar(50) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- テーブルの構造 `response_drafts`
--

CREATE TABLE `response_drafts` (
  `id` int(11) UNSIGNED NOT NULL,
  `survey_id` int(11) UNSIGNED DEFAULT NULL,
  `respondent_id` int(11) UNSIGNED DEFAULT NULL,
  `answer_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`answer_json`)),
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- テーブルの構造 `responses`
--

CREATE TABLE `responses` (
  `id` int(11) UNSIGNED NOT NULL,
  `survey_id` int(11) UNSIGNED DEFAULT NULL,
  `respondent_id` int(11) UNSIGNED DEFAULT NULL,
  `edit_token` varchar(128) DEFAULT NULL,
  `answer_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`answer_json`)),
  `survey_snapshot_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`survey_snapshot_json`)),
  `submitted_at` datetime DEFAULT NULL,
  `email_sent_at` datetime DEFAULT NULL,
  `email_error` text DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- テーブルの構造 `surveys`
--

CREATE TABLE `surveys` (
  `id` int(11) UNSIGNED NOT NULL,
  `public_id` varchar(64) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `questions_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`questions_json`)),
  `status` varchar(20) DEFAULT 'draft',
  `allow_multiple` tinyint(1) DEFAULT 0,
  `allow_edit` tinyint(1) DEFAULT 0,
  `starts_at` datetime DEFAULT NULL,
  `ends_at` datetime DEFAULT NULL,
  `send_confirmation_email` tinyint(1) DEFAULT 1,
  `include_answers_in_email` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- ダンプしたテーブルのインデックス
--

--
-- テーブルのインデックス `phinxlog`
--
ALTER TABLE `phinxlog`
  ADD PRIMARY KEY (`version`);

--
-- テーブルのインデックス `respondents`
--
ALTER TABLE `respondents`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `line_user_id` (`line_user_id`),
  ADD KEY `respondent_master_id` (`respondent_master_id`);

--
-- テーブルのインデックス `respondent_masters`
--
ALTER TABLE `respondent_masters`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `master_code` (`master_code`),
  ADD UNIQUE KEY `line_display_name` (`line_display_name`);

--
-- テーブルのインデックス `response_drafts`
--
ALTER TABLE `response_drafts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `survey_id` (`survey_id`),
  ADD KEY `respondent_id` (`respondent_id`),
  ADD UNIQUE KEY `idx_survey_respondent` (`survey_id`,`respondent_id`);

--
-- テーブルのインデックス `responses`
--
ALTER TABLE `responses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `edit_token` (`edit_token`),
  ADD KEY `survey_id` (`survey_id`),
  ADD KEY `respondent_id` (`respondent_id`),
  ADD KEY `idx_survey_respondent` (`survey_id`,`respondent_id`);

--
-- テーブルのインデックス `surveys`
--
ALTER TABLE `surveys`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `public_id` (`public_id`);

--
-- ダンプしたテーブルの AUTO_INCREMENT
--

--
-- テーブルの AUTO_INCREMENT `respondents`
--
ALTER TABLE `respondents`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- テーブルの AUTO_INCREMENT `respondent_masters`
--
ALTER TABLE `respondent_masters`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- テーブルの AUTO_INCREMENT `response_drafts`
--
ALTER TABLE `response_drafts`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- テーブルの AUTO_INCREMENT `responses`
--
ALTER TABLE `responses`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- テーブルの AUTO_INCREMENT `surveys`
--
ALTER TABLE `surveys`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- ダンプしたテーブルの制約
--

--
-- テーブルの制約 `response_drafts`
--
ALTER TABLE `response_drafts`
  ADD CONSTRAINT `response_drafts_ibfk_1` FOREIGN KEY (`survey_id`) REFERENCES `surveys` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `response_drafts_ibfk_2` FOREIGN KEY (`respondent_id`) REFERENCES `respondents` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- テーブルの制約 `responses`
--
ALTER TABLE `responses`
  ADD CONSTRAINT `responses_ibfk_1` FOREIGN KEY (`survey_id`) REFERENCES `surveys` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `responses_ibfk_2` FOREIGN KEY (`respondent_id`) REFERENCES `respondents` (`id`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
