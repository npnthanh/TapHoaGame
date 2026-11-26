# Triển khai trên hosting PHP / MySQL

## Bố trí

```text
/home/account/
  laravel_app/
  public_html/        # document root của domain
```

Upload đúng hai thư mục ở vị trí ngang hàng. Không trỏ domain vào thư mục cha hoặc `laravel_app`. Apache cần `mod_rewrite` và `AllowOverride All`; Nginx phải tự cấu hình `try_files $uri $uri/ /index.php?$query_string`, chặn dotfile và chỉ cho chạy `index.php`. `public_html/.htaccess` dành cho Apache.

## Cấu hình ứng dụng

1. Cài `composer install --no-dev --optimize-autoloader` trong laravel_app, hoặc upload vendor đã build với cùng PHP.
2. Tạo `.env` riêng: `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://domain`, thông số MySQL và SMTP thật, `SESSION_SECURE_COOKIE=true`. Không upload `.env` ra public_html.
3. Với cài mới: `php artisan key:generate`. Với triển khai lại dữ liệu hiện có: giữ nguyên APP_KEY; nếu đổi key sẽ không đọc được kho key đã mã hóa.
4. Chạy `php artisan migrate --force`, `php artisan db:seed --force`, rồi `php artisan shop:admin`. Seeder production chỉ tạo danh mục/trang hướng dẫn, không thêm sản phẩm mẫu.
5. Cấp quyền ghi phù hợp cho `storage` và `bootstrap/cache` (owner của PHP, thường 775; không 777).
6. Chạy `php artisan optimize`. Khi đổi cấu hình, chạy lại `config:cache`. Assets hiện tại là file tĩnh, không yêu cầu npm build. Vite tùy chọn đã trỏ build vào `public_html/build`.
7. Kiểm tra `/up`, đăng nhập, quy trình nhập key, mua hàng và hoàn tiền trên môi trường staging trước.

Storage key chưa bán và file import không cần public symlink. Chỉ chạy `php artisan storage:link` nếu sau này có ảnh sản phẩm lưu qua public disk; tuyệt đối không lưu file chứa key ở public disk.

## Mail, thanh toán, backup

- Mail local dùng log trong `storage/logs`; production cần SMTP trước khi mở quên mật khẩu cho khách. Log có liên kết reset nhạy cảm, không cung cấp quyền đọc công khai.
- Tích hợp cổng thanh toán theo PAYMENTS.md, không chỉ bật PAYMENT_ENABLED để nhận tiền thật.
- Cấu hình backup database InnoDB theo lịch tại hosting (ví dụ mỗi ngày, giữ nhiều phiên bản) bằng cơ chế backup của nhà cung cấp hoặc mysqldump dùng file cấu hình quyền hạn chế. Backup thêm private storage và APP_KEY, mã hóa bản sao, lưu ngoài máy chủ và kiểm tra phục hồi định kỳ.
- Chưa có cron backup được cài vào hệ điều hành trong bản khởi tạo. Backup không thể khẳng định hoàn thành chỉ bằng việc có lệnh dump; phải xác minh file và phục hồi thử.
- Khi dùng nhiều web worker/server, tất cả phải chung database và APP_KEY, session/cache store chung. Bộ test hiện chưa kiểm thử tải đồng thời nhiều process.

Tài liệu Laravel: https://laravel.com/docs/12.x/deployment
