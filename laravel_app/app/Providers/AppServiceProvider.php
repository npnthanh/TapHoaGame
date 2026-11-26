<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \Illuminate\Support\Facades\View::composer(['layouts.app', 'layouts.admin', 'store.home'], function ($view) {
            $view->with('shopContent', \App\Models\Setting::storefront());
            $view->with('wishlistCount', auth('web')->check()
                ? \Illuminate\Support\Facades\DB::table('wishlists')->where('user_id', auth('web')->id())->count() : 0);
        });
        \Illuminate\Support\Facades\Gate::define('manage-shop', fn ($user) => ! $user->is_blocked && in_array($user->role, ['admin', 'staff'], true));
        \Illuminate\Support\Facades\Gate::define('manage-finance', fn ($user) => ! $user->is_blocked && $user->role === 'admin');
        \Illuminate\Support\Facades\RateLimiter::for('login', fn ($request) => [
            \Illuminate\Cache\RateLimiting\Limit::perMinute(5)->by(mb_strtolower((string) $request->input('email')).'|'.$request->ip()),
            \Illuminate\Cache\RateLimiting\Limit::perMinute(30)->by($request->ip()),
        ]);
    }
}
