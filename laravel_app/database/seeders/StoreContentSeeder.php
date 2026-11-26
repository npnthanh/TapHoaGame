<?php

namespace Database\Seeders;

use App\Models\{Banner, Product, Setting};
use Illuminate\Database\Seeder;

class StoreContentSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            'brand_name' => 'Tạp Hóa Game', 'tagline' => 'Khám phá tựa game tiếp theo và lưu trữ key trong tài khoản.',
            'seo_title' => 'Cửa hàng key game', 'seo_description' => 'Sản phẩm số, thông tin nền tảng, khu vực và giá bán rõ ràng.',
            'support_email' => '', 'support_url' => '', 'support_hours' => '', 'logo_url' => '',
            'favicon_url' => '/assets/favicon.svg',
        ];
        foreach ($defaults as $key => $value) Setting::firstOrCreate(['key' => $key], ['value' => $value]);
        Setting::firstOrCreate(['key' => 'benefits'], ['value' => Setting::storefront()['benefits']]);
        if (! \Illuminate\Support\Facades\DB::table('pages')->where('slug','gioi-thieu')->exists()) {
            \Illuminate\Support\Facades\DB::table('pages')->insert(['slug'=>'gioi-thieu','title'=>'Giới thiệu','body'=>"Tạp Hóa Game (TapHoaGame) là website trải nghiệm quy trình mua sản phẩm số, quản lý ví và giao key trong tài khoản.\n\nBản demo sử dụng hàng hóa và giao dịch minh họa, không tiếp nhận chuyển khoản thật. Chủ cửa hàng có thể cập nhật tên thương hiệu, thông tin liên hệ và nội dung tại trang quản trị.",'is_published'=>true,'created_at'=>now(),'updated_at'=>now()]);
        }
        \Illuminate\Support\Facades\DB::table('pages')->where('slug','chinh-sach')->where('body','Trang chính sách chưa được cửa hàng hoàn thiện. Chủ cửa hàng cần công bố điều kiện bảo hành, đổi trả, hoàn tiền và thông tin liên hệ trước khi mở bán thực tế.')->update(['body'=>"Điều kiện sử dụng bản demo\n\nWebsite hiện dùng dữ liệu minh họa. Không chuyển tiền thật và không sử dụng key DEMO để kích hoạt game. Số dư ví, đơn hàng và thao tác hoàn tiền chỉ phục vụ trải nghiệm.\n\nBạn có thể gửi yêu cầu hỗ trợ trong tài khoản để thử quy trình xử lý. Không nhập dữ liệu cá nhân nhạy cảm hoặc thông tin thanh toán thật vào bản demo.\n\nTrước khi mở bán, chủ cửa hàng cần thay nội dung này bằng chính sách bảo hành, đổi trả, hoàn tiền và thông tin liên hệ đã được xác nhận.",'updated_at'=>now()]);
        if (! Banner::exists()) {
            foreach (Product::where('is_active', true)->where('is_featured', true)->orderBy('sort_order')->limit(3)->get() as $index => $product) {
                Banner::create(['title' => $product->name, 'description' => "{$product->platform} · {$product->region}\nXem thông tin sản phẩm, tồn kho và hướng dẫn kích hoạt.", 'product_id' => $product->id, 'image_url' => $product->image_url, 'button_text' => 'Xem sản phẩm', 'sort_order' => $index]);
            }
        }
    }
}
