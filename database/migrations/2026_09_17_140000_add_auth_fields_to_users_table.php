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
        Schema::table('users', function (Blueprint $table) {
            $table->string('country', 10)->nullable()->after('phone_verified_at');
            $table->date('birth_date')->nullable()->after('country');
            $table->string('gender', 20)->nullable()->after('birth_date'); // male, female, other
            $table->string('referred_by')->nullable()->index()->after('gender');
            $table->string('two_factor_type', 20)->default('app')->after('two_factor_enabled'); // app, email, sms
            $table->unsignedInteger('failed_login_attempts')->default(0)->after('last_login_ip');
            $table->timestamp('locked_until')->nullable()->after('failed_login_attempts');
            $table->timestamp('terms_accepted_at')->nullable()->after('locked_until');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'country',
                'birth_date',
                'gender',
                'referred_by',
                'two_factor_type',
                'failed_login_attempts',
                'locked_until',
                'terms_accepted_at',
            ]);
        });
    }
};
