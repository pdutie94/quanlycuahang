<div class="mb-4 flex items-center justify-between gap-3">
    <h1 class="text-lg font-medium tracking-tight">Migration SQL</h1>
</div>

<div class="space-y-4 rounded-lg bg-white px-4 py-4 lg:px-5 lg:py-5 shadow-sm ring-1 ring-slate-100">
    <?php if (!empty($error)) { ?>
        <div class="rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700"><?php echo htmlspecialchars($error); ?></div>
    <?php } ?>

    <?php if (!empty($success)) { ?>
        <div class="rounded-md border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-700">Đã chạy migration thành công.</div>
    <?php } ?>

    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3" data-migration-summary>
        <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm">
            <div class="text-sm uppercase text-slate-500">Phiên bản hiện tại</div>
            <div class="mt-1 font-medium text-slate-800"><?php echo htmlspecialchars($currentVersion); ?></div>
        </div>
        <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm">
            <div class="text-sm uppercase text-slate-500">Phiên bản mới nhất</div>
            <div class="mt-1 font-medium text-slate-800"><?php echo htmlspecialchars($latestVersion); ?></div>
        </div>
        <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm">
            <div class="text-sm uppercase text-slate-500">Số migration chờ</div>
            <div class="mt-1 font-medium text-slate-800"><?php echo count($pendingVersions); ?></div>
        </div>
    </div>

    <div class="border-t border-slate-100 pt-3">
        <h2 class="text-sm font-medium text-slate-800">Danh sách migration</h2>
        <p class="mt-1 text-sm text-slate-500">Các file SQL trong thư mục sql/ sẽ được chạy theo thứ tự version.</p>
    </div>

    <?php if (empty($allVersions)) { ?>
        <div class="mt-2 rounded-lg border border-dashed border-slate-300 bg-slate-50 px-4 py-3 text-sm text-slate-500">
            Chưa có file migration nào trong thư mục sql/.
        </div>
    <?php } else { ?>
        <div class="space-y-3" data-migration-list>
            <?php foreach ($allVersions as $version) { ?>
                <?php $isPending = in_array($version, $pendingVersions, true); ?>
                <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 shadow-sm">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <div class="text-sm uppercase text-slate-500">Phiên bản</div>
                            <div class="mt-0.5 font-mono text-sm font-medium text-slate-900"><?php echo htmlspecialchars($version); ?></div>
                        </div>
                        <div class="flex flex-col items-end gap-2 text-sm">
                            <div>
                                <?php if ($isPending) { ?>
                                    <span class="inline-flex items-center rounded-full bg-amber-50 px-2 py-0.5 text-sm font-medium text-amber-700">Chưa chạy</span>
                                <?php } else { ?>
                                    <span class="inline-flex items-center rounded-full bg-emerald-50 px-2 py-0.5 text-sm font-medium text-emerald-700">Đã chạy hoặc cũ hơn</span>
                                <?php } ?>
                            </div>
                            <div>
                                <button type="button" class="inline-flex items-center rounded-full border border-slate-300 px-3 py-1 text-sm font-medium text-slate-700 hover:bg-slate-100" data-run-migration-version="<?php echo htmlspecialchars($version); ?>">
                                    Chạy lại
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php } ?>
        </div>
    <?php } ?>

    <div class="pt-2 border-t border-slate-100 flex items-center justify-between" data-migration-actions>
        <p class="text-sm text-slate-500">
            Khi bấm nút, các migration chờ sẽ được chạy lần lượt.
        </p>
        <button type="button" class="inline-flex items-center rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-emerald-700 active:bg-emerald-800 disabled:opacity-50 disabled:cursor-not-allowed" data-run-migration <?php echo empty($pendingVersions) ? 'disabled' : ''; ?>>
            Chạy tất cả migration chờ
        </button>
    </div>
</div>
