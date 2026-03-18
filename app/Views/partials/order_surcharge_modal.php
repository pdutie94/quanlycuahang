<?php
$surchargeTitle = isset($surchargeTitle) ? $surchargeTitle : 'Phụ thu đơn hàng';
?>
<div class="app-modal-overlay" data-order-surcharge-modal>
    <div class="app-modal-sheet-sm">
        <div class="app-modal-header">
            <div class="app-modal-title">
                <?php echo htmlspecialchars($surchargeTitle); ?>
            </div>
            <button type="button" class="app-modal-close" data-order-surcharge-cancel>
                <?php echo ui_icon("x-mark", "h-4 w-4"); ?>
            </button>
        </div>
        <div class="app-modal-body space-y-4">
            <div class="space-y-1">
                <div class="text-sm font-medium text-slate-700">Số tiền phụ thu</div>
                <div class="relative">
                    <input type="text" data-money-input="1" data-order-surcharge-modal-input class="w-full rounded-lg border border-slate-300 bg-white px-3 py-1.5 pr-8 text-right text-sm font-medium text-slate-900 outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500" value="0">
                    <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-sm text-slate-500">đ</span>
                </div>
            </div>
        </div>
        <div class="app-modal-footer">
            <button type="button" class="app-btn-secondary" data-order-surcharge-cancel>Hủy</button>
            <button type="button" class="app-btn-primary" data-order-surcharge-save>Áp dụng</button>
        </div>
    </div>
</div>
