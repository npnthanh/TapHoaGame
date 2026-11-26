# Tạp Hóa Game (`TapHoaGame`)

Hệ thống cửa hàng sản phẩm số, key game bản quyền (Steam/PC), phần mềm và thẻ quà tặng xây dựng trên nền tảng **Laravel 12** & **MySQL**.

## Cấu trúc thư mục

- `laravel_app/` — Mã nguồn ứng dụng Laravel 12 (models, controllers, services, migrations, seeders, Blade views, kiểm thử tự động).
- `public_html/` — Thư mục document root công khai (assets CSS/JS/SVG, ảnh bìa mẫu và `index.php` kết nối sang `../laravel_app`).

## Cài đặt nhanh (Local)

```bash
cd laravel_app
composer install
cp .env.example .env
php artisan key:generate
```

Cập nhật thông tin kết nối MySQL (`DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`) trong `laravel_app/.env`, sau đó khởi tạo dữ liệu:

```bash
php artisan migrate --seed
php artisan shop:admin
```

Chạy kiểm thử tự động:

```bash
php artisan test
```

## Tài liệu chi tiết

- [ Triển khai Hostinger ](laravel_app/docs/HOSTINGER.md)
- [ Rà soát dữ liệu & vận hành ](laravel_app/docs/DATA-AUDIT.md)
