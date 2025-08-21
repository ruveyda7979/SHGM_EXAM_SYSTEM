-- ------------------------------------------------------------------
-- SHGM Exam System – Exams (Sınavlar) tablosu
-- Amaç: Bir sınavın meta bilgisini tutar (başlık, süre, geçme puanı, vb.)
-- Bağımlılık: users (created_by -> users.id)
-- ------------------------------------------------------------------

USE `shgm_exam_system`;

CREATE TABLE IF NOT EXISTS `exams` (
  `id`                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'PK',
  `code`              VARCHAR(32)  NOT NULL COMMENT 'Kısa kod (örn: PPL-2025-01)',
  `title`             VARCHAR(255) NOT NULL COMMENT 'Sınav başlığı',
  `description`       TEXT NULL COMMENT 'Açıklama / yönergeler',
  `duration_minutes`  SMALLINT UNSIGNED NOT NULL DEFAULT 60 COMMENT 'Sınav süresi (dk)',
  `attempt_limit`     TINYINT  UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Maks. deneme',
  `pass_score`        DECIMAL(5,2) NOT NULL DEFAULT 70.00 COMMENT 'Geçme notu',
  `scoring_mode`      ENUM('points','percentage') NOT NULL DEFAULT 'points',
  `shuffle_questions` TINYINT(1) NOT NULL DEFAULT 1,
  `shuffle_answers`   TINYINT(1) NOT NULL DEFAULT 1,
  `status`            ENUM('draft','published','archived') NOT NULL DEFAULT 'draft',
  `start_at`          DATETIME NULL,
  `end_at`            DATETIME NULL,
  `created_by`        BIGINT UNSIGNED NOT NULL COMMENT 'users.id',
  `created_at`        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_exams_code` (`code`),
  KEY `idx_exams_status` (`status`),
  KEY `idx_exams_window` (`start_at`,`end_at`),
  KEY `idx_exams_creator` (`created_by`),
  CONSTRAINT `fk_exams_created_by_users`
    FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

