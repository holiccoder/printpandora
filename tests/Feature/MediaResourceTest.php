<?php

namespace Tests\Feature;

use App\Filament\Resources\MediaResource\Pages\CreateMedia;
use App\Filament\Resources\MediaResource\Pages\EditMedia;
use App\Filament\Resources\MediaResource\Pages\ListMedia;
use App\Models\Admin;
use App\Models\Media;
use App\Models\Product;
use App\Models\ProductCategory;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class MediaResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('admin');
        Filament::bootCurrentPanel();

        $this->actingAs(Admin::factory()->create(), 'admin');
    }

    public function test_can_list_media(): void
    {
        $category = ProductCategory::create([
            'name' => 'Business Cards',
            'slug' => 'business-cards',
        ]);

        $product = Product::create([
            'name' => 'Test Business Cards',
            'slug' => 'test-business-cards',
            'product_category_id' => $category->id,
        ]);

        Storage::fake('public');

        // Add a media item
        $media = $product->addMedia(UploadedFile::fake()->image('image1.png'))
            ->toMediaCollection('product-galleries');

        Livewire::test(ListMedia::class)
            ->assertCanSeeTableRecords([$media])
            ->assertTableColumnExists('url')
            ->assertTableColumnExists('name')
            ->assertTableColumnExists('file_name');
    }

    public function test_can_create_media(): void
    {
        Storage::fake('public');

        $category = ProductCategory::create([
            'name' => 'Business Cards',
            'slug' => 'business-cards',
        ]);

        $product = Product::create([
            'name' => 'Test Business Cards',
            'slug' => 'test-business-cards',
            'product_category_id' => $category->id,
        ]);

        Livewire::test(CreateMedia::class)
            ->fillForm([
                'name' => 'Uploaded Image Name',
                'collection_name' => 'product-galleries',
                'model_type' => Product::class,
                'model_id' => $product->id,
                'file' => [
                    UploadedFile::fake()->image('new-image.png')
                ],
                'custom_properties' => ['foo' => 'bar'],
                'order_column' => 5,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('media', [
            'name' => 'Uploaded Image Name',
            'collection_name' => 'product-galleries',
            'model_type' => Product::class,
            'model_id' => $product->id,
            'order_column' => 5,
        ]);

        $media = Media::first();
        $this->assertSame(['foo' => 'bar'], $media->custom_properties);
    }

    public function test_can_edit_media(): void
    {
        Storage::fake('public');

        $category = ProductCategory::create([
            'name' => 'Business Cards',
            'slug' => 'business-cards',
        ]);

        $product = Product::create([
            'name' => 'Test Business Cards',
            'slug' => 'test-business-cards',
            'product_category_id' => $category->id,
        ]);

        $media = $product->addMedia(UploadedFile::fake()->image('original.png'))
            ->toMediaCollection('product-galleries');

        Livewire::test(EditMedia::class, [
            'record' => $media->getKey(),
        ])
            ->fillForm([
                'name' => 'Updated Name',
                'custom_properties' => ['new_key' => 'new_val'],
                'order_column' => 10,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $media->refresh();
        $this->assertSame('Updated Name', $media->name);
        $this->assertSame(['new_key' => 'new_val'], $media->custom_properties);
        $this->assertSame(10, $media->order_column);
    }

    public function test_can_delete_media(): void
    {
        Storage::fake('public');

        $category = ProductCategory::create([
            'name' => 'Business Cards',
            'slug' => 'business-cards',
        ]);

        $product = Product::create([
            'name' => 'Test Business Cards',
            'slug' => 'test-business-cards',
            'product_category_id' => $category->id,
        ]);

        $media = $product->addMedia(UploadedFile::fake()->image('to-delete.png'))
            ->toMediaCollection('product-galleries');

        $this->assertDatabaseHas('media', ['id' => $media->id]);

        Livewire::test(EditMedia::class, [
            'record' => $media->getKey(),
        ])
            ->callAction('delete');

        $this->assertDatabaseMissing('media', ['id' => $media->id]);
    }
}
