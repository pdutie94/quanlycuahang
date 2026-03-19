<?php

class ProductController extends Controller
{
    public function index()
    {
        $this->requireLogin();
		$keyword = isset($_GET['q']) ? trim($_GET['q']) : '';
		$stockFilter = isset($_GET['stock']) ? $_GET['stock'] : 'all';
		if (!in_array($stockFilter, ['all', 'in_stock', 'low_stock', 'out_of_stock'], true)) {
			$stockFilter = 'all';
		}
		$categoryId = isset($_GET['category_id']) ? (int) $_GET['category_id'] : 0;
		if ($categoryId <= 0) {
			$categoryId = null;
		}

        $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
        if ($page < 1) {
            $page = 1;
        }

		$perPage = 20;
		if ($keyword !== '') {
			$totalCount = Product::countByKeyword($keyword, $stockFilter, $categoryId);
		} else {
			$totalCount = Product::countAll($stockFilter, $categoryId);
		}
        $totalPages = (int) ceil($totalCount / $perPage);
        if ($totalPages < 1) {
            $totalPages = 1;
        }
        if ($page > $totalPages) {
            $page = $totalPages;
        }

		$offset = ($page - 1) * $perPage;
		if ($keyword !== '') {
			$products = Product::searchPaginate($keyword, $perPage, $offset, $stockFilter, $categoryId);
		} else {
			$products = Product::paginate($perPage, $offset, $stockFilter, $categoryId);
		}


        $productUnitsByProduct = [];
        if (!empty($products)) {
            $productIds = [];
            foreach ($products as $product) {
                $productId = isset($product['id']) ? (int) $product['id'] : 0;
                if ($productId > 0) {
                    $productIds[] = $productId;
                }
            }
            $productIds = array_values(array_unique($productIds));

            if (!empty($productIds)) {
                $pdo = Database::getInstance();
                $placeholders = implode(',', array_fill(0, count($productIds), '?'));
                $sql = 'SELECT pu.*, u.name AS unit_name
                    FROM product_units pu
                    JOIN units u ON pu.unit_id = u.id
                    WHERE pu.product_id IN (' . $placeholders . ')
                    ORDER BY pu.product_id, pu.id';
                $stmt = $pdo->prepare($sql);
                foreach ($productIds as $index => $productId) {
                    $stmt->bindValue($index + 1, $productId, PDO::PARAM_INT);
                }
                $stmt->execute();
                $unitRows = $stmt->fetchAll();

                foreach ($unitRows as $row) {
                    $pid = isset($row['product_id']) ? (int) $row['product_id'] : 0;
                    if ($pid <= 0) {
                        continue;
                    }
                    if (!isset($productUnitsByProduct[$pid])) {
                        $productUnitsByProduct[$pid] = [];
                    }
                    $productUnitsByProduct[$pid][] = $row;
                }
            }
        }

		$categories = [];
		if (class_exists('ProductCategory')) {
			$categories = ProductCategory::all();
		}

        $this->render('products/index', [
            'title' => 'Sản phẩm',
            'products' => $products,
            'keyword' => $keyword,
			'stockFilter' => $stockFilter,
            'page' => $page,
            'totalPages' => $totalPages,
            'productUnitsByProduct' => $productUnitsByProduct,
			'categories' => $categories,
			'categoryId' => $categoryId,
			'listHeader' => [
                'title' => 'Sản phẩm',
                'subtitle' => 'Quản lý danh sách sản phẩm đang bán.',
                'primary' => [
                    'url' => 'product/create',
                    'tooltip' => 'Thêm sản phẩm',
                ],
                'sticky' => true,
                'form' => [
                    'method' => 'get',
                    'action' => '',
                    'attrs' => [
                        'data-product-filter-form' => '1',
                    ],
                ],
                'search' => [
                    'param' => 'q',
                    'placeholder' => 'Tìm kiếm theo tên, SKU...',
                    'value' => $keyword,
                    'clear_url' => 'product',
                    'show_clear' => ($keyword !== '' || ($categoryId !== null && $categoryId > 0)),
                ],
                'hidden' => [
                    [
                        'name' => 'stock',
                        'value' => $stockFilter,
                    ],
                    [
                        'name' => 'category_id',
                        'value' => $categoryId !== null && $categoryId > 0 ? (int) $categoryId : '',
                    ],
                ],
                'extra_buttons' => [
                    [
                        'icon' => 'grid',
                        'attrs' => [
                            'data-product-category-filter-open' => '1',
                        ],
                    ],
                ],
                'chips' => [
                    'class' => 'mt-2 flex items-center gap-2 overflow-x-auto whitespace-nowrap text-sm',
                    'items' => [
                        [
                            'kind' => 'button',
                            'data_attr' => 'data-product-clear-filters',
                            'value' => '1',
                            'label' => 'Xóa lọc',
                            'aria_label' => 'Xóa bộ lọc',
                            'icon' => 'clear',
                            'icon_only' => true,
                            'active' => false,
                            'base_class' => 'border inline-flex items-center justify-center rounded-lg h-[30px] w-[30px] text-sm font-medium bg-rose-50 text-rose-700 border-rose-200 hover:bg-rose-100',
                            'active_class' => 'bg-rose-50 text-rose-700 border-rose-200',
                            'inactive_class' => 'bg-rose-50 text-rose-700 border-rose-200',
                        ],
                        [
                            'kind' => 'button',
                            'data_attr' => 'data-product-stock-filter',
                            'value' => 'all',
                            'label' => 'Tất cả',
                            'active' => $stockFilter === 'all',
                            'base_class' => 'border inline-flex items-center rounded-lg px-3 py-1 text-sm font-medium',
                            'active_class' => 'bg-brand-600 text-white border-brand-600',
                            'inactive_class' => 'bg-white text-slate-700 border-slate-200',
                        ],
                        [
                            'kind' => 'button',
                            'data_attr' => 'data-product-stock-filter',
                            'value' => 'in_stock',
                            'label' => 'Còn hàng',
                            'active' => $stockFilter === 'in_stock',
                            'base_class' => 'border inline-flex items-center rounded-lg px-3 py-1 text-sm font-medium',
                            'active_class' => 'bg-brand-600 text-white border-brand-600',
                            'inactive_class' => 'bg-white text-slate-700 border-slate-200',
                        ],
                        [
                            'kind' => 'button',
                            'data_attr' => 'data-product-stock-filter',
                            'value' => 'low_stock',
                            'label' => 'Tồn thấp',
                            'active' => $stockFilter === 'low_stock',
                            'base_class' => 'border inline-flex items-center rounded-lg px-3 py-1 text-sm font-medium',
                            'active_class' => 'bg-brand-600 text-white border-brand-600',
                            'inactive_class' => 'bg-white text-slate-700 border-slate-200',
                        ],
                        [
                            'kind' => 'button',
                            'data_attr' => 'data-product-stock-filter',
                            'value' => 'out_of_stock',
                            'label' => 'Hết hàng',
                            'active' => $stockFilter === 'out_of_stock',
                            'base_class' => 'border inline-flex items-center rounded-lg px-3 py-1 text-sm font-medium',
                            'active_class' => 'bg-brand-600 text-white border-brand-600',
                            'inactive_class' => 'bg-white text-slate-700 border-slate-200',
                        ],
                    ],
                ],
            ],
        ]);
    }

    public function create()
    {
        $this->requireLogin();
        $units = Unit::all();
        $categories = [];
        if (class_exists('ProductCategory')) {
            $categories = ProductCategory::all();
        }
        $this->render('products/form', [
            'title' => 'Thêm sản phẩm',
            'units' => $units,
            'product' => null,
            'productUnits' => [],
            'categories' => $categories,
            'inventoryQtyBase' => null,
            'productLogs' => [],
            'detailHeader' => [
                'title' => 'Thêm sản phẩm',
                'back_url' => 'product',
                'back_label' => 'Quay lại',
                'actions_view' => 'products/_detail_header_actions',
            ],
        ]);
    }

    public function store()
    {
        $this->requireLogin();

        $data = [
            'name' => isset($_POST['name']) ? trim($_POST['name']) : '',
            'code' => isset($_POST['code']) ? trim($_POST['code']) : '',
            'category_id' => isset($_POST['category_id']) && $_POST['category_id'] !== '' ? (int) $_POST['category_id'] : null,
            'base_unit_id' => isset($_POST['base_unit_id']) ? $_POST['base_unit_id'] : null,
        ];

        if (isset($_POST['min_stock_qty'])) {
            $minStockRaw = $_POST['min_stock_qty'];
            $minStockRaw = str_replace(' ', '', $minStockRaw);
            $minStockRaw = str_replace(',', '.', $minStockRaw);
            $minStock = (float) $minStockRaw;
            if ($minStock < 0) {
                $minStock = 0;
            }
            if ($minStock > 0) {
                $data['min_stock_qty'] = $minStock;
            } else {
                $data['min_stock_qty'] = null;
            }
        }

        $productId = Product::create($data);

        $redirectAction = isset($_POST['redirect']) ? $_POST['redirect'] : 'exit';

        $imagePath = $this->handleImageUpload(null);
        if ($imagePath) {
            Product::updateImagePath($productId, $imagePath);
        }

		$unitRows = [];
		$priceSellSingle = isset($_POST['price_sell_single']) ? $_POST['price_sell_single'] : '';
		$priceCostSingle = isset($_POST['price_cost_single']) ? $_POST['price_cost_single'] : '';
		$priceSellSingle = $this->normalizePrice($priceSellSingle);
		$priceCostSingle = $this->normalizePrice($priceCostSingle);
		$baseUnitId = isset($data['base_unit_id']) ? (int) $data['base_unit_id'] : 0;

        $allowFraction = isset($_POST['allow_fraction']) && $_POST['allow_fraction'] === '1' ? 1 : 0;
        $minStepRaw = isset($_POST['min_step']) ? $_POST['min_step'] : '1';
        $minStepRaw = str_replace(' ', '', $minStepRaw);
        $minStepRaw = str_replace(',', '.', $minStepRaw);
        $minStep = (float) $minStepRaw;
        if ($minStep <= 0) {
            $minStep = 1;
        }

		if ($baseUnitId > 0) {
			$unitRows[] = [
				'unit_id' => $baseUnitId,
				'factor' => 1,
				'price_sell' => $priceSellSingle,
				'price_cost' => $priceCostSingle,
                'allow_fraction' => $allowFraction,
                'min_step' => $minStep,
			];
		}

		ProductUnit::saveForProduct($productId, $unitRows);

        $qty = null;
        if (class_exists('Inventory') && isset($_POST['inventory_qty_base'])) {
			$qtyRaw = $_POST['inventory_qty_base'];
            $qtyRaw = str_replace(' ', '', $qtyRaw);
            $qtyRaw = str_replace(',', '.', $qtyRaw);
			$qty = (float) $qtyRaw;
			if ($qty < 0) {
				$qty = 0;
			}
			Inventory::setQtyBase($productId, $qty);
		}

        if (class_exists('ProductLog')) {
            if (!empty($unitRows)) {
                $priceSell = isset($unitRows[0]['price_sell']) ? (float) $unitRows[0]['price_sell'] : 0;
                $priceCost = isset($unitRows[0]['price_cost']) ? (float) $unitRows[0]['price_cost'] : 0;
                $allowFractionFlag = !empty($unitRows[0]['allow_fraction']) ? 1 : 0;
                $minStepValue = isset($unitRows[0]['min_step']) ? (float) $unitRows[0]['min_step'] : 1;

                $parts = [];
                if ($priceSell > 0) {
                    $parts[] = 'Giá bán: ' . Money::format($priceSell);
                }
                if ($priceCost > 0) {
                    $parts[] = 'Giá nhập: ' . Money::format($priceCost);
                }
                $parts[] = 'Bán lẻ: ' . ($allowFractionFlag ? 'Có' : 'Không');
                $minStepText = rtrim(rtrim(number_format($minStepValue, 4, ',', ''), '0'), ',');
                if ($minStepText === '') {
                    $minStepText = '1';
                }
                $parts[] = 'Bước lẻ: ' . $minStepText;

                $detail = implode('; ', $parts);
                ProductLog::create([
                    'product_id' => $productId,
                    'action' => 'init_price',
                    'detail' => $detail,
                ]);
            }

            if ($qty !== null) {
                $qtyValue = (float) $qty;
                if ($qtyValue < 0) {
                    $qtyValue = 0;
                }
                $qtyText = rtrim(rtrim(number_format($qtyValue, 4, ',', ''), '0'), ',');
                if ($qtyText === '') {
                    $qtyText = '0';
                }
                ProductLog::create([
                    'product_id' => $productId,
                    'action' => 'init_inventory',
                    'detail' => 'Tồn kho: ' . $qtyText,
                ]);
            }
		}

        $this->setFlash('success', 'Đã thêm sản phẩm.');
        if ($redirectAction === 'stay') {
            $this->redirect('product/edit?id=' . $productId);
        } else {
            $this->redirect('product');
        }
    }

    public function edit()
    {
        $this->requireLogin();
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        $product = Product::find($id);
        if (!$product) {
            $this->redirect('product');
        }
        $units = Unit::all();
        $productUnits = ProductUnit::findByProduct($id);
        $categories = [];
        if (class_exists('ProductCategory')) {
            $categories = ProductCategory::all();
        }

		$inventoryQtyBase = null;
		if (class_exists('Inventory')) {
			$inventoryQtyBase = Inventory::getQtyBase($id);
		}

        $productLogs = [];
        if (class_exists('ProductLog')) {
            $productLogs = ProductLog::findByProduct($id);
        }

        $this->render('products/form', [
            'title' => 'Sửa sản phẩm',
            'units' => $units,
            'product' => $product,
            'productUnits' => $productUnits,
            'categories' => $categories,
			'inventoryQtyBase' => $inventoryQtyBase,
            'productLogs' => $productLogs,
            'detailHeader' => [
                'title' => 'Sửa sản phẩm',
                'back_url' => 'product',
                'back_label' => 'Quay lại',
                'actions_view' => 'products/_detail_header_actions',
            ],
        ]);
    }

    public function update()
    {
        $this->requireLogin();
        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        $product = Product::find($id);
        if (!$product) {
			$this->redirect('product');
        }

		$inventoryOnly = isset($_POST['inventory_only']) && $_POST['inventory_only'] === '1';

        $oldPriceSell = null;
        $oldPriceCost = null;
        $oldAllowFraction = null;
        $oldMinStep = null;

        if (!$inventoryOnly && class_exists('ProductUnit')) {
            $oldUnits = ProductUnit::findByProduct($id);
            if (!empty($oldUnits) && is_array($oldUnits)) {
                $oldBaseUnitId = isset($product['base_unit_id']) ? (int) $product['base_unit_id'] : 0;
                $oldUnitRow = null;
                foreach ($oldUnits as $row) {
                    if ($oldBaseUnitId > 0 && isset($row['unit_id']) && (int) $row['unit_id'] === $oldBaseUnitId) {
                        $oldUnitRow = $row;
                        break;
                    }
                    if ($oldUnitRow === null && isset($row['factor']) && (float) $row['factor'] === 1.0) {
                        $oldUnitRow = $row;
                    }
                }
                if ($oldUnitRow !== null) {
                    if (isset($oldUnitRow['price_sell'])) {
                        $oldPriceSell = (float) $oldUnitRow['price_sell'];
                    }
                    if (isset($oldUnitRow['price_cost'])) {
                        $oldPriceCost = (float) $oldUnitRow['price_cost'];
                    }
                    if (isset($oldUnitRow['allow_fraction'])) {
                        $oldAllowFraction = (int) $oldUnitRow['allow_fraction'] ? 1 : 0;
                    }
                    if (isset($oldUnitRow['min_step']) && $oldUnitRow['min_step'] !== null) {
                        $oldMinStep = (float) $oldUnitRow['min_step'];
                    }
                }
            }
        }

		if (!$inventoryOnly) {
			$data = [
				'name' => isset($_POST['name']) ? trim($_POST['name']) : '',
				'code' => isset($_POST['code']) ? trim($_POST['code']) : '',
				'category_id' => isset($_POST['category_id']) && $_POST['category_id'] !== '' ? (int) $_POST['category_id'] : null,
				'base_unit_id' => isset($_POST['base_unit_id']) ? $_POST['base_unit_id'] : null,
			];

            if (isset($_POST['min_stock_qty'])) {
                $minStockRaw = $_POST['min_stock_qty'];
                $minStockRaw = str_replace(' ', '', $minStockRaw);
                $minStockRaw = str_replace(',', '.', $minStockRaw);
                $minStock = (float) $minStockRaw;
                if ($minStock < 0) {
                    $minStock = 0;
                }
                if ($minStock > 0) {
                    $data['min_stock_qty'] = $minStock;
                } else {
                    $data['min_stock_qty'] = null;
                }
            }

			Product::update($id, $data);

			$currentImagePath = isset($product['image_path']) ? $product['image_path'] : null;
			$removeImage = isset($_POST['image_remove']) && $_POST['image_remove'] === '1';

			$imagePath = $this->handleImageUpload($currentImagePath);
			if ($imagePath && $imagePath !== $currentImagePath) {
				Product::updateImagePath($id, $imagePath);
			} elseif ($removeImage && $currentImagePath) {
				$publicDir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public';
				$oldPath = $publicDir . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $currentImagePath);
				if (is_file($oldPath)) {
					@unlink($oldPath);
				}
				Product::updateImagePath($id, null);
			}

			$unitRows = [];
			$priceSellSingle = isset($_POST['price_sell_single']) ? $_POST['price_sell_single'] : '';
			$priceCostSingle = isset($_POST['price_cost_single']) ? $_POST['price_cost_single'] : '';
			$priceSellSingle = Money::parsePrice($priceSellSingle);
			$priceCostSingle = Money::parsePrice($priceCostSingle);
			$baseUnitId = isset($data['base_unit_id']) ? (int) $data['base_unit_id'] : 0;

            $allowFraction = isset($_POST['allow_fraction']) && $_POST['allow_fraction'] === '1' ? 1 : 0;
            $minStepRaw = isset($_POST['min_step']) ? $_POST['min_step'] : '1';
            $minStepRaw = str_replace(' ', '', $minStepRaw);
            $minStepRaw = str_replace(',', '.', $minStepRaw);
            $minStep = (float) $minStepRaw;
            if ($minStep <= 0) {
                $minStep = 1;
            }

			if ($baseUnitId > 0) {
				$unitRows[] = [
					'unit_id' => $baseUnitId,
					'factor' => 1,
					'price_sell' => $priceSellSingle,
					'price_cost' => $priceCostSingle,
                    'allow_fraction' => $allowFraction,
                    'min_step' => $minStep,
				];
			}

			ProductUnit::saveForProduct($id, $unitRows);

            if (class_exists('ProductLog') && !empty($unitRows)) {
                $changes = [];

                $newPriceSellValue = isset($unitRows[0]['price_sell']) ? (float) $unitRows[0]['price_sell'] : null;
                $newPriceCostValue = isset($unitRows[0]['price_cost']) ? (float) $unitRows[0]['price_cost'] : null;
                $newAllowFraction = isset($unitRows[0]['allow_fraction']) && $unitRows[0]['allow_fraction'] ? 1 : 0;
                $newMinStepValue = isset($unitRows[0]['min_step']) ? (float) $unitRows[0]['min_step'] : null;

                if ($oldPriceSell !== null && $newPriceSellValue !== null) {
                    if (abs($newPriceSellValue - $oldPriceSell) > 0.0001) {
                        $fromText = Money::format($oldPriceSell);
                        $toText = Money::format($newPriceSellValue);
                        $changes[] = 'Giá bán: ' . $fromText . ' -> ' . $toText;
                    }
                }

                if ($oldPriceCost !== null && $newPriceCostValue !== null) {
                    if (abs($newPriceCostValue - $oldPriceCost) > 0.0001) {
                        $fromText = Money::format($oldPriceCost);
                        $toText = Money::format($newPriceCostValue);
                        $changes[] = 'Giá nhập: ' . $fromText . ' -> ' . $toText;
                    }
                }

                if ($oldAllowFraction !== null) {
                    if ($newAllowFraction !== (int) $oldAllowFraction) {
                        $fromText = $oldAllowFraction ? 'Có' : 'Không';
                        $toText = $newAllowFraction ? 'Có' : 'Không';
                        $changes[] = 'Bán lẻ: ' . $fromText . ' -> ' . $toText;
                    }
                }

                if ($oldMinStep !== null && $newMinStepValue !== null) {
                    if (abs($newMinStepValue - $oldMinStep) > 0.000001) {
                        $fromText = rtrim(rtrim(number_format($oldMinStep, 4, ',', ''), '0'), ',');
                        if ($fromText === '') {
                            $fromText = '1';
                        }
                        $toText = rtrim(rtrim(number_format($newMinStepValue, 4, ',', ''), '0'), ',');
                        if ($toText === '') {
                            $toText = '1';
                        }
                        $changes[] = 'Bước lẻ: ' . $fromText . ' -> ' . $toText;
                    }
                }

                if (!empty($changes)) {
                    $detail = implode('; ', $changes);
                    ProductLog::create([
                        'product_id' => $id,
                        'action' => 'update_price',
                        'detail' => $detail,
                    ]);
                }
            }
		}

		if (class_exists('Inventory') && isset($_POST['inventory_qty_base'])) {
			$qtyRaw = $_POST['inventory_qty_base'];
            $qtyRaw = str_replace(' ', '', $qtyRaw);
            $qtyRaw = str_replace(',', '.', $qtyRaw);
			$qty = (float) $qtyRaw;
			if ($qty < 0) {
				$qty = 0;
			}
			Inventory::setQtyBase($id, $qty);
		}

		if ($inventoryOnly) {
			$this->setFlash('success', 'Đã cập nhật tồn kho sản phẩm.');
			$this->redirect('product/edit?id=' . $id);
		}

		$this->setFlash('success', 'Đã cập nhật sản phẩm.');
		$redirectAction = isset($_POST['redirect']) ? $_POST['redirect'] : 'exit';
		if ($redirectAction === 'stay') {
			$this->redirect('product/edit?id=' . $id);
		} else {
			$this->redirect('product');
		}
    }

    public function delete()
    {
        $this->requireLogin();
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if ($id) {
			$deleted = Product::delete($id);
			if ($deleted) {
				$this->setFlash('success', 'Đã xóa sản phẩm.');
			} else {
				$this->setFlash('error', 'Không thể xóa sản phẩm vì đã có đơn hàng sử dụng.');
			}
        }
        $this->redirect('product');
    }

	protected function handleImageUpload($currentPath)
    {
        if (!isset($_FILES['image']) || !is_array($_FILES['image'])) {
            return $currentPath;
        }

        if ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            return $currentPath;
        }

        $tmpName = $_FILES['image']['tmp_name'];
        if (!is_uploaded_file($tmpName)) {
            return $currentPath;
        }

        $originalName = $_FILES['image']['name'];
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (!in_array($extension, $allowed, true)) {
            return $currentPath;
        }

        $publicDir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public';
        $uploadDir = $publicDir . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'products';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $filename = uniqid('product_', true) . '.' . $extension;
        $targetPath = $uploadDir . DIRECTORY_SEPARATOR . $filename;

        if (!move_uploaded_file($tmpName, $targetPath)) {
            return $currentPath;
        }

        if ($currentPath) {
            $oldPath = $publicDir . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $currentPath);
            if (is_file($oldPath)) {
                @unlink($oldPath);
            }
        }

		return 'uploads/products/' . $filename;
	}

	protected function normalizePrice($value)
	{
		return Money::parsePrice($value);
	}
}
