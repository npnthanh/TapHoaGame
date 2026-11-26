<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $primaryKey = 'key';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['key', 'value'];
    protected function casts(): array { return ['value' => 'json']; }

    public static function storefront(): array
    {
        return array_replace([
            'brand_name' => config('app.name'), 'tagline' => 'Khám phá game và quản lý key trong tài khoản.',
            'seo_title' => 'Cửa hàng key game', 'seo_description' => 'Tìm game theo nền tảng, khu vực và giá bán.',
            'support_email' => '', 'support_url' => '', 'support_hours' => '', 'logo_url' => '',
            'favicon_url' => '/assets/favicon.svg',
            'benefits' => "Nhận key trong tài khoản|Key được giao sau khi thanh toán thành công\nTheo dõi giao dịch|Lịch sử mua hàng và biến động số dư\nHướng dẫn kích hoạt|Kiểm tra nền tảng và khu vực trước khi mua\nHỗ trợ đơn hàng|Gửi yêu cầu kèm mã đơn nếu cần trợ giúp",
        ], static::all()->pluck('value', 'key')->all());
    }
}
