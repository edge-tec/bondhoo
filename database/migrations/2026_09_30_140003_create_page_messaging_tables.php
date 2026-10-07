<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Dedicated Enterprise Page Messaging:
     * Conversations, Agent Assignment, Internal Staff Notes, Labels, Priority.
     */
    public function up(): void
    {
        Schema::create('page_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->constrained('pages')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete(); // The visitor / customer
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete(); // Page agent
            $table->string('status', 20)->default('open')->index(); // open, pending, resolved, archived
            $table->string('priority', 20)->default('normal'); // low, normal, high, urgent
            $table->timestamp('last_message_at')->nullable()->index();
            $table->unsignedInteger('unread_page_count')->default(0);
            $table->unsignedInteger('unread_user_count')->default(0);
            $table->json('labels')->nullable();
            $table->timestamps();

            $table->unique(['page_id', 'user_id']);
            $table->index(['page_id', 'status', 'last_message_at']);
        });

        Schema::create('page_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('page_conversations')->cascadeOnDelete();
            $table->string('sender_type', 20)->default('user'); // 'page' or 'user'
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->text('body');
            $table->json('attachments')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['conversation_id', 'created_at']);
        });

        Schema::create('page_conversation_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('page_conversations')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete(); // Staff member who wrote internal note
            $table->text('body');
            $table->timestamps();

            $table->index(['conversation_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('page_conversation_notes');
        Schema::dropIfExists('page_messages');
        Schema::dropIfExists('page_conversations');
    }
};
