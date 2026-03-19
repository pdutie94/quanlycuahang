<div class="app-modal-overlay" data-product-selector-root>
	<div class="app-modal-sheet">
		<div class="app-modal-header">
			<h2 class="app-modal-title">Chọn sản phẩm</h2>
			<button type="button" class="app-modal-close" data-product-selector-close>
                <?php echo ui_icon("x-mark", "h-4 w-4"); ?>
            </button>
        </div>

		<div class="app-modal-body flex flex-col min-h-0">
			<div class="mb-2 flex items-center">
				<div class="relative flex-1">
					<input type="text" class="form-field block w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 pr-8 text-sm outline-none transition focus:border-brand-500" placeholder="Tìm sản phẩm..." data-product-selector-search>
					<button type="button" class="absolute inset-y-0 right-2 flex items-center text-slate-400 hover:text-slate-600 hidden" data-product-selector-clear>
						<?php echo ui_icon("x-mark", "h-4 w-4"); ?>
					</button>
				</div>
			</div>
			<div class="flex-1 overflow-y-auto rounded-lg border border-slate-200" data-product-selector-list></div>
		</div>
		
		<div class="app-modal-footer">
			<button type="button" class="app-btn-secondary" data-product-selector-cancel>Hủy</button>
			<button type="button" class="app-btn-primary" data-product-selector-confirm>Xác nhận</button>
        </div>
    </div>
</div>
