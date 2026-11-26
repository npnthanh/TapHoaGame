<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\CheckoutService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CartController extends Controller
{
    public function index(Request $request)
    {
        $cart = $request->session()->get('cart', []);
        $products = Product::visible()->withCount('availableKeys')->whereIn('id', array_keys($cart))->get();
        $subtotal = $products->sum(fn ($p) => $p->price * ($cart[$p->id] ?? 0));
        $request->session()->put('checkout_token', $request->session()->get('checkout_token', (string) Str::uuid()));
        return view('store.cart', compact('cart', 'products', 'subtotal'));
    }

    public function add(Request $request, Product $product)
    {
        $data = $request->validate(['quantity' => 'required|integer|min:1|max:20']);
        abort_unless($product->is_active && $product->category->is_active, 404);
        $cart = $request->session()->get('cart', []);
        $quantity = ($cart[$product->id] ?? 0) + (int) $data['quantity'];
        if (count($cart) >= 50 && ! isset($cart[$product->id])) throw ValidationException::withMessages(['cart' => 'Giỏ hàng tối đa 50 sản phẩm.']);
        if ($quantity > 20 || $quantity > $product->availableKeys()->count()) throw ValidationException::withMessages(['quantity' => 'Số lượng vượt tồn kho hoặc giới hạn 20 key.']);
        $cart[$product->id] = $quantity;
        $request->session()->put('cart', $cart);
        $request->session()->forget('checkout_token');
        return back()->with('success', 'Đã thêm sản phẩm vào giỏ.');
    }

    public function update(Request $request, Product $product)
    {
        $data = $request->validate(['quantity' => 'required|integer|min:0|max:20']);
        $cart = $request->session()->get('cart', []);
        if ((int) $data['quantity'] === 0) unset($cart[$product->id]);
        elseif ($product->is_active && $product->category->is_active && $product->availableKeys()->count() >= (int) $data['quantity']) $cart[$product->id] = (int) $data['quantity'];
        else throw ValidationException::withMessages(['quantity' => 'Sản phẩm không đủ hàng hoặc đã ngừng bán.']);
        $request->session()->put('cart', $cart);
        $request->session()->forget('checkout_token');
        return back()->with('success', 'Đã cập nhật giỏ hàng.');
    }

    public function clear(Request $request)
    {
        $request->session()->forget(['cart', 'checkout_token']);
        return back()->with('success', 'Đã làm trống giỏ hàng.');
    }

    public function checkout(Request $request, CheckoutService $checkout)
    {
        $data = $request->validate(['checkout_token' => 'required|uuid', 'coupon' => 'nullable|string|max:50']);
        $order = $checkout->checkout($request->user(), $request->session()->get('cart', []), $data['checkout_token'], $data['coupon'] ?? null);
        $request->session()->forget(['cart', 'checkout_token']);
        return redirect()->route('orders.show', $order)->with('success', 'Thanh toán thành công. Key của bạn đã sẵn sàng.');
    }
}
