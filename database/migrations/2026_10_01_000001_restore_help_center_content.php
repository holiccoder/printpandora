<?php

use Database\Seeders\HelpCenterSeeder;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Existing installations may have run the earlier migrations before the
        // current help-center content was added. The seeder is idempotent and is
        // the canonical source for restoring those categories, articles, and FAQs.
        app(HelpCenterSeeder::class)->run();
    }

    public function down(): void
    {
        // This migration restores content and must not remove customer-facing
        // help articles or FAQs that may have been edited after deployment.
    }
};
