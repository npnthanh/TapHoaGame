<?php

namespace App\Services;

use App\Models\{User, Wallet, WalletTransaction};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WalletService
{
    // All balance mutations lock the user first, then wallet. Caller transactions may nest.
    public function change(User $user, int $amount, string $reference, string $type, string $description, array $links = []): WalletTransaction
    {
        return DB::transaction(function () use ($user, $amount, $reference, $type, $description, $links) {
            User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            if ($existing = WalletTransaction::where('reference', $reference)->first()) {
                if ($existing->wallet_id !== Wallet::where('user_id', $user->id)->value('id') || $existing->amount !== $amount || $existing->type !== $type) {
                    throw ValidationException::withMessages(['wallet' => 'Mã giao dịch đã được sử dụng.']);
                }
                return $existing;
            }
            $wallet = Wallet::firstOrCreate(['user_id' => $user->id]);
            $wallet = Wallet::whereKey($wallet->id)->lockForUpdate()->firstOrFail();
            $newBalance = $wallet->balance + $amount;
            if ($newBalance < 0 || $newBalance > 1_000_000_000_000) {
                throw ValidationException::withMessages(['wallet' => 'Số dư ví không đủ hoặc vượt giới hạn cho phép.']);
            }
            $wallet->forceFill(['balance' => $newBalance])->save();

            return WalletTransaction::create([
                'wallet_id' => $wallet->id, 'reference' => $reference, 'type' => $type,
                'amount' => $amount, 'balance_after' => $newBalance, 'description' => $description,
                ...array_intersect_key($links, array_flip(['order_id', 'deposit_id', 'actor_id'])),
            ]);
        }, 3);
    }
}
