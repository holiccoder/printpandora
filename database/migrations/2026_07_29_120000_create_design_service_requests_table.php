<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('design_service_requests', function (Blueprint $table) {
            $table->id();
            $table->string('email')->index();
            $table->string('business_name');
            $table->text('card_info')->nullable();
            $table->string('business_card_type')->nullable();
            $table->boolean('terms_accepted')->default(false);
            $table->string('logo_path')->nullable();
            $table->json('example_paths')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('handled_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('design_service_requests');
    }
};
