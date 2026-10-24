<?php

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
        Schema::create('job_postings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('page_id')->nullable()->constrained('pages')->nullOnDelete();
            $table->string('title', 200)->index();
            $table->string('company_name', 150)->index();
            $table->string('company_logo', 255)->nullable();
            $table->string('location', 150)->index();
            $table->string('location_type', 30)->default('onsite')->index(); // onsite, remote, hybrid
            $table->string('employment_type', 30)->default('full_time')->index(); // full_time, part_time, contract, internship, freelance
            $table->string('experience_level', 30)->default('mid')->index(); // entry, mid, senior, lead, any
            $table->unsignedInteger('salary_min')->nullable();
            $table->unsignedInteger('salary_max')->nullable();
            $table->string('salary_currency', 10)->default('BDT');
            $table->boolean('salary_negotiable')->default(false);
            $table->text('description');
            $table->text('requirements')->nullable();
            $table->text('benefits')->nullable();
            $table->date('deadline')->nullable()->index();
            $table->string('status', 30)->default('active')->index(); // active, closed, draft
            $table->unsignedInteger('applications_count')->default(0);
            $table->unsignedInteger('views_count')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('job_applications', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('job_posting_id')->constrained('job_postings')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('full_name', 150);
            $table->string('email', 150);
            $table->string('phone', 50);
            $table->text('cover_letter')->nullable();
            $table->string('resume_url', 255)->nullable();
            $table->string('portfolio_url', 255)->nullable();
            $table->unsignedInteger('expected_salary')->nullable();
            $table->string('status', 30)->default('pending')->index(); // pending, shortlisted, interviewed, rejected, hired
            $table->text('admin_notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['job_posting_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_applications');
        Schema::dropIfExists('job_postings');
    }
};
