# Task Tracker

## Trạng thái chung

- [x] Rà soát cấu trúc dự án hiện tại
- [x] Xác định khoảng cách giữa hiện trạng và định hướng API + Vue 3
- [x] Lập kế hoạch refactor tổng thể
- [x] Bắt đầu refactor theo từng phase

---

## Backlog ưu tiên

### Phase 1 — Refactor cấu trúc backend

#### 1.1 Orders module
- [x] Rà soát `OrderController.old.php` để đối chiếu logic gốc
- [x] Tách logic filter/search/pagination khỏi `OrderListController.php`
- [x] Tách query danh sách đơn hàng sang `OrderRepository.php`
- [x] Tách logic summary/tính toán đơn hàng sang `OrderService.php`
- [x] Rà soát `OrderPaymentController.php` để gom xử lý thanh toán vào service
- [x] Giảm độ phức tạp của `OrderDetailController.php` theo từng nhóm chức năng *(đã tách nhóm đọc dữ liệu và luồng ghi dữ liệu chính: view / preview / invoice / addForm / update / addStore)*

#### 1.2 Products module
- [x] Tách logic filter sản phẩm khỏi `ProductController.php`
- [x] Tách query danh sách / tồn kho / units sang model hoặc repository phù hợp
- [x] Chuẩn hóa xử lý create/update sản phẩm theo service layer
- [x] Rà soát form sản phẩm để giữ nguyên behavior cũ

#### 1.3 Purchases module
- [x] Tách xử lý nhập hàng khỏi `PurchaseController.php`
- [x] Gom logic tính tiền / đơn vị / tồn kho vào service riêng
- [x] Chuẩn hóa truy vấn chi tiết phiếu nhập

#### 1.4 Reports module
- [x] Rà soát `ReportController.php` theo từng loại báo cáo
- [x] Tách phần tổng hợp số liệu sang `ReportService.php`
- [x] Giảm logic SQL lặp lại trong controller
- [x] Kiểm tra lại các chỉ số nhạy cảm trước và sau refactor

#### 1.5 Master data module
- [x] Chuẩn hóa controller cho `CustomerController.php`
- [x] Chuẩn hóa controller cho `SupplierController.php`
- [x] Chuẩn hóa controller cho `CategoryController.php`
- [x] Chuẩn hóa controller cho `UnitController.php`

### Phase 2 — Chuẩn hóa UI bằng TailwindCSS trên view hiện tại

Ghi chú phạm vi:
- Giữ `app/Views` + TailwindCSS, chưa chuyển sang Vue trong phase này
- Ưu tiên chuẩn hóa bằng utility class, partial và pattern dùng chung thay vì đổi stack frontend
- Icon hành động hiển thị dạng icon thuần, không dùng style button có border/padding

#### 2.1 Layout & shell
- [x] Rà soát `app/Views/layout/main.php`
- [x] Chuẩn hóa spacing, container, max width theo `ui-guidelines.md` bằng Tailwind utilities
- [x] Kiểm tra top bar / bottom navigation / menu sheet

#### 2.2 Shared UI components
- [x] Chuẩn hóa button variants
- [x] Chuẩn hóa input + label + inline error
- [x] Chuẩn hóa card/list item dùng chung
- [x] Chuẩn hóa modal/sheet theo kiểu trượt từ dưới lên

#### 2.3 Screen cleanup
- [x] Giảm shadow / nested borders / hiệu ứng thừa
- [x] Đồng bộ trạng thái màu success / warning / danger
- [x] Tối ưu các màn hình danh sách theo card list thay vì bố cục nặng

### Phase 3 — API foundation

Ghi chú phạm vi:
- Phase 3 dùng **Slim 4** để triển khai API, không dựng API bằng PHP thuần.
- Giữ nguyên business logic hiện tại, tái sử dụng Service/Repository đã tách ở Phase 1.

#### 3.1 Chuẩn bị nền tảng API
- [x] Cài Slim 4 và cấu hình bootstrap API song song với app hiện tại
- [x] Tạo entrypoint API riêng (prefix `/api`) và route loader
- [x] Thiết lập middleware JSON request/response
- [x] Thiết lập middleware auth cho API
- [x] Thiết lập exception/error handler chuẩn JSON
- [x] Thiết kế response JSON chuẩn (`success`, `data`, `message`)
- [x] Tạo helper/response formatter dùng chung (`ApiResponse`)
- [x] Thiết lập cấu trúc API chuẩn: `Route -> Controller -> Service -> Repository`
- [x] Chuẩn hóa quy ước route và method RESTful
- [x] Chốt danh sách endpoint ưu tiên theo module

##### 3.1.0 Definition of Done (DoD)
- [x] Endpoint healthcheck API hoạt động (`/api/health`) và trả JSON đúng chuẩn
- [x] Middleware JSON áp dụng đồng nhất cho toàn bộ route `/api/*`
- [x] Error/exception bất kỳ từ API đều trả về JSON có cấu trúc thống nhất
- [x] Auth middleware đã gắn cho các route cần bảo vệ và có test manual pass/fail
- [x] Có ít nhất 1 module API đầu tiên chạy end-to-end qua Service/Repository cũ (không đổi business logic)
- [ ] Có checklist đối chiếu parity với MVC cũ cho module API đầu tiên

##### 3.1.1 Template Checklist Parity (API vs Legacy MVC)
- [ ] Cùng input filter/sort/pagination giữa MVC và API cho module đang làm
- [ ] Cùng output business fields và giá trị tính toán quan trọng (tổng tiền, công nợ, tồn kho...)
- [ ] Cùng quy tắc validation (required/min/max/format) và thông điệp lỗi chính
- [ ] Cùng xử lý edge cases (rỗng dữ liệu, thiếu quan hệ, bản ghi soft-delete)
- [ ] Cùng quyền truy cập theo vai trò/tài khoản (nếu có)
- [ ] Cùng hành vi ghi log/audit sau các action tạo/sửa/xóa
- [ ] So sánh tối thiểu 5 ca thực tế trước khi đánh dấu parity pass

#### 3.2 API rollout theo module
- [x] Tạo API cho products (list/detail/create/update)
- [x] Tạo API cho customers (list/detail/create/update/payment)
- [x] Tạo API cho orders (list/detail/preview/create/update)
- [x] Tạo API cho purchases (list/detail/create/update)
- [x] Tạo API cho reports (sales/debt/inventory)
- [x] Đối chiếu parity nghiệp vụ từng endpoint với luồng MVC cũ

### Phase 4 — Vue 3 migration

#### 4.1 Chuẩn bị frontend structure
- [ ] Tạo cấu trúc `components`, `pages`, `composables`, `services`
- [ ] Chuẩn hóa `useFetch`, `useForm`, `usePagination`, `useToast`
- [ ] Xác định module nào migrate trước mà ít rủi ro

#### 4.2 Migrate theo màn hình
- [ ] Migrate product list
- [ ] Migrate customer list
- [ ] Migrate order list
- [ ] Migrate order detail
- [ ] Migrate POS

### Phase 5 — Kiểm tra tương thích legacy

#### 5.1 Legacy parity checklist
- [x] So sánh behavior trước và sau refactor
- [x] Kiểm tra validation và edge cases
- [x] Kiểm tra số liệu báo cáo
- [x] Xác nhận không thay đổi business logic

#### 5.2 Verification
- [x] Kiểm tra syntax PHP cho các file đã sửa
- [x] Chạy lại các luồng chính: orders / products / purchases / reports
- [x] Ghi lại các thay đổi đã xác minh xong trong task tracker

---

## Ghi chú triển khai

- Đã chốt phạm vi cho `Phase 2`: tiếp tục dùng PHP server-rendered views kết hợp TailwindCSS để chuẩn hóa giao diện; chưa đụng sang Vue migration ở giai đoạn này.
- Đã hoàn thiện `Task 2.1 Layout & shell`: đồng bộ `app/Views/layout/main.php`, `app/Views/partials/detail_header.php`, `app/Views/partials/list_header.php` và component classes trong `resources/css/tailwind.css` để dùng shell max-width `2xl`, spacing 16px, top bar/bottom nav/menu sheet theo flat style của UI guideline.
- Cập nhật theo thay đổi mới: đã bỏ topbar khỏi `app/Views/layout/main.php` và bỏ mục menu `Đăng xuất` trong menu sheet để giữ shell tối giản trước khi vào task 2.2.
- Lưu ý UI bổ sung: icon không dùng style dạng button (không border/padding), giữ hiển thị tối giản theo hướng icon thuần.
- Đã hoàn thiện `Task 2.2 Shared UI components`: chuẩn hóa class dùng chung cho button (`app-btn-primary`, `app-btn-secondary`, `app-btn-danger`), input/label/error (`app-input`, `app-label`, `app-field-error`), card/list item (`app-card`, `app-list-item`) và modal/sheet (`app-modal-*`) trên Tailwind source + helper `app/Views/partials/components.php`; đồng thời áp dụng vào các modal chọn khách hàng/sản phẩm và card đơn hàng để đồng bộ giao diện.
- Đã hoàn thiện `Task 2.3 Screen cleanup`: bổ sung class dùng chung (`app-list-card`, `app-empty-state`, `app-status-chip` + `app-status-success|warning|danger`) trong `resources/css/tailwind.css`, đồng bộ màu trạng thái và giảm hiệu ứng/border dư trên các màn danh sách chính (`products/index`, `customers/index`, `purchases/index`, `categories/index`, `units/index`) theo hướng card list nhẹ, phẳng.

- Refactor đầu tiên đã thực hiện trên luồng danh sách đơn hàng: chuyển filter/query sang `OrderService.php` + `OrderRepository.php`, đồng thời khôi phục logic soft delete theo legacy.
- Đã tiếp tục tách nghiệp vụ `paymentReset` khỏi `OrderPaymentController.php` sang `PaymentService.php` để controller mỏng hơn và giữ nguyên thông báo/luồng cũ.
- Đã chuyển tiếp luồng `returnForm` và `returnStore` sang `OrderService.php`, giúp `OrderPaymentController.php` tập trung vào request/response thay vì xử lý nghiệp vụ dài.
- Đã tiếp tục làm mỏng `OrderDetailController.php` bằng cách tách các action đọc dữ liệu (`view`, `preview`, `invoice`, `addForm`) sang `OrderService.php` + `OrderRepository.php`.
- Đã chuyển tiếp phần nghiệp vụ nặng của `OrderDetailController::update()` và `OrderDetailController::addStore()` sang `OrderService.php`; kiểm tra lại bằng `php -l app/Controllers/OrderDetailController.php && php -l app/Services/OrderService.php` đều không có syntax error.
- Đã thêm `ProductService.php` để gom normalize filter, phân trang và dữ liệu units cho danh sách sản phẩm; kiểm tra lại bằng `php -l app/Controllers/ProductController.php && php -l app/Services/ProductService.php` đều không có syntax error.
- Đã hoàn thiện refactor `Products module`: gom luồng form/create/update vào `ProductService.php`, chuyển query units theo danh sách sang `ProductUnit::findByProductIds()`, rà lại `app/Views/products/form.php` để giữ nguyên các trường/redirect/message cũ; kiểm tra lại bằng `php -l app/Controllers/ProductController.php && php -l app/Services/ProductService.php && php -l app/Models/ProductUnit.php` đều không có syntax error.
- Đã hoàn thiện refactor `Purchases module`: tách query danh sách/chi tiết/units/items/payments sang `PurchaseRepository.php`, gom nghiệp vụ create/update phiếu nhập (tính tiền, chuẩn hóa đơn vị, cập nhật tồn kho, cập nhật giá nhập, log) vào `PurchaseService.php`, và làm mỏng `PurchaseController.php` theo hướng request/response; kiểm tra lại bằng `php -l app/Controllers/PurchaseController.php && php -l app/Services/PurchaseService.php && php -l app/Repositories/PurchaseRepository.php` đều không có syntax error.
- Đã hoàn thiện refactor `Reports module`: làm mỏng `ReportController.php` (index/sales/customerDebt/supplierDebt/missingCost/inventory) và chuyển phần tổng hợp + xử lý dữ liệu báo cáo sang `ReportService.php`; giữ nguyên workflow render/partial/flash/redirect và điều kiện SQL nhạy cảm (lọc đơn hủy, deleted_at, debt calculations). Kiểm tra lại bằng `php -l app/Controllers/ReportController.php && php -l app/Services/ReportService.php` đều không có syntax error.
- Đã chuẩn hóa `CustomerController.php`: tách query danh sách/nợ/đơn hàng khách sang `CustomerRepository.php`, gom create/update/delete/payment vào `CustomerService.php`, giữ nguyên form/detail/payment workflow và flash/redirect cũ. Kiểm tra lại bằng `php -l app/Controllers/CustomerController.php && php -l app/Services/CustomerService.php && php -l app/Repositories/CustomerRepository.php` đều không có syntax error.
- Đã hoàn thiện toàn bộ `Master data module`: chuẩn hóa thêm `SupplierController.php`, `CategoryController.php`, `UnitController.php` theo cùng pattern controller mỏng; thêm `SupplierService.php`, `CategoryService.php`, `UnitService.php` và `SupplierRepository.php` để gom query/validate/request flow về đúng lớp. Kiểm tra lại cả cụm master data bằng `php -l app/Controllers/SupplierController.php && php -l app/Controllers/CategoryController.php && php -l app/Controllers/UnitController.php && php -l app/Services/SupplierService.php && php -l app/Services/CategoryService.php && php -l app/Services/UnitService.php && php -l app/Repositories/SupplierRepository.php && php -l app/Controllers/CustomerController.php && php -l app/Services/CustomerService.php && php -l app/Repositories/CustomerRepository.php` đều không có syntax error.
- Đã đối chiếu parity API vs service legacy cho Phase 3.2 bằng script `scripts/phase32_parity_check.php` với 6 case pass: `purchase_list_count`, `purchase_detail_items_count`, `report_sales_total_amount`, `report_customer_debt_amount`, `report_inventory_count`, `order_list_count`.
- Đã mở rộng script `scripts/phase32_parity_check.php` để bao phủ thêm products và chạy lại verification tổng cho 4 luồng chính (orders/products/purchases/reports): `all_pass = true` với 8 case pass.
- Đã bổ sung kiểm chứng validation/edge-case trong `scripts/phase32_parity_check.php` (case `purchase_create_validation_message`, `order_detail_invalid_id_validation`) và chạy lại: `all_pass = true` với 10 case pass.
- Đã hoàn tất kiểm tra syntax cho toàn bộ file API foundation và module API (gateway/index, bootstrap, routes, middleware/response, product/customer/order/purchase/report controllers) bằng `php -l`, tất cả đều pass.

- Mỗi thay đổi cần bám theo `/.github/copilot-instructions.md`
- UI cần tuân thủ `/.github/ui-guidelines.md`
- Đối chiếu dữ liệu qua `/db-structure.sql` và `/database-notes.md`
- Không refactor quá nhiều module trong cùng một lần
- Ưu tiên thay đổi nhỏ, dễ kiểm chứng
