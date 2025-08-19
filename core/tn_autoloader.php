<?php
/**
 * SHGM Exam System - Otomatik Sınıf Yükleyici (Final)
 *
 * 3 katman:
 *  1) Kayıtlı sınıf yolları (self::$classPaths)
 *  2) PSR-4 namespace eşleşmeleri (self::$namespaces)
 *  3) İsim sözleşmesi (CamelCase -> snake_case) ile arama
 *
 * Örnek:
 *  TN_HomeController   -> controllers/tn_home_controller.php
 *  TN_Model            -> core/tn_model.php
 *  HA_ExamModel        -> models/ha_exam_model.php
 *  RP_ReportController -> controllers/rp_report_controller.php
 */

class TN_Autoloader
{
    /** @var array<string,string> */
    private static array $namespaces = [];

    /** @var array<string,string> */
    private static array $classPaths = [];

    private static bool $debug = false;

    /** @var array<int,string> */
    private static array $loadedClasses = [];

    public function register(): self
    {
        self::$debug = defined('APP_DEBUG') ? (bool)APP_DEBUG : false;

        spl_autoload_register([$this, 'loadClass'], true, true);

        $this->registerNamespaces();
        $this->registerClassPaths();

        if (self::$debug) {
            error_log('[TN_AUTOLOADER] Registered (debug=' . (self::$debug ? '1' : '0') . ')');
        }
        return $this;
    }

    private function registerNamespaces(): void
    {
        $basePath = __DIR__ . '/../';

        self::$namespaces = [
            'SHGM\\Core\\'        => $basePath . 'core/',
            'SHGM\\Models\\'      => $basePath . 'models/',
            'SHGM\\Controllers\\' => $basePath . 'controllers/',
            'SHGM\\API\\'         => $basePath . 'api/',
        ];

        if (self::$debug) {
            error_log('[TN_AUTOLOADER] Namespaces: ' . json_encode(self::$namespaces, JSON_UNESCAPED_SLASHES));
        }
    }

    private function registerClassPaths(): void
    {
        $basePath = __DIR__ . '/../';

        self::$classPaths = [
            // Core
            'TN_Router'          => $basePath . 'core/tn_router.php',
            'TN_Controller'      => $basePath . 'core/tn_controller.php',
            'TN_Model'           => $basePath . 'core/tn_model.php',
            'TN_View'            => $basePath . 'core/tn_view.php',
            'TN_Database'        => $basePath . 'core/tn_database.php',
            'TN_ErrorHandler'    => $basePath . 'core/tn_error_handler.php',
            'TN_Logger'          => $basePath . 'core/tn_logger.php',
            'TN_JWT'             => $basePath . 'core/tn_jwt_handler.php',
            'TN_SessionManager'  => $basePath . 'core/tn_session_manager.php',
            'TN_Security'        => $basePath . 'core/tn_security.php',
            'TN_Validator'       => $basePath . 'core/tn_validator.php',
            'TN_AuthMiddleware'  => $basePath . 'core/tn_auth_middleware.php',

            // Models (hız için doğrudan)
            'TN_UserModel'       => $basePath . 'models/tn_user_model.php',

            // API (opsiyonel)
            'TN_BaseAPI'         => $basePath . 'api/tn_base_api.php',
            'TN_AuthAPI'         => $basePath . 'api/tn_auth_api.php',
            'TN_AdminAPI'        => $basePath . 'api/tn_admin_api.php',
            'HA_ExamAPI'         => $basePath . 'api/ha_exam_api.php',
        ];

        if (self::$debug) {
            error_log('[TN_AUTOLOADER] ClassPaths count=' . count(self::$classPaths));
        }
    }

    public function loadClass(string $className): bool
    {
        if (in_array($className, self::$loadedClasses, true)) {
            return true;
        }

        // 1) Doğrudan eşleşen yol
        if (isset(self::$classPaths[$className])) {
            if ($this->loadFile(self::$classPaths[$className], $className)) {
                return true;
            }
        }

        // 2) PSR-4
        foreach (self::$namespaces as $prefix => $baseDir) {
            if (strncmp($className, $prefix, strlen($prefix)) === 0) {
                $relative = substr($className, strlen($prefix));
                $file = $baseDir . str_replace('\\', '/', $relative) . '.php';
                if ($this->loadFile($file, $className)) {
                    return true;
                }
            }
        }

        // 3) İsim sözleşmesi (CamelCase -> snake_case)
        if ($this->loadByConvention($className)) {
            return true;
        }

        if (self::$debug) {
            error_log("[TN_AUTOLOADER] NOT FOUND: {$className}");
        }
        return false;
    }

    private function loadFile(string $filePath, string $className = ''): bool
    {
        if (is_file($filePath) && is_readable($filePath)) {
            require_once $filePath;

            if ($className === '' || class_exists($className, false)) {
                self::$loadedClasses[] = $className ?: $filePath;
                if (self::$debug) {
                    error_log("[TN_AUTOLOADER] Loaded: " . ($className ?: basename($filePath)) . " <- {$filePath}");
                }
                return true;
            }
        } elseif (self::$debug) {
            error_log("[TN_AUTOLOADER] File missing: {$filePath}");
        }
        return false;
    }

    private function loadByConvention(string $className): bool
    {
        $basePath = __DIR__ . '/../';

        // ---- DÜZELTME: önek-aware CamelCase -> snake_case
        $toSnake = static function (string $name): string {
            $prefix = '';
            if (strpos($name, 'TN_') === 0) { $prefix = 'tn_'; $name = substr($name, 3); }
            elseif (strpos($name, 'HA_') === 0) { $prefix = 'ha_'; $name = substr($name, 3); }
            elseif (strpos($name, 'RP_') === 0) { $prefix = 'rp_'; $name = substr($name, 3); }

            // CamelCase -> snake_case
            $name = preg_replace('/(?<!^)[A-Z]/', '_$0', $name);
            $name = strtolower($name);
            $name = preg_replace('/_+/', '_', $name); // çift alt çizgileri tekille

            return $prefix . $name;
        };

        $fileBase = $toSnake($className) . '.php';

        $isController = (bool)preg_match('/Controller$/', $className);
        $isModel      = (bool)preg_match('/Model$/', $className);
        $isApi        = (bool)preg_match('/API$/', $className);

        $candidates = [];

        if (strpos($className, 'TN_') === 0) {
            if ($isController) {
                $candidates[] = $basePath . 'controllers/' . $fileBase;
                $candidates[] = $basePath . 'core/'        . $fileBase;
                $candidates[] = $basePath . 'models/'      . $fileBase;
            } elseif ($isModel) {
                $candidates[] = $basePath . 'models/'      . $fileBase;
                $candidates[] = $basePath . 'core/'        . $fileBase;
                $candidates[] = $basePath . 'controllers/' . $fileBase;
            } elseif ($isApi) {
                $candidates[] = $basePath . 'api/'         . $fileBase;
            } else {
                $candidates[] = $basePath . 'core/'        . $fileBase;
                $candidates[] = $basePath . 'controllers/' . $fileBase;
                $candidates[] = $basePath . 'models/'      . $fileBase;
                $candidates[] = $basePath . 'api/'         . $fileBase;
            }
        } elseif (strpos($className, 'HA_') === 0 || strpos($className, 'RP_') === 0) {
            if ($isController) {
                $candidates[] = $basePath . 'controllers/' . $fileBase;
                $candidates[] = $basePath . 'models/'      . $fileBase;
            } elseif ($isModel) {
                $candidates[] = $basePath . 'models/'      . $fileBase;
                $candidates[] = $basePath . 'controllers/' . $fileBase;
            } elseif ($isApi) {
                $candidates[] = $basePath . 'api/'         . $fileBase;
            } else {
                $candidates[] = $basePath . 'models/'      . $fileBase;
                $candidates[] = $basePath . 'controllers/' . $fileBase;
            }
        } else {
            $candidates[] = $basePath . 'controllers/' . $fileBase;
            $candidates[] = $basePath . 'models/'      . $fileBase;
            $candidates[] = $basePath . 'core/'        . $fileBase;
            $candidates[] = $basePath . 'api/'         . $fileBase;
        }

        foreach ($candidates as $p) {
            if ($this->loadFile($p, $className)) {
                return true;
            }
        }
        return false;
    }

    /** Helpers / API */
    public static function addNamespace(string $prefix, string $baseDir): void
    {
        $prefix = rtrim($prefix, '\\') . '\\';
        $baseDir = rtrim($baseDir, '/') . '/';
        self::$namespaces[$prefix] = $baseDir;

        if (self::$debug) {
            error_log("[TN_AUTOLOADER] addNamespace {$prefix} -> {$baseDir}");
        }
    }

    public static function addClass(string $className, string $filePath): void
    {
        self::$classPaths[$className] = $filePath;

        if (self::$debug) {
            error_log("[TN_AUTOLOADER] addClass {$className} -> {$filePath}");
        }
    }

    public static function getLoadedClasses(): array
    {
        return self::$loadedClasses;
    }

    public static function getNamespaces(): array
    {
        return self::$namespaces;
    }

    public static function getClassPaths(): array
    {
        return self::$classPaths;
    }

    public static function classExists(string $className): bool
    {
        return isset(self::$classPaths[$className]) && is_file(self::$classPaths[$className]);
    }

    public static function getStats(): array
    {
        return [
            'loaded_classes'        => count(self::$loadedClasses),
            'registered_namespaces' => count(self::$namespaces),
            'registered_classes'    => count(self::$classPaths),
            'debug_mode'            => self::$debug,
            'memory_usage'          => memory_get_usage(true),
            'peak_memory'           => memory_get_peak_usage(true),
        ];
    }

    public static function debug(): void
    {
        if (!self::$debug) return;

        echo "<div style='background:#f8f9fa;border:1px solid #dee2e6;padding:12px;margin:10px 0;font-family:monospace'>";
        echo "<h4>🔧 TN_Autoloader Debug</h4>";

        echo "<b>Loaded Classes (" . count(self::$loadedClasses) . "):</b><br>";
        foreach (self::$loadedClasses as $c) echo "✅ {$c}<br>";

        echo "<br><b>Namespaces (" . count(self::$namespaces) . "):</b><br>";
        foreach (self::$namespaces as $p => $d) echo "📁 {$p} → {$d}<br>";

        echo "<br><b>ClassPaths (" . count(self::$classPaths) . "):</b><br>";
        foreach (self::$classPaths as $cn => $fp) {
            $ok = is_file($fp) ? '✅' : '❌';
            echo "{$ok} {$cn} → {$fp}<br>";
        }

        echo "</div>";
    }
}

/* -----------------------------------------------------------
 * Manuel test (APP_DEBUG açık ve ?debug_autoloader=1 ise)
 * ----------------------------------------------------------- */
if (defined('APP_DEBUG') && APP_DEBUG && isset($_GET['debug_autoloader'])) {
    $auto = new TN_Autoloader();
    $auto->register();

    TN_Autoloader::debug();

    $stats = TN_Autoloader::getStats();
    echo "<div style='background:#e7f3ff;padding:10px;margin:10px 0'>";
    echo "<b>📊 Autoloader Stats</b><br>";
    foreach ($stats as $k => $v) {
        echo htmlspecialchars($k) . ': ' . (is_numeric($v) ? number_format($v) : htmlspecialchars((string)$v)) . '<br>';
    }
    echo "</div>";
}
