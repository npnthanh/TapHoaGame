@props(['value'])
<span class="status status-{{ $value }}">{{ ['completed' => 'Hoàn thành', 'paid' => 'Đã thanh toán', 'pending' => 'Đang chờ', 'failed' => 'Thất bại', 'refunded' => 'Đã hoàn tiền', 'available' => 'Chưa bán', 'sold' => 'Đã giao', 'invalid' => 'Lỗi', 'open' => 'Đang mở', 'closed' => 'Đã đóng', 'expired' => 'Hết hạn'][$value] ?? $value }}</span>
