# Hướng dẫn Deploy & Cập nhật Dự án Quản Lý Cửa Hàng (Hosting không composer/nodejs)

## 1. Yêu cầu hệ thống
- PHP >= 7.4 (hoặc 8.x)
- MySQL/MariaDB
- Web server: Nginx hoặc Apache

## 2. Cấu trúc dự án
- Backend: PHP (Slim 4) — thư mục gốc, public/index.php
- Frontend: Vue 3 SPA — build ra public/assets/

## 3. Deploy lên hosting (không cần composer/nodejs)
### 3.1. Build code trên máy local
1. Clone source về máy tính cá nhân:
   ```bash
   git clone <repo-url> quanlycuahang
   cd quanlycuahang
   ```
2. Build frontend:
   ```bash
   cd frontend
   npm install
   npm run build
   # Kết quả build nằm ở ../public/assets/
   ```
3. Nếu có thay đổi backend, build lại trên local (nếu cần).

### 3.2. Upload lên hosting
- Upload toàn bộ source code (trừ thư mục frontend/, node_modules/, ...) lên hosting.
- Đảm bảo thư mục public/ là document root.
- Upload cả thư mục public/assets/ đã build.

### 3.3. Cấu hình database
- Sửa file cấu hình DB: `config/database.php` hoặc `config/database.local.php` (giống hệ thống cũ)
  ```php
  define('DB_HOST', 'localhost');
  define('DB_NAME', 'quanlycuahang');
  define('DB_USER', 'user_db');
  define('DB_PASSWORD', 'matkhau');
  define('DB_CHARSET', 'utf8mb4');
  ```

### 3.4. Import database
- Tạo database và import file `db-structure.sql` (hoặc file backup dữ liệu cũ).

### 3.5. Cấu hình web server
- Apache: trỏ DocumentRoot vào `public/`, bật mod_rewrite, giữ nguyên .htaccess
- Nginx: trỏ root vào `public/`, cấu hình rewrite cho SPA và API như hướng dẫn bên dưới

## 4. Cập nhật code mới (update)
1. Build lại frontend trên máy local:
   ```bash
   cd frontend
   npm install
   npm run build
   ```
2. Upload lại các file đã thay đổi và thư mục public/assets/ lên hosting.
3. Nếu có thay đổi backend, upload các file PHP tương ứng.
4. Không cần chạy composer hay nodejs trên hosting.

## 5. Lưu ý
- Không cần file .env, không cần composer/nodejs trên hosting.
- Chỉ cần upload source đã build và cấu hình DB qua file PHP như cũ.
- Luôn build lại frontend trước khi upload.
- Backup database trước khi cập nhật lớn.
- Nếu gặp lỗi quyền, đảm bảo user PHP/nginx có quyền ghi vào `public/assets/`, `storage/`, ...

## 6. Ví dụ cấu hình Nginx
```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /path/to/quanlycuahang/public;

    index index.php index.html;

    location /api/ {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location / {
        try_files $uri $uri/ /index.html;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

---
Mọi thắc mắc vui lòng liên hệ dev team hoặc đọc thêm tài liệu trong repo.
  - `php -l app/Services/ReportService.php`
  - `php -l app/Services/MetricsService.php`
  - `php -l app/Controllers/OrderDetailController.php`
  - `php -l public/health.php`

- Mở `public/health.php` kiểm tra UI và HTTP code.

## Mở rộng

- Bổ sung logging (Monolog)
- Thêm API JSON endpoint
- Giao diện SPA
