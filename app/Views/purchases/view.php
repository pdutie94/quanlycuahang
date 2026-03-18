<?php
$total = isset($purchase['total_amount']) ? (float) $purchase['total_amount'] : 0;
$paid = isset($purchase['paid_amount']) ? (float) $purchase['paid_amount'] : 0;
$debt = $total - $paid;
$noteRaw = isset($purchase['note']) ? $purchase['note'] : '';
$paymentMethodText = '';
if ($noteRaw !== '') {
	$noteTrim = rtrim($noteRaw);
	if (substr($noteTrim, -9) === '[TT:cash]') {
		$paymentMethodText = 'Tiền mặt';
		$noteRaw = rtrim(substr($noteTrim, 0, -9));
	} elseif (substr($noteTrim, -9) === '[TT:bank]') {
		$paymentMethodText = 'Chuyển khoản';
		$noteRaw = rtrim(substr($noteTrim, 0, -9));
	}
}
?>

<?php if (!isset($detailHeader)) { ?>
<div class="mb-4 flex items-center justify-between gap-3">
	<h1 class="text-lg font-medium tracking-tight">Chi tiết phiếu nhập</h1>
	<div class="flex flex-wrap items-center gap-1.5" data-header-actions-root>
		<a href="<?php echo $basePath; ?>/purchase" class="inline-flex items-center gap-1 rounded-full border border-slate-300 bg-white px-2.5 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-100">
			<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4">
				<path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
			</svg>
			<span>Danh sách</span>
		</a>
		<div class="relative" data-header-actions-menu>
			<button type="button" class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-slate-300 bg-white text-slate-600 hover:bg-slate-100" data-header-actions-toggle>
				<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4">
					<path stroke-linecap="round" stroke-linejoin="round" d="M12 6.75a1.5 1.5 0 110-3 1.5 1.5 0 010 3zM12 13.5a1.5 1.5 0 110-3 1.5 1.5 0 010 3zM12 20.25a1.5 1.5 0 110-3 1.5 1.5 0 010 3z" />
				</svg>
			</button>
			<div class="absolute right-0 z-30 mt-2 w-44 rounded-xl border border-slate-200 bg-white py-1 text-sm  overflow-hidden hidden" data-header-actions-dropdown>
				<a href="<?php echo $basePath; ?>/purchase/edit?id=<?php echo (int) $purchase['id']; ?>" class="flex items-center justify-between gap-2 px-3 py-1.5 text-slate-700 hover:bg-slate-50">
					<div class="flex items-center gap-1.5">
						<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4 text-slate-500">
							<path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931ZM16.862 4.487L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
						</svg>
						<span>Sửa phiếu nhập</span>
					</div>
				</a>
				<?php if ($debt > 0) { ?>
					<button type="button" class="flex w-full items-center justify-between gap-2 px-3 py-1.5 text-left text-slate-700 hover:bg-slate-50" data-purchase-payment-open>
						<div class="flex items-center gap-1.5">
							<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4 text-emerald-600">
								<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z" />
							</svg>
							<span>Thanh toán</span>
						</div>
					</button>
				<?php } ?>
			</div>
		</div>
	</div>
</div>
<?php } ?>
<div class="mb-4 rounded-lg border border-slate-200 bg-white px-4 py-3 ">
	<div class="flex flex-col gap-2">
		<div class="flex items-center justify-between gap-3">
			<div>
				<div class="text-sm text-slate-500">Mã phiếu</div>
				<div class="text-sm old-text-base font-mono font-medium text-slate-900"><?php echo htmlspecialchars($purchase['purchase_code']); ?></div>
				<div class="mt-1 text-sm text-slate-600"><?php echo htmlspecialchars(format_datetime($purchase['purchase_date'])); ?></div>
			</div>
			<div class="flex flex-col items-end gap-1 text-sm">
				<div>
					<?php if ($purchase['status'] === 'paid') { ?>
						<span class="inline-flex items-center rounded-full bg-emerald-50 px-3 py-0.5 text-sm font-medium text-emerald-700">Đã thanh toán</span>
					<?php } else { ?>
						<span class="inline-flex items-center rounded-full bg-amber-50 px-3 py-0.5 text-sm font-medium text-amber-700">Còn nợ</span>
					<?php } ?>
				</div>
			</div>
		</div>
		<div class="text-sm text-slate-600">
			<div>
				<span class="text-slate-500">Nhà cung cấp: </span>
				<span class="text-slate-800"><?php echo htmlspecialchars($purchase['supplier_name']); ?></span>
			</div>
			<div class="mt-1">
				<?php if (!empty($purchase['supplier_phone'])) { ?>
					<span class="mr-3">SĐT: <?php echo htmlspecialchars($purchase['supplier_phone']); ?></span>
				<?php } ?>
				<?php if (!empty($purchase['supplier_address'])) { ?>
					<span>Địa chỉ: <?php echo htmlspecialchars($purchase['supplier_address']); ?></span>
				<?php } ?>
			</div>
		</div>
		<div class="mt-2 text-sm text-slate-600">
			<span class="mr-3">
				Tổng: <span class="font-medium text-slate-900"><?php echo Money::format($total); ?></span>
			</span>
			<span class="mr-3">
				Đã trả: <span class="font-medium text-emerald-600"><?php echo Money::format($paid); ?></span>
			</span>
			<span>
				Còn nợ: <span class="font-medium <?php echo $debt > 0 ? 'text-red-600' : 'text-slate-700'; ?>"><?php echo Money::format($debt); ?></span>
			</span>
		</div>
		<?php if ($paymentMethodText !== '') { ?>
			<div class="mt-1 text-sm text-slate-600">
				Thanh toán: <span class="font-medium text-slate-900"><?php echo htmlspecialchars($paymentMethodText); ?></span>
			</div>
		<?php } ?>
		<?php if ($noteRaw !== '') { ?>
			<div class="mt-1 text-sm text-slate-600">
				Ghi chú: <?php echo nl2br(htmlspecialchars($noteRaw)); ?>
			</div>
		<?php } ?>
	</div>
</div>

<?php if (empty($items)) { ?>
	<div class="rounded-lg border border-dashed border-slate-300 bg-white px-4 py-4 text-center text-sm text-slate-500">
		Phiếu nhập không có mặt hàng nào.
	</div>
<?php } else { ?>
		<div class="rounded-lg border border-slate-200 bg-white ">
		<div class="border-b border-slate-100 px-4 py-2 text-sm font-medium text-slate-800">
			Danh sách sản phẩm
		</div>
		<div class="divide-y divide-slate-100">
			<?php foreach ($items as $item) { ?>
				<div class="flex flex-col gap-2 px-4 py-3 text-sm sm:flex-row sm:items-start sm:justify-between">
					<div class="min-w-0">
						<div class="font-medium text-slate-900 truncate"><?php echo htmlspecialchars($item['product_name']); ?></div>
						<div class="mt-1 text-slate-600">
							Số lượng:
							<span class="font-medium text-slate-900"><?php echo rtrim(rtrim(number_format($item['qty'], 2, ',', ''), '0'), ','); ?></span>
							<span class="text-slate-500"><?php echo htmlspecialchars($item['unit_name']); ?></span>
						</div>
					</div>
					<div class="flex flex-col items-end gap-1 text-sm">
						<div>
							<span class="text-slate-500">Giá vốn:</span>
							<span class="font-medium text-slate-900"><?php echo Money::format($item['price_cost']); ?></span>
						</div>
						<div>
							<span class="text-slate-500">Thành tiền:</span>
							<span class="font-medium text-slate-900"><?php echo Money::format($item['amount']); ?></span>
						</div>
					</div>
				</div>
			<?php } ?>
		</div>
	</div>
<?php } ?>

<?php if (!empty($payments)) { ?>
	<div class="mt-4 rounded-lg border border-slate-200 bg-white ">
		<div class="border-b border-slate-100 px-4 py-2 text-sm font-medium text-slate-800">
			Lịch sử thanh toán
		</div>
		<div class="divide-y divide-slate-100">
			<?php foreach ($payments as $payment) { ?>
				<?php
				$amount = isset($payment['amount']) ? (float) $payment['amount'] : 0;
				$timeText = '';
				if (!empty($payment['paid_at'])) {
					$ts = strtotime($payment['paid_at']);
					if ($ts !== false) {
						$timeText = date('H:i, d/m/Y', $ts);
					}
				}
				?>
				<div class="flex flex-col gap-2 px-4 py-3 text-sm sm:flex-row sm:items-start sm:justify-between">
					<div class="min-w-0">
						<div class="flex flex-wrap items-center gap-2">
							<span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-0.5 text-sm font-medium text-emerald-700">
								<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-3.5 w-3.5">
									<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z" />
								</svg>
								<span>Thanh toán</span>
							</span>
							<span class="font-medium text-slate-900">
								<?php echo Money::format($amount); ?>
							</span>
						</div>
						<?php if (!empty($payment['note'])) { ?>
							<div class="mt-1 text-sm text-slate-600">
								<?php echo nl2br(htmlspecialchars($payment['note'])); ?>
							</div>
						<?php } ?>
					</div>
					<div class="flex flex-col items-end gap-1 text-sm text-slate-500">
						<?php if ($timeText !== '') { ?>
							<div class="inline-flex items-center gap-1">
								<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-3.5 w-3.5">
									<path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
								</svg>
								<span><?php echo htmlspecialchars($timeText); ?></span>
							</div>
						<?php } ?>
					</div>
				</div>
			<?php } ?>
		</div>
	</div>
<?php } ?>

<?php if (!empty($logs)) { ?>
	<div class="mt-4 rounded-lg border border-slate-200 bg-white ">
		<div class="flex items-center justify-between border-b border-slate-100 px-4 py-2">
			<div class="flex items-center gap-2 text-sm font-medium text-slate-800">
				<span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-amber-50 text-amber-700">
					<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4">
						<path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m3.75 0a8.25 8.25 0 1 1-16.5 0 8.25 8.25 0 0 1 16.5 0Z" />
					</svg>
				</span>
				<span>Lịch sử phiếu nhập</span>
			</div>
		</div>
		<div class="max-h-64 divide-y divide-slate-100 overflow-y-auto text-sm">
			<?php foreach ($logs as $log) { ?>
				<div class="px-4 py-2">
					<div class="mb-1">
						<span class="inline-flex items-center rounded-full bg-slate-50 px-2 py-0.5 text-sm font-medium text-slate-600">
							<?php echo htmlspecialchars(format_datetime($log['created_at'])); ?>
						</span>
					</div>
					<?php
					$detailRaw = isset($log['detail']) ? $log['detail'] : '';
					$detailDecoded = json_decode($detailRaw, true);
					if (json_last_error() === JSON_ERROR_NONE && is_array($detailDecoded) && isset($detailDecoded['type'])) {
						$type = $detailDecoded['type'];
						if ($type === 'create') {
							$itemsCount = isset($detailDecoded['items_count']) ? (int) $detailDecoded['items_count'] : 0;
							$totalAmount = isset($detailDecoded['total_amount']) ? (float) $detailDecoded['total_amount'] : 0.0;
							$paidAmount = isset($detailDecoded['paid_amount']) ? (float) $detailDecoded['paid_amount'] : 0.0;
							?>
							<div class="leading-relaxed text-sm text-emerald-700">
								Tạo phiếu nhập với <?php echo $itemsCount; ?> mặt hàng, tổng <?php echo Money::format($totalAmount); ?>, đã trả <?php echo Money::format($paidAmount); ?>.
							</div>
							<?php
						} elseif ($type === 'update') {
							$oldTotal = isset($detailDecoded['old_total']) ? (float) $detailDecoded['old_total'] : 0.0;
							$newTotal = isset($detailDecoded['new_total']) ? (float) $detailDecoded['new_total'] : 0.0;
							$oldPaid = isset($detailDecoded['old_paid']) ? (float) $detailDecoded['old_paid'] : 0.0;
							$newPaid = isset($detailDecoded['new_paid']) ? (float) $detailDecoded['new_paid'] : 0.0;
							$itemsCount = isset($detailDecoded['items_count']) ? (int) $detailDecoded['items_count'] : 0;
							?>
							<div class="leading-relaxed text-sm text-slate-700">
								Cập nhật <?php echo $itemsCount; ?> mặt hàng, tổng từ <?php echo Money::format($oldTotal); ?> lên <?php echo Money::format($newTotal); ?>, đã trả từ <?php echo Money::format($oldPaid); ?> lên <?php echo Money::format($newPaid); ?>.
							</div>
							<?php
						} elseif ($type === 'payment') {
							$amount = isset($detailDecoded['amount']) ? (float) $detailDecoded['amount'] : 0.0;
							$methodText = isset($detailDecoded['method']) ? $detailDecoded['method'] : '';
							?>
							<div class="leading-relaxed text-sm text-emerald-700">
								Thanh toán <?php echo Money::format($amount); ?><?php if ($methodText !== '') { ?> (<?php echo htmlspecialchars($methodText); ?>)<?php } ?>
							</div>
							<?php
						} else {
							?>
							<div class="leading-relaxed text-sm text-slate-700"><?php echo htmlspecialchars($detailRaw); ?></div>
							<?php
						}
					} else {
						?>
						<div class="leading-relaxed text-slate-700"><?php echo $detailRaw; ?></div>
						<?php
					}
					?>
				</div>
			<?php } ?>
		</div>
	</div>
<?php } ?>

<?php if ($debt > 0) { ?>
	<div class="fixed inset-0 z-40 hidden items-center justify-center bg-black/40" data-purchase-payment-modal>
		<div class="w-full max-w-md rounded-2xl bg-white ">
			<div class="flex items-center justify-between border-b border-slate-200 px-4 py-3">
				<h2 class="text-sm font-medium text-slate-800">Thanh toán phiếu nhập</h2>
				<button type="button" class="text-slate-400 hover:text-slate-600" data-purchase-payment-close>
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4">
						<path fill-rule="evenodd" d="M4.293 4.293a1 1 0 0 1 1.414 0L10 8.586l4.293-4.293a1 1 0 1 1 1.414 1.414L11.414 10l4.293 4.293a1 1 0 0 1-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 0 1-1.414-1.414L8.586 10 4.293 5.707a1 1 0 0 1 0-1.414Z" clip-rule="evenodd" />
					</svg>
				</button>
			</div>
			<form method="post" action="<?php echo $basePath; ?>/purchase/paymentStore" class="px-4 py-3 space-y-3">
				<input type="hidden" hidden name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
				<input type="hidden" hidden name="purchase_id" value="<?php echo (int) $purchase['id']; ?>">
				<div class="grid grid-cols-3 gap-3 text-sm">
					<div class="rounded-md bg-slate-50 px-3 py-2">
						<div class="text-sm uppercase  text-slate-500">Tổng tiền</div>
						<div class="mt-1 font-medium text-slate-900"><?php echo Money::format($total); ?></div>
					</div>
					<div class="rounded-md bg-emerald-50 px-3 py-2">
						<div class="text-sm uppercase  text-emerald-600">Đã trả</div>
						<div class="mt-1 font-medium text-emerald-700"><?php echo Money::format($paid); ?></div>
					</div>
					<div class="rounded-md bg-slate-50 px-3 py-2">
						<div class="text-sm uppercase  text-slate-500">Còn nợ</div>
						<div class="mt-1 font-medium text-red-600"><?php echo Money::format($debt); ?></div>
					</div>
				</div>
				<div class="space-y-1">
					<label class="block text-sm text-slate-700">Hình thức thanh toán</label>
					<?php
					$paymentMethodField = 'payment_method';
					$paymentMethodValue = 'cash';
					include __DIR__ . '/../partials/payment_method_radios.php';
					?>
				</div>
				<div class="space-y-1">
					<label class="block text-sm text-slate-700">Số tiền thanh toán</label>
					<div class="relative">
						<?php
						$maxAmount = (float) $debt;
						$amountValue = $maxAmount > 0 ? htmlspecialchars(number_format($maxAmount, 0, '', '.')) : '';
						ui_input_text('amount', $amountValue, [
							'inputmode' => 'numeric',
							'data-money-input' => '1',
							'class' => 'pr-8 text-right'
						]);
						?>
						<span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-sm text-slate-500">đ</span>
					</div>
					<p class="text-sm text-slate-500">Tối đa: <?php echo Money::format($debt); ?>.</p>
				</div>
				<div class="space-y-1">
					<label class="block text-sm text-slate-700">Ghi chú</label>
					<textarea name="note" rows="2" class="form-field block w-full rounded-lg border border-slate-300 bg-slate-50 px-3 py-2 text-sm outline-none focus:border-emerald-500 focus:bg-white"></textarea>
				</div>
				<div class="mt-2 flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
					<button type="button" class="inline-flex items-center rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-100" data-purchase-payment-close>Hủy</button>
					<?php ui_button_primary('Xác nhận thanh toán', ['type' => 'submit', 'class' => 'py-1.5', 'data-loading-button' => '1']); ?>
				</div>
			</form>
		</div>
	</div>
<?php } ?>
