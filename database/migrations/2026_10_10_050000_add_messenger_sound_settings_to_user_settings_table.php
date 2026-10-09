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
        Schema::table('user_settings', function (Blueprint $table) {
            // Master toggle for Messenger sounds
            $table->boolean('messenger_sounds_enabled')->default(true)->after('notification_sms');

            // 1. Incoming Call Ringtone Settings
            $table->boolean('incoming_call_sound_enabled')->default(true)->after('messenger_sounds_enabled');
            $table->string('incoming_call_sound', 64)->default('bondhoo_ring')->after('incoming_call_sound_enabled');
            $table->unsignedTinyInteger('incoming_call_volume')->default(80)->after('incoming_call_sound');

            // 2. Incoming Message Tone Settings
            $table->boolean('incoming_message_sound_enabled')->default(true)->after('incoming_call_volume');
            $table->string('incoming_message_sound', 64)->default('bubble_pop')->after('incoming_message_sound_enabled');
            $table->unsignedTinyInteger('incoming_message_volume')->default(75)->after('incoming_message_sound');

            // 3. Outgoing Message Sent Tone Settings
            $table->boolean('outgoing_message_sound_enabled')->default(true)->after('incoming_message_volume');
            $table->string('outgoing_message_sound', 64)->default('subtle_sent')->after('outgoing_message_sound_enabled');
            $table->unsignedTinyInteger('outgoing_message_volume')->default(70)->after('outgoing_message_sound');

            // Optional custom sound file paths
            $table->string('custom_call_sound_path', 255)->nullable()->after('outgoing_message_volume');
            $table->string('custom_incoming_msg_sound_path', 255)->nullable()->after('custom_call_sound_path');
            $table->string('custom_outgoing_msg_sound_path', 255)->nullable()->after('custom_incoming_msg_sound_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_settings', function (Blueprint $table) {
            $table->dropColumn([
                'messenger_sounds_enabled',
                'incoming_call_sound_enabled',
                'incoming_call_sound',
                'incoming_call_volume',
                'incoming_message_sound_enabled',
                'incoming_message_sound',
                'incoming_message_volume',
                'outgoing_message_sound_enabled',
                'outgoing_message_sound',
                'outgoing_message_volume',
                'custom_call_sound_path',
                'custom_incoming_msg_sound_path',
                'custom_outgoing_msg_sound_path',
            ]);
        });
    }
};
