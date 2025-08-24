<?php
/**
 * SHGM Exam System - Auth Controller
 *  - GET  /login  : giriş formu
 *  - POST /login  : kimlik doğrulama, session (+opsiyonel JWT)
 *  - GET  /logout : oturumu kapat
 */

class TN_AuthController extends TN_Controller
{
    /** Base’ten gelen $session kullanılacak; burada yeniden tanımlamıyoruz. */
    protected TN_UserModel $users;
    protected TN_Validator $v;

    public function __construct()
    {
        parent::__construct();
        $this->users = new TN_UserModel();
        $this->v     = new TN_Validator();
    }

    /** Giriş formu (admin) */
    public function showLogin(): void
    {
        $this->render('auth/tn_login', [
            'title' => 'Yönetici Girişi',
            'error' => $this->session->flash('auth_error'),
            'email' => (string)$this->session->flash('auth_email'),
            // Not: View tn_csrf_input() kullandığı için ayrıca csrf_token göndermek şart değil.
        ]);
    }

    /** Giriş (POST) */
    public function login(): void
    {
        // 1) CSRF doğrulaması
        if (!$this->validateCSRFToken()) {
            $this->session->flash('auth_error', 'Oturum süresi doldu. Lütfen tekrar deneyin.');
            $this->redirect('login');
        }

        // 2) Input + kurallar
        $data = [
            'email'    => trim($_POST['email'] ?? ''),
            'password' => (string)($_POST['password'] ?? '')
        ];
        $rules = [
            'email'    => 'required|email',
            'password' => 'required|min:4'
        ];
        $ok = $this->v->validate($data, $rules);
        if ($ok !== true) {
            $this->session->flash('auth_error', 'E-posta veya şifre geçersiz.');
            $this->session->flash('auth_email', $data['email']);
            $this->redirect('login');
        }

        // 3) Model ile doğrulama
        $user = $this->users->loginWithEmailPassword($data['email'], $data['password']);
        if (!$user) {
            $this->session->flash('auth_error', 'Giriş başarısız. Bilgileri kontrol edin.');
            $this->session->flash('auth_email', $data['email']);
            $this->redirect('login');
        }

        // 4) Session fixation önleme
        $this->session->regenerate(true);

        // 5) Oturum bilgileri
        $this->session->set('user_id', (int)$user['id']);
        $this->session->set('user', [
            'id'    => (int)$user['id'],
            'name'  => $user['name']  ?? '',
            'email' => $user['email'] ?? '',
            'role'  => $user['role']  ?? 'student',
        ]);

        // 6) (Opsiyonel) JWT
        // 6) (Opsiyonel) JWT
if (class_exists('TN_JWT')) {
    $jwt = TN_JWT::make([
        'uid'  => (int)$user['id'],
        'role' => $user['role'] ?? 'student',
        'typ'  => 'admin-web',
    ]);
    $this->session->set('jwt', $jwt);
}


        // 7) Dashboard’a yönlendir
        $this->redirect('admin'); // TN_Controller::redirect base path’i kendisi ekler
    }

    /** Çıkış */
    public function logout(): void
    {
        $this->session->destroy();
        $this->redirect('login');
    }
}
