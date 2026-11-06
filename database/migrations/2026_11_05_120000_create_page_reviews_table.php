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
        if (! Schema::hasTable('page_reviews')) {
            Schema::create('page_reviews', function (Blueprint $table) {
                $table->id();
                $table->foreignId('page_id')->constrained('pages')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->unsignedTinyInteger('rating')->default(5)->index();
                $table->text('review_text')->nullable();
                $table->text('reply_text')->nullable();
                $table->timestamps();

                $table->unique(['page_id', 'user_id']);
                $table->index(['page_id', 'created_at']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('page_reviews');
    }
};
