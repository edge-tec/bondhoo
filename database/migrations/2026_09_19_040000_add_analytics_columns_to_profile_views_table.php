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
        Schema::table('profile_views', function (Blueprint $table) {
            if (! Schema::hasColumn('profile_views', 'ip_hash')) {
                $table->string('ip_hash', 64)->nullable()->after('ip_address')->index();
            }
            if (! Schema::hasColumn('profile_views', 'is_anonymous')) {
                $table->boolean('is_anonymous')->default(false)->after('viewer_id');
            }
            if (! Schema::hasColumn('profile_views', 'source')) {
                $table->string('source', 50)->default('direct')->after('device_type');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('profile_views', function (Blueprint $table) {
            $cols = ['ip_hash', 'is_anonymous', 'source'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('profile_views', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
