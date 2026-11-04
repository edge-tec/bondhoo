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
        Schema::create('learning_certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained('learning_courses')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('certificate_code', 64)->unique();
            $table->string('verification_token', 128)->unique();
            $table->string('student_name', 150);
            $table->string('course_title', 200);
            $table->string('instructor_name', 150);
            $table->string('pdf_url', 500)->nullable();
            $table->string('qr_code_url', 500)->nullable();
            $table->timestamp('issued_at')->useCurrent();
            $table->boolean('is_revoked')->default(false);
            $table->string('revoked_reason', 255)->nullable();
            $table->timestamps();

            $table->unique(['course_id', 'user_id']);
            $table->index('certificate_code');
            $table->index('user_id');
        });

        Schema::create('learning_course_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained('learning_courses')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->text('content')->nullable();
            $table->text('instructor_reply')->nullable();
            $table->timestamp('replied_at')->nullable();
            $table->integer('helpful_votes')->default(0);
            $table->boolean('is_approved')->default(true);
            $table->timestamps();

            $table->unique(['course_id', 'user_id']);
            $table->index(['course_id', 'rating']);
        });

        Schema::create('learning_wishlists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('course_id')->constrained('learning_courses')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'course_id']);
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('learning_wishlists');
        Schema::dropIfExists('learning_course_reviews');
        Schema::dropIfExists('learning_certificates');
    }
};
