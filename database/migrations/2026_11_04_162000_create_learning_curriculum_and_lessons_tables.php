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
        Schema::create('learning_course_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained('learning_courses')->cascadeOnDelete();
            $table->string('title', 200);
            $table->text('description')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_published')->default(true);
            $table->timestamps();

            $table->index(['course_id', 'sort_order']);
        });

        Schema::create('learning_course_lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_id')->constrained('learning_course_sections')->cascadeOnDelete();
            $table->foreignId('course_id')->constrained('learning_courses')->cascadeOnDelete();
            $table->string('title', 200);
            $table->string('slug', 220);
            $table->enum('type', ['video', 'article', 'quiz', 'assignment', 'resource'])->default('video');
            $table->longText('content')->nullable();
            $table->string('video_url', 500)->nullable();
            $table->integer('video_duration_seconds')->default(0);
            $table->integer('sort_order')->default(0);
            $table->boolean('is_preview')->default(false);
            $table->boolean('is_mandatory')->default(true);
            $table->boolean('is_published')->default(true);
            $table->timestamps();

            $table->index(['course_id', 'section_id', 'sort_order']);
        });

        Schema::create('learning_lesson_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_id')->constrained('learning_course_lessons')->cascadeOnDelete();
            $table->string('title', 200);
            $table->string('file_url', 500);
            $table->string('file_type', 50)->default('pdf');
            $table->bigInteger('file_size')->default(0);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index('lesson_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('learning_lesson_attachments');
        Schema::dropIfExists('learning_course_lessons');
        Schema::dropIfExists('learning_course_sections');
    }
};
