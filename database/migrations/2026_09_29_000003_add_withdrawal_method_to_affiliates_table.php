<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('affiliates', function (Blueprint $table): void {
            $table->string('payment_method')->default('paypal')->after('status');
            $table->string('payment_details')->nullable()->after('payment_method');
        });
    }

    public function down(): void
    {
        Schema::table('affiliates', function (Blueprint $table): void {
            $table->dropColumn(['payment_method', 'payment_details']);
        });
    }
};
