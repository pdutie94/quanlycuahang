<?php
$customers = isset($customers) && is_array($customers) ? $customers : [];
?>
<div class="app-modal-overlay" data-pos-customer-modal>
    <div class="app-modal-sheet">
        <div class="app-modal-header">
            <div class="flex items-center gap-2">
                <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-brand-50 text-brand-700">
                    <?php echo ui_icon("user", "h-4 w-4"); ?>
                </span>
                <h2 class="app-modal-title">Chọn khách hàng</h2>
            </div>
            <button type="button" class="app-modal-close" data-pos-customer-cancel>
                <?php echo ui_icon("x-mark", "h-4 w-4"); ?>
            </button>
        </div>
        <div class="app-modal-body flex flex-col min-h-0">
            <div class="mb-2 flex items-center">
                <div class="relative flex-1">
                    <input type="text" class="form-field block w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 pr-8 text-sm outline-none transition focus:border-brand-500" placeholder="Tìm theo tên, SĐT, địa chỉ..." autocomplete="off" data-pos-customer-search>
                    <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-slate-400">
                        <?php echo ui_icon("magnifying-glass-alt", "h-4 w-4"); ?>
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
                            <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-brand-50 text-brand-700">
                                <?php echo ui_icon("user", "h-4 w-4"); ?>
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
                        <span class="hidden items-center gap-1 text-xs font-medium text-brand-700" data-pos-customer-selected-indicator>
                            <span class="inline-flex h-4 w-4 items-center justify-center rounded-full bg-brand-100 text-brand-700">
								<?php echo ui_icon("check", "h-3 w-3"); ?>
                            </span>
                            <span>Đã chọn</span>
                        </span>
                    </button>
                <?php } ?>
            <?php } ?>
            </div>
        </div>
        <div class="app-modal-footer">
            <button type="button" class="app-btn-secondary" data-pos-customer-cancel>Hủy</button>
            <button type="button" class="app-btn-primary" data-pos-customer-confirm>Chọn</button>
        </div>
    </div>
</div>
