<?php if (!isset($detailHeader)) { ?>
<div class="mb-3 flex items-center justify-between gap-3">
	<h1 class="text-lg font-medium tracking-tight"><?php echo $supplier ? 'Chỉnh sửa nhà cung cấp' : 'Thêm nhà cung cấp'; ?></h1>
	<div class="flex flex-wrap items-center gap-1.5">
		<a href="<?php echo $basePath; ?>/supplier" class="inline-flex items-center gap-1 rounded-lg border border-slate-300 px-2.5 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-100">
			<?php echo ui_icon("chevron-left", "h-4 w-4"); ?>
			<span>Danh sách</span>
		</a>
	</div>
</div>
<?php } ?>

<?php $action = $supplier ? $basePath . '/supplier/update' : $basePath . '/supplier/store'; ?>

<form method="post" action="<?php echo $action; ?>" class="space-y-0">
		<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>" />
		<?php if ($supplier) { ?>
			<input type="hidden" name="id" value="<?php echo (int) $supplier['id']; ?>" />
		<?php } ?>
		<section class="app-form-group flex flex-col gap-4">
				<div class="flex flex-col gap-1 md:col-span-2">
					<label for="supplier-name" class="app-label">Tên nhà cung cấp</label>
					<?php
					$supplierNameValue = $supplier ? $supplier['name'] : '';
					   ui_input_text('name', $supplierNameValue, [
						   'id' => 'supplier-name',
						   'required' => 'required',
						   'class' => 'px-3 py-2',
					   ]);
					?>
				</div>
				<div class="flex flex-col gap-1">
					<label for="supplier-phone" class="app-label">Số điện thoại</label>
					<?php
					$supplierPhoneValue = $supplier ? $supplier['phone'] : '';
					   ui_input_text('phone', $supplierPhoneValue, [
						   'id' => 'supplier-phone',
						   'class' => 'px-3 py-2',
					   ]);
					?>
				</div>
				<div class="flex flex-col gap-1">
					<label for="supplier-address" class="app-label">Địa chỉ</label>
					<?php
					$supplierAddressValue = $supplier ? $supplier['address'] : '';
					   ui_input_text('address', $supplierAddressValue, [
						   'id' => 'supplier-address',
						   'class' => 'px-3 py-2',
					   ]);
					?>
				</div>
		</section>
		<div class="mt-4 border-t border-slate-200 pt-4" data-floating-actions>
			<?php
			$submitLabel = $supplier ? 'Cập nhật' : 'Lưu';
			ui_button_primary($submitLabel, ['type' => 'submit', 'data-loading-button' => '1', 'data-floating-primary' => '1']);
			?>
		</div>
</form>
