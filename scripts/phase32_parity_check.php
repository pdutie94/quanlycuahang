<?php

require __DIR__ . '/../config/config.php';
require __DIR__ . '/../config/database.php';
require __DIR__ . '/../app/Core/Database.php';
require __DIR__ . '/../app/Core/Controller.php';
require __DIR__ . '/../vendor/autoload.php';

spl_autoload_register(function ($class) {
    $baseDir = realpath(__DIR__ . '/..');
    $paths = [
        $baseDir . '/app/Controllers/' . $class . '.php',
        $baseDir . '/app/Models/' . $class . '.php',
        $baseDir . '/app/Core/' . $class . '.php',
        $baseDir . '/app/Services/' . $class . '.php',
        $baseDir . '/app/Repositories/' . $class . '.php',
    ];

    foreach ($paths as $path) {
        if (file_exists($path)) {
            require $path;
            return;
        }
    }
});

use App\Modules\Order\OrderApiController;
use App\Modules\Product\ProductApiController;
use App\Modules\Purchase\PurchaseApiController;
use App\Modules\Report\ReportApiController;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\Psr7\Factory\ServerRequestFactory;

function decodeResponse($response): array
{
    $json = (string) $response->getBody();
    $data = json_decode($json, true);
    return is_array($data) ? $data : [];
}

$requestFactory = new ServerRequestFactory();
$responseFactory = new ResponseFactory();

$results = [];

$productQuery = ['q' => '', 'page' => 1];
$productService = ProductService::getProductListData($productQuery, 20);
$productController = new ProductApiController();
$productReq = $requestFactory->createServerRequest('GET', '/api/products')->withQueryParams($productQuery);
$productRes = $productController->list($productReq, $responseFactory->createResponse());
$productApi = decodeResponse($productRes);

$serviceProductCount = isset($productService['products']) && is_array($productService['products']) ? count($productService['products']) : 0;
$apiProductCount = isset($productApi['data']['items']) && is_array($productApi['data']['items']) ? count($productApi['data']['items']) : 0;
$results[] = [
    'case' => 'product_list_count',
    'pass' => $serviceProductCount === $apiProductCount,
    'service' => $serviceProductCount,
    'api' => $apiProductCount,
];

$productId = 0;
if (!empty($productService['products'][0]['id'])) {
    $productId = (int) $productService['products'][0]['id'];
}

if ($productId > 0) {
    $productDetailRes = $productController->detail(
        $requestFactory->createServerRequest('GET', '/api/products/' . $productId),
        $responseFactory->createResponse(),
        ['id' => $productId]
    );
    $productDetailApi = decodeResponse($productDetailRes);
    $productModel = Product::find($productId);

    $serviceProductName = isset($productModel['name']) ? (string) $productModel['name'] : '';
    $apiProductName = isset($productDetailApi['data']['name']) ? (string) $productDetailApi['data']['name'] : '';
    $results[] = [
        'case' => 'product_detail_name',
        'pass' => $serviceProductName === $apiProductName,
        'service' => $serviceProductName,
        'api' => $apiProductName,
    ];
}

$purchaseQuery = ['q' => '', 'page' => 1];
$purchaseService = PurchaseService::getPurchaseListData($purchaseQuery, 20);
$purchaseController = new PurchaseApiController();
$purchaseReq = $requestFactory->createServerRequest('GET', '/api/purchases')->withQueryParams($purchaseQuery);
$purchaseRes = $purchaseController->list($purchaseReq, $responseFactory->createResponse());
$purchaseApi = decodeResponse($purchaseRes);

$servicePurchaseCount = isset($purchaseService['purchases']) && is_array($purchaseService['purchases']) ? count($purchaseService['purchases']) : 0;
$apiPurchaseCount = isset($purchaseApi['data']['items']) && is_array($purchaseApi['data']['items']) ? count($purchaseApi['data']['items']) : 0;
$results[] = [
    'case' => 'purchase_list_count',
    'pass' => $servicePurchaseCount === $apiPurchaseCount,
    'service' => $servicePurchaseCount,
    'api' => $apiPurchaseCount,
];

$purchaseCreateServiceInvalid = PurchaseService::createPurchase([]);
$purchaseCreateApiInvalidRes = $purchaseController->create(
    $requestFactory->createServerRequest('POST', '/api/purchases')->withParsedBody([]),
    $responseFactory->createResponse()
);
$purchaseCreateApiInvalid = decodeResponse($purchaseCreateApiInvalidRes);
$results[] = [
    'case' => 'purchase_create_validation_message',
    'pass' => isset($purchaseCreateServiceInvalid['message'], $purchaseCreateApiInvalid['message'])
        && (string) $purchaseCreateServiceInvalid['message'] === (string) $purchaseCreateApiInvalid['message'],
    'service' => isset($purchaseCreateServiceInvalid['message']) ? (string) $purchaseCreateServiceInvalid['message'] : '',
    'api' => isset($purchaseCreateApiInvalid['message']) ? (string) $purchaseCreateApiInvalid['message'] : '',
];

$purchaseId = 0;
if (!empty($purchaseService['purchases'][0]['id'])) {
    $purchaseId = (int) $purchaseService['purchases'][0]['id'];
}

if ($purchaseId > 0) {
    $purchaseViewService = PurchaseService::getPurchaseViewData($purchaseId);
    $purchaseDetailRes = $purchaseController->detail(
        $requestFactory->createServerRequest('GET', '/api/purchases/' . $purchaseId),
        $responseFactory->createResponse(),
        ['id' => $purchaseId]
    );
    $purchaseDetailApi = decodeResponse($purchaseDetailRes);

    $serviceItemCount = isset($purchaseViewService['items']) && is_array($purchaseViewService['items']) ? count($purchaseViewService['items']) : 0;
    $apiItemCount = isset($purchaseDetailApi['data']['items']) && is_array($purchaseDetailApi['data']['items']) ? count($purchaseDetailApi['data']['items']) : 0;

    $results[] = [
        'case' => 'purchase_detail_items_count',
        'pass' => $serviceItemCount === $apiItemCount,
        'service' => $serviceItemCount,
        'api' => $apiItemCount,
    ];
}

$reportController = new ReportApiController();
$reportQuery = ['start_date' => '', 'end_date' => '', 'page' => 1];

$salesService = ReportService::getSalesData($reportQuery);
$salesRes = $reportController->sales(
    $requestFactory->createServerRequest('GET', '/api/reports/sales')->withQueryParams($reportQuery),
    $responseFactory->createResponse()
);
$salesApi = decodeResponse($salesRes);

$serviceSalesTotal = isset($salesService['summary']['total_amount']) ? (float) $salesService['summary']['total_amount'] : 0.0;
$apiSalesTotal = isset($salesApi['data']['summary']['total_amount']) ? (float) $salesApi['data']['summary']['total_amount'] : 0.0;
$results[] = [
    'case' => 'report_sales_total_amount',
    'pass' => abs($serviceSalesTotal - $apiSalesTotal) < 0.0001,
    'service' => $serviceSalesTotal,
    'api' => $apiSalesTotal,
];

$customerDebtService = ReportService::getCustomerDebtData(['show_all' => '1']);
$customerDebtRes = $reportController->customerDebt(
    $requestFactory->createServerRequest('GET', '/api/reports/customer-debt')->withQueryParams(['show_all' => '1']),
    $responseFactory->createResponse()
);
$customerDebtApi = decodeResponse($customerDebtRes);

$serviceCustomerDebt = isset($customerDebtService['summary']['debt_amount']) ? (float) $customerDebtService['summary']['debt_amount'] : 0.0;
$apiCustomerDebt = isset($customerDebtApi['data']['summary']['debt_amount']) ? (float) $customerDebtApi['data']['summary']['debt_amount'] : 0.0;
$results[] = [
    'case' => 'report_customer_debt_amount',
    'pass' => abs($serviceCustomerDebt - $apiCustomerDebt) < 0.0001,
    'service' => $serviceCustomerDebt,
    'api' => $apiCustomerDebt,
];

$inventoryService = ReportService::getInventoryData();
$inventoryRes = $reportController->inventory(
    $requestFactory->createServerRequest('GET', '/api/reports/inventory'),
    $responseFactory->createResponse()
);
$inventoryApi = decodeResponse($inventoryRes);

$serviceInventoryCount = is_array($inventoryService) ? count($inventoryService) : 0;
$apiInventoryCount = isset($inventoryApi['data']['items']) && is_array($inventoryApi['data']['items']) ? count($inventoryApi['data']['items']) : 0;
$results[] = [
    'case' => 'report_inventory_count',
    'pass' => $serviceInventoryCount === $apiInventoryCount,
    'service' => $serviceInventoryCount,
    'api' => $apiInventoryCount,
];

$orderService = OrderService::getOrderListData(['q' => '', 'page' => 1], 20);
$orderController = new OrderApiController();
$orderRes = $orderController->list(
    $requestFactory->createServerRequest('GET', '/api/orders')->withQueryParams(['q' => '', 'page' => 1]),
    $responseFactory->createResponse()
);
$orderApi = decodeResponse($orderRes);

$serviceOrderCount = isset($orderService['orders']) && is_array($orderService['orders']) ? count($orderService['orders']) : 0;
$apiOrderCount = isset($orderApi['data']['items']) && is_array($orderApi['data']['items']) ? count($orderApi['data']['items']) : 0;
$results[] = [
    'case' => 'order_list_count',
    'pass' => $serviceOrderCount === $apiOrderCount,
    'service' => $serviceOrderCount,
    'api' => $apiOrderCount,
];

$orderInvalidRes = $orderController->detail(
    $requestFactory->createServerRequest('GET', '/api/orders/0'),
    $responseFactory->createResponse(),
    ['id' => 0]
);
$orderInvalidApi = decodeResponse($orderInvalidRes);
$results[] = [
    'case' => 'order_detail_invalid_id_validation',
    'pass' => (int) $orderInvalidRes->getStatusCode() === 422
        && isset($orderInvalidApi['message'])
        && (string) $orderInvalidApi['message'] === 'Invalid order id',
    'service' => 'Invalid order id (expected)',
    'api' => isset($orderInvalidApi['message']) ? (string) $orderInvalidApi['message'] : '',
];

$allPass = true;
foreach ($results as $result) {
    if (empty($result['pass'])) {
        $allPass = false;
        break;
    }
}

echo json_encode([
    'all_pass' => $allPass,
    'cases' => $results,
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
