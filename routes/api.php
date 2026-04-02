<?php

use App\Modules\Product\ProductApiController;
use App\Modules\Customer\CustomerApiController;
use App\Modules\Order\OrderApiController;
use App\Modules\Purchase\PurchaseApiController;
use App\Modules\Report\ReportApiController;
use App\Shared\Middleware\ApiAuthMiddleware;
use App\Shared\Response\ApiResponse;
use Slim\App;

return function (App $app) {
    $app->get('/api/health', function ($request, $response) {
        return ApiResponse::success($response, [
            'status' => 'ok',
            'service' => 'slim4-api',
            'time' => date('c'),
        ]);
    });

    $app->group('/api', function ($group) {
        $productController = new ProductApiController();
        $group->get('/products', [$productController, 'list']);
        $group->get('/products/{id}', [$productController, 'detail']);
        $group->post('/products', [$productController, 'create']);
        $group->put('/products/{id}', [$productController, 'update']);
        $group->patch('/products/{id}', [$productController, 'update']);

        $customerController = new CustomerApiController();
        $group->get('/customers', [$customerController, 'list']);
        $group->get('/customers/{id}', [$customerController, 'detail']);
        $group->post('/customers', [$customerController, 'create']);
        $group->put('/customers/{id}', [$customerController, 'update']);
        $group->patch('/customers/{id}', [$customerController, 'update']);
        $group->delete('/customers/{id}', [$customerController, 'delete']);
        $group->get('/customers/orders/{order_id}/payment', [$customerController, 'paymentInfo']);
        $group->post('/customers/orders/{order_id}/payment', [$customerController, 'paymentStore']);

        $orderController = new OrderApiController();
        $group->get('/orders', [$orderController, 'list']);
        $group->get('/orders/{id}', [$orderController, 'detail']);
        $group->get('/orders/{id}/preview', [$orderController, 'preview']);
        $group->post('/orders', [$orderController, 'create']);
        $group->put('/orders/{id}', [$orderController, 'update']);
        $group->patch('/orders/{id}', [$orderController, 'update']);

        $purchaseController = new PurchaseApiController();
        $group->get('/purchases', [$purchaseController, 'list']);
        $group->get('/purchases/{id}', [$purchaseController, 'detail']);
        $group->post('/purchases', [$purchaseController, 'create']);
        $group->put('/purchases/{id}', [$purchaseController, 'update']);
        $group->patch('/purchases/{id}', [$purchaseController, 'update']);

        $reportController = new ReportApiController();
        $group->get('/reports/sales', [$reportController, 'sales']);
        $group->get('/reports/customer-debt', [$reportController, 'customerDebt']);
        $group->get('/reports/supplier-debt', [$reportController, 'supplierDebt']);
        $group->get('/reports/inventory', [$reportController, 'inventory']);
        $group->post('/reports/inventory/adjust', [$reportController, 'inventoryAdjust']);
    })->add(new ApiAuthMiddleware());
};
