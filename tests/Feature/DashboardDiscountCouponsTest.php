<?php

namespace Tests\Feature;

use App\Models\DiscountCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DashboardDiscountCouponsTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_view_only_currently_available_coupons(): void
    {
        $user = User::factory()->create();

        DiscountCode::create([
            'code' => 'SAVE10',
            'type' => 'percent',
            'value' => 10,
        ]);
        DiscountCode::create([
            'code' => 'MINIMUM50',
            'type' => 'fixed',
            'value' => 5,
            'minimum_subtotal' => 50,
        ]);
        DiscountCode::create([
            'code' => 'INACTIVE',
            'type' => 'percent',
            'value' => 10,
            'is_active' => false,
        ]);
        DiscountCode::create([
            'code' => 'FUTURE',
            'type' => 'percent',
            'value' => 10,
            'starts_at' => now()->addDay(),
        ]);
        DiscountCode::create([
            'code' => 'EXPIRED',
            'type' => 'percent',
            'value' => 10,
            'ends_at' => now()->subDay(),
        ]);
        DiscountCode::create([
            'code' => 'EXHAUSTED',
            'type' => 'percent',
            'value' => 10,
            'max_uses' => 1,
            'usage_count' => 1,
        ]);
        DiscountCode::create([
            'code' => 'FIRSTORDER',
            'type' => 'percent',
            'value' => 15,
            'first_order_only' => true,
        ]);
        DiscountCode::create([
            'code' => 'INVALIDPERCENT',
            'type' => 'percent',
            'value' => 101,
        ]);

        $this->actingAs($user)
            ->get(route('dashboard.discount-coupons'))
            ->assertOk()
            ->assertInertia(fn (Assert $page): Assert => $page
                ->component('dashboard/discount-coupons')
                ->where('coupons', function ($coupons): bool {
                    return collect($coupons)->pluck('code')->all() === [
                        'FIRSTORDER',
                        'MINIMUM50',
                        'SAVE10',
                        'WELCOME15',
                    ];
                })
                ->where('coupons.0.minimum_subtotal', 0)
                ->where('coupons.1.minimum_subtotal', 50)
                ->where('coupons.2.type', 'percent')
            );
    }

    public function test_guest_cannot_view_customer_coupons(): void
    {
        $this->get(route('dashboard.discount-coupons'))
            ->assertRedirect(route('login'));
    }
}
