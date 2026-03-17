<?php
$queryParams = $_GET;
unset($queryParams['page'], $queryParams['ajax']);
$queryString = http_build_query($queryParams);
?>
<?php if (empty($purchases)) { ?>
	<div class="rounded-lg border border-dashed border-slate-300 bg-white px-4 py-4 text-center text-sm text-slate-500">
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
			<a href="<?php echo $basePath; ?>/purchase/view?id=<?php echo $purchase['id']; ?>" class="relative block rounded-2xl bg-white p-3 shadow-sm ring-1 ring-slate-100 transition hover:shadow-md" data-infinite-item>
				<div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
					<div class="min-w-0">
						<div class="flex items-center gap-2">
							<div class="text-sm font-mono font-medium text-emerald-700">
								#<?php echo htmlspecialchars($purchase['purchase_code']); ?>
							</div>
							<?php if ($purchase['status'] === 'paid') { ?>
								<span class="inline-flex items-center rounded-full bg-emerald-50 px-3 py-0.5 text-sm font-medium text-emerald-700">Đã thanh toán</span>
							<?php } else { ?>
								<span class="inline-flex items-center rounded-full bg-amber-50 px-3 py-0.5 text-sm font-medium text-amber-700">Còn nợ</span>
							<?php } ?>
						</div>
						<div class="mt-1 text-sm text-slate-500">
							<span class="inline-flex items-center gap-1">
								<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-3.5 w-3.5">
									<path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
								</svg>
								<span><?php echo htmlspecialchars(format_datetime($purchase['purchase_date'])); ?></span>
							</span>
						</div>
						<div class="mt-1 text-sm text-slate-600">
							<span class="text-slate-500">Nhà cung cấp: </span>
							<span class="font-medium text-slate-800"><?php echo htmlspecialchars($purchase['supplier_name']); ?></span>
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

<div class="fixed inset-0 z-40 hidden items-center justify-center bg-black/40" data-purchase-advanced-filter-root>
	<div class="mx-4 my-6 w-full max-w-sm rounded-2xl bg-white shadow-lg">
		<div class="flex items-center justify-between border-b border-slate-200 px-4 py-2">
			<h2 class="text-sm font-medium text-slate-800">Lọc phiếu nhập</h2>
			<button type="button" class="text-slate-400 hover:text-slate-600" data-purchase-advanced-filter-close>
				<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4">
					<path fill-rule="evenodd" d="M4.293 4.293a1 1 0 0 1 1.414 0L10 8.586l4.293-4.293a1 1 0 1 1 1.414 1.414L11.414 10l4.293 4.293a1 1 0 0 1-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 0 1-1.414-1.414L8.586 10 4.293 5.707a1 1 0 0 1 0-1.414Z" clip-rule="evenodd" />
				</svg>
			</button>
		</div>
		<form method="get" class="px-4 py-3 text-sm" data-order-filter-form>
			<input type="hidden" name="q" value="<?php echo isset($keyword) ? htmlspecialchars($keyword) : ''; ?>">
			<div class="space-y-3">
				<div>
					<label class="mb-1 block text-sm font-medium text-slate-500">Nhà cung cấp</label>
					<?php
					$currentSupplierId = isset($supplierId) ? (int) $supplierId : 0;
					$supplierOptions = ['' => 'Tất cả nhà cung cấp'];
					if (!empty($suppliers) && is_array($suppliers)) {
						foreach ($suppliers as $supplier) {
							$id = isset($supplier['id']) ? (int) $supplier['id'] : 0;
							$supplierOptions[$id] = $supplier['name'];
						}
					}
					ui_select('supplier_id', $supplierOptions, $currentSupplierId);
					?>
				</div>
				<div>
					<label class="mb-1 block text-sm font-medium text-slate-500">Thời gian</label>
					<?php
					ui_input_text('date_range', $dateRangeValue, [
						'autocomplete' => 'off',
						'placeholder' => 'VD: 2026-02-01 - 2026-02-29',
						'data-order-date-range' => '1',
					]);
					?>
					<input type="hidden" name="from_date" value="<?php echo isset($fromDate) ? htmlspecialchars($fromDate) : ''; ?>" data-order-date-from />
					<input type="hidden" name="to_date" value="<?php echo isset($toDate) ? htmlspecialchars($toDate) : ''; ?>" data-order-date-to />
				</div>
			</div>
			<div class="mt-3 flex items-center justify-between gap-2">
				<div>
					<?php if (!empty($fromDate) || !empty($toDate)) { ?>
						<a href="<?php echo $basePath; ?>/purchase" class="inline-flex items-center text-sm font-medium text-slate-400 hover:text-slate-600">
							Xóa lọc
						</a>
					<?php } ?>
				</div>
				<div class="flex items-center gap-2">
					<button type="button" class="inline-flex items-center rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-100" data-purchase-advanced-filter-close>Đóng</button>
					<?php ui_button_primary('Áp dụng', ['type' => 'submit', 'class' => 'py-1.5', 'data-loading-button' => '1']); ?>
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
