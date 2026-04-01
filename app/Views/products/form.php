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
				<button type="button" class="inline-flex h-10 w-10 items-center justify-center text-slate-600 hover:text-slate-800" data-header-actions-toggle>
					<?php echo ui_icon("ellipsis-vertical", "h-4 w-4"); ?>
				</button>
				<div class="absolute right-0 z-30 mt-2 w-44 rounded-xl border border-slate-200 bg-white py-1 text-sm  overflow-hidden hidden" data-header-actions-dropdown>
					<a href="<?php echo $basePath; ?>/product/delete?id=<?php echo (int) $product['id']; ?>" class="flex items-center gap-2 px-3 py-1.5 text-rose-600 hover:bg-rose-50" data-product-delete>
						<?php echo ui_icon("x-mark", "h-4 w-4"); ?>
						<span>Xóa sản phẩm</span>
					</a>
				</div>
			</div>
		<?php } ?>
	</div>
</div>
<?php } ?>
<form method="post" enctype="multipart/form-data" action="<?php echo $basePath; ?>/product/<?php echo $product ? 'update' : 'store'; ?>" class="space-y-3">
	<input type="hidden" hidden name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
	<?php if ($product) { ?>
		<input type="hidden" name="id" value="<?php echo $product['id']; ?>" hidden>
	<?php } ?>
	<!-- Thông tin sản phẩm -->
	<section class="app-form-group space-y-4">
		<h2 class="app-form-group-title">Thông tin sản phẩm</h2>
		<div class="flex flex-col gap-1">
			<label for="product-name" class="app-label">Tên sản phẩm</label>
			<?php
			$productNameValue = $product ? $product['name'] : '';
			ui_input_text('name', $productNameValue, [
				'id' => 'product-name',
				'required' => 'required',
				'class' => 'px-3 py-2',
			]);
			?>
		</div>
		<div class="flex flex-col gap-1">
			<label for="product-code" class="app-label">Mã sản phẩm <span class="font-normal text-slate-400">(tùy chọn)</span></label>
			<?php
			$productCodeValue = $product ? $product['code'] : '';
			ui_input_text('code', $productCodeValue, [
				'id' => 'product-code',
				'placeholder' => 'Để trống để tự sinh từ tên',
				'class' => 'px-3 py-2',
			]);
			?>
		</div>
		<div class="grid grid-cols-2 gap-4">
			<div class="flex flex-col gap-1">
				<label for="base-unit-id" class="app-label">Đơn vị tồn kho</label>
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
						'class' => 'col-start-1 row-start-1 appearance-none px-3 py-2',
					]);
					?>
					<span class="pointer-events-none col-start-1 row-start-1 mr-3 flex items-center justify-end text-slate-400"><?php echo ui_icon('chevron-down', 'h-4 w-4'); ?></span>
				</div>
			</div>
			<div class="flex flex-col gap-1">
				<label for="category-id" class="app-label">Danh mục</label>
				<div class="grid">
					<?php
					$categoryOptions = ['' => 'Chưa phân loại'];
					if (!empty($categories)) {
						foreach ($categories as $category) {
							$categoryOptions[$category['id']] = $category['name'];
						}
					}
					$categorySelected = $product && isset($product['category_id']) ? (int) $product['category_id'] : '';
					ui_select('category_id', $categoryOptions, $categorySelected, [
						'id' => 'category-id',
						'class' => 'col-start-1 row-start-1 appearance-none px-3 py-2',
					]);
					?>
					<span class="pointer-events-none col-start-1 row-start-1 mr-3 flex items-center justify-end text-slate-400"><?php echo ui_icon('chevron-down', 'h-4 w-4'); ?></span>
				</div>
			</div>
		</div>
	</section>

	<!-- Giá sản phẩm -->
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
		$displayPriceSell = $number !== 0.0 ? number_format($number, 0, '', '.') : '0';
	}

	$displayPriceCost = '';
	if ($currentPriceCost !== '' && $currentPriceCost !== null) {
		$number = (float) $currentPriceCost;
		$displayPriceCost = $number !== 0.0 ? number_format($number, 0, '', '.') : '0';
	}

	$displayMinStep = '';
	if ($minStep > 0) {
		$displayMinStep = rtrim(rtrim(number_format($minStep, 4, ',', ''), '0'), ',');
		if ($displayMinStep === '') {
			$displayMinStep = '1';
		}
	}
	?>
	<section class="app-form-group space-y-4">
		<h2 class="app-form-group-title">Giá sản phẩm</h2>
		<div class="grid grid-cols-2 gap-4">
			<div class="flex flex-col gap-1">
				<label for="price-sell-single" class="app-label">Giá bán</label>
				<div class="relative">
					<?php
					ui_input_text('price_sell_single', $displayPriceSell, [
						'id' => 'price-sell-single',
						'inputmode' => 'numeric',
						'data-money-input' => '1',
						'class' => 'px-3 py-2 pr-8',
					]);
					?>
					<span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-sm text-slate-400">đ</span>
				</div>
			</div>
			<div class="flex flex-col gap-1">
				<label for="price-cost-single" class="app-label">Giá nhập</label>
				<div class="relative">
					<?php
					ui_input_text('price_cost_single', $displayPriceCost, [
						'id' => 'price-cost-single',
						'inputmode' => 'numeric',
						'data-money-input' => '1',
						'class' => 'px-3 py-2 pr-8',
					]);
					?>
					<span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-sm text-slate-400">đ</span>
				</div>
			</div>
		</div>
		<label class="flex items-center gap-2">
			<input type="hidden" name="allow_fraction" value="0">
			<input type="checkbox" id="allow-fraction" name="allow_fraction" value="1" <?php echo $allowFraction ? 'checked' : ''; ?> class="h-4 w-4 rounded border-slate-300 text-brand-600" data-product-allow-fraction-toggle>
			<span class="text-sm text-slate-700">Cho phép bán lẻ (số lượng thập phân)</span>
		</label>
		<div class="flex flex-col gap-1<?php echo $allowFraction ? '' : ' hidden'; ?>" data-product-min-step-wrap>
			<label for="min-step" class="app-label">Bước lẻ nhỏ nhất</label>
			<?php
			ui_input_text('min_step', $displayMinStep, [
				'id' => 'min-step',
				'placeholder' => 'Ví dụ: 0,1 hoặc 0,25',
				'class' => 'px-3 py-2 max-w-xs',
				'data-product-min-step-input' => '1',
			]);
			?>
		</div>
	</section>
	<!-- Tồn kho -->
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
		$minStockInputValue = $minStockText !== '' ? $minStockText : '0';
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
	<section class="app-form-group space-y-4">
		<h2 class="app-form-group-title">Tồn kho</h2>
		<div class="grid grid-cols-2 gap-4">
			<div class="flex flex-col gap-1">
				<label for="inventory-qty-base" class="app-label">Số lượng hiện tại</label>
				<div class="relative">
					<?php
					ui_input_text('inventory_qty_base', $inventoryInputValue, [
						'id' => 'inventory-qty-base',
						'class' => 'px-3 py-2 pr-16',
					]);
					?>
					<span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-sm text-slate-400 max-w-[50%] truncate" data-inventory-unit-label><?php echo htmlspecialchars($baseUnitName); ?></span>
				</div>
			</div>
			<div class="flex flex-col gap-1">
				<label for="min-stock-qty" class="app-label">Ngưỡng tồn thấp</label>
				<div class="relative">
					<?php
					ui_input_text('min_stock_qty', $minStockInputValue, [
						'id' => 'min-stock-qty',
						'class' => 'px-3 py-2 pr-16',
					]);
					?>
					<span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-sm text-slate-400 max-w-[50%] truncate" data-inventory-unit-label><?php echo htmlspecialchars($baseUnitName); ?></span>
				</div>
				<p class="text-xs text-slate-400">Để trống nếu không dùng cảnh báo.</p>
			</div>
		</div>
	</section>

	<?php if (!empty($product) && !empty($productLogs) && is_array($productLogs)) { ?>
		<section class="app-form-group space-y-3">
			<h2 class="app-form-group-title">Lịch sử giá &amp; tồn</h2>
			<p class="text-sm text-slate-500">Nhật ký các lần thay đổi giá bán, giá nhập và tồn kho.</p>
			<div class="space-y-2 max-h-64 overflow-y-auto">
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
							<span class="rounded-md bg-slate-100 px-1.5 py-0.5 text-xs text-slate-500"><?php echo htmlspecialchars($actionLabel); ?></span>
						<?php } ?>
					</div>
				<?php
					}
					if ($detailText !== '') {
				?>
					<div class="pl-3 text-sm text-slate-600">
						<?php echo $detailText; ?>
					</div>
				<?php
					}
				}
				?>
			</div>
		</section>
	<?php } ?>

	<script>
	document.addEventListener('DOMContentLoaded', function () {
		var toggle = document.querySelector('[data-product-allow-fraction-toggle]');
		var wrap = document.querySelector('[data-product-min-step-wrap]');
		var input = document.querySelector('[data-product-min-step-input]');
		if (!toggle || !wrap) return;

		function syncMinStepVisibility() {
			var enabled = !!toggle.checked;
			wrap.classList.toggle('hidden', !enabled);
			if (input) {
				input.disabled = !enabled;
			}
		}

		toggle.addEventListener('change', syncMinStepVisibility);
		syncMinStepVisibility();
	});
	</script>

	<div class="mt-3 flex items-center justify-end" data-floating-actions>
		<div class="flex items-center gap-2">
			<?php ui_button_primary('Lưu', ['type' => 'submit', 'name' => 'redirect', 'value' => 'stay', 'data-loading-button' => '1', 'data-floating-primary' => '1']); ?>
			<?php ui_button_secondary('Lưu & thoát', ['type' => 'submit', 'name' => 'redirect', 'value' => 'exit', 'data-loading-button' => '1']); ?>
		</div>
	</div>
</form>
