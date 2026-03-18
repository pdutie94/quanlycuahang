<?php
$totalHeader = isset($purchase['total_amount']) ? (float) $purchase['total_amount'] : 0.0;
$paidHeader = isset($purchase['paid_amount']) ? (float) $purchase['paid_amount'] : 0.0;
$debtHeader = $totalHeader - $paidHeader;
if ($debtHeader < 0) {
	$debtHeader = 0.0;
}
?>
<div class="relative" data-header-actions-menu>
	<button type="button" class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-slate-300 bg-white text-slate-600 hover:bg-slate-100" data-header-actions-toggle>
		<?php echo ui_icon("ellipsis-vertical", "h-4 w-4"); ?>
	</button>
	<div class="absolute right-0 z-30 mt-2 w-44 rounded-xl border border-slate-200 bg-white py-1 text-sm  overflow-hidden hidden" data-header-actions-dropdown>
		<a href="<?php echo $basePath; ?>/purchase/edit?id=<?php echo (int) $purchase['id']; ?>" class="flex items-center justify-between gap-2 px-3 py-1.5 text-slate-700 hover:bg-slate-50">
			<div class="flex items-center gap-1.5">
				<?php echo ui_icon("pencil-square", "h-4 w-4 text-slate-500"); ?>
				<span>Sửa phiếu nhập</span>
			</div>
		</a>
		<?php if ($debtHeader > 0) { ?>
			<button type="button" class="flex w-full items-center justify-between gap-2 px-3 py-1.5 text-left text-slate-700 hover:bg-slate-50" data-purchase-payment-open>
				<div class="flex items-center gap-1.5">
					<?php echo ui_icon("banknotes", "h-4 w-4 text-brand-600"); ?>
					<span>Thanh toán</span>
				</div>
			</button>
		<?php } ?>
	</div>
</div>
