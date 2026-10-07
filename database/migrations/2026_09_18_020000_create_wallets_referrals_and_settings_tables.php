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
        // Add first_name and last_name to users table if not existing
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'first_name')) {
                $table->string('first_name', 100)->nullable()->after('id');
            }
            if (! Schema::hasColumn('users', 'last_name')) {
                $table->string('last_name', 100)->nullable()->after('first_name');
            }
        });

        // Add first_name and last_name to user_profiles table if not existing
        Schema::table('user_profiles', function (Blueprint $table) {
            if (! Schema::hasColumn('user_profiles', 'first_name')) {
                $table->string('first_name', 100)->nullable()->after('display_name');
            }
            if (! Schema::hasColumn('user_profiles', 'last_name')) {
                $table->string('last_name', 100)->nullable()->after('first_name');
            }
        });

        // Wallets table
        if (! Schema::hasTable('wallets')) {
            Schema::create('wallets', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
                $table->decimal('balance', 12, 2)->default(0.00);
                $table->decimal('pending_balance', 12, 2)->default(0.00);
                $table->string('currency', 10)->default('BDT');
                $table->string('status', 20)->default('active'); // active, locked, suspended
                $table->timestamps();
            });
        }

        // Referrals table
        if (! Schema::hasTable('referrals')) {
            Schema::create('referrals', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
                $table->foreignId('referrer_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('referral_code', 32)->unique();
                $table->boolean('reward_claimed')->default(false);
                $table->decimal('reward_amount', 10, 2)->default(0.00);
                $table->string('status', 20)->default('active'); // active, pending, completed
                $table->timestamps();
            });
        }

        // Privacy Settings table
        if (! Schema::hasTable('privacy_settings')) {
            Schema::create('privacy_settings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
                $table->string('profile_visibility', 20)->default('public'); // public, friends, only_me
                $table->string('post_default_privacy', 20)->default('public'); // public, friends, only_me
                $table->string('friends_list_visibility', 20)->default('public'); // public, friends, only_me
                $table->boolean('search_engine_indexing')->default(true);
                $table->string('phone_visibility', 20)->default('friends'); // everyone, friends, only_me
                $table->string('email_visibility', 20)->default('only_me'); // everyone, friends, only_me
                $table->string('birthday_visibility', 20)->default('friends'); // public, friends, only_me
                $table->timestamps();
            });
        }

        // Notification Settings table
        if (! Schema::hasTable('notification_settings')) {
            Schema::create('notification_settings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
                $table->boolean('email_notifications')->default(true);
                $table->boolean('sms_notifications')->default(false);
                $table->boolean('push_notifications')->default(true);
                $table->boolean('friend_request_alerts')->default(true);
                $table->boolean('comment_alerts')->default(true);
                $table->boolean('mention_alerts')->default(true);
                $table->boolean('security_alerts')->default(true);
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_settings');
        Schema::dropIfExists('privacy_settings');
        Schema::dropIfExists('referrals');
        Schema::dropIfExists('wallets');

        Schema::table('user_profiles', function (Blueprint $table) {
            if (Schema::hasColumn('user_profiles', 'last_name')) {
                $table->dropColumn('last_name');
            }
            if (Schema::hasColumn('user_profiles', 'first_name')) {
                $table->dropColumn('first_name');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'last_name')) {
                $table->dropColumn('last_name');
            }
            if (Schema::hasColumn('users', 'first_name')) {
                $table->dropColumn('first_name');
            }
        });
    }
};
