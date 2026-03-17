<?php

class Controller
{
    protected $config;

    public function __construct($config)
    {
        $this->config = $config;
    }

    protected function getCsrfToken()
    {
        if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    protected function verifyCsrfToken()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }

        $sessionToken = isset($_SESSION['csrf_token']) && is_string($_SESSION['csrf_token']) ? $_SESSION['csrf_token'] : '';
        $token = '';
        if (isset($_POST['csrf_token']) && is_string($_POST['csrf_token'])) {
            $token = $_POST['csrf_token'];
        } elseif (isset($_SERVER['HTTP_X_CSRF_TOKEN']) && is_string($_SERVER['HTTP_X_CSRF_TOKEN'])) {
            $token = $_SERVER['HTTP_X_CSRF_TOKEN'];
        }

        if ($sessionToken !== '' && $token !== '' && hash_equals($sessionToken, $token)) {
            return;
        }

        $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'message' => 'Invalid CSRF token',
            ]);
            exit;
        }

        $this->setFlash('error', 'Phiên làm việc đã hết hạn hoặc không hợp lệ. Vui lòng thử lại.');
        $this->redirect('login');
    }

    protected function render($view, $params = [])
    {
        $config = $this->config;
        $flash = isset($_SESSION['flash']) ? $_SESSION['flash'] : null;
        unset($_SESSION['flash']);
        $csrfToken = $this->getCsrfToken();
        extract($params);
        $viewFile = __DIR__ . '/../Views/' . $view . '.php';
        require __DIR__ . '/../Views/layout/main.php';
    }

    protected function renderPartial($view, $params = [])
    {
        $config = $this->config;
        extract($params);
        $viewFile = __DIR__ . '/../Views/' . $view . '.php';
        if (file_exists($viewFile)) {
            require $viewFile;
        } else {
            http_response_code(500);
            echo 'View not found: ' . htmlspecialchars($view, ENT_QUOTES, 'UTF-8');
        }
    }

    protected function redirect($path)
    {
        global $config;
        $base = rtrim($config['base_path'], '/');
        $url = $base . '/' . ltrim($path, '/');
        header('Location: ' . $url);
        exit;
    }

    protected function setFlash($type, $message)
    {
        $_SESSION['flash'] = [
            'type' => $type,
            'message' => $message,
        ];
    }

    protected function requireLogin()
    {
        $timeoutSeconds = 3600;

        if (empty($_SESSION['user'])) {
            $this->redirect('login');
        }

        if (isset($_SESSION['last_activity']) && is_int($_SESSION['last_activity'])) {
            $elapsed = time() - $_SESSION['last_activity'];
            if ($elapsed > $timeoutSeconds) {
                $user = $_SESSION['user'];
                $_SESSION = [];
                if (ini_get('session.use_cookies')) {
                    $params = session_get_cookie_params();
                    setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
                }
                session_destroy();
                $this->redirect('login');
            }
        }

        $_SESSION['last_activity'] = time();
    }
}
