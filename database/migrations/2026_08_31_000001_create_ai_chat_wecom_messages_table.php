<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_chat_wecom_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('channel_id')
                ->constrained('ai_chat_channels')
                ->cascadeOnDelete();
            $table->string('msgid', 128)->unique();
            $table->foreignId('ai_chat_message_id')
                ->nullable()
                ->constrained('ai_chat_messages')
                ->nullOnDelete();
            $table->timestamps();

            $table->index('channel_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_chat_wecom_messages');
    }
};
