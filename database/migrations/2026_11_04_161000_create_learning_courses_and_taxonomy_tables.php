<?php

declare(strict_types=1);

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
        Schema::create('learning_courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instructor_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('learning_categories')->nullOnDelete();
            $table->string('title', 200);
            $table->string('slug', 220)->unique();
            $table->string('subtitle', 300)->nullable();
            $table->longText('description');
            $table->enum('level', ['all_levels', 'beginner', 'intermediate', 'expert'])->default('all_levels');
            $table->string('language', 30)->default('en');
            $table->string('thumbnail_url', 500)->nullable();
            $table->string('promotional_video_url', 500)->nullable();
            $table->enum('type', ['free', 'paid', 'private', 'organization'])->default('free');
            $table->decimal('price', 10, 2)->default(0.00);
            $table->decimal('discounted_price', 10, 2)->nullable();
            $table->string('currency', 10)->default('USD');
            $table->enum('status', ['draft', 'pending', 'published', 'suspended', 'archived'])->default('draft');
            $table->json('learning_objectives')->nullable();
            $table->json('prerequisites')->nullable();
            $table->json('target_audience')->nullable();
            $table->integer('duration_minutes')->default(0);
            $table->integer('total_sections')->default(0);
            $table->integer('total_lessons')->default(0);
            $table->integer('enrollments_count')->default(0);
            $table->decimal('rating_avg', 3, 2)->default(0.00);
            $table->integer('rating_count')->default(0);
            $table->boolean('is_featured')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('instructor_id');
            $table->index('category_id');
            $table->index('status');
            $table->index('type');
            $table->index('price');
            $table->index('rating_avg');
            $table->index('is_featured');
            $table->index(['status', 'category_id', 'published_at']);
        });

        Schema::create('learning_course_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained('learning_courses')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('learning_categories')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['course_id', 'category_id']);
        });

        Schema::create('learning_course_skills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained('learning_courses')->cascadeOnDelete();
            $table->string('skill_name', 100);
            $table->timestamps();

            $table->index(['course_id', 'skill_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('learning_course_skills');
        Schema::dropIfExists('learning_course_categories');
        Schema::dropIfExists('learning_courses');
    }
};
