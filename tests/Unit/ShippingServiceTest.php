<?php

namespace Tests\Unit;

use App\Services\ShippingService;
use Tests\TestCase;

class ShippingServiceTest extends TestCase
{
    public function test_imported_rates_are_selected_by_destination_country(): void
    {
        $shipping = app(ShippingService::class);

        $this->assertSame(19.88, $shipping->fee('standard', 'US'));
        $this->assertSame(15.96, $shipping->fee('standard', 'ca'));
        $this->assertSame(14.84, $shipping->fee('standard', 'NZ'));
        $this->assertSame(12.6, $shipping->fee('standard', 'GB'));

        $this->assertSame(39.96, $shipping->fee('dhl_express', 'US'));
        $this->assertSame(38.47, $shipping->fee('dhl_express', 'AU'));
        $this->assertSame(38.47, $shipping->fee('dhl_express', 'NZ'));
        $this->assertSame(48.25, $shipping->fee('dhl_express', 'GB'));
    }

    public function test_methods_include_country_rates_for_checkout_updates(): void
    {
        $methods = app(ShippingService::class)->methods('US');

        $this->assertSame(19.88, $methods[0]['fee']);
        $this->assertSame(19.88, $methods[0]['country_rates']['US']);
        $this->assertSame(15.96, $methods[0]['country_rates']['CA']);
        $this->assertSame(39.96, $methods[1]['fee']);
    }

    public function test_imported_weight_tiers_use_the_fixed_interval_rate(): void
    {
        $shipping = app(ShippingService::class);

        $this->assertSame(21.14, $shipping->fee('standard', 'US', 250));
        $this->assertSame(30.12, $shipping->fee('dhl_express', 'US', 250));
        $this->assertSame(30.12, $shipping->fee('dhl_express', 'US', 500));
        $this->assertSame(39.96, $shipping->fee('dhl_express', 'US', 541.6));
        $this->assertSame(49.8, $shipping->fee('dhl_express', 'US', 1001));
        $this->assertSame(343.88, $shipping->fee('dhl_express', 'US', 30100));
        $this->assertSame(826.99, $shipping->fee('dhl_express', 'US', 70100));
        $this->assertSame(21.14, $shipping->fee('standard', 'US', 450));
        $this->assertSame(20.86, $shipping->fee('standard', 'US', 451));
        $this->assertSame(20.86, $shipping->fee('standard', 'US', 541.6));

        $methods = $shipping->methods('CA', 250);

        $this->assertSame(15.54, $methods[0]['fee']);
        $this->assertSame(15.54, $methods[0]['country_rates']['CA']);
    }
}
