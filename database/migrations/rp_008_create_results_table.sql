/*
===============================================================================
 Dosya: rp_008_create_results_table.sql
 Amaç : Her SINAV OTURUMU (exam_sessions) için tekil bir "sonuç" kaydı tutmak.
        Doğru/yanlış/boş sayıları, ham puan, yüzde ve geçti/kaldı bilgisi burada.
 Önkoşul:
   - exams(id), students(id), users(id), exam_sessions(id) tabloları mevcut olmalı.
 Notlar:
   - session_id üzerine UNIQUE indeks: 1 oturum = 1 sonuç kuralı garanti.
   - exam_id ve student_id raporlamayı hızlandırmak için ayrıca tutuluyor.
   - BIGINT UNSIGNED FK’ler önceki tablolarla birebir uyumlu.
===============================================================================
*/

USE `shgm_exam_system`;

CREATE TABLE IF NOT EXISTS `results` (
  `id`               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'PK',
  `session_id`       BIGINT UNSIGNED NOT NULL COMMENT 'FK -> exam_sessions.id (tekil sonuç)',
  `exam_id`          BIGINT UNSIGNED NOT NULL COMMENT 'FK -> exams.id (rapor kolaylığı)',
  `student_id`       BIGINT UNSIGNED NOT NULL COMMENT 'FK -> students.id (rapor kolaylığı)',

  `total_questions`  INT UNSIGNED   NOT NULL DEFAULT 0  COMMENT 'Sınavdaki toplam soru',
  `answered_count`   INT UNSIGNED   NOT NULL DEFAULT 0  COMMENT 'Cevaplanan soru sayısı',
  `correct_count`    INT UNSIGNED   NOT NULL DEFAULT 0  COMMENT 'Doğru cevap sayısı',
  `wrong_count`      INT UNSIGNED   NOT NULL DEFAULT 0  COMMENT 'Yanlış cevap sayısı',
  `blank_count`      INT UNSIGNED   NOT NULL DEFAULT 0  COMMENT 'Boş bırakılan soru sayısı',

  `raw_score`        DECIMAL(7,3)   NOT NULL DEFAULT 0.000 COMMENT 'Ham puan',
  `percentage`       DECIMAL(5,2)   NOT NULL DEFAULT 0.00  COMMENT 'Yüzde (0-100)',
  `passed`           TINYINT(1)     NOT NULL DEFAULT 0     COMMENT '0=Kaldı, 1=Geçti',

  `grading_mode`     ENUM('auto','manual','mixed') NOT NULL DEFAULT 'auto' COMMENT 'Notlama türü',
  `graded_by`        BIGINT UNSIGNED NULL COMMENT 'FK -> users.id (manuel notlayan)',
  `graded_at`        DATETIME NULL COMMENT 'Manuel notlama zamanı',
  `submitted_at`     DATETIME NOT NULL COMMENT 'Öğrencinin sınavı gönderdiği an',

  `created_at`       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),

  -- 1 oturuma sadece 1 sonuç
  UNIQUE KEY `uk_results_session` (`session_id`),

  -- Rapor filtreleri ve JOIN performansı için yardımcı indeksler
  KEY `idx_results_exam` (`exam_id`),
  KEY `idx_results_student` (`student_id`),
  KEY `idx_results_passed` (`passed`),

  -- Yabancı anahtarlar (InnoDB)
  CONSTRAINT `fk_results_session`
    FOREIGN KEY (`session_id`) REFERENCES `exam_sessions` (`id`)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT `fk_results_exam`
    FOREIGN KEY (`exam_id`)    REFERENCES `exams` (`id`)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT `fk_results_student`
    FOREIGN KEY (`student_id`) REFERENCES `students` (`id`)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT `fk_results_graded_by`
    FOREIGN KEY (`graded_by`)  REFERENCES `users` (`id`)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
