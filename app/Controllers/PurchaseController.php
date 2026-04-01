<?php

class PurchaseController extends Controller
{
    public function index()
    {
        $this->requireLogin();

        $listData = PurchaseService::getPurchaseListData($_GET, 20);
        $purchases = $listData['purchases'];
        $suppliers = $listData['suppliers'];
        $keyword = $listData['keyword'];
        $fromDate = $listData['fromDate'];
        $toDate = $listData['toDate'];
        $supplierId = $listData['supplierId'];
        $page = $listData['page'];
        $totalPages = $listData['totalPages'];

        $this->render('purchases/index', [
            'title' => 'Phiếu nhập hàng',
            'purchases' => $purchases,
            'page' => $page,
            'totalPages' => $totalPages,
            'keyword' => $keyword,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'suppliers' => $suppliers,
            'supplierId' => $supplierId,
            'listHeader' => [
                'title' => 'Phiếu nhập hàng',
                'subtitle' => 'Quản lý danh sách phiếu nhập hàng và công nợ nhập.',
                'primary' => [
                    'url' => 'purchase/create',
                    'tooltip' => 'Tạo phiếu nhập hàng',
                ],
                'sticky' => true,
                'form' => [
                    'method' => 'get',
                    'action' => '',
                    'attrs' => [],
                ],
                'search' => [
                    'param' => 'q',
                    'placeholder' => 'Tìm theo mã phiếu, nhà cung cấp, SĐT...',
                    'value' => $keyword,
                    'clear_url' => 'purchase',
                    'show_clear' => $keyword !== '',
                ],
                'hidden' => [],
                'extra_buttons' => [
                    [
                        'icon' => 'filter',
                        'attrs' => [
                            'data-purchase-advanced-filter-open' => '1',
                        ],
                    ],
                ],
            ],
        ]);
    }

    public function view()
    {
        $this->requireLogin();

        $result = PurchaseService::getPurchaseViewData(isset($_GET['id']) ? $_GET['id'] : 0);
        if (empty($result['success'])) {
            $this->redirect(isset($result['redirect']) ? $result['redirect'] : 'purchase');
        }

        $this->render('purchases/view', [
            'title' => 'Chi tiết phiếu nhập',
            'purchase' => $result['purchase'],
            'items' => $result['items'],
            'payments' => $result['payments'],
            'logs' => $result['logs'],
            'detailHeader' => [
                'title' => 'Chi tiết phiếu nhập',
                'back_url' => 'purchase',
                'back_label' => 'Quay lại',
                'actions_view' => 'purchases/_detail_header_actions',
            ],
        ]);
    }

    public function create()
    {
        $this->requireLogin();

        $formData = PurchaseService::getCreateFormData();

        $this->render('purchases/form', [
            'title' => 'Tạo phiếu nhập hàng',
            'suppliers' => $formData['suppliers'],
            'productUnits' => $formData['productUnits'],
            'detailHeader' => [
                'title' => 'Tạo phiếu nhập hàng',
                'back_url' => 'purchase',
                'back_label' => 'Quay lại',
                'actions_view' => '',
            ],
        ]);
    }

    public function edit()
    {
        $this->requireLogin();

        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        $formData = PurchaseService::getEditFormData($id);
        if (empty($formData['success'])) {
            $this->redirect(isset($formData['redirect']) ? $formData['redirect'] : 'purchase');
        }

        $this->render('purchases/form', [
            'title' => 'Chỉnh sửa phiếu nhập',
            'suppliers' => $formData['suppliers'],
            'productUnits' => $formData['productUnits'],
            'purchase' => $formData['purchase'],
            'items' => $formData['items'],
            'paymentMethod' => $formData['paymentMethod'],
            'noteForEdit' => $formData['noteForEdit'],
            'detailHeader' => [
                'title' => 'Chỉnh sửa phiếu nhập',
                'back_url' => 'purchase/view?id=' . $id,
                'back_label' => 'Quay lại',
                'actions_view' => '',
            ],
        ]);
    }

    public function store()
    {
        $this->requireLogin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('purchase');
        }

        $this->verifyCsrfToken();

        $result = PurchaseService::createPurchase($_POST);
        if (!empty($result['message'])) {
            $this->setFlash($result['success'] ? 'success' : 'error', $result['message']);
        }
        $this->redirect(isset($result['redirect']) ? $result['redirect'] : 'purchase');
    }

    public function update()
    {
        $this->requireLogin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('purchase');
        }

        $this->verifyCsrfToken();

        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        if ($id <= 0) {
            $this->redirect('purchase');
        }

        $result = PurchaseService::updatePurchase($id, $_POST);
        if (!empty($result['message'])) {
            $this->setFlash($result['success'] ? 'success' : 'error', $result['message']);
        }
        $this->redirect(isset($result['redirect']) ? $result['redirect'] : 'purchase');
    }

    public function paymentStore()
    {
        $this->requireLogin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('purchase');
        }

        $this->verifyCsrfToken();

        $purchaseId = isset($_POST['purchase_id']) ? (int) $_POST['purchase_id'] : 0;
        $amount = isset($_POST['amount']) ? Money::parseAmount($_POST['amount']) : 0;
        $note = isset($_POST['note']) ? trim($_POST['note']) : '';
        $paymentMethod = isset($_POST['payment_method']) && $_POST['payment_method'] === 'bank' ? 'bank' : 'cash';

        if ($purchaseId <= 0 || $amount <= 0) {
            $this->setFlash('error', 'Dữ liệu thanh toán không hợp lệ.');
            $this->redirect('purchase');
        }

        try {
            PaymentService::recordPurchasePayment($purchaseId, $amount, $note, $paymentMethod);
            $this->setFlash('success', 'Đã ghi nhận thanh toán phiếu nhập.');
            $this->redirect('purchase/view?id=' . $purchaseId);
        } catch (Exception $e) {
            $this->setFlash('error', 'Không thể ghi nhận thanh toán: ' . $e->getMessage());
            $this->redirect('purchase/view?id=' . $purchaseId);
        }
    }
}
