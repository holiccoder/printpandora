<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('orders')
            ->where('status', 'confirmed')
            ->update(['status' => 'production']);
    }

    public function down(): void
    {
        // Production now includes orders previously held at the confirmed stage.
        // Moving every production order back would incorrectly rewind later work.
    }
};
