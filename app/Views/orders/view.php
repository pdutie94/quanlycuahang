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
				<span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-emerald-50 text-emerald-700">
					<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4">
					<path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z"></path>
					</svg>
				</span>
				<span class="text-sm font-medium text-slate-900 sm:text-sm">#<?php echo htmlspecialchars($order['order_code']); ?></span>
			</div>
		</div>
		<div class="px-4 py-2">
			<div class="flex flex-wrap items-center gap-2 text-sm sm:text-sm text-slate-600">
				<span class="inline-flex items-center rounded-full bg-sky-50 px-2.5 py-0.5 text-sm font-medium text-sky-700">
					<span><?php echo htmlspecialchars($orderDateFormatted); ?></span>
				</span>
				<span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-sm font-medium <?php echo $order['status'] === 'paid' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700'; ?>">
					<?php echo $order['status'] === 'paid' ? 'Đã thanh toán' : 'Còn nợ'; ?>
				</span>
				<span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-sm font-medium
					<?php
					if ($orderStatus === 'completed') {
						echo 'bg-emerald-50 text-emerald-700';
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

				<div class="mt-3 flex items-center justify-between rounded-md px-3 py-2 <?php echo $profitOrder >= 0 ? 'bg-emerald-50' : 'bg-rose-50'; ?>">
					<div class="flex items-center gap-2">
						<span class="inline-flex h-6 w-6 items-center justify-center rounded-full <?php echo $profitOrder >= 0 ? 'bg-emerald-600 text-white' : 'bg-rose-600 text-white'; ?>">
							<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-3.5 w-3.5">
								<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.22-9.78a.75.75 0 00-1.06-1.06L9 10.94 7.84 9.78a.75.75 0 10-1.06 1.06l1.75 1.75a.75.75 0 001.06 0l3.66-3.66z" clip-rule="evenodd" />
							</svg>
						</span>
						<span class="text-sm font-medium uppercase  <?php echo $profitOrder >= 0 ? 'text-emerald-700' : 'text-rose-700'; ?>">Lợi nhuận</span>
					</div>
					<div class="text-sm old-text-base font-medium <?php echo $profitOrder >= 0 ? 'text-emerald-700' : 'text-rose-700'; ?>">
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
						<select name="order_status" class="block w-full appearance-none rounded-lg border border-slate-300 bg-white px-3 py-1.5 pr-8 text-sm" data-no-select2>
							<option value="pending" <?php echo $orderStatus === 'pending' ? 'selected' : ''; ?>>Chưa hoàn thành</option>
							<option value="completed" <?php echo $orderStatus === 'completed' ? 'selected' : ''; ?>>Đã hoàn thành</option>
							<option value="cancelled" <?php echo $orderStatus === 'cancelled' ? 'selected' : ''; ?>>Đã hủy</option>
						</select>
						<span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2 text-slate-400">
							<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4">
								<path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.168l3.71-3.938a.75.75 0 1 1 1.08 1.04l-4.24 4.5a.75.75 0 0 1-1.08 0l-4.24-4.5a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd"></path>
							</svg>
						</span>
					</div>
					<button type="submit" class="inline-flex items-center gap-1.5 rounded-md border border-sky-600 bg-sky-600 px-3 py-1.5 text-sm font-medium text-white hover:border-sky-700 hover:bg-sky-700">
						<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4">
							<path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
						</svg>
						<span>Cập nhật</span>
					</button>
				</form>
			</div>
		</div>
	</div>

	<div class="rounded-lg border border-slate-200 bg-white ">
		<div class="flex items-center justify-between border-b border-slate-100 px-4 py-2">
			<div class="flex items-center gap-2 text-sm font-medium text-slate-800">
				<span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-amber-50 text-amber-700">
					<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4">
					<path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z"></path>
					</svg>
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
								<a href="<?php echo $basePath; ?>/customer/view?id=<?php echo (int) $order['customer_id']; ?>" class="inline-flex h-6 w-6 items-center justify-center rounded-full border border-slate-200 bg-slate-50 text-slate-500 hover:border-emerald-300 hover:bg-emerald-50 hover:text-emerald-700" title="Xem chi tiết khách hàng">
									<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4">
										<path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
									</svg>
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
						<a href="tel:<?php echo rawurlencode($order['customer_phone']); ?>" class="inline-flex h-6 w-6 items-center justify-center rounded-full border border-emerald-200 bg-emerald-50 text-emerald-600 hover:border-emerald-300 hover:bg-emerald-100">
							<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4">
								<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h1.5a2.25 2.25 0 0 0 2.25-2.25v-1.386c0-.516-.351-.966-.852-1.091l-3.423-.856a1.125 1.125 0 0 0-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97a1.125 1.125 0 0 0 .417-1.173L6.977 3.102A1.125 1.125 0 0 0 5.886 2.25H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z" />
							</svg>
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
					<div class="rounded-md bg-emerald-50 px-3 py-2 text-slate-700">
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
						<span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-slate-50 text-slate-700">
						<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
  <path stroke-linecap="round" stroke-linejoin="round" d="m21 7.5-9-5.25L3 7.5m18 0-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9"></path>
</svg>
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
									<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5">
		  <path stroke-linecap="round" stroke-linejoin="round" d="m20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" />
		</svg>
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
												<span class="font-medium <?php echo $itemProfit >= 0 ? 'text-emerald-600' : 'text-rose-600'; ?>">
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
					<span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-amber-50 text-amber-700">
						<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4">
							<path stroke-linecap="round" stroke-linejoin="round" d="m20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" />
						</svg>
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
							<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
								<path stroke-linecap="round" stroke-linejoin="round" d="m20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" />
							</svg>
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
												<span class="font-medium <?php echo $lineProfit >= 0 ? 'text-emerald-600' : 'text-rose-600'; ?>">
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
						<div class="flex flex-col gap-1 text-sm text-slate-500">
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
		<div class="rounded-lg border border-slate-200 bg-white ">
			<div class="flex items-center justify-between border-b border-slate-100 px-4 py-2">
				<div class="flex items-center gap-2 text-sm font-medium text-slate-800">
					<span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-amber-50 text-amber-700">
						<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4">
							<path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m3.75 0a8.25 8.25 0 1 1-16.5 0 8.25 8.25 0 0 1 16.5 0Z" />
						</svg>
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
							<span class="inline-flex items-center rounded-full bg-slate-50 py-0.5 text-sm font-medium text-slate-600">
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
										<div class="leading-relaxed text-sm text-emerald-700">
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
									<div class="leading-relaxed text-sm text-emerald-700">
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
											$cssClass = 'text-emerald-700';
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
												<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-3">
  <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
</svg>

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
	<div class="fixed p-3 inset-0 z-40 hidden items-center justify-center bg-black/40" data-order-payment-modal>
		<div class="w-full max-w-md rounded-2xl bg-white ">
			<div class="flex items-center justify-between border-b border-slate-200 px-4 py-3">
				<h2 class="text-sm font-medium text-slate-800">Thu tiền đơn hàng</h2>
				<button type="button" class="text-slate-400 hover:text-slate-600" data-order-payment-close>
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4">
						<path fill-rule="evenodd" d="M4.293 4.293a1 1 0 0 1 1.414 0L10 8.586l4.293-4.293a1 1 0 1 1 1.414 1.414L11.414 10l4.293 4.293a1 1 0 0 1-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 0 1-1.414-1.414L8.586 10 4.293 5.707a1 1 0 0 1 0-1.414Z" clip-rule="evenodd" />
					</svg>
				</button>
			</div>
			<form method="post" action="<?php echo $basePath; ?>/order/paymentStore" class="px-4 py-3 space-y-3">
				<div hidden>
					<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
					<input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
				</div>
				<div class="grid grid-cols-3 gap-3 text-sm">
					<div class="rounded-md bg-slate-50 px-3 py-2">
					<div class="text-sm uppercase  text-slate-500">Tổng tiền</div>
						<div class="mt-1 font-medium text-slate-900"><?php echo Money::format($order['total_amount']); ?></div>
					</div>
					<div class="rounded-md bg-emerald-50 px-3 py-2">
					<div class="text-sm uppercase  text-emerald-600">Đã thu</div>
						<div class="mt-1 font-medium text-emerald-700"><?php echo Money::format($order['paid_amount']); ?></div>
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
				<div class="space-y-1">
					<label class="block text-sm text-slate-700">Số tiền thu</label>
					<div class="relative">
						<?php
						$amountValue = htmlspecialchars(number_format($remaining, 0, '', '.'));
						ui_input_text('amount', $amountValue, [
							'inputmode' => 'numeric',
							'data-money-input' => '1',
							'class' => 'pr-9 text-right'
						]);
						?>
						<span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-sm text-slate-500">đ</span>
					</div>
				</div>
				<div class="space-y-1">
					<label class="block text-sm text-slate-700">Ghi chú</label>
					<textarea name="note" rows="2" class="form-field block w-full rounded-lg border border-slate-300 bg-slate-50 px-3 py-2 text-sm outline-none focus:border-emerald-500 focus:bg-white"></textarea>
				</div>
				<div class="mt-2 flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
					<button type="button" class="inline-flex items-center rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-100" data-order-payment-close>Hủy</button>
					<?php ui_button_primary('Xác nhận thu', ['type' => 'submit', 'class' => 'py-1.5', 'data-loading-button' => '1']); ?>
				</div>
			</form>
		</div>
	</div>
<?php } ?>