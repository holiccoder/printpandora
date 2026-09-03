<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('discount_codes', function (Blueprint $table): void {
            $table->boolean('first_order_only')
                ->default(false)
                ->after('max_uses_per_customer');
        });

        $now = now();
        $attributes = [
            'type' => 'percent',
            'value' => 15.00,
            'minimum_subtotal' => 0.00,
            'is_active' => true,
            'starts_at' => null,
            'ends_at' => null,
            'max_uses' => null,
            'max_uses_per_customer' => 1,
            'first_order_only' => true,
            'updated_at' => $now,
        ];

        if (DB::table('discount_codes')->where('code', 'WELCOME15')->exists()) {
            DB::table('discount_codes')
                ->where('code', 'WELCOME15')
                ->update($attributes);
        } else {
            DB::table('discount_codes')->insert([
                'code' => 'WELCOME15',
                ...$attributes,
                'usage_count' => 0,
                'created_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('discount_codes')
            ->where('code', 'WELCOME15')
            ->delete();

        Schema::table('discount_codes', function (Blueprint $table): void {
            $table->dropColumn('first_order_only');
        });
    }
};
