<?php if (!isset($detailHeader)) { ?>
<div class="mb-3 flex items-center justify-between gap-3">
	<h1 class="text-lg font-medium tracking-tight"><?php echo $supplier ? 'Chỉnh sửa nhà cung cấp' : 'Thêm nhà cung cấp'; ?></h1>
	<div class="flex flex-wrap items-center gap-1.5">
		<a href="<?php echo $basePath; ?>/supplier" class="inline-flex items-center gap-1 rounded-full border border-slate-300 px-2.5 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-100">
			<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4">
				<path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
			</svg>
			<span>Danh sách</span>
		</a>
	</div>
</div>
<?php } ?>

<?php $action = $supplier ? $basePath . '/supplier/update' : $basePath . '/supplier/store'; ?>

<div class="space-y-4">
	<div class="rounded-lg border border-slate-200 bg-white ">
		<form method="post" action="<?php echo $action; ?>" class="px-4 py-4">
		<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>" />
		<?php if ($supplier) { ?>
			<input type="hidden" name="id" value="<?php echo (int) $supplier['id']; ?>" />
		<?php } ?>
			<div class="flex flex-col gap-3">
				<div class="space-y-1 md:col-span-2">
					<label class="block text-sm font-medium text-slate-700">Tên nhà cung cấp</label>
					<?php
					$supplierNameValue = $supplier ? $supplier['name'] : '';
					ui_input_text('name', $supplierNameValue, [
						'required' => 'required',
					]);
					?>
				</div>
				<div class="space-y-1">
					<label class="block text-sm font-medium text-slate-700">Số điện thoại</label>
					<?php
					$supplierPhoneValue = $supplier ? $supplier['phone'] : '';
					ui_input_text('phone', $supplierPhoneValue);
					?>
				</div>
				<div class="space-y-1">
					<label class="block text-sm font-medium text-slate-700">Địa chỉ</label>
					<?php
					$supplierAddressValue = $supplier ? $supplier['address'] : '';
					ui_input_text('address', $supplierAddressValue);
					?>
				</div>
				<div data-floating-actions>
					<?php
					$submitLabel = $supplier ? 'Cập nhật' : 'Lưu';
					ui_button_primary($submitLabel, ['type' => 'submit', 'data-loading-button' => '1', 'data-floating-primary' => '1']);
					?>
				</div>
			</div>
		</form>
	</div>
</div>
