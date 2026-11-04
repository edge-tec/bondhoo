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
        Schema::create('learning_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained('learning_courses')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('status', ['active', 'completed', 'cancelled', 'refunded'])->default('active');
            $table->decimal('price_paid', 10, 2)->default(0.00);
            $table->string('currency', 10)->default('USD');
            $table->timestamp('enrolled_at')->useCurrent();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('last_accessed_at')->nullable();
            $table->timestamps();

            $table->unique(['course_id', 'user_id']);
            $table->index('user_id');
            $table->index('status');
        });

        Schema::create('learning_lesson_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enrollment_id')->nullable()->constrained('learning_enrollments')->cascadeOnDelete();
            $table->foreignId('course_id')->constrained('learning_courses')->cascadeOnDelete();
            $table->foreignId('lesson_id')->constrained('learning_course_lessons')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->boolean('is_completed')->default(false);
            $table->integer('watch_time_seconds')->default(0);
            $table->integer('last_position_seconds')->default(0);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'lesson_id']);
            $table->index(['enrollment_id', 'is_completed']);
        });

        Schema::create('learning_course_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enrollment_id')->unique()->constrained('learning_enrollments')->cascadeOnDelete();
            $table->foreignId('course_id')->constrained('learning_courses')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->integer('completed_lessons_count')->default(0);
            $table->integer('total_lessons_count')->default(0);
            $table->decimal('percentage', 5, 2)->default(0.00);
            $table->boolean('is_completed')->default(false);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'course_id']);
            $table->index('is_completed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('learning_course_progress');
        Schema::dropIfExists('learning_lesson_progress');
        Schema::dropIfExists('learning_enrollments');
    }
};
