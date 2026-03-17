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
$grossAmount = $totalAmount + $discountAmount - $surchargeAmount;
if ($grossAmount < 0) {
    $grossAmount = 0.0;
}
$subtotalAmount = $totalAmount + $discountAmount;
$profitOrder = $totalAmount - $totalCost;
$remaining = $totalAmount - (isset($order['paid_amount']) ? (float) $order['paid_amount'] : 0.0);
if ($remaining < 0) {
    $remaining = 0.0;
}
$manualItems = isset($manualItems) && is_array($manualItems) ? $manualItems : [];
$items = isset($items) && is_array($items) ? $items : [];
?>

<div class="space-y-3">
    <div class="grid grid-cols-1 gap-1 text-sm text-slate-600">
        <div class="flex items-center justify-between">
            <span class="text-slate-500">Khách hàng</span>
            <span class="font-semibold text-slate-900"><?php echo !empty($order['customer_name']) ? htmlspecialchars($order['customer_name']) : 'Khách lẻ'; ?></span>
        </div>
        <div class="flex items-center justify-between">
            <span class="text-slate-500">Tổng tiền</span>
            <span class="font-semibold text-slate-900"><?php echo Money::format($totalAmount); ?></span>
        </div>
        <div class="flex items-center justify-between">
            <span class="text-slate-500">Đã thanh toán</span>
            <span class="font-semibold text-emerald-700"><?php echo Money::format(isset($order['paid_amount']) ? (float)$order['paid_amount'] : 0); ?></span>
        </div>
        <div class="flex items-center justify-between">
            <span class="text-slate-500">Còn nợ</span>
            <span class="font-semibold text-rose-700"><?php echo Money::format($remaining); ?></span>
        </div>
    </div>

    <?php if (empty($items) && empty($manualItems)) { ?>
        <div class="rounded-lg border border-dashed border-slate-300 bg-white px-4 py-4 text-center text-sm text-slate-500">
            Đơn hàng không có mặt hàng.
        </div>
    <?php } ?>

    <?php if (!empty($items)) { ?>
        <div class="rounded-lg border border-slate-200 bg-white shadow-sm">
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
        <div class="rounded-lg border border-slate-200 bg-white shadow-sm">
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
