<?php

namespace App\Console\Commands;

use App\Models\{Banner, ProductKey, Setting, User};
use Illuminate\Console\Command;
use Illuminate\Support\Facades\{DB, Hash};

class CheckDeployment extends Command
{
    protected $signature='shop:check {--demo : Kiểm tra cấu hình demo, không xác nhận sẵn sàng nhận tiền thật}';
    protected $description='Kiểm tra cấu hình, dữ liệu và khả năng chạy trước khi mở website';
    public function handle(): int
    {
        $issues=[]; $demo=$this->option('demo');
        foreach(['pdo_mysql','mbstring','openssl','fileinfo','dom','xml','curl'] as $extension) if(!extension_loaded($extension))$issues[]='Thiếu extension '.$extension;
        try{
            DB::connection()->getPdo();
            if(!DB::table('migrations')->where('migration','2026_10_22_000001_add_store_content_features')->exists())$issues[]='Chưa chạy migration mới.';
            if(!User::where('role','admin')->where('is_blocked',false)->exists())$issues[]='Chưa có quản trị viên hoạt động.';
            foreach(User::whereIn('role',['admin','staff'])->get() as $user)if(Hash::check('123456',$user->password))$issues[]='Tài khoản quản trị còn dùng mật khẩu demo 123456.';
            $sampleKeys=0; foreach(ProductKey::cursor() as $key) { if(preg_match('/^(DEMO|TEST|SAMPLE|FAKE|XXXXX)/i',$key->secret))$sampleKeys++; }
            $this->line('Kho key đọc/giải mã được. Key nhận diện là mẫu: '.$sampleKeys);
            if(!$demo && $sampleKeys)$issues[]='Còn key mẫu; không được mở bán thật.';
            if(!Setting::where('key','brand_name')->exists())$issues[]='Chưa có cấu hình shop trong MySQL.';
            $this->line('Banner đang hoạt động: '.Banner::where('is_active',true)->count());
        }catch(\Throwable $e){$issues[]='Lỗi kết nối/schema/mã hóa: '.$e->getMessage();}
        if(!$demo && config('shop.demo_mode'))$issues[]='Chế độ demo đang bật.';
        if($demo && !config('shop.demo_mode'))$issues[]='Bản demo cần SHOP_DEMO_MODE=true.';
        if($demo && config('shop.payments.enabled'))$issues[]='Demo phải tắt nhận thanh toán thật.';
        if(app()->environment('production')){
            if(config('app.debug'))$issues[]='Production phải APP_DEBUG=false.';
            if(!str_starts_with(config('app.url'),'https://'))$issues[]='Production cần URL HTTPS.';
            if(!config('session.secure'))$issues[]='Production cần cookie HTTPS.';
        }else $this->warn('Đang chạy local/testing; cần kiểm tra lại trên Hostinger sau khi cấu hình .env.');
        foreach([storage_path(),base_path('bootstrap/cache')] as $path)if(!is_writable($path))$issues[]='Không ghi được '.$path;
        if($issues){foreach($issues as $issue)$this->error($issue);return self::FAILURE;}
        $this->info($demo?'Cấu hình demo đạt kiểm tra cơ bản; thanh toán thật chưa được tích hợp.':'Kiểm tra kỹ thuật đạt. Cần xác nhận cổng thanh toán, hàng hóa và chính sách trước khi bán thật.');
        return self::SUCCESS;
    }
}
