<?php if (!isset($detailHeader)) { ?>
<div class="mb-4 flex items-center justify-between gap-3">
	<h1 class="text-lg font-medium tracking-tight">
		<?php echo $product ? 'Sửa sản phẩm' : 'Thêm sản phẩm'; ?>
	</h1>
	<div class="flex flex-wrap items-center gap-1.5" data-header-actions-root>
		<a href="<?php echo $basePath; ?>/product" class="inline-flex items-center gap-1 rounded-lg border border-slate-300 px-2.5 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-100">
			<?php echo ui_icon("chevron-left", "h-4 w-4"); ?>
			<span>Quay lại</span>
		</a>
		<?php if ($product) { ?>
			<div class="relative" data-header-actions-menu>
				<button type="button" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-300 bg-white text-slate-600 hover:bg-slate-100" data-header-actions-toggle>
					<?php echo ui_icon("ellipsis-vertical", "h-4 w-4"); ?>
				</button>
				<div class="absolute right-0 z-30 mt-2 w-44 rounded-xl border border-slate-200 bg-white py-1 text-sm  overflow-hidden hidden" data-header-actions-dropdown>
					<a href="<?php echo $basePath; ?>/product/delete?id=<?php echo (int) $product['id']; ?>" class="flex items-center gap-2 px-3 py-1.5 text-red-600 hover:bg-rose-50" data-product-delete>
						<?php echo ui_icon("x-mark", "h-4 w-4"); ?>
						<span>Xóa sản phẩm</span>
					</a>
				</div>
			</div>
		<?php } ?>
	</div>
</div>
<?php } ?>
<form method="post" enctype="multipart/form-data" action="<?php echo $basePath; ?>/product/<?php echo $product ? 'update' : 'store'; ?>" class="space-y-5 rounded-lg bg-white px-4 py-4 lg:px-5 lg:py-5  border border-slate-200">
	<input type="hidden" hidden name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
	<?php if ($product) { ?>
		<input type="hidden" name="id" value="<?php echo $product['id']; ?>" hidden>
	<?php } ?>
	<?php $hasImage = $product && !empty($product['image_path']); ?>
	<div class="flex flex-col gap-4">
		<div class="space-y-1">
			<div class="flex items-start gap-2">
            <button type="button" class="relative h-24 w-24 overflow-hidden rounded-lg border border-dashed border-slate-300 bg-slate-50 text-slate-400 text-sm flex items-center justify-center hover:border-brand-400 hover:text-brand-500" data-image-placeholder>
                <img src="<?php echo $hasImage ? $basePath . '/' . htmlspecialchars($product['image_path']) : ''; ?>" alt="" data-image-preview class="<?php echo $hasImage ? 'h-full w-full object-cover' : 'hidden h-full w-full object-cover'; ?>">
                <span data-image-placeholder-text class="px-1 <?php echo $hasImage ? 'hidden' : ''; ?>">Chọn ảnh sản phẩm</span>
            </button>
            <div class="flex flex-col items-center gap-2 <?php echo $hasImage ? '' : 'hidden'; ?>" data-image-actions>
                <button type="button" class="inline-flex h-6 w-6 items-center justify-center rounded-lg border border-red-200 bg-red-50 text-red-600 hover:bg-red-100" data-image-delete>
                    <?php echo ui_icon("x-mark", "size-4"); ?>


                </button>
            </div>
			<input type="file" name="image" accept="image/*" class="hidden" data-image-input>
			<input type="hidden" name="image_remove" value="0" data-image-remove>
		</div>
		</div>
		<div class="flex flex-col gap-4">
			<div class="relative">
				<label for="product-code" class="absolute left-3 top-0 z-10 -translate-y-1/2 bg-white px-1 leading-none text-sm text-slate-700">Mã sản phẩm (tùy chọn)</label>
				<?php
				$productCodeValue = $product ? $product['code'] : '';
				ui_input_text('code', $productCodeValue, [
					'id' => 'product-code',
					'placeholder' => 'Bỏ trống để tự sinh theo tên (vd: san pham 1 -> sp1)',
					'class' => 'pt-3 pb-2.5',
				]);
				?>
			</div>
			<div class="relative">
				<label for="product-name" class="absolute left-3 top-0 z-10 -translate-y-1/2 bg-white px-1 leading-none text-sm text-slate-700">Tên sản phẩm</label>
				<?php
				$productNameValue = $product ? $product['name'] : '';
				ui_input_text('name', $productNameValue, [
					'id' => 'product-name',
					'required' => 'required',
					'class' => 'pt-3 pb-2.5',
				]);
				?>
			</div>
			<div class="relative">
				<label for="base-unit-id" class="absolute left-3 top-0 z-10 -translate-y-1/2 bg-white px-1 leading-none text-sm text-slate-700">Đơn vị tồn kho</label>
				<div class="grid">
					<?php
					$baseUnitOptions = ['' => 'Chọn đơn vị'];
					foreach ($units as $unit) {
						$baseUnitOptions[$unit['id']] = $unit['name'];
					}
					$baseUnitSelected = $product && isset($product['base_unit_id']) ? $product['base_unit_id'] : '';
					ui_select('base_unit_id', $baseUnitOptions, $baseUnitSelected, [
						'id' => 'base-unit-id',
						'required' => 'required',
						'class' => 'col-start-1 row-start-1 appearance-none pt-3 pr-9',
					]);
					?>
					<span class="pointer-events-none col-start-1 row-start-1 mr-3 flex items-center justify-end text-slate-400"><?php echo ui_icon('chevron-down', 'h-4 w-4'); ?></span>
				</div>
			</div>
			<div class="relative">
				<label for="category-id" class="absolute left-3 top-0 z-10 -translate-y-1/2 bg-white px-1 leading-none text-sm text-slate-700">Danh mục</label>
				<div class="grid">
					<?php
					$categoryOptions = [];
					if (!empty($categories)) {
						foreach ($categories as $category) {
							$categoryOptions[$category['id']] = $category['name'];
						}
					}
					$categorySelected = $product && isset($product['category_id']) ? (int) $product['category_id'] : '';
					ui_select('category_id', $categoryOptions, $categorySelected, [
						'id' => 'category-id',
						'class' => 'col-start-1 row-start-1 appearance-none pt-3 pr-9',
					]);
					?>
					<span class="pointer-events-none col-start-1 row-start-1 mr-3 flex items-center justify-end text-slate-400"><?php echo ui_icon('chevron-down', 'h-4 w-4'); ?></span>
				</div>
			</div>
		</div>
	</div>

	<div class="pt-3 border-t border-slate-100">
		<h2 class="text-sm font-medium text-slate-800">Giá sản phẩm</h2>
		<p class="mt-1 text-sm text-slate-500">Thiết lập giá bán và giá nhập cho một đơn vị duy nhất.</p>
	</div>
	<?php
	$currentPriceSell = '';
	$currentPriceCost = '';
    $allowFraction = 0;
    $minStep = 1.0;
	if (!empty($productUnits) && is_array($productUnits)) {
		$firstUnit = reset($productUnits);
		$currentPriceSell = isset($firstUnit['price_sell']) ? $firstUnit['price_sell'] : '';
		$currentPriceCost = isset($firstUnit['price_cost']) ? $firstUnit['price_cost'] : '';
        if (isset($firstUnit['allow_fraction'])) {
            $allowFraction = (int) $firstUnit['allow_fraction'];
        }
        if (isset($firstUnit['min_step']) && $firstUnit['min_step'] !== null) {
            $minStep = (float) $firstUnit['min_step'];
            if ($minStep <= 0) {
                $minStep = 1.0;
            }
        }
	}

	$displayPriceSell = '';
	if ($currentPriceSell !== '' && $currentPriceSell !== null) {
		$number = (float) $currentPriceSell;
		if ($number !== 0.0) {
			$displayPriceSell = number_format($number, 0, '', '.');
		} else {
			$displayPriceSell = '0';
		}
	}

	$displayPriceCost = '';
	if ($currentPriceCost !== '' && $currentPriceCost !== null) {
		$number = (float) $currentPriceCost;
		if ($number !== 0.0) {
			$displayPriceCost = number_format($number, 0, '', '.');
		} else {
			$displayPriceCost = '0';
		}
	}
    $displayMinStep = '';
    if ($minStep > 0) {
        $displayMinStep = rtrim(rtrim(number_format($minStep, 4, ',', ''), '0'), ',');
        if ($displayMinStep === '') {
            $displayMinStep = '1';
        }
    }
	?>
	<div class="mt-3 flex flex-col gap-4">
		<div class="relative">
			<label for="price-sell-single" class="absolute left-3 top-0 z-10 -translate-y-1/2 bg-white px-1 leading-none text-sm text-slate-700">Giá bán</label>
			<div class="relative">
				<?php
				ui_input_text('price_sell_single', $displayPriceSell, [
					'id' => 'price-sell-single',
					'inputmode' => 'numeric',
					'data-money-input' => '1',
					'class' => 'pr-10 pt-3 pb-2.5',
				]);
				?>
				<span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-sm text-slate-500">đ</span>
			</div>
		</div>
		<div class="relative">
			<label for="price-cost-single" class="absolute left-3 top-0 z-10 -translate-y-1/2 bg-white px-1 leading-none text-sm text-slate-700">Giá nhập</label>
			<div class="relative">
				<?php
				ui_input_text('price_cost_single', $displayPriceCost, [
					'id' => 'price-cost-single',
					'inputmode' => 'numeric',
					'data-money-input' => '1',
					'class' => 'pr-10 pt-3 pb-2.5',
				]);
				?>
				<span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-sm text-slate-500">đ</span>
			</div>
		</div>
        <div class="space-y-1">
            <div class="flex items-center gap-2">
                <input type="hidden" name="allow_fraction" value="0">
                <input type="checkbox" name="allow_fraction" value="1" <?php echo $allowFraction ? 'checked' : ''; ?> class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                <span class="text-sm text-slate-700">Cho phép bán lẻ (số lượng thập phân)</span>
            </div>
        </div>
		<div class="relative max-w-xs">
			<label for="min-step" class="absolute left-3 top-0 z-10 -translate-y-1/2 bg-white px-1 leading-none text-sm text-slate-700">Bước lẻ nhỏ nhất</label>
			<?php
			ui_input_text('min_step', $displayMinStep, [
				'id' => 'min-step',
				'placeholder' => 'Ví dụ: 0,1 hoặc 0,25',
				'class' => 'pt-3 pb-2.5',
			]);
			?>
		</div>
	</div>

	<?php
	$inventoryQtyBaseValue = 0.0;
	if (isset($inventoryQtyBase)) {
	$inventoryQtyBaseValue = (float) $inventoryQtyBase;
	}
	$inventoryQtyCurrentText = rtrim(rtrim(number_format($inventoryQtyBaseValue, 2, ',', ''), '0'), ',');
	if ($inventoryQtyCurrentText === '') {
		$inventoryQtyCurrentText = '0';
	}
	$inventoryInputValue = $product ? $inventoryQtyCurrentText : '';

    $minStockQtyValue = null;
    if ($product && isset($product['min_stock_qty']) && $product['min_stock_qty'] !== null) {
        $minStockQtyValue = (float) $product['min_stock_qty'];
        if ($minStockQtyValue < 0) {
            $minStockQtyValue = 0;
        }
    }
    $minStockInputValue = '';
    if ($minStockQtyValue !== null) {
        $minStockText = rtrim(rtrim(number_format($minStockQtyValue, 2, ',', ''), '0'), ',');
        if ($minStockText === '') {
            $minStockText = '0';
        }
        $minStockInputValue = $minStockText;
    }
	$baseUnitName = '';
	if (!empty($units) && $product && isset($product['base_unit_id'])) {
		foreach ($units as $unit) {
			if ((int) $unit['id'] === (int) $product['base_unit_id']) {
				$baseUnitName = $unit['name'];
				break;
			}
		}
	}
	?>
	<div class="mt-4 pt-3 border-t border-slate-100">
		<h2 class="text-sm font-medium text-slate-800">Tồn kho</h2>
		<p class="mt-1 text-sm text-slate-500">Thiết lập tồn kho ban đầu hoặc cập nhật số lượng hiện tại.</p>
		<div class="mt-3 flex flex-col gap-4">
			<div class="relative">
				<label for="inventory-qty-base" class="absolute left-3 top-0 z-10 -translate-y-1/2 bg-white px-1 leading-none text-sm text-slate-700">Số lượng</label>
				<div class="relative">
					<?php
					ui_input_text('inventory_qty_base', $inventoryInputValue, [
						'id' => 'inventory-qty-base',
						'class' => 'pr-20 pt-3 pb-2.5',
					]);
					?>
					<span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-sm text-slate-400 max-w-[60%] truncate" data-inventory-unit-label><?php echo htmlspecialchars($baseUnitName); ?></span>
				</div>
			</div>
			<div class="relative">
				<label for="min-stock-qty" class="absolute left-3 top-0 z-10 -translate-y-1/2 bg-white px-1 leading-none text-sm text-slate-700">Ngưỡng tồn thấp</label>
				<div class="relative">
                    <?php
                    ui_input_text('min_stock_qty', $minStockInputValue, [
						'id' => 'min-stock-qty',
						'class' => 'pr-20 pt-3 pb-2.5',
                    ]);
                    ?>
					<span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-sm text-slate-400 max-w-[60%] truncate" data-inventory-unit-label><?php echo htmlspecialchars($baseUnitName); ?></span>
                </div>
                <p class="text-sm text-slate-400">Khi tồn kho &gt; 0 và nhỏ hơn hoặc bằng ngưỡng này, hệ thống sẽ báo “Tồn thấp”. Để trống nếu không dùng cảnh báo.</p>
            </div>
		</div>
	</div>

	<?php if (!empty($product) && !empty($productLogs) && is_array($productLogs)) { ?>
		<div class="mt-4 pt-3 border-t border-slate-100">
			<h2 class="text-sm font-medium text-slate-800">Lịch sử giá & tồn</h2>
			<p class="mt-1 text-sm text-slate-500">Nhật ký các lần thay đổi giá bán, giá nhập và tồn kho.</p>
			<div class="mt-3 space-y-2 max-h-64 overflow-y-auto pr-1">
				<?php
				$lastGroupKey = null;
				foreach ($productLogs as $log) {
					$createdAtRaw = isset($log['created_at']) ? $log['created_at'] : '';
					$timeKey = '';
					$timeLabel = '';
					if ($createdAtRaw !== '') {
						$dt = date_create($createdAtRaw);
						if ($dt instanceof DateTime) {
							$timeKey = $dt->format('Y-m-d H:i:s');
							$timeLabel = $dt->format('H:i d/m/Y');
						} else {
							$timeKey = $createdAtRaw;
							$timeLabel = $createdAtRaw;
						}
					}
					$detailText = isset($log['detail']) ? $log['detail'] : '';
					$detailText = htmlspecialchars($detailText);
					$actionKey = isset($log['action']) ? (string) $log['action'] : '';
					$actionLabel = '';
					if ($actionKey !== '') {
						if ($actionKey === 'init_price') {
							$actionLabel = 'Thiết lập giá';
						} elseif ($actionKey === 'update_price') {
							$actionLabel = 'Cập nhật giá';
						} elseif ($actionKey === 'init_inventory') {
							$actionLabel = 'Thiết lập tồn kho';
						} elseif ($actionKey === 'update_inventory') {
							$actionLabel = 'Cập nhật tồn kho';
						} elseif ($actionKey === 'adjust_inventory') {
							$actionLabel = 'Điều chỉnh tồn kho';
						} else {
							$actionLabel = strtoupper(str_replace('_', ' ', $actionKey));
						}
					}

					$groupKey = $timeKey . '|' . $actionKey;

					if ($groupKey !== '' && $groupKey !== $lastGroupKey) {
						$lastGroupKey = $groupKey;
				?>
						<div class="flex items-center gap-2 text-sm text-slate-600 mt-2 first:mt-0">
							<?php if ($timeLabel !== '') { ?>
								<span class="text-sm font-medium text-slate-700"><?php echo htmlspecialchars($timeLabel); ?></span>
							<?php } ?>
							<?php if ($actionLabel !== '') { ?>
								<span class="rounded-lg bg-slate-50 px-1.5 py-0.5 text-sm uppercase  text-slate-500"><?php echo htmlspecialchars($actionLabel); ?></span>
							<?php } ?>
						</div>
				<?php
					}
					if ($detailText !== '') {
				?>
						<div class="pl-4 text-sm text-slate-600">
							<?php echo $detailText; ?>
						</div>
				<?php
					}
				}
				?>
			</div>
		</div>
	<?php } ?>

	<div class="mt-3 flex items-center justify-end" data-floating-actions>
		<div class="flex items-center gap-2">
			<?php ui_button_primary('Lưu', ['type' => 'submit', 'name' => 'redirect', 'value' => 'stay', 'data-loading-button' => '1', 'data-floating-primary' => '1']); ?>
			<?php ui_button_secondary('Lưu & thoát', ['type' => 'submit', 'name' => 'redirect', 'value' => 'exit', 'data-loading-button' => '1']); ?>
		</div>
	</div>
</form>
