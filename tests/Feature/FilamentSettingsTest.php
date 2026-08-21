<?php

namespace Tests\Feature;

use App\Filament\Pages\Settings as SettingsPage;
use App\Models\Admin;
use App\Models\SiteSetting;
use App\Services\SiteSettingsService;
use App\Support\HardcodedContent;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FilamentSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('admin');
        Filament::bootCurrentPanel();
        $this->actingAs(Admin::factory()->create(), 'admin');
        app(HardcodedContent::class)->forget();
    }

    public function test_settings_page_is_registered_and_hydrates_homepage_content(): void
    {
        $this->get(route('filament.admin.pages.settings'))
            ->assertOk();

        $component = Livewire::test(SettingsPage::class);
        $slides = data_get($component->get('data'), 'homepage.hero_carousel.slides', []);

        $this->assertSame('Welcome', data_get($component->get('data'), 'homepage.seo.page_title'));
        $this->assertSame(
            'InkPavo - your go-to solution for printing, customization, and on-demand production.',
            data_get($component->get('data'), 'homepage.seo.page_description'),
        );
        $this->assertCount(10, $slides);
        $this->assertSame(
            "MADE TO BE\nREMEMBERED",
            data_get($slides, array_key_first($slides).'.headline'),
        );
        $this->assertStringContainsString(
            '/images/home/homepage-carousel-01.webp',
            (string) json_encode(
                data_get($slides, array_key_first($slides).'.image_url'),
                JSON_UNESCAPED_SLASHES,
            ),
        );
        $this->assertStringContainsString(
            '/images/home/homepage-carousel-10.webp',
            (string) json_encode(
                data_get($slides, array_key_last($slides).'.image_url'),
                JSON_UNESCAPED_SLASHES,
            ),
        );
    }

    public function test_homepage_settings_are_saved_and_override_the_storefront_content(): void
    {
        $popularProducts = app(HardcodedContent::class)->section('home_page.popular_products');
        $component = Livewire::test(SettingsPage::class);
        $slides = data_get($component->get('data'), 'homepage.hero_carousel.slides', []);
        $slideKey = (string) array_key_first($slides);

        $component
            ->set('data.homepage.seo.page_title', 'Print better with InkPavo')
            ->set('data.homepage.seo.page_description', 'A custom homepage description for search engines.')
            ->set("data.homepage.hero_carousel.slides.{$slideKey}.headline", 'A SAVED HOMEPAGE')
            ->set("data.homepage.hero_carousel.slides.{$slideKey}.cta_text", 'SHOP NOW')
            ->set("data.homepage.hero_carousel.slides.{$slideKey}.cta_href", '/shop')
            ->call('save')
            ->assertHasNoErrors();

        $stored = SiteSetting::query()->where('key', SiteSettingsService::KEY)->value('value');

        $this->assertIsArray($stored);
        $this->assertSame(
            'Print better with InkPavo',
            data_get($stored, 'homepage.seo.page_title'),
        );
        $this->assertSame(
            'A SAVED HOMEPAGE',
            data_get($stored, 'homepage.hero_carousel.slides.0.headline'),
        );
        $this->assertSame(
            'card',
            data_get($stored, 'homepage.hero_carousel.slides.0.features.0.icon'),
        );

        $content = app(HardcodedContent::class);
        $this->assertSame('Print better with InkPavo', $content->section('home_page.seo.page_title'));
        $this->assertSame('A SAVED HOMEPAGE', $content->section('home_page.hero_carousel.slides.0.headline'));
        $this->assertSame($popularProducts, $content->section('home_page.popular_products'));
    }

    public function test_saved_slide_lists_replace_the_fallback_list_without_affecting_other_homepage_sections(): void
    {
        $service = app(SiteSettingsService::class);
        $service->saveSections([
            'homepage' => [
                'hero_carousel' => [
                    'slides' => [
                        [
                            'headline' => 'Only saved slide',
                            'subheadline' => 'Saved description',
                            'cta_text' => 'Read more',
                            'cta_href' => '/about',
                            'image_url' => '/images/home/home-banner1.png',
                            'alt' => 'Saved banner',
                        ],
                    ],
                ],
            ],
        ]);

        app(HardcodedContent::class)->forget();

        $content = app(HardcodedContent::class);

        $this->assertCount(1, $content->section('home_page.hero_carousel.slides'));
        $this->assertSame('Only saved slide', $content->section('home_page.hero_carousel.slides.0.headline'));
        $this->assertNotEmpty($content->section('home_page.popular_products.cards'));
    }
}
