<?php
// Yalnızca local erişim
if (php_sapi_name() !== 'cli') {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    if (!in_array($ip, ['127.0.0.1', '::1'], true)) {
        http_response_code(403);
        exit('Forbidden');
    }
}
header('Content-Type: text/html; charset=utf-8');

// *** ÖNEMLİ: constants koruması için tanımla
define('SYSTEM_START_TIME', microtime(true));
define('SYSTEM_START_MEMORY', memory_get_usage());

// Autoloader & config
require __DIR__ . '/../core/tn_autoloader.php';
require __DIR__ . '/../config/tn_app_config.php';

$__autoloader = new TN_Autoloader();
$__autoloader->register();

// Error handler
if (class_exists('TN_ErrorHandler')) {
    TN_ErrorHandler::register();
}

echo "<h3>Tools / Error Test</h3>";

$mode = $_GET['t'] ?? 'throw';

if ($mode === 'ok') {
    echo "<p>OK – sayfa çalışıyor.</p>";
    exit;
}

if ($mode === 'log') {
    if (class_exists('TN_Logger')) {
        TN_Logger::getInstance()->info('Tools test log message');
        echo "<p>Logger: test mesajı yazıldı.</p>";
    } else {
        echo "<p>TN_Logger sınıfı bulunamadı.</p>";
    }
    exit;
}

// Varsayılan: exception fırlat ve handler’ın yakalamasını sağla
try {
    throw new Exception('Manual test exception from tools/error_test.php');
} catch (Throwable $e) {
    if (class_exists('TN_ErrorHandler')) {
        (new TN_ErrorHandler())->handleException($e);
    } else {
        http_response_code(500);
        echo "<pre>" . htmlspecialchars((string)$e, ENT_QUOTES, 'UTF-8') . "</pre>";
    }
}
