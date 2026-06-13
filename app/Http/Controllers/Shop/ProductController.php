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
            ->firstOrFail();

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
        ]);
    }
}
