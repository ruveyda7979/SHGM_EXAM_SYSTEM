/*
===============================================================================
 Dosya : ha_012_exam_procedures.sql
 Amaç  : Sınav oturumu yaşam döngüsü prosedürleri (başlat, cevap kaydet, bitir).
 Şema  : exam_sessions(status ENUM('in_progress','submitted','cancelled','timeout'))
         exam_answers(answer_json JSON, is_correct, awarded_score)
         rp_score_session() prosedürü mevcut olmalı (puanlama çağrısı için).
===============================================================================
*/

USE `shgm_exam_system`;
DELIMITER $$

-- 1) Oturum başlat: aynı öğrenci/sınav için açık oturum yoksa oluşturur
DROP PROCEDURE IF EXISTS `ha_start_session` $$
CREATE PROCEDURE `ha_start_session`(
    IN  p_exam_id    BIGINT UNSIGNED,
    IN  p_student_id BIGINT UNSIGNED,
    OUT p_session_id BIGINT UNSIGNED
)
BEGIN
    DECLARE v_existing BIGINT UNSIGNED;

    SELECT id INTO v_existing
      FROM exam_sessions
     WHERE exam_id = p_exam_id
       AND student_id = p_student_id
       AND status = 'in_progress'
     LIMIT 1;

    IF v_existing IS NOT NULL THEN
        SET p_session_id = v_existing;
    ELSE
        INSERT INTO exam_sessions (exam_id, student_id, status, started_at)
        VALUES (p_exam_id, p_student_id, 'in_progress', NOW());
        SET p_session_id = LAST_INSERT_ID();
    END IF;
END $$


-- 2) Cevap kaydet / güncelle (idempotent)
DROP PROCEDURE IF EXISTS `ha_upsert_answer` $$
CREATE PROCEDURE `ha_upsert_answer`(
    IN p_session_id  BIGINT UNSIGNED,
    IN p_question_id BIGINT UNSIGNED,
    IN p_answer_json JSON
)
BEGIN
    INSERT INTO exam_answers (session_id, question_id, answer_json, answered_at)
    VALUES (p_session_id, p_question_id, p_answer_json, NOW())
    ON DUPLICATE KEY UPDATE
        answer_json = VALUES(answer_json),
        answered_at = NOW();
END $$


-- 3) Oturumu bitir + otomatik puanlama
DROP PROCEDURE IF EXISTS `ha_finish_session` $$
CREATE PROCEDURE `ha_finish_session`(
    IN p_session_id BIGINT UNSIGNED,
    IN p_pass_threshold DECIMAL(5,2)  -- yüzde, örn: 70.00
)
BEGIN
    -- Oturum durumunu gönderildi yap
    UPDATE exam_sessions
       SET status = 'submitted',
           submitted_at = COALESCE(submitted_at, NOW())
     WHERE id = p_session_id;

    -- Puanlama (results tablosu güncellenir/oluşturulur)
    CALL rp_score_session(p_session_id, p_pass_threshold);
END $$

DELIMITER ;
