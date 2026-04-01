<?php

class OrderListController extends Controller
{
    public function index()
    {
        $this->requireLogin();

        $listData = OrderService::getOrderListData($_GET, 20);
        $orders = $listData['orders'];
        $keyword = $listData['keyword'];
        $status = $listData['status'];
        $orderStatus = $listData['orderStatus'];
        $fromDate = $listData['fromDate'];
        $toDate = $listData['toDate'];
        $page = $listData['page'];
        $totalPages = $listData['totalPages'];
        $totalCount = $listData['totalCount'];
        $perPage = $listData['perPage'];

        $this->render('orders/index', [
            'title' => 'Đơn hàng',
            'orders' => $orders,
            'keyword' => $keyword,
            'status' => $status,
            'statusFilter' => $status,
            'orderStatus' => $orderStatus,
            'orderStatusFilter' => $orderStatus,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'page' => $page,
            'totalPages' => $totalPages,
            'totalCount' => $totalCount,
            'perPage' => $perPage,
            'listHeader' => [
                'title' => 'Đơn hàng',
                'subtitle' => 'Quản lý danh sách đơn hàng bán ra.',
                'primary' => [
                    'url' => 'pos',
                    'tooltip' => 'Tạo đơn mới',
                ],
                'sticky' => true,
                'form' => [
                    'method' => 'get',
                    'action' => '',
                    'attrs' => [
                        'data-order-list-filter' => '1',
                    ],
                ],
                'search' => [
                    'param' => 'q',
                    'placeholder' => 'Tìm theo mã đơn, tên khách, SĐT...',
                    'value' => $keyword,
                    'clear_url' => 'order',
                    'show_clear' => $keyword !== '',
                ],
                'hidden' => [
                    [
                        'name' => 'status',
                        'value' => $status,
                    ],
                    [
                        'name' => 'from_date',
                        'value' => $fromDate,
                    ],
                    [
                        'name' => 'to_date',
                        'value' => $toDate,
                    ],
                ],
                'extra_buttons' => [
                    [
                        'icon' => 'filter',
                        'attrs' => [
                            'data-order-advanced-filter-open' => '1',
                        ],
                    ],
                ],
                'chips' => [
                    'class' => 'flex items-center gap-1.5 overflow-x-auto overflow-y-hidden whitespace-nowrap text-sm',
                    'items' => [
                        [
                            'kind' => 'submit',
                            'name' => 'order_status',
                            'value' => '',
                            'label' => 'Tất cả',
                            'active' => $orderStatus === '',
                            'base_class' => 'border inline-flex items-center rounded-lg px-3 py-1 text-sm font-medium',
                            'active_class' => 'bg-brand-600 text-white border-brand-600',
                            'inactive_class' => 'bg-white text-slate-700 border-slate-200',
                        ],
                        [
                            'kind' => 'submit',
                            'name' => 'order_status',
                            'value' => 'completed',
                            'label' => 'Hoàn thành',
                            'active' => $orderStatus === 'completed',
                            'base_class' => 'border inline-flex items-center rounded-lg px-3 py-1 text-sm font-medium',
                            'active_class' => 'bg-brand-600 text-white border-brand-600',
                            'inactive_class' => 'bg-white text-slate-700 border-slate-200',
                        ],
                        [
                            'kind' => 'submit',
                            'name' => 'order_status',
                            'value' => 'pending',
                            'label' => 'Chưa hoàn thành',
                            'active' => $orderStatus === 'pending',
                            'base_class' => 'border inline-flex items-center rounded-lg px-3 py-1 text-sm font-medium',
                            'active_class' => 'bg-brand-600 text-white border-brand-600',
                            'inactive_class' => 'bg-white text-slate-700 border-slate-200',
                        ],
                        [
                            'kind' => 'submit',
                            'name' => 'order_status',
                            'value' => 'cancelled',
                            'label' => 'Đã hủy',
                            'active' => $orderStatus === 'cancelled',
                            'base_class' => 'border inline-flex items-center rounded-lg px-3 py-1 text-sm font-medium',
                            'active_class' => 'bg-brand-600 text-white border-brand-600',
                            'inactive_class' => 'bg-white text-slate-700 border-slate-200',
                        ],
                    ],
                ],
            ],
            'filters' => [
                'keyword' => [
                    'name' => 'q',
                    'value' => $keyword,
                    'placeholder' => 'Tìm theo mã đơn, tên khách, SĐT',
                ],
                'date_range' => [
                    'from_name' => 'from_date',
                    'to_name' => 'to_date',
                    'from_value' => $fromDate,
                    'to_value' => $toDate,
                ],
                'chips' => [
                    'class' => 'flex items-center gap-1.5 overflow-x-auto overflow-y-hidden whitespace-nowrap text-sm',
                    'items' => [
                        [
                            'kind' => 'submit',
                            'name' => 'order_status',
                            'value' => '',
                            'label' => 'Tất cả',
                            'active' => $orderStatus === '',
                            'base_class' => 'border inline-flex items-center rounded-lg px-3 py-1 text-sm font-medium',
                            'active_class' => 'bg-emerald-600 text-white border-emerald-600',
                            'inactive_class' => 'bg-white text-slate-700 border-slate-200',
                        ],
                        [
                            'kind' => 'submit',
                            'name' => 'order_status',
                            'value' => 'completed',
                            'label' => 'Hoàn thành',
                            'active' => $orderStatus === 'completed',
                            'base_class' => 'border inline-flex items-center rounded-lg px-3 py-1 text-sm font-medium',
                            'active_class' => 'bg-emerald-600 text-white border-emerald-600',
                            'inactive_class' => 'bg-white text-slate-700 border-slate-200',
                        ],
                        [
                            'kind' => 'submit',
                            'name' => 'order_status',
                            'value' => 'pending',
                            'label' => 'Chưa hoàn thành',
                            'active' => $orderStatus === 'pending',
                            'base_class' => 'border inline-flex items-center rounded-lg px-3 py-1 text-sm font-medium',
                            'active_class' => 'bg-emerald-600 text-white border-emerald-600',
                            'inactive_class' => 'bg-white text-slate-700 border-slate-200',
                        ],
                        [
                            'kind' => 'submit',
                            'name' => 'order_status',
                            'value' => 'cancelled',
                            'label' => 'Đã hủy',
                            'active' => $orderStatus === 'cancelled',
                            'base_class' => 'border inline-flex items-center rounded-lg px-3 py-1 text-sm font-medium',
                            'active_class' => 'bg-emerald-600 text-white border-emerald-600',
                            'inactive_class' => 'bg-white text-slate-700 border-slate-200',
                        ],
                    ],
                ],
            ],
        ]);
    }

    public function delete()
    {
        $this->requireLogin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('order');
        }

        $this->verifyCsrfToken();

        $result = OrderService::deleteOrderById(isset($_POST['id']) ? $_POST['id'] : 0);
        $this->setFlash($result['success'] ? 'success' : 'error', $result['message']);
        $this->redirect(isset($result['redirect']) ? $result['redirect'] : 'order');
    }

    public function restore()
    {
        $this->requireLogin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('order');
        }

        $this->verifyCsrfToken();

        $result = OrderService::restoreOrderById(isset($_POST['id']) ? $_POST['id'] : 0);
        $this->setFlash($result['success'] ? 'success' : 'error', $result['message']);
        $this->redirect(isset($result['redirect']) ? $result['redirect'] : 'order');
    }

    public function purgeDeleted()
    {
        $this->requireLogin();

        $result = OrderService::purgeDeletedOrders(isset($_GET['days']) ? $_GET['days'] : 30);
        $this->setFlash($result['success'] ? 'success' : 'error', $result['message']);
        $this->redirect(isset($result['redirect']) ? $result['redirect'] : 'order');
    }
}