<?php

namespace Tests\Feature;

use App\Models\{Deposit, Order, Product, ProductKey, User, WalletTransaction};
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_can_be_seeded_again_without_duplicate_money_orders_or_keys(): void
    {
        $this->seed(DemoSeeder::class);
        $this->seed(DemoSeeder::class);

        $admin = User::where('email', 'admin@gmail.com')->firstOrFail();
        $customer = User::where('email', 'khach@gmail.com')->firstOrFail();
        $this->assertTrue(Hash::check('123456', $admin->password));
        $this->assertTrue(Hash::check('123456', $customer->password));
        $this->assertSame('admin', $admin->role);
        $this->assertSame('customer', $customer->role);
        $this->assertSame(8, Product::count());
        $this->assertSame(80, ProductKey::count());
        $this->assertSame(76, ProductKey::where('status', 'available')->count());
        $this->assertSame(3, Order::count());
        $this->assertSame(1, Deposit::count());
        $this->assertSame(4, WalletTransaction::count());
        $this->assertSame(3620000, $customer->wallet->balance);

        $this->post('/admin/login', ['email' => $admin->email, 'password' => '123456'])
            ->assertRedirect(route('admin.dashboard'));
        $this->get('/admin')->assertOk()->assertSee('Quản trị');
        $this->post('/admin/logout');
        $this->post('/dang-nhap', ['email' => $customer->email, 'password' => '123456'])
            ->assertRedirect(route('account'));
        $this->get('/admin')->assertRedirect(route('admin.login'));
        $this->get('/don-hang')->assertOk();
    }

    public function test_demo_seeder_refuses_production(): void
    {
        $this->app->detectEnvironment(fn () => 'production');
        try {
            app(DemoSeeder::class)->run();
            $this->fail('Production must not accept demo credentials.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('local', $exception->getMessage());
        }
        $this->assertSame(0, User::count());
    }
}
