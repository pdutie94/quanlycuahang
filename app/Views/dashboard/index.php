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

<div class="space-y-5">
	<div class="rounded-card border border-slate-200 bg-white px-4 py-5">
		<div class="flex items-start justify-between gap-3">
			<div>
				<h1 class="font-display text-lg font-bold tracking-tight text-slate-900">Tổng quan hôm nay</h1>
				<p class="mt-1 text-sm text-slate-500 no-underline">Số liệu và thao tác nhanh trong ngày</p>
			</div>
			<span class="inline-flex rounded-chip border border-slate-300 bg-slate-50 px-3 py-1 text-sm font-medium text-slate-700"><?php echo date('d/m/Y'); ?></span>
		</div>
		<div class="mt-3 grid grid-cols-2 gap-2 text-sm">
			<div class="app-kpi-card">
				<div class="text-sm text-slate-500">Doanh thu</div>
				<div class="mt-1 font-semibold text-slate-900"><?php echo Money::format($ordersToday['total_amount']); ?></div>
			</div>
			<div class="app-kpi-card">
				<div class="text-sm text-slate-500">Lợi nhuận</div>
				<div class="mt-1 font-semibold text-slate-900"><?php echo Money::format($ordersToday['profit']); ?></div>
			</div>
			<div class="app-kpi-card">
				<div class="text-sm text-slate-500">Đã thu</div>
				<div class="mt-1 font-semibold text-slate-900"><?php echo Money::format($ordersToday['paid_amount']); ?></div>
			</div>
			<div class="app-kpi-card">
				<div class="text-sm text-slate-500">Còn nợ</div>
				<div class="mt-1 font-semibold text-slate-900"><?php echo Money::format($ordersToday['debt_amount']); ?></div>
			</div>
		</div>
	</div>

	<div class="space-y-3">
		<div class="text-sm font-semibold uppercase tracking-wide text-slate-500">Lối tắt nhanh</div>
		<div class="grid grid-cols-2 gap-2">
			<a href="<?php echo $basePath; ?>/pos" class="flex items-center gap-2 rounded-card border border-brand-600 bg-brand-600 px-3 py-3 text-sm font-semibold text-white">
				<span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-white/20">
					<?php echo ui_icon('cart', 'h-4 w-4'); ?>
				</span>
				<span>Tạo đơn</span>
			</a>
			<a href="<?php echo $basePath; ?>/order" class="flex items-center gap-2 rounded-card border border-slate-200 bg-white px-3 py-3 text-sm font-medium text-slate-700">
				<span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-slate-100 text-slate-600">
					<?php echo ui_icon('clipboard-document', 'h-4 w-4'); ?>
				</span>
				<span>Đơn hàng</span>
			</a>
			<a href="<?php echo $basePath; ?>/product/create" class="flex items-center gap-2 rounded-card border border-slate-200 bg-white px-3 py-3 text-sm font-medium text-slate-700">
				<span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-slate-100 text-slate-600">
					<?php echo ui_icon('plus', 'h-4 w-4'); ?>
				</span>
				<span>Thêm sản phẩm</span>
			</a>
			<a href="<?php echo $basePath; ?>/report" class="flex items-center gap-2 rounded-card border border-slate-200 bg-white px-3 py-3 text-sm font-medium text-slate-700">
				<span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-slate-100 text-slate-600">
					<?php echo ui_icon('chart-pie', 'h-4 w-4'); ?>
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
					<div class="flex items-center justify-between rounded-card border border-rose-100 bg-rose-50 px-3 py-2 text-sm">
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

	<div class="space-y-3">
		<div class="flex items-center justify-between">
			<div class="text-sm font-semibold uppercase tracking-wide text-slate-500">Đơn hàng gần đây</div>
			<a href="<?php echo $basePath; ?>/order" class="text-sm font-medium text-brand-600 hover:text-brand-700">Xem tất cả</a>
		</div>

		<?php if (empty($recentOrders)) { ?>
			<div class="rounded-card border border-slate-200 bg-white px-4 py-3 text-sm text-slate-500">Không có đơn hàng gần đây.</div>
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
