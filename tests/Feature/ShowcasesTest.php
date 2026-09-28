<?php

namespace Tests\Feature;

use App\Filament\Resources\ShowcaseCategoryResource;
use App\Filament\Resources\ShowcaseResource;
use App\Filament\Resources\ShowcaseResource\Pages\CreateShowcase;
use App\Filament\Resources\ShowcaseResource\Pages\EditShowcase;
use App\Jobs\GenerateProductImageWebp;
use App\Models\Admin;
use App\Models\Showcase;
use App\Models\ShowcaseCategory;
use App\Services\ProductImageResolver;
use App\Support\ProductImagePolicy;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Livewire\Livewire;
use Tests\TestCase;

class ShowcasesTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_showcase_categories_are_available_in_requested_order(): void
    {
        $this->get('/showcases')
            ->assertOk()
            ->assertInertia(fn (Assert $page): Assert => $page
                ->has('categories', 7)
                ->where('categories.0.name', 'Cotton Business Cards')
                ->where('categories.0.slug', 'cotton-paper-business-cards')
                ->where('categories.1.name', 'Metal Business Cards')
                ->where('categories.1.slug', 'metal-business-cards')
                ->where('categories.2.name', 'Stickers & Labels')
                ->where('categories.2.slug', 'stickers-labels')
                ->where('categories.3.name', 'Brochures')
                ->where('categories.3.slug', 'folded-brochures')
                ->where('categories.4.name', 'Cards & Postcards')
                ->where('categories.4.slug', 'cards-and-postcards')
                ->where('categories.5.name', 'Paper Stocks')
                ->where('categories.5.slug', 'paper-stocks')
                ->where('categories.6.name', 'Finishes')
                ->where('categories.6.slug', 'finishing-techniques'));

        $this->assertDatabaseMissing('showcase_categories', [
            'slug' => 'premium-business-cards',
        ]);
    }

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

    public function test_current_showcases_belong_to_the_cotton_business_cards_category(): void
    {
        $category = ShowcaseCategory::query()
            ->where('slug', 'cotton-paper-business-cards')
            ->firstOrFail();

        $this->assertGreaterThan(0, Showcase::query()->count());
        $this->assertSame(
            Showcase::query()->count(),
            Showcase::query()->where('category_id', $category->id)->count(),
        );
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

    public function test_showcases_can_be_filtered_by_category_slug(): void
    {
        $category = ShowcaseCategory::create([
            'name' => 'Business cards',
            'slug' => 'business-cards',
        ]);
        $matchingShowcase = Showcase::create([
            'image_name' => 'business-card-showcase',
            'image_url' => '/images/showcases/business-card-showcase.webp',
            'category_id' => $category->id,
        ]);
        Showcase::create([
            'image_name' => 'uncategorized-showcase',
            'image_url' => '/images/showcases/uncategorized-showcase.webp',
        ]);

        $this->get('/showcases?category=business-cards')
            ->assertOk()
            ->assertInertia(fn (Assert $page): Assert => $page
                ->component('showcases')
                ->where('active_category', 'business-cards')
                ->where('categories.0.slug', 'business-cards')
                ->has('showcases.data', 1)
                ->where('showcases.data.0.id', $matchingShowcase->id));
    }

    public function test_admin_can_upload_a_showcase_image(): void
    {
        Storage::fake('public');
        Queue::fake();

        Filament::setCurrentPanel('admin');
        Filament::bootCurrentPanel();
        $this->actingAs(Admin::factory()->create(), 'admin');

        $category = ShowcaseCategory::create([
            'name' => 'Uploaded showcases',
            'slug' => 'uploaded-showcases',
        ]);

        Livewire::test(CreateShowcase::class)
            ->fillForm([
                'image_name' => 'Uploaded showcase',
                'category_id' => $category->getKey(),
                'image_url' => UploadedFile::fake()->image('showcase.png', 400, 200),
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $showcase = Showcase::query()->latest('id')->firstOrFail();
        $path = $showcase->getRawOriginal('image_url');

        $this->assertIsString($path);
        $this->assertMatchesRegularExpression(
            '~^'.preg_quote(ProductImagePolicy::ORIGINALS_DIRECTORY, '~').'/showcases/[0-9A-Z]{26}\.png$~',
            $path,
        );
        Storage::disk('public')->assertExists($path);
        Queue::assertPushed(
            GenerateProductImageWebp::class,
            fn (GenerateProductImageWebp $job): bool => $job->sourcePath === $path
                && $job->webpPath === app(ProductImageResolver::class)->derivativePath($path),
        );

        $this->get('/showcases?category=uploaded-showcases')
            ->assertOk()
            ->assertInertia(fn (Assert $page): Assert => $page
                ->where('showcases.data.0.id', $showcase->id)
                ->where('showcases.data.0.image_url', '/storage/'.$path));
    }

    public function test_existing_showcase_images_are_previewable_in_the_admin_form(): void
    {
        Storage::fake('public');

        $showcase = Showcase::create([
            'image_name' => 'Legacy showcase',
            'image_url' => '/images/showcases/legacy-showcase.webp',
        ]);

        Filament::setCurrentPanel('admin');
        Filament::bootCurrentPanel();
        $this->actingAs(Admin::factory()->create(), 'admin');

        $component = Livewire::test(EditShowcase::class, [
            'record' => $showcase->getRouteKey(),
        ]);
        $page = $component->instance();
        $this->assertInstanceOf(EditShowcase::class, $page);

        $form = $page->getSchema('form');
        $this->assertNotNull($form);

        $field = $form->getComponentByStatePath('image_url');
        $this->assertInstanceOf(FileUpload::class, $field);

        $preview = array_values($field->getUploadedFiles() ?? [])[0] ?? null;

        $this->assertSame('/images/showcases/legacy-showcase.webp', data_get($preview, 'url'));
    }

    public function test_showcase_resource_is_registered_in_the_admin_panel(): void
    {
        Filament::setCurrentPanel('admin');
        Filament::bootCurrentPanel();
        $this->actingAs(Admin::factory()->create(), 'admin');

        $this->assertTrue(Route::has('filament.admin.resources.showcases.index'));
        $this->assertTrue(Route::has('filament.admin.resources.showcase-categories.index'));

        $this->get(ShowcaseResource::getUrl())
            ->assertOk();

        $this->get(ShowcaseCategoryResource::getUrl())
            ->assertOk();
    }
}
