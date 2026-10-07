<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * স্টোরি ভিউজ টেবিল: কোন ইউজার কোন স্টোরি কখন দেখেছেন তা ট্র্যাক করে (ডুপ্লিকেট ভিউ প্রতিরোধ)।
     */
    public function up(): void
    {
        Schema::create('story_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('story_id')->constrained('stories')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('viewed_at')->useCurrent();

            $table->unique(['story_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('story_views');
    }
};
