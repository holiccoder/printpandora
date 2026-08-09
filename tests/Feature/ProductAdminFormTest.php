<?php

namespace Tests\Feature;

use App\Filament\Resources\ProductResource\Pages\CreateProduct;
use App\Filament\Resources\ProductResource\Pages\EditProduct;
use App\Jobs\GenerateProductImageWebp;
use App\Models\Admin;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Services\ProductImageResolver;
use App\Support\ProductImagePolicy;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ProductAdminFormTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('admin');
        Filament::bootCurrentPanel();

        $this->actingAs(Admin::factory()->create(), 'admin');
    }

    public function test_option_value_fields_appear_immediately_after_adding_an_option(): void
    {
        $component = Livewire::test(CreateProduct::class)
            ->assertSee('请先添加一个产品选项')
            ->callFormComponentAction('product_config.options', 'add');

        $this->assertNotEmpty(
            data_get($component->get('data'), 'product_config.options'),
            json_encode($component->get('data'), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
        );

        $component
            ->assertSee('色卡标题')
            ->assertSee('色卡编码');
    }

    public function test_adding_another_option_preserves_existing_option_values(): void
    {
        $component = Livewire::test(CreateProduct::class)
            ->callFormComponentAction('product_config.options', 'add');

        $firstOptionKey = array_key_first(data_get($component->get('data'), 'product_config.options', []));
        $firstRowKey = (string) data_get(
            $component->get('data'),
            "product_config.options.{$firstOptionKey}.row_key",
        );
        $firstValueKey = array_key_first(
            data_get($component->get('data'), "product_config.option_values.{$firstRowKey}", []),
        );

        $component
            ->set("data.product_config.options.{$firstOptionKey}.label", '尺寸')
            ->set("data.product_config.options.{$firstOptionKey}.key", 'size')
            ->set("data.product_config.option_values.{$firstRowKey}.{$firstValueKey}.label", '标准尺寸')
            ->set("data.product_config.option_values.{$firstRowKey}.{$firstValueKey}.code", 'standard')
            ->callFormComponentAction('product_config.options', 'add');

        $this->assertSame(
            '标准尺寸',
            data_get(
                $component->get('data'),
                "product_config.option_values.{$firstRowKey}.{$firstValueKey}.label",
            ),
            json_encode($component->get('data'), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
        );
        $this->assertSame(
            'standard',
            data_get(
                $component->get('data'),
                "product_config.option_values.{$firstRowKey}.{$firstValueKey}.code",
            ),
        );
    }

    public function test_option_value_code_tracks_the_title_slug(): void
    {
        $component = Livewire::test(CreateProduct::class)
            ->callFormComponentAction('product_config.options', 'add');

        $optionKey = array_key_first(data_get($component->get('data'), 'product_config.options', []));
        $rowKey = (string) data_get($component->get('data'), "product_config.options.{$optionKey}.row_key");
        $valueKey = array_key_first(data_get($component->get('data'), "product_config.option_values.{$rowKey}", []));
        $labelFieldPath = "product_config.option_values.{$rowKey}.{$valueKey}.label";
        $labelPath = "data.{$labelFieldPath}";
        $codePath = "data.product_config.option_values.{$rowKey}.{$valueKey}.code";
        $labelField = collect($component->instance()->form->getFlatFields(withHidden: true))
            ->first(fn ($field): bool => $field->getStatePath() === $labelPath);

        $this->assertNotNull($labelField);

        $labelField->state('Soft Touch Film');

        $this->assertSame('Soft Touch Film', $labelField->getState());

        $labelField->callAfterStateUpdated();
        $livewire = $labelField->getLivewire();

        $this->assertSame('soft_touch_film', data_get($livewire, $codePath));

        data_set($livewire, $codePath, 'custom_finish');
        $labelField->state('Matte Finish')->callAfterStateUpdated();

        $this->assertSame('matte_finish', data_get($livewire, $codePath));
        $this->assertSame('Matte Finish', data_get($livewire, $labelPath));
    }

    public function test_gallery_and_pricing_are_disabled_until_options_are_complete(): void
    {
        $component = Livewire::test(CreateProduct::class)
            ->assertFormFieldDisabled('product_config.media.gallery_rules')
            ->assertFormFieldDisabled('product_config.pricing.rules')
            ->callFormComponentAction('product_config.options', 'add');

        $optionKey = array_key_first(data_get($component->get('data'), 'product_config.options', []));
        $rowKey = (string) data_get($component->get('data'), "product_config.options.{$optionKey}.row_key");
        $valueKey = array_key_first(data_get($component->get('data'), "product_config.option_values.{$rowKey}", []));

        $component
            ->set("data.product_config.options.{$optionKey}.label", '尺寸')
            ->set("data.product_config.options.{$optionKey}.key", 'size')
            ->set("data.product_config.option_values.{$rowKey}.{$valueKey}.label", '标准尺寸')
            ->set("data.product_config.option_values.{$rowKey}.{$valueKey}.code", 'standard');

        $component
            ->assertFormFieldEnabled('product_config.media.gallery_rules')
            ->assertFormFieldEnabled('product_config.pricing.rules');
    }

    public function test_product_can_be_created_without_options_gallery_rules_or_pricing_rules(): void
    {
        $category = ProductCategory::create([
            'name' => '名片',
            'slug' => 'business-cards',
        ]);

        Livewire::test(CreateProduct::class)
            ->fillForm([
                'name' => '无选项产品',
                'slug' => 'product-without-options',
                'product_category_id' => $category->getKey(),
                'is_active' => true,
                'product_config.faq' => [],
                'product_config.options' => [],
                'product_config.media.gallery_rules' => [],
                'product_config.pricing.rules' => [],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('products', [
            'name' => '无选项产品',
            'slug' => 'product-without-options',
        ]);
    }

    public function test_existing_gallery_images_resolve_to_filament_preview_urls(): void
    {
        Storage::fake('public');

        $category = ProductCategory::create([
            'name' => '图库测试分类',
            'slug' => 'gallery-test-category',
        ]);
        $path = 'product-galleries/test-preview.jpg';

        Storage::disk('public')->put($path, 'test image');

        $product = Product::create([
            'name' => '图库预览测试',
            'slug' => 'gallery-preview-test',
            'product_category_id' => $category->getKey(),
            'is_active' => true,
            'product_config' => [
                'media' => ['gallery' => [$path], 'gallery_rules' => []],
            ],
        ]);

        $component = Livewire::test(EditProduct::class, [
            'record' => $product->getRouteKey(),
        ]);
        $field = $component->instance()->form
            ->getFlatFields(withHidden: true)['product_config.media.gallery'];

        $preview = array_values($field->getUploadedFiles() ?? [])[0] ?? null;

        $this->assertSame(
            '/storage/'.$path,
            data_get($preview, 'url'),
        );
    }

    public function test_existing_gallery_images_can_be_selected_without_uploading_again(): void
    {
        Storage::fake('public');

        $path = 'product-galleries/already-uploaded.jpg';
        Storage::disk('public')->put($path, 'test image');

        $component = Livewire::test(CreateProduct::class)
            ->callFormComponentAction(
                'existing-image-picker-product-configmediagallery',
                'selectExistingImages',
                ['images' => [$path]],
            );

        $this->assertSame(
            [$path],
            array_values(data_get($component->get('data'), 'product_config.media.gallery', [])),
        );
    }

    public function test_a_new_image_can_be_uploaded_and_selected_from_the_existing_image_modal(): void
    {
        Storage::fake('public');
        Queue::fake();

        $component = Livewire::test(CreateProduct::class)
            ->callFormComponentAction(
                'existing-image-picker-product-configmediagallery',
                'selectExistingImages',
                [
                    'new_images' => [
                        UploadedFile::fake()->image('modal-upload.png', 320, 160),
                    ],
                    'images' => [],
                ],
            );

        $gallery = array_values(data_get(
            $component->get('data'),
            'product_config.media.gallery',
            [],
        ));

        $this->assertCount(1, $gallery);
        $this->assertMatchesRegularExpression(
            '~^'.preg_quote(ProductImagePolicy::ORIGINALS_DIRECTORY, '~').'/product-galleries/[0-9A-Z]{26}\.png$~',
            $gallery[0],
        );
        Storage::disk('public')->assertExists($gallery[0]);
        $webpPath = app(ProductImageResolver::class)->derivativePath($gallery[0]);

        $this->assertIsString($webpPath);
        Storage::disk('public')->assertMissing($webpPath);
        Queue::assertPushed(
            GenerateProductImageWebp::class,
            fn (GenerateProductImageWebp $job): bool => $job->sourcePath === $gallery[0]
                && $job->webpPath === $webpPath,
        );
    }

    public function test_new_gallery_uploads_store_original_paths_and_queue_webp_conversion(): void
    {
        Storage::fake('public');
        Queue::fake();

        $category = ProductCategory::create([
            'name' => 'Business Cards',
            'slug' => 'business-cards',
        ]);

        Livewire::test(CreateProduct::class)
            ->fillForm([
                'name' => 'Optimized Gallery Product',
                'slug' => 'optimized-gallery-product',
                'product_category_id' => $category->getKey(),
                'is_active' => true,
                'product_config.faq' => [],
                'product_config.options' => [],
                'product_config.media.gallery' => [
                    UploadedFile::fake()->image('gallery.png', 320, 160),
                ],
                'product_config.media.gallery_rules' => [],
                'product_config.pricing.rules' => [],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $product = Product::query()->where('slug', 'optimized-gallery-product')->firstOrFail();
        $path = data_get($product->product_config, 'media.gallery.0');

        $this->assertIsString($path);
        $this->assertMatchesRegularExpression(
            '~^'.preg_quote(ProductImagePolicy::ORIGINALS_DIRECTORY, '~').'/product-galleries/[0-9A-Z]{26}\.png$~',
            $path,
        );
        Storage::disk('public')->assertExists($path);
        $webpPath = app(ProductImageResolver::class)->derivativePath($path);

        $this->assertIsString($webpPath);
        Storage::disk('public')->assertMissing($webpPath);
        Queue::assertPushed(
            GenerateProductImageWebp::class,
            fn (GenerateProductImageWebp $job): bool => $job->sourcePath === $path
                && $job->webpPath === $webpPath,
        );

        $sources = Storage::disk('public')->allFiles(
            ProductImagePolicy::ORIGINALS_DIRECTORY.'/product-galleries',
        );

        $this->assertSame([$path], $sources);
    }
}
