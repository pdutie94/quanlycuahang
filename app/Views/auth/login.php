<div class="flex justify-center">
    <div class="w-full max-w-sm">
        <h1 class="text-center text-lg text-xl-old font-medium tracking-tight mb-4">Đăng nhập</h1>
        <?php if (!empty($error)) { ?>
            <div class="mb-3 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700"><?php echo htmlspecialchars($error); ?></div>
        <?php } ?>
        <form method="post" class="rounded-lg bg-white px-4 py-5  border border-slate-200 space-y-4">
            <input type="hidden" hidden name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
            <div class="relative">
                <label class="absolute left-3 top-0 z-10 -translate-y-1/2 bg-white px-1 leading-none text-sm text-slate-700">Tài khoản</label>
                <input type="text" name="username" value="admin" required class="form-field block w-full rounded-xl border border-slate-300 bg-white px-3.5 pt-3 pb-2.5 text-sm outline-none ring-0 transition focus:border-brand-500" />
            </div>
            <div class="relative">
                <label class="absolute left-3 top-0 z-10 -translate-y-1/2 bg-white px-1 leading-none text-sm text-slate-700">Mật khẩu</label>
                <input type="password" name="password" required autofocus class="form-field block w-full rounded-xl border border-slate-300 bg-white px-3.5 pt-3 pb-2.5 text-sm outline-none ring-0 transition focus:border-brand-500" />
            </div>
            <button type="submit" class="mt-2 inline-flex h-[34px] min-h-[34px] w-full items-center justify-center rounded-lg bg-brand-600 px-4 text-sm font-medium text-white hover:bg-brand-700 active:bg-brand-800">Đăng nhập</button>
        </form>
    </div>
</div>
