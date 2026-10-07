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
        Schema::create('user_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('display_name')->nullable();
            $table->string('avatar_url')->nullable();
            $table->string('cover_url')->nullable();
            $table->text('bio')->nullable();
            $table->string('location')->nullable();
            $table->string('website')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('gender', 20)->nullable(); // male, female, other, prefer_not_to_say
            $table->string('work')->nullable();
            $table->string('education')->nullable();
            $table->json('interests')->nullable();
            $table->timestamp('joined_date')->useCurrent();
            $table->timestamps();
        });

        Schema::create('user_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            // Privacy settings
            $table->string('who_can_see_posts', 20)->default('public'); // public, friends, only_me
            $table->string('who_can_send_friend_requests', 20)->default('everyone'); // everyone, friends_of_friends
            $table->string('who_can_follow', 20)->default('everyone'); // everyone, friends
            $table->string('who_can_message', 20)->default('everyone'); // everyone, friends
            $table->string('who_can_see_friends', 20)->default('public'); // public, friends, only_me
            $table->string('find_by_email', 20)->default('everyone'); // everyone, friends, no_one
            $table->string('find_by_phone', 20)->default('everyone'); // everyone, friends, no_one
            $table->string('story_visibility', 20)->default('friends'); // public, friends, only_me
            // Notification preferences
            $table->boolean('notification_email')->default(true);
            $table->boolean('notification_push')->default(true);
            $table->boolean('notification_sms')->default(false);
            // Appearance & localization
            $table->boolean('dark_mode')->default(false);
            $table->string('language', 10)->default('en');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_settings');
        Schema::dropIfExists('user_profiles');
    }
};
