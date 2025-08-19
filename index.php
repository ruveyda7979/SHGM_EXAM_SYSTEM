<?php
/**
 * SHGM Pilot Exam System - Main Entry Point
 * @version 1.0.0
 */

// -------------------------------------------------------------
// 0) Boot ölçümleri
// -------------------------------------------------------------
define('SYSTEM_START_TIME', microtime(true));
define('SYSTEM_START_MEMORY', memory_get_usage());

// -------------------------------------------------------------
// 1) Temel PHP ayarları (ilk aşama – config sonrası tekrar ayarlanır)
// -------------------------------------------------------------
error_reporting(E_ALL);
ini_set('display_errors', '1');
mb_internal_encoding('UTF-8');
setlocale(LC_TIME, 'tr_TR.UTF-8', 'Turkish');

// Uzun işlemler için makul limitler
@set_time_limit(300);
@ini_set('memory_limit', '256M');

// -------------------------------------------------------------
// 2) Composer (varsa) ve kendi autoloader
// -------------------------------------------------------------
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

require_once __DIR__ . '/core/tn_autoloader.php';
$__autoloader = new TN_Autoloader();
$__autoloader->register();

// -------------------------------------------------------------
// 3) .env yükle (config'ten ÖNCE!)
// -------------------------------------------------------------
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $lines = preg_split('/\R/', file_get_contents($envFile));
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        if (strpos($line, '=') === false) continue;

        list($k, $v) = explode('=', $line, 2);
        $k = trim($k);
        $v = trim($v);
        if ((strlen($v) >= 2) && (
            ($v[0] === '"' && substr($v, -1) === '"') ||
            ($v[0] === "'" && substr($v, -1) === "'")
        )) {
            $v = substr($v, 1, -1);
        }
        $_ENV[$k] = $v;
    }
}

// -------------------------------------------------------------
// 4) Uygulama konfigürasyonu
// -------------------------------------------------------------
require_once __DIR__ . '/config/tn_app_config.php';

// Zaman dilimi
date_default_timezone_set(defined('APP_TIMEZONE') ? APP_TIMEZONE : 'Europe/Istanbul');

// Hata gösterim modu (APP_DEBUG’a göre tekrar ayarla)
if (defined('APP_DEBUG') && APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
} else {
    $mask = E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_USER_DEPRECATED;
    if (defined('E_STRICT')) $mask &= ~E_STRICT;
    error_reporting($mask);
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
}

// -------------------------------------------------------------
// 5) Oturum (SESSION_LIFETIME .env’den)
// -------------------------------------------------------------
$lifetime = isset($_ENV['SESSION_LIFETIME']) ? (int)$_ENV['SESSION_LIFETIME'] : 7200;
ini_set('session.gc_maxlifetime', (string)$lifetime);
ini_set('session.cookie_httponly', '1');
ini_set('session.use_strict_mode', '1');

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => $lifetime,
        'path'     => '/',
        'secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

// -------------------------------------------------------------
// 6) Global hata yakalayıcı
// -------------------------------------------------------------
if (class_exists('TN_ErrorHandler')) {
    TN_ErrorHandler::register();
}

// -------------------------------------------------------------
// 7) Router ve Route tanımları
// -------------------------------------------------------------
try {
    $router = new TN_Router();

    $route = isset($_GET['route']) ? trim($_GET['route']) : '';

    // --- Ana sayfa
    $router->addRoute('', 'TN_HomeController', 'index');
    $router->addRoute('home', 'TN_HomeController', 'index');

    // --- Auth
    $router->addRoute('auth/login',         'TN_AuthController', 'login');
    $router->addRoute('auth/logout',        'TN_AuthController', 'logout');
    $router->addRoute('auth/student-login', 'TN_StudentAuthController', 'login');

    // --- Admin
    $router->addRoute('admin',             'TN_AdminController', 'dashboard');
    $router->addRoute('admin/dashboard',   'TN_AdminController', 'dashboard');
    $router->addRoute('admin/students',    'TN_StudentManagementController', 'index');
    $router->addRoute('admin/exams',       'HA_ExamController', 'index');
    $router->addRoute('admin/questions',   'HA_QuestionController', 'index');
    $router->addRoute('admin/reports',     'RP_ReportController', 'index');

    // --- Student
    $router->addRoute('student',             'TN_StudentController', 'dashboard');
    $router->addRoute('student/dashboard',   'TN_StudentController', 'dashboard');
    $router->addRoute('student/exam',        'HA_ExamSessionController', 'start');
    $router->addRoute('student/exam/take',   'HA_ExamSessionController', 'take');
    $router->addRoute('student/exam/submit', 'HA_ExamSessionController', 'submit');

    // Çalıştır
    $router->dispatch($route);

} catch (Throwable $e) {
    if (class_exists('TN_ErrorHandler')) {
        // STATİK değil; yeni handler ile işle
        (new TN_ErrorHandler())->handleException($e);
    } else {
        http_response_code(500);
        if (defined('APP_DEBUG') && APP_DEBUG) {
            echo '<div style="background:#ff6b6b;color:#fff;padding:20px;margin:10px;border-radius:6px">';
            echo '<h3>🚨 System Error</h3>';
            echo '<strong>File:</strong> ' . htmlspecialchars($e->getFile()) . '<br>';
            echo '<strong>Line:</strong> ' . $e->getLine() . '<br>';
            echo '<strong>Message:</strong> ' . htmlspecialchars($e->getMessage()) . '<br>';
            echo '<strong>Time:</strong> ' . date('Y-m-d H:i:s');
            echo '</div>';
        } else {
            include __DIR__ . '/views/shared/tn_error_500.php';
        }
    }
}

// -------------------------------------------------------------
// 8) Performance overlay (sadece debug)
// -------------------------------------------------------------
if (defined('APP_DEBUG') && APP_DEBUG) {
    $endTime      = microtime(true);
    $endMemory    = memory_get_usage();
    $executionMs  = round(($endTime - SYSTEM_START_TIME) * 1000, 2);
    $memoryDelta  = round(($endMemory - SYSTEM_START_MEMORY) / 1048576, 2); // MB
    $peakMemory   = round(memory_get_peak_usage() / 1048576, 2);

    echo '<div style="background:#f8f9fa;border-top:3px solid #007bff;padding:10px;'
       . 'font-size:12px;position:fixed;bottom:0;right:0;width:300px;z-index:9999;">'
       . '<strong>🚀 Performance Info</strong><br>'
       . 'Execution Time: ' . $executionMs . ' ms<br>'
       . 'Memory Usage: ' . $memoryDelta . ' MB<br>'
       . 'Peak Memory: ' . $peakMemory . ' MB<br>'
       . 'Current Time: ' . date('H:i:s')
       . '</div>';
}
