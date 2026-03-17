<?php
$rows = isset($rows) && is_array($rows) ? $rows : [];
$summary = isset($summary) && is_array($summary) ? $summary : ['total_amount' => 0, 'paid_amount' => 0, 'debt_amount' => 0];
$startDate = isset($startDate) ? $startDate : '';
$endDate = isset($endDate) ? $endDate : '';
$keyword = isset($keyword) ? $keyword : '';
$showAll = !empty($showAll);
?>

<div class="mb-4 space-y-2">
	<div class="flex items-center justify-between gap-3">
		<div>
			<h1 class="text-lg font-medium tracking-tight">Công nợ khách hàng</h1>
			<p class="mt-0.5 text-sm text-slate-500">Danh sách khách hàng và số tiền còn nợ theo đơn bán hàng.</p>
		</div>
	</div>
	<?php
	$activeReport = 'customer-debt';
	include __DIR__ . '/_report_nav.php';
	?>

	<form method="get" class="rounded-xl border border-slate-200 bg-white px-3 py-3 text-sm shadow-sm space-y-3">
		<input type="hidden" name="r" value="report/customerDebt">
		<div class="grid grid-cols-1 gap-3 sm:grid-cols-4">
			<div class="space-y-1">
				<label class="block text-sm font-medium uppercase  text-slate-500">Từ ngày</label>
				<?php
				ui_input_text('start_date', $startDate, [
					'type' => 'date',
					'class' => 'px-3 py-1.5',
				]);
				?>
			</div>
			<div class="space-y-1">
				<label class="block text-sm font-medium uppercase  text-slate-500">Đến ngày</label>
				<?php
				ui_input_text('end_date', $endDate, [
					'type' => 'date',
					'class' => 'px-3 py-1.5',
				]);
				?>
			</div>
			<div class="space-y-1">
				<label class="block text-sm font-medium uppercase  text-slate-500">Từ khóa</label>
				<?php
				ui_input_text('q', $keyword, [
					'placeholder' => 'Tên, SĐT, địa chỉ',
					'class' => 'px-3 py-1.5',
				]);
				?>
			</div>
			<div class="space-y-1">
				<label class="block text-sm font-medium uppercase  text-slate-500">Tùy chọn</label>
				<label class="inline-flex items-center gap-2 text-sm text-slate-700">
					<input type="checkbox" name="show_all" value="1" <?php echo $showAll ? 'checked' : ''; ?> class="h-3 w-3 rounded border-slate-300 text-emerald-600">
					<span>Hiển thị cả khách đã thanh toán đủ</span>
				</label>
			</div>
		</div>
		<div class="flex items-center justify-end gap-2">
			<a href="<?php echo $basePath; ?>/report/customer-debt" class="inline-flex items-center rounded-full px-3 py-1 text-sm font-medium text-slate-500 hover:bg-slate-100">Đặt lại</a>
			<?php ui_button_primary('Lọc dữ liệu', ['type' => 'submit', 'class' => 'px-3 py-1 text-sm', 'data-loading-button' => '1']); ?>
		</div>
	</form>
</div>

<div class="space-y-4">
	<div class="grid grid-cols-1 gap-3 md:grid-cols-3">
		<div class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-3 text-sm text-slate-900 shadow-sm">
			<div class="text-sm font-medium uppercase  text-slate-600">Tổng doanh số</div>
			<div class="mt-2 text-lg font-medium"><?php echo Money::format($summary['total_amount']); ?></div>
		</div>
		<div class="rounded-xl border border-emerald-100 bg-emerald-50 px-3 py-3 text-sm text-emerald-900 shadow-sm">
			<div class="text-sm font-medium uppercase  text-emerald-700">Đã thu</div>
			<div class="mt-2 text-lg font-medium"><?php echo Money::format($summary['paid_amount']); ?></div>
		</div>
		<div class="rounded-xl border border-red-100 bg-red-50 px-3 py-3 text-sm text-red-900 shadow-sm">
			<div class="text-sm font-medium uppercase  text-red-700">Còn nợ</div>
			<div class="mt-2 text-lg font-medium"><?php echo Money::format($summary['debt_amount']); ?></div>
		</div>
	</div>

		<div class="rounded-xl border border-slate-200 bg-white text-sm shadow-sm">
		<div class="flex items-center justify-between border-b border-slate-100 px-4 py-2">
			<div class="text-sm font-medium uppercase  text-slate-500">Danh sách công nợ khách hàng</div>
			<div class="text-sm text-slate-500">
				Tổng bản ghi: <?php echo count($rows); ?>
			</div>
		</div>
		<?php if (empty($rows)) { ?>
			<div class="px-4 py-6 text-center text-sm text-slate-500">
				Không có dữ liệu công nợ phù hợp với điều kiện lọc.
			</div>
		<?php } else { ?>
			<div class="overflow-x-auto">
				<table class="min-w-full divide-y divide-slate-100 text-sm">
					<thead class="bg-slate-50">
						<tr>
							<th class="px-3 py-2 text-left font-medium text-slate-600">Khách hàng</th>
							<th class="px-3 py-2 text-left font-medium text-slate-600">Liên hệ</th>
							<th class="px-3 py-2 text-right font-medium text-slate-600">Tổng đơn</th>
							<th class="px-3 py-2 text-right font-medium text-slate-600">Đã thu</th>
							<th class="px-3 py-2 text-right font-medium text-slate-600">Còn nợ</th>
							<th class="px-3 py-2 text-right font-medium text-slate-600">Chi tiết</th>
						</tr>
					</thead>
					<tbody class="divide-y divide-slate-100 bg-white">
						<?php foreach ($rows as $row) { ?>
							<tr class="hover:bg-slate-50">
								<td class="px-3 py-2 align-top">
									<div class="font-medium text-slate-900">
										<?php echo htmlspecialchars($row['name'] !== '' ? $row['name'] : 'Khách lẻ'); ?>
									</div>
									<?php if (!empty($row['address'])) { ?>
										<div class="mt-0.5 text-sm text-slate-500">
											<?php echo htmlspecialchars($row['address']); ?>
										</div>
									<?php } ?>
								</td>
								<td class="px-3 py-2 align-top text-sm text-slate-600">
									<?php if (!empty($row['phone'])) { ?>
										<div>SĐT: <?php echo htmlspecialchars($row['phone']); ?></div>
									<?php } else { ?>
										<div class="text-slate-400">Không có</div>
									<?php } ?>
								</td>
								<td class="px-3 py-2 align-top text-right">
									<?php echo Money::format($row['total_amount']); ?>
								</td>
								<td class="px-3 py-2 align-top text-right text-emerald-700">
									<?php echo Money::format($row['paid_amount']); ?>
								</td>
								<td class="px-3 py-2 align-top text-right <?php echo $row['debt_amount'] > 0 ? 'text-red-600 font-medium' : 'text-slate-500'; ?>">
									<?php echo Money::format($row['debt_amount']); ?>
								</td>
								<td class="px-3 py-2 align-top text-right">
									<?php if (!empty($row['id'])) { ?>
										<a href="<?php echo $basePath; ?>/customer/view?id=<?php echo (int) $row['id']; ?>" class="inline-flex items-center rounded-full border border-slate-300 px-2 py-1 text-sm font-medium text-slate-700 hover:bg-slate-100">
											Xem đơn
										</a>
									<?php } ?>
								</td>
							</tr>
						<?php } ?>
					</tbody>
				</table>
			</div>
		<?php } ?>
	</div>
</div>
