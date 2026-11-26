# Rà soát dữ liệu và bản demo triển khai

Ngày 22/10/2026. Thương hiệu: **Tạp Hóa Game (`TapHoaGame`)**, domain dự kiến: `taphoagame.kivon.tech`, hosting Hostinger. Chưa triển khai lên máy chủ hoặc kết nối tài khoản thanh toán.

## Những phần đã chuyển sang dữ liệu MySQL

| Nội dung | Nguồn và cách quản lý |
| --- | --- |
| Banner và nút chuyển trang | Bảng banners, liên kết product_id; sửa tại /admin/content; giá lấy trực tiếp từ sản phẩm |
| Tên shop, logo/favicon, liên hệ, SEO, lợi ích | Bảng settings; không điền số điện thoại hoặc liên hệ giả |
| Danh mục, thứ tự, trạng thái | Bảng categories; sửa tại /admin/content |
| Sản phẩm, ảnh, giá gốc/giảm, mô tả, hướng dẫn | Bảng products; /admin/products hỗ trợ URL ảnh hoặc tải JPG/PNG/WebP |
| Tồn kho và số lượng đã bán | Bảng product_keys; đếm theo trạng thái, không viết cứng số lượng |
| Đánh giá và điểm trung bình | Chỉ lấy review đã duyệt; người đã mua mới gửi được; bỏ điểm 4.8 giả |
| Yêu thích và số trên menu | Bảng wishlists, theo từng khách; bỏ biểu tượng không hoạt động |
| Trang giới thiệu, FAQ, hướng dẫn, chính sách | Bảng pages; sửa nội dung văn bản tại /admin/content |
| Ví, nạp tiền, đơn, coupon, ticket, báo cáo | Các bảng nghiệp vụ; /admin/reports xuất CSV đơn và tồn kho, không xuất secret key |

Banner giảm giá được tính từ giá hiện tại trong SQL. Đã bỏ cam kết 24/7, bảo hành trọn đời, an toàn 100% và nhãn nền tảng không dựa trên dữ liệu. Sản phẩm không có đánh giá sẽ không hiện sao giả. Sitemap được tạo từ dữ liệu, demo có robots/noindex để hạn chế index.

## Dữ liệu đang có trong bản xuất demo

Bản xuất gồm 27 bảng: 2 tài khoản, 10 sản phẩm, 4 danh mục, 86 key, 3 đơn, 1 lượt nạp minh họa, 1 coupon, 1 ticket, 3 banner, 4 trang nội dung và 10 cấu hình. Không tạo đánh giá giả. Chi tiết số dòng và checksum SQL nằm trong manifest.json đi kèm.

Các bản ghi này đã lưu trong MySQL nhưng vẫn là dữ liệu trải nghiệm: key mẫu không bảo đảm kích hoạt được, tiền ví không có chuyển khoản thực, đơn demo không phải doanh thu thật. Danh mục hiện có hai nhóm sản phẩm Starfield/FC24 với SKU và giá khác nhau do các lần nhập demo; đã giữ nguyên để không mất dữ liệu, chủ shop có thể sửa hoặc ngừng bán từng SKU tại quản trị.

Tài khoản local giữ mật khẩu theo yêu cầu trước đó. Gói Hostinger dùng mật khẩu mạnh ngẫu nhiên trong ACCESS.txt, APP_KEY riêng và mã hóa lại kho key tương ứng. Không xuất session, token đặt lại mật khẩu, cache, hàng đợi hay audit log local vào gói.

## Kiểm tra đã thực hiện

- 46 tests / 271 assertions đạt trên SQLite và MariaDB qua driver MySQL: mua bằng ví, rollback, key, coupon, webhook lặp/sai chữ ký, quyền truy cập, phiên admin/khách riêng, CMS, yêu thích, đánh giá, báo cáo và cấu hình demo.
- Import SQL vào database kiểm tra riêng; migrations đầy đủ, kho key giải mã được bằng APP_KEY của gói; tài khoản xuất đăng nhập được độc lập ở trang admin/khách.
- Kiểm tra HTTP bản production tại local, trang quản trị nội dung/báo cáo/sản phẩm/kho trả 200. Kiểm tra trình duyệt trang chủ và chuyển banner.
- Composer audit không phát hiện advisory; vendor production vượt kiểm tra extension/PHP. Chưa kiểm thử tải hoặc nhiều worker tranh chấp đồng thời.

## Còn cần trước khi bán thật

Chưa có tích hợp SePay/payOS, QR thật, SMTP, backup tự động, nhập XLSX, hoàn tiền ngân hàng/một phần, giữ kho cho đơn chờ hoặc phân quyền chi tiết từng chức năng. Adapter webhook hiện có là hợp đồng nội bộ, không mặc nhiên tương thích với SePay/payOS.

Cần chốt thương hiệu, giá/nguồn hàng hợp lệ, chính sách và liên hệ; thay hoặc loại bỏ có kiểm soát các dữ liệu demo; chọn và thử cổng thanh toán, cấu hình SMTP, backup/phục hồi và kiểm thử vận hành. `SHOP_DEMO_MODE=true` hiện chặn thanh toán thật. Tài liệu triển khai: HOSTINGER.md.
