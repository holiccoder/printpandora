<?php

namespace Tests\Feature;

use App\Filament\Resources\OrderResource\Pages\EditOrder;
use App\Filament\Resources\OrderResource\Pages\ListOrders;
use App\Models\Admin;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductDesignRequest;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class OrderDesignActionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('admin');
        Filament::bootCurrentPanel();
        $this->actingAs(Admin::factory()->create(), 'admin');
    }

    public function test_design_action_is_available_on_order_list_and_edit_page(): void
    {
        $order = $this->makeOrderWithDesignSubmission();

        Livewire::test(ListOrders::class)
            ->assertTableActionHasLabel('design', '设计', $order)
            ->mountTableAction('design', $order)
            ->assertActionMounted()
            ->assertMountedActionModalSee([
                'Submission #'.$order->productDesignRequests()->firstOrFail()->id,
                'Acme Studio',
            ]);

        Livewire::test(EditOrder::class, ['record' => $order->getRouteKey()])
            ->assertActionExists('design')
            ->mountAction('design')
            ->assertActionMounted()
            ->assertMountedActionModalSee([
                'Submission #'.$order->productDesignRequests()->firstOrFail()->id,
                'Acme Studio',
            ]);
    }

    private function makeOrderWithDesignSubmission(): Order
    {
        $user = User::factory()->create([
            'email' => 'client@example.com',
        ]);
        $category = ProductCategory::create([
            'name' => 'Test products',
            'slug' => 'test-products-'.uniqid(),
        ]);
        $product = Product::create([
            'name' => 'Test business cards',
            'slug' => 'test-business-cards-'.uniqid(),
            'product_category_id' => $category->id,
            'price' => 25,
        ]);
        $order = Order::create([
            'user_id' => $user->id,
            'status' => 'processing',
            'total' => 25,
            'customer_name' => 'Client',
            'customer_email' => $user->email,
            'shipping_address' => '1 Main Street',
            'shipping_city' => 'Austin',
            'shipping_state' => 'TX',
            'shipping_zip' => '78701',
            'shipping_country' => 'US',
            'shipping_method' => 'standard',
            'shipping_carrier' => '4PX',
            'shipping_fee' => 0,
        ]);

        $order->items()->create([
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 25,
            'subtotal' => 25,
            'options' => [],
        ]);

        ProductDesignRequest::create([
            'order_id' => $order->id,
            'desgin' => [
                'source' => 'product-page',
                'mode' => 'upload',
                'product_id' => $product->id,
                'product_name' => $product->name,
                'email' => $user->email,
                'business_name' => 'Acme Studio',
                'business_card_type' => 'Classic Business Cards',
                'card_info' => 'Front and back contact details',
                'terms_accepted' => true,
            ],
        ]);

        return $order;
    }
}
