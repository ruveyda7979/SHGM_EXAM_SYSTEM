<?php
/**
 * SHGM Exam System - Ana Uygulama Konfigürasyonu
 * @file config/tn_app_config.php
 */

///////////////////////////////////////////////////////////////
// Doğrudan erişimi engelle
///////////////////////////////////////////////////////////////
if (!defined('SYSTEM_START_TIME')) {
    die('Direct access denied!');
}

///////////////////////////////////////////////////////////////
// Sistem sabitleri (roller, tarih formatları, vb.)
///////////////////////////////////////////////////////////////
require_once __DIR__ . '/tn_constants.php';

///////////////////////////////////////////////////////////////
// Küçük yardımcılar
///////////////////////////////////////////////////////////////
if (!function_exists('tn_bool')) {
    function tn_bool($val, $default = false): bool {
        if ($val === null) return (bool)$default;
        if (is_bool($val)) return $val;
        $s = strtolower((string)$val);
        return in_array($s, ['1','true','on','yes'], true);
    }
}
if (!function_exists('tn_is_https')) {
    function tn_is_https(): bool {
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') return true;
        if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') return true;
        return false;
    }
}

///////////////////////////////////////////////////////////////
// Uygulama sabitleri (ENV üzerinden)
///////////////////////////////////////////////////////////////
define('APP_NAME',     $_ENV['APP_NAME']     ?? 'SHGM Pilot Exam System');
define('APP_VERSION',  '1.0.0');
define('APP_ENV',      $_ENV['APP_ENV']      ?? 'development');
define('APP_DEBUG',    tn_bool($_ENV['APP_DEBUG'] ?? true));
/* Proje alt klasörde: .env → APP_URL=http://localhost/shgm-exam-system */
define('APP_URL',      $_ENV['APP_URL']      ?? 'http://localhost/shgm-exam-system');
define('APP_TIMEZONE', $_ENV['APP_TIMEZONE'] ?? 'Europe/Istanbul');
define('APP_LOCALE',   $_ENV['APP_LOCALE']   ?? 'tr_TR');

///////////////////////////////////////////////////////////////
// Yollar
///////////////////////////////////////////////////////////////
define('ROOT_PATH',       rtrim(dirname(__DIR__), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR);
define('CONFIG_PATH',     ROOT_PATH . 'config'     . DIRECTORY_SEPARATOR);
define('CORE_PATH',       ROOT_PATH . 'core'       . DIRECTORY_SEPARATOR);
define('MODEL_PATH',      ROOT_PATH . 'models'     . DIRECTORY_SEPARATOR);
define('CONTROLLER_PATH', ROOT_PATH . 'controllers'. DIRECTORY_SEPARATOR);
define('VIEW_PATH',       ROOT_PATH . 'views'      . DIRECTORY_SEPARATOR);
define('API_PATH',        ROOT_PATH . 'api'        . DIRECTORY_SEPARATOR);
define('ASSET_PATH',      ROOT_PATH . 'assets'     . DIRECTORY_SEPARATOR);
define('STORAGE_PATH',    ROOT_PATH . 'storage'    . DIRECTORY_SEPARATOR);
define('DATABASE_PATH',   ROOT_PATH . 'database'   . DIRECTORY_SEPARATOR);

// Storage altları
define('UPLOAD_PATH',     STORAGE_PATH . 'uploads'    . DIRECTORY_SEPARATOR);
define('LOG_PATH',        STORAGE_PATH . 'logs'       . DIRECTORY_SEPARATOR);
define('RECORDING_PATH',  STORAGE_PATH . 'recordings' . DIRECTORY_SEPARATOR);
define('TEMP_PATH',       STORAGE_PATH . 'temp'       . DIRECTORY_SEPARATOR);
define('BACKUP_PATH',     STORAGE_PATH . 'backups'    . DIRECTORY_SEPARATOR);
define('CACHE_PATH',      STORAGE_PATH . 'cache'      . DIRECTORY_SEPARATOR);

///////////////////////////////////////////////////////////////
// Yükleme ayarları
///////////////////////////////////////////////////////////////
define('MAX_UPLOAD_SIZE',     (int)($_ENV['MAX_UPLOAD_SIZE'] ?? 104857600)); // 100MB
define('ALLOWED_EXTENSIONS',  explode(',', $_ENV['ALLOWED_EXTENSIONS'] ?? 'jpg,jpeg,png,gif,pdf,mp3,mp4,wav'));

///////////////////////////////////////////////////////////////
// Session ayarları
///////////////////////////////////////////////////////////////
define('SESSION_LIFETIME', (int)($_ENV['SESSION_LIFETIME'] ?? 7200));
define('SESSION_NAME',     'SHGM_EXAM_SESSION');
define('SESSION_SECURE',   tn_is_https());   // HTTPS’de true
define('SESSION_HTTPONLY', true);
define('SESSION_SAMESITE', 'Lax');

///////////////////////////////////////////////////////////////
// JWT & Güvenlik
///////////////////////////////////////////////////////////////
define('JWT_SECRET',     $_ENV['JWT_SECRET']     ?? 'change-this');
define('JWT_EXPIRY',     (int)($_ENV['JWT_EXPIRY'] ?? 7200));
define('JWT_ALGORITHM',  'HS256');
define('JWT_ISSUER',     APP_NAME);

define('ENCRYPTION_KEY', $_ENV['ENCRYPTION_KEY'] ?? 'your-32-character-encryption-key');
define('HASH_ALGO',      $_ENV['HASH_ALGO']      ?? 'sha256');
define('PASSWORD_MIN_LENGTH', 8);
define('LOGIN_ATTEMPT_LIMIT', 5);
define('LOGIN_BLOCK_DURATION', 900); // 15dk

///////////////////////////////////////////////////////////////
// E-posta
///////////////////////////////////////////////////////////////
define('MAIL_DRIVER',        $_ENV['MAIL_DRIVER']        ?? 'smtp');
define('MAIL_HOST',          $_ENV['MAIL_HOST']          ?? 'localhost');
define('MAIL_PORT',         (int)($_ENV['MAIL_PORT']     ?? 587));
define('MAIL_USERNAME',      $_ENV['MAIL_USERNAME']      ?? '');
define('MAIL_PASSWORD',      $_ENV['MAIL_PASSWORD']      ?? '');
define('MAIL_ENCRYPTION',    $_ENV['MAIL_ENCRYPTION']    ?? 'tls');
define('MAIL_FROM_ADDRESS',  $_ENV['MAIL_FROM_ADDRESS']  ?? 'noreply@shgm.gov.tr');
define('MAIL_FROM_NAME',     $_ENV['MAIL_FROM_NAME']     ?? APP_NAME);

///////////////////////////////////////////////////////////////
// Log & Cache
///////////////////////////////////////////////////////////////
define('LOG_LEVEL',      $_ENV['LOG_LEVEL'] ?? 'debug');
define('LOG_MAX_FILES', (int)($_ENV['LOG_MAX_FILES'] ?? 30));
define('LOG_DATE_FORMAT','Y-m-d H:i:s');

define('CACHE_DRIVER',   $_ENV['CACHE_DRIVER']   ?? 'file');
define('CACHE_LIFETIME', (int)($_ENV['CACHE_LIFETIME'] ?? 3600));

///////////////////////////////////////////////////////////////
// Sınav & Kayıt
///////////////////////////////////////////////////////////////
define('EXAM_AUTO_SAVE_INTERVAL', (int)($_ENV['EXAM_AUTO_SAVE_INTERVAL'] ?? 30));
define('EXAM_WARNING_TIME',       (int)($_ENV['EXAM_WARNING_TIME']       ?? 300));
define('EXAM_MAX_ATTEMPTS',       (int)($_ENV['EXAM_MAX_ATTEMPTS']       ?? 3));
define('EXAM_SHUFFLE_QUESTIONS',  tn_bool($_ENV['EXAM_SHUFFLE_QUESTIONS'] ?? true));
define('EXAM_SHUFFLE_ANSWERS',    tn_bool($_ENV['EXAM_SHUFFLE_ANSWERS']   ?? true));

define('RECORDING_MAX_DURATION',  (int)($_ENV['RECORDING_MAX_DURATION'] ?? 3600));
define('RECORDING_QUALITY',       $_ENV['RECORDING_QUALITY'] ?? 'high');
define('RECORDING_FORMAT',        $_ENV['RECORDING_FORMAT']  ?? 'webm');

///////////////////////////////////////////////////////////////
// Monitoring
///////////////////////////////////////////////////////////////
define('MONITOR_SYSTEM',        tn_bool($_ENV['MONITOR_SYSTEM']        ?? true));
define('MONITOR_API_CALLS',     tn_bool($_ENV['MONITOR_API_CALLS']     ?? true));
define('MONITOR_USER_ACTIONS',  tn_bool($_ENV['MONITOR_USER_ACTIONS']  ?? true));

///////////////////////////////////////////////////////////////
// PHP hata ayarları
///////////////////////////////////////////////////////////////
if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
}

///////////////////////////////////////////////////////////////
// Zaman dilimi & limitler
///////////////////////////////////////////////////////////////
if (function_exists('date_default_timezone_set')) {
    date_default_timezone_set(APP_TIMEZONE);
}
@ini_set('memory_limit', '256M');
@ini_set('max_execution_time', '300');
@ini_set('max_input_time', '300');

///////////////////////////////////////////////////////////////
// Uygulama konfigürasyon dizisi (opsiyonel, tn_config kullanır)
///////////////////////////////////////////////////////////////
$TN_APP_CONFIG = [
    'name'        => APP_NAME,
    'version'     => APP_VERSION,
    'environment' => APP_ENV,
    'debug'       => APP_DEBUG,
    'url'         => APP_URL,
    'timezone'    => APP_TIMEZONE,
    'locale'      => APP_LOCALE,
    'charset'     => 'UTF-8',

    'paths' => [
        'root'        => ROOT_PATH,
        'config'      => CONFIG_PATH,
        'core'        => CORE_PATH,
        'models'      => MODEL_PATH,
        'controllers' => CONTROLLER_PATH,
        'views'       => VIEW_PATH,
        'assets'      => ASSET_PATH,
        'storage'     => STORAGE_PATH,
        'uploads'     => UPLOAD_PATH,
        'logs'        => LOG_PATH,
        'recordings'  => RECORDING_PATH,
        'cache'       => CACHE_PATH,
        'database'    => DATABASE_PATH,
    ],

    'security' => [
        'jwt_secret'     => JWT_SECRET,
        'jwt_expiry'     => JWT_EXPIRY,
        'encryption_key' => ENCRYPTION_KEY,
        'hash_algo'      => HASH_ALGO,
    ],

    'exam' => [
        'auto_save_interval' => EXAM_AUTO_SAVE_INTERVAL,
        'warning_time'       => EXAM_WARNING_TIME,
        'max_attempts'       => EXAM_MAX_ATTEMPTS,
        'shuffle_questions'  => EXAM_SHUFFLE_QUESTIONS,
        'shuffle_answers'    => EXAM_SHUFFLE_ANSWERS,
    ],
];
$GLOBALS['TN_APP_CONFIG'] = $TN_APP_CONFIG;

///////////////////////////////////////////////////////////////
// Yardımcı fonksiyonlar
///////////////////////////////////////////////////////////////
if (!function_exists('tn_config')) {
    /** tn_config('paths.root') gibi erişim sağlar */
    function tn_config(string $key, $default = null) {
        $cfg = $GLOBALS['TN_APP_CONFIG'] ?? [];
        foreach (explode('.', $key) as $k) {
            if (is_array($cfg) && array_key_exists($k, $cfg)) {
                $cfg = $cfg[$k];
            } else {
                return $default;
            }
        }
        return $cfg;
    }
}

if (!function_exists('tn_url')) {
    /** Base APP_URL + path birleştirir */
    function tn_url(string $path = ''): string {
        $base = rtrim(APP_URL, '/');
        $p    = ltrim($path, '/');
        return $base . ($p ? '/' . $p : '');
    }
}

if (!function_exists('tn_asset')) {
    function tn_asset(string $path = ''): string {
        return tn_url('assets/' . ltrim($path, '/'));
    }
}

if (!function_exists('tn_storage')) {
    function tn_storage(string $path = ''): string {
        return tn_url('storage/' . ltrim($path, '/'));
    }
}

/* İstersen controller’lar dışında da kullanmak için pratik redirect */
if (!function_exists('tn_redirect')) {
    function tn_redirect(string $path, int $code = 302): void {
        header('Location: ' . tn_url($path), true, $code);
        exit;
    }
}

///////////////////////////////////////////////////////////////
// Debug log
///////////////////////////////////////////////////////////////
if (APP_DEBUG) {
    error_log('[TN_CONFIG] Loaded at ' . date('Y-m-d H:i:s') . ' | URL base: ' . APP_URL);
}
