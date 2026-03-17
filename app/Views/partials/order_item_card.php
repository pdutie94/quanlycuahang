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
    $badgeClass = 'bg-emerald-50 text-emerald-700';
} elseif ($debt > 0) {
    $badgeLabel = 'Còn nợ';
    $badgeClass = 'bg-rose-50 text-rose-700';
} else {
    $badgeLabel = 'Chờ xử lý';
    $badgeClass = 'bg-amber-50 text-amber-700';
}
?>

<a href="<?php echo htmlspecialchars($orderCardUrl); ?>" class="relative block rounded-2xl bg-white p-3 shadow-sm ring-1 ring-slate-100 transition hover:shadow-md" <?php echo $orderCardExtraAttrs; ?>>
    <button type="button" class="absolute right-3 top-3 inline-flex h-6 w-6 items-center justify-center rounded-full bg-slate-100 text-slate-500 transition-colors hover:bg-emerald-50 hover:text-emerald-600" data-order-preview-btn data-order-id="<?php echo (int) (isset($orderCardData['id']) ? $orderCardData['id'] : 0); ?>" data-order-code="<?php echo htmlspecialchars((string) $orderCode); ?>" data-order-date="<?php echo htmlspecialchars($timeText); ?>" data-order-customer="<?php echo htmlspecialchars($customerName); ?>">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-4 w-4">
            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.964-7.178z" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
        </svg>
    </button>

    <div class="space-y-1.5 pr-8 <?php echo $orderStatus === 'cancelled' ? 'opacity-60' : ''; ?>">
        <div class="flex items-center gap-2">
            <div class="text-sm font-mono font-semibold text-emerald-700">#<?php echo htmlspecialchars((string) $orderCode); ?></div>
            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium <?php echo $badgeClass; ?>"><?php echo htmlspecialchars($badgeLabel); ?></span>
        </div>

        <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-slate-500">
            <?php if ($customerName !== '') { ?>
                <span class="inline-flex min-w-0 items-center gap-1">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-3.5 w-3.5 text-slate-400">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0ZM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                    </svg>
                    <span class="truncate font-medium text-slate-700"><?php echo htmlspecialchars($customerName); ?></span>
                </span>
            <?php } ?>
            <?php if ($timeText !== '') { ?>
                <span class="inline-flex items-center gap-1">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-3.5 w-3.5 text-slate-400">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2m6-2a9 9 0 11-18 0 9 9 0 0118 0Z" />
                    </svg>
                    <span><?php echo htmlspecialchars($timeText); ?></span>
                </span>
            <?php } ?>
            <?php if ($itemsCount > 0) { ?>
                <span class="inline-flex items-center gap-1">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-3.5 w-3.5 text-slate-400">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 7.5-9-5.25L3 7.5m18 0-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9" />
                    </svg>
                    <span><?php echo $itemsCount; ?> sản phẩm</span>
                </span>
            <?php } ?>
        </div>

        <div class="flex flex-wrap items-center gap-x-3 text-sm">
            <span class="text-slate-600">Tổng: <span class="font-semibold text-slate-900"><?php echo Money::format($total); ?></span></span>
            <span class="text-slate-600">Thu: <span class="font-semibold text-emerald-700"><?php echo Money::format($paid); ?></span></span>
            <span class="text-slate-600">Nợ: <span class="font-semibold <?php echo $debt > 0 ? 'text-rose-700' : 'text-slate-800'; ?>"><?php echo Money::format($debt); ?></span></span>
            <span class="text-slate-600">LN: <span class="font-semibold <?php echo $profit >= 0 ? 'text-emerald-700' : 'text-rose-700'; ?>"><?php echo Money::format($profit); ?></span></span>
        </div>
    </div>
</a>