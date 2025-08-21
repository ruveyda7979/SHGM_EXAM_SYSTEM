/*
===============================================================================
 Dosya: ha_009_create_recordings_table.sql
 Amaç : Sınav oturumu sırasında kaydedilen VİDEO/SES/EKRAN kayıtlarının
        metadatasını tutmak (dosya konumu, süre, boyut, durum vb.)
 Önkoşul:
   - exam_sessions(id) tablosu mevcut olmalı.
 Notlar:
   - Uygulamanın .env içinde RECORDING_PATH ile dosyalar nereye yazılacaksa
     `storage_path` bunu işaret eder. Silme/temizleme işlemleri bu tabloya bakabilir.
===============================================================================
*/

USE `shgm_exam_system`;

CREATE TABLE IF NOT EXISTS `recordings` (
  `id`               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'PK',
  `session_id`       BIGINT UNSIGNED NOT NULL COMMENT 'FK -> exam_sessions.id',

  `storage_path`     VARCHAR(500) NOT NULL COMMENT 'Sunucudaki tam dosya yolu (ör: storage/recordings/...)',
  `file_name`        VARCHAR(255) NULL COMMENT 'Sadece dosya adı',
  `mime_type`        VARCHAR(100) NULL COMMENT 'Örn: video/webm',
  `format`           VARCHAR(16)  NOT NULL DEFAULT 'webm' COMMENT 'Kayıt formatı',
  `quality`          ENUM('low','medium','high') NOT NULL DEFAULT 'high' COMMENT 'Kayıt kalitesi',
  `duration_seconds` INT UNSIGNED NULL COMMENT 'Saniye cinsinden süre',
  `size_bytes`       BIGINT UNSIGNED NULL COMMENT 'Bayt cinsinden boyut',
  `integrity_hash`   VARCHAR(128) NULL COMMENT 'Örn: SHA-256', 

  `status`           ENUM('pending','uploaded','processing','ready','failed','deleted')
                     NOT NULL DEFAULT 'uploaded' COMMENT 'Kayıt iş akışı durumu',
  `notes`            TEXT NULL COMMENT 'İdari notlar / uyarılar',

  `started_at`       DATETIME NULL COMMENT 'Kayıt başlangıç anı',
  `ended_at`         DATETIME NULL COMMENT 'Kayıt bitiş anı',
  `uploaded_at`      DATETIME NULL COMMENT 'Sunucuya yüklendiği an',

  `created_at`       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),

  -- JOIN ve filtre performansı için indeksler
  KEY `idx_rec_session` (`session_id`),
  KEY `idx_rec_status`  (`status`),

  CONSTRAINT `fk_rec_session`
    FOREIGN KEY (`session_id`) REFERENCES `exam_sessions` (`id`)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
