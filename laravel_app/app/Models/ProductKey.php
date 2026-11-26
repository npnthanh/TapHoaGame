<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductKey extends Model
{
    protected $guarded = ['id'];
    protected $hidden = ['secret', 'fingerprint'];
    protected function casts(): array { return ['secret' => 'encrypted', 'sold_at' => 'datetime']; }
    public function product() { return $this->belongsTo(Product::class); }
    public function orderItem() { return $this->belongsTo(OrderItem::class); }
    public static function fingerprint(string $key): string { return hash('sha256', mb_strtoupper(trim($key))); }
}
