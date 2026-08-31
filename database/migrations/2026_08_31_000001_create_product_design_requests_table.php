<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_design_requests', function (Blueprint $table) {
            $table->id();
            // Keep the requested column name for the product-page JSON payload.
            $table->json('desgin');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_design_requests');
    }
};
