<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\OrderWeightService;
use App\Services\ShippingCalculatorCatalog;
use App\Services\ShippingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ShippingQuoteController extends Controller
{
    /**
     * Return the product categories currently supported by the calculator.
     *
     * @return array<int, string>
     */
    private function productTypes(): array
    {
        return array_values(array_unique([
            ...array_keys((array) config('shipping.calculator_product_slugs', [])),
            ...array_keys((array) config('shipping.calculator_unit_weights_grams', [])),
        ]));
    }

    public function store(
        Request $request,
        ShippingService $shipping,
        OrderWeightService $weights,
        ShippingCalculatorCatalog $catalog,
    ): JsonResponse {
        $validated = $request->validate([
            'country' => ['required', 'string', 'regex:/^[A-Za-z]{2,5}$/'],
            'items' => ['required_without:product_type', 'array', 'min:1', 'max:20'],
            'items.*.product_id' => ['required', 'integer', 'min:1'],
            'items.*.size' => ['nullable', 'string', 'max:100'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:100000'],
            'items.*.custom_width' => ['nullable', 'numeric', 'gt:0', 'max:1000'],
            'items.*.custom_height' => ['nullable', 'numeric', 'gt:0', 'max:1000'],
            // Keep accepting the original one-product request during rollout.
            'product_type' => [
                'required_without:items',
                'nullable',
                'string',
                Rule::in($this->productTypes()),
            ],
            'quantity' => ['required_without:items', 'nullable', 'integer', 'min:1', 'max:100000'],
        ]);

        $country = strtoupper($validated['country']);

        if (is_array($validated['items'] ?? null)) {
            return $this->storeMultipleItems(
                $validated['items'],
                $country,
                $shipping,
                $weights,
                $catalog,
            );
        }

        $productType = $validated['product_type'];
        $quantity = (int) $validated['quantity'];
        $weightGrams = $weights->forCalculator($productType, $quantity);

        return $this->quoteResponse($shipping, $weights, $country, $weightGrams, [
            'product_type' => $productType,
            'quantity' => $quantity,
        ]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    private function storeMultipleItems(
        array $items,
        string $country,
        ShippingService $shipping,
        OrderWeightService $weights,
        ShippingCalculatorCatalog $catalog,
    ): JsonResponse {
        $products = Product::query()
            ->where('is_active', true)
            ->whereIn('id', array_column($items, 'product_id'))
            ->with('category')
            ->get()
            ->keyBy('id');
        $weightLines = [];
        $quoteItems = [];

        foreach ($items as $index => $item) {
            $product = $products->get((int) $item['product_id']);

            if (! $product instanceof Product || ! in_array($catalog->productType($product), $this->productTypes(), true)) {
                throw ValidationException::withMessages([
                    "items.{$index}.product_id" => 'Select an available calculator product.',
                ]);
            }

            $sizeConfiguration = $catalog->sizeConfiguration($product);
            $sizeCode = trim((string) ($item['size'] ?? $sizeConfiguration['defaultSize']));
            $size = $catalog->sizeOption($product, $sizeCode);

            if ($size === null) {
                throw ValidationException::withMessages([
                    "items.{$index}.size" => 'Select a size available for this product.',
                ]);
            }

            $lineOptions = ['sizes' => $sizeCode];

            if ($this->isCustomSize($size)) {
                $width = isset($item['custom_width']) ? (float) $item['custom_width'] : null;
                $height = isset($item['custom_height']) ? (float) $item['custom_height'] : null;

                if ($width === null || $height === null) {
                    throw ValidationException::withMessages([
                        "items.{$index}.custom_width" => 'Enter both custom dimensions.',
                        "items.{$index}.custom_height" => 'Enter both custom dimensions.',
                    ]);
                }

                $this->validateCustomDimension($width, $size['min_width'] ?? null, $size['max_width'] ?? null, "items.{$index}.custom_width");
                $this->validateCustomDimension($height, $size['min_height'] ?? null, $size['max_height'] ?? null, "items.{$index}.custom_height");

                $lineOptions['calculator_width'] = $width;
                $lineOptions['calculator_height'] = $height;
                $lineOptions['calculator_unit'] = $size['unit'] ?? 'in';
            } elseif (is_numeric($size['area_sq_m'] ?? null) && (float) $size['area_sq_m'] > 0) {
                $lineOptions['calculator_area_square_meters'] = (float) $size['area_sq_m'];
            } elseif (
                is_numeric($size['width'] ?? null)
                && (float) $size['width'] > 0
                && is_numeric($size['height'] ?? null)
                && (float) $size['height'] > 0
            ) {
                $lineOptions['calculator_width'] = (float) $size['width'];
                $lineOptions['calculator_height'] = (float) $size['height'];
                $lineOptions['calculator_unit'] = $size['unit'] ?? 'in';
            }

            $quantity = (int) $item['quantity'];
            $weightLines[] = [
                'product' => $product,
                'options' => $lineOptions,
                'quantity' => $quantity,
            ];
            $sizeLabel = $size['label'];

            if ($this->isCustomSize($size)) {
                $sizeLabel .= sprintf(
                    ' (%s x %s %s)',
                    $item['custom_width'],
                    $item['custom_height'],
                    $size['unit'] ?? 'in',
                );
            }

            $quoteItems[] = [
                'product' => $product->name,
                'size' => $sizeLabel,
                'quantity' => $quantity,
            ];
        }

        return $this->quoteResponse(
            $shipping,
            $weights,
            $country,
            $weights->forCalculatorLines($weightLines),
            ['items' => $quoteItems],
        );
    }

    /**
     * @param  array<string, mixed>  $size
     */
    private function isCustomSize(array $size): bool
    {
        return str_contains(
            strtolower((string) $size['code'].' '.(string) $size['label']),
            'custom',
        );
    }

    private function validateCustomDimension(
        float $value,
        mixed $minimum,
        mixed $maximum,
        string $field,
    ): void {
        if (is_numeric($minimum) && $value < (float) $minimum) {
            throw ValidationException::withMessages([
                $field => 'The custom dimension is below the minimum for this product.',
            ]);
        }

        if (is_numeric($maximum) && $value > (float) $maximum) {
            throw ValidationException::withMessages([
                $field => 'The custom dimension exceeds the maximum for this product.',
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $additionalData
     */
    private function quoteResponse(
        ShippingService $shipping,
        OrderWeightService $weights,
        string $country,
        float $weightGrams,
        array $additionalData = [],
    ): JsonResponse {
        $methods = array_map(
            static fn (array $method): array => [
                'code' => $method['code'],
                'label' => $method['label'],
                'carrier' => $method['carrier'],
                'fee' => $method['fee'],
                'currency' => (string) config('shipping.currency', 'USD'),
                'description' => $method['description'],
                'estimated_delivery' => $method['estimated_delivery'],
            ],
            $shipping->methods($country, $weightGrams),
        );

        return response()->json([
            'country' => $country,
            'shipping_weight_grams' => $weights->wholeGrams($weightGrams),
            'currency' => (string) config('shipping.currency', 'USD'),
            'methods' => $methods,
            ...$additionalData,
        ]);
    }
}
