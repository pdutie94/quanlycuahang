<?php
$totalDebt = isset($totalDebt) ? (float) $totalDebt : 0;
?>

<?php if (!isset($detailHeader)) { ?>
<div class="mb-4 flex items-center justify-between gap-3">
	<h1 class="text-lg font-medium tracking-tight">Nhà cung cấp</h1>
		<div class="flex items-center gap-1.5" data-header-actions-root>
		<a href="<?php echo $basePath; ?>/supplier" class="inline-flex items-center gap-1 rounded-full border border-slate-300 bg-white px-2.5 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-100">
			<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4">
				<path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
			</svg>
			<span>Danh sách</span>
		</a>
		<div class="relative" data-header-actions-menu>
			<button type="button" class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-slate-300 bg-white text-slate-600 hover:bg-slate-100" data-header-actions-toggle>
				<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4">
					<path stroke-linecap="round" stroke-linejoin="round" d="M12 6.75a1.5 1.5 0 110-3 1.5 1.5 0 010 3zM12 13.5a1.5 1.5 0 110-3 1.5 1.5 0 010 3zM12 20.25a1.5 1.5 0 110-3 1.5 1.5 0 010 3z" />
				</svg>
			</button>
			<div class="absolute right-0 z-30 mt-2 w-44 rounded-xl border border-slate-200 bg-white py-1 text-sm  overflow-hidden hidden" data-header-actions-dropdown>
				<a href="<?php echo $basePath; ?>/supplier/delete?id=<?php echo $supplier['id']; ?>" onclick="return confirm('Xóa nhà cung cấp này?');" class="flex items-center justify-between gap-2 px-3 py-1.5 text-red-600 hover:bg-red-50">
					<div class="flex items-center gap-1.5">
						<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4 text-red-500">
							<path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673A2.25 2.25 0 0 1 15.916 21H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
						</svg>
						<span>Xóa nhà cung cấp</span>
					</div>
				</a>
			</div>
		</div>
	</div>
</div>
<?php } ?>

<div class="mb-4 rounded-lg border border-slate-200 bg-white px-4 py-3 ">
	<div class="text-sm font-medium text-slate-800"><?php echo htmlspecialchars($supplier['name']); ?></div>
	<div class="mt-1 text-sm text-slate-600">
		<?php if (!empty($supplier['phone'])) { ?>
			<span class="mr-4">SĐT: <?php echo htmlspecialchars($supplier['phone']); ?></span>
		<?php } ?>
		<?php if (!empty($supplier['address'])) { ?>
			<span>Địa chỉ: <?php echo htmlspecialchars($supplier['address']); ?></span>
		<?php } ?>
	</div>
	<div class="mt-2 text-sm">
		<span class="text-slate-600">Tổng nợ hiện tại:</span>
		<span class="ml-2 font-medium <?php echo $totalDebt > 0 ? 'text-red-600' : 'text-emerald-600'; ?>">
			<?php echo Money::format($totalDebt); ?>
		</span>
	</div>
</div>

<div class="space-y-2">
	<div class="flex items-center justify-between gap-2">
		<h2 class="text-sm font-medium text-slate-800">Phiếu nhập gần đây</h2>
	</div>
	<?php if (empty($purchases)) { ?>
		<div class="rounded-lg border border-dashed border-slate-200 bg-white px-4 py-4 text-center text-sm text-slate-500">
			Chưa có phiếu nhập nào.
		</div>
	<?php } else { ?>
		<div class="space-y-2">
			<?php foreach ($purchases as $purchase) { ?>
				<?php
				$total = (float) $purchase['total_amount'];
				$paid = (float) $purchase['paid_amount'];
				$debt = $total - $paid;
				$timeText = '';
				if (!empty($purchase['purchase_date'])) {
					$ts = strtotime($purchase['purchase_date']);
					if ($ts !== false) {
						$timeText = date('H:i, d/m/Y', $ts);
					}
				}
				$status = isset($purchase['status']) ? $purchase['status'] : 'debt';
				$badgeLabel = $status === 'paid' ? 'Đã thanh toán' : ($debt > 0 ? 'Còn nợ' : 'Chờ xử lý');
				$badgeClass = $status === 'paid'
					? 'bg-emerald-50 text-emerald-700'
					: ($debt > 0 ? 'bg-amber-50 text-amber-700' : 'bg-slate-100 text-slate-600');
				?>
				<a href="<?php echo $basePath; ?>/purchase/view?id=<?php echo $purchase['id']; ?>" class="block rounded-2xl bg-white p-3 text-sm  ring-1 ring-slate-100 transition hover:">
					<div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
						<div class="min-w-0">
							<div class="flex items-center gap-2">
								<div class="font-mono text-sm font-medium text-slate-800">
									#<?php echo htmlspecialchars($purchase['purchase_code']); ?>
								</div>
								<span class="inline-flex items-center rounded-full px-3 py-0.5 text-sm font-medium whitespace-nowrap <?php echo $badgeClass; ?>">
									<?php echo htmlspecialchars($badgeLabel); ?>
								</span>
							</div>
							<div class="mt-1 flex flex-wrap items-center gap-2 text-sm text-slate-500">
								<?php if ($timeText !== '') { ?>
									<div class="inline-flex items-center gap-1">
										<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-3.5 w-3.5">
											<path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
										</svg>
										<span><?php echo htmlspecialchars($timeText); ?></span>
									</div>
								<?php } ?>
							</div>
							<div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-slate-600">
								<span>
									Tổng: <span class="font-medium text-slate-900"><?php echo Money::format($total); ?></span>
								</span>
								<span>
									Đã trả: <span class="font-medium text-emerald-600"><?php echo Money::format($paid); ?></span>
								</span>
								<span>
									Còn nợ: <span class="font-medium <?php echo $debt > 0 ? 'text-red-600' : 'text-slate-700'; ?>"><?php echo Money::format($debt); ?></span>
								</span>
							</div>
						</div>
					</div>
				</a>
			<?php } ?>
		</div>
	<?php } ?>
</div>
