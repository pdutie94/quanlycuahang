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

<div class="app-modal-overlay" data-order-advanced-filter-root>
	<div class="app-modal-sheet-sm">
		<div class="app-modal-header">
			<h2 class="app-modal-title">Lọc nâng cao</h2>
			<button type="button" class="app-modal-close" data-order-advanced-filter-close>
				<?php echo ui_icon("x-mark", "h-4 w-4"); ?>
			</button>
		</div>
		<?php $statusValue = isset($statusFilter) ? $statusFilter : ''; ?>
		<?php
		$hasFilter = $statusValue !== '' || $orderStatusValue !== '' || (!empty($fromDate) || !empty($toDate));
		?>
		<form method="get" class="app-modal-body space-y-4" data-order-filter-form>
			<input type="hidden" hidden name="q" value="<?php echo isset($keyword) ? htmlspecialchars($keyword) : ''; ?>">
			<input type="hidden" hidden name="order_status" value="<?php echo htmlspecialchars($orderStatusValue); ?>">
				<div class="relative">
					<label class="absolute left-3 top-0 z-10 -translate-y-1/2 bg-white px-1 leading-none text-sm text-slate-700">Trạng thái thanh toán</label>
				<div class="relative">
					<?php
					$statusOptions = [
						'' => 'Tất cả',
						'paid' => 'Đã thanh toán',
						'debt' => 'Còn nợ',
					];
					ui_select('status', $statusOptions, $statusValue, [
						'data-no-select2' => '1',
						'class' => 'appearance-none pr-8 pt-3',
					]);
					?>
					<span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2 text-slate-400">
						<?php echo ui_icon("chevron-down", "h-4 w-4"); ?>
					</span>
				</div>
			</div>
				<div>
					<div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
						<div class="relative">
							<label class="absolute left-3 top-0 z-10 -translate-y-1/2 bg-white px-1 leading-none text-sm text-slate-700">Từ ngày</label>
							<?php
							ui_input_text('from_date', isset($fromDate) ? $fromDate : '', [
								'type' => 'date',
								'class' => 'pt-3 pb-2.5',
							]);
							?>
						</div>
						<div class="relative">
							<label class="absolute left-3 top-0 z-10 -translate-y-1/2 bg-white px-1 leading-none text-sm text-slate-700">Đến ngày</label>
							<?php
							ui_input_text('to_date', isset($toDate) ? $toDate : '', [
								'type' => 'date',
								'class' => 'pt-3 pb-2.5',
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
					<button type="button" class="app-btn-secondary" data-order-advanced-filter-close>Đóng</button>
					<?php ui_button_primary('Áp dụng', ['type' => 'submit', 'data-loading-button' => '1']); ?>
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

