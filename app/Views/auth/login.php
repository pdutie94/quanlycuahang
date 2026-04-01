<div class="flex justify-center">
    <div class="w-full max-w-sm">
        <h1 class="mb-4 text-center text-xl font-medium tracking-tight">Đăng nhập</h1>
        <?php if (!empty($error)) { ?>
            <div class="mb-3 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700"><?php echo htmlspecialchars($error); ?></div>
        <?php } ?>
        <form method="post" class="rounded-lg bg-white px-4 py-5  border border-slate-200 space-y-4">
            <input type="hidden" hidden name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
            <div class="flex flex-col gap-1">
                <label for="login-username" class="block text-sm text-slate-700">Tài khoản</label>
                <input id="login-username" type="text" name="username" value="admin" required class="form-field block w-full rounded-xl border border-slate-300 bg-white px-3.5 text-sm outline-none ring-0 transition focus:border-brand-500" />
            </div>
            <div class="flex flex-col gap-1">
                <label for="login-password" class="block text-sm text-slate-700">Mật khẩu</label>
                <input id="login-password" type="password" name="password" required autofocus class="form-field block w-full rounded-xl border border-slate-300 bg-white px-3.5 text-sm outline-none ring-0 transition focus:border-brand-500" />
            </div>
            <button type="submit" class="app-btn-primary mt-2 w-full">Đăng nhập</button>
        </form>
    </div>
</div>
