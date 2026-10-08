-- CREATE DATABASE IF NOT EXISTS
CREATE DATABASE IF NOT EXISTS db_ufinance CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE db_ufinance;

-- 1. categories
CREATE TABLE IF NOT EXISTS `categories` (
    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `categories_name_unique` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. transactions
CREATE TABLE IF NOT EXISTS `transactions` (
    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `type` enum('income','expense') COLLATE utf8mb4_unicode_ci NOT NULL,
    `amount` decimal(15,2) NOT NULL,
    `date` date NOT NULL,
    `category_id` bigint(20) unsigned DEFAULT NULL,
    `note` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `transactions_category_id_foreign` (`category_id`),
    KEY `transactions_date_index` (`date`),
    CONSTRAINT `transactions_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. goals
CREATE TABLE IF NOT EXISTS `goals` (
    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
    `target_amount` decimal(15,2) NOT NULL,
    `current_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
    `deadline` date DEFAULT NULL,
    `status` enum('active','achieved') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. recurring_transactions
CREATE TABLE IF NOT EXISTS `recurring_transactions` (
    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `type` enum('income','expense') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'expense',
    `amount` decimal(15,2) NOT NULL,
    `interval_days` int(11) NOT NULL,
    `start_date` date NOT NULL,
    `category_id` bigint(20) unsigned DEFAULT NULL,
    `note` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `recurring_transactions_category_id_foreign` (`category_id`),
    CONSTRAINT `recurring_transactions_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. financial_periods
CREATE TABLE IF NOT EXISTS `financial_periods` (
    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `start_date` date NOT NULL,
    `end_date` date NOT NULL,
    `total_days` int(10) unsigned NOT NULL,
    `budget_mode` enum('auto','manual') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'auto',
    `daily_budget` decimal(15,2) DEFAULT NULL,
    `linked_income_id` bigint(20) unsigned DEFAULT NULL,
    `is_active` tinyint(1) NOT NULL DEFAULT 1,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `financial_periods_linked_income_id_foreign` (`linked_income_id`),
    KEY `idx_financial_periods_active` (`is_active`),
    KEY `idx_financial_periods_dates` (`start_date`,`end_date`),
    CONSTRAINT `financial_periods_linked_income_id_foreign` FOREIGN KEY (`linked_income_id`) REFERENCES `transactions` (`id`) ON DELETE SET NULL,
    CONSTRAINT `chk_dates` CHECK (`end_date` > `start_date`),
    CONSTRAINT `chk_days` CHECK (`total_days` > 0),
    CONSTRAINT `chk_budget` CHECK (`daily_budget` is null or `daily_budget` >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. users
CREATE TABLE IF NOT EXISTS `users` (
    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
    `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
    `email_verified_at` timestamp NULL DEFAULT NULL,
    `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
    `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
