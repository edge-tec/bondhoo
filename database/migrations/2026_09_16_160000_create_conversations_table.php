<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * কনভার্সন টেবিল: ডিরেক্ট (১-অন-১) এবং গ্রুপ চ্যাটের মূল রেকর্ড ধারণ করে।
     */
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->string('type', 20)->default('direct')->index(); // direct, group
            $table->string('title')->nullable(); // গ্রুপ চ্যাটের শিরোনাম
            $table->foreignId('creator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('last_message_id')->nullable(); // সর্বশেষ মেসেজ আইডি (ইনবক্স প্রভিউয়ের জন্য)
            $table->timestamp('last_message_at')->nullable()->index(); // ইনবক্স সর্টিংয়ের জন্য ইনডেক্স করা
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
