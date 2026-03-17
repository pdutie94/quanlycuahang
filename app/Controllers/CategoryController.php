<?php

class CategoryController extends Controller
{
    public function index()
    {
        $this->requireLogin();

        $categories = [];
        $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
        if ($page < 1) {
            $page = 1;
        }

        $perPage = 20;
        $totalPages = 1;

        if (class_exists('ProductCategory')) {
            $totalCount = ProductCategory::countAll();
            $totalPages = (int) ceil($totalCount / $perPage);
            if ($totalPages < 1) {
                $totalPages = 1;
            }
            if ($page > $totalPages) {
                $page = $totalPages;
            }

            $offset = ($page - 1) * $perPage;
            $categories = ProductCategory::paginate($perPage, $offset);
        }

        $this->render('categories/index', [
            'title' => 'Danh mục sản phẩm',
            'categories' => $categories,
            'page' => $page,
            'totalPages' => $totalPages,
        ]);
    }

    public function create()
    {
        $this->requireLogin();

        $categories = [];
        if (class_exists('ProductCategory')) {
            $categories = ProductCategory::all();
        }

        $this->render('categories/form', [
            'title' => 'Thêm danh mục',
            'category' => null,
            'categories' => $categories,
        ]);
    }

    public function store()
    {
        $this->requireLogin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('category');
        }

        $this->verifyCsrfToken();

        $name = isset($_POST['name']) ? trim($_POST['name']) : '';

        if ($name === '') {
            $this->setFlash('error', 'Tên danh mục là bắt buộc.');
            $this->redirect('category');
        }

        if (class_exists('ProductCategory')) {
            ProductCategory::create([
                'name' => $name,
            ]);
        }

        $this->setFlash('success', 'Đã thêm danh mục sản phẩm.');
        $this->redirect('category');
    }

    public function edit()
    {
        $this->requireLogin();

        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if ($id <= 0 || !class_exists('ProductCategory')) {
            $this->redirect('category');
        }

        $category = ProductCategory::find($id);
        if (!$category) {
            $this->redirect('category');
        }

        $categories = ProductCategory::all();

        $this->render('categories/form', [
            'title' => 'Sửa danh mục',
            'category' => $category,
            'categories' => $categories,
        ]);
    }

    public function update()
    {
        $this->requireLogin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('category');
        }

        $this->verifyCsrfToken();

        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        if ($id <= 0 || !class_exists('ProductCategory')) {
            $this->redirect('category');
        }

        $category = ProductCategory::find($id);
        if (!$category) {
            $this->redirect('category');
        }

        $name = isset($_POST['name']) ? trim($_POST['name']) : '';

        if ($name === '') {
            $this->setFlash('error', 'Tên danh mục là bắt buộc.');
            $this->redirect('category/edit?id=' . $id);
        }

        ProductCategory::update($id, [
            'name' => $name,
        ]);

        $this->setFlash('success', 'Đã cập nhật danh mục sản phẩm.');
        $this->redirect('category');
    }

    public function delete()
    {
        $this->requireLogin();

        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if ($id <= 0 || !class_exists('ProductCategory')) {
            $this->redirect('category');
        }

        if ($id === 1) {
            $this->setFlash('error', 'Không thể xóa danh mục mặc định.');
            $this->redirect('category');
        }

        $pdo = Database::getInstance();
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM products WHERE category_id = ? AND deleted_at IS NULL');
        $stmt->execute([$id]);
        $usageCount = (int) $stmt->fetchColumn();

        if ($usageCount > 0) {
            $this->setFlash('error', 'Không thể xóa danh mục vì đang có sản phẩm sử dụng.');
            $this->redirect('category');
        }

        ProductCategory::delete($id);
        $this->setFlash('success', 'Đã xóa danh mục sản phẩm.');
        $this->redirect('category');
    }
}
