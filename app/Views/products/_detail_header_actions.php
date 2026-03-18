<?php if ($product) { ?>
	<div class="relative" data-header-actions-menu>
		<button type="button" class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-slate-300 bg-white text-slate-600 hover:bg-slate-100" data-header-actions-toggle>
			<?php echo ui_icon("ellipsis-vertical", "h-4 w-4"); ?>
		</button>
		<div class="absolute right-0 z-30 mt-2 w-44 rounded-xl border border-slate-200 bg-white py-1 text-sm  overflow-hidden hidden" data-header-actions-dropdown>
			<a href="<?php echo $basePath; ?>/product/delete?id=<?php echo (int) $product['id']; ?>" class="flex items-center gap-2 px-3 py-1.5 text-red-600 hover:bg-rose-50" data-product-delete>
				<?php echo ui_icon("x-mark", "h-4 w-4"); ?>
				<span>Xóa sản phẩm</span>
			</a>
		</div>
	</div>
<?php } ?>

