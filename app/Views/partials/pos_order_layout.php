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

        <div class="flex flex-col rounded-lg bg-white px-4 py-4 lg:px-5 lg:py-5 shadow-sm ring-1 ring-slate-100">
            <div class="mb-1 flex items-center justify-between">
                <div class="flex items-center gap-2 text-sm font-medium text-slate-800">
                    <span>Sản phẩm</span>
                </div>
                <button type="button" class="inline-flex items-center rounded-lg border border-emerald-600 px-3 py-1 text-sm font-medium text-emerald-700 hover:bg-emerald-50" data-product-selector-open data-product-selector-mode="<?php echo $isPos ? 'pos' : 'order-edit-add'; ?>">
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
										<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
					  <path stroke-linecap="round" stroke-linejoin="round" d="m20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" />
									</svg>
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
                                                    <button type="button" data-order-price-edit="1" class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 hover:bg-emerald-50">
                                                        <span data-order-price-display class="font-medium text-slate-900"><?php echo $priceText; ?></span>
                                                        <span>/ <?php echo htmlspecialchars($item['unit_name']); ?></span>
                                                        <span class="inline-flex h-4 w-4 items-center justify-center text-slate-400 group-hover:text-emerald-600">
                                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-3 w-3">
                                                                <path d="M13.586 3.586a2 2 0 0 1 2.828 2.828l-.793.793-2.828-2.828.793-.793ZM11.379 5.793 4 13.172V16h2.828l7.38-7.379-2.83-2.828Z" />
                                                            </svg>
                                                        </span>
                                                    </button>
                                                    <?php
                                                } else {
                                                    echo htmlspecialchars($item['unit_name']);
                                                }
                                                ?>
											</div>
										<div class="mt-1">
												<div class="inline-flex items-stretch overflow-hidden rounded-full border border-slate-300 bg-slate-50">
													<button type="button" data-order-existing-decrease="1" class="inline-flex h-6 w-6 items-center justify-center bg-slate-50 text-sm text-slate-700 hover:bg-slate-100">
														<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-3">
					  <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14" />
					</svg>
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
														<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-3">
					  <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
					</svg>
													</button>
												</div>
											</div>
										</div>
									</div>
								</div>
								<div class="flex flex-col items-end justify-between gap-2 flex-1">
									<div class="flex w-full justify-end">
										<button type="button" data-order-existing-remove="1" class="inline-flex h-5 w-5 items-center justify-center text-rose-500 hover:text-rose-600">
											<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
					  <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
					</svg>
										</button>
									</div>
									<div class="text-sm font-medium text-emerald-600">
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

            <div class="mt-2 border-t border-slate-100 pt-2 space-y-1.5">
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
                    <button type="button" class="inline-flex items-center gap-1 rounded-full border border-transparent text-sm font-medium text-rose-600" data-order-discount-open>
                        <?php if ($isPos) { ?>
                            <span data-pos-discount-amount>-0 đ</span>
                        <?php } else { ?>
                            <span data-order-edit-discount-amount>-<?php echo Money::format(isset($order['discount_amount']) ? (float) $order['discount_amount'] : 0); ?> đ</span>
                        <?php } ?>
                        <span class="inline-flex h-4 w-4 items-center justify-center text-rose-500">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-3 w-3">
                                <path d="M13.586 3.586a2 2 0 0 1 2.828 2.828l-.793.793-2.828-2.828.793-.793ZM11.379 5.793 4 13.172V16h2.828l7.38-7.379-2.83-2.828Z" />
                            </svg>
                        </span>
                    </button>
                </div>
                <div class="flex items-center justify-between text-sm text-slate-600" data-order-surcharge-row>
                    <span>Phụ thu</span>
                    <button type="button" class="inline-flex items-center gap-1 rounded-full border border-transparent text-sm font-medium text-amber-600" data-order-surcharge-open>
                        <?php if ($isPos) { ?>
                            <span data-pos-surcharge-amount>+0 đ</span>
                        <?php } else { ?>
                            <span data-order-edit-surcharge-amount>+<?php echo Money::format(isset($order['surcharge_amount']) ? (float) $order['surcharge_amount'] : 0); ?> đ</span>
                        <?php } ?>
                        <span class="inline-flex h-4 w-4 items-center justify-center text-amber-500">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-3 w-3">
                                <path d="M10.75 4.75a.75.75 0 1 0-1.5 0v10.5a.75.75 0 1 0 1.5 0V4.75Z" />
                                <path d="M4.75 10a.75.75 0 0 1 .75-.75h9a.75.75 0 0 1 0 1.5h-9A.75.75 0 0 1 4.75 10Z" />
                            </svg>
                        </span>
                    </button>
                </div>
                <div class="flex items-center justify-between text-sm">
                    <span class="font-medium text-slate-800">Tổng cộng</span>
                    <div class="flex items-center gap-1 text-right">
                        <?php if ($isPos) { ?>
                            <div class="text-lg font-medium text-emerald-600" data-pos-total>0 đ</div>
                        <?php } else { ?>
                            <div class="text-lg font-medium text-emerald-600" data-order-edit-total><?php echo Money::format(isset($order['total_amount']) ? (float) $order['total_amount'] : 0); ?></div>
                        <?php } ?>
                        <button type="button" class="inline-flex h-5 w-5 items-center justify-center rounded-full text-slate-400 hover:bg-slate-100 hover:text-slate-600" title="Làm tròn tổng tiền" data-order-total-round>
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-3.5 w-3.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <?php /* manual free item modal shared for POS and order edit */ ?>
        <div class="fixed inset-0 z-40 hidden items-center justify-center bg-black/40 p-4" hidden data-pos-manual-edit-modal>
            <div class="flex max-h-full w-full max-w-sm flex-col rounded-2xl bg-white shadow-lg">
                <div class="flex items-center justify-between border-b border-slate-200 px-4 py-2">
                    <h2 class="text-sm font-medium text-slate-800">Chỉnh sửa sản phẩm tự do</h2>
                    <button type="button" class="inline-flex h-7 w-7 items-center justify-center rounded-full text-slate-400 hover:bg-slate-100 hover:text-slate-600" data-pos-manual-edit-cancel>
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 0 1 1.414 0L10 8.586l4.293-4.293a1 1 0 1 1 1.414 1.414L11.414 10l4.293 4.293a1 1 0 0 1-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 0 1-1.414-1.414L8.586 10 4.293 5.707a1 1 0 0 1 0-1.414Z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </div>
                <div class="flex-1 space-y-3 overflow-y-auto px-4 py-3 text-sm">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-600">Tên hàng</label>
                        <input type="text" class="form-field block w-full rounded-md border border-slate-300 bg-slate-50 px-3 py-2 text-sm outline-none focus:border-emerald-500 focus:bg-white" autocomplete="off" data-pos-manual-edit-name>
                    </div>
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-600">Đơn vị</label>
                            <input type="text" class="form-field block w-full rounded-md border border-slate-300 bg-slate-50 px-3 py-2 text-sm outline-none focus:border-emerald-500 focus:bg-white" autocomplete="off" data-pos-manual-edit-unit>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-600">Số lượng</label>
                            <input type="number" min="0" step="0.01" class="form-field block w-full rounded-md border border-slate-300 bg-slate-50 px-3 py-2 text-sm text-right outline-none focus:border-emerald-500 focus:bg-white" data-pos-manual-edit-qty>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div class="space-y-2">
                            <div>
                                <label class="mb-1 block text-sm font-medium text-slate-600">Giá nhập</label>
                                <div class="relative">
                                    <input type="text" class="form-field block w-full rounded-md border border-slate-300 bg-slate-50 px-3 py-2 pr-7 text-sm text-right outline-none focus:border-emerald-500 focus:bg-white" inputmode="numeric" autocomplete="off" data-money-input data-pos-manual-edit-price-buy>
                                    <span class="pointer-events-none absolute inset-y-0 right-2 flex items-center text-sm text-slate-500">đ</span>
                                </div>
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-slate-600">Tổng tiền nhập</label>
                                <div class="relative">
                                    <input type="text" class="form-field block w-full rounded-md border border-slate-300 bg-slate-50 px-3 py-2 pr-7 text-sm text-right outline-none focus:border-emerald-500 focus:bg-white" inputmode="numeric" autocomplete="off" data-money-input data-pos-manual-edit-amount-buy>
                                    <span class="pointer-events-none absolute inset-y-0 right-2 flex items-center text-sm text-slate-500">đ</span>
                                </div>
                            </div>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-600">Giá bán</label>
                            <div class="relative">
                                <input type="text" class="form-field block w-full rounded-md border border-slate-300 bg-slate-50 px-3 py-2 pr-7 text-sm text-right outline-none focus:border-emerald-500 focus:bg-white" inputmode="numeric" autocomplete="off" data-money-input data-pos-manual-edit-price-sell>
                                <span class="pointer-events-none absolute inset-y-0 right-2 flex items-center text-sm text-slate-500">đ</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="flex items-center justify-end gap-2 border-t border-slate-200 px-4 py-3">
                    <button type="button" class="inline-flex items-center rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-100" data-pos-manual-edit-cancel>Hủy</button>
                    <button type="button" class="inline-flex items-center rounded-lg bg-emerald-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-emerald-700 active:bg-emerald-800" data-pos-manual-edit-save>Lưu</button>
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

<div class="fixed inset-0 z-40 hidden flex items-center justify-center bg-slate-900/40 p-4" data-pos-price-modal>
        <div class="w-full max-w-sm rounded-xl bg-white shadow-lg max-h-full flex flex-col">
            <div class="flex items-center justify-between border-b border-slate-200 px-4 py-2">
                <div class="text-sm font-medium text-slate-900">
                    Chỉnh đơn giá
                </div>
                <button type="button" class="inline-flex h-7 w-7 items-center justify-center rounded-full text-slate-400 hover:bg-slate-100 hover:text-slate-600" data-pos-price-cancel>
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke-width="1.5" stroke="currentColor" class="h-4 w-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <div class="flex-1 overflow-y-auto px-4 py-3">
                <div class="mb-3 text-sm text-slate-600" data-pos-price-modal-product></div>
                <div class="mb-3 space-y-1 rounded-lg bg-slate-50 p-2 text-sm text-slate-600">
                    <div class="flex items-center justify-between">
                        <span>Giá gốc</span>
                        <span class="font-medium text-slate-900" data-pos-price-modal-base>0 đ</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span>Đơn giá hiện tại</span>
                        <span class="font-medium text-slate-900" data-pos-price-modal-current>0 đ</span>
                    </div>
                </div>
                <div class="space-y-1">
                    <label class="block text-sm font-medium text-slate-700">Đơn giá mới</label>
                    <div class="relative">
                        <input type="text" data-money-input="1" data-pos-price-modal-input class="w-full rounded-lg border border-slate-300 bg-white px-3 py-1.5 pr-8 text-right text-sm font-medium text-slate-900 outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500" value="0">
                        <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-sm text-slate-500">đ</span>
                    </div>
                </div>
            </div>
            <div class="flex items-center justify-end gap-2 border-t border-slate-200 px-4 py-3">
                <button type="button" class="inline-flex items-center rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-100" data-pos-price-cancel>Hủy</button>
                <button type="button" class="inline-flex items-center rounded-lg bg-emerald-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-emerald-700 active:bg-emerald-800" data-pos-price-save>Áp dụng</button>
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
