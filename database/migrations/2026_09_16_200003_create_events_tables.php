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
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('creator_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->dateTime('start_time');
            $table->dateTime('end_time')->nullable();
            $table->string('location_type')->default('venue'); // venue, online, hybrid
            $table->string('address')->nullable();
            $table->string('online_link')->nullable();
            $table->string('cover_image')->nullable();
            $table->unsignedInteger('max_attendees')->nullable();
            $table->decimal('ticket_price', 10, 2)->default(0.00);
            $table->unsignedInteger('going_count')->default(0);
            $table->unsignedInteger('interested_count')->default(0);
            $table->timestamps();

            $table->index(['creator_id', 'start_time']);
            $table->index(['start_time', 'location_type']);
        });

        Schema::create('event_attendees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('status')->default('going'); // going, interested, declined
            $table->string('ticket_code')->nullable()->unique();
            $table->boolean('checked_in')->default(false);
            $table->timestamps();

            $table->unique(['event_id', 'user_id']);
            $table->index(['event_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_attendees');
        Schema::dropIfExists('events');
    }
};
