<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('discount_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('type', 20);
            $table->decimal('value', 10, 2);
            $table->decimal('minimum_subtotal', 10, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->unsignedInteger('max_uses')->nullable();
            $table->unsignedInteger('max_uses_per_customer')->nullable();
            $table->unsignedInteger('usage_count')->default(0);
            $table->timestamps();
        });

        Schema::create('discount_redemptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('discount_code_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('order_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('code', 50);
            $table->string('customer_email');
            $table->decimal('subtotal', 10, 2);
            $table->decimal('discount_amount', 10, 2);
            $table->decimal('total', 10, 2);
            $table->timestamps();

            $table->index(['discount_code_id', 'customer_email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discount_redemptions');
        Schema::dropIfExists('discount_codes');
    }
};
