-- SHGM Exam System — users
USE `shgm_exam_system`;

CREATE TABLE IF NOT EXISTS `users` (
  `id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`            VARCHAR(120)    NOT NULL,
  `email`           VARCHAR(255)    NOT NULL,
  `password`        VARCHAR(255)    NOT NULL,
  `role`            VARCHAR(32)     NOT NULL DEFAULT 'student',  -- TN_ROLE_* değerleri
  `status`          TINYINT         NOT NULL DEFAULT 1,          -- 1=active, 0=inactive, -1=suspended, 2=pending
  `last_login_at`   DATETIME        NULL,
  `created_at`      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_email` (`email`),
  KEY `idx_users_role` (`role`),
  KEY `idx_users_status` (`status`)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;
