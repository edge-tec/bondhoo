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
        if (! Schema::hasTable('interviews')) {
            Schema::create('interviews', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('application_id')->constrained('job_applications')->cascadeOnDelete();
                $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
                $table->foreignId('scheduled_by')->constrained('users')->cascadeOnDelete();
                $table->string('interview_type', 30)->default('video')->index(); // video, phone, in_person
                $table->timestamp('scheduled_at')->index();
                $table->unsignedSmallInteger('duration_minutes')->default(45);
                $table->string('timezone', 50)->default('Asia/Dhaka');
                $table->string('meeting_url', 255)->nullable();
                $table->string('location', 255)->nullable();
                $table->text('instructions')->nullable();
                $table->string('status', 30)->default('scheduled')->index(); // scheduled, confirmed, rescheduled, completed, cancelled, no_show
                $table->text('feedback')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('saved_jobs')) {
            Schema::create('saved_jobs', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('job_id')->constrained('job_postings')->cascadeOnDelete();
                $table->timestamps();

                $table->unique(['user_id', 'job_id']);
            });
        }

        if (! Schema::hasTable('job_alerts')) {
            Schema::create('job_alerts', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('title', 150);
                $table->string('keywords', 255)->nullable();
                $table->foreignId('category_id')->nullable()->constrained('job_categories')->nullOnDelete();
                $table->json('locations')->nullable();
                $table->json('employment_types')->nullable();
                $table->json('workplace_types')->nullable();
                $table->string('experience_level', 30)->nullable();
                $table->string('frequency', 20)->default('daily'); // instant, daily, weekly
                $table->boolean('is_active')->default(true)->index();
                $table->timestamp('last_notified_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('job_views')) {
            Schema::create('job_views', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('job_id')->constrained('job_postings')->cascadeOnDelete();
                $table->foreignId('viewer_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('ip_address', 45)->nullable();
                $table->string('source', 50)->default('direct');
                $table->timestamp('viewed_at')->useCurrent()->index();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_views');
        Schema::dropIfExists('job_alerts');
        Schema::dropIfExists('saved_jobs');
        Schema::dropIfExists('interviews');
    }
};
