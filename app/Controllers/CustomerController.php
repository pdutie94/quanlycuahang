<?php

class CustomerController extends Controller
{
    public function index()
    {
        $this->requireLogin();

        $listData = CustomerService::getCustomerListData($_GET, 20);
        $customers = $listData['customers'];
        $keyword = $listData['keyword'];
        $debtStatus = $listData['debtStatus'];
        $page = $listData['page'];
        $totalPages = $listData['totalPages'];

        $this->render('customers/index', [
            'title' => 'Khách hàng',
            'customers' => $customers,
            'page' => $page,
            'totalPages' => $totalPages,
            'keyword' => $keyword,
            'debtStatus' => $debtStatus,
			'listHeader' => [
                'title' => 'Khách hàng',
                'subtitle' => 'Quản lý danh sách khách hàng và công nợ.',
                'primary' => [
                    'url' => 'customer/create',
                    'tooltip' => 'Thêm khách hàng',
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
                    'clear_url' => 'customer',
                    'show_clear' => $keyword !== '',
                ],
                'hidden' => [],
                'extra_buttons' => [],
                'chips' => [
                    'class' => 'mt-2 flex items-center gap-2 overflow-x-auto text-sm',
                    'items' => [
                        [
                            'kind' => 'submit',
                            'name' => 'debt_status',
                            'value' => '',
                            'label' => 'Tất cả',
                            'active' => $debtStatus === '',
                            'base_class' => 'border inline-flex items-center rounded-lg px-3 py-1 text-sm font-medium',
                            'active_class' => 'bg-emerald-600 text-white border-emerald-600',
                            'inactive_class' => 'bg-white text-slate-700 border-slate-200',
                        ],
                        [
                            'kind' => 'submit',
                            'name' => 'debt_status',
                            'value' => 'debt',
                            'label' => 'Còn nợ',
                            'active' => $debtStatus === 'debt',
                            'base_class' => 'border inline-flex items-center rounded-lg px-3 py-1 text-sm font-medium',
                            'active_class' => 'bg-emerald-600 text-white border-emerald-600',
                            'inactive_class' => 'bg-white text-slate-700 border-slate-200',
                        ],
                        [
                            'kind' => 'submit',
                            'name' => 'debt_status',
                            'value' => 'nodebt',
                            'label' => 'Không nợ',
                            'active' => $debtStatus === 'nodebt',
                            'base_class' => 'border inline-flex items-center rounded-lg px-3 py-1 text-sm font-medium',
                            'active_class' => 'bg-emerald-600 text-white border-emerald-600',
                            'inactive_class' => 'bg-white text-slate-700 border-slate-200',
                        ],
                    ],
                ],
            ],
        ]);
    }

    public function view()
    {
        $this->requireLogin();

        $result = CustomerService::getCustomerViewData(isset($_GET['id']) ? $_GET['id'] : 0);
        if (empty($result['success'])) {
            $this->redirect(isset($result['redirect']) ? $result['redirect'] : 'customer');
        }

        $this->render('customers/view', [
            'title' => 'Khách hàng',
            'customer' => $result['customer'],
            'orders' => $result['orders'],
            'totalAmountSum' => $result['totalAmountSum'],
            'totalPaidSum' => $result['totalPaidSum'],
            'totalDebt' => $result['totalDebt'],
            'detailHeader' => [
                'title' => 'Khách hàng',
                'back_url' => 'customer',
                'back_label' => 'Quay lại',
                'actions_view' => 'customers/_detail_header_actions',
            ],
        ]);
    }

    public function edit()
    {
        $this->requireLogin();

        $result = CustomerService::getCustomerFormData(isset($_GET['id']) ? $_GET['id'] : 0);
        if (empty($result['success'])) {
            if (!empty($result['message'])) {
                $this->setFlash('error', $result['message']);
            }
            $this->redirect(isset($result['redirect']) ? $result['redirect'] : 'customer');
        }

        $this->render('customers/form', $result);
    }

    public function create()
    {
        $this->requireLogin();

        $this->render('customers/form', CustomerService::getCustomerFormData());
    }

    public function update()
    {
        $this->requireLogin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('customer');
        }

        $this->verifyCsrfToken();

        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        if ($id <= 0) {
            $this->redirect('customer');
        }

        $result = CustomerService::updateCustomer($id, $_POST);
        if (!empty($result['message'])) {
            $this->setFlash($result['success'] ? 'success' : 'error', $result['message']);
        }
        $this->redirect(isset($result['redirect']) ? $result['redirect'] : 'customer');
    }

    public function store()
    {
        $this->requireLogin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('customer');
        }

        $this->verifyCsrfToken();

        $result = CustomerService::createCustomer($_POST);
        if (!empty($result['message'])) {
            $this->setFlash($result['success'] ? 'success' : 'error', $result['message']);
        }
        $this->redirect(isset($result['redirect']) ? $result['redirect'] : 'customer');
    }

    public function delete()
    {
        $this->requireLogin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('customer');
        }

        $this->verifyCsrfToken();

        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        if ($id <= 0) {
            $this->redirect('customer');
        }

        $result = CustomerService::deleteCustomer($id);
        if (!empty($result['message'])) {
            $this->setFlash($result['success'] ? 'success' : 'error', $result['message']);
        }
        $this->redirect(isset($result['redirect']) ? $result['redirect'] : 'customer');
    }

    public function payment()
    {
        $this->requireLogin();

        $result = CustomerService::getCustomerPaymentData(isset($_GET['order_id']) ? $_GET['order_id'] : 0);
        if (empty($result['success'])) {
            $this->redirect(isset($result['redirect']) ? $result['redirect'] : 'customer');
        }

        $this->render('customers/payment', [
            'title' => 'Thu tiền khách hàng',
            'order' => $result['order'],
            'remaining' => $result['remaining'],
        ]);
    }

    public function paymentStore()
    {
        $this->requireLogin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('customer');
        }

        $this->verifyCsrfToken();

		$orderId = isset($_POST['order_id']) ? (int) $_POST['order_id'] : 0;
		$amount = isset($_POST['amount']) ? Money::parseAmount($_POST['amount']) : 0;
        $note = isset($_POST['note']) ? trim($_POST['note']) : '';

		$result = CustomerService::recordCustomerPayment($orderId, $amount, $note);
		if (!empty($result['message'])) {
			$this->setFlash($result['success'] ? 'success' : 'error', $result['message']);
		}
		$this->redirect(isset($result['redirect']) ? $result['redirect'] : 'customer');
	}
}
