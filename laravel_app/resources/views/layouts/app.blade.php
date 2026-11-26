<!doctype html>
<html lang="vi">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title', $shopContent['seo_title']) · {{ $shopContent['brand_name'] }}</title><meta name="description" content="@yield('description', $shopContent['seo_description'])">
@if(config('shop.demo_mode'))<meta name="robots" content="noindex,nofollow">@endif
<link rel="icon" href="{{ $shopContent['favicon_url'] }}"><link rel="stylesheet" href="{{ asset('assets/app.css') }}"><link rel="stylesheet" href="{{ asset('assets/store-data.css') }}?v={{ filemtime(public_path('assets/store-data.css')) }}"><script src="{{ asset('assets/app.js') }}?v={{ filemtime(public_path('assets/app.js')) }}" defer></script>
<link rel="stylesheet" href="{{ asset('assets/custom-select.css') }}?v={{ filemtime(public_path('assets/custom-select.css')) }}">
<script src="{{ asset('assets/custom-select.js') }}?v={{ filemtime(public_path('assets/custom-select.js')) }}" defer></script>
</head>
<body>
@if(config('shop.demo_mode'))<div class="demo-notice" role="note">BẢN DEMO · Sản phẩm, key và số dư dùng để trải nghiệm. Không chuyển tiền thật.</div>@endif
<header class="header"><div class="container header-main">
<a class="brand" href="{{ route('home') }}">@if($shopContent['logo_url'])<img class="shop-logo" src="{{ $shopContent['logo_url'] }}" alt="{{ $shopContent['brand_name'] }}">@else<span class="accent">◇</span><span>{{ $shopContent['brand_name'] }}</span>@endif</a>
<nav class="header-nav"><a class="{{ request()->routeIs('home') ? 'active':'' }}" href="{{ route('home') }}">TRANG CHỦ</a><a href="{{ route('products') }}">SẢN PHẨM</a><a href="{{ route('products',['sort'=>'bestselling']) }}">BÁN CHẠY</a><a href="{{ route('page','huong-dan-mua-hang') }}">HƯỚNG DẪN</a><a href="{{ route('page','faq') }}">HỖ TRỢ</a></nav>
<form class="search-pill" action="{{ route('products') }}"><button aria-label="Tìm kiếm" type="submit"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><circle cx="10.5" cy="10.5" r="6.5"/><path d="m16 16 4.5 4.5"/></svg></button><input aria-label="Tìm kiếm sản phẩm" name="q" placeholder="Tìm tên game, key..." value="{{ request('q') }}"></form>
<div class="header-actions">
    <a class="action-item" href="{{ auth('web')->check() ? route('account') : route('login') }}" aria-label="Profile" title="{{ auth('web')->user()?->name ?: 'Đăng nhập tài khoản' }}">
        <span class="action-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="7" r="3.5"/><path d="M5 21v-3a5 5 0 0 1 5-5h4a5 5 0 0 1 5 5v3"/></svg></span>
        <span>Profile</span>
    </a>
    <a class="action-item" href="{{ route('wishlist') }}" aria-label="Wishlist" title="Danh sách yêu thích">
        <span class="action-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1.1-1.1a5.5 5.5 0 0 0-7.8 7.8L12 21l8.8-8.6a5.5 5.5 0 0 0 0-7.8Z"/></svg><span class="action-badge">{{ $wishlistCount }}</span></span>
        <span>Wishlist</span>
    </a>
    <a class="action-item" href="{{ route('cart') }}" aria-label="Cart" title="Giỏ hàng">
        <span class="action-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 3h2l2.4 11.5a2 2 0 0 0 2 1.5h9.2a2 2 0 0 0 2-1.6L21 7H5"/><circle cx="9" cy="21" r="1"/><circle cx="18" cy="21" r="1"/></svg><span class="action-badge">{{ array_sum(session('cart',[])) }}</span></span>
        <span>Cart</span>
    </a>
</div>
</div></header>
<main class="container" style="padding-top:24px;padding-bottom:70px">
@if(session('success'))<div class="alert success" role="status">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert error" role="alert"><strong>Vui lòng kiểm tra lại:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
@yield('content')
</main>
<footer><div class="container footer-grid"><div><a class="brand" href="{{ route('home') }}">{{ $shopContent['brand_name'] }}</a><p>{{ $shopContent['tagline'] }}</p></div><div><b>Khám phá</b><a href="{{ route('products') }}">Tất cả sản phẩm</a><a href="{{ route('wishlist') }}">Danh sách yêu thích</a><a href="{{ route('orders') }}">Đơn hàng của tôi</a><a href="{{ route('wallet') }}">Ví & nạp tiền</a></div><div><b>Thông tin</b><a href="{{ route('page','huong-dan-mua-hang') }}">Hướng dẫn mua hàng</a><a href="{{ route('page','chinh-sach') }}">Chính sách bảo hành & hoàn tiền</a><a href="{{ route('page','faq') }}">Câu hỏi thường gặp</a><a href="{{ route('page','gioi-thieu') }}">Giới thiệu</a></div><div><b>Liên hệ hỗ trợ</b>@if($shopContent['support_email'])<a href="mailto:{{ $shopContent['support_email'] }}">{{ $shopContent['support_email'] }}</a>@endif @if($shopContent['support_hours'])<p>{{ $shopContent['support_hours'] }}</p>@endif @if($shopContent['support_url'])<a href="{{ $shopContent['support_url'] }}" target="_blank" rel="noopener noreferrer">Kênh hỗ trợ trực tiếp</a>@endif<a href="{{ route('support') }}">Gửi yêu cầu hỗ trợ</a>@if(auth('admin')->check())<a href="{{ route('admin.dashboard') }}">Quản trị</a>@endif</div></div><div class="container footer-bottom">© {{ date('Y') }} {{ $shopContent['brand_name'] }}. <span>Giá hiển thị bằng Việt Nam đồng (₫).</span></div></footer>
</body></html>
