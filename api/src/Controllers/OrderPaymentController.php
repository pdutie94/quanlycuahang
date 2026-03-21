<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Services\PaymentService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class OrderPaymentController extends BaseController
{
    public function __construct(private readonly array $config)
    {
    }

    public function paymentStore(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $orderId = (int) ($args['id'] ?? 0);
        $body = (array) ($request->getParsedBody() ?? []);

        $amount = (float) ($body['amount'] ?? 0);
        $note = trim((string) ($body['note'] ?? ''));
        $paymentMethod = trim((string) ($body['payment_method'] ?? 'cash'));
        if (!in_array($paymentMethod, ['cash', 'bank'], true)) {
            $paymentMethod = 'cash';
        }

        if ($orderId <= 0 || $amount <= 0) {
            return $this->error($response, 'Dữ liệu thanh toán không hợp lệ', 400);
        }

        $pdo = Database::getInstance($this->config['db']);
        $service = new PaymentService($pdo);
        $service->recordOrderPayment($orderId, $amount, $note, $paymentMethod);

        return $this->success($response, ['order_id' => $orderId], 'Đã ghi nhận thanh toán', 201);
    }

    public function returnStore(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $orderId = (int) ($args['id'] ?? 0);
        $body = (array) ($request->getParsedBody() ?? []);

        $note = trim((string) ($body['note'] ?? ''));
        $returnAll = (bool) ($body['return_all'] ?? false);
        $items = $body['items'] ?? [];

        if (!is_array($items)) {
            $items = [];
        }

        if ($orderId <= 0 || (!$returnAll && $items === [])) {
            return $this->error($response, 'Dữ liệu hoàn tiền không hợp lệ', 400);
        }

        $pdo = Database::getInstance($this->config['db']);
        $service = new PaymentService($pdo);
        $result = $service->recordOrderReturn($orderId, $items, $note, $returnAll);

        return $this->success($response, [
            'order_id' => $orderId,
            'total_reduce_amount' => $result['total_reduce_amount'] ?? 0,
            'refund_amount' => $result['refund_amount'] ?? 0,
        ], 'Đã ghi nhận trả hàng', 201);
    }
}
