<?php

class SupplierController extends Controller
{
    public function view()
    {
        $this->requireLogin();

        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if ($id <= 0 || !class_exists('Supplier')) {
            $this->redirect('supplier');
        }

        $supplier = Supplier::find($id);
        if (!$supplier) {
            $this->redirect('supplier');
        }

        $pdo = Database::getInstance();

        $purchaseStmt = $pdo->prepare('SELECT p.*, (p.total_amount - p.paid_amount) AS debt_amount
            FROM purchases p
            WHERE p.supplier_id = ?
            ORDER BY p.purchase_date DESC, p.id DESC');
        $purchaseStmt->execute([$id]);
        $purchases = $purchaseStmt->fetchAll();

        $totalDebt = 0;
        foreach ($purchases as $purchase) {
            if ($purchase['debt_amount'] > 0) {
                $totalDebt += $purchase['debt_amount'];
            }
        }

        $this->render('suppliers/view', [
            'title' => 'Nhà cung cấp',
            'supplier' => $supplier,
            'totalDebt' => $totalDebt,
            'purchases' => $purchases,
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

        $keyword = isset($_GET['q']) ? trim($_GET['q']) : '';
        $suppliers = [];
        $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
        if ($page < 1) {
            $page = 1;
        }

        $perPage = 20;
        $totalPages = 1;

        if (class_exists('Supplier')) {
            if ($keyword !== '') {
                $totalCount = Supplier::countByKeyword($keyword);
            } else {
                $totalCount = Supplier::countAll();
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
                $suppliers = Supplier::searchPaginate($keyword, $perPage, $offset);
            } else {
                $suppliers = Supplier::paginate($perPage, $offset);
            }
        }

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

        $this->render('suppliers/form', [
            'title' => 'Thêm nhà cung cấp',
            'supplier' => null,
            'detailHeader' => [
                'title' => 'Thêm nhà cung cấp',
                'back_url' => 'supplier',
                'back_label' => 'Quay lại',
                'actions_view' => '',
            ],
        ]);
    }

    public function edit()
    {
        $this->requireLogin();

        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if ($id <= 0 || !class_exists('Supplier')) {
            $this->redirect('supplier');
        }

        $supplier = Supplier::find($id);
        if (!$supplier) {
            $this->redirect('supplier');
        }

        $this->render('suppliers/form', [
            'title' => 'Chỉnh sửa nhà cung cấp',
            'supplier' => $supplier,
            'detailHeader' => [
                'title' => 'Chỉnh sửa nhà cung cấp',
                'back_url' => 'supplier/view?id=' . $id,
                'back_label' => 'Quay lại',
                'actions_view' => '',
            ],
        ]);
    }

    public function store()
    {
        $this->requireLogin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('supplier');
        }

        $this->verifyCsrfToken();

        $name = isset($_POST['name']) ? trim($_POST['name']) : '';
        $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
        $address = isset($_POST['address']) ? trim($_POST['address']) : '';

        if ($name === '') {
            $this->setFlash('error', 'Tên nhà cung cấp là bắt buộc.');
            $this->redirect('supplier/create');
        }

        if (class_exists('Supplier')) {
            Supplier::create([
                'name' => $name,
                'phone' => $phone,
                'address' => $address,
            ]);
        }

        $this->setFlash('success', 'Đã thêm nhà cung cấp.');
        $this->redirect('supplier');
    }

    public function update()
    {
        $this->requireLogin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('supplier');
        }

        $this->verifyCsrfToken();

        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        $name = isset($_POST['name']) ? trim($_POST['name']) : '';
        $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
        $address = isset($_POST['address']) ? trim($_POST['address']) : '';

        if ($id <= 0) {
            $this->redirect('supplier');
        }

        if ($name === '' || !class_exists('Supplier')) {
            $this->setFlash('error', 'Tên nhà cung cấp là bắt buộc.');
            $this->redirect('supplier/edit?id=' . $id);
        }

        Supplier::update($id, [
            'name' => $name,
            'phone' => $phone,
            'address' => $address,
        ]);

        $this->setFlash('success', 'Đã cập nhật nhà cung cấp.');
        $this->redirect('supplier');
    }

    public function delete()
    {
        $this->requireLogin();

        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if ($id > 0 && class_exists('Supplier')) {
            Supplier::delete($id);
            $this->setFlash('success', 'Đã xóa nhà cung cấp.');
        }

        $this->redirect('supplier');
    }
}
