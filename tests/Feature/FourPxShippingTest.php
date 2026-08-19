<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use App\Services\FourPxService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FourPxShippingTest extends TestCase
{
    use RefreshDatabase;

    public function test_fourpx_create_query_label_and_tracking_are_saved(): void
    {
        config([
            'services.four_px.enabled' => true,
            'services.four_px.environment' => 'test',
            'services.four_px.app_key' => 'test-app-key',
            'services.four_px.app_secret' => 'test-app-secret',
            'services.four_px.access_token' => 'test-access-token',
            'services.four_px.logistics_product_code' => null,
            'services.four_px.reference_prefix' => 'PP-',
            'services.four_px.tracking_url_template' => 'https://tracking.example/{tracking}',
            'services.four_px.sender' => [
                'first_name' => 'Sender',
                'phone' => '555-0100',
                'country' => 'CN',
                'city' => 'Shenzhen',
            ],
        ]);

        $order = $this->makeOrder();

        Http::fake([
            'https://open-test.4px.com/router/api/service*' => Http::sequence()
                ->push([
                    'result' => '1',
                    'msg' => 'created',
                    'data' => json_encode([
                        'ds_consignment_no' => 'DS-100',
                        '4px_tracking_no' => '4PX-100',
                        'logistics_channel_no' => 'CHANNEL-100',
                    ]),
                ])
                ->push([
                    'result' => '1',
                    'msg' => 'queried',
                    'data' => json_encode([
                        'consignment_info' => [
                            'ds_consignment_no' => 'DS-100',
                            '4px_tracking_no' => '4PX-100',
                            'logistics_channel_no' => 'CHANNEL-100',
                            'consignment_status' => 'H',
                        ],
                    ]),
                ])
                ->push([
                    'result' => '1',
                    'msg' => 'label',
                    'data' => json_encode([
                        'label_url_info' => [
                            'logistics_label' => 'https://labels.example/DS-100.pdf',
                        ],
                    ]),
                ])
                ->push([
                    'result' => '1',
                    'msg' => 'tracking',
                    'data' => json_encode([
                        'deliveryOrderNo' => 'CHANNEL-100',
                        'trackingList' => [[
                            'trackingContent' => 'Shipment received',
                        ]],
                    ]),
                ]),
        ]);

        $service = app(FourPxService::class);
        $service->createShipment($order, [
            'weight_grams' => 250,
            'length_cm' => 10,
            'width_cm' => 8,
            'height_cm' => 4,
            'logistics_product_code' => 'STD',
        ]);

        $order = $service->refreshShipment($order->refresh());
        $order = $service->fetchLabel($order);
        $service->refreshTracking($order);
        $order->refresh();

        $this->assertSame('PP-'.$order->id, $order->fourpx_ref_no);
        $this->assertSame('DS-100', $order->fourpx_consignment_no);
        $this->assertSame('4PX-100', $order->fourpx_tracking_number);
        $this->assertSame('CHANNEL-100', $order->fourpx_logistics_channel_no);
        $this->assertSame('H', $order->fourpx_status);
        $this->assertSame('CHANNEL-100', $order->tracking_number);
        $this->assertSame('https://tracking.example/CHANNEL-100', $order->tracking_url);
        $this->assertSame('https://labels.example/DS-100.pdf', $order->fourpx_label_url);
        $this->assertSame(250, $order->shipping_weight_grams);
        $this->assertSame('Shipment received', data_get($order->fourpx_tracking_response, 'data.trackingList.0.trackingContent'));

        Http::assertSentCount(4);
        Http::assertSent(function (Request $request): bool {
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);
            $signingParameters = [
                'app_key' => $query['app_key'],
                'format' => $query['format'],
                'method' => $query['method'],
                'timestamp' => $query['timestamp'],
                'v' => $query['v'],
            ];
            ksort($signingParameters, SORT_STRING);

            $source = '';
            foreach ($signingParameters as $name => $value) {
                $source .= $name.$value;
            }

            return $query['method'] === 'ds.xms.order.create'
                && $query['sign'] === md5($source.$request->body().'test-app-secret')
                && $query['access_token'] === 'test-access-token';
        });
    }

    private function makeOrder(): Order
    {
        $user = User::factory()->create();
        $category = ProductCategory::create([
            'name' => 'Shipping test products',
            'slug' => 'shipping-test-'.uniqid(),
        ]);
        $product = Product::create([
            'name' => 'Printed cards',
            'slug' => 'printed-cards-'.uniqid(),
            'product_category_id' => $category->id,
        ]);

        $order = Order::create([
            'user_id' => $user->id,
            'status' => 'processing',
            'payment_method' => 'manual',
            'payment_status' => 'paid',
            'total' => 25,
            'customer_name' => 'Test Customer',
            'customer_email' => 'customer@example.com',
            'customer_phone' => '555-0101',
            'shipping_address' => '1 Main Street',
            'shipping_city' => 'Austin',
            'shipping_state' => 'TX',
            'shipping_zip' => '78701',
            'shipping_country' => 'US',
            'shipping_method' => 'standard',
            'shipping_carrier' => '4PX',
            'shipping_fee' => 5.99,
        ]);

        $order->items()->create([
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 19.01,
            'subtotal' => 19.01,
        ]);

        return $order;
    }
}
