<?php
/**
 * TN_JWT – Basit JWT (HS256) yardımcı sınıfı
 * ------------------------------------------
 * .env beklenen değişkenler:
 *   - JWT_SECRET  : İmza anahtarı (güçlü bir değer kullanın)
 *   - JWT_EXPIRY  : Token ömrü (saniye)
 *   - APP_URL     : issuer (iss)
 *   - APP_NAME    : audience (aud)
 *
 * Örnek:
 *   $token   = TN_JWT::make(['sub'=>$userId,'role'=>'admin']);
 *   $payload = TN_JWT::verify($token);
 */
class TN_JWT
{
    /** Küçük bir .env okuyucu (uygulamada zaten varsa bunu kaldırabilirsiniz) */
    protected static function env(string $key, $default=null) {
        if (isset($_ENV[$key])) return $_ENV[$key];
        if (isset($_SERVER[$key])) return $_SERVER[$key];

        static $loaded=false;
        if(!$loaded){
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

    /** base64url yardımcıları */
    protected static function b64url_encode(string $d): string {
        return rtrim(strtr(base64_encode($d), '+/', '-_'), '=');
    }
    protected static function b64url_decode(string $d): string {
        $r = strlen($d) % 4;
        if ($r) $d .= str_repeat('=', 4 - $r);
        return base64_decode(strtr($d, '-_', '+/')) ?: '';
    }

    /** İmza (HS256) */
    protected static function sign(string $input, string $secret): string {
        return hash_hmac('sha256', $input, $secret, true); // raw
    }

    /**
     * JWT üret
     * @param array    $payload  uygulama claim’leri (sub, role, email, …)
     * @param int|null $expiresIn saniye (boşsa .env JWT_EXPIRY)
     */
    public static function make(array $payload, ?int $expiresIn=null): string {
        $secret = (string) self::env('JWT_SECRET', 'change-me');
        $iss    = (string) self::env('APP_URL',  'http://localhost');
        $aud    = (string) self::env('APP_NAME', 'SHGM Exam System');
        $expSec = $expiresIn ?? (int) self::env('JWT_EXPIRY', 7200);
        $now    = time();

        $claims = array_merge($payload, [
            'iss'=>$iss, 'aud'=>$aud,
            'iat'=>$now, 'nbf'=>$now, 'exp'=>$now+$expSec,
            'jti'=>bin2hex(random_bytes(12)),
        ]);

        $header = ['alg'=>'HS256','typ'=>'JWT'];
        $h = self::b64url_encode(json_encode($header,  JSON_UNESCAPED_SLASHES));
        $p = self::b64url_encode(json_encode($claims,  JSON_UNESCAPED_SLASHES));
        $s = self::b64url_encode(self::sign("$h.$p", $secret));

        return "$h.$p.$s";
    }

    /**
     * JWT doğrula ve payload döndür
     * @throws Exception
     */
    public static function verify(string $jwt, int $leeway=30): array {
        if (substr_count($jwt, '.') !== 2) throw new Exception('Malformed JWT');
        [$h64,$p64,$s64] = explode('.', $jwt, 3);

        $header  = json_decode(self::b64url_decode($h64), true);
        $payload = json_decode(self::b64url_decode($p64), true);
        $sig     = self::b64url_decode($s64);

        if (!is_array($header) || ($header['alg'] ?? '')!=='HS256')
            throw new Exception('Unsupported alg');
        if (!is_array($payload)) throw new Exception('Invalid payload');

        $secret   = (string) self::env('JWT_SECRET', 'change-me');
        $expected = self::sign("$h64.$p64", $secret);
        if (!hash_equals($expected, $sig)) throw new Exception('Invalid signature');

        $now = time();
        if (isset($payload['nbf']) && $payload['nbf'] > $now + $leeway)
            throw new Exception('Token not yet valid');
        if (isset($payload['iat']) && $payload['iat'] > $now + $leeway)
            throw new Exception('Token issued in the future');
        if (isset($payload['exp']) && $now - $leeway >= $payload['exp'])
            throw new Exception('Token expired');

        $iss = (string) self::env('APP_URL',  'http://localhost');
        $aud = (string) self::env('APP_NAME', 'SHGM Exam System');
        if (($payload['iss'] ?? null) !== $iss) throw new Exception('Invalid issuer');
        if (($payload['aud'] ?? null) !== $aud) throw new Exception('Invalid audience');

        return $payload;
    }
}
