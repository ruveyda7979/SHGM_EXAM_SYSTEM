-- SHGM Exam System - Örnek soru verileri (questions)
USE `shgm_exam_system`;

-- Güvenlik: Değişkenler dolu mu?
SET @admin_id := IFNULL(@admin_id, (SELECT id FROM users ORDER BY id LIMIT 1));
SET @exam_id  := IFNULL(@exam_id,  (SELECT id FROM exams ORDER BY id LIMIT 1));

-- Tek şıklı soru
INSERT IGNORE INTO `questions`
(`exam_id`, `type`,  `text`,                                                `choices_json`,                       `correct_json`, `points`, `status`, `order_no`, `created_by`)
VALUES
(@exam_id, 'single', 'Yer çekimi ivmesi yaklaşık kaç m/s²''dir?',           '["8.81","9.81","10.81","11.81"]',    '["9.81"]',      1,       'active', 1,          @admin_id),

-- Tek şıklı soru
(@exam_id, 'single', 'Standart atmosfer basıncı yaklaşık kaç hPa''dır?',    '["950","965","1013","1030"]',        '["1013"]',      1,       'active', 2,          @admin_id),

-- Doğru/Yanlış
(@exam_id, 'truefalse', 'Deniz seviyesinde hava yoğunluğu yükseklikle azalır.', '["Doğru","Yanlış"]',           '["Doğru"]',      1,       'active', 3,          @admin_id),

-- Çoktan seçmeli (birden fazla doğru)
(@exam_id, 'multiple', 'Aşağıdakilerden hangileri temel uçuş kumandalarıdır?', '["Aileron","Flap","Rudder","Elevator"]', '["Aileron","Rudder","Elevator"]', 2, 'active', 4, @admin_id),

-- Açık uçlu
(@exam_id, 'text', 'ISA koşullarında deniz seviyesinde sıcaklık kaç °C''dir?', NULL, '["15"]', 1, 'active', 5, @admin_id);
