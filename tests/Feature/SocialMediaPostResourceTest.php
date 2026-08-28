<?php

namespace Tests\Feature;

use App\Filament\Resources\SocialMediaPostResource\Pages\CreateSocialMediaPost;
use App\Models\Admin;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SocialMediaPostResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('admin');
        Filament::bootCurrentPanel();
        $this->actingAs(Admin::factory()->create(), 'admin');
    }

    public function test_create_form_contains_a_panel_for_each_social_media_platform(): void
    {
        $component = Livewire::test(CreateSocialMediaPost::class);
        $form = $component->instance()->getSchema('form');

        $this->assertNotNull($form);
        $this->assertNotNull($form->getComponentByStatePath('content'));

        foreach (['facebook', 'pinterest', 'instagram', 'x', 'youtube', 'linkedin'] as $platform) {
            $this->assertNotNull(
                $form->getComponentByStatePath("platform_contents.{$platform}"),
                "Missing publishing panel for {$platform}.",
            );
        }
    }
}
