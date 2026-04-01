<?php

class OrderPaymentController extends Controller
{
	public function paymentStore()
	{
		$this->requireLogin();

		if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
			$this->redirect('order');
		}

        $this->verifyCsrfToken();

		$orderId = isset($_POST['order_id']) ? (int) $_POST['order_id'] : 0;
		$amount = isset($_POST['amount']) ? Money::parseAmount($_POST['amount']) : 0;
		$note = isset($_POST['note']) ? trim($_POST['note']) : '';
		$paymentMethod = isset($_POST['payment_method']) && $_POST['payment_method'] === 'bank' ? 'bank' : 'cash';

		if ($orderId <= 0 || $amount <= 0) {
			$this->setFlash('error', 'Dữ liệu thanh toán không hợp lệ.');
			$this->redirect('order');
		}

		try {
			PaymentService::recordOrderPayment($orderId, $amount, $note, $paymentMethod);
			$this->setFlash('success', 'Đã ghi nhận thanh toán.');
			$this->redirect('order/view?id=' . $orderId);
		} catch (Exception $e) {
			$this->setFlash('error', 'Không thể ghi nhận thanh toán: ' . $e->getMessage());
			$this->redirect('order/view?id=' . $orderId);
		}
	}

	public function paymentReset()
	{
		$this->requireLogin();

		if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
			$this->redirect('order');
		}

        $this->verifyCsrfToken();

		$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
		if ($id <= 0) {
			$this->redirect('order');
		}

		try {
			PaymentService::resetOrderPayment($id);
			$this->setFlash('success', 'Đã đặt lại thanh toán về trạng thái còn nợ.');
			$this->redirect('order/view?id=' . $id);
		} catch (Exception $e) {
			$this->setFlash('error', 'Không thể đặt lại thanh toán: ' . $e->getMessage());
			$this->redirect('order/view?id=' . $id);
		}
	}

    public function returnForm()
    {
        $this->requireLogin();

        $result = OrderService::getOrderReturnFormData(isset($_GET['id']) ? $_GET['id'] : 0);
        if (empty($result['success'])) {
            if (!empty($result['message'])) {
                $this->setFlash('error', $result['message']);
            }
            $this->redirect(isset($result['redirect']) ? $result['redirect'] : 'order');
        }

        $order = $result['order'];
        $items = $result['items'];
        $id = isset($order['id']) ? (int) $order['id'] : 0;

        $this->render('orders/return', [
            'title' => 'Trả hàng đơn ' . $order['order_code'],
            'order' => $order,
            'items' => $items,
            'detailHeader' => [
                'title' => 'Trả hàng đơn ' . $order['order_code'],
                'back_url' => 'order/view?id=' . $id,
                'back_label' => 'Quay lại',
                'actions_view' => '',
            ],
        ]);
    }

    public function returnStore()
    {
        $this->requireLogin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('order');
        }

        $this->verifyCsrfToken();

        $result = OrderService::processOrderReturn(isset($_POST['order_id']) ? $_POST['order_id'] : 0, $_POST);
        $this->setFlash($result['success'] ? 'success' : 'error', isset($result['message']) ? $result['message'] : 'Không thể ghi nhận trả hàng.');
        $this->redirect(isset($result['redirect']) ? $result['redirect'] : 'order');
    }
}