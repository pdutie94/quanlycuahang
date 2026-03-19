<div class="mb-4 flex items-center justify-between gap-3">
	<h1 class="text-lg font-medium tracking-tight">Thu tiền khách hàng</h1>
	<a href="<?php echo $basePath; ?>/customer/view?id=<?php echo $order['customer_id']; ?>" class="inline-flex items-center gap-1 rounded-lg border border-slate-300 px-2.5 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-100">
		<?php echo ui_icon("chevron-left", "h-4 w-4"); ?>
		<span>Khách hàng</span>
	</a>
</div>

<div class="mb-4 rounded-lg border border-slate-200 bg-white px-4 py-3 ">
    <div class="text-sm font-medium text-slate-800"><?php echo htmlspecialchars($order['customer_name']); ?></div>
    <div class="mt-1 text-sm text-slate-600">
        <?php if (!empty($order['customer_phone'])) { ?>
            <span class="mr-4">SĐT: <?php echo htmlspecialchars($order['customer_phone']); ?></span>
        <?php } ?>
        <?php if (!empty($order['customer_address'])) { ?>
            <span>Địa chỉ: <?php echo htmlspecialchars($order['customer_address']); ?></span>
        <?php } ?>
    </div>
    <div class="mt-2 grid grid-cols-1 gap-2 text-sm sm:grid-cols-3">
        <div>
            <div class="text-slate-500">Mã đơn</div>
            <div class="font-mono text-slate-800"><?php echo htmlspecialchars($order['order_code']); ?></div>
        </div>
        <div>
            <div class="text-slate-500">Tổng tiền</div>
            <div class="font-medium text-slate-800"><?php echo Money::format($order['total_amount']); ?></div>
        </div>
        <div>
            <div class="text-slate-500">Còn nợ</div>
            <div class="font-medium text-red-600"><?php echo Money::format($remaining); ?></div>
        </div>
    </div>
</div>

<div class="rounded-lg border border-slate-200 bg-white px-4 py-4 ">
    <form method="post" action="<?php echo $basePath; ?>/customer/payment/store" class="space-y-4">
        <input type="hidden" hidden name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
		<div class="relative">
			<label class="absolute left-3 top-0 z-10 -translate-y-1/2 bg-white px-1 leading-none text-sm text-slate-700">Số tiền thu</label>
			<div class="relative">
				<?php
				$amountValue = htmlspecialchars(number_format($remaining, 0, '', '.'));
				ui_input_text('amount', $amountValue, [
					'inputmode' => 'numeric',
					'data-money-input' => '1',
					'class' => 'pr-9 pt-3 pb-2.5 text-right'
				]);
				?>
				<span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-sm text-slate-500">đ</span>
			</div>
        </div>
		<div class="relative">
			<label class="absolute left-3 top-0 z-10 -translate-y-1/2 bg-white px-1 leading-none text-sm text-slate-700">Ghi chú</label>
            <textarea name="note" rows="3" class="form-field block w-full rounded-xl border border-slate-300 bg-white px-3.5 pt-3 pb-2.5 text-sm outline-none transition focus:border-brand-500"></textarea>
        </div>
        <div class="flex items-center justify-end gap-3">
            <a href="<?php echo $basePath; ?>/customer/view?id=<?php echo $order['customer_id']; ?>" class="inline-flex h-[34px] min-h-[34px] items-center justify-center rounded-lg border border-slate-300 px-4 text-sm font-medium text-slate-700 hover:bg-slate-100">
                Hủy
            </a>
            <?php ui_button_primary('Ghi nhận thanh toán', ['type' => 'submit', 'data-loading-button' => '1']); ?>
        </div>
    </form>
</div>
