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
                    'is_active' => true,
                ],
            );
        }

        $category = HelpCategory::where('slug', 'design-and-print-knowledge')->firstOrFail();

        $articlesPath = storage_path('from-tool/help-center-articles.php');

        if (file_exists($articlesPath)) {
            $articles = require $articlesPath;

            foreach ($articles as $index => $data) {
                HelpArticle::updateOrCreate(
                    ['slug' => $data['slug']],
                    [
                        'category_id' => $category->id,
                        'title' => $data['title'],
                        'body' => $data['body'],
                        'excerpt' => strip_tags($data['body']),
                        'is_published' => true,
                        'published_at' => now()->subDays(count($articles) - $index),
                        'sort_order' => $index,
                    ],
                );
            }
        }

        $faqs = [
            [
                'question' => 'What file formats do you accept?',
                'answer' => '<p>We recommend print-ready PDFs with bleed. We also accept AI, EPS, SVG, and high-resolution PNG/JPG files.</p>',
            ],
            [
                'question' => 'How long does production take?',
                'answer' => '<p>Most orders are produced within 1–2 business days after artwork approval. Shipping time depends on your selected delivery method.</p>',
            ],
            [
                'question' => 'What is your minimum order quantity?',
                'answer' => '<p>Minimum order quantities vary by product. Many business card products start at 50 pieces; gang-run products typically start at 200 copies per design.</p>',
            ],
            [
                'question' => 'Do you offer design services?',
                'answer' => '<p>Yes. We offer free vector typesetting for print customers, template layouts, and custom original design. Visit our Business Card Design Service page to get started.</p>',
            ],
            [
                'question' => 'Can I track my order?',
                'answer' => '<p>Once your order ships, you will receive a tracking link by email. You can also view order status in your dashboard.</p>',
            ],
            [
                'question' => 'What is your refund policy?',
                'answer' => '<p>If you\'re not satisfied with the print quality due to a production error, contact us within 14 days. We\'ll reprint or refund eligible orders.</p>',
            ],
        ];

        foreach ($faqs as $index => $faq) {
            Faq::updateOrCreate(
                ['question' => $faq['question']],
                [
                    'category_id' => $category->id,
                    'answer' => $faq['answer'],
                    'sort_order' => $index,
                    'is_published' => true,
                ],
            );
        }
    }
}
