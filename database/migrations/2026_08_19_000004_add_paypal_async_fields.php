<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('paypal_order_id')->nullable()->unique()->after('payment_id');
        });

        Schema::create('paypal_webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_id')->unique();
            $table->string('event_type');
            $table->string('paypal_order_id')->nullable()->index();
            $table->string('paypal_capture_id')->nullable()->index();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->json('payload');
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paypal_webhook_events');

        Schema::table('orders', function (Blueprint $table) {
            $table->dropUnique(['paypal_order_id']);
            $table->dropColumn('paypal_order_id');
        });
    }
};
