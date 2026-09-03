<?php

namespace Tests\Feature;

use App\Mail\OrderInvoiceMail;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OrderInvoiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        Storage::fake('local');
    }

    public function test_paid_order_gets_a_pdf_invoice_and_customer_email(): void
    {
        [$user, $order] = $this->makePendingOrder();

        DB::transaction(function () use ($order): void {
            $order->update([
                'payment_status' => 'paid',
                'status' => 'processing',
            ]);
        });

        $paidOrder = $order->fresh();

        $this->assertNotNull($paidOrder->invoice_number);
        $this->assertNotNull($paidOrder->invoice_path);
        $this->assertNotNull($paidOrder->invoice_issued_at);
        $this->assertNotNull($paidOrder->invoice_emailed_at);
        Storage::disk('local')->assertExists($paidOrder->invoice_path);
        Mail::assertSent(OrderInvoiceMail::class, function (OrderInvoiceMail $mail) use ($user, $paidOrder): bool {
            return $mail->hasTo($user->email)
                && $mail->order->is($paidOrder)
                && count($mail->attachments()) === 1;
        });
    }

    public function test_invoice_download_is_limited_to_the_paid_order_owner(): void
    {
        [$user, $order] = $this->makePendingOrder();

        DB::transaction(function () use ($order): void {
            $order->update(['payment_status' => 'paid']);
        });

        $response = $this->actingAs($user)->get(route('dashboard.orders.invoice', $order->id));

        $response->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertHeader('content-disposition');

        $otherUser = User::factory()->create();

        $this->actingAs($otherUser)
            ->get(route('dashboard.orders.invoice', $order->id))
            ->assertNotFound();
    }

    public function test_repeated_paid_updates_do_not_send_another_invoice(): void
    {
        [$user, $order] = $this->makePendingOrder();

        DB::transaction(function () use ($order): void {
            $order->update(['payment_status' => 'paid']);
        });

        $order->update(['status' => 'shipped']);

        Mail::assertSentCount(1);
        Mail::assertSent(OrderInvoiceMail::class, function (OrderInvoiceMail $mail) use ($user): bool {
            return $mail->hasTo($user->email);
        });
    }

    /** @return array{0: User, 1: Order} */
    private function makePendingOrder(): array
    {
        $user = User::factory()->create();
        $category = ProductCategory::create([
            'name' => 'Invoice test products',
            'slug' => 'invoice-test-'.uniqid(),
        ]);
        $product = Product::create([
            'name' => 'Invoice test product',
            'slug' => 'invoice-test-product-'.uniqid(),
            'product_category_id' => $category->id,
        ]);
        $order = Order::create([
            'user_id' => $user->id,
            'status' => 'pending',
            'payment_method' => 'paypal',
            'payment_status' => 'pending',
            'total' => 25,
            'customer_name' => $user->name,
            'customer_email' => $user->email,
            'shipping_address' => '1 Main Street',
            'shipping_city' => 'Austin',
            'shipping_state' => 'TX',
            'shipping_zip' => '78701',
            'shipping_country' => 'US',
            'shipping_method' => 'standard',
            'shipping_carrier' => 'Standard',
            'shipping_fee' => 0,
        ]);
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 25,
            'subtotal' => 25,
            'options' => [],
        ]);

        return [$user, $order];
    }
}
