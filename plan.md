# Refactor Plan

## Mục tiêu

Refactor dự án quản lý cửa hàng theo định hướng trong `/.github/copilot-instructions.md` và `/.github/ui-guidelines.md`, với ưu tiên:

1. **Giữ nguyên logic nghiệp vụ từ hệ thống cũ**
2. **Cải thiện cấu trúc code**
3. **Chuẩn hóa UI theo phong cách flat, mobile-first**
4. **Chuẩn bị nền tảng để chuyển dần sang API-first + Vue 3**

---

## Hiện trạng dự án

- Dự án hiện vẫn chủ yếu là **PHP MVC tự xây dựng**, render view trực tiếp từ `app/Views`.
- Đã có bước đầu tách `Services` và `Repositories`, nhưng controller vẫn còn khá dày.
- UI đang dùng TailwindCSS và đã có một số thành phần mobile-friendly.
- Chưa có kiến trúc `Slim 4 API + Vue 3 SPA` hoàn chỉnh như trong định hướng tương lai.

---

## Nguyên tắc refactor

- **Không thay đổi business logic**
- **Không thay đổi workflow cũ nếu chưa có yêu cầu rõ ràng**
- **Legacy MVC là source of truth**
- **Refactor nhỏ, an toàn, theo từng module**
- **Ưu tiên tách trách nhiệm: Controller -> Service -> Repository**

### Tài liệu tham chiếu

- `/.github/copilot-instructions.md`
- `/.github/ui-guidelines.md`
- `/db-structure.sql`
- `/database-notes.md`

---

## Kế hoạch thực hiện

### Phase 1 — Ổn định và làm mỏng Controller

Mục tiêu:
- Di chuyển logic nghiệp vụ ra `Services`
- Di chuyển truy vấn phức tạp ra `Repositories`
- Giữ controller chủ yếu xử lý request/response/validation

Ưu tiên:
1. `OrderListController.php`
2. `OrderDetailController.php`
3. `ProductController.php`
4. `PurchaseController.php`
5. `ReportController.php`

---

### Phase 2 — Chuẩn hóa UI theo `ui-guidelines.md`

Mục tiêu:
- Đồng nhất style flat UI
- Không dùng shadow nặng
- Dùng border, spacing rõ ràng
- Chuẩn hóa button, input, card, list, sheet, bottom navigation

Việc cần làm:
- Rà soát `app/Views/layout/main.php`
- Chuẩn hóa các partial dùng chung
- Giảm nested borders và hiệu ứng không cần thiết

---

### Phase 3 — Chuẩn bị lớp API song song

Mục tiêu:
- Tạo nền tảng API mà không phá vỡ hệ thống hiện tại
- Chuẩn hóa response JSON
- Dùng lại business logic từ Service

Ưu tiên API:
1. Products
2. Customers
3. Orders
4. Purchases
5. Reports

---

### Phase 4 — Chuyển dần frontend sang Vue 3

Mục tiêu:
- Migrate theo từng module nhỏ
- Dùng Composition API
- Ưu tiên composables, tránh duplication

Thứ tự đề xuất:
1. Product list
2. Customer list
3. Order list
4. Order detail
5. POS

---

### Phase 5 — Kiểm tra legacy parity

Checklist:
- Luồng cũ còn hoạt động đúng
- Tính toán tiền / công nợ / lợi nhuận không thay đổi
- Form validation không sai lệch
- Báo cáo vẫn giữ đúng số liệu

---

## Rủi ro cần lưu ý

- `OrderDetailController.php` và `OrderController.old.php` chứa logic phức tạp, cần đối chiếu kỹ trước khi tách
- `ReportController.php` ảnh hưởng trực tiếp tới số liệu kinh doanh
- Không nên migrate toàn bộ sang SPA trong một lần

---

## Bước tiếp theo đề xuất

Bắt đầu với:
1. Refactor `OrderListController.php`
2. Chuẩn hóa UI cho màn hình danh sách sản phẩm/đơn hàng
3. Tạo lớp API đầu tiên cho `products` hoặc `orders`
