<?php
$rows = isset($rows) && is_array($rows) ? $rows : (isset($orders) && is_array($orders) ? $orders : []);
?>
<?php if (empty($rows)) { ?>
	<div class="rounded-lg border border-dashed border-slate-300 bg-white px-4 py-4 text-center text-sm text-slate-500">
		Không có đơn hàng nào trong khoảng thời gian đã chọn.
	</div>
<?php } else { ?>
	<?php
	$queryParams = $_GET;
	unset($queryParams['page'], $queryParams['ajax']);
	$queryString = http_build_query($queryParams);
	?>
	<div class="space-y-3" data-infinite-list data-infinite-url="<?php echo $basePath; ?>/report/sales" data-infinite-query="<?php echo htmlspecialchars($queryString); ?>" data-current-page="<?php echo isset($page) ? (int) $page : 1; ?>" data-has-more="<?php echo isset($totalPages) && isset($page) && $page < $totalPages ? '1' : '0'; ?>">
		<?php foreach ($rows as $row) { ?>
			<?php
			$total = isset($row['total_amount']) ? (float) $row['total_amount'] : 0.0;
			$cost = isset($row['total_cost']) ? (float) $row['total_cost'] : 0.0;
			$profit = $total - $cost;
			$paid = isset($row['paid_amount']) ? (float) $row['paid_amount'] : 0.0;
			$debt = $total - $paid;
			$orderStatus = isset($row['order_status']) ? $row['order_status'] : null;
			$statusValue = isset($row['status']) ? $row['status'] : null;
			$url = $basePath . '/order/view?id=' . (int) $row['id'];
			$code = isset($row['code']) ? $row['code'] : (isset($row['order_code']) ? $row['order_code'] : '');
			$dateValue = isset($row['doc_date']) ? $row['doc_date'] : (isset($row['order_date']) ? $row['order_date'] : null);
			$customerName = isset($row['customer_name']) ? $row['customer_name'] : '';
			$customerPhone = isset($row['customer_phone']) ? $row['customer_phone'] : '';
			?>
			<a href="<?php echo $url; ?>" class="block rounded-xl border border-slate-200 bg-white px-4 py-2.5 shadow-sm" data-infinite-item>
				<div>
					<div class="flex items-center gap-2">
						<div class="font-mono text-sm font-medium text-emerald-700">
							#<?php echo htmlspecialchars($code); ?>
						</div>
						<div class="inline-flex items-center gap-1 text-sm font-medium">
							<?php
							$paidLabel = '';
							$paidClass = '';
							if ($total > 0 && $paid >= $total) {
								$paidLabel = 'Đã thanh toán';
								$paidClass = 'bg-emerald-50 text-emerald-700';
							} elseif ($paid > 0 && $paid < $total) {
								$paidLabel = 'Đã thu một phần';
								$paidClass = 'bg-amber-50 text-amber-700';
							} else {
								$paidLabel = 'Còn nợ';
								$paidClass = 'bg-amber-50 text-amber-700';
							}
							?>
							<span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-sm font-medium <?php echo $paidClass; ?>"><?php echo htmlspecialchars($paidLabel); ?></span>
						</div>
					</div>
					<div class="mt-1 text-sm text-slate-500">
						<?php if (!empty($dateValue)) { ?>
							<?php echo htmlspecialchars(format_datetime($dateValue)); ?>
						<?php } ?>
					</div>
					<div class="mt-1 text-sm text-slate-600">
						<span class="text-slate-500">Khách hàng: </span>
						<span class="text-slate-800">
							<?php if (!empty($customerName)) { ?>
								<?php echo htmlspecialchars($customerName); ?>
								<?php if (!empty($customerPhone)) { ?>
									<span class="text-slate-400"> - <?php echo htmlspecialchars($customerPhone); ?></span>
								<?php } ?>
							<?php } else { ?>
								<span class="text-slate-400">Khách lẻ</span>
							<?php } ?>
						</span>
					</div>
					<div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-slate-600">
						<span>
							Tổng: <span class="font-medium text-slate-900"><?php echo Money::format($total); ?></span>
						</span>
						<span>
							Lợi nhuận: <span class="font-medium text-emerald-600"><?php echo Money::format($profit); ?></span>
						</span>
						<span>
							Đã thu: <span class="font-medium text-emerald-600"><?php echo Money::format($paid); ?></span>
						</span>
						<span>
							Còn nợ: <span class="font-medium <?php echo $debt > 0 ? 'text-red-600' : 'text-slate-700'; ?>"><?php echo Money::format($debt); ?></span>
						</span>
					</div>
				</div>
			</a>
		<?php } ?>
	</div>
<?php } ?>
