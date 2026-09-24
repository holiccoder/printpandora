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
            $table->timestamp('shipped_at')->nullable()->after('tracking_url');
            $table->index('shipped_at');
        });

        // Keep existing orders in the new eight-status workflow. The old
        // "processing" state represents production, while delivered orders
        // remain in the final shipping state because delivery is not tracked
        // as a separate order status anymore.
        DB::table('orders')
            ->where('status', 'processing')
            ->update(['status' => 'production']);

        DB::table('orders')
            ->where('status', 'delivered')
            ->update(['status' => 'shipped']);

        DB::table('orders')
            ->where('status', 'shipped')
            ->whereNull('shipped_at')
            ->update(['shipped_at' => DB::raw('updated_at')]);
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropIndex(['shipped_at']);
            $table->dropColumn('shipped_at');
        });
    }
};
