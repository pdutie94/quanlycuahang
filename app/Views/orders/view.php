<?php
$orderStatus = isset($order['order_status']) ? $order['order_status'] : 'pending';
$totalAmount = isset($order['total_amount']) ? (float) $order['total_amount'] : 0.0;
$totalCost = isset($order['total_cost']) ? (float) $order['total_cost'] : 0.0;
$discountAmount = isset($order['discount_amount']) ? (float) $order['discount_amount'] : 0.0;
if ($discountAmount < 0) {
    $discountAmount = 0.0;
}
$surchargeAmount = isset($order['surcharge_amount']) ? (float) $order['surcharge_amount'] : 0.0;
if ($surchargeAmount < 0) {
    $surchargeAmount = 0.0;
}
$grossAmount = $totalAmount + $discountAmount - $surchargeAmount;
if ($grossAmount < 0) {
    $grossAmount = 0.0;
}
$subtotalAmount = $totalAmount + $discountAmount;
$profitOrder = $totalAmount - $totalCost;
$remaining = $totalAmount - (isset($order['paid_amount']) ? (float) $order['paid_amount'] : 0.0);
if ($remaining < 0) {
    $remaining = 0.0;
}
$manualItems = isset($manualItems) && is_array($manualItems) ? $manualItems : [];
?>

<div class="space-y-4">
	<?php $orderDateFormatted = date('H:i, d/m/Y', strtotime($order['order_date'])); ?>
	<div class="rounded-lg border border-slate-200 bg-white ">
		<div class="flex items-center justify-between border-b border-slate-100 px-4 py-2">
			<div class="flex items-center gap-2 text-sm font-medium text-slate-800">
				<span class="inline-flex h-7 w-7 items-center justify-center rounded-lg bg-brand-50 text-brand-700">
					<?php echo ui_icon("clipboard-document", "h-4 w-4"); ?>
				</span>
				<span class="text-sm font-medium text-slate-900 sm:text-sm">#<?php echo htmlspecialchars($order['order_code']); ?></span>
			</div>
		</div>
		<div class="px-4 py-2">
			<div class="flex flex-wrap items-center gap-2 text-sm sm:text-sm text-slate-600">
				<span class="inline-flex items-center rounded-lg bg-sky-50 px-2.5 py-0.5 text-sm font-medium text-sky-700">
					<span><?php echo htmlspecialchars($orderDateFormatted); ?></span>
				</span>
				<span class="inline-flex items-center rounded-lg px-2.5 py-0.5 text-sm font-medium <?php echo $order['status'] === 'paid' ? 'bg-brand-50 text-brand-700' : 'bg-amber-50 text-amber-700'; ?>">
					<?php echo $order['status'] === 'paid' ? 'Đã thanh toán' : 'Còn nợ'; ?>
				</span>
				<span class="inline-flex items-center rounded-lg px-2.5 py-0.5 text-sm font-medium
					<?php
					if ($orderStatus === 'completed') {
						echo 'bg-brand-50 text-brand-700';
					} elseif ($orderStatus === 'cancelled') {
						echo 'bg-rose-50 text-rose-700';
					} else {
						echo 'bg-sky-50 text-sky-700';
					}
					?>
				">
					<?php
					if ($orderStatus === 'completed') {
						echo 'Đã hoàn thành';
					} elseif ($orderStatus === 'cancelled') {
						echo 'Đã hủy';
					} else {
						echo 'Chưa hoàn thành';
					}
					?>
				</span>
			</div>
			<div class="mt-3 space-y-2 text-sm">
				<div class="flex items-center justify-between">
						<div class="text-sm font-medium uppercase  text-slate-500">Tổng vốn</div>
					<div class="text-sm old-text-base font-medium text-slate-900"><?php echo Money::format($totalCost); ?></div>
				</div>

				<div class="space-y-0.5">
					<div class="flex items-center justify-between">
						<div class="text-sm font-medium uppercase  text-slate-500">Tổng bán (gốc)</div>
						<div class="text-sm old-text-base font-medium text-slate-900"><?php echo Money::format($grossAmount); ?></div>
					</div>
					<?php if ($discountAmount > 0) { ?>
						<div class="flex items-center justify-between pl-2">
							<span class="text-sm text-rose-600">Giảm giá</span>
							<span class="text-sm font-medium text-rose-600">-<?php echo Money::format($discountAmount); ?></span>
						</div>
					<?php } ?>
					<?php if ($surchargeAmount > 0) { ?>
						<div class="flex items-center justify-between pl-2">
							<span class="text-sm text-sky-600">Phụ thu</span>
							<span class="text-sm font-medium text-sky-600">+<?php echo Money::format($surchargeAmount); ?></span>
						</div>
					<?php } ?>
				</div>

				<div class="mt-1 flex items-center justify-between">
					<div class="text-sm font-medium uppercase  text-slate-500">Tổng bán thực tế</div>
					<div class="text-sm old-text-base font-medium text-slate-900"><?php echo Money::format($totalAmount); ?></div>
				</div>

				<div class="mt-3 flex items-center justify-between rounded-md px-3 py-2 <?php echo $profitOrder >= 0 ? 'bg-brand-50' : 'bg-rose-50'; ?>">
					<div class="flex items-center gap-2">
						<span class="inline-flex h-6 w-6 items-center justify-center rounded-lg <?php echo $profitOrder >= 0 ? 'bg-brand-600 text-white' : 'bg-rose-600 text-white'; ?>">
							<?php echo ui_icon("check", "h-3.5 w-3.5"); ?>
						</span>
						<span class="text-sm font-medium uppercase  <?php echo $profitOrder >= 0 ? 'text-brand-700' : 'text-rose-700'; ?>">Lợi nhuận</span>
					</div>
					<div class="text-sm old-text-base font-medium <?php echo $profitOrder >= 0 ? 'text-brand-700' : 'text-rose-700'; ?>">
						<?php echo ($profitOrder >= 0 ? '+' : '') . Money::format($profitOrder); ?>
					</div>
				</div>

				<div class="mt-2 grid grid-cols-2 gap-2 text-sm">
					<div class="rounded-md bg-slate-50 px-3 py-2">
						<div class="text-sm font-medium uppercase  text-slate-500">Đã thu</div>
						<div class="mt-1 text-sm old-text-base font-medium text-slate-900"><?php echo Money::format($order['paid_amount']); ?></div>
					</div>
					<div class="rounded-md bg-amber-50 px-3 py-2">
						<div class="text-sm font-medium uppercase  text-amber-700">Còn nợ</div>
						<div class="mt-1 text-sm old-text-base font-medium text-amber-700"><?php echo Money::format($remaining); ?></div>
					</div>
				</div>
			</div>
			<div class="mt-3 border-t border-slate-100 pt-3 text-sm text-slate-700">
				<form method="post" action="<?php echo $basePath; ?>/order/updateStatus" class="flex flex-wrap items-center gap-2">
					<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
					<input type="hidden" name="id" value="<?php echo $order['id']; ?>">
					<span class="text-slate-500 hidden sm:block">Trạng thái:</span>
					<div class="relative">
						<select name="order_status" class="form-field block w-full appearance-none rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 pr-8 text-sm outline-none transition focus:border-brand-500" data-no-select2>
							<option value="pending" <?php echo $orderStatus === 'pending' ? 'selected' : ''; ?>>Chưa hoàn thành</option>
							<option value="completed" <?php echo $orderStatus === 'completed' ? 'selected' : ''; ?>>Đã hoàn thành</option>
							<option value="cancelled" <?php echo $orderStatus === 'cancelled' ? 'selected' : ''; ?>>Đã hủy</option>
						</select>
						<span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2 text-slate-400">
							<?php echo ui_icon("chevron-down", "h-4 w-4"); ?>
						</span>
					</div>
					<button type="submit" class="inline-flex h-[34px] min-h-[34px] items-center gap-1.5 rounded-lg border border-brand-600 bg-brand-600 px-4 text-sm font-medium text-white hover:border-brand-700 hover:bg-brand-700">
						<?php echo ui_icon("arrow-path", "h-4 w-4"); ?>
						<span>Cập nhật</span>
					</button>
				</form>
			</div>
		</div>
	</div>

	<div class="rounded-lg border border-slate-200 bg-white ">
		<div class="flex items-center justify-between border-b border-slate-100 px-4 py-2">
			<div class="flex items-center gap-2 text-sm font-medium text-slate-800">
				<span class="inline-flex h-7 w-7 items-center justify-center rounded-lg bg-amber-50 text-amber-700">
						<?php echo ui_icon("user-group", "h-4 w-4"); ?>
				</span>
				<span>Khách hàng</span>
			</div>
		</div>
		<?php
		$noteRaw = isset($order['note']) ? $order['note'] : '';
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
		<div class="px-4 py-2 space-y-2 text-sm text-slate-700">
			<div class="flex flex-col gap-x-6 gap-y-1">
				<div class="flex gap-x-2">
					<span class="text-slate-500 min-w-20">Khách hàng: </span>
					<?php if (!empty($order['customer_name'])) { ?>
						<div class="inline-flex items-center gap-1.5">
							<span class="font-medium text-slate-900"><?php echo htmlspecialchars($order['customer_name']); ?></span>
							<?php if (!empty($order['customer_id'])) { ?>
								<a href="<?php echo $basePath; ?>/customer/view?id=<?php echo (int) $order['customer_id']; ?>" class="inline-flex h-6 w-6 items-center justify-center rounded-lg border border-slate-200 bg-slate-50 text-slate-500 hover:border-brand-300 hover:bg-brand-50 hover:text-brand-700" title="Xem chi tiết khách hàng">
									<?php echo ui_icon("external-link", "h-4 w-4"); ?>
								</a>
							<?php } ?>
						</div>
					<?php } else { ?>
						<span class="text-slate-400">Khách lẻ</span>
					<?php } ?>
				</div>
				<?php if (!empty($order['customer_phone'])) { ?>
					<div class="flex items-center gap-x-2">
						<span class="text-slate-500 min-w-20">SĐT: </span>
						<span class="font-medium text-slate-900"><?php echo htmlspecialchars($order['customer_phone']); ?></span>
						<a href="tel:<?php echo rawurlencode($order['customer_phone']); ?>" class="inline-flex h-6 w-6 items-center justify-center rounded-lg border border-brand-200 bg-brand-50 text-brand-600 hover:border-brand-300 hover:bg-brand-100">
							<?php echo ui_icon("phone", "h-4 w-4"); ?>
						</a>
					</div>
				<?php } ?>
				<?php if (!empty($order['customer_address'])) { ?>
					<div class="flex gap-x-2">
						<span class="text-slate-500 min-w-20">Địa chỉ: </span>
						<span class="text-slate-900"><?php echo htmlspecialchars($order['customer_address']); ?></span>
					</div>
				<?php } ?>
				<?php if ($paymentMethodText !== '') { ?>
					<div class="flex gap-x-2">
						<span class="text-slate-500 min-w-20">Thanh toán: </span>
						<span class="font-medium text-slate-900"><?php echo htmlspecialchars($paymentMethodText); ?></span>
					</div>
				<?php } ?>
				
				<?php if ($noteRaw !== '') { ?>
					<div class="rounded-md bg-brand-50 px-3 py-2 text-slate-700">
						<?php echo nl2br(htmlspecialchars($noteRaw)); ?>
					</div>
				<?php } ?>
			</div>
		</div>
	</div>

		<?php if (empty($items) && empty($manualItems)) { ?>
			<div class="rounded-lg border border-dashed border-slate-300 bg-white px-4 py-4 text-center text-sm text-slate-500">
				Đơn hàng không có mặt hàng nào.
				</div>
			<?php } ?>
		<?php if (!empty($items)) { ?>
			<div class="rounded-lg border border-slate-200 bg-white ">
				<div class="flex items-center justify-between border-b border-slate-100 px-4 py-2">
					<div class="flex items-center gap-2 text-sm font-medium text-slate-800">
						<span class="inline-flex h-7 w-7 items-center justify-center rounded-lg bg-slate-50 text-slate-700">
						<?php echo ui_icon("cube", "size-4"); ?>
					</span>
					<span>Sản phẩm</span>
				</div>
			</div>
				<div class="divide-y divide-slate-100">
					<?php foreach ($items as $item) { ?>
						<?php
						$productImage = '';
						if (!empty($item['product_image_path'])) {
							$productImage = $basePath . '/' . ltrim($item['product_image_path'], '/');
						}
						$productName = isset($item['product_name']) ? $item['product_name'] : '';
						$qtyDisplay = rtrim(rtrim(number_format($item['qty'], 2, ',', ''), '0'), ',');
						$qty = isset($item['qty']) ? (float) $item['qty'] : 0.0;
						$priceSell = isset($item['price_sell']) ? (float) $item['price_sell'] : 0.0;
						$priceCost = isset($item['price_cost']) ? (float) $item['price_cost'] : 0.0;
						$itemProfit = $qty * ($priceSell - $priceCost);
						?>
						<div class="flex items-start gap-3 px-4 py-3 text-sm">
							<div class="flex-shrink-0">
							<?php if ($productImage !== '') { ?>
								<img src="<?php echo htmlspecialchars($productImage); ?>" alt="<?php echo htmlspecialchars($productName); ?>" class="h-10 w-10 rounded-md object-cover">
							<?php } else { ?>
								<div class="flex h-10 w-10 items-center justify-center rounded-md bg-slate-100 text-sm font-medium text-slate-500">
									<?php echo ui_icon("archive-box", "size-5"); ?>
								</div>
							<?php } ?>
							</div>
							<div class="min-w-0 flex-1">
								<div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
									<div class="min-w-0">
										<div class="truncate font-medium text-slate-900"><?php echo htmlspecialchars($productName); ?></div>
										<div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-0.5 text-sm text-slate-600">
											<div class="flex items-center gap-1">
												<span class="text-slate-500">SL</span>
												<span class="font-medium text-slate-900"><?php echo $qtyDisplay; ?></span>
												<span class="text-slate-500"><?php echo htmlspecialchars($item['unit_name']); ?></span>
											</div>
											<div class="flex items-center gap-1">
												<span class="text-slate-500">Đơn giá</span>
												<span class="font-medium text-slate-900"><?php echo Money::format($item['price_sell']); ?></span>
											</div>
											<div class="flex items-center gap-1">
												<span class="text-slate-500">LN</span>
												<span class="font-medium <?php echo $itemProfit >= 0 ? 'text-brand-600' : 'text-rose-600'; ?>">
													<?php echo Money::format($itemProfit); ?>
												</span>
											</div>
										</div>
									</div>
									<div class="flex flex-shrink-0 items-center text-sm font-medium text-slate-900">
										<?php echo Money::format($item['amount']); ?>
									</div>
								</div>
							</div>
						</div>
					<?php } ?>
				</div>
		</div>
	<?php } ?>

    <?php if (!empty($manualItems)) { ?>
		<div class="mt-4 rounded-lg border border-slate-200 bg-white ">
			<div class="flex items-center justify-between border-b border-slate-100 px-4 py-2">
				<div class="flex items-center gap-2 text-sm font-medium text-slate-800">
					<span class="inline-flex h-7 w-7 items-center justify-center rounded-lg bg-amber-50 text-amber-700">
						<?php echo ui_icon("archive-box", "h-4 w-4"); ?>
					</span>
					<span>Sản phẩm khác</span>
				</div>
			</div>
			<div class="divide-y divide-slate-100">
				<?php foreach ($manualItems as $row) { ?>
					<?php
					$name = isset($row['item_name']) ? $row['item_name'] : '';
					$unitName = isset($row['unit_name']) ? $row['unit_name'] : '';
					$qtyVal = isset($row['qty']) ? (float) $row['qty'] : 0.0;
					$qtyText = rtrim(rtrim(number_format($qtyVal, 2, ',', ''), '0'), ',');
					$priceBuy = isset($row['price_buy']) ? (float) $row['price_buy'] : 0.0;
					$priceSell = isset($row['price_sell']) ? (float) $row['price_sell'] : 0.0;
					$amountBuy = isset($row['amount_buy']) ? (float) $row['amount_buy'] : 0.0;
					$amountSell = isset($row['amount_sell']) ? (float) $row['amount_sell'] : 0.0;
					$lineProfit = $amountSell - $amountBuy;
					?>
					<div class="flex items-start gap-3 px-4 py-3 text-sm">
						<div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-md bg-amber-50 text-amber-700">
							<?php echo ui_icon("archive-box", "h-5 w-5"); ?>
						</div>
						<div class="min-w-0 flex-1">
							<div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
								<div class="min-w-0">
									<div class="truncate font-medium text-slate-900"><?php echo htmlspecialchars($name); ?></div>
									<div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-0.5 text-sm text-slate-600">
										<div class="flex items-center gap-1">
											<span class="text-slate-500">SL</span>
											<span class="font-medium text-slate-900"><?php echo $qtyText !== '' ? $qtyText : '0'; ?></span>
											<?php if ($unitName !== '') { ?>
												<span class="text-slate-500"><?php echo htmlspecialchars($unitName); ?></span>
											<?php } ?>
										</div>
										<div class="flex items-center gap-1">
											<span class="text-slate-500">Giá bán</span>
											<span class="font-medium text-slate-900"><?php echo Money::format($priceSell); ?></span>
										</div>
										<?php if ($priceBuy > 0) { ?>
											<div class="flex items-center gap-1">
												<span class="text-slate-500">Giá vốn</span>
												<span class="font-medium text-slate-900"><?php echo Money::format($priceBuy); ?></span>
											</div>
										<?php } ?>
										<?php if ($amountBuy > 0 || $amountSell > 0) { ?>
											<div class="flex items-center gap-1">
												<span class="text-slate-500">LN</span>
												<span class="font-medium <?php echo $lineProfit >= 0 ? 'text-brand-600' : 'text-rose-600'; ?>">
													<?php echo Money::format($lineProfit); ?>
												</span>
											</div>
										<?php } ?>
									</div>
								</div>
								<div class="flex flex-shrink-0 items-center text-sm font-medium text-slate-900">
									<?php echo Money::format($amountSell); ?>
								</div>
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
					<div class="flex flex-col gap-x-2 gap-y-1 px-4 py-3 text-sm sm:flex-row sm:items-start sm:justify-between">
						<div class="min-w-0">
							<div class="flex flex-wrap items-center gap-2">
								<span class="inline-flex items-center gap-1 rounded-lg bg-brand-50 px-2.5 py-0.5 text-sm font-medium text-brand-700">
									<?php echo ui_icon("banknotes", "h-3.5 w-3.5"); ?>
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
						<div class="flex flex-col gap-1 text-sm text-slate-500">
							<?php if ($timeText !== '') { ?>
								<div class="inline-flex items-center gap-1">
									<?php echo ui_icon("clock", "h-3.5 w-3.5"); ?>
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
		<div class="rounded-lg border border-slate-200 bg-white ">
			<div class="flex items-center justify-between border-b border-slate-100 px-4 py-2">
				<div class="flex items-center gap-2 text-sm font-medium text-slate-800">
					<span class="inline-flex h-7 w-7 items-center justify-center rounded-lg bg-amber-50 text-amber-700">
						<?php echo ui_icon("clock", "h-4 w-4"); ?>
					</span>
					<span>Lịch sử thay đổi</span>
				</div>
			</div>
			<div class="max-h-64 divide-y divide-slate-100 overflow-y-auto text-sm">
				<?php
				$logGroups = [];
				foreach ($logs as $log) {
					$timeText = '';
					if (!empty($log['created_at'])) {
						$timeText = format_datetime($log['created_at']);
					}
					if ($timeText === '') {
						$timeText = '-';
					}
					if (!isset($logGroups[$timeText])) {
						$logGroups[$timeText] = [];
					}
					$logGroups[$timeText][] = $log;
				}
				foreach ($logGroups as $timeText => $groupLogs) {
					?>
					<div class="px-4 py-2">
						<div class="mb-1">
							<span class="inline-flex items-center rounded-lg bg-slate-50 py-0.5 text-sm font-medium text-slate-600">
								<?php echo htmlspecialchars($timeText); ?>
							</span>
						</div>
						<div class="space-y-1.5">
							<?php foreach ($groupLogs as $log) { ?>
								<?php
								$detailRaw = isset($log['detail']) ? $log['detail'] : '';
								$detailDecoded = json_decode($detailRaw, true);
								if (json_last_error() === JSON_ERROR_NONE && is_array($detailDecoded) && isset($detailDecoded['type'])) {
									$type = $detailDecoded['type'];
									if ($type === 'add_items') {
										$itemsCount = isset($detailDecoded['items_count']) ? (int) $detailDecoded['items_count'] : 0;
										$totalAmount = isset($detailDecoded['total_amount']) ? (float) $detailDecoded['total_amount'] : 0.0;
										$context = isset($detailDecoded['context']) ? $detailDecoded['context'] : 'add';
										?>
										<div class="leading-relaxed text-sm text-brand-700">
											<?php if ($context === 'update') { ?>
												Tăng số lượng cho <?php echo $itemsCount; ?> sản phẩm, + <?php echo Money::format($totalAmount); ?>
											<?php } else { ?>
												Thêm <?php echo $itemsCount; ?> sản phẩm, + <?php echo Money::format($totalAmount); ?>
											<?php } ?>
										</div>
										<?php
									} elseif ($type === 'remove_items') {
										$itemsCount = isset($detailDecoded['items_count']) ? (int) $detailDecoded['items_count'] : 0;
										$totalAmount = isset($detailDecoded['total_amount']) ? (float) $detailDecoded['total_amount'] : 0.0;
										$context = isset($detailDecoded['context']) ? $detailDecoded['context'] : 'update';
										?>
										<div class="leading-relaxed text-sm text-rose-700">
											<?php if ($context === 'update') { ?>
												Giảm số lượng của <?php echo $itemsCount; ?> sản phẩm, - <?php echo Money::format($totalAmount); ?>
											<?php } else { ?>
												Giảm <?php echo $itemsCount; ?> sản phẩm, - <?php echo Money::format($totalAmount); ?>
											<?php } ?>
										</div>
										<?php
									} elseif ($type === 'return_items') {
										$itemsCount = isset($detailDecoded['items_count']) ? (int) $detailDecoded['items_count'] : 0;
										$totalReduce = isset($detailDecoded['total_reduce_amount']) ? (float) $detailDecoded['total_reduce_amount'] : 0.0;
										$refundAmount = isset($detailDecoded['refund_amount']) ? (float) $detailDecoded['refund_amount'] : 0.0;
										?>
										<div class="leading-relaxed text-sm text-rose-700">
											Trả <?php echo $itemsCount; ?> sản phẩm, - <?php echo Money::format($totalReduce); ?><?php if ($refundAmount > 0) { ?>, hoàn <?php echo Money::format($refundAmount); ?><?php } ?>
										</div>
										<?php
									} elseif ($type === 'payment_reset') {
										$paidBefore = isset($detailDecoded['paid_before']) ? (float) $detailDecoded['paid_before'] : 0.0;
										$paidAfter = isset($detailDecoded['paid_after']) ? (float) $detailDecoded['paid_after'] : 0.0;
										$paymentsCount = isset($detailDecoded['payments_count']) ? (int) $detailDecoded['payments_count'] : 0;
										?>
										<div class="leading-relaxed text-sm text-amber-700">
											Đặt lại thanh toán: đã thu <?php echo Money::format($paidBefore); ?> -> <?php echo Money::format($paidAfter); ?><?php if ($paymentsCount > 0) { ?>, xóa <?php echo $paymentsCount; ?> lần thanh toán<?php } ?>
										</div>
										<?php
									} elseif ($type === 'update_status') {
										$text = isset($detailDecoded['text']) ? $detailDecoded['text'] : '';
										?>
										<div class="leading-relaxed text-sm text-slate-700">
											<?php echo htmlspecialchars($text); ?>
										</div>
									<?php
								} elseif ($type === 'payment') {
									$amount = isset($detailDecoded['amount']) ? (float) $detailDecoded['amount'] : 0.0;
									$methodText = isset($detailDecoded['method_text']) ? $detailDecoded['method_text'] : '';
									$remainingAfter = isset($detailDecoded['remaining_after']) ? (float) $detailDecoded['remaining_after'] : null;
									?>
									<div class="leading-relaxed text-sm text-brand-700">
										Thu <?php echo Money::format($amount); ?><?php if ($methodText !== '') { ?> (<?php echo htmlspecialchars($methodText); ?>)<?php } ?><?php if ($remainingAfter !== null) { ?>, còn nợ <?php echo Money::format($remainingAfter); ?><?php } ?>
									</div>
										<?php
									} else {
										?>
										<div class="leading-relaxed text-sm text-slate-700"><?php echo htmlspecialchars($detailRaw); ?></div>
										<?php
									}
								} else {
									$text = $detailRaw;
									$cssClass = 'text-slate-700';
									if (preg_match('/SL\s+([0-9]+(?:[.,][0-9]+)?)\s*->\s*([0-9]+(?:[.,][0-9]+)?)/u', $text, $m)) {
										$fromNumber = (float) str_replace(',', '.', $m[1]);
										$toNumber = (float) str_replace(',', '.', $m[2]);
										if ($toNumber > $fromNumber) {
											$cssClass = 'text-brand-700';
										} elseif ($toNumber < $fromNumber) {
											$cssClass = 'text-rose-700';
										}
									}
									if (preg_match('/^(.*SL\s+)([0-9]+(?:[.,][0-9]+)?)(\s*->\s*)([0-9]+(?:[.,][0-9]+)?)(.*)$/u', $text, $parts)) {
										$before = $parts[1];
										$fromText = $parts[2];
										$toText = $parts[4];
										$after = $parts[5];
										?>
										<div class="leading-relaxed text-sm <?php echo $cssClass; ?>">
											<?php echo htmlspecialchars($before); ?>
											<?php echo htmlspecialchars($fromText); ?>
											<span class="inline-flex items-center">
												<?php echo ui_icon("arrow-right", "size-3"); ?>

											</span>
											<?php echo htmlspecialchars($toText); ?>
											<?php echo htmlspecialchars($after); ?>
										</div>
										<?php
									} else {
										?>
										<div class="leading-relaxed text-sm <?php echo $cssClass; ?>"><?php echo htmlspecialchars($text); ?></div>
										<?php
									}
								}
								?>
							<?php } ?>
						</div>
					</div>
				<?php } ?>
			</div>
		</div>
	<?php } ?>
</div>
<?php if ($remaining > 0 && $orderStatus !== 'cancelled') { ?>
	<div class="app-modal-overlay" data-order-payment-modal>
		<div class="app-modal-sheet-sm">
			<div class="app-modal-header">
				<h2 class="app-modal-title">Thu tiền đơn hàng</h2>
				<button type="button" class="app-modal-close" data-order-payment-close>
					<?php echo ui_icon("x-mark", "h-4 w-4"); ?>
				</button>
			</div>
			<form method="post" action="<?php echo $basePath; ?>/order/paymentStore" class="app-modal-body space-y-4">
				<div hidden>
					<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
					<input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
				</div>
				<div class="grid grid-cols-3 gap-3 text-sm">
					<div class="rounded-md bg-slate-50 px-3 py-2">
					<div class="text-sm uppercase  text-slate-500">Tổng tiền</div>
						<div class="mt-1 font-medium text-slate-900"><?php echo Money::format($order['total_amount']); ?></div>
					</div>
					<div class="rounded-md bg-brand-50 px-3 py-2">
					<div class="text-sm uppercase  text-brand-600">Đã thu</div>
						<div class="mt-1 font-medium text-brand-700"><?php echo Money::format($order['paid_amount']); ?></div>
					</div>
					<div class="rounded-md bg-slate-50 px-3 py-2">
					<div class="text-sm uppercase  text-slate-500">Còn nợ</div>
						<div class="mt-1 font-medium text-red-600"><?php echo Money::format($remaining); ?></div>
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
				<div class="relative">
					<label class="absolute left-3 top-0 z-10 -translate-y-1/2 bg-white px-1 leading-none text-sm text-slate-700">Số tiền thu</label>
					<div class="relative">
						<?php
						$amountValue = htmlspecialchars(number_format($remaining, 0, '', '.'));
						ui_input_text('amount', $amountValue, [
							'inputmode' => 'numeric',
							'data-money-input' => '1',
							'class' => 'pr-9 pt-3 pb-2.5 text-right'
						]);
						?>
						<span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-sm text-slate-500">đ</span>
					</div>
				</div>
				<div class="relative">
					<label class="absolute left-3 top-0 z-10 -translate-y-1/2 bg-white px-1 leading-none text-sm text-slate-700">Ghi chú</label>
					<textarea name="note" rows="2" class="form-field block w-full rounded-xl border border-slate-300 bg-white px-3.5 pt-3 pb-2.5 text-sm outline-none transition focus:border-brand-500"></textarea>
				</div>
				<div class="app-modal-footer mt-2 pt-2 border-t border-slate-100 px-0 py-0">
					<button type="button" class="app-btn-secondary" data-order-payment-close>Hủy</button>
					<?php ui_button_primary('Xác nhận thu', ['type' => 'submit', 'data-loading-button' => '1']); ?>
				</div>
			</form>
		</div>
	</div>
<?php } ?>