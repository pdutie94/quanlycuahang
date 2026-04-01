<?php

class CategoryController extends Controller
{
    public function index()
    {
        $this->requireLogin();

        $listData = CategoryService::getCategoryListData($_GET, 20);
        $categories = $listData['categories'];
        $page = $listData['page'];
        $totalPages = $listData['totalPages'];

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

        $this->render('categories/form', CategoryService::getCategoryFormData());
    }

    public function store()
    {
        $this->requireLogin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('category');
        }

        $this->verifyCsrfToken();

        $result = CategoryService::createCategory($_POST);
        if (!empty($result['message'])) {
            $this->setFlash($result['success'] ? 'success' : 'error', $result['message']);
        }
        $this->redirect(isset($result['redirect']) ? $result['redirect'] : 'category');
    }

    public function edit()
    {
        $this->requireLogin();

        $result = CategoryService::getCategoryFormData(isset($_GET['id']) ? $_GET['id'] : 0);
        if (empty($result['success'])) {
            $this->redirect(isset($result['redirect']) ? $result['redirect'] : 'category');
        }

        $this->render('categories/form', $result);
    }

    public function update()
    {
        $this->requireLogin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('category');
        }

        $this->verifyCsrfToken();

        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        if ($id <= 0) {
            $this->redirect('category');
        }

        $result = CategoryService::updateCategory($id, $_POST);
        if (!empty($result['message'])) {
            $this->setFlash($result['success'] ? 'success' : 'error', $result['message']);
        }
        $this->redirect(isset($result['redirect']) ? $result['redirect'] : 'category');
    }

    public function delete()
    {
        $this->requireLogin();

        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

        $result = CategoryService::deleteCategory($id);
        if (!empty($result['message'])) {
            $this->setFlash($result['success'] ? 'success' : 'error', $result['message']);
        }
        $this->redirect(isset($result['redirect']) ? $result['redirect'] : 'category');
    }
}
