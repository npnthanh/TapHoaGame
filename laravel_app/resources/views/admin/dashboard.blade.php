@extends('layouts.admin')
@section('title', 'Tổng quan hệ thống quản trị')
@section('content')

@include('admin.nav')

<div class="page-heading">
    <div class="section-kicker">THỐNG KÊ DOANH THU & HOẠT ĐỘNG</div>
    <h1>Tổng quan hoạt động cửa hàng</h1>
    <p class="muted">
        Cập nhật: {{ now()->format('d/m/Y H:i') }} · Số liệu doanh thu từ các đơn hàng hoàn tất, tự động trừ đơn hoàn tiền.
    </p>
</div>

<div class="stats-grid">
    @foreach(['Tổng doanh thu' => $revenue, 'Hôm nay' => $today, 'Tuần này' => $week, 'Tháng này' => $month] as $label => $value)
        <div class="stat wallet-stat" style="border-radius: var(--radius-md);">
            <small style="color: var(--cyan); letter-spacing: 0.8px; font-weight: 700;">{{ mb_strtoupper($label) }}</small>
            <strong>{{ number_format($value, 0, ',', '.') }}₫</strong>
        </div>
    @endforeach
</div>

<div class="stats-grid section" style="margin-top: 24px;">
    @foreach($counts as $label => $value)
        <div class="stat compact" style="border-radius: var(--radius-md);">
            <small style="color: #94a3b8; font-weight: 600;">{{ $label }}</small>
            <strong style="color: #fff; font-size: 26px;">{{ number_format($value) }}</strong>
        </div>
    @endforeach
</div>

<section class="panel section">
    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border); padding-bottom: 16px; margin-bottom: 20px;">
        <h2 style="font-size: 18px; margin: 0;">Biểu đồ doanh thu 7 ngày gần nhất</h2>
        <span class="pill" style="font-size: 10px;">LIVE METRICS</span>
    </div>

    <div class="bar-chart">
        @foreach($chart as $day)
            <div class="bar-column">
                <small style="color: var(--cyan); font-weight: 700;">{{ number_format($day['total'], 0, ',', '.') }}₫</small>
                <div class="bar-track">
                    <div style="height: {{ $chart->max('total') ? max(2, $day['total'] / $chart->max('total') * 100) : 2 }}%"></div>
                </div>
                <span style="color: #94a3b8; margin-top: 6px;">{{ $day['label'] }}</span>
            </div>
        @endforeach
    </div>

    <div style="border-top: 1px solid var(--border); padding-top: 14px; margin-top: 16px; display: flex; gap: 14px; align-items: center; flex-wrap: wrap;">
        <span class="muted" style="font-size: 12px;">Nạp tiền đã duyệt: <strong style="color: #fff;">{{ $depositCount }}</strong></span>
        <span class="muted" style="font-size: 12px;">·</span>
        <span class="muted" style="font-size: 12px;">Trạng thái đơn:</span>
        @foreach($statuses as $status => $count)
            <span style="display: inline-flex; align-items: center; gap: 6px; font-size: 12px;">
                <x-status :value="$status"/> <strong style="color: #fff;">{{ $count }}</strong>
            </span>
        @endforeach
    </div>
</section>

<section class="section">
    <div class="section-heading">
        <h2>Đơn hàng mới nhất</h2>
        <a class="text-link" href="{{ route('admin.orders') }}">Xem toàn bộ đơn hàng</a>
    </div>
    @include('admin.order-table')
</section>

@endsection
