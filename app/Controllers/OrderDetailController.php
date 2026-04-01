<?php

class OrderDetailController extends Controller
{
    public function view()
    {
        $this->requireLogin();

        $result = OrderService::getOrderViewData(isset($_GET['id']) ? $_GET['id'] : 0);
        if (empty($result['success'])) {
            $this->redirect(isset($result['redirect']) ? $result['redirect'] : 'order');
        }

        $this->render('orders/view', [
            'title' => 'Chi tiết đơn hàng',
            'order' => $result['order'],
            'items' => $result['items'],
            'manualItems' => $result['manualItems'],
            'payments' => $result['payments'],
            'logs' => $result['logs'],
            'detailHeader' => [
                'title' => 'Chi tiết đơn hàng',
                'back_url' => 'order',
                'back_label' => 'Quay lại',
                'actions_view' => 'orders/_detail_header_actions',
            ],
        ]);
    }

    public function preview()
    {
        $this->requireLogin();

        $result = OrderService::getOrderPreviewData(isset($_GET['id']) ? $_GET['id'] : 0);
        if (empty($result['success'])) {
            http_response_code(isset($result['statusCode']) ? (int) $result['statusCode'] : 404);
            echo isset($result['message']) ? $result['message'] : 'Không tìm thấy đơn hàng.';
            return;
        }

        $order = $result['order'];
        $items = $result['items'];
        $manualItems = $result['manualItems'];

        $isAjax = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
            || (isset($_GET['ajax']) && $_GET['ajax'] === '1');

        if ($isAjax) {
            header('Content-Type: text/html; charset=utf-8');
            $this->renderPartial('orders/preview', [
                'order' => $order,
                'items' => $items,
                'manualItems' => $manualItems,
            ]);
            return;
        }

        $this->render('orders/preview', [
            'title' => 'Xem trước đơn hàng',
            'order' => $order,
            'items' => $items,
            'manualItems' => $manualItems,
        ]);
    }

    public function invoice()
    {
        $this->requireLogin();

        $result = OrderService::getOrderInvoiceData(isset($_GET['id']) ? $_GET['id'] : 0);
        if (empty($result['success'])) {
            $this->redirect(isset($result['redirect']) ? $result['redirect'] : 'order');
        }

        $this->render('orders/invoice', [
            'title' => 'Hóa đơn đơn hàng ' . $result['order']['order_code'],
            'order' => $result['order'],
            'items' => $result['items'],
        ]);
    }

    public function addForm()
    {
        $this->requireLogin();

        $result = OrderService::getOrderAddFormData(isset($_GET['id']) ? $_GET['id'] : 0);
        if (empty($result['success'])) {
            if (!empty($result['message'])) {
                $this->setFlash('error', $result['message']);
            }
            $this->redirect(isset($result['redirect']) ? $result['redirect'] : 'order');
        }

        $this->render('orders/add', [
            'title' => 'Thêm sản phẩm vào đơn',
            'order' => $result['order'],
            'productUnits' => $result['productUnits'],
        ]);
    }

    public function update()
    {
        $this->requireLogin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('order');
        }

        $this->verifyCsrfToken();

        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        if (!$id) {
            $this->redirect('order');
        }

        $result = OrderService::updateOrderDetails($id, $_POST);
        if (!empty($result['message'])) {
            $this->setFlash($result['success'] ? 'success' : 'error', $result['message']);
        }
        $this->redirect(isset($result['redirect']) ? $result['redirect'] : 'order');
    }

    public function addStore()
    {
        $this->requireLogin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('order');
        }

        $this->verifyCsrfToken();

        $orderId = isset($_POST['order_id']) ? (int) $_POST['order_id'] : 0;
        if ($orderId <= 0) {
            $this->redirect('order');
        }

        $result = OrderService::addItemsToOrder($orderId, $_POST);
        if (!empty($result['message'])) {
            $this->setFlash($result['success'] ? 'success' : 'error', $result['message']);
        }
        $this->redirect(isset($result['redirect']) ? $result['redirect'] : 'order');
    }

    public function edit()
    {
        $this->requireLogin();

        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if (!$id) {
            $this->redirect('order');
        }

        $order = OrderRepository::findForEdit($id);

        if (!$order) {
            $this->redirect('order');
        }

        $orderStatus = isset($order['order_status']) ? $order['order_status'] : 'pending';
        if ($orderStatus === 'completed' || $orderStatus === 'cancelled') {
            $this->setFlash('error', 'Đơn hàng đã hoàn thành hoặc đã hủy, không thể chỉnh sửa.');
            $this->redirect('order/view?id=' . $id);
        }

        $paymentMethod = null;
        $noteForEdit = isset($order['note']) ? (string) $order['note'] : '';
        if ($noteForEdit !== '') {
            $noteTrim = rtrim($noteForEdit);
            if (substr($noteTrim, -9) === '[TT:cash]') {
                $paymentMethod = 'cash';
                $noteForEdit = rtrim(substr($noteTrim, 0, -9));
            } elseif (substr($noteTrim, -9) === '[TT:bank]') {
                $paymentMethod = 'bank';
                $noteForEdit = rtrim(substr($noteTrim, 0, -9));
            }
        }

        $paymentStatus = $order['status'] === 'paid' ? 'pay' : 'debt';

        $pdo = Database::getInstance();

        $unitStmt = $pdo->query('SELECT pu.id, pu.product_id, pu.factor, pu.price_sell, pu.price_cost, pu.allow_fraction, pu.min_step, p.name AS product_name, p.image_path AS product_image_path, u.name AS unit_name
            FROM product_units pu
            JOIN products p ON pu.product_id = p.id
            JOIN units u ON pu.unit_id = u.id
            WHERE p.deleted_at IS NULL
            ORDER BY p.name, u.name');
        $productUnits = $unitStmt->fetchAll();

        $itemStmt = $pdo->prepare('SELECT oi.*, p.name AS product_name, p.image_path AS product_image_path, u.name AS unit_name
            FROM order_items oi
            JOIN products p ON oi.product_id = p.id
            JOIN product_units pu ON oi.product_unit_id = pu.id
            JOIN units u ON pu.unit_id = u.id
            WHERE oi.order_id = ?
            ORDER BY oi.id');
        $itemStmt->execute([$id]);
        $items = $itemStmt->fetchAll();

        $manualItems = [];
        if (class_exists('OrderManualItem')) {
            $manualItems = OrderManualItem::findByOrder($id);
        }

        $customers = [];
        try {
            if (class_exists('Customer')) {
                $customers = Customer::all();
            }
        } catch (Exception $e) {
            $customers = [];
        }

        $this->render('orders/form', [
            'title' => 'Sửa đơn hàng',
            'order' => $order,
            'customers' => $customers,
            'paymentMethod' => $paymentMethod,
            'noteForEdit' => $noteForEdit,
            'paymentStatus' => $paymentStatus,
            'productUnits' => $productUnits,
            'items' => $items,
            'manualItems' => $manualItems,
            'detailHeader' => [
                'title' => 'Sửa đơn hàng',
                'back_url' => 'order/view?id=' . $id,
                'back_label' => 'Quay lại',
                'actions_view' => '',
            ],
        ]);
    }

    public function updateStatus()
    {
        $this->requireLogin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('order');
        }

        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        $orderStatus = isset($_POST['order_status']) ? $_POST['order_status'] : '';

        if ($id <= 0) {
            $this->redirect('order');
        }

        $allowed = ['pending', 'completed', 'cancelled'];
        if (!in_array($orderStatus, $allowed, true)) {
            $this->setFlash('error', 'Trạng thái đơn hàng không hợp lệ.');
            $this->redirect('order/view?id=' . $id);
        }

        $pdo = Database::getInstance();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare('SELECT * FROM orders WHERE id = ? FOR UPDATE');
            $stmt->execute([$id]);
            $order = $stmt->fetch();

            if (!$order) {
                $pdo->rollBack();
                $this->redirect('order');
            }

            $oldStatus = isset($order['order_status']) ? $order['order_status'] : 'pending';
            $newStatus = $orderStatus;

            $updateStmt = $pdo->prepare('UPDATE orders SET order_status = ? WHERE id = ?');
            $updateStmt->execute([$newStatus, $id]);

            if ($oldStatus !== $newStatus) {
                $direction = 0;
                if ($oldStatus !== 'completed' && $newStatus === 'completed') {
                    $direction = -1;
                } elseif ($oldStatus === 'completed' && $newStatus !== 'completed') {
                    $direction = 1;
                }

                if ($direction !== 0) {
                    $itemStmt = $pdo->prepare('SELECT product_id, SUM(qty_base) AS qty_base FROM order_items WHERE order_id = ? GROUP BY product_id');
                    $itemStmt->execute([$id]);
                    $items = $itemStmt->fetchAll();

                    InventoryService::adjustForOrderStatusChange($items, $direction);
                    ProductSalesSummaryService::adjustForOrderStatusChange($items, -$direction);
                }
            }

            if (class_exists('OrderLog')) {
                $statusText = 'Chưa hoàn thành';
                if ($orderStatus === 'completed') {
                    $statusText = 'Đã hoàn thành';
                } elseif ($orderStatus === 'cancelled') {
                    $statusText = 'Đã hủy';
                }
                OrderLog::create([
                    'order_id' => $id,
                    'action' => 'update_status',
                    'detail' => 'Cập nhật trạng thái: ' . $statusText,
                ]);
            }

            $pdo->commit();
            ReportService::clearReportCache();
        } catch (Exception $e) {
            $pdo->rollBack();
            $this->setFlash('error', 'Không thể cập nhật trạng thái đơn hàng: ' . $e->getMessage());
            $this->redirect('order/view?id=' . $id);
        }

        $this->setFlash('success', 'Đã cập nhật trạng thái đơn hàng.');
        $this->redirect('order/view?id=' . $id);
    }
}