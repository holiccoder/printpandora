<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;

class OrderWeightService
{
    private const DEFAULT_CARD_WIDTH_METERS = 0.09;

    private const DEFAULT_CARD_HEIGHT_METERS = 0.054;

    private const SQUARE_CARD_SIZE_METERS = 0.06;

    private const INCH_TO_METERS = 0.0254;

    /**
     * Calculate the total parcel weight for the current cart in grams.
     *
     * Product weight is stored as GSM, so a card's weight is its area in
     * square metres multiplied by the product weight and line quantity.
     *
     * @param  array<string, array<string, mixed>>  $cart
     */
    public function forCart(array $cart): float
    {
        $productIds = [];

        foreach ($cart as $item) {
            $productId = (int) ($item['product_id'] ?? 0);

            if ($productId > 0) {
                $productIds[$productId] = true;
            }
        }

        $products = Product::query()
            ->whereIn('id', array_keys($productIds))
            ->get()
            ->keyBy('id');

        $productWeight = 0.0;

        foreach ($cart as $item) {
            $product = $products->get((int) ($item['product_id'] ?? 0));

            if (! $product) {
                continue;
            }

            $options = is_array($item['options'] ?? null) ? $item['options'] : [];

            $productWeight += $this->forLine(
                $product,
                $options,
                (int) ($item['quantity'] ?? 1),
            );
        }

        return $this->withPackageWeight($productWeight);
    }

    public function forOrder(Order $order): float
    {
        $order->loadMissing('items.product');

        $productWeight = 0.0;

        foreach ($order->items as $item) {
            if (! $item->product) {
                continue;
            }

            $rawOptions = $item->getAttribute('options');
            $options = is_array($rawOptions) ? $rawOptions : [];

            $productWeight += $this->forLine(
                $item->product,
                $options,
                (int) $item->quantity,
            );
        }

        return $this->withPackageWeight($productWeight);
    }

    public function packageWeightGrams(): int
    {
        return max(0, (int) config('shipping.package_weight_grams', 250));
    }

    /**
     * Calculate an estimated parcel weight for the public shipping
     * calculator. Use catalog material weights where a representative
     * product exists, and configured category estimates for static landing
     * pages that do not have products in the catalog yet.
     */
    public function forCalculator(string $productType, int $quantity): float
    {
        $quantity = max(1, $quantity);
        $productSlug = data_get(
            config('shipping.calculator_product_slugs', []),
            $productType,
        );

        if (is_string($productSlug) && $productSlug !== '') {
            $product = Product::query()->where('slug', $productSlug)->first();

            if ($product) {
                return $this->withPackageWeight(
                    $this->forLine($product, [], $quantity),
                );
            }
        }

        $unitWeight = data_get(
            config('shipping.calculator_unit_weights_grams', []),
            $productType,
            1.0,
        );

        return $this->withPackageWeight(
            max(0.0, (float) $unitWeight) * $quantity,
        );
    }

    public function wholeGrams(float $weightGrams): int
    {
        return max(0, (int) round($weightGrams));
    }

    /**
     * @param  array<string, mixed>  $options
     */
    public function forLine(Product $product, array $options = [], int $quantity = 1): float
    {
        $quantity = $this->quantityForLine($options, $quantity);

        if ($quantity <= 0) {
            return 0.0;
        }

        return $this->cardAreaSquareMeters($options)
            * max(0.0, (float) $product->weight)
            * $quantity;
    }

    /**
     * @param  array<string, mixed>  $options
     */
    private function cardAreaSquareMeters(array $options): float
    {
        $size = $this->optionValue($options['sizes'] ?? $options['size'] ?? null);

        if ($size === 'square') {
            return self::SQUARE_CARD_SIZE_METERS ** 2;
        }

        $customWidth = $this->positiveNumber(
            $options['custom_width'] ?? ($options['width'] ?? null),
        );
        $customHeight = $this->positiveNumber(
            $options['custom_height'] ?? ($options['height'] ?? null),
        );

        if ($size === 'custom' && $customWidth !== null && $customHeight !== null) {
            return ($customWidth * self::INCH_TO_METERS)
                * ($customHeight * self::INCH_TO_METERS);
        }

        return self::DEFAULT_CARD_WIDTH_METERS * self::DEFAULT_CARD_HEIGHT_METERS;
    }

    private function withPackageWeight(float $productWeight): float
    {
        return round(max(0.0, $productWeight) + $this->packageWeightGrams(), 4);
    }

    private function optionValue(mixed $value): string
    {
        if (is_array($value)) {
            $value = reset($value);
        }

        return strtolower(trim((string) $value));
    }

    private function positiveNumber(mixed $value): ?float
    {
        if (! is_numeric($value)) {
            return null;
        }

        $number = (float) $value;

        return $number > 0 && is_finite($number) ? $number : null;
    }

    /**
     * The storefront stores the number of cards in the line options. The
     * order/cart line quantity is the fallback for products without a pack
     * quantity option.
     *
     * @param  array<string, mixed>  $options
     */
    private function quantityForLine(array $options, int $lineQuantity): int
    {
        $optionQuantity = $options['quantity'] ?? null;

        if (is_numeric($optionQuantity) && is_finite((float) $optionQuantity)) {
            return max(0, (int) $optionQuantity);
        }

        return max(0, $lineQuantity);
    }
}
