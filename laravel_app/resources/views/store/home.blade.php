@extends('layouts.app')
@section('title', $shopContent['seo_title'])
@section('content')
@if($banners->isNotEmpty())
<section class="hero-slider-container {{ $banners->count() < 2 ? 'single-banner' : '' }}" data-carousel aria-label="Banner nổi bật" aria-roledescription="carousel">
    <div class="hero-slider-track">
        <button class="slider-nav-btn prev" aria-label="Banner trước" type="button" @disabled($banners->count() < 2)>‹</button>
        @if($banners->count() > 1)
            @include('store.banner-preview', ['previewBanner' => $banners->last(), 'direction' => 'previous'])
        @endif
        <div class="hero-slider-stage" aria-live="polite" aria-atomic="true">
        @foreach($banners as $banner)
        <div class="slide-main" data-carousel-slide data-banner-title="{{ $banner->title }}" role="group" aria-roledescription="slide" aria-label="{{ $loop->iteration }} / {{ $banners->count() }}" @if(!$loop->first) hidden @endif>
            <div class="slide-main-visual">@if($banner->image_url ?: $banner->product?->image_url)<img src="{{ $banner->image_url ?: $banner->product?->image_url }}" alt="{{ $banner->title }}">@endif</div>
            <div class="slide-main-content">
                @if($banner->product && $banner->product->original_price > $banner->product->price)<div class="starburst-badge">−{{ round((1-$banner->product->price/$banner->product->original_price)*100) }}%</div>@endif
                <h1 class="slide-title-logo">{{ $banner->title }}</h1>
                <p class="slide-subtitle" style="white-space:pre-line">{{ $banner->description }}</p>
                <div class="slide-actions"><a class="buy-now-btn" href="{{ $banner->product ? route('product', $banner->product) : ($banner->link_url ?: route('products')) }}"><span>{{ $banner->button_text }}</span>@if($banner->product)<small>{{ number_format($banner->product->price,0,',','.') }}₫</small>@endif</a></div>
            </div>
        </div>
        @endforeach
        </div>
        @if($banners->count() > 1)
            @include('store.banner-preview', ['previewBanner' => $banners->get(1), 'direction' => 'next'])
        @endif
        <button class="slider-nav-btn next" aria-label="Banner tiếp theo" type="button" @disabled($banners->count() < 2)>›</button>
    </div>
    @if($banners->count() > 1)<div class="slider-dots">@foreach($banners as $banner)<button class="slider-dot {{ $loop->first ? 'active' : '' }}" type="button" data-carousel-dot="{{ $loop->index }}" aria-label="Xem banner {{ $loop->iteration }}" aria-pressed="{{ $loop->first ? 'true' : 'false' }}"></button>@endforeach</div>@endif
</section>
@else<section class="panel"><h1>{{ $shopContent['brand_name'] }}</h1><p>{{ $shopContent['tagline'] }}</p><a class="button" href="{{ route('products') }}">Khám phá sản phẩm</a></section>@endif
<section class="section-deals featured-products" id="san-pham-noi-bat">
    <div class="section-deals-header">
        <h2 class="section-deals-title">SẢN PHẨM NỔI BẬT</h2>
        <x-section-link :href="route('products')">Xem tất cả</x-section-link>
    </div>
    @if($featured->isNotEmpty())
        <div class="featured-showcase-grid">
            @foreach($featured as $product)<x-product-card :product="$product" variant="showcase"/>@endforeach
        </div>
    @endif
    @if($moreProducts->isNotEmpty())
        <div class="featured-catalog-grid">
            @foreach($moreProducts as $product)<x-product-card :product="$product" variant="compact"/>@endforeach
        </div>
    @elseif($featured->isEmpty())
        <div class="empty">Chưa có sản phẩm đang bán.</div>
    @endif
</section>
<section class="benefits">
    @foreach(array_slice(preg_split('/\r\n|\r|\n/', $shopContent['benefits']), 0, 4) as $benefit)
        @php
            $parts = explode('|', $benefit, 2);
        @endphp
        <div><span>◇</span><div><b>{{ $parts[0] }}</b><small>{{ $parts[1] ?? '' }}</small></div></div>
    @endforeach
</section>
<section class="section" id="danh-muc">
    <div class="section-heading storefront-section-heading">
        <h2>DANH MỤC SẢN PHẨM</h2>
        <x-section-link :href="route('products')">Xem tất cả</x-section-link>
    </div>
    <div class="category-grid">
        @foreach($categories as $category)
            @php
                [$categoryIcon, $categoryTone] = match($category->slug) {
                    'game-steam' => ['gamepad', 'cyan'],
                    'game-pc' => ['monitor', 'violet'],
                    'phan-mem' => ['layers', 'blue'],
                    'the-qua-tang' => ['gift', 'amber'],
                    default => ['grid', 'cyan'],
                };
            @endphp
            <a class="category-tile" data-tone="{{ $categoryTone }}" href="{{ route('products', ['category' => $category->slug]) }}">
                <span class="category-icon"><x-icon :name="$categoryIcon"/></span>
                <div class="category-copy"><b>{{ $category->name }}</b><small>{{ $category->products_count }} sản phẩm</small></div>
                <span class="category-open"><x-icon name="chevron-right"/></span>
            </a>
        @endforeach
    </div>
</section>
<section class="section">
    <div class="section-heading storefront-section-heading">
        <h2>MỚI CẬP NHẬT</h2>
        <x-section-link :href="route('products', ['sort' => 'newest'])">Xem thêm</x-section-link>
    </div>
    <div class="deals-grid">@foreach($latest as $product)<x-product-card :product="$product"/>@endforeach</div>
</section>
@endsection
