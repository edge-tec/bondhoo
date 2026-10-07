<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_trusted_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->constrained('admins')->cascadeOnDelete();
            $table->string('device_fingerprint', 64)->index();
            $table->string('device_name', 100)->nullable();
            $table->string('browser', 50)->nullable();
            $table->string('os', 50)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('trusted_until')->nullable();
            $table->timestamp('last_used_at')->useCurrent();
            $table->timestamps();

            $table->unique(['admin_id', 'device_fingerprint']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_trusted_devices');
    }
};
