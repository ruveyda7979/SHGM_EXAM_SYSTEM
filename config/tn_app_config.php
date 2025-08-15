<?php
/**
 * SHGM Exam System - Ana Uygulama Konfigürasyonu
 * 
 * @author SHGM Development Team
 * @version 1.0.0
 * @created 2024
 * @file tn_app_config.php
 */

// Direct access engelle
if (!defined('SYSTEM_START_TIME')) {
    die('Direct access denied!');
}

// Ana uygulama sabitleri
define('APP_NAME', $_ENV['APP_NAME'] ?? 'SHGM Pilot Exam System');
define('APP_VERSION', '1.0.0');
define('APP_ENV', $_ENV['APP_ENV'] ?? 'development');
define('APP_DEBUG', filter_var($_ENV['APP_DEBUG'] ?? true, FILTER_VALIDATE_BOOLEAN));
define('APP_URL', $_ENV['APP_URL'] ?? 'http://localhost:8000');
define('APP_TIMEZONE', $_ENV['APP_TIMEZONE'] ?? 'Europe/Istanbul');
define('APP_LOCALE', $_ENV['APP_LOCALE'] ?? 'tr_TR');

// Dosya yolları
define('ROOT_PATH', dirname(__DIR__) . '/');
define('CONFIG_PATH', ROOT_PATH . 'config/');
define('CORE_PATH', ROOT_PATH . 'core/');
define('MODEL_PATH', ROOT_PATH . 'models/');
define('CONTROLLER_PATH', ROOT_PATH . 'controllers/');
define('VIEW_PATH', ROOT_PATH . 'views/');
define('API_PATH', ROOT_PATH . 'api/');
define('ASSET_PATH', ROOT_PATH . 'assets/');
define('STORAGE_PATH', ROOT_PATH . 'storage/');
define('DATABASE_PATH', ROOT_PATH . 'database/');

// Upload ve Storage ayarları
define('UPLOAD_PATH', STORAGE_PATH . 'uploads/');
define('LOG_PATH', STORAGE_PATH . 'logs/');
define('RECORDING_PATH', STORAGE_PATH . 'recordings/');
define('TEMP_PATH', STORAGE_PATH . 'temp/');
define('BACKUP_PATH', STORAGE_PATH . 'backups/');
define('CACHE_PATH', STORAGE_PATH . 'cache/');

// Dosya yükleme ayarları
define('MAX_UPLOAD_SIZE', (int)($_ENV['MAX_UPLOAD_SIZE'] ?? 104857600)); // 100MB
define('ALLOWED_EXTENSIONS', explode(',', $_ENV['ALLOWED_EXTENSIONS'] ?? 'jpg,jpeg,png,gif,pdf,mp3,mp4,wav'));

// Session ayarları
define('SESSION_LIFETIME', (int)($_ENV['SESSION_LIFETIME'] ?? 7200)); // 2 saat
define('SESSION_NAME', 'SHGM_EXAM_SESSION');
define('SESSION_SECURE', !APP_DEBUG); // HTTPS'de true olacak
define('SESSION_HTTPONLY', true);
define('SESSION_SAMESITE', 'Lax');

// JWT ayarları
define('JWT_SECRET', $_ENV['JWT_SECRET'] ?? 'your-super-secret-jwt-key-change-this');
define('JWT_EXPIRY', (int)($_ENV['JWT_EXPIRY'] ?? 7200)); // 2 saat
define('JWT_ALGORITHM', 'HS256');
define('JWT_ISSUER', APP_NAME);

// Güvenlik ayarları
define('ENCRYPTION_KEY', $_ENV['ENCRYPTION_KEY'] ?? 'your-32-character-encryption-key');
define('HASH_ALGO', $_ENV['HASH_ALGO'] ?? 'sha256');
define('PASSWORD_MIN_LENGTH', 8);
define('LOGIN_ATTEMPT_LIMIT', 5);
define('LOGIN_BLOCK_DURATION', 900); // 15 dakika

// Email ayarları
define('MAIL_DRIVER', $_ENV['MAIL_DRIVER'] ?? 'smtp');
define('MAIL_HOST', $_ENV['MAIL_HOST'] ?? 'localhost');
define('MAIL_PORT', (int)($_ENV['MAIL_PORT'] ?? 587));
define('MAIL_USERNAME', $_ENV['MAIL_USERNAME'] ?? '');
define('MAIL_PASSWORD', $_ENV['MAIL_PASSWORD'] ?? '');
define('MAIL_ENCRYPTION', $_ENV['MAIL_ENCRYPTION'] ?? 'tls');
define('MAIL_FROM_ADDRESS', $_ENV['MAIL_FROM_ADDRESS'] ?? 'noreply@shgm.gov.tr');
define('MAIL_FROM_NAME', $_ENV['MAIL_FROM_NAME'] ?? APP_NAME);

// Log ayarları
define('LOG_LEVEL', $_ENV['LOG_LEVEL'] ?? 'debug');
define('LOG_MAX_FILES', (int)($_ENV['LOG_MAX_FILES'] ?? 30));
define('LOG_DATE_FORMAT', 'Y-m-d H:i:s');

// Cache ayarları
define('CACHE_DRIVER', $_ENV['CACHE_DRIVER'] ?? 'file');
define('CACHE_LIFETIME', (int)($_ENV['CACHE_LIFETIME'] ?? 3600)); // 1 saat

// Exam ayarları
define('EXAM_AUTO_SAVE_INTERVAL', (int)($_ENV['EXAM_AUTO_SAVE_INTERVAL'] ?? 30)); // 30 saniye
define('EXAM_WARNING_TIME', (int)($_ENV['EXAM_WARNING_TIME'] ?? 300)); // 5 dakika
define('EXAM_MAX_ATTEMPTS', (int)($_ENV['EXAM_MAX_ATTEMPTS'] ?? 3));
define('EXAM_SHUFFLE_QUESTIONS', filter_var($_ENV['EXAM_SHUFFLE_QUESTIONS'] ?? true, FILTER_VALIDATE_BOOLEAN));
define('EXAM_SHUFFLE_ANSWERS', filter_var($_ENV['EXAM_SHUFFLE_ANSWERS'] ?? true, FILTER_VALIDATE_BOOLEAN));

// Recording ayarları
define('RECORDING_MAX_DURATION', (int)($_ENV['RECORDING_MAX_DURATION'] ?? 3600)); // 1 saat
define('RECORDING_QUALITY', $_ENV['RECORDING_QUALITY'] ?? 'high');
define('RECORDING_FORMAT', $_ENV['RECORDING_FORMAT'] ?? 'webm');

// System monitoring ayarları
define('MONITOR_SYSTEM', filter_var($_ENV['MONITOR_SYSTEM'] ?? true, FILTER_VALIDATE_BOOLEAN));
define('MONITOR_API_CALLS', filter_var($_ENV['MONITOR_API_CALLS'] ?? true, FILTER_VALIDATE_BOOLEAN));
define('MONITOR_USER_ACTIONS', filter_var($_ENV['MONITOR_USER_ACTIONS'] ?? true, FILTER_VALIDATE_BOOLEAN));

// Error reporting ayarları
if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
}

// Timezone ayarla
if (function_exists('date_default_timezone_set')) {
    date_default_timezone_set(APP_TIMEZONE);
}

// Memory ve execution time ayarları
ini_set('memory_limit', '256M');
ini_set('max_execution_time', 300);
ini_set('max_input_time', 300);

// Uygulama konfigürasyon dizisi
$TN_APP_CONFIG = [
    'name' => APP_NAME,
    'version' => APP_VERSION,
    'environment' => APP_ENV,
    'debug' => APP_DEBUG,
    'url' => APP_URL,
    'timezone' => APP_TIMEZONE,
    'locale' => APP_LOCALE,
    'charset' => 'UTF-8',
    'paths' => [
        'root' => ROOT_PATH,
        'config' => CONFIG_PATH,
        'core' => CORE_PATH,
        'models' => MODEL_PATH,
        'controllers' => CONTROLLER_PATH,
        'views' => VIEW_PATH,
        'assets' => ASSET_PATH,
        'storage' => STORAGE_PATH,
        'uploads' => UPLOAD_PATH,
        'logs' => LOG_PATH,
        'recordings' => RECORDING_PATH
    ],
    'security' => [
        'jwt_secret' => JWT_SECRET,
        'jwt_expiry' => JWT_EXPIRY,
        'encryption_key' => ENCRYPTION_KEY,
        'hash_algo' => HASH_ALGO
    ],
    'exam' => [
        'auto_save_interval' => EXAM_AUTO_SAVE_INTERVAL,
        'warning_time' => EXAM_WARNING_TIME,
        'max_attempts' => EXAM_MAX_ATTEMPTS,
        'shuffle_questions' => EXAM_SHUFFLE_QUESTIONS,
        'shuffle_answers' => EXAM_SHUFFLE_ANSWERS
    ]
];

// Konfigürasyonu global erişime aç
$GLOBALS['TN_APP_CONFIG'] = $TN_APP_CONFIG;

/**
 * Konfigürasyon değerini al
 */
function tn_config($key, $default = null) {
    $keys = explode('.', $key);
    $config = $GLOBALS['TN_APP_CONFIG'];
    
    foreach ($keys as $k) {
        if (isset($config[$k])) {
            $config = $config[$k];
        } else {
            return $default;
        }
    }
    
    return $config;
}

/**
 * URL oluşturucu
 */
function tn_url($path = '') {
    $baseUrl = rtrim(APP_URL, '/');
    $path = ltrim($path, '/');
    return $baseUrl . ($path ? '/' . $path : '');
}

/**
 * Asset URL oluşturucu
 */
function tn_asset($path = '') {
    return tn_url('assets/' . ltrim($path, '/'));
}

/**
 * Storage URL oluşturucu
 */
function tn_storage($path = '') {
    return tn_url('storage/' . ltrim($path, '/'));
}

// Başarılı yükleme mesajı
if (APP_DEBUG) {
    error_log('[TN_CONFIG] Application configuration loaded successfully at ' . date('Y-m-d H:i:s'));
}
?>