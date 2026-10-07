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
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->nullableMorphs('mediable');
            $table->string('collection', 50)->default('general')->index(); // profile, cover, post, story, message
            $table->string('disk', 30)->default('public');
            $table->string('original_path');
            $table->string('thumbnail_path')->nullable();
            $table->string('medium_path')->nullable();
            $table->string('large_path')->nullable();
            $table->string('mime_type', 100);
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->unsignedBigInteger('size'); // bytes
            $table->string('checksum', 64)->nullable();
            $table->string('processing_status', 20)->default('pending')->index(); // pending, processing, ready, failed
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('media');
    }
};
