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
        if (! Schema::hasTable('companies')) {
            Schema::create('companies', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('owner_user_id')->constrained('users')->cascadeOnDelete();
                $table->string('name', 200)->index();
                $table->string('slug', 220)->unique();
                $table->string('logo', 255)->nullable();
                $table->string('cover_image', 255)->nullable();
                $table->text('description')->nullable();
                $table->string('industry', 100)->nullable()->index();
                $table->string('company_size', 50)->nullable();
                $table->unsignedSmallInteger('founded_year')->nullable();
                $table->string('website', 255)->nullable();
                $table->string('email', 150)->nullable();
                $table->string('phone', 50)->nullable();
                $table->string('headquarters', 150)->nullable()->index();
                $table->json('locations')->nullable();
                $table->string('verification_status', 30)->default('unverified')->index(); // unverified, pending, verified, rejected
                $table->string('visibility', 30)->default('public')->index(); // public, unlisted, private
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('company_members')) {
            Schema::create('company_members', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('role', 30)->default('viewer')->index(); // owner, admin, recruiter, hiring_manager, viewer
                $table->json('permissions')->nullable();
                $table->string('invitation_status', 30)->default('accepted')->index(); // pending, accepted, declined
                $table->foreignId('invited_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('joined_at')->nullable();
                $table->timestamps();

                $table->unique(['company_id', 'user_id']);
            });
        }

        if (! Schema::hasTable('company_verifications')) {
            Schema::create('company_verifications', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->json('documents')->nullable();
                $table->string('status', 30)->default('pending')->index(); // pending, approved, rejected, more_info_required
                $table->text('admin_notes')->nullable();
                $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('reviewed_at')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_verifications');
        Schema::dropIfExists('company_members');
        Schema::dropIfExists('companies');
    }
};
