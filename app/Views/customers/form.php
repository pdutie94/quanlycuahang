<?php
$customerId = isset($customer['id']) ? (int) $customer['id'] : 0;
$name = isset($customer['name']) ? $customer['name'] : '';
$phone = isset($customer['phone']) ? $customer['phone'] : '';
$address = isset($customer['address']) ? $customer['address'] : '';
$isEdit = $customerId > 0;
$titleText = $isEdit ? 'Sửa khách hàng' : 'Thêm khách hàng';
$action = $isEdit ? $basePath . '/customer/update' : $basePath . '/customer/store';
$backUrl = $isEdit ? $basePath . '/customer/view?id=' . $customerId : $basePath . '/customer';
?>

<?php if (!isset($detailHeader)) { ?>
<div class="mb-4 flex items-center justify-between gap-3">
	<h1 class="text-lg font-medium tracking-tight"><?php echo $titleText; ?></h1>
	<a href="<?php echo $backUrl; ?>" class="inline-flex items-center gap-1 rounded-lg border border-slate-300 px-2.5 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-100">
		<?php echo ui_icon("chevron-left", "h-4 w-4"); ?>
		<span><?php echo $isEdit ? 'Chi tiết' : 'Danh sách'; ?></span>
	</a>
</div>
<?php } ?>

<form method="post" action="<?php echo $action; ?>" class="space-y-0">
			<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>" />
			<?php if ($isEdit) { ?>
				<input type="hidden" name="id" value="<?php echo $customerId; ?>" />
			<?php } ?>

			<section class="app-form-group flex flex-col gap-4">
				<div class="flex flex-col gap-1">
					<label for="customer-name" class="app-label">Tên khách hàng</label>
					<input id="customer-name" type="text" name="name" value="<?php echo htmlspecialchars($name); ?>" class="form-field block w-full rounded-lg border border-slate-300 bg-slate-50 px-3 py-2 text-sm outline-none focus:border-brand-500 focus:bg-white" required />
				</div>

				<div class="flex flex-col gap-1">
					<label for="customer-phone" class="app-label">Số điện thoại</label>
					<input id="customer-phone" type="text" name="phone" value="<?php echo htmlspecialchars($phone); ?>" class="form-field block w-full rounded-lg border border-slate-300 bg-slate-50 px-3 py-2 text-sm outline-none focus:border-brand-500 focus:bg-white" />
				</div>

				<div class="flex flex-col gap-1">
					<label for="customer-address" class="app-label">Địa chỉ</label>
					<input id="customer-address" type="text" name="address" value="<?php echo htmlspecialchars($address); ?>" class="form-field block w-full rounded-lg border border-slate-300 bg-slate-50 px-3 py-2 text-sm outline-none focus:border-brand-500 focus:bg-white" />
				</div>
			</section>

			<div class="mt-4 border-t border-slate-200 pt-4" data-floating-actions>
				<button type="submit" class="app-btn-primary" data-loading-button="1" data-floating-primary="1">
					<?php echo $isEdit ? 'Lưu thay đổi' : 'Lưu'; ?>
				</button>
			</div>
</form>
