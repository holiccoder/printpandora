<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Database\Eloquent\Collection;
use Inertia\Inertia;
use Inertia\Response;

class ShippingCalculatorController extends Controller
{
    public function show(): Response
    {
        /** @var Collection<int, ProductCategory> $categories */
        $categories = ProductCategory::query()->get()->keyBy('id');

        $productOptions = Product::query()
            ->where('is_active', true)
            ->with('category')
            ->get()
            ->map(function (Product $product) use ($categories): ?array {
                $category = $product->category;

                if (! $category) {
                    return null;
                }

                $path = [];
                $categorySlugs = [];
                $visited = [];

                while ($category !== null) {
                    $categoryId = (int) $category->getKey();

                    if (isset($visited[$categoryId])) {
                        break;
                    }

                    $visited[$categoryId] = true;
                    array_unshift($path, $category->name);
                    array_unshift($categorySlugs, $category->slug);
                    $category = $category->parent_id
                        ? $categories->get($category->parent_id)
                        : null;
                }

                $shippingType = $this->shippingTypeForCategoryPath($categorySlugs);

                if ($shippingType === null) {
                    return null;
                }

                return [
                    'value' => $product->slug ?: 'product-'.$product->getKey(),
                    'label' => implode(' - ', [...$path, $product->name]),
                    'shippingType' => $shippingType,
                ];
            })
            ->filter()
            ->sortBy('label', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        return Inertia::render('shipping-calculator', [
            'productOptions' => $productOptions,
        ]);
    }

    /**
     * Keep the calculator's existing representative-weight estimates while
     * allowing the selector to show each catalog product separately.
     *
     * @param  array<int, string>  $categorySlugs  Ordered from root to leaf.
     */
    private function shippingTypeForCategoryPath(array $categorySlugs): ?string
    {
        if (in_array('pvc-business-cards', $categorySlugs, true)) {
            return 'pvc-business-cards';
        }

        if (in_array('cotton-business-cards', $categorySlugs, true)) {
            return 'cotton-business-cards';
        }

        if (in_array('business-cards', $categorySlugs, true)) {
            return 'business-cards';
        }

        return match ($categorySlugs[0] ?? '') {
            'cards-and-postcards' => 'postcards',
            'flyers-brochures' => 'flyers',
            'stickers-and-labels' => 'stickers-labels',
            default => null,
        };
    }
}
