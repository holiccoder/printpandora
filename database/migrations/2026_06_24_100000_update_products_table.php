<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('subtitle')->nullable()->after('name');
            $table->string('description_title')->nullable()->after('subtitle');
            $table->json('bullet_points')->nullable()->after('description');
            $table->json('product_options')->nullable()->after('bullet_points');
            $table->text('meta_description')->nullable()->after('product_options');
            $table->string('price_line')->nullable()->after('meta_description');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('price');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('price', 10, 2)->after('description');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'subtitle',
                'price_line',
                'description_title',
                'bullet_points',
                'product_options',
                'meta_description',
            ]);
        });
    }
};
