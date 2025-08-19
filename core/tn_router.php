<?php
/**
 * SHGM Exam System - URL Yönlendirme Sistemi (Legacy-safe)
 */

class TN_Router
{
    private $routes = [];
    private $params = [];
    private $middlewares = [];
    private $currentRoute = null;
    private $debug = false;

    public function __construct()
    {
        $this->debug = defined('APP_DEBUG') ? APP_DEBUG : false;
        if ($this->debug) error_log('[TN_ROUTER] Router initialized');
    }

    private function makeRoute($pattern, $controller, $method = 'index', $middlewares = [], $name = null, $http = null)
    {
        return [
            'pattern'     => trim((string)$pattern, '/'),
            'controller'  => (string)$controller,
            'method'      => (string)$method,
            'middlewares' => (array)$middlewares,
            'name'        => $name,
            'params'      => [],
            'http'        => $http ? strtoupper($http) : null, // 'GET' | 'POST' | null=her ikisi
        ];
    }

    public function addRoute($pattern, $controller, $method = 'index', $middlewares = [], $name = null)
    {
        $route = $this->makeRoute($pattern, $controller, $method, $middlewares, $name, null);
        $this->routes[] = $route;
        if ($this->debug) error_log("[TN_ROUTER] Route added: * {$route['pattern']} -> {$controller}::{$method}");
        return $this;
    }

    public function get($pattern, $controller, $method = 'index', $middlewares = [])
    {
        $route = $this->makeRoute($pattern, $controller, $method, $middlewares, null, 'GET');
        $this->routes[] = $route;
        if ($this->debug) error_log("[TN_ROUTER] Route added (GET): {$pattern} -> {$controller}::{$method}");
        return $this;
    }

    public function post($pattern, $controller, $method = 'index', $middlewares = [])
    {
        $route = $this->makeRoute($pattern, $controller, $method, $middlewares, null, 'POST');
        $this->routes[] = $route;
        if ($this->debug) error_log("[TN_ROUTER] Route added (POST): {$pattern} -> {$controller}::{$method}");
        return $this;
    }

    public function group($prefix, $middlewares, $callback)
    {
        $originalCount = count($this->routes);
        call_user_func($callback, $this);

        for ($i = $originalCount; $i < count($this->routes); $i++) {
            if ($prefix) {
                $this->routes[$i]['pattern'] = trim($prefix, '/') . '/' . $this->routes[$i]['pattern'];
                $this->routes[$i]['pattern'] = trim($this->routes[$i]['pattern'], '/');
            }
            $this->routes[$i]['middlewares'] = array_merge((array)$middlewares, $this->routes[$i]['middlewares']);
        }

        if ($this->debug) {
            $added = count($this->routes) - $originalCount;
            error_log("[TN_ROUTER] Route group created: {$prefix} (+{$added} routes)");
        }
        return $this;
    }

    public function dispatch($uri = null)
    {
        if ($uri === null) {
            $uri = isset($_GET['route']) ? $_GET['route'] : '';
        }
        $uri  = trim($uri, '/');
        $http = isset($_SERVER['REQUEST_METHOD']) ? strtoupper($_SERVER['REQUEST_METHOD']) : 'GET';

        if ($this->debug) error_log("[TN_ROUTER] Dispatching: {$http} /{$uri}");

        $matchedRoute = $this->findRoute($uri, $http);
        if (!$matchedRoute) {
            return $this->handleNotFound($uri);
        }

        $this->currentRoute = $matchedRoute;

        if (!$this->runMiddlewares($matchedRoute['middlewares'])) {
            return false;
        }

        return $this->runController($matchedRoute);
    }

    private function findRoute($uri, $http)
    {
        foreach ($this->routes as $route) {
            if ($route['http'] !== null && $route['http'] !== $http) {
                continue;
            }
            if ($this->matchRoute($route['pattern'], $uri)) {
                $route['params'] = $this->params;
                return $route;
            }
        }
        return null;
    }

    private function matchRoute($pattern, $uri)
    {
        if ($pattern === $uri) {
            $this->params = [];
            return true;
        }

        $patternParts = ($pattern === '') ? [] : explode('/', $pattern);
        $uriParts     = ($uri === '')     ? [] : explode('/', $uri);

        if (count($patternParts) !== count($uriParts)) return false;

        $params = [];

        for ($i = 0; $i < count($patternParts); $i++) {
            $pp = $patternParts[$i];
            $up = $uriParts[$i];

            if (preg_match('/^{([a-zA-Z_][a-zA-Z0-9_]*)}$/', $pp, $m)) {
                $params[$m[1]] = $up;
                continue;
            }
            if (preg_match('/^{([a-zA-Z_][a-zA-Z0-9_]*):(.+)}$/', $pp, $m)) {
                $name = $m[1];
                $rgx  = $m[2];
                if (!preg_match('/^' . $rgx . '$/', $up)) return false;
                $params[$name] = $up;
                continue;
            }
            if ($pp !== $up) return false;
        }

        $this->params = $params;
        return true;
    }

    private function runMiddlewares($middlewares)
    {
        foreach ($middlewares as $mw) {
            if (is_string($mw) && class_exists($mw)) {
                $inst = new $mw();
                if (method_exists($inst, 'handle')) {
                    $ok = $inst->handle();
                    if ($ok === false) {
                        if ($this->debug) error_log("[TN_ROUTER] Middleware blocked: {$mw}");
                        return false;
                    }
                }
            } elseif (is_callable($mw)) {
                $ok = call_user_func($mw);
                if ($ok === false) {
                    if ($this->debug) error_log("[TN_ROUTER] Middleware blocked: callable");
                    return false;
                }
            }
        }
        return true;
    }

    private function runController($route)
    {
        $controllerClass = $route['controller'];
        $method          = $route['method'];
        $params          = $route['params'];

        try {
            if (!class_exists($controllerClass)) {
                throw new Exception("Controller class not found: {$controllerClass}");
            }
            $controller = new $controllerClass();

            if (!method_exists($controller, $method)) {
                throw new Exception("Method not found: {$controllerClass}::{$method}");
            }

            if ($this->debug) {
                error_log("[TN_ROUTER] Running: {$controllerClass}::{$method}");
            }

            return call_user_func_array([$controller, $method], array_values($params));

        } catch (Exception $e) {
            if ($this->debug) error_log("[TN_ROUTER] Controller error: " . $e->getMessage());
            return $this->handleError($e);
        }
    }

    private function handleNotFound($uri)
    {
        http_response_code(404);

        $notFoundView = defined('VIEW_PATH') ? VIEW_PATH . 'shared/tn_error_404.php' : null;
        if ($notFoundView && file_exists($notFoundView)) {
            include $notFoundView;
            return;
        }

        echo "<h1>404 - Page Not Found</h1>";
        echo "<p>The requested page '/" . htmlspecialchars($uri) . "' was not found.</p>";
        if ($this->debug) {
            echo "<pre>Requested URI: /" . htmlspecialchars($uri) . "\n";
            echo "Registered Routes:\n";
            foreach ($this->routes as $r) {
                $h = isset($r['http']) && $r['http'] ? $r['http'] : '*';
                echo "  [{$h}] {$r['pattern']} -> {$r['controller']}::{$r['method']}\n";
            }
            echo "</pre>";
        }
    }

    private function handleError($e)
    {
        http_response_code(500);

        if ($this->debug) {
            echo "<div style='background:#ffebee;border:1px solid #f44336;padding:15px;margin:10px'>";
            echo "<h3>Router Error</h3>";
            echo "<strong>Message:</strong> " . htmlspecialchars($e->getMessage()) . "<br>";
            echo "<strong>File:</strong> " . htmlspecialchars($e->getFile()) . "<br>";
            echo "<strong>Line:</strong> " . (int)$e->getLine() . "<br>";
            echo "<strong>Trace:</strong><pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
            echo "</div>";
            return;
        }

        $errorView = defined('VIEW_PATH') ? VIEW_PATH . 'shared/tn_error_500.php' : null;
        if ($errorView && file_exists($errorView)) {
            include $errorView;
        } else {
            echo "<h1>500 - Internal Server Error</h1>";
            echo "<p>An error occurred while processing your request.</p>";
        }
    }

    public function url($name, $params = [])
    {
        foreach ($this->routes as $route) {
            if ($route['name'] === $name) {
                $url = $route['pattern'];
                foreach ($params as $k => $v) {
                    $url = str_replace("{{$k}}", $v, $url);
                }
                if (function_exists('tn_url')) return tn_url($url);
                return '/' . ltrim($url, '/');
            }
        }
        return function_exists('tn_url') ? tn_url() : '/';
    }

    public function getCurrentRoute() { return $this->currentRoute; }
    public function getParams()       { return $this->params; }
    public function getParam($key, $default = null) { return isset($this->params[$key]) ? $this->params[$key] : $default; }
    public function getRoutes()       { return $this->routes; }

    public function routeExists($uri)
    {
        $uri  = trim($uri, '/');
        $http = isset($_SERVER['REQUEST_METHOD']) ? strtoupper($_SERVER['REQUEST_METHOD']) : 'GET';
        return $this->findRoute($uri, $http) !== null;
    }

    public function redirect($url, $code = 302)
    {
        if ($this->debug) error_log("[TN_ROUTER] Redirecting to: {$url} (Code: {$code})");
        header("Location: {$url}", true, $code);
        exit;
    }

    public function debug()
    {
        if (!$this->debug) return;

        echo "<div style='background:#f8f9fa;border:1px solid #dee2e6;padding:15px;margin:10px 0;font-family:monospace'>";
        echo "<h4>TN_Router Debug Info</h4>";
        echo "<strong>Total Routes:</strong> " . count($this->routes) . "<br><br>";
        echo "<table style='width:100%;border-collapse:collapse;font-size:12px'>";
        echo "<tr style='background:#e9ecef'>";
        echo "<th style='border:1px solid #dee2e6;padding:5px;text-align:left;'>HTTP</th>";
        echo "<th style='border:1px solid #dee2e6;padding:5px;text-align:left;'>Pattern</th>";
        echo "<th style='border:1px solid #dee2e6;padding:5px;text-align:left;'>Controller</th>";
        echo "<th style='border:1px solid #dee2e6;padding:5px;text-align:left;'>Method</th>";
        echo "<th style='border:1px solid #dee2e6;padding:5px;text-align:left;'>Middlewares</th>";
        echo "</tr>";

        foreach ($this->routes as $r) {
            $h = isset($r['http']) && $r['http'] ? $r['http'] : '*';
            echo "<tr>";
            echo "<td style='border:1px solid #dee2e6;padding:5px;'>" . htmlspecialchars($h) . "</td>";
            echo "<td style='border:1px solid #dee2e6;padding:5px;'>/" . htmlspecialchars($r['pattern']) . "</td>";
            echo "<td style='border:1px solid #dee2e6;padding:5px;'>" . htmlspecialchars($r['controller']) . "</td>";
            echo "<td style='border:1px solid #dee2e6;padding:5px;'>" . htmlspecialchars($r['method']) . "</td>";
            echo "<td style='border:1px solid #dee2e6;padding:5px;'>" . htmlspecialchars(implode(', ', $r['middlewares'])) . "</td>";
            echo "</tr>";
        }
        echo "</table>";

        if ($this->currentRoute) {
            echo "<br><strong>Current Route:</strong><br>";
            $h = isset($this->currentRoute['http']) && $this->currentRoute['http'] ? $this->currentRoute['http'] : '*';
            echo "HTTP: " . htmlspecialchars($h) . "<br>";
            echo "Pattern: /" . htmlspecialchars($this->currentRoute['pattern']) . "<br>";
            echo "Controller: " . htmlspecialchars($this->currentRoute['controller']) . "<br>";
            echo "Method: " . htmlspecialchars($this->currentRoute['method']) . "<br>";
            if (!empty($this->params)) {
                echo "Parameters: " . htmlspecialchars(json_encode($this->params)) . "<br>";
            }
        }
        echo "</div>";
    }

    public function getStats()
    {
        $controllerCount = count(array_unique(array_column($this->routes, 'controller')));
        $middlewareCount = 0;
        foreach ($this->routes as $r) $middlewareCount += count($r['middlewares']);

        return [
            'total_routes'       => count($this->routes),
            'unique_controllers' => $controllerCount,
            'total_middlewares'  => $middlewareCount,
            'current_route'      => $this->currentRoute ? $this->currentRoute['pattern'] : null,
            'debug_mode'         => $this->debug
        ];
    }
}
