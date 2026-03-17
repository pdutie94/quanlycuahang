<?php
$ordersToday = isset($ordersToday) ? $ordersToday : ['total_amount' => 0, 'total_cost' => 0, 'profit' => 0, 'paid_amount' => 0, 'debt_amount' => 0];
$ordersMonth = isset($ordersMonth) ? $ordersMonth : ['total_amount' => 0, 'total_cost' => 0, 'profit' => 0, 'paid_amount' => 0, 'debt_amount' => 0];
$purchasesMonth = isset($purchasesMonth) ? $purchasesMonth : ['total_amount' => 0, 'paid_amount' => 0, 'debt_amount' => 0];
$customerDebt = isset($customerDebt) ? (float) $customerDebt : 0;
$supplierDebt = isset($supplierDebt) ? (float) $supplierDebt : 0;
?>

<div class="mb-4 space-y-3">
	<div class="flex items-center justify-between gap-3">
		<div>
			<h1 class="text-lg font-medium tracking-tight">Báo cáo tổng quan</h1>
			<p class="mt-0.5 text-sm text-slate-500">Tổng hợp nhanh doanh thu, nhập hàng và công nợ trong kỳ.</p>
		</div>
	</div>
	<?php
	$activeReport = '';
	include __DIR__ . '/_report_nav.php';
	?>
</div>

<div class="space-y-4">
	<div class="grid grid-cols-1 gap-3 md:grid-cols-2">
		<div class="flex items-stretch gap-3 rounded-2xl border border-emerald-100 bg-white p-3 text-sm text-slate-800 shadow-sm">
			<div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700">
				<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-6 w-6">
					<path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
				</svg>
			</div>
			<div class="flex-1">
				<div class="text-sm font-medium text-slate-500">Doanh thu hôm nay</div>
				<div class="text-lg font-medium text-slate-900"><?php echo Money::format($ordersToday['total_amount']); ?></div>
				<div class="text-xs text-emerald-700">Lợi nhuận: <?php echo Money::format($ordersToday['profit']); ?></div>
			</div>
		</div>
		<div class="flex items-stretch gap-3 rounded-2xl border border-sky-100 bg-white p-3 text-sm text-slate-800 shadow-sm">
			<div class="flex h-10 w-10 items-center justify-center rounded-xl bg-sky-50 text-sky-600">
				<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-6 w-6">
					<path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5m-9-6h.008v.008H12v-.008ZM12 15h.008v.008H12V15Zm0 2.25h.008v.008H12v-.008ZM9.75 15h.008v.008H9.75V15Zm0 2.25h.008v.008H9.75v-.008ZM7.5 15h.008v.008H7.5V15Zm0 2.25h.008v.008H7.5v-.008Zm6.75-4.5h.008v.008h-.008v-.008Zm0 2.25h.008v.008h-.008V15Zm0 2.25h.008v.008h-.008v-.008Zm2.25-4.5h.008v.008H16.5v-.008Zm0 2.25h.008v.008H16.5V15Z" />
				</svg>
			</div>
			<div class="flex-1">
				<div class="text-sm font-medium text-slate-500">Doanh thu tháng này</div>
				<div class="text-lg font-medium text-slate-900"><?php echo Money::format($ordersMonth['total_amount']); ?></div>
				<div class="text-xs text-sky-700">Lợi nhuận: <?php echo Money::format($ordersMonth['profit']); ?></div>
			</div>
		</div>
		<div class="flex items-stretch gap-3 rounded-2xl border border-amber-100 bg-white p-3 text-sm text-slate-800 shadow-sm">
			<div class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-50 text-amber-600">
				<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-6 w-6">
					<path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z" />
				</svg>
			</div>
			<div class="flex-1">
				<div class="text-sm font-medium text-slate-500">Nhập hàng tháng này</div>
				<div class="text-lg font-medium text-slate-900"><?php echo Money::format($purchasesMonth['total_amount']); ?></div>
				<div class="text-xs text-amber-700">
					<span class="mr-2">Đã trả: <?php echo Money::format($purchasesMonth['paid_amount']); ?></span>
					<span>Còn nợ: <?php echo Money::format($purchasesMonth['debt_amount']); ?></span>
				</div>
			</div>
		</div>
	</div>

	<div class="grid grid-cols-1 gap-3 md:grid-cols-2">
		<div class="flex items-stretch gap-3 rounded-2xl border border-rose-100 bg-white p-3 text-sm text-slate-800 shadow-sm">
			<div class="flex h-10 w-10 items-center justify-center rounded-xl bg-rose-50 text-rose-600">
				<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-6 w-6">
					<path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
				</svg>
			</div>
			<div class="flex-1">
				<div class="text-sm font-medium text-slate-500">Khách hàng còn nợ</div>
				<div class="text-lg font-medium text-slate-900"><?php echo Money::format($customerDebt); ?></div>
			</div>
		</div>
		<div class="flex items-stretch gap-3 rounded-2xl border border-violet-100 bg-white p-3 text-sm text-slate-800 shadow-sm">
			<div class="flex h-10 w-10 items-center justify-center rounded-xl bg-violet-50 text-violet-600">
				<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-6 w-6">
					<path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 0 1 .75-.75h3a.75.75 0 0 1 .75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349M3.75 21V9.349m0 0a3.001 3.001 0 0 0 3.75-.615A2.993 2.993 0 0 0 9.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 0 0 2.25 1.016c.896 0 1.7-.393 2.25-1.015a3.001 3.001 0 0 0 3.75.614m-16.5 0a3.004 3.004 0 0 1-.621-4.72l1.189-1.19A1.5 1.5 0 0 1 5.378 3h13.243a1.5 1.5 0 0 1 1.06.44l1.19 1.189a3 3 0 0 1-.621 4.72M6.75 18h3.75a.75.75 0 0 0 .75-.75V13.5a.75.75 0 0 0-.75-.75H6.75a.75.75 0 0 0-.75.75v3.75c0 .414.336.75.75.75Z" />
				</svg>
			</div>
			<div class="flex-1">
				<div class="text-sm font-medium text-slate-500">Còn nợ nhà cung cấp</div>
				<div class="text-lg font-medium text-slate-900"><?php echo Money::format($supplierDebt); ?></div>
			</div>
		</div>
	</div>
</div>
