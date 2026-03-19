<?php
$discountTitle = isset($discountTitle) ? $discountTitle : 'Giảm giá đơn hàng';
?>
<div class="app-modal-overlay" data-order-discount-modal>
    <div class="app-modal-sheet-sm">
        <div class="app-modal-header">
            <div class="app-modal-title">
                <?php echo htmlspecialchars($discountTitle); ?>
            </div>
            <button type="button" class="app-modal-close" data-order-discount-cancel>
                <?php echo ui_icon("x-mark", "h-4 w-4"); ?>
            </button>
        </div>
        <div class="app-modal-body space-y-4">
            <div class="space-y-1">
                <div class="text-sm font-medium text-slate-700">Kiểu giảm giá</div>
                <div class="flex w-full rounded-xl bg-slate-100 p-0.5 text-sm text-slate-700" data-order-discount-type-group>
                    <button type="button" class="inline-flex h-[34px] min-h-[34px] flex-1 items-center justify-center rounded-xl px-3 font-medium data-[active=true]:bg-brand-600 data-[active=true]:text-white" data-order-discount-type-option="none">
                        Không
                    </button>
                    <button type="button" class="inline-flex h-[34px] min-h-[34px] flex-1 items-center justify-center rounded-xl px-3 font-medium data-[active=true]:bg-brand-600 data-[active=true]:text-white" data-order-discount-type-option="fixed">
                        Cố định
                    </button>
                    <button type="button" class="inline-flex h-[34px] min-h-[34px] flex-1 items-center justify-center rounded-xl px-3 font-medium data-[active=true]:bg-brand-600 data-[active=true]:text-white" data-order-discount-type-option="percent">
                        Theo %
                    </button>
                </div>
            </div>
            <div class="space-y-4">
                <div class="relative" data-order-discount-fixed-modal-wrapper>
                    <label class="absolute left-3 top-0 z-10 -translate-y-1/2 bg-white px-1 leading-none text-sm text-slate-700">Số tiền giảm</label>
                    <div class="relative">
                        <input type="text" data-money-input="1" data-order-discount-fixed-modal class="form-field w-full rounded-xl border border-slate-300 bg-white px-3.5 pt-3 pb-2.5 pr-8 text-right text-sm font-medium text-slate-900 outline-none transition focus:border-brand-500" value="0">
                        <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-sm text-slate-500">đ</span>
                    </div>
                </div>
                <div class="relative" data-order-discount-percent-modal-wrapper>
                    <label class="absolute left-3 top-0 z-10 -translate-y-1/2 bg-white px-1 leading-none text-sm text-slate-700">Phần trăm giảm</label>
                    <div class="relative">
                        <input type="number" min="0" max="100" step="0.01" data-order-discount-percent-modal class="form-field w-full rounded-xl border border-slate-300 bg-white px-3.5 pt-3 pb-2.5 pr-8 text-right text-sm font-medium text-slate-900 outline-none transition focus:border-brand-500" value="0">
                        <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-sm text-slate-500">%</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="app-modal-footer">
            <button type="button" class="app-btn-secondary" data-order-discount-cancel>Hủy</button>
            <button type="button" class="app-btn-primary" data-order-discount-save>Áp dụng</button>
        </div>
    </div>
</div>
