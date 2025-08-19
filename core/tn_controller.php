<?php
/**
 * SHGM Exam System - Base Controller
 */

abstract class TN_Controller
{
    protected $view;
    protected $request;
    protected $session;
    protected $logger;
    protected $currentUser = null;
    protected $data = [];
    protected $debug = false;

    public function __construct()
    {
        $this->debug = defined('APP_DEBUG') ? APP_DEBUG : false;

        $this->initializeServices();
        $this->prepareRequest();
        $this->initializeAuth();
        $this->prepareViewData();

        if ($this->debug) {
            error_log('[TN_CONTROLLER] ' . get_class($this) . ' initialized');
        }
    }

    protected function initializeServices()
    {
        if (class_exists('TN_View')) {
            $this->view = new TN_View();
        }

        if (class_exists('TN_SessionManager')) {
            $this->session = TN_SessionManager::getInstance();
        }

        if (class_exists('TN_Logger')) {
            $this->logger = TN_Logger::getInstance();
        }
    }

    protected function prepareRequest()
    {
        $this->request = [
            'method'     => isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET',
            'uri'        => isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/',
            'get'        => $_GET,
            'post'       => $_POST,
            'files'      => $_FILES,
            'headers'    => $this->getAllHeaders(),
            'ip'         => $this->getClientIP(),
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '',
            'timestamp'  => time(),
            'is_ajax'    => $this->isAjaxRequest(),
            'is_json'    => $this->isJsonRequest()
        ];
    }

    protected function initializeAuth()
    {
        if ($this->session && method_exists($this->session, 'get')) {
            $userId = $this->session->get('user_id');

            if ($userId && class_exists('TN_UserModel')) {
                try {
                    $userModel = new TN_UserModel();
                    $this->currentUser = $userModel->find($userId);
                } catch (Exception $e) {
                    if ($this->logger) {
                        $this->logger->error('Failed to load current user: ' . $e->getMessage());
                    }
                }
            }
        }
    }

    protected function prepareViewData()
    {
        $this->data = [
            'title'          => defined('APP_NAME') ? APP_NAME : 'App',
            'user'           => $this->currentUser,
            'is_logged_in'   => $this->isLoggedIn(),
            'current_url'    => isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/',
            'base_url'       => defined('APP_URL') ? APP_URL : '/',
            'app_name'       => defined('APP_NAME') ? APP_NAME : 'App',
            'app_version'    => defined('APP_VERSION') ? APP_VERSION : '1.0.0',
            'csrf_token'     => $this->generateCSRFToken(),
            'flash_messages' => $this->getFlashMessages(),
            'debug'          => $this->debug
        ];
    }

    protected function render($viewName, $data = [], $layout = 'default')
    {
        $viewData = array_merge($this->data, $data);

        if ($this->view) {
            $this->view->render($viewName, $viewData, $layout);
        } else {
            $this->renderManual($viewName, $viewData, $layout);
        }

        if ($this->debug && $this->logger) {
            $this->logger->debug("View rendered: {$viewName}");
        }
    }

    private function renderManual($viewName, $data, $layout)
    {
        extract($data);

        $viewFile = VIEW_PATH . str_replace('.', '/', $viewName) . '.php';

        if (file_exists($viewFile)) {
            if ($layout && $layout !== 'none') {
                $layoutFile = VIEW_PATH . "layouts/{$layout}.php";
                if (file_exists($layoutFile)) {
                    ob_start();
                    include $viewFile;
                    $content = ob_get_clean();
                    include $layoutFile;
                } else {
                    include $viewFile;
                }
            } else {
                include $viewFile;
            }
        } else {
            throw new Exception("View file not found: {$viewFile}");
        }
    }

    protected function json($data, $statusCode = 200, $headers = [])
    {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code($statusCode);

        foreach ($headers as $key => $value) {
            header($key . ': ' . $value);
        }

        $response = [
            'success'     => $statusCode >= 200 && $statusCode < 300,
            'status_code' => $statusCode,
            'timestamp'   => date(defined('TN_DATETIME_FORMAT') ? TN_DATETIME_FORMAT : 'Y-m-d H:i:s'),
            'data'        => $data
        ];

        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($this->debug && $this->logger) {
            $this->logger->debug('JSON response sent: ' . json_encode($response));
        }

        exit;
    }

    protected function jsonSuccess($data = [], $message = 'Success')
    {
        $this->json(['message' => $message, 'result' => $data], 200);
    }

    protected function jsonError($message, $code = 400, $errors = [])
    {
        $this->json(['message' => $message, 'errors' => $errors], $code);
    }

    protected function redirect($url, $code = 302, $message = null)
    {
        if ($message) $this->setFlashMessage('info', $message);
        header('Location: ' . $url, true, $code);
        exit;
    }

    protected function redirectBack($fallback = '/')
    {
        $referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $fallback;
        $this->redirect($referer);
    }

    protected function validate($rules, $data = null)
    {
        if ($data === null) {
            $data = isset($this->request['post']) ? $this->request['post'] : [];
        }

        if (class_exists('TN_Validator')) {
            $validator = new TN_Validator();
            return $validator->validate($data, $rules);
        }

        $errors = [];
        foreach ($rules as $field => $rule) {
            if (strpos($rule, 'required') !== false && (!isset($data[$field]) || $data[$field] === '')) {
                $errors[$field] = ucfirst($field) . ' is required';
            }
        }

        return empty($errors) ? true : $errors;
    }

    protected function generateCSRFToken()
    {
        if (!$this->session) return '';

        $token = $this->session->get('csrf_token');
        if (!$token) {
            $token = bin2hex(random_bytes(32));
            $this->session->set('csrf_token', $token);
        }
        return $token;
    }

    protected function validateCSRFToken($token = null)
    {
        if (!$this->session) return false;
        if ($token === null) {
            $token = isset($this->request['post']['csrf_token']) ? $this->request['post']['csrf_token'] : '';
        }
        $sessionToken = $this->session->get('csrf_token');
        return $sessionToken && hash_equals($sessionToken, $token);
    }

    protected function setFlashMessage($type, $message)
    {
        if (!$this->session) return;

        $messages = $this->session->get('flash_messages', []);
        $messages[] = ['type' => $type, 'message' => $message];
        $this->session->set('flash_messages', $messages);
    }

    protected function getFlashMessages()
    {
        if (!$this->session) return [];

        $messages = $this->session->get('flash_messages', []);
        if (method_exists($this->session, 'remove')) {
            $this->session->remove('flash_messages');
        } else {
            $this->session->set('flash_messages', []);
        }

        return $messages;
    }

    protected function isLoggedIn()
    {
        return $this->currentUser !== null;
    }

    protected function hasPermission($permission)
    {
        if (!$this->isLoggedIn()) return false;

        if (isset($this->currentUser['role']) && $this->currentUser['role'] === TN_ROLE_SUPER_ADMIN) {
            return true;
        }

        return $this->checkRolePermission(isset($this->currentUser['role']) ? $this->currentUser['role'] : null, $permission);
    }

    protected function checkRolePermission($role, $permission)
    {
        $permissions = [
            TN_ROLE_ADMIN => [
                'admin.dashboard', 'admin.users', 'admin.students',
                'exam.create', 'exam.edit', 'exam.delete',
                'question.create', 'question.edit', 'question.delete',
                'report.view', 'report.export'
            ],
            TN_ROLE_INSTRUCTOR => [
                'exam.create', 'exam.edit', 'question.create',
                'question.edit', 'report.view'
            ],
            TN_ROLE_STUDENT => [
                'exam.take', 'result.view'
            ]
        ];

        return isset($permissions[$role]) && in_array($permission, $permissions[$role]);
    }

    protected function requireAuth()
    {
        if (!$this->isLoggedIn()) {
            if (isset($this->request['is_ajax']) && $this->request['is_ajax']) {
                $this->jsonError('Authentication required', 401);
            } else {
                $this->redirect(tn_url('auth/login'));
            }
        }
    }

    protected function requirePermission($permission)
    {
        $this->requireAuth();

        if (!$this->hasPermission($permission)) {
            if (isset($this->request['is_ajax']) && $this->request['is_ajax']) {
                $this->jsonError('Permission denied', 403);
            } else {
                $this->render('shared.error_403', ['permission' => $permission]);
            }
        }
    }

    protected function input($key = null, $default = null)
    {
        $get  = isset($this->request['get']) ? $this->request['get'] : [];
        $post = isset($this->request['post']) ? $this->request['post'] : [];
        $allInput = array_merge($get, $post);

        if ($key === null) return $allInput;
        return isset($allInput[$key]) ? $allInput[$key] : $default;
    }

    protected function file($key)
    {
        return isset($this->request['files'][$key]) ? $this->request['files'][$key] : null;
    }

    protected function isAjaxRequest()
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    protected function isJsonRequest()
    {
        $contentType = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '';
        return strpos($contentType, 'application/json') !== false;
    }

    protected function getClientIP()
    {
        $ipKeys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
        foreach ($ipKeys as $key) {
            if (array_key_exists($key, $_SERVER) && !empty($_SERVER[$key])) {
                $ips = explode(',', $_SERVER[$key]);
                return trim($ips[0]);
            }
        }
        return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'unknown';
    }

    protected function getAllHeaders()
    {
        if (function_exists('getallheaders')) {
            return getallheaders();
        }

        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
                $header = str_replace('_', '-', substr($key, 5));
                $headers[$header] = $value;
            }
        }
        return $headers;
    }

    protected function debugInfo()
    {
        if (!$this->debug) return;

        echo "<div style='background:#f8f9fa;border:1px solid #dee2e6;padding:15px;margin:10px 0;font-family:monospace;'>";
        echo "<h4>" . get_class($this) . " Debug Info</h4>";

        echo "<strong>Request Info:</strong><br>";
        echo "Method: " . $this->request['method'] . "<br>";
        echo "URI: " . $this->request['uri'] . "<br>";
        echo "Is AJAX: " . ($this->request['is_ajax'] ? 'Yes' : 'No') . "<br>";
        echo "Is JSON: " . ($this->request['is_json'] ? 'Yes' : 'No') . "<br>";
        echo "IP: " . $this->request['ip'] . "<br>";

        echo "<br><strong>Authentication:</strong><br>";
        echo "Logged In: " . ($this->isLoggedIn() ? 'Yes' : 'No') . "<br>";
        if ($this->currentUser) {
            echo "User: " . (isset($this->currentUser['name']) ? $this->currentUser['name'] : 'N/A') . "<br>";
            echo "Role: " . (isset($this->currentUser['role']) ? $this->currentUser['role'] : 'N/A') . "<br>";
        }

        echo "<br><strong>View Data Keys:</strong><br>";
        echo implode(', ', array_keys($this->data));

        echo "</div>";
    }
}
