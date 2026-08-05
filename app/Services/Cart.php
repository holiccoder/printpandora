<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Session\SessionManager;

class Cart
{
    public function __construct(
        protected SessionManager $session,
        protected PricingService $pricing,
    ) {}

    public function all(): array
    {
        return $this->session->get('cart', []);
    }

    /**
     * Add a product to the cart. Each unique combination of product + options
     * becomes its own cart line so the same product can be ordered with
     * different option sets. Quantity is always 1 per line.
     *
     * @return string The cart item key for the line that was added/updated.
     */
    public function add(int|string $productId, array $options = []): string
    {
        $cart = $this->all();
        $itemKey = $this->makeItemKey((int) $productId, $options);

        if (!isset($cart[$itemKey])) {
            $product = Product::findOrFail($productId);
            $cart[$itemKey] = [
                'key' => $itemKey,
                'product_id' => $product->id,
                'name' => $product->name,
                'price' => $this->pricing->calculate($productId, $options),
                'quantity' => 1,
                'image' => $product->featured_image,
                'slug' => $product->slug,
                'options' => $options,
            ];
        }

        $this->session->put('cart', $cart);

        return $itemKey;
    }

    public function remove(string $itemKey): void
    {
        $cart = $this->all();
        unset($cart[$itemKey]);
        $this->session->put('cart', $cart);
    }

    public function clear(): void
    {
        $this->session->forget('cart');
        $this->removeDiscountCode();
    }

    public function count(): int
    {
        return count($this->all());
    }

    public function subtotal(): float
    {
        $total = 0;
        foreach ($this->all() as $item) {
            $total += $item['price'] * $item['quantity'];
        }

        return round($total, 2);
    }

    public function total(): float
    {
        return $this->quote()['total'];
    }

    public function discountCode(): ?string
    {
        return $this->session->get('discount_code');
    }

    public function applyDiscountCode(string $code): void
    {
        $this->session->put('discount_code', trim($code));
    }

    public function removeDiscountCode(): void
    {
        $this->session->forget('discount_code');
    }

    /**
     * @return array{code: ?string, subtotal: float, discount: float, total: float}
     */
    public function quote(?string $customerEmail = null, bool $strict = false): array
    {
        $subtotal = $this->subtotal();
        $code = $this->discountCode();

        if (! $code) {
            return ['code' => null, 'subtotal' => $subtotal, 'discount' => 0.0, 'total' => $subtotal];
        }

        try {
            $quote = app(DiscountService::class)->quote($code, $subtotal, $customerEmail);

            return [
                'code' => $quote['code_value'],
                'subtotal' => $quote['subtotal'],
                'discount' => $quote['discount'],
                'total' => $quote['total'],
            ];
        } catch (DiscountException $exception) {
            if ($strict) {
                throw $exception;
            }

            $this->removeDiscountCode();

            return ['code' => null, 'subtotal' => $subtotal, 'discount' => 0.0, 'total' => $subtotal];
        }
    }

    /**
     * Build a stable cart line key from a product and its options.
     */
    protected function makeItemKey(int $productId, array $options): string
    {
        $optionsHash = empty($options)
            ? 'default'
            : substr(md5(json_encode($options)), 0, 10);

        return "{$productId}:{$optionsHash}";
    }
}
