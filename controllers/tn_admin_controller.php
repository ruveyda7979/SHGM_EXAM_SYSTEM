<?php
/**
 * Admin Dashboard Controller
 * - Amaç: Admin ana sayfasını göstermek.
 * - Güvenlik: constructor’da admin yetkisi zorunlu (requirePermission).
 * - Veri: RP_DashboardModel ile sayım/özet ve son kayıtları çeker.
 */

class TN_AdminController extends TN_Controller
{
    public function __construct()
    {
        parent::__construct();

        // Admin paneline giriş için oturum + yetki şart
        // (TN_Controller içindeki izin haritasında 'admin.dashboard' zaten var)
        $this->requirePermission('admin.dashboard');
    }

    /** /admin -> /admin/dashboard yönlendirme istersen */
    public function index()
    {
        return $this->dashboard();
    }

    /** Ana panel */
    public function dashboard()
    {
        require_once __DIR__ . '/../models/rp_dashboard_model.php';
        $m = new RP_DashboardModel();

        // Özet metrikler + son listeler
        $summary       = $m->getSummaryCounts();
        $recentExams   = $m->getRecentExams(5);
        $recentStudents= $m->getRecentStudents(5);

        $this->render('admin/tn_dashboard', [
            'title'           => 'Yönetim Paneli',
            'summary'         => $summary,
            'recent_exams'    => $recentExams,
            'recent_students' => $recentStudents,
        ]);
    }
}
