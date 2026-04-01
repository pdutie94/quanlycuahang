# Task Tracker

## Trạng thái chung

- [x] Rà soát cấu trúc dự án hiện tại
- [x] Xác định khoảng cách giữa hiện trạng và định hướng API + Vue 3
- [x] Lập kế hoạch refactor tổng thể
- [ ] Bắt đầu refactor theo từng phase

---

## Backlog ưu tiên

### Phase 1 — Refactor cấu trúc backend

#### 1.1 Orders module
- [ ] Rà soát `OrderController.old.php` để đối chiếu logic gốc
- [ ] Tách logic filter/search/pagination khỏi `OrderListController.php`
- [ ] Tách query danh sách đơn hàng sang `OrderRepository.php`
- [ ] Tách logic summary/tính toán đơn hàng sang `OrderService.php`
- [ ] Rà soát `OrderPaymentController.php` để gom xử lý thanh toán vào service
- [ ] Giảm độ phức tạp của `OrderDetailController.php` theo từng nhóm chức năng

#### 1.2 Products module
- [ ] Tách logic filter sản phẩm khỏi `ProductController.php`
- [ ] Tách query danh sách / tồn kho / units sang model hoặc repository phù hợp
- [ ] Chuẩn hóa xử lý create/update sản phẩm theo service layer
- [ ] Rà soát form sản phẩm để giữ nguyên behavior cũ

#### 1.3 Purchases module
- [ ] Tách xử lý nhập hàng khỏi `PurchaseController.php`
- [ ] Gom logic tính tiền / đơn vị / tồn kho vào service riêng
- [ ] Chuẩn hóa truy vấn chi tiết phiếu nhập

#### 1.4 Reports module
- [ ] Rà soát `ReportController.php` theo từng loại báo cáo
- [ ] Tách phần tổng hợp số liệu sang `ReportService.php`
- [ ] Giảm logic SQL lặp lại trong controller
- [ ] Kiểm tra lại các chỉ số nhạy cảm trước và sau refactor

#### 1.5 Master data module
- [ ] Chuẩn hóa controller cho `CustomerController.php`
- [ ] Chuẩn hóa controller cho `SupplierController.php`
- [ ] Chuẩn hóa controller cho `CategoryController.php`
- [ ] Chuẩn hóa controller cho `UnitController.php`

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

- Mỗi thay đổi cần bám theo `/.github/copilot-instructions.md`
- UI cần tuân thủ `/.github/ui-guidelines.md`
- Không refactor quá nhiều module trong cùng một lần
- Ưu tiên thay đổi nhỏ, dễ kiểm chứng
