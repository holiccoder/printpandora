<?php

namespace Tests\Feature;

use App\Filament\Pages\MediaLibrary as MediaLibraryPage;
use App\Filament\Resources\PostResource\Pages\CreatePost;
use App\Jobs\GenerateProductImageWebp;
use App\Jobs\PerformProductMediaConversions;
use App\Models\Admin;
use App\Models\Category;
use App\Models\DesignServiceRequest;
use App\Models\Post;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Services\MediaLibraryCatalog;
use App\Services\MediaUsageService;
use App\Services\ProductImageConversionService;
use App\Services\ProductImageResolver;
use App\Services\ProductImageUploadService;
use App\Support\ProductImagePolicy;
use Filament\Facades\Filament;
use Filament\Forms\Components\RichEditor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Testing\Fakes\QueueFake;
use Inertia\Testing\AssertableInertia as Assert;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\Livewire;
use RuntimeException;
use Spatie\MediaLibrary\Conversions\FileManipulator;
use Spatie\MediaLibrary\MediaCollections\Models\Media as BaseMedia;
use Tests\TestCase;

class MediaLibraryTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('admin');
        Filament::bootCurrentPanel();

        $this->admin = Admin::factory()->create();
        $this->actingAs($this->admin, 'admin');
        Storage::fake('public');
        app(MediaLibraryCatalog::class)->invalidate();
    }

    public function test_catalog_discovers_classifies_and_groups_managed_images(): void
    {
        $queue = Queue::fake();

        $sourcePath = app(ProductImageUploadService::class)->store(
            UploadedFile::fake()->image('large-product.png', 2100, 210),
            'product-galleries',
        );
        $this->putImage('uploads/legacy.jpg', 120, 80);
        $this->putImage('temp-uploads/temporary.png');
        $this->putImage('999/conversions/orphan.webp');
        $this->putImage('999/responsive-images/orphan_400_200.webp');
        Storage::disk('public')->put('product-galleries/orphan.webp.failed.json', '{}');

        $assets = app(MediaLibraryCatalog::class)->assets(fresh: true);
        $productAsset = collect($assets)->firstWhere('source_path', $sourcePath);
        $legacyAsset = collect($assets)->firstWhere('primary_path', 'uploads/legacy.jpg');

        $this->assertCount(2, $assets);
        $this->assertIsArray($productAsset);
        $this->assertTrue($productAsset['has_original']);
        $this->assertSame(1, $productAsset['variant_count']);
        $this->assertSame('product_gallery', $productAsset['purpose']);
        $this->assertSame([2100, 210], [$productAsset['width'], $productAsset['height']]);
        $this->assertSame(ProductImagePolicy::STATUS_PROCESSING, $productAsset['conversion_status']);
        $this->assertSame('/storage/'.$sourcePath, $productAsset['url']);
        $this->assertIsArray($legacyAsset);
        $this->assertFalse($legacyAsset['has_original']);
        $this->assertSame('general', $legacyAsset['purpose']);
        $this->assertNull(collect($assets)->firstWhere('primary_path', 'temp-uploads/temporary.png'));
        $this->assertNull(collect($assets)->firstWhere('primary_path', '999/conversions/orphan.webp'));
        $this->assertNull(collect($assets)->firstWhere('primary_path', '999/responsive-images/orphan_400_200.webp'));

        $this->runQueuedProductConversions($queue);

        $convertedAssets = app(MediaLibraryCatalog::class)->assets(fresh: true);
        $convertedProductAsset = collect($convertedAssets)->firstWhere('source_path', $sourcePath);

        $this->assertCount(2, $convertedAssets);
        $this->assertIsArray($convertedProductAsset);
        $this->assertSame(2, $convertedProductAsset['variant_count']);
        $this->assertSame([2000, 200], [
            $convertedProductAsset['width'],
            $convertedProductAsset['height'],
        ]);
        $this->assertSame(ProductImagePolicy::STATUS_READY, $convertedProductAsset['conversion_status']);
        $this->assertSame(
            '/storage/'.app(ProductImageResolver::class)->derivativePath($sourcePath),
            $convertedProductAsset['url'],
        );

        $searchPage = Livewire::test(MediaLibraryPage::class)
            ->set('search', ProductImagePolicy::ORIGINALS_DIRECTORY)
            ->instance();

        $this->assertInstanceOf(MediaLibraryPage::class, $searchPage);

        $searchState = $searchPage->getLibraryState();

        $this->assertSame(1, $searchState['paginator']->total());
        $this->assertSame($sourcePath, $searchState['paginator']->items()[0]['source_path']);
    }

    public function test_catalog_groups_spatie_files_and_reports_their_owner(): void
    {
        $queue = Queue::fake();
        config()->set('media-library.queue_conversions_after_database_commit', false);

        $product = $this->createProduct('Spatie Product');
        $media = $product
            ->addMedia(UploadedFile::fake()->image('override.png', 400, 200))
            ->toMediaCollection('product-gallery-overrides');

        $assets = app(MediaLibraryCatalog::class)->assets(fresh: true);
        $asset = collect($assets)->firstWhere('spatie_media_id', $media->id);

        $this->assertIsArray($asset);
        $this->assertSame('spatie', $asset['purpose']);
        $this->assertSame(1, $asset['variant_count']);
        $this->assertSame(ProductImagePolicy::STATUS_PROCESSING, $asset['conversion_status']);
        $this->assertSame($media->getUrl(), $asset['url']);
        Queue::assertPushed(PerformProductMediaConversions::class);

        $usages = app(MediaUsageService::class)->forAssets([$asset])[$asset['id']];

        $this->assertNotEmpty($usages);
        $this->assertTrue(collect($usages)->contains(
            fn (array $usage): bool => $usage['type'] === 'spatie'
                && $usage['record_id'] === $product->id,
        ));

        $media = $this->runQueuedMediaConversion($queue, $media);
        $readyAsset = collect(app(MediaLibraryCatalog::class)->assets(fresh: true))
            ->firstWhere('spatie_media_id', $media->id);

        $this->assertIsArray($readyAsset);
        $this->assertGreaterThanOrEqual(2, $readyAsset['variant_count']);
        $this->assertSame(ProductImagePolicy::STATUS_READY, $readyAsset['conversion_status']);
        $this->assertSame(
            $media->getUrl(ProductImagePolicy::STOREFRONT_CONVERSION),
            $readyAsset['url'],
        );
    }

    public function test_catalog_keeps_a_failed_direct_conversion_as_one_original_fallback(): void
    {
        $queue = Queue::fake();
        $sourcePath = app(ProductImageUploadService::class)->store(
            UploadedFile::fake()->image('failed.png', 400, 200),
            'product-galleries',
        );
        $job = $queue->pushed(GenerateProductImageWebp::class)->first();

        $this->assertInstanceOf(GenerateProductImageWebp::class, $job);

        $job->failed(new RuntimeException('Test direct conversion failure.'));

        $assets = app(MediaLibraryCatalog::class)->assets(fresh: true);
        $asset = collect($assets)->firstWhere('source_path', $sourcePath);

        $this->assertCount(1, $assets);
        $this->assertIsArray($asset);
        $this->assertSame(ProductImagePolicy::STATUS_FAILED, $asset['conversion_status']);
        $this->assertSame('/storage/'.$sourcePath, $asset['url']);
        $this->assertSame(2, $asset['variant_count']);
        $this->assertContains(
            app(ProductImageResolver::class)->failureMarkerPath($job->webpPath),
            $asset['variant_paths'],
        );
    }

    public function test_spatie_conversion_failure_state_is_cleared_by_a_successful_retry(): void
    {
        $queue = Queue::fake();
        config()->set('media-library.queue_conversions_after_database_commit', false);

        $product = $this->createProduct('Retry Spatie Product');
        $media = $product
            ->addMedia(UploadedFile::fake()->image('retry.png', 400, 200))
            ->toMediaCollection('product-featured-overrides');
        $job = $queue->pushed(PerformProductMediaConversions::class)->first();

        $this->assertInstanceOf(PerformProductMediaConversions::class, $job);

        $job->failed(new RuntimeException('Test media conversion failure.'));
        $media->refresh();

        $this->assertNotEmpty($media->getCustomProperty(ProductImagePolicy::MEDIA_FAILURE_PROPERTY));

        $failedAsset = collect(app(MediaLibraryCatalog::class)->assets(fresh: true))
            ->firstWhere('spatie_media_id', $media->id);

        $this->assertIsArray($failedAsset);
        $this->assertSame(ProductImagePolicy::STATUS_FAILED, $failedAsset['conversion_status']);
        $this->assertSame($media->getUrl(), $failedAsset['url']);

        $media = $this->runQueuedMediaConversion($queue, $media);

        $this->assertEmpty($media->getCustomProperty(ProductImagePolicy::MEDIA_FAILURE_PROPERTY));
        $this->assertTrue($media->hasGeneratedConversion(ProductImagePolicy::STOREFRONT_CONVERSION));

        $readyAsset = collect(app(MediaLibraryCatalog::class)->assets(fresh: true))
            ->firstWhere('spatie_media_id', $media->id);

        $this->assertIsArray($readyAsset);
        $this->assertSame(ProductImagePolicy::STATUS_READY, $readyAsset['conversion_status']);
    }

    public function test_usage_detection_covers_products_posts_and_design_requests(): void
    {
        $path = 'uploads/referenced.webp';
        $this->putImage($path);
        $product = $this->createProduct('Referenced Product', [
            'media' => ['gallery' => [$path]],
        ]);
        $category = Category::create(['name' => 'News', 'slug' => 'news']);

        Post::create([
            'title' => 'Referenced Post',
            'slug' => 'referenced-post',
            'body' => '<p><img src="/storage/'.$path.'"></p>',
            'category_id' => $category->id,
            'admin_id' => $this->admin->id,
        ]);
        DesignServiceRequest::create([
            'email' => 'design@example.com',
            'business_name' => 'Referenced Design',
            'logo_path' => 'http://localhost/storage/'.$path.'?version=1',
        ]);

        $asset = collect(app(MediaLibraryCatalog::class)->assets(fresh: true))
            ->firstWhere('primary_path', $path);
        $usages = app(MediaUsageService::class)->forAssets([$asset])[$asset['id']];

        $types = collect($usages)->pluck('type')->unique()->values()->all();
        sort($types);

        $this->assertSame(['design_service_request', 'post', 'product'], $types);
        $this->assertTrue(collect($usages)->contains(
            fn (array $usage): bool => $usage['record_id'] === $product->id
                && $usage['location'] === '产品配置',
        ));
    }

    public function test_media_library_uploads_multiple_images_to_the_selected_purpose(): void
    {
        $queue = Queue::fake();

        Livewire::test(MediaLibraryPage::class)
            ->fillForm([
                'purpose' => 'product_swatch',
                'uploaded_files' => [
                    UploadedFile::fake()->image('first.png', 320, 160),
                    UploadedFile::fake()->image('second.jpg', 240, 120),
                ],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $files = Storage::disk('public')->allFiles();
        $webpFiles = array_values(array_filter(
            $files,
            fn (string $path): bool => Str::startsWith($path, 'product-options/swatches/')
                && Str::endsWith($path, '.webp'),
        ));
        $sourceFiles = array_values(array_filter(
            $files,
            fn (string $path): bool => Str::startsWith(
                $path,
                ProductImagePolicy::ORIGINALS_DIRECTORY.'/product-options/swatches/',
            ),
        ));

        $this->assertCount(0, $webpFiles);
        $this->assertCount(2, $sourceFiles);
        Queue::assertPushed(GenerateProductImageWebp::class, 2);

        $assets = app(MediaLibraryCatalog::class)->assets(fresh: true);

        $this->assertCount(2, $assets);
        $this->assertTrue(collect($assets)->every(
            fn (array $asset): bool => $asset['conversion_status'] === ProductImagePolicy::STATUS_PROCESSING,
        ));

        $this->runQueuedProductConversions($queue);

        $convertedFiles = Storage::disk('public')->allFiles();
        $webpFiles = array_values(array_filter(
            $convertedFiles,
            fn (string $path): bool => Str::startsWith($path, 'product-options/swatches/')
                && Str::endsWith($path, '.webp'),
        ));
        $convertedAssets = app(MediaLibraryCatalog::class)->assets(fresh: true);

        $this->assertCount(2, $webpFiles);
        $this->assertCount(2, $convertedAssets);
        $this->assertTrue(collect($convertedAssets)->every(
            fn (array $asset): bool => $asset['conversion_status'] === ProductImagePolicy::STATUS_READY,
        ));
    }

    public function test_referenced_assets_are_blocked_and_unreferenced_variants_are_deleted(): void
    {
        $path = app(ProductImageUploadService::class)->store(
            UploadedFile::fake()->image('protected.png', 320, 160),
            'product-galleries',
        );
        $product = $this->createProduct('Protected Product', [
            'media' => ['gallery' => [$path]],
        ]);
        $catalog = app(MediaLibraryCatalog::class);
        $asset = collect($catalog->assets(fresh: true))->firstWhere('source_path', $path);

        $this->assertFalse($catalog->delete($asset['id']));

        Livewire::test(MediaLibraryPage::class)
            ->call('deleteAsset', $asset['id']);

        foreach ($asset['variant_paths'] as $variantPath) {
            Storage::disk('public')->assertExists($variantPath);
        }

        $product->update(['product_config' => null]);

        Livewire::test(MediaLibraryPage::class)
            ->call('deleteAsset', $asset['id']);

        foreach ($asset['variant_paths'] as $variantPath) {
            Storage::disk('public')->assertMissing($variantPath);
        }
    }

    public function test_tampered_asset_identifier_cannot_delete_a_file(): void
    {
        $path = 'uploads/keep.webp';
        $this->putImage($path);

        Livewire::test(MediaLibraryPage::class)
            ->call('deleteAsset', hash('sha256', 'public'."\0".'uploads/another.webp'));

        Storage::disk('public')->assertExists($path);
    }

    public function test_search_filters_sorting_and_pagination_are_applied(): void
    {
        foreach (range(1, 26) as $number) {
            Storage::disk('public')->put(
                sprintf('uploads/image-%02d.jpg', $number),
                'image-'.$number,
            );
        }

        app(MediaLibraryCatalog::class)->invalidate();
        $component = Livewire::test(MediaLibraryPage::class)
            ->set('purposeFilter', 'general')
            ->set('sort', 'name_asc');
        $page = $component->instance();

        $this->assertInstanceOf(MediaLibraryPage::class, $page);

        $state = $page->getLibraryState();

        $this->assertSame(26, $state['paginator']->total());
        $this->assertCount(MediaLibraryCatalog::ITEMS_PER_PAGE, $state['paginator']->items());
        $this->assertSame('image-01.jpg', $state['paginator']->items()[0]['name']);
        $this->assertTrue($state['paginator']->hasMorePages());

        $component->set('search', 'image-17');
        $page = $component->instance();

        $this->assertInstanceOf(MediaLibraryPage::class, $page);

        $searched = $page->getLibraryState();

        $this->assertSame(1, $searched['paginator']->total());
        $this->assertSame('image-17.jpg', $searched['paginator']->items()[0]['name']);
    }

    public function test_blog_featured_uploads_use_public_webp_storage(): void
    {
        $category = Category::create(['name' => 'Updates', 'slug' => 'updates']);

        Livewire::test(CreatePost::class)
            ->fillForm([
                'title' => 'Image Post',
                'slug' => 'image-post',
                'category_id' => $category->id,
                'admin_id' => $this->admin->id,
                'featured_image' => UploadedFile::fake()->image('cover.png', 400, 200),
                'body' => '<p>Post body</p>',
                'is_published' => false,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $post = Post::query()->where('slug', 'image-post')->firstOrFail();

        $this->assertMatchesRegularExpression(
            '~^'.preg_quote(ProductImagePolicy::ORIGINALS_DIRECTORY, '~').'/blog/[0-9A-Z]{26}\.png$~',
            $post->featured_image,
        );
        Storage::disk('public')->assertExists($post->featured_image);
        $webpPath = app(ProductImageResolver::class)->derivativePath($post->featured_image);

        $this->assertIsString($webpPath);
        Storage::disk('public')->assertExists($webpPath);
        $this->assertCount(1, Storage::disk('public')->allFiles(
            ProductImagePolicy::ORIGINALS_DIRECTORY.'/blog',
        ));

        $post->update([
            'is_published' => true,
            'published_at' => now(),
        ]);

        $this->get(route('blog.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page): Assert => $page
                ->component('blog/index')
                ->where('posts.data.0.featured_image', '/storage/'.$webpPath));
    }

    public function test_blog_body_uploads_use_the_public_image_processing_flow(): void
    {
        Queue::fake();

        $fileId = 'body-image';
        $component = Livewire::test(CreatePost::class)
            ->set(
                "componentFileAttachments.data.body.{$fileId}",
                UploadedFile::fake()->image('article.png', 400, 200),
            );
        $page = $component->instance();

        $this->assertInstanceOf(CreatePost::class, $page);

        $form = $page->getSchema('form');

        $this->assertNotNull($form);

        $editor = $form->getComponentByStatePath('body');
        $temporaryFile = data_get(
            $page->componentFileAttachments,
            "data.body.{$fileId}",
        );

        $this->assertInstanceOf(RichEditor::class, $editor);
        $this->assertInstanceOf(TemporaryUploadedFile::class, $temporaryFile);
        $this->assertSame('public', $editor->getFileAttachmentsDiskName());
        $this->assertSame('blog', $editor->getFileAttachmentsDirectory());
        $this->assertSame('public', $editor->getFileAttachmentsVisibility());

        $storedPath = $editor->saveUploadedFileAttachment($temporaryFile);

        $this->assertIsString($storedPath);
        $this->assertMatchesRegularExpression(
            '~^'.preg_quote(ProductImagePolicy::ORIGINALS_DIRECTORY, '~').'/blog/[0-9A-Z]{26}\.png$~',
            $storedPath,
        );
        Storage::disk('public')->assertExists($storedPath);
        Queue::assertPushed(GenerateProductImageWebp::class);
    }

    public function test_only_the_unified_media_library_route_remains(): void
    {
        $this->assertTrue(Route::has('filament.admin.pages.media-library'));
        $this->assertFalse(Route::has('filament.admin.resources.media.index'));
    }

    /**
     * @param  array<string, mixed>|null  $productConfig
     */
    private function createProduct(string $name, ?array $productConfig = null): Product
    {
        $category = ProductCategory::firstOrCreate(
            ['slug' => 'business-cards'],
            ['name' => 'Business Cards'],
        );

        return Product::create([
            'name' => $name,
            'slug' => Str::slug($name),
            'product_category_id' => $category->id,
            'product_config' => $productConfig,
        ]);
    }

    private function putImage(string $path, int $width = 100, int $height = 100): void
    {
        Storage::disk('public')->putFileAs(
            dirname($path),
            UploadedFile::fake()->image(basename($path), $width, $height),
            basename($path),
        );
    }

    private function runQueuedProductConversions(QueueFake $queue): void
    {
        $queue->pushed(GenerateProductImageWebp::class)
            ->each(function (GenerateProductImageWebp $job): void {
                $job->handle(
                    app(ProductImageConversionService::class),
                    app(ProductImageResolver::class),
                    app(MediaLibraryCatalog::class),
                );
            });
    }

    private function runQueuedMediaConversion(QueueFake $queue, BaseMedia $media): BaseMedia
    {
        $job = $queue->pushed(PerformProductMediaConversions::class)->first();

        $this->assertInstanceOf(PerformProductMediaConversions::class, $job);
        $job->handle(app(FileManipulator::class));

        return $media->refresh();
    }
}
