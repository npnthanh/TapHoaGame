<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->default('customer')->index();
            $table->boolean('is_blocked')->default(false);
            $table->string('phone', 30)->nullable();
            $table->text('internal_note')->nullable();
            $table->timestamp('last_login_at')->nullable();
        });
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('sku', 80)->unique();
            $table->string('platform', 60)->default('Steam');
            $table->string('region', 80)->default('Global');
            $table->string('image_url', 2048)->nullable();
            $table->unsignedBigInteger('price');
            $table->unsignedBigInteger('original_price')->nullable();
            $table->text('description')->nullable();
            $table->text('instructions')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('is_featured')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->timestamps();
        });
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('balance')->default(0);
            $table->timestamps();
        });
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('type', 20); // fixed or percent
            $table->unsignedBigInteger('value');
            $table->unsignedBigInteger('minimum_amount')->default(0);
            $table->unsignedBigInteger('maximum_discount')->nullable();
            $table->unsignedInteger('usage_limit')->nullable();
            $table->unsignedInteger('per_user_limit')->default(1);
            $table->unsignedInteger('used_count')->default(0);
            $table->foreignId('product_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->restrictOnDelete();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('number', 40)->unique();
            $table->uuid('checkout_token')->unique();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('coupon_id')->nullable()->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('subtotal');
            $table->unsignedBigInteger('discount')->default(0);
            $table->unsignedBigInteger('total');
            $table->string('status', 30)->default('pending')->index();
            $table->string('payment_method', 30)->default('wallet');
            $table->string('payment_status', 30)->default('pending');
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('fulfilled_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'created_at']);
        });
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->string('product_name');
            $table->unsignedInteger('quantity');
            $table->unsignedBigInteger('unit_price');
            $table->unsignedBigInteger('total');
            $table->timestamps();
        });
        Schema::create('product_keys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->text('secret'); // Laravel encrypted cast, never plaintext
            $table->char('fingerprint', 64)->unique();
            $table->string('status', 20)->default('available');
            $table->foreignId('order_item_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('imported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('sold_at')->nullable();
            $table->timestamps();
            $table->index(['product_id', 'status', 'id']);
        });
        Schema::create('deposits', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 40)->unique();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('amount');
            $table->string('currency', 3)->default('VND');
            $table->string('provider', 50);
            $table->string('provider_transaction_id', 100)->nullable();
            $table->string('status', 30)->default('pending')->index();
            $table->timestamp('expires_at');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            $table->unique(['provider', 'provider_transaction_id']);
        });
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained()->restrictOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('deposit_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reference', 100)->unique();
            $table->string('type', 30);
            $table->bigInteger('amount'); // signed VND; no floating point
            $table->unsignedBigInteger('balance_after');
            $table->string('description', 500);
            $table->timestamps();
        });
        Schema::create('payment_events', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 50);
            $table->string('event_id', 100);
            $table->char('payload_hash', 64);
            $table->foreignId('deposit_id')->constrained()->restrictOnDelete();
            $table->timestamps();
            $table->unique(['provider', 'event_id']);
        });
        Schema::create('coupon_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coupon_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('order_id')->unique()->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('discount');
            $table->timestamps();
        });
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 100)->index();
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->json('metadata')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
        });
        Schema::create('banners', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('image_url', 2048)->nullable();
            $table->string('link_url', 2048)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->longText('body');
            $table->boolean('is_published')->default(false);
            $table->timestamps();
        });
        Schema::create('settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->json('value')->nullable();
            $table->timestamps();
        });
        Schema::create('support_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('subject');
            $table->text('message');
            $table->string('status', 30)->default('open')->index();
            $table->text('admin_reply')->nullable();
            $table->timestamps();
        });
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('order_item_id')->unique()->constrained()->restrictOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->text('comment')->nullable();
            $table->boolean('is_approved')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['reviews', 'support_tickets', 'settings', 'pages', 'banners', 'audit_logs', 'coupon_usages', 'payment_events', 'wallet_transactions', 'deposits', 'product_keys', 'order_items', 'orders', 'coupons', 'wallets', 'products', 'categories'] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn(['role', 'is_blocked', 'phone', 'internal_note', 'last_login_at']));
    }
};
