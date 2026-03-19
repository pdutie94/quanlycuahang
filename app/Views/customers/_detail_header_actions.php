<div class="relative" data-header-actions-menu>
	<button type="button" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-300 bg-white text-slate-600 hover:bg-slate-100" data-header-actions-toggle>
		<?php echo ui_icon("ellipsis-vertical", "h-4 w-4"); ?>
	</button>
	<div class="absolute right-0 z-30 mt-2 w-44 rounded-xl border border-slate-200 bg-white py-1 text-sm  overflow-hidden hidden" data-header-actions-dropdown>
		<a href="<?php echo $basePath; ?>/customer/edit?id=<?php echo (int) $customer['id']; ?>" class="flex items-center justify-between gap-2 px-3 py-1.5 text-slate-700 hover:bg-slate-50">
			<div class="flex items-center gap-1.5">
				<?php echo ui_icon("pencil-square", "h-4 w-4 text-slate-500"); ?>
				<span>Chỉnh sửa</span>
			</div>
		</a>
		<form method="post" action="<?php echo $basePath; ?>/customer/delete" onsubmit="return confirm('Xóa khách hàng sẽ chuyển các đơn hàng về khách lẻ. Bạn chắc chắn muốn xóa?');">
			<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>" />
			<input type="hidden" name="id" value="<?php echo (int) $customer['id']; ?>" />
			<button type="submit" class="flex w-full items-center justify-between gap-2 px-3 py-1.5 text-left text-rose-600 hover:bg-rose-50">
				<div class="flex items-center gap-1.5">
					<?php echo ui_icon("trash", "h-4 w-4 text-rose-500"); ?>
					<span>Xóa khách hàng</span>
				</div>
			</button>
		</form>
	</div>
</div>

