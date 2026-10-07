<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ai_prompts', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('version')->default('1.0.0');
            $table->text('template');
            $table->json('parameters')->nullable();
            $table->string('category')->default('general'); // chat, moderation, translation, caption
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['category', 'is_active']);
        });

        Schema::create('ai_usage_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('provider'); // openai, gemini, claude, ollama
            $table->string('model');
            $table->string('operation'); // chat, search, embedding, moderation, rewrite
            $table->unsignedInteger('prompt_tokens')->default(0);
            $table->unsignedInteger('completion_tokens')->default(0);
            $table->unsignedInteger('total_tokens')->default(0);
            $table->decimal('cost_usd', 10, 6)->default(0.000000);
            $table->unsignedInteger('latency_ms')->default(0);
            $table->string('status')->default('success'); // success, error, cached
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['provider', 'operation']);
            $table->index(['user_id', 'created_at']);
        });

        Schema::create('vector_embeddings', function (Blueprint $table) {
            $table->id();
            $table->string('entity_type'); // post, user, page, group, product, video
            $table->unsignedBigInteger('entity_id');
            $table->string('model')->default('text-embedding-3-small');
            $table->unsignedInteger('dimension')->default(1536);
            $table->json('vector'); // 1536-dim vector stored as JSON array
            $table->text('content_snippet')->nullable();
            $table->timestamps();

            $table->unique(['entity_type', 'entity_id']);
            $table->index(['entity_type', 'dimension']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vector_embeddings');
        Schema::dropIfExists('ai_usage_logs');
        Schema::dropIfExists('ai_prompts');
    }
};
