<?php

namespace App\Console\Commands;

use App\Services\DemoExportService;
use Illuminate\Console\Command;

class ExportDemo extends Command
{
    protected $signature='shop:export-demo {directory : Thư mục mới lưu SQL và cấu hình Hostinger}';
    protected $description='Xuất snapshot demo MySQL, đổi mật khẩu và mã hóa lại kho key bằng APP_KEY riêng';
    public function handle(DemoExportService $export): int
    {
        try { $counts=$export->export($this->argument('directory')); }
        catch(\Throwable $error){$this->error($error->getMessage());return self::FAILURE;}
        $this->info('Đã xuất '.count($counts).' bảng. Xem ACCESS.txt riêng để lấy thông tin đăng nhập.');
        $this->line('Dữ liệu local được giữ nguyên; sessions, cache, reset token và job không được mang lên hosting.');
        return self::SUCCESS;
    }
}
