<?php

namespace Tests\Feature;

use App\Models\{User, Category, Product, ProductKey, Order, Coupon, WalletTransaction};
use App\Services\{CheckoutService, KeyImportService, WalletService};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{DB, Hash, Notification};
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ShopFlowTest extends TestCase
{
    use RefreshDatabase;

    private function buyer(int $balance = 500000): User
    {
        $user = User::factory()->create();
        $user->wallet()->create();
        if ($balance) app(WalletService::class)->change($user, $balance, 'seed:'.$user->id, 'adjustment', 'Test funding');
        return $user;
    }
    private function product(int $stock = 2): Product
    {
        $category = Category::firstOrCreate(['slug' => 'steam'], ['name' => 'Steam']);
        $product = Product::create(['category_id' => $category->id, 'name' => 'Test Game', 'slug' => 'test-'.Str::random(6), 'sku' => Str::random(8), 'price' => 100000]);
        for ($i = 0; $i < $stock; $i++) {
            $secret = 'TEST-'.Str::uuid();
            ProductKey::create(['product_id' => $product->id, 'secret' => $secret, 'fingerprint' => ProductKey::fingerprint($secret)]);
        }
        return $product;
    }

    public function test_registration_creates_zero_wallet_without_role_injection(): void
    {
        $response = $this->post('/dang-ky', ['name' => 'Khách mới', 'email' => 'buyer@example.test', 'password' => 'SecurePassword123', 'password_confirmation' => 'SecurePassword123', 'role' => 'admin']);
        $response->assertRedirect(route('account'));
        $user = User::where('email', 'buyer@example.test')->firstOrFail();
        $this->assertSame('customer', $user->role);
        $this->assertSame(0, $user->wallet->balance);
    }

    public function test_wallet_checkout_encrypts_and_delivers_once_on_retry(): void
    {
        $user = $this->buyer(); $product = $this->product(); $token = (string) Str::uuid();
        $order = app(CheckoutService::class)->checkout($user, [$product->id => 2], $token);
        $retry = app(CheckoutService::class)->checkout($user, [], $token);
        $this->assertSame($order->id, $retry->id);
        $this->assertSame(300000, $user->wallet()->first()->balance);
        $this->assertSame(2, $order->items->first()->keys()->count());
        $this->assertSame(1, WalletTransaction::where('type', 'purchase')->count());
        $key = $product->keys()->first();
        $this->assertNotSame($key->secret, DB::table('product_keys')->where('id', $key->id)->value('secret'));
        $this->assertArrayNotHasKey('secret', $key->toArray());
        $this->actingAs($user)->get(route('orders.show', $order))->assertOk()->assertSee($key->secret)->assertHeader('Cache-Control', 'max-age=0, no-store, private');
    }

    public function test_insufficient_funds_rolls_back_entire_purchase(): void
    {
        $user = $this->buyer(50000); $product = $this->product();
        try { app(CheckoutService::class)->checkout($user, [$product->id => 1], (string) Str::uuid()); $this->fail('Expected insufficient funds'); }
        catch (ValidationException) {}
        $this->assertSame(0, Order::count());
        $this->assertSame(2, $product->availableKeys()->count());
        $this->assertSame(50000, $user->wallet()->first()->balance);
    }

    public function test_insufficient_stock_does_not_charge_wallet(): void
    {
        $user = $this->buyer(); $product = $this->product(0);
        try { app(CheckoutService::class)->checkout($user, [$product->id => 1], (string) Str::uuid()); $this->fail('Expected out of stock'); }
        catch (ValidationException) {}
        $this->assertSame(0, Order::count());
        $this->assertSame(500000, $user->wallet()->first()->balance);
    }

    public function test_cart_update_and_checkout_http_flow(): void
    {
        $user = $this->buyer(); $product = $this->product();
        $this->actingAs($user)->post(route('cart.add', $product), ['quantity' => 1])->assertSessionHasNoErrors();
        $this->patch(route('cart.update', $product), ['quantity' => 2])->assertSessionHasNoErrors();
        $this->get('/gio-hang')->assertOk()->assertSee('200.000');
        $this->post('/thanh-toan', ['checkout_token' => session('checkout_token')])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame(300000, $user->wallet()->first()->balance);
        $this->assertSame(1, Order::count());
    }

    public function test_coupon_limit_and_discount_are_enforced(): void
    {
        $user = $this->buyer(); $product = $this->product(3);
        Coupon::create(['code' => 'SALE20', 'type' => 'percent', 'value' => 20, 'usage_limit' => 1, 'minimum_amount' => 100000, 'per_user_limit' => 1]);
        $order = app(CheckoutService::class)->checkout($user, [$product->id => 1], (string) Str::uuid(), 'sale20');
        $this->assertSame(80000, (int) $order->total);
        try { app(CheckoutService::class)->checkout($user, [$product->id => 1], (string) Str::uuid(), 'SALE20'); $this->fail('Expected coupon limit'); }
        catch (ValidationException) {}
        $this->assertSame(1, Order::count());
        $this->assertSame(1, DB::table('coupon_usages')->count());
    }

    public function test_other_customer_cannot_read_order_or_reuse_token(): void
    {
        $user = $this->buyer(); $other = $this->buyer(); $product = $this->product();
        $order = app(CheckoutService::class)->checkout($user, [$product->id => 1], (string) Str::uuid());
        $this->actingAs($other)->get(route('orders.show', $order))->assertForbidden();
        $this->post('/thanh-toan', ['checkout_token' => $order->checkout_token])->assertForbidden();
    }

    public function test_customer_and_staff_cannot_adjust_wallet(): void
    {
        $customer = $this->buyer();
        $this->actingAs($customer, 'web')->get('/admin')->assertRedirect(route('admin.login'));
        $staff = $this->buyer(); $staff->forceFill(['role' => 'staff'])->save();
        $this->actingAs($staff, 'admin')->get('/admin')->assertOk();
        $this->post(route('admin.users.wallet', $customer), ['amount' => 100000, 'reason' => 'Unauthorized test', 'reference' => Str::uuid()])->assertForbidden();
        $this->assertSame(500000, $customer->wallet()->first()->balance);
    }

    public function test_blocked_account_cannot_login_or_checkout(): void
    {
        $user = $this->buyer(); $user->forceFill(['is_blocked' => true, 'password' => Hash::make('TestPassword123')])->save();
        $this->post('/dang-nhap', ['email' => $user->email, 'password' => 'TestPassword123'])->assertSessionHasErrors('email');
        $this->actingAs($user)->post('/thanh-toan', ['checkout_token' => Str::uuid()])->assertForbidden();
    }

    public function test_import_rejects_duplicate_key_without_partial_inserts(): void
    {
        $user = $this->buyer(); $product = $this->product(0);
        app(KeyImportService::class)->import($product, ['AAA-BBB-CCC'], $user);
        try { app(KeyImportService::class)->import($product, ['NEW-KEY', 'aaa-bbb-ccc'], $user); $this->fail('Expected duplicate'); }
        catch (ValidationException) {}
        $this->assertSame(1, ProductKey::count());
    }

    public function test_key_import_validation_does_not_flash_plaintext_to_session(): void
    {
        $admin = $this->buyer(); $admin->forceFill(['role' => 'admin'])->save();
        $response = $this->actingAs($admin, 'admin')->post('/admin/keys', ['product_id' => 999999, 'keys' => 'PRIVATE-UNSOLD-SECRET']);
        $response->assertSessionHasErrors('product_id');
        $this->assertArrayNotHasKey('keys', session('_old_input', []));
    }

    public function test_admin_can_create_edit_product_and_import_csv(): void
    {
        $admin = $this->buyer(); $admin->forceFill(['role' => 'admin'])->save();
        $category = Category::create(['name' => 'Steam', 'slug' => 'steam']);
        $data = ['name' => 'New Game', 'sku' => 'SKU-NEW', 'slug' => 'new-game', 'category_id' => $category->id, 'platform' => 'Steam', 'region' => 'Global', 'price' => 200000, 'sort_order' => 0, 'is_active' => 1];
        $this->actingAs($admin, 'admin')->post('/admin/products', $data)->assertSessionHasNoErrors()->assertRedirect(route('admin.products'));
        $product = Product::where('slug', 'new-game')->firstOrFail();
        $this->get(route('admin.products.edit', $product))->assertOk();
        $this->put(route('admin.products.update', $product), [...$data, 'price' => 150000])->assertSessionHasNoErrors();
        $file = \Illuminate\Http\UploadedFile::fake()->createWithContent('keys.csv', "CSV-KEY-ONE\nCSV-KEY-TWO");
        $this->post('/admin/keys', ['product_id' => $product->id, 'file' => $file])->assertSessionHasNoErrors();
        $this->assertSame(2, $product->availableKeys()->count());
        $this->assertSame(150000, $product->fresh()->price);
    }

    public function test_admin_refund_is_idempotent_and_keeps_key_sold(): void
    {
        $customer = $this->buyer(); $admin = $this->buyer(0); $admin->forceFill(['role' => 'admin'])->save();
        $product = $this->product(1);
        $order = app(CheckoutService::class)->checkout($customer, [$product->id => 1], (string) Str::uuid());
        foreach (range(1, 2) as $_) $this->actingAs($admin, 'admin')->post(route('admin.orders.refund', $order), ['reason' => 'Hoàn tiền kiểm thử'])->assertSessionHasNoErrors();
        $this->assertSame(500000, $customer->wallet()->first()->balance);
        $this->assertSame(1, WalletTransaction::where('type', 'refund')->count());
        $this->assertSame('sold', $product->keys()->first()->status);
    }

    public function test_same_adjustment_reference_cannot_be_used_with_another_amount(): void
    {
        $user = $this->buyer();
        app(WalletService::class)->change($user, 5000, 'adjust:unique', 'adjustment', 'Test adjustment');
        $this->expectException(ValidationException::class);
        app(WalletService::class)->change($user, 10000, 'adjust:unique', 'adjustment', 'Conflict adjustment');
    }

    public function test_public_catalog_and_admin_pages_render(): void
    {
        $this->seed();
        foreach (['/', '/san-pham', '/san-pham?sort=bestselling', '/san-pham/elden-ring', '/gio-hang', '/trang/faq', '/dang-nhap', '/dang-ky', '/quen-mat-khau'] as $url) $this->get($url)->assertOk();
        $admin = $this->buyer(); $admin->forceFill(['role' => 'admin'])->save();
        $customer = $this->buyer();
        foreach (['/tai-khoan', '/don-hang', '/vi', '/ho-tro'] as $url) $this->actingAs($customer, 'web')->get($url)->assertOk();
        foreach (['/admin', '/admin/products', '/admin/products/create', '/admin/keys', '/admin/orders', '/admin/users', '/admin/finance', '/admin/coupons', '/admin/support'] as $url) $this->actingAs($admin, 'admin')->get($url)->assertOk();
    }

    public function test_password_reset_link_is_sent_through_notification(): void
    {
        Notification::fake();
        $user = $this->buyer();
        $this->post('/quen-mat-khau', ['email' => $user->email])->assertSessionHasNoErrors();
        Notification::assertSentTo($user, \Illuminate\Auth\Notifications\ResetPassword::class);
    }
}
