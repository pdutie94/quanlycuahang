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

### Inventory màn hình hiện có

Các màn hình đang tồn tại trong `app/Views` gồm:

1. `auth/login`
2. `dashboard/index`
3. `products/index`, `products/form`
4. `customers/index`, `customers/view`, `customers/form`, `customers/payment`
5. `orders/index`, `orders/view`, `orders/form`, `orders/invoice`, `orders/return`, `orders/preview`
6. `pos/index`
7. `purchases/index`, `purchases/view`, `purchases/form`
8. `suppliers/index`, `suppliers/view`, `suppliers/form`
9. `categories/index`
10. `units/index`
11. `reports/index`, `reports/sales`, `reports/customer_debt`, `reports/supplier_debt`, `reports/inventory`, `reports/missing_cost`, `reports/sales_orders_list`
12. `users/change_password`
13. `migration/index`
14. `spa/index` như shell/entry cho frontend Vue

### Trạng thái Vue hiện tại

- Đã có module Vue cho `product list`, `customer list`, `order list`, `order detail`, `POS`.
- Chưa có module Vue riêng cho `purchases`, `suppliers`, `categories`, `units`, `reports`, `dashboard`, `auth`, `users/change_password`, `migration`.
- Một số module mới chỉ migrate **một phần**, chưa phải migrate toàn bộ workflow:
  - `products`: mới có list Vue, form vẫn legacy PHP.
  - `customers`: mới có list Vue, detail/form/payment vẫn legacy PHP.
  - `orders`: đã có list/detail Vue, nhưng form/invoice/return vẫn legacy PHP.

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

### Phase 2 — Chuẩn hóa UI theo `ui-guidelines.md` với TailwindCSS

Mục tiêu:

- Giữ nguyên stack render hiện tại: PHP view + TailwindCSS
- Chưa chuyển sang Vue trong Phase 2
- Đồng nhất style flat UI
- Không dùng shadow nặng
- Dùng border, spacing rõ ràng
- Chuẩn hóa button, input, card, list, sheet, bottom navigation

Việc cần làm:

- Ưu tiên refactor bằng Tailwind utilities, partials và class patterns dùng chung
- Rà soát `app/Views/layout/main.php`
- Chuẩn hóa các partial dùng chung
- Giảm nested borders và hiệu ứng không cần thiết

---

### Phase 3 — Chuẩn bị lớp API song song

Mục tiêu:

- Tạo nền tảng API mà không phá vỡ hệ thống hiện tại
- Dùng **Slim 4** làm framework API (không dựng API bằng PHP thuần)
- Chuẩn hóa response JSON
- Dùng lại business logic từ Service

Nguyên tắc triển khai:

- API-first theo hướng `Route -> Controller -> Service -> Repository`
- Legacy MVC vẫn là source of truth cho nghiệp vụ
- Chỉ thay đổi cấu trúc kỹ thuật, không đổi logic tính toán/workflow cũ
- Ưu tiên triển khai Slim 4 song song để migrate cuốn chiếu

Checklist triển khai Slim 4 (đề xuất):

1. Bootstrap Slim 4 (composer + bootstrap + container cơ bản)
2. Tạo skeleton API: `/api`, middleware JSON, error handler, auth middleware
3. Chuẩn hóa `ApiResponse` và format lỗi dùng chung
4. Migrate endpoint theo module ưu tiên (Products -> Customers -> Orders -> Purchases -> Reports)
5. Kiểm tra parity từng endpoint với luồng MVC cũ trước khi mở rộng Vue

Ưu tiên API:

1. Products
2. Customers
3. Orders
4. Purchases
5. Reports

---

### Phase 4 — Chuyển dần frontend sang Vue 3

Mục tiêu:

- Chỉ bắt đầu sau khi Phase 2 đã ổn định UI bằng Tailwind trên view PHP hiện tại
- Migrate theo từng module nhỏ
- Dùng Composition API
- Ưu tiên composables, tránh duplication

Nguyên tắc theo dõi:

- Theo từng **màn hình thực tế**, không đánh dấu theo module nếu mới migrate một phần.
- Phân biệt rõ `list`, `detail`, `form`, `payment`, `invoice`, `return`, `report screen`.
- Nếu một màn Vue vẫn phải nhảy sang route legacy cho action phụ, coi là **migrate một phần**.

#### 4.1 Frontend foundation

1. Tạo cấu trúc `components/pages/composables/services`
2. Chuẩn hóa `useFetch`, `useForm`, `usePagination`, `useToast`
3. Thiết lập router Vue và shell SPA

#### 4.2 Sales flow ưu tiên cao

1. Product list
2. Customer list
3. Order list
4. Order detail
5. POS

#### 4.3 Sales flow còn lại

1. Purchase list
2. Purchase detail
3. Purchase form
4. Customer detail
5. Customer payment
6. Customer form
7. Order form/edit
8. Order return
9. Product form
10. Order invoice

#### 4.4 Master data screens

1. Supplier list
2. Supplier form
3. Supplier detail
4. Category list
5. Unit list

#### 4.5 Dashboard và reports

1. Customer debt report
2. Supplier debt report
3. Inventory report
4. Sales report
5. Missing cost report
6. Report index
7. Sales orders list report
8. Dashboard index

#### 4.6 System và account screens

1. Change password
2. Login
3. Migration utility screen

Ưu tiên triển khai sau phase 4.2:

1. Purchases
2. Suppliers
3. Categories/Units
4. Debt/Inventory reports
5. Dashboard/Auth/System screens

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

Trạng thái hiện tại: các nhóm migrate chính của `Phase 4.x` đã hoàn tất và đã có checklist parity ở `Phase 5`.

Bước tiếp theo phù hợp là:

1. Duy trì vòng lặp parity UI/UX với legacy theo từng màn (ưu tiên lỗi tương tác người dùng báo trực tiếp).
2. Tiếp tục chuẩn hóa thành phần dùng chung để tránh lệch giao diện giữa các màn (ví dụ card danh sách và modal).
3. Giữ nhịp verify sau mỗi cụm thay đổi bằng build + kiểm tra lỗi để đảm bảo không phát sinh regression.
4. Chỉ tối ưu cấu trúc nội bộ khi không làm thay đổi behavior nghiệp vụ đã chốt.
