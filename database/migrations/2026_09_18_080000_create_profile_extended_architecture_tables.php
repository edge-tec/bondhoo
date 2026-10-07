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
        // 1. Extend user_profiles with slug and about if not present
        Schema::table('user_profiles', function (Blueprint $table) {
            if (! Schema::hasColumn('user_profiles', 'slug')) {
                $table->string('slug', 100)->nullable()->unique()->after('display_name');
            }
            if (! Schema::hasColumn('user_profiles', 'about')) {
                $table->text('about')->nullable()->after('bio');
            }
        });

        // 2. Profile Educations
        if (! Schema::hasTable('profile_educations')) {
            Schema::create('profile_educations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('institution_name', 191)->index();
                $table->string('degree', 100)->nullable();
                $table->string('field_of_study', 100)->nullable();
                $table->date('start_date')->nullable();
                $table->date('end_date')->nullable();
                $table->boolean('is_current')->default(false);
                $table->string('grade', 50)->nullable();
                $table->text('description')->nullable();
                $table->string('privacy', 20)->default('public'); // public, friends, only_me
                $table->timestamps();
                $table->softDeletes();

                $table->index(['user_id', 'start_date']);
            });
        }

        // 3. Profile Experiences
        if (! Schema::hasTable('profile_experiences')) {
            Schema::create('profile_experiences', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('company_name', 191)->index();
                $table->string('job_title', 150);
                $table->string('employment_type', 50)->nullable(); // full_time, part_time, contract, freelance, internship
                $table->string('location', 150)->nullable();
                $table->boolean('is_remote')->default(false);
                $table->date('start_date')->nullable();
                $table->date('end_date')->nullable();
                $table->boolean('is_current')->default(false);
                $table->text('description')->nullable();
                $table->string('privacy', 20)->default('public'); // public, friends, only_me
                $table->timestamps();
                $table->softDeletes();

                $table->index(['user_id', 'start_date']);
            });
        }

        // 4. Profile Skills
        if (! Schema::hasTable('profile_skills')) {
            Schema::create('profile_skills', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('name', 100);
                $table->string('level', 50)->default('beginner'); // beginner, intermediate, expert
                $table->unsignedInteger('endorsements_count')->default(0);
                $table->unsignedSmallInteger('display_order')->default(0);
                $table->timestamps();

                $table->unique(['user_id', 'name']);
            });
        }

        // 5. Profile Interests
        if (! Schema::hasTable('profile_interests')) {
            Schema::create('profile_interests', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('name', 100);
                $table->string('category', 50)->nullable();
                $table->timestamps();

                $table->unique(['user_id', 'name']);
            });
        }

        // 6. Profile Languages
        if (! Schema::hasTable('profile_languages')) {
            Schema::create('profile_languages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('language', 100);
                $table->string('proficiency', 50)->default('conversational'); // elementary, conversational, fluent, native
                $table->timestamps();

                $table->unique(['user_id', 'language']);
            });
        }

        // 7. Profile Social Links
        if (! Schema::hasTable('profile_social_links')) {
            Schema::create('profile_social_links', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('platform', 50); // facebook, twitter, linkedin, github, youtube, instagram, website
                $table->string('url', 255);
                $table->unsignedSmallInteger('display_order')->default(0);
                $table->boolean('is_visible')->default(true);
                $table->timestamps();

                $table->unique(['user_id', 'platform']);
            });
        }

        // 8. Profile Verifications (Never store raw media - store storage keys/paths & metadata)
        if (! Schema::hasTable('profile_verifications')) {
            Schema::create('profile_verifications', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('verification_type', 50); // nid, passport, driving_license, organizational
                $table->string('document_number', 100)->nullable();
                $table->string('document_front_key', 255)->nullable();
                $table->string('document_back_key', 255)->nullable();
                $table->string('selfie_key', 255)->nullable();
                $table->json('media_meta')->nullable(); // storage_key, mime_type, size, dimensions, cdn_url
                $table->string('status', 20)->default('pending')->index(); // pending, approved, rejected
                $table->foreignId('admin_id')->nullable()->constrained('users')->nullOnDelete();
                $table->text('rejection_reason')->nullable();
                $table->timestamp('submitted_at')->nullable();
                $table->timestamp('reviewed_at')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['user_id', 'status']);
            });
        }

        // 9. Profile Views (Analytics)
        if (! Schema::hasTable('profile_views')) {
            Schema::create('profile_views', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('viewer_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->string('device_type', 50)->nullable(); // desktop, mobile, tablet
                $table->string('referer', 255)->nullable();
                $table->timestamp('viewed_at')->useCurrent();
                $table->timestamps();

                $table->index(['user_id', 'viewed_at']);
                $table->index(['user_id', 'viewer_id']);
            });
        }

        // 10. Profile Completions
        if (! Schema::hasTable('profile_completions')) {
            Schema::create('profile_completions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
                $table->unsignedTinyInteger('completion_percentage')->default(0);
                $table->boolean('has_avatar')->default(false);
                $table->boolean('has_cover')->default(false);
                $table->boolean('has_bio')->default(false);
                $table->boolean('has_about')->default(false);
                $table->boolean('has_education')->default(false);
                $table->boolean('has_experience')->default(false);
                $table->boolean('has_skills')->default(false);
                $table->boolean('has_social_links')->default(false);
                $table->boolean('has_location')->default(false);
                $table->json('missing_sections')->nullable();
                $table->timestamp('last_calculated_at')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('profile_completions');
        Schema::dropIfExists('profile_views');
        Schema::dropIfExists('profile_verifications');
        Schema::dropIfExists('profile_social_links');
        Schema::dropIfExists('profile_languages');
        Schema::dropIfExists('profile_interests');
        Schema::dropIfExists('profile_skills');
        Schema::dropIfExists('profile_experiences');
        Schema::dropIfExists('profile_educations');

        Schema::table('user_profiles', function (Blueprint $table) {
            if (Schema::hasColumn('user_profiles', 'slug')) {
                $table->dropUnique(['slug']);
                $table->dropColumn('slug');
            }
            if (Schema::hasColumn('user_profiles', 'about')) {
                $table->dropColumn('about');
            }
        });
    }
};
