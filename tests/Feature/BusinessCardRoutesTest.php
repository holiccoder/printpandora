<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class BusinessCardRoutesTest extends TestCase
{
    use RefreshDatabase;

    public function test_shop_index_is_no_longer_available(): void
    {
        $this->get('/shop')->assertNotFound();
    }

    public function test_business_card_landing_exposes_paper_images_in_requested_order(): void
    {
        $paperImages = [
            '/images/business-cards/paper/shop-by-paper-floral.webp',
            '/images/business-cards/paper/shop-by-paper-gradient.webp',
            '/images/business-cards/paper/shop-by-paper-yellow-taxi.webp',
            '/images/business-cards/paper/shop-by-paper-brushed-metal.webp',
        ];

        $this->get('/business-cards')
            ->assertOk()
            ->assertInertia(fn (Assert $page): Assert => $page
                ->component('shop/business-cards')
                ->has('content.business_cards_landing_page.sections.shop_by_paper.items', 4)
                ->where('content.business_cards_landing_page.sections.shop_by_paper.items.0.image_url', $paperImages[0])
                ->where('content.business_cards_landing_page.sections.shop_by_paper.items.1.image_url', $paperImages[1])
                ->where('content.business_cards_landing_page.sections.shop_by_paper.items.2.image_url', $paperImages[2])
                ->where('content.business_cards_landing_page.sections.shop_by_paper.items.3.image_url', $paperImages[3]));

        foreach ($paperImages as $image) {
            $this->assertFileExists(public_path(ltrim($image, '/')));
        }
    }

    public function test_business_card_landing_exposes_size_images_in_requested_order(): void
    {
        $sizeImages = [
            '/images/business-cards/size/standard.webp',
            '/images/business-cards/size/square.webp',
            '/images/business-cards/size/square-premium.webp',
        ];

        $this->get('/business-cards')
            ->assertOk()
            ->assertInertia(fn (Assert $page): Assert => $page
                ->component('shop/business-cards')
                ->has('content.business_cards_landing_page.sections.shop_by_size.items', 3)
                ->where('content.business_cards_landing_page.sections.shop_by_size.items.0.image_url', $sizeImages[0])
                ->where('content.business_cards_landing_page.sections.shop_by_size.items.1.image_url', $sizeImages[1])
                ->where('content.business_cards_landing_page.sections.shop_by_size.items.2.image_url', $sizeImages[2]));

        foreach ($sizeImages as $image) {
            $this->assertFileExists(public_path(ltrim($image, '/')));
        }
    }

    public function test_business_card_landing_exposes_the_delivery_image(): void
    {
        $shippingImage = '/images/business-cards/packing/airplane-shipping-promo.webp';

        $this->get('/business-cards')
            ->assertOk()
            ->assertInertia(fn (Assert $page): Assert => $page
                ->component('shop/business-cards')
                ->where(
                    'content.business_cards_landing_page.sections.shop_by_finish.shipping_info.image_url',
                    $shippingImage,
                ));

        $this->assertFileExists(public_path(ltrim($shippingImage, '/')));
    }

    public function test_public_business_card_path_loads_the_existing_product(): void
    {
        $category = ProductCategory::create([
            'name' => 'Cotton Business Cards',
            'slug' => 'cotton-business-cards',
        ]);

        Product::create([
            'name' => 'Basic cotton business card',
            'slug' => 'basic-cotton-business-card',
            'description' => 'Cotton card',
            'price' => 0,
            'product_category_id' => $category->id,
            'is_active' => true,
            'product_config' => [],
        ]);

        $this->get('/business-cards/basic-cotton')
            ->assertOk()
            ->assertInertia(fn (Assert $page): Assert => $page
                ->component('shop/show')
                ->where('product.slug', 'basic-cotton-business-card'));
    }

    public function test_legacy_business_card_path_redirects_to_the_canonical_path(): void
    {
        $category = ProductCategory::create([
            'name' => 'Cotton Business Cards',
            'slug' => 'cotton-business-cards',
        ]);

        Product::create([
            'name' => 'Basic cotton business card',
            'slug' => 'basic-cotton-business-card',
            'description' => 'Cotton card',
            'price' => 0,
            'product_category_id' => $category->id,
            'is_active' => true,
        ]);

        $this->get('/basic-cotton-business-card')
            ->assertStatus(301)
            ->assertRedirect('/business-cards/basic-cotton');
    }
}
