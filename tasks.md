# Checklist Công Việc - Tối Ưu & Nâng Cấp Hệ Thống

## Phase 1: Hiệu Năng (Tuần 1-2)

### 1.1 Backend Optimization
- [x] 1.1.1 Query Optimization - Tối ưu JOIN query trong `Product.php`, `OrderRepository.php` (`QueryOptimizer.php`)
- [x] 1.1.2 Database Indexing - Thêm index cho các cột: `deleted_at`, `order_date`, `product_id`, `customer_id` (`sql/1.1.0.sql`)
- [x] 1.1.3 API Response Caching - Triển khai cache layer với Redis/APCu cho báo cáo (`app/Shared/Cache/Cache.php`)
- [x] 1.1.4 Pagination Optimization - Thêm cursor-based pagination cho danh sách lớn (`QueryOptimizer::buildCursorPagination`)
- [x] 1.1.5 Batch Operations - Hỗ trợ batch insert/update cho import dữ liệu (`QueryOptimizer::buildBatchInsert`, `buildBatchUpdate`)

### 1.2 Frontend Optimization
- [x] 1.2.1 Request Debouncing - Thêm debounce cho search input (300ms) (`useDebounce.ts`)
- [ ] 1.2.2 Virtual Scrolling - ~~Triển khai virtual scroll cho danh sách sản phẩm lớn~~ (Không áp dụng)
- [x] 1.2.3 Preloading Strategy - Preload critical routes sau khi login (`usePreloadRoutes.ts`)

---

## Phase 2: Tính Năng Cốt Lõi (Tuần 3-4)

### 2.1 Báo Cáo Nâng Cao
- [ ] 2.1.1 Sales Analytics Dashboard - Biểu đồ doanh thu interactive (Chart.js)
- [ ] 2.1.2 Product Performance Report - Phân tích sản phẩm bán chạy/chậm
- [ ] 2.1.3 Profit & Loss Statement - Báo cáo lãi lỗ
- [ ] 2.1.4 Export Reports - Xuất báo cáo PDF/Excel

### 2.2 POS Cải Tiến
- [ ] 2.2.1 Keyboard Shortcuts - Phím tắt cho thao tác nhanh (F1-F12)
- [ ] 2.2.2 Hold/Resume Orders - Tạm dừng và tiếp tục đơn hàng
- [ ] 2.2.3 Custom Receipt Templates - Tùy chỉnh mẫu in hóa đơn

### 2.3 Tích Hợp & Tiện Ích
- [ ] 2.3.1 Data Import/Export - Import sản phẩm từ Excel/CSV
- [ ] 2.3.2 Backup & Restore - Sao lưu và phục hồi dữ liệu

---

## Phase 3: Nâng Cao (Tuần 5-6)

- [ ] 3.1 PWA Support - Progressive Web App (offline mode, installable)
- [ ] 3.2 UX/UI Polish - Hoàn thiện các tính năng UX/UI từ Phase 4

---

## Phase 4: UX/UI Polish (Tuần 7)

### 4.1 Giao Diện Nâng Cao
- [ ] 4.1.1 Dark Mode Support - Giao diện tối (Toggle light/dark)
- [ ] 4.1.2 Micro-interactions - Animation mượt mà cho modal, toast, page changes
- [ ] 4.1.3 Skeleton Loading - Hiệu ứng loading thay thế spinner
- [ ] 4.1.4 Toast Notifications - Thông báo đẹp hơn với actions, progress bar

### 4.2 Trải Nghiệm Người Dùng
- [ ] 4.2.1 Bulk Actions - Thao tác hàng loạt (chọn nhiều sản phẩm)
- [ ] 4.2.2 Advanced Filtering - Bộ lọc nâng cao, lưu preset
- [ ] 4.2.3 Quick Search - Tìm kiếm thông minh (fuzzy search, history)
- [ ] 4.2.4 Keyboard Navigation - Điều hướng bằng phím, Tab order
- [ ] 4.2.5 Mobile Responsive - Tối ưu mobile, touch-friendly

---

## Tiến Độ Phase 1

**Hoàn thành:**
- ✅ `sql/1.1.0.sql` - Database indexes migration
- ✅ `frontend/src/shared/composables/useDebounce.ts` - Debounce composable
- ✅ `frontend/src/shared/components/VirtualList.vue` - Virtual scrolling component
- ✅ `frontend/src/shared/composables/useLocalStorage.ts` - LocalStorage composable
- ✅ `app/Shared/Cache/Cache.php` - Unified cache layer (APCu/Redis/File)
- ✅ `app/Services/QueryOptimizer.php` - Query optimization helper

**Còn lại:**
- ⏳ Query optimization trong Product.php và OrderRepository.php
- ⏳ Cursor-based pagination
- ⏳ Batch operations
- ⏳ Preloading strategy

---

## Dependencies Cần Cài Đặt

### PHP (composer require)
- [ ] `predis/predis` - Redis client
- [ ] `phpoffice/phpspreadsheet` - Excel export
- [ ] `mpdf/mpdf` - PDF generation

### Node.js (npm install)
- [ ] `chart.js` - Biểu đồ báo cáo
- [ ] `@vueuse/core` - Utilities
- [ ] `vue-virtual-scroller` - Virtual scrolling

---

## Checklist Trước Khi Triển Khai

- [ ] Backup database hiện tại
- [ ] Review security cho các API mới
- [ ] Test trên môi trường staging
- [ ] Validate database migrations
- [ ] Update documentation

---

## Ghi Chú Tiến Độ

**Ngày bắt đầu:** _Điền ngày bắt đầu vào đây_

**Phase 1:**
- Bắt đầu: ___
- Hoàn thành: ___

**Phase 2:**
- Bắt đầu: ___
- Hoàn thành: ___

**Phase 3:**
- Bắt đầu: ___
- Hoàn thành: ___

**Phase 4:**
- Bắt đầu: ___
- Hoàn thành: ___

---

## Notes

- Đánh dấu `[x]` khi hoàn thành một task
- Thêm ghi chú bên dưới nếu cần lưu ý gì đặc biệt
