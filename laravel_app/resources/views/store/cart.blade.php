@extends('layouts.app')
@section('title', 'Giỏ hàng của bạn')
@section('content')

<div class="page-heading">
    <div class="section-kicker">XÁC NHẬN ĐƠN HÀNG</div>
    <h1>Giỏ hàng của bạn</h1>
    <p>Kiểm tra kỹ số lượng và tựa game trước khi tiến hành thanh toán tự động.</p>
</div>

@if(count($cart))
    <div class="checkout-layout">
        <div class="panel">
            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border); padding-bottom: 16px; margin-bottom: 8px;">
                <h3 style="margin: 0;">Danh sách game đã chọn ({{ count($products) }})</h3>
                <form method="post" action="{{ route('cart.clear') }}">
                    @csrf 
                    @method('DELETE')
                    <button class="text-button" style="color: #ff3366;">🗑️ Làm trống giỏ hàng</button>
                </form>
            </div>

            @foreach($products as $product)
                <div class="cart-row">
                    <div style="flex: 1;">
                        <h3>
                            <a href="{{ route('product', $product) }}" style="color: #fff;">{{ $product->name }}</a>
                        </h3>
                        <p class="muted">
                            Nền tảng: <strong style="color: var(--cyan);">{{ $product->platform }}</strong> · 
                            Đơn giá: {{ number_format($product->price, 0, ',', '.') }}₫ / key
                        </p>
                        <span class="stock">● Còn {{ $product->available_keys_count }} key khả dụng</span>
                    </div>

                    <form action="{{ route('cart.update', $product) }}" method="post" class="inline-form">
                        @csrf 
                        @method('PATCH')
                        <input aria-label="Số lượng {{ $product->name }}" type="number" name="quantity" min="0" max="20" value="{{ $cart[$product->id] }}">
                        <button class="button small secondary">Cập nhật</button>
                    </form>

                    <div style="text-align: right; min-width: 120px;">
                        <strong style="color: #fff; font-size: 16px;">
                            {{ number_format($product->price * $cart[$product->id], 0, ',', '.') }}₫
                        </strong>
                    </div>
                </div>
            @endforeach

            @if($products->count() !== count($cart))
                <div class="alert error" style="margin-top: 20px;">
                    Có sản phẩm đã ngừng kinh doanh. Mày vui lòng bấm "Làm trống giỏ hàng" và chọn lại sản phẩm nhé.
                </div>
            @endif

            <p class="muted" style="font-size: 12px; margin-top: 18px;">
                💡 <em>Mẹo: Đặt số lượng về 0 và bấm "Cập nhật" để xóa game khỏi giỏ.</em>
            </p>
        </div>

        <aside class="panel" style="border-color: rgba(0, 240, 255, 0.25);">
            <h2 style="font-size: 20px; border-bottom: 1px solid var(--border); padding-bottom: 14px; margin-bottom: 18px;">
                Tổng kết thanh toán
            </h2>

            <div class="summary-row">
                <span class="muted">Tạm tính giỏ hàng:</span>
                <strong style="color: #fff; font-size: 18px;">{{ number_format($subtotal, 0, ',', '.') }}₫</strong>
            </div>

            @auth('web')
                <div style="background: rgba(0, 240, 255, 0.06); border: 1px solid rgba(0, 240, 255, 0.2); border-radius: var(--radius-sm); padding: 14px; margin: 18px 0;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span style="font-size: 12px; color: var(--muted);">Số dư ví hiện tại:</span>
                        <a class="text-link" href="{{ route('wallet') }}" style="font-size: 11px;">+ Nạp thêm</a>
                    </div>
                    <div style="font-size: 22px; font-weight: 800; font-family: var(--font-heading); color: var(--cyan); margin-top: 4px;">
                        {{ number_format(auth('web')->user()->wallet?->balance ?? 0, 0, ',', '.') }}₫
                    </div>
                </div>

                <form action="{{ route('checkout') }}" method="post" class="stack">
                    @csrf
                    <input type="hidden" name="checkout_token" value="{{ session('checkout_token') }}">
                    
                    <label>
                        Mã giảm giá (Coupon)
                        <input name="coupon" value="{{ old('coupon') }}" placeholder="Nhập mã coupon nếu có" maxlength="50" style="text-transform: uppercase;">
                    </label>

                    <small class="muted" style="font-size: 11px; line-height: 1.5;">
                        Mã giảm giá sẽ được xác thực và trừ trực tiếp khi nhấn xác nhận. Bạn có thể xem lại giao dịch trong lịch sử đơn hàng.
                    </small>

                    <button class="button" style="width: 100%; height: 48px; font-size: 14px;" @disabled(!$products->count() || $products->count() !== count($cart))>
                        ⚡ Xác nhận mua bằng ví
                    </button>
                    
                    <div style="text-align: center;">
                        <a class="text-link" href="{{ route('wallet') }}">Chưa đủ tiền? Nạp ví tại đây</a>
                    </div>
                </form>
            @else
                <div style="padding: 16px 0;">
                    <p style="margin-bottom: 16px;">Vui lòng đăng nhập tài khoản để thanh toán bằng ví và lưu trữ mã key.</p>
                    <a class="button" href="{{ route('login') }}" style="width: 100%;">
                        Đăng nhập để thanh toán
                    </a>
                </div>
            @endauth
        </aside>
    </div>
@else
    <div class="empty">
        <div style="font-size: 48px; margin-bottom: 12px;">🛒</div>
        <h2>Giỏ hàng của bạn đang trống</h2>
        <p>Chưa có tựa game nào trong giỏ hàng. Hãy khám phá ngay các tựa game bom tấn đang có deal sốc!</p>
        <a class="button" href="{{ route('products') }}" style="margin-top: 12px;">
            Khám phá kho game ngay
        </a>
    </div>
@endif

@endsection
