<?php

namespace App\Http\Controllers;

use App\Services\OrderWeightService;
use App\Services\ShippingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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
    ): JsonResponse {
        $validated = $request->validate([
            'country' => ['required', 'string', 'regex:/^[A-Za-z]{2,5}$/'],
            'product_type' => ['required', 'string', Rule::in($this->productTypes())],
            'quantity' => ['required', 'integer', 'min:1', 'max:100000'],
        ]);

        $country = strtoupper($validated['country']);
        $productType = $validated['product_type'];
        $quantity = (int) $validated['quantity'];
        $weightGrams = $weights->forCalculator($productType, $quantity);
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
            'product_type' => $productType,
            'quantity' => $quantity,
            'shipping_weight_grams' => $weights->wholeGrams($weightGrams),
            'currency' => (string) config('shipping.currency', 'USD'),
            'methods' => $methods,
        ]);
    }
}
