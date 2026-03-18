<?php
$paymentMethodField = isset($paymentMethodField) && $paymentMethodField !== '' ? $paymentMethodField : 'payment_method';
$paymentMethodValue = isset($paymentMethodValue) && $paymentMethodValue === 'bank' ? 'bank' : 'cash';
?>
<div class="flex w-full rounded-full bg-slate-100 p-0.5 text-sm text-slate-700">
    <label class="inline-flex flex-1">
        <input type="radio" name="<?php echo htmlspecialchars($paymentMethodField); ?>" value="cash" class="peer sr-only" <?php echo $paymentMethodValue === 'cash' ? 'checked' : ''; ?>>
        <span class="inline-flex flex-1 items-center justify-center rounded-full px-3 py-2 font-medium text-slate-700 peer-checked:bg-brand-600 peer-checked:text-white">Tiền mặt</span>
    </label>
    <label class="inline-flex flex-1">
        <input type="radio" name="<?php echo htmlspecialchars($paymentMethodField); ?>" value="bank" class="peer sr-only" <?php echo $paymentMethodValue === 'bank' ? 'checked' : ''; ?>>
        <span class="inline-flex flex-1 items-center justify-center rounded-full px-3 py-2 font-medium text-slate-700 peer-checked:bg-brand-600 peer-checked:text-white">Chuyển khoản</span>
    </label>
</div>
