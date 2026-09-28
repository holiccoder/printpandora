<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // The old workflow had extra design and shipping intermediary states.
        // Fold those states into the six statuses now shown to customers and
        // administrators. A cancelled order remains actionable in the review
        // queue because cancellation is no longer a customer-facing status.
        $statusMap = [
            'processing' => 'production',
            'delivered' => 'shipped',
            'pending_modification' => 'pending_review',
            'pending_production' => 'pending_confirmation',
            'pending_shipment' => 'production',
            'cancelled' => 'pending_review',
        ];

        foreach ($statusMap as $oldStatus => $newStatus) {
            DB::table('orders')
                ->where('status', $oldStatus)
                ->update(['status' => $newStatus]);
        }
    }

    public function down(): void
    {
        $statusMap = [
            'pending_review' => 'pending_modification',
            'pending_confirmation' => 'pending_production',
        ];

        foreach ($statusMap as $newStatus => $oldStatus) {
            DB::table('orders')
                ->where('status', $newStatus)
                ->update(['status' => $oldStatus]);
        }
    }
};
