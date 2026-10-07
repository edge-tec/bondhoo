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
        // 1. User Two Factor Configurations
        if (! Schema::hasTable('user_two_factor')) {
            Schema::create('user_two_factor', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete()->unique();
                $table->text('secret')->nullable();
                $table->string('type', 30)->default('totp'); // totp, google_authenticator, microsoft_authenticator, sms, email
                $table->boolean('is_enabled')->default(false);
                $table->timestamp('confirmed_at')->nullable();
                $table->timestamp('last_used_at')->nullable();
                $table->timestamps();
            });
        }

        // 2. Backup Recovery Codes
        if (! Schema::hasTable('recovery_codes')) {
            Schema::create('recovery_codes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete()->index();
                $table->string('code_hash');
                $table->timestamp('used_at')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recovery_codes');
        Schema::dropIfExists('user_two_factor');
    }
};
