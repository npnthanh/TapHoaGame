<?php

use App\Http\Controllers\{StoreController, AuthController, AdminAuthController, CartController, AccountController, AdminController, PaymentWebhookController};
use Illuminate\Support\Facades\Route;

Route::get('/', [StoreController::class, 'home'])->name('home');
Route::get('/sitemap.xml', [StoreController::class, 'sitemap'])->name('sitemap');
Route::get('/robots.txt', [StoreController::class, 'robots']);
Route::get('/san-pham', [StoreController::class, 'products'])->name('products');
Route::get('/san-pham/{product}', [StoreController::class, 'product'])->name('product');
Route::get('/trang/{slug}', [StoreController::class, 'page'])->name('page');
Route::get('/gio-hang', [CartController::class, 'index'])->name('cart');
Route::post('/gio-hang/{product}', [CartController::class, 'add'])->middleware('throttle:60,1')->name('cart.add');
Route::patch('/gio-hang/{product}', [CartController::class, 'update'])->name('cart.update');
Route::delete('/gio-hang', [CartController::class, 'clear'])->name('cart.clear');
Route::post('/payments/webhook', PaymentWebhookController::class)->middleware('throttle:120,1')->name('payments.webhook');
Route::middleware('guest:admin')->group(function () {
    Route::get('/admin/login', [AdminAuthController::class, 'loginForm'])->name('admin.login');
    Route::post('/admin/login', [AdminAuthController::class, 'login'])->middleware('throttle:login');
});
Route::post('/admin/logout', [AdminAuthController::class, 'logout'])->middleware('admin.auth')->name('admin.logout');
Route::middleware('guest:web')->group(function () {
    Route::get('/dang-nhap', [AuthController::class, 'loginForm'])->name('login');
    Route::post('/dang-nhap', [AuthController::class, 'login'])->middleware('throttle:login');
    Route::get('/dang-ky', [AuthController::class, 'registerForm'])->name('register');
    Route::post('/dang-ky', [AuthController::class, 'register'])->middleware('throttle:5,1');
    Route::get('/quen-mat-khau', [AuthController::class, 'forgotForm'])->name('password.request');
    Route::post('/quen-mat-khau', [AuthController::class, 'forgot'])->middleware('throttle:5,1')->name('password.email');
    Route::get('/dat-lai-mat-khau/{token}', [AuthController::class, 'resetForm'])->name('password.reset');
    Route::post('/dat-lai-mat-khau', [AuthController::class, 'reset'])->middleware('throttle:5,1')->name('password.update');
});
Route::post('/dang-xuat', [AuthController::class, 'logout'])->middleware('auth:web')->name('logout');
Route::middleware(['auth:web', 'auth.session', 'active'])->group(function () {
    Route::get('/yeu-thich', [\App\Http\Controllers\CustomerContentController::class, 'wishlist'])->name('wishlist');
    Route::post('/yeu-thich/{product}', [\App\Http\Controllers\CustomerContentController::class, 'addWishlist'])->name('wishlist.add');
    Route::delete('/yeu-thich/{product}', [\App\Http\Controllers\CustomerContentController::class, 'removeWishlist'])->name('wishlist.remove');
    Route::post('/san-pham/{product}/danh-gia', [\App\Http\Controllers\CustomerContentController::class, 'review'])->middleware('throttle:5,1')->name('reviews.store');
    Route::post('/thanh-toan', [CartController::class, 'checkout'])->middleware('throttle:10,1')->name('checkout');
    Route::get('/tai-khoan', [AccountController::class, 'index'])->name('account');
    Route::patch('/tai-khoan', [AccountController::class, 'profile'])->name('profile.update');
    Route::put('/tai-khoan/mat-khau', [AccountController::class, 'password'])->middleware('throttle:5,1')->name('profile.password');
    Route::get('/don-hang', [AccountController::class, 'orders'])->name('orders');
    Route::get('/don-hang/{order}', [AccountController::class, 'order'])->name('orders.show');
    Route::get('/vi', [AccountController::class, 'wallet'])->name('wallet');
    Route::post('/vi/nap-tien', [AccountController::class, 'deposit'])->middleware('throttle:5,1')->name('deposits.store');
    Route::get('/ho-tro', [AccountController::class, 'support'])->name('support');
    Route::post('/ho-tro', [AccountController::class, 'ticket'])->middleware('throttle:5,1')->name('support.store');
});

Route::prefix('admin')->name('admin.')->middleware(['admin.auth', 'auth.session', 'active', 'can:manage-shop'])->group(function () {
    Route::get('/reports', [\App\Http\Controllers\AdminOperationsController::class,'reports'])->name('reports');
    Route::get('/exports/orders', [\App\Http\Controllers\AdminOperationsController::class,'exportOrders'])->name('exports.orders');
    Route::get('/exports/stock', [\App\Http\Controllers\AdminOperationsController::class,'exportStock'])->name('exports.stock');
    Route::patch('/keys/{key}/status', [\App\Http\Controllers\AdminOperationsController::class,'key'])->name('keys.status');
    Route::get('/coupons/{coupon}/edit', [\App\Http\Controllers\AdminOperationsController::class,'coupon'])->name('coupons.edit');
    Route::put('/coupons/{coupon}', [\App\Http\Controllers\AdminOperationsController::class,'updateCoupon'])->name('coupons.update');
    Route::get('/users/{user}', [\App\Http\Controllers\AdminOperationsController::class,'user'])->name('users.show');
    Route::put('/users/{user}', [\App\Http\Controllers\AdminOperationsController::class,'updateUser'])->name('users.update');
    Route::get('/content', [\App\Http\Controllers\AdminContentController::class, 'index'])->name('content');
    Route::post('/content/settings', [\App\Http\Controllers\AdminContentController::class, 'settings'])->name('content.settings');
    Route::post('/content/banners', [\App\Http\Controllers\AdminContentController::class, 'banner'])->name('content.banners.store');
    Route::put('/content/banners/{banner}', [\App\Http\Controllers\AdminContentController::class, 'banner'])->name('content.banners.update');
    Route::delete('/content/banners/{banner}', [\App\Http\Controllers\AdminContentController::class, 'deleteBanner'])->name('content.banners.delete');
    Route::post('/content/pages', [\App\Http\Controllers\AdminContentController::class, 'page'])->name('content.pages.store');
    Route::put('/content/pages/{page}', [\App\Http\Controllers\AdminContentController::class, 'page'])->name('content.pages.update');
    Route::put('/content/categories/{category}', [\App\Http\Controllers\AdminContentController::class, 'category'])->name('content.categories');
    Route::patch('/content/reviews/{review}', [\App\Http\Controllers\AdminContentController::class, 'review'])->name('content.reviews');
        Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');
        Route::get('/products', [AdminController::class, 'products'])->name('products');
        Route::get('/products/create', [AdminController::class, 'createProduct'])->name('products.create');
        Route::post('/products', [AdminController::class, 'saveProduct'])->name('products.store');
        Route::get('/products/{product}/edit', [AdminController::class, 'editProduct'])->name('products.edit');
        Route::put('/products/{product}', [AdminController::class, 'saveProduct'])->name('products.update');
        Route::delete('/products/{product}', [AdminController::class, 'archiveProduct'])->name('products.archive');
        Route::post('/categories', [AdminController::class, 'category'])->name('categories.store');
        Route::get('/keys', [AdminController::class, 'keys'])->name('keys');
        Route::post('/keys', [AdminController::class, 'importKeys'])->middleware('throttle:10,1')->name('keys.import');
        Route::get('/orders', [AdminController::class, 'orders'])->name('orders');
        Route::get('/orders/{order}', [AdminController::class, 'order'])->name('orders.show');
        Route::post('/orders/{order}/refund', [AdminController::class, 'refund'])->name('orders.refund');
        Route::get('/users', [AdminController::class, 'users'])->name('users');
        Route::post('/users/{user}/wallet', [AdminController::class, 'adjustWallet'])->name('users.wallet');
        Route::patch('/users/{user}/status', [AdminController::class, 'block'])->name('users.block');
        Route::get('/finance', [AdminController::class, 'finance'])->name('finance');
        Route::get('/coupons', [AdminController::class, 'coupons'])->name('coupons');
        Route::post('/coupons', [AdminController::class, 'createCoupon'])->name('coupons.store');
        Route::get('/support', [AdminController::class, 'support'])->name('support');
        Route::patch('/support/{ticket}', [AdminController::class, 'reply'])->name('support.reply');
});
