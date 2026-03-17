<?php
$total = isset($order['total_amount']) ? (float) $order['total_amount'] : 0;
$paid = isset($order['paid_amount']) ? (float) $order['paid_amount'] : 0;
$debt = $total - $paid;
if ($debt < 0) {
    $debt = 0;
}
?>

<?php if (!isset($detailHeader)) { ?>
<div class="mb-4 flex items-center justify-between gap-3">
    <h1 class="text-lg font-medium tracking-tight">Trả hàng đơn <?php echo htmlspecialchars($order['order_code']); ?></h1>
    <div class="flex flex-wrap items-center gap-1.5">
        <a href="<?php echo $basePath; ?>/order/view?id=<?php echo (int) $order['id']; ?>" class="inline-flex items-center gap-1 rounded-full border border-slate-300 bg-white px-2.5 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-100">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
            </svg>
            <span>Quay lại đơn hàng</span>
        </a>
    </div>
</div>
<?php } ?>

<div class="space-y-4">
    <div class="rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-4 py-2 text-sm font-medium text-slate-800">
            Thông tin đơn hàng
        </div>
        <div class="px-4 py-3 text-sm text-slate-700">
            <div class="flex flex-wrap items-center gap-3">
                <div>
                    <span class="text-slate-500">Mã đơn: </span>
                    <span class="font-medium text-slate-900">#<?php echo htmlspecialchars($order['order_code']); ?></span>
                </div>
                <div>
                    <span class="text-slate-500">Tổng tiền: </span>
                    <span class="font-medium text-slate-900"><?php echo Money::format($total); ?></span>
                </div>
                <div>
                    <span class="text-slate-500">Đã thu: </span>
                    <span class="font-medium text-emerald-600"><?php echo Money::format($paid); ?></span>
                </div>
                <div>
                    <span class="text-slate-500">Còn nợ: </span>
                    <span class="font-medium <?php echo $debt > 0 ? 'text-red-600' : 'text-slate-700'; ?>"><?php echo Money::format($debt); ?></span>
                </div>
            </div>
        </div>
    </div>

    <form method="post" action="<?php echo $basePath; ?>/order/returnStore" class="space-y-4">
        <div hidden>
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="order_id" value="<?php echo (int) $order['id']; ?>">
        </div>

        <div class="rounded-lg border border-slate-200 bg-white shadow-sm">
                <div class="flex items-center justify-between border-b border-slate-100 px-4 py-2 text-sm">
                <div class="font-medium text-slate-800">Chọn sản phẩm và số lượng trả</div>
                <label class="inline-flex items-center gap-1.5 text-sm text-slate-700">
                    <input type="checkbox" name="return_all" value="1" class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                    <span>Trả toàn bộ số lượng</span>
                </label>
            </div>
            <?php if (empty($items)) { ?>
                <div class="px-4 py-3 text-sm text-slate-500">
                    Đơn hàng không có mặt hàng nào để trả.
                </div>
            <?php } else { ?>
                <div class="divide-y divide-slate-100">
                    <?php foreach ($items as $item) { ?>
                        <?php
                        $itemId = isset($item['id']) ? (int) $item['id'] : 0;
                        if ($itemId <= 0) {
                            continue;
                        }
                        $qty = isset($item['qty']) ? (float) $item['qty'] : 0;
                        if ($qty <= 0) {
                            continue;
                        }
                        $price = isset($item['price_sell']) ? (float) $item['price_sell'] : 0;
                        if ($price < 0) {
                            $price = 0;
                        }
                        $amount = $qty * $price;
                        $qtyText = rtrim(rtrim(number_format($qty, 2, ',', ''), '0'), ',');
                        ?>
                        <div class="flex flex-col gap-2 px-4 py-3 text-sm sm:flex-row sm:items-start sm:justify-between">
                            <div class="min-w-0">
                                <div class="font-medium text-slate-900 truncate">
                                    <?php echo htmlspecialchars($item['product_name']); ?>
                                </div>
                                <div class="mt-1 text-slate-600">
                                    Đã bán:
                                    <span class="font-medium text-slate-900"><?php echo $qtyText; ?></span>
                                    <span class="text-slate-500"><?php echo htmlspecialchars($item['unit_name']); ?></span>
                                </div>
                                <div class="mt-1 text-sm text-slate-500">
                                    Đơn giá: <span class="font-medium text-slate-700"><?php echo Money::format($price); ?></span>
                                    &middot;
                                    Thành tiền: <span class="font-medium text-slate-700"><?php echo Money::format($amount); ?></span>
                                </div>
                            </div>
                            <div class="flex flex-col items-end gap-1">
                                <label class="text-sm text-slate-600">Số lượng trả</label>
                                <input
                                    type="number"
                                    name="return_qty[<?php echo $itemId; ?>]"
                                    min="0"
                                    max="<?php echo $qty; ?>"
                                    step="0.01"
                                    placeholder="0"
                                    class="h-8 w-24 rounded-md border border-slate-300 bg-slate-50 px-2 text-right text-sm outline-none focus:border-emerald-500 focus:bg-white"
                                >
                            </div>
                        </div>
                    <?php } ?>
                </div>
            <?php } ?>
        </div>

        <div class="flex flex-wrap items-center justify-end gap-2">
            <a href="<?php echo $basePath; ?>/order/view?id=<?php echo (int) $order['id']; ?>" class="inline-flex items-center gap-1.5 rounded-md border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6A2.25 2.25 0 005.25 5.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75" />
                </svg>
                <span>Hủy</span>
            </a>
            <button type="submit" class="inline-flex items-center gap-1.5 rounded-md bg-rose-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-rose-700 active:bg-rose-800">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                </svg>
                <span>Ghi nhận trả hàng</span>
            </button>
        </div>
    </form>
</div>
