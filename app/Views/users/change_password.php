<div class="max-w-md mx-auto">
    <h1 class="text-lg text-xl-old font-medium mb-4">Đổi mật khẩu</h1>
    <form method="post" class="space-y-4 bg-white rounded-lg  p-4">
        <input type="hidden" hidden name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
        <div>
            <label class="block text-sm font-medium mb-1">Mật khẩu hiện tại</label>
            <?php ui_input_text('current_password', '', ['type' => 'password', 'required' => 'required']); ?>
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Mật khẩu mới</label>
            <?php ui_input_text('new_password', '', ['type' => 'password', 'required' => 'required']); ?>
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Xác nhận mật khẩu mới</label>
            <?php ui_input_text('confirm_password', '', ['type' => 'password', 'required' => 'required']); ?>
        </div>
        <div class="pt-2">
            <?php ui_button_primary('Lưu mật khẩu mới', ['type' => 'submit', 'data-loading-button' => '1']); ?>
        </div>
    </form>
</div>
