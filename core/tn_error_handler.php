<?php
/**
 * SHGM Exam System - Error & Exception Handler (Pure PHP)
 * - PHP hatalarını yakalar, exception'a çevirir
 * - JSON isteklerde JSON döner, diğerlerinde HTML/şablon
 * - Loglar TN_Logger ile dosyaya gider
 */

class TN_ErrorHandler
{
    /** @var TN_Logger|null */
    private $logger;

    /** @var bool */
    private $debug;

    /** @var TN_ErrorHandler|null */
    private static $instance = null;

    public function __construct()
    {
        $this->logger = class_exists('TN_Logger') ? TN_Logger::getInstance('error') : null;
        $this->debug  = defined('APP_DEBUG') ? APP_DEBUG : (bool)($_ENV['APP_DEBUG'] ?? false);
    }

    /** Singleton erişimi */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Kayıt ol (index.php’nin başında çağır)
     */
    public static function register(): self
    {
        $handler = self::getInstance();

        // PHP temel ayarlar
        error_reporting(E_ALL);
        ini_set('display_errors', $handler->debug ? '1' : '0');
        ini_set('log_errors', '0');

        set_error_handler([$handler, 'handlePhpError']);
        set_exception_handler([$handler, 'handleException']);
        register_shutdown_function([$handler, 'handleShutdown']);

        return $handler;
    }

    /**
     * PHP hatalarını Exception’a çevir
     */
    public function handlePhpError($severity, $message, $file, $line): bool
    {
        // @ ile bastırılan hatalar atlanır
        if (!(error_reporting() & $severity)) {
            return true;
        }

        $ex = new ErrorException($message, 0, $severity, $file, $line);
        $this->handleException($ex);
        return true; // PHP’ye iletmeyelim
    }

    /**
     * Exception yakala
     */
    public function handleException(Throwable $e): void
    {
        try {
            $status = $this->exceptionToStatus($e);
            $this->logException($e, $status);

            if ($this->wantsJson()) {
                $this->respondJson($status, [
                    'success' => false,
                    'error' => [
                        'type' => get_class($e),
                        'message' => $this->debug ? $e->getMessage() : 'Beklenmeyen bir hata oluştu.',
                        'file' => $this->debug ? $e->getFile() : null,
                        'line' => $this->debug ? $e->getLine() : null,
                        'trace' => $this->debug ? explode("\n", $e->getTraceAsString()) : null,
                    ],
                ]);
            } else {
                $this->respondHtml($status, $e);
            }
        } catch (Throwable $t) {
            // Son çare: ham çıktı
            http_response_code(500);
            header('Content-Type: text/plain; charset=utf-8');
            echo "Fatal error in error handler: " . $t->getMessage();
        }
    }

    /**
     * Fatal shutdown (parse/fatal error)
     */
    public function handleShutdown(): void
    {
        $err = error_get_last();
        if (!$err) return;

        $fatalTypes = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR];
        if (!in_array($err['type'], $fatalTypes, true)) return;

        $e = new ErrorException($err['message'], 0, $err['type'], $err['file'], $err['line']);
        $this->handleException($e);
    }

    private function exceptionToStatus(Throwable $e): int
    {
        // Farklı exception sınıflarına farklı kodlar verilebilir
        return 500;
    }

    private function logException(Throwable $e, int $status): void
    {
        if (!$this->logger) return;

        $context = [
            'status' => $status,
            'file'   => $e->getFile(),
            'line'   => $e->getLine(),
            'url'    => $_SERVER['REQUEST_URI'] ?? '',
            'method' => $_SERVER['REQUEST_METHOD'] ?? '',
        ];

        if ($status >= 500) {
            $this->logger->error($e->getMessage(), $context);
        } else {
            $this->logger->warning($e->getMessage(), $context);
        }
    }

    private function wantsJson(): bool
    {
        // AJAX veya Accept header’ında json
        $xhr = isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

        $accept = strtolower($_SERVER['HTTP_ACCEPT'] ?? '');
        $ctype  = strtolower($_SERVER['CONTENT_TYPE'] ?? '');

        return $xhr || strpos($accept, 'application/json') !== false || strpos($ctype, 'application/json') !== false;
    }

    private function respondJson(int $status, array $payload): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function respondHtml(int $status, Throwable $e): void
    {
        http_response_code($status);

        // Özel view varsa onu kullan
        $view500 = defined('VIEW_PATH') ? (VIEW_PATH . 'shared/tn_error_500.php') : __DIR__ . '/../views/shared/tn_error_500.php';

        if (!$this->debug && file_exists($view500)) {
            // production vari—genel şablon
            include $view500;
            return;
        }

        // debug açıkken detaylı HTML
        $safeMsg  = htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        $safeFile = htmlspecialchars($e->getFile(), ENT_QUOTES, 'UTF-8');
        $safeLine = (int)$e->getLine();
        $trace    = htmlspecialchars($e->getTraceAsString(), ENT_QUOTES, 'UTF-8');

        echo '<div style="background:#ffebee;border:1px solid #f44336;padding:16px;margin:16px;font-family:monospace">';
        echo '<h2>🚨 Exception</h2>';
        echo "<b>Message:</b> {$safeMsg}<br>";
        echo "<b>File:</b> {$safeFile}<br>";
        echo "<b>Line:</b> {$safeLine}<br>";
        echo "<b>Status:</b> {$status}<br>";
        echo '<pre style="white-space:pre-wrap;">' . $trace . '</pre>';
        echo '</div>';
    }
}
