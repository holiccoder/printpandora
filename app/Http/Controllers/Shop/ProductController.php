<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Services\ProductConfigurationService;
use App\Services\ProductImageService;
use App\Support\BusinessCardRoutes;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class ProductController extends Controller
{
    public function index(Request $request, ProductImageService $imageService): InertiaResponse
    {
        $selectedCategory = $request->string('cat')->toString();
        $productQuery = Product::with('category')
            ->where('is_active', true)
            ->latest();

        if ($selectedCategory !== '') {
            $category = ProductCategory::query()
                ->where('slug', $selectedCategory)
                ->first();

            if ($category) {
                $productQuery->whereIn('product_category_id', [
                    $category->getKey(),
                    ...$category->descendantIds(),
                ]);
            }
        }

        $products = $productQuery->simplePaginate(12)->withQueryString();

        $products->getCollection()->transform(function (Product $product) use ($imageService): Product {
            if ($image = $imageService->featuredImageUrl($product)) {
                $product->setAttribute('featured_image', $image);
            }

            return $product;
        });

        $categories = ProductCategory::withCount('products')->get();

        return Inertia::render('shop/index', [
            'products' => $products,
            'categories' => $categories,
            'selectedCategory' => $selectedCategory !== '' ? $selectedCategory : null,
        ]);
    }

    public function show(string $slug, ProductImageService $imageService): InertiaResponse|SymfonyResponse
    {
        $productSlug = BusinessCardRoutes::productSlugForSegment($slug) ?? $slug;
        $product = Product::with('category')
            ->where('is_active', true)
            ->where('slug', $productSlug)
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

        if (request()->routeIs('shop.show')) {
            $canonicalPath = BusinessCardRoutes::pathForProductSlug($productSlug);

            if ($canonicalPath !== null) {
                return redirect()->to($canonicalPath, 301);
            }
        }

        if ($image = $imageService->featuredImageUrl($product)) {
            $product->setAttribute('featured_image', $image);
        }

        return Inertia::render('shop/show', [
            'product' => $product,
            'productOptions' => $this->loadProductOptions($product),
            'fallbackGalleryImages' => $imageService->fallbackGalleryImages($product),
        ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function loadProductOptions(Product $product): ?array
    {
        $options = app(ProductConfigurationService::class)->storefrontOptions($product);

        return is_array($options)
            ? app(ProductImageService::class)->applyGalleryOverrides($product, $options)
            : null;
    }
}
