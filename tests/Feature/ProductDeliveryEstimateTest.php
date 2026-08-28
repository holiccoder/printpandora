<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ProductDeliveryEstimateTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_product_page_shows_latest_standard_and_fast_delivery_dates(): void
    {
        Carbon::setTestNow('2026-08-27 09:00:00');

        $category = ProductCategory::create([
            'name' => 'Business Cards',
            'slug' => 'business-cards',
        ]);

        Product::create([
            'name' => 'Delivery estimate card',
            'slug' => 'delivery-estimate-card',
            'description' => 'Test card',
            'price' => 10,
            'product_category_id' => $category->id,
            'is_active' => true,
            'product_config' => [],
        ]);

        $this->get('/delivery-estimate-card')
            ->assertOk()
            ->assertInertia(fn (Assert $page): Assert => $page
                ->component('shop/show')
                ->where('deliveryEstimates.standard', 'Mon, 14 Sep')
                ->where('deliveryEstimates.fast', 'Thu, 3 Sep'));
    }
}
