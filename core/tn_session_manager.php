<?php
/**
 * SHGM Exam System – Session Manager (Session + JWT Köprüsü)
 * ----------------------------------------------------------
 * Bu sınıf iki işi birden yapar:
 *   1) Klasik PHP oturumu yönetir (mevcut projenin eski API’si korunmuştur).
 *   2) API istekleri için JWT üretip doğrular (HttpOnly cookie + Bearer header).
 *
 * Kullanım özeti:
 *   // Login’de:
 *   $token = TN_SessionManager::getInstance()->issueJwtForUser($userRow);
 *   // -> Cookie'ye yazar ve token string döner
 *
 *   // Korumalı endpoint:
 *   $payload = TN_SessionManager::getInstance()->requireJwtAuth(); // geçmezse 401 + exit
 *   // $payload['sub'] = user_id, $payload['role'] = rol
 *
 *   // Rol kontrol:
 *   if (!TN_SessionManager::getInstance()->hasRoleJwt(['admin','invigilator'])) { ... }
 */

require_once __DIR__ . '/tn_jwt_handler.php';

class TN_SessionManager
{
    /** @var TN_SessionManager|null */
    private static $instance = null;

    /** JWT çerez adı */
    public const AUTH_COOKIE = 'TN_AUTH';

    private function __construct()
    {
        $this->start();
    }

    /** Tek örnek (Singleton) */
    public static function getInstance(): self
    {
        if (!self::$instance) self::$instance = new self();
        return self::$instance;
    }

    /* --------------------------------------------------------
     *  .env yardımcı (küçük loader)
     * ------------------------------------------------------ */
    protected function env(string $key, $default=null) {
        if (isset($_ENV[$key])) return $_ENV[$key];
        if (isset($_SERVER[$key])) return $_SERVER[$key];

        static $loaded=false;
        if (!$loaded) {
            $envPath = __DIR__ . '/../.env';
            if (is_file($envPath)) {
                foreach (file($envPath, FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES) as $line) {
                    $line = trim($line);
                    if ($line==='' || $line[0]==='#') continue;
                    [$k,$v] = array_pad(explode('=', $line, 2), 2, '');
                    $v = trim($v);
                    $v = trim($v, "\"'");
                    $_ENV[trim($k)] = $v;
                }
            }
            $loaded = true;
        }
        return $_ENV[$key] ?? $default;
    }

    /* --------------------------------------------------------
     *  SESSION Bölümü (mevcut projenin orijinal API’si)
     * ------------------------------------------------------ */

    /** Oturumu güvenli başlat */
    public function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) return;

        $lifetime = (int)($this->env('SESSION_LIFETIME', 7200));

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

    public function id(): string { return session_id(); }

    public function regenerate(bool $deleteOld = true): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id($deleteOld);
        }
    }

    // ---- Key/Value API ----
    public function get(string $key, $default=null) { return $_SESSION[$key] ?? $default; }
    public function set(string $key, $value): bool { $_SESSION[$key] = $value; return true; }
    public function has(string $key): bool { return isset($_SESSION[$key]); }
    public function remove(string $key): void { unset($_SESSION[$key]); }

    // ---- Flash mesaj (bir kez okunur) ----
    public function flash(string $key, $value=null)
    {
        if ($value === null) {
            $val = $_SESSION['_flash'][$key] ?? null;
            if (isset($_SESSION['_flash'][$key])) unset($_SESSION['_flash'][$key]);
            return $val;
        } else {
            $_SESSION['_flash'][$key] = $value;
            return true;
        }
    }

    /** Tüm oturumu yok et (session tarafı) */
    public function destroy(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) return;

        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(), '',
                time() - 42000,
                $params['path'],
                $params['domain'] ?? '',
                $params['secure'] ?? false,
                $params['httponly'] ?? true
            );
        }
        @session_destroy();
    }

    /* --------------------------------------------------------
     *  JWT Bölümü (API için)
     * ------------------------------------------------------ */

    /** İstekten JWT’yi al (Authorization: Bearer ... veya çerez) */
    public function extractTokenFromRequest(): ?string
    {
        // 1) Authorization header
        $hdr = $_SERVER['HTTP_AUTHORIZATION'] ?? null;
        if (!$hdr && function_exists('apache_request_headers')) {
            $h = apache_request_headers();
            $hdr = $h['Authorization'] ?? null;
        }
        if ($hdr && stripos($hdr, 'Bearer ') === 0) {
            return trim(substr($hdr, 7));
        }
        // 2) HttpOnly cookie
        return $_COOKIE[self::AUTH_COOKIE] ?? null;
    }

    /** JWT’yi HttpOnly çerez olarak kaydet */
    public function setAuthCookie(string $token): void
    {
        $ttl = (int)$this->env('JWT_EXPIRY', 7200);
        $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
               || ((int)($_SERVER['SERVER_PORT'] ?? 80) === 443);

        setcookie(self::AUTH_COOKIE, $token, [
            'expires'  => time() + $ttl,
            'path'     => '/',
            'secure'   => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    /**
     * Login sonrası çağır: kullanıcıdan JWT üret, çereze yaz ve session’ı tazele.
     * $user = DB satırı (id, role, email alanları beklenir)
     */
    public function issueJwtForUser(array $user): string
    {
        // Session hijacking’e karşı session id yenile
        $this->regenerate(true);

        // Klasik sayfalar için minimal bilgi session’da dursun (opsiyonel)
        $_SESSION['user_id'] = (int)$user['id'];
        $_SESSION['role']    = (string)$user['role'];
        $_SESSION['email']   = (string)$user['email'];

        // JWT payload
        $payload = [
            'sub'   => (string)$user['id'],
            'role'  => (string)$user['role'],
            'email' => (string)$user['email'],
        ];

        $token = TN_JWT::make($payload); // exp .env’den
        $this->setAuthCookie($token);
        return $token;
    }

    /** Geçerli istekteki token’ın doğrulanmış payload’u (yoksa null) */
    public function currentJwtPayload(): ?array
    {
        $jwt = $this->extractTokenFromRequest();
        if (!$jwt) return null;
        try {
            return TN_JWT::verify($jwt);
        } catch (\Throwable $e) {
            return null;
        }
    }

    /** Kimlik doğrulaması zorunlu; başarısızsa 401 döndürür ve sonlandırır */
    public function requireJwtAuth(): array
    {
        $p = $this->currentJwtPayload();
        if (!$p) {
            http_response_code(401);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error'=>'Unauthorized']);
            exit;
        }
        return $p;
    }

    /** Rol kontrolü: tek rol ya da dizi kabul eder */
    public function hasRoleJwt($need): bool
    {
        $p = $this->currentJwtPayload();
        if (!$p) return false;
        $r = $p['role'] ?? null;
        if (is_array($need)) return in_array($r, $need, true);
        return $r === $need;
    }

    /** JWT logout (çerezi temizler) + istersen session’ı da kapatır */
    public function logoutJwt(bool $alsoDestroySession=false): void
    {
        $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
               || ((int)($_SERVER['SERVER_PORT'] ?? 80) === 443);

        setcookie(self::AUTH_COOKIE, '', [
            'expires'  => time() - 3600,
            'path'     => '/',
            'secure'   => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        unset($_COOKIE[self::AUTH_COOKIE]);

        if ($alsoDestroySession) $this->destroy();
    }
}
