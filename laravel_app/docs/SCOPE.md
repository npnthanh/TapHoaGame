# Phạm vi bản demo ngày 22/10/2026

## Đang hoạt động

| Nhóm | Phạm vi đã làm |
| --- | --- |
| Trang chủ | Hero, danh mục, nổi bật, sản phẩm mới, giá giảm, tìm kiếm và thông tin liên hệ |
| Sản phẩm | Danh sách, chi tiết, lọc danh mục/giá/tồn kho, sắp xếp mới/giá/bán chạy, đếm key thật từ kho |
| Tài khoản | Đăng ký, đăng nhập, quên/reset mật khẩu qua mail Laravel, đổi mật khẩu, tên/điện thoại, lịch sử |
| Mua hàng | Giỏ session, chọn số lượng, mã giảm giá áp dụng lúc xác nhận, mua bằng ví, giao key nguyên tử |
| Ví | Số dư, sổ cái, yêu cầu nạp khi adapter được bật, lịch sử, điều chỉnh admin có lý do và người thực hiện |
| Hỗ trợ | Hướng dẫn, FAQ, trang chính sách nháp, ticket của khách và phản hồi admin |
| Dashboard | Doanh thu tổng/ngày/tuần/tháng của đơn hoàn thành, trạng thái đơn, khách, sản phẩm, kho, số lượt nạp đã trả và biểu đồ 7 ngày |
| Quản trị | Tạo/sửa/ngừng bán sản phẩm, thêm danh mục, nhập key thủ công/TXT/CSV, tra cứu kho, tìm đơn theo mã/email, xem key ID đã giao |
| Tài chính | Hoàn toàn bộ đơn về ví một lần, cộng/trừ ví, khóa/mở khách hàng, audit log |
| Coupon | Tạo/sửa/tắt mã, cố định/phần trăm, đơn tối thiểu, hạn, tổng lượt, giới hạn từng khách, sản phẩm/danh mục, giảm tối đa và lịch sử sử dụng |
| CMS | Banner liên kết sản phẩm, settings thương hiệu/liên hệ/SEO/logo/favicon, thứ tự danh mục, trang thông tin, FAQ/chính sách |
| Đánh giá/yêu thích | Yêu thích lưu theo khách, chỉ khách đã mua gửi đánh giá, admin duyệt trước hiển thị |
| Báo cáo | Doanh thu theo thời gian, sản phẩm bán chạy, CSV đơn/tồn kho không chứa secret key, sitemap động |
| Kho/khách | Đánh dấu key chưa bán bị lỗi, ghi chú nội bộ, lịch sử mua/nạp từng khách và gán quyền |
| Phân quyền | customer, staff, admin; staff quản lý cửa hàng, chỉ admin thao tác tiền và khách hàng; admin đăng nhập riêng tại `/admin/login` bằng guard `admin`, khách tại `/dang-nhap` bằng guard `web`; logout độc lập |

## Chưa triển khai

- Cổng thanh toán thực tế (SePay/PayOS/VNPay hoặc cổng khác), QR, thanh toán trực tiếp cho đơn, đối soát và quy trình xử lý tiền đến sau khi yêu cầu hết hạn. Adapter HMAC hiện tại là hợp đồng nội bộ, không mặc nhiên tương thích bất kỳ nhà cung cấp nào.
- Xác thực email tùy chọn; SMTP thực cần cấu hình. Local hiện ghi email vào log.
- Đơn chờ giữ kho, hủy đơn theo quy tắc, hoàn tiền một phần hoặc về ngân hàng. Mua bằng ví hiện là transaction thành công toàn bộ hoặc rollback; lần mua thất bại không tạo đơn thất bại tồn tại riêng.
- Báo cáo nâng cao, nhập/xuất Excel, xem trước coupon trước bước xác nhận, phân quyền theo từng chức năng, 2FA.
- Lịch backup tự động và kiểm tra phục hồi: cần cấu hình theo hosting. Xem DEPLOYMENT.md.
- Tranh chấp concurrent nhiều worker và load test thực tế cần làm trước mở bán ở quy mô lớn.

## Quy tắc dữ liệu

- Dữ liệu lưu trong SQL có thể vẫn là dữ liệu demo; không coi key/số dư/doanh thu mẫu là hàng hoặc tiền thật. Bản Hostinger có SHOP_DEMO_MODE=true, noindex và tắt nhận tiền. Xem DATA-AUDIT.md và HOSTINGER.md.

- Tiền VND là số nguyên BIGINT, không dùng float cho phép tính tiền.
- Tồn kho được tính từ key `available`, không duy trì bộ đếm có thể lệch.
- Checkout khóa user → products theo ID tăng dần → key → coupon → wallet, ghi đơn + sổ cái + giao key trong một transaction InnoDB. Deadlock được retry tối đa 3 lần.
- `checkout_token` unique giúp retry trả về cùng đơn đã mua, không trừ ví lần hai. Token thuộc khách nào chỉ khách đó được dùng lại.
- Key dùng encrypted cast; fingerprint SHA-256 từ key chuẩn hóa để ngăn nhập trùng toàn hệ thống. Secret/fingerprint bị ẩn khi serialize. Không flash nội dung key vào session khi validate lỗi.
- Refund không đưa key đã giao quay về kho. Customer chỉ xem secret của đơn hoàn thành do chính mình sở hữu; admin UI chỉ hiện ID key.
- Không xóa cứng sản phẩm hoặc dữ liệu tài chính trong UI; sản phẩm được ngừng bán để giữ lịch sử.
- Khi chuyển từ phiên đăng nhập chung cũ, middleware tự loại bỏ danh tính admin khỏi guard khách và giữ phiên admin riêng. Đích quay lại sau khi khách đăng nhập chỉ chấp nhận các trang khách, không dùng đường dẫn admin còn lưu từ phiên cũ.
- `APP_KEY` là thành phần bắt buộc để giải mã kho key. Phải lưu an toàn cùng backup; không chạy lại key:generate trên dữ liệu đang dùng.

## Tham khảo

Tham khảo chính thức: https://laravel.com/docs/12.x/releases và https://laravel.com/docs/12.x/deployment. Trang keyvn.site không truy cập được trong lần khảo sát; giao diện hiện tại là thiết kế khởi tạo riêng dựa trên scope, chưa phải bản sao giao diện của trang đó.
