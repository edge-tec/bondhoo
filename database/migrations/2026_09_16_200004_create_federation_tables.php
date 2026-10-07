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
        Schema::create('federated_actors', function (Blueprint $table) {
            $table->id();
            $table->string('actor_uri')->unique();
            $table->string('username');
            $table->string('domain');
            $table->string('name')->nullable();
            $table->text('summary')->nullable();
            $table->string('avatar_url')->nullable();
            $table->string('inbox_url');
            $table->string('outbox_url');
            $table->string('shared_inbox_url')->nullable();
            $table->text('public_key_pem');
            $table->string('public_key_id');
            $table->timestamp('last_fetched_at')->nullable();
            $table->timestamps();

            $table->index(['username', 'domain']);
        });

        Schema::create('federated_activities', function (Blueprint $table) {
            $table->id();
            $table->string('activity_id')->unique();
            $table->string('actor_uri');
            $table->string('type'); // Create, Follow, Like, Announce, Delete, Undo
            $table->string('object_uri')->nullable();
            $table->string('target_inbox_url')->nullable();
            $table->json('payload');
            $table->string('direction')->default('inbound'); // inbound, outbound
            $table->string('status')->default('processed'); // pending, processed, failed
            $table->timestamps();

            $table->index(['actor_uri', 'type']);
            $table->index(['direction', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('federated_activities');
        Schema::dropIfExists('federated_actors');
    }
};
