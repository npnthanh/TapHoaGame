<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex,nofollow">
    <title>@yield('title', 'Quản trị') · Tạp Hóa Game Admin</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('assets/favicon.svg') }}">
    <link rel="stylesheet" href="{{ asset('assets/app.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/admin.css') }}">
    <script src="{{ asset('assets/app.js') }}" defer></script>
    <link rel="stylesheet" href="{{ asset('assets/custom-select.css') }}?v={{ filemtime(public_path('assets/custom-select.css')) }}">
    <script src="{{ asset('assets/custom-select.js') }}?v={{ filemtime(public_path('assets/custom-select.js')) }}" defer></script>
</head>
<body class="admin-shell">
<header class="header admin-header">
    <div class="container header-main">
        <a class="brand" href="{{ route('admin.dashboard') }}"><span class="accent">◇</span><span>Tạp Hóa Game</span><small>ADMIN CONSOLE</small></a>
        <div class="header-actions">
            <a class="text-link" href="{{ route('home') }}">← Về cửa hàng</a>
            @auth('admin')
                <span class="admin-user">{{ auth('admin')->user()->name }}</span>
                <form method="post" action="{{ route('admin.logout') }}">@csrf<button class="button small secondary">Đăng xuất quản trị</button></form>
            @endauth
        </div>
    </div>
</header>
<main class="container main">
    @if(session('success'))<div class="alert success" role="status">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert error" role="alert"><strong>Vui lòng kiểm tra lại:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    @yield('content')
</main>
<footer class="admin-footer"><div class="container">© {{ date('Y') }} Tạp Hóa Game · Hệ thống quản trị</div></footer>
</body>
</html>
