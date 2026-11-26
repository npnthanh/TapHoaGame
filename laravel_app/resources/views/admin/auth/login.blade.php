@extends('layouts.admin')
@section('title', 'Đăng nhập quản trị')
@section('content')
<section class="auth-card panel admin-login">
    <div class="section-kicker">TAPHOAGAME / ADMIN CONSOLE</div>
    <span class="admin-login-icon" aria-hidden="true">⌘</span>
    <h1>Đăng nhập quản trị</h1>
    <p class="muted">Dành cho quản trị viên và nhân viên cửa hàng.</p>
    <form method="post" action="{{ route('admin.login') }}" class="stack">
        @csrf
        <label>Email quản trị<input type="email" name="email" value="{{ old('email') }}" autocomplete="username" required autofocus placeholder="admin@example.com"></label>
        <label>Mật khẩu<input type="password" name="password" autocomplete="current-password" required></label>
        <label class="checkbox"><input type="checkbox" name="remember" value="1"> Ghi nhớ đăng nhập quản trị</label>
        <button class="button">Vào trang quản trị</button>
    </form>
</section>
@endsection
