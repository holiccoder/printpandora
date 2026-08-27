<?php

namespace Database\Seeders;

use App\Models\DiscountCode;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DiscountCodeSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $codes = [
            [
                'code' => 'TEST20',
                'type' => 'percent',
                'value' => 20.00,
                'minimum_subtotal' => 0.00,
                'is_active' => true,
                'starts_at' => null,
                'ends_at' => null,
                'max_uses' => null,
                'max_uses_per_customer' => null,
            ],
            [
                'code' => 'SAVE20',
                'type' => 'percent',
                'value' => 20.00,
                'minimum_subtotal' => 0.00,
                'is_active' => true,
                'starts_at' => null,
                'ends_at' => null,
                'max_uses' => null,
                'max_uses_per_customer' => null,
            ],
            [
                'code' => 'WELCOME20',
                'type' => 'percent',
                'value' => 20.00,
                'minimum_subtotal' => 0.00,
                'is_active' => true,
                'starts_at' => null,
                'ends_at' => null,
                'max_uses' => null,
                'max_uses_per_customer' => null,
            ],
        ];

        foreach ($codes as $codeData) {
            DiscountCode::updateOrCreate(
                ['code' => $codeData['code']],
                $codeData
            );
        }
    }
}
