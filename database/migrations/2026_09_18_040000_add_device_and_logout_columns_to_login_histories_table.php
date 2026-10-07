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
        Schema::table('login_histories', function (Blueprint $table) {
            $table->string('device', 100)->nullable()->after('user_agent');
            $table->string('session_id')->nullable()->index()->after('is_suspicious');
            $table->timestamp('login_at')->nullable()->after('session_id');
            $table->timestamp('logout_at')->nullable()->after('login_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('login_histories', function (Blueprint $table) {
            $table->dropIndex(['session_id']);
            $table->dropColumn(['device', 'session_id', 'login_at', 'logout_at']);
        });
    }
};
