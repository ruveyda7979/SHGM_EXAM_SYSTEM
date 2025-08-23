<?php
/**
 * SHGM Exam System – Student Authentication Controller
 * ----------------------------------------------------
 * Amaç:
 *  - GET  /auth/student-login : Öğrenci giriş formunu göster
 *  - POST /auth/student-login : Öğrenciyi doğrula, oturum aç
 *  - GET  /student/logout     : Çıkış
 *
 * Not: Router’ında şu tanımlar olmalı:
 *   $router->addRoute('auth/student-login', 'TN_StudentAuthController', 'login');
 *   $router->addRoute('student/logout',     'TN_StudentAuthController', 'logout');
 */

class TN_StudentAuthController extends TN_Controller
{
    /** GET form / POST login – tek endpoint */
    public function login()
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

        if ($method === 'GET') {
            return $this->render('student.tn_student_login', [
                'title' => 'Öğrenci Girişi',
                'error' => $this->session ? $this->session->flash('error') : null,
            ], 'none'); // layout yoksa 'none'
        }

        // POST — doğrulama
        $email    = trim($this->input('email', ''));
        $password = (string)$this->input('password', '');
        $csrf     = (string)$this->input('csrf_token', '');

        // CSRF koruması
        if (!$this->validateCSRFToken($csrf)) {
            return $this->jsonError('CSRF token doğrulanamadı.', 419);
        }

        // Basit validasyon
        $v = $this->validate([
            'email'    => 'required|email',
            'password' => 'required|minLen:6',
        ]);

        if ($v !== true) {
            if ($this->session) $this->session->flash('error', 'Eksik ya da hatalı veri.');
            return $this->redirect('/auth/student-login');
        }

        // Model ile kimlik doğrula
        $model   = new TN_StudentModel();
        $student = $model->loginWithEmailPassword($email, $password);

        if (!$student) {
            if ($this->session) $this->session->flash('error', 'E-posta veya şifre hatalı.');
            return $this->redirect('/auth/student-login');
        }

        // Oturum
        if ($this->session) {
            $this->session->regenerate(true);
            $this->session->set('student_id', $student['id']);
            $this->session->set('student', $student); // şifresiz
        }

        // Öğrenci ana sayfasına (aşama 4’te gerçek dashboard’a yönlendiririz)
        return $this->redirect('/student');
    }

    /** Çıkış */
    public function logout()
    {
        if ($this->session) $this->session->destroy();
        return $this->redirect('/auth/student-login');
    }
}
