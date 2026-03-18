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
	<a href="<?php echo $backUrl; ?>" class="inline-flex items-center gap-1 rounded-full border border-slate-300 px-2.5 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-100">
		<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4">
			<path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
		</svg>
		<span><?php echo $isEdit ? 'Chi tiết' : 'Danh sách'; ?></span>
	</a>
</div>
<?php } ?>

<div class="space-y-4">
	<div class="rounded-lg border border-slate-200 bg-white ">
		<form method="post" action="<?php echo $action; ?>" class="px-4 py-4">
			<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>" />
			<?php if ($isEdit) { ?>
				<input type="hidden" name="id" value="<?php echo $customerId; ?>" />
			<?php } ?>

			<div class="flex flex-col gap-3">
				<div class="space-y-1">
					<label class="mb-1 block text-sm font-medium text-slate-700">Tên khách hàng</label>
					<input type="text" name="name" value="<?php echo htmlspecialchars($name); ?>" class="form-field block w-full rounded-lg border border-slate-300 bg-slate-50 px-3 py-2 text-sm outline-none focus:border-emerald-500 focus:bg-white" required />
				</div>

				<div class="space-y-1">
					<label class="mb-1 block text-sm font-medium text-slate-700">Số điện thoại</label>
					<input type="text" name="phone" value="<?php echo htmlspecialchars($phone); ?>" class="form-field block w-full rounded-lg border border-slate-300 bg-slate-50 px-3 py-2 text-sm outline-none focus:border-emerald-500 focus:bg-white" />
				</div>

				<div class="space-y-1">
					<label class="mb-1 block text-sm font-medium text-slate-700">Địa chỉ</label>
					<input type="text" name="address" value="<?php echo htmlspecialchars($address); ?>" class="form-field block w-full rounded-lg border border-slate-300 bg-slate-50 px-3 py-2 text-sm outline-none focus:border-emerald-500 focus:bg-white" />
				</div>

				<div class="pt-2" data-floating-actions>
					<button type="submit" class="inline-flex items-center rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white  hover:bg-emerald-700" data-loading-button="1" data-floating-primary="1">
						<?php echo $isEdit ? 'Lưu thay đổi' : 'Lưu'; ?>
					</button>
				</div>
			</div>
		</form>
	</div>
</div>
