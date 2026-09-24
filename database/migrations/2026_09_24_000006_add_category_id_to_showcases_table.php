<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('showcases', function (Blueprint $table): void {
            $table->foreignId('category_id')
                ->nullable()
                ->after('image_url')
                ->constrained('showcase_categories')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('showcases', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('category_id');
        });
    }
};
