<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_chat_telegram_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')
                ->constrained('ai_chat_conversations')
                ->cascadeOnDelete();
            $table->foreignId('ai_chat_message_id')
                ->nullable()
                ->constrained('ai_chat_messages')
                ->nullOnDelete();
            $table->string('telegram_chat_id', 64);
            $table->unsignedBigInteger('telegram_message_id');
            $table->unsignedBigInteger('telegram_update_id')->nullable()->unique();
            $table->string('direction', 32);
            $table->timestamps();

            $table->unique(['telegram_chat_id', 'telegram_message_id']);
            $table->index(['conversation_id', 'direction']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_chat_telegram_messages');
    }
};
