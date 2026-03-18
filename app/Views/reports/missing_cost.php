<?php
$items = isset($items) && is_array($items) ? $items : [];
$summary = isset($summary) && is_array($summary) ? $summary : [
    'item_count' => 0,
    'order_count' => 0,
    'total_delta_cost' => 0.0,
];
$keyword = isset($keyword) ? $keyword : '';
$startDate = isset($startDate) ? $startDate : '';
$endDate = isset($endDate) ? $endDate : '';
?>

<div class="mb-4 space-y-2">
	<div class="flex items-center justify-between gap-3">
		<div>
			<h1 class="text-lg font-medium tracking-tight text-slate-900">Cập nhật giá vốn thiếu</h1>
			<p class="mt-0.5 text-sm text-slate-500">Thống kê các dòng đơn hàng chưa có hoặc có giá vốn bằng 0 và cho phép cập nhật theo giá vốn hiện tại của sản phẩm.</p>
		</div>
	</div>
	<?php
	$activeReport = 'missing-cost';
	include __DIR__ . '/_report_nav.php';
	?>
</div>

<form method="get" action="<?php echo $basePath; ?>/report/missing-cost" class="mb-4 rounded-lg bg-white px-3 py-3  border border-slate-200 space-y-3">
	<input type="hidden" name="r" value="report/missingCost">
	<div class="grid grid-cols-1 gap-3 md:grid-cols-4">
			<div class="space-y-1">
				<label class="block text-sm font-medium text-slate-600">Từ ngày</label>
			<?php
			ui_input_text('start_date', $startDate, [
				'type' => 'date',
				'class' => 'px-2.5',
			]);
			?>
		</div>
			<div class="space-y-1">
				<label class="block text-sm font-medium text-slate-600">Đến ngày</label>
			<?php
			ui_input_text('end_date', $endDate, [
				'type' => 'date',
				'class' => 'px-2.5',
			]);
			?>
		</div>
			<div class="space-y-1">
				<label class="block text-sm font-medium text-slate-600">Tìm kiếm</label>
			<?php
			ui_input_text('q', $keyword, [
				'placeholder' => 'Mã đơn, tên SP, khách hàng, số ĐT...',
				'class' => 'px-2.5',
			]);
			?>
		</div>
		<div class="flex items-end justify-end">
			<?php ui_button_secondary('Lọc', ['type' => 'submit', 'class' => 'px-3 py-1.5 text-sm', 'data-loading-button' => '1']); ?>
		</div>
	</div>
</form>

	<div class="mb-4 space-y-2">
	<div class="text-sm font-medium uppercase  text-slate-500">Tổng quan dữ liệu thiếu giá vốn</div>
	<div class="grid grid-cols-1 gap-3 md:grid-cols-3">
		<div class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-3 text-sm text-amber-900 ">
			<div class="text-sm font-medium uppercase  text-amber-700">Số dòng thiếu giá vốn</div>
			<div class="mt-2 text-lg font-medium"><?php echo Money::format($summary['item_count'], ''); ?></div>
		</div>
		<div class="rounded-lg border border-sky-200 bg-sky-50 px-3 py-3 text-sm text-sky-900 ">
			<div class="text-sm font-medium uppercase  text-sky-700">Số đơn hàng bị ảnh hưởng</div>
			<div class="mt-2 text-lg font-medium"><?php echo Money::format($summary['order_count'], ''); ?></div>
		</div>
		<div class="rounded-lg border border-brand-200 bg-brand-50 px-3 py-3 text-sm text-brand-900 ">
			<div class="text-sm font-medium uppercase  text-brand-700">Tổng giá vốn dự kiến tăng</div>
			<div class="mt-2 text-lg font-medium"><?php echo Money::format($summary['total_delta_cost']); ?></div>
		</div>
	</div>
</div>

<?php if (empty($items)) { ?>
	<div class="rounded-xl border border-dashed border-slate-300 bg-white px-4 py-6 text-center text-sm text-slate-500">
		Hiện không tìm thấy dòng đơn hàng nào có giá vốn bằng 0 trong phạm vi lọc.
	</div>
<?php } else { ?>
	<form method="post" action="<?php echo $basePath; ?>/report/missing-cost" class="space-y-3" data-missing-cost-form>
		<input type="hidden" hidden name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
		<input type="hidden" hidden name="r" value="report/missingCost">
		<div class="flex flex-wrap items-center justify-between gap-2">
			<div class="text-sm text-slate-600">
				<span class="font-medium"><?php echo (int) $summary['item_count']; ?></span> dòng đơn hàng, 
				<span class="font-medium"><?php echo (int) $summary['order_count']; ?></span> đơn bị ảnh hưởng.
			</div>
			<div class="flex flex-wrap items-center gap-2">
				<button type="button" class="inline-flex items-center rounded-full border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-100" data-missing-cost-select-all>
					Chọn tất cả
				</button>
				<button type="button" class="inline-flex items-center rounded-full border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-100" data-missing-cost-unselect-all>
					Bỏ chọn
				</button>
				<?php ui_button_secondary('Cập nhật đã chọn', ['type' => 'submit', 'name' => 'mode', 'value' => 'selected', 'class' => 'px-3 py-1.5 text-sm', 'data-loading-button' => '1']); ?>
				<?php ui_button_primary('Cập nhật tất cả', ['type' => 'submit', 'name' => 'mode', 'value' => 'all', 'class' => 'px-3 py-1.5 text-sm', 'data-loading-button' => '1']); ?>
			</div>
		</div>

		<div class="overflow-x-auto rounded-xl border border-slate-200 bg-white ">
			<table class="min-w-full text-sm text-left text-slate-700">
				<thead>
					<tr class="bg-slate-50 text-sm uppercase  text-slate-500">
						<th class="px-3 py-2 border-b border-slate-200">
							<input type="checkbox" class="h-4 w-4 rounded border-slate-300 text-brand-600" data-missing-cost-master>
						</th>
						<th class="px-3 py-2 border-b border-slate-200">Đơn hàng</th>
						<th class="px-3 py-2 border-b border-slate-200">Khách hàng</th>
						<th class="px-3 py-2 border-b border-slate-200">Sản phẩm</th>
						<th class="px-3 py-2 border-b border-slate-200">SL</th>
						<th class="px-3 py-2 border-b border-slate-200">Giá bán</th>
						<th class="px-3 py-2 border-b border-slate-200">Giá vốn trong đơn</th>
						<th class="px-3 py-2 border-b border-slate-200">Giá vốn hiện tại</th>
						<th class="px-3 py-2 border-b border-slate-200">Giá vốn dòng sau cập nhật</th>
					</tr>
				</thead>
				<tbody class="divide-y divide-slate-100">
					<?php foreach ($items as $row) { ?>
						<?php
						$itemId = (int) $row['item_id'];
						$orderId = (int) $row['order_id'];
						$qty = isset($row['qty']) ? (float) $row['qty'] : 0.0;
						$priceSell = isset($row['price_sell']) ? (float) $row['price_sell'] : 0.0;
						$itemPriceCost = isset($row['item_price_cost']) ? (float) $row['item_price_cost'] : 0.0;
						$unitPriceCost = isset($row['unit_price_cost']) ? (float) $row['unit_price_cost'] : 0.0;
						if ($itemPriceCost < 0) {
							$itemPriceCost = 0;
						}
						$oldCostTotal = $itemPriceCost * $qty;
						$newCostTotal = $unitPriceCost > 0 ? $unitPriceCost * $qty : 0;
						?>
						<tr>
							<td class="px-3 py-2 align-top">
								<input type="checkbox" name="item_ids[]" value="<?php echo $itemId; ?>" class="h-4 w-4 rounded border-slate-300 text-brand-600" data-missing-cost-item>
							</td>
							<td class="px-3 py-2 align-top">
								<div class="flex flex-col">
									<a href="<?php echo $basePath; ?>/order/view?id=<?php echo $orderId; ?>" class="font-mono text-sm font-medium text-brand-700 hover:underline">
										<?php echo htmlspecialchars($row['order_code']); ?>
									</a>
									<div class="text-sm text-slate-500">
										<?php echo htmlspecialchars(format_datetime($row['order_date'])); ?>
									</div>
								</div>
							</td>
							<td class="px-3 py-2 align-top">
								<div class="text-sm text-slate-700">
									<?php if (!empty($row['customer_name'])) { ?>
									<div class="font-medium text-slate-800"><?php echo htmlspecialchars($row['customer_name']); ?></div>
									<?php } else { ?>
										<div class="text-slate-400">Khách lẻ</div>
									<?php } ?>
									<?php if (!empty($row['customer_phone'])) { ?>
										<div class="text-slate-500"><?php echo htmlspecialchars($row['customer_phone']); ?></div>
									<?php } ?>
								</div>
							</td>
							<td class="px-3 py-2 align-top">
								<div class="text-sm text-slate-700">
									<div class="font-medium text-slate-900">
										<?php echo htmlspecialchars($row['product_name']); ?>
									</div>
									<div class="text-slate-500">
										<?php if (!empty($row['product_code'])) { ?>
										<span class="font-mono text-sm">Mã: <?php echo htmlspecialchars($row['product_code']); ?></span>
										<?php } ?>
										<?php if (!empty($row['unit_name'])) { ?>
											<span class="ml-1 text-sm text-slate-500">(<?php echo htmlspecialchars($row['unit_name']); ?>)</span>
										<?php } ?>
									</div>
								</div>
							</td>
							<td class="px-3 py-2 align-top">
								<div class="text-sm text-slate-800">
									<?php echo rtrim(rtrim(number_format($qty, 2, ',', ''), '0'), ','); ?>
								</div>
							</td>
							<td class="px-3 py-2 align-top">
								<div class="text-sm text-slate-800">
									<?php echo Money::format($priceSell); ?>
								</div>
							</td>
							<td class="px-3 py-2 align-top">
								<div class="text-sm <?php echo $itemPriceCost <= 0 ? 'text-rose-600 font-medium' : 'text-slate-800'; ?>">
									<?php echo Money::format($itemPriceCost); ?>
								</div>
								<?php if ($oldCostTotal > 0) { ?>
									<div class="text-sm text-slate-500">Tổng: <?php echo Money::format($oldCostTotal); ?></div>
								<?php } ?>
							</td>
							<td class="px-3 py-2 align-top">
								<div class="text-sm <?php echo $unitPriceCost > 0 ? 'text-brand-700 font-medium' : 'text-slate-400'; ?>">
									<?php echo Money::format($unitPriceCost); ?>
								</div>
							</td>
							<td class="px-3 py-2 align-top">
								<?php if ($newCostTotal > 0) { ?>
									<div class="text-sm text-brand-700 font-medium">
										<?php echo Money::format($newCostTotal); ?>
									</div>
									<?php if ($newCostTotal > $oldCostTotal) { ?>
										<div class="text-sm text-amber-700">
											+<?php echo Money::format($newCostTotal - $oldCostTotal); ?> so với hiện tại
										</div>
									<?php } ?>
								<?php } else { ?>
									<div class="text-sm text-slate-400">
										Chưa có giá vốn hiện tại
									</div>
								<?php } ?>
							</td>
						</tr>
					<?php } ?>
				</tbody>
			</table>
		</div>
	</form>

	<script>
	document.addEventListener('DOMContentLoaded', function () {
		var form = document.querySelector('[data-missing-cost-form]');
		if (!form) return;

		var master = form.querySelector('[data-missing-cost-master]');
		var items = form.querySelectorAll('[data-missing-cost-item]');
		var btnSelectAll = form.querySelector('[data-missing-cost-select-all]');
		var btnUnselectAll = form.querySelector('[data-missing-cost-unselect-all]');

		function refreshMaster() {
			var total = items.length;
			var checked = 0;
			items.forEach(function (el) {
				if (el.checked) checked++;
			});
			if (!master) return;
			if (checked === 0) {
				master.indeterminate = false;
				master.checked = false;
			} else if (checked === total) {
				master.indeterminate = false;
				master.checked = true;
			} else {
				master.indeterminate = true;
			}
		}

		if (master) {
			master.addEventListener('change', function () {
				var checked = master.checked;
				items.forEach(function (el) {
					el.checked = checked;
				});
				refreshMaster();
			});
		}

		items.forEach(function (el) {
			el.addEventListener('change', function () {
				refreshMaster();
			});
		});

		if (btnSelectAll) {
			btnSelectAll.addEventListener('click', function (e) {
				e.preventDefault();
				items.forEach(function (el) {
					el.checked = true;
				});
				refreshMaster();
			});
		}

		if (btnUnselectAll) {
			btnUnselectAll.addEventListener('click', function (e) {
				e.preventDefault();
				items.forEach(function (el) {
					el.checked = false;
				});
				refreshMaster();
			});
		}
	});
	</script>
<?php } ?>
