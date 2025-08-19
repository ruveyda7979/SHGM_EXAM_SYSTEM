<?php
/**
 * SHGM Exam System - Template Engine / View
 */

class TN_View
{
    protected $viewPath;
    protected $layoutPath;
    protected $data = [];
    protected static $globals = [];
    protected $cache = [];
    protected $sections = [];
    protected $currentSection = null;
    protected $debug = false;

    public function __construct()
    {
        $this->viewPath   = defined('VIEW_PATH') ? VIEW_PATH : __DIR__ . '/../views/';
        $this->layoutPath = rtrim($this->viewPath, '/\\') . '/layouts/';
        $this->debug = defined('APP_DEBUG') ? APP_DEBUG : false;

        if ($this->debug) error_log('[TN_VIEW] View engine initialized');
    }

    public function render($view, $data = [], $layout = 'default')
    {
        $this->data = array_merge(self::$globals, $this->data, $data);

        $viewFile = $this->resolveViewPath($view);
        if (!file_exists($viewFile)) throw new Exception("View file not found: {$viewFile}");

        try {
            if ($layout && $layout !== 'none') {
                $this->renderWithLayout($viewFile, $layout);
            } else {
                $this->renderDirect($viewFile);
            }
            if ($this->debug) error_log('[TN_VIEW] View rendered: ' . $view);
        } catch (Exception $e) {
            if ($this->debug) throw $e;
            error_log('[TN_VIEW] Render error: ' . $e->getMessage());
            echo "<div style='color:red;'>Template render error occurred.</div>";
        }
    }

    protected function renderWithLayout($viewFile, $layout)
    {
        $layoutFile = $this->layoutPath . $layout . '.php';
        if (!file_exists($layoutFile)) throw new Exception("Layout file not found: {$layoutFile}");

        ob_start();
        $this->renderDirect($viewFile);
        $content = ob_get_clean();

        $this->data['content'] = $content;

        extract($this->data);
        include $layoutFile;
    }

    protected function renderDirect($viewFile)
    {
        extract($this->data);
        include $viewFile;
    }

    protected function resolveViewPath($view)
    {
        $path = str_replace('.', '/', $view) . '.php';
        return rtrim($this->viewPath, '/\\') . '/' . $path;
    }

    public function partial($partial, $data = [])
    {
        $partialData = array_merge($this->data, $data);
        $partialFile = $this->resolveViewPath($partial);

        if (file_exists($partialFile)) {
            extract($partialData);
            include $partialFile;
        } elseif ($this->debug) {
            echo "<div style='color:red;'>Partial not found: {$partial}</div>";
        }
    }

    // Eski alışkanlıklar için alias
    public function include($view, $data = []) { $this->partial($view, $data); }

    public function section($name)
    {
        $this->currentSection = $name;
        ob_start();
    }

    public function endSection()
    {
        if ($this->currentSection) {
            $this->sections[$this->currentSection] = ob_get_clean();
            $this->currentSection = null;
        }
    }

    // yield yerine
    public function sectionOut($name, $default = '')
    {
        echo isset($this->sections[$name]) ? $this->sections[$name] : $default;
    }

    public static function share($key, $value = null)
    {
        if (is_array($key)) {
            self::$globals = array_merge(self::$globals, $key);
        } else {
            self::$globals[$key] = $value;
        }
    }

    public function with($key, $value = null)
    {
        if (is_array($key)) {
            $this->data = array_merge($this->data, $key);
        } else {
            $this->data[$key] = $value;
        }
        return $this;
    }

    public function exists($view)
    {
        return file_exists($this->resolveViewPath($view));
    }

    public function e($string)
    {
        return htmlspecialchars($string === null ? '' : $string, ENT_QUOTES, 'UTF-8');
    }

    public function raw($string) { return $string; }

    public function url($path = '')
    {
        return function_exists('tn_url') ? tn_url($path) : '/' . ltrim($path, '/');
    }

    public function asset($path)
    {
        return function_exists('tn_asset') ? tn_asset($path) : $this->url('assets/' . ltrim($path, '/'));
    }

    public function css($files)
    {
        if (!is_array($files)) $files = [$files];
        $out = '';
        foreach ($files as $file) {
            $url = $this->asset('css/' . $file . '.css');
            $out .= '<link rel="stylesheet" href="' . $url . '">' . "\n";
        }
        return $out;
    }

    public function js($files)
    {
        if (!is_array($files)) $files = [$files];
        $out = '';
        foreach ($files as $file) {
            $url = $this->asset('js/' . $file . '.js');
            $out .= '<script src="' . $url . '"></script>' . "\n";
        }
        return $out;
    }

    public function csrf()
    {
        $token = isset($this->data['csrf_token']) ? $this->data['csrf_token'] : '';
        return '<input type="hidden" name="csrf_token" value="' . $this->e($token) . '">';
    }

    public function flashMessages()
    {
        $messages = isset($this->data['flash_messages']) ? $this->data['flash_messages'] : [];
        if (empty($messages)) return '';

        $out = '';
        foreach ($messages as $message) {
            $type = $message['type'];
            $text = $this->e($message['message']);
            $cls  = $this->getAlertClass($type);
            $out .= '<div class="alert ' . $cls . ' alert-dismissible fade show" role="alert">';
            $out .= $text;
            $out .= '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
            $out .= '</div>';
        }
        return $out;
    }

    protected function getAlertClass($type)
    {
        $classes = [
            'success' => 'alert-success',
            'error'   => 'alert-danger',
            'warning' => 'alert-warning',
            'info'    => 'alert-info'
        ];
        return isset($classes[$type]) ? $classes[$type] : 'alert-info';
    }

    public function date($date, $format = null)
    {
        if (!$date) return '';
        $fmt = $format ?: (defined('TN_DISPLAY_DATETIME_FORMAT') ? TN_DISPLAY_DATETIME_FORMAT : 'd.m.Y H:i');
        try { $dt = new DateTime($date); return $dt->format($fmt); } catch (Exception $e) { return $date; }
    }

    public function number($number, $decimals = 0)
    {
        return number_format($number, $decimals, ',', '.');
    }

    public function truncate($text, $length = 100, $suffix = '...')
    {
        if (mb_strlen($text) <= $length) return $text;
        return mb_substr($text, 0, $length) . $suffix;
    }

    public function activeClass($route, $class = 'active')
    {
        $current = isset($this->data['current_url']) ? $this->data['current_url'] : '';
        return strpos($current, $route) !== false ? $class : '';
    }

    public function pagination($pagination, $baseUrl = '')
    {
        if (!isset($pagination['total_pages']) || $pagination['total_pages'] <= 1) return '';

        $out = '<nav aria-label="Page navigation"><ul class="pagination justify-content-center">';

        if (!empty($pagination['has_prev'])) {
            $prev = $pagination['current_page'] - 1;
            $out .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '?page=' . $prev . '">Önceki</a></li>';
        }

        $start = max(1, $pagination['current_page'] - 2);
        $end   = min($pagination['total_pages'], $pagination['current_page'] + 2);

        for ($i = $start; $i <= $end; $i++) {
            $active = ($i === (int)$pagination['current_page']) ? ' active' : '';
            $out .= '<li class="page-item' . $active . '"><a class="page-link" href="' . $baseUrl . '?page=' . $i . '">' . $i . '</a></li>';
        }

        if (!empty($pagination['has_next'])) {
            $next = $pagination['current_page'] + 1;
            $out .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '?page=' . $next . '">Sonraki</a></li>';
        }

        $out .= '</ul></nav>';
        return $out;
    }

    public function debugInfo()
    {
        if (!$this->debug) return '';

        $out  = "<div style='background:#f8f9fa;border:1px solid #dee2e6;padding:15px;margin:10px 0;font-family:monospace;'>";
        $out .= "<h4>TN_View Debug Info</h4>";
        $out .= "<strong>View Path:</strong> " . $this->viewPath . "<br>";
        $out .= "<strong>Layout Path:</strong> " . $this->layoutPath . "<br>";

        $out .= "<br><strong>Available Data (" . count($this->data) . "):</strong><br>";
        foreach (array_keys($this->data) as $key) $out .= $key . "<br>";

        $out .= "<br><strong>Global Data (" . count(self::$globals) . "):</strong><br>";
        foreach (array_keys(self::$globals) as $key) $out .= $key . "<br>";

        if (!empty($this->sections)) {
            $out .= "<br><strong>Sections (" . count($this->sections) . "):</strong><br>";
            foreach (array_keys($this->sections) as $section) $out .= $section . "<br>";
        }

        $out .= "</div>";
        return $out;
    }

    public function clearCache()
    {
        $this->cache = [];
        $this->sections = [];
        if ($this->debug) error_log('[TN_VIEW] Cache cleared');
    }

    public function __call($method, $args)
    {
        $helperFunction = 'tn_view_' . $method;
        if (function_exists($helperFunction)) {
            return call_user_func_array($helperFunction, $args);
        }
        throw new Exception('View method not found: ' . $method);
    }

    public function getStats()
    {
        return [
            'view_path'         => $this->viewPath,
            'layout_path'       => $this->layoutPath,
            'data_count'        => count($this->data),
            'global_data_count' => count(self::$globals),
            'sections_count'    => count($this->sections),
            'cache_count'       => count($this->cache),
            'debug_mode'        => $this->debug
        ];
    }
}

/* === Global helpers === */

function view($viewName = null, $data = [], $layout = 'default')
{
    static $viewInstance = null;
    if ($viewInstance === null) $viewInstance = new TN_View();

    if ($viewName) {
        $viewInstance->render($viewName, $data, $layout);
        return null;
    }
    return $viewInstance;
}

function tn_e($string) { return htmlspecialchars($string === null ? '' : $string, ENT_QUOTES, 'UTF-8'); }
function tn_view_url($path = '') { return function_exists('tn_url') ? tn_url($path) : '/' . ltrim($path, '/'); }
function tn_view_asset($path) { return function_exists('tn_asset') ? tn_asset($path) : tn_view_url('assets/' . ltrim($path, '/')); }

function tn_old($key, $default = '')        { return isset($_SESSION['old_input'][$key]) ? $_SESSION['old_input'][$key] : $default; }
function tn_error($key)                      { $e = isset($_SESSION['validation_errors']) ? $_SESSION['validation_errors'] : []; return isset($e[$key]) ? $e[$key] : ''; }
function tn_has_error($key)                  { $e = isset($_SESSION['validation_errors']) ? $_SESSION['validation_errors'] : []; return isset($e[$key]); }
function tn_selected($value, $current)       { return ($value == $current) ? 'selected' : ''; }
function tn_checked($value, $current)        { return ($value == $current) ? 'checked' : ''; }

function tn_format_bytes($bytes, $precision = 2)
{
    $units = ['B', 'KB', 'MB', 'GB', 'TB']; $i = 0;
    while ($bytes > 1024 && $i < count($units) - 1) { $bytes /= 1024; $i++; }
    return round($bytes, $precision) . ' ' . $units[$i];
}

function tn_time_ago($datetime)
{
    $now = new DateTime(); $ago = new DateTime($datetime); $diff = $now->diff($ago);
    if ($diff->d > 0) return $diff->d . ' gün önce';
    if ($diff->h > 0) return $diff->h . ' saat önce';
    if ($diff->i > 0) return $diff->i . ' dakika önce';
    return 'Az önce';
}

function tn_json($data) { return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); }

/* Debug parametresiyle hafif test */
if (defined('APP_DEBUG') && APP_DEBUG && isset($_GET['debug_view'])) {
    echo "<h3>Testing TN_View</h3>";
    $view = new TN_View();
    $view->with('test_data', 'Hello World')->with('user', ['name' => 'Test User', 'role' => 'admin']);
    TN_View::share(['app_name' => 'SHGM Test', 'version' => '1.0.0']);
    echo $view->debugInfo();
    $stats = $view->getStats();
    echo "<pre>" . print_r($stats, true) . "</pre>";
}
