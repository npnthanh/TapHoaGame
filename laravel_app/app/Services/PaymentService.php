<?php

namespace App\Services;

use App\Models\{Deposit, User};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentService
{
    public function __construct(private WalletService $wallet) {}

    public function confirm(array $data, string $payloadHash): Deposit
    {
        return DB::transaction(function () use ($data, $payloadHash) {
            $provider = config('shop.payments.provider');
            $candidate = Deposit::where('reference', $data['reference'])->where('provider', $provider)->firstOrFail();
            $user = User::whereKey($candidate->user_id)->lockForUpdate()->firstOrFail();
            $deposit = Deposit::whereKey($candidate->id)->lockForUpdate()->firstOrFail();
            if ($data['currency'] !== 'VND' || $data['direction'] !== 'in' || $data['status'] !== 'paid'
                || (int) $data['amount'] !== $deposit->amount || $data['account'] !== config('shop.payments.account')) {
                throw ValidationException::withMessages(['payment' => 'Giao dịch không khớp yêu cầu nạp tiền.']);
            }
            $existing = DB::table('payment_events')->where('provider', $provider)->where('event_id', $data['event_id'])->first();
            if ($existing) {
                abort_unless($existing->deposit_id === $deposit->id && hash_equals($existing->payload_hash, $payloadHash), 409, 'Event conflict.');
                return $deposit;
            }
            if ($deposit->status === 'paid') {
                abort_unless($deposit->provider_transaction_id === $data['transaction_id'], 409, 'Transaction conflict.');
            } else {
                abort_unless($deposit->status === 'pending' && $deposit->expires_at->isFuture(), 409, 'Deposit expired or closed.');
                abort_if(DB::table('deposits')->where('provider', $provider)->where('provider_transaction_id', $data['transaction_id'])->exists(), 409, 'Transaction already used.');
                $deposit->update(['status' => 'paid', 'provider_transaction_id' => $data['transaction_id'], 'paid_at' => now()]);
                $this->wallet->change($user, $deposit->amount, 'deposit:'.$deposit->reference, 'deposit', 'Nạp tiền '.$deposit->reference, ['deposit_id' => $deposit->id]);
            }
            DB::table('payment_events')->insert(['provider' => $provider, 'event_id' => $data['event_id'], 'payload_hash' => $payloadHash, 'deposit_id' => $deposit->id, 'created_at' => now(), 'updated_at' => now()]);
            return $deposit;
        }, 3);
    }
}
