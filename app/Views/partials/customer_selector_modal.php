<?php
$customers = isset($customers) && is_array($customers) ? $customers : [];
?>
<div class="fixed inset-0 z-40 hidden items-center justify-center bg-black/40" data-pos-customer-modal>
    <div class="flex h-full w-full flex-col overflow-hidden bg-white shadow-lg">
        <div class="flex items-center justify-between border-b border-slate-200 px-4 py-2">
            <div class="flex items-center gap-2">
                <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-emerald-50 text-emerald-700">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                    </svg>
                </span>
                <h2 class="text-sm font-medium text-slate-900">Chọn khách hàng</h2>
            </div>
            <button type="button" class="inline-flex h-7 w-7 items-center justify-center rounded-full text-slate-400 hover:bg-slate-100 hover:text-slate-600" data-pos-customer-cancel>
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 0 1 1.414 0L10 8.586l4.293-4.293a1 1 0 1 1 1.414 1.414L11.414 10l4.293 4.293a1 1 0 0 1-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 0 1-1.414-1.414L8.586 10 4.293 5.707a1 1 0 0 1 0-1.414Z" clip-rule="evenodd" />
                </svg>
            </button>
        </div>
        <div class="flex-1 border-b border-slate-200 px-4 py-3 text-sm flex flex-col min-h-0">
            <div class="mb-2 flex items-center">
                <div class="relative flex-1">
                    <input type="text" class="block w-full rounded-lg border border-slate-300 bg-slate-50 px-3 pr-8 py-1.5 text-sm outline-none focus:border-emerald-500 focus:bg-white" placeholder="Tìm theo tên, SĐT, địa chỉ..." autocomplete="off" data-pos-customer-search>
                    <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-slate-400">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35m0 0A7.5 7.5 0 1 0 5.25 5.25a7.5 7.5 0 0 0 11.4 11.4Z" />
                        </svg>
                    </span>
                </div>
            </div>
            <div class="flex-1 overflow-y-auto rounded-lg border border-slate-200" data-pos-customer-list>
            <?php if (empty($customers)) { ?>
                <div class="px-3 py-3 text-center text-sm text-slate-500">
                    Chưa có khách hàng nào.
                </div>
            <?php } else { ?>
                <?php foreach ($customers as $row) { ?>
                    <?php
                    $id = isset($row['id']) ? (int) $row['id'] : 0;
                    if ($id <= 0) {
                        continue;
                    }
                    $name = isset($row['name']) ? $row['name'] : '';
                    $phone = isset($row['phone']) ? $row['phone'] : '';
                    $address = isset($row['address']) ? $row['address'] : '';
                    $searchText = trim(mb_strtolower($name . ' ' . $phone . ' ' . $address));
                    ?>
                    <button type="button" class="flex w-full items-center justify-between gap-2 border-b border-slate-100 px-3 py-2 text-left text-sm text-slate-800" data-pos-customer-item data-customer-id="<?php echo $id; ?>" data-customer-name="<?php echo htmlspecialchars($name); ?>" data-customer-phone="<?php echo htmlspecialchars($phone); ?>" data-customer-address="<?php echo htmlspecialchars($address); ?>" data-search-text="<?php echo htmlspecialchars($searchText); ?>">
                        <div class="flex min-w-0 items-center gap-2">
                            <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-emerald-50 text-emerald-700">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                                </svg>
                            </span>
                            <div class="min-w-0">
                                <div class="truncate font-medium text-slate-900">
                                    <?php echo htmlspecialchars($name); ?><?php if ($phone !== '') { ?> - <?php echo htmlspecialchars($phone); ?><?php } ?>
                                </div>
                                <?php if ($address !== '') { ?>
                                    <div class="mt-0.5 line-clamp-2 text-xs text-slate-500">
                                        <?php echo htmlspecialchars($address); ?>
                                    </div>
                                <?php } ?>
                            </div>
                        </div>
                        <span class="hidden items-center gap-1 text-xs font-medium text-emerald-700" data-pos-customer-selected-indicator>
                            <span class="inline-flex h-4 w-4 items-center justify-center rounded-full bg-emerald-100 text-emerald-700">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-3 w-3">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                </svg>
                            </span>
                            <span>Đã chọn</span>
                        </span>
                    </button>
                <?php } ?>
            <?php } ?>
            </div>
        </div>
        <div class="flex items-center justify-end gap-2 border-t border-slate-200 px-4 py-3">
            <button type="button" class="inline-flex items-center rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-100" data-pos-customer-cancel>Hủy</button>
            <button type="button" class="inline-flex items-center rounded-lg bg-emerald-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-emerald-700 active:bg-emerald-800" data-pos-customer-confirm>Chọn</button>
        </div>
    </div>
</div>
