<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $guarded = ['id'];
    protected function casts(): array { return ['paid_at' => 'datetime', 'fulfilled_at' => 'datetime']; }
    public function getRouteKeyName(): string { return 'number'; }
    public function user() { return $this->belongsTo(User::class); }
    public function items() { return $this->hasMany(OrderItem::class); }
}
