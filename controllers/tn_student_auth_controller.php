<?php
/**
 * SHGM Exam System – Student Authentication Controller
 *  - GET  /auth/student-login : form
 *  - POST /auth/student-login : doğrulama
 *  - GET  /student/logout     : çıkış
 */
class TN_StudentAuthController extends TN_Controller
{
    /** GET form / POST login – tek endpoint */
    public function login()
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

        if ($method === 'GET') {
            return $this->render('student/tn_student_login', [
                'title' => 'Öğrenci Girişi',
                'error' => $this->session ? $this->session->flash('error') : null,
            ]);
        }

        // POST — doğrulama
        $email    = trim($this->input('email', ''));
        $password = (string)$this->input('password', '');

        // CSRF (Controller’dan okunuyorsa parametre gerekmiyor)
        if (!$this->validateCSRFToken()) {
            if ($this->session) $this->session->flash('error', 'Oturum süresi doldu. Lütfen tekrar deneyin.');
            return $this->redirect(function_exists('tn_url') ? tn_url('auth/student-login') : '/auth/student-login');
        }

        // Basit validasyon (validator kurallarınla uyumlu)
        $v = (new TN_Validator())->validate(
            ['email' => $email, 'password' => $password],
            ['email' => 'required|email', 'password' => 'required|min:4']
        );
        if ($v !== true) {
            if ($this->session) $this->session->flash('error', 'Eksik ya da hatalı veri.');
            return $this->redirect(function_exists('tn_url') ? tn_url('auth/student-login') : '/auth/student-login');
        }

        // Model ile kimlik doğrula
        $model   = new TN_StudentModel();
        $student = $model->loginWithEmailPassword($email, $password);

        if (!$student) {
            if ($this->session) $this->session->flash('error', 'E-posta veya şifre hatalı.');
            return $this->redirect(function_exists('tn_url') ? tn_url('auth/student-login') : '/auth/student-login');
        }

        // Oturum
        if ($this->session) {
            $this->session->regenerate(true);

            // Middleware’lerin tutarlı çalışması için user_id + user(role) set ediyoruz
            $this->session->set('user_id', (int)$student['id']);
            $this->session->set('user', [
                'id'    => (int)$student['id'],
                'email' => $student['email'] ?? '',
                'role'  => 'student',
            ]);

            // İstersen ayrıca öğrenciye özel anahtarlar
            $this->session->set('student_id', (int)$student['id']);
            $this->session->set('student', $student); // (şifresiz alanlar)
        }

        // Öğrenci ana sayfası
        return $this->redirect(function_exists('tn_url') ? tn_url('student/dashboard') : '/student/dashboard');
    }

    /** Çıkış */
    public function logout()
    {
        if ($this->session) $this->session->destroy();
        return $this->redirect(function_exists('tn_url') ? tn_url('auth/student-login') : '/auth/student-login');
    }
}
