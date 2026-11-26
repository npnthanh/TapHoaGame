# Adapter thanh toán nội bộ — mặc định tắt

Chưa tích hợp nhà cung cấp cụ thể. Không bật để nhận tiền thật trước khi adapter thực tế được triển khai và thử nghiệm. Cần xác nhận nhà cung cấp, cấu hình merchant/ngân hàng, cách xác minh chữ ký, trạng thái giao dịch và quy trình đối soát.

## Cấu hình

```dotenv
PAYMENT_ENABLED=false
PAYMENT_PROVIDER=bank-adapter
PAYMENT_WEBHOOK_SECRET=
PAYMENT_BANK_NAME=
PAYMENT_ACCOUNT_NUMBER=
PAYMENT_ACCOUNT_HOLDER=
```

Secret ít nhất 32 ký tự ngẫu nhiên. Khi đã có adapter tin cậy, bật và cấu hình tài khoản. Không dùng secret này ở frontend hoặc gửi cho khách.

## Hợp đồng nội bộ

Endpoint: `POST /payments/webhook`, JSON, HTTPS. Không dùng CSRF; xác thực HMAC độc lập.

```json
{
  "event_id": "gateway-event-unique",
  "transaction_id": "bank-transaction-unique",
  "reference": "NAPxxxxxxxxxxxxxx",
  "amount": 100000,
  "currency": "VND",
  "direction": "in",
  "status": "paid",
  "account": "configured-account-number"
}
```

- `X-Payment-Timestamp`: Unix seconds, chênh không quá 300 giây.
- `X-Payment-Signature`: hex HMAC-SHA256 của `timestamp + '.' + RAW_BODY` bằng webhook secret.
- Chữ ký phải tính từ bytes JSON gửi thực tế. Không đổi cách serialize body sau khi ký.
- amount phải đúng yêu cầu nạp, số nguyên VND; hướng tiền vào, trạng thái paid, số tài khoản nhận đúng cấu hình.
- Yêu cầu nạp có hạn 30 phút. Giao dịch đến muộn trả 409 và cần đối soát thủ công; không cộng tiền tự động.

Các mã phản hồi: 200 đã xử lý hoặc retry hợp lệ; 401 chữ ký/timestamp sai; 404 không tìm thấy yêu cầu; 409 xung đột event/transaction/yêu cầu hết hạn; 422 dữ liệu sai; 503 chưa cấu hình; 429 vượt giới hạn request.

User và wallet được khóa trong transaction, unique `(provider,event_id)`, unique `(provider,provider_transaction_id)` và unique ledger reference bảo vệ trước webhook lặp. Một transaction ngân hàng không được cộng vào hai yêu cầu. Hai event khác nhau cùng transaction cùng yêu cầu không cộng lần hai. Không lưu toàn bộ webhook payload hoặc key game vào audit log.

Không giả lập giao dịch thành công ở frontend. UI chỉ tạo yêu cầu chờ và hiển thị hướng dẫn chuyển khoản khi adapter được bật. Chưa có QR và chưa có cơ chế đẩy trạng thái realtime; khách tải lại trang ví để xem trạng thái đã xác nhận.
