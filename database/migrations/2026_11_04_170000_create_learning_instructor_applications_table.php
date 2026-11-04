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
        if (! Schema::hasTable('learning_instructor_applications')) {
            Schema::create('learning_instructor_applications', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('expertise', 255);
                $table->unsignedInteger('experience_years')->default(1);
                $table->string('portfolio_url', 255)->nullable();
                $table->string('sample_video_url', 255)->nullable();
                $table->text('proposal');
                $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
                $table->foreignId('reviewer_id')->nullable()->constrained('users')->nullOnDelete();
                $table->text('reviewer_feedback')->nullable();
                $table->timestamp('reviewed_at')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'status']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('learning_instructor_applications');
    }
};
