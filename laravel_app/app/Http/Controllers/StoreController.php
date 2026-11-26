<?php

namespace App\Http\Controllers;

use App\Models\{Banner, Category, Product};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class StoreController extends Controller
{
    public function sitemap()
    {
        $urls = collect([['url' => route('home'), 'updated' => null], ['url' => route('products'), 'updated' => null]]);
        foreach (Product::visible()->limit(45000)->get(['slug','updated_at']) as $product) $urls->push(['url' => route('product', $product), 'updated' => $product->updated_at?->toAtomString()]);
        foreach (DB::table('pages')->where('is_published',true)->limit(1000)->get(['slug','updated_at']) as $page) $urls->push(['url' => route('page',$page->slug),'updated' => $page->updated_at ? \Illuminate\Support\Carbon::parse($page->updated_at)->toAtomString() : null]);
        return response()->view('store.sitemap', compact('urls'))->header('Content-Type','application/xml; charset=UTF-8');
    }
    public function robots()
    {
        $body = config('shop.demo_mode') ? "User-agent: *\nDisallow: /\n" : "User-agent: *\nDisallow: /admin\nDisallow: /tai-khoan\nDisallow: /don-hang\nDisallow: /vi\nDisallow: /gio-hang\nSitemap: ".url('/sitemap.xml')."\n";
        return response($body)->header('Content-Type','text/plain; charset=UTF-8');
    }
    public function home()
    {
        $featured = Product::visible()->with('category')
            ->withCount(['availableKeys', 'approvedReviews'])->withAvg('approvedReviews', 'rating')
            ->where('is_featured', true)->orderBy('sort_order')->orderBy('id')->limit(24)->get()
            ->unique(fn ($product) => mb_strtolower($product->name))->take(4)->values();

        return view('store.home', [
            'banners' => Banner::with('product')->where('is_active', true)->where(fn ($q) => $q->whereNull('product_id')->orWhereHas('product', fn ($q) => $q->visible()))->orderBy('sort_order')->get(),
            'categories' => Category::where('is_active', true)->orderBy('sort_order')->withCount(['products' => fn ($q) => $q->visible()])->get(),
            'featured' => $featured,
            'moreProducts' => Product::visible()->with('category')
                ->withCount(['availableKeys', 'approvedReviews'])->withAvg('approvedReviews', 'rating')
                ->whereNotIn('id', $featured->modelKeys())->orderByDesc('is_featured')
                ->orderBy('sort_order')->orderBy('id')->limit(32)->get(),
            'latest' => Product::visible()->with('category')->withCount(['availableKeys', 'approvedReviews'])->withAvg('approvedReviews', 'rating')->latest()->orderByDesc('id')->limit(4)->get(),
        ]);
    }

    public function products(Request $request)
    {
        $data = $request->validate([
            'q' => 'nullable|string|max:150', 'category' => 'nullable|string|max:150',
            'min' => 'nullable|integer|min:0|max:1000000000', 'max' => 'nullable|integer|min:0|max:1000000000',
            'stock' => ['nullable', Rule::in(['available', 'soldout'])],
            'sort' => ['nullable', Rule::in(['newest', 'price_asc', 'price_desc', 'bestselling'])],
        ]);
        $query = Product::visible()->with('category')->withCount(['availableKeys', 'approvedReviews'])->withAvg('approvedReviews', 'rating');
        if ($data['q'] ?? null) $query->where('name', 'like', '%'.$data['q'].'%');
        if ($data['category'] ?? null) $query->whereHas('category', fn ($q) => $q->where('slug', $data['category']));
        if (isset($data['min'])) $query->where('price', '>=', $data['min']);
        if (isset($data['max'])) $query->where('price', '<=', $data['max']);
        if (($data['stock'] ?? '') === 'available') $query->has('availableKeys');
        if (($data['stock'] ?? '') === 'soldout') $query->doesntHave('availableKeys');
        match ($data['sort'] ?? 'newest') {
            'price_asc' => $query->orderBy('price'),
            'price_desc' => $query->orderByDesc('price'),
            'bestselling' => $query->withSum(['orderItems as sold_quantity' => fn ($q) => $q->whereHas('order', fn ($q) => $q->where('status', 'completed'))], 'quantity')->orderByDesc('sold_quantity'),
            default => $query->latest()->orderByDesc('id'),
        };
        return view('store.products', ['products' => $query->paginate(12)->withQueryString(), 'categories' => Category::where('is_active', true)->orderBy('sort_order')->get()]);
    }

    public function product(Product $product)
    {
        abort_unless($product->is_active && $product->category->is_active, 404);
        $product->loadCount('availableKeys');
        $product->load(['approvedReviews' => fn ($q) => $q->with('user')->latest()->limit(20)]);
        return view('store.product', compact('product'));
    }

    public function page(string $slug)
    {
        $page = DB::table('pages')->where('slug', $slug)->where('is_published', true)->first();
        abort_unless($page, 404);
        return view('store.page', compact('page'));
    }
}
