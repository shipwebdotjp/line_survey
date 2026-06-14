CREATE TABLE `phinxlog` (
  `version` bigint(20) NOT NULL,
  `migration_name` varchar(100) DEFAULT NULL,
  `start_time` timestamp NULL DEFAULT NULL,
  `end_time` timestamp NULL DEFAULT NULL,
  `breakpoint` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE `respondent_masters` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `master_code` varchar(100) DEFAULT NULL,
  `line_display_name` varchar(255) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `honorific` varchar(50) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `master_code` (`master_code`),
  UNIQUE KEY `line_display_name` (`line_display_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE `surveys` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
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
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `public_id` (`public_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE `respondents` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `line_user_id` varchar(255) DEFAULT NULL,
  `line_display_name` varchar(255) DEFAULT NULL,
  `respondent_master_id` int(11) UNSIGNED DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `honorific` varchar(50) DEFAULT NULL,
  `is_manually_entered` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `line_user_id` (`line_user_id`),
  KEY `respondent_master_id` (`respondent_master_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE `response_drafts` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `survey_id` int(11) UNSIGNED NOT NULL,
  `respondent_id` int(11) UNSIGNED NOT NULL,
  `answer_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`answer_json`)),
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `survey_id` (`survey_id`),
  KEY `respondent_id` (`respondent_id`),
  UNIQUE KEY `idx_survey_respondent` (`survey_id`, `respondent_id`),
  CONSTRAINT `response_drafts_ibfk_1`
    FOREIGN KEY (`survey_id`)
    REFERENCES `surveys` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `response_drafts_ibfk_2`
    FOREIGN KEY (`respondent_id`)
    REFERENCES `respondents` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE `responses` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `survey_id` int(11) UNSIGNED DEFAULT NULL,
  `respondent_id` int(11) UNSIGNED DEFAULT NULL,
  `edit_token` varchar(128) DEFAULT NULL,
  `answer_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`answer_json`)),
  `survey_snapshot_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`survey_snapshot_json`)),
  `submitted_at` datetime DEFAULT NULL,
  `email_sent_at` datetime DEFAULT NULL,
  `email_error` text DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `edit_token` (`edit_token`),
  KEY `survey_id` (`survey_id`),
  KEY `respondent_id` (`respondent_id`),
  KEY `idx_survey_respondent` (`survey_id`, `respondent_id`),
  CONSTRAINT `responses_ibfk_1`
    FOREIGN KEY (`survey_id`)
    REFERENCES `surveys` (`id`)
    ON UPDATE CASCADE,
  CONSTRAINT `responses_ibfk_2`
    FOREIGN KEY (`respondent_id`)
    REFERENCES `respondents` (`id`)
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;