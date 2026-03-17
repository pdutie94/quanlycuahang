<?php
$queryParams = $_GET;
unset($queryParams['page'], $queryParams['ajax']);
$queryString = http_build_query($queryParams);
?>
<?php if (empty($customers)) { ?>
	<div class="rounded-lg border border-dashed border-slate-300 bg-white px-4 py-4 text-center text-sm text-slate-500">
		Chưa có khách hàng nào.
	</div>
<?php } else { ?>
	<div class="space-y-3" data-infinite-list data-infinite-url="<?php echo $basePath; ?>/customer" data-infinite-query="<?php echo htmlspecialchars($queryString); ?>" data-current-page="<?php echo isset($page) ? (int) $page : 1; ?>" data-has-more="<?php echo isset($totalPages) && isset($page) && $page < $totalPages ? '1' : '0'; ?>">
		<?php foreach ($customers as $index => $customer) { ?>
			<?php $debt = isset($customer['debt_amount']) ? (float) $customer['debt_amount'] : 0; ?>
			<a href="<?php echo $basePath; ?>/customer/view?id=<?php echo $customer['id']; ?>" class="block cursor-pointer rounded-2xl bg-white p-3 shadow-sm ring-1 ring-slate-100 transition hover:bg-slate-50" data-infinite-item>
				<div class="flex items-center justify-between gap-3">
					<div class="min-w-0">
						<div class="truncate text-sm font-medium text-slate-900">
							<?php echo htmlspecialchars($customer['name']); ?>
						</div>
						<div class="mt-1 text-sm text-slate-600">
							<?php if (!empty($customer['phone'])) { ?>
								<span class="mr-3">SĐT: <?php echo htmlspecialchars($customer['phone']); ?></span>
							<?php } ?>
							<?php if (!empty($customer['address'])) { ?>
								<span class="line-clamp-1">Địa chỉ: <?php echo htmlspecialchars($customer['address']); ?></span>
							<?php } ?>
						</div>
					</div>
					<div class="flex flex-col items-end gap-1 text-right text-sm">
						<div class="text-sm text-slate-500">Nợ hiện tại</div>
						<div class="text-sm font-medium <?php echo $debt > 0 ? 'text-red-600' : 'text-slate-700'; ?>">
							<?php echo Money::format($debt); ?>
						</div>
					</div>
				</div>
			</a>
		<?php } ?>
	</div>
<?php } ?>
