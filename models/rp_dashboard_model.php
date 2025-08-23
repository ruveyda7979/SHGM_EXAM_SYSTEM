<?php
/**
 * Dashboard Model
 * - Amaç: Panelde gösterilecek hızlı istatistikleri ve son kayıtları sağlamak.
 * - Esnek: created_at / status kolonları yoksa id üzerinden sıralama ve koşullar ile
 *   güvenli şekilde fallback yapar.
 */

class RP_DashboardModel
{
    /** @var PDO */
    private PDO $db;

    public function __construct()
    {
        // tn_db() helper’ı varsa onu kullan; yoksa TN_Database::getInstance()
        $this->db = function_exists('tn_db') ? tn_db() : TN_Database::getInstance();
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    }

    /** Bilgi şemasında kolon var mı? (created_at gibi) */
    private function hasColumn(string $table, string $column): bool
    {
        $sql = "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t AND COLUMN_NAME = :c";
        $st  = $this->db->prepare($sql);
        $st->execute([':t' => $table, ':c' => $column]);
        return (int)$st->fetchColumn() > 0;
    }

    /** Panel kutucukları: toplam sayılar */
    public function getSummaryCounts(): array
    {
        $counts = [];

        $counts['total_users']     = (int)$this->db->query("SELECT COUNT(*) FROM `users`")->fetchColumn();
        $counts['total_students']  = (int)$this->db->query("SELECT COUNT(*) FROM `students`")->fetchColumn();
        $counts['total_exams']     = (int)$this->db->query("SELECT COUNT(*) FROM `exams`")->fetchColumn();
        $counts['total_questions'] = (int)$this->db->query("SELECT COUNT(*) FROM `questions`")->fetchColumn();

        // “aktif sınav” varsa (status string/numeric olabilir) kabaca filtrele
        if ($this->hasColumn('exams', 'status')) {
            $sql = "SELECT COUNT(*) FROM `exams`
                    WHERE status IN ('published','active',1)";
            $counts['active_exams'] = (int)$this->db->query($sql)->fetchColumn();
        } else {
            $counts['active_exams'] = 0;
        }

        return $counts;
    }

    /** Son sınavlar (5 adet) */
    public function getRecentExams(int $limit = 5): array
    {
        $orderCol = $this->hasColumn('exams', 'created_at') ? 'created_at' : 'id';
        $sql = "SELECT id, code, " .
               ($this->hasColumn('exams','title') ? "title" : "code AS title") . ",
                      " . ($this->hasColumn('exams','status') ? "status" : "'-' AS status") . ",
                      {$orderCol} AS created_display
                FROM `exams`
                ORDER BY {$orderCol} DESC
                LIMIT :lim";
        $st = $this->db->prepare($sql);
        $st->bindValue(':lim', $limit, PDO::PARAM_INT);
        $st->execute();
        return $st->fetchAll();
    }

    /** Son öğrenciler (5 adet) */
    public function getRecentStudents(int $limit = 5): array
    {
        $orderCol = $this->hasColumn('students', 'created_at') ? 'created_at' : 'id';
        $selName  = $this->hasColumn('students','student_no')
                    ? "student_no"
                    : ($this->hasColumn('students','tc_kimlik') ? "tc_kimlik" : "id");
        $sql = "SELECT id, {$selName} AS display_no,
                       " . ($this->hasColumn('students','status') ? "status" : "'-' AS status") . ",
                       {$orderCol} AS created_display
                FROM `students`
                ORDER BY {$orderCol} DESC
                LIMIT :lim";
        $st = $this->db->prepare($sql);
        $st->bindValue(':lim', $limit, PDO::PARAM_INT);
        $st->execute();
        return $st->fetchAll();
    }
}
