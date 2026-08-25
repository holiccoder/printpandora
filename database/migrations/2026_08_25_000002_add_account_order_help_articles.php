<?php

use App\Models\HelpArticle;
use App\Models\HelpCategory;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $category = HelpCategory::updateOrCreate(
            ['slug' => 'account-and-orders'],
            [
                'name' => 'Account and orders',
                'description' => 'Manage your account, payments, order status, shipping, and delivery details.',
                'icon' => 'shopping-bag',
                'sort_order' => 2,
                'is_active' => true,
            ],
        );

        $articles = require database_path('seeders/data/help_account_orders.php');

        foreach ($articles as $index => $article) {
            HelpArticle::updateOrCreate(
                ['slug' => $article['slug']],
                [
                    'category_id' => $category->id,
                    'title' => $article['title'],
                    'body' => $article['body'],
                    'excerpt' => $article['excerpt'] ?? strip_tags($article['body']),
                    'is_published' => true,
                    'published_at' => now()->subDays(count($articles) - $index),
                    'sort_order' => $index,
                ],
            );
        }
    }

    public function down(): void
    {
        $articles = require database_path('seeders/data/help_account_orders.php');

        HelpArticle::whereIn('slug', array_column($articles, 'slug'))->delete();
    }
};
