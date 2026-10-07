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
            if (! Schema::hasColumn('live_streams', 'sfu_room_id')) {
                $table->string('sfu_room_id')->nullable()->after('playback_url');
            }
            if (! Schema::hasColumn('live_streams', 'sfu_provider')) {
                $table->string('sfu_provider')->default('gateway')->after('sfu_room_id');
            }
            if (! Schema::hasColumn('live_streams', 'sfu_host_token')) {
                $table->text('sfu_host_token')->nullable()->after('sfu_provider');
            }
            if (! Schema::hasColumn('live_streams', 'recording_file_path')) {
                $table->string('recording_file_path')->nullable()->after('recording_url');
            }
            if (! Schema::hasColumn('live_streams', 'error_message')) {
                $table->text('error_message')->nullable()->after('health_stats');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('live_streams', function (Blueprint $table) {
            $table->dropColumn([
                'sfu_room_id',
                'sfu_provider',
                'sfu_host_token',
                'recording_file_path',
                'error_message',
            ]);
        });
    }
};
