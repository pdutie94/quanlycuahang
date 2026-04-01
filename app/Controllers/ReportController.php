<?php

class ReportController extends Controller
{
    public function index()
    {
        $this->requireLogin();

        $data = ReportService::getOverviewData();

        $this->render('reports/index', [
            'title' => 'Báo cáo tổng quan',
            'ordersToday' => $data['ordersToday'],
            'ordersMonth' => $data['ordersMonth'],
            'purchasesMonth' => $data['purchasesMonth'],
            'customerDebt' => $data['customerDebt'],
            'supplierDebt' => $data['supplierDebt'],
            'delta' => $data['delta'],
            'updatedAtText' => $data['updatedAtText'],
        ]);
    }

    public function sales()
    {
        $this->requireLogin();

        $data = ReportService::getSalesData($_GET);

        if (isset($_GET['ajax']) && $_GET['ajax'] === '1') {
            $this->renderPartial('reports/sales_orders_list', [
                'rows' => $data['rows'],
                'page' => $data['page'],
                'totalPages' => $data['totalPages'],
            ]);
            return;
        }

        $this->render('reports/sales', [
            'title' => 'Báo cáo doanh thu chi tiết',
            'rows' => $data['rows'],
            'summary' => $data['summary'],
            'startDate' => $data['startDate'],
            'endDate' => $data['endDate'],
            'rangeMode' => $data['rangeMode'],
            'page' => $data['page'],
            'totalPages' => $data['totalPages'],
            'hasDateFilter' => $data['hasDateFilter'],
            'dailyStats' => $data['dailyStats'],
        ]);
    }

    public function customerDebt()
    {
        $this->requireLogin();

        $data = ReportService::getCustomerDebtData($_GET);

        $this->render('reports/customer_debt', [
            'title' => 'Công nợ khách hàng',
            'rows' => $data['rows'],
            'summary' => $data['summary'],
            'startDate' => $data['startDate'],
            'endDate' => $data['endDate'],
            'keyword' => $data['keyword'],
            'showAll' => $data['showAll'],
        ]);
    }

    public function supplierDebt()
    {
        $this->requireLogin();

        $data = ReportService::getSupplierDebtData($_GET);

        $this->render('reports/supplier_debt', [
            'title' => 'Công nợ nhà cung cấp',
            'rows' => $data['rows'],
            'summary' => $data['summary'],
            'startDate' => $data['startDate'],
            'endDate' => $data['endDate'],
            'keyword' => $data['keyword'],
            'showAll' => $data['showAll'],
        ]);
    }

    public function missingCost()
    {
        $this->requireLogin();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->verifyCsrfToken();

            $result = ReportService::processMissingCostUpdate($_POST);
            $flashType = isset($result['flashType']) ? $result['flashType'] : ($result['success'] ? 'success' : 'error');
            if (!empty($result['message'])) {
                $this->setFlash($flashType, $result['message']);
            }
            $this->redirect(isset($result['redirect']) ? $result['redirect'] : 'report/missing-cost');
        }

        $data = ReportService::getMissingCostData($_GET);

        $this->render('reports/missing_cost', [
            'title' => 'Cập nhật giá vốn thiếu',
            'items' => $data['items'],
            'summary' => $data['summary'],
            'keyword' => $data['keyword'],
            'startDate' => $data['startDate'],
            'endDate' => $data['endDate'],
        ]);
    }

    public function inventory()
    {
        $this->requireLogin();

        $this->render('reports/inventory', [
            'title' => 'Cập nhật tồn kho',
            'items' => ReportService::getInventoryData(),
        ]);
    }

    public function inventoryAdjust()
    {
        $this->requireLogin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('report/inventory');
        }

        $result = ReportService::adjustInventoryQty(
            isset($_POST['product_id']) ? (int) $_POST['product_id'] : 0,
            isset($_POST['qty_base']) ? $_POST['qty_base'] : ''
        );

        $this->setFlash($result['success'] ? 'success' : 'error', $result['message']);
        $this->redirect(isset($result['redirect']) ? $result['redirect'] : 'report/inventory');
    }
}
