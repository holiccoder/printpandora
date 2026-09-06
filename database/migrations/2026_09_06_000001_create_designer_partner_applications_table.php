<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('designer_partner_applications', function (Blueprint $table) {
            $table->id();
            $table->string('name_or_company');
            $table->string('country_or_region');
            $table->string('email')->index();
            $table->string('website', 2048);
            $table->text('portfolio_links');
            $table->string('design_field');
            $table->text('expected_products_finishes');
            $table->string('status')->default('new')->index();
            $table->text('notes')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->string('user_agent', 1000)->nullable();
            $table->timestamp('handled_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('designer_partner_applications');
    }
};
