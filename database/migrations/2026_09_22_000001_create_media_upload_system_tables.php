<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Media Upload & Processing System:
     * Resumable chunked upload sessions, chunk tracking, async processing jobs, and storage quotas.
     */
    public function up(): void
    {
        // 1. Upload Sessions Table
        Schema::create('media_upload_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('session_id', 64)->unique();
            $table->string('collection', 40)->default('general'); // story, reel, post, profile, cover
            $table->string('filename');
            $table->string('original_name')->nullable();
            $table->string('mime_type', 120);
            $table->unsignedBigInteger('file_size'); // Total expected bytes
            $table->unsignedInteger('chunk_size'); // Bytes per chunk
            $table->unsignedInteger('total_chunks');
            $table->unsignedInteger('uploaded_chunks_count')->default(0);
            $table->string('status', 30)->default('initialized')->index(); // initialized, uploading, paused, assembling, completed, failed, cancelled
            $table->string('checksum', 64)->nullable(); // SHA-256
            $table->string('target_path')->nullable();
            $table->string('temp_dir')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('expires_at')->index();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });

        // 2. Upload Chunks Table
        Schema::create('media_upload_chunks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('upload_session_id')->constrained('media_upload_sessions')->cascadeOnDelete();
            $table->unsignedInteger('chunk_number');
            $table->unsignedBigInteger('chunk_size');
            $table->string('chunk_checksum', 64)->nullable();
            $table->string('temp_path');
            $table->string('status', 20)->default('uploaded'); // uploaded, verified, failed
            $table->timestamp('uploaded_at')->useCurrent();

            $table->unique(['upload_session_id', 'chunk_number']);
        });

        // 3. Media Processing Jobs Table
        Schema::create('media_processing_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->string('job_uuid', 64)->unique();
            $table->string('job_type', 50)->index(); // video_transcode, thumbnail_generate, image_optimize, story_cleanup
            $table->string('status', 30)->default('queued')->index(); // queued, processing, completed, failed, cancelled
            $table->unsignedTinyInteger('progress')->default(0); // 0-100
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->text('error_message')->nullable();
            $table->json('resource_metrics')->nullable(); // memory_peak, cpu_duration, duration_ms
            $table->json('payload')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'job_type']);
        });

        // 4. User Storage Quotas Table
        Schema::create('user_storage_quotas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('tier', 30)->default('free'); // free, basic, pro, enterprise
            $table->unsignedBigInteger('max_storage_bytes')->default(1073741824); // 1 GB default
            $table->unsignedBigInteger('used_storage_bytes')->default(0);
            $table->unsignedBigInteger('max_video_size_bytes')->default(104857600); // 100 MB default
            $table->unsignedBigInteger('max_image_size_bytes')->default(20971520); // 20 MB default
            $table->unsignedInteger('max_daily_uploads')->default(50);
            $table->unsignedInteger('today_uploads_count')->default(0);
            $table->date('last_upload_date')->nullable();
            $table->boolean('is_unlimited')->default(false);
            $table->timestamps();
        });

        // 5. Media System Settings Table (Admin Configurable)
        Schema::create('media_system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 64)->unique();
            $table->text('value')->nullable();
            $table->string('type', 20)->default('string'); // string, integer, boolean, json
            $table->string('group', 30)->default('general'); // limits, workers, cdn, storage
            $table->string('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('media_system_settings');
        Schema::dropIfExists('user_storage_quotas');
        Schema::dropIfExists('media_processing_jobs');
        Schema::dropIfExists('media_upload_chunks');
        Schema::dropIfExists('media_upload_sessions');
    }
};
