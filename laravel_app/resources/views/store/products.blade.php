@extends('layouts.app')
@section('title', 'Khám phá kho game bản quyền')
@section('content')

<div class="page-heading">
    <div class="section-kicker">KHO GAME BẢN QUYỀN TOÀN DIỆN</div>
    <h1>Khám phá bộ sưu tập game</h1>
    <p>Tìm kiếm tựa game bạn yêu thích với mức giá ưu đãi và nhận key ngay lập tức.</p>
</div>

<div class="catalog-layout">
    <aside class="panel">
        <h3 style="display: flex; align-items: center; gap: 8px; margin-bottom: 20px;">
            <span>⚡</span> Bộ lọc nâng cao
        </h3>
        
        <form action="{{ route('products') }}" class="stack">
            <label>
                Từ khóa tìm kiếm
                <input name="q" value="{{ request('q') }}" placeholder="Tên game, từ khóa...">
            </label>

            <label>
                Danh mục nền tảng
                <select name="category">
                    <option value="">Tất cả danh mục</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->slug }}" @selected(request('category') === $category->slug)>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
            </label>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                <label>
                    Giá từ (₫)
                    <input name="min" type="number" min="0" value="{{ request('min') }}" placeholder="0">
                </label>
                <label>
                    Đến (₫)
                    <input name="max" type="number" min="0" value="{{ request('max') }}" placeholder="Max">
                </label>
            </div>

            <label>
                Tình trạng kho
                <select name="stock">
                    <option value="">Tất cả trạng thái</option>
                    <option value="available" @selected(request('stock') === 'available')>Còn hàng (Sẵn sàng giao)</option>
                    <option value="soldout" @selected(request('stock') === 'soldout')>Tạm hết hàng</option>
                </select>
            </label>

            <label>
                Sắp xếp theo
                <select name="sort">
                    @foreach(['newest' => 'Mới nhất', 'price_asc' => 'Giá tăng dần', 'price_desc' => 'Giá giảm dần', 'bestselling' => 'Bán chạy nhất'] as $key => $label)
                        <option value="{{ $key }}" @selected(request('sort', 'newest') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>

            <button class="button" style="width: 100%;">Áp dụng bộ lọc</button>
            <a class="muted" href="{{ route('products') }}" style="text-align: center; font-size: 12px; margin-top: 4px;">Xóa toàn bộ bộ lọc</a>
        </form>
    </aside>

    <div>
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <p class="muted" style="margin: 0; font-size: 13px;">
                Tìm thấy <strong style="color: #fff;">{{ $products->total() }}</strong> sản phẩm phù hợp
            </p>
        </div>

        <div class="product-grid catalog-grid">
            @forelse($products as $product)
                <x-product-card :product="$product"/>
            @empty
                <div class="empty">
                    <h2>Không tìm thấy sản phẩm</h2>
                    <p>Không có sản phẩm nào khớp với bộ lọc hiện tại. Mày hãy thử đổi từ khóa hoặc khoảng giá nhé.</p>
                    <a class="button secondary" href="{{ route('products') }}">Xem tất cả sản phẩm</a>
                </div>
            @endforelse
        </div>

        <div style="margin-top: 36px;">
            {{ $products->links('pagination.simple') }}
        </div>
    </div>
</div>

@endsection
