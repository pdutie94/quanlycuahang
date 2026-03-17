<?php

class AuthController extends Controller
{
    public function login()
    {
        if (!empty($_SESSION['user'])) {
            $this->redirect('dashboard');
        }

        $error = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->verifyCsrfToken();
            $username = isset($_POST['username']) ? trim($_POST['username']) : '';
            $password = isset($_POST['password']) ? $_POST['password'] : '';

            try {
                $user = User::findByUsername($username);

                if ($user && password_verify($password, $user['password_hash'])) {
                    $_SESSION['user'] = [
                        'id' => $user['id'],
                        'username' => $user['username'],
                        'name' => $user['name'],
                    ];
                    $_SESSION['last_activity'] = time();
                    $this->redirect('dashboard');
                } else {
                    $error = 'Sai tài khoản hoặc mật khẩu';
                }
            } catch (Exception $e) {
                $error = 'Không thể đăng nhập. Vui lòng thử lại sau.';
            }
        }

        $this->render('auth/login', [
            'title' => 'Đăng nhập',
            'error' => $error,
        ]);
    }

    public function logout()
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
        $this->redirect('login');
    }
}
