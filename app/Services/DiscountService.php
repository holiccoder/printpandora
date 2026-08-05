<?php

namespace App\Services;

use App\Models\DiscountCode;
use App\Models\DiscountRedemption;
use App\Models\Order;
use Illuminate\Support\Str;
use RuntimeException;

class DiscountService
{
    /**
     * Return the validated pricing quote for a code and cart subtotal.
     * Customer limits are checked when an email is available.
     *
     * @return array{code: DiscountCode, code_value: string, subtotal: float, discount: float, total: float}
     */
    public function quote(string $code, float $subtotal, ?string $customerEmail = null): array
    {
        $normalized = Str::upper(trim($code));
        $discountCode = DiscountCode::where('code', $normalized)->first();

        if (! $discountCode) {
            throw new DiscountException('That discount code is not valid.');
        }

        $this->assertEligible($discountCode, $subtotal, $customerEmail);

        $discount = $discountCode->type === 'percent'
            ? round($subtotal * ((float) $discountCode->value / 100), 2)
            : (float) $discountCode->value;
        $discount = min(max($discount, 0), $subtotal);

        return [
            'code' => $discountCode,
            'code_value' => $discountCode->code,
            'subtotal' => round($subtotal, 2),
            'discount' => round($discount, 2),
            'total' => round(max($subtotal - $discount, 0), 2),
        ];
    }

    public function redeem(
        string $code,
        Order $order,
        string $customerEmail,
        float $subtotal,
        float $discount,
        float $total,
    ): DiscountRedemption {
        $normalized = Str::upper(trim($code));
        $discountCode = DiscountCode::where('code', $normalized)->lockForUpdate()->first();

        if (! $discountCode) {
            throw new DiscountException('That discount code is not valid.');
        }

        $this->assertEligible($discountCode, $subtotal, $customerEmail);

        $redemption = DiscountRedemption::create([
            'discount_code_id' => $discountCode->id,
            'order_id' => $order->id,
            'code' => $discountCode->code,
            'customer_email' => Str::lower(trim($customerEmail)),
            'subtotal' => round($subtotal, 2),
            'discount_amount' => round($discount, 2),
            'total' => round($total, 2),
        ]);

        $discountCode->increment('usage_count');

        return $redemption;
    }

    protected function assertEligible(DiscountCode $discountCode, float $subtotal, ?string $customerEmail): void
    {
        $now = now();

        if (! $discountCode->is_active) {
            throw new DiscountException('That discount code is inactive.');
        }

        if ($discountCode->starts_at && $now->isBefore($discountCode->starts_at)) {
            throw new DiscountException('That discount code is not active yet.');
        }

        if ($discountCode->ends_at && $now->isAfter($discountCode->ends_at)) {
            throw new DiscountException('That discount code has expired.');
        }

        if (! in_array($discountCode->type, ['percent', 'fixed'], true)) {
            throw new DiscountException('That discount code is not configured correctly.');
        }

        if ($discountCode->type === 'percent' && ((float) $discountCode->value <= 0 || (float) $discountCode->value > 100)) {
            throw new DiscountException('That discount code is not configured correctly.');
        }

        if ($discountCode->type === 'fixed' && (float) $discountCode->value <= 0) {
            throw new DiscountException('That discount code is not configured correctly.');
        }

        if ($subtotal < (float) $discountCode->minimum_subtotal) {
            throw new DiscountException(sprintf(
                'This code requires a minimum subtotal of $%s.',
                number_format((float) $discountCode->minimum_subtotal, 2),
            ));
        }

        if ($discountCode->max_uses !== null && $discountCode->usage_count >= $discountCode->max_uses) {
            throw new DiscountException('That discount code has reached its usage limit.');
        }

        if ($customerEmail && $discountCode->max_uses_per_customer !== null) {
            $customerUses = $discountCode->redemptions()
                ->whereRaw('LOWER(customer_email) = ?', [Str::lower(trim($customerEmail))])
                ->count();

            if ($customerUses >= $discountCode->max_uses_per_customer) {
                throw new DiscountException('You have already reached the usage limit for this discount code.');
            }
        }
    }
}

class DiscountException extends RuntimeException
{
}
