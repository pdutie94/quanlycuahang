<?php
$queryParams = $_GET;
unset($queryParams['page'], $queryParams['ajax']);
$queryString = http_build_query($queryParams);
?>
<?php if (empty($purchases)) { ?>
	<div class="rounded-card border border-dashed border-slate-300 bg-white px-4 py-5 text-center text-sm text-slate-500">
		Chưa có phiếu nhập hàng nào.
	</div>
<?php } else { ?>
	<div class="space-y-3" data-infinite-list data-infinite-url="<?php echo $basePath; ?>/purchase" data-infinite-query="<?php echo htmlspecialchars($queryString); ?>" data-current-page="<?php echo isset($page) ? (int) $page : 1; ?>" data-has-more="<?php echo isset($totalPages) && isset($page) && $page < $totalPages ? '1' : '0'; ?>">
		<?php foreach ($purchases as $purchase) { ?>
			<?php
			$total = (float) $purchase['total_amount'];
			$paid = (float) $purchase['paid_amount'];
			$debt = $total - $paid;
			?>
			<a href="<?php echo $basePath; ?>/purchase/view?id=<?php echo $purchase['id']; ?>" class="relative block rounded-card border border-slate-200 bg-white p-4 transition hover:border-slate-300" data-infinite-item>
				<div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
					<div class="min-w-0">
						<div class="flex items-center gap-2">
							<div class="text-sm font-mono font-semibold text-brand-700">
								#<?php echo htmlspecialchars($purchase['purchase_code']); ?>
							</div>
							<?php if ($purchase['status'] === 'paid') { ?>
								<span class="inline-flex items-center rounded-chip border border-brand-200 bg-brand-50 px-3 py-0.5 text-sm font-medium text-brand-700">Đã thanh toán</span>
							<?php } else { ?>
								<span class="inline-flex items-center rounded-chip border border-amber-200 bg-amber-50 px-3 py-0.5 text-sm font-medium text-amber-700">Còn nợ</span>
							<?php } ?>
						</div>
						<div class="mt-1 text-sm text-slate-500">
							<span class="inline-flex items-center gap-1">
								<?php echo ui_icon("calendar", "h-3.5 w-3.5"); ?>
								<span><?php echo htmlspecialchars(format_datetime($purchase['purchase_date'])); ?></span>
							</span>
						</div>
						<div class="mt-1 text-sm text-slate-600">
							<span class="text-slate-500">Nhà cung cấp: </span>
							<span class="font-medium text-slate-800"><?php echo htmlspecialchars($purchase['supplier_name']); ?></span>
						</div>
						<div class="mt-1.5 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-slate-600">
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

<?php
$dateRangeValue = '';
if (!empty($fromDate) || !empty($toDate)) {
	if (!empty($fromDate) && !empty($toDate)) {
		$dateRangeValue = $fromDate . ' - ' . $toDate;
	} elseif (!empty($fromDate)) {
		$dateRangeValue = $fromDate;
	} else {
		$dateRangeValue = $toDate;
	}
}
?>

<div class="app-modal-overlay" data-purchase-advanced-filter-root>
	<div class="app-modal-sheet-sm">
		<div class="app-modal-header">
			<h2 class="app-modal-title">Lọc phiếu nhập</h2>
			<button type="button" class="app-modal-close" data-purchase-advanced-filter-close>
				<?php echo ui_icon("x-mark", "h-4 w-4"); ?>
			</button>
		</div>
		<form method="get" class="app-modal-body space-y-4" data-order-filter-form>
			<input type="hidden" name="q" value="<?php echo isset($keyword) ? htmlspecialchars($keyword) : ''; ?>">
			<div class="space-y-4">
				<div class="relative">
					<label class="absolute left-3 top-0 z-10 -translate-y-1/2 bg-white px-1 leading-none text-sm text-slate-700">Nhà cung cấp</label>
					<?php
					$currentSupplierId = isset($supplierId) ? (int) $supplierId : 0;
					$supplierOptions = ['' => 'Tất cả nhà cung cấp'];
					if (!empty($suppliers) && is_array($suppliers)) {
						foreach ($suppliers as $supplier) {
							$id = isset($supplier['id']) ? (int) $supplier['id'] : 0;
							$supplierOptions[$id] = $supplier['name'];
						}
					}
					ui_select('supplier_id', $supplierOptions, $currentSupplierId, ['class' => 'pt-3']);
					?>
				</div>
				<div class="relative">
					<label class="absolute left-3 top-0 z-10 -translate-y-1/2 bg-white px-1 leading-none text-sm text-slate-700">Thời gian</label>
					<?php
					ui_input_text('date_range', $dateRangeValue, [
						'autocomplete' => 'off',
						'placeholder' => 'VD: 2026-02-01 - 2026-02-29',
						'data-order-date-range' => '1',
						'class' => 'pt-3 pb-2.5',
					]);
					?>
					<input type="hidden" name="from_date" value="<?php echo isset($fromDate) ? htmlspecialchars($fromDate) : ''; ?>" data-order-date-from />
					<input type="hidden" name="to_date" value="<?php echo isset($toDate) ? htmlspecialchars($toDate) : ''; ?>" data-order-date-to />
				</div>
			</div>
			<div class="mt-4 flex items-center justify-between gap-2">
				<div>
					<?php if (!empty($fromDate) || !empty($toDate)) { ?>
						<a href="<?php echo $basePath; ?>/purchase" class="inline-flex items-center text-sm font-medium text-slate-400 hover:text-slate-600">
							Xóa lọc
						</a>
					<?php } ?>
				</div>
				<div class="flex items-center gap-2">
					<button type="button" class="app-btn-secondary" data-purchase-advanced-filter-close>Đóng</button>
					<?php ui_button_primary('Áp dụng', ['type' => 'submit', 'data-loading-button' => '1']); ?>
				</div>
			</div>
		</form>
	</div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
	var root = document.querySelector('[data-purchase-advanced-filter-root]');
	if (!root) return;
	var btnOpen = document.querySelector('[data-purchase-advanced-filter-open]');
	if (btnOpen) {
		btnOpen.addEventListener('click', function (e) {
			e.preventDefault();
			root.classList.remove('hidden');
			root.classList.add('flex');
		});
	}
	function closeFilter() {
		root.classList.add('hidden');
		root.classList.remove('flex');
	}
	root.addEventListener('click', function (e) {
		if (e.target !== root) return;
		closeFilter();
	});
	root.querySelectorAll('[data-purchase-advanced-filter-close]').forEach(function (btn) {
		btn.addEventListener('click', function (e) {
			e.preventDefault();
			closeFilter();
		});
	});
	document.addEventListener('keydown', function (e) {
		if (root.classList.contains('hidden')) return;
		if (e.key === 'Escape') {
			e.preventDefault();
			closeFilter();
		}
	});
});
</script>
