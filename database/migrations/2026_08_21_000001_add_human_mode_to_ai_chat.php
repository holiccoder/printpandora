<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_chat_conversations', function (Blueprint $table) {
            $table->string('mode')->default('ai')->after('user_id')->index();
            $table->timestamp('human_requested_at')->nullable()->after('mode');
        });

        Schema::table('ai_chat_messages', function (Blueprint $table) {
            $table->string('attachment_path')->nullable()->after('content');
            $table->string('attachment_name')->nullable()->after('attachment_path');
        });
    }

    public function down(): void
    {
        Schema::table('ai_chat_conversations', function (Blueprint $table) {
            $table->dropColumn(['mode', 'human_requested_at']);
        });

        Schema::table('ai_chat_messages', function (Blueprint $table) {
            $table->dropColumn(['attachment_path', 'attachment_name']);
        });
    }
};
