<div class="flex justify-center">
    <div class="w-full max-w-sm">
        <h1 class="text-center text-lg text-xl-old font-medium tracking-tight mb-4">Đăng nhập</h1>
        <?php if (!empty($error)) { ?>
            <div class="mb-3 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700"><?php echo htmlspecialchars($error); ?></div>
        <?php } ?>
        <form method="post" class="rounded-lg bg-white px-4 py-5  ring-1 ring-slate-100 space-y-4">
            <input type="hidden" hidden name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
            <div class="space-y-1">
                <label class="block text-sm font-medium text-slate-700">Tài khoản</label>
                <input type="text" name="username" value="admin" required class="form-field block w-full rounded-md border border-slate-300 bg-slate-50 px-3 text-sm outline-none ring-0 focus:border-emerald-500 focus:bg-white" />
            </div>
            <div class="space-y-1">
                <label class="block text-sm font-medium text-slate-700">Mật khẩu</label>
                <input type="password" name="password" required autofocus class="form-field block w-full rounded-md border border-slate-300 bg-slate-50 px-3 text-sm outline-none ring-0 focus:border-emerald-500 focus:bg-white" />
            </div>
            <button type="submit" class="mt-2 inline-flex w-full items-center justify-center rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-medium text-white  hover:bg-emerald-700 active:bg-emerald-800">Đăng nhập</button>
        </form>
    </div>
</div>
