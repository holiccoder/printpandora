<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->timestamp('confirmation_requested_at')->nullable()->after('status');
            $table->timestamp('confirmation_reminded_at')->nullable()->after('confirmation_requested_at');
            $table->index(['status', 'confirmation_requested_at']);
        });

        DB::table('orders')
            ->where('status', 'pending_confirmation')
            ->where('payment_status', 'paid')
            ->whereNull('confirmation_requested_at')
            ->update(['confirmation_requested_at' => DB::raw('updated_at')]);
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropIndex(['status', 'confirmation_requested_at']);
            $table->dropColumn([
                'confirmation_requested_at',
                'confirmation_reminded_at',
            ]);
        });
    }
};
