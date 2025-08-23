<?php
declare(strict_types=1);

/**
 * SHGM Exam System - PDO Database Service (Singleton)
 * Tek erişim noktası (single accessor) => TN_Database::pdo()
 *
 * .env anahtarları:
 *   DB_HOST, DB_PORT, DB_NAME, DB_USERNAME, DB_PASSWORD,
 *   DB_CHARSET, DB_COLLATION, APP_TIMEZONE
 */

if (!defined('SYSTEM_START_TIME')) {
    define('SYSTEM_START_TIME', microtime(true));
}

/** .env'den DB config üret */
function tn_db_config(): array
{
    $env = $_ENV;

    $cfg = [
        'host'      => defined('DB_HOST') ? DB_HOST : ($env['DB_HOST'] ?? 'localhost'),
        'port'      => (int) (defined('DB_PORT') ? DB_PORT : ($env['DB_PORT'] ?? 3306)),
        'database'  => defined('DB_NAME') ? DB_NAME : ($env['DB_NAME'] ?? 'shgm_exam_system'),
        'username'  => defined('DB_USERNAME') ? DB_USERNAME : ($env['DB_USERNAME'] ?? 'root'),
        'password'  => defined('DB_PASSWORD') ? DB_PASSWORD : ($env['DB_PASSWORD'] ?? ''),
        'charset'   => defined('DB_CHARSET') ? DB_CHARSET : ($env['DB_CHARSET'] ?? 'utf8mb4'),
        'collation' => defined('DB_COLLATION') ? DB_COLLATION : ($env['DB_COLLATION'] ?? 'utf8mb4_unicode_ci'),

        // Güvenli/performanslı PDO ayarları
        'options'   => [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false, // native prepared statements
            PDO::MYSQL_ATTR_INIT_COMMAND => null,  // aşağıda set edilecek
        ],

        // Bağlantı tekrar deneme
        'retry_count' => 3,
        'retry_sleep' => 200, // ms
    ];

    // INIT COMMAND (charset/collation/timezone/strict mode)
    $timezone = defined('APP_TIMEZONE') ? APP_TIMEZONE : ($env['APP_TIMEZONE'] ?? 'Europe/Istanbul');
    $init = [];
    $init[] = "SET NAMES {$cfg['charset']} COLLATE {$cfg['collation']}";
    $init[] = "SET SESSION time_zone = '" . str_replace("'", "''", (new DateTimeZone($timezone))->getName()) . "'";
    $init[] = "SET SESSION sql_mode = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'";

    $cfg['options'][PDO::MYSQL_ATTR_INIT_COMMAND] = implode('; ', $init);
    return $cfg;
}

/** DSN üret */
function tn_db_dsn(): string
{
    $c = tn_db_config();
    return "mysql:host={$c['host']};port={$c['port']};dbname={$c['database']};charset={$c['charset']}";
}

/**
 * TN_Database – PDO Singleton
 * NOT: Tek erişim noktası => TN_Database::pdo()
 */
final class TN_Database
{
    /** @var PDO|null */
    private static ?PDO $pdo = null;

    /** Dahili bağlanıcı */
    private static function connect(): void
    {
        if (self::$pdo instanceof PDO) {
            return;
        }

        $cfg = tn_db_config();
        $dsn = tn_db_dsn();

        $attempts = max(1, (int)$cfg['retry_count']);
        $sleepMs  = max(0, (int)$cfg['retry_sleep']);

        for ($i = 1; $i <= $attempts; $i++) {
            try {
                self::$pdo = new PDO($dsn, $cfg['username'], $cfg['password'], $cfg['options']);
                // Güvence: emulated prepares kapalı
                self::$pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
                // InnoDB tavsiyesi
                @self::$pdo->query("SET SESSION storage_engine=InnoDB");
                return;
            } catch (PDOException $e) {
                if (class_exists('TN_Logger')) {
                    TN_Logger::getInstance()->error('[DB] Connect failed (attempt '.$i.'): '.$e->getMessage());
                }
                if ($i === $attempts) {
                    throw $e;
                }
                if ($sleepMs > 0) {
                    usleep($sleepMs * 1000);
                }
            }
        }
    }

    /** === TEK ERİŞİM NOKTASI === */
    public static function pdo(): PDO
    {
        if (!(self::$pdo instanceof PDO)) {
            self::connect();
        }
        return self::$pdo;
    }

    /** Geriye dönük uyumluluk (pdo ile aynı) */
    public static function getInstance(): PDO
    {
        return self::pdo();
    }

    /** Bağlı mı? */
    public static function isConnected(): bool
    {
        return self::$pdo instanceof PDO;
    }

    /** Basit ping (SELECT 1) */
    public static function ping(): bool
    {
        try {
            self::pdo()->query('SELECT 1');
            return true;
        } catch (Throwable $e) {
            return false;
        }
    }

    /** Bağlantıyı kapat */
    public static function close(): void
    {
        self::$pdo = null;
    }

    /**
     * Transaction helper:
     *   TN_Database::transaction(function(PDO $db){ ... });
     */
    public static function transaction(callable $fn)
    {
        $db = self::pdo();
        $db->beginTransaction();
        try {
            $result = $fn($db);
            $db->commit();
            return $result;
        } catch (Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }
}

/** Kısa yol helper */
function tn_db(): PDO
{
    return TN_Database::pdo();
}

/* ----------------------------------------------------------
 |  Debug: ?debug_db=1 (APP_DEBUG true iken)
 * ---------------------------------------------------------- */
if ((defined('APP_DEBUG') && APP_DEBUG) && isset($_GET['debug_db'])) {
    header('Content-Type: text/html; charset=utf-8');

    echo "<h3>🧪 Database Debug</h3>";
    try {
        $cfg = tn_db_config();
        $pdo = TN_Database::pdo();
        $ok  = TN_Database::ping();

        echo "<pre>";
        echo "DSN: " . htmlspecialchars(tn_db_dsn(), ENT_QUOTES, 'UTF-8') . "\n";
        echo "Host: {$cfg['host']}\n";
        echo "DB:   {$cfg['database']}\n";
        echo "Charset/Collation: {$cfg['charset']} / {$cfg['collation']}\n";
        echo "Connected: " . ($ok ? "YES" : "NO") . "\n";
        echo "</pre>";

        $ver = $pdo->query('SELECT VERSION() AS v')->fetch()['v'] ?? 'unknown';
        echo "<p><strong>MySQL Version:</strong> {$ver}</p>";
        echo "<p style='color:green'>✅ Connection OK</p>";
    } catch (Throwable $e) {
        echo "<p style='color:#b00020'>❌ Connection failed:</p>";
        echo "<pre>".htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8')."</pre>";
    }
    exit;
}
