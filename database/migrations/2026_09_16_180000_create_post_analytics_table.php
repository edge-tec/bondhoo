<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * পোস্ট অ্যানালিটিক্স টেবিল: প্রতিটি পোস্টের ইমপ্রেশন, রিচ, ক্লিক এবং এনগেজমেন্ট স্কোর ট্র্যাক করে।
     */
    public function up(): void
    {
        Schema::create('post_analytics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->unique()->constrained('posts')->cascadeOnDelete();
            $table->unsignedInteger('impressions_count')->default(0); // মোট কতবার ফিডে প্রদর্শিত হয়েছে
            $table->unsignedInteger('unique_reach')->default(0); // মোট কতজন স্বতন্ত্র ইউজার দেখেছেন
            $table->unsignedInteger('clicks_count')->default(0); // কতজন বিস্তারিত দেখতে ক্লিক করেছেন
            $table->decimal('engagement_score', 8, 2)->default(0.00); // গণনা করা এনগেজমেন্ট স্কোর
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('post_analytics');
    }
};
