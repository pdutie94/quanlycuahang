<?php
$isEdit = isset($purchase) && is_array($purchase);
?>
<?php if (!isset($detailHeader)) { ?>
<div class="mb-4 flex items-center justify-between gap-3">
	<h1 class="text-lg font-medium tracking-tight">
		<?php echo $isEdit ? 'Chỉnh sửa phiếu nhập' : 'Tạo phiếu nhập hàng'; ?>
	</h1>
	<a href="<?php echo $isEdit ? $basePath . '/purchase/view?id=' . (int) $purchase['id'] : $basePath . '/purchase'; ?>" class="inline-flex items-center gap-1 rounded-full border border-slate-300 px-2.5 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-100">
		<?php echo ui_icon("chevron-left", "h-4 w-4"); ?>
		<span><?php echo $isEdit ? 'Chi tiết' : 'Danh sách'; ?></span>
	</a>
</div>
<?php } ?>

<div class="space-y-4">
	<div class="rounded-lg border border-slate-200 bg-white px-4 py-4 ">
		<form method="post" action="<?php echo $isEdit ? $basePath . '/purchase/update' : $basePath . '/purchase/store'; ?>" class="space-y-4">
			<div hidden>
				<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
				<?php if ($isEdit) { ?>
					<input type="hidden" name="id" value="<?php echo (int) $purchase['id']; ?>">
				<?php } ?>
			</div>
			<div class="relative">
				<label class="absolute left-3 top-0 z-10 -translate-y-1/2 bg-white px-1 leading-none text-sm text-slate-700">Nhà cung cấp</label>
				<?php
				$supplierOptions = ['' => 'Chọn nhà cung cấp'];
				if (isset($suppliers) && is_array($suppliers)) {
					foreach ($suppliers as $supplier) {
						$supplierOptions[$supplier['id']] = $supplier['name'];
					}
				}
				$selectedSupplierId = '';
				if ($isEdit && isset($purchase['supplier_id'])) {
					$selectedSupplierId = (int) $purchase['supplier_id'];
				}
				ui_select('supplier_id', $supplierOptions, $selectedSupplierId, [
					'required' => 'required',
					'class' => 'pt-3',
				]);
				?>
			</div>

			<div class="mt-2 rounded-lg border border-slate-200">
				<div class="flex items-center justify-between border-b border-slate-100 px-4 py-2 text-sm font-medium text-slate-800">
					<div>Danh sách sản phẩm</div>
					<button type="button" class="inline-flex items-center rounded-full border border-brand-600 px-3 py-1.5 text-sm font-medium text-brand-700 hover:bg-brand-50" data-product-selector-open data-product-selector-mode="purchase-add">
						<?php echo ui_icon("plus", "mr-1 h-4 w-4"); ?>
						Thêm SP
					</button>
				</div>
				<div class="pt-1">
					<div class="px-4 py-3">
						<div class="rounded-lg border border-dashed border-slate-200 bg-slate-50 px-4 py-4 text-center text-sm text-slate-500<?php echo $isEdit && !empty($items) ? ' hidden' : ''; ?>" data-purchase-empty>
							Chưa có sản phẩm nào. Nhấn "Thêm sản phẩm" để chọn.
						</div>
						<div class="<?php echo $isEdit ? '' : 'hidden'; ?> space-y-3" data-purchase-item-rows>
							<?php
							if ($isEdit && !empty($items)) {
								foreach ($items as $item) {
									$qty = isset($item['qty']) ? (float) $item['qty'] : 0;
									$priceCost = isset($item['price_cost']) ? (float) $item['price_cost'] : 0;
									$amount = isset($item['amount']) ? (float) $item['amount'] : $qty * $priceCost;
									?>
									<div class="purchase-item-row relative rounded-xl border border-slate-200 bg-white px-3 py-3">
										<button type="button" class="absolute right-3 top-3 inline-flex items-center justify-center text-slate-400 hover:text-rose-500" data-purchase-remove-row>
											<?php echo ui_icon("trash", "h-4 w-4"); ?>
										</button>
										<div class="flex items-center justify-between gap-2 pr-6">
											<input type="hidden" name="product_unit_id[]" value="<?php echo (int) $item['product_unit_id']; ?>" data-purchase-unit-id>
											<div class="flex-1 min-w-0">
												<div class="truncate text-sm font-medium text-slate-900" data-purchase-product-label><?php echo htmlspecialchars($item['product_name']); ?></div>
											<div class="mt-0.5 text-sm text-slate-500" data-purchase-product-sub></div>
											</div>
										</div>
										<div class="mt-3 grid grid-cols-1 gap-4 text-sm sm:grid-cols-3">
											<div class="relative">
												<label class="absolute left-2 top-0 z-10 -translate-y-1/2 bg-white px-1 leading-none text-sm text-slate-700">Số lượng</label>
												<input type="number" name="qty[]" min="0" step="0.001" value="<?php echo rtrim(rtrim(number_format($qty, 3, '.', ''), '0'), '.'); ?>" class="form-field block w-full rounded-md border border-slate-300 bg-slate-50 px-2 pt-3 pb-2.5 text-sm outline-none focus:border-brand-500 focus:bg-white" />
											</div>
											<div class="relative">
												<label class="absolute left-2 top-0 z-10 -translate-y-1/2 bg-white px-1 leading-none text-sm text-slate-700">Giá nhập</label>
												<div class="relative">
													<?php
													$priceCostValue = $priceCost > 0 ? number_format($priceCost, 0, '', '.') : '';
													ui_input_text('price_cost[]', $priceCostValue, [
														'inputmode' => 'numeric',
														'data-money-input' => '1',
														'class' => 'pr-8 pl-2 pt-3 pb-2.5 text-right',
													]);
													?>
													<span class="pointer-events-none absolute inset-y-0 right-2 flex items-center text-sm text-slate-500">đ</span>
												</div>
											</div>
											<div class="relative">
												<label class="absolute left-2 top-0 z-10 -translate-y-1/2 bg-white px-1 leading-none text-sm text-slate-700">Thành tiền</label>
												<div class="relative">
													<?php
													$amountValue = $amount > 0 ? number_format($amount, 0, '', '.') : '';
													ui_input_text('amount[]', $amountValue, [
														'inputmode' => 'numeric',
														'data-money-input' => '1',
														'data-purchase-amount-input' => '1',
														'class' => 'pr-8 pl-2 pt-3 pb-2.5 text-right',
													]);
													?>
													<span class="pointer-events-none absolute inset-y-0 right-2 flex items-center text-sm text-slate-500">đ</span>
												</div>
											</div>
										</div>
										<div class="mt-2 flex items-center justify-end gap-2 text-sm text-slate-600">
											<input type="checkbox" name="update_cost[]" value="1" class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
											<span>Cập nhật giá vốn theo giá nhập này</span>
										</div>
									</div>
								<?php }
							}
							if (!$isEdit || empty($items)) { ?>
								<div class="purchase-item-row relative hidden rounded-xl border border-slate-200 bg-white px-3 py-3">
									<button type="button" class="absolute right-3 top-3 inline-flex items-center justify-center text-slate-400 hover:text-rose-500" data-purchase-remove-row>
										<?php echo ui_icon("trash", "h-4 w-4"); ?>
									</button>
									<div class="flex items-center justify-between gap-2 pr-6">
										<div class="flex-1 min-w-0">
											<div class="truncate text-sm font-medium text-slate-900" data-purchase-product-label>Chưa chọn sản phẩm</div>
											<div class="mt-0.5 text-sm text-slate-500" data-purchase-product-sub></div>
										</div>
									</div>
									<div class="mt-3 grid grid-cols-1 gap-4 text-sm sm:grid-cols-3">
										<div class="relative">
											<label class="absolute left-2 top-0 z-10 -translate-y-1/2 bg-white px-1 leading-none text-sm text-slate-700">Số lượng</label>
											<input type="number" name="qty[]" min="0" step="0.001" value="" class="form-field block w-full rounded-xl border border-slate-300 bg-white px-3.5 pt-3 pb-2.5 text-sm outline-none transition focus:border-brand-500" />
										</div>
										<div class="relative">
											<label class="absolute left-2 top-0 z-10 -translate-y-1/2 bg-white px-1 leading-none text-sm text-slate-700">Giá nhập</label>
											<div class="relative">
												<?php
												ui_input_text('price_cost[]', '', [
													'inputmode' => 'numeric',
													'data-money-input' => '1',
													'class' => 'pr-8 pl-2 pt-3 pb-2.5 text-right',
												]);
												?>
												<span class="pointer-events-none absolute inset-y-0 right-2 flex items-center text-sm text-slate-500">đ</span>
											</div>
										</div>
										<div class="relative">
											<label class="absolute left-2 top-0 z-10 -translate-y-1/2 bg-white px-1 leading-none text-sm text-slate-700">Thành tiền</label>
											<div class="relative">
												<?php
												ui_input_text('amount[]', '', [
													'inputmode' => 'numeric',
													'data-money-input' => '1',
													'data-purchase-amount-input' => '1',
													'class' => 'pr-8 pl-2 pt-3 pb-2.5 text-right',
												]);
												?>
												<span class="pointer-events-none absolute inset-y-0 right-2 flex items-center text-sm text-slate-500">đ</span>
											</div>
										</div>
									</div>
									<div class="mt-2 flex items-center justify-end gap-2 text-sm text-slate-600">
										<input type="checkbox" name="update_cost[]" value="1" class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
										<span>Cập nhật giá vốn theo giá nhập này</span>
									</div>
								</div>
							<?php } ?>
						</div>
					</div>
				</div>
			</div>

			<div class="mt-3 grid grid-cols-1">
					<div class="text-sm font-medium uppercase  text-slate-500">Tổng quan</div>
					<div class="mt-2 space-y-1 text-sm">
						<div class="flex items-center justify-between">
							<span class="text-slate-600">Tổng số lượng</span>
							<span class="font-medium text-slate-900" data-purchase-summary-qty>0</span>
						</div>
						<div class="flex items-center justify-between">
							<span class="text-slate-600">Tổng tiền hàng</span>
							<span class="text-sm old-text-base font-medium text-brand-700" data-purchase-summary-amount>0 đ</span>
						</div>
					</div>
			</div>

			<div class="space-y-4">
			<?php
			$paymentMethodValue = 'cash';
			if (isset($paymentMethod) && $paymentMethod !== null) {
				$paymentMethodValue = $paymentMethod === 'bank' ? 'bank' : 'cash';
			}
			$paymentStatusValue = 'pay';
			if ($isEdit && isset($purchase['status']) && $purchase['status'] === 'debt') {
				$paymentStatusValue = 'debt';
			}
			?>
			<?php if (!$isEdit) { ?>
			<div class="space-y-1">
				<label class="block text-sm font-medium text-slate-700">Thanh toán</label>
				<div class="flex w-full rounded-xl bg-slate-100 p-0.5 text-sm text-slate-700" data-purchase-payment-status-wrapper>
					<label class="inline-flex flex-1">
						<input type="radio" name="payment_status" value="pay" class="peer sr-only" <?php echo $paymentStatusValue === 'pay' ? 'checked' : ''; ?>>
						<span class="inline-flex flex-1 items-center justify-center rounded-xl px-3 py-2 font-medium text-slate-700 peer-checked:bg-brand-600 peer-checked:text-white">Thanh toán</span>
					</label>
					<label class="inline-flex flex-1">
						<input type="radio" name="payment_status" value="debt" class="peer sr-only" <?php echo $paymentStatusValue === 'debt' ? 'checked' : ''; ?>>
						<span class="inline-flex flex-1 items-center justify-center rounded-xl px-3 py-2 font-medium text-slate-700 peer-checked:bg-brand-600 peer-checked:text-white">Ghi nợ</span>
					</label>
				</div>
			</div>
			<div class="space-y-4" data-purchase-payment-fields>
				<div class="space-y-1">
					<label class="block text-sm font-medium text-slate-700">Hình thức thanh toán</label>
					<?php
					$paymentMethodField = 'payment_method';
					// $paymentMethodValue đã có sẵn ở trên
					include __DIR__ . '/../partials/payment_method_radios.php';
					?>
				</div>
				<div class="relative">
					<label class="absolute left-3 top-0 z-10 -translate-y-1/2 bg-white px-1 leading-none text-sm text-slate-700">Số tiền thanh toán</label>
					<div class="relative">
						<?php
						$paidValue = '';
						if ($isEdit && isset($purchase['paid_amount'])) {
							$paidValue = number_format((float) $purchase['paid_amount'], 0, '', '.');
						}
						ui_input_text('paid_amount', $paidValue, [
							'inputmode' => 'numeric',
							'data-money-input' => '1',
							'class' => 'pr-8 pt-3 pb-2.5 text-right',
						]);
						?>
						<span class="pointer-events-none absolute inset-y-0 right-2 flex items-center text-sm text-slate-500">đ</span>
					</div>
				</div>
			</div>
			<?php } ?>
			<?php
			$noteValue = '';
			if ($isEdit) {
				if (isset($noteForEdit)) {
					$noteValue = $noteForEdit;
				} elseif (!empty($purchase['note'])) {
					$noteValue = $purchase['note'];
				}
			}
			?>
			<div class="relative">
				<label class="absolute left-3 top-0 z-10 -translate-y-1/2 bg-white px-1 leading-none text-sm text-slate-700">Ghi chú</label>
				<textarea name="note" rows="3" class="form-field block w-full rounded-xl border border-slate-300 bg-white px-3.5 pt-3 pb-2.5 text-sm outline-none transition focus:border-brand-500" placeholder="Nhập ghi chú cho phiếu nhập này..."><?php echo htmlspecialchars($noteValue); ?></textarea>
			</div>
			</div>

			<div class="pt-2" data-floating-actions>
				<?php
				$submitLabel = $isEdit ? 'Cập nhật phiếu' : 'Lưu phiếu';
				ui_button_primary($submitLabel, ['type' => 'submit', 'class' => 'w-full', 'data-loading-button' => '1', 'data-floating-primary' => '1']);
				?>
			</div>
		</form>
	</div>
</div>
<?php
	if (!empty($productUnits)) {
	$purchaseUnitsForJs = [];
	foreach ($productUnits as $row) {
		$purchaseUnitsForJs[] = [
			'id' => (int) $row['id'],
			'product_id' => (int) $row['product_id'],
			'product_name' => $row['product_name'],
			'unit_name' => $row['unit_name'],
			'price_cost' => isset($row['price_cost']) ? (float) $row['price_cost'] : 0,
			'price_text' => isset($row['price_cost']) ? Money::format($row['price_cost']) : '',
			'image_url' => !empty($row['product_image_path']) ? $basePath . '/' . ltrim($row['product_image_path'], '/') : null,
		];
	}
	?>
	<script>
	window.PURCHASE_PRODUCT_UNITS = <?php echo json_encode($purchaseUnitsForJs); ?>;
	</script>
	<?php include __DIR__ . '/../partials/product_selector_modal.php'; ?>
<?php } ?>
