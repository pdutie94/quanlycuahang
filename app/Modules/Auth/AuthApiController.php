<?php

namespace App\Modules\Auth;

use App\Shared\Response\ApiResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class AuthApiController
{
    public function login(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        if (!empty($_SESSION['user'])) {
            return ApiResponse::success($response, [
                'user' => $_SESSION['user'],
            ]);
        }

        $payload = $this->normalizePayload($request);
        $username = isset($payload['username']) ? trim((string) $payload['username']) : '';
        $password = isset($payload['password']) ? (string) $payload['password'] : '';

        if ($username === '' || $password === '') {
            return ApiResponse::error($response, 'Vui long nhap day du tai khoan va mat khau.', 422);
        }

        try {
            $user = \User::findByUsername($username);
            if (!$user || !password_verify($password, $user['password_hash'])) {
                return ApiResponse::error($response, 'Sai tai khoan hoac mat khau', 422);
            }

            $_SESSION['user'] = [
                'id' => $user['id'],
                'username' => $user['username'],
                'name' => $user['name'],
            ];
            $_SESSION['last_activity'] = time();

            return ApiResponse::success($response, [
                'user' => $_SESSION['user'],
            ], 'Dang nhap thanh cong.');
        } catch (\Exception $e) {
            return ApiResponse::error($response, 'Khong the dang nhap. Vui long thu lai sau.', 500);
        }
    }

    public function me(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return ApiResponse::success($response, [
            'user' => isset($_SESSION['user']) ? $_SESSION['user'] : null,
        ]);
    }

    public function logout(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();

        return ApiResponse::success($response, null, 'Da dang xuat.');
    }

    public function changePassword(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $currentUser = isset($_SESSION['user']) ? $_SESSION['user'] : null;
        if (!is_array($currentUser) || !isset($currentUser['id'])) {
            return ApiResponse::error($response, 'Unauthorized', 401);
        }

        $payload = $this->normalizePayload($request);
        $currentPassword = isset($payload['current_password']) ? (string) $payload['current_password'] : '';
        $newPassword = isset($payload['new_password']) ? (string) $payload['new_password'] : '';
        $confirmPassword = isset($payload['confirm_password']) ? (string) $payload['confirm_password'] : '';

        if ($newPassword === '' || $confirmPassword === '' || $currentPassword === '') {
            return ApiResponse::error($response, 'Vui long nhap day du thong tin.', 422);
        }

        if ($newPassword !== $confirmPassword) {
            return ApiResponse::error($response, 'Mat khau moi va xac nhan khong khop.', 422);
        }

        if (strlen($newPassword) < 8) {
            return ApiResponse::error($response, 'Mat khau moi phai co it nhat 8 ky tu.', 422);
        }

        try {
            $pdo = \Database::getInstance();
            $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
            $stmt->execute([(int) $currentUser['id']]);
            $userRow = $stmt->fetch();

            if (!$userRow || !password_verify($currentPassword, $userRow['password_hash'])) {
                return ApiResponse::error($response, 'Mat khau hien tai khong dung.', 422);
            }

            $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
            $updateStmt = $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
            $updateStmt->execute([$newHash, (int) $currentUser['id']]);

            return ApiResponse::success($response, null, 'Da doi mat khau thanh cong.');
        } catch (\Exception $e) {
            return ApiResponse::error($response, 'Khong the doi mat khau. Vui long thu lai sau.', 500);
        }
    }

    private function normalizePayload(ServerRequestInterface $request): array
    {
        $body = $request->getParsedBody();
        return is_array($body) ? $body : [];
    }
}
