<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_chat_messages', function (Blueprint $table) {
            $table->text('translated_content')->nullable()->after('content');
            $table->string('translation_target', 10)->nullable()->after('translated_content');
        });
    }

    public function down(): void
    {
        Schema::table('ai_chat_messages', function (Blueprint $table) {
            $table->dropColumn(['translated_content', 'translation_target']);
        });
    }
};
