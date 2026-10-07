<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Page Events and Page Commerce Architecture.
     */
    public function up(): void
    {
        Schema::create('page_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->constrained('pages')->cascadeOnDelete();
            $table->string('title');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->string('cover_image_url')->nullable();
            $table->string('location')->nullable();
            $table->timestamp('start_time');
            $table->timestamp('end_time')->nullable();
            $table->boolean('is_online')->default(false);
            $table->unsignedInteger('rsvp_count')->default(0);
            $table->string('status', 20)->default('published'); // published, cancelled, draft
            $table->timestamps();

            $table->unique(['page_id', 'slug']);
            $table->index(['page_id', 'start_time']);
        });

        Schema::create('page_event_rsvps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('page_events')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('status', 20)->default('going'); // going, interested, not_going
            $table->timestamps();

            $table->unique(['event_id', 'user_id']);
        });

        Schema::create('page_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->constrained('pages')->cascadeOnDelete();
            $table->string('title');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->decimal('price', 12, 2);
            $table->string('currency', 3)->default('BDT');
            $table->integer('stock_quantity')->default(0);
            $table->string('status', 20)->default('active'); // active, draft, out_of_stock, archived
            $table->json('media_urls')->nullable();
            $table->timestamps();

            $table->unique(['page_id', 'slug']);
            $table->index(['page_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('page_products');
        Schema::dropIfExists('page_event_rsvps');
        Schema::dropIfExists('page_events');
    }
};
