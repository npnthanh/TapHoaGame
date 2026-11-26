@extends('layouts.app')
@section('title', 'Đơn hàng '.$order->number)
@section('content')

@include('account.nav')

<div class="page-heading">
    <div class="section-kicker">CHI TIẾT ĐƠN HÀNG</div>
    <div style="display: flex; align-items: baseline; gap: 16px; flex-wrap: wrap;">
        <h1 style="margin: 0; font-family: monospace;">{{ $order->number }}</h1>
        <x-status :value="$order->status"/>
    </div>
    <p class="muted" style="margin-top: 8px;">
        Thời gian khởi tạo: {{ $order->created_at->format('d/m/Y H:i:s') }} · Phương thức: Thanh toán số dư ví
    </p>
</div>

<div class="panel" style="margin-bottom: 24px;">
    <div class="summary-row">
        <span class="muted">Tạm tính đơn hàng:</span>
        <b>{{ number_format($order->subtotal, 0, ',', '.') }}₫</b>
    </div>
    @if($order->discount > 0)
        <div class="summary-row">
            <span class="muted">Mã giảm giá đã áp dụng:</span>
            <b style="color: var(--green);">−{{ number_format($order->discount, 0, ',', '.') }}₫</b>
        </div>
    @endif
    <div class="summary-row" style="border-bottom: none; padding-bottom: 0;">
        <span style="font-size: 16px; font-weight: 700; color: #fff;">Tổng tiền đã thanh toán qua ví:</span>
        <b style="font-size: 20px; color: var(--cyan); font-family: var(--font-heading);">
            {{ number_format($order->total, 0, ',', '.') }}₫
        </b>
    </div>
</div>

@foreach($order->items as $item)
    <section class="panel" style="margin-bottom: 20px; border-color: rgba(0, 240, 255, 0.2);">
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border); padding-bottom: 14px; margin-bottom: 16px;">
            <div>
                <h2 style="font-size: 18px; margin: 0; color: #fff;">{{ $item->product_name }}</h2>
                <p class="muted" style="margin: 4px 0 0; font-size: 12px;">
                    Số lượng: <strong style="color: #fff;">{{ $item->quantity }} key</strong> · 
                    Đơn giá: {{ number_format($item->unit_price, 0, ',', '.') }}₫ / key
                </p>
            </div>
            <strong style="font-size: 18px; color: #fff;">
                {{ number_format($item->total, 0, ',', '.') }}₫
            </strong>
        </div>

        @if($order->status === 'completed' && $order->payment_status === 'paid')
            <p style="font-size: 12px; color: var(--green); font-weight: 600; margin-bottom: 12px;">
                ✓ Đã bàn giao key thành công. Hãy bấm "Sao chép" để nhập vào tài khoản của bạn:
            </p>
            @foreach($item->keys as $key)
                <div class="key-row">
                    <code id="key-{{ $key->id }}">{{ $key->secret }}</code>
                    <button type="button" class="button small secondary" data-copy="key-{{ $key->id }}">
                        Sao chép
                    </button>
                </div>
            @endforeach
        @else
            <p class="muted" style="margin: 0;">Đơn hàng này không ở trạng thái giao key.</p>
        @endif
    </section>
@endforeach

<div style="margin-top: 24px;">
    <a class="text-link" href="{{ route('support', ['order' => $order->number]) }}">
        Gặp vấn đề với key kích hoạt? Gửi ticket yêu cầu hỗ trợ đơn hàng này
    </a>
</div>

@endsection
