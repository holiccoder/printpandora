<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_chat_telegram_messages', function (Blueprint $table) {
            $table->unique(
                ['ai_chat_message_id', 'direction'],
                'ai_chat_telegram_messages_message_direction_unique',
            );
        });
    }

    public function down(): void
    {
        Schema::table('ai_chat_telegram_messages', function (Blueprint $table) {
            $table->dropUnique('ai_chat_telegram_messages_message_direction_unique');
        });
    }
};
