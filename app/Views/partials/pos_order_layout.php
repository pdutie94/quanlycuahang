<?php
$layoutMode = isset($layoutMode) ? $layoutMode : 'pos';
$isPos = $layoutMode === 'pos';
?>

<div class="space-y-4" <?php if ($isPos) { ?>data-pos-root<?php } else { ?>data-order-edit-root data-order-base-total="<?php echo isset($order['total_amount']) ? (float) $order['total_amount'] : 0; ?>"<?php } ?>>
    <form method="post" action="<?php echo $basePath; ?><?php echo $isPos ? '/pos/store' : '/order/update'; ?>" <?php if ($isPos) { ?>data-pos-form<?php } ?> class="space-y-4">
        <input type="hidden" hidden name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
        <?php if (!$isPos) { ?>
            <input type="hidden" hidden name="id" value="<?php echo $order['id']; ?>">
        <?php } ?>

        <div class="flex flex-col rounded-lg border border-slate-200 bg-white px-4 py-4 lg:px-5 lg:py-5">
            <div class="mb-1 flex items-center justify-between">
                <div class="flex items-center gap-2 text-sm font-medium text-slate-800">
                    <span>Sản phẩm</span>
                </div>
                <button type="button" class="inline-flex items-center rounded-md border border-brand-600 px-3 py-1 text-sm font-medium text-brand-700 hover:bg-brand-50" data-product-selector-open data-product-selector-mode="<?php echo $isPos ? 'pos' : 'order-edit-add'; ?>">
                    Thêm SP
                </button>
            </div>

			<div class="flex-1">
				<?php if ($isPos) { ?>
					<div data-pos-cart-list class="flex flex-col"></div>
                    <div class="mt-2 rounded-lg border border-dashed border-slate-300 bg-slate-50 px-3 py-3 text-center text-sm text-slate-500" data-pos-empty>
                        Đơn hàng chưa có mặt hàng nào.
                    </div>
                    <?php
                    $manualContext = 'pos';
                    $manualItems = [];
                    include __DIR__ . '/manual_free_items.php';
                    ?>
				<?php } else { ?>
					<div class="flex flex-col" data-order-edit-items-list>
                        <?php if (!empty($items)) { ?>
                            <?php
                            $editUnitsByProduct = [];
                            if (!empty($productUnits)) {
                                foreach ($productUnits as $u) {
                                    $pid = (int) $u['product_id'];
                                    if (!isset($editUnitsByProduct[$pid])) {
                                        $editUnitsByProduct[$pid] = [];
                                    }
                                    $editUnitsByProduct[$pid][] = $u;
                                }
                            }
                            ?>
						<?php foreach ($items as $item) { ?>
							<?php
							$productImage = '';
							if (!empty($item['product_image_path'])) {
								$productImage = $basePath . '/' . ltrim($item['product_image_path'], '/');
							}
							?>
							<div class="flex items-center justify-between gap-3 py-2 border-b border-slate-200 last:border-b-0" data-order-existing-item="1" data-order-item-id="<?php echo (int) $item['id']; ?>" data-product-id="<?php echo (int) $item['product_id']; ?>" data-product-unit-id="<?php echo (int) $item['product_unit_id']; ?>" data-base-qty="<?php echo isset($item['qty']) ? (float) $item['qty'] : 0; ?>" data-price="<?php echo isset($item['price_sell']) ? (float) $item['price_sell'] : 0; ?>" data-base-price="<?php echo isset($item['price_sell']) ? (float) $item['price_sell'] : 0; ?>">
								<div class="flex items-center gap-3">
									<div class="flex h-12 w-12 items-center justify-center overflow-hidden rounded-xl bg-slate-100 text-sm font-medium text-slate-400">
									<?php if ($productImage !== '') { ?>
										<img src="<?php echo htmlspecialchars($productImage); ?>" alt="<?php echo htmlspecialchars($item['product_name']); ?>" class="h-full w-full object-cover">
									<?php } else { ?>
										<?php echo ui_icon("archive-box", "size-6"); ?>
									<?php } ?>
									</div>
									<div>
									<div class="text-sm font-medium text-slate-900"><?php echo htmlspecialchars($item['product_name']); ?></div>
									<div class="mt-0.5 text-sm text-slate-500">
										<div class="mt-0.5">
												<span data-order-existing-qty class="hidden"><?php echo rtrim(rtrim(number_format($item['qty'], 2, ',', ''), '0'), ','); ?></span>
                                                <?php
                                                $priceText = isset($item['price_sell']) ? Money::format($item['price_sell']) : '';
                                                if ($priceText !== '') {
                                                    ?>
                                                    <button type="button" data-order-price-edit="1" class="inline-flex items-center gap-1 rounded-lg hover:bg-brand-50">
                                                        <span data-order-price-display class="font-medium text-slate-900"><?php echo $priceText; ?></span>
                                                        <span>/ <?php echo htmlspecialchars($item['unit_name']); ?></span>
                                                        <span class="inline-flex h-4 w-4 items-center justify-center text-slate-400 group-hover:text-brand-600">
															<?php echo ui_icon("pencil-square", "h-3 w-3"); ?>
                                                        </span>
                                                    </button>
                                                    <?php
                                                } else {
                                                    echo htmlspecialchars($item['unit_name']);
                                                }
                                                ?>
											</div>
										<div class="mt-1">
												<div class="inline-flex items-stretch overflow-hidden rounded-lg border border-slate-300 bg-slate-50">
													<button type="button" data-order-existing-decrease="1" class="inline-flex h-6 w-6 items-center justify-center bg-slate-50 text-sm text-slate-700 hover:bg-slate-100">
														<?php echo ui_icon("minus", "size-3"); ?>
													</button>
                                                    <?php
                                                    $existingMin = 1;
                                                    $existingStep = 1;
                                                    if (!empty($productUnits)) {
                                                        foreach ($productUnits as $u) {
                                                            if ((int) $u['id'] === (int) $item['product_unit_id']) {
                                                                $allowFractionExisting = isset($u['allow_fraction']) ? (int) $u['allow_fraction'] : 0;
                                                                $minStepExisting = isset($u['min_step']) ? (float) $u['min_step'] : 1;
                                                                if ($minStepExisting <= 0) {
                                                                    $minStepExisting = 1;
                                                                }
                                                                if ($allowFractionExisting) {
                                                                    $existingMin = $minStepExisting;
                                                                    $existingStep = $minStepExisting;
                                                                }
                                                                break;
                                                            }
                                                        }
                                                    }
                                                    ?>
													<input type="number" min="<?php echo $existingMin; ?>" step="<?php echo $existingStep; ?>" value="<?php echo isset($item['qty']) ? (float) $item['qty'] : 0; ?>" data-order-existing-qty-input="1" class="h-6 w-10 border-0 bg-slate-50 px-1 text-sm font-medium text-center outline-none">
													<button type="button" data-order-existing-increase="1" class="inline-flex h-6 w-6 items-center justify-center bg-slate-50 text-sm text-slate-700 hover:bg-slate-100">
														<?php echo ui_icon("plus", "size-3"); ?>
													</button>
												</div>
											</div>
										</div>
									</div>
								</div>
								<div class="flex flex-col items-end justify-between gap-2 flex-1">
									<div class="flex w-full justify-end">
										<button type="button" data-order-existing-remove="1" class="inline-flex h-5 w-5 items-center justify-center text-rose-500 hover:text-rose-600">
											<?php echo ui_icon("x-mark", "size-4"); ?>
										</button>
									</div>
									<div class="text-sm font-medium text-brand-600">
										<span data-order-existing-amount><?php echo Money::format($item['amount']); ?></span>
									</div>
								</div>
							</div>
						<?php } ?>
                        <?php } ?>
                        <div class="mt-2 rounded-lg border border-dashed border-slate-300 bg-slate-50 px-3 py-3 text-center text-sm text-slate-500<?php echo !empty($items) ? ' hidden' : ''; ?>" data-order-edit-empty>
                            Đơn hàng chưa có mặt hàng nào.
                        </div>
                    </div>
                <?php } ?>
            </div>

            <?php if (!$isPos) { ?>
            <?php
                $manualContext = 'order_edit';
                $manualItems = isset($manualItems) && is_array($manualItems) ? $manualItems : [];
                include __DIR__ . '/manual_free_items.php';
                ?>
            <?php } ?>

            <div class="mt-3">
                <div class="space-y-1.5">
                <div class="flex items-center justify-between text-sm text-slate-600">
                    <span>Tạm tính</span>
                    <?php if ($isPos) { ?>
                        <span data-pos-subtotal>0 đ</span>
                    <?php } else { ?>
                        <span class="font-medium text-slate-800" data-order-edit-subtotal><?php echo Money::format(isset($order['total_amount']) && isset($order['discount_amount']) ? ((float) $order['total_amount'] + (float) $order['discount_amount']) : (isset($order['total_amount']) ? (float) $order['total_amount'] : 0)); ?> đ</span>
                    <?php } ?>
                </div>
                <div class="flex items-center justify-between text-sm text-slate-600" data-order-discount-row>
                    <span>Giảm giá</span>
                    <button type="button" class="inline-flex items-center gap-1 rounded-lg border border-transparent text-sm font-medium text-rose-600" data-order-discount-open>
                        <?php if ($isPos) { ?>
                            <span data-pos-discount-amount>-0 đ</span>
                        <?php } else { ?>
                            <span data-order-edit-discount-amount>-<?php echo Money::format(isset($order['discount_amount']) ? (float) $order['discount_amount'] : 0); ?> đ</span>
                        <?php } ?>
                        <span class="inline-flex h-4 w-4 items-center justify-center text-rose-500">
                            <?php echo ui_icon("pencil-square", "h-3 w-3"); ?>
                        </span>
                    </button>
                </div>
                <div class="flex items-center justify-between text-sm text-slate-600" data-order-surcharge-row>
                    <span>Phụ thu</span>
                    <button type="button" class="inline-flex items-center gap-1 rounded-lg border border-transparent text-sm font-medium text-amber-600" data-order-surcharge-open>
                        <?php if ($isPos) { ?>
                            <span data-pos-surcharge-amount>+0 đ</span>
                        <?php } else { ?>
                            <span data-order-edit-surcharge-amount>+<?php echo Money::format(isset($order['surcharge_amount']) ? (float) $order['surcharge_amount'] : 0); ?> đ</span>
                        <?php } ?>
                        <span class="inline-flex h-4 w-4 items-center justify-center text-amber-500">
                            <?php echo ui_icon("plus-minus", "h-3 w-3"); ?>
                        </span>
                    </button>
                </div>
                <div class="flex items-center justify-between text-sm">
                    <span class="font-medium text-slate-800">Tổng cộng</span>
                    <div class="flex items-center gap-1 text-right">
                        <?php if ($isPos) { ?>
                            <div class="text-lg font-medium text-brand-600" data-pos-total>0 đ</div>
                        <?php } else { ?>
                            <div class="text-lg font-medium text-brand-600" data-order-edit-total><?php echo Money::format(isset($order['total_amount']) ? (float) $order['total_amount'] : 0); ?></div>
                        <?php } ?>
                        <button type="button" class="inline-flex h-5 w-5 items-center justify-center rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-600" title="Làm tròn tổng tiền" data-order-total-round>
                            <?php echo ui_icon("arrow-path", "h-3.5 w-3.5"); ?>
                        </button>
                    </div>
                </div>
                </div>
            </div>
        </div>

        <?php /* manual free item modal shared for POS and order edit */ ?>
        <div class="app-modal-overlay" hidden data-pos-manual-edit-modal>
            <div class="app-modal-sheet-sm">
                <div class="app-modal-header">
                    <h2 class="app-modal-title">Chỉnh sửa sản phẩm tự do</h2>
                    <button type="button" class="app-modal-close" data-pos-manual-edit-cancel>
                        <?php echo ui_icon("x-mark", "h-4 w-4"); ?>
                    </button>
                </div>
                <div class="app-modal-body space-y-4">
                    <div class="relative">
						<label class="absolute left-3 top-0 z-10 -translate-y-1/2 bg-white px-1 leading-none text-sm text-slate-700">Tên hàng</label>
						<input type="text" class="form-field block w-full rounded-xl border border-slate-300 bg-white px-3.5 pt-3 pb-2.5 text-sm outline-none transition focus:border-brand-500" autocomplete="off" data-pos-manual-edit-name>
                    </div>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="relative">
							<label class="absolute left-3 top-0 z-10 -translate-y-1/2 bg-white px-1 leading-none text-sm text-slate-700">Đơn vị</label>
							<input type="text" class="form-field block w-full rounded-xl border border-slate-300 bg-white px-3.5 pt-3 pb-2.5 text-sm outline-none transition focus:border-brand-500" autocomplete="off" data-pos-manual-edit-unit>
                        </div>
                        <div class="relative">
							<label class="absolute left-3 top-0 z-10 -translate-y-1/2 bg-white px-1 leading-none text-sm text-slate-700">Số lượng</label>
							<input type="number" min="0" step="0.01" class="form-field block w-full rounded-xl border border-slate-300 bg-white px-3.5 pt-3 pb-2.5 text-sm text-right outline-none transition focus:border-brand-500" data-pos-manual-edit-qty>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="space-y-4">
                            <div class="relative">
								<label class="absolute left-3 top-0 z-10 -translate-y-1/2 bg-white px-1 leading-none text-sm text-slate-700">Giá nhập</label>
                                <div class="relative">
									<input type="text" class="form-field block w-full rounded-xl border border-slate-300 bg-white px-3.5 pt-3 pb-2.5 pr-7 text-sm text-right outline-none transition focus:border-brand-500" inputmode="numeric" autocomplete="off" data-money-input data-pos-manual-edit-price-buy>
                                    <span class="pointer-events-none absolute inset-y-0 right-2 flex items-center text-sm text-slate-500">đ</span>
                                </div>
                            </div>
                            <div class="relative">
								<label class="absolute left-3 top-0 z-10 -translate-y-1/2 bg-white px-1 leading-none text-sm text-slate-700">Tổng tiền nhập</label>
                                <div class="relative">
									<input type="text" class="form-field block w-full rounded-xl border border-slate-300 bg-white px-3.5 pt-3 pb-2.5 pr-7 text-sm text-right outline-none transition focus:border-brand-500" inputmode="numeric" autocomplete="off" data-money-input data-pos-manual-edit-amount-buy>
                                    <span class="pointer-events-none absolute inset-y-0 right-2 flex items-center text-sm text-slate-500">đ</span>
                                </div>
                            </div>
                        </div>
                        <div class="relative">
							<label class="absolute left-3 top-0 z-10 -translate-y-1/2 bg-white px-1 leading-none text-sm text-slate-700">Giá bán</label>
                            <div class="relative">
								<input type="text" class="form-field block w-full rounded-xl border border-slate-300 bg-white px-3.5 pt-3 pb-2.5 pr-7 text-sm text-right outline-none transition focus:border-brand-500" inputmode="numeric" autocomplete="off" data-money-input data-pos-manual-edit-price-sell>
                                <span class="pointer-events-none absolute inset-y-0 right-2 flex items-center text-sm text-slate-500">đ</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="app-modal-footer">
                    <button type="button" class="app-btn-secondary" data-pos-manual-edit-cancel>Hủy</button>
                    <button type="button" class="app-btn-primary" data-pos-manual-edit-save>Lưu</button>
                </div>
            </div>
        </div>

        <?php include __DIR__ . '/order_customer_section.php'; ?>
    </form>

    <?php if (!$isPos) { ?>
        <form method="post" action="<?php echo $basePath; ?>/order/addStore" data-order-edit-add-form class="hidden">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
        </form>
    <?php } ?>
</div>

<div class="app-modal-overlay" data-pos-price-modal>
        <div class="app-modal-sheet-sm">
            <div class="app-modal-header">
                <div class="app-modal-title">
                    Chỉnh đơn giá
                </div>
                <button type="button" class="app-modal-close" data-pos-price-cancel>
                    <?php echo ui_icon("x-mark", "h-4 w-4"); ?>
                </button>
            </div>
            <div class="app-modal-body">
                <div class="mb-3 text-sm text-slate-600" data-pos-price-modal-product></div>
                <div class="mb-4 space-y-1 rounded-lg bg-slate-50 p-2 text-sm text-slate-600">
                    <div class="flex items-center justify-between">
                        <span>Giá gốc</span>
                        <span class="font-medium text-slate-900" data-pos-price-modal-base>0 đ</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span>Đơn giá hiện tại</span>
                        <span class="font-medium text-slate-900" data-pos-price-modal-current>0 đ</span>
                    </div>
                </div>
                <div class="relative">
					<label class="absolute left-3 top-0 z-10 -translate-y-1/2 bg-white px-1 leading-none text-sm text-slate-700">Đơn giá mới</label>
                    <div class="relative">
                        <input type="text" data-money-input="1" data-pos-price-modal-input class="form-field w-full rounded-xl border border-slate-300 bg-white px-3.5 pt-3 pb-2.5 pr-8 text-right text-sm font-medium text-slate-900 outline-none transition focus:border-brand-500" value="0">
                        <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-sm text-slate-500">đ</span>
                    </div>
                </div>
            </div>
            <div class="app-modal-footer">
                <button type="button" class="app-btn-secondary" data-pos-price-cancel>Hủy</button>
                <button type="button" class="app-btn-primary" data-pos-price-save>Áp dụng</button>
            </div>
        </div>
    </div>

<?php
$discountTitle = 'Giảm giá đơn hàng';
include __DIR__ . '/order_discount_modal.php';
$surchargeTitle = 'Phụ thu đơn hàng';
include __DIR__ . '/order_surcharge_modal.php';
include __DIR__ . '/customer_selector_modal.php';
?>

<?php if ($isPos) { ?>
<?php
$posProductsForJs = [];
foreach ($products as $product) {
    $posProductsForJs[] = [
        'id' => (int) $product['id'],
        'name' => $product['name'],
        'code' => $product['code'],
        'base_unit_name' => $product['base_unit_name'],
		'image_path' => isset($product['image_path']) ? $product['image_path'] : null,
		'image_url' => !empty($product['image_path']) ? $basePath . '/' . ltrim($product['image_path'], '/') : null,
    ];
}

$posUnitsForJs = [];
foreach ($productUnitsByProduct as $productId => $rows) {
    $list = [];
    foreach ($rows as $row) {
        $list[] = [
            'unit_id' => (int) $row['unit_id'],
            'unit_name' => $row['unit_name'],
            'factor' => (float) $row['factor'],
            'price_sell' => (float) $row['price_sell'],
            'allow_fraction' => isset($row['allow_fraction']) ? (int) $row['allow_fraction'] : 0,
            'min_step' => isset($row['min_step']) ? (float) $row['min_step'] : 1,
        ];
    }
    $posUnitsForJs[$productId] = $list;
}
?>
<script>
window.POS_PRODUCTS = <?php echo json_encode($posProductsForJs); ?>;
window.POS_PRODUCT_UNITS = <?php echo json_encode($posUnitsForJs); ?>;
</script>
<?php include __DIR__ . '/product_selector_modal.php'; ?>
<?php } else { ?>
<?php if (!empty($productUnits)) {
$orderEditUnitsForJs = [];
foreach ($productUnits as $row) {
    $orderEditUnitsForJs[] = [
        'id' => (int) $row['id'],
        'product_id' => (int) $row['product_id'],
        'product_name' => $row['product_name'],
        'product_code' => isset($row['product_code']) ? $row['product_code'] : '',
        'unit_name' => $row['unit_name'],
        'price' => isset($row['price_sell']) ? (float) $row['price_sell'] : 0,
		'price_text' => Money::format($row['price_sell']),
		'image_url' => !empty($row['product_image_path']) ? $basePath . '/' . ltrim($row['product_image_path'], '/') : null,
        'allow_fraction' => isset($row['allow_fraction']) ? (int) $row['allow_fraction'] : 0,
        'min_step' => isset($row['min_step']) ? (float) $row['min_step'] : 1,
    ];
}
?>
<script>
window.ORDER_EDIT_PRODUCT_UNITS = <?php echo json_encode($orderEditUnitsForJs); ?>;
</script>
<?php include __DIR__ . '/product_selector_modal.php'; ?>
<?php } ?>
<?php } ?>
