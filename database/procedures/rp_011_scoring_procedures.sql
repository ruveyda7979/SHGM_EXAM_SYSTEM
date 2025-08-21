/*
===============================================================================
 Dosya : rp_011_scoring_procedures.sql
 Amaç  : Oturum (exam_sessions) puanlama/sonuç üretme prosedürü.
 Şema  : Aşağıdaki kolon varsayımlarıyla çalışır:
   - questions(id, exam_id, type ENUM('single_choice','true_false','multi_choice'),
               correct_answer_json JSON, points DECIMAL)
   - exam_answers(session_id, question_id, answer_json JSON,
                  is_correct TINYINT, awarded_score DECIMAL)
   - exam_sessions(id, exam_id, student_id, submitted_at DATETIME NULL)
   - results(session_id UNIQUE, exam_id, student_id, ... , percentage, passed)
 Not   : Çoktan seçmeli çoklu cevapta, JSON dizilerinin içeriği/sayısı aynıysa doğru
         kabul edilir (sıra önemsiz).
===============================================================================
*/

USE `shgm_exam_system`;
DELIMITER $$

DROP PROCEDURE IF EXISTS `rp_score_session` $$
CREATE PROCEDURE `rp_score_session`(
    IN p_session_id BIGINT UNSIGNED,
    IN p_pass_threshold DECIMAL(5,2) -- yüzde, örn: 70.00
)
BEGIN
    DECLARE v_exam_id   BIGINT UNSIGNED;
    DECLARE v_student_id BIGINT UNSIGNED;
    DECLARE v_total_q   INT UNSIGNED DEFAULT 0;
    DECLARE v_answered  INT UNSIGNED DEFAULT 0;
    DECLARE v_correct   INT UNSIGNED DEFAULT 0;
    DECLARE v_wrong     INT UNSIGNED DEFAULT 0;
    DECLARE v_blank     INT UNSIGNED DEFAULT 0;
    DECLARE v_total_pts DECIMAL(10,3) DEFAULT 0.000;
    DECLARE v_raw       DECIMAL(10,3) DEFAULT 0.000;
    DECLARE v_pct       DECIMAL(5,2)  DEFAULT 0.00;
    DECLARE v_submitted DATETIME;

    -- Oturumun temel bilgileri
    SELECT es.exam_id, es.student_id,
           COALESCE(es.submitted_at, NOW())
      INTO v_exam_id, v_student_id, v_submitted
      FROM exam_sessions es
     WHERE es.id = p_session_id;

    -- Cevapları doğruluk durumuna göre güncelle (is_correct & awarded_score)
    UPDATE exam_answers a
    JOIN questions q ON q.id = a.question_id
       SET a.is_correct =
           CASE q.`type`
             WHEN 'single_choice' THEN
               JSON_UNQUOTE(JSON_EXTRACT(a.answer_json, '$')) =
               JSON_UNQUOTE(JSON_EXTRACT(q.correct_answer_json, '$'))
             WHEN 'true_false' THEN
               JSON_UNQUOTE(JSON_EXTRACT(a.answer_json, '$')) =
               JSON_UNQUOTE(JSON_EXTRACT(q.correct_answer_json, '$'))
             WHEN 'multi_choice' THEN
               (JSON_LENGTH(a.answer_json) = JSON_LENGTH(q.correct_answer_json)
                AND JSON_CONTAINS(a.answer_json, q.correct_answer_json)
                AND JSON_CONTAINS(q.correct_answer_json, a.answer_json))
             ELSE 0
           END,
           a.awarded_score =
           CASE
             WHEN
               CASE q.`type`
                 WHEN 'single_choice' THEN
                   JSON_UNQUOTE(JSON_EXTRACT(a.answer_json, '$')) =
                   JSON_UNQUOTE(JSON_EXTRACT(q.correct_answer_json, '$'))
                 WHEN 'true_false' THEN
                   JSON_UNQUOTE(JSON_EXTRACT(a.answer_json, '$')) =
                   JSON_UNQUOTE(JSON_EXTRACT(q.correct_answer_json, '$'))
                 WHEN 'multi_choice' THEN
                   (JSON_LENGTH(a.answer_json) = JSON_LENGTH(q.correct_answer_json)
                    AND JSON_CONTAINS(a.answer_json, q.correct_answer_json)
                    AND JSON_CONTAINS(q.correct_answer_json, a.answer_json))
                 ELSE 0
               END = 1
             THEN q.points ELSE 0 END
     WHERE a.session_id = p_session_id;

    -- Sınav toplam soru ve toplam puan
    SELECT COUNT(*), COALESCE(SUM(points), 0)
      INTO v_total_q, v_total_pts
      FROM questions
     WHERE exam_id = v_exam_id;

    -- Oturumdaki dağılımlar
    SELECT
      SUM(CASE WHEN a.answer_json IS NOT NULL THEN 1 ELSE 0 END) AS answered_count,
      SUM(CASE WHEN a.is_correct = 1 THEN 1 ELSE 0 END)          AS correct_count,
      SUM(CASE WHEN a.is_correct = 0 AND a.answer_json IS NOT NULL THEN 1 ELSE 0 END) AS wrong_count,
      SUM(CASE WHEN a.answer_json IS NULL THEN 1 ELSE 0 END)     AS blank_count,
      COALESCE(SUM(a.awarded_score),0)                            AS raw_sum
      INTO v_answered, v_correct, v_wrong, v_blank, v_raw
      FROM exam_answers a
     WHERE a.session_id = p_session_id;

    SET v_pct = CASE WHEN v_total_pts > 0
                     THEN ROUND((v_raw / v_total_pts) * 100, 2)
                     ELSE 0.00 END;

    -- Sonuç kaydını ekle/güncelle (session_id benzersiz)
    INSERT INTO results
      (session_id, exam_id, student_id,
       total_questions, answered_count, correct_count, wrong_count, blank_count,
       raw_score, percentage, passed, grading_mode, submitted_at)
    VALUES
      (p_session_id, v_exam_id, v_student_id,
       v_total_q, v_answered, v_correct, v_wrong, v_blank,
       v_raw, v_pct, (v_pct >= p_pass_threshold), 'auto', v_submitted)
    ON DUPLICATE KEY UPDATE
       exam_id         = VALUES(exam_id),
       student_id      = VALUES(student_id),
       total_questions = VALUES(total_questions),
       answered_count  = VALUES(answered_count),
       correct_count   = VALUES(correct_count),
       wrong_count     = VALUES(wrong_count),
       blank_count     = VALUES(blank_count),
       raw_score       = VALUES(raw_score),
       percentage      = VALUES(percentage),
       passed          = VALUES(passed),
       submitted_at    = VALUES(submitted_at),
       grading_mode    = 'auto',
       updated_at      = CURRENT_TIMESTAMP;
END $$
DELIMITER ;
