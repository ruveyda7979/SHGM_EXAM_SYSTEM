/*
===============================================================================
 Dosya : tn_010_create_indexes.sql
 Amaç  : Sorgu performansını artıracak ek indeksler/benzersiz anahtarlar.
 Not   : MySQL 8.0+ ile uyumlu. IF NOT EXISTS desteklidir.
        Daha önce aynı isimde indeks varsa, bu dosya tekrarlı oluşturmaz.
===============================================================================
*/

USE `shgm_exam_system`;

-- Sınav kodu tekil olsun (kataloglama ve URL’lerde kullanışlı)
CREATE UNIQUE INDEX IF NOT EXISTS `uk_exams_code`
  ON `exams` (`code`);

-- Oturumlarda aynı öğrencinin aynı sınava ait sorguları hızlandırır
CREATE INDEX IF NOT EXISTS `idx_es_exam_student`
  ON `exam_sessions` (`exam_id`, `student_id`);

-- Oturum durumuna göre listeleme (rapor/monitoring) için
CREATE INDEX IF NOT EXISTS `idx_es_exam_status`
  ON `exam_sessions` (`exam_id`, `status`);

-- Bir oturum içindeki soru cevaplarını hızlı çeker
CREATE INDEX IF NOT EXISTS `idx_ea_session_question`
  ON `exam_answers` (`session_id`, `question_id`);

-- Sonuçlarda rapor filtreleri (sınav + öğrenci birlikte)
CREATE INDEX IF NOT EXISTS `idx_results_exam_student`
  ON `results` (`exam_id`, `student_id`);
