<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * ব্যাকআপ ও ডিজাস্টার রিকভারি টেবিল তৈরি।
     */
    public function up(): void
    {
        Schema::create('backups', function (Blueprint $table) {
            $table->id();
            $table->string('filename')->index();
            $table->string('disk')->default('local');
            $table->string('type', 50)->default('full')->index(); // full, db, media, redis
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->string('status', 50)->default('pending')->index(); // pending, running, completed, failed
            $table->string('checksum', 64)->nullable();
            $table->boolean('encrypted')->default(true);
            $table->timestamp('completed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('backups');
    }
};
