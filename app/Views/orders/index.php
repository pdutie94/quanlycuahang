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
		<div class="space-y-3" data-infinite-list data-infinite-url="<?php echo $basePath; ?>/order" data-infinite-query="<?php echo htmlspecialchars($queryString); ?>" data-current-page="<?php echo isset($page) ? (int) $page : 1; ?>" data-has-more="<?php echo isset($totalPages) && isset($page) && $page < $totalPages ? '1' : '0'; ?>">
		<?php foreach ($orders as $order) { ?>
			<?php
			$total = (float) $order['total_amount'];
			$paid = (float) $order['paid_amount'];
			$debt = $total - $paid;
			$cost = isset($order['total_cost']) ? (float) $order['total_cost'] : 0.0;
			$profit = $total - $cost;
			$itemsCount = isset($order['items_count']) ? (int) $order['items_count'] : 0;
			$timeText = '';
			if (!empty($order['order_date'])) {
				$ts = strtotime($order['order_date']);
				if ($ts !== false) {
					$timeText = date('H:i, d/m/Y', $ts);
				}
			}
			?>
					<a href="<?php echo $basePath; ?>/order/view?id=<?php echo $order['id']; ?>" class="relative block rounded-2xl bg-white p-3 pr-12 shadow-sm ring-1 ring-slate-100 transition hover:shadow-md" data-infinite-item>
						<button type="button" class="absolute right-3 top-3 inline-flex h-6 w-6 items-center justify-center rounded-full bg-slate-100 text-slate-500 transition-colors hover:bg-emerald-50 hover:text-emerald-600" data-order-preview-btn data-order-id="<?php echo $order['id']; ?>" data-order-code="<?php echo htmlspecialchars($order['order_code']); ?>" data-order-date="<?php echo htmlspecialchars($timeText); ?>">
							<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-4 w-4">
								<path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.964-7.178z" />
								<path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
							</svg>
						</button>
				<?php
				$orderStatus = isset($order['order_status']) ? $order['order_status'] : 'pending';
				$badgeLabel = '';
				$badgeClass = '';
				if ($orderStatus === 'cancelled') {
					$badgeLabel = 'Đã hủy';
					$badgeClass = 'bg-slate-100 text-slate-500';
				} elseif ($order['status'] === 'paid') {
					$badgeLabel = 'Đã thanh toán';
					$badgeClass = 'bg-emerald-50 text-emerald-700';
				} elseif ($debt > 0) {
					$badgeLabel = 'Cần thu tiền';
					$badgeClass = 'bg-rose-50 text-rose-700';
				} else {
					$badgeLabel = 'Chờ xử lý';
					$badgeClass = 'bg-amber-50 text-amber-700';
				}
				$cardClasses = 'space-y-1.5';
				if ($orderStatus === 'cancelled') {
					$cardClasses .= ' opacity-60';
				}
				?>
				<div class="<?php echo $cardClasses; ?>">
					<div class="flex items-center gap-2">
					<div class="text-sm font-mono font-medium text-emerald-700">
						#<?php echo htmlspecialchars($order['order_code']); ?>
					</div>
						<span class="inline-flex items-center rounded-full px-3 py-0.5 text-sm font-medium whitespace-nowrap <?php echo $badgeClass; ?>"><?php echo htmlspecialchars($badgeLabel); ?></span>
					</div>
					<div class="truncate text-sm font-medium text-slate-900">
						<?php if (!empty($order['customer_name'])) { ?>
							<?php echo htmlspecialchars($order['customer_name']); ?>
						<?php } else { ?>
							<span class="text-slate-400">Khách lẻ</span>
						<?php } ?>
					</div>
					<div class="flex flex-wrap items-center gap-x-2 gap-y-1 text-sm text-slate-500">
						<?php if ($timeText !== '') { ?>
							<div class="inline-flex items-center gap-1">
								<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-3.5 w-3.5">
									<path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
								</svg>
								<span><?php echo htmlspecialchars($timeText); ?></span>
							</div>
						<?php } ?>
						<div class="inline-flex items-center gap-1">
							<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-3.5 w-3.5">
								<path stroke-linecap="round" stroke-linejoin="round" d="m21 7.5-9-5.25L3 7.5m18 0-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9" />
							</svg>
							<span><?php echo $itemsCount; ?> sản phẩm</span>
						</div>
					</div>
					<div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-slate-600">
						<span>
							Tổng: <span class="font-medium text-slate-900"><?php echo Money::format($total); ?></span>
						</span>
						<span>
							Đã thu: <span class="font-medium text-emerald-600"><?php echo Money::format($paid); ?></span>
						</span>
						<span>
							Còn nợ:
							<span class="font-medium <?php echo $debt > 0 ? 'text-red-600' : 'text-slate-700'; ?>">
								<?php echo Money::format($debt); ?>
							</span>
						</span>
						<span>
							LN:
							<span class="font-medium <?php echo $profit >= 0 ? 'text-emerald-700' : 'text-rose-700'; ?>">
								<?php echo Money::format($profit); ?>
							</span>
						</span>
					</div>
				</div>
			</a>
		<?php } ?>
	</div>
<?php } ?>


<div class="fixed inset-0 z-[99999] hidden items-center justify-center bg-black/40 p-3" data-order-preview-modal>
	<div class="flex max-h-full w-full max-w-4xl flex-col overflow-hidden rounded-2xl bg-white shadow-xl">
		<div class="flex items-center justify-between border-b border-slate-200 px-3 py-2">
			<div>
				<div class="text-sm font-semibold text-slate-800">
					<span class="order-preview-code-text" data-order-preview-code>Đơn hàng</span>
				</div>
				<div class="text-xs text-slate-500" data-order-preview-date>--</div>
			</div>
			<button type="button" class="inline-flex h-7 w-7 items-center justify-center rounded-full text-slate-400 hover:bg-slate-100 hover:text-slate-600" data-order-preview-close>
				<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4">
					<path fill-rule="evenodd" d="M4.293 4.293a1 1 0 0 1 1.414 0L10 8.586l4.293-4.293a1 1 0 1 1 1.414 1.414L11.414 10l4.293 4.293a1 1 0 0 1-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 0 1-1.414-1.414L8.586 10 4.293 5.707a1 1 0 0 1 0-1.414Z" clip-rule="evenodd" />
				</svg>
			</button>
		</div>
		<div class="flex-1 min-h-0 overflow-y-auto p-3 text-sm" data-order-preview-content>
			<div class="flex items-center justify-center text-slate-500">Đang tải...</div>
		</div>
		<div class="flex items-center justify-end gap-2 border-t border-slate-200 bg-slate-50 px-3 py-2">
			<a href="#" class="inline-flex items-center rounded-lg bg-emerald-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-emerald-700" data-order-preview-open-detail>Xem chi tiết</a>
			<button type="button" class="inline-flex items-center rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-100" data-order-preview-close>Đóng</button>
		</div>
	</div>
</div>

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
<script>
	(function () {
		var modal = document.querySelector('[data-order-preview-modal]');
		var modalContent = modal ? modal.querySelector('[data-order-preview-content]') : null;
		var closeButtons = modal ? modal.querySelectorAll('[data-order-preview-close]') : [];

		function openPreview(html) {
			if (!modal) return;
			modalContent.innerHTML = html;
			modal.classList.remove('hidden');
			modal.classList.add('flex');
		}

		function closePreview() {
			if (!modal) return;
			modal.classList.add('hidden');
			modal.classList.remove('flex');
		}

		if (closeButtons.length > 0) {
			closeButtons.forEach(function (btn) {
				btn.addEventListener('click', function (e) {
					e.preventDefault();
					closePreview();
				});
			});
		}

		if (modal) {
			modal.addEventListener('click', function (e) {
				if (e.target === modal) {
					closePreview();
				}
			});
		}

		document.querySelectorAll('[data-order-preview-btn]').forEach(function (btn) {
			btn.addEventListener('click', function (e) {
				e.preventDefault();
				e.stopPropagation();
				var orderId = btn.getAttribute('data-order-id');
				if (!orderId) return;
			if (!modalContent) return;
			var orderCode = btn.getAttribute('data-order-code') || 'Đơn hàng';
			var orderDate = btn.getAttribute('data-order-date') || '';
			var codeElem = document.querySelector('.order-preview-code-text');
			var dateElem = document.querySelector('[data-order-preview-date]');
			var detailBtn = modal.querySelector('[data-order-preview-open-detail]');
			if (codeElem) codeElem.textContent = orderCode;
			if (dateElem) dateElem.textContent = orderDate;
			if (detailBtn) {
				detailBtn.setAttribute('href', '<?php echo $basePath; ?>/order/view?id=' + encodeURIComponent(orderId));
			}
			openPreview('<div class="py-6 text-center text-slate-500">Đang tải...</div>');
			fetch('<?php echo $basePath; ?>/order/preview?id=' + encodeURIComponent(orderId) + '&ajax=1', {
				method: 'GET',
				credentials: 'same-origin',
				headers: {
					'X-Requested-With': 'XMLHttpRequest'
				},
				})
					.then(function (response) {
						if (!response.ok) {
							throw new Error('Lỗi khi tải dữ liệu: ' + response.status);
						}
						return response.text();
					})
					.then(function (html) {
						openPreview(html);
					})
					.catch(function (error) {
						if (modalContent) {
							modalContent.innerHTML = '<div class="py-6 text-center text-rose-600">' + (error.message || 'Không thể hiển thị dữ liệu') + '</div>';
						}
					});
			});
		});
	})();
</script>
