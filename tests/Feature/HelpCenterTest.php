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
            'slug' => 'design-and-print-knowledge',
            'is_active' => false,
        ]);
    }

    public function test_help_page_exposes_the_requested_categories(): void
    {
        $this->seed(HelpCenterSeeder::class);

        $response = $this->get('/help');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('help/index')
            ->has('categories', 3)
            ->where('categories.0.slug', 'getting-started-with-inkpavo')
            ->where('categories.1.slug', 'account-and-orders')
            ->where('categories.2.slug', 'your-designs')
        );
    }

    public function test_help_page_exposes_the_popular_business_card_faqs(): void
    {
        $this->seed(HelpCenterSeeder::class);

        $response = $this->get('/help');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('help/index')
            ->has('faqs', 5)
            ->where('faqs.0.question', 'How quickly can my business cards be delivered?')
            ->where('faqs.1.question', 'Which business card sizes do you offer?')
            ->where('faqs.2.question', 'Why choose InkPavo business cards?')
            ->where('faqs.3.question', 'About design files')
            ->where('faqs.4.question', 'What is the difference between matte, gloss, and soft-touch business cards?')
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

        $response = $this->get('/help/categories/getting-started-with-inkpavo');

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

        $response = $this->get('/help/categories/account-and-orders');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('help/category')
            ->has('articles', 6)
            ->where('articles.0.slug', 'how-to-place-an-order')
            ->where('articles.5.slug', 'what-payment-methods-do-you-accept')
        );
    }
}
