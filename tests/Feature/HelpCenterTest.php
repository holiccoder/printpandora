<?php

namespace Tests\Feature;

use App\Models\HelpCategory;
use Database\Seeders\HelpCenterSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HelpCenterTest extends TestCase
{
    use RefreshDatabase;

    public function test_help_center_seeds_the_requested_categories_in_order(): void
    {
        $this->seed(HelpCenterSeeder::class);

        $this->assertSame(
            [
                'getting-started-with-inkpavo',
                'account-and-orders',
                'your-designs',
                'shipping-and-delivery',
            ],
            HelpCategory::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->pluck('slug')
                ->all(),
        );

        $this->assertDatabaseHas('help_categories', [
            'slug' => 'getting-started-with-inkpavo',
            'name' => 'Getting started with InkPavo',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('help_categories', [
            'slug' => 'account-and-orders',
            'name' => 'Account and orders',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('help_categories', [
            'slug' => 'your-designs',
            'name' => 'Your designs',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('help_categories', [
            'slug' => 'shipping-and-delivery',
            'name' => 'Shipping & Delivery',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('help_categories', [
            'slug' => 'design-and-print-knowledge',
            'is_active' => false,
        ]);
    }

    public function test_help_page_exposes_the_requested_categories(): void
    {
        $this->seed(HelpCenterSeeder::class);

        $response = $this->get('/faq-and-help-center');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('help/index')
            ->has('categories', 4)
            ->where('categories.0.slug', 'getting-started-with-inkpavo')
            ->where('categories.1.slug', 'account-and-orders')
            ->where('categories.2.slug', 'your-designs')
            ->where('categories.3.slug', 'shipping-and-delivery')
        );
    }

    public function test_help_page_exposes_the_popular_business_card_faqs(): void
    {
        $this->seed(HelpCenterSeeder::class);

        $response = $this->get('/faq-and-help-center');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('help/index')
            ->has('faqs', 6)
            ->where('faqs.0.question', 'How quickly can my business cards be delivered?')
            ->where('faqs.1.question', 'Which business card sizes do you offer?')
            ->where('faqs.2.question', 'Why choose InkPavo business cards?')
            ->where('faqs.3.question', 'About design files')
            ->where('faqs.4.question', 'What is the difference between matte, gloss, and soft-touch business cards?')
            ->where('faqs.5.question', '如果印刷有质量问题如何售后')
        );
    }

    public function test_shipping_category_contains_the_delivery_article(): void
    {
        $this->seed(HelpCenterSeeder::class);

        $category = HelpCategory::query()
            ->where('slug', 'shipping-and-delivery')
            ->firstOrFail();

        $this->assertSame(
            ['shipping-and-delivery-options'],
            $category->publishedArticles()->pluck('slug')->all(),
        );

        $response = $this->get('/faq-and-help-center/categories/shipping-and-delivery');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('help/category')
            ->has('articles', 1)
            ->where('articles.0.slug', 'shipping-and-delivery-options')
        );
    }

    public function test_getting_started_category_contains_account_and_referral_articles(): void
    {
        $this->seed(HelpCenterSeeder::class);

        $category = HelpCategory::query()
            ->where('slug', 'getting-started-with-inkpavo')
            ->firstOrFail();

        $this->assertSame(
            [
                'creating-an-inkpavo-account',
                'changing-inkpavo-account-credentials',
                'how-to-refer-a-friend',
                'how-to-make-money-with-referral-program',
            ],
            $category->publishedArticles()->pluck('slug')->all(),
        );

        $response = $this->get('/faq-and-help-center/categories/getting-started-with-inkpavo');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('help/category')
            ->has('articles', 4)
            ->where('articles.0.slug', 'creating-an-inkpavo-account')
            ->where('articles.3.slug', 'how-to-make-money-with-referral-program')
        );
    }

    public function test_account_and_orders_category_contains_ordering_articles(): void
    {
        $this->seed(HelpCenterSeeder::class);

        $category = HelpCategory::query()
            ->where('slug', 'account-and-orders')
            ->firstOrFail();

        $this->assertSame(
            [
                'how-to-place-an-order',
                'how-to-check-order-status-and-delays',
                'print-and-shipping-turnaround',
                'how-to-edit-an-order-and-reorder',
                'how-to-order-product-samples',
                'what-payment-methods-do-you-accept',
            ],
            $category->publishedArticles()->pluck('slug')->all(),
        );

        $response = $this->get('/faq-and-help-center/categories/account-and-orders');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('help/category')
            ->has('articles', 6)
            ->where('articles.0.slug', 'how-to-place-an-order')
            ->where('articles.5.slug', 'what-payment-methods-do-you-accept')
        );
    }
}
