<div class="fixed inset-0 z-40 hidden items-center justify-center bg-black/40" data-product-selector-root>
	<div class="flex h-full w-full flex-col bg-white  overflow-hidden">
        <div class="flex items-center justify-between border-b border-slate-200 px-4 py-2">
            <h2 class="text-sm font-medium text-slate-800">Chọn sản phẩm</h2>
            <button type="button" class="text-slate-400 hover:text-slate-600" data-product-selector-close>
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 0 1 1.414 0L10 8.586l4.293-4.293a1 1 0 1 1 1.414 1.414L11.414 10l4.293 4.293a1 1 0 0 1-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 0 1-1.414-1.414L8.586 10 4.293 5.707a1 1 0 0 1 0-1.414Z" clip-rule="evenodd" />
                </svg>
            </button>
        </div>

		<div class="flex-1 border-b border-slate-200 px-4 py-3 flex flex-col min-h-0">
			<div class="mb-2 flex items-center">
				<div class="relative flex-1">
					<input type="text" class="block w-full rounded-lg border border-slate-300 bg-slate-50 px-3 pr-8 py-1.5 text-sm outline-none focus:border-emerald-500 focus:bg-white" placeholder="Tìm sản phẩm..." data-product-selector-search>
					<button type="button" class="absolute inset-y-0 right-2 flex items-center text-slate-400 hover:text-slate-600 hidden" data-product-selector-clear>
						<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4">
							<path fill-rule="evenodd" d="M4.293 4.293a1 1 0 0 1 1.414 0L10 8.586l4.293-4.293a1 1 0 1 1 1.414 1.414L11.414 10l4.293 4.293a1 1 0 0 1-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 0 1-1.414-1.414L8.586 10 4.293 5.707a1 1 0 0 1 0-1.414Z" clip-rule="evenodd" />
						</svg>
					</button>
				</div>
			</div>
			<div class="flex-1 overflow-y-auto rounded-lg border border-slate-200" data-product-selector-list></div>
		</div>
		
		<div class="flex items-center justify-end gap-2 px-4 py-3">
            <button type="button" class="inline-flex items-center rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-100" data-product-selector-cancel>Hủy</button>
            <button type="button" class="inline-flex items-center rounded-lg bg-emerald-600 px-4 py-1.5 text-sm font-medium text-white hover:bg-emerald-700" data-product-selector-confirm>Xác nhận</button>
        </div>
    </div>
</div>
