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
        Schema::create('learning_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_id')->unique()->constrained('learning_course_lessons')->cascadeOnDelete();
            $table->foreignId('course_id')->constrained('learning_courses')->cascadeOnDelete();
            $table->string('title', 200);
            $table->longText('instructions');
            $table->integer('max_score')->default(100);
            $table->integer('passing_score')->default(60);
            $table->integer('due_days_after_enrollment')->nullable();
            $table->boolean('allow_resubmissions')->default(true);
            $table->timestamps();

            $table->index('course_id');
        });

        Schema::create('learning_assignment_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_id')->constrained('learning_assignments')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->longText('submission_text')->nullable();
            $table->string('file_url', 500)->nullable();
            $table->integer('score')->nullable();
            $table->text('feedback')->nullable();
            $table->enum('status', ['submitted', 'graded', 'resubmission_requested'])->default('submitted');
            $table->foreignId('graded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('graded_at')->nullable();
            $table->timestamps();

            $table->index(['assignment_id', 'user_id']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('learning_assignment_submissions');
        Schema::dropIfExists('learning_assignments');
    }
};
