CREATE DATABASE IF NOT EXISTS db_ufinance
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE db_ufinance;

SET NAMES utf8mb4;

-- ----------------------------------------------------------------------------
-- 1. TABEL APLIKASI
-- ----------------------------------------------------------------------------

CREATE TABLE categories (
  id   BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(100)    NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY categories_name_unique (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE transactions (
  id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  type        ENUM('income','expense') NOT NULL,
  amount      DECIMAL(15,2)   NOT NULL,
  `date`      DATE            NOT NULL,
  category_id BIGINT UNSIGNED NULL,
  note        TEXT            NULL,
  created_at  TIMESTAMP       NULL,
  updated_at  TIMESTAMP       NULL,
  PRIMARY KEY (id),
  KEY idx_transactions_date (`date`),
  KEY idx_transactions_type_date (type, `date`),
  CONSTRAINT transactions_category_id_foreign
    FOREIGN KEY (category_id) REFERENCES categories (id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT chk_transactions_amount CHECK (amount >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE goals (
  id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  name           VARCHAR(150)    NOT NULL,
  target_amount  DECIMAL(15,2)   NOT NULL,
  current_amount DECIMAL(15,2)   NOT NULL DEFAULT 0,
  deadline       DATE            NULL,
  status         ENUM('active','achieved') NOT NULL DEFAULT 'active',
  created_at     TIMESTAMP       NULL,
  updated_at     TIMESTAMP       NULL,
  PRIMARY KEY (id),
  KEY idx_goals_status_deadline (status, deadline),
  CONSTRAINT chk_goals_target  CHECK (target_amount  >= 0),
  CONSTRAINT chk_goals_current CHECK (current_amount >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE recurring_transactions (
  id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  type          ENUM('income','expense') NOT NULL DEFAULT 'expense',
  amount        DECIMAL(15,2)   NOT NULL,
  interval_days INT UNSIGNED    NOT NULL,
  start_date    DATE            NOT NULL,
  category_id   BIGINT UNSIGNED NULL,
  note          TEXT            NULL,
  PRIMARY KEY (id),
  KEY idx_recurring_start_date (start_date),
  CONSTRAINT recurring_transactions_category_id_foreign
    FOREIGN KEY (category_id) REFERENCES categories (id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT chk_recurring_amount   CHECK (amount >= 0),
  CONSTRAINT chk_recurring_interval CHECK (interval_days > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE financial_periods (
  id               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  start_date       DATE            NOT NULL,
  end_date         DATE            NOT NULL,
  total_days       INT UNSIGNED    NOT NULL,
  budget_mode      ENUM('auto','manual') NOT NULL DEFAULT 'auto',
  daily_budget     DECIMAL(15,2)   NULL,
  linked_income_id BIGINT UNSIGNED NULL,
  is_active        TINYINT(1)      NOT NULL DEFAULT 1,
  created_at       TIMESTAMP       NULL,
  updated_at       TIMESTAMP       NULL,
  active_flag      TINYINT UNSIGNED
    GENERATED ALWAYS AS (CASE WHEN is_active = 1 THEN 1 ELSE NULL END) VIRTUAL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_financial_periods_one_active (active_flag),
  KEY idx_financial_periods_active (is_active),
  KEY idx_financial_periods_dates (start_date, end_date),
  CONSTRAINT financial_periods_linked_income_id_foreign
    FOREIGN KEY (linked_income_id) REFERENCES transactions (id)
    ON DELETE SET NULL,
  CONSTRAINT chk_dates  CHECK (end_date > start_date),
  CONSTRAINT chk_days   CHECK (total_days > 0),
  CONSTRAINT chk_budget CHECK (daily_budget IS NULL OR daily_budget >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE users (
  id                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  name              VARCHAR(255) NOT NULL,
  email             VARCHAR(255) NOT NULL,
  email_verified_at TIMESTAMP NULL,
  password          VARCHAR(255) NOT NULL,
  remember_token    VARCHAR(100) NULL,
  created_at        TIMESTAMP NULL,
  updated_at        TIMESTAMP NULL,
  PRIMARY KEY (id),
  UNIQUE KEY users_email_unique (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE password_reset_tokens (
  email      VARCHAR(255) NOT NULL,
  token      VARCHAR(255) NOT NULL,
  created_at TIMESTAMP NULL,
  PRIMARY KEY (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE sessions (
  id            VARCHAR(255) NOT NULL,
  user_id       BIGINT UNSIGNED NULL,
  ip_address    VARCHAR(45) NULL,
  user_agent    TEXT NULL,
  payload       LONGTEXT NOT NULL,
  last_activity INT NOT NULL,
  PRIMARY KEY (id),
  KEY sessions_user_id_index (user_id),
  KEY sessions_last_activity_index (last_activity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE cache (
  `key`      VARCHAR(255) NOT NULL,
  value      MEDIUMTEXT   NOT NULL,
  expiration BIGINT       NOT NULL,
  PRIMARY KEY (`key`),
  KEY cache_expiration_index (expiration)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE cache_locks (
  `key`      VARCHAR(255) NOT NULL,
  owner      VARCHAR(255) NOT NULL,
  expiration BIGINT       NOT NULL,
  PRIMARY KEY (`key`),
  KEY cache_locks_expiration_index (expiration)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE jobs (
  id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  queue        VARCHAR(255) NOT NULL,
  payload      LONGTEXT NOT NULL,
  attempts     SMALLINT UNSIGNED NOT NULL,
  reserved_at  INT UNSIGNED NULL,
  available_at INT UNSIGNED NOT NULL,
  created_at   INT UNSIGNED NOT NULL,
  PRIMARY KEY (id),
  KEY jobs_queue_index (queue)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE job_batches (
  id             VARCHAR(255) NOT NULL,
  name           VARCHAR(255) NOT NULL,
  total_jobs     INT NOT NULL,
  pending_jobs   INT NOT NULL,
  failed_jobs    INT NOT NULL,
  failed_job_ids LONGTEXT NOT NULL,
  options        MEDIUMTEXT NULL,
  cancelled_at   INT NULL,
  created_at     INT NOT NULL,
  finished_at    INT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE failed_jobs (
  id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  uuid       VARCHAR(255) NOT NULL,
  connection TEXT NOT NULL,
  queue      TEXT NOT NULL,
  payload    LONGTEXT NOT NULL,
  exception  LONGTEXT NOT NULL,
  failed_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY failed_jobs_uuid_unique (uuid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE migrations (
  id        INT UNSIGNED NOT NULL AUTO_INCREMENT,
  migration VARCHAR(255) NOT NULL,
  batch     INT NOT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO migrations (migration, batch) VALUES
  ('0001_01_01_000000_create_users_table', 1),
  ('0001_01_01_000001_create_cache_table', 1),
  ('0001_01_01_000002_create_jobs_table', 1),
  ('2026_10_05_000001_create_categories_table', 1),
  ('2026_10_05_000002_create_transactions_table', 1),
  ('2026_10_05_000003_create_goals_table', 1),
  ('2026_10_05_000004_create_recurring_transactions_table', 1),
  ('2026_10_05_000005_create_financial_periods_table', 1);

INSERT IGNORE INTO categories (name) VALUES
  ('Salary'), ('Food'), ('Transport'), ('Bills'), ('Shopping'),
  ('Health'), ('Entertainment'), ('Savings'), ('Other');
