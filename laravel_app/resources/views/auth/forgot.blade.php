@extends('layouts.app')
@section('title', 'Quên mật khẩu')
@section('content')<section class="auth-card panel"><h1>Quên mật khẩu?</h1><p class="muted">Nhập email của bạn để nhận liên kết đặt lại mật khẩu.</p><form method="post" action="{{ route('password.email') }}" class="stack">@csrf<label>Email<input name="email" type="email" required autocomplete="email" value="{{ old('email') }}"></label><button class="button">Gửi hướng dẫn</button></form></section>@endsection
