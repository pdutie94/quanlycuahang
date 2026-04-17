Dưới đây là mô tả tổng quan dự án Quản lý Cửa Hàng:

Tổng quan kiến trúc
Monorepo kết hợp backend PHP và frontend Vue3 chạy trên cùng 1 domain (Laragon). Build frontend ra public/ để Slim4 serve tĩnh, API qua prefix /api/.

quanlycuahang/
├── app/               # Backend PHP (Slim4)
├── routes/api.php     # Định nghĩa toàn bộ route API
├── config/            # Cấu hình DB, app
├── bootstrap/api.php  # Khởi tạo Slim app
├── frontend/src/      # Vue3 + TypeScript
├── public/            # Entry point PHP + build output Vue
└── sql/               # Migration files
Backend (Slim4 PHP)
Tổ chức theo kiến trúc layered / modular:

Layer	Vị trí	Vai trò
Controller	app/Modules/*/	Nhận request, trả response
Service	app/Services/	Business logic
Repository	app/Repositories/	Truy vấn DB
Model	app/Models/	Ánh xạ bảng/entity
Shared	app/Shared/	Middleware, Response helper
Các module API:

Auth — đăng nhập, đổi mật khẩu, me/logout
Product — CRUD sản phẩm, form data
Category / Unit — danh mục, đơn vị tính
Customer — CRUD + thanh toán công nợ khách hàng
Supplier — CRUD + form data nhà cung cấp
Order — CRUD đơn hàng, cập nhật trạng thái, hoàn trả, thanh toán, thùng rác
Purchase — CRUD phiếu nhập, thanh toán NCC
POS — bootstrap dữ liệu bán hàng tại quầy
Report — tổng quan, doanh thu, công nợ KH/NCC, tồn kho, giá vốn
System/Migration — quản lý migration DB
Middleware: ApiAuthMiddleware bảo vệ toàn bộ nhóm /api (trừ /api/auth/login, /api/health).

Frontend (Vue3 + TypeScript + Vite + Tailwind)
Tổ chức theo feature modules, mỗi module có cấu trúc:

modules/<feature>/
  pages/        # Vue page components (route targets)
  composables/  # Composition API logic
  services/     # Gọi API (axios/fetch)
  types.ts      # TypeScript types
Các module frontend:

Module	Pages chính
auth	LoginPage
dashboard	DashboardPage
pos	PosPage (bán hàng tại quầy)
product	ProductListPage, ProductFormPage
category	CategoryListPage, CategoryFormPage
unit	UnitListPage
customer	List, Detail, Form, DebtPayment, Payment
supplier	List, Detail, Form, DebtPayment
order	List, Detail, Form, Invoice, Return, Trash
purchase	List, Detail, Form (phiếu nhập)
report	Overview, Sales, Inventory, CustomerDebt, SupplierDebt, MissingCost, CostUpdate
system	ChangePassword, Migration
Shared components dùng chung:

AppModalSheet, ActionConfirmSheet — modal/sheet chuẩn
ProductSelectorModal — chọn sản phẩm
PaymentModal, DiscountModal, SurchargeModal, PriceEditModal, ManualItemModal — các modal nghiệp vụ
OrderItemCard, CustomerItemCard, SupplierItemCard — card hiển thị
useInfiniteList, useFormat, useToast, useUrlFilters — composables tiện ích
Luồng nghiệp vụ chính
Bán hàng (POS): PosPage → bootstrap sản phẩm → chọn sản phẩm → tạo đơn → thanh toán
Quản lý đơn hàng: xem list, chi tiết, cập nhật trạng thái, in hóa đơn, hoàn trả, công nợ
Nhập hàng: tạo phiếu nhập → chọn NCC + sản phẩm → thanh toán NCC
Công nợ: theo dõi công nợ KH và NCC, thanh toán từng phần
Báo cáo: tổng quan doanh thu, tồn kho, giá vốn, missing cost

