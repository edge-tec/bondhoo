<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * পোস্ট টেবিলে গ্রুপ আইডি ও পেজ আইডি যোগ করে, যাতে পোস্টগুলো কোনো গ্রুপ বা পেজের অধীন হতে পারে।
     */
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->foreignId('group_id')->nullable()->after('user_id')->constrained('groups')->nullOnDelete();
            $table->foreignId('page_id')->nullable()->after('group_id')->constrained('pages')->nullOnDelete();

            $table->index(['group_id', 'created_at']);
            $table->index(['page_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropForeign(['group_id']);
            $table->dropForeign(['page_id']);
            $table->dropColumn(['group_id', 'page_id']);
        });
    }
};
