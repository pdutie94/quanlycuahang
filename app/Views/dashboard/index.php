<?php
if (!isset($ordersToday)) {
    $ordersToday = ['total_amount' => 0, 'profit' => 0, 'paid_amount' => 0, 'debt_amount' => 0];
}
if (!isset($ordersMonth)) {
    $ordersMonth = ['total_amount' => 0, 'profit' => 0];
}
if (!isset($purchasesMonth)) {
    $purchasesMonth = ['total_amount' => 0, 'paid_amount' => 0, 'debt_amount' => 0];
}
if (!isset($customerDebt)) {
    $customerDebt = 0;
}
if (!isset($supplierDebt)) {
    $supplierDebt = 0;
}
$lowStockItems = isset($lowStockItems) && is_array($lowStockItems) ? $lowStockItems : [];
?>

<div class="mb-5 flex items-center justify-between gap-3">
	<div>
		<h1 class="text-lg font-medium tracking-tight">Tổng quan hôm nay</h1>
		<p class="mt-1 text-sm text-slate-600">Báo cáo nhanh, lối tắt và đơn hàng mới.</p>
	</div>
</div>

<!-- Lối tắt nhanh -->
<div class="mb-5 space-y-3">
	<div class="flex items-center justify-between">
		<div class="text-sm font-medium  text-slate-500 uppercase">Lối tắt nhanh</div>
        <a href="<?php echo $basePath; ?>/backup/database" class="text-sm font-medium text-slate-600 hover:text-emerald-700">Backup DB</a>
	</div>
	<div class="grid grid-cols-3 gap-2 text-center text-sm">
		<a href="<?php echo $basePath; ?>/pos" class="flex flex-col items-center gap-1 rounded-2xl bg-emerald-600 px-2 py-3 text-sm font-medium text-white shadow-sm">
			<span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-500/90">
				<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
					<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" />
				</svg>
			</span>
			<span>Tạo đơn</span>
		</a>
		<a href="<?php echo $basePath; ?>/product/create" class="flex flex-col items-center gap-1 rounded-2xl bg-white px-2 py-3 text-sm font-medium text-slate-700 shadow-sm ring-1 ring-slate-100">
			<span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
				<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
					<path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
				</svg>
			</span>
			<span>Thêm SP</span>
		</a>
		<!-- Đơn hàng -->
		<a href="<?php echo $basePath; ?>/order" class="flex flex-col items-center gap-1 rounded-2xl bg-white px-2 py-3 text-sm font-medium text-slate-700 shadow-sm ring-1 ring-slate-100">
			<span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
				<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
					<path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 3h6m-6 3h6M7.5 4.5h9.75A1.75 1.75 0 0 1 19 6.25v13a1.75 1.75 0 0 1-1.75 1.75H6.75A1.75 1.75 0 0 1 5 19.25v-13A1.75 1.75 0 0 1 6.75 4.5Z" />
				</svg>
			</span>
			<span>Đơn hàng</span>
		</a>
		<!-- Báo cáo -->
		<!-- <a href="<?php echo $basePath; ?>/report" class="flex flex-col items-center gap-1 rounded-2xl bg-white px-2 py-3 text-sm font-medium text-slate-700 shadow-sm ring-1 ring-slate-100">
			<span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
				<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
					<path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />
				</svg>
			</span>
			<span>Báo cáo</span>
		</a> -->
	</div>
</div>

<!-- Báo cáo nhanh hôm nay -->
<div class="mb-5 space-y-2">
	<div class="text-sm font-medium  text-slate-500 uppercase">Báo cáo nhanh hôm nay</div>
	<div class="grid grid-cols-2 gap-2">
		<div class="rounded-2xl bg-white px-4 py-3 shadow-sm ring-1 ring-slate-100">
			<div class="text-sm font-medium  text-slate-400 uppercase">Doanh thu</div>
			<div class="mt-1 text-lg text-xl-old font-medium text-slate-900"><?php echo Money::format($ordersToday['total_amount']); ?></div>
		</div>
        <div class="rounded-2xl bg-white px-4 py-3 shadow-sm ring-1 ring-slate-100">
			<div class="text-sm font-medium  text-slate-400 uppercase">Lợi nhuận</div>
			<div class="mt-1 text-lg text-xl-old font-medium text-slate-900"><?php echo Money::format($ordersToday['profit']); ?></div>
		</div>
		<div class="rounded-2xl bg-white px-4 py-3 shadow-sm ring-1 ring-slate-100">
			<div class="text-sm font-medium  text-slate-400 uppercase">Đã thu</div>
			<div class="mt-1 text-lg text-xl-old font-medium text-slate-900"><?php echo Money::format($ordersToday['paid_amount']); ?></div>
		</div>
		<div class="rounded-2xl bg-white px-4 py-3 shadow-sm ring-1 ring-slate-100">
			<div class="text-sm font-medium  text-slate-400 uppercase">Còn nợ</div>
			<div class="mt-1 text-lg text-xl-old font-medium <?php echo $ordersToday['debt_amount'] > 0 ? 'text-rose-600' : 'text-slate-900'; ?>"><?php echo Money::format($ordersToday['debt_amount']); ?></div>
		</div>
	</div>
</div>

<!-- Cảnh báo hàng sắp hết -->
<?php if (!empty($lowStockItems)) { ?>
<div class="mb-5 space-y-2">
    <div class="flex items-center justify-between">
        <div class="text-sm font-medium  text-rose-600 uppercase">Hàng sắp hết</div>
        <a href="<?php echo $basePath; ?>/report/inventory" class="text-sm font-medium text-rose-600 hover:text-rose-700">Xem tồn kho</a>
    </div>
    <div class="space-y-2">
        <?php foreach ($lowStockItems as $row) { ?>
            <?php
            $qtyBase = isset($row['qty_base']) ? (float) $row['qty_base'] : 0.0;
            $minStock = isset($row['min_stock_qty']) ? (float) $row['min_stock_qty'] : 0.0;
            $ratio = $minStock > 0 ? ($qtyBase / $minStock) : 0;
            $qtyText = rtrim(rtrim(number_format($qtyBase, 2, ',', ''), '0'), ',');
            if ($qtyText === '') {
                $qtyText = '0';
            }
            ?>
            <div class="flex items-center justify-between rounded-2xl border border-rose-100 bg-rose-50 px-3 py-2 text-sm text-rose-800">
                <div class="min-w-0">
                    <div class="truncate font-medium text-rose-900"><?php echo htmlspecialchars($row['name']); ?></div>
                    <div class="mt-0.5 flex flex-wrap items-center gap-2 text-sm text-rose-700">
                        <span>Tồn: <?php echo $qtyText; ?> <?php echo htmlspecialchars($row['base_unit_name']); ?></span>
                        <?php if ($minStock > 0) { ?>
                            <span>Ngưỡng: <?php echo rtrim(rtrim(number_format($minStock, 2, ',', ''), '0'), ','); ?> <?php echo htmlspecialchars($row['base_unit_name']); ?></span>
                        <?php } ?>
                        <?php if (!empty($row['category_name'])) { ?>
                            <span>Danh mục: <?php echo htmlspecialchars($row['category_name']); ?></span>
                        <?php } ?>
                    </div>
                </div>
                <div class="ml-2 flex flex-col items-end text-sm font-medium text-rose-700">
                    <?php if ($ratio <= 0.3) { ?>
                        <span class="rounded-full bg-rose-600 px-2 py-0.5 text-sm text-white">Rất thấp</span>
                    <?php } elseif ($ratio <= 0.7) { ?>
                        <span class="rounded-full bg-rose-500 px-2 py-0.5 text-sm text-white">Sắp hết</span>
                    <?php } else { ?>
                        <span class="rounded-full bg-rose-400 px-2 py-0.5 text-sm text-white">Thấp</span>
                    <?php } ?>
                </div>
            </div>
        <?php } ?>
    </div>
</div>
<?php } ?>

<!-- Đơn hàng gần đây -->
<?php
$recentOrders = isset($recentOrders) && is_array($recentOrders) ? $recentOrders : [];
?>

<div class="space-y-2">
	<div class="flex items-center justify-between">
		<div class="text-sm font-medium  text-slate-500 uppercase">Đơn hàng gần đây</div>
		<a href="<?php echo $basePath; ?>/order" class="text-sm font-medium text-emerald-600 hover:text-emerald-700">Xem tất cả</a>
	</div>

	<?php if (empty($recentOrders)) { ?>
		<div class="rounded-2xl bg-white px-4 py-3 text-sm text-slate-500 shadow-sm ring-1 ring-slate-100">
			Không có đơn hàng mới nào được ghi nhận gần đây.
		</div>
	<?php } else { ?>
		<div class="space-y-2">
			<?php foreach ($recentOrders as $order) { ?>
				<?php
				$total = isset($order['total_amount']) ? (float) $order['total_amount'] : 0;
				$paid = isset($order['paid_amount']) ? (float) $order['paid_amount'] : 0;
				$debt = $total - $paid;
				$cost = isset($order['total_cost']) ? (float) $order['total_cost'] : 0.0;
				$profit = $total - $cost;
				$itemsCount = isset($order['items_count']) ? (int) $order['items_count'] : 0;
				$timeText = '';
				if (!empty($order['order_date'])) {
					$ts = strtotime($order['order_date']);
					if ($ts !== false) {
						$timeText = date('H:i, d/m/Y', $ts);
					}
				}
				$orderStatus = isset($order['order_status']) ? $order['order_status'] : 'pending';
				$badgeLabel = '';
				$badgeClass = '';
				if ($orderStatus === 'cancelled') {
					$badgeLabel = 'Đã hủy';
					$badgeClass = 'bg-slate-100 text-slate-500';
				} elseif ($order['status'] === 'paid') {
					$badgeLabel = 'Đã thanh toán';
					$badgeClass = 'bg-emerald-50 text-emerald-700';
				} elseif ($debt > 0) {
					$badgeLabel = 'Cần thu tiền';
					$badgeClass = 'bg-rose-50 text-rose-700';
				} else {
					$badgeLabel = 'Chờ xử lý';
					$badgeClass = 'bg-amber-50 text-amber-700';
				}
				$cardClasses = 'space-y-1.5';
				if ($orderStatus === 'cancelled') {
					$cardClasses .= ' opacity-60';
				}
				?>
				<a href="<?php echo $basePath; ?>/order/view?id=<?php echo (int) $order['id']; ?>" class="relative block rounded-2xl bg-white p-3 text-sm shadow-sm ring-1 ring-slate-100 transition hover:shadow-md">
					<div class="<?php echo $cardClasses; ?>">
						<div class="flex items-center gap-2">
							<div class="text-sm font-mono font-medium text-emerald-700">
								#<?php echo htmlspecialchars($order['order_code']); ?>
							</div>
							<span class="inline-flex items-center rounded-full px-3 py-0.5 text-sm font-medium whitespace-nowrap <?php echo $badgeClass; ?>"><?php echo htmlspecialchars($badgeLabel); ?></span>
						</div>
						<div class="truncate text-sm font-medium text-slate-900">
							<?php if (!empty($order['customer_name'])) { ?>
								<?php echo htmlspecialchars($order['customer_name']); ?>
							<?php } else { ?>
								<span class="text-slate-400">Khách lẻ</span>
							<?php } ?>
						</div>
						<div class="flex flex-wrap items-center gap-x-2 gap-y-1 text-sm text-slate-500">
							<?php if ($timeText !== '') { ?>
								<div class="inline-flex items-center gap-1">
									<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-3.5 w-3.5">
										<path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
									</svg>
									<span><?php echo htmlspecialchars($timeText); ?></span>
								</div>
							<?php } ?>
							<div class="inline-flex items-center gap-1">
								<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-3.5 w-3.5">
									<path stroke-linecap="round" stroke-linejoin="round" d="m21 7.5-9-5.25L3 7.5m18 0-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9" />
								</svg>
								<span><?php echo $itemsCount; ?> sản phẩm</span>
							</div>
						</div>
						<div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-slate-600">
							<span>
								Tổng: <span class="font-medium text-slate-900"><?php echo Money::format($total); ?></span>
							</span>
							<span>
								Đã thu: <span class="font-medium text-emerald-600"><?php echo Money::format($paid); ?></span>
							</span>
							<span>
								Còn nợ:
								<span class="font-medium <?php echo $debt > 0 ? 'text-red-600' : 'text-slate-700'; ?>">
									<?php echo Money::format($debt); ?>
								</span>
							</span>
							<span>
								LN:
								<span class="font-medium <?php echo $profit >= 0 ? 'text-emerald-700' : 'text-rose-700'; ?>">
									<?php echo Money::format($profit); ?>
								</span>
							</span>
						</div>
					</div>
				</a>
			<?php } ?>
		</div>
	<?php } ?>
</div>
