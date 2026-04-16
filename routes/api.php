<?php

use App\Modules\Product\ProductApiController;
use App\Modules\Auth\AuthApiController;
use App\Modules\Category\CategoryApiController;
use App\Modules\Customer\CustomerApiController;
use App\Modules\Order\OrderApiController;
use App\Modules\Pos\PosApiController;
use App\Modules\Purchase\PurchaseApiController;
use App\Modules\Purchase\PurchaseBootstrapApiController;
use App\Modules\Unit\UnitApiController;
use App\Modules\Supplier\SupplierApiController;
use App\Modules\Report\ReportApiController;
use App\Modules\System\MigrationApiController;
use App\Shared\Middleware\ApiAuthMiddleware;
use App\Shared\Response\ApiResponse;
use Slim\App;

return function (App $app) {
    $authController = new AuthApiController();
    $app->post('/api/auth/login', [$authController, 'login']);

    $app->get('/api/health', function ($request, $response) {
        return ApiResponse::success($response, [
            'status' => 'ok',
            'service' => 'slim4-api',
            'time' => date('c'),
        ]);
    });

    $app->group('/api', function ($group) {
        $authController = new AuthApiController();
        $migrationController = new MigrationApiController();

        $group->get('/auth/me', [$authController, 'me']);
        $group->post('/auth/logout', [$authController, 'logout']);
        $group->post('/auth/change-password', [$authController, 'changePassword']);

        $group->get('/migrations', [$migrationController, 'info']);
        $group->post('/migrations/apply', [$migrationController, 'apply']);
        $group->post('/migrations/run', [$migrationController, 'run']);

        $productController = new ProductApiController();
        $group->get('/products', [$productController, 'list']);
        $group->get('/products/bootstrap/form-data', [$productController, 'formData']);
        $group->get('/products/{id}/form-data', [$productController, 'formEditData']);
        $group->get('/products/{id}', [$productController, 'detail']);
        $group->post('/products', [$productController, 'create']);
        $group->put('/products/{id}', [$productController, 'update']);
        $group->patch('/products/{id}', [$productController, 'update']);
        $group->delete('/products/{id}', [$productController, 'delete']);

        $categoryController = new CategoryApiController();
        $group->get('/categories', [$categoryController, 'list']);
        $group->get('/categories/{id}', [$categoryController, 'detail']);
        $group->post('/categories', [$categoryController, 'create']);
        $group->put('/categories/{id}', [$categoryController, 'update']);
        $group->patch('/categories/{id}', [$categoryController, 'update']);
        $group->delete('/categories/{id}', [$categoryController, 'delete']);

        $unitController = new UnitApiController();
        $group->get('/units', [$unitController, 'list']);
        $group->post('/units', [$unitController, 'create']);
        $group->put('/units/{id}', [$unitController, 'update']);
        $group->patch('/units/{id}', [$unitController, 'update']);
        $group->delete('/units/{id}', [$unitController, 'delete']);

        $customerController = new CustomerApiController();
        $group->get('/customers', [$customerController, 'list']);
        $group->get('/customers/{id}/payment', [$customerController, 'customerPaymentInfo']);
        $group->post('/customers/{id}/payment', [$customerController, 'customerPaymentStore']);
        $group->get('/customers/{id}', [$customerController, 'detail']);
        $group->post('/customers', [$customerController, 'create']);
        $group->put('/customers/{id}', [$customerController, 'update']);
        $group->patch('/customers/{id}', [$customerController, 'update']);
        $group->delete('/customers/{id}', [$customerController, 'delete']);
        $group->get('/customers/orders/{order_id}/payment', [$customerController, 'paymentInfo']);
        $group->post('/customers/orders/{order_id}/payment', [$customerController, 'paymentStore']);

        $supplierController = new SupplierApiController();
        $group->get('/suppliers', [$supplierController, 'list']);
        $group->get('/suppliers/{id}', [$supplierController, 'detail']);
        $group->get('/suppliers/bootstrap/form-data', [$supplierController, 'formData']);
        $group->get('/suppliers/{id}/form-data', [$supplierController, 'formEditData']);
        $group->post('/suppliers', [$supplierController, 'store']);
        $group->put('/suppliers/{id}', [$supplierController, 'update']);
        $group->patch('/suppliers/{id}', [$supplierController, 'update']);
        $group->delete('/suppliers/{id}', [$supplierController, 'delete']);
        $group->post('/suppliers/{id}/payment', [$supplierController, 'paymentStore']);

        $orderController = new OrderApiController();
        $group->get('/orders', [$orderController, 'list']);
        $group->get('/orders/deleted', [$orderController, 'deletedList']);
        $group->get('/orders/{id}', [$orderController, 'detail']);
        $group->get('/orders/{id}/preview', [$orderController, 'preview']);
        $group->get('/orders/{id}/invoice', [$orderController, 'invoice']);
        $group->get('/orders/{id}/return', [$orderController, 'returnInfo']);
        $group->post('/orders', [$orderController, 'create']);
        $group->put('/orders/{id}', [$orderController, 'update']);
        $group->patch('/orders/{id}', [$orderController, 'update']);
        $group->post('/orders/{id}/status', [$orderController, 'updateStatus']);
        $group->delete('/orders/{id}', [$orderController, 'delete']);
        $group->post('/orders/{id}/restore', [$orderController, 'restore']);
        $group->post('/orders/purge-selected', [$orderController, 'purgeSelected']);
        $group->post('/orders/{id}/return', [$orderController, 'returnStore']);
        $group->post('/orders/{id}/payment', [$orderController, 'paymentStore']);
        $group->post('/orders/{id}/payment/reset', [$orderController, 'paymentReset']);

        $posController = new PosApiController();
        $group->get('/pos/bootstrap', [$posController, 'bootstrap']);

        $purchaseController = new PurchaseApiController();
        $purchaseBootstrapController = new PurchaseBootstrapApiController();
        $group->get('/purchases', [$purchaseController, 'list']);
        $group->get('/purchases/{id}', [$purchaseController, 'detail']);
        $group->get('/purchases/bootstrap/form-data', [$purchaseBootstrapController, 'formData']);
        $group->post('/purchases', [$purchaseController, 'create']);
        $group->put('/purchases/{id}', [$purchaseController, 'update']);
        $group->patch('/purchases/{id}', [$purchaseController, 'update']);
        $group->delete('/purchases/{id}', [$purchaseController, 'delete']);
        $group->post('/purchases/{id}/payment', [$purchaseController, 'paymentStore']);

        $reportController = new ReportApiController();
        $group->get('/reports/overview', [$reportController, 'overview']);
        $group->get('/reports/sales', [$reportController, 'sales']);
        $group->get('/reports/customer-debt', [$reportController, 'customerDebt']);
        $group->get('/reports/supplier-debt', [$reportController, 'supplierDebt']);
        $group->get('/reports/inventory', [$reportController, 'inventory']);
        $group->post('/reports/inventory/adjust', [$reportController, 'inventoryAdjust']);
        $group->get('/reports/missing-cost', [$reportController, 'missingCost']);
        $group->post('/reports/missing-cost/update', [$reportController, 'missingCostUpdate']);

        // Giá vốn
        $group->get('/reports/cost-update', [$reportController, 'costUpdate']);
        $group->post('/reports/cost-update', [$reportController, 'costUpdate']);
    })->add(new ApiAuthMiddleware());
};
