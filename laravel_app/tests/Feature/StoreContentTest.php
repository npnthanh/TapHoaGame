<?php

namespace Tests\Feature;

use App\Models\{Banner, Product, ProductKey, Review, Setting, User};
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StoreContentTest extends TestCase
{
    use RefreshDatabase;
    private function admin(): User
    {
        $user=User::factory()->create(); $user->forceFill(['role'=>'admin'])->save(); return $user;
    }
    public function test_home_banner_prices_and_ratings_are_database_driven(): void
    {
        $this->seed();
        $product=Product::first(); $product->update(['price'=>123000]);
        Banner::query()->update(['is_active'=>false]);
        Banner::create(['title'=>'Banner SQL trực tiếp','description'=>'Nội dung quản trị','product_id'=>$product->id,'button_text'=>'Khám phá SQL']);
        $this->get('/')->assertOk()->assertSee('Banner SQL trực tiếp')->assertSee('123.000')->assertDontSee('4.8')->assertDontSee('1,250,000')->assertDontSee('PHANTOM LIBERTY');
    }
    public function test_admin_can_save_content_and_staff_cannot_change_shop_settings(): void
    {
        $admin=$this->admin();
        $data=Setting::storefront();$data['brand_name']='Tên shop trong MySQL';
        $this->actingAs($admin,'admin')->post('/admin/content/settings',$data)->assertSessionHasNoErrors();
        $this->get('/')->assertSee('Tên shop trong MySQL');
        $staff=User::factory()->create();$staff->forceFill(['role'=>'staff'])->save();
        $this->actingAs($staff,'admin')->post('/admin/content/settings',$data)->assertForbidden();
    }
    public function test_content_routes_save_banner_category_and_plain_text_page(): void
    {
        $this->seed();$admin=$this->admin();
        $this->actingAs($admin,'admin')->get('/admin/content')->assertOk();
        $this->post('/admin/content/banners',['title'=>'Banner mới','product_id'=>Product::first()->id,'button_text'=>'Xem game','image_url'=>'/assets/covers/elden_ring.jpg','sort_order'=>1,'is_active'=>1])->assertSessionHasNoErrors();
        $this->post('/admin/content/pages',['title'=>'Trang SQL','slug'=>'trang-sql','body'=>'<script>alert(1)</script> Nội dung SQL','is_published'=>1])->assertSessionHasNoErrors();
        $this->get('/trang/trang-sql')->assertOk()->assertSee('&lt;script&gt;',false)->assertDontSee('<script>alert(1)</script>',false);
    }
    public function test_wishlist_is_owned_by_customer_and_does_not_duplicate(): void
    {
        $this->seed();$user=User::factory()->create();$product=Product::first();
        $this->actingAs($user,'web')->post(route('wishlist.add',$product))->assertSessionHasNoErrors();
        $this->post(route('wishlist.add',$product))->assertSessionHasNoErrors();
        $this->assertSame(1,DB::table('wishlists')->count());
        $this->get('/yeu-thich')->assertOk()->assertSee($product->name);
        $other=User::factory()->create();$this->actingAs($other,'web')->delete(route('wishlist.remove',$product));
        $this->assertSame(1,DB::table('wishlists')->count());
        $this->actingAs($user,'web')->delete(route('wishlist.remove',$product));
        $this->assertSame(0,DB::table('wishlists')->count());
    }
    public function test_review_requires_purchase_and_moderation(): void
    {
        $this->seed(DemoSeeder::class);
        $customer=User::where('email','khach@gmail.com')->firstOrFail();$admin=User::where('email','admin@gmail.com')->firstOrFail();
        $product=Product::where('slug','elden-ring')->firstOrFail();
        $stranger=User::factory()->create();
        $this->actingAs($stranger,'web')->post(route('reviews.store',$product),['rating'=>5,'comment'=>'Chưa mua'])->assertForbidden();
        $this->withSession(['password_hash_web'=>null])->actingAs($customer,'web')->post(route('reviews.store',$product),['rating'=>4,'comment'=>'Đánh giá mua hàng thật trong demo'])->assertSessionHasNoErrors();
        $review=Review::firstOrFail();$this->assertFalse($review->is_approved);
        $this->get(route('product',$product))->assertDontSee('Đánh giá mua hàng thật trong demo');
        $this->actingAs($admin,'admin')->patch(route('admin.content.reviews',$review),['is_approved'=>1])->assertSessionHasNoErrors();
        $this->get(route('product',$product))->assertSee('Đánh giá mua hàng thật trong demo');
    }
    public function test_reports_use_transactions_and_do_not_export_key_secrets(): void
    {
        $this->seed(DemoSeeder::class);$admin=User::where('role','admin')->first();
        $this->actingAs($admin,'admin')->get('/admin/reports')->assertOk()->assertSee('1.380.000');
        $this->get('/admin/exports/orders')->assertDownload('orders.csv');
        $stock=$this->get('/admin/exports/stock');$stock->assertDownload('inventory.csv');
        $this->assertStringNotContainsString('DEMO-NOT-ACTIVATABLE',$stock->streamedContent());
        $this->actingAs(User::where('role','customer')->first(),'web');
        \Illuminate\Support\Facades\Auth::guard('admin')->logout();
        $this->get('/admin/exports/orders')->assertRedirect(route('admin.login'));
    }
    public function test_sold_keys_cannot_be_returned_to_stock_and_coupon_edit_works(): void
    {
        $this->seed(DemoSeeder::class);$admin=User::where('role','admin')->first();$coupon=\App\Models\Coupon::first();
        $this->actingAs($admin,'admin')->patch(route('admin.keys.status',ProductKey::where('status','sold')->first()),['status'=>'available','reason'=>'Không được bán lại'])->assertStatus(409);
        $key=ProductKey::where('status','available')->first();
        $this->patch(route('admin.keys.status',$key),['status'=>'invalid','reason'=>'Kiểm tra lỗi key'])->assertSessionHasNoErrors();
        $this->assertSame('invalid',$key->fresh()->status);
        $this->get(route('admin.coupons.edit',$coupon))->assertOk();
        $this->put(route('admin.coupons.update',$coupon),['type'=>'percent','value'=>20,'minimum_amount'=>0,'per_user_limit'=>1,'is_active'=>0])->assertSessionHasNoErrors();
        $this->assertFalse($coupon->fresh()->is_active);
        $this->get(route('admin.users.show',User::where('role','customer')->first()))->assertOk();
    }
    public function test_demo_has_robots_rules_and_sitemap_from_published_products(): void
    {
        $this->seed();config(['shop.demo_mode'=>true]);
        $this->get('/robots.txt')->assertOk()->assertSee('Disallow: /');
        $this->get('/sitemap.xml')->assertOk()->assertHeader('Content-Type','application/xml; charset=UTF-8')->assertSee('/san-pham/elden-ring');
        Product::where('slug','elden-ring')->update(['is_active'=>false]);
        $this->get('/sitemap.xml')->assertDontSee('/san-pham/elden-ring');
    }

    public function test_deployment_check_command_runs_without_exposing_secrets(): void
    {
        $this->seed();$this->admin();config(['shop.demo_mode'=>true,'shop.payments.enabled'=>false]);
        $this->artisan('shop:check',['--demo'=>true])->assertSuccessful();
    }
}
