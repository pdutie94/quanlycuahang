<?php
$activeReport = isset($activeReport) ? $activeReport : '';
?>

<div class="mt-1 -mx-1">
	<div class="flex items-center gap-2 overflow-x-auto overflow-y-hidden whitespace-nowrap px-1 py-0.5">
		<?php $isActive = $activeReport === 'sales'; ?>
		<a href="<?php echo $basePath; ?>/report/sales" class="inline-flex shrink-0 items-center gap-1 rounded-full border pl-1 pr-2 py-1 text-sm font-medium  <?php echo $isActive ? 'border-emerald-300 bg-emerald-50 text-emerald-700' : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50'; ?>">
			<span class="inline-flex h-6 w-6 items-center justify-center rounded-full <?php echo $isActive ? 'bg-emerald-500 text-white' : 'bg-emerald-100 text-emerald-600'; ?>">
				<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4">
					<path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25C6.996 12 7.5 12.504 7.5 13.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25A1.125 1.125 0 0 1 9.75 19.875V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25A1.125 1.125 0 0 1 16.5 19.875V4.125Z" />
				</svg>
			</span>
			<span>Doanh thu chi tiết</span>
		</a>

		<?php $isActive = $activeReport === 'inventory'; ?>
		<a href="<?php echo $basePath; ?>/report/inventory" class="inline-flex shrink-0 items-center gap-1 rounded-full border pl-1 pr-2 py-1 text-sm font-medium  <?php echo $isActive ? 'border-sky-300 bg-sky-50 text-sky-700' : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50'; ?>">
			<span class="inline-flex h-6 w-6 items-center justify-center rounded-full <?php echo $isActive ? 'bg-sky-500 text-white' : 'bg-sky-100 text-sky-600'; ?>">
				<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4">
					<path stroke-linecap="round" stroke-linejoin="round" d="m21 7.5-9-5.25L3 7.5m18 0-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9" />
				</svg>
			</span>
			<span>Cập nhật tồn kho</span>
		</a>

		<?php $isActive = $activeReport === 'customer-debt'; ?>
		<a href="<?php echo $basePath; ?>/report/customer-debt" class="inline-flex shrink-0 items-center gap-1 rounded-full border pl-1 pr-2 py-1 text-sm font-medium  <?php echo $isActive ? 'border-rose-300 bg-rose-50 text-rose-700' : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50'; ?>">
			<span class="inline-flex h-6 w-6 items-center justify-center rounded-full <?php echo $isActive ? 'bg-rose-500 text-white' : 'bg-rose-100 text-rose-600'; ?>">
				<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4">
					<path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
				</svg>
			</span>
			<span>Công nợ khách hàng</span>
		</a>

		<?php $isActive = $activeReport === 'supplier-debt'; ?>
		<a href="<?php echo $basePath; ?>/report/supplier-debt" class="inline-flex shrink-0 items-center gap-1 rounded-full border pl-1 pr-2 py-1 text-sm font-medium  <?php echo $isActive ? 'border-violet-300 bg-violet-50 text-violet-700' : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50'; ?>">
			<span class="inline-flex h-6 w-6 items-center justify-center rounded-full <?php echo $isActive ? 'bg-violet-500 text-white' : 'bg-violet-100 text-violet-600'; ?>">
				<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4">
					<path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 0 0-10.026 0 1.106 1.106 0 0 0-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12" />
				</svg>
			</span>
			<span>Công nợ nhà cung cấp</span>
		</a>

		<?php $isActive = $activeReport === 'missing-cost'; ?>
		<a href="<?php echo $basePath; ?>/report/missing-cost" class="inline-flex shrink-0 items-center gap-1 rounded-full border pl-1 pr-2 py-1 text-sm font-medium  <?php echo $isActive ? 'border-amber-300 bg-amber-50 text-amber-700' : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50'; ?>">
			<span class="inline-flex h-6 w-6 items-center justify-center rounded-full <?php echo $isActive ? 'bg-amber-500 text-white' : 'bg-amber-100 text-amber-600'; ?>">
				<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4">
					<path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3Z" />
					<path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6Z" />
				</svg>
			</span>
			<span>Cập nhật giá vốn</span>
		</a>
	</div>
</div>

