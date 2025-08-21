-- SHGM Exam System - Admin kullanıcı tohum verileri
USE `shgm_exam_system`;

-- Aynı dosyayı tekrar çalıştırırsan şema bozulmasın diye
-- mevcut e-postaya çakışırsa sadece ad/rol/durum güncellenir (şifreye dokunmuyoruz).
INSERT INTO `users` (`name`, `email`, `password`, `role`, `status`)
VALUES
  ('Sistem Yöneticisi', 'admin@shgm.gov.tr', SHA2('Admin!123', 256), 'admin', 1),
  ('Gözetmen',         'invigilator@shgm.gov.tr', SHA2('Invigilator!123', 256), 'invigilator', 1)
ON DUPLICATE KEY UPDATE
  `name`  = VALUES(`name`),
  `role`  = VALUES(`role`),
  `status`= VALUES(`status`);
