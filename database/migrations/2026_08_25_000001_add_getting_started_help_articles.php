<?php

use App\Models\HelpArticle;
use App\Models\HelpCategory;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $category = HelpCategory::updateOrCreate(
            ['slug' => 'getting-started-with-inkpavo'],
            [
                'name' => 'Getting started with InkPavo',
                'description' => 'Learn how InkPavo works, choose the right products, and place your first order.',
                'icon' => 'document-text',
                'sort_order' => 1,
                'is_active' => true,
            ],
        );

        $articles = require database_path('seeders/data/help_getting_started.php');

        foreach ($articles as $index => $article) {
            HelpArticle::updateOrCreate(
                ['slug' => $article['slug']],
                [
                    'category_id' => $category->id,
                    'title' => $article['title'],
                    'body' => $article['body'],
                    'excerpt' => $article['excerpt'],
                    'is_published' => true,
                    'published_at' => now()->subDays(count($articles) - $index),
                    'sort_order' => $index,
                ],
            );
        }
    }

    public function down(): void
    {
        $articles = require database_path('seeders/data/help_getting_started.php');

        HelpArticle::whereIn('slug', array_column($articles, 'slug'))->delete();
    }
};
