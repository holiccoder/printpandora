<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Session\SessionManager;

class Cart
{
    public function __construct(protected SessionManager $session) {}

    public function all(): array
    {
        return $this->session->get('cart', []);
    }

    public function add(int $productId, int $quantity = 1): void
    {
        $cart = $this->all();

        if (isset($cart[$productId])) {
            $cart[$productId]['quantity'] += $quantity;
        } else {
            $product = Product::findOrFail($productId);
            $cart[$productId] = [
                'product_id' => $product->id,
                'name' => $product->name,
                'price' => (float) $product->price,
                'quantity' => $quantity,
                'image' => $product->featured_image,
                'slug' => $product->slug,
            ];
        }

        $this->session->put('cart', $cart);
    }

    public function update(int $productId, int $quantity): void
    {
        $cart = $this->all();

        if ($quantity <= 0) {
            $this->remove($productId);

            return;
        }

        if (isset($cart[$productId])) {
            $cart[$productId]['quantity'] = $quantity;
            $this->session->put('cart', $cart);
        }
    }

    public function remove(int $productId): void
    {
        $cart = $this->all();
        unset($cart[$productId]);
        $this->session->put('cart', $cart);
    }

    public function clear(): void
    {
        $this->session->forget('cart');
    }

    public function count(): int
    {
        return array_sum(array_column($this->all(), 'quantity'));
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
        return $this->subtotal();
    }
}
