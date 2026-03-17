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
		<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4">
			<path stroke-linecap="round" stroke-linejoin="round" d="M12 6.75a1.5 1.5 0 110-3 1.5 1.5 0 010 3zM12 13.5a1.5 1.5 0 110-3 1.5 1.5 0 010 3zM12 20.25a1.5 1.5 0 110-3 1.5 1.5 0 010 3z" />
		</svg>
	</button>
	<div class="absolute right-0 z-30 mt-2 w-44 rounded-xl border border-slate-200 bg-white py-1 text-sm shadow-lg overflow-hidden hidden" data-header-actions-dropdown>
		<a href="<?php echo $basePath; ?>/order/invoice?id=<?php echo (int) $order['id']; ?>" class="flex items-center justify-between gap-2 px-3 py-1.5 text-slate-700 hover:bg-slate-50">
			<div class="flex items-center gap-1.5">
				<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke-width="1.5" stroke="currentColor" class="h-4 w-4 text-slate-600">
					<path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3h10.5A2.25 2.25 0 0 1 19.5 5.25v13.5A2.25 2.25 0 0 1 17.25 21H6.75A2.25 2.25 0 0 1 4.5 18.75V5.25A2.25 2.25 0 0 1 6.75 3zm3 4.5h4.5m-4.5 3h4.5m-4.5 3h2.25" />
				</svg>
				<span>In hóa đơn</span>
			</div>
		</a>
		<?php if ($remainingHeader > 0 && $orderStatusHeader !== 'cancelled') { ?>
			<button type="button" class="flex w-full items-center justify-between gap-2 px-3 py-1.5 text-left text-slate-700 hover:bg-slate-50" data-order-payment-open>
				<div class="flex items-center gap-1.5">
					<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4 text-emerald-600">
						<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z" />
					</svg>
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
						<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4 text-amber-600">
							<path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l-3 3m0 0l3 3m-3-3h7.5a3.75 3.75 0 000-7.5h-1.5" />
						</svg>
						<span>Đặt lại thanh toán</span>
					</div>
				</button>
			</form>
		<?php } ?>
		<?php if ($orderStatusHeader !== 'completed' && $orderStatusHeader !== 'cancelled') { ?>
			<a href="<?php echo $basePath; ?>/order/edit?id=<?php echo $order['id']; ?>" class="flex items-center justify-between gap-2 px-3 py-1.5 text-slate-700 hover:bg-slate-50">
				<div class="flex items-center gap-1.5">
					<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4 text-slate-500">
						<path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931ZM16.862 4.487L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H9" />
					</svg>
					<span>Sửa đơn</span>
				</div>
			</a>
		<?php } ?>
		<?php if ($orderStatusHeader !== 'cancelled') { ?>
			<a href="<?php echo $basePath; ?>/order/returnForm?id=<?php echo $order['id']; ?>" class="flex items-center justify-between gap-2 px-3 py-1.5 text-rose-600 hover:bg-rose-50">
				<div class="flex items-center gap-1.5">
					<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4 text-rose-500">
						<path stroke-linecap="round" stroke-linejoin="round" d="M9 10.5V6.75A2.25 2.25 0 0 1 11.25 4.5h6A2.25 2.25 0 0 1 19.5 6.75V9M9 10.5 6.75 8.25M9 10.5 11.25 8.25M9 13.5v3.75A2.25 2.25 0 0 0 11.25 19.5h6A2.25 2.25 0 0 0 19.5 17.25V15M9 13.5 6.75 15.75M9 13.5 11.25 15.75" />
					</svg>
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
						<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4 text-rose-500">
							<path stroke-linecap="round" stroke-linejoin="round" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M4.5 6.75h15m-1.5 0l-.621 12.42A1.125 1.125 0 0 1 16.257 20.25H7.743a1.125 1.125 0 0 1-1.122-1.08L6 6.75m3-3h6A1.125 1.125 0 0 1 16.125 4.875V6.75H7.875V4.875A1.125 1.125 0 0 1 9 3.75Z" />
						</svg>
						<span class="text-rose-600">Xóa đơn hàng</span>
					</div>
				</button>
			</form>
		<?php } ?>
	</div>
</div>
