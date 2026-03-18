<?php
$queryParams = $_GET;
unset($queryParams['page'], $queryParams['ajax']);
$queryString = http_build_query($queryParams);
?>
<?php if (empty($suppliers)) { ?>
	<div class="rounded-lg border border-dashed border-slate-300 bg-white px-4 py-4 text-center text-sm text-slate-500">
		Chưa có nhà cung cấp nào.
	</div>
<?php } else { ?>
	<div class="space-y-3" data-infinite-list data-infinite-url="<?php echo $basePath; ?>/supplier" data-infinite-query="<?php echo htmlspecialchars($queryString); ?>" data-current-page="<?php echo isset($page) ? (int) $page : 1; ?>" data-has-more="<?php echo isset($totalPages) && isset($page) && $page < $totalPages ? '1' : '0'; ?>">
		<?php foreach ($suppliers as $supplier) { ?>
			<a href="<?php echo $basePath; ?>/supplier/view?id=<?php echo $supplier['id']; ?>" class="relative block rounded-card bg-white p-3 text-sm border border-slate-200 transition hover:bg-slate-50" data-infinite-item>
				<div class="flex items-center justify-between gap-3">
					<div class="min-w-0">
						<div class="truncate text-sm font-medium text-slate-900">
							<span><?php echo htmlspecialchars($supplier['name']); ?></span>
						</div>
						<div class="mt-1 text-sm text-slate-600">
							<?php if (!empty($supplier['phone'])) { ?>
								<span class="mr-3">SĐT: <?php echo htmlspecialchars($supplier['phone']); ?></span>
							<?php } ?>
							<?php if (!empty($supplier['address'])) { ?>
								<span class="line-clamp-1">Địa chỉ: <?php echo htmlspecialchars($supplier['address']); ?></span>
							<?php } ?>
						</div>
					</div>
				</div>
			</a>
		<?php } ?>
	</div>
<?php } ?>
