<?php

use App\Models\Product;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Set the GSM/material weights used by the product catalog and 4PX data.
     */
    public function up(): void
    {
        $weights = [
            'classic-standard-business-cards' => 300,
            'classic-special-business-cards' => 300,
            'classic-quality-business-cards' => 320,
            'classic-solid-business-cards' => 640,
            'basic-cotton-business-card' => 530,
            'classic-cotton-business-card' => 530,
            'premium-cotton-business-card' => 530,
            'luxe-cotton-business-card' => 530,
            'grand-cotton-business-card' => 530,
            'standard-pvc-card' => 1300,
            'premium-pvc-card' => 1550,
            'basic-pvc-card' => 530,
            'super-business-cards' => 350,
            'luxe-business-cards' => 700,
            'classic-metal-business-cards' => 2400,
            'premium-metal-business-cards' => 2400,
            'luxe-metal-business-cards' => 2400,
        ];

        foreach ($weights as $slug => $weight) {
            Product::query()
                ->where('slug', $slug)
                ->update(['weight' => $weight]);
        }
    }

    /**
     * Product weights are business data; do not erase later manual edits when
     * rolling this migration back.
     */
    public function down(): void
    {
        // Intentionally left blank.
    }
};
