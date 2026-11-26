<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('banners', function (Blueprint $table) {
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('button_text', 80)->default('Xem sản phẩm');
        });
        Schema::create('wishlists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'product_id']);
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('wishlists');
        Schema::table('banners', function (Blueprint $table) {
            $table->dropConstrainedForeignId('product_id');
            $table->dropColumn('button_text');
        });
    }
};
