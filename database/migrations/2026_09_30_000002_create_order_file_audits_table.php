<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_file_audits', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_file_id')->nullable()->constrained()->nullOnDelete();
            $table->string('actor_type', 32)->default('system');
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('action', 64);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['order_id', 'action']);
            $table->index(['actor_type', 'actor_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_file_audits');
    }
};
