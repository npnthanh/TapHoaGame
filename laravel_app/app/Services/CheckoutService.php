<?php

namespace App\Services;

use App\Models\{User, Product, Order, Coupon};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CheckoutService
{
    public function __construct(private WalletService $wallet) {}

    public function checkout(User $user, array $cart, string $token, ?string $code = null): Order
    {
        return DB::transaction(function () use ($user, $cart, $token, $code) {
            $buyer = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            abort_if($buyer->is_blocked, 403);
            if ($existing = Order::where('checkout_token', $token)->first()) {
                abort_unless($existing->user_id === $user->id, 403);
                return $existing;
            }
            if (! Str::isUuid($token) || ! count($cart) || count($cart) > 50) {
                throw ValidationException::withMessages(['cart' => 'Giỏ hàng không hợp lệ.']);
            }
            ksort($cart, SORT_NUMERIC);
            $subtotal = 0;
            $lines = [];
            foreach ($cart as $id => $quantity) {
                if (! is_numeric($id) || ! is_int($quantity) || $quantity < 1 || $quantity > 20) {
                    throw ValidationException::withMessages(['cart' => 'Số lượng mua từ 1 đến 20 mỗi sản phẩm.']);
                }
                $product = Product::visible()->whereKey($id)->lockForUpdate()->first();
                if (! $product) {
                    throw ValidationException::withMessages(['cart' => 'Sản phẩm đã ngừng bán. Vui lòng cập nhật giỏ hàng.']);
                }
                $keys = $product->availableKeys()->orderBy('id')->limit($quantity)->lockForUpdate()->get();
                if ($keys->count() !== $quantity) {
                    throw ValidationException::withMessages(['cart' => "{$product->name} không đủ key trong kho."]);
                }
                $subtotal += $product->price * $quantity;
                $lines[] = compact('product', 'quantity', 'keys');
            }
            $coupon = null;
            $discount = 0;
            if ($code) {
                $coupon = Coupon::where('code', mb_strtoupper(trim($code)))->lockForUpdate()->first();
                if (! $coupon || ! $coupon->is_active || $coupon->starts_at?->isFuture() || $coupon->expires_at?->isPast()
                    || ($coupon->usage_limit !== null && $coupon->used_count >= $coupon->usage_limit)
                    || $subtotal < $coupon->minimum_amount
                    || DB::table('coupon_usages')->where('coupon_id', $coupon->id)->where('user_id', $user->id)->count() >= $coupon->per_user_limit) {
                    throw ValidationException::withMessages(['coupon' => 'Mã giảm giá không hợp lệ, hết lượt hoặc chưa đủ điều kiện.']);
                }
                $eligible = 0;
                foreach ($lines as $line) {
                    if ((! $coupon->product_id || $line['product']->id === $coupon->product_id)
                        && (! $coupon->category_id || $line['product']->category_id === $coupon->category_id)) {
                        $eligible += $line['product']->price * $line['quantity'];
                    }
                }
                if ($eligible === 0 || ! in_array($coupon->type, ['percent', 'fixed'], true) || ($coupon->type === 'percent' && $coupon->value > 100)) {
                    throw ValidationException::withMessages(['coupon' => 'Mã không áp dụng cho sản phẩm trong giỏ.']);
                }
                $discount = min($eligible, $coupon->type === 'percent' ? intdiv($eligible * $coupon->value, 100) : $coupon->value);
                if ($coupon->maximum_discount !== null) $discount = min($discount, $coupon->maximum_discount);
            }
            $order = Order::create([
                'number' => 'SA'.now()->format('ymd').Str::upper(Str::random(10)),
                'checkout_token' => $token, 'user_id' => $user->id, 'coupon_id' => $coupon?->id,
                'subtotal' => $subtotal, 'discount' => $discount, 'total' => $subtotal - $discount,
                'status' => 'completed', 'payment_status' => 'paid', 'payment_method' => 'wallet',
                'paid_at' => now(), 'fulfilled_at' => now(),
            ]);
            $this->wallet->change($buyer, -$order->total, 'order:'.$order->number, 'purchase', 'Mua đơn '.$order->number, ['order_id' => $order->id]);
            foreach ($lines as $line) {
                $item = $order->items()->create([
                    'product_id' => $line['product']->id, 'product_name' => $line['product']->name,
                    'quantity' => $line['quantity'], 'unit_price' => $line['product']->price,
                    'total' => $line['product']->price * $line['quantity'],
                ]);
                foreach ($line['keys'] as $key) {
                    $key->update(['status' => 'sold', 'order_item_id' => $item->id, 'sold_at' => now()]);
                }
            }
            if ($coupon) {
                $coupon->increment('used_count');
                DB::table('coupon_usages')->insert(['coupon_id' => $coupon->id, 'user_id' => $user->id, 'order_id' => $order->id, 'discount' => $discount, 'created_at' => now(), 'updated_at' => now()]);
            }
            return $order;
        }, 3);
    }
}
