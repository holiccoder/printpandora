<?php

namespace Tests\Feature;

use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class StaticPageRoutesTest extends TestCase
{
    public function test_static_pages_use_the_requested_public_slugs(): void
    {
        $pages = [
            '/contact-us' => 'contact',
            '/about-inkpavo' => 'about',
            '/privacy-policy' => 'privacy',
            '/terms-and-conditions' => 'terms',
            '/shipping-and-cost-calculator' => 'shipping-calculator',
            '/shipping-policy' => 'shipping',
        ];

        foreach ($pages as $path => $component) {
            $this->get($path)
                ->assertOk()
                ->assertInertia(fn (Assert $page): Assert => $page->component($component));
        }
    }

    public function test_help_center_uses_the_requested_public_slug(): void
    {
        $this->assertSame(
            'faq-and-help-center',
            $this->app['router']->getRoutes()->getByName('help')->uri(),
        );
    }

    public function test_sitemap_is_served_as_a_direct_xml_response(): void
    {
        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml')
            ->assertSee('<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"', false);
    }
}
