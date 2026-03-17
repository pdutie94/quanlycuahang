-- 1.0.19: Thêm bảng tổng kết doanh số sản phẩm để tránh subquery nặng trong Product model

CREATE TABLE IF NOT EXISTS product_sales_summary (
    product_id INT NOT NULL PRIMARY KEY,
    sold_qty DECIMAL(16,4) NOT NULL DEFAULT 0,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_product_sales_summary_sold_qty (sold_qty)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Khởi tạo dữ liệu lần đầu
REPLACE INTO product_sales_summary (product_id, sold_qty)
SELECT oi.product_id, IFNULL(SUM(oi.qty_base),0)
FROM order_items oi
JOIN orders o ON oi.order_id = o.id AND o.deleted_at IS NULL
WHERE o.order_status IS NULL OR o.order_status <> 'cancelled'
GROUP BY oi.product_id;

-- Trigger cập nhật khi có thay đổi order_items
-- Lưu ý: migration UI/app dùng PDO exec, không hỗ trợ các lệnh DELIMITER của client.
-- Nếu cần, thêm trigger trực tiếp vào DB bằng admin tool hoặc cập nhật qua mã ứng dụng.
-- Không tạo trigger trong migration để tránh lỗi syntax tại đây.

-- (Giữ chung điều kiện cập nhật: nếu đơn hàng bị xóa/hủy, sản phẩm có thể không được đếm)
-- Tạm thời, lúc này product_sales_summary được cập nhật bằng manual/periodic job hoặc các thao tác cập nhật sau này.

