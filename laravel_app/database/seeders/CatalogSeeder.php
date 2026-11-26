<?php

namespace Database\Seeders;

use App\Models\{Category, Product};
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        // Insert only: rerunning does not overwrite content edited by an operator.
        $categories = [];
        foreach (['game-steam' => 'Game Steam', 'game-pc' => 'Game PC', 'phan-mem' => 'Phần mềm', 'the-qua-tang' => 'Thẻ quà tặng'] as $slug => $name) {
            $categories[$slug] = Category::firstOrCreate(['slug' => $slug], ['name' => $name, 'sort_order' => count($categories)]);
        }
        if (app()->environment('local', 'testing')) {
            $samples = [
                ['elden-ring', 'ELDEN RING', 'game-steam', 690000, 990000, 'Steam', '/assets/covers/elden_ring.jpg'],
                ['cyberpunk-2077', 'Cyberpunk 2077', 'game-steam', 450000, 990000, 'Steam', '/assets/covers/cyberpunk_hero.jpg'],
                ['baldurs-gate-3', "Baldur's Gate 3", 'game-steam', 890000, 990000, 'Steam', '/assets/covers/baldurs_gate_3.jpg'],
                ['red-dead-redemption-2', 'Red Dead Redemption 2', 'game-pc', 390000, 1000000, 'Rockstar', '/assets/covers/rdr2.jpg'],
                ['hollow-knight', 'Hollow Knight', 'game-steam', 120000, 165000, 'Steam', null],
                ['stardew-valley', 'Stardew Valley', 'game-steam', 165000, 220000, 'Steam', null],
                ['windows-11-pro', 'Windows 11 Pro', 'phan-mem', 290000, 490000, 'Microsoft', null],
                ['steam-wallet-100', 'Steam Wallet 100.000₫', 'the-qua-tang', 100000, null, 'Steam', null],
            ];
            foreach ($samples as $index => [$slug, $name, $category, $price, $original, $platform, $imageUrl]) {
                Product::firstOrCreate(['slug' => $slug], [
                    'name' => $name, 'sku' => 'SAMPLE-'.str_pad($index + 1, 4, '0', STR_PAD_LEFT),
                    'category_id' => $categories[$category]->id, 'price' => $price, 'original_price' => $original,
                    'platform' => $platform, 'region' => 'Global', 'is_featured' => $index < 4, 'sort_order' => $index,
                    'image_url' => $imageUrl,
                    'description' => "Sản phẩm minh họa cho bản khởi tạo Tạp Hóa Game. Giá và khu vực chỉ là dữ liệu mẫu, chưa có key bán thật.\n\nQuản trị viên cần cập nhật mô tả, giá, khu vực kích hoạt và nguồn key trước khi mở bán.",
                    'instructions' => 'Hướng dẫn minh họa: đăng nhập nền tảng tương ứng, mở mục kích hoạt sản phẩm và nhập key đã nhận. Quản trị viên cần xác minh quy trình cho từng sản phẩm.',
                ]);
            }
        }
        $pages = [
            'huong-dan-mua-hang' => ['Hướng dẫn mua hàng', "1. Tạo tài khoản hoặc đăng nhập.\n2. Tìm sản phẩm, kiểm tra nền tảng và khu vực kích hoạt.\n3. Thêm sản phẩm vào giỏ và chọn số lượng.\n4. Kiểm tra số dư ví, nhập mã giảm giá nếu có và xác nhận mua.\n5. Mở Đơn hàng & key để xem và sao chép key đã nhận.\n\nNạp tiền: mở Ví & nạp tiền, tạo yêu cầu và chuyển đúng số tiền, đúng nội dung trong thời hạn được hiển thị. Chỉ chuyển khoản khi kênh thanh toán đã được cửa hàng công bố.\n\nNếu phát sinh lỗi, gửi yêu cầu hỗ trợ kèm mã đơn hàng."],
            'faq' => ['Câu hỏi thường gặp', "Tôi nhận key ở đâu?\nKey hiển thị trong Đơn hàng & key ngay sau khi thanh toán bằng ví thành công.\n\nTôi có xem lại key đã mua được không?\nBạn có thể mở lại chi tiết đơn hàng trong tài khoản của mình.\n\nNạp tiền chưa được cộng thì làm gì?\nKiểm tra đúng số tiền, nội dung và thời hạn yêu cầu. Liên hệ hỗ trợ kèm mã giao dịch; không chuyển lại nhiều lần.\n\nKey gặp lỗi phải làm gì?\nGửi yêu cầu hỗ trợ với mã đơn và thông báo lỗi kích hoạt. Không gửi mật khẩu tài khoản."],
            'chinh-sach' => ['Chính sách bảo hành & hoàn tiền', 'Trang chính sách chưa được cửa hàng hoàn thiện. Chủ cửa hàng cần công bố điều kiện bảo hành, đổi trả, hoàn tiền và thông tin liên hệ trước khi mở bán thực tế.'],
        ];
        foreach ($pages as $slug => [$title, $body]) {
            if (! DB::table('pages')->where('slug', $slug)->exists()) DB::table('pages')->insert(['slug' => $slug, 'title' => $title, 'body' => $body, 'is_published' => true, 'created_at' => now(), 'updated_at' => now()]);
        }
    }
}
