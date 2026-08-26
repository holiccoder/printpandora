<?php

namespace Tests\Feature;

use App\Filament\Resources\ShowcaseResource;
use App\Models\Admin;
use App\Models\Showcase;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ShowcasesTest extends TestCase
{
    use RefreshDatabase;

    public function test_imported_showcases_are_available_to_the_frontend(): void
    {
        $showcase = Showcase::query()->first();

        $this->assertNotNull($showcase);
        $this->assertStringStartsWith('/images/showcases/', $showcase->image_url);

        $this->get('/showcases')
            ->assertOk()
            ->assertInertia(fn (Assert $page): Assert => $page
                ->component('showcases')
                ->where('showcases.data.0.id', $showcase->id)
                ->missing('showcases.data.0.image_name')
                ->where('showcases.data.0.image_url', $showcase->image_url));
    }

    public function test_showcases_are_paginated_at_sixteen_per_page(): void
    {
        Showcase::query()->delete();

        $showcases = collect(range(1, 17))->map(
            fn (int $index): Showcase => Showcase::create([
                'image_name' => "showcase-{$index}",
                'image_url' => "/images/showcases/showcase-{$index}.webp",
            ]),
        );

        $this->get('/showcases')
            ->assertOk()
            ->assertInertia(fn (Assert $page): Assert => $page
                ->component('showcases')
                ->has('showcases.data', 16)
                ->where('showcases.current_page', 1)
                ->where('showcases.last_page', 2)
                ->where('showcases.data.0.id', $showcases->first()->id));

        $this->get('/showcases?page=2')
            ->assertOk()
            ->assertInertia(fn (Assert $page): Assert => $page
                ->component('showcases')
                ->has('showcases.data', 1)
                ->where('showcases.current_page', 2)
                ->where('showcases.last_page', 2)
                ->where('showcases.data.0.id', $showcases->last()->id));
    }

    public function test_showcase_resource_is_registered_in_the_admin_panel(): void
    {
        Filament::setCurrentPanel('admin');
        Filament::bootCurrentPanel();
        $this->actingAs(Admin::factory()->create(), 'admin');

        $this->assertTrue(Route::has('filament.admin.resources.showcases.index'));

        $this->get(ShowcaseResource::getUrl())
            ->assertOk();
    }
}
