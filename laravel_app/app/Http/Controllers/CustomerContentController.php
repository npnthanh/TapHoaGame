<?php

namespace App\Http\Controllers;

use App\Models\{Product, OrderItem, Review};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerContentController extends Controller
{
    public function wishlist(Request $request)
    {
        $products = Product::visible()->whereIn('id', DB::table('wishlists')->where('user_id', $request->user()->id)->select('product_id'))->withCount('availableKeys')->withAvg('approvedReviews', 'rating')->withCount('approvedReviews')->paginate(12);
        return view('account.wishlist', compact('products'));
    }
    public function addWishlist(Request $request, Product $product)
    {
        abort_unless($product->is_active && $product->category->is_active, 404);
        DB::table('wishlists')->insertOrIgnore(['user_id' => $request->user()->id, 'product_id' => $product->id, 'created_at' => now(), 'updated_at' => now()]);
        return back()->with('success', 'Đã thêm vào danh sách yêu thích.');
    }
    public function removeWishlist(Request $request, Product $product)
    {
        DB::table('wishlists')->where('user_id', $request->user()->id)->where('product_id', $product->id)->delete();
        return back()->with('success', 'Đã bỏ sản phẩm khỏi danh sách yêu thích.');
    }
    public function review(Request $request, Product $product)
    {
        $data = $request->validate(['rating' => 'required|integer|between:1,5', 'comment' => 'nullable|string|max:3000']);
        $item = OrderItem::where('product_id', $product->id)->whereHas('order', fn ($q) => $q->where('user_id', $request->user()->id)->where('status', 'completed')->where('payment_status', 'paid'))->first();
        abort_unless($item, 403, 'Bạn cần mua sản phẩm thành công trước khi đánh giá.');
        Review::updateOrCreate(['order_item_id' => $item->id], $data + ['product_id' => $product->id, 'user_id' => $request->user()->id, 'is_approved' => false]);
        return back()->with('success', 'Đã gửi đánh giá để quản trị viên duyệt.');
    }
}
