@extends('layouts.app')
@section('title', 'Tạo tài khoản thành viên')
@section('content')

<section class="auth-card panel">
    <div class="section-kicker" style="justify-content: center;">THÀNH VIÊN MỚI</div>
    <h1>Tạo tài khoản</h1>
    <p class="muted" style="text-align: center; margin-bottom: 24px;">
        Gia nhập cộng đồng Tạp Hóa Game để nhận ưu đãi và quản lý kho key game.
    </p>

    <form method="post" action="{{ route('register') }}" class="stack">
        @csrf
        <label>
            Tên hiển thị
            <input name="name" value="{{ old('name') }}" maxlength="100" required autocomplete="name" placeholder="Nguyễn Văn A">
        </label>
        
        <label>
            Địa chỉ email
            <input type="email" name="email" value="{{ old('email') }}" required autocomplete="email" placeholder="name@example.com">
        </label>
        
        <label>
            Mật khẩu
            <input type="password" name="password" minlength="10" required autocomplete="new-password" placeholder="Tối thiểu 10 ký tự">
            <small class="muted">Bao gồm tối thiểu 10 ký tự kết hợp chữ và số để bảo vệ kho key.</small>
        </label>
        
        <label>
            Xác nhận mật khẩu
            <input type="password" name="password_confirmation" minlength="10" required autocomplete="new-password" placeholder="Nhập lại mật khẩu">
        </label>

        <button class="button" style="height: 48px; font-size: 14px; margin-top: 8px;">
            Hoàn tất đăng ký
        </button>
    </form>

    <div style="text-align: center; margin-top: 24px; padding-top: 18px; border-top: 1px solid var(--border);">
        <p class="muted" style="margin: 0; font-size: 13px;">
            Đã có tài khoản? <a class="text-link" href="{{ route('login') }}">Đăng nhập ngay</a>
        </p>
    </div>
</section>

@endsection
