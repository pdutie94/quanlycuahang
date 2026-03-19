<?php if (!isset($detailHeader)) { ?>
<div class="mb-4 flex items-center justify-between gap-3">
	<h1 class="text-lg font-medium tracking-tight">Khách hàng</h1>
    <div class="flex items-center gap-2" data-header-actions-root>
		<a href="<?php echo $basePath; ?>/customer" class="inline-flex items-center gap-1 rounded-lg border border-slate-300 px-2.5 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-100">
			<?php echo ui_icon("chevron-left", "h-4 w-4"); ?>
			<span>Danh sách</span>
		</a>
		<div class="relative" data-header-actions-menu>
			<button type="button" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-300 bg-white text-slate-600 hover:bg-slate-100" data-header-actions-toggle>
				<?php echo ui_icon("ellipsis-vertical", "h-4 w-4"); ?>
			</button>
			<div class="absolute right-0 z-30 mt-2 w-44 rounded-xl border border-slate-200 bg-white py-1 text-sm  overflow-hidden hidden" data-header-actions-dropdown>
				<a href="<?php echo $basePath; ?>/customer/edit?id=<?php echo (int) $customer['id']; ?>" class="flex items-center justify-between gap-2 px-3 py-1.5 text-slate-700 hover:bg-slate-50">
					<div class="flex items-center gap-1.5">
						<?php echo ui_icon("pencil-square", "h-4 w-4 text-slate-500"); ?>
						<span>Chỉnh sửa</span>
					</div>
				</a>
				<form method="post" action="<?php echo $basePath; ?>/customer/delete" onsubmit="return confirm('Xóa khách hàng sẽ chuyển các đơn hàng về khách lẻ. Bạn chắc chắn muốn xóa?');">
					<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>" />
					<input type="hidden" name="id" value="<?php echo (int) $customer['id']; ?>" />
					<button type="submit" class="flex w-full items-center justify-between gap-2 px-3 py-1.5 text-left text-rose-600 hover:bg-rose-50">
						<div class="flex items-center gap-1.5">
							<?php echo ui_icon("trash", "h-4 w-4 text-rose-500"); ?>
							<span>Xóa khách hàng</span>
						</div>
					</button>
				</form>
			</div>
		</div>
	</div>
</div>
<?php } ?>

<div class="mb-4 rounded-lg border border-slate-200 bg-white px-4 py-3 ">
    <div class="text-sm font-medium text-slate-800"><?php echo htmlspecialchars($customer['name']); ?></div>
    <div class="mt-1 text-sm text-slate-600">
        <?php if (!empty($customer['phone'])) { ?>
            <span class="mr-4">SĐT: <?php echo htmlspecialchars($customer['phone']); ?></span>
        <?php } ?>
        <?php if (!empty($customer['address'])) { ?>
            <span>Địa chỉ: <?php echo htmlspecialchars($customer['address']); ?></span>
        <?php } ?>
    </div>
	<?php
	$totalAmountSum = isset($totalAmountSum) ? (float) $totalAmountSum : 0.0;
	$totalPaidSum = isset($totalPaidSum) ? (float) $totalPaidSum : 0.0;
	$totalDebt = isset($totalDebt) ? (float) $totalDebt : 0.0;
	?>
	<div class="mt-3 grid grid-cols-1 gap-2 sm:grid-cols-2 text-sm">
		<div class="rounded-md bg-slate-50 px-3 py-2">
			<div class="text-sm font-medium uppercase  text-slate-500">Tổng tiền</div>
			<div class="mt-1 text-sm old-text-base font-medium text-slate-900"><?php echo Money::format($totalAmountSum); ?></div>
		</div>
		<div class="rounded-md bg-brand-50 px-3 py-2">
			<div class="text-sm font-medium uppercase  text-brand-600">Đã thu</div>
			<div class="mt-1 text-sm old-text-base font-medium text-brand-700"><?php echo Money::format($totalPaidSum); ?></div>
		</div>
		<div class="rounded-md bg-slate-50 px-3 py-2">
			<div class="text-sm font-medium uppercase  text-slate-500">Còn nợ</div>
			<div class="mt-1 text-sm old-text-base font-medium <?php echo $totalDebt > 0 ? 'text-red-600' : 'text-slate-700'; ?>"><?php echo Money::format($totalDebt); ?></div>
		</div>
	</div>
</div>

<?php if (empty($orders)) { ?>
	<div class="rounded-lg border border-dashed border-slate-300 bg-white px-4 py-4 text-center text-sm text-slate-500">
		Khách hàng chưa có đơn hàng nào.
	</div>
<?php } else { ?>
	<div class="mb-2 flex items-center justify-between text-sm text-slate-600">
		<div class="font-medium uppercase  text-slate-500">Đơn hàng</div>
		<button type="button" class="inline-flex items-center rounded-lg border border-slate-300 px-2 py-0.5 text-sm font-medium text-slate-600 hover:bg-slate-100" data-customer-selection-toggle>
			<?php echo ui_icon("chevron-right", "mr-1 h-3.5 w-3.5"); ?>
			<span>Chọn đơn</span>
		</button>
	</div>
	<div class="mb-3 hidden rounded-lg border border-brand-200 bg-brand-50 px-3 py-2 text-sm text-slate-700" data-customer-order-selection-panel>
		<div class="flex items-center gap-1.5">
			<span class="inline-flex h-5 w-5 items-center justify-center rounded-lg bg-brand-100 text-brand-700">
				<?php echo ui_icon("arrow-left-on-rectangle", "h-3.5 w-3.5"); ?>
			</span>
			<span class="font-medium text-slate-800">Đã chọn <span data-customer-selected-count>0</span> đơn</span>
		</div>
		<div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 sm:mt-0">
			<span>Tổng: <span class="font-medium text-slate-900" data-customer-selected-total>0 đ</span></span>
			<span>Đã thu: <span class="font-medium text-brand-700" data-customer-selected-paid>0 đ</span></span>
			<span>Còn nợ: <span class="font-medium text-rose-600" data-customer-selected-debt>0 đ</span></span>
			<button type="button" class="inline-flex items-center rounded-lg border border-slate-300 px-2 py-0.5 text-sm font-medium text-slate-600 hover:bg-slate-100" data-customer-selection-clear>Hủy chọn</button>
		</div>
	</div>
	<div class="space-y-3" data-customer-order-list>
		<?php foreach ($orders as $order) { ?>
			<?php
			$total = (float) $order['total_amount'];
			$paid = (float) $order['paid_amount'];
			$debt = $total - $paid;
			$cost = isset($order['total_cost']) ? (float) $order['total_cost'] : 0.0;
			$profit = $total - $cost;
			$timeText = '';
			if (!empty($order['order_date'])) {
				$ts = strtotime($order['order_date']);
				if ($ts !== false) {
					$timeText = date('H:i, d/m/Y', $ts);
				}
			}
			$itemsCount = isset($order['items_count']) ? (int) $order['items_count'] : null;
			$orderStatus = isset($order['order_status']) ? $order['order_status'] : 'pending';
			$badgeLabel = '';
			$badgeClass = '';
			if ($orderStatus === 'cancelled') {
				$badgeLabel = 'Đã hủy';
				$badgeClass = 'bg-slate-100 text-slate-500';
			} elseif ($order['status'] === 'paid') {
				$badgeLabel = 'Đã thanh toán';
				$badgeClass = 'bg-brand-50 text-brand-700';
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
			$isDeleted = isset($order['deleted_at']) && $order['deleted_at'] !== null;
			if ($isDeleted) {
				$cardClasses .= ' opacity-60';
				$badgeLabel = 'Đã xóa tạm';
				$badgeClass = 'bg-slate-100 text-slate-500';
			}
			?>
			<a href="<?php echo $basePath; ?>/order/view?id=<?php echo $order['id']; ?>" class="relative block rounded-card bg-white p-3 border border-slate-200 transition hover:bg-slate-50" data-customer-order-item data-order-id="<?php echo (int) $order['id']; ?>" data-order-total="<?php echo $total; ?>" data-order-paid="<?php echo $paid; ?>" data-order-debt="<?php echo $debt; ?>">
				<div class="<?php echo $cardClasses; ?>">
					<div class="min-w-0">
						<div class="flex items-center gap-2">
							<div class="text-sm font-mono font-medium text-brand-700">
								#<?php echo htmlspecialchars($order['order_code']); ?>
							</div>
							<span class="inline-flex items-center rounded-lg px-3 py-0.5 text-sm font-medium whitespace-nowrap <?php echo $badgeClass; ?>"><?php echo htmlspecialchars($badgeLabel); ?></span>
						</div>
						<div class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-1 text-sm text-slate-500">
							<?php if ($timeText !== '') { ?>
								<div class="inline-flex items-center gap-1">
									<?php echo ui_icon("calendar", "h-3.5 w-3.5"); ?>
									<span><?php echo htmlspecialchars($timeText); ?></span>
								</div>
							<?php } ?>
							<?php if ($itemsCount !== null) { ?>
								<div class="inline-flex items-center gap-1">
									<?php echo ui_icon("cube", "h-3.5 w-3.5"); ?>
									<span><?php echo $itemsCount; ?> sản phẩm</span>
								</div>
							<?php } ?>
						</div>
						<div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-slate-600">
							<span>
								Tổng: <span class="font-medium text-slate-900"><?php echo Money::format($total); ?></span>
							</span>
							<span>
								Đã thu: <span class="font-medium text-brand-600"><?php echo Money::format($paid); ?></span>
							</span>
							<span>
								Còn nợ: <span class="font-medium <?php echo $debt > 0 ? 'text-rose-600' : 'text-slate-700'; ?>"><?php echo Money::format($debt); ?></span>
							</span>
							<span>
								Lợi nhuận: <span class="font-medium <?php echo $profit >= 0 ? 'text-brand-600' : 'text-rose-600'; ?>"><span class="font-medium"><?php echo Money::format($profit); ?></span></span>
							</span>
						</div>
					</div>
				</div>
			</a>
		<?php } ?>
	</div>
<?php } ?>
