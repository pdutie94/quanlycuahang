
<?php
$queryParams = $_GET;
unset($queryParams['page'], $queryParams['ajax']);
$queryString = http_build_query($queryParams);
$categoryList = isset($categories) && is_array($categories) ? $categories : [];
$categoryId = isset($categoryId) ? (int) $categoryId : 0;
if ($categoryId < 0) {
	$categoryId = 0;
}
?>
<div data-product-results>
	<?php if (empty($products)) { ?>
		<div class="app-empty-state">
			Chưa có sản phẩm nào.
		</div>
	<?php } else { ?>
		<div class="space-y-2" data-infinite-list data-infinite-url="<?php echo $basePath; ?>/product" data-infinite-query="<?php echo htmlspecialchars($queryString); ?>" data-current-page="<?php echo isset($page) ? (int) $page : 1; ?>" data-has-more="<?php echo isset($totalPages) && isset($page) && $page < $totalPages ? '1' : '0'; ?>">
		<?php foreach ($products as $index => $product) { ?>
			<?php
			$productId = isset($product['id']) ? (int) $product['id'] : 0;
			$unitsForProduct = isset($productUnitsByProduct[$productId]) && is_array($productUnitsByProduct[$productId]) ? $productUnitsByProduct[$productId] : [];
			$primaryPrice = null;
			$primaryCost = null;
			$primaryUnit = '';
            $baseUnitId = isset($product['base_unit_id']) ? (int) $product['base_unit_id'] : 0;
			if (!empty($unitsForProduct)) {
                if ($baseUnitId > 0) {
                    foreach ($unitsForProduct as $u) {
                        $price = isset($u['price_sell']) ? (float) $u['price_sell'] : 0;
                        if ($price <= 0) {
                            continue;
                        }
                        if (isset($u['unit_id']) && (int) $u['unit_id'] === $baseUnitId) {
                            $primaryPrice = $price;
                            $primaryCost = isset($u['price_cost']) ? (float) $u['price_cost'] : null;
                            $primaryUnit = isset($u['unit_name']) ? $u['unit_name'] : '';
                            break;
                        }
                    }
                }
				if ($primaryPrice === null) {
					foreach ($unitsForProduct as $u) {
						$price = isset($u['price_sell']) ? (float) $u['price_sell'] : 0;
						if ($price <= 0) {
							continue;
						}
						$primaryPrice = $price;
						$primaryCost = isset($u['price_cost']) ? (float) $u['price_cost'] : null;
						$primaryUnit = isset($u['unit_name']) ? $u['unit_name'] : '';
						break;
					}
				}
			}
			$stockLabel = null;
			$soldLabel = null;
            $statusLabel = null;
            $statusClass = '';
			$stockQtyRaw = null;
			$inStock = null;
			$unitLabel = isset($product['base_unit_name']) ? $product['base_unit_name'] : '';
			if (isset($product['inventory_qty_base'])) {
				$stockQtyRaw = $product['inventory_qty_base'];
			} elseif (isset($product['inventory_qty'])) {
				$stockQtyRaw = $product['inventory_qty'];
			} elseif (isset($product['stock_qty'])) {
				$stockQtyRaw = $product['stock_qty'];
			}
			if ($stockQtyRaw !== null && $stockQtyRaw !== '') {
				$qty = (float) $stockQtyRaw;
				if ($qty < 0) {
					$qty = 0;
				}
				$qtyText = rtrim(rtrim(number_format($qty, 2, ',', '.'), '0'), ',');
				$stockLabel = 'Kho: ' . $qtyText . ($unitLabel !== '' ? ' ' . $unitLabel : '');
				$inStock = $qty > 0;

                $minStock = null;
                if (isset($product['min_stock_qty']) && $product['min_stock_qty'] !== null) {
                    $minStock = (float) $product['min_stock_qty'];
                    if ($minStock < 0) {
                        $minStock = 0;
                    }
                }

				if ($qty <= 0) {
					$statusLabel = 'Hết hàng';
					$statusClass = 'app-status-danger';
				} elseif ($minStock !== null && $minStock > 0 && $qty <= $minStock) {
					$statusLabel = 'Tồn thấp';
					$statusClass = 'app-status-warning';
				} else {
					$statusLabel = 'Còn hàng';
					$statusClass = 'app-status-success';
				}
			}

			if (isset($product['sold_qty'])) {
				$soldQty = (float) $product['sold_qty'];
				if ($soldQty > 0) {
					$soldText = rtrim(rtrim(number_format($soldQty, 2, ',', '.'), '0'), ',');
					if ($soldText === '') {
						$soldText = '0';
					}
					$soldLabel = 'Bán: ' . $soldText . ($unitLabel !== '' ? ' ' . $unitLabel : '');
				}
			}
			?>
			<div class="app-list-card relative cursor-pointer" data-product-edit-row data-url="<?php echo $basePath; ?>/product/edit?id=<?php echo $product['id']; ?>" data-infinite-item>
				<div class="flex items-center gap-2.5">
					<!-- Đã bỏ phần hiển thị ảnh sản phẩm ở danh sách -->
					<div class="flex-1 min-w-0">
						<div class="flex items-center justify-between gap-x-2">
							<div class="truncate text-sm font-medium text-slate-900"><?php echo htmlspecialchars($product['name']); ?></div>
							<?php if ($statusLabel !== null && $statusClass !== '') { ?>
								<span class="app-status-chip flex-none <?php echo $statusClass; ?>"><?php echo $statusLabel; ?></span>
							<?php } ?>
						</div>
						<div class="mt-0.5 flex items-center gap-1 text-sm text-slate-500 truncate">
							<?php if (!empty($product['code'])) { ?>
								<span><?php echo htmlspecialchars($product['code']); ?></span>
							<?php } ?>
							<?php if (!empty($product['category_name'])) { ?>
								<span class="text-slate-300">·</span>
								<span class="truncate"><?php echo htmlspecialchars($product['category_name']); ?></span>
							<?php } ?>
							<?php if ($stockLabel !== null) { ?>
								<span class="text-slate-300">·</span>
								<span><?php echo htmlspecialchars($stockLabel); ?></span>
							<?php } ?>
						</div>
						<div class="mt-0.5 flex items-center gap-x-2 text-sm">
							<?php if ($primaryPrice !== null) { ?>
								<span class="font-semibold text-brand-600"><?php echo Money::format($primaryPrice); ?><?php if ($primaryUnit !== '') { ?>/<?php echo htmlspecialchars($primaryUnit); ?><?php } ?></span>
								<?php if ($primaryCost !== null && $primaryCost > 0) { ?>
									<span class="text-slate-500"><?php echo Money::format($primaryCost); ?><?php if ($primaryUnit !== '') { ?>/<?php echo htmlspecialchars($primaryUnit); ?><?php } ?></span>
								<?php } ?>
							<?php } else { ?>
								<span class="text-slate-500">Chưa có giá</span>
							<?php } ?>
						</div>
					</div>
				</div>
			</div>
		<?php } ?>
		</div>
	<?php } ?>
</div>

<a
	href="<?php echo $basePath; ?>/product/create"
			class="fixed bottom-[5rem] right-3 z-20 inline-flex h-10 w-10 items-center justify-center rounded-lg bg-brand-600 text-white md:hidden"
	title="Thêm sản phẩm"
	aria-label="Thêm sản phẩm"
>
	<?php echo ui_icon("plus", "h-5 w-5"); ?>
</a>

<?php if (!empty($categoryList)) { ?>
<div class="app-modal-overlay hidden" data-product-category-filter-root>
	<div class="app-modal-sheet-sm">
		<div class="app-modal-header">
			<div class="app-modal-title">Lọc theo danh mục</div>
			<button type="button" class="app-modal-close" data-product-category-filter-close>
				<?php echo ui_icon("x-mark", "h-4 w-4"); ?>
			</button>
		</div>
		<div class="app-modal-body space-y-2">
			<?php
			$isAllSelected = $categoryId <= 0;
			?>
			<button type="button" class="flex w-full items-center justify-between rounded-lg border px-3 py-2 text-sm <?php echo $isAllSelected ? 'border-brand-500 text-brand-700' : 'border-slate-200 text-slate-700 hover:bg-slate-50'; ?>" data-product-category-option data-category-id="">
				<span>Tất cả danh mục</span>
				<?php echo ui_icon("check", "h-4 w-4" . ($isAllSelected ? '' : ' hidden'), ['data-product-category-check' => '1']); ?>
			</button>
			<?php foreach ($categoryList as $cat) { ?>
				<?php
				if (!isset($cat['id'])) {
					continue;
				}
				$id = (int) $cat['id'];
				if ($id <= 0) {
					continue;
				}
				$name = isset($cat['name']) ? $cat['name'] : '';
				$selected = $categoryId > 0 && $categoryId === $id;
				?>
				<button type="button" class="flex w-full items-center justify-between rounded-lg border px-3 py-2 text-sm <?php echo $selected ? 'border-brand-500 text-brand-700' : 'border-slate-200 text-slate-700 hover:bg-slate-50'; ?>" data-product-category-option data-category-id="<?php echo $id; ?>">
					<span class="truncate"><?php echo htmlspecialchars($name); ?></span>
					<?php echo ui_icon("check", "h-4 w-4" . ($selected ? '' : ' hidden'), ['data-product-category-check' => '1']); ?>
				</button>
			<?php } ?>
		</div>
	</div>
</div>
<?php } ?>

<script>
document.addEventListener('DOMContentLoaded', function () {
	var form = document.querySelector('form[data-product-filter-form]');
	if (!form) return;
	var resultRoot = document.querySelector('[data-product-results]');
	if (!resultRoot) return;

	var searchInput = form.querySelector('input[name="q"]');
	var searchClear = form.querySelector('[data-list-search-clear]');
	var debounceTimer = null;
	var pendingController = null;
	var latestRequestId = 0;
	var isComposing = false;

	function setStockActiveState(value) {
		var selectedValue = value || 'all';
		form.querySelectorAll('[data-product-stock-filter]').forEach(function (btn) {
			var isActive = (btn.getAttribute('data-product-stock-filter') || 'all') === selectedValue;
			btn.classList.toggle('bg-brand-600', isActive);
			btn.classList.toggle('text-white', isActive);
			btn.classList.toggle('border-brand-600', isActive);
			btn.classList.toggle('bg-white', !isActive);
			btn.classList.toggle('text-slate-700', !isActive);
			btn.classList.toggle('border-slate-200', !isActive);
		});
	}

	function setCategoryActiveState(value) {
		var selectedValue = value || '';
		document.querySelectorAll('[data-product-category-option]').forEach(function (btn) {
			var isActive = (btn.getAttribute('data-category-id') || '') === selectedValue;
			btn.classList.toggle('border-brand-500', isActive);
			btn.classList.toggle('text-brand-700', isActive);
			btn.classList.toggle('border-slate-200', !isActive);
			btn.classList.toggle('text-slate-700', !isActive);
			btn.classList.toggle('hover:bg-slate-50', !isActive);

			var icon = btn.querySelector('[data-product-category-check]');
			if (icon) {
				icon.classList.toggle('hidden', !isActive);
			}
		});
	}

	function updateSearchClearVisibility() {
		if (!searchClear || !searchInput) {
			return;
		}
		var hasKeyword = (searchInput.value || '').trim() !== '';
		searchClear.classList.toggle('hidden', !hasKeyword);
	}

	function updateClearFiltersVisibility() {
		var clearBtn = form.querySelector('[data-product-clear-filters]');
		if (!clearBtn) {
			return;
		}
		var stockInput = form.querySelector('input[name="stock"]');
		var categoryInput = form.querySelector('input[name="category_id"]');
		var hasStockFilter = !!(stockInput && (stockInput.value || '') !== '' && (stockInput.value || '') !== 'all');
		var hasCategoryFilter = !!(categoryInput && (categoryInput.value || '') !== '');
		clearBtn.classList.toggle('hidden', !(hasStockFilter || hasCategoryFilter));
	}

	function setHiddenField(name, value) {
		var input = form.querySelector('input[name="' + name + '"]');
		if (!input) {
			input = document.createElement('input');
			input.type = 'hidden';
			input.name = name;
			form.appendChild(input);
		}
		input.value = value;
	}

	function removeField(name) {
		form.querySelectorAll('input[name="' + name + '"]').forEach(function (input) {
			input.remove();
		});
	}

	function buildRequestUrl() {
		var formData = new FormData(form);
		formData.delete('page');
		formData.delete('ajax');

		var params = new URLSearchParams();
		formData.forEach(function (value, key) {
			params.append(key, value == null ? '' : String(value));
		});

		var action = (form.getAttribute('action') || '').trim();
		var baseUrl = action !== '' ? action : window.location.pathname;
		params.append('ajax', '1');
		var query = params.toString();
		if (!query) {
			return baseUrl;
		}
		return baseUrl + (baseUrl.indexOf('?') === -1 ? '?' : '&') + query;
	}

	function syncBrowserUrl() {
		var formData = new FormData(form);
		formData.delete('page');
		formData.delete('ajax');
		var params = new URLSearchParams();
		formData.forEach(function (value, key) {
			params.append(key, value == null ? '' : String(value));
		});
		var path = window.location.pathname;
		var query = params.toString();
		var nextUrl = query ? (path + '?' + query) : path;
		window.history.replaceState(null, '', nextUrl);
	}

	function renderAjaxResults(html) {
		var parser = new DOMParser();
		var doc = parser.parseFromString(html, 'text/html');
		var nextResultRoot = doc.querySelector('[data-product-results]');
		if (!nextResultRoot) {
			return false;
		}
		resultRoot.innerHTML = nextResultRoot.innerHTML;
		if (typeof window.APP_initInfiniteScroll === 'function') {
			window.APP_initInfiniteScroll();
		}
		return true;
	}

	function requestResults() {
		if (pendingController) {
			pendingController.abort();
		}
		pendingController = new AbortController();
		latestRequestId++;
		var requestId = latestRequestId;
		var url = buildRequestUrl();

		fetch(url, {
			method: 'GET',
			headers: {
				'X-Requested-With': 'XMLHttpRequest'
			},
			signal: pendingController.signal
		}).then(function (response) {
			if (!response.ok) {
				throw new Error('Search request failed');
			}
			return response.text();
		}).then(function (html) {
			if (requestId !== latestRequestId) {
				return;
			}
			if (renderAjaxResults(html)) {
				syncBrowserUrl();
			}
		}).catch(function (error) {
			if (error && error.name === 'AbortError') {
				return;
			}
		}).finally(function () {
			if (requestId === latestRequestId) {
				pendingController = null;
			}
		});
	}

	function triggerDebouncedRequest() {
		updateSearchClearVisibility();
		if (debounceTimer) {
			clearTimeout(debounceTimer);
		}
		debounceTimer = setTimeout(function () {
			requestResults();
		}, 300);
	}

	form.addEventListener('submit', function (e) {
		e.preventDefault();
		requestResults();
	});

	if (searchInput) {
		updateSearchClearVisibility();
		searchInput.addEventListener('compositionstart', function () {
			isComposing = true;
		});
		searchInput.addEventListener('compositionend', function () {
			isComposing = false;
			triggerDebouncedRequest();
		});
		searchInput.addEventListener('input', function () {
			if (isComposing) {
				return;
			}
			triggerDebouncedRequest();
		});
		searchInput.addEventListener('keydown', function (e) {
			if (e.key !== 'Enter') {
				return;
			}
			e.preventDefault();
			requestResults();
		});
	}

	if (searchClear) {
		searchClear.addEventListener('click', function (e) {
			e.preventDefault();
			if (searchInput) {
				searchInput.value = '';
			}
			updateSearchClearVisibility();
			triggerDebouncedRequest();
		});
	}

	var stockInput = form.querySelector('input[name="stock"]');
	setStockActiveState(stockInput && stockInput.value ? stockInput.value : 'all');
	var categoryInput = form.querySelector('input[name="category_id"]');
	setCategoryActiveState(categoryInput && categoryInput.value ? categoryInput.value : '');
	updateClearFiltersVisibility();

    form.querySelectorAll('[data-product-stock-filter]').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            var value = btn.getAttribute('data-product-stock-filter') || 'all';
            setHiddenField('stock', value);
			setStockActiveState(value);
			updateClearFiltersVisibility();
            requestResults();
        });
    });

	var clearFiltersBtn = form.querySelector('[data-product-clear-filters]');
	if (clearFiltersBtn) {
		clearFiltersBtn.addEventListener('click', function (e) {
			e.preventDefault();
			setHiddenField('stock', 'all');
			removeField('category_id');
			setStockActiveState('all');
			setCategoryActiveState('');
			updateClearFiltersVisibility();
			requestResults();
		});
	}

	var root = document.querySelector('[data-product-category-filter-root]');
	if (!root) {
		return;
	}
	var btnOpen = document.querySelector('[data-product-category-filter-open]');
	function openPopup() {
		if (typeof window.APP_openModal === 'function') {
			window.APP_openModal(root);
			return;
		}
		root.classList.remove('hidden');
		root.classList.add('flex');
		document.body.classList.add('overflow-hidden');
	}

	function closePopup() {
		if (typeof window.APP_closeModal === 'function') {
			window.APP_closeModal(root);
			return;
		}
		root.classList.add('hidden');
		root.classList.remove('flex');
		document.body.classList.remove('overflow-hidden');
	}

	if (btnOpen) {
		btnOpen.addEventListener('click', function (e) {
			e.preventDefault();
			openPopup();
		});
	}
	root.addEventListener('click', function (e) {
		if (e.target !== root) return;
		closePopup();
	});
	root.querySelectorAll('[data-product-category-filter-close]').forEach(function (btn) {
		btn.addEventListener('click', function (e) {
			e.preventDefault();
			closePopup();
		});
	});
	root.querySelectorAll('[data-product-category-option]').forEach(function (btn) {
		btn.addEventListener('click', function (e) {
			e.preventDefault();
			var id = btn.getAttribute('data-category-id') || '';
			if (id === '') {
				removeField('category_id');
			} else {
				setHiddenField('category_id', id);
			}
			setCategoryActiveState(id);
			updateClearFiltersVisibility();
			closePopup();
			requestResults();
		});
	});
	document.addEventListener('keydown', function (e) {
		if (root.classList.contains('hidden')) return;
		if (e.key === 'Escape') {
			e.preventDefault();
			closePopup();
		}
	});
});
</script>
