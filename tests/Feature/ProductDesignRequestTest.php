<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductDesignRequest;
use App\Models\User;
use App\Services\Cart;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductDesignRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_form_stores_json_and_files_separately_from_design_service_requests(): void
    {
        Storage::fake('public');

        $this->post(route('product-designs.store'), [
            'desgin' => json_encode([
                'source' => 'product-page',
                'mode' => 'upload',
                'product_id' => 42,
                'product_name' => 'Classic Business Cards',
                'product_slug' => 'classic-business-cards',
                'email' => 'client@example.com',
                'business_name' => 'Acme',
                'card_info' => 'Name and contact details',
                'business_card_type' => 'Classic Business Cards',
                'design_service_code' => null,
                'terms_accepted' => true,
            ], JSON_THROW_ON_ERROR),
            'design_file' => UploadedFile::fake()->create(
                'artwork.pdf',
                1,
                'application/pdf',
            ),
            'logo_file' => UploadedFile::fake()->image('logo.png'),
            'example_files' => [
                UploadedFile::fake()->image('example.png'),
            ],
            'return_to' => '/classic-business-cards',
        ])->assertRedirect('/classic-business-cards');

        $request = ProductDesignRequest::query()->firstOrFail();

        $this->assertSame('product-page', $request->desgin['source']);
        $this->assertSame('upload', $request->desgin['mode']);
        $this->assertSame('client@example.com', $request->desgin['email']);
        $this->assertNotNull($request->desgin['design_path']);
        $this->assertStringStartsWith(
            'product-designs/designs/',
            $request->desgin['design_path'],
        );
        $this->assertNotNull($request->desgin['logo_path']);
        $this->assertCount(1, $request->desgin['example_paths']);
        $this->assertDatabaseCount('design_service_requests', 0);

        Storage::disk('public')->assertExists($request->desgin['design_path']);
        Storage::disk('public')->assertExists($request->desgin['logo_path']);
        Storage::disk('public')->assertExists($request->desgin['example_paths'][0]);
    }

    public function test_canva_form_stores_its_file_without_redirecting_to_design_service_page(): void
    {
        Storage::fake('public');

        $this->post(route('product-designs.store'), [
            'desgin' => json_encode([
                'source' => 'product-page',
                'mode' => 'canva',
                'product_id' => 42,
                'product_name' => 'Classic Business Cards',
                'product_slug' => 'classic-business-cards',
            ], JSON_THROW_ON_ERROR),
            'design_file' => UploadedFile::fake()->image('canva-design.png'),
            'return_to' => '/classic-business-cards',
        ])->assertRedirect('/classic-business-cards');

        $request = ProductDesignRequest::query()->firstOrFail();

        $this->assertSame('canva', $request->desgin['mode']);
        $this->assertNotNull($request->desgin['design_path']);
        $this->assertDatabaseCount('design_service_requests', 0);
        Storage::disk('public')->assertExists($request->desgin['design_path']);
    }

    public function test_upload_mode_requires_a_main_design_file(): void
    {
        $response = $this->post(route('product-designs.store'), [
            'desgin' => json_encode([
                'source' => 'product-page',
                'mode' => 'upload',
                'product_name' => 'Classic Business Cards',
                'product_slug' => 'classic-business-cards',
                'email' => 'client@example.com',
                'business_name' => 'Acme',
                'business_card_type' => 'Classic Business Cards',
                'terms_accepted' => true,
            ], JSON_THROW_ON_ERROR),
        ]);

        $response->assertSessionHasErrors('design_file');
        $this->assertDatabaseCount('product_design_requests', 0);
    }

    public function test_upload_mode_rejects_a_design_file_over_75_mb(): void
    {
        $response = $this->post(route('product-designs.store'), [
            'desgin' => json_encode([
                'source' => 'product-page',
                'mode' => 'upload',
                'product_name' => 'Classic Business Cards',
                'product_slug' => 'classic-business-cards',
                'email' => 'client@example.com',
                'business_name' => 'Acme',
                'business_card_type' => 'Classic Business Cards',
                'terms_accepted' => true,
            ], JSON_THROW_ON_ERROR),
            'design_file' => UploadedFile::fake()->create(
                'oversized.pdf',
                76801,
                'application/pdf',
            ),
        ]);

        $response->assertSessionHasErrors('design_file');
        $this->assertDatabaseCount('product_design_requests', 0);
    }

    public function test_product_design_submission_is_attached_to_the_checkout_order(): void
    {
        $user = User::factory()->create([
            'email' => 'design-client@example.com',
        ]);
        $category = ProductCategory::create([
            'name' => 'Design test products',
            'slug' => 'design-test-products-'.uniqid(),
        ]);
        $product = Product::create([
            'name' => 'Design test product',
            'slug' => 'design-test-product-'.uniqid(),
            'product_category_id' => $category->id,
            'price' => 25,
        ]);

        $this->post(route('product-designs.store'), [
            'desgin' => json_encode([
                'source' => 'product-page',
                'mode' => 'upload',
                'product_id' => $product->id,
                'product_name' => $product->name,
                'product_slug' => $product->slug,
                'email' => $user->email,
                'business_name' => 'Design Client',
                'business_card_type' => 'Classic Business Cards',
                'terms_accepted' => true,
            ], JSON_THROW_ON_ERROR),
            'design_file' => UploadedFile::fake()->create(
                'artwork.pdf',
                1,
                'application/pdf',
            ),
        ])->assertRedirect();

        $this->actingAs($user);
        app(Cart::class)->add($product->id);

        $this->get(route('shop.checkout'))->assertOk();

        $order = Order::firstOrFail();
        $this->assertDatabaseHas('product_design_requests', [
            'id' => ProductDesignRequest::firstOrFail()->id,
            'order_id' => $order->id,
        ]);
    }
}
