<?php
/**
 * SHGM Exam System - Session Manager (Pure PHP)
 * Basit singleton oturum yöneticisi
 */

class TN_SessionManager
{
    /** @var TN_SessionManager|null */
    private static $instance = null;

    private function __construct()
    {
        $this->start();
    }

    /** Tek örnek */
    public static function getInstance(): self
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /** Oturumu güvenli şekilde başlat */
    public function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $lifetime = isset($_ENV['SESSION_LIFETIME']) ? (int)$_ENV['SESSION_LIFETIME'] : 7200;

        // Cookie parametreleri
        @session_set_cookie_params([
            'lifetime' => $lifetime,
            'path'     => '/',
            'secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        @ini_set('session.gc_maxlifetime', (string)$lifetime);
        @ini_set('session.use_strict_mode', '1');
        @ini_set('session.cookie_httponly', '1');

        @session_start();
    }

    public function id(): string
    {
        return session_id();
    }

    public function regenerate(bool $deleteOld = true): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id($deleteOld);
        }
    }

    // ---- Key/Value API ----
    public function get(string $key, $default = null)
    {
        return $_SESSION[$key] ?? $default;
    }

    public function set(string $key, $value): bool
    {
        $_SESSION[$key] = $value;
        return true;
    }

    public function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    public function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    // ---- Flash mesaj (bir kez okunur) ----
    public function flash(string $key, $value = null)
    {
        if ($value === null) {
            $val = $_SESSION['_flash'][$key] ?? null;
            if (isset($_SESSION['_flash'][$key])) {
                unset($_SESSION['_flash'][$key]);
            }
            return $val;
        } else {
            $_SESSION['_flash'][$key] = $value;
            return true;
        }
    }

    /** Tüm oturumu yok et */
    public function destroy(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) return;

        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'] ?? '',
                $params['secure'] ?? false,
                $params['httponly'] ?? true
            );
        }
        @session_destroy();
    }
}
