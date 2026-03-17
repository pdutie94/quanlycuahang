<?php

class UserController extends Controller
{
    public function changePasswordForm()
    {
        $this->requireLogin();

        $this->render('users/change_password', [
            'title' => 'Đổi mật khẩu',
        ]);
    }

    public function changePassword()
    {
        $this->requireLogin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('user/changePasswordForm');
        }

        $this->verifyCsrfToken();

        $currentUser = isset($_SESSION['user']) ? $_SESSION['user'] : null;
        if (!is_array($currentUser) || !isset($currentUser['id']) || !isset($currentUser['username'])) {
            $this->redirect('login');
        }

        $userId = (int) $currentUser['id'];
        $username = $currentUser['username'];

        $currentPassword = isset($_POST['current_password']) ? $_POST['current_password'] : '';
        $newPassword = isset($_POST['new_password']) ? $_POST['new_password'] : '';
        $confirmPassword = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';

        if ($newPassword === '' || $confirmPassword === '' || $currentPassword === '') {
            $this->setFlash('error', 'Vui lòng nhập đầy đủ thông tin.');
            $this->redirect('user/changePasswordForm');
        }

        if ($newPassword !== $confirmPassword) {
            $this->setFlash('error', 'Mật khẩu mới và xác nhận không khớp.');
            $this->redirect('user/changePasswordForm');
        }

        if (strlen($newPassword) < 8) {
            $this->setFlash('error', 'Mật khẩu mới phải có ít nhất 8 ký tự.');
            $this->redirect('user/changePasswordForm');
        }

        try {
            $pdo = Database::getInstance();
            $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
            $stmt->execute([$userId]);
            $userRow = $stmt->fetch();

            if (!$userRow || !password_verify($currentPassword, $userRow['password_hash'])) {
                $this->setFlash('error', 'Mật khẩu hiện tại không đúng.');
                $this->redirect('user/changePasswordForm');
            }

            $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
            $updateStmt = $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
            $updateStmt->execute([$newHash, $userId]);

            $this->setFlash('success', 'Đã đổi mật khẩu thành công.');
            $this->redirect('dashboard');
        } catch (Exception $e) {
            $this->setFlash('error', 'Không thể đổi mật khẩu. Vui lòng thử lại sau.');
            $this->redirect('user/changePasswordForm');
        }
    }
}
