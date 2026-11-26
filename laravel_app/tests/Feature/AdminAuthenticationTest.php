<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role): User
    {
        $user = User::factory()->create(['password' => 'TestPassword123']);
        $user->forceFill(['role' => $role])->save();
        $user->wallet()->create();
        return $user;
    }

    private function signIn(User $user, string $path)
    {
        return $this->post($path, ['email' => $user->email, 'password' => 'TestPassword123']);
    }

    public function test_admin_and_customer_have_different_login_pages(): void
    {
        $this->get('/admin')->assertRedirect(route('admin.login'));
        $this->get('/admin/login')->assertOk()->assertSee('Đăng nhập quản trị')->assertDontSee('Đăng ký thành viên');
        $this->get('/tai-khoan')->assertRedirect(route('login'));
        $this->get('/dang-nhap')->assertOk()->assertSee('Đăng ký thành viên')->assertDontSee('Vào trang quản trị');
        $this->getJson('/admin')->assertUnauthorized();
    }

    public function test_customer_cannot_sign_in_through_admin_form(): void
    {
        $customer = $this->user('customer');
        $this->signIn($customer, '/admin/login')->assertSessionHasErrors('email');
        $this->assertGuest('admin');
        $this->assertGuest('web');
    }

    public function test_admin_and_staff_cannot_sign_in_through_customer_form(): void
    {
        foreach (['admin', 'staff'] as $role) {
            $user = $this->user($role);
            $this->signIn($user, '/dang-nhap')->assertSessionHasErrors('email');
            $this->assertGuest('web');
        }
    }

    public function test_admin_intended_url_does_not_change_customer_login_destination(): void
    {
        $customer = $this->user('customer'); $admin = $this->user('admin');
        $this->get('/admin/keys')->assertRedirect(route('admin.login'));
        $this->signIn($customer, '/dang-nhap')->assertRedirect(route('account'));
        $this->get('/admin/login')->assertOk();
        $this->signIn($admin, '/admin/login')->assertRedirect('/admin/keys');
        $this->assertAuthenticatedAs($customer, 'web');
        $this->assertAuthenticatedAs($admin, 'admin');
        $this->get('/admin')->assertOk()->assertSee($admin->name)->assertDontSee('Giỏ hàng');
        $this->get('/tai-khoan')->assertOk()->assertSee($customer->name);
    }

    public function test_admin_logout_preserves_customer_login(): void
    {
        $customer = $this->user('customer'); $admin = $this->user('admin');
        $this->signIn($customer, '/dang-nhap');
        $this->signIn($admin, '/admin/login');
        $this->post('/admin/logout')->assertRedirect(route('admin.login'));
        $this->assertGuest('admin');
        $this->assertAuthenticatedAs($customer, 'web');
        $this->get('/tai-khoan')->assertOk();
        $this->get('/admin')->assertRedirect(route('admin.login'));
    }

    public function test_customer_logout_preserves_admin_login(): void
    {
        $customer = $this->user('customer'); $admin = $this->user('admin');
        $this->signIn($admin, '/admin/login');
        $this->get('/dang-nhap')->assertOk();
        $this->signIn($customer, '/dang-nhap');
        $this->post('/dang-xuat')->assertRedirect(route('home'));
        $this->assertGuest('web');
        $this->assertAuthenticatedAs($admin, 'admin');
        $this->get('/admin')->assertOk();
        $this->get('/tai-khoan')->assertRedirect(route('login'));
    }

    public function test_blocked_admin_is_rejected_and_role_changes_are_enforced(): void
    {
        $admin = $this->user('admin');
        $admin->forceFill(['is_blocked' => true])->save();
        $this->signIn($admin, '/admin/login')->assertSessionHasErrors('email');
        $this->assertGuest('admin');
        $admin->forceFill(['is_blocked' => false, 'role' => 'customer'])->save();
        $this->actingAs($admin, 'admin')->get('/admin')->assertForbidden();
    }

    public function test_old_admin_intended_url_cannot_redirect_customer_back_to_admin(): void
    {
        $admin = $this->user('admin'); $customer = $this->user('customer');
        $this->signIn($admin, '/admin/login');
        $this->withSession(['url.intended' => url('/admin/orders')]);
        $this->get('/dang-nhap')->assertOk();
        $this->signIn($customer, '/dang-nhap')->assertRedirect(route('account'));
        $this->assertAuthenticatedAs($customer, 'web');
        $this->assertAuthenticatedAs($admin, 'admin');
    }

    public function test_legacy_admin_identity_in_web_guard_does_not_block_customer_login(): void
    {
        $admin = $this->user('admin');
        $this->actingAs($admin, 'admin');
        $this->actingAs($admin, 'web');
        $this->get('/dang-nhap')->assertOk();
        $this->assertGuest('web');
        $this->assertAuthenticatedAs($admin, 'admin');
        $this->get('/tai-khoan')->assertRedirect(route('login'));
    }

    public function test_customer_can_still_return_to_wallet_after_login(): void
    {
        $customer = $this->user('customer');
        $this->get('/vi')->assertRedirect(route('login'));
        $this->signIn($customer, '/dang-nhap')->assertRedirect('/vi');
    }

    public function test_legacy_admin_web_login_cannot_access_customer_account(): void
    {
        $admin = $this->user('admin');
        $this->actingAs($admin, 'web')->get('/tai-khoan')->assertRedirect(route('login'));
        $this->assertGuest('web');
    }

    public function test_switching_customer_account_does_not_reuse_old_password_hash_or_lose_admin(): void
    {
        $admin=$this->user('admin');$first=$this->user('customer');$second=$this->user('customer');
        $this->signIn($admin,'/admin/login');$this->get('/admin')->assertOk();
        $this->signIn($first,'/dang-nhap');$this->get('/tai-khoan')->assertOk();
        $this->post('/dang-xuat');
        $this->signIn($second,'/dang-nhap');$this->get('/tai-khoan')->assertOk();
        $this->assertAuthenticatedAs($second,'web');$this->assertAuthenticatedAs($admin,'admin');
    }

    public function test_admin_password_change_redirects_to_admin_login(): void
    {
        $admin=$this->user('admin');$this->signIn($admin,'/admin/login');$this->get('/admin')->assertOk();
        $admin->forceFill(['password'=>'DifferentPassword123'])->save();
        \Illuminate\Support\Facades\Auth::guard('admin')->forgetUser();
        $this->get('/admin')->assertRedirect(route('admin.login'));
    }
}
