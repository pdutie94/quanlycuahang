<?php
$orderCardData = isset($orderCardData) && is_array($orderCardData) ? $orderCardData : [];
$orderCardUrl = isset($orderCardUrl) ? (string) $orderCardUrl : ($basePath . '/order/view?id=' . (int) (isset($orderCardData['id']) ? $orderCardData['id'] : 0));
$orderCardExtraAttrs = isset($orderCardExtraAttrs) ? (string) $orderCardExtraAttrs : '';

$total = isset($orderCardData['total_amount']) ? (float) $orderCardData['total_amount'] : 0.0;
$paid = isset($orderCardData['paid_amount']) ? (float) $orderCardData['paid_amount'] : 0.0;
$debt = $total - $paid;
$cost = isset($orderCardData['total_cost']) ? (float) $orderCardData['total_cost'] : 0.0;
$profit = $total - $cost;

$orderCode = isset($orderCardData['order_code']) ? $orderCardData['order_code'] : (isset($orderCardData['code']) ? $orderCardData['code'] : '');
$orderDateValue = isset($orderCardData['order_date']) ? $orderCardData['order_date'] : (isset($orderCardData['doc_date']) ? $orderCardData['doc_date'] : '');
$timeText = '';
if (!empty($orderDateValue)) {
    $ts = strtotime($orderDateValue);
    if ($ts !== false) {
        $timeText = date('H:i, d/m/Y', $ts);
    }
}

$itemsCount = isset($orderCardData['items_count']) ? (int) $orderCardData['items_count'] : 0;
$customerName = isset($orderCardData['customer_name']) ? trim((string) $orderCardData['customer_name']) : '';

$orderStatus = isset($orderCardData['order_status']) ? $orderCardData['order_status'] : 'pending';
$statusValue = isset($orderCardData['status']) ? $orderCardData['status'] : '';
$badgeLabel = '';
$badgeClass = '';
if ($orderStatus === 'cancelled') {
    $badgeLabel = 'Đã hủy';
    $badgeClass = 'bg-slate-100 text-slate-500';
} elseif ($statusValue === 'paid' || ($total > 0 && $paid >= $total)) {
    $badgeLabel = 'Đã thanh toán';
    $badgeClass = 'bg-brand-50 text-brand-700';
} elseif ($debt > 0) {
    $badgeLabel = 'Còn nợ';
    $badgeClass = 'bg-rose-50 text-rose-700';
} else {
    $badgeLabel = 'Chờ xử lý';
    $badgeClass = 'bg-amber-50 text-amber-700';
}
?>

<a href="<?php echo htmlspecialchars($orderCardUrl); ?>" class="relative block rounded-card border border-slate-200 bg-white p-3 transition-colors hover:border-brand-200" <?php echo $orderCardExtraAttrs; ?>>
    <button type="button" class="absolute right-2 top-2 inline-flex h-8 w-8 items-center justify-center rounded-full text-slate-500 transition-colors hover:bg-brand-50 hover:text-brand-600" data-order-preview-btn data-order-id="<?php echo (int) (isset($orderCardData['id']) ? $orderCardData['id'] : 0); ?>" data-order-code="<?php echo htmlspecialchars((string) $orderCode); ?>" data-order-date="<?php echo htmlspecialchars($timeText); ?>" data-order-customer="<?php echo htmlspecialchars($customerName); ?>">
        <?php echo ui_icon("eye", "h-4 w-4"); ?>
    </button>

    <div class="space-y-1.5 pr-10 <?php echo $orderStatus === 'cancelled' ? 'opacity-60' : ''; ?>">
        <div class="flex items-center gap-2">
            <div class="text-sm font-mono font-semibold text-brand-700">#<?php echo htmlspecialchars((string) $orderCode); ?></div>
            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium <?php echo $badgeClass; ?>"><?php echo htmlspecialchars($badgeLabel); ?></span>
        </div>

        <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-slate-500">
            <?php if ($customerName !== '') { ?>
                <span class="inline-flex min-w-0 items-center gap-1">
					<?php echo ui_icon("user", "h-3.5 w-3.5 text-slate-400"); ?>
                    <span class="truncate font-medium text-slate-700"><?php echo htmlspecialchars($customerName); ?></span>
                </span>
            <?php } ?>
            <?php if ($timeText !== '') { ?>
                <span class="inline-flex items-center gap-1">
					<?php echo ui_icon("clock", "h-3.5 w-3.5 text-slate-400"); ?>
                    <span><?php echo htmlspecialchars($timeText); ?></span>
                </span>
            <?php } ?>
            <?php if ($itemsCount > 0) { ?>
                <span class="inline-flex items-center gap-1">
                    <?php echo ui_icon("cube", "h-3.5 w-3.5 text-slate-400"); ?>
                    <span><?php echo $itemsCount; ?> sản phẩm</span>
                </span>
            <?php } ?>
        </div>

        <div class="flex flex-wrap items-center gap-x-3 text-sm">
            <span class="text-slate-600">Tổng: <span class="font-semibold text-slate-900"><?php echo Money::format($total); ?></span></span>
            <span class="text-slate-600">Thu: <span class="font-semibold text-brand-700"><?php echo Money::format($paid); ?></span></span>
            <span class="text-slate-600">Nợ: <span class="font-semibold <?php echo $debt > 0 ? 'text-rose-700' : 'text-slate-800'; ?>"><?php echo Money::format($debt); ?></span></span>
            <span class="text-slate-600">LN: <span class="font-semibold <?php echo $profit >= 0 ? 'text-brand-700' : 'text-rose-700'; ?>"><?php echo Money::format($profit); ?></span></span>
        </div>
    </div>
</a>