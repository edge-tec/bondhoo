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
        if (! Schema::hasTable('candidate_profiles')) {
            Schema::create('candidate_profiles', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
                $table->string('professional_title', 150)->nullable()->index();
                $table->text('professional_summary')->nullable();
                $table->unsignedTinyInteger('experience_years')->default(0);
                $table->string('current_company', 150)->nullable();
                $table->string('current_position', 150)->nullable();
                $table->unsignedInteger('expected_salary')->nullable();
                $table->string('salary_currency', 10)->default('BDT');
                $table->json('preferred_employment_types')->nullable();
                $table->json('preferred_workplace_types')->nullable();
                $table->json('preferred_locations')->nullable();
                $table->string('availability_status', 30)->default('open_to_work')->index(); // actively_looking, open_to_work, not_looking
                $table->string('profile_visibility', 30)->default('public')->index(); // public, recruiters_only, private
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('candidate_resumes')) {
            Schema::create('candidate_resumes', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('title', 150);
                $table->string('file_path', 255);
                $table->string('original_filename', 255);
                $table->string('mime_type', 100);
                $table->unsignedInteger('file_size');
                $table->boolean('is_default')->default(false)->index();
                $table->string('visibility', 30)->default('recruiters_only')->index(); // public, recruiters_only, private
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('candidate_certifications')) {
            Schema::create('candidate_certifications', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('title', 200);
                $table->string('issuing_organization', 200);
                $table->date('issue_date')->nullable();
                $table->date('expiry_date')->nullable();
                $table->string('credential_id', 100)->nullable();
                $table->string('credential_url', 255)->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('candidate_certifications');
        Schema::dropIfExists('candidate_resumes');
        Schema::dropIfExists('candidate_profiles');
    }
};
