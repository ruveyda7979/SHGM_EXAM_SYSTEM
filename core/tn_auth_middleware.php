<?php
/**
 * SHGM Exam System - Auth Middleware
 * - handle(): giriş zorunluluğu (class adı olarak verildiğinde çağrılır)
 * - requireRole($roles): rol kontrolü (string|array)
 * - requireCsrf(): POST CSRF doğrulaması (tn_security.php kullanır)
 * - requireGuest(): zaten girişliyse login sayfasına erişimi engeller
 */

class TN_AuthMiddleware
{
    /** Giriş zorunluluğu */
    public function handle(): bool
    {
        $sess = class_exists('TN_SessionManager')
            ? TN_SessionManager::getInstance()
            : null;

        $userId = $sess ? $sess->get('user_id') : ($_SESSION['user_id'] ?? null);

        if (!$userId) {
            $loginUrl = function_exists('tn_url') ? tn_url('login') : '/login';
            header('Location: '.$loginUrl, true, 302);
            return false;
        }
        return true;
    }

    /** Rol kontrolü: 'admin' | ['admin','instructor'] ... */
    public static function requireRole($roles): bool
    {
        $roles = (array)$roles;

        $sess = class_exists('TN_SessionManager')
            ? TN_SessionManager::getInstance()
            : null;

        $user = $sess ? $sess->get('user') : ($_SESSION['user'] ?? null);
        $role = $user['role'] ?? null;

        if (!$role || !in_array($role, $roles, true)) {
            http_response_code(403);
            echo 'Forbidden (insufficient role).';
            return false;
        }
        return true;
    }

    /** CSRF kontrolü (sadece POST rotaları için kullan) */
    public static function requireCsrf(): bool
    {
        // tn_security.php içindeki yardımcı:
        $token = $_POST['csrf_token'] ?? '';
        if (!function_exists('tn_csrf_verify') || !tn_csrf_verify($token)) {
            http_response_code(419); // Laravel'in kullandığı gibi "expired/invalid"
            echo 'Invalid CSRF token.';
            return false;
        }
        return true;
    }

    /** Guest zorunluluğu: girişliyse /admin’e yönlendir */
    public static function requireGuest(): bool
    {
        $sess = class_exists('TN_SessionManager')
            ? TN_SessionManager::getInstance()
            : null;

        $userId = $sess ? $sess->get('user_id') : ($_SESSION['user_id'] ?? null);

        if ($userId) {
            $home = function_exists('tn_url') ? tn_url('admin') : '/admin';
            header('Location: '.$home, true, 302);
            return false;
        }
        return true;
    }
}
