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
            <div class="relative">
                <label class="absolute left-3 top-0 z-10 -translate-y-1/2 bg-white px-1 leading-none text-sm text-slate-700">Số tiền phụ thu</label>
                <div class="relative">
                    <input type="text" data-money-input="1" data-order-surcharge-modal-input class="form-field w-full rounded-xl border border-slate-300 bg-white px-3.5 pt-3 pb-2.5 pr-8 text-right text-sm font-medium text-slate-900 outline-none transition focus:border-brand-500" value="0">
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
