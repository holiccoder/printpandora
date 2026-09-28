<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\ProductImageService;
use App\Support\BusinessCardRoutes;
use App\Support\PostcardProductCatalog;
use App\Support\StickerProductCatalog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SearchController extends Controller
{
    public function index(Request $request, ProductImageService $imageService): Response
    {
        $query = trim((string) $request->query('q', ''));
        $query = mb_substr($query, 0, 100);
        $terms = preg_split('/\s+/', $query, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        $products = Product::query()
            ->select([
                'id',
                'name',
                'slug',
                'subtitle',
                'description',
                'featured_image',
                'product_category_id',
            ])
            ->with('category:id,name,slug')
            ->where('is_active', true)
            ->when($terms !== [], function (Builder $products) use ($terms): void {
                $products->where(function (Builder $search) use ($terms): void {
                    foreach ($terms as $term) {
                        $pattern = "%{$term}%";

                        $search->where(function (Builder $match) use ($pattern): void {
                            $match
                                ->where('name', 'like', $pattern)
                                ->orWhere('slug', 'like', $pattern)
                                ->orWhere('subtitle', 'like', $pattern)
                                ->orWhere('description', 'like', $pattern)
                                ->orWhereHas(
                                    'category',
                                    fn (Builder $category): Builder => $category->where('name', 'like', $pattern),
                                );
                        });
                    }
                });
            })
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        $products->getCollection()->transform(
            function (Product $product) use ($imageService): array {
                $slug = (string) $product->slug;

                return [
                    'id' => (int) $product->getKey(),
                    'name' => (string) $product->name,
                    'href' => $this->productHref($slug),
                    'summary' => $this->summary($product),
                    'featured_image' => $imageService->featuredImageUrl($product),
                    'category' => [
                        'name' => (string) ($product->category?->name ?? ''),
                    ],
                ];
            },
        );

        return Inertia::render('shop/search', [
            'query' => $query,
            'products' => $products,
        ]);
    }

    private function productHref(string $slug): string
    {
        return BusinessCardRoutes::pathForProductSlug($slug)
            ?? PostcardProductCatalog::pathForProductSlug($slug)
            ?? StickerProductCatalog::pathForProductSlug($slug)
            ?? '/'.ltrim($slug, '/');
    }

    private function summary(Product $product): string
    {
        $text = trim(preg_replace(
            '/\s+/',
            ' ',
            strip_tags((string) ($product->subtitle ?: $product->description)),
        ) ?? '');

        return mb_strlen($text) > 140
            ? mb_substr($text, 0, 137).'...'
            : $text;
    }
}
