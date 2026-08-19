<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('fourpx_ref_no')->nullable()->index()->after('tracking_url');
            $table->string('fourpx_consignment_no')->nullable()->after('fourpx_ref_no');
            $table->string('fourpx_tracking_number')->nullable()->after('fourpx_consignment_no');
            $table->string('fourpx_logistics_channel_no')->nullable()->after('fourpx_tracking_number');
            $table->string('fourpx_status')->nullable()->after('fourpx_logistics_channel_no');
            $table->string('fourpx_label_url')->nullable()->after('fourpx_status');
            $table->text('fourpx_last_error')->nullable()->after('fourpx_label_url');
            $table->json('fourpx_response')->nullable()->after('fourpx_last_error');
            $table->json('fourpx_tracking_response')->nullable()->after('fourpx_response');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'fourpx_ref_no',
                'fourpx_consignment_no',
                'fourpx_tracking_number',
                'fourpx_logistics_channel_no',
                'fourpx_status',
                'fourpx_label_url',
                'fourpx_last_error',
                'fourpx_response',
                'fourpx_tracking_response',
            ]);
        });
    }
};
