<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Services\ProductImageService;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class ProductController extends Controller
{
    public function index(ProductImageService $imageService): InertiaResponse
    {
        $products = Product::with('category')
            ->where('is_active', true)
            ->latest()
            ->simplePaginate(12);

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
        ]);
    }

    public function show(string $slug, ProductImageService $imageService): InertiaResponse|SymfonyResponse
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
        if (is_array($product->product_options) && ! empty($product->product_options)) {
            return app(ProductImageService::class)->applyGalleryOverrides($product, $product->product_options);
        }

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

        if (! is_array($decoded)) {
            return null;
        }

        $decoded['pricing_data'] = $this->loadDynamicPricingData($product->slug);

        return app(ProductImageService::class)->applyGalleryOverrides($product, $decoded);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function loadDynamicPricingData(string $slug): ?array
    {
        // Per-product pricing data: scenario key => JSON file in
        // storage/from-tool/数据文档/<dir>. Products with no UV finish
        // only provide the rectangle/square scenarios.
        $configs = [
            'classic-standard-business-cards' => [
                'dir' => '300g铜版纸',
                'files' => [
                    'rectangle' => '300g铜版纸 长方形.json',
                    'uv' => '300g铜版纸 uv.json',
                    'square' => '300g铜版纸 正方形.json',
                    'square_uv' => '300g铜版纸 正方形uv.json',
                ],
            ],
            'classic-special-business-cards' => [
                'dir' => '300g艺术纸',
                'files' => [
                    'rectangle' => '300g艺术纸-荷兰白卡.json',
                    'square' => '300g艺术纸-荷兰白卡-正方形.json',
                ],
            ],
            'classic-quality-business-cards' => [
                'dir' => '320g铜版纸',
                'files' => [
                    'rectangle' => '320g铜版纸.json',
                    'square' => '320g铜版纸-正方形.json',
                ],
            ],
            'classic-solid-business-cards' => [
                'dir' => '350g白卡',
                'files' => [
                    'rectangle' => '350g白卡.json',
                    'square' => '350g白卡-正方形.json',
                ],
            ],
            'basic-cotton-business-card' => [
                'dir' => '棉纸',
                'files' => [
                    'rectangle' => '棉纸-基础型.json',
                ],
            ],
            'classic-cotton-business-card' => [
                'dir' => '棉纸',
                'files' => [
                    'rectangle' => '棉纸-经典型.json',
                ],
            ],
            'premium-cotton-business-card' => [
                'dir' => '棉纸',
                'files' => [
                    'rectangle' => '棉纸-高级型.json',
                ],
            ],
            'luxe-cotton-business-card' => [
                'dir' => '棉纸',
                'files' => [
                    'rectangle' => '棉纸-豪华型.json',
                ],
            ],
            'grand-cotton-business-card' => [
                'dir' => '棉纸',
                'files' => [
                    'rectangle' => '棉纸-奢华型.json',
                ],
            ],
            'standard-pvc-card' => [
                'dir' => 'pvc',
                'files' => [
                    'rectangle' => 'pvc0.38.json',
                ],
            ],
            'premium-pvc-card' => [
                'dir' => 'pvc',
                'files' => [
                    'rectangle' => 'pvc0.76.json',
                ],
            ],
            'super-business-cards' => [
                'dir' => '350g精品纸',
                'files' => [
                    'rectangle' => '350g精品纸.json',
                    'square' => '350g精品纸-正方形.json',
                ],
            ],
            'luxe-business-cards' => [
                'dir' => '700g精品纸',
                'files' => [
                    'rectangle' => '700g精品纸.json',
                    'square' => '700g精品纸-正方形.json',
                ],
            ],
        ];

        $config = $configs[$slug] ?? null;

        if ($config === null) {
            return null;
        }

        $basePath = base_path('storage/from-tool/数据文档/'.$config['dir']);

        $data = [];

        foreach ($config['files'] as $key => $file) {
            $path = $basePath.'/'.$file;

            if (! file_exists($path)) {
                return null;
            }

            $content = file_get_contents($path);
            $decoded = $content !== false ? json_decode($content, true) : null;

            if (! is_array($decoded)) {
                return null;
            }

            $data[$key] = $decoded;
        }

        return $data;
    }
}
