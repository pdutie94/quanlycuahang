# Danh Sách Tasks Chỉnh Sửa Dự Án Quản Lý Cửa Hàng

Dưới đây là danh sách tasks chi tiết cho các chỉnh sửa đã đề xuất. Mỗi task được mô tả rõ ràng, actionable và có thể thực hiện độc lập. Tasks được nhóm theo ưu tiên triển khai (Cao nhất → Trung bình → Thấp hơn) để dễ theo dõi. Bạn có thể đánh dấu hoàn thành từng task khi thực hiện.

## Ưu Tiên Cao Nhất

- [x] **Xóa dead code - ProductRepository.php:** Xóa file `app/Repositories/ProductRepository.php` vì không được sử dụng ở đâu.
- [x] **Thêm composite indexes cho database:** Chạy các lệnh SQL để thêm indexes: `idx_orders_filter`, `idx_order_items_order`, `idx_products_filter`.
- [x] **Refactor OrderController::view() để tránh N+1 queries:** Sửa method `view()` trong `OrderController.php` để gộp 4 queries thành 1-2 queries với JOINs.
- [x] **Refactor OrderController::index() subquery:** Thay subquery count items bằng JOIN đơn giản trong method `index()`.
- [x] **Tách OrderController thành multiple controllers:** Tạo 3 files mới: `OrderListController.php`, `OrderDetailController.php`, `OrderPaymentController.php` và di chuyển methods tương ứng từ `OrderController.php` gốc.
- [x] **Khôi phục header list (listHeader) cho danh sách đơn hàng:** Thêm `listHeader` dữ liệu vào `OrderListController::index()` để partial `list_header.php` hiển thị header + thanh tìm kiếm.

## Ưu Tiên Trung Bình

- [x] **Refactor Product model để tránh duplication:** Tạo method private `buildBaseProductQuery($includeSearch = false)` trong `Product.php` để tái sử dụng logic giữa `searchPaginate()` và `paginate()`.
- [x] **Thêm constants cho magic strings:** Tạo classes `OrderStatus.php` và `PaymentStatus.php` với constants thay thế hard-code strings như 'cancelled', 'paid'.
- [x] **Thêm Validation Service:** Tạo file `app/Services/ValidationService.php` với methods `validateOrderData()` và `validateProductData()`.
- [x] **Thêm Error Logging:** Cài đặt Monolog (hoặc fallback logger), cấu hình trong `config/config.php` và sử dụng trong controllers để log errors.
- [x] **Di chuyển business logic ra khỏi controllers:** Chuyển logic tính toán (tổng tiền, kiểm tra stock) từ controllers vào Services/Repositories tương ứng (OrderService hiện đã có `calculateOrderSummary`, `normalizeOrderDate`).

## Ưu Tiên Thấp Hơn

- [x] **Tối ưu subqueries trong Product model:** Thay subquery tính sold_qty bằng materialized view hoặc bảng summary trong `Product.php`.
- [x] **Thêm caching cho aggregated data:** Cấu hình APCu/Redis và cache kết quả SUM queries trong `ReportService.php`.
- [x] **Thêm health check endpoint:** Mở rộng `public/health.php` để monitor database connections và disk space.
- [ ] **Thêm application metrics:** Log performance metrics (query times, memory usage) trong controllers và services.
- [x] **Version control cho database:** Tổ chức lại scripts trong `sql/` folder với version tracking.
- [x] **Documentation:** Thêm `README.md` với setup instructions và API docs nếu có.

## Lưu Ý

- Mỗi task nên được test sau khi hoàn thành (chạy ứng dụng, kiểm tra queries, code standards).
- Nếu gặp khó khăn, bắt đầu từ tasks ưu tiên cao để thấy cải thiện ngay lập tức.
- Tổng cộng 16 tasks, ước tính thời gian: 2-4 tuần tùy kinh nghiệm. Nếu cần code samples cho task cụ thể, hãy cho biết!
