<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->string('invoice_number')->nullable()->unique()->after('payment_id');
            $table->string('invoice_path')->nullable()->after('invoice_number');
            $table->timestamp('invoice_issued_at')->nullable()->after('invoice_path');
            $table->timestamp('invoice_emailed_at')->nullable()->after('invoice_issued_at');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropUnique(['invoice_number']);
            $table->dropColumn([
                'invoice_number',
                'invoice_path',
                'invoice_issued_at',
                'invoice_emailed_at',
            ]);
        });
    }
};
