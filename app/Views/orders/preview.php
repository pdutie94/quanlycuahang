<?php
$orderStatus = isset($order['order_status']) ? $order['order_status'] : 'pending';
$totalAmount = isset($order['total_amount']) ? (float) $order['total_amount'] : 0.0;
$totalCost = isset($order['total_cost']) ? (float) $order['total_cost'] : 0.0;
$discountAmount = isset($order['discount_amount']) ? (float) $order['discount_amount'] : 0.0;
if ($discountAmount < 0) {
    $discountAmount = 0.0;
}
$surchargeAmount = isset($order['surcharge_amount']) ? (float) $order['surcharge_amount'] : 0.0;
if ($surchargeAmount < 0) {
    $surchargeAmount = 0.0;
}
$baseAmount = $totalAmount + $discountAmount - $surchargeAmount;
if ($baseAmount < 0) {
    $baseAmount = 0.0;
}
$profitOrder = $totalAmount - $totalCost;
$remaining = $totalAmount - (isset($order['paid_amount']) ? (float) $order['paid_amount'] : 0.0);
if ($remaining < 0) {
    $remaining = 0.0;
}
$paymentStatus = isset($order['status']) ? (string) $order['status'] : '';
$paymentStatusLabel = 'Chờ thanh toán';
$paymentStatusClass = 'bg-amber-50 text-amber-700';
if ($paymentStatus === 'paid' || ($totalAmount > 0 && (isset($order['paid_amount']) ? (float) $order['paid_amount'] : 0.0) >= $totalAmount)) {
    $paymentStatusLabel = 'Đã thanh toán';
    $paymentStatusClass = 'bg-brand-50 text-brand-700';
} elseif ($remaining > 0) {
    $paymentStatusLabel = 'Còn nợ';
    $paymentStatusClass = 'bg-rose-50 text-rose-700';
}
$orderStatusLabel = 'Chờ xử lý';
$orderStatusClass = 'bg-amber-50 text-amber-700';
if ($orderStatus === 'cancelled') {
    $orderStatusLabel = 'Đã hủy';
    $orderStatusClass = 'bg-slate-100 text-slate-600';
} elseif ($orderStatus === 'completed') {
    $orderStatusLabel = 'Hoàn tất';
    $orderStatusClass = 'bg-sky-50 text-sky-700';
}
$debtClass = $remaining > 0 ? 'text-rose-700' : 'text-slate-700';
$orderNote = isset($order['note']) ? trim((string) $order['note']) : (isset($order['notes']) ? trim((string) $order['notes']) : '');
$manualItems = isset($manualItems) && is_array($manualItems) ? $manualItems : [];
$items = isset($items) && is_array($items) ? $items : [];
?>

<div class="space-y-3">
    <div class="space-y-3">
        <div>
            <div class="flex flex-wrap items-center gap-2">
                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold <?php echo $paymentStatusClass; ?>"><?php echo htmlspecialchars($paymentStatusLabel); ?></span>
                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold <?php echo $orderStatusClass; ?>"><?php echo htmlspecialchars($orderStatusLabel); ?></span>
            </div>
        </div>

        <div class="rounded-lg bg-white">
            <div class="space-y-1.5">
                <div class="flex items-center justify-between gap-3">
                    <span class="text-slate-500">Tạm tính</span>
                    <span class="font-medium text-slate-900"><?php echo Money::format($baseAmount); ?></span>
                </div>
                <div class="flex items-center justify-between gap-3">
                    <span class="text-slate-500">Giảm giá</span>
                    <span class="font-medium <?php echo $discountAmount > 0 ? 'text-brand-700' : 'text-slate-400'; ?>"><?php echo $discountAmount > 0 ? '-' . Money::format($discountAmount) : Money::format(0); ?></span>
                </div>
                <div class="flex items-center justify-between gap-3">
                    <span class="text-slate-500">Phụ thu</span>
                    <span class="font-medium <?php echo $surchargeAmount > 0 ? 'text-amber-700' : 'text-slate-400'; ?>"><?php echo $surchargeAmount > 0 ? '+' . Money::format($surchargeAmount) : Money::format(0); ?></span>
                </div>
            </div>
            <div class="my-2 border-t border-dashed border-slate-200"></div>
            <div class="flex items-center justify-between gap-3">
                <span class="font-medium text-slate-700">Tổng cộng</span>
                <span class="text-base font-semibold text-slate-900"><?php echo Money::format($totalAmount); ?></span>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-3">
            <div class="rounded-lg bg-white px-2.5 py-2 ring-1 ring-slate-200">
                <div class="text-xs text-slate-500">Đã thanh toán</div>
                <div class="mt-0.5 font-semibold text-brand-700"><?php echo Money::format(isset($order['paid_amount']) ? (float)$order['paid_amount'] : 0); ?></div>
            </div>
            <div class="rounded-lg bg-white px-2.5 py-2 ring-1 ring-slate-200">
                <div class="text-xs text-slate-500">Còn nợ</div>
                <div class="mt-0.5 font-semibold <?php echo $debtClass; ?>"><?php echo Money::format($remaining); ?></div>
            </div>
        </div>
    </div>

    <?php if ($orderNote !== '') { ?>
        <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm">
            <div class="text-slate-500">Ghi chú</div>
            <div class="mt-1 text-slate-800"><?php echo nl2br(htmlspecialchars($orderNote)); ?></div>
        </div>
    <?php } ?>

    <?php if (empty($items) && empty($manualItems)) { ?>
        <div class="rounded-lg border border-dashed border-slate-300 bg-white px-4 py-4 text-center text-sm text-slate-500">
            Đơn hàng không có mặt hàng.
        </div>
    <?php } ?>

    <?php if (!empty($items)) { ?>
        <div class="rounded-lg border border-slate-200 bg-white ">
            <div class="border-b border-slate-100 px-3 py-2 text-sm font-medium text-slate-800">Sản phẩm</div>
            <div class="divide-y divide-slate-100">
                <?php foreach ($items as $item) {
                    $productName = isset($item['product_name']) ? $item['product_name'] : '';
                    $qtyDisplay = rtrim(rtrim(number_format($item['qty'], 2, ',', ''), '0'), ',');
                    $itemProfit = (float)$item['qty'] * ((float)$item['price_sell'] - (float)$item['price_cost']);
                ?>
                    <div class="flex items-center justify-between px-3 py-1 text-sm">
                        <div>
                            <div class="font-medium text-slate-900"><?php echo htmlspecialchars($productName); ?></div>
                            <div class="text-slate-500">SL: <?php echo $qtyDisplay; ?> <?php echo htmlspecialchars($item['unit_name']); ?> - Giá: <?php echo Money::format($item['price_sell']); ?></div>
                        </div>
                        <div class="text-slate-900"><?php echo Money::format($item['amount']); ?></div>
                    </div>
                <?php } ?>
            </div>
        </div>
    <?php } ?>

    <?php if (!empty($manualItems)) { ?>
        <div class="rounded-lg border border-slate-200 bg-white ">
            <div class="border-b border-slate-100 px-3 py-2 text-sm font-medium text-slate-800">Sản phẩm khác</div>
            <div class="divide-y divide-slate-100">
                <?php foreach ($manualItems as $row) {
                    $qtyVal = isset($row['qty']) ? (float)$row['qty'] : 0.0;
                    $qtyText = rtrim(rtrim(number_format($qtyVal, 2, ',', ''), '0'), ',');
                    $amountSell = isset($row['amount_sell']) ? (float)$row['amount_sell'] : 0.0;
                ?>
                    <div class="flex items-center justify-between px-3 py-1 text-sm">
                        <div>
                            <div class="font-medium text-slate-900"><?php echo htmlspecialchars($row['item_name']); ?></div>
                            <div class="text-slate-500">SL: <?php echo $qtyText ?: '0'; ?> <?php echo htmlspecialchars($row['unit_name']); ?> - Giá: <?php echo Money::format($row['price_sell']); ?></div>
                        </div>
                        <div class="text-slate-900"><?php echo Money::format($amountSell); ?></div>
                    </div>
                <?php } ?>
            </div>
        </div>
    <?php } ?>
</div>
