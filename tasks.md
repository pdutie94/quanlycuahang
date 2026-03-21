# Migration Tasks: PHP MVC → Slim 4 API + Vue 3 SPA

## Mục tiêu
Chuyển từ PHP render HTML sang:
- **Backend**: Slim 4 làm JSON API (`/api/*`)
- **Frontend**: Vue 3 + Vite + TypeScript làm Admin Panel SPA
- **Single domain**: cùng `public/`, `index.php` phân luồng API vs SPA

## Stack
| Layer | Tech |
|---|---|
| API | Slim 4 + PHP-DI |
| Auth | JWT lưu **localStorage**, gửi qua `Authorization: Bearer` header |
| Frontend | Vue 3 (Composition API) + Vite + TypeScript |
| State | Pinia |
| Routing | Vue Router 4 (history mode) |
| CSS | TailwindCSS v3 |
| Icons | Lucide Vue Next |
| HTTP | Axios (qua service layer, không gọi trực tiếp trong component) |

## Standards (Bắt buộc áp dụng xuyên suốt)

### HTTP Status Codes (Bắt buộc)
| Code | Nghĩa |
|---|---|
| 200 | Success |
| 201 | Created |
| 400 | Validation error |
| 401 | Unauthorized |
| 404 | Not found |
| 500 | Server error |

> FE chỉ cần check HTTP status — không cần check cả `success` lẫn status code

### API Response Format
```json
{ "success": true, "data": {}, "message": "", "error": null }
```

### Pagination Format
```json
{ "data": [], "meta": { "page": 1, "per_page": 20, "total": 200 } }
```

> Chọn cách A ở giai đoạn này: giữ `data + meta` cho endpoint phân trang để FE xử lý nhanh; không bắt buộc bọc thêm `success/message`.

### API Naming
- Endpoints: `/api/{resource}` (số nhiều, kebab-case)
- Không có `/v1/` — chỉ thêm versioning khi thực sự breaking change

### Frontend Service Layer
- **Không** gọi `axios.get()` trực tiếp trong component
- Mọi API call phải qua `src/services/{module}Service.ts`
- Ví dụ: `productService.getAll()`, `orderService.create(payload)`

### Form UX Standard
- Label luôn hiển thị (static, không floating animation)
- Label đặt trên input, dùng block layout (ví dụ: `mb-1 text-sm font-medium text-gray-700`)

### App-like UX (Bắt buộc)
- Không reload trang giữa các route
- Layout persistent (topbar/bottom nav không re-render)
- Loading bằng skeleton — **KHÔNG** dùng text "Loading..."
- Optimistic UI cho các hành động nhanh (thêm item, đổi trạng thái)

---

## Phase 1 — Skeleton & Infrastructure
> Mục tiêu: Dựng khung, routing split, layout shell Vue

**Backend**
- [x] **1.1** Khởi tạo `api/composer.json`, cài Slim 4 + PHP-DI + firebase/php-jwt + vlucas/phpdotenv + monolog/monolog
- [x] **1.2** Tạo `api/bootstrap.php` — khởi động Slim app, load container, đăng ký routes
- [x] **1.3** Tạo `api/src/Routes/routes.php` — group `/api`, health check endpoint `GET /api/health`
- [x] **1.4** Tạo `api/src/Middleware/AuthMiddleware.php` — verify JWT từ `Authorization` header, trả 401 nếu không hợp lệ
- [x] **1.5** Tạo `api/src/Middleware/CorsMiddleware.php` — giữ cấu hình đơn giản cho same-domain, sẵn sàng mở rộng nếu public API sau này
- [x] **1.6** Tạo `api/src/Middleware/ExceptionHandlerMiddleware.php` — catch mọi exception, trả JSON `{success: false, message}`
- [x] **1.7** Tạo `api/src/Core/Response.php` — static helpers chuẩn hóa format:
	- `success($data = [], $message = '', $status = 200)` → mặc định HTTP 200, create dùng HTTP 201
	- `error($message = '', $code = 400, $errors = null)` → HTTP 4xx/5xx
	- `paginate($data, $meta)` → HTTP 200 kèm meta
- [x] **1.8** Tạo `api/src/Controllers/BaseController.php` — base class với `$this->success()`, `$this->error()`, `$this->paginate()` wrapping `Response`; mọi Controller extend class này
- [x] **1.9** Tạo `api/src/Core/Logger.php` bằng **Monolog** — log error, request fail, payment issue vào `logs/app.log`
- [x] **1.10** Tạo `.env.example` + `.env` (gitignore) — chứa `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`, `JWT_SECRET`, `APP_ENV`; thay thế `config.local.php`
- [x] **1.11** Tạo `api/src/Core/Config.php` — đọc từ `.env` bằng `vlucas/phpdotenv`
- [x] **1.12** Migrate `app/Core/Database.php` sang `api/src/Core/` với namespace `App\Core`
- [x] **1.13** Migrate `app/Core/Money.php` sang `api/src/Core/`
- [x] **1.14** Cập nhật `public/index.php` — phân luồng: URI bắt đầu bằng `/api/` → Slim bootstrap; còn lại → `file_get_contents(__DIR__ . '/admin/index.html')` (Vue SPA)

**Frontend** *(đã scaffold tại `apps/frontend/` bằng Vite vue-ts)*
- [x] **1.15** Cài dependencies: `vue-router`, `pinia`, `axios`, `lucide-vue-next` + cấu hình TailwindCSS
- [x] **1.16** Cấu hình `vite.config.ts` — build output → `public/admin/`, dev proxy `/api` → `http://localhost` (Laragon)
- [x] **1.17** Tạo `src/lib/api.ts` — Axios instance, `baseURL: /api`, interceptor tự attach `Authorization: Bearer <token>`, xử lý 401 → redirect `/login`
- [x] **1.18** Tạo `src/lib/format.ts` — `formatMoney()`, `formatDate()`, `formatRelativeTime()` (port từ PHP)
- [x] **1.19** Tạo `src/stores/auth.ts` — Pinia store: `user`, `token`, `isLoggedIn`, `login()`, `logout()`, `fetchMe()`
- [x] **1.20** Tạo `src/router/index.ts` — history mode, auth guard (redirect `/login` nếu chưa đăng nhập)
- [x] **1.21** Tạo `src/layouts/AppLayout.vue` — topbar + bottom nav (Home/POS/Products/Reports/Menu) + slide-up menu sheet; layout persistent
- [x] **1.22** Test end-to-end: `GET /api/health` → JSON 200; truy cập domain → Vue SPA shell load

---

## Phase 2 — Authentication
> Mục tiêu: Login/logout hoàn chỉnh, auth guard hoạt động

- [x] **2.1** Tạo `api/src/Controllers/AuthController.php` — `login()`, `logout()`, `me()`
- [x] **2.2** Implement JWT: tạo/verify token bằng `firebase/php-jwt`, trả token trong JSON response body
- [x] **2.3** Routes: `POST /api/auth/login`, `POST /api/auth/logout`, `GET /api/auth/me`
- [x] **2.4** Tạo `src/views/auth/LoginView.vue` — form login (static label, border input), gọi `authService.login()`, lưu token vào localStorage → redirect
- [x] **2.5** Auth guard trong router: `beforeEach` check `auth.isLoggedIn`, redirect `/login` nếu chưa đăng nhập
- [x] **2.6** `fetchMe()` gọi `GET /api/auth/me` khi app khởi động để rehydrate user từ token đã lưu; nếu token invalid/hết hạn thì auto logout
- [x] **2.7** Tạo `src/services/authService.ts` — `login(payload)`, `logout()`, `getMe()`
- [x] **2.8** Logout: clear localStorage token, clear Pinia store, redirect `/login`
- [x] **2.9** Test: login → dashboard, route guard redirect, token persist sau reload *(cần test thủ công với tài khoản thật trong DB)*

---

## Phase 3 — Dashboard
> Mục tiêu: Trang dashboard với metrics cơ bản

- [x] **3.1** Tạo `api/src/Controllers/DashboardController.php` — migrate logic từ `DashboardController.php` cũ
- [x] **3.2** Route: `GET /api/dashboard/metrics`
- [x] **3.3** Tạo `src/services/dashboardService.ts`
- [x] **3.4** Tạo `src/stores/dashboard.ts`
- [x] **3.5** Tạo `src/views/DashboardView.vue` — metrics cards dùng skeleton loader khi chờ data

---

## Phase 4 — CRUD Modules đơn giản
> Mục tiêu: Products, Categories, Units, Suppliers, Customers
> Mỗi module: Controller → Request class → Route → Service file (FE) → Store → Views

### 4A — Products
- [x] **4A.1** `api/src/Controllers/ProductController.php` — index (paginated), show, store, update, delete
- [x] **4A.2** `api/src/Requests/CreateProductRequest.php`, `UpdateProductRequest.php` — validate input
- [x] **4A.3** Routes: `GET|POST /api/products`, `GET|PUT|DELETE /api/products/{id}`
- [x] **4A.4** `src/services/productService.ts`
- [x] **4A.5** `src/stores/products.ts`
- [x] **4A.6** `src/views/products/ProductListView.vue` — skeleton loader, search/filter
- [x] **4A.7** `src/views/products/ProductFormView.vue` — create/edit, static label form style

### 4B — Categories
- [x] **4B.1** Controller + Routes `GET|POST /api/categories`, `PUT|DELETE /api/categories/{id}`
- [x] **4B.2** `src/services/categoryService.ts` + `src/views/master-data/CategoriesView.vue`

### 4C — Units
- [x] **4C.1** Controller + Routes `GET|POST /api/units`, `PUT|DELETE /api/units/{id}`
- [x] **4C.2** `src/services/unitService.ts` + `src/views/master-data/UnitsView.vue`

### 4D — Suppliers
- [x] **4D.1** `api/src/Controllers/SupplierController.php` + Request classes
- [x] **4D.2** Routes `GET|POST /api/suppliers`, `GET|PUT|DELETE /api/suppliers/{id}`
- [x] **4D.3** `src/services/supplierService.ts`
- [x] **4D.4** `src/views/suppliers/` — SupplierListView, SupplierDetailView, SupplierFormView

### 4E — Customers
- [x] **4E.1** `api/src/Controllers/CustomerController.php` + Request classes
- [x] **4E.2** Routes `GET|POST /api/customers`, `GET|PUT|DELETE /api/customers/{id}`, `POST /api/customers/{id}/payment`
- [x] **4E.3** `src/services/customerService.ts`
- [x] **4E.4** `src/views/customers/` — CustomerListView, CustomerDetailView, CustomerFormView (tích hợp form thanh toán công nợ)

---

## Phase 5 — Orders & POS
> Mục tiêu: Module phức tạp nhất — giữ toàn bộ Service layer logic, store quản lý cart độc lập với API

- [x] **5.1** Migrate `OrderService.php`, `PaymentService.php`, `ValidationService.php` sang namespace `App\Services`
- [x] **5.2** Migrate `OrderRepository.php` sang namespace `App\Repositories`
- [x] **5.3** `api/src/Controllers/OrderController.php` — index (paginated), show, store, update status, delete, restore
- [x] **5.4** `api/src/Controllers/OrderPaymentController.php` — paymentStore, returnStore
- [x] **5.5** Request classes: `CreateOrderRequest.php`, `UpdateOrderStatusRequest.php`
- [x] **5.6** Routes: `GET|POST /api/orders`, `GET|PUT|DELETE /api/orders/{id}`, `POST /api/orders/{id}/payment`, `POST /api/orders/{id}/return`
- [x] **5.7** `src/services/orderService.ts`
- [x] **5.8** `src/stores/orders.ts`
- [x] **5.9** `src/views/orders/` — OrderListView, OrderDetailView, OrderFormView
- [x] **5.10** `src/stores/pos.ts` — cart state hoàn toàn local, **không** phụ thuộc trực tiếp API response; optimistic add/remove item
- [x] **5.11** `src/views/pos/PosView.vue` — product selector, cart panel, checkout; submit → `orderService.create()`

---

## Phase 6 — Purchases
- [x] **6.1** `api/src/Controllers/PurchaseController.php` + Request classes
- [x] **6.2** Routes `GET|POST /api/purchases`, `GET|PUT /api/purchases/{id}`, `POST /api/purchases/{id}/payment`
- [x] **6.3** `src/services/purchaseService.ts`
- [x] **6.4** `src/views/purchases/` — list + detail + form

---

## Phase 7 — Reports
- [x] **7.1** `api/src/Controllers/ReportController.php` — sales, customerDebt, supplierDebt, missingCost, inventory, inventoryAdjust
- [x] **7.2** Tất cả report endpoints trả theo pagination format chuẩn
- [x] **7.3** `src/services/reportService.ts`
- [x] **7.4** `src/views/reports/` — ReportSalesView, ReportCustomerDebtView, ReportInventoryView, ...

---

## Phase 8 — Misc & Cleanup
- [x] **8.1** `api/src/Controllers/UserController.php` — đổi mật khẩu
- [x] **8.2** `api/src/Controllers/BackupController.php` — backup database
- [x] **8.3** `api/src/Controllers/MigrationController.php` — chạy SQL migration
- [x] **8.4** Xóa toàn bộ `app/Views/` cũ
- [x] **8.5** Xóa các Controller PHP render cũ trong `app/Controllers/`
- [x] **8.6** Dọn `public/index.php` — bỏ autoload PHP MVC cũ, chỉ còn Slim bootstrap

---

## App-like UX Checklist
- [ ] Bottom navigation persistent (Home / POS / Products / Reports / Menu)
- [ ] Slide-up sheet component (thay thế modal dropdown)
- [ ] Skeleton loader component (KHÔNG dùng text "Loading...")
- [ ] Optimistic UI (add/remove không chờ API response)
- [ ] Offline toast khi mất kết nối
- [ ] Page transition (slide left/right theo route depth)
- [ ] Pull-to-refresh (mobile)
- [ ] PWA: service worker + cache strategy (đã có `manifest.webmanifest`)

---

## Notes
- File PHP cũ (`app/Controllers/`, `app/Views/`, `app/Services/`…) **giữ nguyên** đến Phase 8 để tham khảo logic
- Không thay đổi DB schema trong quá trình migration
- Config nhạy cảm (DB credentials, JWT secret) lưu trong `.env` — **không commit**; commit `.env.example` làm template
- `apps/frontend/` đã được scaffold bằng Vite `vue-ts` template — dùng làm thư mục frontend
- Build Vue output vào `public/admin/`; PHP `index.php` serve `file_get_contents(__DIR__ . '/admin/index.html')` cho mọi non-API request
- Mọi API endpoint đều bọc trong `ExceptionHandlerMiddleware` → tự động format lỗi chuẩn
- Mọi Controller extend `BaseController` — không tự wrap JSON thủ công
