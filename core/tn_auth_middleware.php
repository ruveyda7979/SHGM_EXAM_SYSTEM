<?php
/**
 * SHGM Exam System - Auth Middlewares
 * -------------------------------------------------------------
 * Router ile çalışacak, "handle" eden fonksiyonlar üretir.
 * TN_Router::addRoute/get/post çağrılarına middleware olarak verilir.
 *
 * Kullanım (index.php):
 *   // sadece giriş zorunlu
 *   $router->get('profile', SomeController::class, 'index', [ TN_AuthMiddleware::auth() ]);
 *
 *   // yalnız admin/instructor
 *   $router->get('admin', TN_AdminController::class, 'dashboard',
 *       [ TN_AuthMiddleware::auth(), TN_AuthMiddleware::role(['admin','instructor']) ]);
 *
 *   // sadece misafir (giriş yapmamış) erişebilsin
 *   $router->get('login', TN_AuthController::class, 'showLogin',
 *       [ TN_AuthMiddleware::guest() ]);
 */

class TN_AuthMiddleware
{
    /** Ortak: oturumdan kullanıcıyı (aktifse) yükle */
    private static function currentUser(): ?array
    {
        // Session
        if (!class_exists('TN_SessionManager')) return null;
        $session = TN_SessionManager::getInstance();
        $uid = $session->get('user_id');
        if (!$uid) return null;

        // DB
        try {
            if (class_exists('TN_Database')) {
                /** @var PDO $pdo */
                $pdo = TN_Database::getInstance();
                $st = $pdo->prepare("SELECT id, name, email, role, status FROM users WHERE id = :id LIMIT 1");
                $st->execute([':id' => (int) $uid]);
                $row = $st->fetch(PDO::FETCH_ASSOC);
                if ($row && (int)$row['status'] === 1) {
                    return $row;
                }
            }
        } catch (Throwable $e) {
            // sessiz geç
        }
        return null;
    }

    /** Ortak: AJAX isteği mi? */
    private static function isAjax(): bool
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /** Ortak: JSON hata bastır ve durdur */
    private static function jsonAbort(int $code, string $message)
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'status_code' => $code,
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s'),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /** Ortak: yönlendir ve durdur */
    private static function redirectAbort(string $to)
    {
        header('Location: ' . (function_exists('tn_url') ? tn_url($to) : ('/' . ltrim($to, '/'))), true, 302);
        exit;
    }

    // ---------------------------------------------------------
    // MIDDLEWARE FABRİKALARI (router bunları çağırır)
    // ---------------------------------------------------------

    /**
     * Zorunlu giriş (oturum).
     * Oturum yoksa: AJAX => 401 JSON, normal => /login'e yönlendir.
     */
    public static function auth(): callable
    {
        return function () {
            $user = self::currentUser();
            if ($user) return true;

            if (self::isAjax()) {
                self::jsonAbort(401, 'Authentication required.');
            } else {
                self::redirectAbort('login');
            }
            return false; // erişilmez
        };
    }

    /**
     * Sadece misafirler (giriş YAPMAMIŞ) erişebilsin.
     * Oturum varsa /admin (veya /student/dashboard) gibi bir sayfaya atar.
     */
    public static function guest(string $redirect = 'admin')
    {
        return function () use ($redirect) {
            $user = self::currentUser();
            if (!$user) return true;

            // Giriş yaptıysa rolüne göre yönlendir
            $to = $redirect;
            $role = strtolower($user['role'] ?? '');
            if ($role === 'student') $to = 'student/dashboard';
            self::redirectAbort($to);
            return false;
        };
    }

    /**
     * Rol kontrolü. Örn: role(['admin','instructor'])
     * Uygun değilse: AJAX => 403 JSON, normal => 403 sayfası veya login.
     */
    public static function role(array $allowedRoles): callable
    {
        $allowed = array_map('strtolower', $allowedRoles);

        return function () use ($allowed) {
            $user = self::currentUser();
            if (!$user) {
                if (self::isAjax()) {
                    self::jsonAbort(401, 'Authentication required.');
                } else {
                    self::redirectAbort('login');
                }
            }

            $role = strtolower($user['role'] ?? '');
            if (in_array($role, $allowed, true)) {
                return true;
            }

            if (self::isAjax()) {
                self::jsonAbort(403, 'Permission denied.');
            } else {
                // Basit 403
                http_response_code(403);
                echo '<h1>403 - Permission Denied</h1>';
                echo '<p>Bu sayfaya erişim yetkiniz yok.</p>';
                exit;
            }
            return false;
        };
    }
}
