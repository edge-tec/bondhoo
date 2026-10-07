<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * গ্রুপ ও গ্রুপ মেম্বারশিপ টেবিলসমূহ তৈরি করে।
     */
    public function up(): void
    {
        // ১. গ্রুপস টেবিল
        Schema::create('groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('privacy', 20)->default('public')->index(); // public, private
            $table->string('cover_image_url')->nullable();
            $table->foreignId('creator_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedInteger('members_count')->default(1);
            $table->unsignedInteger('posts_count')->default(0);
            $table->timestamps();
        });

        // ২. গ্রুপ মেম্বারশিপ টেবিল
        Schema::create('group_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('groups')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('role', 20)->default('member'); // admin, moderator, member
            $table->string('status', 20)->default('active')->index(); // active, pending, banned
            $table->timestamp('joined_at')->useCurrent();
            $table->timestamps();

            $table->unique(['group_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('group_members');
        Schema::dropIfExists('groups');
    }
};
