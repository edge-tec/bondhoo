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
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('content')->nullable();
            $table->string('audience', 20)->default('public')->index(); // public, friends, followers, only_me
            $table->string('type', 20)->default('text')->index(); // text, media, link, poll
            $table->string('location')->nullable();
            $table->string('feeling_activity')->nullable();
            $table->json('link_preview')->nullable();
            $table->json('poll_data')->nullable();
            $table->boolean('is_pinned')->default(false)->index();
            $table->boolean('comments_disabled')->default(false);
            $table->unsignedInteger('likes_count')->default(0)->index();
            $table->unsignedInteger('comments_count')->default(0);
            $table->unsignedInteger('shares_count')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'created_at']);
            $table->index(['audience', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
