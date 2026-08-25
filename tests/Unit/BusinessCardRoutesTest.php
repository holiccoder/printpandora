<?php

namespace Tests\Unit;

use App\Support\BusinessCardRoutes;
use Tests\TestCase;

class BusinessCardRoutesTest extends TestCase
{
    public function test_business_card_products_have_the_requested_public_paths(): void
    {
        $routes = [
            'basic-cotton-business-card' => '/business-cards/basic-cotton',
            'classic-cotton-business-card' => '/business-cards/classic-cotton',
            'premium-cotton-business-card' => '/business-cards/premium-cotton',
            'luxe-cotton-business-card' => '/business-cards/luxe-cotton',
            'grand-cotton-business-card' => '/business-cards/grand-cotton',
            'basic-pvc-card' => '/business-cards/basic-pvc',
            'standard-pvc-card' => '/business-cards/standard-pvc',
            'premium-pvc-card' => '/business-cards/premium-pvc',
            'classic-metal-business-cards' => '/business-cards/classic-metal',
            'premium-metal-business-cards' => '/business-cards/premium-metal',
            'luxe-metal-business-cards' => '/business-cards/luxe-metal',
            'classic-standard-business-cards' => '/business-cards/classic-standard',
            'classic-special-business-cards' => '/business-cards/classic-special',
            'classic-quality-business-cards' => '/business-cards/classic-quality',
            'classic-solid-business-cards' => '/business-cards/classic-solid',
        ];

        foreach ($routes as $productSlug => $path) {
            $segment = basename($path);

            $this->assertSame($path, BusinessCardRoutes::pathForProductSlug($productSlug));
            $this->assertSame($productSlug, BusinessCardRoutes::productSlugForSegment($segment));
        }
    }
}
