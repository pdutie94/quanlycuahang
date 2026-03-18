<?php
$summary = isset($summary) && is_array($summary) ? $summary : [
    'order_count' => 0,
    'total_amount' => 0.0,
    'total_cost' => 0.0,
    'profit' => 0.0,
    'paid_amount' => 0.0,
    'debt_amount' => 0.0,
];
$startDate = isset($startDate) ? $startDate : '';
$endDate = isset($endDate) ? $endDate : '';
$rangeMode = isset($rangeMode) ? $rangeMode : 'day';
if (!in_array($rangeMode, ['day', 'month', 'quarter', 'year'], true)) {
	$rangeMode = 'day';
}

$today = date('Y-m-d');
$dayValue = $startDate !== '' ? $startDate : $today;
$monthValue = $startDate !== '' ? substr($startDate, 0, 7) : date('Y-m');
$yearValue = $startDate !== '' ? substr($startDate, 0, 4) : date('Y');
$quarterValue = '';
$quarterYear = $yearValue;
if (preg_match('/^\d{4}-(\d{2})-\d{2}$/', $dayValue, $m)) {
	$monthNum = (int) $m[1];
	if ($monthNum >= 1 && $monthNum <= 12) {
		$quarterValue = (string) ((int) floor(($monthNum - 1) / 3) + 1);
	}
}
?>

<div class="mb-4 space-y-2">
	<div class="flex items-center justify-between gap-3">
		<div>
			<h1 class="text-lg font-medium tracking-tight">Báo cáo doanh thu chi tiết</h1>
			<p class="mt-0.5 text-sm text-slate-500">Xem danh sách đơn hàng, doanh thu và lợi nhuận theo khoảng thời gian.</p>
		</div>
	</div>
	<?php
	$activeReport = 'sales';
	include __DIR__ . '/_report_nav.php';
	?>
</div>

<form method="get" action="<?php echo $basePath; ?>/report/sales" class="mb-4 rounded-lg bg-white px-3 py-3  ring-1 ring-slate-100" data-sales-filter-form>
	<input type="hidden" name="r" value="report/sales">
	<input type="hidden" name="filter_mode" value="<?php echo htmlspecialchars($rangeMode); ?>" data-sales-mode-input>
	<div class="flex items-center justify-between gap-2">
		<span class="flex text-sm font-medium text-slate-600">Thời gian</span>
		<?php if (!empty($hasDateFilter)) { ?>
			<a href="<?php echo $basePath; ?>/report/sales" class="inline-flex items-center rounded-full px-2 py-0.5 text-sm font-medium text-slate-400 hover:text-slate-600">
				Xóa lọc
			</a>
		<?php } ?>
	</div>
	<div class="mt-2 flex flex-wrap items-center gap-2">
		<button type="button" class="inline-flex items-center rounded-full border px-3 py-1 text-sm font-medium data-[active=\"1\"]:border-emerald-600 data-[active=\"1\"]:bg-emerald-50 data-[active=\"1\"]:text-emerald-700 border-slate-300 text-slate-700 hover:bg-slate-100" data-sales-mode="day">
			Ngày
		</button>
		<button type="button" class="inline-flex items-center rounded-full border px-3 py-1 text-sm font-medium data-[active=\"1\"]:border-emerald-600 data-[active=\"1\"]:bg-emerald-50 data-[active=\"1\"]:text-emerald-700 border-slate-300 text-slate-700 hover:bg-slate-100" data-sales-mode="month">
			Tháng
		</button>
		<button type="button" class="inline-flex items-center rounded-full border px-3 py-1 text-sm font-medium data-[active=\"1\"]:border-emerald-600 data-[active=\"1\"]:bg-emerald-50 data-[active=\"1\"]:text-emerald-700 border-slate-300 text-slate-700 hover:bg-slate-100" data-sales-mode="quarter">
			Quý
		</button>
		<button type="button" class="inline-flex items-center rounded-full border px-3 py-1 text-sm font-medium data-[active=\"1\"]:border-emerald-600 data-[active=\"1\"]:bg-emerald-50 data-[active=\"1\"]:text-emerald-700 border-slate-300 text-slate-700 hover:bg-slate-100" data-sales-mode="year">
			Năm
		</button>
	</div>
	<div class="mt-3 space-y-2">
		<div class="space-y-1" data-sales-input="day">
			<label class="block text-sm font-medium text-slate-600">Chọn ngày</label>
			<?php
			ui_input_text('day', $dayValue, [
				'type' => 'date',
				'class' => 'px-2.5',
				'data-sales-day-input' => '1',
			]);
			?>
		</div>
		<div class="space-y-1 hidden" data-sales-input="month">
			<label class="block text-sm font-medium text-slate-600">Chọn tháng</label>
			<?php
			ui_input_text('month', $monthValue, [
				'type' => 'month',
				'class' => 'px-2.5',
				'data-sales-month-input' => '1',
			]);
			?>
		</div>
		<div class="space-y-1 hidden" data-sales-input="quarter">
			<label class="block text-sm font-medium text-slate-600">Chọn quý</label>
			<div class="grid grid-cols-2 gap-2">
				<div>
					<select name="quarter" class="form-field block w-full rounded-lg border border-slate-300 bg-slate-50 px-2.5 py-1.5 text-sm outline-none focus:border-emerald-500 focus:bg-white" data-sales-quarter-input>
						<option value="">Chọn quý</option>
						<option value="1" <?php echo $quarterValue === '1' ? 'selected' : ''; ?>>Quý 1</option>
						<option value="2" <?php echo $quarterValue === '2' ? 'selected' : ''; ?>>Quý 2</option>
						<option value="3" <?php echo $quarterValue === '3' ? 'selected' : ''; ?>>Quý 3</option>
						<option value="4" <?php echo $quarterValue === '4' ? 'selected' : ''; ?>>Quý 4</option>
					</select>
				</div>
				<div>
					<?php
					ui_input_text('quarter_year', $quarterYear, [
						'type' => 'number',
						'min' => '2000',
						'max' => '2100',
						'class' => 'px-2.5',
						'data-sales-quarter-year-input' => '1',
					]);
					?>
				</div>
			</div>
		</div>
		<div class="space-y-1 hidden" data-sales-input="year">
			<label class="block text-sm font-medium text-slate-600">Chọn năm</label>
			<?php
			ui_input_text('year', $yearValue, [
				'type' => 'number',
				'min' => '2000',
				'max' => '2100',
				'class' => 'px-2.5',
				'data-sales-year-input' => '1',
			]);
			?>
		</div>
	</div>
	<div class="mt-3 flex justify-end">
		<?php ui_button_primary('Lọc', ['type' => 'submit', 'class' => 'px-3 py-1.5', 'data-loading-button' => '1']); ?>
	</div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function () {
	var form = document.querySelector('[data-sales-filter-form]');
	if (!form) return;

	var modeInput = form.querySelector('[data-sales-mode-input]');
	var dayInput = form.querySelector('[data-sales-day-input]');
	var monthInput = form.querySelector('[data-sales-month-input]');
	var quarterInput = form.querySelector('[data-sales-quarter-input]');
	var quarterYearInput = form.querySelector('[data-sales-quarter-year-input]');
	var yearInput = form.querySelector('[data-sales-year-input]');

	function setActiveMode(mode) {
		if (modeInput) {
			modeInput.value = mode;
		}
		form.querySelectorAll('[data-sales-mode]').forEach(function (btn) {
			var btnMode = btn.getAttribute('data-sales-mode');
			btn.dataset.active = btnMode === mode ? '1' : '0';
		});
		form.querySelectorAll('[data-sales-input]').forEach(function (wrapper) {
			var wrapperMode = wrapper.getAttribute('data-sales-input');
			if (wrapperMode === mode) {
				wrapper.classList.remove('hidden');
			} else {
				wrapper.classList.add('hidden');
			}
		});
	}

	form.querySelectorAll('[data-sales-mode]').forEach(function (btn) {
		btn.addEventListener('click', function (e) {
			e.preventDefault();
			var mode = btn.getAttribute('data-sales-mode') || 'day';
			setActiveMode(mode);
		});
	});

	var initialMode = modeInput ? (modeInput.value || 'day') : 'day';
	setActiveMode(initialMode);

	form.addEventListener('submit', function () {
		var mode = modeInput ? (modeInput.value || 'day') : 'day';

		if (dayInput) {
			if (mode !== 'day') {
				dayInput.removeAttribute('name');
			}
		}
		if (monthInput) {
			if (mode !== 'month') {
				monthInput.removeAttribute('name');
			}
		}
		if (quarterInput) {
			if (mode !== 'quarter') {
				quarterInput.removeAttribute('name');
			}
		}
		if (quarterYearInput) {
			if (mode !== 'quarter') {
				quarterYearInput.removeAttribute('name');
			}
		}
		if (yearInput) {
			if (mode !== 'year') {
				yearInput.removeAttribute('name');
			}
		}
	});
});
</script>

	<div class="mb-4 space-y-2">
	<div class="text-sm font-medium uppercase  text-slate-500">Tổng quan doanh thu</div>
	<div class="grid grid-cols-2 gap-3">
		<div class="rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-3 text-sm text-emerald-900 ">
			<div class="text-sm font-medium uppercase  text-emerald-700">Số đơn hàng</div>
			<div class="mt-2 text-lg font-medium"><?php echo Money::format($summary['order_count'], ''); ?></div>
		</div>
		<div class="rounded-lg border border-sky-200 bg-sky-50 px-3 py-3 text-sm text-sky-900 ">
			<div class="text-sm font-medium uppercase  text-sky-700">Tổng doanh thu</div>
			<div class="mt-2 text-lg font-medium"><?php echo Money::format($summary['total_amount']); ?></div>
		</div>
		<div class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-3 text-sm text-amber-900 ">
			<div class="text-sm font-medium uppercase  text-amber-700">Tổng lợi nhuận</div>
			<div class="mt-2 text-lg font-medium"><?php echo Money::format($summary['profit']); ?></div>
		</div>
		<div class="rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-3 text-sm text-indigo-900 ">
			<div class="text-sm font-medium uppercase  text-indigo-700">Đã thu</div>
			<div class="mt-2 text-lg font-medium"><?php echo Money::format($summary['paid_amount']); ?></div>
		</div>
		<div class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-3 text-sm text-rose-900 ">
			<div class="text-sm font-medium uppercase  text-rose-700">Còn nợ</div>
			<div class="mt-2 text-lg font-medium"><?php echo Money::format($summary['debt_amount']); ?></div>
		</div>
	</div>
</div>

<?php if ($rangeMode === 'month' && !empty($dailyStats)) { ?>
	<div class="mb-4 space-y-2">
		<div class="flex items-center justify-between gap-2">
			<div class="text-sm font-medium uppercase  text-slate-500">Thống kê theo ngày trong tháng</div>
		</div>
		<div class="grid grid-cols-1 gap-2">
			<?php foreach ($dailyStats as $row) { ?>
				<?php
				$dayDate = isset($row['day']) ? $row['day'] : '';
				$orderCount = isset($row['order_count']) ? (int) $row['order_count'] : 0;
				$totalAmount = isset($row['total_amount']) ? (float) $row['total_amount'] : 0.0;
				$totalCost = isset($row['total_cost']) ? (float) $row['total_cost'] : 0.0;
				$profit = $totalAmount - $totalCost;
				$margin = 0.0;
				if ($totalAmount > 0) {
					$margin = ($profit / $totalAmount) * 100;
				}
				$dayLabel = $dayDate ? date('d/m/Y', strtotime($dayDate)) : '';
				?>
				<div class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm ">
					<div class="flex items-center justify-between">
						<div class="text-sm font-medium text-slate-800"><?php echo htmlspecialchars($dayLabel); ?></div>
						<div class="text-sm text-slate-500"><?php echo $orderCount; ?> đơn</div>
					</div>
					<div class="mt-1 text-sm text-slate-500">
						<span>Tổng tiền:</span>
						<span class="ml-1 font-medium text-slate-900"><?php echo Money::format($totalAmount); ?></span>
					</div>
					<div class="mt-1 text-sm text-slate-500">
						<span>Lợi nhuận:</span>
						<span class="ml-1 font-medium text-emerald-600"><?php echo Money::format($profit); ?></span>
					</div>
					<div class="mt-1 text-sm text-slate-500">
						<span>Biên lợi nhuận:</span>
						<span class="ml-1 font-medium <?php echo $margin >= 0 ? 'text-emerald-600' : 'text-rose-600'; ?>">
							<?php echo number_format($margin, 1); ?>%
						</span>
					</div>
				</div>
			<?php } ?>
		</div>
	</div>
<?php } ?>

<div class="mb-2 mt-4 text-sm font-medium uppercase  text-slate-500">
	Danh sách đơn hàng
</div>

<?php include __DIR__ . '/sales_orders_list.php'; ?>
