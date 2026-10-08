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
        Schema::table('email_logs', function (Blueprint $table) {
            if (! Schema::hasColumn('email_logs', 'smtp_message_id')) {
                $table->string('smtp_message_id', 255)->nullable()->index()->after('mail_class');
            }
            if (! Schema::hasColumn('email_logs', 'smtp_response')) {
                $table->string('smtp_response', 500)->nullable()->after('smtp_message_id');
            }
            if (! Schema::hasColumn('email_logs', 'from_address')) {
                $table->string('from_address', 255)->nullable()->after('recipient');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('email_logs', function (Blueprint $table) {
            $columns = ['smtp_message_id', 'smtp_response', 'from_address'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('email_logs', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
