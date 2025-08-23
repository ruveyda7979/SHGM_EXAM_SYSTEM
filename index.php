<?php
/**
 * SHGM Pilot Exam System - Main Entry Point
 * -------------------------------------------------------------
 * - .env yüklenir
 * - Uygulama config’i set edilir
 * - Oturum & güvenlik politikaları ayarlanır
 * - Global hata yakalayıcı devrede
 * - Router kurulur (GET/POST login & logout, admin/student alanları)
 * - Middleware: Auth + Role + CSRF
 * - DEBUG açıkken performans overlay
 */

///////////////////////////////////////////////////////////////
// 0) Boot ölçümleri
///////////////////////////////////////////////////////////////
define('SYSTEM_START_TIME', microtime(true));
define('SYSTEM_START_MEMORY', memory_get_usage());

///////////////////////////////////////////////////////////////
// 1) Temel PHP ayarları (config öncesi; config sonrası tekrar ayarlanır)
///////////////////////////////////////////////////////////////
error_reporting(E_ALL);
ini_set('display_errors', '1');
mb_internal_encoding('UTF-8');
setlocale(LC_TIME, 'tr_TR.UTF-8', 'Turkish');
@set_time_limit(300);
@ini_set('memory_limit', '256M');

///////////////////////////////////////////////////////////////
// 2) Composer (varsa) ve autoloader
///////////////////////////////////////////////////////////////
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}
require_once __DIR__ . '/core/tn_autoloader.php';
$__autoloader = new TN_Autoloader();
$__autoloader->register();

/**
 * NOT: Fonksiyon dosyaları (sınıf olmayanlar) autoloadera takılmaz.
 * Güvenlik yardımcıları (CSRF vs.) için manuel dahil et.
 */
require_once __DIR__ . '/core/tn_security.php';

///////////////////////////////////////////////////////////////
// 3) .env yükle (config’ten ÖNCE!)
///////////////////////////////////////////////////////////////
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $lines = preg_split('/\R/', file_get_contents($envFile));
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        if (strpos($line, '=') === false) continue;

        [$k, $v] = explode('=', $line, 2);
        $k = trim($k);
        $v = trim($v);
        if (strlen($v) >= 2) {
            $q = $v[0];
            if (($q === '"' || $q === "'") && substr($v, -1) === $q) {
                $v = substr($v, 1, -1);
            }
        }
        $_ENV[$k] = $v;
    }
}

///////////////////////////////////////////////////////////////
// 4) Uygulama konfigürasyonu
///////////////////////////////////////////////////////////////
require_once __DIR__ . '/config/tn_app_config.php';

// Zaman dilimi
date_default_timezone_set(defined('APP_TIMEZONE') ? APP_TIMEZONE : 'Europe/Istanbul');

// Hata gösterimi (APP_DEBUG’a göre)
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

///////////////////////////////////////////////////////////////
// 5) Oturum ayarları (.env → SESSION_LIFETIME)
///////////////////////////////////////////////////////////////
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

///////////////////////////////////////////////////////////////
// 6) Global hata yakalayıcı (varsa)
///////////////////////////////////////////////////////////////
if (class_exists('TN_ErrorHandler')) {
    TN_ErrorHandler::register();
}

///////////////////////////////////////////////////////////////
// 7) Router ve Route tanımları (+ middleware bağlama)
///////////////////////////////////////////////////////////////
try {
    $router = new TN_Router();

    // htaccess varsa “temiz URL” ile /login → route=login
    // htaccess yoksa index.php?route=login şeklinde çalışır.
    $route = isset($_GET['route']) ? trim($_GET['route']) : '';

    // ==========================
    // AUTH (Yönetici)
    // ==========================
    // Kök / → login formu
    $router->get('', TN_AuthController::class, 'showLogin');

    // /login (GET) → form
    $router->get('login', TN_AuthController::class, 'showLogin');

    // /login (POST) → kimlik doğrulama (+CSRF)
    $router->post('login', TN_AuthController::class, 'login', [
        fn() => TN_AuthMiddleware::requireCsrf()
    ]);

    // /logout (GET) → çıkış (giriş zorunlu)
    $router->get('logout', TN_AuthController::class, 'logout', [
        TN_AuthMiddleware::class
    ]);

    // /auth/* alias’ları (.htaccess yönlendirmeleriyle uyumlu)
    $router->get('auth',        TN_AuthController::class, 'showLogin');
    $router->get('auth/login',  TN_AuthController::class, 'showLogin');
    $router->post('auth/login', TN_AuthController::class, 'login', [
        fn() => TN_AuthMiddleware::requireCsrf()
    ]);
    $router->get('auth/logout', TN_AuthController::class, 'logout', [
        TN_AuthMiddleware::class
    ]);

    // ==========================
    // AUTH (Öğrenci)
    // ==========================
    $router->get('student-login',        TN_StudentAuthController::class, 'showLogin');
    $router->post('student-login',       TN_StudentAuthController::class, 'login', [
        fn() => TN_AuthMiddleware::requireCsrf()
    ]);
    $router->get('auth/student-login',   TN_StudentAuthController::class, 'showLogin');
    $router->post('auth/student-login',  TN_StudentAuthController::class, 'login', [
        fn() => TN_AuthMiddleware::requireCsrf()
    ]);

    // ==========================
    // ADMIN (korumalı)
    // ==========================
    $requireAdmin = [
        TN_AuthMiddleware::class,
        fn() => TN_AuthMiddleware::requireRole('admin'),
    ];
    $requireAdminOrInstructor = [
        TN_AuthMiddleware::class,
        fn() => TN_AuthMiddleware::requireRole(['admin','instructor']),
    ];

    $router->get('admin',             TN_AdminController::class, 'dashboard', $requireAdmin);
    $router->get('admin/dashboard',   TN_AdminController::class, 'dashboard', $requireAdmin);
    $router->get('admin/students',    TN_StudentManagementController::class, 'index', $requireAdmin);
    $router->get('admin/reports',     RP_ReportController::class, 'index', $requireAdmin);

    $router->get('admin/exams',       HA_ExamController::class, 'index', $requireAdminOrInstructor);
    $router->get('admin/questions',   HA_QuestionController::class, 'index', $requireAdminOrInstructor);

    // ==========================
    // STUDENT (korumalı)
    // ==========================
    $requireStudent = [
        TN_AuthMiddleware::class,
        fn() => TN_AuthMiddleware::requireRole('student'),
    ];

    $router->get('student',               TN_StudentController::class, 'dashboard', $requireStudent);
    $router->get('student/dashboard',     TN_StudentController::class, 'dashboard', $requireStudent);
    $router->get('student/exam',          HA_ExamSessionController::class, 'start', $requireStudent);
    $router->get('student/exam/take',     HA_ExamSessionController::class, 'take', $requireStudent);
    $router->post('student/exam/submit',  HA_ExamSessionController::class, 'submit', [
        ...$requireStudent,
        fn() => TN_AuthMiddleware::requireCsrf()
    ]);

    // Çalıştır
    $router->dispatch($route);

} catch (Throwable $e) {
    if (class_exists('TN_ErrorHandler')) {
        (new TN_ErrorHandler())->handleException($e);
    } else {
        http_response_code(500);
        if (defined('APP_DEBUG') && APP_DEBUG) {
            echo '<div style="background:#ff6b6b;color:#fff;padding:20px;margin:10px;border-radius:6px">';
            echo '<h3>🚨 System Error</h3>';
            echo '<strong>File:</strong> ' . htmlspecialchars($e->getFile()) . '<br>';
            echo '<strong>Line:</strong> ' . (int)$e->getLine() . '<br>';
            echo '<strong>Message:</strong> ' . htmlspecialchars($e->getMessage()) . '<br>';
            echo '<strong>Time:</strong> ' . date('Y-m-d H:i:s');
            echo '</div>';
        } else {
            $fallback = __DIR__ . '/views/shared/tn_error_500.php';
            if (file_exists($fallback)) {
                include $fallback;
            } else {
                echo "<h1>500 - Internal Server Error</h1>";
                echo "<p>An error occurred while processing your request.</p>";
            }
        }
    }
}

///////////////////////////////////////////////////////////////
// 8) DEBUG overlay (APP_DEBUG=true iken görünür)
///////////////////////////////////////////////////////////////
if (defined('APP_DEBUG') && APP_DEBUG) {
    $endTime      = microtime(true);
    $endMemory    = memory_get_usage();
    $executionMs  = round(($endTime - SYSTEM_START_TIME) * 1000, 2);
    $memoryDelta  = round(($endMemory - SYSTEM_START_MEMORY) / 1048576, 2);
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
