<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_chat_wecom_app_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')
                ->constrained('ai_chat_conversations')
                ->cascadeOnDelete();
            $table->foreignId('ai_chat_message_id')
                ->nullable()
                ->constrained('ai_chat_messages')
                ->nullOnDelete();
            $table->string('wecom_userid', 128)->nullable();
            // MsgId of an inbound operator callback message; used to make
            // WeCom callback retries idempotent. Null for outbound
            // notifications, which carry no MsgId.
            $table->string('msgid', 128)->nullable()->unique();
            $table->string('direction', 32);
            $table->timestamps();

            $table->index('conversation_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_chat_wecom_app_messages');
    }
};
