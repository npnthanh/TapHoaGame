@extends('layouts.app')
@section('title', 'Đăng nhập tài khoản')
@section('content')

<section class="auth-card panel">
    <div class="section-kicker" style="justify-content: center;">BẢO MẬT & ĐĂNG NHẬP</div>
    <h1>Chào mừng trở lại.</h1>
    <p class="muted" style="text-align: center; margin-bottom: 24px;">
        Đăng nhập để xem kho key game và thanh toán nhanh bằng ví.
    </p>

    <form action="{{ route('login') }}" method="post" class="stack">
        @csrf
        <label>
            Địa chỉ email
            <input type="email" name="email" value="{{ old('email') }}" required autocomplete="email" placeholder="name@example.com">
        </label>
        
        <label>
            Mật khẩu
            <input type="password" name="password" required autocomplete="current-password" placeholder="••••••••••">
        </label>

        <div style="display: flex; justify-content: space-between; align-items: center;">
            <label class="checkbox" style="margin: 0;">
                <input type="checkbox" name="remember" value="1"> Ghi nhớ đăng nhập
            </label>
            <a class="text-link" href="{{ route('password.request') }}" style="font-size: 12px;">Quên mật khẩu?</a>
        </div>

        <button class="button" style="height: 48px; font-size: 14px; margin-top: 8px;">
            Đăng nhập ngay
        </button>
    </form>

    <div style="text-align: center; margin-top: 24px; padding-top: 18px; border-top: 1px solid var(--border);">
        <p class="muted" style="margin: 0; font-size: 13px;">
            Chưa có tài khoản Tạp Hóa Game? <a class="text-link" href="{{ route('register') }}">Đăng ký thành viên mới</a>
        </p>
    </div>
</section>

@endsection
