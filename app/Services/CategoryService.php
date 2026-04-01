<?php

class CategoryService
{
    public static function getCategoryListData(array $queryParams, int $perPage = 20): array
    {
        $categories = [];
        $page = isset($queryParams['page']) ? (int) $queryParams['page'] : 1;
        if ($page < 1) {
            $page = 1;
        }

        $totalPages = 1;
        if (class_exists('ProductCategory')) {
            $totalCount = ProductCategory::countAll();
            $totalPages = (int) ceil($totalCount / $perPage);
            if ($totalPages < 1) {
                $totalPages = 1;
            }
            if ($page > $totalPages) {
                $page = $totalPages;
            }
            $offset = ($page - 1) * $perPage;
            $categories = ProductCategory::paginate($perPage, $offset);
        }

        return [
            'categories' => $categories,
            'page' => $page,
            'totalPages' => $totalPages,
        ];
    }

    public static function getCategoryFormData($id = 0): array
    {
        $id = (int) $id;
        $categories = class_exists('ProductCategory') ? ProductCategory::all() : [];
        if ($id <= 0) {
            return [
                'success' => true,
                'title' => 'Thêm danh mục',
                'category' => null,
                'categories' => $categories,
            ];
        }

        if (!class_exists('ProductCategory')) {
            return ['success' => false, 'redirect' => 'category'];
        }

        $category = ProductCategory::find($id);
        if (!$category) {
            return ['success' => false, 'redirect' => 'category'];
        }

        return [
            'success' => true,
            'title' => 'Sửa danh mục',
            'category' => $category,
            'categories' => $categories,
        ];
    }

    public static function createCategory(array $payload): array
    {
        $name = isset($payload['name']) ? trim((string) $payload['name']) : '';
        if ($name === '') {
            return [
                'success' => false,
                'message' => 'Tên danh mục là bắt buộc.',
                'redirect' => 'category',
            ];
        }

        if (class_exists('ProductCategory')) {
            ProductCategory::create(['name' => $name]);
        }

        return [
            'success' => true,
            'message' => 'Đã thêm danh mục sản phẩm.',
            'redirect' => 'category',
        ];
    }

    public static function updateCategory(int $id, array $payload): array
    {
        if ($id <= 0 || !class_exists('ProductCategory')) {
            return ['success' => false, 'redirect' => 'category'];
        }

        $category = ProductCategory::find($id);
        if (!$category) {
            return ['success' => false, 'redirect' => 'category'];
        }

        $name = isset($payload['name']) ? trim((string) $payload['name']) : '';
        if ($name === '') {
            return [
                'success' => false,
                'message' => 'Tên danh mục là bắt buộc.',
                'redirect' => 'category/edit?id=' . $id,
            ];
        }

        ProductCategory::update($id, ['name' => $name]);
        return [
            'success' => true,
            'message' => 'Đã cập nhật danh mục sản phẩm.',
            'redirect' => 'category',
        ];
    }

    public static function deleteCategory(int $id): array
    {
        if ($id <= 0 || !class_exists('ProductCategory')) {
            return ['success' => false, 'redirect' => 'category'];
        }

        if ($id === 1) {
            return [
                'success' => false,
                'message' => 'Không thể xóa danh mục mặc định.',
                'redirect' => 'category',
            ];
        }

        $pdo = Database::getInstance();
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM products WHERE category_id = ? AND deleted_at IS NULL');
        $stmt->execute([$id]);
        $usageCount = (int) $stmt->fetchColumn();

        if ($usageCount > 0) {
            return [
                'success' => false,
                'message' => 'Không thể xóa danh mục vì đang có sản phẩm sử dụng.',
                'redirect' => 'category',
            ];
        }

        ProductCategory::delete($id);
        return [
            'success' => true,
            'message' => 'Đã xóa danh mục sản phẩm.',
            'redirect' => 'category',
        ];
    }
}
