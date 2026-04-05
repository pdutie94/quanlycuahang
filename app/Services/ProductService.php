<?php

class ProductService
{
    public static function getProductListData(array $queryParams, int $perPage = 20): array
    {
        $filters = self::normalizeListFilters($queryParams);

        if ($filters['keyword'] !== '') {
            $totalCount = Product::countByKeyword($filters['keyword'], $filters['stockFilter'], $filters['categoryId']);
        } else {
            $totalCount = Product::countAll($filters['stockFilter'], $filters['categoryId']);
        }

        $pagination = self::resolvePagination($filters['page'], $totalCount, $perPage);

        if ($filters['keyword'] !== '') {
            $products = Product::searchPaginate(
                $filters['keyword'],
                $pagination['perPage'],
                $pagination['offset'],
                $filters['stockFilter'],
                $filters['categoryId']
            );
        } else {
            $products = Product::paginate(
                $pagination['perPage'],
                $pagination['offset'],
                $filters['stockFilter'],
                $filters['categoryId']
            );
        }

        return [
            'products' => $products,
            'keyword' => $filters['keyword'],
            'stockFilter' => $filters['stockFilter'],
            'categoryId' => $filters['categoryId'],
            'page' => $pagination['page'],
            'totalPages' => $pagination['totalPages'],
            'totalCount' => $totalCount,
            'perPage' => $pagination['perPage'],
            'productUnitsByProduct' => self::loadProductUnitsByProduct($products),
            'categories' => self::getCategories(),
        ];
    }

    public static function getCreateFormData(): array
    {
        return array_merge(self::getFormMeta('Thêm sản phẩm'), [
            'success' => true,
            'units' => Unit::all(),
            'product' => null,
            'productUnits' => [],
            'categories' => self::getCategories(),
            'inventoryQtyBase' => null,
            'productLogs' => [],
        ]);
    }

    public static function getEditFormData($id): array
    {
        $id = (int) $id;
        if ($id <= 0) {
            return [
                'success' => false,
                'redirect' => 'product',
            ];
        }

        $product = Product::find($id);
        if (!$product) {
            return [
                'success' => false,
                'redirect' => 'product',
            ];
        }

        return array_merge(self::getFormMeta('Sửa sản phẩm'), [
            'success' => true,
            'product' => $product,
            'units' => Unit::all(),
            'productUnits' => class_exists('ProductUnit') ? ProductUnit::findByProduct($id) : [],
            'categories' => self::getCategories(),
            'inventoryQtyBase' => class_exists('Inventory') ? Inventory::getQtyBase($id) : null,
            'productLogs' => class_exists('ProductLog') ? ProductLog::findByProduct($id) : [],
        ]);
    }

    public static function createProduct(array $payload, ?string $imagePath = null): array
    {
        $data = self::buildProductData($payload);
        $productId = Product::create($data);

        if ($imagePath) {
            Product::updateImagePath($productId, $imagePath);
        }

        $baseUnitId = isset($data['base_unit_id']) ? (int) $data['base_unit_id'] : 0;
        $unitRows = self::buildUnitRows($payload, $baseUnitId);
        ProductUnit::saveForProduct($productId, $unitRows);

        $qty = null;
        if (class_exists('Inventory') && array_key_exists('inventory_qty_base', $payload)) {
            $qty = self::normalizeNonNegativeQuantity($payload['inventory_qty_base']);
            Inventory::setQtyBase($productId, $qty);
        }

        self::logInitialProductSetup($productId, $unitRows);

        $redirectAction = isset($payload['redirect']) ? $payload['redirect'] : 'exit';

        return [
            'success' => true,
            'message' => 'Đã thêm sản phẩm.',
            'productId' => $productId,
            'redirect' => $redirectAction === 'stay' ? 'product/edit?id=' . $productId : 'product',
        ];
    }

    public static function updateProduct(int $id, array $payload, array $options = []): array
    {
        $product = Product::find($id);
        if (!$product) {
            return [
                'success' => false,
                'redirect' => 'product',
            ];
        }

        $inventoryOnly = isset($payload['inventory_only']) && $payload['inventory_only'] === '1';
        $oldSnapshot = self::captureBaseUnitSnapshot($product);

        if (!$inventoryOnly) {
            $data = self::buildProductData($payload);
            Product::update($id, $data);

            if (!empty($options['updateImage'])) {
                $imagePath = array_key_exists('imagePath', $options) ? $options['imagePath'] : null;
                Product::updateImagePath($id, $imagePath);
            }

            $baseUnitId = isset($data['base_unit_id']) ? (int) $data['base_unit_id'] : 0;
            $unitRows = self::buildUnitRows($payload, $baseUnitId);
            ProductUnit::saveForProduct($id, $unitRows);
            self::logUpdatedProductSetup($id, $oldSnapshot, $unitRows);
        }

        if (class_exists('Inventory') && array_key_exists('inventory_qty_base', $payload)) {
            $qty = self::normalizeNonNegativeQuantity($payload['inventory_qty_base']);
            Inventory::setQtyBase($id, $qty);
        }

        if ($inventoryOnly) {
            return [
                'success' => true,
                'message' => 'Đã cập nhật tồn kho sản phẩm.',
                'redirect' => 'product/edit?id=' . $id,
            ];
        }

        $redirectAction = isset($payload['redirect']) ? $payload['redirect'] : 'exit';

        return [
            'success' => true,
            'message' => 'Đã cập nhật sản phẩm.',
            'redirect' => $redirectAction === 'stay' ? 'product/edit?id=' . $id : 'product',
        ];
    }

    public static function normalizeListFilters(array $queryParams): array
    {
        $keyword = isset($queryParams['q']) ? trim((string) $queryParams['q']) : '';

        $stockFilter = isset($queryParams['stock']) ? (string) $queryParams['stock'] : 'all';
        if (!in_array($stockFilter, ['all', 'in_stock', 'low_stock', 'out_of_stock'], true)) {
            $stockFilter = 'all';
        }

        $categoryId = isset($queryParams['category_id']) ? (int) $queryParams['category_id'] : 0;
        if ($categoryId <= 0) {
            $categoryId = null;
        }

        $page = isset($queryParams['page']) ? (int) $queryParams['page'] : 1;
        if ($page < 1) {
            $page = 1;
        }

        return [
            'keyword' => $keyword,
            'stockFilter' => $stockFilter,
            'categoryId' => $categoryId,
            'page' => $page,
        ];
    }

    private static function getFormMeta(string $title): array
    {
        return [
            'title' => $title,
            'detailHeader' => [
                'title' => $title,
                'back_url' => 'product',
                'back_label' => 'Quay lại',
                'actions_view' => 'products/_detail_header_actions',
            ],
        ];
    }

    private static function getCategories(): array
    {
        if (!class_exists('ProductCategory')) {
            return [];
        }

        return ProductCategory::all();
    }

    private static function buildProductData(array $payload): array
    {
        $data = [
            'name' => isset($payload['name']) ? trim((string) $payload['name']) : '',
            'code' => isset($payload['code']) ? trim((string) $payload['code']) : '',
            'category_id' => isset($payload['category_id']) && $payload['category_id'] !== '' ? (int) $payload['category_id'] : null,
            'base_unit_id' => isset($payload['base_unit_id']) ? $payload['base_unit_id'] : null,
        ];

        if (array_key_exists('min_stock_qty', $payload)) {
            $data['min_stock_qty'] = self::normalizeOptionalPositiveQuantity($payload['min_stock_qty']);
        }

        return $data;
    }

    private static function buildUnitRows(array $payload, int $baseUnitId): array
    {
        if ($baseUnitId <= 0) {
            return [];
        }

        $allowFraction = isset($payload['allow_fraction']) && $payload['allow_fraction'] === '1' ? 1 : 0;

        return [[
            'unit_id' => $baseUnitId,
            'factor' => 1,
            'price_sell' => Money::parsePrice(isset($payload['price_sell_single']) ? $payload['price_sell_single'] : ''),
            'price_cost' => Money::parsePrice(isset($payload['price_cost_single']) ? $payload['price_cost_single'] : ''),
            'allow_fraction' => $allowFraction,
            'min_step' => self::normalizeMinStep(isset($payload['min_step']) ? $payload['min_step'] : '1'),
        ]];
    }

    private static function captureBaseUnitSnapshot(array $product): array
    {
        $snapshot = [
            'priceSell' => null,
            'priceCost' => null,
            'allowFraction' => null,
            'minStep' => null,
        ];

        if (!class_exists('ProductUnit')) {
            return $snapshot;
        }

        $productId = isset($product['id']) ? (int) $product['id'] : 0;
        if ($productId <= 0) {
            return $snapshot;
        }

        $oldUnits = ProductUnit::findByProduct($productId);
        if (empty($oldUnits) || !is_array($oldUnits)) {
            return $snapshot;
        }

        $oldBaseUnitId = isset($product['base_unit_id']) ? (int) $product['base_unit_id'] : 0;
        $oldUnitRow = null;
        foreach ($oldUnits as $row) {
            if ($oldBaseUnitId > 0 && isset($row['unit_id']) && (int) $row['unit_id'] === $oldBaseUnitId) {
                $oldUnitRow = $row;
                break;
            }
            if ($oldUnitRow === null && isset($row['factor']) && (float) $row['factor'] === 1.0) {
                $oldUnitRow = $row;
            }
        }

        if ($oldUnitRow === null) {
            return $snapshot;
        }

        if (isset($oldUnitRow['price_sell'])) {
            $snapshot['priceSell'] = (float) $oldUnitRow['price_sell'];
        }
        if (isset($oldUnitRow['price_cost'])) {
            $snapshot['priceCost'] = (float) $oldUnitRow['price_cost'];
        }
        if (isset($oldUnitRow['allow_fraction'])) {
            $snapshot['allowFraction'] = (int) $oldUnitRow['allow_fraction'] ? 1 : 0;
        }
        if (isset($oldUnitRow['min_step']) && $oldUnitRow['min_step'] !== null) {
            $snapshot['minStep'] = (float) $oldUnitRow['min_step'];
        }

        return $snapshot;
    }

    private static function logInitialProductSetup(int $productId, array $unitRows)
    {
        if (!class_exists('ProductLog')) {
            return;
        }

        if (!empty($unitRows)) {
            $priceSell = isset($unitRows[0]['price_sell']) ? (float) $unitRows[0]['price_sell'] : 0;
            $priceCost = isset($unitRows[0]['price_cost']) ? (float) $unitRows[0]['price_cost'] : 0;
            $allowFractionFlag = !empty($unitRows[0]['allow_fraction']) ? 1 : 0;
            $minStepValue = isset($unitRows[0]['min_step']) ? (float) $unitRows[0]['min_step'] : 1;

            $parts = [];
            if ($priceSell > 0) {
                $parts[] = 'Giá bán: ' . Money::format($priceSell);
            }
            if ($priceCost > 0) {
                $parts[] = 'Giá nhập: ' . Money::format($priceCost);
            }
            $parts[] = 'Bán lẻ: ' . ($allowFractionFlag ? 'Có' : 'Không');

            $minStepText = rtrim(rtrim(number_format($minStepValue, 4, ',', ''), '0'), ',');
            if ($minStepText === '') {
                $minStepText = '1';
            }
            $parts[] = 'Bước lẻ: ' . $minStepText;

            ProductLog::create([
                'product_id' => $productId,
                'action' => 'init_price',
                'detail' => implode('; ', $parts),
            ]);
        }
    }

    private static function logUpdatedProductSetup(int $productId, array $oldSnapshot, array $unitRows)
    {
        if (!class_exists('ProductLog') || empty($unitRows)) {
            return;
        }

        $changes = [];
        $newPriceSellValue = isset($unitRows[0]['price_sell']) ? (float) $unitRows[0]['price_sell'] : null;
        $newPriceCostValue = isset($unitRows[0]['price_cost']) ? (float) $unitRows[0]['price_cost'] : null;
        $newAllowFraction = isset($unitRows[0]['allow_fraction']) && $unitRows[0]['allow_fraction'] ? 1 : 0;
        $newMinStepValue = isset($unitRows[0]['min_step']) ? (float) $unitRows[0]['min_step'] : null;

        if ($oldSnapshot['priceSell'] !== null && $newPriceSellValue !== null && abs($newPriceSellValue - $oldSnapshot['priceSell']) > 0.0001) {
            $changes[] = 'Giá bán: ' . Money::format($oldSnapshot['priceSell']) . ' -> ' . Money::format($newPriceSellValue);
        }

        if ($oldSnapshot['priceCost'] !== null && $newPriceCostValue !== null && abs($newPriceCostValue - $oldSnapshot['priceCost']) > 0.0001) {
            $changes[] = 'Giá nhập: ' . Money::format($oldSnapshot['priceCost']) . ' -> ' . Money::format($newPriceCostValue);
        }

        if ($oldSnapshot['allowFraction'] !== null && $newAllowFraction !== (int) $oldSnapshot['allowFraction']) {
            $changes[] = 'Bán lẻ: ' . ($oldSnapshot['allowFraction'] ? 'Có' : 'Không') . ' -> ' . ($newAllowFraction ? 'Có' : 'Không');
        }

        if ($oldSnapshot['minStep'] !== null && $newMinStepValue !== null && abs($newMinStepValue - $oldSnapshot['minStep']) > 0.000001) {
            $fromText = rtrim(rtrim(number_format($oldSnapshot['minStep'], 4, ',', ''), '0'), ',');
            if ($fromText === '') {
                $fromText = '1';
            }

            $toText = rtrim(rtrim(number_format($newMinStepValue, 4, ',', ''), '0'), ',');
            if ($toText === '') {
                $toText = '1';
            }

            $changes[] = 'Bước lẻ: ' . $fromText . ' -> ' . $toText;
        }

        if (empty($changes)) {
            return;
        }

        ProductLog::create([
            'product_id' => $productId,
            'action' => 'update_price',
            'detail' => implode('; ', $changes),
        ]);
    }

    private static function normalizeOptionalPositiveQuantity($value)
    {
        $number = self::normalizeNonNegativeQuantity($value);
        return $number > 0 ? $number : null;
    }

    private static function normalizeNonNegativeQuantity($value): float
    {
        $raw = str_replace(' ', '', (string) $value);
        $raw = str_replace(',', '.', $raw);
        $number = (float) $raw;
        if ($number < 0) {
            $number = 0;
        }

        return $number;
    }

    private static function normalizeMinStep($value): float
    {
        $number = self::normalizeNonNegativeQuantity($value);
        if ($number <= 0) {
            $number = 1;
        }

        return $number;
    }

    private static function resolvePagination(int $page, int $totalCount, int $perPage): array
    {
        $totalPages = (int) ceil($totalCount / $perPage);
        if ($totalPages < 1) {
            $totalPages = 1;
        }

        if ($page > $totalPages) {
            $page = $totalPages;
        }

        return [
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => $totalPages,
            'offset' => ($page - 1) * $perPage,
        ];
    }

    private static function loadProductUnitsByProduct(array $products): array
    {
        if (empty($products) || !class_exists('ProductUnit')) {
            return [];
        }

        $productIds = [];
        foreach ($products as $product) {
            $productId = isset($product['id']) ? (int) $product['id'] : 0;
            if ($productId > 0) {
                $productIds[] = $productId;
            }
        }

        return ProductUnit::findByProductIds($productIds);
    }
}
