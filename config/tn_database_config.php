<?php
/**
 * SHGM Exam System - Veritabanı Konfigürasyonu
 * 
 * @author SHGM Development Team
 * @version 1.0.0
 * @created 2024
 * @file tn_database_config.php
 */

// Direct access engelle
if (!defined('SYSTEM_START_TIME')) {
    die('Direct access denied!');
}

// Ana veritabanı ayarları
define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
define('DB_PORT', $_ENV['DB_PORT'] ?? '3306');
define('DB_NAME', $_ENV['DB_NAME'] ?? 'shgm_exam_system');
define('DB_USERNAME', $_ENV['DB_USERNAME'] ?? 'root');
define('DB_PASSWORD', $_ENV['DB_PASSWORD'] ?? '');
define('DB_CHARSET', $_ENV['DB_CHARSET'] ?? 'utf8mb4');
define('DB_COLLATION', $_ENV['DB_COLLATION'] ?? 'utf8mb4_unicode_ci');

// PDO ayarları
define('DB_TIMEOUT', 30);
define('DB_PERSISTENT', false);
define('DB_FETCH_MODE', PDO::FETCH_ASSOC);
define('DB_ERROR_MODE', PDO::ERRMODE_EXCEPTION);

// Connection pool ayarları
define('DB_MAX_CONNECTIONS', 10);
define('DB_CONNECTION_RETRY', 3);
define('DB_RETRY_DELAY', 1); // saniye

// Query ayarları
define('DB_QUERY_TIMEOUT', 30);
define('DB_SLOW_QUERY_LOG', true);
define('DB_SLOW_QUERY_TIME', 2); // saniye
define('DB_QUERY_CACHE', true);
define('DB_CACHE_LIFETIME', 300); // 5 dakika

// Backup ayarları
define('DB_BACKUP_AUTO', true);
define('DB_BACKUP_INTERVAL', 24); // saat
define('DB_BACKUP_RETENTION', 30); // gün
define('DB_BACKUP_COMPRESS', true);

// Migration ayarları
define('DB_MIGRATION_TABLE', 'tn_migrations');
define('DB_SEED_AUTO_RUN', APP_ENV === 'development');

// Tablo prefix ayarları
define('DB_TABLE_PREFIX', '');

// SSL ayarları (production için)
define('DB_SSL_ENABLE', false);
define('DB_SSL_CA', '');
define('DB_SSL_CERT', '');
define('DB_SSL_KEY', '');
define('DB_SSL_VERIFY', false);

// Veritabanı konfigürasyon dizisi
$TN_DATABASE_CONFIG = [
    'default' => [
        'driver' => 'mysql',
        'host' => DB_HOST,
        'port' => DB_PORT,
        'database' => DB_NAME,
        'username' => DB_USERNAME,
        'password' => DB_PASSWORD,
        'charset' => DB_CHARSET,
        'collation' => DB_COLLATION,
        'prefix' => DB_TABLE_PREFIX,
        'options' => [
            PDO::ATTR_ERRMODE => DB_ERROR_MODE,
            PDO::ATTR_DEFAULT_FETCH_MODE => DB_FETCH_MODE,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_PERSISTENT => DB_PERSISTENT,
            PDO::ATTR_TIMEOUT => DB_TIMEOUT,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET . " COLLATE " . DB_COLLATION,
            PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
            PDO::MYSQL_ATTR_FOUND_ROWS => true
        ]
    ],
    
    // Test veritabanı (development için)
    'test' => [
        'driver' => 'mysql',
        'host' => DB_HOST,
        'port' => DB_PORT,
        'database' => DB_NAME . '_test',
        'username' => DB_USERNAME,
        'password' => DB_PASSWORD,
        'charset' => DB_CHARSET,
        'collation' => DB_COLLATION,
        'prefix' => 'test_',
        'options' => [
            PDO::ATTR_ERRMODE => DB_ERROR_MODE,
            PDO::ATTR_DEFAULT_FETCH_MODE => DB_FETCH_MODE,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_PERSISTENT => false
        ]
    ],
    
    // Backup veritabanı
    'backup' => [
        'driver' => 'mysql',
        'host' => DB_HOST,
        'port' => DB_PORT,
        'database' => DB_NAME . '_backup',
        'username' => DB_USERNAME,
        'password' => DB_PASSWORD,
        'charset' => DB_CHARSET,
        'collation' => DB_COLLATION,
        'prefix' => 'bak_'
    ]
];

// SSL konfigürasyonu (production için)
if (DB_SSL_ENABLE && APP_ENV === 'production') {
    $TN_DATABASE_CONFIG['default']['options'][PDO::MYSQL_ATTR_SSL_CA] = DB_SSL_CA;
    $TN_DATABASE_CONFIG['default']['options'][PDO::MYSQL_ATTR_SSL_CERT] = DB_SSL_CERT;
    $TN_DATABASE_CONFIG['default']['options'][PDO::MYSQL_ATTR_SSL_KEY] = DB_SSL_KEY;
    $TN_DATABASE_CONFIG['default']['options'][PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = DB_SSL_VERIFY;
}

// Tablo isimleri
$TN_TABLES = [
    // Kullanıcı tabloları (tn_ prefix)
    'users' => 'tn_users',
    'user_sessions' => 'tn_user_sessions',
    'user_roles' => 'tn_user_roles',
    'user_permissions' => 'tn_user_permissions',
    'students' => 'tn_students',
    'student_sessions' => 'tn_student_sessions',
    'system_logs' => 'tn_system_logs',
    'error_logs' => 'tn_error_logs',
    'api_logs' => 'tn_api_logs',
    'migrations' => 'tn_migrations',
    'settings' => 'tn_settings',
    'cache' => 'tn_cache',
    
    // Sınav tabloları (ha_ prefix - hareket/activity)
    'exams' => 'ha_exams',
    'exam_categories' => 'ha_exam_categories',
    'questions' => 'ha_questions',
    'question_types' => 'ha_question_types',
    'question_media' => 'ha_question_media',
    'exam_sessions' => 'ha_exam_sessions',
    'exam_answers' => 'ha_exam_answers',
    'exam_results' => 'ha_exam_results',
    'exam_attempts' => 'ha_exam_attempts',
    'recordings' => 'ha_recordings',
    'recording_files' => 'ha_recording_files',
    
    // Rapor tabloları (rp_ prefix)
    'reports' => 'rp_reports',
    'report_data' => 'rp_report_data',
    'statistics' => 'rp_statistics',
    'analytics' => 'rp_analytics',
    'performance_metrics' => 'rp_performance_metrics',
    'grading_comparisons' => 'rp_grading_comparisons'
];

// Global erişim için kaydet
$GLOBALS['TN_DATABASE_CONFIG'] = $TN_DATABASE_CONFIG;
$GLOBALS['TN_TABLES'] = $TN_TABLES;

/**
 * Tablo adını al
 */
function tn_table($table) {
    return $GLOBALS['TN_TABLES'][$table] ?? $table;
}

/**
 * Veritabanı konfigürasyonunu al
 */
function tn_db_config($connection = 'default') {
    return $GLOBALS['TN_DATABASE_CONFIG'][$connection] ?? null;
}

/**
 * DSN string oluştur
 */
function tn_db_dsn($connection = 'default') {
    $config = tn_db_config($connection);
    if (!$config) return null;
    
    return sprintf(
        '%s:host=%s;port=%s;dbname=%s;charset=%s',
        $config['driver'],
        $config['host'],
        $config['port'],
        $config['database'],
        $config['charset']
    );
}

/**
 * Migration durumunu kontrol et
 */
function tn_check_migrations() {
    try {
        $pdo = new PDO(tn_db_dsn(), DB_USERNAME, DB_PASSWORD);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Migration tablosunu kontrol et
        $result = $pdo->query("SHOW TABLES LIKE '" . tn_table('migrations') . "'");
        return $result->rowCount() > 0;
        
    } catch (PDOException $e) {
        if (APP_DEBUG) {
            error_log('[TN_DB] Migration check failed: ' . $e->getMessage());
        }
        return false;
    }
}

/**
 * Veritabanı bağlantısını test et
 */
function tn_test_db_connection($connection = 'default') {
    try {
        $config = tn_db_config($connection);
        if (!$config) {
            throw new Exception("Database configuration for '{$connection}' not found");
        }
        
        $pdo = new PDO(
            tn_db_dsn($connection),
            $config['username'],
            $config['password'],
            $config['options']
        );
        
        // Basit test sorgusu
        $result = $pdo->query('SELECT 1 as test');
        $row = $result->fetch();
        
        return $row['test'] === 1;
        
    } catch (PDOException $e) {
        if (APP_DEBUG) {
            error_log('[TN_DB] Connection test failed: ' . $e->getMessage());
        }
        return false;
    }
}

/**
 * Veritabanı versiyon bilgisini al
 */
function tn_db_version($connection = 'default') {
    try {
        $config = tn_db_config($connection);
        $pdo = new PDO(tn_db_dsn($connection), $config['username'], $config['password']);
        
        $result = $pdo->query('SELECT VERSION() as version');
        $row = $result->fetch();
        
        return $row['version'];
        
    } catch (PDOException $e) {
        return 'Unknown';
    }
}

// Development ortamında bağlantı testi
if (APP_DEBUG && APP_ENV === 'development') {
    register_shutdown_function(function() {
        if (tn_test_db_connection()) {
            error_log('[TN_DB] Database connection test: PASSED at ' . date('Y-m-d H:i:s'));
        } else {
            error_log('[TN_DB] Database connection test: FAILED at ' . date('Y-m-d H:i:s'));
        }
    });
}

// Başarılı yükleme mesajı
if (APP_DEBUG) {
    error_log('[TN_DB_CONFIG] Database configuration loaded successfully at ' . date('Y-m-d H:i:s'));
}
?>