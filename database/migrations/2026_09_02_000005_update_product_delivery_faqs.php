<?php

use App\Models\Product;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    private const DELIVERY_FAQ_ANSWER = 'Standard shipping takes 7 - 12 business days. Express shipping takes 2 - 5 business days.';

    public function up(): void
    {
        Product::query()
            ->whereNotNull('product_config')
            ->eachById(function (Product $product): void {
                $config = is_array($product->product_config) ? $product->product_config : [];
                $faqs = is_array($config['faq'] ?? null) ? $config['faq'] : [];
                $changed = false;

                foreach ($faqs as &$faq) {
                    if (! is_array($faq) || ($faq['question'] ?? null) !== 'How long does delivery take?') {
                        continue;
                    }

                    if (($faq['answer'] ?? null) !== self::DELIVERY_FAQ_ANSWER) {
                        $faq['answer'] = self::DELIVERY_FAQ_ANSWER;
                        $changed = true;
                    }
                }

                unset($faq);

                if ($changed) {
                    $config['faq'] = array_values($faqs);
                    $product->forceFill(['product_config' => $config])->saveQuietly();
                }
            });
    }

    public function down(): void
    {
        // The previous FAQ wording is not restored because it was incorrect.
    }
};
