<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductCategory;
use Inertia\Inertia;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::with('category')
            ->where('is_active', true)
            ->latest()
            ->simplePaginate(12);

        $categories = ProductCategory::withCount('products')->get();

        return Inertia::render('shop/index', [
            'products' => $products,
            'categories' => $categories,
        ]);
    }

    public function show(string $slug)
    {
        $product = Product::with('category')
            ->where('is_active', true)
            ->where('slug', $slug)
            ->first();

        // Unknown slugs render the Inertia 404 page instead of Laravel's
        // default ModelNotFoundException response. The route is now
        // top-level (`/{slug}`) so any unmatched single-segment URL lands
        // here — we need to fall back gracefully when it isn't a product.
        if (! $product) {
            return Inertia::render('errors/not-found')
                ->toResponse(request())
                ->setStatusCode(404);
        }

        $related = Product::with('category')
            ->where('is_active', true)
            ->where('product_category_id', $product->product_category_id)
            ->where('id', '!=', $product->id)
            ->latest()
            ->limit(4)
            ->get();

        return Inertia::render('shop/show', [
            'product' => $product,
            'related' => $related,
            'productOptions' => $this->loadProductOptions($product),
        ]);
    }

    private function loadProductOptions(Product $product): ?array
    {
        $categorySlug = $product->category?->slug;

        if (! $categorySlug) {
            return null;
        }

        $path = base_path("content/product-options/{$categorySlug}/{$product->slug}.json");

        if (! file_exists($path)) {
            return null;
        }

        $content = file_get_contents($path);

        if ($content === false) {
            return null;
        }

        $decoded = json_decode($content, true);

        return is_array($decoded) ? $decoded : null;
    }
}
