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
        // 1. Enterprise SMTP Settings Table
        if (! Schema::hasTable('smtp_settings')) {
            Schema::create('smtp_settings', function (Blueprint $table) {
                $table->id();
                $table->string('mail_mailer', 50)->default('smtp');
                $table->string('mail_host')->nullable();
                $table->unsignedInteger('mail_port')->default(587);
                $table->string('mail_username')->nullable();
                $table->text('mail_password')->nullable(); // Encrypted via Crypt::encryptString
                $table->string('mail_encryption', 20)->default('tls'); // tls, ssl, starttls, null
                $table->string('mail_from_address')->nullable();
                $table->string('mail_from_name')->nullable();
                $table->string('mail_reply_to')->nullable();
                $table->boolean('smtp_auth')->default(true);
                $table->unsignedSmallInteger('timeout')->default(30);
                $table->unsignedSmallInteger('rate_limit_per_minute')->default(60);
                $table->boolean('is_enabled')->default(true)->index();
                $table->timestamps();
            });
        }

        // 2. Enhance Email Logs Table if needed
        if (Schema::hasTable('email_logs')) {
            Schema::table('email_logs', function (Blueprint $table) {
                if (! Schema::hasColumn('email_logs', 'email_type')) {
                    $table->string('email_type', 60)->nullable()->index()->after('recipient');
                }
                if (! Schema::hasColumn('email_logs', 'attempts')) {
                    $table->unsignedSmallInteger('attempts')->default(1)->after('status');
                }
                if (! Schema::hasColumn('email_logs', 'idempotency_key')) {
                    $table->string('idempotency_key', 100)->nullable()->index()->after('attempts');
                }
                if (! Schema::hasColumn('email_logs', 'metadata')) {
                    $table->json('metadata')->nullable()->after('error_message');
                }
            });
        }

        // 3. Enhance Notification Settings Table with granular preferences
        if (Schema::hasTable('notification_settings')) {
            Schema::table('notification_settings', function (Blueprint $table) {
                if (! Schema::hasColumn('notification_settings', 'friend_accepted_alerts')) {
                    $table->boolean('friend_accepted_alerts')->default(true)->after('friend_request_alerts');
                }
                if (! Schema::hasColumn('notification_settings', 'message_alerts')) {
                    $table->boolean('message_alerts')->default(true)->after('friend_accepted_alerts');
                }
                if (! Schema::hasColumn('notification_settings', 'like_alerts')) {
                    $table->boolean('like_alerts')->default(true)->after('comment_alerts');
                }
                if (! Schema::hasColumn('notification_settings', 'share_alerts')) {
                    $table->boolean('share_alerts')->default(true)->after('like_alerts');
                }
                if (! Schema::hasColumn('notification_settings', 'follower_alerts')) {
                    $table->boolean('follower_alerts')->default(true)->after('share_alerts');
                }
                if (! Schema::hasColumn('notification_settings', 'page_alerts')) {
                    $table->boolean('page_alerts')->default(true)->after('follower_alerts');
                }
                if (! Schema::hasColumn('notification_settings', 'group_alerts')) {
                    $table->boolean('group_alerts')->default(true)->after('page_alerts');
                }
                if (! Schema::hasColumn('notification_settings', 'login_alerts')) {
                    $table->boolean('login_alerts')->default(true)->after('security_alerts');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('smtp_settings');

        if (Schema::hasTable('email_logs')) {
            Schema::table('email_logs', function (Blueprint $table) {
                $columns = ['email_type', 'attempts', 'idempotency_key', 'metadata'];
                foreach ($columns as $column) {
                    if (Schema::hasColumn('email_logs', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('notification_settings')) {
            Schema::table('notification_settings', function (Blueprint $table) {
                $columns = [
                    'friend_accepted_alerts',
                    'message_alerts',
                    'like_alerts',
                    'share_alerts',
                    'follower_alerts',
                    'page_alerts',
                    'group_alerts',
                    'login_alerts',
                ];
                foreach ($columns as $column) {
                    if (Schema::hasColumn('notification_settings', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
