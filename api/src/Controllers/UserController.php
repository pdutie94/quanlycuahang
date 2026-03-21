<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class UserController extends BaseController
{
    public function __construct(private readonly array $config)
    {
    }

    public function changePassword(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $auth = $request->getAttribute('auth');
        $userId = (int) ($auth->sub ?? 0);
        if ($userId <= 0) {
            return $this->error($response, 'Unauthorized', 401);
        }

        $body = (array) ($request->getParsedBody() ?? []);
        $currentPassword = (string) ($body['current_password'] ?? '');
        $newPassword = (string) ($body['new_password'] ?? '');
        $confirmPassword = (string) ($body['confirm_password'] ?? '');

        $errors = [];
        if ($currentPassword === '') {
            $errors['current_password'] = 'Required';
        }
        if ($newPassword === '') {
            $errors['new_password'] = 'Required';
        }
        if ($confirmPassword === '') {
            $errors['confirm_password'] = 'Required';
        }
        if ($newPassword !== '' && strlen($newPassword) < 8) {
            $errors['new_password'] = 'Password must be at least 8 characters';
        }
        if ($newPassword !== '' && $confirmPassword !== '' && $newPassword !== $confirmPassword) {
            $errors['confirm_password'] = 'Password confirmation does not match';
        }

        if (!empty($errors)) {
            return $this->error($response, 'Dữ liệu đổi mật khẩu không hợp lệ', 400, $errors);
        }

        $pdo = Database::getInstance($this->config['db']);
        $stmt = $pdo->prepare('SELECT id, password_hash FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        if (!$user) {
            return $this->error($response, 'Không tìm thấy người dùng', 404);
        }

        if (!password_verify($currentPassword, (string) $user['password_hash'])) {
            return $this->error($response, 'Mật khẩu hiện tại không đúng', 400, [
                'current_password' => 'Current password is incorrect',
            ]);
        }

        $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $updateStmt = $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
        $updateStmt->execute([$newHash, $userId]);

        return $this->success($response, [], 'Đổi mật khẩu thành công');
    }
}
