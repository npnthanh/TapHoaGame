@extends('layouts.app')
@section('title', 'Sản phẩm yêu thích')
@section('content')@include('account.nav')<div class="page-heading"><h1>Sản phẩm yêu thích</h1></div><div class="deals-grid">@forelse($products as $product)<div><x-product-card :product="$product"/><form method="post" action="{{ route('wishlist.remove', $product) }}" class="section">@csrf @method('DELETE')<button class="text-button">Bỏ yêu thích</button></form></div>@empty<div class="empty">Danh sách yêu thích của bạn đang trống.</div>@endforelse</div>{{ $products->links('pagination.simple') }}@endsection
