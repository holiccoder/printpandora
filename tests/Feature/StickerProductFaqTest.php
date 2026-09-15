<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Services\ProductConfigurationService;
use App\Support\StickerProductCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StickerProductFaqTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_sticker_product_exposes_the_shared_product_faq(): void
    {
        foreach (StickerProductCatalog::slugs() as $slug) {
            $product = Product::where('slug', $slug)->firstOrFail();
            $options = app(ProductConfigurationService::class)->storefrontOptions($product);

            $this->assertNotNull($options, $slug);
            $this->assertSame(
                'Frequently asked questions',
                data_get($options, 'detail_sections.faq.heading'),
                $slug,
            );
            $this->assertSame(
                StickerProductCatalog::faq(),
                data_get($options, 'detail_sections.faq.items'),
                $slug,
            );
        }
    }
}
