# Database Notes

## Mục đích

Tài liệu này tóm tắt cấu trúc database hiện tại từ `db-structure.sql` để phục vụ việc **refactor an toàn**, đối chiếu logic legacy, và tránh thay đổi sai các trường dữ liệu quan trọng.

> Nguồn tham chiếu chính: `db-structure.sql`

---

## Nguyên tắc khi refactor liên quan DB

- **Không đổi tên field nếu chưa thật sự cần thiết**
- **Không thay đổi kiểu dữ liệu nghiệp vụ cũ nếu chưa có yêu cầu rõ ràng**
- **Ưu tiên giữ tương thích với dữ liệu hiện tại**
- Khi tối ưu query, cần đối chiếu index sẵn có trước
- Các bảng liên quan tiền, công nợ, tồn kho cần kiểm tra kỹ trước/sau khi refactor

---

## Nhóm bảng chính

### 1. Bán hàng (Sales)

#### `orders`
Thông tin đơn hàng bán ra.

Các cột quan trọng:
- `order_code`
- `customer_id`
- `order_date`
- `total_amount`
- `total_cost`
- `paid_amount`
- `status` (`paid`, `debt`)
- `order_status` (`pending`, `completed`, `cancelled`)
- `discount_type`
- `discount_value`
- `discount_amount`
- `surcharge_amount`
- `deleted_at`

Lưu ý:
- Đây là bảng **rất nhạy cảm**, không được thay đổi logic tính toán khi refactor.
- Có dùng soft delete qua `deleted_at`.

#### `order_items`
Chi tiết sản phẩm trong đơn hàng.

Các cột quan trọng:
- `order_id`
- `product_id`
- `product_unit_id`
- `qty`
- `qty_base`
- `real_weight`
- `price_sell`
- `price_cost`
- `amount`

Lưu ý:
- `qty_base` ảnh hưởng trực tiếp đến logic tồn kho.
- `product_unit_id` liên kết đến đơn vị bán cụ thể.

#### `order_manual_items`
Các dòng nhập tay trong đơn hàng.

Lưu ý:
- Có thể chứa logic đặc biệt từ legacy.
- Không nên đơn giản hóa nếu chưa đọc kỹ controller cũ.

#### `order_logs`
Log thay đổi liên quan đơn hàng.

---

### 2. Sản phẩm & kho (Products & Inventory)

#### `products`
Thông tin sản phẩm.

Các cột quan trọng:
- `name`
- `code`
- `category_id`
- `base_unit_id`
- `min_stock_qty`
- `image_path`
- `deleted_at`

#### `product_units`
Các đơn vị tính / quy đổi / giá theo đơn vị.

Các cột quan trọng:
- `product_id`
- `unit_id`
- `factor`
- `price_sell`
- `price_cost`
- `allow_fraction`
- `min_step`

Lưu ý:
- Đây là bảng cốt lõi cho logic bán hàng theo đơn vị.
- Khi refactor form order/purchase cần kiểm tra kỹ phần quy đổi đơn vị.

#### `inventory`
Tồn kho quy đổi theo đơn vị cơ sở.

Các cột quan trọng:
- `product_id`
- `qty_base`
- `updated_at`

Lưu ý:
- `qty_base` là số liệu gốc để theo dõi tồn kho.
- Các thay đổi ở `orders` và `purchases` có thể ảnh hưởng trực tiếp bảng này.

#### `product_categories`
Danh mục sản phẩm.

#### `product_logs`
Lịch sử thay đổi sản phẩm.

#### `product_sales_summary`
Bảng tổng hợp số lượng bán ra.

Lưu ý:
- Có thể đang phục vụ tối ưu báo cáo/tìm kiếm.
- Cần giữ nguyên logic cập nhật hoặc fallback hiện có.

---

### 3. Nhập hàng (Purchases)

#### `purchases`
Thông tin phiếu nhập.

Các cột quan trọng:
- `purchase_code`
- `supplier_id`
- `purchase_date`
- `total_amount`
- `paid_amount`
- `status`
- `discount_type`
- `discount_value`
- `discount_amount`

#### `purchase_items`
Chi tiết sản phẩm nhập.

Các cột quan trọng:
- `purchase_id`
- `product_id`
- `product_unit_id`
- `qty`
- `qty_base`
- `price_cost`
- `amount`

#### `purchase_logs`
Log thay đổi phiếu nhập.

Lưu ý:
- Logic nhập hàng ảnh hưởng trực tiếp tới `inventory`.
- Cần kiểm tra kỹ tính nhất quán giữa `purchase_items.qty_base` và tồn kho.

---

### 4. Thanh toán & công nợ

#### `payments`
Lưu các khoản thanh toán cho khách hàng hoặc nhà cung cấp.

Các cột quan trọng:
- `type` (`customer`, `supplier`)
- `customer_id`
- `supplier_id`
- `order_id`
- `purchase_id`
- `amount`
- `paid_at`
- `note`

Lưu ý:
- Bảng này liên quan trực tiếp tới công nợ khách hàng và nhà cung cấp.
- Khi refactor payment flow, cần đối chiếu chặt với `orders.paid_amount` và `purchases.paid_amount`.

---

### 5. Đối tượng nghiệp vụ cơ bản

#### `customers`
Khách hàng.

#### `suppliers`
Nhà cung cấp.

#### `units`
Đơn vị tính.

#### `users`
Người dùng hệ thống.

---

### 6. Dự án / xuất vật tư

#### `projects`
Thông tin công trình / dự án.

#### `project_issues`
Phiếu xuất cho công trình.

#### `project_issue_items`
Chi tiết vật tư xuất theo phiếu.

Lưu ý:
- Đây là nhóm bảng mở rộng, nên refactor sau các luồng chính order/purchase/product.

---

## Quan hệ dữ liệu quan trọng

### Sales flow
- `orders.customer_id` → `customers.id`
- `order_items.order_id` → `orders.id`
- `order_items.product_id` → `products.id`
- `order_items.product_unit_id` → `product_units.id`

### Purchase flow
- `purchases.supplier_id` → `suppliers.id`
- `purchase_items.purchase_id` → `purchases.id`
- `purchase_items.product_id` → `products.id`
- `purchase_items.product_unit_id` → `product_units.id`

### Inventory flow
- `inventory.product_id` → `products.id`
- `product_units.unit_id` → `units.id`
- `products.base_unit_id` → `units.id`

### Payment flow
- `payments.order_id` → `orders.id`
- `payments.purchase_id` → `purchases.id`
- `payments.customer_id` → `customers.id`
- `payments.supplier_id` → `suppliers.id`

---

## Các điểm nhạy cảm cần giữ nguyên

Khi refactor, cần đặc biệt tránh thay đổi logic liên quan đến:

- tính **tổng tiền đơn hàng**
- tính **giá vốn**
- tính **công nợ**
- tính **chiết khấu** và **phụ thu**
- quy đổi **đơn vị tính**
- cập nhật **tồn kho**
- trạng thái `paid/debt`, `pending/completed/cancelled`
- soft delete qua `deleted_at`

---

## Tham khảo khi làm việc

Khi refactor một module, nên đối chiếu theo thứ tự:

1. `db-structure.sql`
2. Controller cũ / legacy flow
3. Model / Service / Repository hiện tại
4. UI form và các trường submit thực tế

---

## Gợi ý sử dụng

- Dùng file này như checklist trước khi sửa `orders`, `purchases`, `products`, `payments`
- Nếu phát hiện thêm rule nghiệp vụ từ code cũ, có thể bổ sung vào đây
- Không dùng file này để thay thế migration; đây chỉ là **tài liệu tham chiếu**
