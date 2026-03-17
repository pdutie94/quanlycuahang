<?php
$items = isset($items) && is_array($items) ? $items : [];
$totalItems = count($items);
?>

<div class="mb-4 space-y-2">
	<div class="flex items-center justify-between gap-3">
		<div>
			<h1 class="text-lg font-medium tracking-tight text-slate-900">Cập nhật tồn kho</h1>
			<p class="mt-0.5 text-sm text-slate-500">Xem nhanh số lượng tồn và điều chỉnh tồn kho thực tế cho từng sản phẩm.</p>
		</div>
	</div>
	<?php
	$activeReport = 'inventory';
	include __DIR__ . '/_report_nav.php';
	?>
</div>

<?php if (empty($items)) { ?>
	<div class="rounded-xl border border-dashed border-slate-300 bg-white px-4 py-6 text-center text-sm text-slate-500">
		Chưa có dữ liệu tồn kho.
	</div>
<?php } else { ?>
	<div class="space-y-3">
		<?php foreach ($items as $row) { ?>
			<?php
			$qtyBase = isset($row['qty_base']) ? (float) $row['qty_base'] : 0.0;
			$updatedAt = !empty($row['updated_at']) ? $row['updated_at'] : null;
			?>
			<div class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 shadow-sm">
				<div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
					<div class="min-w-0">
						<div class="flex items-center gap-2">
							<span class="text-sm font-medium text-slate-900 truncate">
								<?php echo htmlspecialchars($row['name']); ?>
							</span>
						</div>
						<div class="mt-1 text-sm text-slate-500">
							<?php if (!empty($row['category_name'])) { ?>
								Danh mục: <?php echo htmlspecialchars($row['category_name']); ?>
							<?php } else { ?>
								Danh mục: <span class="text-slate-400">Chưa phân loại</span>
							<?php } ?>
						</div>
						<div class="mt-1 text-sm text-slate-700">
							Tồn kho:
							<span class="font-medium text-slate-900">
								<?php echo rtrim(rtrim(number_format($qtyBase, 2, ',', ''), '0'), ','); ?>
								<?php echo htmlspecialchars($row['base_unit_name']); ?>
							</span>
						</div>
						<form method="post" action="<?php echo $basePath; ?>/report/inventoryAdjust" class="mt-2 flex flex-wrap items-center gap-2 text-sm text-slate-600">
							<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
							<input type="hidden" name="product_id" value="<?php echo (int) $row['id']; ?>">
							<?php
							$inventoryAdjustValue = rtrim(rtrim(number_format($qtyBase, 2, ',', ''), '0'), ',');
							?>
							<div class="w-24">
								<?php
								ui_input_text('qty_base', $inventoryAdjustValue, [
									'class' => 'h-8 px-2 text-right text-sm text-slate-800',
								]);
								?>
							</div>
							<span class="text-slate-500"><?php echo htmlspecialchars($row['base_unit_name']); ?></span>
							<?php ui_button_primary('Cập nhật tồn', ['type' => 'submit', 'class' => 'h-8 px-2.5 text-sm', 'data-loading-button' => '1']); ?>
						</form>
					</div>
					<div class="flex flex-col items-end justify-between gap-1 text-sm text-slate-500">
						<?php if ($updatedAt) { ?>
							<div>Cập nhật: <?php echo htmlspecialchars(format_datetime($updatedAt)); ?></div>
						<?php } else { ?>
							<div>Chưa có giao dịch</div>
						<?php } ?>
					</div>
				</div>
			</div>
		<?php } ?>
	</div>
<?php } ?>
