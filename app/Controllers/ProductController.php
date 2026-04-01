<?php

class ProductController extends Controller
{
    public function index()
    {
        $this->requireLogin();

        $listData = ProductService::getProductListData($_GET, 20);
        $products = $listData['products'];
        $keyword = $listData['keyword'];
        $stockFilter = $listData['stockFilter'];
        $categoryId = $listData['categoryId'];
        $page = $listData['page'];
        $totalPages = $listData['totalPages'];
        $productUnitsByProduct = $listData['productUnitsByProduct'];
        $categories = $listData['categories'];

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
        $this->render('products/form', ProductService::getCreateFormData());
    }

    public function store()
    {
        $this->requireLogin();
        $imagePath = $this->handleImageUpload(null);

        $result = ProductService::createProduct($_POST, $imagePath);
        if (!empty($result['message'])) {
            $this->setFlash($result['success'] ? 'success' : 'error', $result['message']);
        }

        $this->redirect(isset($result['redirect']) ? $result['redirect'] : 'product');
    }

    public function edit()
    {
        $this->requireLogin();
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        $formData = ProductService::getEditFormData($id);
        if (empty($formData['success'])) {
            $this->redirect(isset($formData['redirect']) ? $formData['redirect'] : 'product');
        }

        $this->render('products/form', $formData);
    }

    public function update()
    {
        $this->requireLogin();
        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        if ($id <= 0) {
            $this->redirect('product');
        }

		$product = Product::find($id);
        if (!$product) {
			$this->redirect('product');
        }

		$inventoryOnly = isset($_POST['inventory_only']) && $_POST['inventory_only'] === '1';
        $imagePath = null;
        $updateImage = false;

		if (!$inventoryOnly) {
			$currentImagePath = isset($product['image_path']) ? $product['image_path'] : null;
			$removeImage = isset($_POST['image_remove']) && $_POST['image_remove'] === '1';
			$imagePath = $this->handleImageUpload($currentImagePath);

			if ($imagePath && $imagePath !== $currentImagePath) {
				$updateImage = true;
			} elseif ($removeImage && $currentImagePath) {
				$publicDir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public';
				$oldPath = $publicDir . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $currentImagePath);
				if (is_file($oldPath)) {
					@unlink($oldPath);
				}
				$imagePath = null;
				$updateImage = true;
			}
		}

		$result = ProductService::updateProduct($id, $_POST, [
			'imagePath' => $imagePath,
			'updateImage' => $updateImage,
		]);

		if (!empty($result['message'])) {
			$this->setFlash($result['success'] ? 'success' : 'error', $result['message']);
		}

		$this->redirect(isset($result['redirect']) ? $result['redirect'] : 'product');
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
