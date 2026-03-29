<div class="max-w-md mx-auto">
    <h1 class="text-lg text-xl-old font-medium mb-4">Đổi mật khẩu</h1>
    <form method="post" class="space-y-4 bg-white rounded-lg  p-4">
        <input type="hidden" hidden name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
        <div class="flex flex-col gap-1">
            <label for="current-password" class="block text-sm text-slate-700">Mật khẩu hiện tại</label>
            <?php ui_input_text('current_password', '', ['id' => 'current-password', 'type' => 'password', 'required' => 'required', 'class' => 'pb-2.5']); ?>
        </div>
        <div class="flex flex-col gap-1">
            <label for="new-password" class="block text-sm text-slate-700">Mật khẩu mới</label>
            <?php ui_input_text('new_password', '', ['id' => 'new-password', 'type' => 'password', 'required' => 'required', 'class' => 'pb-2.5']); ?>
        </div>
        <div class="flex flex-col gap-1">
            <label for="confirm-password" class="block text-sm text-slate-700">Xác nhận mật khẩu mới</label>
            <?php ui_input_text('confirm_password', '', ['id' => 'confirm-password', 'type' => 'password', 'required' => 'required', 'class' => 'pb-2.5']); ?>
        </div>
        <div class="pt-2">
            <?php ui_button_primary('Lưu mật khẩu mới', ['type' => 'submit', 'data-loading-button' => '1']); ?>
        </div>
    </form>
</div>
