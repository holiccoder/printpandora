<?php

namespace Tests\Feature;

use App\Models\ProductDesignRequest;
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
        $this->assertNotNull($request->desgin['logo_path']);
        $this->assertCount(1, $request->desgin['example_paths']);
        $this->assertDatabaseCount('design_service_requests', 0);

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
}
