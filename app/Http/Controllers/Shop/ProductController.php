<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\ProductConfigurationService;
use App\Services\ProductImageService;
use App\Services\ShippingService;
use App\Support\BusinessCardRoutes;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class ProductController extends Controller
{
    public function show(
        string $slug,
        ProductImageService $imageService,
        ShippingService $shippingService,
    ): InertiaResponse|SymfonyResponse {
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

        $deliveryEstimateStart = now();

        return Inertia::render('shop/show', [
            'product' => $product,
            'productOptions' => $this->loadProductOptions($product),
            'fallbackGalleryImages' => $imageService->fallbackGalleryImages($product),
            'deliveryEstimates' => [
                'standard' => $shippingService
                    ->latestDeliveryDate('standard', $deliveryEstimateStart)
                    ->format('D, j M'),
                'fast' => $shippingService
                    ->latestDeliveryDate('dhl_express', $deliveryEstimateStart)
                    ->format('D, j M'),
            ],
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
