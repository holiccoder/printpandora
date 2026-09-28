<?php

use Database\Seeders\FlyersAndBrochuresProductSeeder;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        (new FlyersAndBrochuresProductSeeder)->run();
    }

    public function down(): void
    {
        // This is a one-way catalog update. The seeder restores the current
        // canonical configuration if the catalog is rebuilt.
    }
};
