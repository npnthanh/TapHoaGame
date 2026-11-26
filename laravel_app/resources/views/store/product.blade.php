@extends('layouts.app')
@section('title', $product->seo_title ?: $product->name)
@section('description', $product->seo_description ?: Str::limit($product->description, 160))
@section('content')

<div class="breadcrumb">
    <a href="{{ route('home') }}">Trang chủ</a> / 
    <a href="{{ route('products') }}">Sản phẩm</a> / 
    <a href="{{ route('products', ['category' => $product->category->slug]) }}">{{ $product->category->name }}</a> / 
    <span style="color: #fff;">{{ $product->name }}</span>
</div>

<div class="detail-grid">
    <div class="product-art detail-art tone-{{ $product->id % 5 }}">
        @if($product->image_url)
            <img src="{{ $product->image_url }}" alt="{{ $product->name }}">
        @else
            <div class="art-orbit"></div>
            <span class="cover-platform">{{ $product->platform }}</span>
            <strong>{{ $product->name }}</strong>
            <span class="cover-edition">SẢN PHẨM SỐ</span>
        @endif

        @if($product->original_price > $product->price)
            <span class="discount" style="font-size: 13px; padding: 4px 12px;">
                −{{ round((1 - $product->price / $product->original_price) * 100) }}%
            </span>
        @endif
    </div>

    <section>
        <div style="display: flex; gap: 8px; align-items: center; margin-bottom: 12px;">
            <span class="pill">{{ $product->category->name }}</span>
            <span class="pill" style="border-color: rgba(255,255,255,0.15); color: #fff; background: rgba(255,255,255,0.05);">
                {{ $product->platform }}
            </span>
        </div>

        <h1 style="margin-bottom: 10px;">{{ $product->name }}</h1>
        
        <p class="muted" style="font-size: 13px;">
            Nền tảng: <strong style="color: #fff;">{{ $product->platform }}</strong> · 
            Khu vực: <strong style="color: #fff;">{{ $product->region }}</strong> · 
            Mã định danh SKU: <code style="color: var(--cyan);">{{ $product->sku }}</code>
        </p>

        <div class="price-line detail-price" style="display: flex; align-items: baseline; gap: 14px;">
            <strong style="color: var(--cyan); font-size: 36px;">
                {{ number_format($product->price, 0, ',', '.') }}₫
            </strong>
            @if($product->original_price > $product->price)
                <del style="font-size: 18px; color: #64748b;">
                    {{ number_format($product->original_price, 0, ',', '.') }}₫
                </del>
            @endif
        </div>

        <p class="stock {{ $product->available_keys_count ? '' : 'out' }}" style="font-size: 13px; margin: 16px 0;">
            ● {{ $product->available_keys_count ? 'Sẵn sàng giao tự động: Còn '.$product->available_keys_count.' key trong kho' : 'Tạm thời hết hàng' }}
        </p>

        <form action="{{ route('cart.add', $product) }}" method="post" class="buy-form">
            @csrf
            <label style="min-width: 90px;">
                Số lượng mua
                <input type="number" name="quantity" value="1" min="1" max="{{ min(20, $product->available_keys_count) }}" required>
            </label>
            <button class="button" style="height: 44px;" @disabled(!$product->available_keys_count)>
                Thêm vào giỏ hàng 
            </button>
        </form>

        <div style="background: rgba(0, 240, 255, 0.04); border: 1px solid rgba(0, 240, 255, 0.15); border-radius: var(--radius-sm); padding: 16px; margin-top: 24px;">
            <div style="display: flex; align-items: center; gap: 10px; font-weight: 700; color: #fff; margin-bottom: 6px;">
                <span style="color: var(--cyan); font-size: 18px;">⚡</span> Giao nhận tự động & Bảo hành
            </div>
            <p style="margin: 0; font-size: 12px; color: #94a3b8; line-height: 1.6;">
                Key được lưu trong tài khoản sau khi thanh toán thành công. Điều kiện bảo hành và hoàn tiền áp dụng theo chính sách cửa hàng đã công bố.
            </p>
        </div>
    </section>
</div>

<div class="two-columns section">
    <section class="panel">
        <h2 style="display: flex; align-items: center; gap: 8px; margin-bottom: 18px;">
            <span>📋</span> Chi tiết sản phẩm & Cấu hình
        </h2>
        <div class="prose">{{ $product->description ?: 'Thông tin chi tiết về sản phẩm đang được ban quản trị cập nhật.' }}</div>
    </section>

    <section class="panel">
        <h2 style="display: flex; align-items: center; gap: 8px; margin-bottom: 18px;">
            <span>🔑</span> Hướng dẫn kích hoạt key
        </h2>
        <div class="prose">{{ $product->instructions ?: 'Sau khi thanh toán thành công, truy cập "Đơn hàng của tôi" để lấy mã key. Khởi động ứng dụng của nền tảng (Steam/Epic) -> Kích hoạt sản phẩm -> Nhập mã key.' }}</div>
    </section>
</div>

<section class="panel section"><h2>Đánh giá từ khách đã mua</h2>@forelse($product->approvedReviews as $review)<article class="ticket"><b>{{ $review->user->name }} · {{ $review->rating }}/5</b><small>{{ $review->created_at->format('d/m/Y') }}</small><p class="prose">{{ $review->comment }}</p></article>@empty<p class="muted">Chưa có đánh giá được duyệt.</p>@endforelse
@auth('web')<form method="post" action="{{ route('reviews.store', $product) }}" class="stack section">@csrf<label>Điểm đánh giá<select name="rating">@foreach(range(5,1) as $rating)<option value="{{ $rating }}">{{ $rating }} / 5</option>@endforeach</select></label><label>Nhận xét<textarea name="comment" maxlength="3000" rows="3"></textarea></label><small>Chỉ khách đã mua thành công sản phẩm này có thể gửi đánh giá. Đánh giá sẽ được duyệt trước khi hiển thị.</small><button class="button">Gửi đánh giá</button></form>@endauth
<form method="post" action="{{ route('wishlist.add', $product) }}" class="section">@csrf<button class="button secondary">♡ Thêm vào yêu thích</button></form></section>
@endsection
