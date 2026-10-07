<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * স্টোরিজ টেবিল: ২৪ ঘণ্টার ক্ষণস্থায়ী স্টোরি কন্টেন্ট ধারণ করে।
     */
    public function up(): void
    {
        Schema::create('stories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('type', 20)->default('text'); // text, media
            $table->text('content')->nullable(); // টেক্সট স্টোরির লেখা অথবা ক্যাপশন
            $table->string('background_color', 30)->nullable(); // টেক্সট স্টোরির ব্যাকগ্রাউন্ড কালার/গ্রেডিয়েন্ট
            $table->string('privacy', 20)->default('public')->index(); // public, friends, only_me
            $table->timestamp('expires_at')->index(); // ২৪ ঘণ্টা পর মেয়াদোত্তীর্ণ হওয়ার সময়
            $table->unsignedInteger('views_count')->default(0); // মোট ভিউ সংখ্যা
            $table->timestamps();

            $table->index(['user_id', 'expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stories');
    }
};
