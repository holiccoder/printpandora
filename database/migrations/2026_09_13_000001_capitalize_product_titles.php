<?php

use App\Models\Product;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * @var array<string, string>
     */
    private const TITLES = [
        'basic-cotton-business-card' => 'Basic Cotton Business Card',
        'classic-cotton-business-card' => 'Classic Cotton Business Card',
        'premium-cotton-business-card' => 'Premium Cotton Business Card',
        'luxe-cotton-business-card' => 'Luxe Cotton Business Card',
        'grand-cotton-business-card' => 'Grand Cotton Business Card',
        'standard-pvc-card' => 'Standard PVC Card',
        'premium-pvc-card' => 'Premium PVC Card',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $this->applyTitles(self::TITLES);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->applyTitles([
            'basic-cotton-business-card' => 'Basic cotton business card',
            'classic-cotton-business-card' => 'Classic cotton business card',
            'premium-cotton-business-card' => 'Premium cotton business card',
            'luxe-cotton-business-card' => 'Luxe cotton business card',
            'grand-cotton-business-card' => 'Grand cotton business card',
            'standard-pvc-card' => 'Standard PVC card',
            'premium-pvc-card' => 'Premium PVC card',
        ]);
    }

    /**
     * @param  array<string, string>  $titles
     */
    private function applyTitles(array $titles): void
    {
        foreach ($titles as $slug => $title) {
            $product = Product::query()->where('slug', $slug)->first();

            if (! $product instanceof Product) {
                continue;
            }

            $attributes = ['name' => $title];
            $config = $product->product_config;

            if (is_array($config) && is_array($config['product'] ?? null)) {
                $config['product']['name'] = $title;
                $attributes['product_config'] = $config;
            }

            $product->forceFill($attributes);

            if ($product->isDirty()) {
                $product->saveQuietly();
            }
        }
    }
};
