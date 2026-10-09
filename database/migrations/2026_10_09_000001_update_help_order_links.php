<?php

use App\Models\HelpArticle;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    private const ARTICLE_SLUGS = [
        'how-to-place-an-order',
        'how-to-check-order-status-and-delays',
        'how-to-edit-an-order-and-reorder',
    ];

    public function up(): void
    {
        $this->replaceOrderLinks('/orders', '/dashboard/orders');
    }

    public function down(): void
    {
        $this->replaceOrderLinks('/dashboard/orders', '/orders');
    }

    private function replaceOrderLinks(string $from, string $to): void
    {
        HelpArticle::query()
            ->whereIn('slug', self::ARTICLE_SLUGS)
            ->get()
            ->each(function (HelpArticle $article) use ($from, $to): void {
                $article->update([
                    'body' => str_replace(
                        'href="'.$from.'"',
                        'href="'.$to.'"',
                        $article->body,
                    ),
                ]);
            });
    }
};
