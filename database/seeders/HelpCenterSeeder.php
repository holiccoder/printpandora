<?php

namespace Database\Seeders;

use App\Models\Faq;
use App\Models\HelpArticle;
use App\Models\HelpCategory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class HelpCenterSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $categories = [
            [
                'slug' => 'getting-started-with-inkpavo',
                'name' => 'Getting started with InkPavo',
                'description' => 'Learn how InkPavo works, choose the right products, and place your first order.',
                'icon' => 'document-text',
                'sort_order' => 1,
            ],
            [
                'slug' => 'account-and-orders',
                'name' => 'Account and orders',
                'description' => 'Manage your account, payments, order status, shipping, and delivery details.',
                'icon' => 'shopping-bag',
                'sort_order' => 2,
            ],
            [
                'slug' => 'your-designs',
                'name' => 'Your designs',
                'description' => 'Upload, prepare, proof, and manage artwork for your print projects.',
                'icon' => 'palette',
                'sort_order' => 3,
            ],
            [
                'slug' => 'design-and-print-knowledge',
                'name' => 'Design and Print knowledge',
                'description' => 'File formats, bleed, templates, special finishes, and production-ready artwork guidelines.',
                'icon' => 'palette',
                'sort_order' => 4,
                'is_active' => false,
            ],
        ];

        foreach ($categories as $definition) {
            HelpCategory::updateOrCreate(
                ['slug' => $definition['slug']],
                [
                    'name' => $definition['name'],
                    'description' => $definition['description'],
                    'icon' => $definition['icon'],
                    'sort_order' => $definition['sort_order'],
                    'is_active' => $definition['is_active'] ?? true,
                ],
            );
        }

        $upsertArticle = function (HelpCategory $category, array $data, int $index, int $total): void {
            HelpArticle::updateOrCreate(
                ['slug' => $data['slug']],
                [
                    'category_id' => $category->id,
                    'title' => $data['title'],
                    'body' => $data['body'],
                    'excerpt' => $data['excerpt'] ?? \Illuminate\Support\Str::limit(strip_tags($data['body']), 200),
                    'is_published' => true,
                    'published_at' => now()->subDays($total - $index),
                    'sort_order' => $index,
                ],
            );
        };

        $gettingStartedCategory = HelpCategory::where('slug', 'getting-started-with-inkpavo')->firstOrFail();
        $gettingStartedArticles = require database_path('seeders/data/help_getting_started.php');

        foreach ($gettingStartedArticles as $index => $data) {
            $upsertArticle($gettingStartedCategory, $data, $index, count($gettingStartedArticles));
        }

        $accountOrdersCategory = HelpCategory::where('slug', 'account-and-orders')->firstOrFail();
        $accountOrdersArticles = require database_path('seeders/data/help_account_orders.php');

        foreach ($accountOrdersArticles as $index => $data) {
            $upsertArticle($accountOrdersCategory, $data, $index, count($accountOrdersArticles));
        }

        $category = HelpCategory::where('slug', 'design-and-print-knowledge')->firstOrFail();

        $articlesPath = storage_path('from-tool/help-center-articles.php');

        if (file_exists($articlesPath)) {
            $articles = require $articlesPath;

            foreach ($articles as $index => $data) {
                $upsertArticle($category, $data, $index, count($articles));
            }
        }

        $legacyFaqQuestions = [
            'What file formats do you accept?',
            'How long does production take?',
            'What is your minimum order quantity?',
            'Do you offer design services?',
            'Can I track my order?',
            'What is your refund policy?',
        ];

        Faq::query()
            ->where('category_id', $category->id)
            ->whereIn('question', $legacyFaqQuestions)
            ->delete();

        $faqs = require database_path('seeders/data/help_popular_faqs.php');

        foreach ($faqs as $index => $faq) {
            Faq::updateOrCreate(
                [
                    'category_id' => $category->id,
                    'question' => $faq['question'],
                ],
                [
                    'answer' => $faq['answer'],
                    'sort_order' => $index,
                    'is_published' => true,
                ],
            );
        }
    }
}
