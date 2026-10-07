<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Page team members and custom RBAC permissions.
     */
    public function up(): void
    {
        Schema::create('page_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->constrained('pages')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('role', 30)->default('moderator'); // owner, admin, manager, content_manager, moderator, analyst, viewer
            $table->json('custom_permissions')->nullable(); // Granular overrides
            $table->string('status', 20)->default('active')->index(); // active, invited, suspended
            $table->foreignId('invited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('invitation_token', 64)->nullable()->unique();
            $table->timestamp('joined_at')->nullable();
            $table->timestamps();

            $table->unique(['page_id', 'user_id']);
            $table->index(['page_id', 'role']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('page_members');
    }
};
