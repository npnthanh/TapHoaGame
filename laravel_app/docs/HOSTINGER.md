# Bản demo Tạp Hóa Game trên taphoagame.kivon.tech

Bản này dùng Laravel 12 và MySQL/MariaDB, PHP 8.2+. Trên hPanel chọn PHP 8.3 nếu có; các extension `pdo_mysql`, mbstring, openssl, fileinfo, DOM/XML, curl, zip cần được bật. Các nội dung, banner, giá, tồn kho, đơn hàng, ví, yêu thích và đánh giá đọc từ SQL. `SHOP_DEMO_MODE=true` hiển thị thông báo demo và vô hiệu nhận thanh toán thật.

## Gói deploy

Gói ZIP có `laravel_app/`, `public_html/`, hai file SQL, cấu hình `.env.hostinger`, manifest và `ACCESS.txt`. Mật khẩu trong bản SQL đã đổi ngẫu nhiên riêng; `123456` chỉ còn ở local. Kho key đã mã hóa lại bằng APP_KEY mới của `.env.hostinger`; không dùng APP_KEY local hoặc chạy `key:generate` sau khi import bản SQL demo.

1. Thêm website/subdomain `taphoagame.kivon.tech` trong hPanel, trỏ DNS theo thông số tài khoản của bạn và bật SSL.
2. Xác định đúng document root của subdomain. Đặt nội dung `public_html/` vào document root đó và đặt `laravel_app/` ở thư mục **ngang hàng**. Không đưa Laravel hoặc SQL vào thư mục public. Nếu hPanel tạo document root tùy chỉnh, bố trí hai thư mục ngang hàng để `public_html/index.php` tìm thấy `../laravel_app`.
3. Tạo database và user MySQL riêng trong hPanel. Import **taphoagame-demo.sql** qua phpMyAdmin vào database **mới, trống**. SQL có lệnh tạo lại bảng; không import vào database đang vận hành. Để bắt đầu sạch, dùng `taphoagame-schema.sql`, không dùng cả hai file liên tiếp.
4. Copy `.env.hostinger` thành `laravel_app/.env`. Chỉ thay `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`, xác nhận `DB_HOST` do hPanel cung cấp. Giữ APP_KEY đi kèm. `APP_URL=https://taphoagame.kivon.tech`, `APP_ENV=production`, `APP_DEBUG=false`, cookie secure đã có sẵn.
5. Gói có vendor production đã cài. Nếu cần cài lại qua SSH: vào `laravel_app` và chạy `composer install --no-dev --optimize-autoloader`. CLI PHP phải tương thích với PHP website.
6. Cấp quyền ghi cho `storage` và `bootstrap/cache` theo owner hosting (thường 775, không 777). Cấp quyền ghi `public_html/uploads/products` nếu dùng tải ảnh sản phẩm.
7. Chạy qua SSH:

```sh
cd /DUONG_DAN_THUC_TE/laravel_app
php artisan migrate --force
php artisan optimize
php artisan shop:check --demo
```

8. Đăng nhập `https://taphoagame.kivon.tech/admin/login` bằng tài khoản trong **ACCESS.txt**. Khách dùng `/dang-nhap`. Không upload `ACCESS.txt`, SQL hoặc ZIP vào public_html; xóa bản archive khỏi vùng public nếu đã lỡ upload để giải nén.

Nếu chọn SQL schema trống, cần chạy `php artisan db:seed --force` và `php artisan shop:admin` để tạo nội dung mặc định và quản trị viên. File ACCESS.txt chỉ tương ứng với SQL demo có dữ liệu.

Nếu không có SSH, import SQL đã đủ schema; có thể chạy giao diện không cache. Gói đã có vendor. Xem kết quả `/up`, sau đó kiểm thử đăng nhập, banner, sửa sản phẩm và mua bằng ví demo. Việc thực thi CLI/cron phụ thuộc gói Hostinger, không có endpoint web công khai để chạy migrate/artisan.

## Vận hành demo

- `/admin/content`: tên shop tạm, logo/favicon, liên hệ, SEO, banner, trang thông tin, duyệt đánh giá và danh mục.
- `/admin/products`: tên, SKU, nền tảng, khu vực, giá, nội dung, hình ảnh và trạng thái bán. Sản phẩm cũ có giá/nguồn key minh họa cần được chủ shop kiểm duyệt trước bán thật.
- `/admin/keys`: nhập TXT/CSV một cột, đánh dấu lỗi key chưa giao. Không xuất secret key trong báo cáo CSV.
- `/admin/users`: quản lý quyền, ghi chú, lịch sử và điều chỉnh ví demo có sổ cái.
- `/admin/reports`: doanh thu, sản phẩm bán chạy và xuất CSV đơn/tồn kho. Dữ liệu là giao dịch demo trong MySQL, không phải doanh thu thật.
- `/admin/coupons`: tạo, sửa/tắt, điều kiện áp dụng và lịch sử sử dụng mã.
- Khách: giỏ hàng, mua bằng ví, xem key, yêu thích, gửi đánh giá sau khi mua, ticket hỗ trợ.
- Mail đang dùng `log`, vì chưa có SMTP. Quên mật khẩu demo tạo liên kết trong log private; có thể đổi qua SSH bằng `php artisan shop:password email`. Không mở log ra public.
- Bản demo gửi `robots.txt` chặn index và meta `noindex`. Không nhận tiền thật, chưa tích hợp SePay/PayOS, chưa có QR ngân hàng thật.

## Trước khi chuyển sang bán thật

Không chỉ đổi SHOP_DEMO_MODE. Cần database sạch hoặc kế hoạch loại bỏ có kiểm soát các tài khoản/số dư/key/đơn mẫu; nhập hàng hợp lệ, xác nhận giá và khu vực; cấu hình SMTP; công bố liên hệ và chính sách; tích hợp một cổng thanh toán đã chọn và thử webhook/đối soát. Sau đó đặt `SHOP_DEMO_MODE=false`, kiểm tra `shop:check`, thực hiện backup và phục hồi thử. Gói demo chưa có kiểm thử tải nhiều worker, nhập Excel và backup tự động của hệ điều hành. Backup theo lịch có thể cấu hình bằng hệ thống backup hPanel.

Tham khảo chính thức: [PHP trên Hostinger](https://www.hostinger.com/support/1575755-how-to-change-the-php-version-of-your-hostinger-hosting-plan/), [Composer trên Hostinger](https://www.hostinger.com/support/8727597-how-to-install-composer-locally-at-hostinger/), [hướng dẫn Laravel](https://support.hostinger.com/en/articles/6152127-how-to-deploy-laravel-8-at-hostinger). Hướng dẫn Laravel của Hostinger là cho phiên bản cũ; dự án này dùng bố cục private Laravel và public_html riêng như trên.
