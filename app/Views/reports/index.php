<?php
$ordersToday = isset($ordersToday) ? $ordersToday : ['total_amount' => 0, 'total_cost' => 0, 'profit' => 0, 'paid_amount' => 0, 'debt_amount' => 0];
$ordersMonth = isset($ordersMonth) ? $ordersMonth : ['total_amount' => 0, 'total_cost' => 0, 'profit' => 0, 'paid_amount' => 0, 'debt_amount' => 0];
$purchasesMonth = isset($purchasesMonth) ? $purchasesMonth : ['total_amount' => 0, 'paid_amount' => 0, 'debt_amount' => 0];
$customerDebt = isset($customerDebt) ? (float) $customerDebt : 0;
$supplierDebt = isset($supplierDebt) ? (float) $supplierDebt : 0;
$delta = isset($delta) && is_array($delta) ? $delta : [];
$updatedAtText = isset($updatedAtText) ? (string) $updatedAtText : date('H:i d/m');

$formatDelta = function ($deltaItem, $periodLabel) {
	$amount = isset($deltaItem['amount']) ? (float) $deltaItem['amount'] : 0;
	$percent = isset($deltaItem['percent']) ? $deltaItem['percent'] : null;
	$isUp = $amount > 0;
	$isDown = $amount < 0;
	$prefix = $isUp ? '+' : '';
	$className = $isUp ? 'text-emerald-600' : ($isDown ? 'text-rose-600' : 'text-slate-500');
	if ($percent !== null) {
		$text = $prefix . rtrim(rtrim(number_format($percent, 1, '.', ''), '0'), '.') . '%';
	} else {
		$text = 'Không đổi';
	}
	return [
		'class' => $className,
		'text' => $text . ' so với ' . $periodLabel,
	];
};

$monthTotalDelta = $formatDelta(isset($delta['orders_month_total']) ? $delta['orders_month_total'] : [], 'tháng trước');
$monthProfitDelta = $formatDelta(isset($delta['orders_month_profit']) ? $delta['orders_month_profit'] : [], 'tháng trước');
$todayTotalDelta = $formatDelta(isset($delta['orders_today_total']) ? $delta['orders_today_total'] : [], 'hôm qua');
$todayProfitDelta = $formatDelta(isset($delta['orders_today_profit']) ? $delta['orders_today_profit'] : [], 'hôm qua');
$purchaseDelta = $formatDelta(isset($delta['purchases_month_total']) ? $delta['purchases_month_total'] : [], 'tháng trước');
$customerDebtDelta = $formatDelta(isset($delta['customer_debt']) ? $delta['customer_debt'] : [], 'đầu tháng');
$supplierDebtDelta = $formatDelta(isset($delta['supplier_debt']) ? $delta['supplier_debt'] : [], 'đầu tháng');

$renderKpiCard = function ($config) {
	$title = isset($config['title']) ? (string) $config['title'] : '';
	$value = isset($config['value']) ? (string) $config['value'] : '';
	$deltaText = isset($config['delta_text']) ? (string) $config['delta_text'] : '';
	$deltaClass = isset($config['delta_class']) ? (string) $config['delta_class'] : 'text-slate-500';
	$borderClass = isset($config['border_class']) ? (string) $config['border_class'] : 'border-slate-200';
	?>
	<div class="rounded-2xl border <?php echo $borderClass; ?> bg-white p-3 text-sm text-slate-800">
		<div class="min-w-0">
			<div class="text-sm font-medium text-slate-500"><?php echo htmlspecialchars($title); ?></div>
			<div class="mt-0.5 text-lg font-medium text-slate-900"><?php echo $value; ?></div>
			<div class="mt-1 text-xs <?php echo $deltaClass; ?>"><?php echo htmlspecialchars($deltaText); ?></div>
		</div>
	</div>
	<?php
};
?>

<div class="mb-4 space-y-3">
	<div class="flex items-center justify-between gap-3">
		<div>
			<h1 class="text-lg font-medium tracking-tight">Báo cáo tổng quan</h1>
			<p class="mt-0.5 text-sm text-slate-500">Tổng hợp nhanh doanh thu, nhập hàng và công nợ hiện tại.</p>
			<p class="mt-0.5 text-xs text-slate-400">Cập nhật lần cuối: <?php echo htmlspecialchars($updatedAtText); ?></p>
		</div>
	</div>
	<?php
	$activeReport = '';
	include __DIR__ . '/_report_nav.php';
	?>
</div>

<div class="space-y-4">
	<div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
		<?php
		$renderKpiCard([
			'title' => 'Doanh thu tháng này',
			'value' => Money::format($ordersMonth['total_amount']),
			'delta_text' => $monthTotalDelta['text'],
			'delta_class' => $monthTotalDelta['class'],
			'border_class' => 'border-sky-100',
		]);
		$renderKpiCard([
			'title' => 'Lợi nhuận tháng này',
			'value' => Money::format($ordersMonth['profit']),
			'delta_text' => $monthProfitDelta['text'],
			'delta_class' => $monthProfitDelta['class'],
			'border_class' => 'border-emerald-100',
		]);
		$renderKpiCard([
			'title' => 'Doanh thu hôm nay',
			'value' => Money::format($ordersToday['total_amount']),
			'delta_text' => $todayTotalDelta['text'],
			'delta_class' => $todayTotalDelta['class'],
			'border_class' => 'border-emerald-100',
		]);
		$renderKpiCard([
			'title' => 'Lợi nhuận hôm nay',
			'value' => Money::format($ordersToday['profit']),
			'delta_text' => $todayProfitDelta['text'],
			'delta_class' => $todayProfitDelta['class'],
			'border_class' => 'border-teal-100',
		]);
		$renderKpiCard([
			'title' => 'Nhập hàng tháng này',
			'value' => Money::format($purchasesMonth['total_amount']),
			'delta_text' => $purchaseDelta['text'],
			'delta_class' => $purchaseDelta['class'],
			'border_class' => 'border-amber-100',
		]);
		$renderKpiCard([
			'title' => 'Khách hàng còn nợ',
			'value' => Money::format($customerDebt),
			'delta_text' => $customerDebtDelta['text'],
			'delta_class' => $customerDebtDelta['class'],
			'border_class' => 'border-rose-100',
		]);
		$renderKpiCard([
			'title' => 'Còn nợ nhà cung cấp',
			'value' => Money::format($supplierDebt),
			'delta_text' => $supplierDebtDelta['text'],
			'delta_class' => $supplierDebtDelta['class'],
			'border_class' => 'border-violet-100',
		]);
		?>
	</div>
</div>
