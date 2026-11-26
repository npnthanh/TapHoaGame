<div class="admin-label" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
    <span class="pill" style="border-color: rgba(0, 240, 255, 0.4); background: rgba(0, 240, 255, 0.1);">
        ⚡ TẠP HÓA GAME / HỆ THỐNG QUẢN TRỊ
    </span>
    <a class="text-link" href="{{ route('home') }}" style="font-size: 12px;">
        ← Xem cửa hàng
    </a>
</div>

<nav class="subnav">
    <a href="{{ route('admin.content') }}">Nội dung & cấu hình</a>
    <a class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">
        📊 Tổng quan
    </a>
    <a class="{{ request()->routeIs('admin.products*') ? 'active' : '' }}" href="{{ route('admin.products') }}">
        🎮 Sản phẩm
    </a>
    <a class="{{ request()->routeIs('admin.keys*') ? 'active' : '' }}" href="{{ route('admin.keys') }}">
        🔑 Kho key
    </a>
    <a class="{{ request()->routeIs('admin.orders*') ? 'active' : '' }}" href="{{ route('admin.orders') }}">
        📦 Đơn hàng
    </a>
    <a class="{{ request()->routeIs('admin.coupons*') ? 'active' : '' }}" href="{{ route('admin.coupons') }}">
        🏷️ Mã giảm giá
    </a>
    <a class="{{ request()->routeIs('admin.support*') ? 'active' : '' }}" href="{{ route('admin.support') }}">
        💬 Hỗ trợ
    </a>
    @can('manage-finance')
        <a href="{{ route('admin.reports') }}">Báo cáo & xuất CSV</a>
        <a class="{{ request()->routeIs('admin.users*') ? 'active' : '' }}" href="{{ route('admin.users') }}">
            👥 Khách hàng & Ví
        </a>
        <a class="{{ request()->routeIs('admin.finance*') ? 'active' : '' }}" href="{{ route('admin.finance') }}">
            💳 Nạp tiền & Nhật ký
        </a>
    @endcan
</nav>
