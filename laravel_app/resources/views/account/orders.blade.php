@extends('layouts.app')
@section('title', 'Lịch sử đơn hàng & Kho key')
@section('content')

@include('account.nav')

<div class="page-heading">
    <div class="section-kicker">KHO BẢN QUYỀN CÁ NHÂN</div>
    <h1>Đơn hàng & Key của bạn</h1>
    <p>Tra cứu lại các mã key game đã mua và kiểm tra tình trạng giao hàng.</p>
</div>

<form class="filter-bar" style="margin-bottom: 24px;">
    <input aria-label="Mã đơn hàng" name="q" value="{{ request('q') }}" placeholder="Nhập mã đơn hàng (VD: SA...)" style="max-width: 380px;">
    <button class="button small">Tra cứu đơn</button>
</form>

<div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>Mã đơn hàng</th>
                <th>Thời gian mua</th>
                <th>Tổng thanh toán</th>
                <th>Trạng thái</th>
                <th>Thao tác</th>
            </tr>
        </thead>
        <tbody>
            @forelse($orders as $order)
                <tr>
                    <td>
                        <strong style="color: #fff; font-family: monospace; font-size: 14px;">{{ $order->number }}</strong>
                    </td>
                    <td>{{ $order->created_at->format('d/m/Y H:i') }}</td>
                    <td>
                        <strong style="color: var(--cyan);">{{ number_format($order->total, 0, ',', '.') }}₫</strong>
                    </td>
                    <td>
                        <x-status :value="$order->status"/>
                    </td>
                    <td>
                        <a class="button small secondary" href="{{ route('orders.show', $order) }}" style="padding: 5px 12px;">
                            Xem key & Chi tiết
                        </a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="empty">
                        <div style="font-size: 40px; margin-bottom: 10px;">📦</div>
                        <p style="margin: 0;">Bạn chưa có đơn hàng nào.</p>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div style="margin-top: 24px;">
    {{ $orders->links('pagination.simple') }}
</div>

@endsection
