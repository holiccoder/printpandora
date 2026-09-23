<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Category;
use App\Models\Post;
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

    public function test_business_card_landing_exposes_the_latest_three_blog_posts(): void
    {
        $admin = Admin::factory()->create();
        $category = Category::query()->create([
            'name' => 'Design Tips',
            'slug' => 'design-tips',
        ]);

        $createPost = function (string $suffix, int $secondsAgo) use ($admin, $category): Post {
            return Post::query()->create([
                'title' => "Business card article {$suffix}",
                'slug' => "business-card-article-{$suffix}",
                'body' => '<p>Article body.</p>',
                'featured_image' => null,
                'category_id' => $category->id,
                'admin_id' => $admin->id,
                'is_published' => true,
                'published_at' => now()->subSeconds($secondsAgo),
            ]);
        };

        $latest = $createPost('latest', 1);
        $second = $createPost('second', 2);
        $third = $createPost('third', 3);
        $createPost('older', 4);

        $this->get('/business-cards')
            ->assertOk()
            ->assertInertia(fn (Assert $page): Assert => $page
                ->component('shop/business-cards')
                ->has('blogPosts', 3)
                ->where('blogPosts.0.id', $latest->id)
                ->where('blogPosts.1.id', $second->id)
                ->where('blogPosts.2.id', $third->id));
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

    public function test_renamed_business_cards_use_new_paths_and_old_paths_are_not_available(): void
    {
        $category = ProductCategory::create([
            'name' => 'Business Cards',
            'slug' => 'business-cards',
        ]);

        $products = [
            ['name' => 'Super Standard Business Cards', 'slug' => 'super-standard-business-cards', 'path' => '/business-cards/super-standard'],
            ['name' => 'Super Luxe Business Cards', 'slug' => 'super-luxe-business-cards', 'path' => '/business-cards/super-luxe'],
            ['name' => 'Standard Quality Business Cards', 'slug' => 'standard-quality-business-cards', 'path' => '/business-cards/standard-quality'],
            ['name' => 'Solid Quality Business Cards', 'slug' => 'solid-quality-business-cards', 'path' => '/business-cards/solid-quality'],
        ];

        foreach ($products as $product) {
            Product::create([
                'name' => $product['name'],
                'slug' => $product['slug'],
                'description' => 'Business card',
                'price' => 0,
                'product_category_id' => $category->id,
                'is_active' => true,
                'product_config' => [],
            ]);

            $this->get($product['path'])
                ->assertOk()
                ->assertInertia(fn (Assert $page): Assert => $page
                    ->component('shop/show')
                    ->where('product.slug', $product['slug'])
                    ->where('product.name', $product['name']));
        }

        foreach ([
            '/business-cards/super',
            '/business-cards/luxe',
            '/business-cards/classic-quality',
            '/business-cards/classic-solid',
            '/super-business-cards',
            '/luxe-business-cards',
            '/classic-quality-business-cards',
            '/classic-solid-business-cards',
        ] as $oldPath) {
            $this->get($oldPath)->assertNotFound();
        }
    }

    public function test_business_card_navigation_uses_the_requested_group_order_and_children(): void
    {
        $this->get('/business-cards')
            ->assertOk()
            ->assertInertia(fn (Assert $page): Assert => $page
                ->has('content.global_chrome.header.business_cards_mega_menu.link_groups.1.links', 6)
                ->where('content.global_chrome.header.business_cards_mega_menu.link_groups.1.links.0.label', 'Cotton Business Cards')
                ->where('content.global_chrome.header.business_cards_mega_menu.link_groups.1.links.1.label', 'Super Business Cards')
                ->where('content.global_chrome.header.business_cards_mega_menu.link_groups.1.links.2.label', 'Quality Business Cards')
                ->where('content.global_chrome.header.business_cards_mega_menu.link_groups.1.links.3.label', 'Metal Business Cards')
                ->where('content.global_chrome.header.business_cards_mega_menu.link_groups.1.links.4.label', 'PVC Business Cards')
                ->where('content.global_chrome.header.business_cards_mega_menu.link_groups.1.links.5.label', 'Classic Business Cards')
                ->where('content.global_chrome.header.business_cards_mega_menu.link_groups.1.links.1.children.0.label', 'Super Standard Business Cards')
                ->where('content.global_chrome.header.business_cards_mega_menu.link_groups.1.links.1.children.0.href', '/business-cards/super-standard')
                ->where('content.global_chrome.header.business_cards_mega_menu.link_groups.1.links.1.children.1.label', 'Super Luxe Business Cards')
                ->where('content.global_chrome.header.business_cards_mega_menu.link_groups.1.links.1.children.1.href', '/business-cards/super-luxe')
                ->where('content.global_chrome.header.business_cards_mega_menu.link_groups.1.links.2.children.0.label', 'Standard Quality Business Cards')
                ->where('content.global_chrome.header.business_cards_mega_menu.link_groups.1.links.2.children.0.href', '/business-cards/standard-quality')
                ->where('content.global_chrome.header.business_cards_mega_menu.link_groups.1.links.2.children.1.label', 'Solid Quality Business Cards')
                ->where('content.global_chrome.header.business_cards_mega_menu.link_groups.1.links.2.children.1.href', '/business-cards/solid-quality'));
    }
}
