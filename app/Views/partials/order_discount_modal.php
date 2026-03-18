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
                <div class="flex w-full rounded-full bg-slate-100 p-0.5 text-sm text-slate-700" data-order-discount-type-group>
                    <button type="button" class="inline-flex flex-1 items-center justify-center rounded-full px-3 py-1.5 font-medium data-[active=true]:bg-brand-600 data-[active=true]:text-white" data-order-discount-type-option="none">
                        Không
                    </button>
                    <button type="button" class="inline-flex flex-1 items-center justify-center rounded-full px-3 py-1.5 font-medium data-[active=true]:bg-brand-600 data-[active=true]:text-white" data-order-discount-type-option="fixed">
                        Cố định
                    </button>
                    <button type="button" class="inline-flex flex-1 items-center justify-center rounded-full px-3 py-1.5 font-medium data-[active=true]:bg-brand-600 data-[active=true]:text-white" data-order-discount-type-option="percent">
                        Theo %
                    </button>
                </div>
            </div>
                <div class="space-y-3">
                    <div class="space-y-1" data-order-discount-fixed-modal-wrapper>
                    <div class="text-sm font-medium text-slate-700">Số tiền giảm</div>
                    <div class="relative">
                        <input type="text" data-money-input="1" data-order-discount-fixed-modal class="w-full rounded-lg border border-slate-300 bg-white px-3 py-1.5 pr-8 text-right text-sm font-medium text-slate-900 outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500" value="0">
                        <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-sm text-slate-500">đ</span>
                    </div>
                </div>
                <div class="space-y-1" data-order-discount-percent-modal-wrapper>
                    <div class="text-sm font-medium text-slate-700">Phần trăm giảm</div>
                    <div class="relative">
                        <input type="number" min="0" max="100" step="0.01" data-order-discount-percent-modal class="w-full rounded-lg border border-slate-300 bg-white px-3 py-1.5 pr-8 text-right text-sm font-medium text-slate-900 outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500" value="0">
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
