<?php
/**
 * SHGM Exam System - File Logger (Pure PHP)
 * Günlük dosyası: storage/logs/{channel}-{Y-m-d}.log
 */

class TN_Logger
{
    /** @var TN_Logger|null */
    private static $instance = null;

    /** @var string */
    private $channel = 'app';

    /** @var string */
    private $logPath;

    /** @var string */
    private $minLevel; // debug|info|notice|warning|error|critical|alert|emergency

    /** @var int  Kaç gün/kaç dosya tutulacak (basit rotasyon) */
    private $maxFiles;

    /** @var bool */
    private $initialized = false;

    private function __construct($channel = 'app')
    {
        $this->channel  = $channel ?: 'app';
        $this->logPath  = rtrim($_ENV['LOG_PATH'] ?? 'storage/logs/', '/\\') . DIRECTORY_SEPARATOR;
        $this->minLevel = strtolower($_ENV['LOG_LEVEL'] ?? 'debug');
        $this->maxFiles = max(1, (int)($_ENV['LOG_MAX_FILES'] ?? 30));
        $this->init();
    }

    /** PSR benzeri: getInstance */
    public static function getInstance($channel = 'app')
    {
        if (self::$instance === null) {
            self::$instance = new TN_Logger($channel);
        } else {
            self::$instance->channel = $channel ?: 'app';
        }
        return self::$instance;
    }

    /** Alias: instance() */
    public static function instance($channel = 'app')
    {
        return self::getInstance($channel);
    }

    /** Kanal değiştirerek kullanmak istersen */
    public function withChannel($channel)
    {
        $this->channel = $channel ?: 'app';
        return $this;
    }

    private function init()
    {
        if ($this->initialized) return;
        if (!is_dir($this->logPath)) {
            @mkdir($this->logPath, 0775, true);
        }
        $this->rotate();
        $this->initialized = true;
    }

    /** Basit dosya rotasyonu: en yeni N dosya kalsın */
    private function rotate()
    {
        if (!is_dir($this->logPath)) return;

        $files = glob($this->logPath . '*.log');
        if (!$files) return;

        usort($files, function($a, $b){ return filemtime($b) <=> filemtime($a); });

        if (count($files) <= $this->maxFiles) return;

        $toDelete = array_slice($files, $this->maxFiles);
        foreach ($toDelete as $f) {
            @unlink($f);
        }
    }

    /** Seviyeyi sayıya çevir (syslog sırası) */
    private function levelWeight($level)
    {
        static $map = [
            'emergency' => 0,
            'alert'     => 1,
            'critical'  => 2,
            'error'     => 3,
            'warning'   => 4,
            'notice'    => 5,
            'info'      => 6,
            'debug'     => 7,
        ];
        $level = strtolower($level);
        return $map[$level] ?? 7;
    }

    private function shouldLog($level)
    {
        return $this->levelWeight($level) <= $this->levelWeight($this->minLevel);
    }

    private function interpolate($message, array $context = [])
    {
        if (strpos($message, '{') === false) return $message;
        $replace = [];
        foreach ($context as $key => $val) {
            if (is_null($val) || is_scalar($val)) {
                $replace['{'.$key.'}'] = (string)$val;
            } elseif ($val instanceof Throwable) {
                $replace['{'.$key.'}'] = $val->getMessage();
            } else {
                $replace['{'.$key.'}'] = json_encode($val, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
            }
        }
        return strtr($message, $replace);
    }

    private function linePrefix($level)
    {
        $ts = date('Y-m-d H:i:s');
        $app = defined('APP_NAME') ? APP_NAME : 'SHGM';
        $env = defined('APP_ENV') ? APP_ENV : ($_ENV['APP_ENV'] ?? 'production');
        $level = strtoupper($level);
        return "[{$ts}] {$app}.{$env}.{$this->channel}.{$level}: ";
    }

    private function logfile()
    {
        $date = date('Y-m-d');
        return $this->logPath . $this->channel . '-' . $date . '.log';
    }

    public function log($level, $message, array $context = [])
    {
        if (!$this->shouldLog($level)) return false;

        $this->init();

        $msg = $this->interpolate($message, $context);
        $line = $this->linePrefix($level) . $msg;

        if (!empty($context)) {
            $line .= ' ' . json_encode($context, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        }
        $line .= PHP_EOL;

        $file = $this->logfile();

        $fp = @fopen($file, 'ab');
        if (!$fp) return false;

        @flock($fp, LOCK_EX);
        fwrite($fp, $line);
        @flock($fp, LOCK_UN);
        fclose($fp);

        return true;
    }

    // PSR-3 benzeri yardımcılar
    public function emergency($m, array $c=[]) { return $this->log('emergency', $m, $c); }
    public function alert($m, array $c=[])     { return $this->log('alert', $m, $c); }
    public function critical($m, array $c=[])  { return $this->log('critical', $m, $c); }
    public function error($m, array $c=[])     { return $this->log('error', $m, $c); }
    public function warning($m, array $c=[])   { return $this->log('warning', $m, $c); }
    public function notice($m, array $c=[])    { return $this->log('notice', $m, $c); }
    public function info($m, array $c=[])      { return $this->log('info', $m, $c); }
    public function debug($m, array $c=[])     { return $this->log('debug', $m, $c); }
}

/** Küçük helper */
function tn_log($level, $message, array $context = [])
{
    return TN_Logger::getInstance()->log($level, $message, $context);
}
