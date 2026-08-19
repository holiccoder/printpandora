<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedInteger('shipping_weight_grams')->nullable()->after('shipping_fee');
            $table->decimal('shipping_length_cm', 10, 2)->nullable()->after('shipping_weight_grams');
            $table->decimal('shipping_width_cm', 10, 2)->nullable()->after('shipping_length_cm');
            $table->decimal('shipping_height_cm', 10, 2)->nullable()->after('shipping_width_cm');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'shipping_weight_grams',
                'shipping_length_cm',
                'shipping_width_cm',
                'shipping_height_cm',
            ]);
        });
    }
};
