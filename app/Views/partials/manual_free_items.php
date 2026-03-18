<?php

$manualContext = isset($manualContext) ? $manualContext : 'pos';
$manualItems = isset($manualItems) && is_array($manualItems) ? $manualItems : [];
$isPosManual = $manualContext === 'pos';
?>

<?php if ($isPosManual) { ?>
    <div class="mt-3 space-y-3" data-pos-manual-items-root>
        <div class="flex items-center justify-between gap-2">
            <div class="flex items-center gap-2 text-sm font-medium text-amber-800">
                <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-amber-100 text-amber-700">
                    <?php echo ui_icon("archive-box", "h-4 w-4"); ?>
                </span>
                <span>Sản phẩm khác</span>
            </div>
            <button type="button" class="inline-flex items-center rounded-lg border border-brand-600 px-3 py-1 text-sm font-medium text-brand-700 hover:bg-brand-50" data-pos-manual-add-row>
                <span>Thêm SP</span>
            </button>
        </div>
        <div class="space-y-2" data-pos-manual-items-rows></div>
        <div class="rounded-lg border border-dashed border-slate-300 bg-slate-50 px-3 py-3 text-center text-sm text-slate-500" data-pos-manual-empty>
            Chưa có sản phẩm khác nào.
        </div>
        <div class="hidden" data-pos-manual-row-template>
            <div class="pos-manual-item-row relative cursor-pointer rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-800 " data-pos-manual-card>
                <button type="button" class="absolute -right-1 -top-1 inline-flex h-5 w-5 items-center justify-center rounded-full bg-rose-100 text-rose-600  ring-1 ring-rose-200 hover:bg-rose-200 hover:text-rose-700" data-pos-manual-remove-row>
                    <?php echo ui_icon("x-mark", "h-3 w-3"); ?>
                </button>
                <div class="min-w-0">
                    <div class="text-sm font-medium text-slate-900">
                        <span data-pos-manual-display-name>Chưa nhập tên hàng</span>
                        <span class="ml-1 text-sm text-slate-500" data-pos-manual-display-unit></span>
                    </div>
                    <div class="mt-1 text-sm text-slate-600">
                        <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                            <span>SL: <span class="font-medium" data-pos-manual-display-qty>0</span></span>
                            <span>Giá: <span class="font-medium" data-pos-manual-display-sell>0 đ</span></span>
                            <span>Tổng: <span class="font-medium" data-pos-manual-line-sell-total>0 đ</span></span>
                        </div>
                    </div>
                </div>
                <input type="hidden" name="manual_item_name[]" value="">
                <input type="hidden" name="manual_unit_name[]" value="">
                <input type="hidden" name="manual_qty[]" value="">
                <input type="hidden" name="manual_price_buy[]" value="">
                <input type="hidden" name="manual_price_sell[]" value="">
            </div>
        </div>
    </div>
<?php } else { ?>
    <?php $manualItems = isset($manualItems) && is_array($manualItems) ? $manualItems : []; ?>
    <div class="mt-3 space-y-3" data-order-edit-manual-root>
        <div class="flex items-center justify-between gap-2">
            <div class="flex items-center gap-2 text-sm font-medium text-amber-800">
                <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-amber-100 text-amber-700">
                    <?php echo ui_icon("archive-box", "h-4 w-4"); ?>
                </span>
                <span>Sản phẩm khác</span>
            </div>
            <button type="button" class="inline-flex items-center rounded-lg border border-brand-600 px-3 py-1 text-sm font-medium text-brand-700 hover:bg-brand-50" data-order-edit-manual-add-row>
                <span>Thêm SP</span>
            </button>
        </div>
        <div class="space-y-2" data-order-edit-manual-rows>
            <?php if (!empty($manualItems)) { ?>
                <?php foreach ($manualItems as $row) { ?>
                    <?php
                    $name = isset($row['item_name']) ? $row['item_name'] : '';
                    $unitName = isset($row['unit_name']) ? $row['unit_name'] : '';
                    $qtyVal = isset($row['qty']) ? (float) $row['qty'] : 0.0;
                    $qtyText = rtrim(rtrim(number_format($qtyVal, 2, ',', ''), '0'), ',');
                    $priceBuy = isset($row['price_buy']) ? (float) $row['price_buy'] : 0.0;
                    $priceSell = isset($row['price_sell']) ? (float) $row['price_sell'] : 0.0;
                    $amountSell = isset($row['amount_sell']) ? (float) $row['amount_sell'] : 0.0;
                    $displayPriceSell = $priceSell > 0 ? Money::format($priceSell) . ' đ' : '0 đ';
                    $displayAmountSell = $amountSell > 0 ? Money::format($amountSell) . ' đ' : '0 đ';
                    ?>
                    <div class="order-edit-manual-item-row relative cursor-pointer rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-800 ">
                        <button type="button" class="absolute -right-1 -top-1 inline-flex h-5 w-5 items-center justify-center rounded-full bg-rose-100 text-rose-600  ring-1 ring-rose-200 hover:bg-rose-200 hover:text-rose-700" data-order-edit-manual-remove-row>
                            <?php echo ui_icon("x-mark", "h-3 w-3"); ?>
                        </button>
                        <div class="min-w-0 pr-6">
                            <div class="text-sm font-medium text-slate-900">
                                <span data-order-edit-manual-display-name><?php echo $name !== '' ? htmlspecialchars($name) : 'Chưa nhập tên hàng'; ?></span>
                                <span class="ml-1 text-sm text-slate-500" data-order-edit-manual-display-unit><?php echo $unitName !== '' ? ' - ' . htmlspecialchars($unitName) : ''; ?></span>
                            </div>
                            <div class="mt-1 text-sm text-slate-600">
                                <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                                    <span>SL: <span class="font-medium" data-order-edit-manual-display-qty><?php echo $qtyText !== '' ? htmlspecialchars($qtyText) : '0'; ?></span></span>
                                    <span>Giá: <span class="font-medium" data-order-edit-manual-display-sell><?php echo htmlspecialchars($displayPriceSell); ?></span></span>
                                    <span>Tổng: <span class="font-medium text-brand-600" data-order-edit-manual-amount><?php echo htmlspecialchars($displayAmountSell); ?></span></span>
                                </div>
                            </div>
                        </div>
                        <input type="hidden" name="manual_item_name[]" value="<?php echo htmlspecialchars($name); ?>">
                        <input type="hidden" name="manual_unit_name[]" value="<?php echo htmlspecialchars($unitName); ?>">
                        <input type="hidden" name="manual_qty[]" value="<?php echo htmlspecialchars($qtyText); ?>">
                        <input type="hidden" name="manual_price_buy[]" value="<?php echo $priceBuy > 0 ? htmlspecialchars(number_format($priceBuy, 0, '', '.')) : ''; ?>">
                        <input type="hidden" name="manual_price_sell[]" value="<?php echo $priceSell > 0 ? htmlspecialchars(number_format($priceSell, 0, '', '.')) : ''; ?>">
                    </div>
                <?php } ?>
            <?php } ?>
        </div>
        <div class="mt-2 rounded-lg border border-dashed border-slate-300 bg-slate-50 px-3 py-3 text-center text-sm text-slate-500<?php echo !empty($manualItems) ? ' hidden' : ''; ?>" data-order-edit-manual-empty>
            Chưa có sản phẩm khác nào.
        </div>
        <div class="hidden" data-order-edit-manual-row-template>
            <div class="order-edit-manual-item-row relative cursor-pointer rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-800 ">
                <button type="button" class="absolute -right-1 -top-1 inline-flex h-5 w-5 items-center justify-center rounded-full bg-rose-100 text-rose-600  ring-1 ring-rose-200 hover:bg-rose-200 hover:text-rose-700" data-order-edit-manual-remove-row>
                    <?php echo ui_icon("x-mark", "h-3 w-3"); ?>
                </button>
                <div class="min-w-0 pr-6">
                    <div class="text-sm font-medium text-slate-900">
                        <span data-order-edit-manual-display-name>Chưa nhập tên hàng</span>
                        <span class="ml-1 text-sm text-slate-500" data-order-edit-manual-display-unit></span>
                    </div>
                    <div class="mt-1 text-sm text-slate-600">
                        <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                            <span>SL: <span class="font-medium" data-order-edit-manual-display-qty>0</span></span>
                            <span>Giá: <span class="font-medium" data-order-edit-manual-display-sell>0 đ</span></span>
                            <span>Tổng: <span class="font-medium text-brand-600" data-order-edit-manual-amount>0 đ</span></span>
                        </div>
                    </div>
                </div>
                <input type="hidden" name="manual_item_name[]" value="">
                <input type="hidden" name="manual_unit_name[]" value="">
                <input type="hidden" name="manual_qty[]" value="">
                <input type="hidden" name="manual_price_buy[]" value="">
                <input type="hidden" name="manual_price_sell[]" value="">
            </div>
        </div>
    </div>
<?php } ?>
