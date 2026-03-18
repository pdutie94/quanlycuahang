<?php
$orderStatusHeader = isset($order['order_status']) ? $order['order_status'] : 'pending';
$totalAmountHeader = isset($order['total_amount']) ? (float) $order['total_amount'] : 0.0;
$paidAmountHeader = isset($order['paid_amount']) ? (float) $order['paid_amount'] : 0.0;
$remainingHeader = $totalAmountHeader - $paidAmountHeader;
if ($remainingHeader < 0) {
	$remainingHeader = 0.0;
}
?>
<div class="relative" data-header-actions-menu>
	<button type="button" class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-slate-300 bg-white text-slate-600 hover:bg-slate-100" data-header-actions-toggle>
		<?php echo ui_icon("ellipsis-vertical", "h-4 w-4"); ?>
	</button>
	<div class="absolute right-0 z-30 mt-2 w-44 rounded-xl border border-slate-200 bg-white py-1 text-sm  overflow-hidden hidden" data-header-actions-dropdown>
		<a href="<?php echo $basePath; ?>/order/invoice?id=<?php echo (int) $order['id']; ?>" class="flex items-center justify-between gap-2 px-3 py-1.5 text-slate-700 hover:bg-slate-50">
			<div class="flex items-center gap-1.5">
				<?php echo ui_icon("document-text", "h-4 w-4 text-slate-600"); ?>
				<span>In hóa đơn</span>
			</div>
		</a>
		<?php if ($remainingHeader > 0 && $orderStatusHeader !== 'cancelled') { ?>
			<button type="button" class="flex w-full items-center justify-between gap-2 px-3 py-1.5 text-left text-slate-700 hover:bg-slate-50" data-order-payment-open>
				<div class="flex items-center gap-1.5">
					<?php echo ui_icon("banknotes", "h-4 w-4 text-brand-600"); ?>
					<span>Thu tiền</span>
				</div>
			</button>
		<?php } ?>
		<?php if ($order['status'] === 'paid' && $orderStatusHeader !== 'cancelled') { ?>
			<form method="post" action="<?php echo $basePath; ?>/order/paymentReset" class="flex items-center justify-between gap-2 px-3 py-1.5 text-slate-700 hover:bg-amber-50">
				<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
				<input type="hidden" name="id" value="<?php echo $order['id']; ?>">
				<button type="submit" class="flex w-full items-center justify-between gap-2 text-left" onclick="return confirm('Đặt lại về CHƯA THANH TOÁN? Hệ thống sẽ xóa toàn bộ lịch sử thu tiền của đơn này.')">
					<div class="flex items-center gap-1.5">
						<?php echo ui_icon("undo-left", "h-4 w-4 text-amber-600"); ?>
						<span>Đặt lại thanh toán</span>
					</div>
				</button>
			</form>
		<?php } ?>
		<?php if ($orderStatusHeader !== 'completed' && $orderStatusHeader !== 'cancelled') { ?>
			<a href="<?php echo $basePath; ?>/order/edit?id=<?php echo $order['id']; ?>" class="flex items-center justify-between gap-2 px-3 py-1.5 text-slate-700 hover:bg-slate-50">
				<div class="flex items-center gap-1.5">
					<?php echo ui_icon("pencil-square", "h-4 w-4 text-slate-500"); ?>
					<span>Sửa đơn</span>
				</div>
			</a>
		<?php } ?>
		<?php if ($orderStatusHeader !== 'cancelled') { ?>
			<a href="<?php echo $basePath; ?>/order/returnForm?id=<?php echo $order['id']; ?>" class="flex items-center justify-between gap-2 px-3 py-1.5 text-rose-600 hover:bg-rose-50">
				<div class="flex items-center gap-1.5">
					<?php echo ui_icon("return", "h-4 w-4 text-rose-500"); ?>
					<span>Trả hàng</span>
				</div>
			</a>
		<?php } ?>
		<?php if ($orderStatusHeader !== 'completed') { ?>
			<form method="post" action="<?php echo $basePath; ?>/order/delete" class="flex items-center justify-between gap-2 px-3 py-1.5 text-slate-700 hover:bg-rose-50" onsubmit="return confirm('Bạn có chắc chắn muốn xóa tạm đơn hàng này? Đơn sẽ được lưu 30 ngày trước khi xóa hẳn.');">
				<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
				<input type="hidden" name="id" value="<?php echo (int) $order['id']; ?>">
				<button type="submit" class="flex w-full items-center justify-between gap-2 text-left">
					<div class="flex items-center gap-1.5">
						<?php echo ui_icon("delete-box", "h-4 w-4 text-rose-500"); ?>
						<span class="text-rose-600">Xóa đơn hàng</span>
					</div>
				</button>
			</form>
		<?php } ?>
	</div>
</div>
