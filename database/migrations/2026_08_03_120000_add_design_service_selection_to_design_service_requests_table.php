<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('design_service_requests', function (Blueprint $table) {
            $table->string('design_service_code')->nullable()->after('business_card_type');
            $table->decimal('design_service_fee', 8, 2)->nullable()->after('design_service_code');
        });
    }

    public function down(): void
    {
        Schema::table('design_service_requests', function (Blueprint $table) {
            $table->dropColumn(['design_service_code', 'design_service_fee']);
        });
    }
};
