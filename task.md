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

### Phase 2 — Chuẩn hóa UI

#### 2.1 Layout & shell
- [ ] Rà soát `app/Views/layout/main.php`
- [ ] Chuẩn hóa spacing, container, max width theo `ui-guidelines.md`
- [ ] Kiểm tra top bar / bottom navigation / menu sheet

#### 2.2 Shared UI components
- [ ] Chuẩn hóa button variants
- [ ] Chuẩn hóa input + label + inline error
- [ ] Chuẩn hóa card/list item dùng chung
- [ ] Chuẩn hóa modal/sheet theo kiểu trượt từ dưới lên

#### 2.3 Screen cleanup
- [ ] Giảm shadow / nested borders / hiệu ứng thừa
- [ ] Đồng bộ trạng thái màu success / warning / danger
- [ ] Tối ưu các màn hình danh sách theo card list thay vì bố cục nặng

### Phase 3 — API foundation

#### 3.1 Chuẩn bị nền tảng API
- [ ] Thiết kế response JSON chuẩn
- [ ] Tạo helper/response formatter dùng chung
- [ ] Xác định naming route `/api/...`
- [ ] Xác định các endpoint ưu tiên theo module

#### 3.2 API rollout theo module
- [ ] Tạo API cho products
- [ ] Tạo API cho customers
- [ ] Tạo API cho orders
- [ ] Tạo API cho purchases
- [ ] Tạo API cho reports

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
- [ ] So sánh behavior trước và sau refactor
- [ ] Kiểm tra validation và edge cases
- [ ] Kiểm tra số liệu báo cáo
- [ ] Xác nhận không thay đổi business logic

#### 5.2 Verification
- [ ] Kiểm tra syntax PHP cho các file đã sửa
- [ ] Chạy lại các luồng chính: orders / products / purchases / reports
- [ ] Ghi lại các thay đổi đã xác minh xong trong task tracker

---

## Ghi chú triển khai

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

- Mỗi thay đổi cần bám theo `/.github/copilot-instructions.md`
- UI cần tuân thủ `/.github/ui-guidelines.md`
- Đối chiếu dữ liệu qua `/db-structure.sql` và `/database-notes.md`
- Không refactor quá nhiều module trong cùng một lần
- Ưu tiên thay đổi nhỏ, dễ kiểm chứng
