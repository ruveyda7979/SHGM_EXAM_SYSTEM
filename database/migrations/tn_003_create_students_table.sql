-- SHGM Exam System — students
USE `shgm_exam_system`;

CREATE TABLE IF NOT EXISTS `students` (
  `id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`         BIGINT UNSIGNED NULL,          -- users.id ile bağ
  `tc_kimlik`       CHAR(11)        NULL,          -- TN_TC_PATTERN'e uygunluk uygulama tarafında doğrulanacak
  `student_no`      VARCHAR(32)     NULL,
  `phone`           VARCHAR(20)     NULL,
  `status`          TINYINT         NOT NULL DEFAULT 1,  -- öğrenci profil durumu
  `last_login_at`   DATETIME        NULL,
  `created_at`      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_students_user` (`user_id`),
  UNIQUE KEY `uq_students_tc` (`tc_kimlik`),
  CONSTRAINT `fk_students_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON UPDATE CASCADE
    ON DELETE SET NULL
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;
