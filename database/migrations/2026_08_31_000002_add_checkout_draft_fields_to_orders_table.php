<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->string('checkout_token', 64)->nullable()->unique()->after('id');
            $table->string('shipping_address')->nullable()->change();
            $table->string('shipping_city')->nullable()->change();
            $table->string('shipping_zip')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropColumn('checkout_token');
            $table->string('shipping_address')->nullable(false)->change();
            $table->string('shipping_city')->nullable(false)->change();
            $table->string('shipping_zip')->nullable(false)->change();
        });
    }
};
