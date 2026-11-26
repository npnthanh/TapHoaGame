@extends('layouts.app')
@section('title', 'Ví cá nhân & Nạp tiền')
@section('content')

@include('account.nav')

<div class="page-heading">
    <div class="section-kicker">QUẢN LÝ TÀI CHÍNH</div>
    <h1>Ví tiền & Lịch sử giao dịch</h1>
    <p>Theo dõi số dư, nạp tiền tự động và quản lý lịch sử biến động số dư minh bạch.</p>
</div>

<div class="two-columns">
    <div class="stat wallet-stat" style="border-radius: var(--radius-md); padding: 32px;">
        <small style="color: var(--cyan); letter-spacing: 1.5px; font-weight: 700;">SỐ DƯ KHẢ DỤNG</small>
        <strong>{{ number_format($wallet?->balance ?? 0, 0, ',', '.') }}₫</strong>
        <p style="margin: 8px 0 0; color: #94a3b8; font-size: 13px;">
            Số dư dùng để thanh toán mua key tức thì không cần chờ duyệt.
        </p>
    </div>

    <section class="panel">
        <h2 style="font-size: 18px; margin-bottom: 16px;">⚡ Nạp tiền vào ví</h2>
        @if(config('shop.payments.enabled') && config('shop.payments.account') && config('shop.payments.secret'))
            <form method="post" action="{{ route('deposits.store') }}" class="stack">
                @csrf
                <label>
                    Số tiền cần nạp (₫)
                    <input type="number" name="amount" min="10000" max="50000000" step="1000" value="100000" required>
                </label>
                <button class="button" style="width: 100%;">Tạo yêu cầu nạp tiền</button>
            </form>
        @else
            <div style="background: rgba(255, 255, 255, 0.03); border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 18px;">
                <p class="muted" style="margin: 0 0 14px; font-size: 13px; line-height: 1.6;">
                    Kênh nạp tiền tự động đang được bảo trì hoặc thiết lập. Mày vui lòng gửi ticket hoặc liên hệ hỗ trợ để được hướng dẫn nạp trực tiếp qua tài khoản ngân hàng nhé.
                </p>
                <a class="button secondary small" href="{{ route('support') }}">
                    Gửi yêu cầu hỗ trợ nạp tiền
                </a>
            </div>
        @endif
    </section>
</div>

<section class="section">
    <div class="section-heading">
        <h2>Yêu cầu nạp tiền gần đây</h2>
    </div>

    @forelse($deposits as $deposit)
        <div class="panel deposit-row" style="margin-bottom: 16px;">
            <div>
                <b style="font-size: 16px; color: #fff;">{{ $deposit->reference }}</b>
                <p style="margin: 6px 0; font-size: 14px;">
                    <strong style="color: var(--cyan);">{{ number_format($deposit->amount, 0, ',', '.') }}₫</strong> · 
                    <x-status :value="$deposit->status === 'pending' && $deposit->expires_at->isPast() ? 'expired' : $deposit->status"/>
                </p>
                <small class="muted">
                    Thời gian: {{ $deposit->created_at->format('d/m/Y H:i') }} · Mã đối tác: {{ $deposit->provider_transaction_id ?: 'Chờ thanh toán' }}
                </small>
            </div>

            @if($deposit->status === 'pending' && $deposit->expires_at->isFuture())
                <div style="background: rgba(0, 240, 255, 0.06); border: 1px solid rgba(0, 240, 255, 0.25); border-radius: var(--radius-sm); padding: 14px; min-width: 260px;">
                    <b style="color: #fff; font-size: 13px;">{{ config('shop.payments.bank') }} · {{ config('shop.payments.account') }}</b>
                    <p style="margin: 4px 0; font-size: 12px; color: #cbd5e1;">Chủ tài khoản: {{ config('shop.payments.holder') }}</p>
                    <p style="margin: 4px 0; font-size: 12px;">Cú pháp CK: <code style="color: var(--cyan); font-weight: 700;">{{ $deposit->reference }}</code></p>
                    <small class="muted" style="display: block; margin-top: 4px;">Hết hạn: {{ $deposit->expires_at->format('H:i d/m/Y') }}</small>
                </div>
            @endif
        </div>
    @empty
        <div class="panel" style="text-align: center; padding: 36px 20px;">
            <p class="muted" style="margin: 0;">Chưa có yêu cầu nạp tiền nào được tạo.</p>
        </div>
    @endforelse

    <div style="margin-top: 20px;">
        {{ $deposits->links('pagination.simple') }}
    </div>
</section>

<section class="section">
    <div class="section-heading">
        <h2>Lịch sử biến động số dư</h2>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Thời gian</th>
                    <th>Nội dung / Mã tham chiếu</th>
                    <th>Biến động số dư</th>
                    <th>Số dư sau giao dịch</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transactions as $entry)
                    <tr>
                        <td>{{ $entry->created_at->format('d/m/Y H:i') }}</td>
                        <td>
                            <strong style="color: #fff;">{{ $entry->description }}</strong>
                            <small class="muted block" style="font-family: monospace;">{{ $entry->reference }}</small>
                        </td>
                        <td style="font-weight: 700; color: {{ $entry->amount >= 0 ? 'var(--green)' : '#ff3366' }};">
                            {{ $entry->amount >= 0 ? '+' : '' }}{{ number_format($entry->amount, 0, ',', '.') }}₫
                        </td>
                        <td style="font-weight: 600; color: #fff;">
                            {{ number_format($entry->balance_after, 0, ',', '.') }}₫
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="empty">Chưa có giao dịch biến động số dư.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($transactions instanceof \Illuminate\Contracts\Pagination\Paginator)
        <div style="margin-top: 20px;">
            {{ $transactions->links('pagination.simple') }}
        </div>
    @endif
</section>

@endsection
