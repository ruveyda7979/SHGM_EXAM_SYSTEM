-- ------------------------------------------------------------------
-- SHGM Exam System – Exam Answers (Verilen Cevaplar)
-- Amaç: Bir oturumda her soru için verilen cevabı saklar
-- Bağımlılıklar: exam_sessions, questions
-- ------------------------------------------------------------------

USE `shgm_exam_system`;

CREATE TABLE IF NOT EXISTS `exam_answers` (
  `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `session_id`   BIGINT UNSIGNED NOT NULL,
  `question_id`  BIGINT UNSIGNED NOT NULL,
  `answer_text`  TEXT NULL,            -- metin veya JSON (uygulama karar verir)
  `is_correct`   TINYINT(1) NULL,      -- null: henüz değerlendirilmedi
  `score`        DECIMAL(5,2) NULL,    -- puan (otomatik/manuel)
  `answered_at`  DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ans_session`  (`session_id`),
  KEY `idx_ans_question` (`question_id`),
  CONSTRAINT `fk_ans_session`
    FOREIGN KEY (`session_id`)  REFERENCES `exam_sessions` (`id`)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT `fk_ans_question`
    FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

