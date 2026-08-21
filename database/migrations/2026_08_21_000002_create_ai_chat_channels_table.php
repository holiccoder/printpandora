<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_chat_channels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')
                ->constrained('ai_chat_conversations')
                ->cascadeOnDelete();
            $table->string('provider', 32);
            // Telegram chat IDs can be larger than a signed 32-bit integer;
            // strings also preserve negative group IDs without casting issues.
            $table->string('external_chat_id', 64)->nullable();
            $table->string('external_user_id', 64)->nullable();
            $table->string('external_username')->nullable();
            $table->string('link_token_hash', 64)->nullable()->unique();
            $table->timestamp('link_token_expires_at')->nullable();
            $table->unsignedBigInteger('last_update_id')->nullable();
            $table->timestamps();

            $table->unique(['provider', 'external_chat_id']);
            $table->index(['conversation_id', 'provider']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_chat_channels');
    }
};
