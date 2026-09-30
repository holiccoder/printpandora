<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_files', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('identifier')->nullable();
            $table->string('path');
            $table->string('original_name');
            $table->string('label')->nullable();
            $table->string('source', 32)->default('customer');
            $table->string('status', 32)->default('awaiting_confirmation');
            $table->unsignedInteger('version')->default(1);
            $table->boolean('is_current')->default(false);
            $table->foreignId('uploaded_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('uploaded_by_admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->foreignId('confirmed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable();
            $table->string('origin_type')->nullable();
            $table->unsignedBigInteger('origin_id')->nullable();
            $table->string('origin_field')->nullable();
            $table->unsignedInteger('origin_index')->nullable();
            $table->timestamps();

            $table->unique(['order_id', 'identifier']);
            $table->index(['order_id', 'status']);
            $table->index(['order_id', 'version']);
            $table->index(['origin_type', 'origin_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_files');
    }
};
