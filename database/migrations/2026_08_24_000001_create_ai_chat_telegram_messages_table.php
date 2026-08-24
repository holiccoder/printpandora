<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ai_chat_telegram_messages')) {
            $this->addMissingIndexes();

            return;
        }

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
            $table->unsignedBigInteger('telegram_update_id')->nullable();
            $table->string('direction', 32);
            $table->timestamps();

            $table->unique('telegram_update_id', 'tg_update_unique');
            $table->unique(
                ['telegram_chat_id', 'telegram_message_id'],
                'tg_chat_message_unique',
            );
            $table->index(
                ['conversation_id', 'direction'],
                'tg_conversation_direction_index',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_chat_telegram_messages');
    }

    private function addMissingIndexes(): void
    {
        if (! $this->hasIndex(['telegram_update_id'], true)) {
            Schema::table('ai_chat_telegram_messages', function (Blueprint $table) {
                $table->unique('telegram_update_id', 'tg_update_unique');
            });
        }

        if (! $this->hasIndex(['telegram_chat_id', 'telegram_message_id'], true)) {
            Schema::table('ai_chat_telegram_messages', function (Blueprint $table) {
                $table->unique(
                    ['telegram_chat_id', 'telegram_message_id'],
                    'tg_chat_message_unique',
                );
            });
        }

        if (! $this->hasIndex(['conversation_id', 'direction'], false)) {
            Schema::table('ai_chat_telegram_messages', function (Blueprint $table) {
                $table->index(
                    ['conversation_id', 'direction'],
                    'tg_conversation_direction_index',
                );
            });
        }
    }

    /** @param list<string> $columns */
    private function hasIndex(array $columns, bool $unique): bool
    {
        foreach (Schema::getIndexes('ai_chat_telegram_messages') as $index) {
            if (($index['columns'] ?? []) === $columns
                && (bool) ($index['unique'] ?? false) === $unique) {
                return true;
            }
        }

        return false;
    }
};
