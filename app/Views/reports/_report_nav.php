<?php
$activeReport = isset($activeReport) ? $activeReport : '';
?>

<div class="mt-1 -mx-1">
	<div class="flex items-center gap-2 overflow-x-auto overflow-y-hidden whitespace-nowrap px-1 py-0.5">
		<?php $isActive = $activeReport === 'sales'; ?>
		<a href="<?php echo $basePath; ?>/report/sales" class="inline-flex shrink-0 items-center gap-1 rounded-full border pl-1 pr-2 py-1 text-sm font-medium  <?php echo $isActive ? 'border-brand-300 bg-brand-50 text-brand-700' : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50'; ?>">
			<span class="inline-flex h-6 w-6 items-center justify-center rounded-full <?php echo $isActive ? 'bg-brand-500 text-white' : 'bg-brand-100 text-brand-600'; ?>">
				<?php echo ui_icon("chart-bar", "h-4 w-4"); ?>
			</span>
			<span>Doanh thu chi tiết</span>
		</a>

		<?php $isActive = $activeReport === 'inventory'; ?>
		<a href="<?php echo $basePath; ?>/report/inventory" class="inline-flex shrink-0 items-center gap-1 rounded-full border pl-1 pr-2 py-1 text-sm font-medium  <?php echo $isActive ? 'border-sky-300 bg-sky-50 text-sky-700' : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50'; ?>">
			<span class="inline-flex h-6 w-6 items-center justify-center rounded-full <?php echo $isActive ? 'bg-sky-500 text-white' : 'bg-sky-100 text-sky-600'; ?>">
				<?php echo ui_icon("cube", "h-4 w-4"); ?>
			</span>
			<span>Cập nhật tồn kho</span>
		</a>

		<?php $isActive = $activeReport === 'customer-debt'; ?>
		<a href="<?php echo $basePath; ?>/report/customer-debt" class="inline-flex shrink-0 items-center gap-1 rounded-full border pl-1 pr-2 py-1 text-sm font-medium  <?php echo $isActive ? 'border-rose-300 bg-rose-50 text-rose-700' : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50'; ?>">
			<span class="inline-flex h-6 w-6 items-center justify-center rounded-full <?php echo $isActive ? 'bg-rose-500 text-white' : 'bg-rose-100 text-rose-600'; ?>">
				<?php echo ui_icon("user", "h-4 w-4"); ?>
			</span>
			<span>Công nợ khách hàng</span>
		</a>

		<?php $isActive = $activeReport === 'supplier-debt'; ?>
		<a href="<?php echo $basePath; ?>/report/supplier-debt" class="inline-flex shrink-0 items-center gap-1 rounded-full border pl-1 pr-2 py-1 text-sm font-medium  <?php echo $isActive ? 'border-violet-300 bg-violet-50 text-violet-700' : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50'; ?>">
			<span class="inline-flex h-6 w-6 items-center justify-center rounded-full <?php echo $isActive ? 'bg-violet-500 text-white' : 'bg-violet-100 text-violet-600'; ?>">
				<?php echo ui_icon("truck", "h-4 w-4"); ?>
			</span>
			<span>Công nợ nhà cung cấp</span>
		</a>

		<?php $isActive = $activeReport === 'missing-cost'; ?>
		<a href="<?php echo $basePath; ?>/report/missing-cost" class="inline-flex shrink-0 items-center gap-1 rounded-full border pl-1 pr-2 py-1 text-sm font-medium  <?php echo $isActive ? 'border-amber-300 bg-amber-50 text-amber-700' : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50'; ?>">
			<span class="inline-flex h-6 w-6 items-center justify-center rounded-full <?php echo $isActive ? 'bg-amber-500 text-white' : 'bg-amber-100 text-amber-600'; ?>">
				<?php echo ui_icon("tag", "h-4 w-4"); ?>
			</span>
			<span>Cập nhật giá vốn</span>
		</a>
	</div>
</div>

