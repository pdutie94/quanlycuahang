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
			if (!isset($row['order_code']) && isset($row['code'])) {
				$row['order_code'] = $row['code'];
			}
			if (!isset($row['order_date']) && isset($row['doc_date'])) {
				$row['order_date'] = $row['doc_date'];
			}
			if (!isset($row['items_count'])) {
				$row['items_count'] = 0;
			}
			?>
			<?php
			$orderCardData = $row;
			$orderCardUrl = $basePath . '/order/view?id=' . (int) $row['id'];
			$orderCardExtraAttrs = 'data-infinite-item';
			include __DIR__ . '/../partials/order_item_card.php';
			?>
		<?php } ?>
	</div>
<?php } ?>

<?php include __DIR__ . '/../partials/order_preview_modal.php'; ?>
