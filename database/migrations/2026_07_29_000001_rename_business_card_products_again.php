<?php

use App\Models\Product;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $renames = [
            'standard-classic-business-card' => [
                'name' => 'Classic Standard Business Cards',
                'slug' => 'classic-standard-business-cards',
            ],
            'classic-lush-business-cards' => [
                'name' => 'Classic Solid Business Cards',
                'slug' => 'classic-solid-business-cards',
            ],
            'classic-special-business-cards' => [
                'name' => 'Classic Special Business Cards',
                'slug' => 'classic-special-business-cards',
            ],
            'classic-quality-business-cards' => [
                'name' => 'Classic Quality Business Cards',
                'slug' => 'classic-quality-business-cards',
            ],
        ];

        foreach ($renames as $oldSlug => $new) {
            Product::where('slug', $oldSlug)->update([
                'name' => $new['name'],
                'slug' => $new['slug'],
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $reverseRenames = [
            'classic-standard-business-cards' => [
                'name' => 'Standard Classic Business card',
                'slug' => 'standard-classic-business-card',
            ],
            'classic-solid-business-cards' => [
                'name' => 'Classic lush business cards',
                'slug' => 'classic-lush-business-cards',
            ],
            'classic-special-business-cards' => [
                'name' => 'Classic special business cards',
                'slug' => 'classic-special-business-cards',
            ],
            'classic-quality-business-cards' => [
                'name' => 'Classic quality business cards',
                'slug' => 'classic-quality-business-cards',
            ],
        ];

        foreach ($reverseRenames as $currentSlug => $old) {
            Product::where('slug', $currentSlug)->update([
                'name' => $old['name'],
                'slug' => $old['slug'],
            ]);
        }
    }
};
