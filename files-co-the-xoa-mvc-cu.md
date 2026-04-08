# Danh sách file PHP MVC cũ có thể xóa (không còn dùng ở bản Slim 4 API + Vue)

## 1. Thư mục View (render view PHP)
- app/Views/ (toàn bộ thư mục)

## 2. Controller cũ (render view)
- app/Controllers/ (toàn bộ thư mục)

## 3. Core hỗ trợ render view
- app/Core/Controller.php (chứa hàm render, renderPartial)

## 4. Các file layout, partial, helper view
- app/Views/layout/
- app/Views/partials/

## 5. Các file route, entrypoint cũ nếu không còn dùng
- public/index.php (nếu đã chuyển sang public/index.html cho SPA)
- scripts/phase32_parity_check.php (nếu chỉ dùng cho parity check, không còn chạy thực tế)

## 6. Ghi chú
- Đã xác nhận: Không còn route, controller, hay entrypoint nào sử dụng PHP render view ở bản mới. Tất cả giao diện đều qua Vue, backend chỉ trả JSON qua Slim 4 API.
- Nếu cần giữ lại một số file để đối chiếu logic hoặc backup, nên di chuyển sang thư mục lưu trữ riêng.
