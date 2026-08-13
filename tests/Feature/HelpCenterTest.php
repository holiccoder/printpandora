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
                'design-and-print-knowledge',
            ],
            HelpCategory::query()
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
    }

    public function test_help_page_exposes_the_requested_categories(): void
    {
        $this->seed(HelpCenterSeeder::class);

        $response = $this->get('/help');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('help/index')
            ->has('categories', 4)
            ->where('categories.0.slug', 'getting-started-with-inkpavo')
            ->where('categories.1.slug', 'account-and-orders')
            ->where('categories.2.slug', 'your-designs')
        );
    }
}
