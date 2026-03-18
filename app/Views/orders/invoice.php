<?php
$orderStatus = isset($order['order_status']) ? $order['order_status'] : 'pending';
$remaining = isset($order['total_amount']) && isset($order['paid_amount']) ? $order['total_amount'] - $order['paid_amount'] : 0;
?>

<style>
@page {
    margin: 5mm;
}

@media print {
    header,
    nav {
        display: none !important;
    }
    body {
        background: #ffffff !important;
    }
    .invoice-print-wrapper {
        margin: 0 !important;
        padding: 0 !important;
        box-shadow: none !important;
        border-radius: 0 !important;
        max-width: 100% !important;
        width: 100% !important;
        font-size: 13px !important;
    }
    .invoice-print-wrapper table {
        font-size: 12px !important;
    }
}
</style>

<div class="mx-auto mt-4 max-w-3xl rounded-xl bg-white p-6  ring-1 ring-slate-200 invoice-print-wrapper">
	<div class="flex items-start justify-between gap-4 border-b border-dashed border-slate-200 pb-4">
		<div>
			<div class="text-sm font-medium uppercase  text-slate-500">Hóa đơn bán hàng</div>
			<div class="mt-1 text-lg font-medium text-slate-900">Đơn hàng #<?php echo htmlspecialchars($order['order_code']); ?></div>
			<?php if (!empty($order['order_date'])) { ?>
				<?php
				$ts = strtotime($order['order_date']);
				$timeText = $ts !== false ? date('H:i, d/m/Y', $ts) : $order['order_date'];
				?>
				<div class="mt-0.5 text-sm text-slate-500">Ngày: <?php echo htmlspecialchars($timeText); ?></div>
			<?php } ?>
		</div>
		<div class="text-right text-sm text-slate-600">
			<div><?php echo htmlspecialchars($config['app_name']); ?></div>
			<?php if (!empty($order['customer_name']) || !empty($order['customer_phone']) || !empty($order['customer_address'])) { ?>
				<div class="mt-2 text-sm uppercase  text-slate-500">Khách hàng</div>
				<div class="mt-0.5 text-sm text-slate-700">
					<?php if (!empty($order['customer_name'])) { ?>
						<div><?php echo htmlspecialchars($order['customer_name']); ?></div>
					<?php } ?>
					<?php if (!empty($order['customer_phone'])) { ?>
						<div><?php echo htmlspecialchars($order['customer_phone']); ?></div>
					<?php } ?>
					<?php if (!empty($order['customer_address'])) { ?>
						<div><?php echo htmlspecialchars($order['customer_address']); ?></div>
					<?php } ?>
				</div>
			<?php } ?>
		</div>
	</div>

	<div class="mt-4 overflow-x-auto rounded-lg border border-slate-200">
		<table class="min-w-full border-collapse text-sm">
			<thead class="bg-slate-50 text-sm uppercase  text-slate-500">
				<tr>
					<th class="border-b border-slate-200 px-3 py-2 text-left">STT</th>
					<th class="border-b border-slate-200 px-3 py-2 text-left">Sản phẩm</th>
					<th class="border-b border-slate-200 px-3 py-2 text-left">ĐVT</th>
					<th class="border-b border-slate-200 px-3 py-2 text-right">SL</th>
					<th class="border-b border-slate-200 px-3 py-2 text-right">Đơn giá</th>
					<th class="border-b border-slate-200 px-3 py-2 text-right">Thành tiền</th>
				</tr>
			</thead>
			<tbody class="align-top text-sm old-text-base">
				<?php
				$index = 1;
				foreach ($items as $row) {
					$name = isset($row['product_name']) ? $row['product_name'] : '';
					$unitName = isset($row['unit_name']) ? $row['unit_name'] : '';
					$qty = isset($row['qty']) ? (float) $row['qty'] : 0.0;
					$price = isset($row['price_sell']) ? (float) $row['price_sell'] : 0.0;
					$amount = isset($row['amount']) ? (float) $row['amount'] : $qty * $price;
					if ($qty < 0) {
						$qty = 0;
					}
					if ($price < 0) {
						$price = 0;
					}
					if ($amount < 0) {
						$amount = 0;
					}
					$qtyText = rtrim(rtrim(number_format($qty, 2, ',', ''), '0'), ',');
					if ($qtyText === '') {
						$qtyText = '0';
					}
				?>
					<tr>
						<td class="border-b border-slate-100 px-3 py-1.5 text-slate-600"><?php echo $index; ?></td>
						<td class="border-b border-slate-100 px-3 py-1.5 text-slate-800"><?php echo htmlspecialchars($name); ?></td>
						<td class="border-b border-slate-100 px-3 py-1.5 text-slate-600"><?php echo htmlspecialchars($unitName); ?></td>
						<td class="border-b border-slate-100 px-3 py-1.5 text-right tabular-nums text-slate-700"><?php echo htmlspecialchars($qtyText); ?></td>
						<td class="border-b border-slate-100 px-3 py-1.5 text-right tabular-nums text-slate-700"><?php echo Money::format($price); ?></td>
						<td class="border-b border-slate-100 px-3 py-1.5 text-right tabular-nums font-medium text-slate-900"><?php echo Money::format($amount); ?></td>
					</tr>
				<?php
					$index++;
				}
				?>
			</tbody>
		</table>
	</div>

	<div class="mt-4 grid gap-4 text-sm sm:grid-cols-2">
		<div class="space-y-1 text-sm text-slate-600">
			<div class="font-medium text-slate-800">Ghi chú</div>
			<div class="min-h-[40px] rounded-md border border-slate-200 bg-slate-50 px-3 py-2">
				<?php echo !empty($order['note']) ? nl2br(htmlspecialchars($order['note'])) : '<span class="text-slate-400">Không có</span>'; ?>
			</div>
		</div>
		<div class="space-y-1 text-sm text-slate-600">
			<div class="font-medium text-slate-800">Tổng kết</div>
			<div class="rounded-md border border-slate-200 bg-slate-50 px-3 py-2">
				<?php
				$discountAmount = isset($order['discount_amount']) ? (float) $order['discount_amount'] : 0;
				if ($discountAmount < 0) {
					$discountAmount = 0;
				}
				$surchargeAmount = isset($order['surcharge_amount']) ? (float) $order['surcharge_amount'] : 0;
				if ($surchargeAmount < 0) {
					$surchargeAmount = 0;
				}
				$subtotal = isset($order['total_amount']) ? (float) $order['total_amount'] + $discountAmount - $surchargeAmount : $discountAmount;
				?>
				<div class="flex items-center justify-between">
					<span>Tạm tính</span>
					<span class="font-medium text-slate-900"><?php echo Money::format($subtotal); ?></span>
				</div>
				<?php if ($discountAmount > 0) { ?>
				<div class="mt-1 flex items-center justify-between">
					<span>Giảm giá</span>
					<span class="font-medium text-rose-600">-<?php echo Money::format($discountAmount); ?></span>
				</div>
				<?php } ?>
				<?php if ($surchargeAmount > 0) { ?>
				<div class="mt-1 flex items-center justify-between">
					<span>Phụ thu</span>
					<span class="font-medium text-amber-700">+<?php echo Money::format($surchargeAmount); ?></span>
				</div>
				<?php } ?>
				<div class="mt-1 flex items-center justify-between">
					<span>Tổng cộng</span>
					<span class="font-medium text-slate-900"><?php echo Money::format($order['total_amount']); ?></span>
				</div>
				<div class="mt-1 flex items-center justify-between">
					<span>Đã thanh toán</span>
					<span class="font-medium text-brand-700"><?php echo Money::format($order['paid_amount']); ?></span>
				</div>
				<div class="mt-1 flex items-center justify-between">
					<span>Còn nợ</span>
					<span class="font-medium text-red-600"><?php echo Money::format($remaining); ?></span>
				</div>
			</div>
		</div>
	</div>

	<div class="mt-6 flex items-center justify-between text-sm text-slate-500 print:hidden">
		<button type="button" onclick="window.print()" class="inline-flex items-center gap-1 rounded-full border border-slate-300 px-3 py-1.5 font-medium text-slate-700 hover:bg-slate-50">
			<span>In / Lưu PDF</span>
		</button>
		<a href="<?php echo $basePath; ?>/order/view?id=<?php echo (int) $order['id']; ?>" class="text-sm font-medium text-brand-600 hover:text-brand-700">
			Quay lại chi tiết đơn hàng
		</a>
	</div>
</div>
