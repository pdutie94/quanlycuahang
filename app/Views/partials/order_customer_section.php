		<div class="rounded-lg border border-slate-200 bg-white px-4 py-4 lg:px-5 lg:py-5">
            <div class="mb-3 text-sm font-medium text-slate-800">
                Thông tin khách hàng
            </div>

            <?php if ($isPos) { ?>
                <?php
                $selectedCustomerId = 0;
                $paymentStatus = 'pay';
                $paymentMethod = 'cash';
                $paymentAmount = 0;
                $noteValue = '';
                ?>
                <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                    <input type="hidden" name="customer_id" value="" data-pos-customer-id>
                    <input type="hidden" name="customer_mode" value="guest" data-pos-customer-mode-input>
                    <div class="space-y-2 md:col-span-2">
                        <label class="block text-sm text-slate-700">Khách hàng</label>
                        <div class="inline-flex w-full rounded-full bg-slate-100 p-0.5 text-sm text-slate-700">
                            <button type="button" class="inline-flex flex-1 items-center justify-center rounded-full px-3 py-1.5 font-medium text-slate-700" data-pos-customer-mode="existing">
                                Khách cũ
                            </button>
                            <button type="button" class="inline-flex flex-1 items-center justify-center rounded-full px-3 py-1.5 font-medium text-slate-700" data-pos-customer-mode="new">
                                Khách mới
                            </button>
                            <button type="button" class="inline-flex flex-1 items-center justify-center rounded-full px-3 py-1.5 font-medium text-slate-700" data-pos-customer-mode="guest">
                                Khách lẻ
                            </button>
                        </div>
                    </div>
                    <div class="space-y-1 md:col-span-2 hidden" data-pos-existing-customer-wrapper>
                        <label class="block text-sm text-slate-700">Khách cũ</label>
                        <button type="button" class="flex w-full items-center justify-between rounded-lg border border-dashed border-amber-300 bg-amber-50 px-3 py-2 text-left text-sm text-amber-800 hover:border-amber-400 hover:bg-amber-100" data-pos-existing-customer-placeholder>
                            <div class="flex items-center gap-2">
                                <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-amber-100 text-amber-700">
                                    <?php echo ui_icon("user", "h-4 w-4"); ?>
                                </span>
                                <div class="flex flex-col">
                                    <span class="font-medium" data-pos-existing-customer-name>Chưa chọn khách</span>
                                    <span class="text-xs text-amber-700" data-pos-existing-customer-meta>Nhấn để chọn khách từ danh sách</span>
                                </div>
                            </div>
                            <span class="ml-2 inline-flex h-6 w-6 items-center justify-center text-amber-500">
                                <?php echo ui_icon("chevron-right", "h-4 w-4"); ?>
                            </span>
                        </button>
                    </div>
                    <div class="space-y-1 hidden" data-pos-new-customer>
                        <label class="block text-sm text-slate-700">Tên khách hàng</label>
                        <input type="text" name="customer_name" value="" class="form-field block w-full rounded-lg border border-slate-300 bg-slate-50 px-3 text-sm outline-none focus:border-brand-500 focus:bg-white" />
                    </div>
                    <div class="space-y-1 hidden" data-pos-new-customer>
                        <label class="block text-sm text-slate-700">Số điện thoại</label>
                        <input type="text" name="customer_phone" value="" class="form-field block w-full rounded-lg border border-slate-300 bg-slate-50 px-3 text-sm outline-none focus:border-brand-500 focus:bg-white" />
                    </div>
                    <div class="space-y-1 md:col-span-2 hidden" data-pos-new-customer>
                        <label class="block text-sm text-slate-700">Địa chỉ</label>
                        <input type="text" name="customer_address" value="" class="form-field block w-full rounded-lg border border-slate-300 bg-slate-50 px-3 text-sm outline-none focus:border-brand-500 focus:bg-white" />
                    </div>
					<div class="space-y-1 md:col-span-2">
                        <label class="block text-sm text-slate-700">Thanh toán</label>
						<div class="flex w-full rounded-full bg-slate-100 p-0.5 text-sm text-slate-700">
							<label class="inline-flex flex-1">
								<input type="radio" name="payment_status" value="pay" class="peer sr-only" checked>
								<span class="inline-flex flex-1 items-center justify-center rounded-full px-3 py-2 font-medium text-slate-700 peer-checked:bg-brand-600 peer-checked:text-white">Thanh toán</span>
							</label>
							<label class="inline-flex flex-1">
								<input type="radio" name="payment_status" value="debt" class="peer sr-only">
								<span class="inline-flex flex-1 items-center justify-center rounded-full px-3 py-2 font-medium text-slate-700 peer-checked:bg-brand-600 peer-checked:text-white">Ghi nợ</span>
							</label>
						</div>
                    </div>
                    <div class="space-y-1 md:col-span-2" data-pos-payment-method-wrapper>
                        <label class="block text-sm text-slate-700">Hình thức thanh toán</label>
						<?php
						$paymentMethodField = 'payment_method';
						$paymentMethodValue = 'cash';
						include __DIR__ . '/payment_method_radios.php';
						?>
                    </div>
					<div class="space-y-1 md:col-span-2" data-pos-payment-method-wrapper>
						<label class="block text-sm text-slate-700">Số tiền thanh toán</label>
						<div class="relative">
							<?php
							ui_input_text('payment_amount', '0', [
								'inputmode' => 'numeric',
								'data-money-input' => '1',
								'class' => 'pr-9 py-2 text-right text-base font-semibold tracking-tight',
							]);
							?>
							<span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-sm text-slate-500">đ</span>
						</div>
					</div>
                    <div class="space-y-1 md:col-span-2">
                        <label class="block text-sm text-slate-700">Ghi chú</label>
                        <textarea name="note" rows="3" class="form-field block w-full rounded-lg border border-slate-300 bg-slate-50 px-3 py-2 text-sm outline-none focus:border-brand-500 focus:bg-white"></textarea>
                    </div>
                </div>
                <input type="hidden" name="items_json" value="" data-pos-items-json>
				<input type="hidden" name="discount_type" value="none" data-order-discount-type>
				<input type="hidden" name="discount_value" value="0" data-order-discount-value>
				<input type="hidden" name="discount_amount" value="0" data-order-discount-amount-hidden data-pos-discount-hidden>
                <input type="hidden" name="surcharge_amount" value="0" data-pos-surcharge-hidden>
				<input type="hidden" name="round_total" value="0" data-order-round-total-flag>
				<div class="mt-4 flex flex-wrap gap-2" data-floating-actions>
					<button type="button" data-pos-submit-order class="inline-flex flex-1 items-center justify-center rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-700 active:bg-brand-800" data-loading-button="1" data-floating-primary="1">
                        Nhập đơn
                    </button>
                </div>
            <?php } else { ?>
                <?php
                $selectedCustomerId = isset($order['customer_id']) ? (int) $order['customer_id'] : 0;
                $paymentStatus = isset($paymentStatus) ? $paymentStatus : (isset($order['status']) && $order['status'] === 'debt' ? 'debt' : 'pay');
                $paymentMethod = isset($paymentMethod) && $paymentMethod ? $paymentMethod : 'cash';
                $paymentAmount = isset($order['paid_amount']) ? (float) $order['paid_amount'] : 0;
                $noteValue = isset($noteForEdit) ? $noteForEdit : (isset($order['note']) ? $order['note'] : '');
                $paymentStatusValue = $paymentStatus === 'debt' ? 'debt' : 'pay';
                $paymentMethodValue = $paymentMethod === 'bank' ? 'bank' : 'cash';
                $paymentAmountValue = $paymentAmount;
				$discountTypeValue = isset($order['discount_type']) ? $order['discount_type'] : 'none';
				if (!in_array($discountTypeValue, ['none', 'fixed', 'percent'], true)) {
					$discountTypeValue = 'none';
				}
				$discountValueRaw = isset($order['discount_value']) ? (float) $order['discount_value'] : 0;
				if ($discountValueRaw < 0) {
					$discountValueRaw = 0;
				}
				$discountAmountRaw = isset($order['discount_amount']) ? (float) $order['discount_amount'] : 0;
				if ($discountAmountRaw < 0) {
					$discountAmountRaw = 0;
				}
				$surchargeAmountRaw = isset($order['surcharge_amount']) ? (float) $order['surcharge_amount'] : 0;
				if ($surchargeAmountRaw < 0) {
					$surchargeAmountRaw = 0;
				}
				$baseSubtotalForRound = (float) $order['total_amount'] + $discountAmountRaw - $surchargeAmountRaw;
				$rawTotalForRound = $baseSubtotalForRound - $discountAmountRaw + $surchargeAmountRaw;
				if ($rawTotalForRound < 0) {
					$rawTotalForRound = 0;
				}
				$roundedForRound = floor($rawTotalForRound / 1000) * 1000;
				$roundTotalFlagValue = ($roundedForRound > 0 && $roundedForRound === (float) $order['total_amount'] && $rawTotalForRound > $roundedForRound) ? 1 : 0;

				$currentCustomerNameLine = 'Chưa chọn khách';
				$currentCustomerMeta = 'Nhấn để chọn khách từ danh sách';
				if ($selectedCustomerId > 0 && isset($customers) && is_array($customers)) {
					foreach ($customers as $c) {
						$cid = isset($c['id']) ? (int) $c['id'] : 0;
						if ($cid === $selectedCustomerId) {
							$line = isset($c['name']) ? $c['name'] : '';
							if (!empty($c['phone'])) {
								$line .= ' - ' . $c['phone'];
							}
							if ($line !== '') {
								$currentCustomerNameLine = $line;
							}
							if (!empty($c['address'])) {
								$currentCustomerMeta = $c['address'];
							}
							break;
						}
					}
				}
				$initialCustomerMode = $selectedCustomerId > 0 ? 'existing' : 'guest';
				$orderDateValue = '';
				if (!empty($order['order_date'])) {
					$orderDateTs = strtotime($order['order_date']);
					if ($orderDateTs !== false) {
						$orderDateValue = date('Y-m-d\TH:i', $orderDateTs);
					}
				}
                ?>

				<div class="grid grid-cols-1 gap-3 md:grid-cols-2">
					<input type="hidden" name="customer_id" value="<?php echo $selectedCustomerId > 0 ? $selectedCustomerId : ''; ?>" data-order-customer-id>
					<input type="hidden" name="customer_mode" value="<?php echo $initialCustomerMode; ?>" data-order-customer-mode-input>
					<div class="space-y-2 md:col-span-2">
						<label class="block text-sm text-slate-700">Khách hàng</label>
						<div class="inline-flex w-full rounded-full bg-slate-100 p-0.5 text-sm text-slate-700">
							<button type="button" class="inline-flex flex-1 items-center justify-center rounded-full px-3 py-1.5 font-medium text-slate-700" data-order-customer-mode="existing">
								Khách cũ
							</button>
							<button type="button" class="inline-flex flex-1 items-center justify-center rounded-full px-3 py-1.5 font-medium text-slate-700" data-order-customer-mode="new">
								Khách mới
							</button>
							<button type="button" class="inline-flex flex-1 items-center justify-center rounded-full px-3 py-1.5 font-medium text-slate-700" data-order-customer-mode="guest">
								Khách lẻ
							</button>
						</div>
					</div>
					<div class="space-y-1 md:col-span-2<?php echo $selectedCustomerId > 0 ? '' : ' hidden'; ?>" data-order-existing-customer-wrapper>
						<label class="block text-sm text-slate-700">Khách cũ</label>
						<button type="button" class="flex w-full items-center justify-between rounded-lg border border-dashed border-amber-300 bg-amber-50 px-3 py-2 text-left text-sm text-amber-800 hover:border-amber-400 hover:bg-amber-100" data-order-existing-customer-placeholder>
							<div class="flex items-center gap-2">
								<span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-amber-100 text-amber-700">
									<?php echo ui_icon("user", "h-4 w-4"); ?>
								</span>
								<div class="flex flex-col">
									<span class="font-medium" data-order-existing-customer-name><?php echo htmlspecialchars($currentCustomerNameLine); ?></span>
									<span class="text-xs text-amber-700" data-order-existing-customer-meta><?php echo htmlspecialchars($currentCustomerMeta); ?></span>
								</div>
							</div>
							<span class="ml-2 inline-flex h-6 w-6 items-center justify-center text-amber-500">
								<?php echo ui_icon("chevron-right", "h-4 w-4"); ?>
							</span>
						</button>
					</div>
					<div class="space-y-1 hidden" data-order-new-customer>
						<label class="block text-sm text-slate-700">Tên khách hàng</label>
						<?php ui_input_text('customer_name', ''); ?>
					</div>
					<div class="space-y-1 hidden" data-order-new-customer>
						<label class="block text-sm text-slate-700">Số điện thoại</label>
						<?php ui_input_text('customer_phone', ''); ?>
					</div>
					<div class="space-y-1 md:col-span-2 hidden" data-order-new-customer>
						<label class="block text-sm text-slate-700">Địa chỉ</label>
						<?php ui_input_text('customer_address', ''); ?>
					</div>
					<div class="space-y-1 md:col-span-2">
						<label class="block text-sm text-slate-700">Ngày giờ đơn hàng</label>
						<input type="datetime-local" name="order_date" value="<?php echo htmlspecialchars($orderDateValue); ?>" class="form-field block w-full rounded-lg border border-slate-300 bg-slate-50 px-3 py-2 text-sm outline-none focus:border-brand-500 focus:bg-white">
					</div>
					<div class="space-y-1 md:col-span-2">
						<label class="block text-sm text-slate-700">Ghi chú</label>
						<textarea name="note" rows="3" class="form-field block w-full rounded-lg border border-slate-300 bg-slate-50 px-3 py-2 text-sm outline-none focus:border-brand-500 focus:bg-white"><?php echo htmlspecialchars($noteValue); ?></textarea>
					</div>
					<input type="hidden" name="discount_type" value="<?php echo $discountTypeValue; ?>" data-order-discount-type>
					<input type="hidden" name="discount_value" value="<?php echo $discountValueRaw; ?>" data-order-discount-value>
					<input type="hidden" name="discount_amount" value="<?php echo $discountAmountRaw; ?>" data-order-discount-amount-hidden>
                    <input type="hidden" name="surcharge_amount" value="<?php echo $surchargeAmountRaw; ?>" data-order-surcharge-value>
					<input type="hidden" name="round_total" value="<?php echo $roundTotalFlagValue; ?>" data-order-round-total-flag>
				</div>

				<div class="mt-4 sticky bottom-[4.2rem] z-10 -mx-1 flex items-center justify-end gap-3 border-t border-slate-200 bg-white/95 px-1 pb-1 pt-2 backdrop-blur" data-floating-actions>
                    <a href="<?php echo $basePath; ?>/order/view?id=<?php echo $order['id']; ?>" class="inline-flex items-center rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">
                        Hủy
                    </a>
					<button type="submit" class="inline-flex min-h-11 items-center rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700 active:bg-brand-800" data-loading-button="1" data-floating-primary="1">
                        Lưu thay đổi
                    </button>
                </div>
            <?php } ?>
        </div>
