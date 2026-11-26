<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Product extends Model
{
    protected $fillable = ['category_id', 'name', 'slug', 'sku', 'platform', 'region', 'image_url', 'price', 'original_price', 'description', 'instructions', 'is_active', 'is_featured', 'sort_order', 'seo_title', 'seo_description'];
    protected function casts(): array { return ['price' => 'integer', 'original_price' => 'integer', 'is_active' => 'boolean', 'is_featured' => 'boolean']; }
    public function getRouteKeyName(): string { return 'slug'; }
    public function category() { return $this->belongsTo(Category::class); }
    public function keys() { return $this->hasMany(ProductKey::class); }
    public function availableKeys() { return $this->keys()->where('status', 'available'); }
    public function orderItems() { return $this->hasMany(OrderItem::class); }
    public function reviews() { return $this->hasMany(Review::class); }
    public function approvedReviews() { return $this->reviews()->where('is_approved', true); }
    public function scopeVisible(Builder $query): void { $query->where('is_active', true)->whereHas('category', fn ($q) => $q->where('is_active', true)); }
}
