# Refactor & Tối ưu Vue Project

## 1. Chuẩn hóa format tiền/ngày
- [x] Tạo composable `useFormat.js` cho các hàm:
  - [x] `formatMoney`
  - [x] `formatDateTime`
  - [x] `parseAmount`
- [ ] Refactor các file sử dụng hàm này để dùng composable chung cho từng module:
  - [x] auth
  - [x] category
  - [x] customer
  - [x] dashboard
  - [x] order
  - [ ] pos
  - [ ] product
  - [ ] purchase
  - [ ] report
  - [ ] supplier
  - [ ] system
  - [ ] unit

## 2. Chuẩn hóa logic phân bổ công nợ
- [ ] Tạo composable `useDebtAllocation.js` cho logic preview phân bổ công nợ (customer/supplier)
- [ ] Refactor CustomerDebtPaymentPage.vue và SupplierDebtPaymentPage.vue dùng composable này

## 3. Chuẩn hóa UI preview phân bổ
- [ ] Tách component UI preview phân bổ công nợ dùng chung (nếu hợp lý)
- [ ] Refactor 2 trang công nợ sử dụng component này

## 4. Dọn dẹp code
- [ ] Xử lý các TODO còn lại (ví dụ: gọi API thực tế)
- [ ] Loại bỏ code không dùng (dead code)
- [ ] Kiểm tra lại các composable, component cũ không còn sử dụng

## 5. Kiểm thử & review
- [ ] Đảm bảo không thay đổi logic nghiệp vụ
- [ ] Test lại các edge case phân bổ công nợ
- [ ] Review UI/UX sau refactor
