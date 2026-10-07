<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * পেজ এবং পেজ ফলোয়ার টেবিলসমূহ তৈরি করে।
     */
    public function up(): void
    {
        // ১. পেজেস টেবিল
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('category', 50)->default('General')->index();
            $table->text('bio')->nullable();
            $table->string('avatar_url')->nullable();
            $table->string('cover_image_url')->nullable();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedInteger('followers_count')->default(0);
            $table->unsignedInteger('posts_count')->default(0);
            $table->boolean('is_verified')->default(false);
            $table->timestamps();
        });

        // ২. পেজ ফলোয়ার টেবিল
        Schema::create('page_followers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->constrained('pages')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['page_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('page_followers');
        Schema::dropIfExists('pages');
    }
};
