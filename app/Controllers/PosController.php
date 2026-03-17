<?php

class PosController extends Controller
{
    public function index()
    {
        $this->requireLogin();

        $pdo = Database::getInstance();

        $productStmt = $pdo->query('SELECT p.*, u.name AS base_unit_name, c.name AS category_name
            FROM products p
            JOIN units u ON p.base_unit_id = u.id
            LEFT JOIN product_categories c ON p.category_id = c.id
            WHERE p.deleted_at IS NULL
            ORDER BY p.name');
        $products = $productStmt->fetchAll();

        $unitStmt = $pdo->query('SELECT pu.*, u.name AS unit_name
            FROM product_units pu
            JOIN units u ON pu.unit_id = u.id
            ORDER BY pu.product_id, u.name');
        $unitRows = $unitStmt->fetchAll();

        $productUnitsByProduct = [];
        foreach ($unitRows as $row) {
            $pid = isset($row['product_id']) ? (int) $row['product_id'] : 0;
            if ($pid <= 0) {
                continue;
            }
            if (!isset($productUnitsByProduct[$pid])) {
                $productUnitsByProduct[$pid] = [];
            }
            $productUnitsByProduct[$pid][] = $row;
        }
        $customers = [];
        try {
            if (class_exists('Customer')) {
                $customers = Customer::all();
            }
        } catch (Exception $e) {
            $customers = [];
        }

        $this->render('pos/index', [
            'title' => 'POS bán hàng',
            'products' => $products,
            'productUnitsByProduct' => $productUnitsByProduct,
            'customers' => $customers,
        ]);
    }

    public function store()
    {
        $this->requireLogin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('pos');
        }

        $this->verifyCsrfToken();

		$itemsJson = isset($_POST['items_json']) ? $_POST['items_json'] : '';
		$items = json_decode($itemsJson, true);
		if (!is_array($items)) {
			$items = [];
		}

		$paymentStatus = isset($_POST['payment_status']) && $_POST['payment_status'] === 'debt' ? 'debt' : 'pay';
		$paymentMethod = isset($_POST['payment_method']) ? $_POST['payment_method'] : 'cash';
		$paymentAmountRaw = isset($_POST['payment_amount']) ? (string) $_POST['payment_amount'] : '0';
		$paymentAmount = Money::parseAmount($paymentAmountRaw);
		if ($paymentAmount < 0) {
			$paymentAmount = 0;
		}

        $customerId = isset($_POST['customer_id']) ? (int) $_POST['customer_id'] : 0;
        $customerName = isset($_POST['customer_name']) ? trim($_POST['customer_name']) : '';
        $customerPhone = isset($_POST['customer_phone']) ? trim($_POST['customer_phone']) : '';
        $customerAddress = isset($_POST['customer_address']) ? trim($_POST['customer_address']) : '';
        $note = isset($_POST['note']) ? trim($_POST['note']) : '';

        $pdo = Database::getInstance();
        $pdo->beginTransaction();

        try {
            if ($customerId) {
                $customer = Customer::find($customerId);
                if (!$customer) {
                    $customerId = 0;
                }
            }

            if (!$customerId && ($customerName !== '' || $customerPhone !== '' || $customerAddress !== '')) {
                $customerId = Customer::create([
                    'name' => $customerName,
                    'phone' => $customerPhone,
                    'address' => $customerAddress,
                ]);
            }

            $totalAmount = 0;
            $totalCost = 0;
            $preparedItems = [];
            foreach ($items as $item) {
                $productId = isset($item['product_id']) ? (int) $item['product_id'] : 0;
                $unitId = isset($item['unit_id']) ? (int) $item['unit_id'] : 0;
                $qty = isset($item['quantity']) ? (float) $item['quantity'] : 0;
                $price = isset($item['price']) ? (float) $item['price'] : 0;
                if ($productId <= 0 || $unitId <= 0 || $qty <= 0 || $price < 0) {
                    continue;
                }
                $productUnit = ProductUnit::findByProductAndUnit($productId, $unitId);
                if (!$productUnit) {
                    continue;
                }

                $factor = isset($productUnit['factor']) ? (float) $productUnit['factor'] : 0;
                if ($factor <= 0) {
                    $factor = 1;
                }

                $allowFraction = isset($productUnit['allow_fraction']) ? (int) $productUnit['allow_fraction'] : 0;
                $minStep = isset($productUnit['min_step']) ? (float) $productUnit['min_step'] : 1;
                if ($minStep <= 0) {
                    $minStep = 1;
                }

                if ($allowFraction === 0) {
                    $qtyInt = (int) round($qty);
                    if (abs($qty - $qtyInt) > 0.0001) {
                        continue;
                    }
                    $qty = $qtyInt;
                } else {
                    if ($minStep > 0) {
                        $steps = floor(($qty + 0.0000001) / $minStep);
                        $qty = $steps * $minStep;
                        if ($qty <= 0) {
                            continue;
                        }
                    }
                }

                $qtyBase = $qty * $factor;
                $priceSell = $price;
                $priceCost = isset($productUnit['price_cost']) ? (float) $productUnit['price_cost'] : 0;
                $amount = $priceSell * $qty;

                $totalAmount += $amount;
                $totalCost += $priceCost * $qty;

                $preparedItems[] = [
                    'product_id' => $productId,
                    'product_unit_id' => (int) $productUnit['id'],
                    'qty' => $qty,
                    'qty_base' => $qtyBase,
                    'real_weight' => null,
                    'price_sell' => $priceSell,
                    'price_cost' => $priceCost,
                    'amount' => $amount,
                ];
            }

            $manualNames = isset($_POST['manual_item_name']) ? $_POST['manual_item_name'] : [];
            $manualUnits = isset($_POST['manual_unit_name']) ? $_POST['manual_unit_name'] : [];
            $manualQtys = isset($_POST['manual_qty']) ? $_POST['manual_qty'] : [];
            $manualPricesBuy = isset($_POST['manual_price_buy']) ? $_POST['manual_price_buy'] : [];
            $manualPricesSell = isset($_POST['manual_price_sell']) ? $_POST['manual_price_sell'] : [];

            $manualBuild = ManualLineItemBuilder::buildFromArrays($manualNames, $manualUnits, $manualQtys, $manualPricesBuy, $manualPricesSell);
            $manualItems = isset($manualBuild['items']) && is_array($manualBuild['items']) ? $manualBuild['items'] : [];
            $manualTotalBuy = isset($manualBuild['total_buy_amount']) ? (float) $manualBuild['total_buy_amount'] : 0.0;
            $manualTotalSell = isset($manualBuild['total_sell_amount']) ? (float) $manualBuild['total_sell_amount'] : 0.0;

            if ($manualTotalBuy < 0) {
                $manualTotalBuy = 0.0;
            }
            if ($manualTotalSell < 0) {
                $manualTotalSell = 0.0;
            }

            if ($manualTotalSell > 0) {
                $totalAmount += $manualTotalSell;
            }
            if ($manualTotalBuy > 0) {
                $totalCost += $manualTotalBuy;
            }

			if (empty($preparedItems) && empty($manualItems)) {
				$pdo->rollBack();
				$this->setFlash('error', 'Giỏ hàng không hợp lệ.');
				$this->redirect('pos');
			}

			$discountType = isset($_POST['discount_type']) ? $_POST['discount_type'] : 'none';
			if (!in_array($discountType, ['none', 'fixed', 'percent'], true)) {
				$discountType = 'none';
			}
			$discountValueRaw = isset($_POST['discount_value']) ? (string) $_POST['discount_value'] : '0';
			$discountValue = (float) str_replace([',', ' '], ['', ''], $discountValueRaw);
			if ($discountValue < 0) {
				$discountValue = 0;
			}
			$discountAmount = 0;
			if ($discountType === 'fixed') {
				$discountAmount = (float) $discountValue;
			} elseif ($discountType === 'percent') {
				if ($discountValue > 100) {
					$discountValue = 100;
				}
				$discountAmount = round($totalAmount * $discountValue / 100);
			}
			if ($discountAmount < 0) {
				$discountAmount = 0;
			}
			if ($discountAmount > $totalAmount) {
				$discountAmount = $totalAmount;
			}

			$surchargeRaw = isset($_POST['surcharge_amount']) ? (string) $_POST['surcharge_amount'] : '0';
			$surchargeAmount = Money::parseAmount($surchargeRaw);
			if ($surchargeAmount < 0) {
				$surchargeAmount = 0;
			}

			$finalTotal = $totalAmount - $discountAmount + $surchargeAmount;
			if ($finalTotal < 0) {
				$finalTotal = 0;
			}
			$finalTotal = Money::roundDownThousand($finalTotal);

			if ($paymentAmount > $finalTotal) {
				$paymentAmount = $finalTotal;
			}

			$status = 'debt';
			$paidAmount = 0.0;

			if ($paymentStatus === 'pay') {
				if ($finalTotal <= 0) {
					$status = 'paid';
					$paidAmount = 0.0;
				} elseif ($paymentAmount >= $finalTotal) {
					$status = 'paid';
					$paidAmount = $finalTotal;
				} elseif ($paymentAmount > 0) {
					$status = 'debt';
					$paidAmount = $paymentAmount;
				}
			} else {
				if ($finalTotal <= 0) {
					$status = 'paid';
				}
			}

            $orderId = Order::create([
                'customer_id' => $customerId ?: null,
                'total_amount' => $finalTotal,
                'total_cost' => $totalCost,
                'paid_amount' => $paidAmount,
                'status' => $status,
                'order_status' => 'pending',
                'note' => $note,
				'discount_type' => $discountType,
				'discount_value' => $discountValue,
				'discount_amount' => $discountAmount,
				'surcharge_amount' => $surchargeAmount,
            ]);

			foreach ($preparedItems as $row) {
				$row['order_id'] = $orderId;
				OrderItem::create($row);
			}

            if (!empty($manualItems)) {
                foreach ($manualItems as $row) {
                    $row['order_id'] = $orderId;
                    OrderManualItem::create($row);
                }
            }

			if ($paymentStatus === 'pay' && $paidAmount > 0) {
				Payment::create([
					'type' => 'customer',
					'customer_id' => $customerId ?: null,
					'supplier_id' => null,
					'order_id' => $orderId,
					'purchase_id' => null,
					'amount' => $paidAmount,
					'note' => $note,
				]);
			}

            $pdo->commit();
            $this->setFlash('success', 'Đã lưu đơn hàng #' . $orderId . '.');
            $this->redirect('pos');
        } catch (Exception $e) {
            $pdo->rollBack();
            $this->setFlash('error', 'Không thể lưu đơn hàng: ' . $e->getMessage());
            $this->redirect('pos');
        }
    }
}
