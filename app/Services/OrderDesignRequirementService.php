<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductDesignRequest;
use App\Support\DesignServiceProduct;
use App\Support\FreeSamplePackProduct;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

final class OrderDesignRequirementService
{
    /**
     * Ensure every payable cart line has a design request that belongs to it.
     * Product-page submissions are attached to the pending order before this
     * method is called.
     *
     * @param  array<int|string, array<string, mixed>>  $cartItems
     */
    public function assertReady(Order $order, array $cartItems): void
    {
        $order->loadMissing([
            'productDesignRequests',
            'designServiceRequests',
        ]);

        foreach ($cartItems as $item) {
            $productId = (int) ($item['product_id'] ?? 0);
            $product = $productId > 0 ? Product::query()->find($productId) : null;

            if (! $product) {
                throw ValidationException::withMessages([
                    'design' => '购物车中包含无效商品。',
                ]);
            }

            if (in_array($product->slug, [
                FreeSamplePackProduct::SLUG,
            ], true)) {
                continue;
            }

            if ($product->slug === DesignServiceProduct::SLUG) {
                $requestId = (int) data_get(
                    $item,
                    'options.design_service_request_id',
                    0,
                );

                if ($requestId === 0 || ! $order->designServiceRequests->contains('id', $requestId)) {
                    $this->fail($product->name);
                }

                continue;
            }

            $designRequest = $this->productDesignRequestsForProduct(
                $order->productDesignRequests,
                $productId,
            )->first(function (array $payload) use ($item): bool {
                $pendingDesignId = trim((string) ($item['pending_design_id'] ?? ''));

                return $this->hasRequiredMaterial($payload)
                    && ($pendingDesignId === ''
                        || (string) ($payload['client_id'] ?? '') === $pendingDesignId);
            });

            if ($designRequest === null) {
                $this->fail($product->name);
            }
        }
    }

    /**
     * @param  array<mixed, mixed>  $payload
     */
    private function hasRequiredMaterial(array $payload): bool
    {
        $mode = (string) ($payload['mode'] ?? '');

        if (in_array($mode, ['upload', 'canva'], true)) {
            return $this->hasValue($payload['design_path'] ?? null);
        }

        if ($mode === 'design-for-you') {
            return trim((string) ($payload['business_name'] ?? '')) !== ''
                && trim((string) ($payload['business_card_type'] ?? '')) !== ''
                && ($payload['terms_accepted'] ?? false) === true;
        }

        return false;
    }

    /**
     * @param  Collection<int, ProductDesignRequest>  $requests
     * @return Collection<int, array<mixed, mixed>>
     */
    private function productDesignRequestsForProduct(Collection $requests, int $productId): Collection
    {
        return $requests
            ->sortByDesc('created_at')
            ->map(fn ($request): mixed => $request->getAttribute('desgin'))
            ->filter(fn (mixed $payload): bool => is_array($payload)
                && (int) ($payload['product_id'] ?? 0) === $productId)
            ->values();
    }

    private function hasValue(mixed $value): bool
    {
        if (is_array($value)) {
            return collect($value)->contains(fn (mixed $item): bool => $this->hasValue($item));
        }

        return is_string($value) && trim($value) !== '';
    }

    private function fail(string $productName): never
    {
        throw ValidationException::withMessages([
            'design' => "请先为商品“{$productName}”提交设计稿、参考图或设计资料，然后再提交订单或付款。",
        ]);
    }
}
