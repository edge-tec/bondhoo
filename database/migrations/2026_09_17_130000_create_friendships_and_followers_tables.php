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
        Schema::create('friendships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('friend_id')->constrained('users')->cascadeOnDelete();
            $table->enum('status', ['pending', 'accepted', 'declined', 'blocked'])->default('pending');
            $table->timestamps();

            $table->unique(['user_id', 'friend_id']);
            $table->index(['user_id', 'status']);
            $table->index(['friend_id', 'status']);
        });

        Schema::create('user_followers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete(); // The user being followed
            $table->foreignId('follower_id')->constrained('users')->cascadeOnDelete(); // The follower
            $table->timestamps();

            $table->unique(['user_id', 'follower_id']);
            $table->index('user_id');
            $table->index('follower_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_followers');
        Schema::dropIfExists('friendships');
    }
};
