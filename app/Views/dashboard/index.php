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

<?php
$recentOrders = isset($recentOrders) && is_array($recentOrders) ? $recentOrders : [];
$lowStockPreview = array_slice($lowStockItems, 0, 5);
?>

<div class="space-y-4">
	<div class="rounded-2xl bg-slate-900 px-4 py-4 text-white ">
		<div class="flex items-start justify-between gap-3">
			<div>
				<h1 class="text-base font-semibold">Tổng quan hôm nay</h1>
				<p class="mt-1 text-sm text-slate-200 no-underline">Số liệu và thao tác nhanh trong ngày</p>
			</div>
			<span class="inline-flex rounded-full bg-white/15 px-2 py-0.5 text-sm font-medium text-slate-100"><?php echo date('d/m/Y'); ?></span>
		</div>
		<div class="mt-3 grid grid-cols-2 gap-2 text-sm">
			<div class="rounded-xl bg-white/10 px-3 py-2">
				<div class="text-sm text-slate-200">Doanh thu</div>
				<div class="mt-0.5 font-semibold text-white"><?php echo Money::format($ordersToday['total_amount']); ?></div>
			</div>
			<div class="rounded-xl bg-white/10 px-3 py-2">
				<div class="text-sm text-slate-200">Lợi nhuận</div>
				<div class="mt-0.5 font-semibold text-white"><?php echo Money::format($ordersToday['profit']); ?></div>
			</div>
			<div class="rounded-xl bg-white/10 px-3 py-2">
				<div class="text-sm text-slate-200">Đã thu</div>
				<div class="mt-0.5 font-semibold text-white"><?php echo Money::format($ordersToday['paid_amount']); ?></div>
			</div>
			<div class="rounded-xl bg-white/10 px-3 py-2">
				<div class="text-sm text-slate-200">Còn nợ</div>
				<div class="mt-0.5 font-semibold text-white"><?php echo Money::format($ordersToday['debt_amount']); ?></div>
			</div>
		</div>
	</div>

	<div class="space-y-2">
		<div class="text-sm font-semibold uppercase text-slate-500">Lối tắt nhanh</div>
		<div class="grid grid-cols-2 gap-2">
			<a href="<?php echo $basePath; ?>/pos" class="flex items-center gap-2 rounded-2xl bg-emerald-600 px-3 py-3 text-sm font-semibold text-white ">
				<span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-500/90">
					<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4">
						<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" />
					</svg>
				</span>
				<span>Tạo đơn</span>
			</a>
			<a href="<?php echo $basePath; ?>/order" class="flex items-center gap-2 rounded-2xl bg-white px-3 py-3 text-sm font-medium text-slate-700  ring-1 ring-slate-100">
				<span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-slate-100 text-slate-600">
					<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4">
						<path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 3h6m-6 3h6M7.5 4.5h9.75A1.75 1.75 0 0 1 19 6.25v13a1.75 1.75 0 0 1-1.75 1.75H6.75A1.75 1.75 0 0 1 5 19.25v-13A1.75 1.75 0 0 1 6.75 4.5Z" />
					</svg>
				</span>
				<span>Đơn hàng</span>
			</a>
			<a href="<?php echo $basePath; ?>/product/create" class="flex items-center gap-2 rounded-2xl bg-white px-3 py-3 text-sm font-medium text-slate-700  ring-1 ring-slate-100">
				<span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-slate-100 text-slate-600">
					<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4">
						<path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
					</svg>
				</span>
				<span>Thêm sản phẩm</span>
			</a>
			<a href="<?php echo $basePath; ?>/report" class="flex items-center gap-2 rounded-2xl bg-white px-3 py-3 text-sm font-medium text-slate-700  ring-1 ring-slate-100">
				<span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-slate-100 text-slate-600">
					<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4">
						<path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6a7.5 7.5 0 1 0 7.5 7.5h-7.5V6Z" />
						<path stroke-linecap="round" stroke-linejoin="round" d="M13.5 10.5H21A7.5 7.5 0 0 0 13.5 3v7.5Z" />
					</svg>
				</span>
				<span>Báo cáo</span>
			</a>
		</div>
	</div>



	<?php if (!empty($lowStockPreview)) { ?>
		<div class="space-y-2">
			<div class="flex items-center justify-between">
				<div class="text-sm font-semibold uppercase text-rose-600">Hàng sắp hết</div>
				<a href="<?php echo $basePath; ?>/report/inventory" class="text-sm font-medium text-rose-600 hover:text-rose-700">Xem tồn kho</a>
			</div>
			<div class="space-y-2">
				<?php foreach ($lowStockPreview as $row) { ?>
					<?php
					$qtyBase = isset($row['qty_base']) ? (float) $row['qty_base'] : 0.0;
					$minStock = isset($row['min_stock_qty']) ? (float) $row['min_stock_qty'] : 0.0;
					$ratio = $minStock > 0 ? ($qtyBase / $minStock) : 0;
					$qtyText = rtrim(rtrim(number_format($qtyBase, 2, ',', ''), '0'), ',');
					if ($qtyText === '') {
						$qtyText = '0';
					}
					?>
					<div class="flex items-center justify-between rounded-2xl border border-rose-100 bg-rose-50 px-3 py-2 text-sm">
						<div class="min-w-0">
							<div class="truncate font-medium text-rose-900"><?php echo htmlspecialchars($row['name']); ?></div>
							<div class="mt-0.5 text-sm text-rose-700">Tồn: <?php echo $qtyText; ?> <?php echo htmlspecialchars($row['base_unit_name']); ?></div>
						</div>
						<?php if ($ratio <= 0.3) { ?>
							<span class="ml-2 rounded-full bg-rose-600 px-2 py-0.5 text-sm font-medium text-white">Rất thấp</span>
						<?php } elseif ($ratio <= 0.7) { ?>
							<span class="ml-2 rounded-full bg-rose-500 px-2 py-0.5 text-sm font-medium text-white">Sắp hết</span>
						<?php } else { ?>
							<span class="ml-2 rounded-full bg-rose-400 px-2 py-0.5 text-sm font-medium text-white">Thấp</span>
						<?php } ?>
					</div>
				<?php } ?>
			</div>
		</div>
	<?php } ?>

	<div class="space-y-2">
		<div class="flex items-center justify-between">
			<div class="text-sm font-semibold uppercase text-slate-500">Đơn hàng gần đây</div>
			<a href="<?php echo $basePath; ?>/order" class="text-sm font-medium text-emerald-600 hover:text-emerald-700">Xem tất cả</a>
		</div>

		<?php if (empty($recentOrders)) { ?>
			<div class="rounded-2xl bg-white px-4 py-3 text-sm text-slate-500  ring-1 ring-slate-100">Không có đơn hàng gần đây.</div>
		<?php } else { ?>
			<div class="space-y-2">
				<?php foreach ($recentOrders as $order) { ?>
					<?php
					$orderCardData = $order;
					$orderCardUrl = $basePath . '/order/view?id=' . (int) $order['id'];
					$orderCardExtraAttrs = '';
					include __DIR__ . '/../partials/order_item_card.php';
					?>
				<?php } ?>
			</div>
		<?php } ?>
	</div>
</div>

<?php include __DIR__ . '/../partials/order_preview_modal.php'; ?>
