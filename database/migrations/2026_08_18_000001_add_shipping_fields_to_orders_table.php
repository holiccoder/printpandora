<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('shipping_method')->default('standard')->after('shipping_country');
            $table->string('shipping_carrier')->default('Standard')->after('shipping_method');
            $table->decimal('shipping_fee', 10, 2)->default(0)->after('shipping_carrier');
            $table->string('tracking_number')->nullable()->after('shipping_fee');
            $table->string('tracking_url')->nullable()->after('tracking_number');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'shipping_method',
                'shipping_carrier',
                'shipping_fee',
                'tracking_number',
                'tracking_url',
            ]);
        });
    }
};
