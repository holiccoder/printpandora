<?php

namespace App\Http\Controllers;

use App\Services\ShippingCalculatorCatalog;
use Inertia\Inertia;
use Inertia\Response;

class ShippingCalculatorController extends Controller
{
    public function show(ShippingCalculatorCatalog $catalog): Response
    {
        return Inertia::render('shipping-calculator', [
            'productOptions' => $catalog->productOptions(),
        ]);
    }
}
