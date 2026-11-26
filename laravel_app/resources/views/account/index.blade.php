@extends('layouts.app')
@section('title', 'Tài khoản của tôi')
@section('content')

@include('account.nav')

<div class="page-heading">
    <div class="section-kicker">KHÔNG GIAN CÁ NHÂN</div>
    <h1>Xin chào, {{ $user->name }}.</h1>
    <p>Quản lý tài khoản, mật khẩu bảo mật và thông tin ví mua hàng của bạn.</p>
</div>

<div class="stats-grid">
    <div class="stat wallet-stat" style="border-radius: var(--radius-md);">
        <small style="color: var(--cyan); letter-spacing: 1px; font-weight: 700;">SỐ DƯ VÍ KHẢ DỤNG</small>
        <strong>{{ number_format($user->wallet?->balance ?? 0, 0, ',', '.') }}₫</strong>
        <a href="{{ route('wallet') }}" class="text-link">+ Nạp thêm tiền vào ví</a>
    </div>

    <div class="stat" style="border-radius: var(--radius-md);">
        <small style="letter-spacing: 1px; font-weight: 700;">TỔNG ĐƠN HÀNG ĐÃ MUA</small>
        <strong>{{ $orderCount }}</strong>
        <a href="{{ route('orders') }}" class="text-link">Xem kho key đã sở hữu</a>
    </div>
</div>

<div class="two-columns section">
    <form method="post" action="{{ route('profile.update') }}" class="panel stack">
        @csrf 
        @method('PATCH')
        <h2 style="font-size: 18px; border-bottom: 1px solid var(--border); padding-bottom: 12px; margin-bottom: 6px;">
            Thông tin cá nhân
        </h2>
        <label>
            Tên hiển thị
            <input name="name" value="{{ old('name', $user->name) }}" required>
        </label>
        <label>
            Địa chỉ email
            <input type="email" value="{{ $user->email }}" disabled style="opacity: 0.6; cursor: not-allowed;">
        </label>
        <label>
            Số điện thoại
            <input name="phone" value="{{ old('phone', $user->phone) }}" maxlength="30" placeholder="Chưa cập nhật">
        </label>
        <button class="button">Lưu thay đổi</button>
    </form>

    <form method="post" action="{{ route('profile.password') }}" class="panel stack">
        @csrf 
        @method('PUT')
        <h2 style="font-size: 18px; border-bottom: 1px solid var(--border); padding-bottom: 12px; margin-bottom: 6px;">
            Đổi mật khẩu bảo vệ
        </h2>
        <label>
            Mật khẩu hiện tại
            <input type="password" name="current_password" autocomplete="current-password" required>
        </label>
        <label>
            Mật khẩu mới (tối thiểu 10 ký tự)
            <input type="password" name="password" minlength="10" autocomplete="new-password" required>
        </label>
        <label>
            Nhập lại mật khẩu mới
            <input type="password" name="password_confirmation" autocomplete="new-password" required>
        </label>
        <button class="button secondary">Cập nhật mật khẩu</button>
    </form>
</div>

@endsection
