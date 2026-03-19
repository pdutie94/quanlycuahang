<?php
$totalDebt = isset($totalDebt) ? (float) $totalDebt : 0;
?>

<?php if (!isset($detailHeader)) { ?>
<div class="mb-4 flex items-center justify-between gap-3">
	<h1 class="text-lg font-medium tracking-tight">Nhà cung cấp</h1>
		<div class="flex items-center gap-1.5" data-header-actions-root>
		<a href="<?php echo $basePath; ?>/supplier" class="inline-flex items-center gap-1 rounded-lg border border-slate-300 bg-white px-2.5 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-100">
			<?php echo ui_icon("chevron-left", "h-4 w-4"); ?>
			<span>Danh sách</span>
		</a>
		<div class="relative" data-header-actions-menu>
			<button type="button" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-300 bg-white text-slate-600 hover:bg-slate-100" data-header-actions-toggle>
				<?php echo ui_icon("ellipsis-vertical", "h-4 w-4"); ?>
			</button>
			<div class="absolute right-0 z-30 mt-2 w-44 rounded-xl border border-slate-200 bg-white py-1 text-sm  overflow-hidden hidden" data-header-actions-dropdown>
				<a href="<?php echo $basePath; ?>/supplier/delete?id=<?php echo $supplier['id']; ?>" onclick="return confirm('Xóa nhà cung cấp này?');" class="flex items-center justify-between gap-2 px-3 py-1.5 text-red-600 hover:bg-red-50">
					<div class="flex items-center gap-1.5">
						<?php echo ui_icon("trash", "h-4 w-4 text-red-500"); ?>
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
		<span class="ml-2 font-medium <?php echo $totalDebt > 0 ? 'text-red-600' : 'text-brand-600'; ?>">
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
					? 'bg-brand-50 text-brand-700'
					: ($debt > 0 ? 'bg-amber-50 text-amber-700' : 'bg-slate-100 text-slate-600');
				?>
				<a href="<?php echo $basePath; ?>/purchase/view?id=<?php echo $purchase['id']; ?>" class="block rounded-card bg-white p-3 text-sm border border-slate-200 transition hover:bg-slate-50">
					<div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
						<div class="min-w-0">
							<div class="flex items-center gap-2">
								<div class="font-mono text-sm font-medium text-slate-800">
									#<?php echo htmlspecialchars($purchase['purchase_code']); ?>
								</div>
								<span class="inline-flex items-center rounded-lg px-3 py-0.5 text-sm font-medium whitespace-nowrap <?php echo $badgeClass; ?>">
									<?php echo htmlspecialchars($badgeLabel); ?>
								</span>
							</div>
							<div class="mt-1 flex flex-wrap items-center gap-2 text-sm text-slate-500">
								<?php if ($timeText !== '') { ?>
									<div class="inline-flex items-center gap-1">
										<?php echo ui_icon("calendar", "h-3.5 w-3.5"); ?>
										<span><?php echo htmlspecialchars($timeText); ?></span>
									</div>
								<?php } ?>
							</div>
							<div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-slate-600">
								<span>
									Tổng: <span class="font-medium text-slate-900"><?php echo Money::format($total); ?></span>
								</span>
								<span>
									Đã trả: <span class="font-medium text-brand-600"><?php echo Money::format($paid); ?></span>
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
