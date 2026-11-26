<?php

namespace Tests\Feature;

use App\Models\{User, Deposit, WalletTransaction};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentWebhookTest extends TestCase
{
    use RefreshDatabase;

    private function payload(): array
    {
        config(['shop.payments.enabled' => true, 'shop.payments.secret' => str_repeat('s', 48), 'shop.payments.account' => '123456', 'shop.payments.provider' => 'test-bank']);
        $user = User::factory()->create(); $user->wallet()->create();
        Deposit::create(['user_id' => $user->id, 'reference' => 'NAPTEST', 'amount' => 100000, 'provider' => 'test-bank', 'expires_at' => now()->addMinutes(30)]);
        return ['event_id' => 'event-1', 'transaction_id' => 'bank-tx-1', 'reference' => 'NAPTEST', 'amount' => 100000, 'currency' => 'VND', 'direction' => 'in', 'status' => 'paid', 'account' => '123456'];
    }
    private function send(array $data, ?string $signature = null, ?int $timestamp = null)
    {
        $body = json_encode($data); $timestamp ??= time();
        $signature ??= hash_hmac('sha256', $timestamp.'.'.$body, config('shop.payments.secret'));
        return $this->call('POST', '/payments/webhook', [], [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json', 'HTTP_X_PAYMENT_TIMESTAMP' => (string) $timestamp, 'HTTP_X_PAYMENT_SIGNATURE' => $signature], $body);
    }
    public function test_valid_webhook_and_retries_credit_only_once(): void
    {
        $data = $this->payload();
        $this->send($data)->assertOk(); $this->send($data)->assertOk();
        $data['event_id'] = 'event-2'; $this->send($data)->assertOk();
        $this->assertSame(100000, User::first()->wallet->balance);
        $this->assertSame(1, WalletTransaction::count());
    }
    public function test_invalid_signature_and_stale_timestamp_do_not_credit(): void
    {
        $data = $this->payload();
        $this->send($data, 'bad')->assertUnauthorized();
        $this->send($data, null, time() - 600)->assertUnauthorized();
        $this->assertSame(0, WalletTransaction::count());
    }
    public function test_wrong_amount_account_and_direction_are_rejected(): void
    {
        $data = $this->payload();
        $this->send([...$data, 'amount' => 200000])->assertUnprocessable();
        $this->send([...$data, 'account' => 'OTHER'])->assertUnprocessable();
        $this->send([...$data, 'direction' => 'out'])->assertUnprocessable();
        $this->assertSame(0, WalletTransaction::count());
    }
    public function test_same_bank_transaction_cannot_credit_two_deposits(): void
    {
        $data = $this->payload(); $this->send($data)->assertOk();
        $other = User::factory()->create(); $other->wallet()->create();
        Deposit::create(['user_id' => $other->id, 'reference' => 'NAPOTHER', 'amount' => 100000, 'provider' => 'test-bank', 'expires_at' => now()->addMinutes(30)]);
        $this->send([...$data, 'event_id' => 'event-2', 'reference' => 'NAPOTHER'])->assertConflict();
        $this->assertSame(0, $other->wallet()->first()->balance);
        $this->assertSame(1, WalletTransaction::count());
    }
    public function test_expired_deposit_and_conflicting_event_are_rejected(): void
    {
        $data = $this->payload();
        Deposit::first()->update(['expires_at' => now()->subMinute()]);
        $this->send($data)->assertConflict();
        Deposit::first()->update(['expires_at' => now()->addMinute()]);
        $this->send($data)->assertOk();
        $this->send([...$data, 'transaction_id' => 'different-tx'])->assertConflict();
    }
    public function test_unconfigured_webhook_is_closed(): void
    {
        config(['shop.payments.enabled' => false]);
        $this->postJson('/payments/webhook', [])->assertStatus(503);
    }
}
