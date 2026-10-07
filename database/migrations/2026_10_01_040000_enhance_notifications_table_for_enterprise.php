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
        Schema::table('notifications', function (Blueprint $table) {
            if (! Schema::hasColumn('notifications', 'group_key')) {
                $table->string('group_key', 128)->nullable()->after('read_at')->index();
            }

            if (! Schema::hasColumn('notifications', 'category')) {
                $table->string('category', 48)->nullable()->after('group_key')->index();
            }

            $table->index(['notifiable_type', 'notifiable_id', 'read_at'], 'notif_user_read_idx');
            $table->index(['notifiable_type', 'notifiable_id', 'created_at'], 'notif_user_created_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex('notif_user_read_idx');
            $table->dropIndex('notif_user_created_idx');

            if (Schema::hasColumn('notifications', 'category')) {
                $table->dropColumn('category');
            }

            if (Schema::hasColumn('notifications', 'group_key')) {
                $table->dropColumn('group_key');
            }
        });
    }
};
