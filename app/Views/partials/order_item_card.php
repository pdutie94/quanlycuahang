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

$customerName = isset($orderCardData['customer_name']) ? trim((string) $orderCardData['customer_name']) : '';
$displayCustomerName = $customerName !== '' ? $customerName : 'Khách lẻ';

$orderStatus = isset($orderCardData['order_status']) ? $orderCardData['order_status'] : 'pending';
$statusValue = isset($orderCardData['status']) ? $orderCardData['status'] : '';
$isPaymentDone = $statusValue === 'paid' || $debt <= 0;
$badgeLabel = $isPaymentDone ? 'Đã xong' : 'Còn nợ';
$badgeClass = $isPaymentDone ? 'bg-brand-100 text-brand-800' : 'bg-rose-100 text-rose-800';
?>

<a href="<?php echo htmlspecialchars($orderCardUrl); ?>" class="relative block rounded-card border border-slate-200 bg-white p-3 transition-colors hover:border-brand-200" <?php echo $orderCardExtraAttrs; ?>>
    <button type="button" class="absolute right-1 top-1 inline-flex h-[30px] w-[30px] items-center justify-center rounded-lg text-slate-500 transition-colors hover:bg-brand-50 hover:text-brand-600" data-order-preview-btn data-order-id="<?php echo (int) (isset($orderCardData['id']) ? $orderCardData['id'] : 0); ?>" data-order-code="<?php echo htmlspecialchars((string) $orderCode); ?>" data-order-date="<?php echo htmlspecialchars($timeText); ?>" data-order-customer="<?php echo htmlspecialchars($displayCustomerName); ?>">
        <?php echo ui_icon("eye", "h-5 w-5"); ?>
    </button>

    <div class="space-y-1.5 <?php echo $orderStatus === 'cancelled' ? 'opacity-60' : ''; ?>">
        <div class="flex items-center gap-2">
            <div class="min-w-0 truncate text-sm font-semibold text-slate-900"><?php echo htmlspecialchars($displayCustomerName); ?></div>
            <span class="inline-flex shrink-0 items-center rounded-md px-2 py-0.5 text-xs font-semibold <?php echo $badgeClass; ?>"><?php echo htmlspecialchars($badgeLabel); ?></span>
        </div>

        <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-[13px] text-slate-500">
            <span class="inline-flex items-center gap-1">
				<?php echo ui_icon("clipboard-document", "h-3.5 w-3.5 text-slate-400"); ?>
                <span><?php echo htmlspecialchars((string) $orderCode); ?></span>
            </span>
            <?php if ($timeText !== '') { ?>
                <span class="inline-flex items-center gap-1">
					<?php echo ui_icon("clock", "h-3.5 w-3.5 text-slate-400"); ?>
                    <span><?php echo htmlspecialchars($timeText); ?></span>
                </span>
            <?php } ?>
        </div>

        <div class="flex flex-wrap items-center gap-x-3 text-sm">
            <span class="text-slate-600">Tổng: <span class="font-semibold text-slate-900"><?php echo Money::format($total); ?></span></span>
            <?php if ($isPaymentDone) { ?>
                <span class="text-slate-600">Lãi: <span class="font-semibold <?php echo $profit >= 0 ? 'text-brand-700' : 'text-rose-700'; ?>"><?php echo Money::format($profit); ?></span></span>
            <?php } else { ?>
                <span class="text-slate-600">Nợ: <span class="font-semibold text-rose-700"><?php echo Money::format($debt); ?></span></span>
            <?php } ?>
        </div>
    </div>
</a>