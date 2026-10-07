<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * কনভার্সন পার্টিসিপেন্টস টেবিল: চ্যাটে অংশগ্রহণকারী ইউজারদের তথ্য, রোল এবং সর্বশেষ পড়া মেসেজ ট্র্যাক করে।
     */
    public function up(): void
    {
        Schema::create('conversation_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('conversations')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('role', 20)->default('member'); // member, admin
            $table->unsignedBigInteger('last_read_message_id')->nullable(); // ইউজার কত নম্বর মেসেজ পর্যন্ত পড়েছেন
            $table->timestamp('last_read_at')->nullable(); // কখন পড়েছেন
            $table->boolean('is_muted')->default(false);
            $table->timestamps();

            // একজন ইউজার একই কনভার্সনে একবারই পার্টিসিপেন্ট হতে পারবেন
            $table->unique(['conversation_id', 'user_id']);
            $table->index(['user_id', 'last_read_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conversation_participants');
    }
};
