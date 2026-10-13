<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class ShippingCalculatorCatalog
{
    /** @var Collection<int, ProductCategory>|null */
    private ?Collection $categories = null;

    /** @var array<int, array{sizes: array<int, array<string, mixed>>, defaultSize: string}> */
    private array $sizeConfigurations = [];

    public function __construct(private ProductConfigurationService $configurations) {}

    /**
     * @return array<int, array<string, mixed>>
     */
    public function productOptions(): array
    {
        return Product::query()
            ->where('is_active', true)
            ->with('category')
            ->get()
            ->map(function (Product $product): ?array {
                $category = $product->category;

                if (! $category) {
                    return null;
                }

                $path = [];
                $categorySlugs = [];
                $visited = [];
                $categories = $this->categories();

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

                $sizeConfiguration = $this->sizeConfiguration($product);

                return [
                    'value' => (string) $product->getKey(),
                    'label' => implode(' - ', [...$path, $product->name]),
                    'shippingType' => $shippingType,
                    'sizes' => $sizeConfiguration['sizes'],
                    'defaultSize' => $sizeConfiguration['defaultSize'],
                ];
            })
            ->filter()
            ->sortBy('label', SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();
    }

    public function productType(Product $product): ?string
    {
        $category = $product->relationLoaded('category')
            ? $product->category
            : $product->category()->first();
        $categorySlugs = [];
        $visited = [];
        $categories = $this->categories();

        while ($category !== null) {
            $categoryId = (int) $category->getKey();

            if (isset($visited[$categoryId])) {
                break;
            }

            $visited[$categoryId] = true;
            array_unshift($categorySlugs, $category->slug);
            $category = $category->parent_id
                ? $categories->get($category->parent_id)
                : null;
        }

        return $this->shippingTypeForCategoryPath($categorySlugs);
    }

    /**
     * @return array{sizes: array<int, array<string, mixed>>, defaultSize: string}
     */
    public function sizeConfiguration(Product $product): array
    {
        $productId = (int) $product->getKey();

        if (isset($this->sizeConfigurations[$productId])) {
            return $this->sizeConfigurations[$productId];
        }

        $storefrontOptions = $this->configurations->storefrontOptions($product) ?? [];
        $optionGroups = is_array($storefrontOptions['option_groups'] ?? null)
            ? $storefrontOptions['option_groups']
            : [];
        $sizeGroup = null;

        foreach ($optionGroups as $group) {
            if (is_array($group) && in_array($group['key'] ?? null, ['sizes', 'size'], true)) {
                $sizeGroup = $group;

                break;
            }
        }

        $rawValues = is_array($sizeGroup['values'] ?? null)
            ? $sizeGroup['values']
            : (is_array($storefrontOptions['sizes'] ?? null) ? $storefrontOptions['sizes'] : []);
        $sizes = [];

        foreach ($rawValues as $rawValue) {
            if (! is_array($rawValue)) {
                continue;
            }

            $label = trim((string) ($rawValue['label'] ?? $rawValue['name'] ?? ''));
            $code = trim((string) ($rawValue['code'] ?? ''));

            if ($code === '') {
                $code = Str::slug($label);
            }

            if ($label === '' || $code === '') {
                continue;
            }

            $size = [
                'code' => $code,
                'label' => $label,
                'unit' => (string) ($rawValue['unit'] ?? 'in'),
            ];

            foreach ([
                'description',
                'width',
                'height',
                'area_sq_m',
                'min_width',
                'max_width',
                'min_height',
                'max_height',
            ] as $property) {
                if (array_key_exists($property, $rawValue)) {
                    $size[$property] = $rawValue[$property];
                }
            }

            $sizes[] = $size;
        }

        if ($sizes === []) {
            $sizes[] = [
                'code' => 'standard',
                'label' => 'Standard size',
                'unit' => 'in',
            ];
        }

        $defaultSize = $sizeGroup['default'] ?? $sizes[0]['code'];

        if (is_array($defaultSize)) {
            $defaultSize = reset($defaultSize);
        }

        $defaultSize = (string) $defaultSize;

        if (! collect($sizes)->contains('code', $defaultSize)) {
            $defaultSize = $sizes[0]['code'];
        }

        return $this->sizeConfigurations[$productId] = [
            'sizes' => $sizes,
            'defaultSize' => $defaultSize,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function sizeOption(Product $product, string $code): ?array
    {
        foreach ($this->sizeConfiguration($product)['sizes'] as $size) {
            if ($size['code'] === $code) {
                return $size;
            }
        }

        return null;
    }

    /**
     * @param  array<int, string>  $categorySlugs
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

    /** @return Collection<int, ProductCategory> */
    private function categories(): Collection
    {
        return $this->categories ??= ProductCategory::query()->get()->keyBy('id');
    }
}
