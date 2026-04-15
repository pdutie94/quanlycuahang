# Project Optimization Plan Tasks

## P1 - Quick Wins
- [done] Chuẩn hóa API `useUrlFilters` và thống nhất call-site `applyFilters` tại các list page.
- [done] Dọn code thừa đã xác nhận không dùng:
  - `frontend/src/shared/composables/usePagination.ts`
  - `frontend/src/shared/composables/useForm.ts`
  - `OrderRepository::findDeletedById()`
  - `OrderRepository::findForPayment()`
  - `BaseRepository::formatPaginated()`
- [done] Giảm render recompute:
  - Precompute `displayItems` ở `ProductListPage.vue`
  - Precompute `productLogsWithTone` ở `ProductFormPage.vue`
  - Tái sử dụng formatter từ `useFormat` trong `OrderItemCard.vue`

## P2 - Backend Performance
- [done] Xử lý N+1 ở purchase detail bằng cách join `allow_fraction`, `min_step` ngay trong `PurchaseRepository::findItemsByPurchaseId`.
- [done] Xử lý N+1 công nợ supplier bằng query tổng hợp 1 lần (`SupplierRepository::getPurchaseTotalsBySupplierIds`) rồi map vào danh sách.

## P3 - Shared Abstractions
- [done] Tạo shared backend utility `ServiceHelper` để dùng chung:
  - `resolvePagination()`
  - `normalizePage()`
  - `sanitizeContactFields()`
- [done] Áp dụng utility dùng chung tại `CustomerService`, `SupplierService`, `OrderService`, `ProductService`, `PurchaseService`.
- [done] Tạo shared UI `EntityListState.vue` và áp dụng cho `OrderListPage.vue`, `PurchaseListPage.vue`, `ProductListPage.vue`.

## P4 - Order Query Unification
- [done] Hợp nhất logic query list/deleted list trong `OrderRepository`:
  - `buildListSelectSql()`
  - `paginateByFilters()`
  - Giảm lặp SQL giữa `paginateFiltered()` và `paginateDeletedFiltered()`.

## P5 - Hardening & Verification
- [done] Chuẩn hóa contract create purchase: trả `purchaseId` trực tiếp từ service, bỏ regex parse id trong `PurchaseApiController`.
- [done] Verify frontend:
  - `npm run type-check` pass
  - `npm run build` pass
- [done] Verify backend:
  - `php -l` pass cho toàn bộ file PHP đã thay đổi
- [todo] Smoke test thủ công trên UI:
  - Lọc danh sách đơn hàng / phiếu nhập / sản phẩm
  - Xem chi tiết phiếu nhập có dữ liệu `allow_fraction`, `min_step`
  - Tạo phiếu nhập qua API và kiểm tra `purchaseId` trả về đúng
