<?php if ($product) { ?>
	<div class="relative" data-header-actions-menu>
		<button type="button" class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-slate-300 bg-white text-slate-600 hover:bg-slate-100" data-header-actions-toggle>
			<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4">
				<path stroke-linecap="round" stroke-linejoin="round" d="M12 6.75a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3ZM12 13.5a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3ZM12 20.25a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3Z" />
			</svg>
		</button>
		<div class="absolute right-0 z-30 mt-2 w-44 rounded-xl border border-slate-200 bg-white py-1 text-sm  overflow-hidden hidden" data-header-actions-dropdown>
			<a href="<?php echo $basePath; ?>/product/delete?id=<?php echo (int) $product['id']; ?>" class="flex items-center gap-2 px-3 py-1.5 text-red-600 hover:bg-rose-50" data-product-delete>
				<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4">
					<path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
				</svg>
				<span>Xóa sản phẩm</span>
			</a>
		</div>
	</div>
<?php } ?>

