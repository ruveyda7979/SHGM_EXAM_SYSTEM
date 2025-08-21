-- ------------------------------------------------------------------
-- SHGM Exam System – Questions (Sorular) tablosu
-- Amaç: Her sınavın sorularını ve şıklarını tutar
-- Bağımlılık: exams (exam_id -> exams.id)
-- ------------------------------------------------------------------

USE `shgm_exam_system`;

CREATE TABLE IF NOT EXISTS `questions` (
  `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `exam_id`      BIGINT UNSIGNED NOT NULL,
  `type`         ENUM('single','multiple','truefalse','text') NOT NULL DEFAULT 'single',
  `text`         TEXT NOT NULL,
  `choices_json` LONGTEXT NULL COMMENT 'Seçenekler (JSON string)',
  `correct_json` LONGTEXT NULL COMMENT 'Doğru cevap(lar) (JSON string)',
  `points`       DECIMAL(5,2) NOT NULL DEFAULT 1.00,
  `order_no`     INT UNSIGNED NOT NULL DEFAULT 1,
  `status`       ENUM('active','passive') NOT NULL DEFAULT 'active',
  `created_by`   BIGINT UNSIGNED NOT NULL,  -- users.id
  `created_at`   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_q_exam` (`exam_id`),
  KEY `idx_q_order` (`exam_id`,`order_no`),
  CONSTRAINT `fk_q_exam`
    FOREIGN KEY (`exam_id`) REFERENCES `exams` (`id`)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT `fk_q_created_by`
    FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
