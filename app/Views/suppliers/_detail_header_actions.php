<div class="relative" data-header-actions-menu>
	<button type="button" class="inline-flex h-10 w-10 items-center justify-center text-slate-600 hover:text-slate-800" data-header-actions-toggle>
		<?php echo ui_icon("ellipsis-vertical", "h-4 w-4"); ?>
	</button>
	<div class="absolute right-0 z-30 mt-2 w-44 rounded-xl border border-slate-200 bg-white py-1 text-sm  overflow-hidden hidden" data-header-actions-dropdown>
		<a href="<?php echo $basePath; ?>/supplier/delete?id=<?php echo $supplier['id']; ?>" onclick="return confirm('Xóa nhà cung cấp này?');" class="flex items-center justify-between gap-2 px-3 py-1.5 text-rose-600 hover:bg-rose-50">
			<div class="flex items-center gap-1.5">
				<?php echo ui_icon("trash", "h-4 w-4 text-rose-500"); ?>
				<span>Xóa nhà cung cấp</span>
			</div>
		</a>
	</div>
</div>

