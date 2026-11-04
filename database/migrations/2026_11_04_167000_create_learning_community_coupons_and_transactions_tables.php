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
        Schema::create('learning_discussions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained('learning_courses')->cascadeOnDelete();
            $table->foreignId('lesson_id')->nullable()->constrained('learning_course_lessons')->nullOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('title', 200);
            $table->text('content');
            $table->integer('replies_count')->default(0);
            $table->boolean('is_pinned')->default(false);
            $table->boolean('is_locked')->default(false);
            $table->timestamps();

            $table->index(['course_id', 'lesson_id']);
            $table->index('user_id');
        });

        Schema::create('learning_discussion_replies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('discussion_id')->constrained('learning_discussions')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('content');
            $table->boolean('is_instructor_reply')->default(false);
            $table->boolean('is_solution')->default(false);
            $table->timestamps();

            $table->index('discussion_id');
            $table->index('user_id');
        });

        Schema::create('learning_course_announcements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained('learning_courses')->cascadeOnDelete();
            $table->foreignId('instructor_id')->constrained('users')->cascadeOnDelete();
            $table->string('title', 200);
            $table->text('content');
            $table->timestamps();

            $table->index('course_id');
        });

        Schema::create('learning_course_coupons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->nullable()->constrained('learning_courses')->cascadeOnDelete();
            $table->string('code', 50)->unique();
            $table->enum('discount_type', ['percentage', 'fixed'])->default('percentage');
            $table->decimal('discount_amount', 10, 2);
            $table->integer('max_uses')->default(0);
            $table->integer('times_used')->default(0);
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('is_active');
        });

        Schema::create('learning_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enrollment_id')->nullable()->constrained('learning_enrollments')->nullOnDelete();
            $table->foreignId('course_id')->constrained('learning_courses')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('instructor_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('amount', 10, 2);
            $table->decimal('instructor_earning', 10, 2)->default(0.00);
            $table->decimal('platform_fee', 10, 2)->default(0.00);
            $table->string('currency', 10)->default('USD');
            $table->string('payment_method', 50)->default('wallet');
            $table->string('transaction_reference', 100)->unique();
            $table->enum('status', ['pending', 'completed', 'refunded', 'failed'])->default('completed');
            $table->string('idempotency_key', 100)->nullable()->unique();
            $table->timestamps();

            $table->index(['user_id', 'course_id', 'status']);
            $table->index('instructor_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('learning_transactions');
        Schema::dropIfExists('learning_course_coupons');
        Schema::dropIfExists('learning_course_announcements');
        Schema::dropIfExists('learning_discussion_replies');
        Schema::dropIfExists('learning_discussions');
    }
};
