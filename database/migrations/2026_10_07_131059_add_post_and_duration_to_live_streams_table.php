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
        Schema::table('live_streams', function (Blueprint $table) {
            if (! Schema::hasColumn('live_streams', 'duration')) {
                $table->unsignedInteger('duration')->nullable()->after('ended_at');
            }
            if (! Schema::hasColumn('live_streams', 'generated_post_id')) {
                $table->foreignId('generated_post_id')->nullable()->after('recording_url')->constrained('posts')->nullOnDelete();
            }
            if (! Schema::hasColumn('live_streams', 'last_heartbeat_at')) {
                $table->timestamp('last_heartbeat_at')->nullable()->after('duration');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('live_streams', function (Blueprint $table) {
            $table->dropForeign(['generated_post_id']);
            $table->dropIndex(['status', 'last_heartbeat_at']);
            $table->dropColumn(['duration', 'generated_post_id', 'last_heartbeat_at']);
        });
    }
};
