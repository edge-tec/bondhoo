<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * মেসেজেস টেবিল: কনভার্সনের প্রতিটি মেসেজ, ডেলিভারি স্ট্যাটাস (sent, delivered, seen) এবং টাইমস্ট্যাম্প সংরক্ষণ করে।
     */
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('conversations')->cascadeOnDelete();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->string('type', 20)->default('text')->index(); // text, media, system
            $table->text('body')->nullable(); // মেসেজের মূল লেখা
            $table->string('delivery_status', 20)->default('sent')->index(); // sent, delivered, seen
            $table->timestamp('sent_at')->useCurrent();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->json('metadata')->nullable(); // রিপ্লাই বা ক্লায়েন্ট আইডি সংরক্ষণের জন্য
            $table->boolean('is_deleted_for_everyone')->default(false);
            $table->timestamps();

            $table->index(['conversation_id', 'id']);
            $table->index(['sender_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
