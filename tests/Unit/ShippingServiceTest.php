<?php

namespace Tests\Unit;

use App\Services\ShippingService;
use Tests\TestCase;

class ShippingServiceTest extends TestCase
{
    public function test_imported_rates_are_selected_by_destination_country(): void
    {
        $shipping = app(ShippingService::class);

        $this->assertSame(142.0, $shipping->fee('standard', 'US'));
        $this->assertSame(114.0, $shipping->fee('standard', 'ca'));
        $this->assertSame(106.0, $shipping->fee('standard', 'NZ'));
        $this->assertSame(90.0, $shipping->fee('standard', 'GB'));

        $this->assertSame(201.0, $shipping->fee('dhl_express', 'US'));
        $this->assertSame(193.5, $shipping->fee('dhl_express', 'AU'));
        $this->assertSame(193.5, $shipping->fee('dhl_express', 'NZ'));
        $this->assertSame(242.7, $shipping->fee('dhl_express', 'GB'));
    }

    public function test_methods_include_country_rates_for_checkout_updates(): void
    {
        $methods = app(ShippingService::class)->methods('US');

        $this->assertSame(142.0, $methods[0]['fee']);
        $this->assertSame(142.0, $methods[0]['country_rates']['US']);
        $this->assertSame(114.0, $methods[0]['country_rates']['CA']);
        $this->assertSame(201.0, $methods[1]['fee']);
    }
}
