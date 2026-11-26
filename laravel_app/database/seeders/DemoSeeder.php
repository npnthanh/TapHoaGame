<?php

namespace Database\Seeders;

use App\Models\{AuditLog, Coupon, Deposit, Order, Product, ProductKey, User, WalletTransaction};
use App\Services\{CheckoutService, KeyImportService, WalletService};
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local', 'testing')) {
            throw new \RuntimeException('DemoSeeder chỉ chạy trong môi trường local hoặc testing.');
        }

        DB::transaction(function () {
            $this->call(CatalogSeeder::class);

            $admin = $this->account('admin@gmail.com', 'Quản trị viên', 'admin');
            $customer = $this->account('khach@gmail.com', 'Khách trải nghiệm', 'customer');

            $products = Product::where('sku', 'like', 'SAMPLE-%')->orderBy('id')->get();
            foreach ($products as $product) {
                $missing = [];
                for ($index = 1; $index <= 10; $index++) {
                    $key = sprintf('DEMO-NOT-ACTIVATABLE-%04d-%02d', $product->id, $index);
                    if (! ProductKey::where('fingerprint', ProductKey::fingerprint($key))->exists()) {
                        $missing[] = $key;
                    }
                }
                if ($missing) {
                    app(KeyImportService::class)->import($product, $missing, $admin);
                    ProductKey::whereIn('fingerprint', array_map([ProductKey::class, 'fingerprint'], $missing))
                        ->update(['created_at' => now()->subDays(4)]);
                }
            }

            $fundedAt = now()->subDays(3);
            $deposit = Deposit::firstOrCreate(['reference' => 'DEMO-NAP-0001'], [
                'user_id' => $customer->id,
                'amount' => 5000000,
                'provider' => 'local-demo',
                'provider_transaction_id' => 'DEMO-BANK-0001',
                'status' => 'paid',
                'paid_at' => $fundedAt,
                'expires_at' => $fundedAt->copy()->addMinutes(30),
                'created_at' => $fundedAt,
                'updated_at' => $fundedAt,
            ]);
            if ((int) $deposit->user_id !== $customer->id || $deposit->provider !== 'local-demo' || $deposit->amount !== 5000000) {
                throw new \RuntimeException('Mã nạp mẫu đã được dùng cho dữ liệu khác.');
            }
            $funding = app(WalletService::class)->change(
                $customer, 5000000, 'demo:funding:khach', 'deposit',
                'Nạp tiền DEMO — dữ liệu minh họa, không có chuyển khoản thật',
                ['deposit_id' => $deposit->id, 'actor_id' => $admin->id],
            );
            $funding->forceFill(['created_at' => $fundedAt, 'updated_at' => $fundedAt])->save();

            $examples = [
                ['elden-ring', 1, 2, 'e69bef48-d3b0-4e6b-b601-000000000001'],
                ['cyberpunk-2077', 1, 1, 'e69bef48-d3b0-4e6b-b601-000000000002'],
                ['hollow-knight', 2, 0, 'e69bef48-d3b0-4e6b-b601-000000000003'],
            ];
            foreach ($examples as [$slug, $quantity, $daysAgo, $token]) {
                if (Order::where('checkout_token', $token)->exists()) continue;
                $product = $products->firstWhere('slug', $slug);
                if (! $product) continue;
                $order = app(CheckoutService::class)->checkout($customer, [$product->id => $quantity], $token);
                $purchasedAt = now()->subDays($daysAgo);
                $order->forceFill(['created_at' => $purchasedAt, 'updated_at' => $purchasedAt, 'paid_at' => $purchasedAt, 'fulfilled_at' => $purchasedAt])->save();
                foreach ($order->items as $item) {
                    $item->forceFill(['created_at' => $purchasedAt, 'updated_at' => $purchasedAt])->save();
                    $item->keys()->update(['sold_at' => $purchasedAt, 'updated_at' => $purchasedAt]);
                }
                WalletTransaction::where('order_id', $order->id)->update(['created_at' => $purchasedAt, 'updated_at' => $purchasedAt]);
            }

            Coupon::firstOrCreate(['code' => 'DEMO10'], [
                'type' => 'percent', 'value' => 10, 'minimum_amount' => 100000,
                'maximum_discount' => 100000, 'usage_limit' => 100, 'per_user_limit' => 5,
                'starts_at' => now()->subDay(), 'expires_at' => now()->addDays(30),
            ]);

            DB::table('support_tickets')->updateOrInsert(
                ['user_id' => $customer->id, 'subject' => '[DEMO] Hướng dẫn kích hoạt key'],
                ['order_id' => $customer->orders()->oldest()->value('id'),
                    'message' => 'Tôi muốn xem thử quy trình hỗ trợ khi nhận key game. Đây là yêu cầu minh họa.',
                    'status' => 'closed',
                    'admin_reply' => 'Chào bạn! Key có tiền tố DEMO chỉ dùng để trải nghiệm website, không kích hoạt được game thật. Bạn có thể xem và sao chép key trong chi tiết đơn hàng.',
                    'created_at' => now()->subDay(), 'updated_at' => now()],
            );

            AuditLog::firstOrCreate(
                ['action' => 'demo.seeded', 'subject_type' => User::class, 'subject_id' => $customer->id],
                ['user_id' => $admin->id, 'metadata' => ['sample_products' => $products->count(), 'note' => 'Dữ liệu trải nghiệm local']],
            );
        });

        $this->command?->info('Đã tạo dữ liệu DEMO, 2 tài khoản, kho key thử, 3 đơn hàng, ví, coupon và ticket hỗ trợ.');
    }

    private function account(string $email, string $name, string $role): User
    {
        $user = User::firstOrNew(['email' => $email]);
        $user->forceFill([
            'name' => $name, 'password' => '123456', 'role' => $role,
            'is_blocked' => false, 'email_verified_at' => now(),
        ])->save();
        $user->wallet()->firstOrCreate([]);

        return $user;
    }
}
