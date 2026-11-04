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
        if (! Schema::hasTable('job_categories')) {
            Schema::create('job_categories', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('parent_id')->nullable()->constrained('job_categories')->cascadeOnDelete();
                $table->string('name', 150)->index();
                $table->string('slug', 170)->unique();
                $table->text('description')->nullable();
                $table->string('status', 30)->default('active')->index(); // active, inactive
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('job_skills')) {
            Schema::create('job_skills', function (Blueprint $table): void {
                $table->id();
                $table->string('name', 100)->unique();
                $table->string('slug', 120)->unique();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('job_job_skill')) {
            Schema::create('job_job_skill', function (Blueprint $table): void {
                $table->foreignId('job_posting_id')->constrained('job_postings')->cascadeOnDelete();
                $table->foreignId('job_skill_id')->constrained('job_skills')->cascadeOnDelete();
                $table->primary(['job_posting_id', 'job_skill_id']);
            });
        }

        if (! Schema::hasTable('application_questions')) {
            Schema::create('application_questions', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('job_posting_id')->constrained('job_postings')->cascadeOnDelete();
                $table->text('question');
                $table->string('type', 30)->default('short_answer'); // short_answer, long_answer, yes_no, single_choice, multiple_choice
                $table->json('options')->nullable();
                $table->boolean('is_required')->default(false);
                $table->unsignedSmallInteger('display_order')->default(0);
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('application_questions');
        Schema::dropIfExists('job_job_skill');
        Schema::dropIfExists('job_skills');
        Schema::dropIfExists('job_categories');
    }
};
