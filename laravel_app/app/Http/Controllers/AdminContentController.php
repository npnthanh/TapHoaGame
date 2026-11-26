<?php

namespace App\Http\Controllers;

use App\Models\{AuditLog, Banner, Category, Product, Review, Setting};
use App\Rules\PublicAssetUrl;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Gate};
use Illuminate\Validation\Rule;

class AdminContentController extends Controller
{
    public function index()
    {
        return view('admin.content', [
            'settings' => Setting::storefront(), 'banners' => Banner::orderBy('sort_order')->get(),
            'products' => Product::orderBy('name')->get(), 'categories' => Category::orderBy('sort_order')->get(),
            'pages' => DB::table('pages')->orderBy('id')->get(),
            'reviews' => Review::with(['product', 'user'])->latest()->paginate(15),
        ]);
    }
    public function settings(Request $request)
    {
        Gate::authorize('manage-finance');
        $data = $request->validate([
            'brand_name' => 'required|string|max:80', 'tagline' => 'required|string|max:500',
            'seo_title' => 'required|string|max:150', 'seo_description' => 'required|string|max:500',
            'support_email' => 'nullable|email|max:255', 'support_url' => 'nullable|url:http,https|max:2048',
            'support_hours' => 'nullable|string|max:150', 'logo_url' => ['nullable', 'string', 'max:2048', new PublicAssetUrl],
            'favicon_url' => ['required', 'string', 'max:2048', new PublicAssetUrl], 'benefits' => 'required|string|max:2000',
        ]);
        DB::transaction(function () use ($data) {
            foreach ($data as $key => $value) Setting::updateOrCreate(['key' => $key], ['value' => $value ?? '']);
            AuditLog::record('content.settings_saved');
        });
        return back()->with('success', 'Đã lưu cấu hình website vào MySQL.');
    }
    public function banner(Request $request, ?Banner $banner = null)
    {
        $banner ??= new Banner;
        $data = $request->validate([
            'title' => 'required|string|max:200', 'description' => 'nullable|string|max:2000',
            'product_id' => 'nullable|exists:products,id', 'image_url' => ['nullable', 'string', 'max:2048', new PublicAssetUrl],
            'link_url' => 'nullable|url:http,https|max:2048', 'button_text' => 'required|string|max:80',
            'sort_order' => 'required|integer|min:0|max:10000', 'is_active' => 'nullable|boolean',
        ]);
        $data['is_active'] = $request->boolean('is_active');
        DB::transaction(function () use ($banner, $data) { $banner->fill($data)->save(); AuditLog::record('content.banner_saved', $banner); });
        return back()->with('success', 'Đã lưu banner.');
    }
    public function deleteBanner(Banner $banner)
    {
        DB::transaction(function () use ($banner) { AuditLog::record('content.banner_deleted', $banner); $banner->delete(); });
        return back()->with('success', 'Đã xóa banner.');
    }
    public function page(Request $request, ?int $page = null)
    {
        if ($page) abort_unless(DB::table('pages')->where('id', $page)->exists(), 404);
        $data = $request->validate(['title' => 'required|string|max:200', 'slug' => ['required', 'alpha_dash:ascii', 'max:200', Rule::unique('pages')->ignore($page)], 'body' => 'required|string|max:100000', 'is_published' => 'nullable|boolean']);
        $data['is_published'] = $request->boolean('is_published');
        DB::transaction(function () use ($data, $page) {
            if ($page) DB::table('pages')->where('id', $page)->update($data + ['updated_at' => now()]);
            else DB::table('pages')->insert($data + ['created_at' => now(), 'updated_at' => now()]);
            AuditLog::record('content.page_saved', null, ['page_id' => $page, 'slug' => $data['slug']]);
        });
        return back()->with('success', 'Đã lưu nội dung trang.');
    }
    public function category(Request $request, Category $category)
    {
        $data = $request->validate(['name' => 'required|string|max:150', 'slug' => ['required', 'alpha_dash:ascii', 'max:150', Rule::unique('categories')->ignore($category)], 'sort_order' => 'required|integer|min:0|max:10000', 'is_active' => 'nullable|boolean']);
        $data['is_active'] = $request->boolean('is_active');
        DB::transaction(function () use ($data, $category) { $category->update($data); AuditLog::record('category.updated', $category); });
        return back()->with('success', 'Đã cập nhật danh mục.');
    }
    public function review(Request $request, Review $review)
    {
        $request->validate(['is_approved' => 'required|boolean']);
        DB::transaction(function () use ($review, $request) { $review->update(['is_approved' => $request->boolean('is_approved')]); AuditLog::record('review.moderated', $review); });
        return back()->with('success', 'Đã cập nhật trạng thái đánh giá.');
    }
}
