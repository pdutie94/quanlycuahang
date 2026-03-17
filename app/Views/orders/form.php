<?php if (!isset($detailHeader)) { ?>
<div class="mb-4 flex items-center justify-between gap-3">
	<h1 class="text-lg font-medium tracking-tight">Sửa đơn hàng</h1>
	<a href="<?php echo $basePath; ?>/order/view?id=<?php echo $order['id']; ?>" class="inline-flex items-center gap-1 rounded-full border border-slate-300 px-2.5 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-100">
		<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4">
			<path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
		</svg>
        <span>Chi tiết</span>
    </a>
</div>
<?php } ?>

<?php
$layoutMode = 'order_edit';
include __DIR__ . '/../partials/pos_order_layout.php';
?>
