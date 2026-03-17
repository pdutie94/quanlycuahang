# Quản Lý Cửa Hàng

Ứng dụng quản lý bán hàng PHP nhỏ (no framework) chạy trên MySQL.

## Yêu cầu

- PHP 7.4+ (có PDO MySQL)
- MySQL 5.7 / 8.0
- Web server (Apache / Nginx)
- Optional: APCu/Redis cho cache hiệu năng

## Cài đặt nhanh

1. Clone repo vào `www/`:
   ```bash
   git clone <repo> quanlycuahang
   cd quanlycuahang
   ```

2. Cấu hình database
   - Copy `config/database.php` hoặc tạo `config/database.local.php` với giá trị:
     ```php
     define('DB_HOST', 'localhost');
     define('DB_NAME', 'quanlycuahang');
     define('DB_USER', 'root');
     define('DB_PASSWORD', '');
     define('DB_CHARSET', 'utf8mb4');
     ```

3. Tạo database và chạy migrations
   - Dùng MySQL import file cơ sở:
     ```bash
     mysql -u root -p < quanlycuahang.sql
     ```
   - Hoặc chạy migration bằng UI:
     - Truy cập `http://localhost/quanlycuahang/public/index.php?r=migration`
     - Nhấn `Run pending migrations`.

4. Chạy ứng dụng
   - Apache: đặt `public/` làm document root.
   - Truy cập: `http://localhost/quanlycuahang/public/`

## Tính năng đã triển khai

- Đơn hàng, sản phẩm, khách hàng, nhà cung cấp, nhập hàng, báo cáo.
- Migration theo version `sql/1.0.*.sql`.
- Cache aggregated report (APCu / Redis / fallback file) tại `app/Services/ReportService.php`.
- Health check endpoint: `public/health.php` (DB, migration, thư mục upload, disk, system).
- Metrics logging: `app/Services/MetricsService.php` => `logs/metrics.log`.
- `Product` subquery tối ưu `product_sales_summary` hoặc fallback subquery.

## Tài liệu mở rộng

- `task.md`: báo cáo tiến độ mã hóa.
- `app/Controllers`: controllers rõ ràng, tách `OrderList`, `OrderDetail`, `OrderPayment`.
- `app/Services`: business logic ra ngoài controller.

## Kiểm thử

- Chạy `php -l` các file:
  - `php -l app/Services/ReportService.php`
  - `php -l app/Services/MetricsService.php`
  - `php -l app/Controllers/OrderDetailController.php`
  - `php -l public/health.php`

- Mở `public/health.php` kiểm tra UI và HTTP code.

## Mở rộng

- Bổ sung logging (Monolog)
- Thêm API JSON endpoint
- Giao diện SPA
