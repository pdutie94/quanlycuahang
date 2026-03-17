<?php
$queryParams = $_GET;
unset($queryParams['page'], $queryParams['ajax']);
$queryString = http_build_query($queryParams);
$orderStatusValue = isset($orderStatusFilter) ? $orderStatusFilter : (isset($_GET['order_status']) ? $_GET['order_status'] : '');
if (!in_array($orderStatusValue, ['', 'completed', 'pending', 'cancelled'], true)) {
	$orderStatusValue = '';
}
?>
<?php if (empty($orders)) { ?>
	<div class="rounded-lg border border-dashed border-slate-300 bg-white px-4 py-4 text-center text-sm text-slate-500">
		Chưa có đơn hàng nào.
	</div>
	<?php } else { ?>
		<div class="space-y-2" data-infinite-list data-infinite-url="<?php echo $basePath; ?>/order" data-infinite-query="<?php echo htmlspecialchars($queryString); ?>" data-current-page="<?php echo isset($page) ? (int) $page : 1; ?>" data-has-more="<?php echo isset($totalPages) && isset($page) && $page < $totalPages ? '1' : '0'; ?>">
		<?php foreach ($orders as $order) { ?>
			<?php
			$orderCardData = $order;
			$orderCardUrl = $basePath . '/order/view?id=' . (int) $order['id'];
			$orderCardExtraAttrs = 'data-infinite-item';
			include __DIR__ . '/../partials/order_item_card.php';
			?>
		<?php } ?>
	</div>
<?php } ?>


<?php include __DIR__ . '/../partials/order_preview_modal.php'; ?>

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

<div class="fixed inset-0 z-40 hidden items-center justify-center bg-black/40" data-order-advanced-filter-root>
	<div class="w-full max-w-sm rounded-2xl bg-white shadow-lg mx-4 my-6">
		<div class="flex items-center justify-between border-b border-slate-200 px-3 py-2">
			<h2 class="text-sm font-medium text-slate-800">Lọc nâng cao</h2>
			<button type="button" class="text-slate-400 hover:text-slate-600" data-order-advanced-filter-close>
				<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4">
					<path fill-rule="evenodd" d="M4.293 4.293a1 1 0 0 1 1.414 0L10 8.586l4.293-4.293a1 1 0 1 1 1.414 1.414L11.414 10l4.293 4.293a1 1 0 0 1-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 0 1-1.414-1.414L8.586 10 4.293 5.707a1 1 0 0 1 0-1.414Z" clip-rule="evenodd" />
				</svg>
			</button>
		</div>
		<?php $statusValue = isset($statusFilter) ? $statusFilter : ''; ?>
		<?php
		$hasFilter = $statusValue !== '' || $orderStatusValue !== '' || (!empty($fromDate) || !empty($toDate));
		?>
		<form method="get" class="space-y-4 px-3 py-2 text-sm" data-order-filter-form>
			<input type="hidden" name="q" value="<?php echo isset($keyword) ? htmlspecialchars($keyword) : ''; ?>">
			<input type="hidden" name="order_status" value="<?php echo htmlspecialchars($orderStatusValue); ?>">
				<div>
					<label class="mb-1 block text-sm font-medium text-slate-500">Trạng thái thanh toán</label>
				<div class="relative">
					<?php
					$statusOptions = [
						'' => 'Tất cả',
						'paid' => 'Đã thanh toán',
						'debt' => 'Còn nợ',
					];
					ui_select('status', $statusOptions, $statusValue, [
						'data-no-select2' => '1',
						'class' => 'appearance-none pr-8',
					]);
					?>
					<span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2 text-slate-400">
						<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4">
							<path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.168l3.71-3.938a.75.75 0 1 1 1.08 1.04l-4.24 4.5a.75.75 0 0 1-1.08 0l-4.24-4.5a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd" />
						</svg>
					</span>
				</div>
			</div>
				<div>
					<div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
						<div>
							<label class="mb-1 block text-sm font-medium text-slate-500">Từ ngày</label>
							<?php
							ui_input_text('from_date', isset($fromDate) ? $fromDate : '', [
								'type' => 'date',
								'class' => 'px-3 py-1.5',
							]);
							?>
						</div>
						<div>
							<label class="mb-1 block text-sm font-medium text-slate-500">Đến ngày</label>
							<?php
							ui_input_text('to_date', isset($toDate) ? $toDate : '', [
								'type' => 'date',
								'class' => 'px-3 py-1.5',
							]);
							?>
						</div>
					</div>
				</div>
			<div class="flex items-center justify-between gap-2 pt-1">
				<div>
					<?php if ($hasFilter) { ?>
						<a href="<?php echo $basePath; ?>/order" class="inline-flex items-center text-sm font-medium text-slate-400 hover:text-slate-600">
							Xóa lọc
						</a>
					<?php } ?>
				</div>
				<div class="flex items-center gap-2">
					<button type="button" class="inline-flex items-center rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-100" data-order-advanced-filter-close>Đóng</button>
					<?php ui_button_primary('Áp dụng', ['type' => 'submit', 'class' => 'py-1.5', 'data-loading-button' => '1']); ?>
				</div>
			</div>
		</form>
	</div>
</div>

<script>
	document.addEventListener('DOMContentLoaded', function () {
		var root = document.querySelector('[data-order-advanced-filter-root]');
		if (!root) {
			return;
		}
		var btnOpen = document.querySelector('[data-order-advanced-filter-open]');
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
		root.querySelectorAll('[data-order-advanced-filter-close]').forEach(function (btn) {
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

