<?php

class SupplierController extends Controller
{
    public function view()
    {
        $this->requireLogin();

        $result = SupplierService::getSupplierViewData(isset($_GET['id']) ? $_GET['id'] : 0);
        if (empty($result['success'])) {
            $this->redirect(isset($result['redirect']) ? $result['redirect'] : 'supplier');
        }

        $this->render('suppliers/view', [
            'title' => 'Nhà cung cấp',
            'supplier' => $result['supplier'],
            'totalDebt' => $result['totalDebt'],
            'purchases' => $result['purchases'],
            'detailHeader' => [
                'title' => 'Nhà cung cấp',
                'back_url' => 'supplier',
                'back_label' => 'Quay lại',
                'actions_view' => 'suppliers/_detail_header_actions',
            ],
        ]);
    }

    public function index()
    {
        $this->requireLogin();

        $listData = SupplierService::getSupplierListData($_GET, 20);
        $suppliers = $listData['suppliers'];
        $keyword = $listData['keyword'];
        $page = $listData['page'];
        $totalPages = $listData['totalPages'];

        $this->render('suppliers/index', [
            'title' => 'Nhà cung cấp',
            'suppliers' => $suppliers,
            'keyword' => $keyword,
            'page' => $page,
            'totalPages' => $totalPages,
			'listHeader' => [
                'title' => 'Nhà cung cấp',
                'subtitle' => 'Quản lý danh sách nhà cung cấp và công nợ nhập hàng.',
                'primary' => [
                    'url' => 'supplier/create',
                    'tooltip' => 'Thêm nhà cung cấp',
                ],
                'sticky' => true,
                'form' => [
                    'method' => 'get',
                    'action' => '',
                    'attrs' => [],
                ],
                'search' => [
                    'param' => 'q',
                    'placeholder' => 'Tìm kiếm theo tên, SĐT, địa chỉ...',
                    'value' => $keyword,
                    'clear_url' => 'supplier',
                    'show_clear' => $keyword !== '',
                ],
                'hidden' => [],
                'extra_buttons' => [],
            ],
        ]);
    }

    public function create()
    {
        $this->requireLogin();

        $this->render('suppliers/form', SupplierService::getSupplierFormData());
    }

    public function edit()
    {
        $this->requireLogin();

        $result = SupplierService::getSupplierFormData(isset($_GET['id']) ? $_GET['id'] : 0);
        if (empty($result['success'])) {
            $this->redirect(isset($result['redirect']) ? $result['redirect'] : 'supplier');
        }

        $this->render('suppliers/form', $result);
    }

    public function store()
    {
        $this->requireLogin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('supplier');
        }

        $this->verifyCsrfToken();

        $result = SupplierService::createSupplier($_POST);
        if (!empty($result['message'])) {
            $this->setFlash($result['success'] ? 'success' : 'error', $result['message']);
        }
        $this->redirect(isset($result['redirect']) ? $result['redirect'] : 'supplier');
    }

    public function update()
    {
        $this->requireLogin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('supplier');
        }

        $this->verifyCsrfToken();

        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        if ($id <= 0) {
            $this->redirect('supplier');
        }

        $result = SupplierService::updateSupplier($id, $_POST);
        if (!empty($result['message'])) {
            $this->setFlash($result['success'] ? 'success' : 'error', $result['message']);
        }
        $this->redirect(isset($result['redirect']) ? $result['redirect'] : 'supplier');
    }

    public function delete()
    {
        $this->requireLogin();

        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        $result = SupplierService::deleteSupplier($id);
        if (!empty($result['message'])) {
            $this->setFlash($result['success'] ? 'success' : 'error', $result['message']);
        }
        $this->redirect(isset($result['redirect']) ? $result['redirect'] : 'supplier');
    }
}
