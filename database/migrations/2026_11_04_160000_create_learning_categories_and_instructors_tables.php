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
        Schema::create('learning_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('learning_categories')->nullOnDelete();
            $table->string('name', 100);
            $table->string('slug', 120)->unique();
            $table->string('icon', 255)->nullable();
            $table->text('description')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->integer('courses_count')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });

        Schema::create('learning_instructors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('headline', 255);
            $table->text('biography')->nullable();
            $table->json('expertise')->nullable();
            $table->string('website_url', 255)->nullable();
            $table->string('linkedin_url', 255)->nullable();
            $table->string('github_url', 255)->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'suspended'])->default('pending');
            $table->boolean('is_verified')->default(false);
            $table->decimal('commission_rate', 5, 2)->default(70.00);
            $table->integer('total_students')->default(0);
            $table->integer('total_courses')->default(0);
            $table->decimal('rating_avg', 3, 2)->default(0.00);
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('is_verified');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('learning_instructors');
        Schema::dropIfExists('learning_categories');
    }
};
