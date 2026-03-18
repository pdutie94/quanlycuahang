<?php
$surchargeTitle = isset($surchargeTitle) ? $surchargeTitle : 'Phụ thu đơn hàng';
?>
<div class="fixed inset-0 z-40 hidden flex items-center justify-center bg-slate-900/40 p-4" data-order-surcharge-modal>
    <div class="w-full max-w-sm rounded-xl bg-white  max-h-full flex flex-col">
        <div class="flex items-center justify-between border-b border-slate-200 px-4 py-2">
            <div class="text-sm font-medium text-slate-900">
                <?php echo htmlspecialchars($surchargeTitle); ?>
            </div>
            <button type="button" class="inline-flex h-7 w-7 items-center justify-center rounded-full text-slate-400 hover:bg-slate-100 hover:text-slate-600" data-order-surcharge-cancel>
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke-width="1.5" stroke="currentColor" class="h-4 w-4">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        <div class="flex-1 overflow-y-auto px-4 py-3 space-y-4 text-sm">
            <div class="space-y-1">
                <div class="text-sm font-medium text-slate-700">Số tiền phụ thu</div>
                <div class="relative">
                    <input type="text" data-money-input="1" data-order-surcharge-modal-input class="w-full rounded-lg border border-slate-300 bg-white px-3 py-1.5 pr-8 text-right text-sm font-medium text-slate-900 outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500" value="0">
                    <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-sm text-slate-500">đ</span>
                </div>
            </div>
        </div>
        <div class="flex items-center justify-end gap-2 border-t border-slate-200 px-4 py-3">
            <button type="button" class="inline-flex items-center rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-100" data-order-surcharge-cancel>Hủy</button>
            <button type="button" class="inline-flex items-center rounded-lg bg-emerald-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-emerald-700 active:bg-emerald-800" data-order-surcharge-save>Áp dụng</button>
        </div>
    </div>
</div>
