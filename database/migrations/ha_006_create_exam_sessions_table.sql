-- ------------------------------------------------------------------
-- SHGM Exam System – Exam Sessions (Sınav Oturumları)
-- Amaç: Bir öğrencinin bir sınavdaki tek denemesini temsil eder
-- Bağımlılıklar: exams, students
-- ------------------------------------------------------------------

USE `shgm_exam_system`;

CREATE TABLE IF NOT EXISTS `exam_sessions` (
  `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `exam_id`      BIGINT UNSIGNED NOT NULL,
  `student_id`   BIGINT UNSIGNED NOT NULL,
  `token`        CHAR(36) NOT NULL,  -- UUID
  `status`       ENUM('active','submitted','timeout','cancelled') NOT NULL DEFAULT 'active',
  `started_at`   DATETIME NOT NULL,
  `finished_at`  DATETIME NULL,
  `remote_ip`    VARCHAR(45) NULL,
  `user_agent`   VARCHAR(255) NULL,
  `created_at`   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_session_token` (`token`),
  KEY `idx_session_exam`    (`exam_id`),
  KEY `idx_session_student` (`student_id`),
  CONSTRAINT `fk_session_exam`
    FOREIGN KEY (`exam_id`)   REFERENCES `exams`    (`id`)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT `fk_session_student`
    FOREIGN KEY (`student_id`) REFERENCES `students` (`id`)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

