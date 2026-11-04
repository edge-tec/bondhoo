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
        Schema::create('learning_quizzes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_id')->unique()->constrained('learning_course_lessons')->cascadeOnDelete();
            $table->foreignId('course_id')->constrained('learning_courses')->cascadeOnDelete();
            $table->string('title', 200);
            $table->text('instructions')->nullable();
            $table->integer('time_limit_minutes')->default(0);
            $table->integer('passing_score')->default(70);
            $table->integer('max_attempts')->default(0);
            $table->boolean('shuffle_questions')->default(true);
            $table->timestamps();

            $table->index('course_id');
        });

        Schema::create('learning_quiz_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_id')->constrained('learning_quizzes')->cascadeOnDelete();
            $table->text('question');
            $table->enum('type', ['multiple_choice', 'multiple_select', 'true_false', 'short_answer'])->default('multiple_choice');
            $table->integer('points')->default(1);
            $table->text('explanation')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['quiz_id', 'sort_order']);
        });

        Schema::create('learning_quiz_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained('learning_quiz_questions')->cascadeOnDelete();
            $table->text('option_text');
            $table->boolean('is_correct')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['question_id', 'sort_order']);
        });

        Schema::create('learning_quiz_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_id')->constrained('learning_quizzes')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->integer('attempt_number')->default(1);
            $table->decimal('score', 5, 2)->default(0.00);
            $table->integer('total_points')->default(0);
            $table->integer('earned_points')->default(0);
            $table->boolean('passed')->default(false);
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->index(['quiz_id', 'user_id', 'attempt_number']);
        });

        Schema::create('learning_quiz_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attempt_id')->constrained('learning_quiz_attempts')->cascadeOnDelete();
            $table->foreignId('question_id')->constrained('learning_quiz_questions')->cascadeOnDelete();
            $table->json('selected_option_ids')->nullable();
            $table->text('text_answer')->nullable();
            $table->boolean('is_correct')->default(false);
            $table->integer('points_awarded')->default(0);
            $table->timestamps();

            $table->index(['attempt_id', 'question_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('learning_quiz_answers');
        Schema::dropIfExists('learning_quiz_attempts');
        Schema::dropIfExists('learning_quiz_options');
        Schema::dropIfExists('learning_quiz_questions');
        Schema::dropIfExists('learning_quizzes');
    }
};
